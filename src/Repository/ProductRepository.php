<?php

declare(strict_types=1);

namespace TechStore\Repository;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(string $sort = 'name'): array
    {
        return $this->search('', $sort);
    }

    public function search(string $query, string $sort = 'name'): array
    {
        $allowedSorts = ['name', 'price_cents', 'available DESC, name'];
        $orderBy = in_array($sort, $allowedSorts, true) ? $sort : 'name';

        if ($query === '') {
            $sql = "
                SELECT p.*, c.name AS category_name
                FROM products p
                JOIN categories c ON c.id = p.category_id
                ORDER BY $orderBy
            ";
            return $this->pdo->query($sql)->fetchAll();
        }

        $sql = "
            SELECT p.*, c.name AS category_name
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.name LIKE :query
            ORDER BY $orderBy
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['query' => "%{$query}%"]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.*, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id'
        );
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public function candidatesForShopping(): array
    {
        // Kept from the recommendation prototype so future catalog matching can reuse the same fields.
        return $this->pdo->query(
            'SELECT p.id, p.name, p.description, c.name AS category_name, p.price_cents, p.available
             FROM products p
             JOIN categories c ON c.id = p.category_id
             ORDER BY p.available DESC, p.category_id, p.price_cents'
        )->fetchAll();
    }
}
