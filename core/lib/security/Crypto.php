<?php

declare(strict_types=1);

namespace AaoSikheSystem\Security;

/**
 * AaoSikheSystem Secure - Cryptography Utilities
 * 
 * @package AaoSikheSystem
 */

class Crypto
{
    private const AES_METHOD = 'aes-256-gcm';
    private const RSA_PADDING = OPENSSL_PKCS1_OAEP_PADDING;
    
    /**
     * AES-256-GCM Encryption
     */
    public static function encryptAes(string $data, string $key): string
    {
        if (strlen($key) !== 32) {
            throw new \InvalidArgumentException('Encryption key must be 32 bytes for AES-256');
        }
        
        $iv = random_bytes(openssl_cipher_iv_length(self::AES_METHOD));
        $tag = '';
        
        $ciphertext = openssl_encrypt(
            $data,
            self::AES_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );
        
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }
        
        return base64_encode($iv . $tag . $ciphertext);
    }
    
    /**
     * AES-256-GCM Decryption
     */
    public static function decryptAes(string $encryptedData, string $key): string
    {
        if (strlen($key) !== 32) {
            throw new \InvalidArgumentException('Decryption key must be 32 bytes for AES-256');
        }
        
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length(self::AES_METHOD);
        
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $ciphertext = substr($data, $ivLength + 16);
        
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::AES_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }
        
        return $plaintext;
    }
    
    /**
     * RSA Encryption
     */
    public static function encryptRsa(string $data, string $publicKey): string
    {
        $encrypted = '';
        $success = openssl_public_encrypt($data, $encrypted, $publicKey, self::RSA_PADDING);
        
        if (!$success) {
            throw new \RuntimeException('RSA encryption failed');
        }
        
        return base64_encode($encrypted);
    }
    
    /**
     * RSA Decryption
     */
    public static function decryptRsa(string $encryptedData, string $privateKey): string
    {
        $data = base64_decode($encryptedData);
        $decrypted = '';
        $success = openssl_private_decrypt($data, $decrypted, $privateKey, self::RSA_PADDING);
        
        if (!$success) {
            throw new \RuntimeException('RSA decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * Hybrid encryption (AES key wrapped with RSA)
     */
    public static function hybridEncrypt(string $data, string $publicKey): array
    {
        // Generate random AES key
        $aesKey = random_bytes(32);
        
        // Encrypt data with AES
        $encryptedData = self::encryptAes($data, $aesKey);
        
        // Encrypt AES key with RSA
        $encryptedKey = self::encryptRsa($aesKey, $publicKey);
        
        return [
            'data' => $encryptedData,
            'key' => $encryptedKey
        ];
    }
    
    /**
     * Hybrid decryption
     */
    public static function hybridDecrypt(array $encryptedPackage, string $privateKey): string
    {
        // Decrypt AES key with RSA
        $aesKey = self::decryptRsa($encryptedPackage['key'], $privateKey);
        
        // Decrypt data with AES
        return self::decryptAes($encryptedPackage['data'], $aesKey);
    }
    
    /**
     * Generate HMAC
     */
    public static function hmac(string $data, string $key): string
    {
        return hash_hmac('sha256', $data, $key);
    }
    
    /**
     * Verify HMAC
     */
    public static function verifyHmac(string $data, string $key, string $hmac): bool
    {
        return hash_equals(self::hmac($data, $key), $hmac);
    }
    
    /**
     * Password hashing with Argon2id
     */
    public static function hashPassword(string $password): string
    {
        $options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1
        ];
        
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random string
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * URL-safe base64 encode
     */
    public static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    /**
     * URL-safe base64 decode
     */
    public static function base64UrlDecode(string $data): string
    {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
    
    /**
     * Generate secure token
     */
    public static function generateToken(int $length = 32): string
    {
        return self::base64UrlEncode(random_bytes($length));
    }
    /**
 * Encrypt image file using AES-256-GCM
 */
public static function encryptImage(
    string $inputPath,
    string $outputPath,
    string $key
): void {
    if (!file_exists($inputPath)) {
        throw new \RuntimeException('Image file not found');
    }

    $data = file_get_contents($inputPath);
    $encrypted = self::encryptAes($data, $key);

    file_put_contents($outputPath, $encrypted);
}
/**
 * Decrypt encrypted image file
 */
public static function decryptImage(
    string $encryptedPath,
    string $outputPath,
    string $key
): void {
    if (!file_exists($encryptedPath)) {
        throw new \RuntimeException('Encrypted image not found');
    }

    $encryptedData = file_get_contents($encryptedPath);
    $decrypted = self::decryptAes($encryptedData, $key);

    file_put_contents($outputPath, $decrypted);
}
/**
 * Encrypt image path (URL-safe)
 */
public static function encryptImagePath(string $path, string $key=null): string
{
    $key = $key ??hash('sha256', APP_KEY, true);
    
    return self::base64UrlEncode(
        self::encryptAes($path, $key)
    );
}
/**
 * Decrypt image path
 */
public static function decryptImagePath(string $encryptedPath, string $key): string
{
    return self::decryptAes(
        self::base64UrlDecode($encryptedPath),
        $key
    );
}




}