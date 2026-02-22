<?php
namespace AaoSikheSystem\security;

/**
 * KMS abstraction.
 * - In production, implement cloud provider SDK (AWS KMS, GCP KMS).
 * - Fallback here uses local file-based encryption with symmetric key stored in ENV (not as secure).
 */
class KMS
{
    // if using a cloud provider, implement these methods to call provider
    public static function encrypt(string $plaintext, ?string $keyId = null): array
    {
        // If you have an env provided key for simple fallback:
        $key = getenv('LOCAL_PII_KEY') ?: null;
        if (!$key) {
            throw new \RuntimeException('No KMS provider configured and LOCAL_PII_KEY missing');
        }

        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        $data = base64_encode($cipher);
        return [
            'key_id' => $keyId ?? 'local',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ciphertext' => $data,
            'alg' => 'AES-256-GCM'
        ];
    }

    public static function decrypt(array $enc): string
    {
        $key = getenv('LOCAL_PII_KEY') ?: null;
        if (!$key) {
            throw new \RuntimeException('No KMS provider configured and LOCAL_PII_KEY missing');
        }
        $iv = base64_decode($enc['iv']);
        $tag = base64_decode($enc['tag']);
        $cipher = base64_decode($enc['ciphertext']);
        $plaintext = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Decrypt failed');
        }
        return $plaintext;
    }
}
