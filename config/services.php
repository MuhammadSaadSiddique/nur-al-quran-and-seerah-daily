<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'quran' => [
        'client_id' => env('QURAN_CLIENT_ID'),
        'client_secret' => env('QURAN_CLIENT_SECRET'),
        'redirect_uri' => env('QURAN_REDIRECT_URI', env('APP_URL', 'http://localhost') . '/oauth/callback'),
        'auth_url' => env('Quran_Endpoint', 'https://oauth2.quran.foundation'),
        'api_url' => env('Quran_API_Endpoint', 'https://apis.quran.foundation'),
    ],

    'quran_translations' => [
        'base_url' => env('QURAN_TRANSLATIONS_URL', 'https://api.quran.com/api/v4'),
        'ca_bundle' => env('QURAN_TRANSLATIONS_CA_BUNDLE'),
    ],

    'bulk_api_token' => env('BULK_API_TOKEN'),

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
        'server_key' => env('FIREBASE_SERVER_KEY', env('FCM_SERVER_KEY')),
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

];
