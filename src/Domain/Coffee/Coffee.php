<?php

declare(strict_types=1);

namespace CoffeeLab\Domain\Coffee;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Coffee
{
    public function __construct(
        public CoffeeId $id,
        public string $name,
        public string $origin,
        public RoastLevel $roastLevel,
        public DateTimeImmutable $createdAt,
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Coffee name must not be blank.');
        }

        if (trim($origin) === '') {
            throw new InvalidArgumentException('Coffee origin must not be blank.');
        }
    }
}
