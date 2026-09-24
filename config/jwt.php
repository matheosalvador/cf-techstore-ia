<?php

declare(strict_types=1);

/**
 * Configuration JWT - Verification cryptographique
 *
 * CONTRACT (a ne pas modifier sans impact sur les consommateurs existants) :
 * - Algorithm : HS256 (HMAC + SHA-256)
 * - Claims obligatoires : sub (int), role (string), iat (int), exp (int), iss (string), aud (string)
 * - Duree par defaut : 1 heure (3600 secondes)
 *
 * SECURITE :
 * - JWT_SECRET doit etre une chaine aleatoire d'au moins 32 caracteres
 * - Ne jamais commiter la valeur reelle en production
 * - En production : utiliser des variables d'environnement ou un secret manager
 */
return [
    // Cle secrete pour la signature HMAC (HS256)
    // Generer avec : bin2hex(random_bytes(32))
    'secret' => (string) getenv('JWT_SECRET') ?: 'development-secret-123456789012345678',

    // Emetteur du token (issuer)
    'issuer' => (string) getenv('JWT_ISSUER') ?: 'techstore.local',

    // Audience autorisee
    'audience' => (string) getenv('JWT_AUDIENCE') ?: 'techstore.web',

    // Algorithme de signature (ne pas changer sans migration)
    'algorithm' => ['HS256'],

    // Tolerance pour les horloges desynchronisees (en secondes)
    // 0 = strict, 30-60 = recommande pour les environnements distribues
    'leeway' => 0,

    // Duree de validite par defaut (en secondes)
    'ttl' => 3600,
];
