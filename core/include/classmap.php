<?php

declare(strict_types=1);

$projectRoot = dirname(dirname(__DIR__));

return [
    // CORE
    'AaoSikheSystem\\Session\\SessionHandler' =>
    $projectRoot . '/core/lib/session/SessionHandler.php',

    'AaoSikheSystem\\Session\\SessionManager' =>
    $projectRoot . '/core/lib/session/SessionManager.php',

    'AaoSikheSystem\\Security\\Crypto' =>
    $projectRoot . '/core/lib/security/Crypto.php',

    'AaoSikheSystem\\Security\\SecurityHelper' =>
    $projectRoot . '/core/lib/security/SecurityHelper.php',

    'AaoSikheSystem\\helper\\PathManager' =>
    $projectRoot . '/core/lib/helper/PathManager.php',

    'AaoSikheSystem\\helper\\Utility' =>
    $projectRoot . '/core/lib/helper/Utility.php',

    'AaoSikheSystem\\router\\Router' =>
    $projectRoot . '/core/lib/router/Router.php',


    'AaoSikheSystem\\helper\\FeatureManager' =>
    $projectRoot . '/core/lib/helper/FeatureManager.php',

    'AaoSikheSystem\\helper\\SettingHelper' =>
    $projectRoot . '/core/lib/helper/SettingHelper.php',

    'AaoSikheSystem\\helper\\HeaderHelper' =>
    $projectRoot . '/core/lib/helper/HeaderHelper.php',

    'AaoSikheSystem\\helper\\PageSpeed' =>
    $projectRoot . '/core/lib/helper/PageSpeed.php',

    'AaoSikheSystem\\helper\\SystemHelper' =>
    $projectRoot . '/core/lib/helper/SystemHelper.php',

    'AaoSikheSystem\\helper\\FileHelper' =>
    $projectRoot . '/core/lib/helper/FileHelper.php',


    'AaoSikheSystem\\db\\DBManager' =>
    $projectRoot . '/core/lib/db/DBManager.php',

    'AaoSikheSystem\\db\\Connection' =>
    $projectRoot . '/core/lib/db/Connection.php',

    'AaoSikheSystem\\cache\\CacheManager' =>
    $projectRoot . '/core/lib/cache/CacheManager.php',

    'AaoSikheSystem\\Security\\SecurityManager' =>
    $projectRoot . '/core/lib/security/SecurityManager.php',

    'AaoSikheSystem\\rate_limit\\RateLimiter' =>
    $projectRoot . '/core/lib/rate_limit/RateLimiter.php',

    'AaoSikheSystem\\view\View' =>
    $projectRoot . '/core/lib/view/View.php',

    'AaoSikheSystem\\view\\helper\\AssetsHelper' =>
    $projectRoot . '/core/lib/view/helper/AssetsHelper.php',


    'AaoSikheSystem\\view\\helper\\UrlHelper' =>
    $projectRoot . '/core/lib/view/helper/UrlHelper.php',

    'AaoSikheSystem\\view\\helper\AjaxHelper' =>
    $projectRoot . '/core/lib/view/helper/AjaxHelper.php',

    'AaoSikheSystem\\Security\\Captcha' =>
    $projectRoot . '/core/lib/security/Captcha.php',

    'AaoSikheSystem\\Security\\CaptchaValidator' =>
    $projectRoot . '/core/lib/security/CaptchaValidator.php',
    //  'AaoSikheSystem\\error\\ExceptionHandler' =>   AaoSikheSystem\Security\CaptchaValidator
    //         $projectRoot . '/core/lib/error/ExceptionHandler.php',

    //         'AaoSikheSystem\\error\\ErrorHandler' =>   
    //         $projectRoot . '/core/lib/error/ErrorHandler.php',
    // APP
    'App\\helpers\\MetaDataHelper' =>
    $projectRoot . '/app/helpers/MetaDataHelper.php',


    'App\\Controllers\\HomeController' =>
    $projectRoot . '/app/controllers/HomeController.php',

    'App\\Controllers\\DashboardController' =>
    $projectRoot . '/app/controllers/DashboardController.php',



];

//return [

//     // ================= CORE =================
//     'AaoSikheSystem\\Session\\SessionHandler' =>
//         dirname(__DIR__) . '/clib/session/SessionHandler.php',

//     'AaoSikheSystem\\Security\\Crypto' =>
//         dirname(__DIR__) . '/lib/security/Crypto.php',

//     'AaoSikheSystem\\Security\\SecurityHelper' =>
//         dirname(__DIR__) . '/lib/security/SecurityHelper.php',

//     'AaoSikheSystem\\helper\\PathManager' =>   
//         dirname(__DIR__) . '/lib/helper/PathManager.php',

//     'AaoSikheSystem\\helper\\FeatureManager' =>   
//         dirname(__DIR__) . '/lib/helper/FeatureManager.php',

//         'AaoSikheSystem\\helper\\PageSpeed' =>   
//         dirname(__DIR__) . '/lib/helper/PageSpeed.php',

//     'AaoSikheSystem\\db\\DBManager' =>   
//         dirname(__DIR__) . '/lib/db/DBManager.php',

//     'AaoSikheSystem\\router\\Router' =>   
//         dirname(__DIR__) . '/core/lib/router/Router.php',

//     // ================= APP CONTROLLERS =================
//     'App\\Controllers\\HomeController' =>
//         dirname(__DIR__) . '/app/Controllers/HomeController.php',

//     // Add more app controllers here
// ];
