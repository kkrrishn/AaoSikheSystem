<?php

declare(strict_types=1);

namespace AaoSikheSystem\Security;

class Captcha
{
    public static function generateText(int $length = 6): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        return substr(str_shuffle($chars), 0, $length);
    }

    public static function encrypt(string $text): string
    {
        $key = hash('sha256', APP_KEY, true);
        return Crypto::encryptAes($text, $key);
    }

    public static function decrypt(string $token): string
    {
        $key = hash('sha256', APP_KEY, true);
        return Crypto::decryptAes($token, $key);
    }
}
