<?php

declare(strict_types=1);

namespace AaoSikheSystem\view\helper;

/**
 * AaoSikheSystem Secure - AJAX View Helper
 * 
 * @package AaoSikheSystem
 */

class AjaxHelper
{
    public static function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'status' => $statusCode < 400 ? 'success' : 'error',
            'data' => $data,
            'success'=>$statusCode < 400 ? true : false,
            'timestamp' => time()
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    public static function validateAjaxRequest(): bool
    {
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            return false;
        }
        
        return \AaoSikheSystem\Security\SecurityHelper::validateRequest();
    }
    
    public static function Error(string $message, int $statusCode = 400, array $additional = []): void
    {
        $response = array_merge([
            'error' => $message,
            'code' => $statusCode
        ], $additional);
        
        self::jsonResponse($response, $statusCode);
    }
    
    public static function Success(array $data = [], string $message = ''): void
    {
        $response = $data;
        if ($message) {
            $response['message'] = $message;
        }
        
        self::jsonResponse($response, 200);
    }
}