<?php
//
// test.php - MeowBase Test Script
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
use Paheon\MeowBase\Config;
use Paheon\MeowBase\MeowBase;
use Paheon\MeowBase\ClassBase;
use Psr\Log\LogLevel;

// Profiler will read this global variable for the application start time, 
//   so it should be run at the beginning of the application
$prgStartTime = microtime(true);    

require(__DIR__.'/vendor/autoload.php');

// Load config //
$localSetting = [
    "general" => [
        "sessionName" => "meowTest",
    ],
];  // override default config
$config = new Config($localSetting);

// Run MeowBase //
$meow = new MeowBase($config);

// Determine Web or CLI //
$isWeb = $meow->configTree["sapi"] != "cli";
$br = $isWeb ? "<br>\n" : "\n";

//--- Test ClassBase function ---//

// Protected property //
echo "Test ClassBase function".$br;
echo "--------------------------------".$br;
echo "Non-write protected property:".$br;
echo "Read debug = ".var_export($meow->debug, true).$br;
echo "Write debug = true".$br;
$meow->debug = true;
echo "Read debug = ".var_export($meow->debug, true).$br;
$meow->debug = false;
echo $br;

echo "Write protected property:".$br;
echo "Read lazyLoad = ".var_export($meow->lazyLoad, true).$br;
echo "Write lazyLoad = []".$br;
$meow->lazyLoad = [];
echo "Read lazyLoad = ".var_export($meow->lazyLoad, true).$br;
echo $br;

// Mass Getter and Mass Setter //
class test extends ClassBase {
    protected string $a = "a";
    protected string $b = "b";
    protected string $c = "c";
    protected string $d = "d";
    protected string $e = "e";
    protected string $f = "f";
    protected string $g = "g";
}
$testClass = new test();
echo "Test massSetter:".$br;
echo "Before massSetter:".var_export($testClass, true).$br;
$unsetList = $testClass->massSetter([ "a" => "A", "c" => "C", "d" => "D", "f" => "F", "g" => "G" , "r" => "R", "x" => "X" ]);
echo "After massSetter:".var_export($testClass, true).$br;
echo "Error: ".$testClass->lastError.$br;
echo "Unset list: ".var_export($unsetList, true).$br;
echo $br;

$propList = $testClass->massGetter([ "a" => "0", "b" => "1", "e" => "2", "f" => "3", "g" => "4", "r" => "It is r", "x" => "It is x" ]);
echo "Test massGetter:".$br;
echo "propList list: ".var_export($propList, true).$br;
echo "Error: ".$testClass->lastError.$br;
echo $br;

$logicList = [ "y", "Yup", "yes", "T", "ture", "turth", "1", "true", "on", "open", "enable", "E", "100", "1", "On", 1, 1000, 0.1, 0x10,
               "n", "Nop", "no", "F", "false", "Fake", "0", "off", "close", "disable", "D", "-1", "000", 0, 0.0, 0x0 ];
echo "Test isTrue:".$br;
foreach ($logicList as $value) {
    echo "Test isTrue('$value') = ".var_export($meow->isTrue($value), true).$br;
}
echo $br;
$meow->profiler->record("ClassBase function test completed");

//--- Test Config Function ---//

echo "Test Config Function".$br;
echo "--------------------------------".$br;

// set Log enable and disable //
echo "Log/enable = ".var_export($meow->config->getConfigByPath("log/enable"), true)." (Initial Value)" .$br;    

$meow->config->setConfigByPath("log/enable", false);
echo "log/enable = ".var_export($meow->config->getConfigByPath("log/enable"), true).$br;     

$meow->config->setConfigByPath("log/enable", true);
echo "log/enable = ".var_export($meow->config->getConfigByPath("log/enable"), true).$br;     
echo $br;

// Read by getConfigByPath //
echo "Time zone: ".$meow->config->getConfigByPath("general/timeZone").$br;
echo "Session name: ".$meow->config->getConfigByPath("general/sessionName")." (the default value is 'meow')".$br;
echo $br;

// Try to use configTree to access log config //
echo "Log path : ".$meow->configTree['log']['path'].$br;         // Same as $meow->config->getConfigByPath("log/path")
echo "Log level : ".$meow->configTree['log']['level'].$br;       // Same as $meow->config->getConfigByPath("log/level")
echo "Log enable : ".var_export($meow->configTree['log']['enable'], true).$br;     // Same as $meow->config->getConfigByPath("log/enable")
echo $br;

// Multiple level access by path //
$path = "cache/adapterList/memcached/servers/main/host";
echo "Config '$path': ".$meow->config->getConfigByPath($path).$br;
$path = "cache/adapterList/files/path";
echo "Config '$path': ".$meow->config->getConfigByPath($path).$br;
$path = "cache/not-exist/adapterList/files/path";
echo "Config '$path': ".$meow->config->getConfigByPath($path).$br;
echo "Error: ".$meow->config->lastError.$br;
echo $br;
$meow->profiler->record("Config test completed");

// Test user defined setting //
echo "User defined setting: ".$meow->config->getConfigByPath("mySetting/mySetting1").$br;
echo "User defined setting: ".$meow->config->getConfigByPath("mySetting/mySetting2").$br;
echo $br;

//--- Test Log function ---//
function logFunc2(MeowBase $meow, string $data, string $br) {
    $data .= " -> logFunc2";
    echo "logFunc2 Called : data = ".$data.$br;
    logFunc1($meow, $data, $br);
    $meow->log->sysLog("logFunc2 -> Test Log function", [ "data" => $data ], LogLevel::INFO);
}
function logFunc1(MeowBase $meow, string $data, string $br) {
    $data .= " -> logFunc1";
    echo "logFunc1 Called : data = ".$data.$br;
    $meow->log->sysLog("logFunc1 -> Test Log function", [ "data" => $data ], LogLevel::DEBUG);
}

echo "Test Log function".$br;
echo "--------------------------------".$br;
$data = null;
$logFile = $meow->log->getLogFilePath();
echo "Log file path: ".$logFile.$br;
echo $br;
echo "Write debug message".$br;  
$meow->log->sysLog("Current time zone", [ "timeZone" => $meow->config->getConfigByPath("general/timeZone") ]);     // Log time zone for debug
echo "Write error message".$br;
$meow->log->sysLog("Error occured!", [ "error code" => 123 ], LogLevel::ERROR);
echo "Write warning message".$br;
$meow->log->sysLog("Data type mismatch!", [ "data" => $data ], LogLevel::WARNING);
echo "Write info message".$br;
$meow->log->stack = true;                        // Enable stack tracking to show full calling process
logFunc2($meow, "Call stack enabled!", $br);     
$meow->log->stack = false;                       // Disable stack tracking to hide calling process
logFunc2($meow, "Call stack disabled!", $br);    
$meow->log->sysLog("Log demo completed!", null, LogLevel::INFO);
echo $br;
$meow->profiler->record("Log test completed");

//--- Test Cache function ---//

echo "Test Cache function".$br;
echo "--------------------------------".$br;
echo "Site ID: ".var_export($meow->cache->siteID, true).$br;
echo $br;

echo "Cache Enable: ".(($meow->cache->enable) ? "Yes" : "No").$br;
echo "Cache Default lifetime: ".$meow->cache->lifeTime.$br;
echo "Cache Driver: ".$meow->cache->adpater.$br;
echo "Cache Pool Object: ".(is_object($meow->cache->pool) ? "Exist" : "Not found").$br;
echo "Cache Item Object: ".(is_object($meow->cache->item) ? "Exist" : "Not found").$br;
echo $br;

// Check hit //
echo "Cache access test:".$br;
$key = "testKey";
$val = "testValue";
echo "Key='".var_export($key, true)."'".$br;
echo "Val='".var_export($val, true)."'".$br;
echo $br;

$cached = false;
$saveData = true;
for($i = 1; $i < 3 && $saveData; $i++) {
    echo "Round ".($i).$br;
    // Check cache hit //
    if ($meow->cache->isHit($key)) {
        echo "Cache hit!".$br;
        $value = $meow->cache->get();
        echo "Value = ".var_export($value, true)."!".$br;
        if ($meow->cache->delItem($key)) {
            echo "Removed from cache successfully!".$br;
        } else {
            echo "Fail to remove from cache!".$br;
            $saveData = false;
        }
    } else {
        echo "Cache Miss!".$br;
    }
    // Save data to cache //
    if ($saveData) {
        if (is_object($meow->cache->item)) {
            echo "Set value =".var_export($val, true).$br;
            $meow->cache->set($val);
            echo "Save ".($meow->cache->save() ? "success" : "fail")."!".$br;
        } else {
            echo "Item object not exist!".$br;
        }
    }
    echo $br;
}

// remove test data //
if ($meow->cache->isHit($key)) {
    echo "Removed saved data from cache!".$br;
    $meow->cache->delItem($key);
}
echo $br;
$meow->profiler->record("Cache function test completed");

//--- Test CacheDB function ---//

// Drop table if exists //
echo "Test CacheDB function".$br;
echo "--------------------------------".$br;
$meow->db->enableLog = true;
$meow->profiler->record("DB Test start", "DB Test");
$meow->cache->clear();
$meow->db->drop("test");
$meow->profiler->record("DB Preparation Done", "DB Test");

// Create table //
$meow->db->create("test", [
    "id" => [ "INT", "NOT NULL", "AUTO_INCREMENT", "PRIMARY KEY" ],
    "name" => [ "VARCHAR(64)", "NOT NULL" ],
    "description" => [ "VARCHAR(128)", "NOT NULL" ],
    "value" => [ "INT", "NOT NULL", "DEFAULT 0" ],
    "created_at" => [ "DATETIME", "NOT NULL", "DEFAULT CURRENT_TIMESTAMP" ],
]);
$meow->profiler->record("Create Table", "DB Test");

// Insert 1000 records //
for ($i = 1; $i <= 1000; $i++) {
    $paddedNumber = str_pad($i, 5, '0', STR_PAD_LEFT);
    $meow->db->insert("test", [
        "name" => "name-" . $paddedNumber,
        "description" => $paddedNumber . "-Description",
        "value" => rand(1, 1000)
    ]);
    $meow->log->enable = false;     // Prevent log too much insert SQL statement
}
$meow->log->enable = true;          // Enable log again
$meow->profiler->record("Insert 1000 Records", "DB Test");

// Select records //
$meow->profiler->record("Cached Select Test Start", "DB Cached Select Test");
$data0 = $meow->db->select("test", "*", [
    "value[>=]" => 500
]);
echo "select command readed ".count($data0)." records".$br;
$meow->profiler->record("Select Records where value >= 500 (By select)", "DB Cached Select Test");

// Select records and build cache //
$data1 = $meow->db->cachedSelect("test", "*", [
    "value[>=]" => 500
]);
echo "cachedSelect command readed ".count($data1)." records (first time)".$br;
$meow->profiler->record("Select Records where value >= 500 (By cachedSelect first time)", "DB Cached Select Test");

// Select records again and test cache hit //
$data2 = $meow->db->cachedSelect("test", "*", [
    "value[>=]" => 500
]);
echo "cachedSelect command readed ".count($data2)." records (second time)".$br;
$meow->profiler->record("Select Records where value >= 500 (By cachedSelect second time)", "DB Cached Select Test");

// Get a single record //
$meow->profiler->record("Cached Get Test Start", "DB Cached Get Test");
$data3 = $meow->db->get("test", "*", [
    "name" => "name-00500"
]);
$meow->profiler->record("Get a single record (By get)", "DB Cached Get Test");

// Get a single record and build cache //
$data4 = $meow->db->cachedGet("test", "*", [
    "name" => "name-00500"
]);
$meow->profiler->record("Get a single record (By cachedGet first time)", "DB Cached Get Test");

// Select records again and test cache hit //
$data5 = $meow->db->cachedGet("test", "*", [
    "name" => "name-00500"
]);
$meow->profiler->record("Get a single record (By cachedGet second time)", "DB Cached Get Test");

var_dump($data3);
var_dump($data4);
var_dump($data5);

$meow->profiler->record("Record read test. Done!", "DB Test");

// Select records again and test cache hit //
$data6 = $meow->db->cachedGet("test", "*", [
    "name" => "name-00500"
]);
$meow->profiler->record("Ensure cache still exist (By cachedGet third time)", "DB Test");

// Update record test //
$meow->db->update("test", [
    "description" => "00500-UpdatedRecord",
    "value" => 99999
], [
    "id" => 500
]);
$meow->profiler->record("Update the record to clear cache", "DB Test");

// Get a single record //
$data7 = $meow->db->get("test", "*", [
    "name" => "name-00500"
]);
$meow->profiler->record("Get a single record (By get)", "DB Test");

// Get a single record and build cache //
$data8 = $meow->db->cachedGet("test", "*", [
    "name" => "name-00500"
]);
$meow->profiler->record("Get a single record (By cachedGet first time)", "DB Test");

// Select records again and test cache hit //
$data9 = $meow->db->cachedGet("test", "*", [
    "name" => "name-00500"
]);
$meow->profiler->record("Get a single record (By cachedGet second time)", "DB Test");

var_dump($data6);
var_dump($data7);
var_dump($data8);
var_dump($data9);

$meow->profiler->record("DB Test Completed!", "DB Test");

// Show report //
echo $meow->profiler->report($isWeb);
