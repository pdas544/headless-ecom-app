<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Config;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    private const ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE';

    private const ALLOWED_HEADERS = 'Content-Type, Authorization, X-Request-Id';

    private const MAX_AGE = '86400';

    public function __construct(
        private readonly Config $config,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowed = $this->allowedOrigin($origin);

        if ($origin === '' || $allowed === null) {
            return $handler->handle($request);
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $this->preflight($allowed);
        }

        $response = $handler->handle($request);
        $response = $response->withHeader('Access-Control-Allow-Origin', $allowed);
        $response = $response->withHeader('Vary', 'Origin');

        return $response;
    }

    private function allowedOrigin(string $origin): ?string
    {
        if ($origin === '') {
            return null;
        }

        $allowed = $this->config->getOptional('CORS_ALLOWED_ORIGINS');

        if ($allowed === null) {
            return null;
        }

        foreach (explode(',', $allowed) as $candidate) {
            $candidate = trim($candidate);

            if ($candidate !== '' && $candidate === $origin) {
                return $origin;
            }
        }

        return null;
    }

    private function preflight(string $allowed): ResponseInterface
    {
        return $this->responseFactory->createResponse(204)
            ->withHeader('Access-Control-Allow-Origin', $allowed)
            ->withHeader('Access-Control-Allow-Methods', self::ALLOWED_METHODS)
            ->withHeader('Access-Control-Allow-Headers', self::ALLOWED_HEADERS)
            ->withHeader('Access-Control-Max-Age', self::MAX_AGE)
            ->withHeader('Vary', 'Origin');
    }
}
