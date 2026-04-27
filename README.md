# Zhylon OAuth2

> **Zhylon OAuth2 Provider for Laravel Socialite**

Integrate [ZhylonID](https://id.zhylon.net) single sign-on into your Laravel
application with just a few lines of code. This package provides a first-class
Laravel Socialite driver for the Zhylon OAuth2 service, handling the full
authentication flow, token management, and user synchronization out of the box.

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
- Configurable **callback URL**, **redirect path**, and **post-login destination**
- Ships with a ready-to-use **migration** for the required user fields
- Minimal setup — works with any existing Laravel auth scaffold

---

## 📦 Requirements

| Dependency        | Version                   |
|-------------------|---------------------------|
| PHP               | `^8.3`                    |
| Laravel           | `^10.0 \| ^11.0 \| ^12.0` |
| Laravel Socialite | `^5.0`                    |

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
php artisan vendor:publish --provider="Zhylon\ZhylonAuth\ZhylonAuthServiceProvider"
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
# The URL path that triggers the OAuth redirect (default: /auth/zhylon)
ZHYLON_AUTH_SITE_PATH="/auth/zhylon"

# The ZhylonID base URI (default: https://id.zhylon.net)
ZHYLON_AUTH_BASE_URI="https://id.zhylon.net"

# Where to redirect the user after a successful login (default: /dashboard)
ZHYLON_AUTH_HOME="/dashboard"
```

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

        return redirect()->intended(config('zhylon-auth.home', '/dashboard'));
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

| Variable                       | Required | Default                 | Description                                      |
|--------------------------------|----------|-------------------------|--------------------------------------------------|
| `ZHYLON_AUTH_CLIENT_ID`        | ✅        | —                       | Your OAuth application's client ID               |
| `ZHYLON_AUTH_CLIENT_SECRET`    | ✅        | —                       | Your OAuth application's client secret           |
| `ZHYLON_AUTH_CALLBACK_WEBSITE` | ❌        | `config('app.url')`     | Base URL of your application (no trailing slash) |
| `ZHYLON_AUTH_SITE_PATH`        | ❌        | `/auth/zhylon`          | Path for the OAuth redirect route                |
| `ZHYLON_AUTH_BASE_URI`         | ❌        | `https://id.zhylon.net` | ZhylonID OAuth server base URL                   |
| `ZHYLON_AUTH_HOME`             | ❌        | `/dashboard`            | Redirect destination after successful login      |

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
value of `ZHYLON_AUTH_CALLBACK_WEBSITE` + `ZHYLON_AUTH_SITE_PATH` + `/callback`.
For example: `https://your-app.com/auth/zhylon/callback`.

**Columns not found after migration**
If the migration did not run, execute `php artisan migrate`. If the columns
are missing from the migration file, re-publish with:
`php artisan vendor:publish --force`.

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