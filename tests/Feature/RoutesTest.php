<?php

use Illuminate\Support\Facades\Route;
use Zhylon\ZhylonAuth\Tests\Fixtures\CustomController;

it('registers the default routes', function () {
    expect(route('zhylon-auth.redirect', [], false))->toBe('/auth/zhylon/redirect')
        ->and(route('zhylon-auth.callback', [], false))->toBe('/auth/zhylon/callback');
});

it('uses the configured prefix and paths', function () {
    $this->withPackageConfig([
        'routes.prefix'   => '/sso',
        'routes.redirect' => '/go',
        'routes.callback' => '/back',
    ]);

    expect(route('zhylon-auth.redirect', [], false))->toBe('/sso/go')
        ->and(route('zhylon-auth.callback', [], false))->toBe('/sso/back');
});

it('uses the configured route name prefix', function () {
    $this->withPackageConfig(['routes.name' => 'sso.']);

    expect(Route::has('sso.redirect'))->toBeTrue()
        ->and(Route::has('sso.callback'))->toBeTrue()
        ->and(Route::has('zhylon-auth.redirect'))->toBeFalse();
});

it('uses the configured middleware', function () {
    $this->withPackageConfig(['routes.middleware' => ['web', 'guest']]);

    expect(Route::getRoutes()->getByName('zhylon-auth.redirect')->middleware())
        ->toBe(['web', 'guest']);
});

it('uses the configured controller', function () {
    $this->withPackageConfig(['routes.controller' => CustomController::class]);

    $this->get('/auth/zhylon/redirect')->assertRedirect('/custom-redirect');
});

it('does not register routes when disabled', function () {
    $this->withPackageConfig(['routes.enabled' => false]);

    expect(Route::has('zhylon-auth.redirect'))->toBeFalse()
        ->and(Route::has('zhylon-auth.callback'))->toBeFalse();
});
