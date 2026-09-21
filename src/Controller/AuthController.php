<?php

declare(strict_types=1);

namespace TechStore\Controller;

use TechStore\Auth\AuthHelper;
use TechStore\Auth\JwtService;
use TechStore\Repository\UserRepository;

final class AuthController extends BaseController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService $jwtService,
        private readonly AuthHelper $auth
    ) {
    }

    public function login(): void
    {
        $input = $this->input();
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->json(['message' => 'Identifiants invalides.'], 401);
            return;
        }

        $token = $this->jwtService->createToken($user);

        $this->json([
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
            ],
        ]);
    }

    public function me(): void
    {
        $user = $this->auth->currentUser();
        if (!$user) {
            $this->json(['message' => 'Utilisateur non authentifié.'], 401);
            return;
        }

        $this->json(['user' => [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
        ]]);
    }
}

