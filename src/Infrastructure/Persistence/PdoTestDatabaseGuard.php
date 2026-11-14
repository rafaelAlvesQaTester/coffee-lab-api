<?php

declare(strict_types=1);

namespace CoffeeLab\Infrastructure\Persistence;

use RuntimeException;

final class PdoTestDatabaseGuard
{
    public static function validateConfiguration(
        array $testConfiguration,
        ?string $applicationDatabase,
    ): void {
        $requiredKeys = ['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'];
        foreach ($requiredKeys as $key) {
            if (($testConfiguration[$key] ?? '') === '') {
                throw new RuntimeException('Test database configuration must be complete.');
            }
        }

        self::validatePort($testConfiguration['PORT']);
        if (
            $testConfiguration['HOST'] !== 'database-test'
            || strpbrk($testConfiguration['HOST'], ";\0\r\n") !== false
            || preg_match('/\A[A-Za-z0-9_.-]+\z/', $testConfiguration['DATABASE']) !== 1
        ) {
            throw new RuntimeException('Test database must use the isolated integration service.');
        }

        if (
            is_string($applicationDatabase)
            && $applicationDatabase !== ''
            && $testConfiguration['DATABASE'] === $applicationDatabase
        ) {
            throw new RuntimeException('Integration and application databases must be different.');
        }
    }

    public static function assertConnectedDatabase(?string $actualDatabase, string $expectedDatabase): void
    {
        if ($actualDatabase === null || $actualDatabase === '' || $actualDatabase !== $expectedDatabase) {
            throw new RuntimeException('Unable to verify the integration database identity.');
        }
    }

    public static function runAfterIdentityCheck(
        ?string $actualDatabase,
        string $expectedDatabase,
        callable $operation,
    ): mixed {
        self::assertConnectedDatabase($actualDatabase, $expectedDatabase);

        return $operation();
    }

    private static function validatePort(string $port): void
    {
        if (filter_var($port, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]) === false) {
            throw new RuntimeException('Invalid database port configuration.');
        }
    }
}
