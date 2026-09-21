<?php

declare(strict_types=1);

namespace TechStore\Repository;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listAll(): array
    {
        return $this->pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }
}

