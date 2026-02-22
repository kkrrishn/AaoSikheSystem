<?php

declare(strict_types=1);

namespace AaoSikheSystem\Error;
use AaoSikheSystem\Env;
/**
 * AaoSikheSystem Secure - Exception Handler
 * 
 * @package AaoSikheSystem
 */

class ExceptionHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
    }
    
    public static function handleException(\Throwable $exception): void
    {
        $statusCode = self::getStatusCode($exception);
        $isAjax = self::isAjaxRequest();
        
        http_response_code($statusCode);
        
        if ($isAjax) {
            self::sendJsonResponse($exception, $statusCode);
        } else {
            self::sendHtmlResponse($exception, $statusCode);
        }
        
        // Log the exception
        self::logException($exception);
    }
    
    private static function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof \RuntimeException && strpos($exception->getMessage(), 'not found') !== false) {
            return 404;
        }
        
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }
        
        return 500;
    }
    
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    private static function sendJsonResponse(\Throwable $exception, int $statusCode): void
    {
        $response = [
            'status' => 'error',
            'message' => self::getErrorMessage($exception, $statusCode),
            'code' => $statusCode
        ];
        
        if (Env::get('APP_ENV') === 'development') {
            $response['debug'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    private static function sendHtmlResponse(\Throwable $exception, int $statusCode): void
    {
        $errorTemplate = self::getErrorTemplate($statusCode);
        
        if (file_exists($errorTemplate)) {
            extract([
                'exception' => $exception,
                'statusCode' => $statusCode,
                'isDev' => Env::get('APP_ENV') === 'development'
            ]);
            
            include $errorTemplate;
        } else {
            self::sendDefaultErrorPage($exception, $statusCode);
        }
    }
    
    private static function getErrorTemplate(int $statusCode): string
    {
        $templateName = match($statusCode) {
            404 => '404.php',
            403 => '403.php',
            500 => '500.php',
            default => 'error.php'
        };
        
        return __DIR__ . '/../../../../app/errors/' . $templateName;
    }
    
    private static function sendDefaultErrorPage(\Throwable $exception, int $statusCode): void
    {
        $message = self::getErrorMessage($exception, $statusCode);
        
        if (Env::get('APP_ENV') === 'development') {
            $debugInfo = "<h3>Debug Information:</h3>";
            $debugInfo .= "<p><strong>Message:</strong> {$exception->getMessage()}</p>";
            $debugInfo .= "<p><strong>File:</strong> {$exception->getFile()}</p>";
            $debugInfo .= "<p><strong>Line:</strong> {$exception->getLine()}</p>";
            $debugInfo .= "<pre>{$exception->getTraceAsString()}</pre>";
        } else {
            $debugInfo = '';
        }
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error {$statusCode}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; }
                .error-container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
                .error-code { color: #d9534f; font-size: 24px; margin-bottom: 10px; }
                .error-message { font-size: 18px; margin-bottom: 20px; }
                .debug-info { background: #f8f9fa; padding: 15px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='error-code'>Error {$statusCode}</div>
                <div class='error-message'>{$message}</div>
                {$debugInfo}
            </div>
        </body>
        </html>
        ";
    }
    
    private static function getErrorMessage(\Throwable $exception, int $statusCode): string
    {
        return match($statusCode) {
            404 => 'The requested page could not be found.',
            403 => 'You do not have permission to access this resource.',
            500 => 'An internal server error occurred.',
            default => 'An error occurred while processing your request.'
        };
    }
    
    private static function logException(\Throwable $exception): void
    {
        $logMessage = sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        $logFile = __DIR__ . '/../../../storage/logs/error.log';
       
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}