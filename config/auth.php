<?php

/**
 * Auth configuration: token lifetimes, JWT issuer/audience, refresh cookie
 * flags and the password hashing policy. Secrets are never copied into this
 * array — the issuer reads JWT_SECRET from Config directly.
 *
 * @return array{
 *     issuer: string,
 *     audience: string,
 *     jwt_ttl: int,
 *     refresh_ttl_days: int,
 *     refresh_cookie: array{
 *         name: string,
 *         path: string,
 *         secure: bool,
 *         http_only: bool,
 *         same_site: string
 *     },
 *     password: array{
 *         algo: string,
 *         options: array{memory_cost: int, time_cost: int, threads: int}
 *     }
 * }
 */

declare(strict_types=1);

use App\Core\Config;

return static function (Config $config): array {
    $secret = $config->get('JWT_SECRET');

    if (strlen($secret) < 32) {
        throw new RuntimeException(
            'JWT_SECRET must be at least 32 characters. Generate one with: openssl rand -hex 32',
        );
    }

    $jwtTtl = $config->getInt('JWT_TTL');
    $refreshTtlDays = $config->getInt('REFRESH_TTL_DAYS');

    if ($jwtTtl < 60 || $jwtTtl > 3600) {
        throw new RuntimeException('JWT_TTL must be between 60 and 3600 seconds');
    }

    if ($refreshTtlDays < 1 || $refreshTtlDays > 365) {
        throw new RuntimeException('REFRESH_TTL_DAYS must be between 1 and 365');
    }

    return [
        'issuer' => $config->get('APP_URL'),
        'audience' => 'headless-ecom-api',
        'jwt_ttl' => $jwtTtl,
        'refresh_ttl_days' => $refreshTtlDays,
        'refresh_cookie' => [
            'name' => 'refresh_token',
            'path' => '/api/v1/auth',
            'secure' => !$config->isLocal(),
            'http_only' => true,
            'same_site' => 'Lax',
        ],
        'password' => [
            'algo' => PASSWORD_ARGON2ID,
            'options' => [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1,
            ],
        ],
    ];
};
