<?php

declare(strict_types=1);

namespace TechStore\Controller;

use TechStore\Repository\ProductRepository;

final class PageController extends BaseController
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function catalog(): void
    {
        $this->render('catalog', [
            'title' => 'Catalogue',
            'products' => $this->products->all('name'),
        ]);
    }

    public function login(): void
    {
        $this->render('login', ['title' => 'Connexion']);
    }

    public function cart(): void
    {
        $this->render('cart', ['title' => 'Panier']);
    }

    public function orders(): void
    {
        $this->render('orders', ['title' => 'Mes commandes']);
    }

    public function orderDetail(): void
    {
        $this->render('order_detail', ['title' => 'Détail commande']);
    }

    public function managerOrders(): void
    {
        $this->render('manager_orders', ['title' => 'Suivi commandes']);
    }
}

