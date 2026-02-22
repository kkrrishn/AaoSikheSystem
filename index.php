<?php

declare(strict_types=1);
use AaoSikheSystem\helper\PathManager;
use AaoSikheSystem\helper\SettingHelper;
use AaoSikheSystem\view\helper\UrlHelper;
use AaoSikheSystem\Security\SecurityManager;


define('BASE_PATH', realpath(__DIR__));

if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
    $uri = 'https://';
} else {
    $uri = 'http://'; 
}
$uri .= $_SERVER['HTTP_HOST'];
define("BASE_URI", $uri.'/as/');
/**
 * AaoSikheSystem Secure - Front Controller
 * 
 * @package AaoSikheSystem
 */

// Load bootstrap
require_once __DIR__ . '/core/include/autoload.php';
require_once __DIR__ . '/core/include/bootstrap.php';
$start = microtime(true);
// Load configuration
$appConfig = require_once __DIR__ . '/core/config/app.php';
$dbConfig = require_once __DIR__ . '/core/config/databases.php';

\AaoSikheSystem\helper\FeatureManager::init($appConfig);
\AaoSikheSystem\helper\PageSpeed::start();
$db = \AaoSikheSystem\db\DBManager::getInstance($dbConfig);
PathManager::load($appConfig);
PathManager::defineConstants();
SecurityManager::apply();
$s= new SettingHelper($db);
$s->load();


// Initialize router
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$router = new \AaoSikheSystem\router\Router($basePath );

//Image protection
$router->get('/image/view', 'ImageController@view', 'media.view');
$router->get('/captcha', 'CaptchaController@image', 'captcha');

require_once __DIR__.'/routers/path.home.php';


// API routes
$router->group('/api', function($router) {
    $router->get('/users', 'ApiController@getUsers', 'api.users');
    $router->post('/users', 'ApiController@createUser', 'api.users.create');
    $router->get('/data', 'ApiController@getData', 'api.data');
});
UrlHelper::bindRouter($router);
// Dispatch request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$currentRouteName = null;



// Remove query string
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

try {
    $router->dispatch($method, $uri);
    

} catch (Exception $e) {
    \AaoSikheSystem\Error\ExceptionHandler::handleException($e);
}

// ✅ Register PageSpeed display only for NON-API (view) routes

// if (!preg_match('#/api/#', $_SERVER['REQUEST_URI'] ?? '')) {
//     register_shutdown_function(function () {
//         \AaoSikheSystem\helper\PageSpeed::displayInFooter();
//         \AaoSikheSystem\helper\PageSpeed::logPerformance();
//     });
// }

if (
    !preg_match('#/(api|ajax)/#i', $uri) &&
    (
        empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
    )
) {
    register_shutdown_function(function () {
        \AaoSikheSystem\helper\PageSpeed::displayInFooter();
        \AaoSikheSystem\helper\PageSpeed::logPerformance();
    });
}

