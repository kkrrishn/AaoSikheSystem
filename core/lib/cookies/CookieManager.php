<?php

namespace AaoSikheSystem\cookies;

use AaoSikheSystem\security\Crypto;
use AaoSikheSystem\db\DBManager;
use AaoSikheSystem\logger\Logger;
use AaoSikheSystem\rate_limit\RateLimiter;
use AaoSikheSystem\cache\RedisCache;
use AaoSikheSystem\monitoring\RealTimeMonitor;

class CookieManager
{
    private static string $cookieName = 'aas_auth';
    private static int $expiry = 3600;

    /* ================= ISSUE COOKIE ================= */

public static function issueAuthCookie(object $user): void
{
    self::enforceHTTPS();

    $db = DBManager::getInstance();

    $token        = bin2hex(random_bytes(32));
    $fingerprint  = CookieFingerprint::generate();
    $ipHash       = CookieFingerprint::ipHash();
    $issuedAt     = time();
    $expiresAt    = $issuedAt + self::$expiry;

    $payload = [
        'uid'         => $user->id,
        'token'       => $token,
        'fingerprint' => $fingerprint,
        'issued_at'   => $issuedAt,
        'expires_at'  => $expiresAt,
        'ip_hash'     => $ipHash,
        'csrf'        => bin2hex(random_bytes(16))
    ];

    $tokenHash = hash('sha256', $token);

    // 🔒 Use transaction for consistency
    $db->transaction(function ($db) use (
        $user,
        $tokenHash,
        $fingerprint,
        $ipHash,
        $expiresAt
    ) {

        $db->insert(
            "INSERT INTO auth_tokens 
            (user_id, token_hash, device_hash, ip_hash, expires_at, last_used_at, is_revoked)
            VALUES (?, ?, ?, ?, ?, NOW(), 0)",
            "issss",
            [
                $user->id,
                $tokenHash,
                $fingerprint,
                $ipHash,
                date('Y-m-d H:i:s', $expiresAt)
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    | If using AES-256-GCM, do NOT manually sign again.
    */

    $encrypted = Crypto::encryptAes(json_encode($payload),APP_KEY);

    self::setCookie($encrypted);

    /*
    |--------------------------------------------------------------------------
    | Warm Redis Cache (optional but recommended)
    |--------------------------------------------------------------------------
    */

    try {
        $redis = new \AaoSikheSystem\cache\RedisCache();
        $redis->set("auth_token_" . $tokenHash, 1, 300);
    } catch (\Throwable $e) {
        // Never break login if Redis fails
    }

    RealTimeMonitor::track('auth_login', $user->id);
}


    /* ================= VALIDATE ================= */

    public static function validateAuthCookie(): ?array
    {
        if (!isset($_COOKIE[self::$cookieName])) {
            return null;
        }

        $parts = explode('.', $_COOKIE[self::$cookieName]);
        if (count($parts) !== 2) {
            return self::reject("Malformed cookie");
        }

        [$encrypted, $signature] = $parts;

        if (!hash_equals(
            hash_hmac('sha256', $encrypted, SecurityHelper::appKey()),
            $signature
        )) {
            return self::reject("Signature tampering detected");
        }

        $json = Crypto::decryptAes($encrypted, APP_KEY);
        if (!$json) {
            return self::reject("Decryption failed");
        }

        $payload = json_decode($json, true);

        if ($payload['expires_at'] < time()) {
            return self::reject("Expired cookie");
        }

        if ($payload['fingerprint'] !== CookieFingerprint::generate()) {
            return self::reject("Device mismatch");
        }

        if (config('cookie_security.strict_ip_check')) {
            if ($payload['ip_hash'] !== CookieFingerprint::ipHash()) {
                return self::reject("IP mismatch");
            }
        }

        if (!self::validateTokenInDB($payload)) {
            return self::reject("Replay or revoked token");
        }

        self::rotateToken($payload);

        return $payload;
    }

    /* ================= TOKEN VALIDATION ================= */

    private static function validateTokenInDB(array $payload): bool
    {
        $tokenHash = hash('sha256', $payload['token']);
        $cacheKey  = "auth_token_" . $tokenHash;

        $cache = new RedisCache();

        // 1️⃣ Check Redis first
        $exists = $cache->get($cacheKey);
        if ($exists) {
            return true;
        }

        // 2️⃣ Check DB
        $row = DBManager::getInstance()->selectRow(
            "SELECT id 
         FROM auth_tokens 
         WHERE token_hash = ? 
         AND is_revoked = 0 
         AND expires_at > NOW() 
         LIMIT 1",
            "s",
            [$tokenHash]
        );

        if (!$row) {
            return false;
        }

        // 3️⃣ Store in cache (5 minutes)
        $cache->set($cacheKey, 1, 300);

        return true;
    }


    /* ================= TOKEN ROTATION ================= */

 public static function rotateToken(array $payload): void
{
    $rotationInterval = config('app.cookie_security.rotation_interval');

    if (!$rotationInterval) {
        return;
    }

    // If not yet time to rotate → stop
    if ((time() - $payload['issued_at']) < $rotationInterval) {
        return;
    }

    $tokenHash = hash('sha256', $payload['token']);

    DBManager::getInstance()->update(
        "UPDATE auth_tokens 
         SET is_revoked = 1 
         WHERE token_hash = ?",
        "s",
        [$tokenHash]
    );

    $user = (object)['id' => $payload['uid']];
    self::issueAuthCookie($user);
}


    /* ================= REJECT ================= */

    private static function reject(string $reason)
    {
        Logger::warnings("Auth cookie rejected: {$reason}");
        RateLimiter::hit($_SERVER['REMOTE_ADDR']);

        RealTimeMonitor::track('auth_reject', $reason);

        self::destroy();
        return null;
    }

    /* ================= DESTROY ================= */

    public static function destroy(): void
    {
        setcookie(self::$cookieName, '', time() - 3600, '/');
        unset($_COOKIE[self::$cookieName]);
    }

    /* ================= SECURE SET ================= */

    private static function setCookie(string $value): void
    {
        setcookie(self::$cookieName, $value, [
            'expires' => time() + self::$expiry,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    private static function enforceHTTPS(): void
    {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            throw new \Exception("Secure cookies require HTTPS");
        }
    }
}
