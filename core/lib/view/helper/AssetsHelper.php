<?php

declare(strict_types=1);

namespace AaoSikheSystem\view\helper;

/**
 * Assets View Helper - Template-level asset management
 * 
 * @package AaoSikheSystem
 */
class AssetsHelper
{
    private static string $baseUrl;
    private static string $assetsPath = '/assets/';
    private static string $cacheBuster = '';
    private static array $loadedAssets = [];
    private static array $assetBundles = [];
    private static array $inlineScripts = [];
    private static array $inlineStyles = [];

    /**
     * Initialize assets helper
     */
    public static function init(string $baseUrl, string $assetsPath = '/assets/', string $version = ''): void
    {
        self::$baseUrl = rtrim($baseUrl, '/');
        self::$assetsPath = rtrim($assetsPath, '/') . '/';
        self::$cacheBuster = $version ? '?v=' . $version : '?v=' . date('YmdHis');
    }

    /**
     * CSS file inclusion with automatic cache busting
     */
    public static function css(string $path, array $attributes = []): string
    {
        $key = 'css_' . $path;
        if (in_array($key, self::$loadedAssets)) {
            return '';
        }

        self::$loadedAssets[] = $key;
        
        $url = self::getAssetUrl($path);
        $attrs = self::buildAttributes($attributes);

        return "<link rel=\"stylesheet\" href=\"{$url}\"{$attrs}>\n";
    }

    /**
     * JavaScript file inclusion with automatic cache busting
     */
    public static function js(string $path, array $attributes = []): string
    {
        $key = 'js_' . $path;
        if (in_array($key, self::$loadedAssets)) {
            return '';
        }

        self::$loadedAssets[] = $key;
        
        $url = self::getAssetUrl($path);
        $attrs = self::buildAttributes($attributes);

        return "<script src=\"{$url}\"{$attrs}></script>\n";
    }

    /**
     * Image tag with responsive attributes support
     */
    public static function img(string $path, string $alt = '', array $attributes = []): string
    {
        $url = self::getAssetUrl($path);
        $attrs = self::buildAttributes($attributes);

        if (!isset($attributes['alt'])) {
            $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
            $attrs .= " alt=\"{$alt}\"";
        }

        return "<img src=\"{$url}\"{$attrs}>";
    }

    /**
     * Responsive image with srcset
     */
    public static function responsiveImg(string $basePath, array $sizes, string $alt = '', array $attributes = []): string
    {
        $srcset = [];
        foreach ($sizes as $width => $path) {
            $url = self::getAssetUrl($path);
            $srcset[] = "{$url} {$width}w";
        }

        $url = self::getAssetUrl($basePath);
        $srcsetString = implode(', ', $srcset);
        $attrs = self::buildAttributes($attributes);

        return "<img src=\"{$url}\" srcset=\"{$srcsetString}\" alt=\"{$alt}\"{$attrs}>";
    }

    /**
     * Favicon inclusion
     */
    public static function favicon(string $path = 'img/favicon.ico'): string
    {
        $url = self::getAssetUrl($path);
        return "<link rel=\"shortcut icon\" href=\"{$url}\" type=\"image/x-icon\">\n";
    }

    /**
     * Preload critical assets
     */
    public static function preload(string $path, string $as = 'script',$tag=''): string
    {
        $url = self::getAssetUrl($path);
        return "<link rel=\"preload\" href=\"{$url}\" as=\"{$as}\"{$tag} >\n";
    }

    /**
     * Inline CSS for critical styles
     */
    public static function inlineCss(string $css): string
    {
        $hashed = md5($css);
        $key = 'inline_css_' . $hashed;

        if (!in_array($key, self::$inlineStyles)) {
            self::$inlineStyles[] = $key;
            return "<style>{$css}</style>\n";
        }

        return '';
    }

    /**
     * Inline JavaScript
     */
    public static function inlineJs(string $js, bool $defer = false): string
    {
        $hashed = md5($js);
        $key = 'inline_js_' . $hashed;

        if (!in_array($key, self::$inlineScripts)) {
            self::$inlineScripts[] = $key;
            $deferAttr = $defer ? ' defer' : '';
            return "<script{$deferAttr}>{$js}</script>\n";
        }

        return '';
    }

    /**
     * Load asset bundle (multiple CSS/JS files)
     */
    public static function bundle(string $bundleName): string
    {
        if (!isset(self::$assetBundles[$bundleName])) {
            return "<!-- Bundle '{$bundleName}' not found -->\n";
        }

        $output = '';
        $bundle = self::$assetBundles[$bundleName];

        // Load CSS files
        foreach ($bundle['css'] ?? [] as $cssFile) {
            $output .= self::css($cssFile);
        }

        // Load JS files
        foreach ($bundle['js'] ?? [] as $jsFile) {
            $output .= self::js($jsFile);
        }

        return $output;
    }

    /**
     * Define an asset bundle
     */
    public static function defineBundle(string $name, array $cssFiles = [], array $jsFiles = []): void
    {
        self::$assetBundles[$name] = [
            'css' => $cssFiles,
            'js' => $jsFiles
        ];
    }

    /**
     * Google Fonts loader
     */
    public static function googleFonts(array $families, array $subsets = ['latin']): string
    {
        $familyParams = [];
        foreach ($families as $family => $weights) {
            if (is_array($weights)) {
                $weights = implode(',', $weights);
            }
            $familyParams[] = str_replace(' ', '+', $family) . ':' . $weights;
        }

        $familyString = implode('|', $familyParams);
        $subsetString = implode(',', $subsets);

        $output = "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
        $output .= "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
        $output .= "<link href=\"https://fonts.googleapis.com/css2?family={$familyString}&display=swap&subset={$subsetString}\" rel=\"stylesheet\">\n";

        return $output;
    }

    /**
     * Font Awesome icons
     */
    public static function fontAwesome(string $version = '6.4.0', string $kitCode = ''): string
    {
        if ($kitCode) {
            return "<script src=\"https://kit.fontawesome.com/{$kitCode}.js\" crossorigin=\"anonymous\"></script>\n";
        }

        return self::css("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/{$version}/css/all.min.css");
    }

    /**
     * Bootstrap CSS/JS
     */
    public static function bootstrap(string $version = '5.3.0', bool $includeJs = true, bool $includeIcons = false): string
    {
        $output = self::css("https://cdn.jsdelivr.net/npm/bootstrap@{$version}/dist/css/bootstrap.min.css");

        if ($includeIcons) {
            $output .= self::css("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css");
        }

        if ($includeJs) {
            $output .= self::js("https://cdn.jsdelivr.net/npm/bootstrap@{$version}/dist/js/bootstrap.bundle.min.js");
        }

        return $output;
    }

    /**
     * jQuery inclusion
     */
    public static function jquery(string $version = '3.6.0'): string
    {
        return self::js("https://code.jquery.com/jquery-{$version}.min.js");
    }

    /**
     * Get full asset URL
     */
    private static function getAssetUrl(string $path): string
    {
        // If it's already a full URL, return as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $url = self::$baseUrl . self::$assetsPath . ltrim($path, '/');

        // Add cache buster for local assets
        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            $url .= self::$cacheBuster;
        }

        return $url;
    }

    /**
     * Build HTML attributes string
     */
    private static function buildAttributes(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $html = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html[] = $key;
                }
            } elseif ($value !== null) {
                $html[] = $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return $html ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Get all loaded assets (for debugging)
     */
    public static function getLoadedAssets(): array
    {
        return self::$loadedAssets;
    }

    /**
     * Clear loaded assets cache
     */
    public static function clearCache(): void
    {
        self::$loadedAssets = [];
        self::$inlineScripts = [];
        self::$inlineStyles = [];
    }
}