<?php

declare(strict_types=1);

namespace CoffeeLab\Infrastructure\Persistence;

use CoffeeLab\Application\Coffee\CoffeeRepository;
use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;

final class InMemoryCoffeeRepository implements CoffeeRepository
{
    /**
     * @var array<string, Coffee>
     */
    private array $coffees = [];

    public function save(Coffee $coffee): void
    {
        $this->coffees[(string) $coffee->id] = $coffee;
    }

    public function findById(CoffeeId $id): ?Coffee
    {
        return $this->coffees[(string) $id] ?? null;
    }

    /**
     * @return list<Coffee>
     */
    public function findAll(): array
    {
        return array_values($this->coffees);
    }
}
