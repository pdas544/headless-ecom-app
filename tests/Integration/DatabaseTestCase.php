<?php

declare(strict_types=1);

namespace Tests\Integration;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;
use Tests\Support\Factories\RefreshTokenFactory;
use Tests\Support\Factories\UserFactory;
use Throwable;

/**
 * Wraps every test in a database transaction that is rolled back afterwards,
 * so auth flows can share state across requests without leaking between tests.
 */
abstract class DatabaseTestCase extends IntegrationTestCase
{
    private ?ConnectionInterface $connection = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $capsule = $this->container->get(Capsule::class);
            assert($capsule instanceof Capsule);
            $connection = $capsule->getConnection();
            $connection->getPdo();
        } catch (Throwable $exception) {
            self::markTestSkipped(
                'Test database is not reachable (' . $exception->getMessage() . '); run inside the app container.',
            );
        }

        $name = $connection->getDatabaseName();

        if (!str_ends_with($name, '_test')) {
            throw new RuntimeException("Refusing to run integration tests against database '{$name}'.");
        }

        if (!$connection->getSchemaBuilder()->hasTable('users')) {
            self::markTestSkipped('Test database is not migrated; run `make migrate-test`.');
        }

        $this->connection = $connection;
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null && $this->connection->transactionLevel() > 0) {
            $this->connection->rollBack();
        }

        $this->connection = null;

        parent::tearDown();
    }

    protected function db(): ConnectionInterface
    {
        if ($this->connection === null) {
            throw new RuntimeException('Database transaction has not been started.');
        }

        return $this->connection;
    }

    protected function faker(): Generator
    {
        return Factory::create();
    }

    protected function users(): UserFactory
    {
        return new UserFactory($this->db(), $this->faker());
    }

    protected function refreshTokens(): RefreshTokenFactory
    {
        return new RefreshTokenFactory($this->db(), $this->faker());
    }
}
