<?php
namespace AaoSikheSystem\cookies;

use AaoSikheSystem\Security\SecurityHelper;

class CookieFingerprint
{
    public static function generate(): string
    {
        $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';

        $partialIp = substr($ip, 0, strrpos($ip, '.'));
        $salt = SecurityHelper::appKey();

        return hash('sha256', $ua . $lang . $partialIp . $salt);
    }

    public static function ipHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return hash('sha256', $ip);
    }
}
