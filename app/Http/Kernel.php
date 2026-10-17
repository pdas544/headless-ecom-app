<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\CatchThrowableMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\JsonBodyParserMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\RouterMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Kernel
{
    /** @var list<class-string<MiddlewareInterface>> */
    private const MIDDLEWARE = [
        CatchThrowableMiddleware::class,
        RequestIdMiddleware::class,
        CorsMiddleware::class,
        JsonBodyParserMiddleware::class,
        RouterMiddleware::class,
    ];

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pipeline = new MiddlewarePipeline($this->middleware(), new TerminalHandler());

        return $pipeline->handle($request);
    }

    /**
     * @return list<MiddlewareInterface>
     */
    private function middleware(): array
    {
        $middleware = [];

        foreach (self::MIDDLEWARE as $class) {
            $resolved = $this->container->get($class);
            assert($resolved instanceof MiddlewareInterface);
            $middleware[] = $resolved;
        }

        return $middleware;
    }
}
