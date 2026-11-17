<?php

declare(strict_types=1);

namespace Tests\Integration;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Boots the real DI container and runs the full middleware pipeline
 * (no HTTP layer) to verify the API contract end to end.
 */
final class ApiSmokeTest extends IntegrationTestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string, 2: int, 3: string, 4: string, 5: string}>
     */
    public static function scenarioProvider(): iterable
    {
        yield 'welcome' => ['GET', '/', 200, 'application/json', '', ''];
        yield 'version' => ['GET', '/api/v1', 200, 'application/json', '', ''];
        yield 'unknown route' => ['GET', '/products', 404, 'application/problem+json', '', ''];
        yield 'wrong method' => ['POST', '/health', 405, 'application/problem+json', '', ''];
        yield 'invalid json body' => [
            'POST',
            '/health',
            400,
            'application/problem+json',
            '{"bad json',
            'application/json',
        ];
    }

    #[DataProvider('scenarioProvider')]
    public function testApiContract(
        string $method,
        string $path,
        int $expectedStatus,
        string $expectedContentType,
        string $rawBody,
        string $requestContentType,
    ): void {
        $request = new ServerRequest([], [], $this->uri($path), $method);

        if ($rawBody !== '') {
            $request = $request
                ->withHeader('Content-Type', $requestContentType)
                ->withBody((new StreamFactory())->createStream($rawBody));
        }

        $response = $this->kernel()->handle($request);

        self::assertSame($expectedStatus, $response->getStatusCode());
        self::assertSame($expectedContentType, $response->getHeaderLine('Content-Type'));
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $response->getHeaderLine('X-Request-Id'));
    }

    public function testHealthResponseShape(): void
    {
        $response = $this->kernel()->handle(
            new ServerRequest([], [], $this->uri('/health'), 'GET'),
        );

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertContains($response->getStatusCode(), [200, 503]);
        self::assertSame($body['status'] === 'ok' ? 200 : 503, $response->getStatusCode());
        self::assertSame('0.1.0', $body['version']);

        /** @var array<string, string> $checks */
        $checks = $body['checks'];
        self::assertSame(['app', 'database', 'cache'], array_keys($checks));
        self::assertSame('ok', $checks['app']);
        self::assertContains($checks['database'], ['ok', 'fail']);
        self::assertContains($checks['cache'], ['ok', 'fail']);
    }

    public function testHealthReportsOkWhenDependenciesAreReachable(): void
    {
        if (!$this->infraReachable()) {
            self::markTestSkipped('Postgres/Redis not reachable from this runtime; run inside the app container.');
        }

        $response = $this->kernel()->handle(
            new ServerRequest([], [], $this->uri('/health'), 'GET'),
        );

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'status' => 'ok',
            'version' => '0.1.0',
            'checks' => ['app' => 'ok', 'database' => 'ok', 'cache' => 'ok'],
        ], $body);
    }

    private function infraReachable(): bool
    {
        foreach (['DATABASE_URL', 'REDIS_URL'] as $key) {
            $url = getenv($key);

            if (!is_string($url)) {
                return false;
            }

            $parts = parse_url($url);

            if ($parts === false || !isset($parts['host'], $parts['port'])) {
                return false;
            }

            $socket = @fsockopen($parts['host'], (int) $parts['port'], $errorCode, $errorMessage, 0.3);
            $reachable = is_resource($socket);

            if ($reachable) {
                fclose($socket);
            }

            if (!$reachable) {
                return false;
            }
        }

        return true;
    }
}
