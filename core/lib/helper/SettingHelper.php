<?php

namespace AaoSikheSystem\helper;

use AaoSikheSystem\db\DBManager;
use RuntimeException;
use JsonException;
use InvalidArgumentException;

class SettingHelper
{
    private DBManager $db;

    /** @var array<string, mixed> */
    private static array $cache = [];

    /** @var bool */
    private static bool $isLoaded = false;

    /** @var bool */
    private bool $useCache;

    /** @var array<string, string> */
    private const TYPE_MAPPING = [
        'string'  => 'string',
        'number'  => 'int',
        'boolean' => 'bool',
        'array'   => 'array',
        'object'  => 'array'
    ];

    /** @var array<string, mixed> */
private const DEFAULT_SETTINGS = [

    /* =======================
     * SITE BASICS
     * ======================= */
    'site_name' => [
        'value' => 'AaoSikhe System',
        'type' => 'string',
        'description' => 'Website name',
        'is_public' => true
    ],
    'site_tagline' => [
        'value' => 'Learn Anything, Anytime',
        'type' => 'string',
        'description' => 'Website tagline',
        'is_public' => true
    ],
    'site_logo' => [
        'value' => '/assets/images/logo.png',
        'type' => 'string',
        'description' => 'Site logo path',
        'is_public' => true
    ],
    'site_favicon' => [
        'value' => '/assets/images/favicon.ico',
        'type' => 'string',
        'description' => 'Site favicon',
        'is_public' => true
    ],
    'site_language' => [
        'value' => 'en',
        'type' => 'string',
        'description' => 'Default site language',
        'is_public' => true
    ],
    'site_timezone' => [
        'value' => 'Asia/Kolkata',
        'type' => 'string',
        'description' => 'Default timezone',
        'is_public' => false
    ],

    /* =======================
     * USER & AUTH
     * ======================= */
    'user_registration' => [
        'value' => true,
        'type' => 'boolean',
        'description' => 'Allow user registration',
        'is_public' => false
    ],
    'email_verification_required' => [
        'value' => true,
        'type' => 'boolean',
        'description' => 'Email verification on signup',
        'is_public' => false
    ],
    'max_login_attempts' => [
        'value' => 5,
        'type' => 'number',
        'description' => 'Max failed login attempts',
        'is_public' => false
    ],
    'account_lock_time' => [
        'value' => 900,
        'type' => 'number',
        'description' => 'Account lock time (seconds)',
        'is_public' => false
    ],
    'password_min_length' => [
        'value' => 8,
        'type' => 'number',
        'description' => 'Minimum password length',
        'is_public' => false
    ],

    /* =======================
     * COURSES
     * ======================= */
    'course_approval_required' => [
        'value' => true,
        'type' => 'boolean',
        'description' => 'Admin approval required for courses',
        'is_public' => false
    ],
    'max_courses_per_instructor' => [
        'value' => 50,
        'type' => 'number',
        'description' => 'Max courses per instructor',
        'is_public' => false
    ],
    'default_course_visibility' => [
        'value' => 'public',
        'type' => 'string',
        'description' => 'Default course visibility',
        'is_public' => false
    ],
    'course_review_enabled' => [
        'value' => true,
        'type' => 'boolean',
        'description' => 'Enable course reviews',
        'is_public' => true
    ],
    'course_rating_min' => [
        'value' => 1,
        'type' => 'number',
        'description' => 'Minimum rating allowed',
        'is_public' => false
    ],
    'free_course_enabled' => [
        'value' => true,
        'type' => 'boolean',
        'description' => 'Allow free courses',
        'is_public' => true
    ],

    /* =======================
     * PAYMENTS
     * ======================= */
    'currency' => [
        'value' => 'INR',
        'type' => 'string',
        'description' => 'Default currency',
        'is_public' => true
    ],
    'currency_symbol' => [
        'value' => '₹',
        'type' => 'string',
        'description' => 'Currency symbol',
        'is_public' => true
    ],
    'tax_percentage' => [
        'value' => 18,
        'type' => 'number',
        'description' => 'GST / Tax percentage',
        'is_public' => false
    ],

    /* =======================
     * FILE UPLOADS
     * ======================= */
    'max_upload_size' => [
        'value' => 10485760,
        'type' => 'number',
        'description' => 'Max upload size in bytes (10MB)',
        'is_public' => false
    ],
    'allowed_file_types' => [
        'value' => ['jpg','jpeg','png','pdf','mp4'],
        'type' => 'array',
        'description' => 'Allowed upload file types',
        'is_public' => false
    ],
    'upload_path' => [
        'value' => '/storage/uploads',
        'type' => 'string',
        'description' => 'Upload directory path',
        'is_public' => false
    ],

    /* =======================
     * SECURITY
     * ======================= */
    'enable_csrf' => [
        'value' => true,
        'type' => 'boolean',
        'description' => 'Enable CSRF protection',
        'is_public' => false
    ],
    'enable_xss_filter' => [
        'value' => true,
        'type' => 'boolean',
        'description' => 'Enable XSS filtering',
        'is_public' => false
    ],
    'jwt_expiry_time' => [
        'value' => 3600,
        'type' => 'number',
        'description' => 'JWT token expiry (seconds)',
        'is_public' => false
    ],

    /* =======================
     * EMAIL
     * ======================= */
    'email_from_address' => [
        'value' => 'no-reply@aaosikhe.com',
        'type' => 'string',
        'description' => 'Default sender email',
        'is_public' => false
    ],
    'email_from_name' => [
        'value' => 'AaoSikhe Support',
        'type' => 'string',
        'description' => 'Sender name',
        'is_public' => false
    ],
    'smtp_enabled' => [
        'value' => false,
        'type' => 'boolean',
        'description' => 'Enable SMTP',
        'is_public' => false
    ],

    /* =======================
     * SYSTEM
     * ======================= */
    'maintenance_mode' => [
        'value' => false,
        'type' => 'boolean',
        'description' => 'Enable maintenance mode',
        'is_public' => true
    ],
    'maintenance_message' => [
        'value' => 'System under maintenance. Please try later.',
        'type' => 'string',
        'description' => 'Maintenance notice',
        'is_public' => true
    ],
    'debug_mode' => [
        'value' => false,
        'type' => 'boolean',
        'description' => 'Enable debug mode',
        'is_public' => false
    ],

    /* =======================
     * API & FEATURES
     * ======================= */
    'api_rate_limit' => [
        'value' => [
            'requests' => 100,
            'per_seconds' => 60
        ],
        'type' => 'object',
        'description' => 'API rate limiting rules',
        'is_public' => false
    ],
    'feature_flags' => [
        'value' => [
            'chat' => true,
            'ai' => false,
            'certificates' => true
        ],
        'type' => 'object',
        'description' => 'Feature toggle flags',
        'is_public' => false
    ],
    'ui_preferences' => [
        'value' => [
            'theme' => 'light',
            'sidebar' => 'expanded'
        ],
        'type' => 'object',
        'description' => 'Default UI preferences',
        'is_public' => true
    ],
];


    public function __construct(DBManager $db, bool $useCache = true)
    {
        $this->db = $db;
        $this->useCache = $useCache;
    }

    /* ===============================
     * CACHE MANAGEMENT
     * =============================== */

    /**
     * Load all settings into cache
     */
    private function loadCache(): void
    {
        if (self::$isLoaded || !$this->useCache) {
            return;
        }

        if (!$this->db->healthCheck()) {
            throw new RuntimeException('Database connection failed');
        }

        try {
            $rows = $this->db->select(
                "SELECT setting_key, setting_value, setting_type 
                 FROM settings 
                 WHERE 1"
            );

            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $this->castValue(
                    $row['setting_value'],
                    $row['setting_type']
                );
            }

            self::$isLoaded = true;
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to load settings: ' . $e->getMessage());
        }
    }

    public function load(){
            $data = self::all();
            foreach ($data??[] as $key => $value) {
                define(strtoupper($key),$value);
            }
          
    }
    /**
     * Clear settings cache
     */
    public function clearCache(): void
    {
        self::$cache = [];
        self::$isLoaded = false;
    }

    /**
     * Reload settings from database
     */
    public function reload(): void
    {
        $this->clearCache();
        $this->loadCache();
    }

    /* ===============================
     * CRUD OPERATIONS
     * =============================== */

    /**
     * Get a setting value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->useCache) {
            $this->loadCache();
            return self::$cache[$key] ?? $default;
        }

        // Direct database fetch without cache
        try {
            $row = $this->db->selectOne(
                "SELECT setting_value, setting_type 
                 FROM settings 
                 WHERE setting_key = ?
                 LIMIT 1",
                "s",
                [$key]
            );

            return $row ? $this->castValue($row['setting_value'], $row['setting_type']) : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Get multiple settings at once
     * 
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        
        if ($this->useCache) {
            $this->loadCache();
            foreach ($keys as $key) {
                $result[$key] = self::$cache[$key] ?? $default;
            }
            return $result;
        }

        // Database fetch for multiple keys
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $types = str_repeat('s', count($keys));
        
        try {
            $rows = $this->db->select(
                "SELECT setting_key, setting_value, setting_type 
                 FROM settings 
                 WHERE setting_key IN ($placeholders) AND deleted_at IS NULL",
                $types,
                $keys
            );

            $dbResults = [];
            foreach ($rows as $row) {
                $dbResults[$row['setting_key']] = $this->castValue(
                    $row['setting_value'],
                    $row['setting_type']
                );
            }

            foreach ($keys as $key) {
                $result[$key] = $dbResults[$key] ?? $default;
            }
        } catch (\Exception $e) {
            foreach ($keys as $key) {
                $result[$key] = $default;
            }
        }

        return $result;
    }

    /**
     * Set or update a setting
     */
    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        bool $isPublic = false,
        ?string $description = null,
        ?string $group = null
    ): bool {
        if (!array_key_exists($type, self::TYPE_MAPPING)) {
            throw new InvalidArgumentException("Invalid setting type: $type");
        }

        $now = time();
        $storedValue = $this->prepareValue($value, $type);
        
        if (empty($description)) {
            $description = self::DEFAULT_SETTINGS[$key]['description'] ?? null;
        }

        try {
            $exists = $this->db->selectOne(
                "SELECT id FROM settings WHERE setting_key = ? LIMIT 1",
                "s",
                [$key]
            );

            if ($exists) {
                $this->db->update(
                    "UPDATE settings SET 
                        setting_value = ?, 
                        setting_type = ?, 
                        is_public = ?, 
                        description = ?, 
                        setting_group = ?,
                        updated_at = ?
                     WHERE setting_key = ?",
                    "ssisssi",
                    [
                        $storedValue, 
                        $type, 
                        (int)$isPublic, 
                        $description, 
                        $group,
                        $now, 
                        $key
                    ]
                );
            } else {
                $id = $this->generateSettingId();
                
                $this->db->insert(
                    "INSERT INTO settings 
                    (id, setting_key, setting_value, setting_type, description, 
                     is_public, setting_group, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    "sssssiiss",
                    [
                        $id, 
                        $key, 
                        $storedValue, 
                        $type, 
                        $description,
                        (int)$isPublic, 
                        $group,
                        $now, 
                        $now
                    ]
                );
            }

            // Update cache
            if ($this->useCache) {
                self::$cache[$key] = $value;
            }

            return true;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to set setting '$key': " . $e->getMessage());
        }
    }

    /**
     * Delete a setting (soft delete)
     */
    public function delete(string $key): bool
    {
        try {
            $this->db->update(
                "UPDATE settings SET deleted_at = ? WHERE setting_key = ?",
                "is",
                [time(), $key]
            );

            // Remove from cache
            if ($this->useCache && isset(self::$cache[$key])) {
                unset(self::$cache[$key]);
            }

            return true;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to delete setting '$key': " . $e->getMessage());
        }
    }

    /**
     * Permanently remove a setting
     */
    public function forceDelete(string $key): bool
    {
        try {
            $this->db->delete(
                "DELETE FROM settings WHERE setting_key = ?",
                "s",
                [$key]
            );

            // Remove from cache
            if ($this->useCache && isset(self::$cache[$key])) {
                unset(self::$cache[$key]);
            }

            return true;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to force delete setting '$key': " . $e->getMessage());
        }
    }

    /* ===============================
     * BULK OPERATIONS
     * =============================== */

    /**
     * Set multiple settings at once
     */
    public function setMultiple(array $settings): bool
    {
        try {
            $this->db->beginTransaction();

            foreach ($settings as $key => $setting) {
                if (!is_array($setting)) {
                    $setting = ['value' => $setting];
                }

                $this->set(
                    $key,
                    $setting['value'] ?? null,
                    $setting['type'] ?? 'string',
                    $setting['is_public'] ?? false,
                    $setting['description'] ?? null,
                    $setting['group'] ?? null
                );
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new RuntimeException("Failed to set multiple settings: " . $e->getMessage());
        }
    }

    /* ===============================
     * QUERY METHODS
     * =============================== */

    /**
     * Get all settings
     */
    public function all(bool $includeDeleted = false): array
    {
        if ($this->useCache && !$includeDeleted) {
            $this->loadCache();
            return self::$cache;
        }

        $whereClause = $includeDeleted ? '' : 'WHERE deleted_at IS NULL';
        
        $rows = $this->db->select(
            "SELECT setting_key, setting_value, setting_type 
             FROM settings $whereClause"
        );

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $this->castValue(
                $row['setting_value'],
                $row['setting_type']
            );
        }

        return $settings;
    }

    /**
     * Get public settings
     */
    public function public(): array
    {
        $rows = $this->db->select(
            "SELECT setting_key, setting_value, setting_type 
             FROM settings 
             WHERE is_public = 1 AND deleted_at IS NULL"
        );

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $this->castValue(
                $row['setting_value'],
                $row['setting_type']
            );
        }

        return $settings;
    }

    /**
     * Get settings by group
     */
    public function getByGroup(string $group): array
    {
        $rows = $this->db->select(
            "SELECT setting_key, setting_value, setting_type 
             FROM settings 
             WHERE setting_group = ? AND deleted_at IS NULL",
            "s",
            [$group]
        );

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $this->castValue(
                $row['setting_value'],
                $row['setting_type']
            );
        }

        return $settings;
    }

    /**
     * Search settings by key or description
     */
    public function search(string $query): array
    {
        $rows = $this->db->select(
            "SELECT setting_key, setting_value, setting_type 
             FROM settings 
             WHERE (setting_key LIKE ? OR description LIKE ?) 
               AND deleted_at IS NULL",
            "ss",
            ["%$query%", "%$query%"]
        );

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $this->castValue(
                $row['setting_value'],
                $row['setting_type']
            );
        }

        return $settings;
    }

    /**
     * Check if a setting exists
     */
    public function has(string $key): bool
    {
        if ($this->useCache) {
            $this->loadCache();
            return isset(self::$cache[$key]);
        }

        try {
            $result = $this->db->selectOne(
                "SELECT 1 FROM settings 
                 WHERE setting_key = ? 
                 LIMIT 1",
                "s",
                [$key]
            );
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    /* ===============================
     * VALUE CONVERSION
     * =============================== */

    /**
     * Cast database value to proper PHP type
     */
    private function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return match ($type) {
                'number'  => 0,
                'boolean' => false,
                'array', 'object' => [],
                default   => null,
            };
        }

        return match ($type) {
            'number'  => is_numeric($value) ? (int)$value : 0,
            'boolean' => (bool)(int)$value,
            'array', 'object' => $this->decodeJson($value),
            default   => $value,
        };
    }

    /**
     * Prepare value for database storage
     */
    private function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'array', 'object' => $this->encodeJson($value),
            'boolean' => (string)(int)(bool)$value,
            'number'  => (string)(int)$value,
            default => (string)$value,
        };
    }

    /**
     * JSON encode with error handling
     */
    private function encodeJson(mixed $value): string
    {
        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to encode JSON: ' . $e->getMessage());
        }
    }

    /**
     * JSON decode with error handling
     */
    private function decodeJson(string $json): array
    {
        try {
            $result = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($result) ? $result : [];
        } catch (JsonException $e) {
            return [];
        }
    }

    /* ===============================
     * INSTALLATION & SETUP
     * =============================== */

    /**
     * Initialize settings table
     */
    public static function install(DBManager $db): void
    {
        if ($db->tableExists('settings')) {
            return;
        }

        $db->createTableIfNotExists(
            'settings',
            [
                'id'            => 'VARCHAR(36) NOT NULL',
                'setting_key'   => 'VARCHAR(255) NOT NULL',
                'setting_value' => 'LONGTEXT',
                'setting_type'  => "ENUM('string','number','boolean','array','object') NOT NULL",
                'description'   => 'TEXT',
                'is_public'     => 'TINYINT(1) DEFAULT 0',
                'setting_group' => 'VARCHAR(100)',
                'created_at'    => 'BIGINT NOT NULL',
                'updated_at'    => 'BIGINT NOT NULL',
                'deleted_at'    => 'BIGINT',
            ],
            'id',
            [
                'UNIQUE KEY uk_setting_key (setting_key)',
                'KEY idx_is_public (is_public)',
                'KEY idx_setting_group (setting_group)',
                'KEY idx_deleted_at (deleted_at)'
            ]
        );

        self::seedDefaults($db);
    }

    /**
     * Seed default settings
     */
    private static function seedDefaults(DBManager $db): void
    {
        $now = time();
        $helper = new self($db, false);

        foreach (self::DEFAULT_SETTINGS as $key => $config) {
            $helper->set(
                $key,
                $config['value'],
                $config['type'],
                $config['is_public'],
                $config['description']
            );
        }
    }

    /**
     * Reset to default settings
     */
    public function resetToDefaults(): void
    {
        try {
            $this->db->beginTransaction();
            
            // Delete all existing settings
            $this->db->delete("DELETE FROM settings");
            
            // Reset cache
            $this->clearCache();
            
            // Seed defaults
            self::seedDefaults($this->db);
            
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new RuntimeException("Failed to reset settings: " . $e->getMessage());
        }
    }

    /* ===============================
     * UTILITY METHODS
     * =============================== */

    /**
     * Generate unique setting ID
     */
    private function generateSettingId(): string
    {
        return 'stg_' . bin2hex(random_bytes(8)) . '_' . time();
    }

    /**
     * Get cache status
     */
    public function getCacheStatus(): array
    {
        return [
            'is_loaded' => self::$isLoaded,
            'cache_size' => count(self::$cache),
            'cache_keys' => array_keys(self::$cache),
            'use_cache' => $this->useCache
        ];
    }

    /**
     * Validate setting type
     */
    public function validateType(string $type): bool
    {
        return array_key_exists($type, self::TYPE_MAPPING);
    }

    /**
     * Get supported types
     */
    public function getSupportedTypes(): array
    {
        return array_keys(self::TYPE_MAPPING);
    }
}