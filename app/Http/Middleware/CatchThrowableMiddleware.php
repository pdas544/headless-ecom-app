<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\ProblemResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class CatchThrowableMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ProblemResponder $responder)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $throwable) {
            return $this->responder->respondTo($throwable);
        }
    }
}
