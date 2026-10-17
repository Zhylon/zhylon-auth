<?php

use Illuminate\Support\Facades\Auth;
use Zhylon\ZhylonAuth\Tests\Fixtures\User;

function recallerName(): string
{
    return Auth::guard()->getRecallerName();
}

it('sets the remember cookie depending on the config', function (mixed $remember, bool $expected) {
    $this->withPackageConfig(['login.remember' => $remember]);
    $this->fakeZhylonUser();

    $response = $this->get('/auth/zhylon/callback?code=abc');

    $expected
        ? $response->assertCookie(recallerName())
        : $response->assertCookieMissing(recallerName());

    expect(null !== User::sole()->remember_token)->toBe($expected);
})->with([
    'false'            => [false, false],
    'true'             => [true, true],
    'string "false"'   => ['false', false],
    'string "true"'    => ['true', true],
    'string "1"'       => ['1', true],
    'request, no flag' => ['request', false],
]);

it('stores the remember flag from the request in request mode', function () {
    $this->withPackageConfig(['login.remember' => 'request']);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/redirect?remember=1')->assertSessionHas('zhylon-auth.remember', true);
    $this->get('/auth/zhylon/redirect')->assertSessionHas('zhylon-auth.remember', false);
});

it('ignores the remember flag outside of request mode', function () {
    $this->withPackageConfig(['login.remember' => false]);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/redirect?remember=1')->assertSessionMissing('zhylon-auth.remember');
});

it('remembers the user across the full flow when requested', function (string $query, bool $expected) {
    $this->withPackageConfig(['login.remember' => 'request']);
    $this->fakeZhylonUser();

    $sessionCookie = config('session.cookie');
    $sessionId = $this->get('/auth/zhylon/redirect'.$query)->getCookie($sessionCookie)->getValue();

    $response = $this->withCookie($sessionCookie, $sessionId)->get('/auth/zhylon/callback?code=abc');

    $expected
        ? $response->assertCookie(recallerName())
        : $response->assertCookieMissing(recallerName());

    $response->assertSessionMissing('zhylon-auth.remember');
})->with([
    'with ?remember=1' => ['?remember=1', true],
    'with ?remember=0' => ['?remember=0', false],
    'without flag'     => ['', false],
]);
