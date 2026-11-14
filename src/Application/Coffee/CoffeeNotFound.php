<?php

declare(strict_types=1);

namespace CoffeeLab\Application\Coffee;

use RuntimeException;

final class CoffeeNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Coffee not found: %s.', $id));
    }
}
