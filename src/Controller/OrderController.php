<?php

declare(strict_types=1);

namespace TechStore\Controller;

use TechStore\Auth\AuthHelper;
use TechStore\Repository\OrderRepository;

final class OrderController extends BaseController
{
    public function __construct(
        private readonly AuthHelper $auth,
        private readonly OrderRepository $orders
    ) {
    }

    public function mine(): void
    {
        $user = $this->auth->currentUser();
        if (!$user) {
            $this->json(['message' => 'Utilisateur non authentifié.'], 401);
            return;
        }

        if ($user['role'] === 'manager') {
            $this->json(['orders' => $this->orders->findValidatedOrders()]);
            return;
        }

        $this->json(['orders' => $this->orders->findByUser((int) $user['id'])]);
    }

    public function show(): void
    {
        $user = $this->auth->currentUser();
        if (!$user) {
            $this->json(['message' => 'Utilisateur non authentifié.'], 401);
            return;
        }

        $order = $this->orders->findWithItems((int) ($_GET['id'] ?? 0));
        if (!$order) {
            $this->json(['message' => 'Commande inexistante.'], 404);
            return;
        }

        $this->json(['order' => $order]);
    }
}

