# Changelog

All notable changes to `zhylon/zhylon-auth` will be documented in this file

## v2.0 - 2026-02-26

### BREAKING CHANGES

- **Package renamed**: The Composer package name changed from `tobymaxham/zhylon-auth` to `zhylon/zhylon-auth`. Update your `composer.json` and run `composer require zhylon/zhylon-auth` to migrate.
- **Namespace changed**: All classes moved from `TobyMaxham\ZhylonAuth\` to `Zhylon\ZhylonAuth\`. Any application code that references these classes directly must be updated:
  - `TobyMaxham\ZhylonAuth\Controllers\ZhylonAuthController` → `Zhylon\ZhylonAuth\Controllers\ZhylonAuthController`
  - `TobyMaxham\ZhylonAuth\Exceptions\ZhylonException` → `Zhylon\ZhylonAuth\Exceptions\ZhylonException`
  - `TobyMaxham\ZhylonAuth\Providers\ZhylonAuthServiceProvider` → `Zhylon\ZhylonAuth\Providers\ZhylonAuthServiceProvider`
  - `TobyMaxham\ZhylonAuth\Providers\SocialiteZhylonProvider` → `Zhylon\ZhylonAuth\Providers\SocialiteZhylonProvider`
- **PHP requirement raised**: Minimum PHP version is now `^8.3` (previously `^8.0`).
- **Socialite requirement raised**: Minimum `laravel/socialite` version is now `^5.24` (previously `^5.5`).

### Added

- Named routes: redirect and callback routes are now registered as `zhylon-auth.redirect` and `zhylon-auth.callback`.
- New `create_team` config option (`ZHYLON_AUTH_CREATE_TEAM`, default `true`) to control whether a Jetstream team is automatically created on first login. Set to `false` to disable team creation without needing Jetstream.
- OAuth redirect now explicitly requests the `profile.read` scope.
- `@throws` PHPDoc annotations on methods that can throw exceptions.
- Return type declarations on all provider and controller methods.

### Changed

- Dev dependency switched from `friendsofphp/php-cs-fixer` to `laravel/pint ^1.27`.
- Repository and issue tracker URLs updated to `github.com/zhylon/zhylon-auth`.
- Code style updated to PSR-12 / Pint conventions (import ordering, brace placement).

## v1.1 - 2024-04-15

- better error handling

## v1.0 - 2022-12-27

- initial release