<?php

Illuminate\Support\Facades\Route::group(['prefix' => config('zhylon-auth.service.site_path'), 'middleware' => ['web']], function () {
    Illuminate\Support\Facades\Route::get('/redirect', [Zhylon\ZhylonAuth\Controllers\ZhylonAuthController::class, 'redirect'])->name('zhylon-auth.redirect');
    Illuminate\Support\Facades\Route::get('/callback', [Zhylon\ZhylonAuth\Controllers\ZhylonAuthController::class, 'callback'])->name('zhylon-auth.callback');
});
