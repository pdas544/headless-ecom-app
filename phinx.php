<?php

declare(strict_types=1);

$root = __DIR__;

$fileValues = [];
$envPath = $root . '/.env';

if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
            $value = substr($value, 1, -1);
        }

        $fileValues[$key] = $value;
    }
}

$env = static function (string $key) use ($fileValues): ?string {
    foreach ([$_SERVER, $_ENV] as $source) {
        if (isset($source[$key]) && is_string($source[$key]) && $source[$key] !== '') {
            return $source[$key];
        }
    }

    $value = getenv($key);

    if (is_string($value) && $value !== '') {
        return $value;
    }

    return $fileValues[$key] ?? null;
};

/**
 * @return array{adapter: string, host: string, name: string, user: string, pass: string, port: int, charset: string}
 */
$parseDatabaseUrl = static function (string $url): array {
    $parts = parse_url($url);

    if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
        throw new RuntimeException('Invalid database URL for Phinx');
    }

    if (!in_array(strtolower($parts['scheme']), ['pgsql', 'postgres', 'postgresql'], true)) {
        throw new RuntimeException('Phinx database URL must use a pgsql:// scheme');
    }

    $name = ltrim($parts['path'] ?? '', '/');

    if ($name === '' || !isset($parts['user'])) {
        throw new RuntimeException('Phinx database URL must include a user and a database name');
    }

    return [
        'adapter' => 'pgsql',
        'host' => $parts['host'],
        'name' => $name,
        'user' => rawurldecode($parts['user']),
        'pass' => rawurldecode($parts['pass'] ?? ''),
        'port' => (int) ($parts['port'] ?? 5432),
        'charset' => 'utf8',
    ];
};

$databaseUrl = $env('DATABASE_URL')
    ?? throw new RuntimeException('DATABASE_URL must be set to run Phinx');

$development = $parseDatabaseUrl($databaseUrl);

$testUrl = $env('DATABASE_TEST_URL');
$test = $testUrl !== null ? $parseDatabaseUrl($testUrl) : $development;

if ($testUrl === null) {
    $test['name'] = str_ends_with($development['name'], '_test')
        ? $development['name']
        : $development['name'] . '_test';
}

return [
    'paths' => [
        'migrations' => $root . '/database/migrations',
        'seeds' => $root . '/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinx_migrations',
        'default_environment' => 'development',
        'development' => $development,
        'test' => $test,
    ],
];
