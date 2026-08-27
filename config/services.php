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

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Platform (Meta Cloud API)
    |--------------------------------------------------------------------------
    |
    | Global defaults for the WhatsApp integration. Per-organization
    | credentials (access token, phone number id, app secret, verify token)
    | are stored ENCRYPTED in the database via the WABA configuration UI and
    | always take precedence over the bootstrap values below.
    |
    */

    'whatsapp' => [
        // 'mock' (offline simulator) or 'meta_cloud_api' (real Graph API).
        'driver' => env('WABA_DRIVER', 'mock'),

        'base_url' => env('WABA_BASE_URL', 'https://graph.facebook.com'),
        'api_version' => env('WABA_API_VERSION', 'v22.0'),

        'default_country_code' => (string) env('WABA_DEFAULT_COUNTRY_CODE', '91'),
        'template_language' => env('WABA_TEMPLATE_LANGUAGE', 'en'),

        // Global emergency kill switch. When false, no outbound job calls Meta.
        'sending_enabled' => (bool) env('WHATSAPP_SENDING_ENABLED', true),

        // Dedicated log channel; never records tokens/secrets/authorization headers.
        'log_channel' => env('WABA_LOG_CHANNEL', 'whatsapp'),

        // HTTP client hardening for outbound Graph API + media downloads.
        'http' => [
            'connect_timeout' => (int) env('WABA_HTTP_CONNECT_TIMEOUT', 10),
            'timeout' => (int) env('WABA_HTTP_TIMEOUT', 30),
            'max_redirects' => (int) env('WABA_HTTP_MAX_REDIRECTS', 2),
            'max_download_bytes' => (int) env('WABA_MAX_DOWNLOAD_BYTES', 33_554_432), // 32 MB
        ],

        // Bootstrap / single-tenant fallback credentials. Prefer the DB config.
        'bootstrap' => [
            'access_token' => env('WABA_ACCESS_TOKEN'),
            'phone_number_id' => env('WABA_PHONE_NUMBER_ID'),
            'business_account_id' => env('WABA_BUSINESS_ACCOUNT_ID'),
            'app_id' => env('WABA_APP_ID'),
            'app_secret' => env('WABA_APP_SECRET'),
            'webhook_verify_token' => env('WABA_WEBHOOK_VERIFY_TOKEN'),
        ],

        // Retry backoff (seconds) for transient Meta errors, per attempt.
        'retry_backoff' => [5, 30, 120, 600],
    ],

];
