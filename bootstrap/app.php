<?php

declare(strict_types=1);

use App\Core\Config\Config;
use App\Core\Routing\Router;
use App\Core\Support\Environment;
use App\Core\View\ViewFactory;

require_once dirname(__DIR__) . '/vendor/autoload.php';

Environment::load(dirname(__DIR__) . '/.env');

$config = new Config(require dirname(__DIR__) . '/config/app.php');
$router = new Router();
$viewFactory = new ViewFactory(dirname(__DIR__) . '/resources/views');

return [
    'config' => $config,
    'router' => $router,
    'view' => $viewFactory,
];
