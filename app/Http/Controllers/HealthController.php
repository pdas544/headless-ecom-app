<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\HealthChecker;
use App\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HealthController implements RequestHandlerInterface
{
    public function __construct(
        private readonly JsonResponder $json,
        private readonly HealthChecker $health,
        private readonly Config $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->health->check();

        return $this->json->respond(
            [
                'status' => $result['status'],
                'version' => $this->config->getOptional('APP_VERSION') ?? 'unknown',
                'checks' => $result['checks'],
            ],
            $result['status'] === 'ok' ? 200 : 503,
        );
    }
}
