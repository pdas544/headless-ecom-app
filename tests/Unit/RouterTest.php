<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\RouteMatchType;
use App\Core\Router;
use Closure;
use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $routes = require dirname(__DIR__, 2) . '/routes/api.php';
        assert($routes instanceof Closure);
        $this->router = new Router($routes);
    }

    public function testWelcomeRouteResolves(): void
    {
        $match = $this->router->match('GET', '/');

        self::assertSame(RouteMatchType::Found, $match->type);
        self::assertSame('App\Http\Controllers\WelcomeController', $match->handler);
    }

    public function testHealthRouteResolves(): void
    {
        $match = $this->router->match('GET', '/health');

        self::assertSame(RouteMatchType::Found, $match->type);
        self::assertSame('App\Http\Controllers\HealthController', $match->handler);
    }

    public function testGroupPathResolves(): void
    {
        $match = $this->router->match('GET', '/api/v1');

        self::assertSame(RouteMatchType::Found, $match->type);
        self::assertSame('App\Http\Controllers\VersionController', $match->handler);
    }

    public function testUnknownPathIsNotFound(): void
    {
        $match = $this->router->match('GET', '/nope');

        self::assertSame(RouteMatchType::NotFound, $match->type);
        self::assertNull($match->handler);
    }

    public function testWrongMethodListsAllowedMethods(): void
    {
        $match = $this->router->match('POST', '/health');

        self::assertSame(RouteMatchType::MethodNotAllowed, $match->type);
        self::assertSame(['GET'], $match->allowedMethods);
    }
}
