<?php
/**
 * autoload-simple-example.php - Simple Dynamic Autoload Usage Example
 * 
 * This file demonstrates how to dynamically add autoload rules in your PHP program.
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Tools\Autoload;

// ============================================
// Example 1: Add new namespace mapping
// ============================================

// Assuming "MyApp\\Controllers\\" is not configured in composer.json
// We can dynamically add it in the program
Autoload::addPsr4('MyApp\\Controllers\\', __DIR__ . '/../app/controllers/');

// Now you can use it directly without manual require
// $userController = new \MyApp\Controllers\UserController();

// ============================================
// Example 2: Use relative path (auto-converted)
// ============================================

// Using relative path, Autoload will automatically find the project root
Autoload::addNamespace('MyApp\\Models\\', 'app/models/');

// ============================================
// Example 3: Load autoload rules from config file or database
// ============================================

// For example, load from config file
$autoloadRules = [
    'MyApp\\Services\\' => 'app/services/',
    'MyApp\\Helpers\\' => 'app/helpers/',
];

foreach ($autoloadRules as $namespace => $path) {
    Autoload::addNamespace($namespace, $path);
}

// ============================================
// Example 4: Directly use Composer ClassLoader
// ============================================

$loader = Autoload::getComposerLoader();
if ($loader) {
    // Add PSR-4 mapping
    $loader->addPsr4('Vendor\\Package\\', __DIR__ . '/../custom/vendor-package/src/');
    
    // Add class map (direct mapping of class name to file)
    $loader->addClassMap([
        'MyCustomClass' => __DIR__ . '/../classes/MyCustomClass.php',
    ]);
}

echo "Autoload configuration completed!\n";
echo "You can now use the newly added namespaces.\n";
