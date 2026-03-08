<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'A-Racing',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOL),
    'url' => getenv('APP_URL') ?: 'http://127.0.0.1:8000',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Stockholm',
    'locale' => getenv('APP_LOCALE') ?: 'sv',
    'db' => require __DIR__ . '/database.php',
];
