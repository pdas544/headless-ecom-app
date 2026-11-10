<?php

/**
 * Database configuration derived from DATABASE_URL
 * (e.g. pgsql://user:pass@db:5432/ecom?sslmode=require).
 *
 * @return array{
 *     driver: string,
 *     host: string,
 *     port: int,
 *     database: string,
 *     username: string,
 *     password: string,
 *     charset: string,
 *     timezone: string,
 *     sslmode: string,
 *     options: array<int, int|bool>
 * }
 */

declare(strict_types=1);

use App\Core\Config;

return static function (Config $config): array {
    $url = $config->get('DATABASE_URL');
    $parts = parse_url($url);

    if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
        throw new RuntimeException('DATABASE_URL is not a valid URL');
    }

    $scheme = strtolower($parts['scheme']);

    if (!in_array($scheme, ['pgsql', 'postgres', 'postgresql'], true)) {
        throw new RuntimeException('DATABASE_URL must use a pgsql:// scheme');
    }

    $database = ltrim($parts['path'] ?? '', '/');

    if ($database === '' || !isset($parts['user'])) {
        throw new RuntimeException('DATABASE_URL must include a user and a database name');
    }

    $query = [];
    parse_str($parts['query'] ?? '', $query);
    $sslMode = $query['sslmode'] ?? 'prefer';

    return [
        'driver' => 'pgsql',
        'host' => $parts['host'],
        'port' => $parts['port'] ?? 5432,
        'database' => $database,
        'username' => rawurldecode($parts['user']),
        'password' => rawurldecode($parts['pass'] ?? ''),
        'charset' => 'utf8',
        'timezone' => 'UTC',
        'sslmode' => is_string($sslMode) ? $sslMode : 'prefer',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ];
};
