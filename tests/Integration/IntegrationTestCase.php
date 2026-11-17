<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Support\IntegrationEnvironment;

/**
 * Boots the real container against the test environment. Integration tests
 * never inherit the developer's `.env` database or token settings.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        IntegrationEnvironment::apply();
        $this->container = require dirname(__DIR__, 2) . '/config/container.php';
    }

    protected function kernel(): Kernel
    {
        $kernel = $this->container->get(Kernel::class);
        assert($kernel instanceof Kernel);

        return $kernel;
    }

    protected function uri(string $path): string
    {
        return 'https://api.test' . $path;
    }
}
