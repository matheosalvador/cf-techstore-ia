<?php

declare(strict_types=1);

namespace TechStore\Controller;

use TechStore\Auth\AuthHelper;
use TechStore\Repository\OrderRepository;
use TechStore\Repository\ProductRepository;
use TechStore\Service\CartCalculator;

final class CartController extends BaseController
{
    public function __construct(
        private readonly AuthHelper $auth,
        private readonly ProductRepository $products,
        private readonly OrderRepository $orders,
        private readonly CartCalculator $calculator
    ) {
    }

    public function totals(): void
    {
        $input = $this->input();
        $items = $this->normalizeItems($input['items'] ?? []);
        $discount = (int) ($input['discount_percent'] ?? 0);

        $this->json(['totals' => $this->calculator->calculate($items, $discount)]);
    }

    public function checkout(): void
    {
        $user = $this->auth->currentUser();
        if (!$user) {
            $this->json(['message' => 'Utilisateur non authentifié.'], 401);
            return;
        }

        if ($user['role'] !== 'customer') {
            $this->json(['message' => 'Seuls les clients peuvent valider un panier.'], 403);
            return;
        }

        $input = $this->input();
        $items = $this->normalizeItems($input['items'] ?? []);
        if ($items === []) {
            $this->json(['message' => 'Le panier est vide.'], 422);
            return;
        }

        foreach ($items as $item) {
            $product = $this->products->find((int) $item['product_id']);
            if (!$product) {
                $this->json(['message' => 'Produit absent.'], 404);
                return;
            }
            if ((int) $product['available'] !== 1) {
                $this->json(['message' => 'Produit indisponible.'], 422);
                return;
            }
        }

        $discount = (int) ($input['discount_percent'] ?? 0);
        $totals = $this->calculator->calculate($items, $discount);
        $order = $this->orders->createValidated((int) $user['id'], $items, $totals);

        $this->json(['message' => 'Commande validée.', 'order' => $order], 201);
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            $unitPrice = (int) ($item['unit_price_cents'] ?? 0);
            $name = (string) ($item['product_name'] ?? $item['name'] ?? 'Product');

            $normalized[] = [
                'product_id' => (int) ($item['product_id'] ?? $item['id'] ?? 0),
                'product_name' => $name,
                'unit_price_cents' => $unitPrice,
                'quantity' => $quantity,
                'line_total_cents' => $unitPrice * $quantity,
            ];
        }

        return $normalized;
    }
}

