<?php
// config/cache.php
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'prefix' => env('CACHE_PREFIX', 'aao_sikhe_'),
    
    'drivers' => [
        'file' => [
            'path' => __DIR__ . '/../storage/cache/',
            'ttl' => env('CACHE_TTL', 3600),
        ],
        
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_DB', 0),
            'ttl' => env('CACHE_TTL', 3600),
        ],
        
        'apc' => [
            'ttl' => env('CACHE_TTL', 3600),
        ],
        
        'array' => [
            'ttl' => env('CACHE_TTL', 3600),
        ],
    ],
];