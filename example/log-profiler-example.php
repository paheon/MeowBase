<?php
/**
 * Log and Profiler Example
 * 
 * This example demonstrates how to use SysLog for logging and Profiler
 * for performance measurement.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\SysLog;
use Paheon\MeowBase\Profiler;
use Psr\Log\LogLevel;

// Profiler will read this global variable for the application start time
$prgStartTime = microtime(true);

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "Log and Profiler Example".$br;
echo "==========================================".$br.$br;

// Example 1: Basic Logging
echo "Example 1: Basic Logging".$br;
echo "--------------------------------".$br;

$config = new Config();
$log = new SysLog(
    $config->getConfigByPath("log/path"),
    $config->getConfigByPath("log/level"),
    $config->getConfigByPath("log/option")
);
$log->enable = $config->getConfigByPath("log/enable");

echo "Logger initialized".$br;
echo "Log path: ".$log->getLogFilePath().$br;
echo "Log threshold: ".$log->threshold.$br;
echo "Log enabled: ".var_export($log->enable, true).$br.$br;

// Write different log levels
echo "Writing log messages:".$br;
$log->sysLog("This is a debug message", ['data' => 'test'], LogLevel::DEBUG);
echo "  - Debug message written".$br;

$log->sysLog("This is an info message", ['action' => 'user_login'], LogLevel::INFO);
echo "  - Info message written".$br;

$log->sysLog("This is a warning message", ['warning' => 'low_memory'], LogLevel::WARNING);
echo "  - Warning message written".$br;

$log->sysLog("This is an error message", ['error_code' => 500], LogLevel::ERROR);
echo "  - Error message written".$br.$br;

// Example 2: Stack Tracking
echo "Example 2: Stack Tracking".$br;
echo "--------------------------------".$br;

function functionA($log, $br) {
    echo "  functionA called".$br;
    $log->sysLog("Inside functionA", null, LogLevel::DEBUG);
    functionB($log, $br);
}

function functionB($log, $br) {
    echo "  functionB called".$br;
    $log->sysLog("Inside functionB", null, LogLevel::DEBUG);
}

// Without stack tracking
echo "Logging without stack tracking:".$br;
$log->stack = false;
$log->depth = 0;
functionA($log, $br);
echo $br;

// With stack tracking
echo "Logging with stack tracking:".$br;
$log->stack = true;
$log->depth = 5; // Track up to 5 levels
functionA($log, $br);
$log->stack = false;
echo $br;

// Example 3: Profiler - Basic Usage
echo "Example 3: Profiler - Basic Usage".$br;
echo "--------------------------------".$br;

$profiler = new Profiler();
echo "Profiler initialized".$br;

// Record some operations
$profiler->record("Example Start");
usleep(100000); // Simulate some work (100ms)

$profiler->record("After First Operation");
usleep(50000); // 50ms

$profiler->record("After Second Operation");
usleep(30000); // 30ms

$profiler->record("Example End");

// Generate report
echo "Performance Report:".$br;
echo $profiler->report($isWeb);
echo $br;

// Example 4: Profiler - Grouped Operations
echo "Example 4: Profiler - Grouped Operations".$br;
echo "--------------------------------".$br;

$profiler2 = new Profiler();
$profiler2->record("Database Query Start", "database");
usleep(200000); // Simulate database query
$profiler2->record("Database Query End", "database");

$profiler2->record("Cache Operation Start", "cache");
usleep(50000); // Simulate cache operation
$profiler2->record("Cache Operation End", "cache");

$profiler2->record("Total Operation", "all");

echo "Grouped Performance Report:".$br;
echo $profiler2->report($isWeb);
echo $br;

// Example 5: Log Threshold Management
echo "Example 5: Log Threshold Management".$br;
echo "--------------------------------".$br;

echo "Current log threshold: ".$log->getThreshold().$br;

// Change log threshold
$log->setThreshold(LogLevel::WARNING);
echo "Changed log threshold to: ".$log->getThreshold().$br;

// Now only warning and error messages will be logged
$log->sysLog("This debug message won't be logged", null, LogLevel::DEBUG);
$log->sysLog("This warning will be logged", null, LogLevel::WARNING);

// Reset to DEBUG
$log->setThreshold(LogLevel::DEBUG);
echo "Reset log threshold to: ".$log->getThreshold().$br.$br;

// Example 6: Enable/Disable Logging
echo "Example 6: Enable/Disable Logging".$br;
echo "--------------------------------".$br;

echo "Logging enabled: ".var_export($log->enable, true).$br;

// Disable logging
$log->enable = false;
echo "Disabled logging".$br;
$log->sysLog("This won't be logged", null, LogLevel::INFO);
echo "  (No log written)".$br;

// Re-enable logging
$log->enable = true;
echo "Re-enabled logging".$br;
$log->sysLog("This will be logged", null, LogLevel::INFO);
echo "  (Log written)".$br.$br;

// Example 7: Profiler with Custom Zero Padding
echo "Example 7: Profiler with Custom Zero Padding".$br;
echo "--------------------------------".$br;

$profiler3 = new Profiler(3); // Zero pad to 3 digits
$profiler3->record("First");
$profiler3->record("Second");
$profiler3->record("Third");

echo "Profiler with 3-digit zero padding:".$br;
echo $profiler3->report($isWeb);
echo $br;

// Example 8: Debug Information
echo "Example 8: Debug Information".$br;
echo "--------------------------------".$br;

$logDebug = $log->__debugInfo();
echo "Log debug info:".$br;
echo "  enable: ".var_export($logDebug['enable'], true).$br;
echo "  stack: ".var_export($logDebug['stack'], true).$br;
echo "  depth: ".$logDebug['depth'].$br.$br;

$profilerDebug = $profiler->__debugInfo();
echo "Profiler debug info:".$br;
echo "  serial: ".$profilerDebug['serial'].$br;
echo "  timeRec groups: ".implode(", ", array_keys($profilerDebug['timeRec'])).$br;
echo "  zeroPad: ".$profilerDebug['zeroPad'].$br.$br;

echo "Example completed!".$br;
