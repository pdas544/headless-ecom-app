<?php

declare(strict_types=1);

namespace App\Core;

final class RequestContext
{
    private static ?string $requestId = null;

    public static function id(): string
    {
        return self::$requestId ??= bin2hex(random_bytes(16));
    }

    public static function set(string $requestId): void
    {
        self::$requestId = $requestId;
    }

    public static function reset(): void
    {
        self::$requestId = null;
    }
}
