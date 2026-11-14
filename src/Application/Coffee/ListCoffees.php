<?php

declare(strict_types=1);

namespace CoffeeLab\Application\Coffee;

use CoffeeLab\Domain\Coffee\Coffee;

final readonly class ListCoffees
{
    public function __construct(private CoffeeRepository $repository)
    {
    }

    /**
     * @return list<Coffee>
     */
    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
