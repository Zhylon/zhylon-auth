# Zhylon oAuth via LaravelSocialite

This package is a LaravelSocialite driver for the Zhylon oAuth service.
To register for the service visit [https://id.zhylon.net](https://id.zhylon.net).


## Composer install package

```bash
composer require tobymaxham/zhylon-auth
```

## Prepare your User model

Since the package will fill the user model with the data from the oAuth provider,
you need to add some fields to your user model `fillable` array.

```php
protected $fillable = [
    'zhylon_id',
    'zhylon_token',
    'zhylon_refresh_token',
];
```

## Config and Migration

```bash
php artisan vendor:publish
php artisan migrate
```

Change you `.env` file:

```dotenv
ZHYLON_AUTH_CLIENT_ID=YOUR_CLIENT_ID
ZHYLON_AUTH_CLIENT_SECRET=YOUR_CLIENT_SECRET
ZHYLON_AUTH_CALLBACK_WEBSITE="https://your-website.com"
```

If you want you could also change some project specific settings:
```dotenv
ZHYLON_AUTH_SITE_PATH="/auth/zhylon"
ZHYLON_AUTH_BASE_URI="https://id.zhylon.net"
ZHYLON_AUTH_HOME="/dashboard"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/Zhylon/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you've found a bug regarding security please mail git@maxham.de instead of using the issue tracker.

## Credits

- [TobyMaxham](https://github.com/TobyMaxham)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.