<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'A-Racing',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOL),
    'url' => getenv('APP_URL') ?: 'http://127.0.0.1:8000',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Stockholm',
    'locale' => getenv('APP_LOCALE') ?: 'sv',
    'ai' => [
        'url_import' => [
            'openai_api_key' => getenv('AI_URL_IMPORT_OPENAI_API_KEY') ?: '',
        ],
    ],
    'payment' => [
        'stripe' => [
            'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
            'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
            'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
        ],
    ],
    'db' => require __DIR__ . '/database.php',
];
