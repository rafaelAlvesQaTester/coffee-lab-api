<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function testGetHealthReturnsExpectedJsonResponse(): void
    {
        $configuredBaseUrl = getenv('COFFEE_LAB_API_URL');
        $baseUrl = is_string($configuredBaseUrl) && $configuredBaseUrl !== ''
            ? $configuredBaseUrl
            : 'http://127.0.0.1:8083';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents(
            rtrim($baseUrl, '/') . '/health',
            false,
            $context,
        );
        $responseHeaders = $http_response_header ?? [];

        self::assertNotFalse(
            $responseBody,
            'Could not reach the API. Start the Docker environment before running integration tests.',
        );

        $statusLine = $responseHeaders[0] ?? '';
        self::assertMatchesRegularExpression('/\s200(?:\s|$)/', $statusLine);

        $contentType = null;
        foreach ($responseHeaders as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $contentType = trim(substr($header, strlen('Content-Type:')));
                break;
            }
        }

        self::assertNotNull($contentType, 'The response must include a Content-Type header.');
        self::assertStringStartsWith('application/json', strtolower($contentType));
        self::assertSame(
            [
                'status' => 'ok',
                'service' => 'coffee-lab-api',
            ],
            json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
