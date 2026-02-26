<?php

namespace Zhylon\ZhylonAuth\Controllers;

use TypeError;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Zhylon\ZhylonAuth\Exceptions\ZhylonException;

class ZhylonAuthController
{
    public function redirect()
    {
        return Socialite::driver('zhylon')
            ->scopes(['profile.read'])
            ->redirect();
    }

    /**
     * @throws ZhylonException
     */
    public function callback()
    {
        if (! request()->has('code')) {
            throw new ZhylonException('You must authorize the application to access your Zhylon account.');
        }

        try {
            $driver = Socialite::driver('zhylon');
            $zhylonUser = $driver->user();

            /** @var User $user */
            $user = User::where('zhylon_id', $zhylonUser->id)
                ->orWhere('email', $zhylonUser->email)
                ->first();

            if ($user) {
                return $this->handleExistingUser($user, $zhylonUser);
            }

            $createUser = User::create([
                'zhylon_id'            => $zhylonUser->id,
                'name'                 => $zhylonUser->name,
                'email'                => $zhylonUser->email,
                'zhylon_token'         => $zhylonUser->token,
                'zhylon_refresh_token' => $zhylonUser->refreshToken,
            ]);

            $this->createTeam($createUser);

            return $this->loginAndRedirect($createUser);
        } catch (Exception|TypeError) {
            throw new ZhylonException('Error while fetching user data from Zhylon.');
        }
    }

    private function handleExistingUser(User $user, $zhylonUser)
    {
        if (! empty($user->zhylon_id)) {
            return redirect('/login')->withErrors(
                __('auth.oauth.we are unable to login you in because you already have an account with this email')
            );
        }

        $user->update([
            'zhylon_id'            => $zhylonUser->id,
            'zhylon_token'         => $zhylonUser->token,
            'zhylon_refresh_token' => $zhylonUser->refreshToken,
        ]);

        $this->createTeam($user);

        return $this->loginAndRedirect($user);
    }

    private function loginAndRedirect(User $user)
    {
        Auth::login($user);

        return redirect(config('zhylon-auth.service.home'));
    }

    private function createTeam($user): void
    {
        if (! config('zhylon-auth.service.create_team')
            || ! class_exists('\Laravel\Jetstream\Jetstream')
            || ! class_exists('\App\Models\Team')) {
            return;
        }

        if (! \Laravel\Jetstream\Jetstream::hasTeamFeatures()) {
            return;
        }

        if ($user->personalTeam()) {
            return;
        }

        $user->ownedTeams()->save(\App\Models\Team::forceCreate([
            'user_id'       => $user->id,
            'name'          => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
