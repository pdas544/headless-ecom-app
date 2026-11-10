<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;
use RuntimeException;

/**
 * Postgres connection factories. Connection settings come from config/database.php
 * (already derived from DATABASE_URL and validated there); methods re-validate the
 * keys they depend on so a malformed array fails with a clear message.
 */
final class Database
{
    private const CONNECT_TIMEOUT_SECONDS = 2;

    /**
     * @param array<string, mixed> $config
     */
    public static function dsn(array $config): string
    {
        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;connect_timeout=%d',
            self::string($config, 'host'),
            self::integer($config, 'port'),
            self::string($config, 'database'),
            self::string($config, 'sslmode', 'prefer'),
            self::CONNECT_TIMEOUT_SECONDS,
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function pdo(array $config): PDO
    {
        $pdo = new PDO(
            self::dsn($config),
            self::string($config, 'username'),
            self::string($config, 'password', ''),
            self::options($config),
        );

        $pdo->exec("SET TIME ZONE 'UTC'");

        return $pdo;
    }

    /**
     * Boots Eloquent's standalone Capsule (global) using the same connection
     * settings. Safe to call once per process; later calls replace the global.
     *
     * @param array<string, mixed> $config
     */
    public static function bootEloquent(array $config): Capsule
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => self::string($config, 'driver', 'pgsql'),
            'host' => self::string($config, 'host'),
            'port' => self::integer($config, 'port'),
            'database' => self::string($config, 'database'),
            'username' => self::string($config, 'username'),
            'password' => self::string($config, 'password', ''),
            'charset' => self::string($config, 'charset', 'utf8'),
            'options' => self::options($config),
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->getConnection()->getPdo()->exec("SET TIME ZONE 'UTC'");

        return $capsule;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<int, int|bool>
     */
    private static function options(array $config): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
        ];

        $configured = $config['options'] ?? null;

        if (is_array($configured)) {
            foreach ($configured as $key => $value) {
                if (is_int($key) && (is_int($value) || is_bool($value))) {
                    $options[$key] = $value;
                }
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function string(array $config, string $key, string $default = ''): string
    {
        $value = $config[$key] ?? $default;

        if (!is_string($value) || ($value === '' && $default === '')) {
            throw new RuntimeException("Database config '{$key}' must be a non-empty string");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function integer(array $config, string $key): int
    {
        $value = $config[$key] ?? null;

        if (!is_int($value) || $value <= 0) {
            throw new RuntimeException("Database config '{$key}' must be a positive integer");
        }

        return $value;
    }
}
