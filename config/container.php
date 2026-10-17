<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\LoggerFactory;
use App\Core\ResponseEmitter;
use DI\ContainerBuilder;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

$basePath = dirname(__DIR__);

$builder = new ContainerBuilder();

$builder->addDefinitions([
    Config::class => static fn (): Config => Config::load($basePath),

    LoggerInterface::class => static fn (Config $config): LoggerInterface => LoggerFactory::create($config),

    ErrorHandler::class => static fn (LoggerInterface $logger, Config $config): ErrorHandler
        => new ErrorHandler($logger, $config),

    ResponseFactoryInterface::class => static fn (): ResponseFactoryInterface => new ResponseFactory(),

    StreamFactoryInterface::class => static fn (): StreamFactoryInterface => new StreamFactory(),

    ResponseEmitter::class => static fn (): ResponseEmitter => new ResponseEmitter(),
]);

return $builder->build();
