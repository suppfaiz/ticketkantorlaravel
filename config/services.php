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

    'sso' => [
        'driver' => env('SSO_DRIVER', 'local'),
        'api_url' => env('SSO_API_URL', 'https://sso.kantor.com/api/v1'),
        'client_id' => env('SSO_CLIENT_ID', 'it-helpdesk-client'),
        'client_secret' => env('SSO_CLIENT_SECRET', ''),
        'jwt_public_key' => env('SSO_JWT_PUBLIC_KEY', ''),
        'jwt_algorithm' => env('SSO_JWT_ALGORITHM', 'RS256'),
        'jwt_issuer' => env('SSO_JWT_ISSUER'),
        'jwt_audience' => env('SSO_JWT_AUDIENCE'),
    ],

    'loa' => [
        'driver' => env('LOA_DRIVER', 'local'),
        'api_url' => env('LOA_API_URL', 'https://loa.kantor.com/api/v1'),
        'api_key' => env('LOA_API_KEY', ''),
        'webhook_secret' => env('LOA_WEBHOOK_SECRET', ''),
        'mock_auto_approve' => env('LOA_MOCK_AUTO_APPROVE', false),
    ],

];
