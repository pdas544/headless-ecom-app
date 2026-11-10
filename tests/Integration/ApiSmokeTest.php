<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Kernel;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Boots the real DI container and runs the full middleware pipeline
 * (no HTTP layer) to verify the API contract end to end.
 */
final class ApiSmokeTest extends TestCase
{
    private const REQUIRED_ENV = [
        'APP_NAME' => 'headless-ecom',
        'APP_ENV' => 'local',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'http://localhost:8081',
        'APP_VERSION' => '0.1.0',
        'DATABASE_URL' => 'pgsql://ecom:ecom@db:5432/ecom',
        'REDIS_URL' => 'redis://cache:6379',
        'LOG_LEVEL' => 'debug',
        'JWT_SECRET' => 'test-secret-test-secret-test-secret-1234567890',
        'JWT_TTL' => '900',
        'REFRESH_TTL_DAYS' => '30',
        'MAIL_DSN' => 'smtp://mailpit:1025',
        'MAIL_FROM' => 'no-reply@example.test',
        'CORS_ALLOWED_ORIGINS' => 'http://localhost:3000',
    ];

    private ContainerInterface $container;

    protected function setUp(): void
    {
        foreach (self::REQUIRED_ENV as $key => $value) {
            putenv("{$key}={$value}");
        }

        $this->container = require dirname(__DIR__, 2) . '/config/container.php';
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::REQUIRED_ENV) as $key) {
            putenv($key);
        }
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: int, 3: string, 4: string, 5: string}>
     */
    public static function scenarioProvider(): iterable
    {
        yield 'welcome' => ['GET', '/', 200, 'application/json', '', ''];
        yield 'health' => ['GET', '/health', 200, 'application/json', '', ''];
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

        self::assertSame([
            'status' => 'ok',
            'version' => '0.1.0',
            'checks' => [
                'app' => 'ok',
                'database' => 'not-configured',
                'cache' => 'not-configured',
            ],
        ], $body);
    }

    private function kernel(): Kernel
    {
        $kernel = $this->container->get(Kernel::class);
        assert($kernel instanceof Kernel);

        return $kernel;
    }

    private function uri(string $path): string
    {
        return 'https://api.test' . $path;
    }
}
