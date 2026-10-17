<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(Config $config, string $stream = 'php://stdout'): LoggerInterface
    {
        $handler = new StreamHandler($stream, self::level($config->get('LOG_LEVEL')));
        $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true));

        $logger = new Logger($config->get('APP_NAME'));
        $logger->pushHandler($handler);
        $logger->pushProcessor(static function (LogRecord $record) use ($config): LogRecord {
            $record->extra['request_id'] = RequestContext::id();
            $record->extra['app_env'] = $config->get('APP_ENV');

            return $record;
        });

        return $logger;
    }

    private static function level(string $name): Level
    {
        return match (strtolower($name)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Debug,
        };
    }
}
