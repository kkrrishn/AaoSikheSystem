<?php

declare(strict_types=1);

namespace AaoSikheSystem\helper;

/**
 * HEADERHelper - HTTP Header Management Helper
 * 
 * @package AaoSikheSystem
 */
class HeaderHelper
{
    /**
     * Set standard security headers
     */
    public static function setSecurityHeaders(?string $cspNonce = null): void
    {
        // HSTS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Clickjacking protection
        header('X-Frame-Options: DENY');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        if ($cspNonce) {
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'nonce-{$cspNonce}'; " .
                   "style-src 'self' 'unsafe-inline'; " .
                   "img-src 'self' data: https:; " .
                   "font-src 'self'; " .
                   "connect-src 'self';";
            header("Content-Security-Policy: {$csp}");
        }
    }

    /**
     * Set JSON response header
     */
    public static function jsonHeader(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    /**
     * Set CSV download headers
     */
    public static function csvDownloadHeader(string $filename = 'export.csv'): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Set file download headers
     */
    public static function fileDownloadHeader(string $filepath, ?string $filename = null): void
    {
        if (!file_exists($filepath)) {
            http_response_code(404);
            exit('File not found.');
        }

        $filename = $filename ?? basename($filepath);
        $mimeType = mime_content_type($filepath) ?: 'application/octet-stream';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    /**
     * Redirect to a URL
     */
    public static function redirect(string $url, int $status = 302): void
{
    // 1️⃣ Sanitize URL
    $url = trim($url); // remove spaces
    $url = filter_var($url, FILTER_SANITIZE_URL);

    // 2️⃣ Parse URL
    $parsed = parse_url($url);

    // 3️⃣ Only allow relative URLs or http/https absolute URLs
    if ($parsed === false) {
        // Invalid URL, redirect to homepage
        $url = '/';
    } else {
        if (!isset($parsed['scheme'])) {
            // Relative URL, safe to use
            $url = '/' . ltrim($url, '/');
        } elseif (!in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
            // Disallow other schemes
            $url = '/';
        }
    }

    // 4️⃣ Redirect safely
    header('Location: ' . $url, true, $status);
    exit;
}


    /**
     * Prevent caching
     */
    public static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Allow caching for a certain time
     */
    public static function cacheFor(int $seconds): void
    {
        header('Cache-Control: public, max-age=' . $seconds);
        header('Pragma: cache');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    }

    /**
     * Force content type
     */
    public static function contentType(string $type, string $charset = 'UTF-8'): void
    {
        header("Content-Type: {$type}; charset={$charset}");
    }
}
