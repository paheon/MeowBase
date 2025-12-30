<?php
/**
 * minstart.php - MeowBase Minimal Start Script
 * 
 * Start the MeowBase application with minimal code.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 */
use Paheon\MeowBase\Config;
use Paheon\MeowBase\MeowBase;

// Profiler will read this global variable for the application start time, 
//   so it should be run at the beginning of the application
$prgStartTime = microtime(true);    

require(__DIR__.'/vendor/autoload.php');

// Load config //
$config = new Config();

// Run MeowBase //
$meow = new MeowBase($config);

echo "DONE!";