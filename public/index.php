<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/bootstrap/app.php';

if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
require dirname(__DIR__) . '/routes/web.php';

$response = $app['router']->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
$response->send();
