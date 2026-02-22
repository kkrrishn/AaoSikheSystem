<?php

declare(strict_types=1);

namespace AaoSikheSystem\rate_limit;

use AaoSikheSystem\cache\CacheManager;
use AaoSikheSystem\helper\FeatureManager;

/**
 * Rate Limiter - Implements various rate limiting algorithms
 * 
 * @package AaoSikheSystem
 */
class RateLimiter
{
    private CacheManager $cache;
    private string $prefix = 'rate_limit_';
    private static ?self $instance = null;
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }
    private function isEnabled(): bool
    {
        try {
            if (!class_exists(FeatureManager::class)) {
                return false;
            }
            return FeatureManager::isEnabled('rate_limit');
        } catch (\Throwable $e) {
            // avoid any recursion / fatal loops
            return false;
        }
    }
    public static function getInstance(): self
{
    if (!self::$instance) {
        self::$instance = new self(new CacheManager());
    }
    return self::$instance;
}
public static function hit(string $ip): bool
{
    return self::getInstance()->attempt(
        'auth_fail_' . $ip,
        5,      // 5 attempts
        300     // 5 minutes
    );
}

    /**
     * Token Bucket Algorithm - Flexible rate limiting
     */
    public function tokenBucket(
        string $key, 
        int $maxTokens, 
        int $refillRate, // tokens per second
        int $refillInterval = 1, // seconds
        int $cost = 1
    ): bool {

        if (!$this->isEnabled()) {
            // When disabled by FeatureManager, behave as allowed or blocked per your policy.
            // I return false so caller knows rate limiting is not passed (you may change to true).
            return false;
        }
        $cacheKey = $this->prefix . 'bucket_' . md5($key);
        
        $bucket = $this->cache->get($cacheKey, [
            'tokens' => $maxTokens,
            'last_refill' => time()
        ]);
        
        $now = time();
        $timePassed = $now - $bucket['last_refill'];
        
        // Refill tokens based on time passed
        if ($timePassed > 0) {
            $tokensToAdd = ($timePassed * $refillRate) / $refillInterval;
            $bucket['tokens'] = min($maxTokens, $bucket['tokens'] + $tokensToAdd);
            $bucket['last_refill'] = $now;
        }
        
        // Check if enough tokens available
        if ($bucket['tokens'] >= $cost) {
            $bucket['tokens'] -= $cost;
            $this->cache->set($cacheKey, $bucket, 3600);
            return true;
        }
        
        $this->cache->set($cacheKey, $bucket, 3600);
        return false;
    }
    
    /**
     * Fixed Window Algorithm - Simple rate limiting
     */
    public function fixedWindow(
        string $key, 
        int $maxRequests, 
        int $windowSize // seconds
    ): bool {
        $cacheKey = $this->prefix . 'fixed_' . md5($key);
        $window = floor(time() / $windowSize);
        $windowKey = $cacheKey . '_' . $window;
        
        $current = $this->cache->get($windowKey, 0);
        
        if ($current >= $maxRequests) {
            return false;
        }
        
        $this->cache->set($windowKey, $current + 1, $windowSize + 1);
        return true;
    }
    
    /**
     * Sliding Window Algorithm - More accurate rate limiting
     */
    public function slidingWindow(
        string $key, 
        int $maxRequests, 
        int $windowSize // seconds
    ): bool {
         if (!$this->isEnabled()) {
            return true; // Allow all if disabled
        }
        $cacheKey = $this->prefix . 'sliding_' . md5($key);
        $now = time();
        $windowStart = $now - $windowSize;
        
        // Get existing requests in current window
        $requests = $this->cache->get($cacheKey, []);
        
        // Remove expired requests
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check if under limit
        if (count($requests) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        $requests[] = $now;
        $this->cache->set($cacheKey, $requests, $windowSize + 1);
        
        return true;
    }
    
    /**
     * Leaky Bucket Algorithm - Smooth rate limiting
     */
    public function leakyBucket(
        string $key, 
        int $capacity, 
        int $leakRate // requests per second
    ): bool {
        $cacheKey = $this->prefix . 'leaky_' . md5($key);
        
        $bucket = $this->cache->get($cacheKey, [
            'water' => 0,
            'last_leak' => time()
        ]);
        
        $now = time();
        $timePassed = $now - $bucket['last_leak'];
        
        // Leak water based on time passed
        if ($timePassed > 0) {
            $leakAmount = $timePassed * $leakRate;
            $bucket['water'] = max(0, $bucket['water'] - $leakAmount);
            $bucket['last_leak'] = $now;
        }
        
        // Check if bucket has capacity
        if ($bucket['water'] < $capacity) {
            $bucket['water'] += 1;
            $this->cache->set($cacheKey, $bucket, 3600);
            return true;
        }
        
        $this->cache->set($cacheKey, $bucket, 3600);
        return false;
    }
    
    /**
     * Generic rate limit check with multiple algorithms support
     */
    public function attempt(
        string $key, 
        int $maxAttempts, 
        int $decaySeconds, 
        string $algorithm = 'sliding'
    ): bool {
         if (!$this->isEnabled()) {
        return false;
    }

        return match ($algorithm) {
            'token_bucket' => $this->tokenBucket($key, $maxAttempts, $maxAttempts / $decaySeconds, $decaySeconds),
            'fixed_window' => $this->fixedWindow($key, $maxAttempts, $decaySeconds),
            'sliding_window' => $this->slidingWindow($key, $maxAttempts, $decaySeconds),
            'leaky_bucket' => $this->leakyBucket($key, $maxAttempts, $maxAttempts / $decaySeconds),
            default => $this->slidingWindow($key, $maxAttempts, $decaySeconds)
        };
    }
    
    /**
     * Check remaining attempts
     */
    public function remaining(string $key, int $maxAttempts, int $decaySeconds): int
    {
        $cacheKey = $this->prefix . 'sliding_' . md5($key);
        $now = time();
        $windowStart = $now - $decaySeconds;
        
        $requests = $this->cache->get($cacheKey, []);
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $maxAttempts - count($requests));
    }
    
    /**
     * Get retry after seconds
     */
    public function availableIn(string $key, int $decaySeconds): int
    {
        $cacheKey = $this->prefix . 'sliding_' . md5($key);
        $now = time();
        $windowStart = $now - $decaySeconds;
        
        $requests = $this->cache->get($cacheKey, []);
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        if (empty($requests)) {
            return 0;
        }
        
        $oldestRequest = min($requests);
        return max(0, ($oldestRequest + $decaySeconds) - $now);
    }
    
    /**
     * Clear rate limits for a key
     */
    public function clear(string $key): bool
    {
        $patterns = [
            $this->prefix . 'sliding_' . md5($key),
            $this->prefix . 'fixed_' . md5($key),
            $this->prefix . 'token_' . md5($key),
            $this->prefix . 'leaky_' . md5($key),
        ];
        
        $success = true;
        foreach ($patterns as $pattern) {
            if (!$this->cache->delete($pattern)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get rate limit headers for HTTP responses
     */
    public function getHeaders(
        string $key, 
        int $maxAttempts, 
        int $decaySeconds
    ): array {
        $remaining = $this->remaining($key, $maxAttempts, $decaySeconds);
        $retryAfter = $this->availableIn($key, $decaySeconds);
        
        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => time() + $retryAfter,
            'Retry-After' => $retryAfter
        ];
    }
    
    /**
     * Multi-dimensional rate limiting (IP + User ID + Action)
     */
    public function multiDimensionalAttempt(
        array $dimensions, // ['ip' => '127.0.0.1', 'user_id' => 123, 'action' => 'login']
        int $maxAttempts, 
        int $decaySeconds
    ): bool {
        $key = $this->buildMultiDimensionalKey($dimensions);
        return $this->attempt($key, $maxAttempts, $decaySeconds);
    }
    
    /**
     * Build key from multiple dimensions
     */
    private function buildMultiDimensionalKey(array $dimensions): string
    {
        ksort($dimensions); // Ensure consistent ordering
        return 'multi_' . md5(implode('|', $dimensions));
    }
    
    /**
     * Global rate limiting across all users
     */
    public function globalLimit(
        string $action, 
        int $maxRequests, 
        int $windowSize
    ): bool {
        $key = 'global_' . $action;
        return $this->fixedWindow($key, $maxRequests, $windowSize);
    }
    
    /**
     * Burst protection - allow bursts but maintain overall rate
     */
    public function burstProtection(
        string $key, 
        int $burstLimit, // Maximum burst allowed
        int $sustainedLimit, // Sustained rate per minute
        int $windowSize = 60
    ): bool {
        $burstKey = $this->prefix . 'burst_' . md5($key);
        $sustainedKey = $this->prefix . 'sustained_' . md5($key);
        
        // Check burst limit (short window)
        $burstWindow = 10; // 10 seconds for burst detection
        if (!$this->fixedWindow($burstKey, $burstLimit, $burstWindow)) {
            return false;
        }
        
        // Check sustained limit (longer window)
        return $this->fixedWindow($sustainedKey, $sustainedLimit, $windowSize);
    }
    
    /**
     * Adaptive rate limiting based on system load
     */
    public function adaptiveLimit(
        string $key, 
        int $baseLimit, 
        int $windowSize,
        float $systemLoadThreshold = 0.8
    ): bool {
        // Get system load
        $systemLoad = $this->getSystemLoad();
        
        // Adjust limit based on system load
        $adjustedLimit = $baseLimit;
        if ($systemLoad > $systemLoadThreshold) {
            // Reduce limit under high load
            $reductionFactor = 1 - (($systemLoad - $systemLoadThreshold) / (1 - $systemLoadThreshold));
            $adjustedLimit = (int) max(1, $baseLimit * $reductionFactor);
        }
        
        return $this->fixedWindow($key, $adjustedLimit, $windowSize);
    }
    
    /**
     * Get current system load (Linux/Unix)
     */
    public function getSystemLoad(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] ?? 0.0; // 1-minute load average
        }
        
        // Fallback for Windows or other systems
        return 0.0;
    }
    
    /**
     * Rate limiting with gradual backoff
     */
    public function withBackoff(
        string $key, 
        int $maxAttempts, 
        int $baseDecay, 
        float $backoffFactor = 2.0
    ): bool {
        $cacheKey = $this->prefix . 'backoff_' . md5($key);
        
        $backoffData = $this->cache->get($cacheKey, [
            'attempts' => 0,
            'next_reset' => time() + $baseDecay
        ]);
        
        $now = time();
        
        // Reset if backoff period has passed
        if ($now >= $backoffData['next_reset']) {
            $backoffData = [
                'attempts' => 0,
                'next_reset' => $now + $baseDecay
            ];
        }
        
        // Check if allowed
        if ($backoffData['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts and update backoff
        $backoffData['attempts']++;
        $currentDecay = (int) ($baseDecay * pow($backoffFactor, $backoffData['attempts'] - 1));
        $backoffData['next_reset'] = $now + $currentDecay;
        
        $this->cache->set($cacheKey, $backoffData, $currentDecay + 1);
        return true;
    }
    
    /**
     * Get rate limit statistics
     */
    public function getStats(string $key, int $maxAttempts, int $decaySeconds): array
    {
        return [
            'key' => $key,
            'max_attempts' => $maxAttempts,
            'decay_seconds' => $decaySeconds,
            'remaining' => $this->remaining($key, $maxAttempts, $decaySeconds),
            'available_in' => $this->availableIn($key, $decaySeconds),
            'retry_after' => $this->availableIn($key, $decaySeconds),
            'reset_time' => date('Y-m-d H:i:s', time() + $this->availableIn($key, $decaySeconds))
        ];
    }
    
    /**
     * Check if a key is currently limited
     */
    public function limited(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        return $this->remaining($key, $maxAttempts, $decaySeconds) === 0;
    }
    
    /**
     * Get all rate limit keys (for admin purposes)
     */
    public function getAllKeys(): array
    {
        // Note: This is a simplified implementation
        // In production, you might want to use Redis SCAN or maintain a separate index
        return [
            'info' => 'Use cache-specific methods to list keys in production'
        ];
    }
    
    /**
     * Reset all rate limits (use with caution)
     */
    public function resetAll(): bool
    {
        // Note: This is a simplified implementation
        // In production, you would use cache-specific flush methods
        return $this->cache->clear();
    }
    
    /**
     * Middleware-style rate limiting for HTTP requests
     */
    public function forHttpRequest(
        string $identifier, 
        int $maxRequests, 
        int $windowSeconds,
        array $headers = []
    ): array {
        $allowed = $this->attempt($identifier, $maxRequests, $windowSeconds);
        $responseHeaders = $this->getHeaders($identifier, $maxRequests, $windowSeconds);
        
        return [
            'allowed' => $allowed,
            'headers' => array_merge($headers, $responseHeaders),
            'remaining' => $this->remaining($identifier, $maxRequests, $windowSeconds),
            'retry_after' => $this->availableIn($identifier, $windowSeconds)
        ];
    }
}