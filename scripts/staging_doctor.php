<?php

declare(strict_types=1);

use App\Core\Support\Environment;

require dirname(__DIR__) . '/vendor/autoload.php';

Environment::load(dirname(__DIR__) . '/.env');

$checks = [];

$requiredEnv = ['APP_ENV', 'APP_URL', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'];
$missingEnv = [];
foreach ($requiredEnv as $key) {
    if (trim((string) getenv($key)) === '') {
        $missingEnv[] = $key;
    }
}
$checks[] = [
    'label' => 'Required env keys',
    'ok' => $missingEnv === [],
    'details' => $missingEnv === [] ? 'OK' : ('Missing: ' . implode(', ', $missingEnv)),
];

$paths = [
    'storage/cache',
    'storage/logs',
    'storage/imports',
    'storage/sessions',
    'public/uploads/product-images',
];
foreach ($paths as $relative) {
    $full = dirname(__DIR__) . '/' . $relative;
    if (!is_dir($full)) {
        @mkdir($full, 0775, true);
    }

    $checks[] = [
        'label' => 'Writable: ' . $relative,
        'ok' => is_dir($full) && is_writable($full),
        'details' => is_dir($full) ? (is_writable($full) ? 'OK' : 'Not writable') : 'Could not create directory',
    ];
}

$databaseConfig = require dirname(__DIR__) . '/config/database.php';
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    (string) ($databaseConfig['host'] ?? '127.0.0.1'),
    (int) ($databaseConfig['port'] ?? 3306),
    (string) ($databaseConfig['database'] ?? ''),
    (string) ($databaseConfig['charset'] ?? 'utf8mb4')
);

$pdo = null;
try {
    $pdo = new PDO($dsn, (string) ($databaseConfig['username'] ?? ''), (string) ($databaseConfig['password'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $checks[] = ['label' => 'Database connection', 'ok' => true, 'details' => 'OK'];
} catch (Throwable $exception) {
    $checks[] = ['label' => 'Database connection', 'ok' => false, 'details' => $exception->getMessage()];
}

if ($pdo instanceof PDO) {
    $hasSchemaMigrations = false;
    $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
    if ($stmt !== false && $stmt->fetch() !== false) {
        $hasSchemaMigrations = true;
    }

    $files = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
    sort($files);

    if ($hasSchemaMigrations) {
        $applied = [];
        foreach ($pdo->query('SELECT migration FROM schema_migrations') as $row) {
            $applied[(string) $row['migration']] = true;
        }

        $pending = 0;
        foreach ($files as $file) {
            if (!isset($applied[basename($file)])) {
                $pending++;
            }
        }

        $checks[] = [
            'label' => 'Migration status',
            'ok' => $pending === 0,
            'details' => $pending === 0 ? 'No pending migrations' : $pending . ' pending migration(s)',
        ];
    } else {
        $checks[] = [
            'label' => 'Migration status',
            'ok' => false,
            'details' => 'schema_migrations saknas. Kör php scripts/migrate.php',
        ];
    }
}

$hasFailure = false;
foreach ($checks as $check) {
    $symbol = $check['ok'] ? '✔' : '✖';
    echo sprintf("[%s] %s: %s\n", $symbol, $check['label'], $check['details']);
    if (!$check['ok']) {
        $hasFailure = true;
    }
}

exit($hasFailure ? 1 : 0);
