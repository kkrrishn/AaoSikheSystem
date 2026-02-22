<?php
namespace AaoSikheSystem\helper;

class FeatureManager
{
    private static array $features = [];

    public static function init(array $config): void
    {
        self::$features = $config['features'] ?? [];
    }

    public static function isEnabled(string $feature): bool
    {
        return !empty(self::$features[$feature]) && self::$features[$feature] === true;
    }

    public static function enable(string $feature): void
    {
        self::$features[$feature] = true;
    }

    public static function disable(string $feature): void
    {
        self::$features[$feature] = false;
    }

    public static function all(): array
    {
        return self::$features;
    }
}
