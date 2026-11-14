<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit\Domain\Coffee;

use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use CoffeeLab\Domain\Coffee\RoastLevel;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;

final class CoffeeTest extends TestCase
{
    public function testCreatesCoffeeWithValidDomainValues(): void
    {
        $id = CoffeeId::fromString('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918');
        $createdAt = new DateTimeImmutable('2026-11-14T00:00:00+00:00');

        $coffee = new Coffee(
            $id,
            'Serra do Caparaó',
            'Brasil',
            RoastLevel::Medium,
            $createdAt,
        );

        self::assertSame($id, $coffee->id);
        self::assertSame('Serra do Caparaó', $coffee->name);
        self::assertSame('Brasil', $coffee->origin);
        self::assertSame(RoastLevel::Medium, $coffee->roastLevel);
        self::assertSame($createdAt, $coffee->createdAt);
    }

    public function testRejectsBlankName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coffee name must not be blank.');

        new Coffee(
            CoffeeId::fromString('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918'),
            " \t\n ",
            'Brasil',
            RoastLevel::Light,
            new DateTimeImmutable(),
        );
    }

    public function testRejectsBlankOrigin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coffee origin must not be blank.');

        new Coffee(
            CoffeeId::fromString('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918'),
            'Serra do Caparaó',
            " \t\n ",
            RoastLevel::Dark,
            new DateTimeImmutable(),
        );
    }

    public function testRejectsUuidWithInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coffee id must be a valid UUID.');

        CoffeeId::fromString('not-a-uuid');
    }

    public function testRejectsUuidWithUnsupportedVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Coffee id must be a valid UUID.');

        CoffeeId::fromString('aaf9e963-e0ac-9aa3-94fc-34bf2b2f8918');
    }

    public function testNormalizesUuidToLowercase(): void
    {
        $id = CoffeeId::fromString('AAF9E963-E0AC-4AA3-94FC-34BF2B2F8918');

        self::assertSame('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918', (string) $id);
    }

    public function testSupportsOnlyTheDefinedRoastLevels(): void
    {
        self::assertSame(
            ['light', 'medium', 'dark'],
            array_map(
                static fn (RoastLevel $level): string => $level->value,
                RoastLevel::cases(),
            ),
        );
        self::assertNull(RoastLevel::tryFrom('very-dark'));
    }

    public function testRequiresAnImmutableDateTimeForCreatedAt(): void
    {
        $this->expectException(TypeError::class);

        new Coffee(
            CoffeeId::fromString('aaf9e963-e0ac-4aa3-94fc-34bf2b2f8918'),
            'Serra do Caparaó',
            'Brasil',
            RoastLevel::Medium,
            'not-a-date',
        );
    }
}
