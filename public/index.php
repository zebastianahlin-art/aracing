<?php

declare(strict_types=1);

use App\Core\Http\Response;
use App\Modules\Redirect\Repositories\RedirectRepository;
use App\Modules\Redirect\Services\RedirectService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';


$timezone = getenv('APP_TIMEZONE') ?: 'Europe/Stockholm';
date_default_timezone_set($timezone);

$sessionPath = getenv('SESSION_SAVE_PATH') ?: dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
require dirname(__DIR__) . '/routes/web.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

if (in_array($method, ['GET', 'HEAD'], true)) {
    $redirectService = new RedirectService(new RedirectRepository($app['pdo']));
    $requestPath = parse_url($uri, PHP_URL_PATH) ?: '/';
    $resolved = $redirectService->resolveForPath($requestPath);

    if ($resolved !== null) {
        $redirectService->registerHit((int) $resolved['redirect_id']);

        $location = (string) $resolved['target_path'];
        $query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
        if ($query !== '') {
            $location .= (str_contains($location, '?') ? '&' : '?') . $query;
        }

        (new Response('', (int) $resolved['redirect_type'], [
            'Location' => $location,
            'Content-Type' => 'text/html; charset=UTF-8',
        ]))->send();
        exit;
    }
}

$response = $app['router']->dispatch($method, $uri);
$response->send();
