<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NextSMS Tanzania Configuration
    |--------------------------------------------------------------------------
    | Provider: https://www.nextsms.co.tz / Beem Africa routing
    | Docs: https://messaging-service.co.tz/api/sms/v1/text/single
    | Alternative single-send: https://apisms.beem.africa/v1/send
    |
    | Fill via .env. Service auto-detects auth mode:
    |  - If NEXTSMS_API_KEY is set -> uses Bearer / x-api-key
    |  - Else if USERNAME + PASSWORD -> uses Basic Auth
    | If SMS_DISABLED=true all sends are logged only (for dev).
    */

    'enabled' => env('NEXTSMS_ENABLED', true),

    // Primary single SMS endpoint
    // For NextSMS: https://messaging-service.co.tz/api/sms/v1/text/single
    // For Beem:    https://apisms.beem.africa/v1/send
    'api_url' => env('NEXTSMS_API_URL', 'https://messaging-service.co.tz/api/sms/v1/text/single'),

    // Auth: either api_key OR username/password
    'api_key' => env('NEXTSMS_API_KEY', ''),
    'username' => env('NEXTSMS_USERNAME', ''),
    'password' => env('NEXTSMS_PASSWORD', ''),

    // Sender ID approved at NextSMS (e.g. JOBARN, FrontDesk, NEXTSMS)
    'sender_id' => env('NEXTSMS_SENDER_ID', 'FrontDesk'),

    // Fallback recipient for testing when visitor has no phone (leave empty to skip)
    'test_phone' => env('NEXTSMS_TEST_PHONE', ''),

    // If true, don't call API — just log. Useful for local dev without credits.
    'log_only' => env('NEXTSMS_LOG_ONLY', false),

    // Welcome templates — {name} {company} {host} {badge} will be replaced
    'templates' => [
        'first_visit' => env(
            'NEXTSMS_TEMPLATE_FIRST',
            'Karibu {name} katika duka la JOBARN! Asante kwa kututembelea mara ya kwanza. Host wako ni {host}. Badge yako: {badge}. Tunakuhudumia kwa furaha!'
        ),
        'returning' => env(
            'NEXTSMS_TEMPLATE_RETURNING',
            'Karibu tena {name}! Tunafurahi kukuona tena dukani. Host wako: {host}. Badge: {badge}. Asante!'
        ),
        'checkout' => env(
            'NEXTSMS_TEMPLATE_CHECKOUT',
            'Asante {name} kwa kututembelea duka la JOBARN! Ume-checkout {time} — Badge {badge}. Karibu tena! www.jobarn.co.tz'
        ),
    ],
];
