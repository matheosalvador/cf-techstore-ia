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

    /**
     * Recupere l'utilisateur courant a partir du token JWT.
     * Effectue une validation cryptographique complete.
     *
     * @return array|null Utilisateur ou null si non authentifie
     */
    public function currentUser(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            error_log('[JWT] Pas de token Bearer');
            return null;
        }

        // Validation CRYPTOGRAPHIQUE
        error_log('[JWT] Validation cryptographique en cours...');
        $claims = $this->jwtService->validateToken(substr($header, 7));

        if (!$claims) {
            error_log('[JWT] Token REJETE - Signature ou claims invalides');
            return null;
        }

        error_log('[JWT] Token VALIDE - sub:' . $claims['sub'] . ', role:' . $claims['role']);
        $user = $this->users->find((int) $claims['sub']);
        if (!$user) {
            return null;
        }

        $user['role'] = $claims['role'];
        return $user;
    }
}
