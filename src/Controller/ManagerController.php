<?php

declare(strict_types=1);

namespace TechStore\Controller;

use TechStore\Auth\AuthHelper;
use TechStore\Repository\OrderRepository;

final class ManagerController extends BaseController
{
    public function __construct(
        private readonly AuthHelper $auth,
        private readonly OrderRepository $orders
    ) {
    }

    public function orders(): void
    {
        $user = $this->auth->currentUser();
        if (!$user) {
            $this->json(['message' => 'Utilisateur non authentifié.'], 401);
            return;
        }

        if ($user['role'] !== 'manager') {
            $this->json(['message' => 'Accès réservé au responsable magasin.'], 403);
            return;
        }

        $this->json(['orders' => $this->orders->findValidatedOrders()]);
    }
}

