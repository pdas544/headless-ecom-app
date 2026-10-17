<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Exception\HttpException;
use App\Core\ProblemResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonBodyParserMiddleware implements MiddlewareInterface
{
    private const BODY_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private readonly ProblemResponder $responder)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! in_array($request->getMethod(), self::BODY_METHODS, true)) {
            return $handler->handle($request);
        }

        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        if ($contentType === '' || ! str_contains($contentType, 'application/json')) {
            return $handler->handle($request);
        }

        $raw = trim((string) $request->getBody());

        if ($raw === '') {
            return $handler->handle($request->withParsedBody(null));
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return $this->responder->respondTo(HttpException::badRequest('Request body is not valid JSON.'));
        }

        return $handler->handle($request->withParsedBody($decoded));
    }
}
