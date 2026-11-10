<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\ResponseEmitter;
use App\Http\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class ContainerTest extends TestCase
{
    private const REQUIRED_ENV = [
        'APP_NAME' => 'headless-ecom',
        'APP_ENV' => 'local',
        'APP_DEBUG' => 'true',
        'APP_URL' => 'http://localhost:8081',
        'DATABASE_URL' => 'pgsql://ecom:ecom@db:5432/ecom',
        'REDIS_URL' => 'redis://cache:6379',
        'LOG_LEVEL' => 'debug',
        'JWT_SECRET' => 'test-secret-test-secret-test-secret-1234567890',
        'JWT_TTL' => '900',
        'REFRESH_TTL_DAYS' => '30',
        'MAIL_DSN' => 'smtp://mailpit:1025',
        'MAIL_FROM' => 'no-reply@example.test',
    ];

    private ContainerInterface $container;

    protected function setUp(): void
    {
        foreach (self::REQUIRED_ENV as $key => $value) {
            putenv("{$key}={$value}");
        }

        $this->container = require dirname(__DIR__, 2) . '/config/container.php';
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::REQUIRED_ENV) as $key) {
            putenv($key);
        }
    }

    public function testCoreServicesResolve(): void
    {
        self::assertInstanceOf(Config::class, $this->container->get(Config::class));
        self::assertInstanceOf(LoggerInterface::class, $this->container->get(LoggerInterface::class));
        self::assertInstanceOf(ErrorHandler::class, $this->container->get(ErrorHandler::class));
        self::assertInstanceOf(ResponseFactoryInterface::class, $this->container->get(ResponseFactoryInterface::class));
        self::assertInstanceOf(StreamFactoryInterface::class, $this->container->get(StreamFactoryInterface::class));
        self::assertInstanceOf(ResponseEmitter::class, $this->container->get(ResponseEmitter::class));
    }

    public function testKernelIsAutowired(): void
    {
        self::assertInstanceOf(Kernel::class, $this->container->get(Kernel::class));
    }
}
