<?php

declare(strict_types=1);

namespace AaoSikheSystem\cache;

/**
 * File Cache - File-based cache driver
 * 
 * @package AaoSikheSystem
 */
class FileCache implements CacheInterface
{
    private string $cachePath;
    private int $defaultTtl;
    
    public function __construct(array $config = [])
    {
        $this->cachePath = $config['path'] ?? __DIR__ . '/../../storage/cache/';
        $this->defaultTtl = $config['ttl'] ?? 3600;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Check if expired
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $ttl = $ttl ?? $this->defaultTtl;
        
        $data = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'created' => time()
        ];
        
        $result = file_put_contents($file, serialize($data), LOCK_EX);
        
        return $result !== false;
    }
    
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    public function clear(): bool
    {
        $files = glob($this->cachePath . '*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
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
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Get file path for cache key
     */
    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->cachePath . $hash . '.cache';
    }
    
    /**
     * Get cache directory size in bytes
     */
    public function getCacheSize(): int
    {
        $size = 0;
        $files = glob($this->cachePath . '*.cache');
        
        foreach ($files as $file) {
            $size += filesize($file);
        }
        
        return $size;
    }
    
    /**
     * Get cache file count
     */
    public function getCacheCount(): int
    {
        $files = glob($this->cachePath . '*.cache');
        return count($files);
    }
}