<?php

declare(strict_types=1);

namespace AaoSikheSystem\Security;

class CaptchaValidator
{
    public static function validate(string $input): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['captcha'])) {
            return false;
        }

        // Expire after 2 minutes
        if (time() - $_SESSION['captcha']['time'] > 240) {
            unset($_SESSION['captcha']);
            return false;
        }

        $token = $_SESSION['captcha']['token'];
        unset($_SESSION['captcha']); // one-time use

        $original = Captcha::decrypt($token);

        return hash_equals($original, strtoupper(trim($input)));
    }
}

