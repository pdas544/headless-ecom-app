<?php

declare(strict_types=1);

namespace App\Core;

final class RouteMatch
{
    /**
     * @param array<string, string> $vars
     * @param list<string>         $allowedMethods
     */
    public function __construct(
        public readonly RouteMatchType $type,
        public readonly ?string $handler = null,
        public readonly array $vars = [],
        public readonly array $allowedMethods = [],
    ) {
    }
}
