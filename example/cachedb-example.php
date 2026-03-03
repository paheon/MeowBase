<?php
/**
 * CacheDB Example
 * 
 * This example demonstrates how to use CacheDB which extends Medoo
 * with caching capabilities for database queries.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\Cache;
use Paheon\MeowBase\SysLog;
use Paheon\MeowBase\CacheDB;
use Psr\Log\LogLevel;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "CacheDB Example".$br;
echo "==========================================".$br.$br;

// Example 1: Setup CacheDB
echo "Example 1: Setup CacheDB".$br;
echo "--------------------------------".$br;

$config = new Config();
$sqlConfig = $config->getConfigByPath("db/sql");

// Check if database is configured
if (empty($sqlConfig['database'])) {
    echo "Database not configured. Please configure database in config file.".$br;
    echo "Skipping CacheDB examples...".$br.$br;
    exit;
}

// Initialize Cache and Log
$cache = new Cache($config->getConfigByPath("cache"));
$log = new SysLog(
    $config->getConfigByPath("log/path"),
    $config->getConfigByPath("log/level"),
    $config->getConfigByPath("log/option")
);

// Create CacheDB instance
$db = new CacheDB($sqlConfig, $cache, $log);
$db->enableCache = true;  // Enable caching
$db->enableLog = false;   // Disable SQL logging for cleaner output

echo "CacheDB initialized".$br;
echo "Cache enabled: ".var_export($db->enableCache, true).$br;
echo "Log enabled: ".var_export($db->enableLog, true).$br.$br;

// Example 2: Create Test Table
echo "Example 2: Create Test Table".$br;
echo "--------------------------------".$br;

// Drop table if exists
$db->drop("cache_test");

// Create table
$db->create("cache_test", [
    "id" => [ "INT", "NOT NULL", "AUTO_INCREMENT", "PRIMARY KEY" ],
    "name" => [ "VARCHAR(64)", "NOT NULL" ],
    "value" => [ "INT", "NOT NULL", "DEFAULT 0" ],
    "created_at" => [ "DATETIME", "NOT NULL", "DEFAULT CURRENT_TIMESTAMP" ],
]);

echo "Table 'cache_test' created".$br.$br;

// Example 3: Insert Data
echo "Example 3: Insert Data".$br;
echo "--------------------------------".$br;

// Insert some test data
for ($i = 1; $i <= 10; $i++) {
    $db->insert("cache_test", [
        "name" => "Item " . $i,
        "value" => rand(1, 100)
    ]);
}

echo "Inserted 10 test records".$br.$br;

// Example 4: Regular Select (No Cache)
echo "Example 4: Regular Select (No Cache)".$br;
echo "--------------------------------".$br;

echo "Executing regular SELECT (no cache):".$br;
$startTime = microtime(true);
$data1 = $db->select("cache_test", "*", [
    "value[>=]" => 50
]);
$time1 = microtime(true) - $startTime;
echo "  - Found ".count($data1)." records".$br;
echo "  - Query time: ".number_format($time1 * 1000, 2)." ms".$br.$br;

// Example 5: Cached Select (First Time - Cache Miss)
echo "Example 5: Cached Select (First Time - Cache Miss)".$br;
echo "--------------------------------".$br;

echo "Executing cached SELECT (first time - cache miss):".$br;
$startTime = microtime(true);
$data2 = $db->cachedSelect("cache_test", "*", [
    "value[>=]" => 50
]);
$time2 = microtime(true) - $startTime;
echo "  - Found ".count($data2)." records".$br;
echo "  - Query time: ".number_format($time2 * 1000, 2)." ms".$br;
echo "  - Status: Cache MISS (data loaded from database)".$br.$br;

// Example 6: Cached Select (Second Time - Cache Hit)
echo "Example 6: Cached Select (Second Time - Cache Hit)".$br;
echo "--------------------------------".$br;

echo "Executing cached SELECT (second time - cache hit):".$br;
$startTime = microtime(true);
$data3 = $db->cachedSelect("cache_test", "*", [
    "value[>=]" => 50
]);
$time3 = microtime(true) - $startTime;
echo "  - Found ".count($data3)." records".$br;
echo "  - Query time: ".number_format($time3 * 1000, 2)." ms".$br;
echo "  - Status: Cache HIT (data loaded from cache)".$br;
echo "  - Performance improvement: ".number_format(($time1 - $time3) / $time1 * 100, 1)."%".$br.$br;

// Example 7: Cache Invalidation on Data Change
echo "Example 7: Cache Invalidation on Data Change".$br;
echo "--------------------------------".$br;

echo "Updating data (this should invalidate cache):".$br;
$db->update("cache_test", ["value" => 99], ["id" => 1]);
echo "  - Updated record with id=1".$br;

echo "Executing cached SELECT after update:".$br;
$data4 = $db->cachedSelect("cache_test", "*", [
    "value[>=]" => 50
]);
echo "  - Found ".count($data4)." records".$br;
echo "  - Status: Cache MISS (cache invalidated, fresh data loaded)".$br.$br;

// Example 8: Cached Get (Single Record)
echo "Example 8: Cached Get (Single Record)".$br;
echo "--------------------------------".$br;

echo "Using cachedGet for single record:".$br;
$record1 = $db->cachedGet("cache_test", "*", ["id" => 5]);
echo "  - First call (cache miss):".$br;
if ($record1) {
    echo "    ID: ".$record1['id'].", Name: ".$record1['name'].", Value: ".$record1['value'].$br;
}

$record2 = $db->cachedGet("cache_test", "*", ["id" => 5]);
echo "  - Second call (cache hit):".$br;
if ($record2) {
    echo "    ID: ".$record2['id'].", Name: ".$record2['name'].", Value: ".$record2['value'].$br;
}
echo $br;

// Example 9: Cache Tags
echo "Example 9: Cache Tags".$br;
echo "--------------------------------".$br;

echo "Using cached queries with custom tags:".$br;
$data5 = $db->cachedSelect("cache_test", "*", [
    "value[<]" => 30
], null, null, ["custom_tag", "low_value"], 3600);
echo "  - Query cached with tags: custom_tag, low_value".$br;
echo "  - Found ".count($data5)." records".$br.$br;

// Example 10: Manual Cache Management
echo "Example 10: Manual Cache Management".$br;
echo "--------------------------------".$br;

echo "Clearing cache for specific table:".$br;
if ($db->delTableCache("cache_test")) {
    echo "  - Cache for table 'cache_test' cleared".$br;
}

echo "Clearing all query cache:".$br;
if ($db->delQueryCache()) {
    echo "  - All query cache cleared".$br;
}
echo $br;

// Example 11: Cached Calculations
echo "Example 11: Cached Calculations".$br;
echo "--------------------------------".$br;

echo "Using cached calculations:".$br;

// Cached count
$count1 = $db->cachedCalc("count", "cache_test", null, null, ["value[>=]" => 50]);
echo "  - Count (first call): ".$count1.$br;
$count2 = $db->cachedCalc("count", "cache_test", null, null, ["value[>=]" => 50]);
echo "  - Count (second call, cached): ".$count2.$br;

// Cached average
$avg = $db->cachedCalc("avg", "cache_test", null, "value", null);
echo "  - Average value: ".$avg.$br;

// Cached max
$max = $db->cachedCalc("max", "cache_test", null, "value", null);
echo "  - Max value: ".$max.$br;

// Cached min
$min = $db->cachedCalc("min", "cache_test", null, "value", null);
echo "  - Min value: ".$min.$br;
echo $br;

// Example 12: SQL Logging
echo "Example 12: SQL Logging".$br;
echo "--------------------------------".$br;

$db->enableLog = true;
$db->logResult = false; // Don't log query results

echo "SQL logging enabled".$br;
$db->select("cache_test", "*", ["id" => 1]);
echo "  - Check log file for SQL statements".$br;
$db->enableLog = false;
echo $br;

// Example 13: Error Handling
echo "Example 13: Error Handling".$br;
echo "--------------------------------".$br;

echo "Testing error handling:".$br;
$result = $db->select("non_existent_table", "*");
if ($result === null) {
    echo "  - Error occurred: ".$db->lastError.$br;
    $sqlError = $db->getSQLError();
    if ($sqlError) {
        echo "  - SQL Error: ".$sqlError.$br;
    }
}
echo $br;

// Cleanup
echo "Cleanup:".$br;
$db->drop("cache_test");
echo "  - Test table dropped".$br.$br;

// Example 14: Debug Information
echo "Example 14: Debug Information".$br;
echo "--------------------------------".$br;

$debugInfo = $db->__debugInfo();
echo "CacheDB debug info:".$br;
echo "  enableCache: ".var_export($debugInfo['enableCache'], true).$br;
echo "  enableLog: ".var_export($debugInfo['enableLog'], true).$br;
echo "  logResult: ".var_export($debugInfo['logResult'], true).$br.$br;

echo "Example completed!".$br;
