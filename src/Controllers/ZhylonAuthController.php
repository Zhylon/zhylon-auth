<?php

namespace TobyMaxham\ZhylonAuth\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use TobyMaxham\ZhylonAuth\Exceptions\ZhylonException;

class ZhylonAuthController
{
    public function redirect()
    {
        return Socialite::driver('zhylon')->redirect();
    }

    public function callback()
    {
        if (!request()->has('code')) {
            throw new ZhylonException('You must authorize the application to access your Zhylon account.');
        }

        try {
            $driver = Socialite::driver('zhylon');
            $zhylonUser = $driver->user();
        } catch (\Exception|\TypeError $e) {
            throw new ZhylonException('Error while fetching user data from Zhylon.');
        }

        /** @var User $user */
        $user = User::where('zhylon_id', $zhylonUser->id)
            ->orWhere('email', $zhylonUser->email)
            ->first();

        $fields = [
            'zhylon_id'            => $zhylonUser->id,
            'name'                 => $zhylonUser->name,
            'email'                => $zhylonUser->email,
            'zhylon_token'         => $zhylonUser->token,
            'zhylon_refresh_token' => $zhylonUser->refreshToken,
        ];

        if ($user) {
            if($this->handleExistingUser($user, $fields)) {
                return redirect('/login')->withErrors(
                    __('auth.oauth.we are unable to login you in because you already have an account with this email')
                );
            }
        } else {
            $fields['password'] = Str::random(32);
            $user = User::create($fields);
        }

        $this->createTeam($user);
        Auth::login($user);

        return redirect(config('zhylon-auth.service.home'));
    }

    private function handleExistingUser(User $user, array $fields): bool
    {
        if (!empty($user->zhylon_id)) {
            return false;
        }

        $user->update($fields);

        return true;
    }

    private function createTeam($user): void
    {
        if (!class_exists('\Laravel\Jetstream\Jetstream') || !class_exists('\App\Models\Team')) {
            return;
        }

        if (!\Laravel\Jetstream\Jetstream::hasTeamFeatures()) {
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
