<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\HttpException;
use ErrorException;
use Psr\Log\LoggerInterface;
use Throwable;

final class ErrorHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Config $config,
    ) {
    }

    public function register(): void
    {
        set_exception_handler(function (Throwable $throwable): void {
            $this->onThrowable($throwable);
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            return $this->onError($severity, $message, $file, $line);
        });

        register_shutdown_function(function (): void {
            $this->onShutdown();
        });
    }

    public function onThrowable(Throwable $throwable): void
    {
        $this->logger->error('Unhandled throwable', [
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]);

        $this->emit($this->toProblem($throwable));
    }

    /**
     * PHP warnings/notices are logged and suppressed so they never leak as HTML.
     * Fatal errors bypass this handler and are caught by onShutdown().
     */
    public function onError(int $severity, string $message, string $file, int $line): bool
    {
        if ((error_reporting() & $severity) === 0) {
            return false;
        }

        $this->logger->warning('PHP error', [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ]);

        return true;
    }

    public function onShutdown(): void
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $isFatal = in_array(
            $error['type'],
            [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR],
            true,
        );

        if (! $isFatal) {
            return;
        }

        $this->onThrowable(new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line'],
        ));
    }

    public function toProblem(Throwable $throwable): ProblemDetails
    {
        if ($throwable instanceof HttpException) {
            $status = $throwable->status;
            $detail = $throwable->getMessage();
        } else {
            $status = 500;
            $detail = $this->debug() ? $throwable->getMessage() : 'An unexpected error occurred.';
        }

        $extensions = [];

        if ($this->debug()) {
            $extensions['debug'] = [
                'exception' => $throwable::class,
                'trace' => explode("\n", $throwable->getTraceAsString()),
            ];
        }

        return new ProblemDetails(
            status: $status,
            type: ProblemDetails::typeFor($status),
            title: ProblemDetails::titleFor($status),
            detail: $detail,
            instance: $this->instance(),
            extensions: $extensions,
        );
    }

    private function emit(ProblemDetails $problem): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($problem->status);
        header('Content-Type: ' . ProblemDetails::CONTENT_TYPE);

        echo json_encode(
            $problem->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    private function debug(): bool
    {
        return $this->config->isDebug() && $this->config->isLocal();
    }

    private function instance(): ?string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        return is_string($uri) && $uri !== '' ? $uri : null;
    }
}
