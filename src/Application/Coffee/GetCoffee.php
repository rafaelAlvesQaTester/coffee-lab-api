<?php

declare(strict_types=1);

namespace CoffeeLab\Application\Coffee;

use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;

final readonly class GetCoffee
{
    public function __construct(private CoffeeRepository $repository)
    {
    }

    public function execute(string $id): Coffee
    {
        $coffeeId = CoffeeId::fromString($id);
        $coffee = $this->repository->findById($coffeeId);

        if ($coffee === null) {
            throw CoffeeNotFound::withId((string) $coffeeId);
        }

        return $coffee;
    }
}
