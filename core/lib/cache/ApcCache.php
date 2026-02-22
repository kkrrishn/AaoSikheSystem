<?php

declare(strict_types=1);

namespace AaoSikheSystem\cache;

/**
 * APC Cache - APC/uOpcode cache driver
 * 
 * @package AaoSikheSystem
 */
class ApcCache implements CacheInterface
{
    private int $defaultTtl;
    
    public function __construct(array $config = [])
    {
        $this->defaultTtl = $config['ttl'] ?? 3600;
        
        if (!extension_loaded('apc') && !extension_loaded('apcu')) {
            throw new \RuntimeException('APC or APCu extension is required for ApcCache');
        }
    }
    
    public function get(string $key, $default = null)
    {
        $success = false;
        $value = apc_fetch($key, $success);
        
        return $success ? $value : $default;
    }
    
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        return apc_store($key, $value, $ttl);
    }
    
    public function delete(string $key): bool
    {
        return apc_delete($key);
    }
    
    public function clear(): bool
    {
        return apc_clear_cache() && apc_clear_cache('user');
    }
    
    public function getMultiple(array $keys, $default = null): array
    {
        $values = apc_fetch($keys, $success);
        
        if (!$success) {
            return array_fill_keys($keys, $default);
        }
        
        // Fill missing keys with default value
        foreach ($keys as $key) {
            if (!array_key_exists($key, $values)) {
                $values[$key] = $default;
            }
        }
        
        return $values;
    }
    
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!apc_store($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        
        foreach ($keys as $key) {
            if (!apc_delete($key)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    public function has(string $key): bool
    {
        return apc_exists($key);
    }
    
    /**
     * Get APC cache info
     */
    public function getInfo(): array
    {
        return apc_cache_info('user', true) ?: [];
    }
    
    /**
     * Check if APC is enabled
     */
    public static function isEnabled(): bool
    {
        return extension_loaded('apc') || extension_loaded('apcu');
    }
}