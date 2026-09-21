<?php

declare(strict_types=1);

namespace TechStore\Auth;

use TechStore\Repository\UserRepository;

final class AuthHelper
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly UserRepository $users
    ) {
    }

    public function currentUser(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $claims = $this->jwtService->readToken(substr($header, 7));
        if (!$claims) {
            return null;
        }

        $user = $this->users->find((int) $claims['sub']);
        if (!$user) {
            return null;
        }

        $user['role'] = $claims['role'];
        return $user;
    }
}

