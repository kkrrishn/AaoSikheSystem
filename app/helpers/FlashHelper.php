<?php
declare(strict_types=1);

namespace App\helpers;

class FlashHelper
{
    public static function set(string $type, string $message): void
    {
        $_SESSION["flash_{$type}"] = $message;
    }

    public static function get(string $type): string
    {
        $message = $_SESSION["flash_{$type}"] ?? '';
        unset($_SESSION["flash_{$type}"]);
        return $message;
    }

    public static function has(string $type): bool
    {
        return isset($_SESSION["flash_{$type}"]);
    }

    public static function success(string $message): void
    {
        self::set('success', $message);
    }

    public static function error(string $message): void
    {
        self::set('error', $message);
    }

    public static function warning(string $message): void
    {
        self::set('warning', $message);
    }

    public static function info(string $message): void
    {
        self::set('info', $message);
    }

    public static function display(): void
    {
        $types = ['success', 'error', 'warning', 'info'];
        
        foreach ($types as $type) {
            if (self::has($type)) {
                $message = self::get($type);
                echo self::renderMessage($type, $message);
            }
        }
    }

    private static function renderMessage(string $type, string $message): string
    {
        $icons = [
            'success' => 'check-circle',
            'error' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle'
        ];

        $icon = $icons[$type] ?? 'info-circle';
        $title = ucfirst($type);

        return "
            <div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
                <div class=\"d-flex align-items-center\">
                    <i class=\"fas fa-{$icon} me-2\"></i>
                    <div>
                        <strong>{$title}:</strong> {$message}
                    </div>
                </div>
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
            </div>
        ";
    }

    public static function setOldInput(array $input): void
    {
        $_SESSION['old_input'] = $input;
    }

    public static function getOldInput(string $key, $default = ''): string
    {
        return $_SESSION['old_input'][$key] ?? $default;
    }

    public static function clearOldInput(): void
    {
        unset($_SESSION['old_input']);
    }
}