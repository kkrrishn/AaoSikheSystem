<?php

declare(strict_types=1);

namespace AaoSikheSystem\cache;

/**
 * Cache Helper - Utility functions for common cache operations
 * 
 * @package AaoSikheSystem
 */
class CacheHelper
{
    private CacheManager $cache;
    
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * Cache database query results
     */
    public function cacheQuery(string $key, callable $query, int $ttl = 3600): array
    {
        return $this->cache->remember($key, $ttl, $query);
    }
    
    /**
     * Cache API response
     */
    public function cacheApiCall(string $key, callable $apiCall, int $ttl = 300)
    {
        return $this->cache->remember($key, $ttl, $apiCall);
    }
    
    /**
     * Cache configuration
     */
    public function cacheConfig(string $key, callable $configLoader, int $ttl = 86400): array
    {
        return $this->cache->remember($key, $ttl, $configLoader);
    }
    
    /**
     * Cache template/views
     */
    public function cacheView(string $key, callable $viewRenderer, int $ttl = 3600): string
    {
        return $this->cache->remember($key, $ttl, $viewRenderer);
    }
    
    /**
     * Cache with tags (simulated)
     */
    public function taggedCache(string $tag, string $key, callable $callback, int $ttl = 3600)
    {
        $fullKey = "tag_{$tag}_{$key}";
        return $this->cache->remember($fullKey, $ttl, $callback);
    }
    
    /**
     * Clear cache by tag
     */
    public function clearTag(string $tag): bool
    {
        // This is a simplified implementation
        // In production, you might want to maintain a tag index
        $pattern = "tag_{$tag}_*";
        return $this->cache->clear(); // Simplified - clear all for demo
    }
    
    /**
     * Rate limiting using cache
     */
    public function rateLimit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $attempts = $this->cache->get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        $this->cache->set($key, $attempts + 1, $decaySeconds);
        return true;
    }
    
    /**
     * Cache locking mechanism
     */
    public function lock(string $key, int $timeout = 10): bool
    {
        $lockKey = "lock_{$key}";
        
        for ($i = 0; $i < $timeout; $i++) {
            if ($this->cache->set($lockKey, true, 60)) {
                return true;
            }
            sleep(1);
        }
        
        return false;
    }
    
    /**
     * Release cache lock
     */
    public function unlock(string $key): bool
    {
        $lockKey = "lock_{$key}";
        return $this->cache->delete($lockKey);
    }
}