<?php

declare(strict_types=1);

namespace CoffeeLab\Http;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class RequestLogger
{
    private readonly Closure $writer;

    public function __construct(?Closure $writer = null)
    {
        $this->writer = $writer ?? static fn (string $line): bool => error_log($line);
    }

    public static function normalizeMethod(?string $method): string
    {
        if ($method === null || preg_match('/\A[A-Za-z]{1,16}\z/', $method) !== 1) {
            return 'OTHER';
        }

        return strtoupper($method);
    }

    public static function normalizeRoute(?string $path): string
    {
        if ($path === '/health' || $path === '/coffees') {
            return $path;
        }

        if ($path !== null && preg_match('/\A\/coffees\/[^\/]+\z/', $path) === 1) {
            return '/coffees/{id}';
        }

        return 'unmatched';
    }

    public static function correlationId(?string $candidate): string
    {
        if (
            $candidate !== null
            && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i', $candidate) === 1
        ) {
            return $candidate;
        }

        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }

    public static function levelForStatus(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        }

        if ($statusCode >= 400) {
            return 'warning';
        }

        return 'info';
    }

    public function log(
        string $method,
        string $route,
        int $statusCode,
        float $durationMs,
        string $correlationId,
        ?string $errorType = null,
    ): bool {
        try {
            if (!is_finite($durationMs) || $durationMs < 0) {
                return false;
            }

            $record = [
                'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                    ->format('Y-m-d\TH:i:s.u\Z'),
                'level' => self::levelForStatus($statusCode),
                'method' => $method,
                'route' => $route,
                'status' => $statusCode,
                'duration_ms' => round($durationMs, 3),
                'correlation_id' => $correlationId,
            ];

            if ($errorType !== null) {
                $record['error_type'] = $errorType;
            }

            $line = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

            return (bool) ($this->writer)($line);
        } catch (Throwable) {
            return false;
        }
    }
}
