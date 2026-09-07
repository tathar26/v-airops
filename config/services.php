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

    'airlabs' => [
        'key' => env('AIRLABS_API_KEY'),
    ],

    'schedules_api' => [
        'base_url' => env('SCHEDULES_API_BASE_URL', 'https://schedules.artmex-hosting.com'),
        'key' => env('SCHEDULES_API_KEY'),
        'timeout' => (int) env('SCHEDULES_API_TIMEOUT', 30),
        'retry_attempts' => (int) env('SCHEDULES_API_RETRY_ATTEMPTS', 3),
        'retry_sleep' => (int) env('SCHEDULES_API_RETRY_SLEEP_MS', 500),
    ],

    'cloudflare' => [
        'turnstile_site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY'),
        'turnstile_secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
    ],

    'acars' => [
        'live_flight_retention_hours' => (int) env('LIVE_FLIGHT_RETENTION_HOURS', 8),
    ],

    'carto' => [
        'api_key' => env('CARTO_API_KEY'),
    ],

    'vpilot_acars' => [
        'releases_url' => env('VPILOT_ACARS_RELEASES_URL', 'https://gitea.artmex-hosting.com/tathar26/vops-acars/releases/latest'),
    ],

];

