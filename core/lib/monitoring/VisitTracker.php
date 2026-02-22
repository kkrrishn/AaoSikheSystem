<?php

declare(strict_types=1);

namespace AaoSikheSystem\monitoring;

use AaoSikheSystem\db\DBManager;
use AaoSikheSystem\cache\CacheManager;

/**
 * VisitTracker - Logs visits, page views, and events
 * 
 * @package AaoSikheSystem
 */
class VisitTracker
{
    private DBManager $db;
    private CacheManager $cache;
    private string $table = 'user_visits';
    private bool $enabled = true;

    public function __construct(DBManager $db, CacheManager $cache)
    {
        $this->db = $db;
        $this->cache = $cache;

        $this->ensureTablesExist();
    }

    protected function ensureTablesExist(): void
    {
        // 1️⃣ user_visits table
        $this->db->createTableIfNotExists('user_visits', [
            'visit_id'    => 'VARCHAR(255) NOT NULL',
            'session_id'  => 'VARCHAR(255) DEFAULT NULL',
            'user_id'     => 'VARCHAR(255) DEFAULT NULL',
            'ip_address'  => 'VARCHAR(255) DEFAULT NULL',
            'url'         => 'TEXT',
            'referrer'    => 'TEXT',
            'method'      => 'VARCHAR(50) DEFAULT NULL',
            'language'    => 'VARCHAR(255) DEFAULT NULL',
            'device_type' => 'VARCHAR(255) DEFAULT NULL',
            'browser'     => 'VARCHAR(255) DEFAULT NULL',
            'platform'    => 'VARCHAR(255) DEFAULT NULL',
            'country'     => 'VARCHAR(255) DEFAULT NULL',
            'city'        => 'VARCHAR(255) DEFAULT NULL',
            'latitude'    => 'DECIMAL(10,6) DEFAULT NULL',
            'longitude'   => 'DECIMAL(10,6) DEFAULT NULL',
            'visit_time'  => 'BIGINT(20) NOT NULL',
            'created_at'  => 'BIGINT(20) NOT NULL'
        ], 'visit_id', [
            'INDEX idx_user_id (user_id)',
            'INDEX idx_ip (ip_address)',
            'INDEX idx_visit_time (visit_time)'
        ]);

        // 2️⃣ visit_page_views table
        $this->db->createTableIfNotExists('visit_page_views', [
            'id'           => 'VARCHAR(255) NOT NULL',
            'visit_id'     => 'VARCHAR(255) NOT NULL',
            'page_url'     => 'TEXT',
            'page_title'   => 'VARCHAR(255) DEFAULT NULL',
            'load_time'    => 'FLOAT DEFAULT NULL',
            'scroll_depth' => 'FLOAT DEFAULT NULL',
            'time_on_page' => 'FLOAT DEFAULT NULL',
            'created_at'   => 'BIGINT(20) NOT NULL'
        ], 'id', [
            'INDEX idx_visit_id (visit_id)'
        ]);

        // 3️⃣ visit_events table
        $this->db->createTableIfNotExists('visit_events', [
            'id'          => 'VARCHAR(255) NOT NULL',
            'visit_id'    => 'VARCHAR(255) NOT NULL',
            'event_type'  => 'VARCHAR(255) NOT NULL',
            'event_data'  => 'TEXT DEFAULT NULL',
            'event_time'  => 'BIGINT(20) NOT NULL',
            'created_at'  => 'BIGINT(20) NOT NULL'
        ], 'id', [
            'INDEX idx_visit_id (visit_id)',
            'INDEX idx_event_type (event_type)'
        ]);
    }

    public function trackVisit(array $visitData = []): string
    {
        if (!$this->enabled) {
            return '';
        }

        $visitId = $this->generateVisitId();
        $timestamp = time();

        $defaultData = [
            'visit_id'    => $visitId,
            'session_id'  => session_id(),
            'user_id'     => null,
            'ip_address'  => $this->getClientIp(),
            'url'         => $this->getCurrentUrl(),
            'referrer'    => $_SERVER['HTTP_REFERER'] ?? '',
            'method'      => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'language'    => $this->getClientLanguage(),
            'device_type' => $this->getDeviceType(),
            'browser'     => $this->getBrowser(),
            'platform'    => $this->getPlatform(),
            'country'     => '',
            'city'        => '',
            'latitude'    => null,
            'longitude'   => null,
            'visit_time'  => $timestamp,
            'created_at'  => $timestamp
        ];

        $visitData = array_merge($defaultData, $visitData);

        $query = "INSERT INTO {$this->table} 
            (visit_id, session_id, user_id, ip_address, url, referrer, method, language, 
             device_type, browser, platform, country, city, latitude, longitude, visit_time, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array_values($visitData);
        $paramTypes = str_repeat('s', count($params));

        $this->db->insert($query, $paramTypes, $params);

        $this->cacheRecentVisit($visitData);
        $this->updateSessionStats($visitData['session_id']);

        return $visitId;
    }

    public function trackPageView(string $visitId, array $pageData = []): bool
    {
        $timestamp = time();

        $defaultData = [
            'id'           => $this->generateId('page_'),
            'visit_id'     => $visitId,
            'page_url'     => $this->getCurrentUrl(),
            'page_title'   => $this->getPageTitle(),
            'load_time'    => $this->getPageLoadTime(),
            'scroll_depth' => 0,
            'time_on_page' => 0,
            'created_at'   => $timestamp
        ];

        $pageData = array_merge($defaultData, $pageData);

        $query = "INSERT INTO visit_page_views 
            (id, visit_id, page_url, page_title, load_time, scroll_depth, time_on_page, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array_values($pageData);
        $paramTypes = str_repeat('s', count($params));

        try {
    $this->db->insert($query, $paramTypes, $params);
    return true;
} catch (\Exception $e) {
    return false;
}
    }

    public function trackEvent(string $visitId, string $eventType, array $eventData = []): bool
    {
        $timestamp = time();

        $event = [
            'id'         => $this->generateId('event_'),
            'visit_id'   => $visitId,
            'event_type' => $eventType,
            'event_data' => json_encode($eventData),
            'event_time' => $timestamp,
            'created_at' => $timestamp
        ];

        $query = "INSERT INTO visit_events 
            (id, visit_id, event_type, event_data, event_time, created_at)
            VALUES (?, ?, ?, ?, ?, ?)";

        $params = array_values($event);
        $paramTypes = str_repeat('s', count($params));

       try {
    $this->db->insert($query, $paramTypes, $params);
    return true;
} catch (\Exception $e) {
    return false;
}
    }

    private function generateVisitId(): string
    {
        return $this->generateId('visit_');
    }

    private function generateId(string $prefix = ''): string
    {
        return $prefix . bin2hex(random_bytes(8)) . '_' . uniqid();
    }

    private function getClientIp(): string
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function getCurrentUrl(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
            '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
    }

    private function getClientLanguage(): string
    {
        return $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';
    }

    private function getDeviceType(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/(mobile|iphone|ipod|android|blackberry)/i', $ua)) return 'mobile';
        if (preg_match('/(tablet|ipad|playbook|silk)/i', $ua)) return 'tablet';
        return 'desktop';
    }

    private function getBrowser(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return match (true) {
            str_contains($ua, 'MSIE'), str_contains($ua, 'Trident') => 'Internet Explorer',
            str_contains($ua, 'Edge') => 'Microsoft Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            str_contains($ua, 'Opera') => 'Opera',
            default => 'Unknown',
        };
    }

    private function getPlatform(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iOS') => 'iOS',
            default => 'Unknown',
        };
    }

    private function getPageTitle(): string
    {
        return $_POST['page_title'] ?? ($_SERVER['REQUEST_URI'] ?? 'Unknown');
    }

    private function getPageLoadTime(): float
    {
        return round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2);
    }

    private function cacheRecentVisit(array $visitData): void
    {
        $key = "recent_visit_{$visitData['visit_id']}";
        $this->cache->set($key, $visitData, 3600);

        $recentKey = "recent_visits";
        $recentVisits = $this->cache->get($recentKey, []);
        array_unshift($recentVisits, $visitData);
        $recentVisits = array_slice($recentVisits, 0, 50);
        $this->cache->set($recentKey, $recentVisits, 3600);
    }

    private function updateSessionStats(string $sessionId): void
    {
        $key = "session_stats_{$sessionId}";
        $stats = $this->cache->get($key, ['visit_count' => 0, 'last_visit' => null]);
        $stats['visit_count']++;
        $stats['last_visit'] = time();
        $this->cache->set($key, $stats, 86400);
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
