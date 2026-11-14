<?php

declare(strict_types=1);

namespace CoffeeLab\Application\Coffee;

use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;

interface CoffeeRepository
{
    public function save(Coffee $coffee): void;

    public function findById(CoffeeId $id): ?Coffee;

    /**
     * @return list<Coffee>
     */
    public function findAll(): array;
}
