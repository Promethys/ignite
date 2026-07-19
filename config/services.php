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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'formbricks' => [
        'webhook_secret' => env('FORMBRICKS_WEBHOOK_SECRET'),
        'app_url' => env('FORMBRICKS_APP_URL', 'https://app.formbricks.com'),
        'workspace_id' => env('FORMBRICKS_WORKSPACE_ID'),
    ],

    'discord' => [
        'ops_webhook_url' => env('DISCORD_OPS_WEBHOOK_URL'),
        'ops_enabled' => env('DISCORD_OPS_ENABLED', false),
    ],
];
