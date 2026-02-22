<?php

declare(strict_types=1);

namespace AaoSikheSystem\monitoring;

use AaoSikheSystem\db\DBManager;

/**
 * Visit Reporter - Generates reports and exports visit data
 * 
 * @package AaoSikheSystem
 */
class VisitReporter
{
    private VisitAnalyzer $analyzer;
    private DBManager $db;

    public function __construct(VisitAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
        $this->db = DBManager::getInstance();

        // Auto ensure tables exist
        $this->ensureTables();
    }

    /**
     * Ensure all visit tracking tables exist
     */
    protected function ensureTables(): void
    {
        $this->db->createTableIfNotExists('user_visits', [
            'visit_id'     => 'VARCHAR(255) NOT NULL',
            'session_id'   => 'VARCHAR(255) DEFAULT NULL',
            'user_id'      => 'VARCHAR(255) DEFAULT NULL',
            'ip_address'   => 'VARCHAR(255) DEFAULT NULL',
            'url'          => 'TEXT',
            'referrer'     => 'TEXT',
            'device_type'  => 'VARCHAR(255) DEFAULT NULL',
            'browser'      => 'VARCHAR(255) DEFAULT NULL',
            'platform'     => 'VARCHAR(255) DEFAULT NULL',
            'country'      => 'VARCHAR(255) DEFAULT NULL',
            'city'         => 'VARCHAR(255) DEFAULT NULL',
            'latitude'     => 'DECIMAL(10,6) DEFAULT NULL',
            'longitude'    => 'DECIMAL(10,6) DEFAULT NULL',
            'visit_time'   => 'DATETIME NOT NULL',
            'created_at'   => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ], 'visit_id', [
            'INDEX idx_session_id (session_id)',
            'INDEX idx_user_id (user_id)',
            'INDEX idx_ip (ip_address)',
            'INDEX idx_visit_time (visit_time)'
        ]);

        $this->db->createTableIfNotExists('visit_page_views', [
            'id'           => 'VARCHAR(255) NOT NULL',
            'visit_id'     => 'VARCHAR(255) NOT NULL',
            'page_url'     => 'TEXT',
            'page_title'   => 'VARCHAR(255) DEFAULT NULL',
            'load_time'    => 'FLOAT DEFAULT NULL',
            'scroll_depth' => 'FLOAT DEFAULT NULL',
            'time_on_page' => 'FLOAT DEFAULT NULL',
            'created_at'   => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ], 'id', [
            'INDEX idx_visit_id (visit_id)',
            'INDEX idx_created_at (created_at)'
        ]);

        $this->db->createTableIfNotExists('visit_events', [
            'id'          => 'VARCHAR(255) NOT NULL',
            'visit_id'    => 'VARCHAR(255) NOT NULL',
            'event_type'  => 'VARCHAR(255) NOT NULL',
            'event_label' => 'VARCHAR(255) DEFAULT NULL',
            'event_value' => 'TEXT DEFAULT NULL',
            'created_at'  => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ], 'id', [
            'INDEX idx_visit_id (visit_id)',
            'INDEX idx_event_type (event_type)'
        ]);
    }

    // --- Existing report generation methods ---
    public function generateDailyReport(string $date): array
    {
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';

        return [
            'date' => $date,
            'summary' => $this->analyzer->getVisitStats($startDate, $endDate),
            'popular_pages' => $this->analyzer->getPopularPages($startDate, $endDate),
            'traffic_sources' => $this->analyzer->getTrafficSources($startDate, $endDate),
            'device_stats' => $this->analyzer->getDeviceStats($startDate, $endDate),
            'hourly_distribution' => $this->analyzer->getHourlyDistribution($startDate, $endDate),
            'bounce_rate' => $this->analyzer->getBounceRate($startDate, $endDate)
        ];
    }

    public function generateWeeklyReport(string $startDate): array
    {
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));

        return [
            'period' => $startDate . ' to ' . $endDate,
            'summary' => $this->analyzer->getVisitStats($startDate, $endDate),
            'popular_pages' => $this->analyzer->getPopularPages($startDate, $endDate, 15),
            'traffic_sources' => $this->analyzer->getTrafficSources($startDate, $endDate),
            'device_stats' => $this->analyzer->getDeviceStats($startDate, $endDate),
            'geographical_stats' => $this->analyzer->getGeographicalStats($startDate, $endDate)
        ];
    }

    public function generateRealtimeDashboard(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        return [
            'today' => $this->analyzer->getVisitStats($today . ' 00:00:00', $today . ' 23:59:59'),
            'yesterday' => $this->analyzer->getVisitStats($yesterday . ' 00:00:00', $yesterday . ' 23:59:59'),
            'active_users' => $this->analyzer->getActiveUsers(30),
            'current_hour_visits' => $this->getCurrentHourVisits(),
            'top_pages_today' => $this->analyzer->getPopularPages($today . ' 00:00:00', $today . ' 23:59:59', 5)
        ];
    }

    private function getCurrentHourVisits(): int
    {
        $currentHour = date('Y-m-d H:00:00');
        $nextHour = date('Y-m-d H:00:00', strtotime('+1 hour'));

        $stats = $this->analyzer->getVisitStats($currentHour, $nextHour);
        return $stats['total_visits'] ?? 0;
    }

    public function exportToCsv(string $startDate, string $endDate, string $filename): bool
    {
        $query = "SELECT * FROM user_visits WHERE visit_time BETWEEN ? AND ? ORDER BY visit_time DESC";
        $visits = $this->db->select($query,'ss', [$startDate, $endDate]);

        if (empty($visits)) {
            return false;
        }

        $fp = fopen($filename, 'w');
        fputcsv($fp, array_keys($visits[0]));
        foreach ($visits as $visit) {
            fputcsv($fp, $visit);
        }
        fclose($fp);

        return true;
    }

    public function generatePerformanceReport(string $startDate, string $endDate): array
    {
        $query = "SELECT 
                    page_url,
                    COUNT(*) as page_views,
                    AVG(load_time) as avg_load_time,
                    MIN(load_time) as min_load_time,
                    MAX(load_time) as max_load_time,
                    AVG(scroll_depth) as avg_scroll_depth,
                    AVG(time_on_page) as avg_time_on_page
                  FROM visit_page_views 
                  WHERE created_at BETWEEN ? AND ?
                  GROUP BY page_url
                  HAVING page_views > 10
                  ORDER BY avg_load_time DESC";

        $performanceData = $this->db->select($query, 'ss',[$startDate, $endDate]);

        return [
            'slowest_pages' => array_slice($performanceData, 0, 10),
            'fastest_pages' => array_slice(array_reverse($performanceData), 0, 10),
            'summary' => [
                'avg_page_load_time' => count($performanceData)
                    ? array_sum(array_column($performanceData, 'avg_load_time')) / count($performanceData)
                    : 0,
                'total_page_views' => array_sum(array_column($performanceData, 'page_views'))
            ]
        ];
    }
}
