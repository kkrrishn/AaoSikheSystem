<?php

declare(strict_types=1);

namespace AaoSikheSystem\monitoring;

use AaoSikheSystem\cache\CacheManager;
use AaoSikheSystem\db\DBManager;

/**
 * Real-time Monitor - Provides real-time monitoring capabilities
 * 
 * @package AaoSikheSystem
 */
class RealTimeMonitor
{
    private CacheManager $cache;
    private VisitAnalyzer $analyzer;
    private DBManager $db;
    
    public function __construct(CacheManager $cache, VisitAnalyzer $analyzer)
    {
        $this->cache = $cache;
        $this->analyzer = $analyzer;
        $this->db = DBManager::getInstance();
         $this->ensureTablesExist();
    }

 protected function ensureTablesExist(): void
    {
        $db = $this->db;

        // 1️⃣ user_visits table
        $db->createTableIfNotExists(
            'user_visits',
            [
                'id'          => 'VARCHAR(255) NOT NULL',
                'session_id'  => 'VARCHAR(255) NOT NULL',
                'visit_id'    => 'VARCHAR(255) DEFAULT NULL',
                'ip_address'  => 'VARCHAR(255) DEFAULT NULL',
                'user_agent'  => 'VARCHAR(255) DEFAULT NULL',
                'device_type' => 'VARCHAR(255) DEFAULT NULL',
                'browser'     => 'VARCHAR(255) DEFAULT NULL',
                'country'     => 'VARCHAR(255) DEFAULT NULL',
                'city'        => 'VARCHAR(255) DEFAULT NULL',
                'visit_time'  => 'BIGINT(20) NOT NULL',
            ],
            'id',
            [
                'INDEX idx_session_id (session_id)',
                'INDEX idx_ip (ip_address)',
                'INDEX idx_country (country)',
                'INDEX idx_city (city)',
                'INDEX idx_visit_time (visit_time)',
            ]
        );

        // 2️⃣ visit_page_views table
        $db->createTableIfNotExists(
            'visit_page_views',
            [
                'id'           => 'VARCHAR(255) NOT NULL',
                'visit_id'     => 'VARCHAR(255) NOT NULL',
                'page_url'     => 'TEXT NOT NULL',
                'page_title'   => 'VARCHAR(255) DEFAULT NULL',
                'load_time'    => 'FLOAT DEFAULT 0',
                'scroll_depth' => 'INT DEFAULT 0',
                'time_on_page' => 'INT DEFAULT 0',
                'created_at'   => 'BIGINT(20) NOT NULL',
            ],
            'id',
            [
                'INDEX idx_visit_id (visit_id)',
                'INDEX idx_created_at (created_at)',
            ]
        );
    }

    
    /**
     * Get real-time active visitors
     */
    public function getActiveVisitors(): array
    {
        $activeVisitors = [];
        $timeThreshold =strtotime('-5 minutes');
        
        $query = "SELECT 
                    uv.session_id,
                    uv.ip_address,
                    uv.user_agent,
                    uv.device_type,
                    uv.browser,
                    uv.country,
                    uv.city,
                    uv.visit_time,
                    vpv.page_url,
                    vpv.page_title
                  FROM user_visits uv
                  LEFT JOIN visit_page_views vpv ON uv.visit_id = vpv.visit_id
                  WHERE uv.visit_time > ?
                  ORDER BY uv.visit_time DESC
                  LIMIT 100";
        
        $recentVisits = $this->db->select($query, 'i',[$timeThreshold]);
        
        foreach ($recentVisits as $visit) {
            $sessionId = $visit['session_id'];
            if (!isset($activeVisitors[$sessionId])) {
                $activeVisitors[$sessionId] = [
                    'session_id' => $sessionId,
                    'ip_address' => $visit['ip_address'],
                    'device_type' => $visit['device_type'],
                    'browser' => $visit['browser'],
                    'country' => $visit['country'],
                    'city' => $visit['city'],
                    'first_seen' => $visit['visit_time'],
                    'last_seen' => $visit['visit_time'],
                    'page_views' => [],
                    'current_page' => $visit['page_url']
                ];
            }
            
            $activeVisitors[$sessionId]['last_seen'] = $visit['visit_time'];
            $activeVisitors[$sessionId]['current_page'] = $visit['page_url'];
            
            if ($visit['page_url']) {
                $activeVisitors[$sessionId]['page_views'][] = [
                    'url' => $visit['page_url'],
                    'title' => $visit['page_title'],
                    'time' => $visit['visit_time']
                ];
            }
        }
        
        return array_values($activeVisitors);
    }
    
    /**
     * Get real-time visit statistics
     */
    public function getRealtimeStats(): array
    {
        $now = time();
        $intervals = [
            'last_5_minutes' => $now - 300,
            'last_15_minutes' => $now - 900,
            'last_hour' => $now - 3600,
            'last_3_hours' => $now - 10800
        ];
        
        $stats = [];
        
        foreach ($intervals as $label => $timestamp) {
            $time = date('Y-m-d H:i:s', $timestamp);
            $stats[$label] = $this->analyzer->getVisitStats($time, date('Y-m-d H:i:s'));
        }
        
        return $stats;
    }
    
    /**
     * Get geographical heatmap data
     */
    public function getGeographicalHeatmap(string $startDate, string $endDate): array
    {
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $query = "SELECT 
                    country,
                    city,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors
                  FROM user_visits 
                  WHERE visit_time BETWEEN ? AND ? AND country != '' AND city != ''
                  GROUP BY country, city
                  ORDER BY visits DESC";
        
        return $this->db->select($query, 'ii',[$startDate, $endDate]);
    }
    
    /**
     * Track live event (WebSocket compatible)
     */
    public function trackLiveEvent(string $eventType, array $eventData): void
    {
        $event = [
            'type' => $eventType,
            'data' => $eventData,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s')
        ];
        
        $key = "live_event_" . uniqid();
        $this->cache->set($key, $event, 300); // Store for 5 minutes
        
        // Also add to live events stream
        $streamKey = "live_events_stream";
        $events = $this->cache->get($streamKey, []);
        array_unshift($events, $event);
        $events = array_slice($events, 0, 100); // Keep last 100 events
        $this->cache->set($streamKey, $events, 300);
    }
    
    /**
     * Get live events stream
     */
    public function getLiveEvents(int $limit = 50): array
    {
        $streamKey = "live_events_stream";
        $events = $this->cache->get($streamKey, []);
        return array_slice($events, 0, $limit);
    }
    
    /**
     * Monitor system health
     */
    public function getSystemHealth(): array
    {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'last_update' => date('Y-m-d H:i:s')
        ];
        
        return $health;
    }
    
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            $this->db->select("SELECT 1");
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Database is responding normally'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time_ms' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function checkCacheHealth(): array
    {
        try {
            $start = microtime(true);
            $this->cache->set('health_check', 'ok', 60);
            $value = $this->cache->get('health_check');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => $value === 'ok' ? 'healthy' : 'unhealthy',
                'response_time_ms' => $responseTime,
                'message' => $value === 'ok' ? 'Cache is working normally' : 'Cache value mismatch'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time_ms' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function checkStorageHealth(): array
    {
        $storagePath = __DIR__ . '/../../storage/';
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        $usagePercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
        
        return [
            'status' => $usagePercent < 90 ? 'healthy' : 'warning',
            'usage_percent' => $usagePercent,
            'free_space_gb' => round($freeSpace / (1024 * 1024 * 1024), 2),
            'total_space_gb' => round($totalSpace / (1024 * 1024 * 1024), 2),
            'message' => $usagePercent < 90 ? 'Storage is adequate' : 'Storage space is running low'
        ];
    }
    /**
 * Static tracking helper (Facade style)
 */
public static function track(string $eventType, $data = null): void
{
    try {

        $db = \AaoSikheSystem\db\DBManager::getInstance();

        $cache = new \AaoSikheSystem\cache\CacheManager();
        $analyzer = new \AaoSikheSystem\monitoring\VisitAnalyzer($db);

        $instance = new self($cache, $analyzer);

        $payload = [
            'user_id'    => is_scalar($data) ? $data : null,
            'meta'       => is_array($data) ? $data : [],
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'timestamp'  => time(),
        ];

        $instance->trackLiveEvent($eventType, $payload);

    } catch (\Throwable $e) {
        error_log("RealTimeMonitor track failed: " . $e->getMessage());
    }
}

}