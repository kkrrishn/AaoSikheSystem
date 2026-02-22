<?php

declare(strict_types=1);

namespace AaoSikheSystem\monitoring;

use AaoSikheSystem\db\DBManager;

/**
 * Visit Analyzer - Analyzes and reports on user visits
 * 
 * @package AaoSikheSystem
 */
class VisitAnalyzer
{
    private DBManager $db;
    
    public function __construct(DBManager $db)
    {
        $this->db = $db;
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
            'visit_id'    => 'VARCHAR(255) NOT NULL',
            'session_id'  => 'VARCHAR(255) NOT NULL',
            'ip_address'  => 'VARCHAR(255) DEFAULT NULL',
            'user_agent'  => 'VARCHAR(255) DEFAULT NULL',
            'device_type' => 'VARCHAR(255) DEFAULT NULL',
            'browser'     => 'VARCHAR(255) DEFAULT NULL',
            'platform'    => 'VARCHAR(255) DEFAULT NULL',
            'country'     => 'VARCHAR(255) DEFAULT NULL',
            'city'        => 'VARCHAR(255) DEFAULT NULL',
            'referrer'    => 'TEXT DEFAULT NULL',
            'url'         => 'TEXT DEFAULT NULL',
            'visit_time'  => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'created_at'  => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        ],
        'id',
        [
            'INDEX idx_visit_id (visit_id)',
            'INDEX idx_session_id (session_id)',
            'INDEX idx_ip (ip_address)',
            'INDEX idx_country (country)',
            'INDEX idx_city (city)',
            'INDEX idx_visit_time (visit_time)'
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
            'scroll_depth' => 'FLOAT DEFAULT 0',
            'time_on_page' => 'FLOAT DEFAULT 0',
            'created_at'   => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        ],
        'id',
        [
            'INDEX idx_visit_id (visit_id)',
            'INDEX idx_created_at (created_at)'
        ]
    );

    // 3️⃣ visit_events table
    $db->createTableIfNotExists(
        'visit_events',
        [
            'id'         => 'VARCHAR(255) NOT NULL',
            'visit_id'   => 'VARCHAR(255) NOT NULL',
            'event_type' => 'VARCHAR(255) NOT NULL',
            'event_data' => 'JSON DEFAULT NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        ],
        'id',
        [
            'INDEX idx_visit_id (visit_id)',
            'INDEX idx_event_type (event_type)',
            'INDEX idx_created_at (created_at)'
        ]
    );
}

    /**
     * Get visit statistics for a time period
     */
    public function getVisitStats(string $startDate, string $endDate): array
    {
        $query = "SELECT 
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    AVG(TIMESTAMPDIFF(SECOND, visit_time, created_at)) as avg_session_duration,
                    MAX(TIMESTAMPDIFF(SECOND, visit_time, created_at)) as max_session_duration,
                    COUNT(*) / COUNT(DISTINCT ip_address) as visits_per_visitor
                  FROM user_visits 
                  WHERE visit_time BETWEEN ? AND ?";
        
        $result = $this->db->select($query, [$startDate, $endDate]);
        
        return $result[0] ?? [];
    }
    
    /**
     * Get popular pages
     */
    public function getPopularPages(string $startDate, string $endDate, int $limit = 10): array
    {
        $query = "SELECT 
                    page_url,
                    page_title,
                    COUNT(*) as page_views,
                    AVG(load_time) as avg_load_time,
                    AVG(scroll_depth) as avg_scroll_depth,
                    AVG(time_on_page) as avg_time_on_page
                  FROM visit_page_views 
                  WHERE created_at BETWEEN ? AND ?
                  GROUP BY page_url, page_title
                  ORDER BY page_views DESC
                  LIMIT ?";
        
        return $this->db->select($query, [$startDate, $endDate, $limit]);
    }
    
    /**
     * Get traffic sources
     */
    public function getTrafficSources(string $startDate, string $endDate): array
    {
        $query = "SELECT 
                    CASE 
                        WHEN referrer = '' THEN 'Direct'
                        WHEN referrer LIKE '%google.%' THEN 'Google'
                        WHEN referrer LIKE '%facebook.%' THEN 'Facebook'
                        WHEN referrer LIKE '%twitter.%' THEN 'Twitter'
                        WHEN referrer LIKE '%linkedin.%' THEN 'LinkedIn'
                        ELSE 'Other Referral'
                    END as source,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors
                  FROM user_visits 
                  WHERE visit_time BETWEEN ? AND ?
                  GROUP BY source
                  ORDER BY visits DESC";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
    
    /**
     * Get device statistics
     */
    public function getDeviceStats(string $startDate, string $endDate): array
    {
        $query = "SELECT 
                    device_type,
                    browser,
                    platform,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors
                  FROM user_visits 
                  WHERE visit_time BETWEEN ? AND ?
                  GROUP BY device_type, browser, platform
                  ORDER BY visits DESC";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
    
    /**
     * Get geographical distribution
     */
    public function getGeographicalStats(string $startDate, string $endDate): array
    {
        $query = "SELECT 
                    country,
                    city,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors
                  FROM user_visits 
                  WHERE visit_time BETWEEN ? AND ? AND country != ''
                  GROUP BY country, city
                  ORDER BY visits DESC";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
    
    /**
     * Get hourly visit distribution
     */
    public function getHourlyDistribution(string $startDate, string $endDate): array
    {
        $query = "SELECT 
                    HOUR(visit_time) as hour,
                    COUNT(*) as visits
                  FROM user_visits 
                  WHERE visit_time BETWEEN ? AND ?
                  GROUP BY HOUR(visit_time)
                  ORDER BY hour";
        
        return $this->db->select($query, [$startDate, $endDate]);
    }
    
    /**
     * Get user journey for a session
     */
    public function getUserJourney(string $sessionId): array
    {
        $query = "SELECT 
                    uv.visit_id,
                    uv.visit_time,
                    uv.url,
                    uv.referrer,
                    vpv.page_title,
                    vpv.load_time,
                    vpv.scroll_depth,
                    vpv.time_on_page
                  FROM user_visits uv
                  LEFT JOIN visit_page_views vpv ON uv.visit_id = vpv.visit_id
                  WHERE uv.session_id = ?
                  ORDER BY uv.visit_time ASC";
        
        return $this->db->select($query, [$sessionId]);
    }
    
    /**
     * Get real-time active users
     */
    public function getActiveUsers(int $minutes = 5): array
    {
        $timeThreshold = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));
        
        $query = "SELECT 
                    COUNT(DISTINCT session_id) as active_users,
                    device_type,
                    browser
                  FROM user_visits 
                  WHERE visit_time > ?
                  GROUP BY device_type, browser
                  ORDER BY active_users DESC";
        
        return $this->db->select($query, [$timeThreshold]);
    }
    
    /**
     * Get bounce rate (single page visits)
     */
    public function getBounceRate(string $startDate, string $endDate): float
    {
        $query = "SELECT 
                    COUNT(*) as total_visits,
                    SUM(CASE WHEN page_views = 1 THEN 1 ELSE 0 END) as bounce_visits
                  FROM (
                    SELECT 
                        uv.visit_id,
                        COUNT(vpv.id) as page_views
                    FROM user_visits uv
                    LEFT JOIN visit_page_views vpv ON uv.visit_id = vpv.visit_id
                    WHERE uv.visit_time BETWEEN ? AND ?
                    GROUP BY uv.visit_id
                  ) as visit_stats";
        
        $result = $this->db->select($query, [$startDate, $endDate]);
        
        if (empty($result) || $result[0]['total_visits'] == 0) {
            return 0.0;
        }
        
        return ($result[0]['bounce_visits'] / $result[0]['total_visits']) * 100;
    }
    
    /**
     * Get conversion rate for specific events
     */
    public function getConversionRate(string $eventType, string $startDate, string $endDate): float
    {
        $query = "SELECT 
                    COUNT(DISTINCT uv.visit_id) as total_visits,
                    COUNT(DISTINCT ve.visit_id) as converted_visits
                  FROM user_visits uv
                  LEFT JOIN visit_events ve ON uv.visit_id = ve.visit_id AND ve.event_type = ?
                  WHERE uv.visit_time BETWEEN ? AND ?";
        
        $result = $this->db->select($query, [$eventType, $startDate, $endDate]);
        
        if (empty($result) || $result[0]['total_visits'] == 0) {
            return 0.0;
        }
        
        return ($result[0]['converted_visits'] / $result[0]['total_visits']) * 100;
    }
}