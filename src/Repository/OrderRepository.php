<?php

declare(strict_types=1);

namespace TechStore\Repository;

use PDO;

final class OrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function findValidatedOrders(): array
    {
        return $this->pdo->query(
            "SELECT o.*, u.first_name, u.last_name
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.status = 'validated'
             ORDER BY o.created_at DESC"
        )->fetchAll();
    }

    public function findWithItems(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.*, u.first_name, u.last_name, u.email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.id = :id'
        );
        $statement->execute(['id' => $id]);
        $order = $statement->fetch();

        if (!$order) {
            return null;
        }

        $items = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id');
        $items->execute(['id' => $id]);
        $order['items'] = $items->fetchAll();

        return $order;
    }

    public function createValidated(int $userId, array $items, array $totals): array
    {
        $this->pdo->beginTransaction();

        $nextId = (int) $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM orders')->fetchColumn();
        $reference = sprintf('ORD-%s-%06d', gmdate('Y'), $nextId);

        $statement = $this->pdo->prepare(
            'INSERT INTO orders (reference, user_id, status, discount_percent, discounted_subtotal_cents, tax_cents, total_cents, created_at)
             VALUES (:reference, :user_id, :status, :discount_percent, :discounted_subtotal_cents, :tax_cents, :total_cents, :created_at)'
        );
        $statement->execute([
            'reference' => $reference,
            'user_id' => $userId,
            'status' => 'validated',
            'discount_percent' => $totals['discount_percent'],
            'discounted_subtotal_cents' => $totals['discounted_subtotal_cents'],
            'tax_cents' => $totals['tax_cents'],
            'total_cents' => $totals['total_cents'],
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);

        $orderId = (int) $this->pdo->lastInsertId();
        $line = $this->pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit_price_cents, quantity, line_total_cents)
             VALUES (:order_id, :product_id, :product_name, :unit_price_cents, :quantity, :line_total_cents)'
        );

        foreach ($items as $item) {
            $line->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_price_cents' => $item['unit_price_cents'],
                'quantity' => $item['quantity'],
                'line_total_cents' => $item['line_total_cents'],
            ]);
        }

        $this->pdo->commit();

        return $this->findWithItems($orderId);
    }
}

