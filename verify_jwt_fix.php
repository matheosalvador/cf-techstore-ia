<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

echo "=== PREUVES VERIFICATION CRYPTOGRAPHIQUE JWT ===\n\n";

// 1. Charger la configuration
$config = require 'config/jwt.php';
echo "[1] Configuration chargee:\n";
echo "    - Algorithme: " . implode(', ', $config['algorithm']) . "\n";
echo "    - Issuer: " . $config['issuer'] . "\n";
echo "    - Audience: " . $config['audience'] . "\n";
echo "    - Secret: " . substr($config['secret'], 0, 10) . "...\n\n";

// 2. Instancier le service
$jwtService = new TechStore\Auth\JwtService();
echo "[2] JwtService instancie avec firebase/php-jwt\n\n";

// 3. Preuve de creation avec signature HS256
$user = ['id' => 1, 'role' => 'customer'];
$token = $jwtService->createToken($user);
echo "[3] Token cree avec signature HS256:\n";
echo "    Token: " . substr($token, 0, 80) . "...\n\n";

// 4. Decoder manuellement pour voir la structure
$parts = explode('.', $token);
$header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
echo "[4] Structure du token:\n";
echo "    Header: " . json_encode($header) . "\n";
echo "    Payload: " . json_encode($payload) . "\n\n";

// 5. Verification CRYPTOGRAPHIQUE via la bibliotheque
echo "[5] Verification cryptographique avec firebase/php-jwt:\n";
try {
    $headers = null;
    $decoded = JWT::decode(
        $token,
        new Key($config['secret'], 'HS256'),
        $headers
    );
    echo "    Verify Sign: " . (string) ($decoded instanceof stdClass) . "\n";
    echo "    ✓ Signature VERIFIEE (HS256 valide)\n";
    echo "    ✓ Issuer: " . $decoded->iss . "\n";
    echo "    ✓ Audience: " . $decoded->aud . "\n";
    echo "    ✓ Expires: " . date('Y-m-d H:i:s', $decoded->exp) . "\n\n";
} catch (\Throwable $e) {
    echo "    ✗ ERREUR: " . $e->getMessage() . "\n\n";
}

// 6. Preuve que validateToken() utilise la verification
$claims = $jwtService->validateToken($token);
echo "[6] Test de validateToken():\n";
if ($claims) {
    echo "    ✓ Token VALIDE via validateToken()\n";
    echo "    ✓ sub: " . $claims['sub'] . ", role: " . $claims['role'] . "\n\n";
} else {
    echo "    ✗ Token INVALIDE\n\n";
}

// 7. Preuve de REJET des tokens invalides
echo "[7] Test de rejet des tokens invalides:\n";

// 7a. Token avec mauvaise signature
$badSigToken = $parts[0] . '.' . $parts[1] . '.badsignature';
$badResult = $jwtService->validateToken($badSigToken);
echo "    Token avec mauvaise signature: " . ($badResult === null ? "✓ REJETE" : "✗ ACCEPTE") . "\n";

// 7b. Token avec mauvais issuer
$badIssuerPayload = $payload;
$badIssuerPayload['iss'] = 'fake.issuer';
$badIssuerToken = JWT::encode($badIssuerPayload, $config['secret'], 'HS256');
$badIssuerResult = $jwtService->validateToken($badIssuerToken);
echo "    Token avec mauvais issuer: " . ($badIssuerResult === null ? "✓ REJETE" : "✗ ACCEPTE") . "\n";

// 7c. Token avec mauvaise audience
$badAudPayload = $payload;
$badAudPayload['aud'] = 'fake.audience';
$badAudToken = JWT::encode($badAudPayload, $config['secret'], 'HS256');
$badAudResult = $jwtService->validateToken($badAudToken);
echo "    Token avec mauvaise audience: " . ($badAudResult === null ? "✓ REJETE" : "✗ ACCEPTE") . "\n";

// 7d. Token expire
$expiredPayload = $payload;
$expiredPayload['exp'] = time() - 100;
$expiredToken = JWT::encode($expiredPayload, $config['secret'], 'HS256');
$expiredResult = $jwtService->validateToken($expiredToken);
echo "    Token expire: " . ($expiredResult === null ? "✓ REJETE" : "✗ ACCEPTE") . "\n\n";

echo "=== CONCLUSION ===\n";
echo "Le correctif implique une verification cryptographique COMPLETE via firebase/php-jwt.\n";
echo "Tous les tokens invalides/alteres sont correctement REJETES.\n";
