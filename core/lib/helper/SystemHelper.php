<?php
namespace AaoSikheSystem\helper;

class SystemHelper
{
    /**
     * Get complete system information
     * @return array
     */
    public static function getCompleteSystemInfo(): array
    {
        return [
            'os' => self::getOSInfo(),
            'php' => self::getPHPInfo(),
            'server' => self::getServerInfo(),
            'database' => self::getDatabaseInfo(),
            'hardware' => self::getHardwareInfo(),
            'performance' => self::getPerformanceInfo(),
            'security' => self::getSecurityInfo(),
            'network' => self::getNetworkInfo(),
            'timestamps' => [
                'current' => date('Y-m-d H:i:s'),
                'uptime' => self::getSystemUptime(),
                'php_start_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ?? time())
            ]
        ];
    }

    /**
     * Get detailed OS information
     * @return array
     */
    public static function getOSInfo(): array
    {
        $info = [];
        
        // Basic OS detection
        $info['name'] = php_uname('s');
        $info['hostname'] = php_uname('n');
        $info['release'] = php_uname('r');
        $info['version'] = php_uname('v');
        $info['machine'] = php_uname('m');
        $info['full_info'] = php_uname();
        
        // Platform specific info
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $info['platform'] = 'Windows';
            $info['windows_version'] = self::getWindowsVersion();
        } else {
            $info['platform'] = 'Unix/Linux';
            $info['distribution'] = self::getLinuxDistribution();
            $info['kernel'] = self::getKernelVersion();
            $info['load_average'] = self::getLoadAverage();
        }
        
        $info['architecture'] = (PHP_INT_SIZE === 8) ? '64-bit' : '32-bit';
        $info['timezone'] = date_default_timezone_get();
        $info['locale'] = setlocale(LC_ALL, 0);
        
        return $info;
    }

    /**
     * Get Windows version details
     * @return array
     */
    private static function getWindowsVersion(): array
    {
        $info = [];
        
        if (function_exists('win32_ps_stat_proc')) {
            $info['edition'] = php_uname('v');
        }
        
        // Try to get more details via WMI if available
        if (class_exists('COM')) {
            try {
                $wmi = new \COM('WinMgmts:\\\\.');
                $os = $wmi->InstancesOf('Win32_OperatingSystem');
                
                foreach ($os as $o) {
                    $info['caption'] = $o->Caption;
                    $info['version'] = $o->Version;
                    $info['build'] = $o->BuildNumber;
                    $info['service_pack'] = $o->CSDVersion;
                    break;
                }
            } catch (\Exception $e) {
                $info['error'] = $e->getMessage();
            }
        }
        
        return $info;
    }

    /**
     * Get Linux distribution info
     * @return array
     */
    private static function getLinuxDistribution(): array
    {
        $distro = ['name' => 'Unknown', 'version' => 'Unknown', 'id' => 'Unknown'];
        
        if (is_readable('/etc/os-release')) {
            $lines = file('/etc/os-release', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = strtolower(trim($key));
                    $value = trim($value, '"\'');
                    
                    if ($key === 'name') $distro['name'] = $value;
                    if ($key === 'version') $distro['version'] = $value;
                    if ($key === 'id') $distro['id'] = $value;
                    if ($key === 'version_id') $distro['version_id'] = $value;
                    if ($key === 'pretty_name') $distro['pretty_name'] = $value;
                }
            }
        } elseif (is_readable('/etc/redhat-release')) {
            $distro['name'] = 'Red Hat/CentOS';
            $distro['version'] = file_get_contents('/etc/redhat-release');
        } elseif (is_readable('/etc/debian_version')) {
            $distro['name'] = 'Debian';
            $distro['version'] = file_get_contents('/etc/debian_version');
        }
        
        return $distro;
    }

    /**
     * Get kernel version
     * @return string
     */
    private static function getKernelVersion(): string
    {
        return php_uname('r');
    }

    /**
     * Get system load average
     * @return array
     */
    private static function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0] ?? 0,
                '5min' => $load[1] ?? 0,
                '15min' => $load[2] ?? 0
            ];
        }
        
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    /**
     * Get detailed PHP information
     * @return array
     */
    public static function getPHPInfo(): array
    {
        $info = [];
        
        // Basic PHP info
        $info['version'] = PHP_VERSION;
        $info['version_id'] = PHP_VERSION_ID;
        $info['zend_version'] = zend_version();
        $info['sapi'] = PHP_SAPI;
        $info['interface'] = php_sapi_name();
        
        // Configuration
        $info['ini_loaded_file'] = php_ini_loaded_file();
        $info['ini_scanned_files'] = php_ini_scanned_files();
        $info['configuration'] = [
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_input_time' => ini_get('max_input_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'display_errors' => ini_get('display_errors'),
            'error_reporting' => ini_get('error_reporting'),
            'log_errors' => ini_get('log_errors'),
            'error_log' => ini_get('error_log'),
            'default_charset' => ini_get('default_charset'),
            'date.timezone' => ini_get('date.timezone')
        ];
        
        // Extensions
        $info['extensions'] = get_loaded_extensions();
        $info['extension_count'] = count($info['extensions']);
        
        // Important extensions check
        $info['important_extensions'] = [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mysqli' => extension_loaded('mysqli'),
            'openssl' => extension_loaded('openssl'),
            'curl' => extension_loaded('curl'),
            'gd' => extension_loaded('gd'),
            'mbstring' => extension_loaded('mbstring'),
            'json' => extension_loaded('json'),
            'xml' => extension_loaded('xml'),
            'zip' => extension_loaded('zip'),
            'intl' => extension_loaded('intl'),
            'opcache' => extension_loaded('Zend OPcache')
        ];
        
        // PHP features
        $info['features'] = [
            '64bit' => (PHP_INT_SIZE === 8),
            'thread_safe' => (ZEND_THREAD_SAFE === true),
            'debug_build' => (PHP_DEBUG === 1),
            'fpm' => (PHP_SAPI === 'fpm-fcgi'),
            'cli' => (PHP_SAPI === 'cli')
        ];
        
        // OpCache info if available
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status(false);
            if ($opcache) {
                $info['opcache'] = [
                    'enabled' => $opcache['opcache_enabled'],
                    'memory_usage' => $opcache['memory_usage'],
                    'statistics' => $opcache['opcache_statistics']
                ];
            }
        }
        
        // Realpath cache
        $info['realpath_cache'] = [
            'size' => realpath_cache_size(),
            'config_size' => realpath_cache_size(),
            'ttl' => ini_get('realpath_cache_ttl')
        ];
        
        return $info;
    }

    /**
     * Get server information
     * @return array
     */
    public static function getServerInfo(): array
    {
        $info = [];
        
        // Web server
        $info['software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $info['name'] = $_SERVER['SERVER_NAME'] ?? 'Unknown';
        $info['addr'] = $_SERVER['SERVER_ADDR'] ?? 'Unknown';
        $info['port'] = $_SERVER['SERVER_PORT'] ?? 'Unknown';
        $info['protocol'] = $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown';
        $info['admin'] = $_SERVER['SERVER_ADMIN'] ?? 'Unknown';
        $info['signature'] = $_SERVER['SERVER_SIGNATURE'] ?? 'Unknown';
        
        // Request info
        $info['request'] = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'scheme' => $_SERVER['REQUEST_SCHEME'] ?? (isset($_SERVER['HTTPS']) ? 'https' : 'http'),
            'uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'query' => $_SERVER['QUERY_STRING'] ?? '',
            'time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
            'time_float' => $_SERVER['REQUEST_TIME_FLOAT'] ?? 0
        ];
        
        // Remote client
        $info['client'] = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'port' => $_SERVER['REMOTE_PORT'] ?? 'Unknown',
            'host' => $_SERVER['REMOTE_HOST'] ?? gethostbyaddr($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'accept' => $_SERVER['HTTP_ACCEPT'] ?? 'Unknown',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'Unknown'
        ];
        
        // Document root and paths
        $info['paths'] = [
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
            'current_working_dir' => getcwd(),
            'php_self' => $_SERVER['PHP_SELF'] ?? 'Unknown'
        ];
        
        // Detect specific servers
        $serverSoftware = strtolower($info['software']);
        $info['server_type'] = 'Unknown';
        
        if (strpos($serverSoftware, 'apache') !== false) {
            $info['server_type'] = 'Apache';
            $info['apache_modules'] = function_exists('apache_get_modules') ? apache_get_modules() : [];
        } elseif (strpos($serverSoftware, 'nginx') !== false) {
            $info['server_type'] = 'Nginx';
        } elseif (strpos($serverSoftware, 'iis') !== false || strpos($serverSoftware, 'microsoft-iis') !== false) {
            $info['server_type'] = 'IIS';
        } elseif (strpos($serverSoftware, 'lite speed') !== false) {
            $info['server_type'] = 'LiteSpeed';
        }
        
        return $info;
    }

    /**
     * Get database information
     * @return array
     */
    public static function getDatabaseInfo(): array
    {
        $info = [];
        
        // Check available database extensions
        $info['available_drivers'] = [
            'pdo' => class_exists('PDO') ? \PDO::getAvailableDrivers() : [],
            'mysqli' => extension_loaded('mysqli'),
            'mysql' => extension_loaded('mysql'),
            'pgsql' => extension_loaded('pgsql'),
            'sqlite' => extension_loaded('sqlite3'),
            'mongodb' => extension_loaded('mongodb'),
            'redis' => extension_loaded('redis')
        ];
        
        // Try to detect active database connections
        $info['active_connections'] = [];
        
        // Check for common framework database configurations
        $info['framework_detection'] = [
            'laravel' => defined('LARAVEL_START'),
            'symfony' => class_exists('Symfony\Component\HttpKernel\Kernel'),
            'codeigniter' => defined('BASEPATH'),
            'cakephp' => defined('CAKE_CORE_INCLUDE_PATH'),
            'yii' => defined('YII_VERSION'),
            'wordpress' => defined('WPINC')
        ];
        
        return $info;
    }

    /**
     * Get hardware information
     * @return array
     */
    public static function getHardwareInfo(): array
    {
        $info = [];
        
        // Memory information
        $info['memory'] = self::getMemoryInfo();
        
        // CPU information
        $info['cpu'] = self::getCPUInfo();
        
        // Disk information
        $info['disk'] = self::getDiskInfo();
        
        return $info;
    }

    /**
     * Get memory information
     * @return array
     */
    private static function getMemoryInfo(): array
    {
        $memory = [];
        
        // Try to get system memory info on Linux
        if (is_readable('/proc/meminfo')) {
            $meminfo = file('/proc/meminfo');
            foreach ($meminfo as $line) {
                $parts = explode(':', $line);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    $memory[$key] = $value;
                }
            }
        }
        
        // PHP memory usage
        $memory['php'] = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak_usage' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage_readable' => self::formatBytes(memory_get_usage(true)),
            'memory_peak_readable' => self::formatBytes(memory_get_peak_usage(true))
        ];
        
        return $memory;
    }

    /**
     * Get CPU information
     * @return array
     */
    private static function getCPUInfo(): array
    {
        $cpu = [];
        
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file('/proc/cpuinfo');
            $cores = [];
            $currentCore = [];
            
            foreach ($cpuinfo as $line) {
                if (trim($line) === '') {
                    if (!empty($currentCore)) {
                        $cores[] = $currentCore;
                        $currentCore = [];
                    }
                    continue;
                }
                
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    $currentCore[$key] = $value;
                }
            }
            
            if (!empty($currentCore)) {
                $cores[] = $currentCore;
            }
            
            $cpu['cores'] = $cores;
            $cpu['core_count'] = count($cores);
            
            if (!empty($cores)) {
                $cpu['model'] = $cores[0]['model name'] ?? $cores[0]['Processor'] ?? 'Unknown';
                $cpu['vendor'] = $cores[0]['vendor_id'] ?? 'Unknown';
                $cpu['mhz'] = $cores[0]['cpu MHz'] ?? 'Unknown';
            }
        }
        
        return $cpu;
    }

    /**
     * Get disk information
     * @return array
     */
    private static function getDiskInfo(): array
    {
        $disk = [];
        
        // Get disk free space for various paths
        $paths = [
            'root' => '/',
            'tmp' => sys_get_temp_dir(),
            'current' => getcwd(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null
        ];
        
        foreach ($paths as $name => $path) {
            if ($path && is_dir($path)) {
                $free = disk_free_space($path);
                $total = disk_total_space($path);
                $used = $total - $free;
                
                if ($total > 0) {
                    $disk[$name] = [
                        'path' => $path,
                        'total' => $total,
                        'free' => $free,
                        'used' => $used,
                        'usage_percentage' => round(($used / $total) * 100, 2),
                        'total_readable' => self::formatBytes($total),
                        'free_readable' => self::formatBytes($free),
                        'used_readable' => self::formatBytes($used)
                    ];
                }
            }
        }
        
        return $disk;
    }

    /**
     * Get performance information
     * @return array
     */
    public static function getPerformanceInfo(): array
    {
        $performance = [];
        
        // Execution time
        $performance['execution_time'] = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        
        // Database query count (if available)
        $performance['database_queries'] = [
            'count' => 0,
            'time' => 0
        ];
        
        // Include file count
        $performance['included_files'] = [
            'count' => count(get_included_files()),
            'files' => get_included_files()
        ];
        
        // Opcache statistics
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status(false);
            if ($opcache) {
                $performance['opcache'] = $opcache['opcache_statistics'];
            }
        }
        
        // Realpath cache
        $performance['realpath_cache'] = [
            'size' => realpath_cache_size(),
            'entries' => count(realpath_cache_get())
        ];
        
        return $performance;
    }

    /**
     * Get security information
     * @return array
     */
    public static function getSecurityInfo(): array
    {
        $security = [];
        
        // SSL/TLS information
        $security['ssl'] = [
            'https' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'ssl_version' => $_SERVER['SSL_VERSION'] ?? null,
            'ssl_cipher' => $_SERVER['SSL_CIPHER'] ?? null,
            'ssl_protocol' => $_SERVER['SSL_PROTOCOL'] ?? null
        ];
        
        // Headers security
        $security['headers'] = [
            'hsts' => stripos($_SERVER['HTTP_STRICT_TRANSPORT_SECURITY'] ?? '', 'max-age') !== false,
            'xss_protection' => isset($_SERVER['HTTP_X_XSS_PROTECTION']),
            'content_type_options' => isset($_SERVER['HTTP_X_CONTENT_TYPE_OPTIONS']),
            'frame_options' => isset($_SERVER['HTTP_X_FRAME_OPTIONS']),
            'content_security_policy' => isset($_SERVER['HTTP_CONTENT_SECURITY_POLICY'])
        ];
        
        // PHP security settings
        $security['php_settings'] = [
            'safe_mode' => ini_get('safe_mode'),
            'open_basedir' => ini_get('open_basedir'),
            'disable_functions' => ini_get('disable_functions'),
            'disable_classes' => ini_get('disable_classes'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'allow_url_include' => ini_get('allow_url_include'),
            'expose_php' => ini_get('expose_php')
        ];
        
        // File permissions (check important directories)
        $security['file_permissions'] = [];
        $paths = [
            'root' => dirname(__FILE__, 4), // Go up 4 levels from helper directory
            'public' => $_SERVER['DOCUMENT_ROOT'] ?? null,
            'tmp' => sys_get_temp_dir(),
            'session_save_path' => session_save_path()
        ];
        
        foreach ($paths as $name => $path) {
            if ($path && file_exists($path)) {
                $security['file_permissions'][$name] = [
                    'path' => $path,
                    'permission' => substr(sprintf('%o', fileperms($path)), -4),
                    'owner' => fileowner($path),
                    'group' => filegroup($path)
                ];
            }
        }
        
        return $security;
    }

    /**
     * Get network information
     * @return array
     */
    public static function getNetworkInfo(): array
    {
        $network = [];
        
        // Get server IP information
        $network['server_ips'] = [];
        
        // Try to get all server IPs
        if (function_exists('gethostbynamel')) {
            $hostname = gethostname();
            $ips = gethostbynamel($hostname);
            if ($ips) {
                $network['server_ips'] = $ips;
            }
        }
        
        // Get client IP (with proxy detection)
        $network['client_ip'] = self::getClientIP();
        
        // DNS information
        $network['dns'] = [
            'hostname' => gethostname(),
            'fqdn' => gethostbyaddr($_SERVER['SERVER_ADDR'] ?? '127.0.0.1'),
            'dns_get_record' => function_exists('dns_get_record') ? dns_get_record(gethostname(), DNS_ALL) : []
        ];
        
        // Port information
        $network['ports'] = [
            'server_port' => $_SERVER['SERVER_PORT'] ?? null,
            'remote_port' => $_SERVER['REMOTE_PORT'] ?? null
        ];
        
        return $network;
    }

    /**
     * Get client IP address (handles proxies)
     * @return string
     */
    public static function getClientIP(): string
    {
        $ip = '';
        
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ipList = explode(',', $_SERVER[$header]);
                foreach ($ipList as $potentialIp) {
                    $potentialIp = trim($potentialIp);
                    if (filter_var($potentialIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $potentialIp;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get system uptime
     * @return string
     */
    public static function getSystemUptime(): string
    {
        if (is_readable('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = floatval(explode(' ', $uptime)[0]);
            
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);
            $seconds = floor($uptime % 60);
            
            return sprintf('%d days, %02d:%02d:%02d', $days, $hours, $minutes, $seconds);
        }
        
        return 'Unknown';
    }

    /**
     * Format bytes to human readable format
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Check if running in CLI mode
     * @return bool
     */
    public static function isCLI(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Check if running on Windows
     * @return bool
     */
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Check if running on Linux
     * @return bool
     */
    public static function isLinux(): bool
    {
        return !self::isWindows();
    }

    /**
     * Get available PHP functions (not disabled)
     * @return array
     */
    public static function getAvailableFunctions(): array
    {
        $disabled = explode(',', ini_get('disable_functions'));
        $allFunctions = get_defined_functions()['internal'];
        
        return array_diff($allFunctions, $disabled);
    }

    /**
     * Get environment variables
     * @return array
     */
    public static function getEnvironmentVariables(): array
    {
        return [
            'php' => $_ENV,
            'server' => $_SERVER,
            'getenv' => getenv()
        ];
    }

    /**
     * Get session information
     * @return array
     */
    public static function getSessionInfo(): array
    {
        $info = [];
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $info['status'] = 'active';
            $info['id'] = session_id();
            $info['name'] = session_name();
            $info['save_path'] = session_save_path();
            $info['cookie_params'] = session_get_cookie_params();
            $info['module'] = session_module_name();
            $info['data_count'] = count($_SESSION ?? []);
        } else {
            $info['status'] = 'inactive';
        }
        
        return $info;
    }

    /**
     * Get installed packages via composer (if available)
     * @return array
     */
    public static function getComposerPackages(): array
    {
        $packages = [];
        
        $composerPaths = [
            dirname(__FILE__, 4) . '/composer.lock', // Go up 4 levels
            dirname(__FILE__, 3) . '/composer.lock',
            dirname(__FILE__, 2) . '/composer.lock',
            'composer.lock'
        ];
        
        foreach ($composerPaths as $path) {
            if (file_exists($path)) {
                $composerLock = json_decode(file_get_contents($path), true);
                if (isset($composerLock['packages'])) {
                    $packages = $composerLock['packages'];
                    break;
                }
            }
        }
        
        return $packages;
    }

    /**
     * Generate system report file
     * @param string $filename
     * @param bool $includeSensitive Include sensitive information
     * @return string Path to generated file
     */
    public static function generateReport(string $filename = 'system_report.json', bool $includeSensitive = false): string
    {
        $data = self::getCompleteSystemInfo();
        
        if (!$includeSensitive) {
            // Remove sensitive information
            unset($data['security']['file_permissions']);
            unset($data['network']['client_ip']);
            unset($data['server']['client']['ip']);
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $path = sys_get_temp_dir() . '/' . $filename;
        
        file_put_contents($path, $json);
        
        return $path;
    }

    /**
     * Check system requirements against a specification
     * @param array $requirements
     * @return array
     */
    public static function checkRequirements(array $requirements): array
    {
        $results = [];
        
        foreach ($requirements as $key => $requirement) {
            switch ($key) {
                case 'php_version':
                    $results[$key] = [
                        'required' => $requirement,
                        'actual' => PHP_VERSION,
                        'status' => version_compare(PHP_VERSION, $requirement, '>=')
                    ];
                    break;
                    
                case 'extensions':
                    foreach ($requirement as $ext) {
                        $results["extension_{$ext}"] = [
                            'required' => true,
                            'actual' => extension_loaded($ext),
                            'status' => extension_loaded($ext)
                        ];
                    }
                    break;
                    
                case 'memory_limit':
                    $current = ini_get('memory_limit');
                    $currentBytes = self::convertToBytes($current);
                    $requiredBytes = self::convertToBytes($requirement);
                    
                    $results[$key] = [
                        'required' => $requirement,
                        'actual' => $current,
                        'status' => $currentBytes >= $requiredBytes
                    ];
                    break;
            }
        }
        
        return $results;
    }

    /**
     * Convert shorthand memory notation to bytes
     * @param string $value
     * @return int
     */
    private static function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}