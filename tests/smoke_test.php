<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
require dirname(__DIR__) . '/routes/web.php';

$paths = ['/', '/category/test', '/product/test', '/cart', '/checkout', '/checkout/confirmation', '/admin', '/admin/orders'];

foreach ($paths as $path) {
    $response = $app['router']->dispatch('GET', $path);
    $reflect = new ReflectionClass($response);
    $prop = $reflect->getProperty('status');
    $prop->setAccessible(true);
    $status = $prop->getValue($response);

    if ($status !== 200) {
        fwrite(STDERR, "Expected 200 for {$path}, got {$status}\n");
        exit(1);
    }
}

echo "Smoke routes OK\n";
