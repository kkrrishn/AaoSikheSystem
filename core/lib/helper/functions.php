<?php

namespace AaoSikheSystem\helper;

/**
 * Get configuration value
 */
function appConfig(string $key = null, $default = null)
{
    static $config = null;
    
    // Load config once
    if ($config === null) {
        $configFile = dirname(__DIR__, 2) . '/config/app.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            $config = [];
        }
    }
    
    // Return entire config if no key specified
    if ($key === null) {
        return $config;
    }
    
    // Get nested config value
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }
    
    return $value;
}