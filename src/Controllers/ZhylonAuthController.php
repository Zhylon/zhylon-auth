<?php

namespace Zhylon\ZhylonAuth\Controllers;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Laravel\Socialite\Facades\Socialite;
use Zhylon\ZhylonAuth\Exceptions\ZhylonException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * All helper methods are protected, so the controller can be extended and
 * swapped via the "zhylon-auth.routes.controller" config option.
 */
class ZhylonAuthController
{
    protected const string REMEMBER_SESSION_KEY = 'zhylon-auth.remember';

    public function redirect(Request $request): SymfonyRedirectResponse
    {
        if ('request' === $this->rememberMode()) {
            $request->session()->put(self::REMEMBER_SESSION_KEY, $request->boolean('remember'));
        }

        if ($intended = $this->intendedUrl($request)) {
            redirect()->setIntendedUrl($intended);
        }

        $driver = Socialite::driver('zhylon')
            ->scopes(config('zhylon-auth.service.scopes', ['profile.read']));

        if ($loginHint = $this->loginHint($request)) {
            $driver->with(['login_hint' => $loginHint]);
        }

        return $driver->redirect();
    }

    /**
     * The "intended" query parameter, if it points to this application.
     * Only relative paths and absolute URLs on the current host are accepted,
     * everything else is ignored to prevent open redirects.
     */
    protected function intendedUrl(Request $request): ?string
    {
        $url = $request->query('intended');

        if (! is_string($url) || '' === $url || strlen($url) > 2048) {
            return null;
        }

        // Backslashes and control characters can trick browsers into treating
        // a path like "/\evil.com" as an external URL.
        if (preg_match('/[\\\\\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($scheme, ['http', 'https'], true)
            && is_string($host)
            && 0 === strcasecmp($host, $request->getHost())) {
            return $url;
        }

        return null;
    }

    /**
     * The "login_hint" query parameter (usually an e-mail address), passed on
     * to ZhylonID to pre-fill the login form.
     */
    protected function loginHint(Request $request): ?string
    {
        $hint = $request->query('login_hint');

        if (! is_string($hint) || '' === trim($hint) || strlen($hint) > 255) {
            return null;
        }

        return trim($hint);
    }

    /**
     * @throws ZhylonException
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! $request->has('code')) {
            throw new ZhylonException('You must authorize the application to access your Zhylon account.');
        }

        try {
            $zhylonUser = Socialite::driver('zhylon')->user();
        } catch (Throwable $e) {
            throw new ZhylonException('Error while fetching user data from Zhylon.', previous: $e);
        }

        $user = $this->findUser($zhylonUser);

        if (! $user) {
            $user = $this->createUser($zhylonUser);

            return $this->loginAndRedirect($request, $user);
        }

        // Zhylon ID exists, but the e-mail address differs
        if (! empty($user->zhylon_id) && $user->email != $zhylonUser->getEmail()) {
            return redirect(config('zhylon-auth.login.login_url', '/login'))->withErrors(
                __('auth.oauth.we are unable to login you in because you already have an account with this email')
            );
        }

        $this->updateUser($user, $zhylonUser);

        return $this->loginAndRedirect($request, $user);
    }

    protected function findUser(SocialiteUser $zhylonUser): ?Model
    {
        return $this->userModel()::query()
            ->where('zhylon_id', $zhylonUser->getId())
            ->orWhere('email', $zhylonUser->getEmail())
            ->first();
    }

    protected function createUser(SocialiteUser $zhylonUser): Model
    {
        $user = $this->userModel()::create($this->getUserData($zhylonUser));

        $this->createTeam($user);

        return $user;
    }

    protected function updateUser(Model $user, SocialiteUser $zhylonUser): void
    {
        $user->update([
            'zhylon_id'            => $zhylonUser->getId(), // may be null or numeric when created before 2024-10-20
            'zhylon_token'         => $zhylonUser->token,
            'zhylon_refresh_token' => $zhylonUser->refreshToken,
        ]);

        // Zhylon has already confirmed ownership of this email address, so
        // linking a Zhylon account also counts as verifying it.
        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->createTeam($user);
    }

    protected function loginAndRedirect(Request $request, Model $user): RedirectResponse
    {
        Auth::guard(config('zhylon-auth.login.guard'))->login($user, $this->shouldRemember($request));

        return redirect()->intended(config('zhylon-auth.login.home', '/dashboard'));
    }

    protected function shouldRemember(Request $request): bool
    {
        return match ($this->rememberMode()) {
            'request' => (bool) $request->session()->pull(self::REMEMBER_SESSION_KEY, false),
            'always'  => true,
            default   => false,
        };
    }

    /**
     * Normalizes the "remember" config value to "always", "never" or "request".
     */
    protected function rememberMode(): string
    {
        $value = config('zhylon-auth.login.remember', false);

        if (is_string($value) && 'request' === strtolower($value)) {
            return 'request';
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'always' : 'never';
    }

    /**
     * @return class-string<Model>
     */
    protected function userModel(): string
    {
        return config('zhylon-auth.user.model')
            ?: config('auth.providers.users.model', 'App\\Models\\User');
    }

    protected function createTeam(Model $user): void
    {
        if (! config('zhylon-auth.user.create_team')
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

    protected function getUserData(SocialiteUser $zhylonUser): array
    {
        $data = [
            'zhylon_id'            => $zhylonUser->getId(),
            'name'                 => $zhylonUser->getName(),
            'email'                => $zhylonUser->getEmail(),
            'zhylon_token'         => $zhylonUser->token,
            'zhylon_refresh_token' => $zhylonUser->refreshToken,
        ];

        if (config('zhylon-auth.user.random_password', true)) {
            $data['password'] = Hash::make(Str::random(32));
        }

        return $data;
    }
}
