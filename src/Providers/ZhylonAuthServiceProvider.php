<?php

namespace Zhylon\ZhylonAuth\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class ZhylonAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/zhylon-auth.php', 'zhylon-auth'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/zhylon-auth.php' => config_path('zhylon-auth.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../../migrations/' => database_path('migrations'),
        ], 'migrations');

        if (config('zhylon-auth.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes.php');
        }

        $this->registerSocialiteDriver();
    }

    private function registerSocialiteDriver(): void
    {
        // Laravel 13 binds extend() callbacks to the manager, so $this inside
        // them is not this provider. Capture the URL builder beforehand.
        $callbackUrl = fn (): string => $this->callbackUrl();

        $this->callAfterResolving(Factory::class, function (Factory $socialite) use ($callbackUrl) {
            $socialite->extend('zhylon', function () use ($socialite, $callbackUrl) {
                $config = config('zhylon-auth.service');
                $config['redirect'] = $callbackUrl();

                return $socialite->buildProvider(SocialiteZhylonProvider::class, $config);
            });
        });
    }

    /**
     * Build the absolute callback URL from the registered route, so it always
     * matches the configured paths.
     */
    private function callbackUrl(): string
    {
        $website = config('zhylon-auth.service.callback_website') ?: config('app.url');

        $routes = config('zhylon-auth.routes');
        $name = ($routes['name'] ?? 'zhylon-auth.').'callback';

        $path = Route::has($name)
            ? route($name, [], false)
            : '/'.trim($routes['prefix'] ?? '', '/').'/'.trim($routes['callback'] ?? 'callback', '/');

        return rtrim($website, '/').'/'.ltrim($path, '/');
    }
}
