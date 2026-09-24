<?php

use PHPUnit\Framework\TestCase;
use TechStore\Auth\JwtService;

class JwtServiceTest extends TestCase
{
    public function testValidateTokenRejectsInvalidSignature()
    {
        $jwt = new JwtService();
        $token = $jwt->createToken(['id' => 1, 'role' => 'user']);

        // Corrompre la signature
        $parts = explode('.', $token);
        $parts[2] = 'invalid';
        $invalidToken = implode('.', $parts);

        $this->assertNull($jwt->validateToken($invalidToken));
    }

    public function testValidateTokenAcceptsValidToken()
    {
        $jwt = new JwtService();
        $token = $jwt->createToken(['id' => 1, 'role' => 'user']);

        $claims = $jwt->validateToken($token);
        $this->assertSame(1, $claims['sub']);
        $this->assertSame('user', $claims['role']);
    }

    public function testValidateTokenRejectsExpiredToken()
    {
        $jwt = new JwtService();
        
        // Creer un token expire
        $payload = [
            'sub' => 2,
            'role' => 'user',
            'iat' => time() - 200,
            'exp' => time() - 100,  // expire il y a 100 secondes
            'iss' => 'techstore.local',
            'aud' => 'techstore.web',
        ];
        
        $config = require 'config/jwt.php';
        $expiredToken = \Firebase\JWT\JWT::encode($payload, $config['secret'], 'HS256');

        $this->assertNull($jwt->validateToken($expiredToken));
    }

    public function testContractPreserved()
    {
        $jwt = new JwtService();
        $token = $jwt->createToken(['id' => 42, 'role' => 'customer']);
        
        $claims = $jwt->validateToken($token);
        
        // Verifier le contrat: sub (int) et role (string)
        $this->assertIsInt($claims['sub']);
        $this->assertIsString($claims['role']);
        $this->assertEquals(42, $claims['sub']);
        $this->assertEquals('customer', $claims['role']);
    }
}
