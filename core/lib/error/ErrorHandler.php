<?php

declare(strict_types=1);

namespace AaoSikheSystem\Error;

/**
 * AaoSikheSystem Secure - Error Handler
 * 
 * @package AaoSikheSystem
 */

class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
    }
    
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        
        return true;
    }
}