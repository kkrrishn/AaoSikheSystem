<?php

declare(strict_types=1);

namespace AaoSikheSystem\cache;

/**
 * Cache Interface - Contract for all cache drivers
 * 
 * @package AaoSikheSystem
 */
interface CacheInterface
{
    /**
     * Get item from cache
     */
    public function get(string $key, $default = null);
    
    /**
     * Store item in cache
     */
    public function set(string $key, $value, ?int $ttl = null): bool;
    
    /**
     * Delete item from cache
     */
    public function delete(string $key): bool;
    
    /**
     * Clear entire cache
     */
    public function clear(): bool;
    
    /**
     * Get multiple items
     */
    public function getMultiple(array $keys, $default = null): array;
    
    /**
     * Store multiple items
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;
    
    /**
     * Delete multiple items
     */
    public function deleteMultiple(array $keys): bool;
    
    /**
     * Check if item exists
     */
    public function has(string $key): bool;
}