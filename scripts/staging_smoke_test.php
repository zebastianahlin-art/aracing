<?php

declare(strict_types=1);

$baseUrl = rtrim((string) (getenv('SMOKE_BASE_URL') ?: 'http://127.0.0.1:8000'), '/');

$routes = [
    // Storefront
    '/',
    '/category/test',
    '/product/test',
    '/search?q=test',
    '/cart',
    '/checkout',
    // Customer/account
    '/login',
    '/register',
    '/account',
    '/wishlist',
    '/stock-alerts',
    '/compare',
    // Admin
    '/admin',
    '/admin/products',
    '/admin/orders',
    '/admin/purchasing',
    '/admin/fitment/workflow',
    '/admin/ai/import',
    '/admin/supplier-monitoring',
    '/admin/ai-operational-reports',
    '/admin/ai-inventory-insights',
    '/admin/ai-pricing-insights',
    '/admin/ai-assortment-gap-insights',
    '/admin/ai-merchandising-insights',
];

$errors = [];
foreach ($routes as $route) {
    $url = $baseUrl . $route;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HEADER => false,
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '') {
        $errors[] = sprintf('%s -> curl error: %s', $route, $error);
        echo sprintf("✖ %s -> curl error: %s\n", $route, $error);
        continue;
    }

    $ok = $status >= 200 && $status < 400;
    if (!$ok) {
        $errors[] = sprintf('%s -> HTTP %d', $route, $status);
    }

    $bodyLength = is_string($body) ? strlen($body) : 0;
    echo sprintf("%s %s -> HTTP %d (%d bytes)\n", $ok ? '✔' : '✖', $route, $status, $bodyLength);
}

if ($errors !== []) {
    fwrite(STDERR, "\nSmoke test misslyckades:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "\nSmoke test OK för alla routes.\n";
