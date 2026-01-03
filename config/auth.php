<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MongoDB User Provider
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'mongodb',                 // 👈 IMPORTANT
            'model' => App\Models\User::class,     // 👈 Must extend MongoDB Model
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset for MongoDB Users
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',    // You can keep this table in MongoDB OR MySQL
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
