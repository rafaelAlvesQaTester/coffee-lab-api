<?php

declare(strict_types=1);

namespace CoffeeLab\Infrastructure\Persistence;

use PDO;
use PDOException;
use RuntimeException;

final class PdoConnectionFactory
{
    private function __construct()
    {
    }

    public static function fromEnvironment(): PDO
    {
        return self::connect(self::environmentConfiguration('DB_'), false);
    }

    public static function forTests(): PDO
    {
        foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $name) {
            if (getenv($name) !== false) {
                throw new RuntimeException('Integration tests must not receive application database configuration.');
            }
        }

        $testConfiguration = self::environmentConfiguration('COFFEE_LAB_TEST_DB_');
        PdoTestDatabaseGuard::validateConfiguration($testConfiguration, null);

        return self::connect($testConfiguration, true);
    }

    /**
     * @return array<string, string>
     */
    private static function environmentConfiguration(string $prefix): array
    {
        return [
            'HOST' => self::requiredEnvironmentValue($prefix . 'HOST'),
            'PORT' => self::requiredEnvironmentValue($prefix . 'PORT'),
            'DATABASE' => self::requiredEnvironmentValue($prefix . 'DATABASE'),
            'USERNAME' => self::requiredEnvironmentValue($prefix . 'USERNAME'),
            'PASSWORD' => self::requiredEnvironmentValue($prefix . 'PASSWORD'),
        ];
    }

    /**
     * @param array<string, string> $configuration
     */
    private static function connect(
        array $configuration,
        bool $testDatabase,
    ): PDO
    {
        $host = $configuration['HOST'];
        $portValue = $configuration['PORT'];
        $database = $configuration['DATABASE'];
        $username = $configuration['USERNAME'];
        $password = $configuration['PASSWORD'];

        if (
            strpbrk($host, ";\0\r\n") !== false
            || preg_match('/\A[A-Za-z0-9_.-]+\z/', $database) !== 1
            || filter_var($portValue, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 65535],
            ]) === false
        ) {
            throw new RuntimeException('Invalid PostgreSQL connection configuration.');
        }

        try {
            $connection = new PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, (int) $portValue, $database),
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            );
        } catch (PDOException $exception) {
            if ($testDatabase) {
                throw new RuntimeException('Unable to connect to the configured test database.');
            }

            throw $exception;
        }

        if ($testDatabase) {
            try {
                $statement = $connection->query(
                    'SELECT current_database() AS database_name',
                );
            } catch (PDOException) {
                throw new RuntimeException('Unable to verify the integration database identity.');
            }

            try {
                $identity = $statement === false ? false : $statement->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException) {
                throw new RuntimeException('Unable to verify the integration database identity.');
            }

            return PdoTestDatabaseGuard::runAfterIdentityCheck(
                is_array($identity) && is_string($identity['database_name'] ?? null)
                    ? $identity['database_name']
                    : null,
                $database,
                static function () use ($connection): PDO {
                    $connection->exec("SET TIME ZONE 'UTC'");

                    return $connection;
                },
            );
        }

        $connection->exec("SET TIME ZONE 'UTC'");

        return $connection;
    }

    private static function requiredEnvironmentValue(string $name): string
    {
        $value = getenv($name);
        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('Missing required environment variable: %s.', $name));
        }

        return $value;
    }
}
