<?php
namespace AaoSikheSystem\security;

class PII
{
    /**
     * Encrypt an array/object of sensitive fields. Returns JSON-serializable structure to store in DB.
     */
    public static function encryptFields(array $data, ?string $keyId = null): array
    {
        $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);
        $enc = KMS::encrypt($plaintext, $keyId);
        return $enc;
    }

    /**
     * Decrypt PII stored structure.
     */
    public static function decryptFields(array $enc): array
    {
        $json = KMS::decrypt($enc);
        $data = json_decode($json, true);
        if ($data === null) {
            throw new \RuntimeException('Invalid PII JSON');
        }
        return $data;
    }
}
