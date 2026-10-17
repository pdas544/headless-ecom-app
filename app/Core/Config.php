<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use RuntimeException;

final class Config
{
    private const REQUIRED = ['APP_NAME', 'APP_ENV', 'APP_URL', 'DATABASE_URL', 'REDIS_URL', 'LOG_LEVEL'];

    /** @var array<string, string> */
    private array $values;

    /** @param array<string, string> $values */
    private function __construct(array $values)
    {
        $this->values = $values;
    }

    /** @param array<string, string> $values */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    public static function load(string $basePath): self
    {
        $env = self::fromEnvironment();

        $missing = self::missing($env);

        if ($missing !== [] && self::hasEnvFile($basePath)) {
            self::loadDotenv($basePath);
            $env = self::fromEnvironment();
            $missing = self::missing($env);
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Missing required environment variables: ' . implode(', ', $missing)
                . '. Copy .env.example to .env or set them via environment.',
            );
        }

        return new self($env);
    }

    public function get(string $key): string
    {
        return $this->values[$key] ?? throw new RuntimeException("Unknown config key: {$key}");
    }

    public function getOptional(string $key): ?string
    {
        $value = $this->values[$key] ?? null;

        return $value === '' ? null : $value;
    }

    public function getBool(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
            ?? throw new RuntimeException("Config key {$key} is not a valid boolean");
    }

    public function getInt(string $key): int
    {
        $value = filter_var($this->get($key), FILTER_VALIDATE_INT);

        if ($value === false) {
            throw new RuntimeException("Config key {$key} is not a valid integer");
        }

        return $value;
    }

    public function isDebug(): bool
    {
        return $this->getBool('APP_DEBUG');
    }

    public function isLocal(): bool
    {
        return $this->get('APP_ENV') === 'local';
    }

    /** @return array<string, string> */
    private static function fromEnvironment(): array
    {
        /** @var array<string, mixed> $server */
        $server = $_SERVER;
        /** @var array<string, mixed> $envVars */
        $envVars = $_ENV;

        /** @var array<string, string> $collected */
        $collected = [];

        foreach ([$server, $envVars, getenv()] as $source) {
            foreach ($source as $key => $value) {
                if (is_string($key) && is_string($value) && $value !== '') {
                    $collected[$key] = $value;
                }
            }
        }

        return $collected;
    }

    /**
     * @param array<string, string> $env
     *
     * @return list<string>
     */
    private static function missing(array $env): array
    {
        /** @var list<string> $missing */
        $missing = [];

        foreach (self::REQUIRED as $key) {
            if (! isset($env[$key]) || $env[$key] === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    private static function hasEnvFile(string $basePath): bool
    {
        return is_file($basePath . '/.env');
    }

    private static function loadDotenv(string $basePath): void
    {
        Dotenv::createUnsafeImmutable($basePath)->load();
    }
}
