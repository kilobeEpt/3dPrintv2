<?php
/**
 * Simple JWT Implementation - Replacement for firebase/php-jwt
 * Basic JWT encoding/decoding with HS256
 */

class SimpleJWT
{
    /**
     * Encode payload to JWT token
     */
    public static function encode(array $payload, string $secret, string $algorithm = 'HS256'): string
    {
        if ($algorithm !== 'HS256') {
            throw new Exception('Only HS256 algorithm is supported');
        }
        
        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => $algorithm
        ];
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Decode and verify JWT token
     */
    public static function decode(string $token, string $secret, array $allowedAlgorithms = ['HS256']): object
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Decode header
        $header = json_decode(self::base64UrlDecode($headerEncoded), true);
        
        if (!$header || !isset($header['alg'])) {
            throw new Exception('Invalid token header');
        }
        
        if (!in_array($header['alg'], $allowedAlgorithms)) {
            throw new Exception('Algorithm not allowed');
        }
        
        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Signature verification failed');
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded));
        
        if (!$payload) {
            throw new Exception('Invalid token payload');
        }
        
        // Check expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token has expired');
        }
        
        return $payload;
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
