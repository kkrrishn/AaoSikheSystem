<?php

declare(strict_types=1);
use AaoSikheSystem\Env;
use AaoSikheSystem\security\TokenManager;


/**
 * AaoSikheSystem Secure - Bootstrap
 * 
 * @package AaoSikheSystem
 */

// Load environment
require_once __DIR__ . '/env.php';

Env::load();

// Set error reporting based on environment
if (Env::get('APP_ENV', 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// Set default timezone
date_default_timezone_set(Env::get('APP_TIMEZONE', 'UTC'));

// Initialize error handling
require_once __DIR__ . '/../lib/error/ErrorHandler.php';
require_once __DIR__ . '/../lib/error/ExceptionHandler.php';

AaoSikheSystem\Error\ErrorHandler::register();
AaoSikheSystem\Error\ExceptionHandler::register();

// Initialize session with secure settings
require_once __DIR__ . '/../lib/session/SessionHandler.php';

AaoSikheSystem\Session\SessionHandler::start();

// Initialize TokenManager (example)

$tokenConfig = [
    'jwt_secret' => getenv('JWT_SECRET') ?: 'frgYS4&5fd1*4dfkuj68h6gr9@764%l42dfr',
    'access_ttl' => 900,
    'refresh_ttl' => 60*60*24*30
];



//  $tokenManager = new TokenManager($tokenConfig, new AaoSikheSystem\logger\Logger());

// // // // Make $tokenManager available globally (simple)
//  $GLOBALS['tokenManager'] = $tokenManager;

