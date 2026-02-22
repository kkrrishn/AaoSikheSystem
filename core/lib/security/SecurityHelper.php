<?php

declare(strict_types=1);

namespace AaoSikheSystem\Security;

/**
 * AaoSikheSystem Secure - Security Helper
 * 
 * @package AaoSikheSystem
 */

class SecurityHelper
{
    private static ?string $csrfToken = null;
    private static ?string $appKey = null;

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (self::$csrfToken === null) {
            self::$csrfToken = bin2hex(random_bytes(32));
            $_SESSION['_csrf_token'] = self::$csrfToken;
        }

        return self::$csrfToken;
    }

    function generateReferralCode($name, $amount = 50)
    {
        // Clean name (only letters, uppercase, max 5 chars)
        $cleanName = strtoupper(preg_replace("/[^a-zA-Z]/", "", $name));
        $cleanName = substr($cleanName, 0, 5);

        // Random 4 digit number
        $random = rand(1000, 9999);

        return "REF" . $amount . $cleanName . $random;
    }


    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(?string $token): bool
    {
        $storedToken = $_SESSION['_csrf_token'] ?? null;

        if ($token === null || $storedToken === null) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Validate CSRF token from request (form or header)
     */
    public static function validateRequest($token = null): bool
    {
        $token = $token ?? ($_POST['_csrf_token'] ??
            ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

        return self::validateCsrfToken($token);
    }

    /**
     * Escape HTML content
     */
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape JavaScript content
     */
    public static function escapeJs(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * Escape HTML attribute
     */
    public static function escapeAttr(string $value): string
    {
        return self::escapeHtml($value);
    }

    /**
     * Sanitize URL
     */
    public static function sanitizeUrl(string $url): string
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $parsed = parse_url($url);

        if ($parsed === false) {
            return '';
        }

        // Allow only http/https protocols
        $allowedSchemes = ['http', 'https'];
        if (isset($parsed['scheme']) && !in_array(strtolower($parsed['scheme']), $allowedSchemes)) {
            return '';
        }

        return $url;
    }

    /**
     * Generate CSP nonce
     */
    public static function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Secure file upload validation
     */
    public static function validateUploadedFile(array $file): array
    {
        $errors = [];

        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code: ' . $file['error'];
            return $errors;
        }

        // Check file size (default 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed';
        }

        // Get real MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Allowed MIME types
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mime, $allowedMimes)) {
            $errors[] = 'File type not allowed';
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'File extension not allowed';
        }

        return $errors;
    }

    /**
     * Secure filename generation
     */
    public static function generateSecureFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = bin2hex(random_bytes(16));

        return $name . '.' . strtolower($extension);
    }

    /**
     * Constant-time string comparison
     */
    public static function constantTimeEquals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders(?string $cspNonce = null): void
    {
        // HSTS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');

        // X-Frame-Options
        header('X-Frame-Options: DENY');

        // Referrer-Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        if ($cspNonce) {
            $csp = "default-src 'self'; script-src 'self' 'nonce-{$cspNonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;";
            header("Content-Security-Policy: {$csp}");
        }
    }
    /**
     * Get validated application key
     */
    public static function appKey(): string
    {
        if (self::$appKey !== null) {
            return self::$appKey;
        }

        $env = require __DIR__ . '/../../include/env.php';

        if (!isset($env['APP_KEY'])) {
            throw new \RuntimeException("APP_KEY not defined in env.php");
        }

        $key = $env['APP_KEY'];

        // Remove base64 prefix if exists
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        if ($key === false || strlen($key) < 32) {
            throw new \RuntimeException("APP_KEY must be at least 32 bytes (256-bit)");
        }

        self::$appKey = $key;

        return self::$appKey;
    }

    /**
     * Generate cryptographically secure random string
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Constant-time comparison
     */
    public static function safeCompare(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    /**
     * Hash data using SHA-256
     */
    public static function hash(string $data): string
    {
        return hash('sha256', $data);
    }
}
