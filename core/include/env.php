<?php

declare(strict_types=1);
namespace AaoSikheSystem;
/**
 * AaoSikheSystem Secure - Environment Configuration
 * 
 * @package AaoSikheSystem
 */

class Env
{
    private static ?array $cache = null;
    private static string $envPath = __DIR__ . '/../../.env';
        
    public static function load(): void
    {
        if (!file_exists(self::$envPath)) {
            return;
        }
       

        $lines = file(self::$envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            [$name, $value] = self::parseLine($line);
            if ($name !== '') {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }
    }

    private static function parseLine(string $line): array
    {
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            return ['', ''];
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);

        // Remove quotes
        if (preg_match('/^"([^"]*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match('/^\'([^\']*)\'$/', $value, $matches)) {
            $value = $matches[1];
        }

        // Handle comments
        if (($pos = strpos($value, ' #')) !== false) {
            $value = substr($value, 0, $pos);
        }

        return [$name, $value];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }

        // Convert to appropriate types
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
        }

        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        return $value;
    }

    public static function getRequired(string $key): mixed
    {
        $value = self::get($key);
        
        if ($value === null) {
            throw new \RuntimeException("Required environment variable {$key} is not set");
        }
        
        return $value;
    }
}