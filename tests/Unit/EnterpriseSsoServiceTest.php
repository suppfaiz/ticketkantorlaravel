<?php

namespace Tests\Unit;

use App\Services\EnterpriseSsoService;
use Tests\TestCase;

class EnterpriseSsoServiceTest extends TestCase
{
    public function test_it_validates_rs256_signature_and_required_claims(): void
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($privateKey);

        $details = openssl_pkey_get_details($privateKey);
        $this->assertIsArray($details);

        config([
            'services.sso.jwt_public_key' => $details['key'],
            'services.sso.jwt_algorithm' => 'RS256',
            'services.sso.jwt_issuer' => 'https://sso.kantor.internal',
            'services.sso.jwt_audience' => 'nala-helpdesk',
        ]);

        $header = $this->encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $payload = $this->encode([
            'sub' => 'EMP-001',
            'iss' => 'https://sso.kantor.internal',
            'aud' => 'nala-helpdesk',
            'iat' => time(),
            'exp' => time() + 300,
        ]);
        $signedContent = "{$header}.{$payload}";

        $this->assertTrue(openssl_sign($signedContent, $signature, $privateKey, OPENSSL_ALGO_SHA256));
        $token = $signedContent.'.'.$this->base64UrlEncode($signature);

        $result = (new EnterpriseSsoService)->validateToken($token);

        $this->assertTrue($result['valid']);
        $this->assertSame('EMP-001', $result['claims']['sub']);
    }

    public function test_it_rejects_a_tampered_rs256_token(): void
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $details = openssl_pkey_get_details($privateKey);

        config([
            'services.sso.jwt_public_key' => $details['key'],
            'services.sso.jwt_algorithm' => 'RS256',
            'services.sso.jwt_issuer' => null,
            'services.sso.jwt_audience' => null,
        ]);

        $header = $this->encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $originalPayload = $this->encode(['sub' => 'EMP-001', 'exp' => time() + 300]);
        $this->assertTrue(openssl_sign(
            "{$header}.{$originalPayload}",
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        ));

        $tamperedPayload = $this->encode(['sub' => 'ADMIN', 'exp' => time() + 300]);
        $token = "{$header}.{$tamperedPayload}.".$this->base64UrlEncode($signature);

        $result = (new EnterpriseSsoService)->validateToken($token);

        $this->assertFalse($result['valid']);
        $this->assertSame('Signature token JWT tidak sah.', $result['error']);
    }

    private function encode(array $value): string
    {
        return $this->base64UrlEncode(json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
