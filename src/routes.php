<?php

Illuminate\Support\Facades\Route::group(['prefix' => config('zhylon-auth.service.site_path'), 'middleware' => ['web']], function () {
    Illuminate\Support\Facades\Route::get('/redirect', [TobyMaxham\ZhylonAuth\Controllers\ZhylonAuthController::class, 'redirect']);
    Illuminate\Support\Facades\Route::get('/callback', [TobyMaxham\ZhylonAuth\Controllers\ZhylonAuthController::class, 'callback']);
});
