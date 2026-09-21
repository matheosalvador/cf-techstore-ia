<?php

declare(strict_types=1);

namespace TechStore\Controller;

use Throwable;
use TechStore\Repository\ProductRepository;

final class ProductController extends BaseController
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function search(): void
    {
        try {
            $query = (string) ($_GET['q'] ?? '');
            $sort = (string) ($_GET['sort'] ?? 'name');
            $products = $this->products->search($query, $sort);

            $this->json(['products' => $products]);
        } catch (Throwable) {
            $this->json(['message' => 'La recherche a échoué.'], 500);
        }
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $product = $this->products->find($id);

        if (!$product) {
            $this->json(['message' => 'Produit introuvable.'], 404);
            return;
        }

        $this->json(['product' => $product]);
    }
}

