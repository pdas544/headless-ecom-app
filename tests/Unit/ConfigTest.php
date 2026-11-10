<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigTest extends TestCase
{
    public function testGetReturnsKnownValue(): void
    {
        $config = Config::fromArray(['APP_NAME' => 'headless-ecom']);

        self::assertSame('headless-ecom', $config->get('APP_NAME'));
    }

    public function testGetFailsOnUnknownKey(): void
    {
        $config = Config::fromArray([]);

        $this->expectException(RuntimeException::class);
        $config->get('NOPE');
    }

    public function testRequiredKeysAreValidatedOnLoad(): void
    {
        $key = 'APP_NAME';
        $original = getenv($key);
        $serverValue = $_SERVER[$key] ?? null;
        $envValue = $_ENV[$key] ?? null;

        putenv($key);
        unset($_SERVER[$key], $_ENV[$key]);

        $path = sys_get_temp_dir() . '/config-test-' . uniqid();

        try {
            self::expectException(RuntimeException::class);
            self::expectExceptionMessageMatches('/Missing required environment variables/');

            Config::load($path);
        } finally {
            if (is_string($original)) {
                putenv("{$key}={$original}");
            }

            if ($serverValue !== null) {
                $_SERVER[$key] = $serverValue;
            }

            if ($envValue !== null) {
                $_ENV[$key] = $envValue;
            }

            @rmdir($path);
        }
    }
}
