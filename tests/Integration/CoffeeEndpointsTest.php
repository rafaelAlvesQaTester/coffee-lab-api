<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class CoffeeEndpointsTest extends TestCase
{
    public function testPostCoffeesReturnsCreatedCoffee(): void
    {
        // Arrange
        $payload = json_encode([
            'name' => 'Integration coffee',
            'origin' => 'Brazil',
            'roastLevel' => 'medium',
        ], JSON_THROW_ON_ERROR);

        // Act
        $response = $this->request('POST', '/coffees', $payload);

        // Assert
        self::assertSame(201, $response['status']);
        self::assertStringStartsWith('application/json', strtolower($response['contentType']));
        self::assertSame(
            ['id', 'name', 'origin', 'roastLevel', 'createdAt'],
            array_keys($response['body']),
        );
        self::assertSame('Integration coffee', $response['body']['name']);
        self::assertIsString($response['body']['createdAt']);
    }

    public function testGetCoffeesReturnsJsonList(): void
    {
        // Arrange / Act
        $response = $this->request('GET', '/coffees');

        // Assert
        self::assertSame(200, $response['status']);
        self::assertStringStartsWith('application/json', strtolower($response['contentType']));
        self::assertIsArray($response['body']);
        self::assertSame(array_values($response['body']), $response['body']);

        foreach ($response['body'] as $coffee) {
            self::assertSame(
                ['id', 'name', 'origin', 'roastLevel', 'createdAt'],
                array_keys($coffee),
            );
        }
    }

    public function testGetCoffeeReturnsNotFoundForValidUnknownUuid(): void
    {
        // Arrange / Act
        $response = $this->request('GET', '/coffees/aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918');

        // Assert
        self::assertSame(404, $response['status']);
        self::assertSame(['status' => 'error', 'message' => 'Coffee not found.'], $response['body']);
    }

    public function testGetCoffeeReturnsBadRequestForInvalidUuid(): void
    {
        // Arrange / Act
        $response = $this->request('GET', '/coffees/not-a-uuid');

        // Assert
        self::assertSame(400, $response['status']);
        self::assertSame(['status' => 'error', 'message' => 'Invalid coffee id.'], $response['body']);
    }

    public function testPostCoffeesReturnsBadRequestForMalformedJson(): void
    {
        // Arrange / Act
        $response = $this->request('POST', '/coffees', '{"name":');

        // Assert
        self::assertSame(400, $response['status']);
        self::assertSame(['status' => 'error', 'message' => 'Malformed JSON body.'], $response['body']);
    }

    public function testPostCoffeesReturnsUnprocessableEntityForInvalidData(): void
    {
        // Arrange
        $payload = json_encode([
            'name' => 'Coffee',
            'origin' => 'Brazil',
            'roastLevel' => 'very-dark',
        ], JSON_THROW_ON_ERROR);

        // Act
        $response = $this->request('POST', '/coffees', $payload);

        // Assert
        self::assertSame(422, $response['status']);
        self::assertSame(['status' => 'error', 'message' => 'Invalid coffee data.'], $response['body']);
    }

    /**
     * @return array{status: int, contentType: string, body: array<string, mixed>|list<mixed>}
     */
    private function request(string $method, string $path, ?string $body = null): array
    {
        $configuredBaseUrl = getenv('COFFEE_LAB_API_URL');
        $baseUrl = is_string($configuredBaseUrl) && $configuredBaseUrl !== ''
            ? $configuredBaseUrl
            : 'http://127.0.0.1:8083';
        $headers = ['Accept: application/json'];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = @file_get_contents(
            rtrim($baseUrl, '/') . $path,
            false,
            $context,
        );
        $responseHeaders = $http_response_header ?? [];
        self::assertNotFalse(
            $responseBody,
            'Could not reach the API. Start the Docker environment before running integration tests.',
        );

        $statusLine = $responseHeaders[0] ?? '';
        self::assertMatchesRegularExpression('/\s(\d{3})(?:\s|$)/', $statusLine);
        preg_match('/\s(\d{3})(?:\s|$)/', $statusLine, $statusMatches);

        $contentType = '';
        foreach ($responseHeaders as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $contentType = trim(substr($header, strlen('Content-Type:')));
                break;
            }
        }

        return [
            'status' => (int) $statusMatches[1],
            'contentType' => $contentType,
            'body' => json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR),
        ];
    }
}
