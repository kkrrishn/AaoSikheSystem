<?php

declare(strict_types=1);

namespace AaoSikheSystem\cache;

use AaoSikheSystem\helper\FeatureManager;
/**
 * Cache Manager - Main cache operations handler
 * 
 * @package AaoSikheSystem
 */
class CacheManager
{
    private static ?CacheManager $instance = null;
    private array $drivers = [];
    private string $defaultDriver = 'file';
    private array $config;
    
    private function __construct(array $config = [])
    {
  
        $this->config = array_merge([
            'default' => 'file',
            'prefix' => 'aao_sikhe_',
            'drivers' => [
                'file' => [
                    'path' => __DIR__ . '/../../storage/cache/',
                    'ttl' => 3600
                ],
                'redis' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'password' => null,
                    'database' => 0,
                    'ttl' => 3600
                ],
                'apc' => [
                    'ttl' => 3600
                ],
                'array' => [
                    'ttl' => 3600
                ]
            ]
        ], $config);
        
        $this->defaultDriver = $this->config['default'];
    }
    
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        
        return self::$instance;
    }
    
    /**
     * Get cache driver instance
     */
    public function driver(?string $driver = null): CacheInterface
    {
        $driver = $driver ?: $this->defaultDriver;
        
        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }
        
        return $this->drivers[$driver];
    }
    
    /**
     * Create cache driver instance
     */
    private function createDriver(string $driver): CacheInterface
    {
        $driverConfig = $this->config['drivers'][$driver] ?? [];
        
        return match ($driver) {
            'file' => new FileCache($driverConfig),
            'redis' => new RedisCache($driverConfig),
            'apc' => new ApcCache($driverConfig),
            'array' => new ArrayCache($driverConfig),
            default => throw new \InvalidArgumentException("Unsupported cache driver: $driver")
        };
    }
    
    /**
     * Store item in cache
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!FeatureManager::isEnabled('cache')) {
            return false;
        }
         

        return $this->driver()->set($this->prefixKey($key), $value, $ttl);
    }
    
    /**
     * Retrieve item from cache
     */
    public function get(string $key, $default = null)
    {
          if (!FeatureManager::isEnabled('cache')) {
            return $default ;
        }

        return $this->driver()->get($this->prefixKey($key), $default);
    }
    
    /**
     * Check if item exists in cache
     */
    public function has(string $key): bool
    {
          if (!FeatureManager::isEnabled('cache')) {
            return false;
        }

        return $this->driver()->has($this->prefixKey($key));
    }
    
    /**
     * Remove item from cache
     */
    public function delete(string $key): bool
    {
          if (!FeatureManager::isEnabled('cache')) {
            return false;
        }

        return $this->driver()->delete($this->prefixKey($key));
    }
    
    /**
     * Clear entire cache
     */
    public function clear(): bool
    {
         if (!FeatureManager::isEnabled('cache')) {
            return false;
        }

        return $this->driver()->clear();
    }
    
    /**
     * Get multiple items
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $prefixedKeys = array_map(fn($key) => $this->prefixKey($key), $keys);
        return $this->driver()->getMultiple($prefixedKeys, $default);
    }
    
    /**
     * Store multiple items
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefixKey($key)] = $value;
        }
        
        return $this->driver()->setMultiple($prefixedValues, $ttl);
    }
    
    /**
     * Delete multiple items
     */
    public function deleteMultiple(array $keys): bool
    {
        $prefixedKeys = array_map(fn($key) => $this->prefixKey($key), $keys);
        return $this->driver()->deleteMultiple($prefixedKeys);
    }
    
    /**
     * Add key prefix
     */
    private function prefixKey(string $key): string
    {
        return $this->config['prefix'] . $key;
    }
    
    /**
     * Remember value (get or set if not exists)
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Remember value forever
     */
    public function rememberForever(string $key, callable $callback)
    {
        return $this->remember($key, 31536000, $callback); // 1 year
    }
    
    /**
     * Get all available drivers
     */
    public function getDrivers(): array
    {
        return array_keys($this->config['drivers']);
    }
}