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
use Paheon\MeowBase\DTree;
use Paheon\MeowBase\DTreeIterator;
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

// Test DTree //
echo "Test DTree function".$br;
echo "--------------------------------".$br;
echo "The test will create a tree structure like this:".$br;
echo "Root".$br;
echo "├── A".$br;
echo "│   ├── A1".$br;
echo "│   └── A2".$br;
echo "├── B".$br;
echo "│   ├── B1".$br;  
echo "│   ├── B2".$br;
echo "│   │   ├── B2X".$br;
echo "│   │   └── B2Y".$br;
echo "│   └── B3".$br;
echo "├── C".$br;
echo "│   └── C1".$br;  
echo "├── D".$br;
echo "│   ├── D1".$br;
echo "│   └── D2".$br;

echo "Create root node".$br;
$tree = new DTree();    
$tree->data = "Root";

echo "Add child nodes".$br;
// Create first level nodes
$nodeA = $tree->createNode("A", "Data A");
$nodeB = $tree->createNode("B", "Data B");
$tree->createNode("C", "Data C");

// Create second level nodes under A
$nodeA->createByPath("A1", "Data A1");          // Relative path
$nodeB->createByPath("/A/A2", "Data A2");       // Absolute path

// Create second level nodes under B
$nodeB1 = new DTree("B1", "Data B1", $nodeB);   // Create node and hook to node B
$nodeB2 = new DTree("B2", "Data B2");           // Create node first
$nodeB->AddNode($nodeB2);                       // Hook node B2 to node B by AddNode()
$nodeB->createNode("B3", "Data B3");

// Create third level nodes under B2
$nodeB2->createNode("B2X", "Data B2X");
$nodeB2Y = new DTree("B2Y", "Data B2Y", $nodeB2);

// Create remained nodes by createByArray //
$nodeList = $tree->createByArray([
    "/C/C1" => "Data C1",
    "/D/D1" => "Data D1",
    "D/D2"  => "Data D2",
]);
$nodeC = $nodeList["/C/C1"]->parent;
$nodeD = $nodeList["/D/D1"]->parent;
echo "Result from createByArray:".$br;
var_dump($nodeList);

echo "Tree structure created".$br;
echo "tree: ".$br;
var_dump($tree);
echo "nodeA: ".$br;
var_dump($nodeA);
echo "nodeB: ".$br;
var_dump($nodeB);
echo "nodeC: ".$br;
var_dump($nodeC);
echo "nodeD: ".$br;
var_dump($nodeD);

// Test path finding
echo "Test path finding:".$br;
$testPaths = [
    "/A/A1",
    "/B/B2/B2X",
    "/C",
    "/E",           // Non-existent path
    "B2/B2Y",       // Relative path from B2 node
    "B/B2/B2Y",     // Relative path from root node
    "/D/D1"
];

echo "Using nodeB: ".$nodeB->getPath().$br;
foreach ($testPaths as $path) {
    $node = $nodeB->findByPath($path);
    echo "Finding path '$path': " . ($node ? "Found (path: {$node->getPath()}, data: {$node->data})" : "Not found") . $br;
}

// Test tree iteration
echo $br."Test tree iteration (Global):".$br;
$iterator = new DTreeIterator($tree);
foreach ($iterator as $position => $node) {
    echo str_pad("", strlen($node->getPath()) * 2, " ") . $node->getPath() . " => " . $node->data . $br;
}

echo $br."Test tree iteration (from nodeB):".$br;
$iterator = new DTreeIterator($nodeB, false);
foreach ($iterator as $position => $node) {
    echo str_pad("", strlen($node->getPath()) * 2, " ") . $node->getPath() . " => " . $node->data . $br;
}

// Test node operations
echo $br."Test node operations:".$br;

// Test adding duplicate node
echo "Adding duplicate node 'A1' to node A: " . ($nodeA->createNode("A1", "New A1", false) ? "Success" : "Failed - " . $nodeA->lastError) . $br;
// Show node A children
echo "Node A children: ".$br;
var_dump($nodeA->children["A1"]);

// Test replacing existing node
echo "Replacing node 'A1' in node A: " . ($nodeA->createNode("A1", "Replaced A1", true) ? "Success" : "Failed - " . $nodeA->lastError) . $br;
// Show node A children
echo "Node A children: ".$br;
var_dump($nodeA->children["A1"]);

// Test deleting node
echo "Deleting node 'A2' from node A: " . ($nodeA->delNode("A2") ? "Success" : "Failed - " . $nodeA->lastError) . $br;
// Show node A children
echo "Node A children: ".$br;
var_dump($nodeA->children);

// Test copy node
$copyNode = $nodeA->copyNode("A1", $nodeA, "A1-copy");
echo "Copy node from 'A1' to 'A1-copy': " . ($copyNode ? "Success (new node: {$copyNode->name})" : "Failed - " . $nodeA->lastError) . $br;
// Show node A children
echo "Node A children after copy: ".$br;
var_dump($nodeA->children);

// Test renaming node
echo "Renaming node 'A1' to 'A1-renamed': " . ($nodeA->renameNode("A1", "A1-renamed") ? "Success" : "Failed - " . $nodeA->lastError) . $br;
// Show node A children
echo "Node A children after renaming: ".$br;
var_dump($nodeA->children);

// Test duplicating node
$dupNode = $nodeA->dupNode("A1-renamed", null, "A1-dup");
echo "Duplicating node 'A1-renamed': " . ($dupNode ? "Success (new node: {$dupNode->name})" : "Failed - " . $nodeA->lastError) . $br;
// Show node A1-dup children
echo "Node A1-dup after duplication: ".$br;
var_dump($dupNode);
echo "Node A children after duplication: ".$br;
var_dump($nodeA->children);

// Test moving node
$moveNode = $nodeA->moveNode("A1-renamed", $nodeB);
echo "Moving node 'A1-renamed' to node B: " . ($moveNode ? "Success" : "Failed - " . $nodeA->lastError) . $br;
// Show node A and B children
echo "Node A children after moving: ".$br;
var_dump($nodeA->children);
echo "Node B children after moving: ".$br;
var_dump($nodeB->children);

// Test sorting
$nodeB->sortNode(true);
echo "Sorted node B children (ascending): " . implode(", ", array_keys($nodeB->children)) . $br;

$nodeB->sortNode(false);
echo "Sorted node B children (descending): " . implode(", ", array_keys($nodeB->children)) . $br;

// Test serialize and unserialize
echo $br."Test serialize and unserialize:".$br;

// Serialize the tree
$serializedTree = $tree->serialize();
echo "Serialized tree: ".$serializedTree.$br;

// Unserialize the tree
$unserializedTree = $tree->unserialize($serializedTree);
echo "Unserialized tree: ".($unserializedTree ? "Success" : "Failed - " . $tree->lastError).$br;

// Verify the unserialized tree structure
if ($unserializedTree) {
    echo "Unserialized tree structure:".$br;
    $iterator = new DTreeIterator($unserializedTree);
    foreach ($iterator as $position => $node) {
        echo str_pad("", strlen($node->getPath()) * 2, " ") . $node->getPath() . " => " . $node->data . $br;
    }
} else {
    echo "Failed to unserialize tree due to hash mismatch or other error.".$br;
}

$meow->profiler->record("Serialize/Unserialize test completed", "DTree Test");

// Show report //
echo $br . $meow->profiler->report($isWeb);
