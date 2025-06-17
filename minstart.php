<?php
//
// minstart.php - MeowBase Minimal Start Script
//
// Version: 1.0.0   - 2025-05-04
// Author: Vincent Leung
// Copyright: 2023-2025 Vincent Leung
// License: MIT
//
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