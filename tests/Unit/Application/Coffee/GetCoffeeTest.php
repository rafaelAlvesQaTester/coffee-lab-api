<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit\Application\Coffee;

use CoffeeLab\Application\Coffee\CoffeeNotFound;
use CoffeeLab\Application\Coffee\GetCoffee;
use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use CoffeeLab\Domain\Coffee\RoastLevel;
use CoffeeLab\Infrastructure\Persistence\InMemoryCoffeeRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GetCoffeeTest extends TestCase
{
    public function testReturnsExistingCoffee(): void
    {
        // Arrange
        $repository = new InMemoryCoffeeRepository();
        $coffee = new Coffee(
            CoffeeId::fromString('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918'),
            'Serra do Caparaó',
            'Brasil',
            RoastLevel::Medium,
            new DateTimeImmutable('2026-11-14T00:00:00+00:00'),
        );
        $repository->save($coffee);
        $useCase = new GetCoffee($repository);

        // Act
        $result = $useCase->execute((string) $coffee->id);

        // Assert
        self::assertSame($coffee, $result);
    }

    public function testThrowsWhenCoffeeDoesNotExist(): void
    {
        // Arrange
        $useCase = new GetCoffee(new InMemoryCoffeeRepository());

        // Act
        try {
            $useCase->execute('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918');
            self::fail('Expected missing coffee to raise CoffeeNotFound.');
        } catch (CoffeeNotFound $exception) {
            // Assert
            self::assertSame(
                'Coffee not found: aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918.',
                $exception->getMessage(),
            );
        }
    }

    public function testRejectsInvalidUuid(): void
    {
        // Arrange
        $useCase = new GetCoffee(new InMemoryCoffeeRepository());

        // Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coffee id must be a valid UUID.');

        // Assert
        $useCase->execute('not-a-uuid');
    }
}
