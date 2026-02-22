<?php

declare(strict_types=1);

return [
    'name' => \AaoSikheSystem\Env::get('APP_NAME', 'AaoSikhe'),
    'env' => \AaoSikheSystem\Env::get('APP_ENV', 'development'), // or 'development',production
    'debug' => \AaoSikheSystem\Env::get('APP_DEBUG', false),
    'url' => \AaoSikheSystem\Env::get('APP_URL', 'http://localhost'),
    'timezone' => \AaoSikheSystem\Env::get('APP_TIMEZONE', 'UTC'),
    'key' => \AaoSikheSystem\Env::get('APP_KEY'),
    'cipher' => 'AES-256-GCM',
    
    /*
    |--------------------------------------------------------------------------
    | Application Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'base' => BASE_PATH ?? dirname(__DIR__),
        'app' => \AaoSikheSystem\Env::get('APP_PATH', BASE_PATH . '/app'),
        'config' => \AaoSikheSystem\Env::get('CONFIG_PATH', BASE_PATH . '/config'),
        'public' => \AaoSikheSystem\Env::get('PUBLIC_PATH', BASE_PATH . '/public'),
        'storage' => \AaoSikheSystem\Env::get('STORAGE_PATH', BASE_PATH . '/storage'),
        'database' => \AaoSikheSystem\Env::get('DATABASE_PATH', BASE_PATH . '/database'),
        'resources' => \AaoSikheSystem\Env::get('RESOURCES_PATH', BASE_PATH . '/resources'),
        'uploads' => \AaoSikheSystem\Env::get('UPLOADS_PATH', BASE_PATH . '/storage/uploads'),
        'logs' => \AaoSikheSystem\Env::get('LOGS_PATH', BASE_PATH . '/storage/logs'),
        'cache' => \AaoSikheSystem\Env::get('CACHE_PATH', BASE_PATH . '/storage/framework/cache'),
        'sessions' => \AaoSikheSystem\Env::get('SESSIONS_PATH', BASE_PATH . '/storage/framework/sessions'),
        'views' => \AaoSikheSystem\Env::get('VIEWS_PATH', BASE_PATH . '/storage/framework/views'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Custom Application Paths 
    |--------------------------------------------------------------------------
    */
    'custom_paths' => [
        'user_uploads' => BASE_PATH .'/storage/uploads/users',
        'temporary_files' => BASE_PATH .'/storage/temp',
        'exports' => BASE_PATH .'/storage/exports',
        'backups' => BASE_PATH .'/storage/backups',
        'secure_documents' => BASE_PATH .'/storage/secure',
        'profile_pictures' => BASE_PATH .'/storage/uploads/profiles',
        'course_materials' => BASE_PATH .'/storage/uploads/courses',
        'education_documents'=>BASE_PATH.'/storage/uploads/education_documents',
        'document'=>BASE_PATH.'/storage/uploads/documents',
    ],
    
    'security' => [
        'csrf' => true,
        'csp' => false,
        'hsts' => true,
        'xss_protection' => true,
        'content_type_nosniff' => true,
        'frame_options' => 'DENY',
    ],
    
    'session' => [
        'name' => 'AaoSikheSystem_session',
        'lifetime' => 7200, // 2 hours
        'path' => '/',
        'domain' => \AaoSikheSystem\Env::get('SESSION_DOMAIN'),
        'secure' => \AaoSikheSystem\Env::get('APP_ENV') === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'performance' => [
        'monitor' => \AaoSikheSystem\Env::get('PERFORMANCE_MONITOR', true),
        'log_slow_pages' => \AaoSikheSystem\Env::get('LOG_SLOW_PAGES', true),
        'slow_page_threshold' => \AaoSikheSystem\Env::get('SLOW_PAGE_THRESHOLD', 2.0), // seconds
    ],
    // ==========================
    // SYSTEM TOGGLES
    // ==========================
    'features' => [
        'cache'        => false,   // Enable/Disable all caching
        'logger'       => true,   // Enable logging system
        'monitoring'   => true,   // Enable visit tracking, performance monitor, etc.
        'encryption'   => true,   // Enable crypto operations
        'rate_limit'=>true,
    ],
    /*
|--------------------------------------------------------------------------
| Cookie Security (Zero Trust Architecture)
|--------------------------------------------------------------------------
*/

'cookie_security' => [

    // Enable full cookie security layer
    'enabled' => true,

    // Strict IP matching
    'strict_ip_check' => true,

    // Enable device fingerprint validation
    'enable_fingerprint' => true,

    // Token rotation system
    'token_rotation' => true,

    // Rotate every X seconds
    'rotation_interval' => 900, // 15 minutes

    // Multi device limit per user
    'multi_device_limit' => 3,

    // Cookie name
    'cookie_name' => 'AaoSikhe_auth',

    // Lifetime (seconds)
    'lifetime' => 3600,

    // Force HTTPS only
    'require_https' => true,

],


];