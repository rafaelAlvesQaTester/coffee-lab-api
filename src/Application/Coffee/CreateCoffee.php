<?php

declare(strict_types=1);

namespace CoffeeLab\Application\Coffee;

use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use CoffeeLab\Domain\Coffee\RoastLevel;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class CreateCoffee
{
    public function __construct(private CoffeeRepository $repository)
    {
    }

    public function execute(string $name, string $origin, string $roastLevel): Coffee
    {
        $level = RoastLevel::tryFrom($roastLevel);
        if ($level === null) {
            throw new InvalidArgumentException('Roast level must be light, medium, or dark.');
        }

        $coffee = new Coffee(
            CoffeeId::fromString($this->generateUuid()),
            $name,
            $origin,
            $level,
            new DateTimeImmutable('now', new DateTimeZone('UTC')),
        );

        $this->repository->save($coffee);

        return $coffee;
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
