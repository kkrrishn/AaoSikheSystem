<?php

declare(strict_types=1);

namespace AaoSikheSystem\Session;

use AaoSikheSystem\helper\PathManager;

class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $session = PathManager::config('session', []);

        session_name($session['name'] ?? 'APP_SESSION');

        session_set_cookie_params([
            'lifetime' => $session['lifetime'] ?? 0,
            'path'     => $session['path'] ?? '/',
            'domain'   => $session['domain'] ?? '',
            'secure'   => $session['secure'] ?? false,
            'httponly' => $session['httponly'] ?? true,
            'samesite' => $session['samesite'] ?? 'Lax',
        ]);

        session_start();
    }

    /* ================= FLASH MESSAGES ================= */

    public static function flashSuccess(string $message): void
    {
        $_SESSION['flash_success'] = $message;
    }

    public static function flashError(string $message): void
    {
        $_SESSION['flash_error'] = $message;
    }

    public static function getFlashSuccess(): ?string
    {
        if (!isset($_SESSION['flash_success'])) {
            return null;
        }

        $message = $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
        return $message;
    }

    public static function getFlashError(): ?string
    {
        if (!isset($_SESSION['flash_error'])) {
            return null;
        }

        $message = $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
        return $message;
    }

    /* ================= OLD INPUT ================= */

    public static function setOldInput(array $data): void
    {
        $_SESSION['old_input'] = $data;
    }

    public static function old(string $key, $default = '')
    {
        return $_SESSION['old_input'][$key] ?? $default;
    }

    public static function clearOldInput(): void
    {
        unset($_SESSION['old_input']);
    }

    /* ================= SESSION ARRAY HELPERS ================= */

    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $session = &$_SESSION;

        foreach ($keys as $k) {
            if (!isset($session[$k]) || !is_array($session[$k])) {
                $session[$k] = [];
            }
            $session = &$session[$k];
        }

        $session = $value;
    }

    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $session = $_SESSION;

        foreach ($keys as $k) {
            if (!isset($session[$k])) {
                return $default;
            }
            $session = $session[$k];
        }

        return $session;
    }

    public static function has(string $key): bool
    {
        return self::get($key, '__not_found__') !== '__not_found__';
    }

    public static function user(string $key=''){
        if(!isset($_SESSION['user'])||!isset($_SESSION['user'][$key]))
            return false;
        if(empty($key)||$key==null)
            return $_SESSION['user'];

        return $_SESSION['user'][$key];
    }

    public static function forget(string $key): void
    {
        $keys = explode('.', $key);
        $session = &$_SESSION;

        $lastKey = array_pop($keys);

        foreach ($keys as $k) {
            if (!isset($session[$k]) || !is_array($session[$k])) {
                return;
            }
            $session = &$session[$k];
        }

        unset($session[$lastKey]);
    }
}
