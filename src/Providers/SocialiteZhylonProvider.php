<?php

namespace Zhylon\ZhylonAuth\Providers;

use Laravel\Socialite\Two\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Illuminate\Http\Client\ConnectionException;

class SocialiteZhylonProvider extends AbstractProvider
{
    public function getZhylonUrl(): string
    {
        return config('zhylon-auth.service.base_uri');
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getZhylonUrl().'/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getZhylonUrl().'/oauth/token';
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     *
     * @throws ConnectionException
     */
    public function getAccessTokenResponse($code): array
    {
        $pendingRequest = Http::withHeaders($this->getTokenHeaders($code));
        $pendingRequest->withoutVerifying();
        $response = $pendingRequest->post(
            $this->getTokenUrl(),
            $this->getTokenFields($code)
        );

        return $response->json();
    }

    /**
     * @throws ConnectionException
     */
    protected function getUserByToken($token)
    {
        $pendingRequest = Http::withToken($token);
        $pendingRequest->withoutVerifying();
        $response = $pendingRequest->get($this->getZhylonUrl().'/api/userinfo');

        return $response->json();
    }

    protected function mapUserToObject(array $user): User
    {
        $user = $user['user'];

        return (new User)->setRaw($user)->map([
            'id'             => $user['id'],
            'email'          => $user['email'],
            'name'           => $user['name'],
            'nickname'       => $user['name'],
            'email_verified' => $user['email_verified'],
        ]);
    }
}
