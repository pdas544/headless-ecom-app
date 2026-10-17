<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Exception\HttpException;
use App\Core\ProblemResponder;
use App\Core\RouteMatchType;
use App\Core\Router;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Router $router,
        private readonly ContainerInterface $container,
        private readonly ProblemResponder $responder,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $match = $this->router->match($method, $path);

        if ($match->type === RouteMatchType::NotFound) {
            return $this->responder->respondTo(HttpException::notFound(
                sprintf('No route matches %s %s.', $method, $path),
            ));
        }

        if ($match->type === RouteMatchType::MethodNotAllowed) {
            return $this->responder->respondTo(HttpException::methodNotAllowed(
                'Allowed methods: ' . implode(', ', $match->allowedMethods),
            ));
        }

        $controller = $this->resolve($match->handler ?? '');

        return $controller->handle($this->withRouteVars($request, $match->vars));
    }

    private function resolve(string $handlerClass): RequestHandlerInterface
    {
        if ($handlerClass === '') {
            throw new LogicException('Matched route handler is not a class string.');
        }

        $controller = $this->container->get($handlerClass);

        if (! $controller instanceof RequestHandlerInterface) {
            throw new LogicException(sprintf('Route handler %s is not a request handler.', $handlerClass));
        }

        return $controller;
    }

    /**
     * @param array<string, string> $vars
     */
    private function withRouteVars(ServerRequestInterface $request, array $vars): ServerRequestInterface
    {
        foreach ($vars as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $request;
    }
}
