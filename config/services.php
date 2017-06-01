<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'facebook' => [
        'client_id' => '1327709913915742',
        'client_secret' => 'bab6811c5a86356978b92af47f655ecc',
        'redirect' => 'http://localhost',
    ],
    'google' => [
        'client_id' => '85643794193-tf4jmni7bqirqqvm3fio8l0qgc5idmrh.apps.googleusercontent.com',
        'client_secret' => '',
        'redirect' => 'http://localhost',
    ],
    'twitter' => [
        'client_id' => 'v9ZuzZASJZ6W5AdtGBf6KuDtY',
        'client_secret' => 'jlBiEuMjqggShJwG7BztRZHSREp0RrQRPn1fTQQTzXX8FZgxDS',
        'redirect' => 'http://localhost',
    ]
];
