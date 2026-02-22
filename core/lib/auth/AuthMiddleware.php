<?php
namespace AaoSikheSystem\lib\auth;

use AaoSikheSystem\security\TokenManager;
use AaoSikheSystem\logger\Logger;

/**
 * Basic middleware for endpoints that require access token.
 * Example usage in your Router dispatch: call this before controller method.
 */
class AuthMiddleware
{
    protected TokenManager $tokens;
    protected Logger $logger;

    public function __construct(TokenManager $tokens, Logger $logger)
    {
        $this->tokens = $tokens;
        $this->logger = $logger;
    }

    public function requireAuth(): int
    {
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$hdr || strpos($hdr, 'Bearer ') !== 0) {
            http_response_code(401);
            throw new \RuntimeException('Unauthorized');
        }
        $jwt = substr($hdr, 7);
        try {
            $payload = $this->tokens->validateAccessToken($jwt);
            // ensure type access
            if (($payload->type ?? '') !== 'access') {
                throw new \RuntimeException('Invalid token');
            }
            return (int)$payload->sub;
        } catch (\Throwable $e) {
            $this->logger->info('Auth failed', ['error'=>$e->getMessage()]);
            http_response_code(401);
            throw $e;
        }
    }
}
