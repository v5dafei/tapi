<?php

return [

    'defaults' => [
        'guard' => 'carrier',
    ],

    'guards' => [
        'carrier' => [
            'driver' => 'jwt',
            'provider' => 'carrier_user',
        ],
        'admin' => [
            'driver' => 'jwt',
            'provider' => 'admin_user',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'user',
        ],
        'agent' => [
            'driver' => 'jwt',
            'provider' => 'agent',
        ],
        'navigation' => [
            'driver' => 'jwt',
            'provider' => 'navigation_user',
        ]
    ],

    'providers' => [
        'carrier_user' => [
            'driver' => 'eloquent',
            'model' => \App\Models\CarrierUser::class,
        ],
        'admin_user'   => [
            'driver' => 'eloquent',
            'model' => \App\Models\AdminUser::class,
        ],
        'user'   => [
            'driver' => 'eloquent',
            'model' => \App\Models\Player::class,
        ],
        'agent'   => [
            'driver' => 'eloquent',
            'model' => \App\Models\Player::class,
        ],
        'navigation_user'   => [
            'driver' => 'eloquent',
            'model' => \App\Models\NavigationPlayer::class,
        ]
    ],

    'passwords' => [
    ],

    'password_timeout' => 10800,
];
