<?php

declare(strict_types=1);

use App\Core\Support\Environment;

require dirname(__DIR__) . '/vendor/autoload.php';

Environment::load(dirname(__DIR__) . '/.env');

$config = require dirname(__DIR__) . '/config/database.php';
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    (string) ($config['host'] ?? '127.0.0.1'),
    (int) ($config['port'] ?? 3306),
    (string) ($config['database'] ?? ''),
    (string) ($config['charset'] ?? 'utf8mb4')
);

$dryRun = in_array('--dry-run', $argv, true);
$statusOnly = in_array('--status', $argv, true);

try {
    $pdo = new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Kunde inte ansluta till databasen: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$files = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
sort($files);

$applied = [];
foreach ($pdo->query('SELECT migration FROM schema_migrations ORDER BY migration ASC') as $row) {
    $applied[(string) $row['migration']] = true;
}

$pending = [];
foreach ($files as $file) {
    $name = basename($file);
    if (!isset($applied[$name])) {
        $pending[] = $file;
    }
}

echo 'Totalt migrationer: ' . count($files) . PHP_EOL;
echo 'Applicerade migrationer: ' . count($applied) . PHP_EOL;
echo 'Pending migrationer: ' . count($pending) . PHP_EOL;

if ($pending !== []) {
    echo PHP_EOL . "Pending-lista:" . PHP_EOL;
    foreach ($pending as $file) {
        echo '- ' . basename($file) . PHP_EOL;
    }
}

if ($statusOnly || $pending === []) {
    exit(0);
}

if ($dryRun) {
    echo PHP_EOL . 'Dry-run aktiv: inga SQL-filer kördes.' . PHP_EOL;
    exit(0);
}

echo PHP_EOL . 'Kör migrationer...' . PHP_EOL;
foreach ($pending as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, 'Kunde inte läsa fil: ' . $name . PHP_EOL);
        exit(1);
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $stmt->execute(['migration' => $name]);
        $pdo->commit();
        echo '✔ ' . $name . PHP_EOL;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, 'Migration misslyckades (' . $name . '): ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo PHP_EOL . 'Klart. Alla pending migrationer är applicerade.' . PHP_EOL;
