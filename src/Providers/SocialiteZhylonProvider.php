<?php

namespace TobyMaxham\ZhylonAuth\Providers;

use Laravel\Socialite\Two\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;

class SocialiteZhylonProvider extends AbstractProvider
{
    public function getZhylonUrl(): string
    {
        return config('zhylon-auth.service.base_uri');
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getZhylonUrl().'/oauth/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return $this->getZhylonUrl().'/oauth/token';
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $pendingRequest = Http::withHeaders($this->getTokenHeaders($code));
        $pendingRequest->withoutVerifying();
        $response = $pendingRequest->post(
            $this->getTokenUrl(),
            $this->getTokenFields($code)
        );

        return $response->json();
    }

    protected function getUserByToken($token)
    {
        $pendingRequest = Http::withToken($token);
        $pendingRequest->withoutVerifying();
        $response = $pendingRequest->get($this->getZhylonUrl().'/api/userinfo');

        return $response->json();
    }

    protected function mapUserToObject(array $user)
    {
        $user = $user['user'];

        return (new User())->setRaw($user)->map([
            'id'             => $user['id'],
            'email'          => $user['email'],
            'name'           => $user['name'],
            'nickname'       => $user['name'],
            'email_verified' => $user['email_verified'],
        ]);

    }
}
