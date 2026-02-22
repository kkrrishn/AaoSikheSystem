<?php

declare(strict_types=1);

namespace AaoSikheSystem\helper;

/**
 * URL Helper - Handles URL generation, parsing and manipulation
 * 
 * @package AaoSikheSystem
 */
class UrlHelper
{
    private static string $baseUrl;
    private static string $basePath;
    private static bool $prettyUrls = true;
    private static array $routes = [];
    
    /**
     * Initialize URL helper
     */
    public static function init(string $baseUrl, string $basePath = '/', bool $prettyUrls = true): void
    {
        self::$baseUrl = rtrim($baseUrl, '/');
        self::$basePath = rtrim($basePath, '/') . '/';
        self::$prettyUrls = $prettyUrls;
    }
    
    /**
     * Get full URL for path
     */
    public static function to(string $path = '', array $params = []): string
    {
        $url = self::$baseUrl . self::$basePath . ltrim($path, '/');
        
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $queryString;
        }
        
        return $url;
    }
    
    /**
     * Get current URL
     */
    public static function current(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Get current URL without query parameters
     */
    public static function currentWithoutQuery(): string
    {
        $current = self::current();
        return strtok($current, '?');
    }
    
    /**
     * Get previous URL from referer
     */
    public static function previous(string $fallback = '/'): string
    {
        return $_SERVER['HTTP_REFERER'] ?? self::to($fallback);
    }
    
    /**
     * Get base URL
     */
    public static function base(): string
    {
        return self::$baseUrl;
    }
    
    /**
     * Get base path
     */
    public static function basePath(): string
    {
        return self::$basePath;
    }
    
    /**
     * Check if current URL matches pattern
     */
    public static function is(string $pattern): bool
    {
        $current = self::currentWithoutQuery();
        $pattern = self::$baseUrl . self::$basePath . ltrim($pattern, '/');
        
        return $current === $pattern;
    }
    
    /**
     * Check if current URL starts with pattern
     */
    public static function startsWith(string $pattern): bool
    {
        $current = self::currentWithoutQuery();
        $pattern = self::$baseUrl . self::$basePath . ltrim($pattern, '/');
        
        return strpos($current, $pattern) === 0;
    }
    
    /**
     * Check if current URL contains string
     */
    public static function contains(string $string): bool
    {
        return strpos(self::current(), $string) !== false;
    }
    
    /**
     * Get specific segment from URL
     */
    public static function segment(int $index, string $default = ''): string
    {
        $path = parse_url(self::current(), PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        return $segments[$index - 1] ?? $default;
    }
    
    /**
     * Get all URL segments
     */
    public static function segments(): array
    {
        $path = parse_url(self::current(), PHP_URL_PATH);
        return array_filter(explode('/', trim($path, '/')));
    }
    
    /**
     * Get query parameter
     */
    public static function query(string $key, string $default = ''): string
    {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get all query parameters
     */
    public static function queries(): array
    {
        return $_GET;
    }
    
    /**
     * Add query parameters to URL
     */
    public static function withQuery(array $params): string
    {
        $current = self::currentWithoutQuery();
        $existingParams = self::queries();
        $mergedParams = array_merge($existingParams, $params);
        
        if (empty($mergedParams)) {
            return $current;
        }
        
        return $current . '?' . http_build_query($mergedParams);
    }
    
    /**
     * Remove query parameters from URL
     */
    public static function withoutQuery(array $keys = []): string
    {
        $current = self::currentWithoutQuery();
        $existingParams = self::queries();
        
        if (empty($keys)) {
            return $current;
        }
        
        $filteredParams = array_diff_key($existingParams, array_flip($keys));
        
        if (empty($filteredParams)) {
            return $current;
        }
        
        return $current . '?' . http_build_query($filteredParams);
    }
    
    /**
     * Generate route URL
     */
    public static function route(string $name, array $params = [], array $query = []): string
    {
        if (!isset(self::$routes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' not found");
        }
        
        $route = self::$routes[$name];
        $url = self::$baseUrl . self::$basePath . ltrim($route, '/');
        
        // Replace route parameters
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
            $url = str_replace('{' . $key . '?}', $value, $url);
        }
        
        // Remove optional parameters that weren't provided
        $url = preg_replace('/\{[^}]+\?\}/', '', $url);
        
        // Add query parameters
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        
        return $url;
    }
    
    /**
     * Register routes for URL generation
     */
    public static function registerRoutes(array $routes): void
    {
        self::$routes = array_merge(self::$routes, $routes);
    }
    
    /**
     * Get route by name
     */
    public static function getRoute(string $name): ?string
    {
        return self::$routes[$name] ?? null;
    }
    
    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        if (!headers_sent()) {
            header("Location: {$url}", true, $statusCode);
            exit;
        }
        
        // Fallback if headers already sent
        echo "<script>window.location.href='{$url}';</script>";
        exit;
    }
    
    /**
     * Redirect to route
     */
    public static function redirectToRoute(string $name, array $params = [], array $query = []): void
    {
        $url = self::route($name, $params, $query);
        self::redirect($url);
    }
    
    /**
     * Redirect back to previous page
     */
    public static function redirectBack(string $fallback = '/'): void
    {
        self::redirect(self::previous($fallback));
    }
    
    /**
     * Check if URL is absolute
     */
    public static function isAbsolute(string $url): bool
    {
        return preg_match('/^https?:\/\//', $url) === 1;
    }
    
    /**
     * Make URL absolute if it's relative
     */
    public static function absolute(string $url): string
    {
        if (self::isAbsolute($url)) {
            return $url;
        }
        
        return self::$baseUrl . self::$basePath . ltrim($url, '/');
    }
    
    /**
     * Get domain from URL
     */
    public static function domain(string $url = ''): string
    {
        $url = $url ?: self::current();
        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }
    
    /**
     * Get scheme from URL
     */
    public static function scheme(string $url = ''): string
    {
        $url = $url ?: self::current();
        $parsed = parse_url($url);
        return $parsed['scheme'] ?? 'http';
    }
    
    /**
     * Get path from URL
     */
    public static function path(string $url = ''): string
    {
        $url = $url ?: self::current();
        $parsed = parse_url($url);
        return $parsed['path'] ?? '/';
    }
    
    /**
     * Validate URL format
     */
    public static function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Encode URL components
     */
    public static function encode(string $url): string
    {
        return urlencode($url);
    }
    
    /**
     * Decode URL components
     */
    public static function decode(string $url): string
    {
        return urldecode($url);
    }
    
    /**
     * Get URL fragment (hash)
     */
    public static function fragment(string $url = ''): string
    {
        $url = $url ?: self::current();
        $parsed = parse_url($url);
        return $parsed['fragment'] ?? '';
    }
    
    /**
     * Add fragment to URL
     */
    public static function withFragment(string $url, string $fragment): string
    {
        $base = strtok($url, '#');
        return $base . '#' . $fragment;
    }
    
    /**
     * Secure URL (force HTTPS)
     */
    public static function secure(string $url = ''): string
    {
        $url = $url ?: self::current();
        return preg_replace('/^http:/', 'https:', $url);
    }
    
    /**
     * Check if current request is HTTPS
     */
    public static function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               ($_SERVER['SERVER_PORT'] ?? null) == 443 ||
               ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https';
    }
    
    /**
     * Get client IP address
     */
    public static function clientIp(): string
    {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}