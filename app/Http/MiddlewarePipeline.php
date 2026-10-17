<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private array $middleware,
        private readonly RequestHandlerInterface $terminal,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $next = $this->middleware[0] ?? null;

        if ($next === null) {
            return $this->terminal->handle($request);
        }

        $this->middleware = array_slice($this->middleware, 1);

        return $next->process($request, $this);
    }
}
