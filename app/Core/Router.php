<?php

declare(strict_types=1);

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

final class Router
{
    private readonly Dispatcher $dispatcher;

    /**
     * @param callable(RouteCollector): void $routeDefinition
     */
    public function __construct(callable $routeDefinition)
    {
        $this->dispatcher = simpleDispatcher($routeDefinition);
    }

    public function match(string $method, string $path): RouteMatch
    {
        $info = $this->dispatcher->dispatch($method, $path);

        return match ($info[0]) {
            Dispatcher::FOUND => $this->found($info),
            Dispatcher::NOT_FOUND => new RouteMatch(RouteMatchType::NotFound),
            default => new RouteMatch(RouteMatchType::MethodNotAllowed, allowedMethods: $this->allowedMethods($info)),
        };
    }

    /**
     * @param array<int, mixed> $info
     */
    private function found(array $info): RouteMatch
    {
        $handler = is_string($info[1]) ? $info[1] : '';

        return new RouteMatch(RouteMatchType::Found, handler: $handler, vars: $this->vars($info));
    }

    /**
     * @param array<int, mixed> $info
     *
     * @return array<string, string>
     */
    private function vars(array $info): array
    {
        $vars = [];

        foreach (is_array($info[2]) ? $info[2] : [] as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $vars[$name] = $value;
            }
        }

        return $vars;
    }

    /**
     * @param array<int, mixed> $info
     *
     * @return list<string>
     */
    private function allowedMethods(array $info): array
    {
        $allowed = [];

        foreach (is_array($info[1]) ? $info[1] : [] as $method) {
            if (is_string($method)) {
                $allowed[] = $method;
            }
        }

        return $allowed;
    }
}
