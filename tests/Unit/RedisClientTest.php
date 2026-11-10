<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\RedisClient;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use RuntimeException;

final class RedisClientTest extends TestCase
{
    public function testBuildsClientFromRedisUrlWithoutConnecting(): void
    {
        $client = RedisClient::fromUrl('redis://cache:6379/0');

        self::assertInstanceOf(ClientInterface::class, $client);
    }

    public function testRejectsNonRedisScheme(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/redis:\/\/ or rediss:\/\//');

        RedisClient::fromUrl('http://cache:6379');
    }
}
