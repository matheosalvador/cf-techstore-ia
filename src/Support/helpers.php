<?php

declare(strict_types=1);

function format_price(int $cents): string
{
    return number_format($cents / 100, 2, ',', ' ') . ' €';
}

function project_path(string $path = ''): string
{
    $root = dirname(__DIR__, 2);
    return $path === '' ? $root : $root . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function legacy_env(string $key, ?string $default = null): ?string
{
    static $values = null;

    if ($values === null) {
        $values = [];
        $envFile = project_path('.env');
        if (!is_file($envFile)) {
            $envFile = project_path('.env.example');
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $values[trim($name)] = trim($value);
        }
    }

    return $_ENV[$key] ?? getenv($key) ?: ($values[$key] ?? $default);
}

