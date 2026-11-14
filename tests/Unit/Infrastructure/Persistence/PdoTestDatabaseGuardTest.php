<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit\Infrastructure\Persistence;

use CoffeeLab\Infrastructure\Persistence\PdoTestDatabaseGuard;
use CoffeeLab\Infrastructure\Persistence\PdoConnectionFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdoTestDatabaseGuardTest extends TestCase
{
    public function testRejectsAValidDatabaseNameOnANonIsolatedHost(): void
    {
        // Arrange
        $configuration = $this->testConfiguration(['HOST' => 'production-db.internal']);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        PdoTestDatabaseGuard::validateConfiguration($configuration, 'coffee_lab');
    }

    public function testRejectsMissingTestDatabaseConfiguration(): void
    {
        // Act / Assert
        $this->expectException(RuntimeException::class);
        PdoTestDatabaseGuard::validateConfiguration([], 'coffee_lab');
    }

    public function testFactoryRejectsMissingDedicatedEnvironmentBeforeConnecting(): void
    {
        // Arrange
        $names = [
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'COFFEE_LAB_TEST_DB_HOST',
            'COFFEE_LAB_TEST_DB_PORT',
            'COFFEE_LAB_TEST_DB_DATABASE',
            'COFFEE_LAB_TEST_DB_USERNAME',
            'COFFEE_LAB_TEST_DB_PASSWORD',
        ];
        $originalValues = [];
        foreach ($names as $name) {
            $value = getenv($name);
            $originalValues[$name] = is_string($value) ? $value : false;
            putenv($name);
        }

        // Act
        $rejected = false;
        try {
            PdoConnectionFactory::forTests();
        } catch (RuntimeException) {
            $rejected = true;
        } finally {
            foreach ($originalValues as $name => $value) {
                if ($value === false) {
                    putenv($name);
                } else {
                    putenv($name . '=' . $value);
                }
            }
        }

        // Assert
        self::assertTrue($rejected);
    }

    public function testRejectsTheApplicationDatabaseName(): void
    {
        // Arrange
        $configuration = $this->testConfiguration(['DATABASE' => 'coffee_lab']);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        PdoTestDatabaseGuard::validateConfiguration($configuration, 'coffee_lab');
    }

    public function testRejectsAnUnverifiableConnectedDatabaseIdentity(): void
    {
        // Act / Assert
        $this->expectException(RuntimeException::class);
        PdoTestDatabaseGuard::assertConnectedDatabase(null, 'coffee_lab_test');
    }

    public function testAcceptsAnIsolatedTestDatabaseConfigurationAndIdentity(): void
    {
        // Arrange
        $testConfiguration = $this->testConfiguration();

        // Act
        PdoTestDatabaseGuard::validateConfiguration($testConfiguration, 'coffee_lab');
        PdoTestDatabaseGuard::assertConnectedDatabase(
            $testConfiguration['DATABASE'],
            $testConfiguration['DATABASE'],
        );

        // Assert
        self::assertSame('coffee_lab_test', $testConfiguration['DATABASE']);
    }

    public function testAuthorizationFailurePreventsWriteOperationFromStarting(): void
    {
        // Arrange
        $writesStarted = false;

        // Act
        try {
            PdoTestDatabaseGuard::runAfterIdentityCheck(
                'coffee_lab_test',
                'different_database',
                static function () use (&$writesStarted): void {
                    $writesStarted = true;
                },
            );
        } catch (RuntimeException) {
        }

        // Assert
        self::assertFalse($writesStarted);
    }

    public function testConfigurationFailureDoesNotExposePasswords(): void
    {
        // Arrange
        $secret = 'test-password-must-not-be-in-error-output';
        $testConfiguration = $this->testConfiguration(['DATABASE' => 'unauthorized_test']);
        $testConfiguration['PASSWORD'] = $secret;

        // Act
        try {
            PdoTestDatabaseGuard::validateConfiguration($testConfiguration, 'coffee_lab');
            self::fail('Expected database configuration to be rejected.');
        } catch (RuntimeException $exception) {
            // Assert
            self::assertStringNotContainsString($secret, $exception->getMessage());
        }
    }

    /**
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function testConfiguration(array $overrides = []): array
    {
        return array_replace([
            'HOST' => 'database-test',
            'PORT' => '5432',
            'DATABASE' => 'coffee_lab_test',
            'USERNAME' => 'coffee_lab_test_user',
            'PASSWORD' => 'test-only-placeholder',
        ], $overrides);
    }
}
