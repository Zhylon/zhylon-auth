<?php

namespace Zhylon\ZhylonAuth\Tests;

use Mockery;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Database\Schema\Blueprint;
use Zhylon\ZhylonAuth\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;
use Laravel\Socialite\SocialiteServiceProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Zhylon\ZhylonAuth\Providers\ZhylonAuthServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Package config applied before the app boots (routes are registered at boot).
     */
    protected array $packageConfig = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SocialiteServiceProvider::class,
            ZhylonAuthServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', 'https://app.test');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('zhylon-auth.service.client_id', 'client-id');
        $app['config']->set('zhylon-auth.service.client_secret', 'client-secret');

        foreach ($this->packageConfig as $key => $value) {
            $app['config']->set('zhylon-auth.'.$key, $value);
        }
    }

    /**
     * Reboot the application with the given package config.
     */
    protected function withPackageConfig(array $config): static
    {
        $this->packageConfig = $config;

        $this->refreshApplication();
        $this->createUsersTable();

        return $this;
    }

    protected function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('zhylon_id')->nullable();
            $table->text('zhylon_token')->nullable();
            $table->text('zhylon_refresh_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Fake the Zhylon Socialite driver to return the given user.
     */
    protected function fakeZhylonUser(array $attributes = []): SocialiteUser
    {
        $user = (new SocialiteUser)->map(array_merge([
            'id'    => 'zhylon-1',
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
        ], $attributes));
        $user->token = 'access-token';
        $user->refreshToken = 'refresh-token';

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($user);
        $driver->shouldReceive('scopes')->andReturnSelf();
        $driver->shouldReceive('with')->andReturnSelf();
        $driver->shouldReceive('redirect')->andReturn(redirect('https://id.zhylon.net/oauth/authorize'));

        Socialite::shouldReceive('driver')->with('zhylon')->andReturn($driver);

        return $user;
    }
}
