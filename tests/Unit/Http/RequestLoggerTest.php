<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit\Http;

use CoffeeLab\Http\RequestLogger;
use PHPUnit\Framework\TestCase;

final class RequestLoggerTest extends TestCase
{
    public function testWritesStructuredRequestRecord(): void
    {
        $lines = [];
        $logger = new RequestLogger(static function (string $line) use (&$lines): bool {
            $lines[] = $line;
            return true;
        });

        $logger->log('GET', '/coffees/{id}', 200, 12.34567, 'request-123');

        self::assertCount(1, $lines);
        $record = json_decode($lines[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertMatchesRegularExpression(
            '/\A\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\.\d{6}Z\z/',
            $record['timestamp'],
        );
        self::assertSame([
            'level' => 'info',
            'method' => 'GET',
            'route' => '/coffees/{id}',
            'status' => 200,
            'duration_ms' => 12.346,
            'correlation_id' => 'request-123',
        ], array_diff_key($record, ['timestamp' => true]));
    }

    public function testNormalizesRoutesWithoutLoggingIdentifiersOrQueryStrings(): void
    {
        self::assertSame('/health', RequestLogger::normalizeRoute('/health'));
        self::assertSame('/coffees', RequestLogger::normalizeRoute('/coffees'));
        self::assertSame('/coffees/{id}', RequestLogger::normalizeRoute(
            '/coffees/ecf23a07-8f6c-447d-bca4-fc3d5d1c6b80',
        ));
        self::assertSame('unmatched', RequestLogger::normalizeRoute('/private/user@example.com?token=secret'));
    }

    public function testNormalizesMethodAndCorrelationId(): void
    {
        self::assertSame('GET', RequestLogger::normalizeMethod('get'));
        self::assertSame('OTHER', RequestLogger::normalizeMethod("GET\r\nX-Injected: yes"));
        self::assertSame(
            'ecf23a07-8f6c-447d-bca4-fc3d5d1c6b80',
            RequestLogger::correlationId('ecf23a07-8f6c-447d-bca4-fc3d5d1c6b80'),
        );
        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            RequestLogger::correlationId("bad\r\nid"),
        );
        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            RequestLogger::correlationId(str_repeat('a', 65)),
        );
    }

    public function testMapsFailureStatusesToLogLevels(): void
    {
        self::assertSame('info', RequestLogger::levelForStatus(399));
        self::assertSame('warning', RequestLogger::levelForStatus(422));
        self::assertSame('error', RequestLogger::levelForStatus(500));
    }

    public function testReportsLogWriterFailure(): void
    {
        $logger = new RequestLogger(static fn (string $line): bool => false);

        self::assertFalse($logger->log('GET', '/health', 200, 1.0, 'ecf23a07-8f6c-447d-bca4-fc3d5d1c6b80'));
    }

    public function testSerializationAndWriterExceptionsDoNotEscape(): void
    {
        $writerCalled = false;
        $logger = new RequestLogger(static function (string $line) use (&$writerCalled): bool {
            $writerCalled = true;
            throw new \RuntimeException('writer failure');
        });

        self::assertFalse($logger->log('GET', '/health', 200, 1.0, 'request-id'));
        self::assertTrue($writerCalled);

        $writerCalled = false;
        $logger = new RequestLogger(static function (string $line) use (&$writerCalled): bool {
            $writerCalled = true;
            return true;
        });

        self::assertFalse($logger->log("GET\xB1", '/health', 200, 1.0, 'request-id'));
        self::assertFalse($writerCalled);
    }
}
