<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/**
 * Forces integration tests onto deterministic, non-production configuration.
 * The test database always ends in `_test`; the guard refuses anything else.
 */
final class IntegrationEnvironment
{
    /** @var array<string, string> */
    private const DEFAULTS = [
        'APP_NAME' => 'headless-ecom',
        'APP_ENV' => 'test',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'http://localhost:8081',
        'APP_VERSION' => '0.1.0',
        'REDIS_URL' => 'redis://cache:6379',
        'LOG_LEVEL' => 'debug',
        'JWT_SECRET' => 'test-secret-test-secret-test-secret-1234567890',
        'JWT_TTL' => '900',
        'REFRESH_TTL_DAYS' => '30',
        'MAIL_DSN' => 'smtp://mailpit:1025',
        'MAIL_FROM' => 'no-reply@example.test',
        'CORS_ALLOWED_ORIGINS' => 'http://localhost:3000',
    ];

    public static function apply(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            self::put($key, $value);
        }

        self::put('DATABASE_URL', self::testDatabaseUrl());
    }

    public static function testDatabaseUrl(): string
    {
        $explicit = self::env('DATABASE_TEST_URL');

        if ($explicit !== null) {
            self::assertTestDatabase($explicit);

            return $explicit;
        }

        $base = self::env('DATABASE_URL') ?? 'pgsql://ecom:ecom@db:5432/ecom';
        $url = preg_replace_callback(
            '#^(.*?://[^/]+/)([^/?]+)(.*)$#',
            static function (array $matches): string {
                $name = str_ends_with($matches[2], '_test') ? $matches[2] : $matches[2] . '_test';

                return $matches[1] . $name . $matches[3];
            },
            $base,
            1,
        );

        if (!is_string($url)) {
            throw new RuntimeException('Unable to derive the test database URL.');
        }

        self::assertTestDatabase($url);

        return $url;
    }

    private static function assertTestDatabase(string $url): void
    {
        $path = parse_url($url, PHP_URL_PATH);
        $name = is_string($path) ? ltrim($path, '/') : '';

        if ($name === '' || !str_ends_with($name, '_test')) {
            throw new RuntimeException(
                'Refusing to run integration tests against a non-test database: ' . $url,
            );
        }
    }

    private static function env(string $key): ?string
    {
        foreach ([$_SERVER, $_ENV] as $source) {
            if (isset($source[$key]) && is_string($source[$key]) && $source[$key] !== '') {
                return $source[$key];
            }
        }

        $value = getenv($key);

        return $value === false || $value === '' ? null : $value;
    }

    private static function put(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
