<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseTest extends TestCase
{
    /** @return array<string, mixed> */
    private function config(): array
    {
        return [
            'driver' => 'pgsql',
            'host' => 'db',
            'port' => 5432,
            'database' => 'ecom',
            'username' => 'ecom',
            'password' => 'ecom',
            'charset' => 'utf8',
            'timezone' => 'UTC',
            'sslmode' => 'require',
            'options' => [],
        ];
    }

    public function testBuildsPostgresDsnWithTimeoutAndSslMode(): void
    {
        $dsn = Database::dsn($this->config());

        self::assertSame(
            'pgsql:host=db;port=5432;dbname=ecom;sslmode=require;connect_timeout=2',
            $dsn,
        );
    }

    public function testDsnDefaultsSslModeToPrefer(): void
    {
        $config = $this->config();
        unset($config['sslmode']);

        self::assertStringContainsString('sslmode=prefer', Database::dsn($config));
    }

    public function testPdoRejectsMissingHostBeforeConnecting(): void
    {
        $config = $this->config();
        unset($config['host']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/'host' must be a non-empty string/");

        Database::pdo($config);
    }

    public function testDsnRejectsInvalidPort(): void
    {
        $config = $this->config();
        $config['port'] = '5432';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/'port' must be a positive integer/");

        Database::dsn($config);
    }
}
