<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\HealthChecker;
use App\Core\LoggerFactory;
use App\Core\ProblemResponder;
use App\Core\RedisClient;
use App\Core\ResponseEmitter;
use App\Core\Router;
use App\Http\Middleware\CatchThrowableMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\JsonBodyParserMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\RouterMiddleware;
use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

$basePath = dirname(__DIR__);

$routes = require $basePath . '/routes/api.php';
assert($routes instanceof Closure);

/** @var Closure(Config): array<string, mixed> $databaseConfigLoader */
$databaseConfigLoader = require $basePath . '/config/database.php';

$builder = new ContainerBuilder();

$builder->addDefinitions([

    Config::class => static fn (): Config => Config::load($basePath),

    LoggerInterface::class => static fn (Config $config): LoggerInterface => LoggerFactory::create($config),

    ErrorHandler::class => static fn (LoggerInterface $logger, Config $config): ErrorHandler
        => new ErrorHandler($logger, $config),

    ResponseFactoryInterface::class => static fn (): ResponseFactoryInterface => new ResponseFactory(),

    StreamFactoryInterface::class => static fn (): StreamFactoryInterface => new StreamFactory(),

    ResponseEmitter::class => static fn (): ResponseEmitter => new ResponseEmitter(),

    Router::class => static fn (): Router => new Router($routes),

    PDO::class => static fn (Config $config): PDO => Database::pdo($databaseConfigLoader($config)),

    Capsule::class => static fn (Config $config): Capsule => Database::bootEloquent($databaseConfigLoader($config)),

    ClientInterface::class => static fn (Config $config): ClientInterface
        => RedisClient::fromUrl($config->get('REDIS_URL')),

    HealthChecker::class => static fn (Config $config, LoggerInterface $logger): HealthChecker
        => new HealthChecker(
            $logger,
            static function () use ($config, $databaseConfigLoader): void {
                $pdo = Database::pdo($databaseConfigLoader($config));
                $statement = $pdo->query('SELECT 1');

                if ($statement === false) {
                    throw new RuntimeException('Database health query failed');
                }

                $statement->fetchColumn();
            },
            static function () use ($config): void {
                $status = RedisClient::fromUrl($config->get('REDIS_URL'))->ping();

                if (strtoupper((string) $status) !== 'PONG') {
                    throw new RuntimeException('Unexpected Redis PING response');
                }
            },
        ),

    ProblemResponder::class => static fn (
        ErrorHandler $errorHandler,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ProblemResponder => new ProblemResponder($errorHandler, $responseFactory, $streamFactory),

    CatchThrowableMiddleware::class => static fn (ProblemResponder $responder): CatchThrowableMiddleware
        => new CatchThrowableMiddleware($responder),

    RequestIdMiddleware::class => static fn (): RequestIdMiddleware => new RequestIdMiddleware(),

    CorsMiddleware::class => static fn (
        Config $config,
        ResponseFactoryInterface $responseFactory,
    ): CorsMiddleware => new CorsMiddleware($config, $responseFactory),

    JsonBodyParserMiddleware::class => static fn (ProblemResponder $responder): JsonBodyParserMiddleware
        => new JsonBodyParserMiddleware($responder),

    RouterMiddleware::class => static fn (
        Router $router,
        DI\Container $container,
        ProblemResponder $responder,
    ): RouterMiddleware => new RouterMiddleware($router, $container, $responder),
]);

return $builder->build();
