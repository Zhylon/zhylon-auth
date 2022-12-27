<?php

namespace TobyMaxham\ZhylonAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class ZhylonAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/zhylon-auth.php', 'zhylon-auth'
        );
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/zhylon-auth.php' => config_path('zhylon-auth.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../../migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->loadRoutes();
    }

    private function loadRoutes()
    {
        $this->loadRoutesFrom(realpath(__DIR__.'/../routes.php'));

        $socialite = $this->app->make(Factory::class);
        $socialite->extend('zhylon', function () use ($socialite) {
            $config = config('zhylon-auth.service');
            $config['redirect'] = $config['callback_website'].$config['site_path'].'/callback';

            return $socialite->buildProvider(SocialiteZhylonProvider::class, $config);
        });
    }
}
