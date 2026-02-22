<?php
namespace AaoSikheSystem\lib\audit;

use AaoSikheSystem\db\DBManager;
use AaoSikheSystem\logger\Logger;
use PDO;

class AuditLogger
{
    protected Logger $logger;
    protected DBManager $db;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->db = DBManager::getInstance();
        $this->ensureTable();
    }

    /**
     * Ensure audit_logs table exists
     */
   protected function ensureTable(): void
{
    $db = DBManager::getInstance();

    // Define table columns
    $columns = [
        'id'         => 'VARCHAR(255) NOT NULL',
        'user_id'    => 'VARCHAR(255) DEFAULT NULL',
        'action'     => 'VARCHAR(255) NOT NULL',
        'payload'    => 'JSON DEFAULT NULL',
        'created_at' => 'DATETIME NOT NULL',
        'ip'         => 'VARCHAR(255) DEFAULT NULL',
        'prev_hash'  => 'VARCHAR(255) DEFAULT NULL',
        'hash'       => 'VARCHAR(255) NOT NULL'
    ];

    // Define indexes
    $indexes = [
        'INDEX idx_user_id (user_id)',
        'INDEX idx_created_at (created_at)'
    ];

    // Create the table if missing
    $db->createTableIfNotExists(
        'audit_logs',
        $columns,
        'id',       // Primary key
        $indexes
    );
}


    /**
     * Append an audit event with hash chaining for integrity.
     * Payload should be array (will be stored as JSON).
     */
    public function append(?string $userId, string $action, array $payload = [], ?string $ip = null): void
    {
        // Get last hash
        $prev = $this->db->selectOne("SELECT hash FROM audit_logs ORDER BY created_at DESC LIMIT 1");
        $prevHash = $prev['hash'] ?? null;

        $createdAt = date('Y-m-d H:i:s');
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Compute hash
        $raw = implode('|', [
            $prevHash ?: '',
            $userId ?: '',
            $action,
            $payloadJson,
            $createdAt,
            $ip ?: ''
        ]);
        $hash = hash('sha256', $raw);
        $id = uniqid('log_', true);

        // Insert audit log
        $this->db->insert(
            "INSERT INTO audit_logs (id, user_id, action, payload, created_at, ip, prev_hash, hash) VALUES (?,?,?,?,?,?,?,?)",
            'ssssssss',
            [$id, $userId, $action, $payloadJson, $createdAt, $ip, $prevHash, $hash]
        );

        $this->logger->info('Audit appended', [
            'id' => $id,
            'user_id' => $userId,
            'action' => $action
        ]);
    }
}
