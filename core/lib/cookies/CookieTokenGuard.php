<?php
namespace AaoSikheSystem\cookies;

use AaoSikheSystem\cookies\CookieManager;

class CookieTokenGuard
{
    public static function handle(): void
    {
        $payload = CookieManager::validateAuthCookie();

        if (!$payload) {
            header("Location: /login");
            exit;
        }

        $_REQUEST['auth_user_id'] = $payload['uid'];
    }
}
