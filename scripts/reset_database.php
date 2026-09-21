<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/src/Support/helpers.php';

$dbPath = legacy_env('DB_PATH', 'database/techstore.sqlite');
$fullPath = project_path($dbPath);

if (!is_dir(dirname($fullPath))) {
    mkdir(dirname($fullPath), 0777, true);
}

if (is_file($fullPath)) {
    unlink($fullPath);
}

$pdo = new PDO('sqlite:' . $fullPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$schema = file_get_contents(project_path('database/schema.sql'));
$seed = file_get_contents(project_path('database/seed.sql'));

$pdo->exec($schema);
$pdo->exec($seed);

echo "TechStore Legacy database is ready at {$dbPath}." . PHP_EOL;

