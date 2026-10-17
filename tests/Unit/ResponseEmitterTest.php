<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\ResponseEmitter;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;

final class ResponseEmitterTest extends TestCase
{
    public function testEmitsResponseBody(): void
    {
        $response = (new ResponseFactory())
            ->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new StreamFactory())->createStream('{"ok":true}'));

        ob_start();
        (new ResponseEmitter())->emit($response);
        $output = ob_get_clean();

        self::assertSame('{"ok":true}', $output);
    }
}
