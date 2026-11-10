<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use Closure;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseConfigTest extends TestCase
{
    public function testParsesDatabaseUrl(): void
    {
        $db = $this->load('pgsql://ecom:s3cr%40t@db:5432/ecom?sslmode=require');

        self::assertSame('pgsql', $db['driver']);
        self::assertSame('db', $db['host']);
        self::assertSame(5432, $db['port']);
        self::assertSame('ecom', $db['database']);
        self::assertSame('ecom', $db['username']);
        self::assertSame('s3cr@t', $db['password']);
        self::assertSame('require', $db['sslmode']);
        self::assertSame('UTC', $db['timezone']);
        self::assertFalse($db['options'][PDO::ATTR_EMULATE_PREPARES]);
        self::assertSame(PDO::ERRMODE_EXCEPTION, $db['options'][PDO::ATTR_ERRMODE]);
    }

    public function testDefaultsPortAndSslMode(): void
    {
        $db = $this->load('postgres://ecom:ecom@db/ecom');

        self::assertSame(5432, $db['port']);
        self::assertSame('prefer', $db['sslmode']);
    }

    public function testRejectsUnknownScheme(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/pgsql:\/\/ scheme/');

        $this->load('mysql://ecom:ecom@db:3306/ecom');
    }

    public function testRejectsMissingDatabase(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/user and a database name/');

        $this->load('pgsql://ecom@db:5432');
    }

    /**
     * @return array{
     *     driver: string, host: string, port: int, database: string, username: string, password: string,
     *     charset: string, timezone: string, sslmode: string, options: array<int, int|bool>
     * }
     */
    private function load(string $url): array
    {
        /** @var Closure(Config): array<string, mixed> $loader */
        $loader = require dirname(__DIR__, 2) . '/config/database.php';

        /** @var array{
         *     driver: string, host: string, port: int, database: string, username: string, password: string,
         *     charset: string, timezone: string, sslmode: string, options: array<int, int|bool>
         * } $db
         */
        $db = $loader(Config::fromArray(['DATABASE_URL' => $url]));

        return $db;
    }
}
