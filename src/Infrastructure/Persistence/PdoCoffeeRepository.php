<?php

declare(strict_types=1);

namespace CoffeeLab\Infrastructure\Persistence;

use CoffeeLab\Application\Coffee\CoffeeRepository;
use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use CoffeeLab\Domain\Coffee\RoastLevel;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final readonly class PdoCoffeeRepository implements CoffeeRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function save(Coffee $coffee): void
    {
        $statement = $this->connection->prepare(
            <<<'SQL'
                INSERT INTO coffees (id, name, origin, roast_level, created_at)
                VALUES (:id, :name, :origin, :roast_level, :created_at)
                ON CONFLICT (id) DO UPDATE SET
                    name = EXCLUDED.name,
                    origin = EXCLUDED.origin,
                    roast_level = EXCLUDED.roast_level,
                    created_at = EXCLUDED.created_at
                SQL,
        );
        $statement->execute([
            'id' => (string) $coffee->id,
            'name' => $coffee->name,
            'origin' => $coffee->origin,
            'roast_level' => $coffee->roastLevel->value,
            'created_at' => $coffee->createdAt->format('Y-m-d H:i:s.uP'),
        ]);
    }

    public function findById(CoffeeId $id): ?Coffee
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, origin, roast_level, created_at FROM coffees WHERE id = :id',
        );
        $statement->execute(['id' => (string) $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->toCoffee($row) : null;
    }

    /**
     * @return list<Coffee>
     */
    public function findAll(): array
    {
        $statement = $this->connection->query(
            'SELECT id, name, origin, roast_level, created_at FROM coffees ORDER BY created_at, id',
        );
        $rows = $statement->fetchAll();

        return array_map(fn (array $row): Coffee => $this->toCoffee($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toCoffee(array $row): Coffee
    {
        return new Coffee(
            CoffeeId::fromString($row['id']),
            $row['name'],
            $row['origin'],
            RoastLevel::from($row['roast_level']),
            (new DateTimeImmutable($row['created_at']))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
