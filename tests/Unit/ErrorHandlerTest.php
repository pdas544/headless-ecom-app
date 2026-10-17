<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Exception\HttpException;
use App\Core\ProblemDetails;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

final class ErrorHandlerTest extends TestCase
{
    public function testHttpExceptionMapsToItsStatus(): void
    {
        $problem = $this->handler(debug: false)->toProblem(HttpException::notFound('No product 42.'));

        self::assertSame(404, $problem->status);
        self::assertSame('Not Found', $problem->title);
        self::assertSame('https://headless-ecom.dev/problems/not-found', $problem->type);
        self::assertSame('No product 42.', $problem->detail);
    }

    public function testUnknownThrowableMapsTo500WithGenericDetail(): void
    {
        $problem = $this->handler(debug: false)->toProblem(new RuntimeException('secret db dsn'));

        self::assertSame(500, $problem->status);
        self::assertSame('An unexpected error occurred.', $problem->detail);
        self::assertArrayNotHasKey('debug', $problem->toArray());
    }

    public function testDebugAddsTraceExtensionOnlyWhenLocalAndDebug(): void
    {
        $problem = $this->handler(debug: true)->toProblem(new RuntimeException('boom'));

        self::assertSame('boom', $problem->detail);

        $array = $problem->toArray();
        self::assertArrayHasKey('debug', $array);
        self::assertSame(RuntimeException::class, $array['debug']['exception']);
    }

    public function testProblemJsonIsSerializableWithContentType(): void
    {
        $problem = $this->handler(debug: false)->toProblem(HttpException::badRequest());

        $json = json_encode($problem, JSON_THROW_ON_ERROR);

        self::assertStringContainsString('"status":400', $json);
        self::assertSame('application/problem+json', ProblemDetails::CONTENT_TYPE);
    }

    public function testUnknownStatusFallsBackToGenericTitle(): void
    {
        self::assertSame('Error', ProblemDetails::titleFor(418));
    }

    private function handler(bool $debug): ErrorHandler
    {
        $config = Config::fromArray([
            'APP_ENV' => 'local',
            'APP_DEBUG' => $debug ? 'true' : 'false',
        ]);

        return new ErrorHandler(new NullLogger(), $config);
    }
}
