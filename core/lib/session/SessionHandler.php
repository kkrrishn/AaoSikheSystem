<?php

declare(strict_types=1);

namespace AaoSikheSystem\Session;

use AaoSikheSystem\Security\Crypto;
use AaoSikheSystem\Security\SecurityHelper;
use AaoSikheSystem\helper\PathManager;

class SessionHandler implements \SessionHandlerInterface
{
    private string $savePath;
    private bool $encryptData;
    private string $encryptionKey;
    private int $sessionTimeout;
    private bool $regenerateId;
    private array $securityConfig;

    private static ?self $instance = null;

    private function __construct(array $config = [])
    {
        $this->securityConfig = array_merge([
            'encrypt'     => false,
            'timeout'     => 7200,
            'regenerate'  => true,
            'fingerprint' => true,
            'same_site'   => 'Lax',
            'http_only'   => true,
            'secure'      => false,
        ], $config);

        $this->encryptData    = $this->securityConfig['encrypt'];
        $this->sessionTimeout = $this->securityConfig['timeout'];
        $this->regenerateId   = $this->securityConfig['regenerate'];
        $this->encryptionKey  = $this->getEncryptionKey();

        $this->setSavePath();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /* ================= SESSION START ================= */

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        session_name('SCSESSID');

        $handler = self::instance();
        session_set_save_handler($handler, true);

        session_set_cookie_params([
            'lifetime' => $handler->sessionTimeout,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $handler->securityConfig['secure'],
            'httponly' => $handler->securityConfig['http_only'],
            'samesite' => $handler->securityConfig['same_site'],
        ]);

        session_start();

        if (empty($_SESSION['_created'])) {
            session_regenerate_id(true);
        }

        $handler->validateSession();
        $handler->regenerateSessionId();
        $handler->restoreFlash();
    }

    /* ================= SAVE PATH ================= */

    private function setSavePath(): void
    {
        $this->savePath = PathManager::get(
            'sessions',
            dirname(__DIR__, 2) . '/../storage/sessions'
        );

        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0700, true);
        }

        ini_set('session.save_path', $this->savePath);
        ini_set('session.gc_probability', '0'); // disable PHP GC
        ini_set('session.gc_divisor', '100');
        ini_set('session.gc_maxlifetime', (string) $this->sessionTimeout);
    }

    /* ================= ENCRYPTION ================= */

    private function getEncryptionKey(): string
    {
        if (!$this->encryptData) {
            return str_repeat('x', 32);
        }

        $key = \AaoSikheSystem\Env::get('SESSION_ENCRYPTION_KEY');

        if ($key && str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded && strlen($decoded) === 32) {
                return $decoded;
            }
        }

        return random_bytes(32);
    }

    /* ================= HANDLER INTERFACE ================= */

    public function open(string $path, string $name): bool
    {
        $this->savePath = $path;
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $file = $this->savePath . '/sess_' . $id;
        if (!is_file($file)) {
            return '';
        }

        $data = file_get_contents($file);
        return $this->encryptData ? Crypto::decryptAes($data, $this->encryptionKey) : $data;
    }

    public function write(string $id, string $data): bool
    {
        if ($this->encryptData) {
            $data = Crypto::encryptAes($data, $this->encryptionKey);
        }

        return file_put_contents(
            $this->savePath . '/sess_' . $id,
            $data,
            LOCK_EX
        ) !== false;
    }

    public function destroy(string $id): bool
    {
        $file = $this->savePath . '/sess_' . $id;
        if (is_file($file)) {
            unlink($file);
        }
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $files = glob($this->savePath . '/sess_*') ?: [];
        $count = 0;

        foreach ($files as $file) {
            if (filemtime($file) + $max_lifetime < time()) {
                unlink($file);
                $count++;
            }
        }
        return $count;
    }

    /* ================= SECURITY ================= */

    private function validateSession(): void
    {
        if (empty($_SESSION)) {
            $this->initializeSession();
            return;
        }

        if ($this->isExpired()) {
            $this->regenerateSession(true);
            return;
        }

        if ($this->securityConfig['fingerprint'] && !$this->validateFingerprint()) {
            $this->regenerateSession(true);
        }

        $_SESSION['_last_activity'] = time();
    }

    private function initializeSession(): void
    {
        $_SESSION['_created'] = $_SESSION['_last_activity'] = time();

        if ($this->securityConfig['fingerprint']) {
            $_SESSION['_fingerprint'] = $this->generateFingerprint();
        }
    }

    private function isExpired(): bool
    {
        return (time() - ($_SESSION['_last_activity'] ?? 0)) > $this->sessionTimeout;
    }

    private function generateFingerprint(): string
    {
        return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    }

    private function validateFingerprint(): bool
    {
        return SecurityHelper::constantTimeEquals(
            $_SESSION['_fingerprint'] ?? '',
            $this->generateFingerprint()
        );
    }

    private function regenerateSessionId(): void
    {
        if (!$this->regenerateId) {
            return;
        }

        if ((time() - ($_SESSION['_last_regeneration'] ?? 0)) > 300) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
            $_SESSION['_fingerprint'] = $this->generateFingerprint();
        }
    }

    public function regenerateSession(bool $destroy = false): void
    {
        session_regenerate_id($destroy);
        $this->initializeSession();
    }

    /* ================= FLASH ================= */

    private function restoreFlash(): void
    {
        if (isset($_SESSION['_flash_keep'])) {
            $_SESSION['_flash'] = $_SESSION['_flash_keep'];
            unset($_SESSION['_flash_keep']);
        }
    }

    /* ================= UTILITIES ================= */

    public static function destroySession(): void
    {
        session_destroy();
        session_unset();

        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    public static function generateEncryptionKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }
}
