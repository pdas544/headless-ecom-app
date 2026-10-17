<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Exception\HttpException;
use App\Core\ProblemResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Kernel
{
    public function __construct(private readonly ProblemResponder $problemResponder)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemResponder->respondTo(HttpException::notFound(
            sprintf('No route matches %s /%s.', $request->getMethod(), ltrim($request->getUri()->getPath(), '/')),
        ));
    }
}
