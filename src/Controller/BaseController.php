<?php

declare(strict_types=1);

namespace TechStore\Controller;

abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = project_path('views/' . $view . '.php');
        require project_path('views/layout.php');
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    protected function input(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

