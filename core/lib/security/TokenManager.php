<?php
namespace AaoSikheSystem\security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use AaoSikheSystem\db\DBManager;
use AaoSikheSystem\logger\Logger;
use AaoSikheSystem\security\Crypto;
use PDOException;

class TokenManager
{
    protected string $jwtSecret;
    protected string $algo = 'HS256';
    protected int $accessTtl;   // seconds
    protected int $refreshTtl;  // seconds
    protected Logger $logger;
    protected array $dbConfig;

    public function __construct(array $config, Logger $logger,array $dbConfig)
    {
        $this->jwtSecret = $config['jwt_secret'] ?? (getenv('JWT_SECRET') ?: throw new \InvalidArgumentException('JWT_SECRET required'));
        $this->accessTtl = $config['access_ttl'] ?? 900; // 15 mins
        $this->refreshTtl = $config['refresh_ttl'] ?? 60 * 60 * 24 * 30; // 30 days
        $this->logger = $logger;
        $this->dbConfig = $dbConfig;

        $this->ensureTableExists();

        // Occasionally clean up old tokens
        if (random_int(1, 20) === 1) {
            $this->cleanupExpiredTokens();
        }
    }

    /**
     * Ensure refresh_tokens table exists.
     */
    protected function ensureTableExists(): void
{
    $db = DBManager::getInstance($this->dbConfig);

    $columns = [
        'id'          => 'VARCHAR(255) NOT NULL',
        'user_id'     => 'VARCHAR(255) NOT NULL',
        'device_id'   => 'VARCHAR(255) DEFAULT NULL',
        'token_hash'  => 'VARCHAR(255) NOT NULL',
        'expires_at'  => 'DATETIME NOT NULL',
        'revoked'     => 'TINYINT(1) DEFAULT 0',
        'ip'          => 'VARCHAR(255) DEFAULT NULL',
        'user_agent'  => 'VARCHAR(255) DEFAULT NULL',
        'created_at'  => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ];

    $indexes = [
        'INDEX idx_user_id (user_id)',
        'INDEX idx_device_id (device_id)',
        'INDEX idx_expires_at (expires_at)',
        'INDEX idx_revoked (revoked)',
    ];

    $db->createTableIfNotExists(
        'refresh_tokens',
        $columns,
        'id',        // Primary key
        $indexes     // Indexes
    );
}


    /**
     * Automatically clean up old or expired tokens.
     */
    protected function cleanupExpiredTokens(): void
    {
        try {
            $pdo = DBManager::getInstance($this->dbConfig);
            $now = date('Y-m-d H:i:s');
            $deleted = $pdo->delete(
                "DELETE FROM refresh_tokens WHERE revoked = 1 OR expires_at < ?",
                's',
                [$now]
            );

            if ($deleted > 0) {
                $this->logger->info("Auto-cleaned $deleted expired/old refresh tokens");
            }
        } catch (PDOException $e) {
            $this->logger->warning('Failed to auto-clean refresh tokens', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create access + refresh tokens for user.
     */
    public function issueTokens(string $userId, ?string $deviceId = null, ?string $ip = null, ?string $ua = null): array
    {
        $now = time();

        // Access token (JWT)
        $accessPayload = [
            'iat' => $now,
            'exp' => $now + $this->accessTtl,
            'sub' => $userId,
            'type' => 'access'
        ];
        $accessToken = JWT::encode($accessPayload, $this->jwtSecret, $this->algo);

        // Refresh token (stored hashed)
        $refresh = Crypto::randomString(32);
        $refreshHash = password_hash($refresh, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', $now + $this->refreshTtl);
        $id = uniqid('rt_', true);

        $pdo = DBManager::getInstance($this->dbConfig);
        $pdo->insert(
            "INSERT INTO refresh_tokens (id, user_id, device_id, token_hash, expires_at, ip, user_agent)
             VALUES (?,?,?,?,?,?,?)",
            'sssssss',
            [$id, $userId, $deviceId, $refreshHash, $expiresAt, $ip, $ua]
        );

        $this->logger->info("Issued refresh token", ['user_id' => $userId, 'device_id' => $deviceId]);

        return [
            'access_token'        => $accessToken,
            'access_expires_in'   => $this->accessTtl,
            'refresh_token'       => $refresh,
            'refresh_expires_at'  => $expiresAt
        ];
    }

    /**
     * Validate access token.
     */
    public function validateAccessToken(string $token): object
    {
        return JWT::decode($token, new Key($this->jwtSecret, $this->algo));
    }

    /**
     * Rotate refresh token (issue new).
     */
    public function refresh(string $refreshToken, ?string $deviceId = null, ?string $ip = null, ?string $ua = null): array
    {
        $pdo = DBManager::getInstance($this->dbConfig);
        $rows = $pdo->select(
            "SELECT * FROM refresh_tokens
             WHERE revoked = 0 
               AND (device_id = ? OR ? IS NULL)
             ORDER BY created_at DESC LIMIT 50",
            'ss',
            [$deviceId, $deviceId]
        );

        foreach ($rows as $row) {
            if (password_verify($refreshToken, $row['token_hash'])) {
                if (strtotime($row['expires_at']) < time()) {
                    throw new \RuntimeException('Refresh token expired');
                }

                $userId = $row['user_id'];
                $pdo->update("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?", 's', [$row['id']]);
                $this->logger->info("Refresh token used and revoked", ['user_id' => $userId, 'refresh_id' => $row['id']]);

                return $this->issueTokens($userId, $deviceId, $ip, $ua);
            }
        }

        throw new \RuntimeException('Invalid refresh token');
    }

    /**
     * Revoke all refresh tokens for a user.
     */
    public function revokeAllForUser(string $userId): void
    {
        $pdo = DBManager::getInstance($this->dbConfig);
        $pdo->update("UPDATE refresh_tokens SET revoked = 1 WHERE user_id = ? AND revoked = 0", 's', [$userId]);
        $this->logger->info("Revoked all refresh tokens for user", ['user_id' => $userId]);
    }

    /**
     * Revoke all tokens for a specific device.
     */
    public function revokeDevice(string $userId, string $deviceId): void
    {
        $pdo = DBManager::getInstance($this->dbConfig);
        $pdo->update(
            "UPDATE refresh_tokens SET revoked = 1 WHERE user_id = ? AND device_id = ? AND revoked = 0",
            'ss',
            [$userId, $deviceId]
        );
        $this->logger->info("Revoked refresh tokens for device", ['user_id' => $userId, 'device_id' => $deviceId]);
    }

    /**
     * Revoke by token ID.
     */
    public function revokeById(string $id): void
    {
        $pdo = DBManager::getInstance($this->dbConfig);
        $pdo->update("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?", 's', [$id]);
    }
}
