<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\WelcomeController;
use FastRoute\RouteCollector;

return static function (RouteCollector $r): void {
    $r->get('/', WelcomeController::class);

    $r->get('/health', HealthController::class);

    $r->addGroup('/api/v1', static function (RouteCollector $r): void {
        $r->get('', VersionController::class);
    });
};
