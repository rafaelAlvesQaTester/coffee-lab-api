<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit\Application\Coffee;

use CoffeeLab\Application\Coffee\CreateCoffee;
use CoffeeLab\Domain\Coffee\RoastLevel;
use CoffeeLab\Infrastructure\Persistence\InMemoryCoffeeRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CreateCoffeeTest extends TestCase
{
    public function testCreatesAndPersistsCoffee(): void
    {
        // Arrange
        $repository = new InMemoryCoffeeRepository();
        $useCase = new CreateCoffee($repository);

        // Act
        $coffee = $useCase->execute('Serra do Caparaó', 'Brasil', 'medium');

        // Assert
        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/',
            (string) $coffee->id,
        );
        self::assertSame('Serra do Caparaó', $coffee->name);
        self::assertSame('Brasil', $coffee->origin);
        self::assertSame(RoastLevel::Medium, $coffee->roastLevel);
        self::assertInstanceOf(DateTimeImmutable::class, $coffee->createdAt);
        self::assertSame($coffee, $repository->findById($coffee->id));
    }

    public function testRejectsBlankName(): void
    {
        // Arrange
        $useCase = new CreateCoffee(new InMemoryCoffeeRepository());

        // Act
        try {
            $useCase->execute(" \t\n ", 'Brasil', 'light');
            self::fail('Expected blank coffee name to be rejected.');
        } catch (InvalidArgumentException $exception) {
            // Assert
            self::assertSame('Coffee name must not be blank.', $exception->getMessage());
        }
    }

    public function testRejectsBlankOrigin(): void
    {
        // Arrange
        $useCase = new CreateCoffee(new InMemoryCoffeeRepository());

        // Act
        try {
            $useCase->execute('Serra do Caparaó', " \t\n ", 'light');
            self::fail('Expected blank coffee origin to be rejected.');
        } catch (InvalidArgumentException $exception) {
            // Assert
            self::assertSame('Coffee origin must not be blank.', $exception->getMessage());
        }
    }

    public function testRejectsUnsupportedRoastLevel(): void
    {
        // Arrange
        $useCase = new CreateCoffee(new InMemoryCoffeeRepository());

        // Act
        try {
            $useCase->execute('Serra do Caparaó', 'Brasil', 'very-dark');
            self::fail('Expected unsupported roast level to be rejected.');
        } catch (InvalidArgumentException $exception) {
            // Assert
            self::assertSame('Roast level must be light, medium, or dark.', $exception->getMessage());
        }
    }
}
