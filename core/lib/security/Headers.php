<?php
namespace AaoSikheSystem\security;

class Headers
{
    public static function sendSecurityHeaders(): void
    {
        // Enforce HTTPS only in production
        if ((getenv('APP_ENV') ?? 'development') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Very permissive example CSP — lock down in real app
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self';");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer-when-downgrade');
        header('Permissions-Policy: geolocation=(), microphone=()');
    }
}
