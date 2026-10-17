<?php

function authorizeQuery(string $location): array
{
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return $query;
}

it('stores a relative intended path', function () {
    $this->get('/auth/zhylon/redirect?intended=/settings?tab=profile')
        ->assertSessionHas('url.intended', '/settings?tab=profile');
});

it('stores an intended url on the same host', function () {
    $this->get('/auth/zhylon/redirect?intended='.urlencode('http://localhost/settings'))
        ->assertSessionHas('url.intended', 'http://localhost/settings');
});

it('ignores intended urls pointing elsewhere', function (string $url) {
    $this->get('/auth/zhylon/redirect?intended='.urlencode($url))
        ->assertSessionMissing('url.intended');
})->with([
    'other host'          => 'https://evil.test/settings',
    'protocol relative'   => '//evil.test/settings',
    'backslash'           => '/\\evil.test',
    'javascript'          => 'javascript:alert(1)',
    'subdomain lookalike' => 'http://localhost.evil.test/',
    'no leading slash'    => 'evil.test',
    'tab in path'         => "/\t/evil.test",
]);

it('sends the user to the intended path after login', function () {
    $this->fakeZhylonUser();

    $sessionCookie = config('session.cookie');
    $sessionId = $this->get('/auth/zhylon/redirect?intended=/settings')->getCookie($sessionCookie)->getValue();

    $this->withCookie($sessionCookie, $sessionId)
        ->get('/auth/zhylon/callback?code=abc')
        ->assertRedirect('http://localhost/settings');
});

it('passes the login hint to zhylon', function () {
    $location = $this->get('/auth/zhylon/redirect?login_hint='.urlencode(' jane@example.com '))
        ->headers->get('Location');

    expect(authorizeQuery($location)['login_hint'])->toBe('jane@example.com');
});

it('does not send an empty login hint', function () {
    $location = $this->get('/auth/zhylon/redirect?login_hint=')->headers->get('Location');

    expect(authorizeQuery($location))->not->toHaveKey('login_hint');
});
