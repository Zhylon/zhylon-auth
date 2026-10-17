<?php

use Laravel\Socialite\Facades\Socialite;
use Zhylon\ZhylonAuth\Providers\SocialiteZhylonProvider;

function zhylonRedirectUrl(): string
{
    $driver = Socialite::driver('zhylon');

    return (new ReflectionProperty($driver, 'redirectUrl'))->getValue($driver);
}

it('registers the zhylon driver', function () {
    expect(Socialite::driver('zhylon'))->toBeInstanceOf(SocialiteZhylonProvider::class);
});

it('builds the callback url from app.url', function () {
    expect(zhylonRedirectUrl())->toBe('https://app.test/auth/zhylon/callback');
});

it('builds the callback url from callback_website', function () {
    $this->withPackageConfig(['service.callback_website' => 'https://other.test/']);

    expect(zhylonRedirectUrl())->toBe('https://other.test/auth/zhylon/callback');
});

it('follows the configured route paths', function () {
    $this->withPackageConfig([
        'routes.prefix'   => '/sso',
        'routes.callback' => '/back',
    ]);

    expect(zhylonRedirectUrl())->toBe('https://app.test/sso/back');
});

it('falls back to the configured paths when routes are disabled', function () {
    $this->withPackageConfig([
        'routes.enabled'  => false,
        'routes.prefix'   => 'sso/',
        'routes.callback' => 'back',
    ]);

    expect(zhylonRedirectUrl())->toBe('https://app.test/sso/back');
});

it('redirects to the zhylon authorize page with the configured scopes', function () {
    $this->withPackageConfig(['service.scopes' => ['profile.read', 'email']]);

    $location = $this->get('/auth/zhylon/redirect')->assertRedirect()->headers->get('Location');

    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith('https://id.zhylon.net/oauth/authorize?')
        ->and($query['client_id'])->toBe('client-id')
        ->and($query['redirect_uri'])->toBe('https://app.test/auth/zhylon/callback')
        ->and($query['scope'])->toBe('profile.read email');
});
