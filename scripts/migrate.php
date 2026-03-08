<?php

declare(strict_types=1);

$root = __DIR__;
$files = glob($root . '/database/migrations/*.sql') ?: [];
sort($files);

echo "Migration files:\n";
foreach ($files as $file) {
    echo '- ' . basename($file) . PHP_EOL;
}

echo "\nKör SQL-filerna manuellt i MariaDB i denna foundation-fas.\n";
