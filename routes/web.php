<?php

use Slim\Routing\RouteCollectorProxy;
use Alang\DesafioIpag\Middleware\ApiKeyMiddleware;
use Alang\DesafioIpag\Middleware\JwtMiddleware;
use Alang\DesafioIpag\Middleware\RateLimitMiddleware;
use Alang\DesafioIpag\Controllers\AuthController;
use Alang\DesafioIpag\Controllers\OrderController;
use Alang\DesafioIpag\Controllers\HealthController;

// Grupo de rotas com middleware
$app->group('/auth', function ($group) {
    $group->post('/token', AuthController::class . ':generateToken');
})->add(new ApiKeyMiddleware());

// Rotas protegidas por JWT
$app->group('/orders', function ($group) {
    $group->get('', OrderController::class . ':list');
    $group->post('', OrderController::class . ':create');
    $group->get('/summary', OrderController::class . ':summary');
    $group->get('/{order_id}', OrderController::class . ':get');
    $group->put('/{order_id}/status', OrderController::class . ':updateStatus');
})->add(new JwtMiddleware())->add(new RateLimitMiddleware());

$app->get('/health', HealthController::class . ':check');