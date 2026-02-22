<?php

declare(strict_types=1);

namespace AaoSikheSystem\cache;

/**
 * Array Cache - In-memory array cache driver
 * 
 * @package AaoSikheSystem
 */
class ArrayCache implements CacheInterface
{
    private array $storage = [];
    private int $defaultTtl;
    
    public function __construct(array $config = [])
    {
        $this->defaultTtl = $config['ttl'] ?? 3600;
    }
    
    public function get(string $key, $default = null)
    {
        if (!isset($this->storage[$key])) {
            return $default;
        }
        
        $item = $this->storage[$key];
        
        // Check if expired
        if ($item['expires'] > 0 && $item['expires'] < time()) {
            unset($this->storage[$key]);
            return $default;
        }
        
        return $item['value'];
    }
    
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        $this->storage[$key] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'created' => time()
        ];
        
        return true;
    }
    
    public function delete(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }
    
    public function clear(): bool
    {
        $this->storage = [];
        return true;
    }
    
    public function getMultiple(array $keys, $default = null): array
    {
        $results = [];
        
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }
        
        return $results;
    }
    
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        
        return true;
    }
    
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        
        return true;
    }
    
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Get all stored keys
     */
    public function getAllKeys(): array
    {
        return array_keys($this->storage);
    }
    
    /**
     * Get storage count
     */
    public function getCount(): int
    {
        return count($this->storage);
    }
}