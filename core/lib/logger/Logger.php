<?php
/**
 * AaoSikheSystem - Logger
 * Provides file-based logging with severity levels, rotation, and audit support.
 */

namespace AaoSikheSystem\logger;
use AaoSikheSystem\helper\FeatureManager;
use DateTime;
use Exception;

class Logger
{
    private string $logDir;
    private string $logFile;
    private int $maxFileSize; // in bytes
    private bool $enableRotation;
private static ?self $instance = null;
    private const LEVELS = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL', 'SECURITY', 'AUDIT'];

    public function __construct(
        string $logDir = __DIR__ . '/storage/logs/',
        string $fileName = 'system.log',
        int $maxFileSize = 5_000_000,
        bool $enableRotation = true
    ) {
         if (!FeatureManager::isEnabled('logger')) {
            return; // Disable constructor if logging is turned off
        }
        $this->logDir = realpath($logDir) ?: $logDir;
        $this->logFile = $this->logDir . '/' . $fileName;
        $this->maxFileSize = $maxFileSize;
        $this->enableRotation = $enableRotation;

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0775, true);
        }
    }
public static function getInstance(): self
{
    if (!self::$instance) {
        self::$instance = new self(
            config('app.paths.logs'),
            'system.log'
        );
    }
    return self::$instance;
}
    /**
     * Write a log entry.
     */
    public function log(string $level, string $message, array $context = []): void
    {
         if (!FeatureManager::isEnabled('logger')) {
            return; // Disable constructor if logging is turned off
        }
        if (!in_array(strtoupper($level), self::LEVELS, true)) {
            throw new Exception("Invalid log level: $level");
        }

        $this->rotateIfNeeded();

        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $user = $context['user'] ?? 'system';
        $requestId = $context['request_id'] ?? bin2hex(random_bytes(4));
        $extra = $context['extra'] ?? '';

        $logEntry = sprintf(
            "[%s] [%s] [user:%s] [ip:%s] [req:%s] %s %s%s",
            $timestamp,
            strtoupper($level),
            $user,
            $ip,
            $requestId,
            $message,
            $extra ? ' | ' . json_encode($extra, JSON_UNESCAPED_SLASHES) : '',
            PHP_EOL
        );

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /** Shorthand methods for each log level */
    public function debug(string $message, array $context = []): void    { $this->log('DEBUG', $message, $context); }
    public function info(string $message, array $context = []): void     { $this->log('INFO', $message, $context); }
    public function warning(string $message, array $context = []): void  { $this->log('WARNING', $message, $context); }
    public function error(string $message, array $context = []): void    { $this->log('ERROR', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
    public function security(string $message, array $context = []): void { $this->log('SECURITY', $message, $context); }
    public function audit(string $message, array $context = []): void    { $this->log('AUDIT', $message, $context); }

    /**
     * Automatically rotate log file when it exceeds max size.
     */
    private function rotateIfNeeded(): void
    {
         if (!FeatureManager::isEnabled('logger')) {
            return; // Disable constructor if logging is turned off
        }
        if (!$this->enableRotation || !file_exists($this->logFile)) {
            return;
        }

        if (filesize($this->logFile) >= $this->maxFileSize) {
            $timestamp = (new DateTime())->format('Ymd_His');
            $backupFile = $this->logDir . '/system_' . $timestamp . '.log';
            rename($this->logFile, $backupFile);
        }
    }

    /**
     * Create a cryptographic hash chain for tamper-proof audit logs.
     * Each entry’s hash depends on the previous one.
     */
    public function auditSecure(string $message, array $context = []): void
    {
         if (!FeatureManager::isEnabled('logger')) {
            return ; // Disable constructor if logging is turned off
        }
        $prevHash = $this->getLastHash();
        $entry = $message . json_encode($context);
        $currentHash = hash('sha256', $prevHash . $entry);

        $context['hash'] = $currentHash;
        $this->audit($message, $context);
    }

    private function getLastHash(): string
    {
        if (!FeatureManager::isEnabled('logger') || !file_exists($this->logFile)) {
            return '';
        }
        if (!file_exists($this->logFile)) {
            return '';
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastLine = end($lines);
        if (preg_match('/"hash":"([a-f0-9]{64})"/', $lastLine, $matches)) {
            return $matches[1];
        }

        return '';
    }
    public static function warnings(string $message, array $context = []): void
{
    self::getInstance()->log('WARNING', $message, $context);
}

public static function securitys(string $message, array $context = []): void
{
    self::getInstance()->log('SECURITY', $message, $context);
}

}
