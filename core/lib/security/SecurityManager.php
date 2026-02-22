<?php

declare(strict_types=1);

namespace AaoSikheSystem\Security;

use AaoSikheSystem\helper\PathManager;

class SecurityManager
{
    public static function apply(): void
    {
        $security = PathManager::config('security', []);

        if ($security['content_type_nosniff'] ?? false) {
            header('X-Content-Type-Options: nosniff');
        }

        if ($security['xss_protection'] ?? false) {
            header('X-XSS-Protection: 1; mode=block');
        }

        if ($security['frame_options'] ?? false) {
            header('X-Frame-Options: ' . $security['frame_options']);
        }

        if (($security['hsts'] ?? false) && APP_ENV === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        if ($security['csp'] ?? false) {
            header("Content-Security-Policy: default-src 'self'");
        }
    }
}
