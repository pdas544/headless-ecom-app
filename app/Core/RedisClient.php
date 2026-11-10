<?php

declare(strict_types=1);

namespace App\Core;

use Predis\Client;
use Predis\ClientInterface;
use RuntimeException;

/**
 * Builds a Predis client from a redis:// or rediss:// URL with short
 * connect/read timeouts so health probes and rate limiting fail fast.
 */
final class RedisClient
{
    public static function fromUrl(string $url): ClientInterface
    {
        if (preg_match('/^rediss?:\/\//', $url) !== 1) {
            throw new RuntimeException('REDIS_URL must use a redis:// or rediss:// scheme');
        }

        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('REDIS_URL is not a valid URL');
        }

        $parameters = [
            'scheme' => strtolower($parts['scheme']) === 'rediss' ? 'tls' : 'tcp',
            'host' => $parts['host'],
            'port' => $parts['port'] ?? 6379,
            'database' => self::databaseIndex($parts['path'] ?? ''),
            'timeout' => 1.0,
            'read_write_timeout' => 1.5,
            'persistent' => false,
        ];

        if (isset($parts['user'])) {
            $parameters['username'] = rawurldecode($parts['user']);
        }

        if (isset($parts['pass'])) {
            $parameters['password'] = rawurldecode($parts['pass']);
        }

        return new Client($parameters);
    }

    private static function databaseIndex(string $path): int
    {
        $index = ltrim($path, '/');

        return $index === '' ? 0 : (int) $index;
    }
}
