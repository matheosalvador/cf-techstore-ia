<?php

declare(strict_types=1);

namespace TechStore\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use UnexpectedValueException;
use DomainException;

/**
 * Service JWT avec verification cryptographique complete.
 *
 * Ce service respecte le contrat JWT existant :
 * - sub (int) : Identifiant utilisateur
 * - role (string) : Role de l'utilisateur
 * - iat (int) : Timestamp de creation
 * - exp (int) : Timestamp d'expiration
 * - iss (string) : Emetteur du token
 * - aud (string) : Audience autorisee
 * - alg : HS256 (HMAC + SHA-256)
 */
final class JwtService
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require project_path('config/jwt.php');
    }

    /**
     * Cree un token JWT signe avec HS256.
     *
     * @param array $user ['id' => int, 'role' => string]
     * @return string Token JWT
     */
    public function createToken(array $user): string
    {
        $now = time();
        $ttl = (int) ($this->config['ttl'] ?? 3600);

        $payload = [
            'sub' => (int) $user['id'],
            'role' => (string) $user['role'],
            'iat' => $now,
            'exp' => $now + $ttl,
            'iss' => $this->config['issuer'] ?? 'techstore.local',
            'aud' => $this->config['audience'] ?? 'techstore.web',
        ];

        return JWT::encode(
            $payload,
            $this->getSigningKey(),
            $this->config['algorithm'][0] ?? 'HS256'
        );
    }

    /**
     * Valide cryptographiquement un token JWT et retourne les claims.
     *
     * @param string $token Token JWT a valider
     * @return array|null ['sub' => int, 'role' => string] ou null si invalide
     */
    public function validateToken(string $token): ?array
    {
        try {
            // Configuration du leeway pour la tolerance temporelle
            JWT::$leeway = $this->config['leeway'] ?? 0;

            $headers = null;
            $decoded = JWT::decode(
                $token,
                new Key(
                    $this->getVerificationKey(),
                    $this->config['algorithm'][0] ?? 'HS256'
                ),
                $headers
            );

            $payload = (array) $decoded;

            // Validation supplementaire du contrat metier
            if (!isset($payload['sub'], $payload['role'])) {
                return null;
            }

            return [
                'sub' => (int) $payload['sub'],
                'role' => (string) $payload['role'],
            ];

        } catch (ExpiredException) {
            // Token expire
            return null;
        } catch (BeforeValidException|SignatureInvalidException|UnexpectedValueException|DomainException) {
            // Toute autre erreur de validation
            return null;
        }
    }

    /**
     * Lit un token JWT SANS validation cryptographique.
     * A utiliser UNIQUEMENT pour le debogage ou la journalisation.
     *
     * @param string $token Token JWT
     * @return array|null Payload decode ou null si invalide
     * @deprecated Utiliser validateToken() pour une validation securisee
     */
    public function readToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key('', 'HS256'), ['HS256']);
            $payload = (array) $decoded;

            if (!isset($payload['sub'], $payload['role'])) {
                return null;
            }

            return [
                'sub' => (int) $payload['sub'],
                'role' => (string) $payload['role'],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Recupere la cle de signature pour la creation de tokens.
     */
    private function getSigningKey(): string
    {
        $secret = $this->config['secret'] ?? getenv('JWT_SECRET') ?: 'development-secret';
        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET is not configured');
        }
        return $secret;
    }

    /**
     * Recupere la cle de verification pour la validation de tokens.
     */
    private function getVerificationKey(): string
    {
        return $this->getSigningKey();
    }
}
