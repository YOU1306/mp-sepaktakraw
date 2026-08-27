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

    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        // Used to verify X-Razorpay-Signature on /webhooks/payment (raw body HMAC).
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'sender_id' => env('MSG91_SENDER_ID', 'MPSTKW'),

        /*
        |----------------------------------------------------------------------
        | DLT-approved MSG91 Flow template IDs — one per SMS message type.
        |----------------------------------------------------------------------
        |
        | Indian DLT regulation requires every transactional SMS to match an
        | approved template. Register each template (with a single content
        | placeholder) on your DLT portal + MSG91 dashboard, then put the
        | template IDs here. The full message text is sent as the template's
        | first variable ("1"). Until an ID is set, that message type falls
        | back to free text, which DLT-locked accounts will reject.
        */

        'templates' => [
            'otp' => env('MSG91_TEMPLATE_OTP'),
            'approved' => env('MSG91_TEMPLATE_APPROVED'),
            'rejected' => env('MSG91_TEMPLATE_REJECTED'),
            'membership_expiry' => env('MSG91_TEMPLATE_MEMBERSHIP_EXPIRY'),
        ],
    ],

    'aadhaar' => [
        // Place UIDAI's published Offline-KYC signing certificate (PEM/CER) here.
        // Until this file exists, uploads are extracted but not cryptographically verified.
        'certificate_path' => env('AADHAAR_UIDAI_CERT_PATH', storage_path('app/uidai/uidai_offline_kyc_cert.pem')),
    ],

];
