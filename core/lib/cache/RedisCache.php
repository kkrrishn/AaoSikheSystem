<?php

declare(strict_types=1);

namespace AaoSikheSystem\cache;

/**
 * Redis Cache - Redis cache driver
 * 
 * @package AaoSikheSystem
 */
class RedisCache implements CacheInterface
{
    private ?\Redis $redis;
    private array $config;
    private int $defaultTtl;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'timeout' => 2.5,
            'ttl' => 3600
        ], $config);
        
        $this->defaultTtl = $this->config['ttl'];
        $this->connect();
    }
    
    /**
     * Connect to Redis server
     */
    private function connect(): void
    {
        $this->redis = new \Redis();
        
        try {
            $connected = $this->redis->connect(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout']
            );
            
            if (!$connected) {
                throw new \RuntimeException('Could not connect to Redis server');
            }
            
            if ($this->config['password'] !== null) {
                $this->redis->auth($this->config['password']);
            }
            
            $this->redis->select($this->config['database']);
            
        } catch (\RedisException $e) {
            throw new \RuntimeException('Redis connection failed: ' . $e->getMessage());
        }
    }
    
    public function get(string $key, $default = null)
    {
        try {
            $value = $this->redis->get($key);
            
            if ($value === false) {
                return $default;
            }
            
            return unserialize($value);
        } catch (\RedisException $e) {
            return $default;
        }
    }
    
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            $serialized = serialize($value);
            
            if ($ttl > 0) {
                return $this->redis->setex($key, $ttl, $serialized);
            } else {
                return $this->redis->set($key, $serialized);
            }
        } catch (\RedisException $e) {
            return false;
        }
    }
    
    public function delete(string $key): bool
    {
        try {
            return $this->redis->del($key) > 0;
        } catch (\RedisException $e) {
            return false;
        }
    }
    
    public function clear(): bool
    {
        try {
            return $this->redis->flushDB();
        } catch (\RedisException $e) {
            return false;
        }
    }
    
    public function getMultiple(array $keys, $default = null): array
    {
        try {
            $values = $this->redis->mget($keys);
            $results = [];
            
            foreach ($values as $index => $value) {
                $key = $keys[$index];
                $results[$key] = $value === false ? $default : unserialize($value);
            }
            
            return $results;
        } catch (\RedisException $e) {
            return array_fill_keys($keys, $default);
        }
    }
    
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        try {
            $this->redis->multi();
            
            foreach ($values as $key => $value) {
                $this->set($key, $value, $ttl);
            }
            
            $this->redis->exec();
            return true;
        } catch (\RedisException $e) {
            $this->redis->discard();
            return false;
        }
    }
    
    public function deleteMultiple(array $keys): bool
    {
        try {
            return $this->redis->del($keys) > 0;
        } catch (\RedisException $e) {
            return false;
        }
    }
    
    public function has(string $key): bool
    {
        try {
            return $this->redis->exists($key);
        } catch (\RedisException $e) {
            return false;
        }
    }
    
    /**
     * Get Redis instance
     */
    public function getRedis(): \Redis
    {
        return $this->redis;
    }
    
    public function __destruct()
    {
        if ($this->redis) {
            $this->redis->close();
        }
    }
}