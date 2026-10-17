# Zhylon OAuth2

> **Zhylon OAuth2 Provider for Laravel Socialite**

Integrate [ZhylonID](https://id.zhylon.net) single sign-on into your Laravel
application with just a few lines of code. This package provides a first-class
Laravel Socialite driver for the Zhylon OAuth2 service, handling the full
authentication flow, token management, and user synchronization out of the box.

[![Tests](https://img.shields.io/github/actions/workflow/status/Zhylon/zhylon-auth/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Zhylon/zhylon-auth/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/zhylon/zhylon-auth.svg?style=flat-square)](https://packagist.org/packages/zhylon/zhylon-auth)
[![Total Downloads](https://img.shields.io/packagist/dt/zhylon/zhylon-auth.svg?style=flat-square)](https://packagist.org/packages/zhylon/zhylon-auth)
[![License](https://img.shields.io/github/license/Zhylon/zhylon-auth?style=flat-square)](LICENSE.md)

---

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Preparing Your User Model](#-preparing-your-user-model)
- [Usage](#-usage)
    - [OAuth Flow Overview](#oauth-flow-overview)
    - [Controller Example](#controller-example)
    - [Accessing User Data](#accessing-user-data)
    - [Token Refresh](#token-refresh)
- [Environment Variables Reference](#-environment-variables-reference)
- [Troubleshooting](#-troubleshooting)
- [Testing](#-testing)
- [Security](#-security)
- [Changelog](#-changelog)
- [Contributing](#-contributing)
- [Credits](#-credits)
- [License](#-license)

---

## ✨ Features

- Drop-in **Laravel Socialite** driver for Zhylon OAuth2
- Automatic **user creation and synchronization** on login
- Stores `zhylon_id`, `zhylon_token`, and `zhylon_refresh_token` on your User model
- Fully configurable **routes** (prefix, paths, middleware, names, controller)
- Optional **"remember me"** cookie — always, never, or per request
- Configurable **callback URL**, **guard**, and **post-login destination**
- Ships with a ready-to-use **migration** for the required user fields
- Minimal setup — works with any existing Laravel auth scaffold

---

## 📦 Requirements

| Dependency        | Version                            |
|-------------------|------------------------------------|
| PHP               | `^8.3`                             |
| Laravel           | `^10.0 \| ^11.0 \| ^12.0 \| ^13.0` |
| Laravel Socialite | `^5.24`                            |

> **Note:** You need an active ZhylonID account and a registered OAuth
> application. Sign up at [https://id.zhylon.net](https://id.zhylon.net).

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require zhylon/zhylon-auth
```

Publish the configuration file and migration:

```bash
php artisan vendor:publish --provider="Zhylon\ZhylonAuth\Providers\ZhylonAuthServiceProvider"
```

Run the database migration to add the required columns to your `users` table:

```bash
php artisan migrate
```

---

## ⚙️ Configuration

### Environment Variables

Add the following variables to your `.env` file. You will find the client
credentials in your [ZhylonID dashboard](https://id.zhylon.net) after
registering your application.

```dotenv
ZHYLON_AUTH_CLIENT_ID=your-client-id
ZHYLON_AUTH_CLIENT_SECRET=your-client-secret
ZHYLON_AUTH_CALLBACK_WEBSITE="https://your-application.com"
```

**Optional settings** — these have sensible defaults but can be customized:

```dotenv
# The ZhylonID base URI (default: https://id.zhylon.net)
ZHYLON_AUTH_BASE_URI="https://id.zhylon.net"

# Where to redirect the user after a successful login (default: /dashboard)
ZHYLON_AUTH_HOME="/dashboard"

# Set the "remember me" cookie: true, false or request (default: false)
ZHYLON_AUTH_REMEMBER=false
```

### Routes

By default the package registers these routes:

| Name                   | URL                     |
|------------------------|-------------------------|
| `zhylon-auth.redirect` | `/auth/zhylon/redirect` |
| `zhylon-auth.callback` | `/auth/zhylon/callback` |

Every part of the URL can be changed:

```dotenv
ZHYLON_AUTH_SITE_PATH="/sso"         # prefix
ZHYLON_AUTH_REDIRECT_PATH="/login"   # -> /sso/login
ZHYLON_AUTH_CALLBACK_PATH="/return"  # -> /sso/return
```

Middleware, route name prefix and the controller class can be set in the
`routes` section of `config/zhylon-auth.php`. To register the routes yourself,
set `ZHYLON_AUTH_ROUTES_ENABLED=false` and keep the route names
(`zhylon-auth.redirect` / `zhylon-auth.callback`). The OAuth callback URL is
built from the callback route, so it always matches your configuration.

Link to the login with:

```blade
<a href="{{ route('zhylon-auth.redirect') }}">Login with Zhylon</a>
```

### Remember Me

`ZHYLON_AUTH_REMEMBER` controls Laravel's "remember me" cookie:

| Value     | Behavior                                                         |
|-----------|------------------------------------------------------------------|
| `false`   | Never set the cookie (default)                                   |
| `true`    | Always set the cookie                                            |
| `request` | Set it when the redirect route is called with `?remember=1`      |

With `request` you can offer a "stay signed in" checkbox:

```blade
<form method="GET" action="{{ route('zhylon-auth.redirect') }}">
    <label><input type="checkbox" name="remember" value="1"> Stay signed in</label>
    <button type="submit">Login with Zhylon</button>
</form>
```

> Your `users` table needs a `remember_token` column (included in Laravel's
> default migration).

### Login Link Parameters

The redirect route accepts these optional query parameters:

| Parameter    | Description                                                                    |
|--------------|--------------------------------------------------------------------------------|
| `intended`   | Where to send the user after login, e.g. `/settings`                           |
| `login_hint` | E-mail address passed to ZhylonID to pre-fill the login form                   |
| `remember`   | `1` to set the "remember me" cookie (only with `ZHYLON_AUTH_REMEMBER=request`) |

```php
route('zhylon-auth.redirect', [
    'intended'   => '/settings',
    'login_hint' => $email,
    'remember'   => 1,
]);
```

This is also the URL to use as return address for links sent by ZhylonID
(e.g. magic links): after the user is signed in at ZhylonID, the OAuth flow
runs without any further prompt and the user lands on `intended`.

`intended` only accepts paths of your application (`/settings`) or absolute
URLs on the current host. Anything else is ignored to prevent open redirects,
and the user is sent to `ZHYLON_AUTH_HOME` instead. Without `intended`, the
URL Laravel stored when a guest opened a protected page is used.

### Config File

After publishing, the config file is available at `config/zhylon-auth.php`.
This file maps the environment variables above and can be adjusted for more
advanced setups (e.g., per-environment overrides).

---

## 👤 Preparing Your User Model

The package syncs OAuth user data into your `User` model. You need to add the
three Zhylon fields to the `$fillable` array so that mass-assignment works
correctly:

```php
// app/Models/User.php

protected $fillable = [
    'name',
    'email',
    // ... your existing fields ...
    'zhylon_id',
    'zhylon_token',
    'zhylon_refresh_token',
];
```

The migration published in the previous step will automatically add these three columns to your `users` table:

| Column                 | Type           | Description                               |
|------------------------|----------------|-------------------------------------------|
| `zhylon_id`            | `string\|null` | Unique user ID from ZhylonID              |
| `zhylon_token`         | `text\|null`   | Current OAuth access token                |
| `zhylon_refresh_token` | `text\|null`   | OAuth refresh token for re-authentication |

---

## 🧑‍💻 Usage

### OAuth Flow Overview

The package implements the standard **OAuth2 Authorization Code** flow:

```
User clicks "Login with Zhylon"
        │
        ▼
Your app redirects → https://id.zhylon.net/oauth/authorize
        │
        ▼ (User authenticates & grants permission)
        │
Zhylon redirects back → https://your-app.com/auth/zhylon/callback
        │
        ▼
Package exchanges code for token, creates/updates User, logs them in
        │
        ▼
User is redirected to ZHYLON_AUTH_HOME
```

### Controller Example

The package registers the redirect and callback routes automatically.
If you need to build a custom controller or override the default behavior,
here is a full example:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ZhylonAuthController extends Controller
{
    /**
     * Redirect the user to the ZhylonID authorization page.
     */
    public function redirect()
    {
        return Socialite::driver('zhylon')
            ->scopes(['profile.read'])
            ->redirect();
    }

    /**
     * Handle the callback from ZhylonID after authorization.
     */
    public function callback()
    {
        $socialiteUser = Socialite::driver('zhylon')->user();

        $user = User::updateOrCreate(
            ['zhylon_id' => $socialiteUser->getId()],
            [
                'name'                  => $socialiteUser->getName(),
                'email'                 => $socialiteUser->getEmail(),
                'zhylon_token'          => $socialiteUser->token,
                'zhylon_refresh_token'  => $socialiteUser->refreshToken,
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->intended(config('zhylon-auth.login.home', '/dashboard'));
    }
}
```

Register the routes in `routes/web.php`:

```php
use App\Http\Controllers\Auth\ZhylonAuthController;

Route::get('/auth/zhylon', [ZhylonAuthController::class, 'redirect'])->name('auth.zhylon');
Route::get('/auth/zhylon/callback', [ZhylonAuthController::class, 'callback'])->name('auth.zhylon.callback');
```

### Accessing User Data

Once the user is authenticated, you can access the Zhylon-specific fields
directly from the authenticated user:

```php
$user = Auth::user();

echo $user->zhylon_id;            // Zhylon user identifier
echo $user->zhylon_token;         // Current access token
echo $user->zhylon_refresh_token; // Refresh token
```

### Token Refresh

If you need to refresh an expired access token on behalf of a user, you can
use Socialite's `refreshToken` method:

```php
use Laravel\Socialite\Facades\Socialite;

$newToken = Socialite::driver('zhylon')
    ->refreshToken($user->zhylon_refresh_token);

$user->update([
    'zhylon_token'         => $newToken->token,
    'zhylon_refresh_token' => $newToken->refreshToken,
]);
```

> **Note:** ZhylonID may rotate refresh tokens on use. Always persist the new
> refresh token returned from this call.

---

## 📄 Environment Variables Reference

| Variable                       | Required | Default                      | Description                                      |
|--------------------------------|----------|------------------------------|--------------------------------------------------|
| `ZHYLON_AUTH_CLIENT_ID`        | ✅       | —                            | Your OAuth application's client ID               |
| `ZHYLON_AUTH_CLIENT_SECRET`    | ✅       | —                            | Your OAuth application's client secret           |
| `ZHYLON_AUTH_CALLBACK_WEBSITE` | ❌       | `config('app.url')`          | Base URL of your application (no trailing slash) |
| `ZHYLON_AUTH_BASE_URI`         | ❌       | `https://id.zhylon.net`      | ZhylonID OAuth server base URL                   |
| `ZHYLON_AUTH_ROUTES_ENABLED`   | ❌       | `true`                       | Register the package routes                      |
| `ZHYLON_AUTH_SITE_PATH`        | ❌       | `/auth/zhylon`               | Route prefix                                     |
| `ZHYLON_AUTH_REDIRECT_PATH`    | ❌       | `/redirect`                  | Path of the redirect route (after the prefix)    |
| `ZHYLON_AUTH_CALLBACK_PATH`    | ❌       | `/callback`                  | Path of the callback route (after the prefix)    |
| `ZHYLON_AUTH_REMEMBER`         | ❌       | `false`                      | "Remember me" cookie: `true`, `false`, `request` |
| `ZHYLON_AUTH_GUARD`            | ❌       | default guard                | Auth guard used for the login                    |
| `ZHYLON_AUTH_HOME`             | ❌       | `/dashboard`                 | Redirect destination after successful login      |
| `ZHYLON_AUTH_LOGIN_URL`        | ❌       | `/login`                     | Redirect destination when the login fails        |
| `ZHYLON_AUTH_USER_MODEL`       | ❌       | `auth.providers.users.model` | User model class                                 |
| `ZHYLON_AUTH_CREATE_TEAM`      | ❌       | `true`                       | Create a Jetstream personal team on first login  |
| `ZHYLON_AUTH_RANDOM_PASSWORD`  | ❌       | `true`                       | Set a random password for new users              |

---

## 🔧 Troubleshooting

**`InvalidStateException` after callback**
This typically happens when the session is lost between the redirect and the
callback. Make sure your `session` middleware is applied to the callback
route and that your session driver is properly configured.

**`Client error: 401 Unauthorized`**
Double-check that `ZHYLON_AUTH_CLIENT_ID` and `ZHYLON_AUTH_CLIENT_SECRET` in
your `.env` match the credentials in your [ZhylonID dashboard](https://id.zhylon.net).
Also clear the config cache: `php artisan config:clear`.

**Callback URL mismatch**
The callback URL registered in your ZhylonID application must exactly match the
value of `ZHYLON_AUTH_CALLBACK_WEBSITE` + `ZHYLON_AUTH_SITE_PATH` + `ZHYLON_AUTH_CALLBACK_PATH`.
For example: `https://your-app.com/auth/zhylon/callback`.
Run `php artisan route:list --name=zhylon-auth` to see the registered URLs.

**Columns not found after migration**
If the migration did not run, execute `php artisan migrate`. If the columns
are missing from the migration file, re-publish with:
`php artisan vendor:publish --force`.

---

## 🧪 Testing

```bash
composer test
```

The test suite uses [Pest](https://pestphp.com) and
[Orchestra Testbench](https://github.com/orchestral/testbench).

---

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for a full history of changes.

---

## 🤝 Contributing

Contributions are welcome! Please read the [Contributing Guide](https://github.com/Zhylon/.github/blob/main/CONTRIBUTING.md) before submitting a pull request.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-new-feature`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature/my-new-feature`
5. Open a Pull Request

---

## 🔒 Security

- **Never commit** your `.env` file or expose `ZHYLON_AUTH_CLIENT_SECRET` publicly.
- Store tokens (`zhylon_token`, `zhylon_refresh_token`) encrypted in the database using Laravel's [encrypted casting](https://laravel.com/docs/eloquent-mutators#encrypted-casting) if your threat model requires it.
- The callback route should be protected against CSRF. Laravel Socialite handles the `state` parameter automatically to prevent CSRF attacks during the OAuth flow.
- If you discover a **security vulnerability**, please do **not** use the public issue tracker. Instead, send an email to [security@zhylon.net](mailto:security@zhylon.net). All security reports are addressed promptly.

---

## 🙏 Credits

- [TobyMaxham](https://github.com/TobyMaxham)
- [All Contributors](../../contributors)

---

## 📄 License

The MIT License (MIT). Please see the [License File](LICENSE.md) for details.