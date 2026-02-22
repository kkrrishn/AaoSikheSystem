<?php

declare(strict_types=1);

namespace AaoSikheSystem\db;

/**
 * AaoSikheSystem Secure - Database Connection with Enhanced Features
 * 
 * @package AaoSikheSystem
 */

class Connection
{
    private ?\PDO $pdo = null;
    private array $config;
    private bool $isConnected = false;
    private int $queryCount = 0;
    private array $queryLog = [];
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function connect(): void
    {
        if ($this->isConnected) {
            return;
        }
        
        $dsn = $this->buildDsn();
        $options = $this->getPdoOptions();

        try {
            $this->pdo = new \PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                $options
            );
            
            // Set charset if specified
            if (isset($this->config['charset'])) {
                $this->pdo->exec("SET NAMES '{$this->config['charset']}'");
            }
            
            // Set timezone if specified
            if (isset($this->config['timezone'])) {
                $this->pdo->exec("SET time_zone = '{$this->config['timezone']}'");
            }
            
            $this->isConnected = true;
            
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function buildDsn(): string
    {
        $driver = $this->config['driver'] ?? 'mysql';
        
        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']}";
                if (isset($this->config['port'])) {
                    $dsn .= ";port={$this->config['port']}";
                }
                if (isset($this->config['charset'])) {
                    $dsn .= ";charset={$this->config['charset']}";
                }
                if (isset($this->config['unix_socket'])) {
                    $dsn .= ";unix_socket={$this->config['unix_socket']}";
                }
                return $dsn;
                
            case 'pgsql':
                $dsn = "pgsql:host={$this->config['host']};dbname={$this->config['database']}";
                if (isset($this->config['port'])) {
                    $dsn .= ";port={$this->config['port']}";
                }
                if (isset($this->config['sslmode'])) {
                    $dsn .= ";sslmode={$this->config['sslmode']}";
                }
                return $dsn;
                
            case 'sqlite':
                return "sqlite:{$this->config['database']}";
                
            case 'sqlsrv':
                $dsn = "sqlsrv:Server={$this->config['host']}";
                if (isset($this->config['port'])) {
                    $dsn .= ",{$this->config['port']}";
                }
                $dsn .= ";Database={$this->config['database']}";
                return $dsn;
                
            default:
                throw new \InvalidArgumentException("Unsupported database driver: {$driver}");
        }
    }
    
    private function getPdoOptions(): array
    {
        $defaultOptions = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
        ];
        
        // Merge custom options
        if (isset($this->config['options']) && is_array($this->config['options'])) {
            $defaultOptions = array_merge($defaultOptions, $this->config['options']);
        }
        
        return $defaultOptions;
    }
    
    public function getPdo(): \PDO
    {
        $this->connect();
        return $this->pdo;
    }
    
    public function isConnected(): bool
    {
        return $this->isConnected;
    }
    
    public function disconnect(): void
    {
        $this->pdo = null;
        $this->isConnected = false;
    }
    
    public function beginTransaction(): bool
    {
        $this->queryCount++;
        $this->logQuery('BEGIN TRANSACTION');
        return $this->getPdo()->beginTransaction();
    }
    
    public function commit(): bool
    {
        $this->queryCount++;
        $this->logQuery('COMMIT');
        return $this->getPdo()->commit();
    }
    
    public function rollBack(): bool
    {
        $this->queryCount++;
        $this->logQuery('ROLLBACK');
        return $this->getPdo()->rollBack();
    }
    
    public function inTransaction(): bool
    {
        return $this->pdo ? $this->pdo->inTransaction() : false;
    }
    
    /**
     * Get the number of queries executed
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }
    
    /**
     * Get the query log
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * Log a query (for debugging)
     */
    private function logQuery(string $query): void
    {
        if ($this->config['log_queries'] ?? false) {
            $this->queryLog[] = [
                'query' => $query,
                'time' => microtime(true)
            ];
        }
    }
    
    /**
     * Get database server version
     */
    public function getServerVersion(): string
    {
        return $this->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }
    
    /**
     * Get driver name
     */
    public function getDriverName(): string
    {
        return $this->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }
}