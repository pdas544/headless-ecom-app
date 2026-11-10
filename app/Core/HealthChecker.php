<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs dependency probes lazily: probes are closures so the app (and this
 * service) can be constructed even when Postgres/Redis are unavailable —
 * a failed probe degrades the health response instead of crashing the request.
 */
final class HealthChecker
{
    /**
     * @param Closure(): void $databaseProbe
     * @param Closure(): void $cacheProbe
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Closure $databaseProbe,
        private readonly Closure $cacheProbe,
    ) {
    }

    /**
     * @return array{status: 'ok'|'degraded', checks: array{app: 'ok', database: 'ok'|'fail', cache: 'ok'|'fail'}}
     */
    public function check(): array
    {
        $database = $this->probe('database', $this->databaseProbe);
        $cache = $this->probe('cache', $this->cacheProbe);

        return [
            'status' => ($database === 'ok' && $cache === 'ok') ? 'ok' : 'degraded',
            'checks' => [
                'app' => 'ok',
                'database' => $database,
                'cache' => $cache,
            ],
        ];
    }

    /**
     * @param Closure(): void $probe
     *
     * @return 'ok'|'fail'
     */
    private function probe(string $name, Closure $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (Throwable $exception) {
            $this->logger->warning('Health check failed', [
                'check' => $name,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return 'fail';
        }
    }
}
