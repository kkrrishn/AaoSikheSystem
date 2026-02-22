<?php

namespace AaoSikheSystem\helper;

class PageSpeed
{
    private static $timers = [];
    private static $startTime;
    private static $memoryUsage;

    /**
     * Start measuring page load time
     */
    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$memoryUsage = memory_get_usage();
    }

    /**
     * Get current loading time
     */
    public static function getLoadTime(): float
    {
        return microtime(true) - self::$startTime;
    }

    /**
     * Get formatted loading time
     */
    public static function getFormattedLoadTime(): string
    {
        $time = self::getLoadTime();
        
        if ($time < 1) {
            return round($time * 1000, 2) . ' ms';
        } else {
            return round($time, 3) . ' seconds';
        }
    }

    /**
     * Get memory usage
     */
    public static function getMemoryUsage(): string
    {
        $memory = memory_get_usage() - self::$memoryUsage;
        
        if ($memory < 1024) {
            return $memory . ' bytes';
        } elseif ($memory < 1048576) {
            return round($memory / 1024, 2) . ' KB';
        } else {
            return round($memory / 1048576, 2) . ' MB';
        }
    }

    /**
     * Get peak memory usage
     */
    public static function getPeakMemoryUsage(): string
    {
        $memory = memory_get_peak_usage(true);
        
        if ($memory < 1024) {
            return $memory . ' bytes';
        } elseif ($memory < 1048576) {
            return round($memory / 1024, 2) . ' KB';
        } else {
            return round($memory / 1048576, 2) . ' MB';
        }
    }

    /**
     * Start a custom timer
     */
    public static function startTimer(string $name): void
    {
        self::$timers[$name] = microtime(true);
    }

    /**
     * Get custom timer result
     */
    public static function getTimer(string $name): float
    {
        if (isset(self::$timers[$name])) {
            return microtime(true) - self::$timers[$name];
        }
        
        return 0;
    }

    /**
     * Get configuration value safely
     */
    private static function getConfig(string $key, $default = null)
    {
        // Method 1: If you have a global config array
        if (isset($GLOBALS['appConfig']) && isset($GLOBALS['appConfig'][$key])) {
            return $GLOBALS['appConfig'][$key];
        }
        
        // Method 2: If you have a config function
        if (function_exists('config')) {
            return config($key, $default);
        }
        
        // Method 3: Direct file access (fallback)
        $configFile = dirname(__DIR__, 2) . '/config/app.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $keys = explode('.', $key);
            $value = $config;
            
            foreach ($keys as $k) {
                if (isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    return $default;
                }
            }
            return $value;
        }
        
        return $default;
    }

    /**
     * Check if debug mode is enabled
     */
    private static function isDebugEnabled(): bool
    {
        return (bool) self::getConfig('debug', false);
    }

    /**
     * Get performance report
     */
    public static function getPerformanceReport(): array
    {
        return [
            'load_time' => self::getLoadTime(),
            'load_time_formatted' => self::getFormattedLoadTime(),
            'memory_usage' => self::getMemoryUsage(),
            'peak_memory' => self::getPeakMemoryUsage(),
            'included_files' => count(get_included_files()),
            'server_load' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
        ];
    }

    /**
     * Display performance info in footer
     */
    public static function displayInFooter(): void
    {
        // Only display if debug is enabled
        if (!self::isDebugEnabled()) {
            return;
        }

        $report = self::getPerformanceReport();
        
        echo "\n<!-- Page Performance Debug -->\n";
        echo "<!-- Load Time: " . $report['load_time_formatted'] . " -->\n";
        echo "<!-- Memory Usage: " . $report['memory_usage'] . " -->\n";
        echo "<!-- Peak Memory: " . $report['peak_memory'] . " -->\n";
        echo "<!-- Included Files: " . $report['included_files'] . " -->\n";
        
        if ($report['server_load']) {
            echo "<!-- Server Load: " . implode(', ', $report['server_load']) . " -->\n";
        }
        
        // Display in browser for admins (simplified check)
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
        $isLocal = $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1';
        
        if ($isAdmin || $isLocal) {
            echo "<div style='position:fixed;bottom:10px;right:10px;background:rgba(0,0,0,0.8);color:white;padding:10px;border-radius:5px;font-size:12px;z-index:9999;font-family:monospace;'>";
            echo "⚡ " . $report['load_time_formatted'] . " | ";
            echo "💾 " . $report['memory_usage'] . " | ";
            echo "📁 " . $report['included_files'] . " files";
            echo "</div>";
        }
    }

    /**
     * Log performance data
     */
    public static function logPerformance(): void
    {
        $report = self::getPerformanceReport();
        $logFile = self::getConfig('logs', dirname(__DIR__, 2) . '/../storage/logs') . '/performance.log';
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? '/',
            'load_time' => $report['load_time'],
            'memory_usage' => $report['memory_usage'],
            'peak_memory' => $report['peak_memory'],
            'included_files' => $report['included_files'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logMessage = "PERFORMANCE: " . json_encode($logData);
        
        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        error_log($logMessage . PHP_EOL, 3, $logFile);
    }
      public static function getStats(): array
    {
        $loadTime = microtime(true) - self::$startTime;
        $memory = memory_get_usage() - self::$memoryUsage;
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'load_time' => $loadTime,
            'load_time_ms' => round($loadTime * 1000, 2),
            'load_time_formatted' => $loadTime < 1 ? 
                round($loadTime * 1000, 2) . ' ms' : 
                round($loadTime, 3) . ' seconds',
            'memory_usage' => self::formatBytes($memory),
            'peak_memory' => self::formatBytes($peakMemory),
            'included_files' => count(get_included_files())
        ];
    }
      private static function formatBytes($bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' bytes';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
    public static function display(): void
    {
        $stats = self::getStats();
        
        // Always show in local development
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
        
        if ($isLocal || (isset($_GET['debug']) && $_GET['debug'] === 'performance')) {
            echo "<div style='position:fixed;bottom:10px;right:10px;background:rgba(0,0,0,0.9);color:#0f0;padding:10px;border-radius:5px;font-size:12px;z-index:9999;font-family:monospace;border:1px solid #0f0;'>";
            echo "⚡ <strong>Performance Stats</strong><br>";
            echo "Time: " . $stats['load_time_formatted'] . "<br>";
            echo "Memory: " . $stats['memory_usage'] . "<br>";
            echo "Peak: " . $stats['peak_memory'] . "<br>";
            echo "Files: " . $stats['included_files'];
            echo "</div>";
        }
        
        // Always log to HTML comments
        echo "\n<!-- Performance: " . $stats['load_time_formatted'] . " | Memory: " . $stats['memory_usage'] . " -->\n";
    }
}