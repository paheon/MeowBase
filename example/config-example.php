<?php
/**
 * Config Example
 * 
 * This example demonstrates how to use the Config class to manage
 * application configuration with hierarchical structure and path-based access.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.1
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "Config Example".$br;
echo "==========================================".$br.$br;

// Example 1: Basic Configuration Setup
echo "Example 1: Basic Configuration Setup".$br;
echo "--------------------------------".$br;

// Create config with local settings
$localSetting = [
    "general" => [
        "sessionName" => "myApp",
        "timeZone" => "Asia/Hong_Kong",
    ],
    "log" => [
        "enable" => true,
        "level" => "DEBUG",
    ]
];

$config = new Config($localSetting);

echo "Configuration created with local settings".$br;
echo "Session name: ".$config->getConfigByPath("general/sessionName").$br;
echo "Time zone: ".$config->getConfigByPath("general/timeZone").$br;
echo "Log enabled: ".var_export($config->getConfigByPath("log/enable"), true).$br.$br;

// Example 2: Reading Configuration by Path
echo "Example 2: Reading Configuration by Path".$br;
echo "--------------------------------".$br;

// Single level path
echo "Single level - log/enable: ".var_export($config->getConfigByPath("log/enable"), true).$br;

// Multi-level path
$path = "cache/adapterList/files/path";
echo "Multi-level path '$path': ".$config->getConfigByPath($path).$br;

// Deep nested path
$deepPath = "cache/adapterList/memcached/servers/main/host";
echo "Deep nested path '$deepPath': ".$config->getConfigByPath($deepPath).$br;

// Non-existent path
$invalidPath = "cache/not-exist/path";
echo "Invalid path '$invalidPath': ".var_export($config->getConfigByPath($invalidPath), true).$br;
echo "Error: ".$config->lastError.$br.$br;

// Example 3: Setting Configuration by Path
echo "Example 3: Setting Configuration by Path".$br;
echo "--------------------------------".$br;

// Set single level
echo "Setting log/enable to false:".$br;
$config->setConfigByPath("log/enable", false);
echo "  log/enable: ".var_export($config->getConfigByPath("log/enable"), true).$br;

// Set back to true
$config->setConfigByPath("log/enable", true);
echo "  log/enable (after reset): ".var_export($config->getConfigByPath("log/enable"), true).$br;

// Set nested path
echo "Setting cache/lifeTime to 3600:".$br;
$config->setConfigByPath("cache/lifeTime", 3600);
echo "  cache/lifeTime: ".$config->getConfigByPath("cache/lifeTime").$br.$br;

// Example 4: Direct Array Access via configTree
echo "Example 4: Direct Array Access via configTree".$br;
echo "--------------------------------".$br;

// Note: configTree is available in MeowBase, not directly in Config
// This example shows how it would be used in MeowBase context
echo "Note: configTree is available in MeowBase class".$br;
echo "In MeowBase, you can access: \$meow->configTree['log']['path']".$br.$br;

// Example 5: Loading Configuration from File
echo "Example 5: Loading Configuration from File".$br;
echo "--------------------------------".$br;

// Load config from file (if exists)
$loadedConfig = $config->loadConfig();
echo "Configuration loaded from file: ".var_export(count($loadedConfig) > 0, true).$br;
if (count($loadedConfig) > 0) {
    echo "Loaded config keys: ".implode(", ", array_keys($loadedConfig)).$br;
}
echo $br;

// Example 6: Custom Configuration Values
echo "Example 6: Custom Configuration Values".$br;
echo "--------------------------------".$br;

// Add custom configuration
$customConfig = new Config([
    "myApp" => [
        "apiKey" => "secret-key-123",
        "endpoints" => [
            "users" => "/api/v1/users",
            "posts" => "/api/v1/posts"
        ]
    ]
]);

echo "Custom configuration:".$br;
echo "  API Key: ".$customConfig->getConfigByPath("myApp/apiKey").$br;
echo "  Users endpoint: ".$customConfig->getConfigByPath("myApp/endpoints/users").$br;
echo "  Posts endpoint: ".$customConfig->getConfigByPath("myApp/endpoints/posts").$br.$br;

// Example 7: Configuration Path Information
echo "Example 7: Configuration Path Information".$br;
echo "--------------------------------".$br;

echo "Config file path: ".$config->file.$br;
echo "Document root: ".$config->docRoot.$br;
echo "ETC path: ".$config->etcPath.$br;
echo "VAR path: ".$config->varPath.$br.$br;

// Example 8: Debug Information
echo "Example 8: Debug Information".$br;
echo "--------------------------------".$br;

$debugInfo = $config->__debugInfo();
echo "Config debug info (showing structure):".$br;
echo "  etcPath: ".$debugInfo['etcPath'].$br;
echo "  varPath: ".$debugInfo['varPath'].$br;
echo "  file: ".$debugInfo['file'].$br;
echo "  docRoot: ".$debugInfo['docRoot'].$br;
echo "  config keys: ".implode(", ", array_keys($debugInfo['config'])).$br.$br;

echo "Example completed!".$br;
