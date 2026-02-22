<?php

declare(strict_types=1);

namespace AaoSikheSystem\helper;

use AaoSikheSystem\Env;

class PathManager
{
    private static array $paths = [];

    private static array $app = []; // ✅ ADD THIS
    /**
     * Load paths from base and environment-specific config.
     */
    public static function load(array $config): void
    {
        $env = Env::get('APP_ENV', 'production');
        // ✅ Store full app config
        self::$app = $config;
        self::$paths = [];

        // Load base paths
        if (isset($config['paths'])) {
            self::$paths = array_merge(self::$paths, $config['paths']);
        }

        // Load custom paths
        if (isset($config['custom_paths'])) {
            self::$paths = array_merge(self::$paths, $config['custom_paths']);
        }

        // Check if environment-specific config exists
        $envConfigFile = dirname(__DIR__, 2) . "/config/app.{$env}.php";
        if (file_exists($envConfigFile)) {
            $envConfig = require $envConfigFile;
            if (isset($envConfig['paths'])) {
                self::$paths = array_merge(self::$paths, $envConfig['paths']);
            }
            if (isset($envConfig['custom_paths'])) {
                self::$paths = array_merge(self::$paths, $envConfig['custom_paths']);
            }
        }

        // Replace placeholders with BASE_PATH for safety
        foreach (self::$paths as $key => $path) {
            if (str_starts_with($path, '/')) {
                self::$paths[$key] = BASE_PATH . $path;
            }
        }
    }

    /**
     * Get a specific path
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$paths[$key] ?? $default;
    }
    /**
 * Get absolute filesystem path
 * Example: /var/www/app/storage/uploads/profiles
 */
public static function absolute(string $key): ?string
{
    $path = self::get($key);

    if (!$path) {
        return null;
    }

    // Normalize slashes
    $path = str_replace('\\', '/', $path);

    // If already absolute, return as-is
    if (str_starts_with($path, '/')) {
        return $path;
    }

    // Otherwise, prepend BASE_PATH
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}

    
    /**
     * Get relative web path (for encrypted media, streaming, etc.)
     * Example: /storage/uploads/profiles/
     */
    public static function relative(string $key): ?string
    {
        $path = self::get($key);
        if (!$path) {
            return null;
        }

        // Normalize slashes
        $path = str_replace('\\', '/', $path);
        $base = str_replace('\\', '/', BASE_PATH);

        // Remove BASE_PATH → return web-relative path
        if (str_starts_with($path, $base)) {
            return substr($path, strlen($base));
        }

        return $path;
    }


    public static function url(string $key): ?string
    {
        $path = self::get($key);
        if (!$path) return null;

        // Remove BASE_PATH from absolute path
        $relative = str_replace('\\', '/', str_replace(BASE_PATH, '', $path));

        return rtrim(self::$app['url'] ?? '', '/') . $relative;
    }

    /**
     * Get all paths
     */
    public static function all(): array
    {
        return self::$paths;
    }

    /* ===========================
        APP CONFIG ACCESS
    =========================== */

    public static function config(string $key, mixed $default = null): mixed
    {
        return self::$app[$key] ?? $default;
    }

    public static function feature(string $key): bool
    {
        return self::$app['features'][$key] ?? false;
    }



    /**
     * Define constants automatically (optional)
     */
    public static function defineConstants(): void
    {
        // ✅ APP CONSTANTS
        if (!defined('APP_NAME')) {
            define('APP_NAME', self::$app['name'] ?? 'Application');
        }

        if (!defined('APP_ENV')) {
            define('APP_ENV', self::$app['env'] ?? 'production');
        }

        if (!defined('APP_URL')) {
            define('APP_URL', rtrim(self::$app['url'] ?? '', '/'));
        }
        if (!defined('APP_KEY')) {
            if (empty(self::$app['key'])) {
                throw new \RuntimeException('APP_KEY is not set');
            }
            define('APP_KEY', self::$app['key']);
        }
        // ✅ PATH CONSTANTS
        foreach (self::$paths as $key => $value) {
            $constName = strtoupper($key) . '_PATH';
            if (!defined($constName)) {
                define($constName, $value);
            }
        }
    }
}
