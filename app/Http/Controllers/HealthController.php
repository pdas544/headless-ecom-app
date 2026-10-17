<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HealthController implements RequestHandlerInterface
{
    public function __construct(
        private readonly JsonResponder $json,
        private readonly Config $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json->respond([
            'status' => 'ok',
            'version' => $this->config->getOptional('APP_VERSION') ?? 'unknown',
            'checks' => [
                'app' => 'ok',
                'database' => 'not-configured',
                'cache' => 'not-configured',
            ],
        ]);
    }
}
