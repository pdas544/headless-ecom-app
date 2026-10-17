<?php

declare(strict_types=1);

use App\Core\ErrorHandler;
use App\Core\ResponseEmitter;
use App\Http\Kernel;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;

$basePath = dirname(__DIR__);

require $basePath . '/vendor/autoload.php';

/** @var ContainerInterface $container */
$container = require $basePath . '/config/container.php';

$errorHandler = $container->get(ErrorHandler::class);
assert($errorHandler instanceof ErrorHandler);
$errorHandler->register();

$kernel = $container->get(Kernel::class);
assert($kernel instanceof Kernel);

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);

$response = $kernel->handle(ServerRequestFactory::fromGlobals());

$emitter->emit($response);
