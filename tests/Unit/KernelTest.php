<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Kernel;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class KernelTest extends TestCase
{
    private const REQUIRED_ENV = [
        'APP_NAME' => 'headless-ecom',
        'APP_ENV' => 'local',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'http://localhost:8081',
        'DATABASE_URL' => 'pgsql://ecom:ecom@db:5432/ecom',
        'REDIS_URL' => 'redis://cache:6379',
        'LOG_LEVEL' => 'debug',
        'APP_VERSION' => '0.1.0',
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

    public function testWelcomeEndpointReturnsOkJson(): void
    {
        $response = $this->kernel()->handle(new ServerRequest([], [], 'https://api.test/', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ok', $body['status']);
    }

    public function testUnmatchedRouteReturnsProblemJson404(): void
    {
        $response = $this->kernel()->handle(new ServerRequest([], [], 'https://api.test/products', 'GET'));

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(404, $body['status']);
        self::assertSame('https://headless-ecom.dev/problems/not-found', $body['type']);
        self::assertStringContainsString('GET /products', (string) $body['detail']);
    }

    public function testWrongMethodReturnsProblemJson405(): void
    {
        $response = $this->kernel()->handle(new ServerRequest([], [], 'https://api.test/health', 'POST'));

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(405, $body['status']);
    }

    public function testInvalidJsonBodyReturnsProblemJson400(): void
    {
        $request = (new ServerRequest(
            [],
            [],
            'https://api.test/health',
            'POST',
            'php://temp',
            ['Content-Type' => ['application/json']],
        ))->withBody((new StreamFactory())->createStream('{"name": "'));

        $response = $this->kernel()->handle($request);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(400, $body['status']);
    }

    public function testRequestHasCorrelationIdHeader(): void
    {
        $response = $this->kernel()->handle(new ServerRequest([], [], 'https://api.test/health', 'GET'));

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $response->getHeaderLine('X-Request-Id'));
    }

    public function testPreflightCorsFromAllowedOriginReturns204WithHeaders(): void
    {
        $request = new ServerRequest([], [], 'https://api.test/health', 'OPTIONS', 'php://temp', [
            'Origin' => ['http://localhost:3000'],
            'Access-Control-Request-Method' => ['POST'],
            'Access-Control-Request-Headers' => ['Content-Type'],
        ]);

        $response = $this->kernel()->handle($request);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertSame('GET, POST, PUT, PATCH, DELETE', $response->getHeaderLine('Access-Control-Allow-Methods'));
    }

    public function testDisallowedOriginGetsNoCorsHeader(): void
    {
        $response = $this->kernel()->handle(new ServerRequest([], [], 'https://api.test/', 'GET', 'php://temp', [
            'Origin' => ['https://evil.example.com'],
        ]));

        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    private function kernel(): Kernel
    {
        $kernel = $this->container->get(Kernel::class);
        \assert($kernel instanceof Kernel);

        return $kernel;
    }
}
