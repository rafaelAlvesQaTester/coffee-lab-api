<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Integration;

use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use CoffeeLab\Domain\Coffee\RoastLevel;
use CoffeeLab\Infrastructure\Persistence\PdoCoffeeRepository;
use CoffeeLab\Infrastructure\Persistence\PdoConnectionFactory;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class PdoCoffeeRepositoryTest extends TestCase
{
    private PDO $connection;

    private PdoCoffeeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = PdoConnectionFactory::forTests();
        $this->connection->beginTransaction();
        $this->repository = new PdoCoffeeRepository($this->connection);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection) && $this->connection->inTransaction()) {
            $this->connection->rollBack();
        }

        parent::tearDown();
    }

    public function testSavesAndReadsTheSameCoffee(): void
    {
        // Arrange
        $coffee = $this->coffee(
            $this->uuidV4(),
            RoastLevel::Medium,
            '2026-11-14T03:00:00.123456-03:00',
        );

        // Act
        $this->repository->save($coffee);
        $found = $this->repository->findById($coffee->id);

        // Assert
        self::assertNotNull($found);
        self::assertSame((string) $coffee->id, (string) $found->id);
        self::assertSame($coffee->name, $found->name);
        self::assertSame($coffee->origin, $found->origin);
        self::assertSame($coffee->roastLevel, $found->roastLevel);
        self::assertEquals($coffee->createdAt, $found->createdAt);
        self::assertSame('UTC', $found->createdAt->getTimezone()->getName());
        self::assertSame($coffee->createdAt->getTimestamp(), $found->createdAt->getTimestamp());
        self::assertSame(
            $coffee->createdAt->format('u'),
            $found->createdAt->format('u'),
        );
    }

    public function testListsCoffeesWithEverySupportedRoastLevel(): void
    {
        // Arrange
        $coffees = [
            $this->coffee($this->uuidV4(), RoastLevel::Light, '2026-11-14T00:00:00+00:00'),
            $this->coffee($this->uuidV4(), RoastLevel::Medium, '2026-11-14T01:00:00+00:00'),
            $this->coffee($this->uuidV4(), RoastLevel::Dark, '2026-11-14T02:00:00+00:00'),
        ];

        // Act
        foreach ($coffees as $coffee) {
            $this->repository->save($coffee);
        }
        $listed = $this->repository->findAll();

        // Assert
        $expectedIds = array_map(static fn (Coffee $coffee): string => (string) $coffee->id, $coffees);
        $listedIds = array_map(static fn (Coffee $coffee): string => (string) $coffee->id, $listed);
        $foundIds = array_values(array_intersect($expectedIds, $listedIds));
        self::assertCount(3, $foundIds);
        $listedTestCoffees = array_values(array_filter(
            $listed,
            static fn (Coffee $coffee): bool => in_array((string) $coffee->id, $expectedIds, true),
        ));
        self::assertSame(
            ['light', 'medium', 'dark'],
            array_map(
                static fn (Coffee $coffee): string => $coffee->roastLevel->value,
                $listedTestCoffees,
            ),
        );
    }

    public function testReturnsNullForAnUnknownCoffeeId(): void
    {
        // Arrange
        $id = CoffeeId::fromString($this->uuidV4());

        // Act
        $result = $this->repository->findById($id);

        // Assert
        self::assertNull($result);
    }

    public function testPropagatesDatabaseQueryFailures(): void
    {
        // Arrange
        $missingSchema = 'missing_coffee_schema_' . bin2hex(random_bytes(8));
        $this->connection->exec(sprintf('SET LOCAL search_path TO "%s"', $missingSchema));

        // Act / Assert
        $this->expectException(PDOException::class);
        $this->repository->findAll();
    }

    private function coffee(
        string $id,
        RoastLevel $roastLevel,
        string $createdAt = '2026-11-14T00:00:00+00:00',
    ): Coffee {
        return new Coffee(
            CoffeeId::fromString($id),
            'Integration coffee',
            'Brazil',
            $roastLevel,
            new DateTimeImmutable($createdAt, new DateTimeZone('UTC')),
        );
    }

    private function uuidV4(): string
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
