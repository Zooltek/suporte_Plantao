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

    'plenus' => [
        'url' => env('PLENUS_API_URL'),
    ],

    'financeiro' => [
        // Chave de API para autenticar a integração inbound com o sistema
        // financeiro (cabeçalho X-API-Key). Fail-closed: se vazia, o acesso é
        // negado pelo middleware EnsureIntegrationApiKey.
        'api_key' => env('FINANCIAL_INTEGRATION_API_KEY'),
    ],

    'notion' => [
        'api_key' => env('NOTION_API_KEY'),
        'database_id' => env('NOTION_DATABASE_ID'),
        'version' => env('NOTION_VERSION', '2022-06-28'),
    ],

];
