<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit\Application\Coffee;

use CoffeeLab\Application\Coffee\ListCoffees;
use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use CoffeeLab\Domain\Coffee\RoastLevel;
use CoffeeLab\Infrastructure\Persistence\InMemoryCoffeeRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ListCoffeesTest extends TestCase
{
    public function testReturnsAllAvailableCoffees(): void
    {
        // Arrange
        $repository = new InMemoryCoffeeRepository();
        $coffees = [
            new Coffee(
                CoffeeId::fromString('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918'),
                'Serra do Caparaó',
                'Brasil',
                RoastLevel::Medium,
                new DateTimeImmutable('2026-11-14T00:00:00+00:00'),
            ),
            new Coffee(
                CoffeeId::fromString('d0c260d3-83a8-4fe7-958e-c9bb772c02cc'),
                'Yirgacheffe',
                'Etiópia',
                RoastLevel::Light,
                new DateTimeImmutable('2026-11-14T01:00:00+00:00'),
            ),
        ];
        foreach ($coffees as $coffee) {
            $repository->save($coffee);
        }
        $useCase = new ListCoffees($repository);

        // Act
        $result = $useCase->execute();

        // Assert
        self::assertSame($coffees, $result);
    }

    public function testReturnsAnEmptyListWhenNoCoffeesExist(): void
    {
        // Arrange
        $useCase = new ListCoffees(new InMemoryCoffeeRepository());

        // Act
        $result = $useCase->execute();

        // Assert
        self::assertSame([], $result);
    }
}
