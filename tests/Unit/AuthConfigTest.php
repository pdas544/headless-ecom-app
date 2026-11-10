<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use Closure;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthConfigTest extends TestCase
{
    /** @var array<string, string> */
    private const BASE_ENV = [
        'APP_ENV' => 'local',
        'APP_URL' => 'http://localhost:8081',
        'JWT_SECRET' => 'test-secret-test-secret-test-secret-1234567890',
        'JWT_TTL' => '900',
        'REFRESH_TTL_DAYS' => '30',
    ];

    public function testLoadsAuthSettings(): void
    {
        $auth = $this->load(self::BASE_ENV);

        self::assertSame('http://localhost:8081', $auth['issuer']);
        self::assertSame('headless-ecom-api', $auth['audience']);
        self::assertSame(900, $auth['jwt_ttl']);
        self::assertSame(30, $auth['refresh_ttl_days']);
        self::assertSame('refresh_token', $auth['refresh_cookie']['name']);
        self::assertSame('/api/v1/auth', $auth['refresh_cookie']['path']);
        self::assertFalse($auth['refresh_cookie']['secure']);
        self::assertTrue($auth['refresh_cookie']['http_only']);
        self::assertSame('Lax', $auth['refresh_cookie']['same_site']);
        self::assertSame(PASSWORD_ARGON2ID, $auth['password']['algo']);
        self::assertSame(65536, $auth['password']['options']['memory_cost']);
    }

    public function testSecureCookieOutsideLocal(): void
    {
        $auth = $this->load([...self::BASE_ENV, 'APP_ENV' => 'production']);

        self::assertTrue($auth['refresh_cookie']['secure']);
    }

    public function testRejectsShortJwtSecret(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/JWT_SECRET must be at least 32 characters/');

        $this->load([...self::BASE_ENV, 'JWT_SECRET' => 'too-short']);
    }

    public function testRejectsOutOfRangeTtls(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/JWT_TTL must be between/');

        $this->load([...self::BASE_ENV, 'JWT_TTL' => '10']);
    }

    /**
     * @param array<string, string> $env
     *
     * @return array{
     *     issuer: string,
     *     audience: string,
     *     jwt_ttl: int,
     *     refresh_ttl_days: int,
     *     refresh_cookie: array{name: string, path: string, secure: bool, http_only: bool, same_site: string},
     *     password: array{algo: string, options: array{memory_cost: int, time_cost: int, threads: int}}
     * }
     */
    private function load(array $env): array
    {
        /** @var Closure(Config): array<string, mixed> $loader */
        $loader = require dirname(__DIR__, 2) . '/config/auth.php';

        /** @var array{
         *     issuer: string,
         *     audience: string,
         *     jwt_ttl: int,
         *     refresh_ttl_days: int,
         *     refresh_cookie: array{name: string, path: string, secure: bool, http_only: bool, same_site: string},
         *     password: array{algo: string, options: array{memory_cost: int, time_cost: int, threads: int}}
         * } $auth
         */
        $auth = $loader(Config::fromArray($env));

        return $auth;
    }
}
