<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

final class ProblemResponder
{
    public function __construct(
        private readonly ErrorHandler $errorHandler,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function respondTo(Throwable $throwable): ResponseInterface
    {
        return $this->respond($this->errorHandler->toProblem($throwable));
    }

    public function respond(ProblemDetails $problem): ResponseInterface
    {
        $body = json_encode(
            $problem->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        return $this->responseFactory
            ->createResponse($problem->status)
            ->withHeader('Content-Type', ProblemDetails::CONTENT_TYPE)
            ->withBody($this->streamFactory->createStream($body));
    }
}
