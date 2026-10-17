# Changelog

All notable changes to `zhylon/zhylon-auth` will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [v2.2] — 2026-10-15

### Fixed

- Users who already had a `zhylon_id` were rejected on every login with "already have an account with this email". The error is now only shown when the Zhylon ID exists but the e-mail address differs.

### Added

- Linking a Zhylon account to an existing user now marks the e-mail address as verified (`email_verified_at`), since Zhylon has already confirmed it.

---

## [v2.1] — 2026-04-27

### Added

- New `random_password` config option (`ZHYLON_AUTH_RANDOM_PASSWORD`, default `true`) — when enabled, a cryptographically random 32-character password is automatically hashed and assigned to newly created users.

### Fixed

- OAuth callback URL now falls back to `app.url` if `callback_website` is not configured or empty in `zhylon-auth.service`, preventing a broken redirect on minimal setups.

---

## [v2.0] — 2026-02-26

### ⚠️ Breaking Changes

**Package renamed**
The Composer package name changed from `tobymaxham/zhylon-auth` to `zhylon/zhylon-auth`.

```bash
composer require zhylon/zhylon-auth
```

**Namespace changed**
All classes moved from `TobyMaxham\ZhylonAuth\` to `Zhylon\ZhylonAuth\`. Update any direct class references in your application:

| Old                                                         | New                                                     |
|-------------------------------------------------------------|---------------------------------------------------------|
| `TobyMaxham\ZhylonAuth\Controllers\ZhylonAuthController`    | `Zhylon\ZhylonAuth\Controllers\ZhylonAuthController`    |
| `TobyMaxham\ZhylonAuth\Exceptions\ZhylonException`          | `Zhylon\ZhylonAuth\Exceptions\ZhylonException`          |
| `TobyMaxham\ZhylonAuth\Providers\ZhylonAuthServiceProvider` | `Zhylon\ZhylonAuth\Providers\ZhylonAuthServiceProvider` |
| `TobyMaxham\ZhylonAuth\Providers\SocialiteZhylonProvider`   | `Zhylon\ZhylonAuth\Providers\SocialiteZhylonProvider`   |

**Minimum requirements raised**

| Dependency          | Old    | New     |
|---------------------|--------|---------|
| PHP                 | `^8.0` | `^8.3`  |
| `laravel/socialite` | `^5.5` | `^5.24` |

---

### Added

- Named routes: the redirect and callback routes are now registered as `zhylon-auth.redirect` and `zhylon-auth.callback`.
- New `create_team` config option (`ZHYLON_AUTH_CREATE_TEAM`, default `true`) — controls whether a Jetstream team is automatically created on first login. Set to `false` to disable team creation even without Jetstream installed.
- OAuth redirect now explicitly requests the `profile.read` scope.
- `@throws` PHPDoc annotations on all methods that can throw exceptions.
- Return type declarations on all provider and controller methods.

### Changed

- Dev dependency switched from `friendsofphp/php-cs-fixer` to `laravel/pint ^1.27`.
- Repository and issue tracker URLs updated to `github.com/zhylon/zhylon-auth`.
- Code style updated to PSR-12 / Pint conventions (import ordering, brace placement).

---

## [v1.1] — 2024-04-15

### Changed

- Improved error handling across the OAuth callback flow.

---

## [v1.0] — 2022-12-27

- Initial release.