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
        $path = sys_get_temp_dir() . '/config-test-' . uniqid();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessageMatches('/Missing required environment variables/');

        try {
            Config::load($path);
        } finally {
            @rmdir($path);
        }
    }
}
