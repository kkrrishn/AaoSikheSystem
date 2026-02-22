<?php
namespace AaoSikheSystem\lib\rate_limit;

use Predis\Client as RedisClient;

/**
 * AdvancedRateLimiter:
 * - Use Redis counters if available for atomic increments.
 * - Fallback to filesystem counters.
 * - Supports progressive delays for login attempts (exponential backoff).
 */
class AdvancedRateLimiter
{
    protected ?RedisClient $redis = null;
    protected string $prefix;
    protected int $limit;
    protected int $window; // seconds
    protected string $fallbackDir;

    public function __construct(array $opts = [])
    {
        $this->prefix = $opts['prefix'] ?? 'rl:';
        $this->limit = $opts['limit'] ?? 100;
        $this->window = $opts['window'] ?? 60;
        $this->fallbackDir = sys_get_temp_dir() . '/aao_rl';
        if (!is_dir($this->fallbackDir)) mkdir($this->fallbackDir, 0755, true);

        if (!empty($opts['redis'])) {
            try {
                $this->redis = new RedisClient($opts['redis']);
                $this->redis->connect();
            } catch (\Throwable $e) {
                $this->redis = null;
            }
        }
    }

    protected function key(string $id, string $action): string
    {
        return $this->prefix . md5($id . '|' . $action);
    }

    public function allow(string $id, string $action = 'global'): bool
    {
        $key = $this->key($id, $action);
        if ($this->redis) {
            $ttl = $this->window;
            $count = $this->redis->incr($key);
            if ($count === 1) $this->redis->expire($key, $ttl);
            return $count <= $this->limit;
        } else {
            $file = "{$this->fallbackDir}/" . sha1($key) . '.json';
            $now = time();
            $data = (file_exists($file)) ? json_decode(file_get_contents($file), true) : ['ts' => $now, 'count' => 0];
            if ($now - $data['ts'] > $this->window) {
                $data = ['ts' => $now, 'count' => 0];
            }
            $data['count']++;
            file_put_contents($file, json_encode($data));
            return $data['count'] <= $this->limit;
        }
    }

    /**
     * Progressive delay to use for login/OTP attempts.
     * returns seconds to delay.
     */
    public function progressiveDelay(string $id, int $failures): int
    {
        // e.g. 0, 1, 2, 4, 8 seconds...
        if ($failures <= 1) return 0;
        $delay = (int) pow(2, min(10, $failures - 1));
        return min($delay, 3600); // cap 1 hour
    }
}
