<?php

declare(strict_types=1);

namespace TechStore\Database;

use PDO;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dbPath = legacy_env('DB_PATH', 'database/techstore.sqlite');
        if (!str_contains($dbPath, DIRECTORY_SEPARATOR) && !str_contains($dbPath, '/')) {
            $dbPath = 'database/' . $dbPath;
        }

        self::$pdo = new PDO('sqlite:' . project_path($dbPath));
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        return self::$pdo;
    }

    public static function reset(?PDO $pdo = null): void
    {
        self::$pdo = $pdo;
    }
}

