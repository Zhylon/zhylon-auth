<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OAuth Client
    |--------------------------------------------------------------------------
    |
    | Credentials of your OAuth application registered at ZhylonID.
    |
    */

    'service' => [
        'client_id'     => env('ZHYLON_AUTH_CLIENT_ID'),
        'client_secret' => env('ZHYLON_AUTH_CLIENT_SECRET'),
        'base_uri'      => env('ZHYLON_AUTH_BASE_URI', 'https://id.zhylon.net'),

        // Base URL used to build the OAuth callback URL. Falls back to app.url.
        'callback_website' => env('ZHYLON_AUTH_CALLBACK_WEBSITE'),

        'scopes' => ['profile.read'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The package registers a redirect and a callback route. Every part of the
    | URL can be changed here. Set "enabled" to false to register the routes
    | yourself (keep the route names, they are used to build the callback URL).
    |
    */

    'routes' => [
        'enabled'    => env('ZHYLON_AUTH_ROUTES_ENABLED', true),
        'prefix'     => env('ZHYLON_AUTH_SITE_PATH', '/auth/zhylon'),
        'redirect'   => env('ZHYLON_AUTH_REDIRECT_PATH', '/redirect'),
        'callback'   => env('ZHYLON_AUTH_CALLBACK_PATH', '/callback'),
        'middleware' => ['web'],
        'name'       => 'zhylon-auth.',
        'controller' => Zhylon\ZhylonAuth\Controllers\ZhylonAuthController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    |
    | remember: true    -> always set the "remember me" cookie
    |           false   -> never set it
    |           request -> set it when the redirect route was called with
    |                      ?remember=1 (e.g. from a "stay signed in" checkbox)
    |
    */

    'login' => [
        'guard'     => env('ZHYLON_AUTH_GUARD'),
        'remember'  => env('ZHYLON_AUTH_REMEMBER', false),
        'home'      => env('ZHYLON_AUTH_HOME', '/dashboard'),
        'login_url' => env('ZHYLON_AUTH_LOGIN_URL', '/login'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    'user' => [
        // Falls back to auth.providers.users.model when null.
        'model'           => env('ZHYLON_AUTH_USER_MODEL'),
        'create_team'     => env('ZHYLON_AUTH_CREATE_TEAM', true),
        'random_password' => env('ZHYLON_AUTH_RANDOM_PASSWORD', true),
    ],

];
