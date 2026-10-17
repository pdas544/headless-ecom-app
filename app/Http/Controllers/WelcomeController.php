<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WelcomeController implements RequestHandlerInterface
{
    public function __construct(private readonly JsonResponder $json)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json->respond([
            'name' => 'headless-ecom API',
            'status' => 'ok',
            'health' => '/health',
            'api' => '/api/v1',
        ]);
    }
}
