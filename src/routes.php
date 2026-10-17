<?php

use Illuminate\Support\Facades\Route;

$config = config('zhylon-auth.routes');

Route::middleware($config['middleware'] ?? ['web'])
    ->prefix($config['prefix'] ?? '/auth/zhylon')
    ->name($config['name'] ?? 'zhylon-auth.')
    ->group(function () use ($config) {
        $controller = $config['controller'] ?? Zhylon\ZhylonAuth\Controllers\ZhylonAuthController::class;

        Route::get($config['redirect'] ?? '/redirect', [$controller, 'redirect'])->name('redirect');
        Route::get($config['callback'] ?? '/callback', [$controller, 'callback'])->name('callback');
    });
