<?php

return [

'whatsapp' => [
    'qr_bridge_url' => env('VITE_QR_BRIDGE_URL', 'http://localhost:8602/qr'),
],

'support_api' => [
    'base_url' => env('PFE_BACKEND_URL', 'http://localhost:8600'),
    'public_url' => env('PFE_BACKEND_PUBLIC_URL', env('VITE_API_BASE_URL', 'http://localhost:8600/api/v1')),
    'prefix' => env('PFE_BACKEND_API_PREFIX', '/api/v1'),
    'timeout' => (int) env('PFE_BACKEND_TIMEOUT', 60),
    'bearer_token' => env('PFE_BACKEND_BEARER_TOKEN'),
],

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
'glpi' => [
  'url'       => env('GLPI_URL'),
  'app_token' => env('GLPI_APP_TOKEN'),
  'user_token'=> env('GLPI_USER_TOKEN'),
],
'ai' => [
    'key'      => env('AI_API_KEY', ''),
    'model'    => env('AI_MODEL', 'llama-3.3-70b-versatile'),
    'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai/v1'),
    'timeout'  => env('AI_TIMEOUT', 15),
],
'gmail' => [
    'client_id'     => env('GMAIL_CLIENT_ID'),
    'client_secret' => env('GMAIL_CLIENT_SECRET'),
    'refresh_token' => env('GMAIL_REFRESH_TOKEN'),
],

'teams' => [
    'webhook_url' => env('TEAMS_WEBHOOK_URL'),
],



    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
