<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\HealthChecker;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HealthCheckerTest extends TestCase
{
    public function testReportsOkWhenAllProbesPass(): void
    {
        $handler = new TestHandler();
        $checker = new HealthChecker(
            new Logger('test', [$handler]),
            static function (): void {
            },
            static function (): void {
            },
        );

        self::assertSame([
            'status' => 'ok',
            'checks' => ['app' => 'ok', 'database' => 'ok', 'cache' => 'ok'],
        ], $checker->check());
        self::assertCount(0, $handler->getRecords());
    }

    public function testDegradesWhenDatabaseProbeFails(): void
    {
        $handler = new TestHandler();
        $checker = new HealthChecker(
            new Logger('test', [$handler]),
            static function (): void {
                throw new RuntimeException('connection refused');
            },
            static function (): void {
            },
        );

        $result = $checker->check();

        self::assertSame('degraded', $result['status']);
        self::assertSame('fail', $result['checks']['database']);
        self::assertSame('ok', $result['checks']['cache']);
        self::assertCount(1, $handler->getRecords());

        /** @var array{context: array{check: string}} $record */
        $record = $handler->getRecords()[0];
        self::assertSame('database', $record['context']['check']);
    }

    public function testDegradesWhenAllProbesFail(): void
    {
        $checker = new HealthChecker(
            new Logger('test', [new TestHandler()]),
            static function (): void {
                throw new RuntimeException('db down');
            },
            static function (): void {
                throw new RuntimeException('cache down');
            },
        );

        self::assertSame(
            ['app' => 'ok', 'database' => 'fail', 'cache' => 'fail'],
            $checker->check()['checks'],
        );
    }
}
