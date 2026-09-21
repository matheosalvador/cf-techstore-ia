<?php

declare(strict_types=1);

namespace TechStore\Auth;

final class JwtService
{
    public function createToken(array $user): string
    {
        $now = time();
        $payload = [
            'sub' => (int) $user['id'],
            'role' => $user['role'],
            'iat' => $now,
            'exp' => $now + 3600,
            'iss' => legacy_env('JWT_ISSUER', 'techstore.local'),
            'aud' => legacy_env('JWT_AUDIENCE', 'techstore.web'),
        ];

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $unsigned = $this->base64UrlEncode(json_encode($header)) . '.' . $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $unsigned, legacy_env('JWT_SECRET', 'development-secret'), true);

        return $unsigned . '.' . $this->base64UrlEncode($signature);
    }

    public function readToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (!is_array($payload) || empty($payload['sub']) || empty($payload['role'])) {
            return null;
        }

        return [
            'sub' => (int) $payload['sub'],
            'role' => (string) $payload['role'],
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}

