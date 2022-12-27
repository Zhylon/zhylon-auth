# Zhylon oAuth via LaravelSocialite

## Composer install package
```bash
composer require tobymaxham/zhylon-auth
```

## Config and Migration

```bash
php artisan vendor:publish
php artisan migrate
```

Change you `.env` file:

```dotenv
ZHYLON_AUTH_CLIENT_ID=
ZHYLON_AUTH_CLIENT_SECRET=
ZHYLON_AUTH_CALLBACK_WEBSITE="https://your-website.com"
ZHYLON_AUTH_SITE_PATH="/zhylon-auth"
ZHYLON_AUTH_BASE_URI="https://zhylon.de"
ZHYLON_AUTH_HOME="/dashboard"
```
