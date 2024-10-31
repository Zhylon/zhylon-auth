<?php

return [
    'service' => [
        'client_id'        => env('ZHYLON_AUTH_CLIENT_ID'),
        'client_secret'    => env('ZHYLON_AUTH_CLIENT_SECRET'),
        'callback_website' => env('ZHYLON_AUTH_CALLBACK_WEBSITE'),
        'site_path'        => env('ZHYLON_AUTH_SITE_PATH', '/auth/zhylon'),
        'base_uri'         => env('ZHYLON_AUTH_BASE_URI', 'https://id.zhylon.net'),
        'home'             => env('ZHYLON_AUTH_HOME', '/dashboard'),
    ],
];