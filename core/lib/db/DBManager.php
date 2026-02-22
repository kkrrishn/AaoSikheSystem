<?php

declare(strict_types=1);

namespace AaoSikheSystem\db;

use PDOStatement;

/**
 * AaoSikheSystem Secure - Database Manager with ParamType Support
 * 
 * @package AaoSikheSystem
 */

class DBManager
{
    private static ?DBManager $instance = null;
    private array $connections = [];
    private array $config;
    private string $defaultConnection;

    private function __construct(array $config)
    {

        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? 'primary';
    }

    public static function getInstance(array $config = []): self
    {

        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function connection(?string $name = null): Connection
    {
        $name = $name ?: $this->defaultConnection;

        if (!isset($this->connections[$name])) {
            if (!isset($this->config['connections'][$name])) {
                throw new \InvalidArgumentException("Database connection '{$name}' not configured");
            }

            $this->connections[$name] = new Connection($this->config['connections'][$name]);
        }

        return $this->connections[$name];
    }

    /**
     * Execute a SELECT query with prepared statements and parameter type support
     */
    public function select(string $query, $paramTypes = null, array $params = [], ?string $connection = null): array
    {
        $stmt = $this->execute($query, $paramTypes, $params, $connection);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Execute a SELECT query and return single row
     */
    public function selectOne(string $query, $paramTypes = null, array $params = [], ?string $connection = null): ?array
    {
        $stmt = $this->execute($query, $paramTypes, $params, $connection);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute an INSERT query
     */
    public function insert(
        string $query,
        $paramTypes = null,
        array $params = [],
        mixed $returnId = null,
        ?string $connection = null
    ): int|string|bool|PDOStatement {

        $stmt = $this->execute($query, $paramTypes, $params, $connection);

        if ($stmt === false) {
            return false; // execution failed
        }

        // 🔑 If custom ID is provided, return it
        if ($returnId !== null) {
            return $returnId;
        }

        // 🔑 Otherwise return auto-increment ID
        $pdo = $this->connection($connection)->getPdo();
        if($connection===null)
            return $stmt?true: $pdo->lastInsertId();
        else
            return $stmt?? $pdo->lastInsertId();
    }



    /**
     * Execute an UPDATE query
     */
    public function update(string $query, $paramTypes = null, array $params = [], ?string $connection = null): bool|int
    {
        $stmt = $this->execute($query, $paramTypes, $params, $connection);
        return ($stmt ? $stmt->rowCount() : 0) > 0;
    }

    /**
     * Execute a DELETE query
     */
    public function delete(string $query, $paramTypes = null, array $params = [], ?string $connection = null): int
    {
        $stmt = $this->execute($query, $paramTypes, $params, $connection);
        return $stmt->rowCount();
    }

    /**
     * Execute a raw query with prepared statements and parameter type support
     */
    public function execute(string $query, $paramTypes = null, array $params = [], ?string $connection = null): \PDOStatement
    {
        $conn = $this->connection($connection);
        $pdo = $conn->getPdo();

        try {
            $stmt = $pdo->prepare($query);

            if (!empty($params)) {
                $this->bindParameters($stmt, $paramTypes, $params);
            }

            $stmt->execute();
            return $stmt;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database query failed: " . $e->getMessage());
        }
    }

    /**
     * Bind parameters with type support
     */
    private function bindParameters(\PDOStatement $stmt, $paramTypes, array $params): void
    {
        // If paramTypes is null, auto-detect types
        if ($paramTypes === null) {
            foreach ($params as $key => $value) {
                $paramKey = is_int($key) ? $key + 1 : $key;
                $stmt->bindValue($paramKey, $value, $this->getPdoType($value));
            }
            return;
        }

        // If paramTypes is a string (like 'sis' for string-int-string)
        if (is_string($paramTypes)) {
            $this->bindWithTypeString($stmt, $paramTypes, $params);
            return;
        }

        // If paramTypes is an array of types
        if (is_array($paramTypes)) {
            $this->bindWithTypeArray($stmt, $paramTypes, $params);
            return;
        }

        throw new \InvalidArgumentException("Invalid paramTypes format");
    }

    /**
     * Bind parameters using type string (like 'sis' for string-int-string)
     */
    private function bindWithTypeString(\PDOStatement $stmt, string $typeString, array $params): void
    {
        $typeString = strtolower($typeString);
        $paramCount = strlen($typeString);

        if (count($params) !== $paramCount) {
            throw new \InvalidArgumentException("Parameter count mismatch: expected {$paramCount}, got " . count($params));
        }

        foreach ($params as $index => $value) {
            $paramKey = $index + 1;
            $typeChar = $typeString[$index] ?? 's';

            $stmt->bindValue($paramKey, $value, $this->getPdoTypeFromChar($typeChar, $value));
        }
    }

    /**
     * Bind parameters using type array
     */
    private function bindWithTypeArray(\PDOStatement $stmt, array $typeArray, array $params): void
    {
        if (count($params) !== count($typeArray)) {
            throw new \InvalidArgumentException("Parameter count mismatch: expected " . count($typeArray) . ", got " . count($params));
        }

        foreach ($params as $index => $value) {
            $paramKey = is_int($index) ? $index + 1 : $index;
            $type = $typeArray[$index] ?? \PDO::PARAM_STR;

            if (is_string($type)) {
                $type = $this->getPdoTypeFromChar($type, $value);
            }

            $stmt->bindValue($paramKey, $value, $type);
        }
    }

    /**
     * Get PDO type from character
     */
    private function getPdoTypeFromChar(string $char, $value): int
    {
        return match (strtolower($char)) {
            'i', 'd' => \PDO::PARAM_INT,
            'b' => \PDO::PARAM_BOOL,
            's' => \PDO::PARAM_STR,
            'n' => \PDO::PARAM_NULL,
            'l' => \PDO::PARAM_LOB,
            default => $this->getPdoType($value) // Auto-detect if unknown char
        };
    }

    /**
     * Auto-detect PDO type from value
     */
    private function getPdoType(mixed $value): int
    {
        if (is_int($value)) {
            return \PDO::PARAM_INT;
        }

        if (is_bool($value)) {
            return \PDO::PARAM_BOOL;
        }

        if (is_null($value)) {
            return \PDO::PARAM_NULL;
        }

        if (is_resource($value)) {
            return \PDO::PARAM_LOB;
        }

        return \PDO::PARAM_STR;
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(?string $connection = null): bool
    {
        return $this->connection($connection)->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(?string $connection = null): bool
    {
        return $this->connection($connection)->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollBack(?string $connection = null): bool
    {
        return $this->connection($connection)->rollBack();
    }

    /**
     * Execute within a transaction
     */
    public function transaction(callable $callback, ?string $connection = null): mixed
    {
        $this->beginTransaction($connection);

        try {
            $result = $callback($this);
            $this->commit($connection);
            return $result;
        } catch (\Exception $e) {
            $this->rollBack($connection);
            throw $e;
        }
    }

    /**
     * Health check for connections
     */
    public function healthCheck(?string $connection = null): bool
    {
        try {
            $conn = $this->connection($connection);
            $pdo = $conn->getPdo();
            $pdo->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all connection names
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->config['connections'] ?? []);
    }

    /**
     * Close all connections
     */
    public function closeAll(): void
    {
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }
        $this->connections = [];
    }


    public function getNextCustomId(
        string $table,
        string $column,
        string $prefix = 'ass',
        ?int $padLength = null,
        ?string $connection = null
    ): string {
        $conn = $this->connection($connection);
        $pdo  = $conn->getPdo();

        // Prepare SQL (DO NOT reuse named parameters)
        $sql = "
        SELECT $column
        FROM $table
        WHERE $column LIKE :prefix_like
        ORDER BY CAST(
            SUBSTRING($column, LENGTH(:prefix_len) + 1) AS UNSIGNED
        ) DESC
        LIMIT 1
    ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'prefix_like' => $prefix . '%',
            'prefix_len'  => $prefix
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row && isset($row[$column])) {
            // Example: ass00009 → 9
            $lastId     = $row[$column];
            $number     = (int) substr($lastId, strlen($prefix));
            $nextNumber = $number + 1;
        } else {
            $nextNumber = 1;
        }

        // Optional zero padding
        if ($padLength !== null) {
            return $prefix . str_pad((string) $nextNumber, $padLength, '0', STR_PAD_LEFT);
        }

        return $prefix . $nextNumber;
    }


    /**
     * Create a table if it does not exist.
     *
     * @param string $tableName  The table name
     * @param array  $columns    Associative array of column definitions:
     *                           ['column_name' => 'VARCHAR(255) NOT NULL', ...]
     * @param string|null $primaryKey Optional primary key name
     * @param array $indexes Optional array of indexes:
     *                       ['UNIQUE (email)', 'INDEX idx_user (user_id)']
     * @param string|null $connection Optional connection name
     * @return bool True if executed successfully
     */
    public function createTableIfNotExists(
        string $tableName,
        array $columns,
        ?string $primaryKey = null,
        array $indexes = [],
        ?string $connection = null
    ): bool {
        $conn = $this->connection($connection);
        $pdo = $conn->getPdo();

        // Prepare column definitions
        $columnDefs = [];
        foreach ($columns as $name => $definition) {
            $columnDefs[] = "`$name` $definition";
        }

        // Add primary key if provided
        if ($primaryKey) {
            $columnDefs[] = "PRIMARY KEY (`$primaryKey`)";
        }

        // Add any additional indexes
        foreach ($indexes as $indexDef) {
            $columnDefs[] = $indexDef;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
        " . implode(",\n        ", $columnDefs) . "
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $pdo->exec($sql);
            return true;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Failed to create table '$tableName': " . $e->getMessage());
        }
    }

    public function tableExists(string $table, ?string $connection = null): bool
    {
        $pdo = $this->connection($connection)->getPdo();

        $stmt = $pdo->prepare("
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
        AND table_name = ?
        LIMIT 1
    ");

        $stmt->execute([$table]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Execute a SELECT query and return a single associative row
     */
    public function selectRow(
        string $query,
        $paramTypes = null,
        array $params = [],
        ?string $connection = null
    ): ?array {
        $stmt = $this->execute($query, $paramTypes, $params, $connection);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
    /**
     * Execute a SELECT query and return a single scalar value
     */
    public function value(
        string $query,
        $paramTypes = null,
        array $params = [],
        ?string $connection = null
    ): mixed {
        $stmt = $this->execute($query, $paramTypes, $params, $connection);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : null;
    }
}
