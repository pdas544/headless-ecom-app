<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\ProblemDetails;
use App\Core\ProblemResponder;
use App\Http\Kernel;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class KernelTest extends TestCase
{
    public function testUnmatchedRouteReturnsProblemJson404(): void
    {
        $kernel = new Kernel($this->responder());

        $response = $kernel->handle(new ServerRequest([], [], 'https://api.test/products', 'GET'));

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(ProblemDetails::CONTENT_TYPE, $response->getHeaderLine('Content-Type'));

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(404, $body['status']);
        self::assertSame('Not Found', $body['title']);
        self::assertSame('https://headless-ecom.dev/problems/not-found', $body['type']);
        self::assertStringContainsString('GET /products', (string) $body['detail']);
    }

    private function responder(): ProblemResponder
    {
        $config = Config::fromArray(['APP_ENV' => 'local', 'APP_DEBUG' => 'false']);

        return new ProblemResponder(
            new ErrorHandler(new NullLogger(), $config),
            new ResponseFactory(),
            new StreamFactory(),
        );
    }
}
