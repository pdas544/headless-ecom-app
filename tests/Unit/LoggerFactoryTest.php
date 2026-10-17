<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\LoggerFactory;
use App\Core\RequestContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class LoggerFactoryTest extends TestCase
{
    private string $stream;

    protected function setUp(): void
    {
        RequestContext::reset();
        $this->stream = tempnam(sys_get_temp_dir(), 'log-test-') ?: throw new RuntimeException('tempnam failed');
    }

    protected function tearDown(): void
    {
        if (is_file($this->stream)) {
            unlink($this->stream);
        }
    }

    public function testCreatesPsr3LoggerWithJsonOutput(): void
    {
        $logger = LoggerFactory::create($this->config('debug'), $this->stream);

        self::assertInstanceOf(LoggerInterface::class, $logger);

        $logger->info('hello', ['key' => 'value']);

        $lines = file($this->stream, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        self::assertCount(1, $lines);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('hello', $decoded['message']);
        self::assertSame('value', $decoded['context']['key']);
        self::assertArrayHasKey('request_id', $decoded['extra']);
        self::assertSame('local', $decoded['extra']['app_env']);
    }

    public function testFallsBackToDebugOnInvalidLevel(): void
    {
        $logger = LoggerFactory::create($this->config('not-a-level'), $this->stream);

        self::assertInstanceOf(LoggerInterface::class, $logger);

        $logger->debug('should be captured');
        self::assertNotSame('', (string) file_get_contents($this->stream));
    }

    private function config(string $level): Config
    {
        return Config::fromArray([
            'APP_NAME' => 'headless-ecom',
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'LOG_LEVEL' => $level,
        ]);
    }
}
