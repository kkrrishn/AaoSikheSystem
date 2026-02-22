<?php
declare(strict_types=1);

namespace App\helpers;

class AuthHelper
{
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function getUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    public static function isInstructor(): bool
    {
        $role = $_SESSION['user']['role'] ?? '';
        return $role === 'instructor' || $role === 'admin';
    }

    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['flash_error'] = 'Please login to access this page.';
            header('Location: /login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        
        if (!self::isAdmin()) {
            $_SESSION['flash_error'] = 'Access denied. Administrator privileges required.';
            header('Location: /');
            exit;
        }
    }

    public static function requireInstructor(): void
    {
        self::requireAuth();
        
        if (!self::isInstructor()) {
            $_SESSION['flash_error'] = 'Access denied. Instructor privileges required.';
            header('Location: /');
            exit;
        }
    }

    public static function redirectIfLoggedIn(string $redirectTo = '/dashboard'): void
    {
        if (self::isLoggedIn()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    public static function generatePasswordHash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateRandomPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}