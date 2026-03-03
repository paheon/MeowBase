<?php
/**
 * autoload-usage.php - Example of dynamic autoload configuration
 * 
 * This file demonstrates how to dynamically add or modify autoload rules
 * in your PHP program.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Tools\Autoload;

// ============================================
// Method 1: Use Autoload class to add PSR-4 mapping
// ============================================

// Add new namespace mapping
// Example: Map "MyApp\\Controllers\\" to "app/controllers/" directory
Autoload::addPsr4('MyApp\\Controllers\\', __DIR__ . '/../app/controllers/');

// Add multiple paths to the same namespace
Autoload::addPsr4('MyApp\\Models\\', [
    __DIR__ . '/../app/models/',
    __DIR__ . '/../app/custom-models/'
]);

// Use relative path (automatically converted to absolute path)
Autoload::addNamespace('MyApp\\Services\\', 'app/services/');

// ============================================
// Method 2: Directly use Composer's ClassLoader
// ============================================

$loader = Autoload::getComposerLoader();
if ($loader) {
    // Add PSR-4 mapping
    $loader->addPsr4('Vendor\\Package\\', __DIR__ . '/../vendor/custom-package/src/');
    
    // Add PSR-0 mapping (legacy)
    $loader->add('OldStyle_', __DIR__ . '/../legacy/');
    
    // Add class map (direct mapping of class name to file path)
    $loader->addClassMap([
        'MyClass' => __DIR__ . '/../classes/MyClass.php',
        'AnotherClass' => __DIR__ . '/../classes/AnotherClass.php',
    ]);
}

// ============================================
// Method 3: Register custom autoload function
// ============================================

// Define a custom autoload function
$customAutoloader = function ($className) {
    // Custom class loading logic
    $file = __DIR__ . '/../custom/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
};

// Register custom autoloader (can be set to execute first)
Autoload::register($customAutoloader, true); // true = prepend (execute first)

// ============================================
// Method 4: Dynamically add autoload based on conditions
// ============================================

// Example: Dynamically add based on environment variable or config file
$environment = getenv('APP_ENV') ?: 'production';

if ($environment === 'development') {
    // Development environment: Add development tools path
    Autoload::addPsr4('DevTools\\', __DIR__ . '/../dev-tools/');
}

// Dynamically load based on config file
$autoloadConfig = [
    'MyApp\\Controllers\\' => 'app/controllers/',
    'MyApp\\Models\\' => 'app/models/',
    'MyApp\\Services\\' => 'app/services/',
];

foreach ($autoloadConfig as $namespace => $path) {
    Autoload::addNamespace($namespace, $path);
}

// ============================================
// View registered autoloaders
// ============================================

$registeredLoaders = Autoload::getRegisteredLoaders();
echo "Number of registered autoloaders: " . count($registeredLoaders) . "\n";

// ============================================
// Usage example: Now you can use the newly added namespace
// ============================================

// Assuming you have added "MyApp\\Controllers\\" mapping
// Now you can use it directly without require
// $controller = new \MyApp\Controllers\UserController();

echo "Autoload configuration completed!\n";
