<?php
declare(strict_types=1);

$classMap = require __DIR__ . '/classmap.php';

spl_autoload_register(
    function (string $class) use ($classMap): void {

        // ---------------- FASTEST: CLASSMAP ----------------
        if (isset($classMap[$class])) {
            require $classMap[$class];
            return;
        }

        // ---------------- PSR-4 FALLBACK ----------------
        $projectRoot = dirname(dirname(__DIR__)); // go from core/include to project root

        // ----- CORE -----
        $corePrefix = 'AaoSikheSystem\\';
        $coreBaseDir = $projectRoot . '/core/';

        if (strncmp($class, $corePrefix, strlen($corePrefix)) === 0) {
            $relativeClass = substr($class, strlen($corePrefix));
            $file = $coreBaseDir
                  . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
                  . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }
        }

        // ----- APP -----
        $appPrefix = 'App\\';
        $appBaseDir = $projectRoot . '/app/';

        if (strncmp($class, $appPrefix, strlen($appPrefix)) === 0) {
            $relativeClass = substr($class, strlen($appPrefix));
            $file = $appBaseDir
                  . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
                  . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }
        }

    },
    true,
    true
);





// declare(strict_types=1);

// /**
//  * AaoSikheSystem Secure - PSR-4 Autoloader
//  * 
//  * @package AaoSikheSystem
//  */


// spl_autoload_register(function (string $className): void {
//     $baseDir = __DIR__ . '/../';
    
//     $prefix = 'AaoSikheSystem\\';
//     $prefixLength = strlen($prefix);

//     if (strncmp($prefix, $className, $prefixLength) !== 0) {
//         return;
//     }
    
//     $relativeClass = substr($className, $prefixLength);
 
//     // Map core classes to the core directory
//     $coreNamespaces = ['lib', 'case', 'router','logger','helper', 'view', 'security', 'session','db','rate_limit'];
    
//     // First try: Look in core/ directory structure
//     foreach ($coreNamespaces as $namespace) {
         
//         $file = $baseDir . 'core/' . $namespace . '/' . str_replace('\\', '/', $relativeClass) . '.php';
           
//         if (file_exists($file)) {

//             require_once $file;
//             return;
//         }
//     }
    
//     // Second try: Look in root directory structure (without core/)
//     foreach ($coreNamespaces as $namespace) {
     
//         $file = $baseDir . $namespace . '/' . str_replace('\\', '/', $relativeClass) . '.php';
           
//         if (file_exists($file)) {
//             require_once $file;
//             return;
//         }
//     }
    
//     // Third try: Direct path (for non-core classes)
//     $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
  
//     if (file_exists($file)) {
//         require_once $file;
//     }
// });
// // Register app namespace
// spl_autoload_register(function (string $className): void {
//     $baseDir = __DIR__ . '/../../app';
    
//     $prefix = 'App\\';
//     $prefixLength = strlen($prefix);
    
//     if (strncmp($prefix, $className, $prefixLength) !== 0) {
//         return;
//     }
    
//     $relativeClass = substr($className, $prefixLength);
//     $file = $baseDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
//     if (file_exists($file)) {
//         require_once $file;
//     }
// });