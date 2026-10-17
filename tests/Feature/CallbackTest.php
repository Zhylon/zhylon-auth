<?php

use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Zhylon\ZhylonAuth\Tests\Fixtures\User;
use Zhylon\ZhylonAuth\Tests\Fixtures\OtherUser;
use Zhylon\ZhylonAuth\Exceptions\ZhylonException;

it('throws when the code is missing', function () {
    $this->withoutExceptionHandling()->get('/auth/zhylon/callback');
})->throws(ZhylonException::class, 'You must authorize the application');

it('wraps errors from socialite', function () {
    Socialite::shouldReceive('driver')->andThrow(new RuntimeException('boom'));

    try {
        $this->withoutExceptionHandling()->get('/auth/zhylon/callback?code=abc');
    } catch (ZhylonException $e) {
        expect($e->getPrevious())->toBeInstanceOf(RuntimeException::class);

        return;
    }

    $this->fail('ZhylonException was not thrown.');
});

it('creates a new user and logs them in', function () {
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc')->assertRedirect('/dashboard');

    $user = User::sole();

    expect($user->zhylon_id)->toBe('zhylon-1')
        ->and($user->email)->toBe('jane@example.com')
        ->and($user->zhylon_token)->toBe('access-token')
        ->and($user->zhylon_refresh_token)->toBe('refresh-token')
        ->and($user->password)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('does not set a password when random_password is disabled', function () {
    $this->withPackageConfig(['user.random_password' => false]);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc');

    expect(User::sole()->password)->toBeNull();
});

it('links an existing user by email and verifies the email', function () {
    $user = User::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('secret')]);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc')->assertRedirect('/dashboard');

    $user->refresh();

    expect(User::count())->toBe(1)
        ->and($user->zhylon_id)->toBe('zhylon-1')
        ->and($user->zhylon_token)->toBe('access-token')
        ->and($user->hasVerifiedEmail())->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('logs in a returning user', function () {
    $user = User::create(['name' => 'Jane', 'email' => 'jane@example.com', 'zhylon_id' => 'zhylon-1']);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc')->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('rejects a linked user whose email has changed', function () {
    User::create(['name' => 'Jane', 'email' => 'old@example.com', 'zhylon_id' => 'zhylon-1']);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc')
        ->assertRedirect('/login')
        ->assertSessionHasErrors();

    $this->assertGuest();
});

it('redirects to the configured login url on errors', function () {
    $this->withPackageConfig(['login.login_url' => '/sign-in']);
    User::create(['name' => 'Jane', 'email' => 'old@example.com', 'zhylon_id' => 'zhylon-1']);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc')->assertRedirect('/sign-in');
});

it('redirects to the configured home', function () {
    $this->withPackageConfig(['login.home' => '/home']);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc')->assertRedirect('/home');
});

it('redirects to the intended url', function () {
    $this->fakeZhylonUser();

    $this->withSession(['url.intended' => 'https://app.test/settings'])
        ->get('/auth/zhylon/callback?code=abc')
        ->assertRedirect('https://app.test/settings');
});

it('uses the configured guard', function () {
    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);
    config()->set('zhylon-auth.login.guard', 'admin');
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc');

    $this->assertAuthenticated('admin');
    $this->assertGuest('web');
});

it('uses the configured user model', function () {
    $this->withPackageConfig(['user.model' => OtherUser::class]);
    $this->fakeZhylonUser();

    $this->get('/auth/zhylon/callback?code=abc');

    expect(auth()->user())->toBeInstanceOf(OtherUser::class);
});
