<?php

declare(strict_types=1);

namespace CoffeeLab\Domain\Coffee;

use InvalidArgumentException;

final readonly class CoffeeId
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalizedValue = strtolower($value);
        $isNilUuid = $normalizedValue === '00000000-0000-0000-0000-000000000000';
        $isMaxUuid = $normalizedValue === 'ffffffff-ffff-ffff-ffff-ffffffffffff';
        $hasValidVersionAndVariant = preg_match(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            $normalizedValue,
        ) === 1;

        if (!$isNilUuid && !$isMaxUuid && !$hasValidVersionAndVariant) {
            throw new InvalidArgumentException('Coffee id must be a valid UUID.');
        }

        return new self($normalizedValue);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
