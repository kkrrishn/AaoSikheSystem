<?php

declare(strict_types=1);

namespace AaoSikheSystem\view\helper;

/**
 * URL View Helper - Template-level URL generation and manipulation
 * 
 * @package AaoSikheSystem
 */
class UrlHelper
{
    private static string $baseUrl;
    private static string $basePath = '/';
    private static array $namedRoutes = [];
    private static bool $prettyUrls = true;
    private static ?string $currentRouteName = null;



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
     * Generate full URL for path
     */
    public static function to(string $path = '', array $params = []): string
    {
        $url = self::$baseUrl . self::$basePath . ltrim($path, '/');

        if (!empty($params)) {
            $queryString = http_build_query($params);
            $separator = strpos($url, '?') === false ? '?' : '&';
            $url .= $separator . $queryString;
        }

        return $url;
    }

    /**
     * Generate URL for named route
     */
    public static function route(string $name, array $params = [], array $query = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' not found");
        }

        $route = self::$namedRoutes[$name];
        $url = self::$baseUrl . self::$basePath . ltrim($route, '/');


        // Replace route parameters
        foreach ($params as $key => $value) {
            $url = str_replace(['{' . $key . '}', '{' . $key . '?}'], (string)$value, $url);
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
     * Register named routes
     */
    public static function registerRoutes(array $routes): void
    {
        self::$namedRoutes = array_merge(self::$namedRoutes, $routes);
    }

    /**
     * Generate secure URL (HTTPS)
     */
    public static function secure(string $path = '', array $params = []): string
    {
        $url = self::to($path, $params);
        return preg_replace('/^http:/', 'https:', $url);
    }

    /**
     * Generate URL for asset
     */
    public static function asset(string $path): string
    {
        return self::$baseUrl . '/assets/' . ltrim($path, '/');
    }

    /**
     * Generate current URL with modifications
     */
    public static function current(array $params = [], bool $merge = true): string
    {
        $current = self::getCurrentUrl();

        if (empty($params)) {
            return $current;
        }

        if ($merge) {
            $currentParams = self::getCurrentQueryParams();
            $params = array_merge($currentParams, $params);
        }

        $baseUrl = strtok($current, '?');

        if (empty($params)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Get current URL without query parameters
     */
    public static function currentWithoutQuery(): string
    {
        $current = self::getCurrentUrl();
        return strtok($current, '?');
    }

    /**
     * Add query parameters to current URL
     */
    public static function withQuery(array $params): string
    {
        return self::current($params, true);
    }

    /**
     * Remove query parameters from current URL
     */
    public static function withoutQuery(array $keys = []): string
    {
        $currentParams = self::getCurrentQueryParams();

        if (empty($keys)) {
            return self::currentWithoutQuery();
        }

        $filteredParams = array_diff_key($currentParams, array_flip($keys));
        return self::current($filteredParams, false);
    }

    /**
     * Generate URL with fragment
     */
    public static function withFragment(string $url, string $fragment): string
    {
        $base = strtok($url, '#');
        return $base . '#' . urlencode($fragment);
    }

    /**
     * Generate mailto link
     */
    public static function mailto(string $email, string $text = null, array $params = []): string
    {
        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        $displayText = $text ?: $email;

        return "<a href=\"mailto:{$email}{$queryString}\">{$displayText}</a>";
    }

    /**
     * Generate tel link
     */
    public static function tel(string $phoneNumber, string $text = null): string
    {
        $cleanNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        $displayText = $text ?: $phoneNumber;

        return "<a href=\"tel:{$cleanNumber}\">{$displayText}</a>";
    }

    /**
     * Generate social media share URLs
     */
    public static function share(string $platform, string $url, string $text = ''): string
    {
        $encodedUrl = urlencode($url);
        $encodedText = urlencode($text);

        return match ($platform) {
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}",
            'twitter' => "https://twitter.com/intent/tweet?url={$encodedUrl}&text={$encodedText}",
            'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url={$encodedUrl}",
            'whatsapp' => "https://api.whatsapp.com/send?text={$encodedText}%20{$encodedUrl}",
            'telegram' => "https://t.me/share/url?url={$encodedUrl}&text={$encodedText}",
            'email' => "mailto:?subject=Shared%20Link&body={$encodedText}%20{$encodedUrl}",
            default => $url
        };
    }

    /**
     * Check if current URL matches pattern
     */
    public static function is(string $pattern): bool
    {
        $current = self::currentWithoutQuery();
        $patternUrl = self::to($pattern);

        return $current === $patternUrl;
    }

    /**
     * Check if current URL starts with pattern
     */
    public static function startsWith(string $pattern): bool
    {
        $current = self::currentWithoutQuery();
        $patternUrl = self::to($pattern);

        return strpos($current, $patternUrl) === 0;
    }

    /**
     * Check if current URL contains string
     */
    public static function contains(string $string): bool
    {
        return strpos(self::getCurrentUrl(), $string) !== false;
    }

    /**
     * Generate pagination URL
     */
    public static function pagination(int $page, string $pageParam = 'page'): string
    {
        return self::withQuery([$pageParam => $page]);
    }

    /**
     * Generate sorting URL
     */
    public static function sort(string $column, string $direction = 'asc', string $sortParam = 'sort', string $dirParam = 'dir'): string
    {
        $params = [
            $sortParam => $column,
            $dirParam => $direction
        ];

        return self::withQuery($params);
    }

    /**
     * Get current URL
     */
    private static function getCurrentUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . '://' . $host . $uri;
    }

    /**
     * Get current query parameters
     */
    private static function getCurrentQueryParams(): array
    {
        parse_str($_SERVER['QUERY_STRING'] ?? '', $params);
        return $params;
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
     * Escape URL for safe output
     */
    public static function escape(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate URL for file download
     */
    public static function download(string $filename, string $displayName = null): string
    {
        $displayName = $displayName ?: basename($filename);
        $url = self::to('download/' . $filename);

        return "<a href=\"{$url}\" download=\"{$displayName}\">{$displayName}</a>";
    }

    /**
     * Safe route generation with fallback
     */
    public static function routeSafe(string $name, array $params = [], array $query = [], string $fallback = '#'): string
    {
        try {
            return self::route($name, $params, $query);
        } catch (\InvalidArgumentException $e) {
            // Log the error in development
            if (self::isDevelopment()) {
                error_log("Route '{$name}' not found: " . $e->getMessage());
            }
            return $fallback;
        }
    }

    /**
     * Check if route exists
     */
    public static function routeExists(string $name): bool
    {
        return isset(self::$namedRoutes[$name]);
    }

    /**
     * Check if running in development mode
     */
    private static function isDevelopment(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'production') === 'development';
    }
 
public static function isRoute(string $name): bool
{
    
    return self::$currentRouteName === $name;
}

public static function setCurrentRouteName(?string $name): void
{
    self::$currentRouteName = $name;
}


    public static function bindRouter(\AaoSikheSystem\router\Router $router): void
    {
        self::$namedRoutes = $router->getNamedRoutes();
        
    }
}
