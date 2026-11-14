<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit\Controller;

use CoffeeLab\Application\Coffee\CreateCoffee;
use CoffeeLab\Application\Coffee\GetCoffee;
use CoffeeLab\Application\Coffee\ListCoffees;
use CoffeeLab\Controller\CoffeeController;
use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use CoffeeLab\Domain\Coffee\RoastLevel;
use CoffeeLab\Infrastructure\Persistence\InMemoryCoffeeRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CoffeeControllerTest extends TestCase
{
    public function testCreatesCoffeeWithExpectedResponse(): void
    {
        // Arrange
        $controller = $this->createController();

        // Act
        [$statusCode, $body] = $this->captureResponse(
            fn () => $controller->create('{"name":"Serra do Caparaó","origin":"Brasil","roastLevel":"medium"}'),
        );

        // Assert
        self::assertSame(201, $statusCode);
        self::assertSame(
            ['id', 'name', 'origin', 'roastLevel', 'createdAt'],
            array_keys($body),
        );
        self::assertSame('Serra do Caparaó', $body['name']);
        self::assertSame('Brasil', $body['origin']);
        self::assertSame('medium', $body['roastLevel']);
        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/',
            $body['id'],
        );
    }

    public function testReturnsBadRequestForMalformedJson(): void
    {
        // Arrange
        $controller = $this->createController();

        // Act
        [$statusCode, $body] = $this->captureResponse(fn () => $controller->create('{"name":'));

        // Assert
        self::assertSame(400, $statusCode);
        self::assertSame(['status' => 'error', 'message' => 'Malformed JSON body.'], $body);
    }

    public function testReturnsUnprocessableEntityForInvalidData(): void
    {
        // Arrange
        $controller = $this->createController();

        // Act
        [$statusCode, $body] = $this->captureResponse(
            fn () => $controller->create('{"name":" ","origin":"Brasil","roastLevel":"light"}'),
        );

        // Assert
        self::assertSame(422, $statusCode);
        self::assertSame(['status' => 'error', 'message' => 'Invalid coffee data.'], $body);
    }

    public function testListsEmptyCollection(): void
    {
        // Arrange
        $controller = $this->createController();

        // Act
        [$statusCode, $body] = $this->captureResponse(fn () => $controller->index());

        // Assert
        self::assertSame(200, $statusCode);
        self::assertSame([], $body);
    }

    public function testListsAvailableCoffees(): void
    {
        // Arrange
        $repository = new InMemoryCoffeeRepository();
        $coffee = $this->coffee();
        $repository->save($coffee);
        $controller = $this->createController($repository);

        // Act
        [$statusCode, $body] = $this->captureResponse(fn () => $controller->index());

        // Assert
        self::assertSame(200, $statusCode);
        self::assertCount(1, $body);
        self::assertSame((string) $coffee->id, $body[0]['id']);
        self::assertSame('Yirgacheffe', $body[0]['name']);
    }

    public function testReturnsExistingCoffee(): void
    {
        // Arrange
        $repository = new InMemoryCoffeeRepository();
        $coffee = $this->coffee();
        $repository->save($coffee);
        $controller = $this->createController($repository);

        // Act
        [$statusCode, $body] = $this->captureResponse(
            fn () => $controller->show((string) $coffee->id),
        );

        // Assert
        self::assertSame(200, $statusCode);
        self::assertSame((string) $coffee->id, $body['id']);
        self::assertSame('Yirgacheffe', $body['name']);
    }

    public function testReturnsNotFoundForMissingCoffee(): void
    {
        // Arrange
        $controller = $this->createController();

        // Act
        [$statusCode, $body] = $this->captureResponse(
            fn () => $controller->show('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918'),
        );

        // Assert
        self::assertSame(404, $statusCode);
        self::assertSame(['status' => 'error', 'message' => 'Coffee not found.'], $body);
    }

    public function testReturnsBadRequestForInvalidUuid(): void
    {
        // Arrange
        $controller = $this->createController();

        // Act
        [$statusCode, $body] = $this->captureResponse(fn () => $controller->show('invalid'));

        // Assert
        self::assertSame(400, $statusCode);
        self::assertSame(['status' => 'error', 'message' => 'Invalid coffee id.'], $body);
    }

    private function createController(?InMemoryCoffeeRepository $repository = null): CoffeeController
    {
        $repository ??= new InMemoryCoffeeRepository();

        return new CoffeeController(
            new CreateCoffee($repository),
            new GetCoffee($repository),
            new ListCoffees($repository),
        );
    }

    private function coffee(): Coffee
    {
        return new Coffee(
            CoffeeId::fromString('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918'),
            'Yirgacheffe',
            'Etiópia',
            RoastLevel::Light,
            new DateTimeImmutable('2026-11-14T00:00:00+00:00'),
        );
    }

    /**
     * @return array{int, array<string, mixed>|list<mixed>}
     */
    private function captureResponse(callable $action): array
    {
        http_response_code(200);
        ob_start();

        try {
            $action();
            $responseBody = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        return [
            http_response_code(),
            json_decode((string) $responseBody, true, flags: JSON_THROW_ON_ERROR),
        ];
    }
}
