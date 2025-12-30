<?php
/**
 * test.php - MeowBase Test Script
 * 
 * This file is used to test MeowBase framework functionality.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 */
use Paheon\MeowBase\Config;
use Paheon\MeowBase\MeowBase;
use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\Tools\DTree;
use Paheon\MeowBase\Tools\DTreeIterator;
use Paheon\MeowBase\Tools\File;
use Paheon\MeowBase\Tools\Url;
use Paheon\MeowBase\Tools\Mime;
use Paheon\MeowBase\Tools\Mailer;
use Paheon\MeowBase\Tools\CsvDB;
use Paheon\MeowBase\Tools\PHP;
use Paheon\MeowBase\Tools\User;
use Paheon\MeowBase\Tools\UserCSV;
use Paheon\MeowBase\Tools\UserDB;
use Paheon\MeowBase\Tools\UserGroupCSV;
use Paheon\MeowBase\Tools\UserGroupDB;
use Paheon\MeowBase\Tools\UserPermCSV;
use Paheon\MeowBase\Tools\UserPermDB;
use Paheon\MeowBase\Tools\UserManager;
use Paheon\MeowBase\Tools\Password;

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
$isWeb = !PHP::isCLI();
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
class test {
    use ClassBase;
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
    $meow->log->sysLog("logFunc2 -> Test Log function", [ "data" => $data ], 'info');
}
function logFunc1(MeowBase $meow, string $data, string $br) {
    $data .= " -> logFunc1";
    echo "logFunc1 Called : data = ".$data.$br;
    $meow->log->sysLog("logFunc1 -> Test Log function", [ "data" => $data ], 'debug');
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
$meow->log->sysLog("Error occured!", [ "error code" => 123 ], 'error');
echo "Write warning message".$br;
$meow->log->sysLog("Data type mismatch!", [ "data" => $data ], 'warning');
echo "Write info message".$br;
$meow->log->stack = true;                        // Enable stack tracking to show full calling process
logFunc2($meow, "Call stack enabled!", $br);     
$meow->log->stack = false;                       // Disable stack tracking to hide calling process
logFunc2($meow, "Call stack disabled!", $br);    
$meow->log->sysLog("Log demo completed!", null, 'info');
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

echo "data3: (Get a single record by get) get(\"test\", \"*\", [ \"name\" => \"name-00500\" ]) = ".$br;
var_dump($data3);
echo "data4: (Get a single record by cachedGet 1st time, result same as data3) cachedGet(\"test\", \"*\", [ \"name\" => \"name-00500\" ]) = ".$br;
var_dump($data4);
echo "data5: (Get a single record by cachedGet 2nd time, result same as data4) cachedGet(\"test\", \"*\", [ \"name\" => \"name-00500\" ]) = ".$br;
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

echo "data6: (Get a single record by cachedGet 3rd time (ensure cache still exist), result same as data5) cachedGet(\"test\", \"*\", [ \"name\" => \"name-00500\" ]) = ".$br;
var_dump($data6);
echo "data7: (Get a single record by get (cleared cache by update record), result same as data6) get(\"test\", \"*\", [ \"name\" => \"name-00500\" ]) = ".$br;
var_dump($data7);
echo "data8: (Get a single record by cachedGet 4th time (check whether cache is updated), result same as data7) cachedGet(\"test\", \"*\", [ \"name\" => \"name-00500\" ]) = ".$br;
var_dump($data8);
echo "data9: (Get a single record by cachedGet 5th time, result same as data8) cachedGet(\"test\", \"*\", [ \"name\" => \"name-00500\" ]) = ".$br;
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
$meow->profiler->record("Create DTree Start", "DTree Test");
$tree = new DTree();    
$tree->data = "Root";

echo "Add child nodes".$br;
// Create first level nodes
$nodeA = $tree->createNode([ 'name' => "A", 'data' => "Data A" ]);
$nodeB = $tree->createNode([ 'name' => "B", 'data' => "Data B" ]);
$tree->createNode([ 'name' => "C", 'data' => "Data C" ]);

// Create second level nodes under A
$nodeA->createByPath("A1", [ 'data' => "Data A1" ]);          // Relative path
$nodeB->createByPath("/A/A2", [ 'data' => "Data A2" ]);       // Absolute path

// Create second level nodes under B
$nodeB1 = new DTree("B1", "Data B1", $nodeB);   // Create node and hook to node B
$nodeB2 = new DTree("B2", "Data B2");           // Create node first
$nodeB->AddNode($nodeB2);                       // Hook node B2 to node B by AddNode()
$nodeB->createNode([ 'name' => "B3", 'data' => "Data B3" ]);

// Create third level nodes under B2
$nodeB2->createNode([ 'name' => "B2X", 'data' => "Data B2X" ]);
$nodeB2Y = new DTree("B2Y", "Data B2Y", $nodeB2);

// Create remained nodes by createByArray //
$nodeList = $tree->loadFromArray([
    "/C/C1" => [ 'data' => "Data C1" ],
    "/D/D1" => [ 'data' => "Data D1" ],
    "D/D2"  => [ 'data' => "Data D2" ],
]);
$nodeC = $nodeList["/C/C1"]->parent;
$nodeD = $nodeList["/D/D1"]->parent;
echo "Result from loadFromArray:".$br;
var_dump($nodeList);

// Save tree to array //
echo "Save tree to array".$br;
$nodeList2 = $tree->SaveToArray();
echo "Result from SaveToArray:".$br;
var_dump($nodeList2);

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

$meow->profiler->record("Create DTree Completed", "DTree Test");

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
    echo "Position: ".$position.$br;
    echo str_pad("", strlen($node->getPath()) * 2, " ") . $node->getPath() . " => " . $node->data . $br;
}

// Test node operations
echo $br."Test node operations:".$br;

// Test adding duplicate node
echo "Adding duplicate node 'A1' to node A: " . ($nodeA->createNode([ 'name' => "A1", 'data' => "New A1", 'replace' => false ]) ? "Success" : "Failed - " . $nodeA->lastError) . $br;
// Show node A children
echo "Node A children: ".$br;
var_dump($nodeA->children["A1"]);

// Test replacing existing node
echo "Replacing node 'A1' in node A: " . ($nodeA->createNode([ 'name' => "A1", 'data' => "Replaced A1", 'replace' => true ]) ? "Success" : "Failed - " . $nodeA->lastError) . $br;
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
$meow->profiler->record("DTree Operation Completed", "DTree Test");

// Test sorting
$nodeB->sortNode(true);
echo "Sorted node B children (ascending): " . implode(", ", array_keys($nodeB->children)) . $br;

$nodeB->sortNode(false);
echo "Sorted node B children (descending): " . implode(", ", array_keys($nodeB->children)) . $br;
$meow->profiler->record("DTree Sorting Completed", "DTree Test");

// Test serialize and unserialize
echo $br."Test serialize and unserialize:".$br;

// Serialize the tree
$serializedTree = $tree->serialize();
echo "Serialized tree: ".$serializedTree.$br;

// Unserialize the tree
$meow->profiler->record("Serialize test Completed", "DTree Test");
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

$meow->profiler->record("Unserialize test completed", "DTree Test");

// Test File class //
echo $br."Test File class".$br;
echo "--------------------------------".$br;
$meow->profiler->record("File Test Start", "File Test");

// Create File object
$file = new File();
echo "File object created".$br;
echo "Home path: ".($file->home ?? "null").$br;

// Set home to current directory
$file->setHomeToCurrent();
echo "Home path set to current directory: ".$file->home.$br;

// Set home to a specific directory
$file->setHome(__DIR__);
echo "Home path set to __DIR__: ".$file->home.$br;

// Test file path building
$fullPath = $file->genFile("test.txt");
echo "Full path for 'test.txt': ".$fullPath.$br;

// Test file path with substitution
$filePath = $file->genFile("[type]/[name].[ext]", [
    "type" => "documents",
    "name" => "report",
    "ext" => "pdf"
]);
echo "File path with substitution: ".$filePath.$br;

// Test temporary file creation by tempFile
$tempFilePath = "";
$tempFile = $file->tempFile($tempFilePath);
if ($tempFile !== false) {
    echo "Temporary file created: ".$tempFilePath.$br;
    // Write something to the temp file
    fwrite($tempFile, "This is a test content for the temporary file.");
    fseek($tempFile, 0);
    // Read the content
    $content = fread($tempFile, 1024);
    echo "Content read from temp file: ".$content.$br;
    // Close the temp file (this will also delete it)
    fclose($tempFile);
} else {
    echo "Failed to create temporary file: ".$file->lastError.$br;
}
if (file_exists($tempFilePath)) {
    echo "Deleting temp file: ".$tempFilePath.$br;
    unlink($tempFilePath);
}

// Test temporary file creation by genTempFile
$tempFilePath = "";
$tempFile = $file->genTempFile("", "MyTemp_");
if ($tempFile !== false) {
    echo "Temporary file created: ".$tempFile.$br;
    // Write something to the temp file
    file_put_contents($tempFile, "This is a test content for the temporary file.");
    // Read the content
    $content = file_get_contents($tempFile);
    echo "Content read from temp file: ".$content.$br;
    // Close the temp file (this will also delete it)
} else {
    echo "Failed to create temporary file: ".$file->lastError.$br;
}
if (file_exists($tempFilePath)) {
    echo "Deleting temp file: ".$tempFilePath.$br;
    unlink($tempFilePath);
}


$meow->profiler->record("File Test Completed", "File Test");

// Test Url class //
echo $br."Test Url class".$br;
echo "--------------------------------".$br;
$meow->profiler->record("Url Test Start", "Url Test");

// Create Url object
$url = new Url();
echo "Url object created".$br;
echo "Home URL: ".($url->home ?? "null").$br;

// Set home URL
$url->setHome("https://example.com/app");
echo "Home URL set to: ".$url->home.$br;

// Test URL building
$fullUrl = $url->genUrl("users/profile", ["id" => 123, "view" => "full"], "section1", true);
echo "Full URL: ".$fullUrl.$br;

$relativeUrl = $url->genUrl("users/profile", ["id" => 123, "view" => "full"], "section1", false);
echo "Relative URL: ".$relativeUrl.$br;

// Test URL modification
$sourceUrl = "https://example.com/products?category=electronics&sort=price";
$modifiedUrl = $url->modifyUrl($sourceUrl, [
    "path" => "/services",
    "query" => ["category" => "software", "filter" => "new"]
]);
echo "Source URL: ".$sourceUrl.$br;
echo "Modified URL: ".$modifiedUrl.$br;

// Test URL info (only if we have internet connection)
echo "Testing URL info for https://example.com:".$br;
$urlInfo = $url->urlInfo("https://example.com");
if ($urlInfo !== false) {
    echo "URL info retrieved successfully".$br;
    echo "HTTP code: ".$urlInfo["http_code"].$br;
    echo "Content type: ".$urlInfo["content_type"].$br;
} else {
    echo "Failed to retrieve URL info: ".$url->lastError.$br;
}

$meow->profiler->record("Url Test Completed", "Url Test");

// Test Mime class //
echo $br."Test Mime class".$br;
echo "--------------------------------".$br;
$meow->profiler->record("Mime Test Start", "Mime Test");

// Create Mime object with default paths
$mime = new Mime();
echo "Mime object created".$br;

// Test file to MIME type conversion
$testFile = __FILE__;
echo "Testing MIME type for current file: ".$testFile.$br;
$mimeType = $mime->file2Mime($testFile);
if ($mimeType !== false) {
    echo "MIME type: ".$mimeType.$br;
} else {
    echo "Failed to determine MIME type: ".$mime->lastError.$br;
}

// Test MIME to icon conversion
if ($mimeType !== false) {
    echo "Testing icon for MIME type: ".$mimeType.$br;
    $icon = $mime->mime2Icon($mimeType);
    if ($icon !== false) {
        echo "Icon: ".$icon.$br;
    } else {
        echo "Failed to determine icon: ".$mime->lastError.$br;
    }
}

// Test alias to MIME conversion
echo "Testing alias to MIME conversion for 'text/plain'".$br;
$aliasMime = $mime->alias2Mime("text/plain");
if ($aliasMime !== false) {
    echo "Alias MIME: ".$aliasMime.$br;
} else {
    echo "Failed to determine alias MIME: ".$mime->lastError.$br;
}

$meow->profiler->record("Mime Test Completed", "Mime Test");

// Test Mailer class //
echo $br."Test Mailer class".$br;
echo "--------------------------------".$br;
$meow->profiler->record("Mailer Test Start", "Mailer Test");

// Create Mailer object
$mailer = new Mailer($meow->configTree["mailer"]);
echo "Mailer object created".$br;

// Test email validation
echo "Testing email validation:".$br;
$testEmails = [
    'valid@example.com',
    'invalid.email',
    'test@nonexistentdomain.xyz'
];

foreach ($testEmails as $email) {
    echo "Validating '$email': " . ($mailer->emailValidate($email, true) ? "Valid" : "Invalid - " . $mailer->lastError) . $br;
}
echo $br;

// Test setting addresses
echo "Testing address setting:".$br;
$addresses = [
    'from' => ['sender@example.com' => 'Test Sender'],
    'to' => ['recipient1@example.com' => 'Recipient 1', 'recipient2@example.com' => 'Recipient 2'],
    'cc' => ['cc1@example.com' => 'CC 1'],
    'bcc' => ['bcc1@example.com' => 'BCC 1'],
    'replyto' => ['reply@example.com' => 'Reply To']
];

foreach ($addresses as $type => $addr) {
    echo "Setting $type addresses: " . ($mailer->addAddress($type, $addr) ? "Success" : "Failed - " . $mailer->lastError) . $br;
}
echo $br;

// Test setting subject and body
echo "Testing subject and body setting:".$br;
$mailer->setSubject("Test Email Subject");
$mailer->setBody(
    "<h1>Test Email</h1><p>This is a <b>test</b> email body.</p>",
    true,
    "This is a test email body."
);
echo "Subject and body set!".$br;
echo $br;

// Test adding attachments
echo "Testing attachment handling:".$br;
$tempFile = tempnam(sys_get_temp_dir(), 'test_');
echo "Create temp file '$tempFile' for attachment: ".$br;
file_put_contents($tempFile, "This is a test attachment content.");

// Test regular attachment
try {
    echo "Adding regular attachment: " . ($mailer->addAttachment($tempFile, 'test.txt') ? "Success" : "Failed - " . $mailer->lastError) . $br;
} catch (\Exception $e) {
    echo "Error adding regular attachment: " . $e->getMessage() . $br;
}

// Test string attachment
try {
    echo "Adding string attachment: " . ($mailer->addStringAttachment("This is a string attachment content.", 'string.txt') ? "Success" : "Failed - " . $mailer->lastError) . $br;
} catch (\Exception $e) {
    echo "Error adding string attachment: " . $e->getMessage() . $br;
}

// Test embedded image
try {
    echo "Adding embedded image: " . ($mailer->addEmbeddedImage($tempFile, 'test_image', 'test.jpg') ? "Success" : "Failed - " . $mailer->lastError) . $br;
} catch (\Exception $e) {
    echo "Error adding embedded image: " . $e->getMessage() . $br;
}

if (file_exists($tempFile)) {
    echo "Deleting temp file: ".$tempFile.$br;
    unlink($tempFile);
}
$meow->profiler->record("Mailer setting test completed", "Mailer Test");
echo $br;

// Test async mode
$skipSendTest = true;   // Please set the from, to, cc, bcc, replyTo, subject, body, attachments, etc. before running this test
$tempFile = "";
$tmpFile = new File();
echo "Testing email sending with async mode:".$br;
if ($skipSendTest) {
    echo "This test skipped! (set \$skipSendTest to false to run this test):".$br;
} else {
    $tempFile = $tmpFile->genTempFile("", "test_");
    try {
        $mailer->reset();
        $mailer->async = true;

        $mailer->from = ['sender@example.com' => 'Test Sender'];
        $mailer->to = ['recipient1@example.com' => 'First Recipient', 'recipient2@example.com' => 'Second Recipient'];
        $mailer->CC = ['cc1@example.com' => 'CC Recipient'];
        $mailer->BCC = ['bcc1@example.com' => 'BCC Recipient'];
        $mailer->replyTo = ['reply@example.com' => 'Reply To'];
        $mailer->subject = "Test Email - Async Mode";
        $mailer->setBody(
            "<h1>Async Mode Test</h1><p>This is a test email to recipients.</p>",
            true,
            "This is a test email to recipients."
        );

        // Add attachments file
        echo "Adding contents to temp file '$tempFile' as attachments".$br;
        file_put_contents($tempFile, "This is a test attachment content.");

        $mailer->addAttachment($tempFile, 'test.txt');
        $mailer->addStringAttachment("This is a string attachment content.", 'string.txt');
        $mailer->addEmbeddedImage($tempFile, 'test_image', 'test.jpg');

        // Display email details before sending
        echo "Email details:".$br;
        foreach ($mailer->from as $addr => $name) {
            echo "- From: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->to as $addr => $name) {
            echo "- To: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->CC as $addr => $name) {
            echo "- CC: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->BCC as $addr => $name) {
            echo "- BCC: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->replyTo as $addr => $name) {
            echo "- Reply-To: " . $addr . " (" . $name . ")".$br;
        }
        echo "- Subject: " . $mailer->subject.$br;
        echo "- Body: " . $mailer->body.$br;
        echo "- Alt Body: " . $mailer->altBody.$br;
        echo "- Attachments: " . count($mailer->attachments) . " files".$br;

        $mailer->config = ["debug" => 4];
        echo "Sending email with async mode: " . ($mailer->send() ? "Success" : "Failed - " . $mailer->lastError) . $br;

    } catch (\Exception $e) {
        echo "Error sending email: " . $e->getMessage() . $br;
    }
}
$meow->profiler->record("Send email with async mode", "Mailer Test");
echo $br;

// Test proessing email with async mode
$skipSendTest = true;   
echo "Testing email processing with async mode :".$br;
if ($skipSendTest) {
    echo "This test skipped! (set \$skipSendTest to false to run this test):".$br;
} else {
    try {
        $mailer->config = ["debug" => 4];
        $result = $mailer->sendAsync();
        echo "Proecess email with async mode: successfully send = " . $result["success"] . ", failed to send = " . $result["failed"] . $br;
        foreach ($result['errors'] as $error) {
            echo "Error sending email: " . print_r($error['error'], true). $br;
        }
    } catch (\Exception $e) {
        echo "Error sending email: " . $e->getMessage() . $br;
    }
}
// Clean up temp file
if (file_exists($tempFile)) {
    echo "Deleting temp file: ".$tempFile.$br;
    unlink($tempFile);
}
$meow->profiler->record("Process email with async mode", "Mailer Test");
echo $br;

// Test direct mode
$skipSendTest = true;   // Please set the from, to, cc, bcc, replyTo, subject, body, attachments, etc. before running this test
echo "Testing email sending with direct mode:".$br;
if ($skipSendTest) {
    echo "This test skipped! (set \$skipSendTest to false to run this test):".$br;
} else {
    $tempFile = $tmpFile->genTempFile("", "test_");
    try {
        $mailer->reset();
        $mailer->async = false;

        $mailer->from = ['sender@example.com' => 'Test Sender'];
        $mailer->to = ['recipient1@example.com' => 'First Recipient', 'recipient2@example.com' => 'Second Recipient'];
        $mailer->CC = ['cc1@example.com' => 'CC Recipient'];
        $mailer->BCC = ['bcc1@example.com' => 'BCC Recipient'];
        $mailer->replyTo = ['reply@example.com' => 'Reply To'];
        $mailer->subject = "Test Email - Direct Mode";
        $mailer->setBody(
            "<h1>Direct Mode Test</h1><p>This is a test email to recipients.</p>",
            true,
            "This is a test email to recipients."
        );

        // Add attachments
        echo "Adding contents to temp file '$tempFile' as attachments".$br;
        file_put_contents($tempFile, "This is a test attachment content.");

        $mailer->addAttachment($tempFile, 'test.txt');
        $mailer->addStringAttachment("This is a string attachment content.", 'string.txt');
        $mailer->addEmbeddedImage($tempFile, 'test_image', 'test.jpg');

        // Display email details before sending
        echo "Email details:".$br;
        foreach ($mailer->from as $addr => $name) {
            echo "- From: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->to as $addr => $name) {
            echo "- To: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->CC as $addr => $name) {
            echo "- CC: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->BCC as $addr => $name) {
            echo "- BCC: " . $addr . " (" . $name . ")".$br;
        }
        foreach ($mailer->replyTo as $addr => $name) {
            echo "- Reply-To: " . $addr . " (" . $name . ")".$br;
        }
        echo "- Subject: " . $mailer->subject.$br;
        echo "- Body: " . $mailer->body.$br;
        echo "- Alt Body: " . $mailer->altBody.$br;
        echo "- Attachments: " . count($mailer->attachments) . " files".$br;

        $mailer->config = ["debug" => 4];
        echo "Sending email with direct mode: " . ($mailer->send() ? "Success" : "Failed - " . $mailer->lastError) . $br;

    } catch (\Exception $e) {
        echo "Error sending email: " . $e->getMessage() . $br;
    }
    // Clean up temp file
    if (file_exists($tempFile)) {
        echo "Deleting temp file: ".$tempFile.$br;
        unlink($tempFile);
    }
}    
$meow->profiler->record("Send email with direct mode", "Mailer Test");
echo $br;

$meow->profiler->record("Mailer Test Completed", "Mailer Test");

// Test CsvDB class //
echo $br."Test CsvDB class".$br;
echo "--------------------------------".$br;
$meow->profiler->record("CsvDB Test Start", "CsvDB Test");

// Create test CSV file path
$testCsvFile = $meow->config->docRoot . $meow->config->varPath . "/tmp/test_data.csv";

// Create CsvDB object
$csv = new CsvDB($testCsvFile, [
    "name",
    "age",
    "email",
    "status"
]);
echo "CsvDB object created with test file: ".$testCsvFile.$br;

// Test basic operations
echo $br."Testing basic operations:".$br;

// Test adding records
$records = [
    [
        "name" => "John Doe",
        "age" => "30",
        "email" => "john@example.com",
        "status" => "inactive"
    ],
    [
        "name" => "Jane Smith",
        "age" => "25",
        "email" => "jane@mydomain.com",
        "status" => "active"
    ],
    [
        "csvRowID" => 3,
        "name" => "Bob Johnson",
        "age" => "45",
        "email" => "bob@example.com",
        "status" => "pending"
    ]
];

echo "Adding test records:".$br;
foreach ($records as $record) {
    $rowID = $csv->setRow($record);
    echo "Added record with rowID: ".$rowID.$br;
}

var_dump($csv->data);

// Show records //
echo $br."Show records:".$br;
foreach ($csv as $idx => $row) {
    echo "ID($idx): ".$row['csvRowID']." - name=".$row["name"].", age=".$row['age']. ", email=".$row['email'].", status=".$row['status'].$br;
}
$meow->profiler->record("Prepare test records", "CsvDB Test");

// Test saving to file
$errCode = $csv->save();
echo $br."Saving records to file: ".($errCode == 0 ? "Success" : "Failed($errCode) - ".$csv->lastError).$br;

$meow->profiler->record("First time save records", "CsvDB Test");

// Test loading from file
$errCode = $csv->load();
echo $br."Loading records from file: ".($errCode == 0 ? "Success" : "Failed($errCode) - ".$csv->lastError).$br;

// Show records //
echo $br."Show records:".$br;
foreach ($csv as $idx => $row) {
    echo "ID($idx): ".$row['csvRowID']." - name=".$row["name"].", age=".$row['age']. ", email=".$row['email'].", status=".$row['status'].$br;
}

$meow->profiler->record("First time load records", "CsvDB Test");

// Test searching records
echo $br."Testing search functionality:".$br;

// Test simple equality
echo $br."Testing simple equality (status = active):".$br;
$searchResults = $csv->search(["status" => "active"]);
echo "Found ".count($searchResults)." active records:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (status: ".$result["status"].")".$br;
}

// Test comparison operators
echo $br."Testing comparison operators (age > 30):".$br;
$searchResults = $csv->search([
    "age[>]" => 30
]);
echo "Found ".count($searchResults)." records with age > 30:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (age: ".$result["age"].")".$br;
}

// Test multiple conditions
echo $br."Testing multiple conditions (status = active AND age >= 15):".$br;
$searchResults = $csv->search([
    "status" => "active",
    "age[>=]" => 15
]);
echo "Found ".count($searchResults)." active records with status = active AND age >= 15:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (age: ".$result["age"].")".$br;
}

// Test LIKE operator
echo $br."Testing LIKE operator (email ~ example.com):".$br;
$searchResults = $csv->search([
    "email[~]" => "example.com"
]);
echo "Found ".count($searchResults)." records with email LIKE example.com:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (".$result["email"].")".$br;
}

// Test NOT LIKE operator
echo $br."Testing NOT LIKE operator (name !~ Smith):".$br;
$searchResults = $csv->search([
    "name[!~]" => "Smith"
]);
echo "Found ".count($searchResults)." records with name NOT LIKE Smith:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (".$result["email"].")".$br;
}

// Test IN operator
echo $br."Testing equality operator with array (status = [ 'active', 'pending' ]):".$br;
$searchResults = $csv->search([
    "status" => ["active", "pending"]
]);
echo "Found ".count($searchResults)." records with status = active OR status = pending:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (status: ".$result["status"].")".$br;
}

// Test BETWEEN operator
echo $br."Testing BETWEEN operator (age <> [ 25, 35 ]):".$br;
$searchResults = $csv->search([
    "age[<>]" => [25, 35]
]);
echo "Found ".count($searchResults)." records with age between 25 AND 35:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (age: ".$result["age"].")".$br;
}

// Test BETWEEN operator
echo $br."Testing NOT BETWEEN operator (age >< [ 25, 35 ]):".$br;
$searchResults = $csv->search([
    "age[><]" => [25, 35]
]);
echo "Found ".count($searchResults)." records with age not between 25 AND 35:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (age: ".$result["age"].")".$br;
}

// Test NOT operators
echo $br."Testing NOT equal and NOT LIKE operators (status != active AND email !~ example.com):".$br;
$searchResults = $csv->search([
    "status[!=]" => "active",
    "email[!~]" => "example.com"
]);
echo "Found ".count($searchResults)." records with status != active AND email NOT LIKE example.com:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (status: ".$result["status"].", email: ".$result["email"].")".$br;
}

// Test AND operator
echo $br."Testing AND operator (status = active AND age > 30):".$br;
$searchResults = $csv->search([
    'AND' => [
        "status" => "active",
        "age[>]" => 30
    ]
]);
echo "Found ".count($searchResults)." records with status = active AND age > 30:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (status: ".$result["status"].", age: ".$result["age"].")".$br;
}

// Test OR operator
echo $br."Testing OR operator (status = active OR age > 40):".$br;
$searchResults = $csv->search([
    'OR' => [
        "status" => "active",
        "age[>]" => 40
    ]
]);
echo "Found ".count($searchResults)." records with status = active OR age > 40:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (status: ".$result["status"].", age: ".$result["age"].")".$br;
}

// Test complex AND/OR combination
echo $br."Testing complex AND/OR combination (status = pending AND (age > 40 OR email ~ mydomain.com)):".$br;
$searchResults = $csv->search([
    'AND' => [
        "status" => "pending",
        'OR' => [
            "age[>]" => 40,
            "email[~]" => "mydomain.com"
        ]
    ]
]);
echo "Found ".count($searchResults)." records with status = pending AND (age > 40 OR email ~ mydomain.com):".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (status: ".$result["status"].", age: ".$result["age"].", email: ".$result["email"].")".$br;
}

// Test AND/OR with comments
echo $br."Testing complex AND/OR combination with remarks ((status = active OR age > 40) AND (email ~ mydomain.com OR name !~ Smith)):".$br;
$searchResults = $csv->search([
    "AND #Main condition" => [
        "OR #First group" => [
            "status" => "active",
            "age[>]" => 40
        ],
        "OR #Second group" => [
            "email[~]" => "mydomain.com",
            "name[!~]" => "Smith"
        ]
    ]
]);
echo "Found ".count($searchResults)." records with ((status = active OR age > 40) AND (email ~ mydomain.com OR name !~ Smith)):".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (status: ".$result["status"].", age: ".$result["age"].", email: ".$result["email"].")".$br;
}

// Test search with sorting
echo $br."Testing search with sorting (email ~ example.com => sort by age in descending order):".$br;
$searchResults = $csv->search(["email[~]" => "example.com"], "age", false);
echo "Found ".count($searchResults)." records sorted by age in descending order:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (age: ".$result["age"].")".$br;
}
echo $br."Testing search with sorting (email ~ example.com => sort by age in ascending order):".$br;
$searchResults = $csv->search(["email[~]" => "example.com"], "age");
echo "Found ".count($searchResults)." records sorted by age in ascending order:".$br;
foreach ($searchResults as $result) {
    echo "- ".$result["name"]." (age: ".$result["age"].")".$br;
}

$meow->profiler->record("Search records", "CsvDB Test");

// Test queue operations
echo $br."Testing queue operations (add, update, delete):".$br;

// Queue append
$newRec = [
    "name" => "Alice Brown",
    "age" => "28",
    "email" => "alice@example.com",
    "status" => "active"
];
$csv->queueAppend($newRec);
echo "Queued new record for append: ".json_encode($newRec)."<br>";

// Queue update
$updateRec = ["status" => "active", "age" => "18"];
$csv->queueUpdate(
    ["email" => "john@example.com"],
    $updateRec
);
echo "Queued update for John's record: ".json_encode($updateRec)."<br>";

// Queue delete
$csv->queueDelete(["email" => "bob@example.com"]);
echo "Queued deletion of Bob's record".$br;

$meow->profiler->record("Prepare queue operations", "CsvDB Test");

// Run queue
echo $br."Running queue operations:".$br;
$queueResult = $csv->runQueue();
echo "Queue operation results:".$br;
echo "- Added: ".count($queueResult["add"])." records".$br;
echo "- Updated: ".count($queueResult["update"])." records".$br;
echo "- Deleted: ".count($queueResult["del"])." records".$br;

// Show records //
echo $br."Show records:".$br;
foreach ($csv as $idx => $row) {
    echo "ID($idx): ".$row['csvRowID']." - name=".$row["name"].", age=".$row['age']. ", email=".$row['email'].", status=".$row['status'].$br;
}

$meow->profiler->record("Perform queue operations", "CsvDB Test");


// Test iterator functionality
echo $br."Testing iterator functionality:".$br;
echo "Iterating through all records:".$br;
foreach ($csv as $row) {
    echo "- ".$row["name"]." (".$row["email"].")".$br;
}

$meow->profiler->record("Use iterator to show records", "CsvDB Test");

// Test getting specific row
echo $br."Testing getRow functionality:".$br;
echo "Get Row 1 by getRow(1):".$br;
$row = $csv->getRow(1);
if ($row !== false) {
    echo "Row 1: ".$row["name"]." (".$row["email"].")".$br;
} else {
    echo "Row 1 not found".$br;
}
echo "Get Row 10 by getRow(10):".$br;
$row = $csv->getRow(10);
if ($row !== false) {
    echo "Row 10: ".$row["name"]." (".$row["email"].")".$br;
} else {
    echo "Row 10 not found".$br;
}

// Test generate empty record
echo $br."Testing genEmptyRec functionality:".$br;
$emptyRec = $csv->genEmptyRec();
echo "Empty record structure:".$br;
print_r($emptyRec);

// Create record with empty record
echo $br."Testing create record with empty record:".$br;
$emptyRec = $csv->genEmptyRec();
$emptyRec["csvRowID"] = 11;
$emptyRec["name"] = "Empty Record";
$emptyRec["age"] = "30";
$emptyRec["email"] = "empty@example.com";
$emptyRec["status"] = "empty";
$csv->setRow($emptyRec);
echo "Empty record structure:".$br;
var_dump($emptyRec);

// Show records //
echo $br."Show records by direct access after create record:".$br;
var_dump($csv->data);

// Add one more new record
$newRec = [
    "name" => "New Record",
    "age" => "16",
    "email" => "new@example.com",
    "status" => "new"
];
$csv->setRow($newRec, 7);

// Show records //
echo $br."Show records:".$br;
foreach ($csv as $idx => $row) {
    echo "ID($idx): ".$row['csvRowID']." - name=".$row["name"].", age=".$row['age']. ", email=".$row['email'].", status=".$row['status'].$br;
}

$meow->profiler->record("Second time prepare test records", "CsvDB Test");

// Save records to file
echo $br."Saving records to file:".$br;
$errCode = $csv->save();
echo "Saving records to file: ".($errCode == 0 ? "Success" : "Failed($errCode) - ".$csv->lastError).$br;

$meow->profiler->record("Second time save records", "CsvDB Test");

// Load records from file
echo $br."Loading records from file:".$br;
$errCode = $csv->load();
echo "Loading records from file: ".($errCode == 0 ? "Success" : "Failed($errCode) - ".$csv->lastError).$br;

// Show records //
echo $br."Show records:".$br;
foreach ($csv as $idx => $row) {
    echo "ID($idx): ".$row['csvRowID']." - name=".$row["name"].", age=".$row['age']. ", email=".$row['email'].", status=".$row['status'].$br;
}

$meow->profiler->record("Second time load records", "CsvDB Test");

// Test sorting records
echo $br."Testing rowID sorting:".$br;
echo "Sorting rowID in dscending order:".$br;
$csv->sortByRowID(false);
foreach ($csv as $idx => $row) {
    echo "ID($idx): ".$row['csvRowID']." - name=".$row["name"].", age=".$row['age']. ", email=".$row['email'].", status=".$row['status'].$br;
}
$csv->sortByRowID(true);
echo "Sorted rowID in ascending order:".$br;
foreach ($csv as $idx => $row) {
    echo "ID($idx): ".$row['csvRowID']." - name=".$row["name"].", age=".$row['age']. ", email=".$row['email'].", status=".$row['status'].$br;
}

$meow->profiler->record("RowID sorting", "CsvDB Test");

// Clean up
echo $br."Cleaning up test files:".$br;
if (file_exists($testCsvFile)) {
    unlink($testCsvFile);
    echo "Test CSV file deleted".$br;
}

$meow->profiler->record("CsvDB Test Completed", "CsvDB Test");

//--- Test UserCSV Function ---//
echo $br."USER CLASS TEST (CSV Storage)".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserCSV Test Start", "UserCsv Test");

// Initialize UserCSV with standard User class configuration //
echo "Initializing UserCSV...".$br;
$userCSV = new UserCSV($meow->configTree['user']['user']);
echo $br;

// Clear old user data //
$csvFileExists = false;
if (file_exists($userCSV->userTableFile)) {
    echo "Remove old user csv file: ".$userCSV->userTableFile.$br;
    unlink($userCSV->userTableFile);       
    $csvFileExists = true;
}
if (file_exists($userCSV->userGroupTableFile)) {
    echo "Remove old user group csv file: ".$userCSV->userGroupTableFile.$br;
    unlink($userCSV->userGroupTableFile);
    $csvFileExists = true;
}
if (file_exists($userCSV->userGroupLinkTableFile)) {
    echo "Remove old user group link csv file: ".$userCSV->userGroupLinkTableFile.$br;
    unlink($userCSV->userGroupLinkTableFile);
    $csvFileExists = true;
}
if ($csvFileExists) {
    echo "User csv file exists, re-initialize UserCSV...".$br;
    $userCSV = new UserCSV($meow->configTree['user']['user']);
}
echo "UserCSV initialized successfully".$br.$br;

// Display field name mapping in UserCSV class
echo "Field name mapping in UserCSV class:".$br;
$userFields = $userCSV->userFields;
foreach ($userFields as $key => $value) {
    echo "  $key => $value".$br;
}
echo $br;

// Test 1.1: Create Users
$meow->profiler->record("Ready for user testing", "UserCsv Test");
echo "Test 1.1: Create Users".$br;
echo "---------------------".$br;

// Create first user in UserCSV class
$user1Data = [
    'userName' => "testuser1",
    'email'    => "user1@test.com",
];

$user1ID = $userCSV->createUser("user1", "Password123!", $user1Data);
if ($user1ID > 0) {
    echo "User 1 created successfully with ID: $user1ID".$br;
} else {
    echo "Failed to create User 1: " . $userCSV->lastError . $br;
}

// Create second user using standard userFields
$user2Data = [
    'userName' => "testuser2", 
    'email'    => "user2@test.com"
];
$user2ID = $userCSV->createUser("user2", "SecurePass456!", $user2Data);
if ($user2ID > 0) {
    echo "User 2 created successfully with ID: $user2ID".$br;
} else {
    echo "Failed to create User 2: " . $userCSV->lastError . $br;
}

// Create third user using standard userFields
$user3Data = [
    'userName' => "testuser3",
    'email'    => "user3@test.com"
];
$user3ID = $userCSV->createUser("user3", "MyPass789!", $user3Data);
if ($user3ID > 0) {
    echo "User 3 created successfully with ID: $user3ID".$br;
} else {
    echo "Failed to create User 3: " . $userCSV->lastError . $br;
}

$meow->profiler->record("Create user test completed", "UserCsv Test");

// Get User Information
echo "Read User Information:".$br;

// Get user by ID (normalization function handles field name conversion)
$user1 = $userCSV->getUserByID($user1ID);
if ($user1) {
    echo "Get User 1 by ID: $user1ID".$br;
    var_dump($user1);
} else {
    echo "Failed to get User 1 by ID".$br;
}

// Get user by login name (normalization function handles field name conversion)
$loginName = "user2";
$user2 = $userCSV->getUserByLoginName($loginName);
if ($user2) {
    echo "Get User 2 by login name: '$loginName'".$br;
    var_dump($user2);
} else {
    echo "Failed to get User 2 by login name '$loginName'".$br;
}
echo $br;

$meow->profiler->record("Read user test completed", "UserCsv Test");
echo $br;

// Test 4: Update User Tests
echo "Update User Tests:".$br;

echo "Updating user 1 by ID: $user1ID...".$br;
$updateData = [
    'userName' => "updateduser1",
    'email' => "updated1@test.com",
    'extraData' => "This is extra data for user 1"
];
echo "Update data:".$br;
var_dump($updateData);
$updateResult = $userCSV->updateUser($updateData, $user1ID);
if ($updateResult) {
    echo "User 1 updated successfully by ID $user1ID".$br;
    // Get updated user info
    $updatedUser = $userCSV->getUserByID($user1ID);
    if ($updatedUser) {
        var_dump($updatedUser);
    }
    
    // Get updated user to verify
    $updatedUserCheck = $userCSV->getUserByID($user1ID, true);
    if ($updatedUserCheck) {
        echo "Updated user verification:".$br;
        var_dump($updatedUserCheck);
    }
} else {
    echo "Failed to update User 1 as current user: " . $userCSV->lastError . $br;
}
echo $br;

// Update user by specific ID (use logical field names)
echo "Updating user 2 by ID: $user2ID...".$br;
$updateData2 = [
    'userName' => "updateduser2",
    'email' => "updated2@test.com"
];
echo "Update data:".$br;
var_dump($updateData2);
echo $br;
$updateResult2 = $userCSV->updateUser($updateData2, $user2ID);
if ($updateResult2) {
    echo "User 2 updated successfully by ID $user2ID".$br;
    // Get updated user info (normalization function handles field name conversion)
    $updatedUser2 = $userCSV->getUserByID($user2ID);
    if ($updatedUser2) {
        echo "Updated userName: " . $updatedUser2['userName'] . $br;
        echo "Updated email: " . $updatedUser2['email'] . $br;
    }
} else {
    echo "Failed to update User 2 by ID: " . $userCSV->lastError . $br;
}
echo $br;

// Test updating current user with additional fields (use logical field names)
echo "Testing update current user with additional fields...".$br;
$additionalUpdateData = [
    'status' => User::USER_STATUS_ACTIVE,
    'lastActive' => time() - 10,
];
var_dump($additionalUpdateData);
// Note: updateUser now requires userID parameter
$additionalUpdateResult = $userCSV->updateUser($additionalUpdateData, $user1ID);
if ($additionalUpdateResult) {
    echo "Current user additional fields updated successfully by ID $user1ID".$br;
    // Get updated user info (normalization function handles field name conversion)
    $updatedUser = $userCSV->getUserByID($user1ID);
    if ($updatedUser) {
        echo "Updated status: " . ($updatedUser['status'] ?? 'N/A') . $br;
        echo "Updated last_active: " . ($updatedUser['lastActive'] ?? 'N/A') . $br;
    }
} else {
    echo "Failed to update current user additional fields by ID $user1ID: " . $userCSV->lastError . $br;
}

$meow->profiler->record("Update user test completed", "UserCsv Test");
echo $br;

// Delete User Tests
echo "Delete User Tests:".$br;

// Show all records before delete test
echo "Records before delete test:".$br;
$remainingUsers = $userCSV->getMultiUserByID();
if (is_array($remainingUsers)) {
    var_dump($remainingUsers);
} else {
    echo "Cannot load user records!".$br;
}   
echo $br;

// Delete user by specific ID
echo "Deleting user 3 by specific ID $user3ID...".$br;
$deleteResult = $userCSV->delUser($user3ID);
if ($deleteResult) {
    echo "User 3 deleted successfully".$br;
    
    // Verify user is deleted
    $deletedUser = $userCSV->getUserByID($user3ID);
    if (!$deletedUser) {
        echo "User 3 confirmed deleted (not found in database)".$br;
    } else {
        echo "User 3 still exists in database (unexpected)".$br;
    }
} else {
    echo "Failed to delete User 3: " . $userCSV->lastError . $br;
}

echo "Records after delete User 3:".$br;
$remainingUsers = $userCSV->getMultiUserByID();
if (is_array($remainingUsers)) {
    var_dump($remainingUsers);
} else {
    echo "Cannot load user records!".$br;
}   
echo $br;

// Delete user by ID directly
echo "Deleting user 1 by ID $user1ID...".$br;
$deleteUser1Result = $userCSV->delUser($user1ID);
if ($deleteUser1Result) {
    echo "User 1 deleted successfully by ID".$br;
    // Verify user is deleted
    $deletedUser1 = $userCSV->getUserByID($user1ID);
    if (!$deletedUser1) {
        echo "User 1 confirmed deleted (not found in database)".$br;
    } else {
        echo "User 1 still exists in database (unexpected)".$br;
    }
} else {
    echo "Failed to delete User 1: " . $userCSV->lastError . $br;
}
echo "Records after delete current user:".$br;

// Note: userDB is protected, using getUserByID to verify instead
$remainingUsers = $userCSV->getMultiUserByID();
if (is_array($remainingUsers)) {
    var_dump($remainingUsers);
} else {
    echo "Cannot load user records!".$br;
}   

$meow->profiler->record("Delete user test completed", "UserCsv Test");
echo $br;

// Final Verification
echo "Final Verification".$br;
// Check remaining users
echo "Checking remaining users in database...".$br;
$remainingUser = $userCSV->getUserByID($user2ID);
if ($remainingUser) {
    echo "User 2 still exists: " . $remainingUser['userName'] . " (" . $remainingUser['email'] . ")".$br;
} else {
    echo "User 2 not found (may have been deleted)".$br;
}
echo $br;

//--- Test UserGroupCSV Function ---//
echo $br."USERGROUP CLASS TEST (CSV Storage)".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserGroupCSV Test Start", "UserGroupCsv Test");

// Initialize UserGroupCSV //
$userGroupCSVConfig = $meow->configTree['user']['userGroup'] ?? [];
$userGroupCSV = new UserGroupCSV($userGroupCSVConfig);

// Clean up old CSV files if they exist //
$csvGroupTableFile = $userGroupCSV->userGroupTableFile ?? "";
$csvGroupLinkFile = $userGroupCSV->userGroupLinkTableFile ?? "";
if ($csvGroupTableFile && file_exists($csvGroupTableFile)) {
    unlink($csvGroupTableFile);
    echo "Removed old group csv file: $csvGroupTableFile".$br;
}
if ($csvGroupLinkFile && file_exists($csvGroupLinkFile)) {
    unlink($csvGroupLinkFile);
    echo "Removed old group link csv file: $csvGroupLinkFile".$br;
}

// Re-initialize after cleanup so CsvDB rebuilds headers //
$userGroupCSV = new UserGroupCSV($userGroupCSVConfig);

// Create user groups //
echo "Creating CSV user groups...".$br;
$csvAdminGroupID = $userGroupCSV->createUserGroup("csv_admin_group", ['groupDesc' => "CSV Admin Group"]);
if ($csvAdminGroupID > 0) {
    echo "CSV admin group created successfully with ID: $csvAdminGroupID".$br;
} else {
    echo "Failed to create CSV admin group: " . $userGroupCSV->lastError . $br;
}
$csvEditorGroupID = $userGroupCSV->createUserGroup("csv_editor_group", ['groupDesc' => "CSV Editor Group"]);
if ($csvEditorGroupID > 0) {
    echo "CSV editor group created successfully with ID: $csvEditorGroupID".$br;
} else {
    echo "Failed to create CSV editor group: " . $userGroupCSV->lastError . $br;
}
echo $br;

// Read user groups //
echo "Reading CSV user groups by ID $csvAdminGroupID...".$br;
$csvAdminGroup = $userGroupCSV->getUserGroupByID($csvAdminGroupID);
echo "Admin group record:".$br;
var_dump($csvAdminGroup);
echo "Reading CSV user groups by name 'csv_editor_group'...".$br;
$csvEditorGroup = $userGroupCSV->getUserGroupByName("csv_editor_group");
echo "Editor group record (by name):".$br;
var_dump($csvEditorGroup);
echo $br;

// Determine a user ID to test links with //
echo "Determining a user ID to test links with...".$br;
$csvGroupUserID = $user2ID ?? $user1ID ?? null;
if (!$csvGroupUserID) {
    $helperData = [
        'userName' => "csvgroupuser",
        'email' => "csvgroupuser@test.com"
    ];
    $csvGroupUserID = $userCSV->createUser("csvgroupuser", "CsvGroupPass123!", $helperData);
    echo "Created helper user for group testing with ID: $csvGroupUserID".$br;
}

// Add user to groups //
echo "Adding user $csvGroupUserID to CSV admin group...".$br;
$csvAddAdmin = $userGroupCSV->addUserToGroup($csvGroupUserID, $csvAdminGroupID);
echo "Add to admin group result: " . ($csvAddAdmin ? "Success" : "Failed - " . $userGroupCSV->lastError) . $br;

echo "Adding user $csvGroupUserID to CSV editor group...".$br;
$csvAddEditor = $userGroupCSV->addUserToGroup($csvGroupUserID, $csvEditorGroupID);
echo "Add to editor group result: " . ($csvAddEditor ? "Success" : "Failed - " . $userGroupCSV->lastError) . $br;

// Check membership //
$csvIsInAdmin = $userGroupCSV->isUserInGroup($csvGroupUserID, $csvAdminGroupID);
echo "Is user $csvGroupUserID in admin group? " . ($csvIsInAdmin ? "Yes" : "No") . $br;
$csvIsInEditor = $userGroupCSV->isUserInGroup($csvGroupUserID, $csvEditorGroupID);
echo "Is user $csvGroupUserID in editor group? " . ($csvIsInEditor ? "Yes" : "No") . $br;

// List groups for user //
$csvGroupsForUser = $userGroupCSV->getGroupsByUser($csvGroupUserID);
echo "Groups for user $csvGroupUserID:".$br;
var_dump($csvGroupsForUser);

// List users for admin group //
$csvUsersInAdmin = $userGroupCSV->getUsersInGroup($csvAdminGroupID);
echo "Users in admin group $csvAdminGroupID:".$br;
var_dump($csvUsersInAdmin);

// Remove user from group //
echo "Removing user $csvGroupUserID from CSV admin group...".$br;
$csvRemoveResult = $userGroupCSV->delUserFromGroup($csvGroupUserID, $csvAdminGroupID);
echo "Remove result: " . ($csvRemoveResult ? "Success" : "Failed - " . $userGroupCSV->lastError) . $br;
$csvIsInAdminAfter = $userGroupCSV->isUserInGroup($csvGroupUserID, $csvAdminGroupID);
echo "Is user still in admin group? " . ($csvIsInAdminAfter ? "Yes (unexpected)" : "No (expected)") . $br;

$meow->profiler->record("UserGroupCSV Test Completed", "UserGroupCsv Test");
echo $br;

//--- Test UserPermCSV Function ---//
echo $br."USERPERM CLASS TEST (CSV Storage)".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserPermCSV Test Start", "UserPermCSV Test");

// Initialize UserPermCSV with standard configuration //
echo "Initializing UserPermCSV...".$br;
$userPermConfig = $meow->configTree['user']['userPerm'] ?? [];
$userPermCSV = new UserPermCSV($userPermConfig);
// Clear old permission data //
if (file_exists($userPermCSV->userPermTableFile)) {
    echo "Remove old user permission csv file: ".$userPermCSV->userPermTableFile.$br;
    unlink($userPermCSV->userPermTableFile);
}
if (file_exists($userPermCSV->userGroupPermTableFile)) {
    echo "Remove old user group permission csv file: ".$userPermCSV->userGroupPermTableFile.$br;
    unlink($userPermCSV->userGroupPermTableFile);
}

$userPermAdminGroupID = 1;
$userPermEditorGroupID = 2;
$userPermViewerGroupID = 3;
$userPermCSV = new UserPermCSV($userPermConfig);
echo "UserPermCSV initialized successfully".$br.$br;
$meow->profiler->record("Ready for user permission testing", "UserPermCSV Test");

// This section tests basic UserPerm CRUD operations

// Get a userID from previous tests (use user2ID if available, otherwise use 1)
$testUserID = $user2ID;

// Test 1: User Permission Management (basic CRUD)
echo "User Permission Management (Basic CRUD):".$br;

// Set user permissions (new API requires userID as first parameter)
echo "Setting user permissions for user ID $testUserID, 'articles' item...".$br;
$setResult1 = $userPermCSV->setUserPerm($testUserID, "articles", "read", 1);
if ($setResult1) {
    echo "Set articles read permission: Success (read permission: 1)".$br;
} else {
    echo "Set articles read permission failed: " . $userPermCSV->lastError . $br;
}

$setResult2 = $userPermCSV->setUserPerm($testUserID, "articles", "write", 1);
if ($setResult2) {
    echo "Set articles write permission: Success (write permission: 1)".$br;
} else {
    echo "Set articles write permission failed: " . $userPermCSV->lastError . $br;
}

$setResult3 = $userPermCSV->setUserPerm($testUserID, "articles", "delete", 0);
if ($setResult3) {
    echo "Set articles delete permission: Success (delete permission: 0)".$br;
} else {
    echo "Set articles delete permission failed: " . $userPermCSV->lastError . $br;
}

// Set permissions for another item
echo "Setting user permissions for user ID $testUserID, 'users' item...".$br;
$setResult4 = $userPermCSV->setUserPerm($testUserID, "users", "read", 1);
$setResult5 = $userPermCSV->setUserPerm($testUserID, "users", "write", 0);
$setResult6 = $userPermCSV->setUserPerm($testUserID, "users", "delete", 0);

echo "Set users read permission (read permission: 1): " . ($setResult4 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Set users write permission (write permission: 0): " . ($setResult5 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Set users delete permission (delete permission: 0): " . ($setResult6 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;

$meow->profiler->record("Set user permissions", "UserPermCSV Test");
echo $br;

// Test 2: Get User Permissions (new API returns array)
echo "Get User Permissions:".$br;

// Get all permissions for an item (new API requires userID as first parameter)
$articlePerms = $userPermCSV->getUserPerm($testUserID, "articles");
echo "All article permissions for user $testUserID: ".$br;
if ($articlePerms && is_array($articlePerms)) {
    echo "  read: " . ($articlePerms['read'] ?? "NULL") . $br;
    echo "  write: " . ($articlePerms['write'] ?? "NULL") . $br;
    echo "  delete: " . ($articlePerms['delete'] ?? "NULL") . $br;
    echo "articlePerms:<br>";
    var_dump($articlePerms);
} else {
    echo "  No permissions found".$br;
}

$userPerms = $userPermCSV->getUserPerm($testUserID, "users");
echo "All user permissions for user $testUserID: ".$br;
if ($userPerms && is_array($userPerms)) {
    echo "  read: " . ($userPerms['read'] ?? "NULL") . $br;
    echo "  write: " . ($userPerms['write'] ?? "NULL") . $br;
    echo "  delete: " . ($userPerms['delete'] ?? "NULL") . $br;
    echo "userPerms:<br>";
    var_dump($userPerms);
} else {
    echo "  No permissions found".$br;
}

$meow->profiler->record("Get user permissions", "UserPermCSV Test");
echo $br;

// Group Permissions
echo "Group Permissions:".$br;

// Set group permissions
echo "Setting admin group ($userPermAdminGroupID) permissions for 'articles'...".$br;
$setGroupResult1 = $userPermCSV->setGroupPerm($userPermAdminGroupID, "articles", "read", 1);
$setGroupResult2 = $userPermCSV->setGroupPerm($userPermAdminGroupID, "articles", "write", 1);
$setGroupResult3 = $userPermCSV->setGroupPerm($userPermAdminGroupID, "articles", "delete", 1);

echo "Set admin articles read permission to 1: " . ($setGroupResult1 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Set admin articles write permission to 1: " . ($setGroupResult2 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Set admin articles delete permission to 1: " . ($setGroupResult3 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;

echo "Setting editor group ($userPermEditorGroupID) permissions for 'articles'...".$br;
$setGroupResult4 = $userPermCSV->setGroupPerm($userPermEditorGroupID, "articles", "read", 1);
$setGroupResult5 = $userPermCSV->setGroupPerm($userPermEditorGroupID, "articles", "write", 1);
$setGroupResult6 = $userPermCSV->setGroupPerm($userPermEditorGroupID, "articles", "delete", 0);

echo "Set editor articles read permission to 1: " . ($setGroupResult4 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Set editor articles write permission to 1: " . ($setGroupResult5 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Set editor articles delete permission to 0: " . ($setGroupResult6 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo $br;

// Get group permissions
$adminArticlePerms = $userPermCSV->getGroupPerm($userPermAdminGroupID, "articles");
echo "Admin article permissions: ".$br;
if ($adminArticlePerms && is_array($adminArticlePerms)) {
    var_dump($adminArticlePerms);
} else {
    echo "  No permissions found".$br;
}

$editorArticlePerms = $userPermCSV->getGroupPerm($userPermEditorGroupID, "articles");
echo "Editor article permissions: ".$br;
if ($editorArticlePerms && is_array($editorArticlePerms)) {
    var_dump($editorArticlePerms);
} else {
    echo "  No permissions found".$br;
}

$meow->profiler->record("Group permissions", "UserPermCSV Test");
echo $br;

// Delete Operations (basic CRUD)
echo "Delete Operations:".$br;

// Delete specific permission
echo "Deleting articles write permission for user $testUserID...".$br;
$deleteResult1 = $userPermCSV->delUserPerm($testUserID, "articles", "write");

// Verify the delete
echo "Verify the delete:".$br;
$articlePermsAfterDelete = $userPermCSV->getUserPerm($testUserID, "articles");
echo "Articles permissions after delete: ".$br;
if ($articlePermsAfterDelete && is_array($articlePermsAfterDelete)) {
    echo "  read: " . ($articlePermsAfterDelete['read'] ?? "NULL") . $br;
    echo "  write: " . ($articlePermsAfterDelete['write'] ?? "NULL") . $br;
    echo "  delete: " . ($articlePermsAfterDelete['delete'] ?? "NULL") . $br;
    echo "articlePermsAfterDelete:<br>";
    var_dump($articlePermsAfterDelete);
} else {
    echo "  No permissions found".$br;
}
echo $br;

// Check if permission is deleted
$articlePermsAfterDelete = $userPermCSV->getUserPerm($testUserID, "articles");
echo "Articles permissions after delete: ".$br;
if ($articlePermsAfterDelete && is_array($articlePermsAfterDelete)) {
    echo "  read: " . ($articlePermsAfterDelete['read'] ?? "NULL") . $br;
    echo "  write: " . ($articlePermsAfterDelete['write'] ?? "NULL") . $br;
    echo "  delete: " . ($articlePermsAfterDelete['delete'] ?? "NULL") . $br;
    echo "articlePermsAfterDelete:<br>";
    var_dump($articlePermsAfterDelete);
} else {
    echo "  No permissions found".$br;
}

// Delete all permissions for an item
echo "Deleting all permissions for user $testUserID...".$br;
$deleteResult2 = $userPermCSV->delUserPerm($testUserID, "users");
echo "Delete all permissions: " . ($deleteResult2 ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Verify the delete:".$br;
$userPermsAfterDelete = $userPermCSV->getUserPerm($testUserID, "users");
echo "Users permissions after delete: ".$br;
if ($userPermsAfterDelete && is_array($userPermsAfterDelete)) {
    echo "  read: " . ($userPermsAfterDelete['read'] ?? "NULL") . $br;
    echo "  write: " . ($userPermsAfterDelete['write'] ?? "NULL") . $br;
    echo "  delete: " . ($userPermsAfterDelete['delete'] ?? "NULL") . $br;
    echo "userPermsAfterDelete:<br>";
    var_dump($userPermsAfterDelete);
} else {
    echo "  No permissions found".$br;
}
echo $br;

// Delete group permission
echo "Deleting editor group articles delete permission ...".$br;
$deleteGroupResult = $userPermCSV->delGroupPerm($userPermEditorGroupID, "articles", "delete");
echo "Delete editor group articles delete permission: " . ($deleteGroupResult ? "Success" : "Failed - " . $userPermCSV->lastError) . $br;
echo "Verify the delete:".$br;
$editorGroupArticlePermsAfterDelete = $userPermCSV->getGroupPerm($userPermEditorGroupID, "articles");
echo "Editor group article permissions after delete: ".$br;
if ($editorGroupArticlePermsAfterDelete && is_array($editorGroupArticlePermsAfterDelete)) {
    echo "  read: " . ($editorGroupArticlePermsAfterDelete['read'] ?? "NULL") . $br;
    echo "  write: " . ($editorGroupArticlePermsAfterDelete['write'] ?? "NULL") . $br;
    echo "  delete: " . ($editorGroupArticlePermsAfterDelete['delete'] ?? "NULL") . $br;
    echo "editorGroupArticlePermsAfterDelete:<br>";
    var_dump($editorGroupArticlePermsAfterDelete);
} else {
    echo "  No permissions found".$br;
}
echo $br;

$meow->profiler->record("Delete operations", "UserPermCSV Test");
echo $br;

$meow->profiler->record("UserPermCSV Test Completed", "UserPermCSV Test");

//--- Test UserDB Function ---//
echo $br."USER CLASS TEST (Database Storage)".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserDB Test Start", "UserDB Test");

// Initialize UserDB with standard User class configuration //
echo "Initializing UserDB...".$br;
$userDB = new UserDB($meow->db, $meow->configTree['user']['user']);
echo $br;

// Clear old user data from database //
echo "Clearing old user data from database...".$br;
$meow->db->delete($userDB->userTable, []);
echo "UserDB initialized successfully".$br.$br;

// Display standard user fields configuration
echo "Using standard User class userFields configuration:".$br;
$userFields = $userDB->userFields;
foreach ($userFields as $key => $value) {
    echo "  $key => $value".$br;
}
echo $br;

// Create Users Test //
$meow->profiler->record("Ready for user testing", "UserDB Test");
echo "Creating Users:".$br;

// Create first user (use logical field names - normalization function handles conversion)
$user1Data = [
    'userName' => "testuser1",
    'email'    => "user1@test.com",
];
var_dump($user1Data);
$user1ID = $userDB->createUser("user1", "Password123!", $user1Data);
if ($user1ID > 0) {
    echo "User 1 created successfully with ID: $user1ID".$br;
} else {
    echo "Failed to create User 1: " . $userDB->lastError . $br;
}

// Create second user (use logical field names)
$user2Data = [
    'userName' => "testuser2", 
    'email' => "user2@test.com"
];
var_dump($user2Data);
$user2ID = $userDB->createUser("user2", "SecurePass456!", $user2Data);
if ($user2ID > 0) {
    echo "User 2 created successfully with ID: $user2ID".$br;
} else {
    echo "Failed to create User 2: " . $userDB->lastError . $br;
}

// Create third user (use logical field names)
$user3Data = [
    'userName' => "testuser3",
    'email' => "user3@test.com"
];
var_dump($user3Data);
$user3ID = $userDB->createUser("user3", "MyPass789!", $user3Data);
if ($user3ID > 0) {
    echo "User 3 created successfully with ID: $user3ID".$br;
} else {
    echo "Failed to create User 3: " . $userDB->lastError . $br;
}

$meow->profiler->record("Create user test completed", "UserDB Test");
echo $br;

// Test 2: Get User Information
echo "Read User Information:".$br;

// Get user by ID (normalization function handles field name conversion)
$user1 = $userDB->getUserByID($user1ID);
if ($user1) {
    echo "Get User 1 by ID $user1ID: " . $user1['userName'] . " (" . $user1['email'] . ")".$br;
} else {
    echo "Failed to get User 1 by ID $user1ID".$br;
}
var_dump($user1);

// Get user by login name (normalization function handles field name conversion)
$loginName = "user2";
$user2 = $userDB->getUserByLoginName($loginName);
if ($user2) {
    echo "Get User 2 by login name $loginName: " . $user2['userName'] . " (" . $user2['email'] . ")".$br;
} else {
    echo "Failed to get User 2 by login name $loginName".$br;
}
var_dump($user2);

$meow->profiler->record("Read user test completed", "UserDB Test");

// Test 3: Update User Tests
echo "Update User Tests:".$br;

echo "Updating user 1 by ID $user1ID...".$br;
$updateData = [
    'userName' => "updateduser1",
    'email' => "updated1@test.com",
    'extraData' => "This is extra data for user 1"
];
var_dump($updateData);
$updateResult = $userDB->updateUser($updateData, $user1ID);
if ($updateResult) {
    echo "User 1 updated successfully by ID $user1ID".$br;
    // Get updated user to verify
    $updatedUserCheck = $userDB->getUserByID($user1ID);
    if ($updatedUserCheck) {
        echo "Updated user verification:".$br;
        var_dump($updatedUserCheck);
    }
} else {
    echo "Failed to update User 1 as current user: " . $userDB->lastError . $br;
}
echo $br;

// Update user by specific ID (use logical field names)
echo "Updating user 2 by specific ID $user2ID...".$br;
$updateData2 = [
    'userName' => "updateduser2",
    'email' => "updated2@test.com"
];
var_dump($updateData2);
$updateResult2 = $userDB->updateUser($updateData2, $user2ID);
if ($updateResult2) {
    echo "User 2 updated successfully by ID $user2ID".$br;
    // Get updated user info (normalization function handles field name conversion)
    $updatedUser2 = $userDB->getUserByID($user2ID);
    if ($updatedUser2) {
        echo "Updated userName: " . $updatedUser2['userName'] . $br;
        echo "Updated email: " . $updatedUser2['email'] . $br;
    }
} else {
    echo "Failed to update User 2 by ID $user2ID: " . $userDB->lastError . $br;
}
echo $br;

// Test updating current user with additional fields (use logical field names)
echo "Testing update current user with additional fields...".$br;
$additionalUpdateData = [
    'status' => "active",
    'lastActive' => time() - 10,
];
var_dump($additionalUpdateData);
$additionalUpdateResult = $userDB->updateUser($additionalUpdateData, $user1ID);
if ($additionalUpdateResult) {
    echo "Current user additional fields updated successfully".$br;
    // Get updated user info (normalization function handles field name conversion)
    $updatedUser = $userDB->getUserByID($user1ID);
    if ($updatedUser) {
        echo "Updated status: " . ($updatedUser['status'] ?? 'N/A') . $br;
        echo "Updated last_active: " . ($updatedUser['lastActive'] ?? 'N/A') . $br;
    }
} else {
    echo "Failed to update current user additional fields: " . $userDB->lastError . $br;
}

$meow->profiler->record("Update user test completed", "UserDB Test");
echo $br;

// Test 7: Delete User Tests
echo "Delete User Tests:".$br;
// Show all records before delete test
echo "Records before delete test:".$br;
$remainingUsers = $userDB->getMultiUserByID();
if (is_array($remainingUsers)) {
    var_dump($remainingUsers);
} else {
    echo "Cannot load user records!".$br;
}   
echo $br;

// Delete user by specific ID
echo "Deleting user 3 by specific ID $user3ID...".$br;
$deleteResult = $userDB->delUser($user3ID);
if ($deleteResult) {
    echo "User 3 deleted successfully by ID $user3ID".$br;
    
    // Verify user is deleted
    $deletedUser = $userDB->getUserByID($user3ID);
    if (!$deletedUser) {
        echo "User 3 confirmed deleted (not found in database) by ID $user3ID".$br;
    } else {
        echo "User 3 still exists in database (unexpected) by ID $user3ID".$br;
    }
} else {
    echo "Failed to delete User 3 by ID $user3ID: " . $userDB->lastError . $br;
}
echo "Records after delete User 3:".$br;
$remainingUsers = $userDB->getMultiUserByID();
if (is_array($remainingUsers)) {
    var_dump($remainingUsers);
} else {
    echo "Cannot load user records!".$br;
}   
echo $br;

// Delete user by ID directly
echo "Deleting user 1 by ID $user1ID...".$br;
$deleteUser1Result = $userDB->delUser($user1ID);
if ($deleteUser1Result) {
    echo "User 1 deleted successfully by ID $user1ID".$br;
    // Verify user is deleted
    $deletedUser1 = $userDB->getUserByID($user1ID);
    if (!$deletedUser1) {
        echo "User 1 confirmed deleted (not found in database) by ID $user1ID".$br;
    } else {
        echo "User 1 still exists in database (unexpected) by ID $user1ID".$br;
    }
} else {
    echo "Failed to delete User 1 by ID $user1ID: " . $userDB->lastError . $br;
}

echo "Records after delete User 1:".$br;
$remainingUsers = $userDB->getMultiUserByID();
if (is_array($remainingUsers)) {
    var_dump($remainingUsers);
} else {
    echo "Cannot load user records!".$br;
}   

$meow->profiler->record("Delete user test completed by ID $user1ID", "UserDB Test");
echo $br;

// Test 8: Final Verification
echo "Final Verification".$br;
// Check remaining users
echo "Checking remaining users in database...".$br;
$remainingUser = $userDB->getUserByID($user2ID);
if ($remainingUser) {
    echo "User 2 still exists: " . $remainingUser['userName'] . " (" . $remainingUser['email'] . ")".$br;
} else {
    echo "User 2 not found (may have been deleted)".$br;
}

echo $br;
$meow->profiler->record("UserDB Test Completed", "UserDB Test");

//--- Test UserGroupDB Function ---//
echo $br."USERGROUP CLASS TEST (Database Storage)".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserGroupDB Test Start", "UserGroupDB Test");

// Initialize UserGroupDB //
$userGroupDBConfig = $meow->configTree['user']['userGroup'] ?? [];
$userGroupDB = new UserGroupDB($meow->db, $userGroupDBConfig);

// Clear previous database records //
echo "Clearing old group data from database...".$br;
$meow->db->delete($userGroupDB->userGroupTable, []);
$meow->db->delete($userGroupDB->userGroupLinkTable, []);

// Create user groups //
echo "Creating DB user groups...".$br;
$dbAdminGroupID = $userGroupDB->createUserGroup("db_admin_group", ['groupDesc' => "DB Admin Group"]);
$dbEditorGroupID = $userGroupDB->createUserGroup("db_editor_group", ['groupDesc' => "DB Editor Group"]);
echo "DB admin group ID: $dbAdminGroupID".$br;
echo "DB editor group ID: $dbEditorGroupID".$br;

// Read user groups //
$dbAdminGroup = $userGroupDB->getUserGroupByID($dbAdminGroupID);
echo "DB admin group record:".$br;
var_dump($dbAdminGroup);
$dbEditorGroup = $userGroupDB->getUserGroupByName("db_editor_group");
echo "DB editor group record (by name):".$br;
var_dump($dbEditorGroup);

// Add user to groups //
$dbGroupUserID = $user2ID;
echo "Adding user $dbGroupUserID to DB admin group...".$br;
$dbAddAdmin = $userGroupDB->addUserToGroup($dbGroupUserID, $dbAdminGroupID);
echo "Add to admin group result: " . ($dbAddAdmin ? "Success" : "Failed - " . $userGroupDB->lastError) . $br;

echo "Adding user $dbGroupUserID to DB editor group...".$br;
$dbAddEditor = $userGroupDB->addUserToGroup($dbGroupUserID, $dbEditorGroupID);
echo "Add to editor group result: " . ($dbAddEditor ? "Success" : "Failed - " . $userGroupDB->lastError) . $br;

// Check membership //
$dbIsInAdmin = $userGroupDB->isUserInGroup($dbGroupUserID, $dbAdminGroupID);
echo "Is user $dbGroupUserID in DB admin group? " . ($dbIsInAdmin ? "Yes" : "No") . $br;

// List groups for user //
$dbGroupsForUser = $userGroupDB->getGroupsByUser($dbGroupUserID);
echo "DB groups for user $dbGroupUserID:".$br;
var_dump($dbGroupsForUser);

// List users for admin group //
$dbUsersInAdmin = $userGroupDB->getUsersInGroup($dbAdminGroupID);
echo "Users in DB admin group $dbAdminGroupID:".$br;
var_dump($dbUsersInAdmin);

// Remove user from group //
echo "Removing user $dbGroupUserID from DB admin group...".$br;
$dbRemoveResult = $userGroupDB->delUserFromGroup($dbGroupUserID, $dbAdminGroupID);
echo "Remove result: " . ($dbRemoveResult ? "Success" : "Failed - " . $userGroupDB->lastError) . $br;
$dbIsInAdminAfter = $userGroupDB->isUserInGroup($dbGroupUserID, $dbAdminGroupID);
echo "Is user still in DB admin group? " . ($dbIsInAdminAfter ? "Yes (unexpected)" : "No (expected)") . $br;

$meow->profiler->record("UserGroupDB Test Completed", "UserGroupDB Test");
echo $br;

//--- Test UserPermDB Function ---//
echo $br."USERPERM CLASS TEST (Database Storage)".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserPermDB Test Start", "UserPermDB Test");

// Initialize UserPermDB with standard configuration //
echo "Initializing UserPermDB...".$br;
$userPermDB = new UserPermDB($meow->db, $meow->configTree['user']['userPerm'] ?? []);
echo "UserPermDB initialized successfully".$br.$br;
$userPermDBAdminGroupID = 1;
$userPermDBEditorGroupID = 2;
$userPermDBViewerGroupID = 3;

// Clear old permission data from database //
echo "Clearing old permission data from database...".$br;
$meow->db->delete($userPermDB->userGroupPermTable, []);
$meow->db->delete($userPermDB->userPermTable, []);

$meow->profiler->record("Ready for user permission testing", "UserPermDB Test");

// Get a userID from previous tests (use user2ID if available, otherwise use 1)
$testUserID = $user2ID ?? 1;

// Test 1: User Permission Management (basic CRUD)
echo "User Permission Management (Basic CRUD):".$br;

// Set user permissions (new API requires userID as first parameter)
echo "Setting user permissions for user ID $testUserID, 'articles' item...".$br;
$setResult1 = $userPermDB->setUserPerm($testUserID, "articles", "read", 1);
if ($setResult1) {
    echo "Set articles read permission: Success (read permission: 1)".$br;
} else {
    echo "Set articles read permission failed: " . $userPermDB->lastError . $br;
}

$setResult2 = $userPermDB->setUserPerm($testUserID, "articles", "write", 1);
if ($setResult2) {
    echo "Set articles write permission: Success (write permission: 1)".$br;
} else {
    echo "Set articles write permission failed: " . $userPermDB->lastError . $br;
}

$setResult3 = $userPermDB->setUserPerm($testUserID, "articles", "delete", 0);
if ($setResult3) {
    echo "Set articles delete permission: Success (delete permission: 0)".$br;
} else {
    echo "Set articles delete permission failed: " . $userPermDB->lastError . $br;
}

// Set permissions for another item
echo "Setting user permissions for user ID $testUserID, 'users' item...".$br;
$setResult4 = $userPermDB->setUserPerm($testUserID, "users", "read", 1);
$setResult5 = $userPermDB->setUserPerm($testUserID, "users", "write", 0);
$setResult6 = $userPermDB->setUserPerm($testUserID, "users", "delete", 0);

echo "Set users read permission (read permission: 1): " . ($setResult4 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;
echo "Set users write permission (write permission: 0): " . ($setResult5 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;
echo "Set users delete permission (delete permission: 0): " . ($setResult6 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;

$meow->profiler->record("Set user permissions", "UserPermDB Test");
echo $br;

// Test 2: Get User Permissions (new API returns array)
echo "Get User Permissions:".$br;

// Get all permissions for an item (new API requires userID as first parameter)
$articlePerms = $userPermDB->getUserPerm($testUserID, "articles");
echo "All article permissions for user $testUserID: ".$br;
if ($articlePerms && is_array($articlePerms)) {
    echo "  read: " . ($articlePerms['read'] ?? 0) . $br;
    echo "  write: " . ($articlePerms['write'] ?? 0) . $br;
    echo "  delete: " . ($articlePerms['delete'] ?? 0) . $br;
    var_dump($articlePerms);
} else {
    echo "  No permissions found".$br;
}

$userPerms = $userPermDB->getUserPerm($testUserID, "users");
echo "All user permissions for user $testUserID: ".$br;
if ($userPerms && is_array($userPerms)) {
    echo "  read: " . ($userPerms['read'] ?? "NULL") . $br;
    echo "  write: " . ($userPerms['write'] ?? "NULL") . $br;
    echo "  delete: " . ($userPerms['delete'] ?? "NULL") . $br;
    var_dump($userPerms);
} else {
    echo "  No permissions found".$br;
}

$meow->profiler->record("Get user permissions", "UserPermDB Test");
echo $br;

// Test 3: Group Permissions (basic CRUD)
echo "Group Permissions (Basic CRUD):".$br;

// Set group permissions
echo "Setting admin group ($userPermDBAdminGroupID) permissions for 'articles'...".$br;
$setGroupResult1 = $userPermDB->setGroupPerm($userPermDBAdminGroupID, "articles", "read", 1);
$setGroupResult2 = $userPermDB->setGroupPerm($userPermDBAdminGroupID, "articles", "write", 1);
$setGroupResult3 = $userPermDB->setGroupPerm($userPermDBAdminGroupID, "articles", "delete", 1);

echo "Set admin articles read permission to 1: " . ($setGroupResult1 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;
echo "Set admin articles write permission to 1: " . ($setGroupResult2 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;
echo "Set admin articles delete permission to 1: " . ($setGroupResult3 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;

echo "Setting editor group ($userPermDBEditorGroupID) permissions for 'articles'...".$br;
$setGroupResult4 = $userPermDB->setGroupPerm($userPermDBEditorGroupID, "articles", "read", 1);
$setGroupResult5 = $userPermDB->setGroupPerm($userPermDBEditorGroupID, "articles", "write", 1);
$setGroupResult6 = $userPermDB->setGroupPerm($userPermDBEditorGroupID, "articles", "delete", 0);

echo "Set editor articles read permission to 1: " . ($setGroupResult4 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;
echo "Set editor articles write permission to 1: " . ($setGroupResult5 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;
echo "Set editor articles delete permission to 0: " . ($setGroupResult6 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;

// Get group permissions (new API requires groupID and item)
$adminArticlePerms = $userPermDB->getGroupPerm($userPermDBAdminGroupID, "articles");
echo "Admin article permissions: ".$br;
if ($adminArticlePerms && is_array($adminArticlePerms)) {
    var_dump($adminArticlePerms);
} else {
    echo "  No permissions found".$br;
}

$editorArticlePerms = $userPermDB->getGroupPerm($userPermDBEditorGroupID, "articles");
echo "Editor article permissions: ".$br;
if ($editorArticlePerms && is_array($editorArticlePerms)) {
    var_dump($editorArticlePerms);
} else {
    echo "  No permissions found".$br;
}

$meow->profiler->record("Group permissions", "UserPermDB Test");
echo $br;

// Test 4: Delete Operations (basic CRUD)
echo "Delete Operations:".$br;

// Delete specific permission
echo "Deleting articles write permission for user $testUserID...".$br;
$deleteResult1 = $userPermDB->delUserPerm($testUserID, "articles", "write");
echo "Delete articles write permission: " . ($deleteResult1 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;

// Check if permission is deleted
$articlePermsAfterDelete = $userPermDB->getUserPerm($testUserID, "articles");
echo "Articles permissions after delete: ".$br;
if ($articlePermsAfterDelete && is_array($articlePermsAfterDelete)) {
    echo "  read: " . ($articlePermsAfterDelete['read'] ?? "NULL") . $br;
    echo "  write: " . ($articlePermsAfterDelete['write'] ?? "NULL") . $br;
    echo "  delete: " . ($articlePermsAfterDelete['delete'] ?? "NULL") . $br;
    var_dump($articlePermsAfterDelete);
} else {
    echo "  No permissions found".$br;
}

// Delete all permissions for an item
echo "Deleting all users permissions for user $testUserID...".$br;
$deleteResult2 = $userPermDB->delUserPerm($testUserID, "users");
echo "Delete all users permissions: " . ($deleteResult2 ? "Success" : "Failed - " . $userPermDB->lastError) . $br;

// Delete group permission
echo "Deleting editor articles delete permission...".$br;
$deleteGroupResult = $userPermDB->delGroupPerm($userPermDBEditorGroupID, "articles", "delete");
echo "Delete editor articles delete permission: " . ($deleteGroupResult ? "Success" : "Failed - " . $userPermDB->lastError) . $br;

$meow->profiler->record("Delete operations", "UserPermDB Test");
echo $br;

// Note: Permission checking (checkUserPerm), group management (addUserToGroup, hasGroup, getUserGroups),
// and permission inheritance are now handled by UserManager and tested in UserManager test section

$meow->profiler->record("Final verification", "UserPermDB Test");
echo $br;

$meow->profiler->record("UserPermDB Test Completed", "UserPermDB Test");

//--- Test UserManager with UserCSV ---//
echo $br."USER MANAGER CLASS TEST (with CSV Storage)".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserManager CSV Test Start", "UserManager CSV Test");

// Initialize UserManager with UserCSV
echo "Initializing UserManager with UserCSV...".$br;

// Initialize Password (shared between UserManagerCSV and UserManagerDB)
$password = new Password($meow->configTree['user']['manager']['password'] ?? []);

// Initialize User, UserGroup, UserPerm with CSV storage
$userManagerUserCSV = new UserCSV($meow->configTree['user']['user'] ?? []);
$userManagerGroupCSV = new UserGroupCSV($meow->configTree['user']['userGroup'] ?? []);
$userManagerPermCSV = new UserPermCSV($meow->configTree['user']['userPerm'] ?? []);

// Initialize UserManager with CSV components
$userManagerConfig = $meow->configTree['user']['manager'] ?? [];
$userManagerCSV = new UserManager($userManagerUserCSV, $userManagerConfig, $password, $userManagerGroupCSV, $userManagerPermCSV);

echo "UserManager with UserCSV initialized successfully".$br.$br;

// Create User via UserManager (using UserCSV)
echo "Create User via UserManager (UserCSV)".$br;
$csvUser1Data = [
    'userName' => "csvmgruser1",
    'email' => "csvmgruser1@test.com",
];
$csvUser1ID = $userManagerCSV->createUser("csvmgruser1", "CSVPass123!", $csvUser1Data);
if ($csvUser1ID > 0) {
    echo "CSV User 1 created via UserManager with ID: $csvUser1ID".$br;
} else {
    echo "Failed to create CSV User 1: " . $userManagerCSV->lastError . $br;
}

$meow->profiler->record("Create user via UserManager CSV", "UserManager CSV Test");
echo $br;

// Login Tests via UserManager (using UserCSV)
echo "Login Tests via UserManager (UserCSV)".$br;

// Test login with correct credentials
echo "Testing login with correct credentials...".$br;
$csvLoginResult = $userManagerCSV->login("csvmgruser1", "CSVPass123!");
if ($csvLoginResult) {
    echo "CSV User 1 logged in successfully via UserManager".$br;
    $csvUserID = $userManagerCSV->resolveUserID();
    echo "Logged in User ID: " . ($csvUserID ?? 'N/A') . $br;
    echo "Is logged in: " . ($userManagerCSV->isLoggedIn() ? 'Yes' : 'No') . $br;
    echo "Current user:".$br;
    var_dump($userManagerCSV->user);
    echo "\$_SESSION[".$userManagerCSV->sessionVarName."]:".$br;
    var_dump($_SESSION[$userManagerCSV->sessionVarName] ?? null) . $br;
} else {
    echo "CSV User 1 login failed: " . $userManagerCSV->lastError . $br;
}
echo $br;


// Logout first
echo "Logging out...".$br;
$userManagerCSV->logout();
echo "Is logged in after logout: " . ($userManagerCSV->isLoggedIn() ? "Yes" : "No") . $br;
echo "Current user:".$br;
var_dump($userManagerCSV->user);
echo "\$_SESSION[".$userManagerCSV->sessionVarName."]:".$br;
var_dump($_SESSION[$userManagerCSV->sessionVarName] ?? null) . $br;
echo $br;

// Test login with non-exists user
echo "Testing login with non-exists user...".$br;
$csvWrongLoginResult = $userManagerCSV->login("NonExistUser", "WrongPassword");
if ($csvWrongLoginResult) {
    echo "Login with non-exists user succeeded (unexpected)".$br;
} else {
    echo "Login with non-exists user failed as expected: " . $userManagerCSV->lastError . $br;
}
echo $br;

// Test login with wrong password
echo "Testing login with wrong password...".$br;
$csvWrongLoginResult = $userManagerCSV->login("csvmgruser1", "WrongPassword");
if ($csvWrongLoginResult) {
    echo "Login with wrong password succeeded (unexpected)".$br;
} else {
    echo "Login with wrong password failed as expected: " . $userManagerCSV->lastError . $br;
}
echo $br;

// Test login again with correct credentials
echo "Testing login again with correct credentials...".$br;
$csvLoginResult2 = $userManagerCSV->login("csvmgruser1", "CSVPass123!");
if ($csvLoginResult2) {
    echo "CSV User 1 logged in successfully again".$br;
    echo "Is logged in: " . ($userManagerCSV->isLoggedIn() ? 'Yes' : 'No') . $br;
} else {
    echo "CSV User 1 login failed: " . $userManagerCSV->lastError . $br;
}
$meow->profiler->record("Login via UserManager CSV", "UserManager CSV Test");
echo $br;

// Continue Login via UserManager (using UserCSV)
echo "Continue Login via UserManager (UserCSV)".$br;
// Simulate page reload by creating new UserManager instance with same session
$userManagerCSV2 = new UserManager($userCSV, $userManagerConfig, $password, $userManagerGroupCSV, $userManagerPermCSV);
$csvContinueLoginResult = $userManagerCSV2->continueLogin();
echo "Continue login after session: " . ($csvContinueLoginResult ? "Success" : "Failed - " . $userManagerCSV2->lastError) . $br;
if ($csvContinueLoginResult) {
    echo "Is logged in after continueLogin: " . ($userManagerCSV2->isLoggedIn() ? "Yes" : "No") . $br;
    $csvContinuedUserID = $userManagerCSV2->resolveUserID();
    echo "Continued login User ID: " . ($csvContinuedUserID ?? 'N/A') . $br;
    echo "Current user:".$br;
    var_dump($userManagerCSV2->user);
    echo "\$_SESSION[".$userManagerCSV2->sessionVarName."]:".$br;
    var_dump($_SESSION[$userManagerCSV2->sessionVarName] ?? null) . $br;
}
$meow->profiler->record("Continue Login via UserManager CSV", "UserManager CSV Test");
echo $br;

// Create UserGroup via UserManager (using UserGroupCSV)
echo "Create UserGroup via UserManager (UserGroupCSV)".$br;
$csvGroup1ID = $userManagerCSV->createUserGroup('csvadmin', ['groupDesc' => 'CSV Admin group']);
if ($csvGroup1ID > 0) {
    echo "CSV Admin group created via UserManager with ID: $csvGroup1ID".$br;
} else {
    echo "Failed to create CSV Admin group: " . $userManagerCSV->lastError . $br;
}
$csvGroup2ID = $userManagerCSV->createUserGroup('csveditor', ['groupDesc' => 'CSV Editor group']);
if ($csvGroup2ID > 0) {
    echo "CSV Editor group created via UserManager with ID: $csvGroup2ID".$br;
} else {
    echo "Failed to create CSV Editor group: " . $userManagerCSV->lastError . $br;
}
$meow->profiler->record("Create group via UserManager CSV", "UserManager CSV Test");
echo $br;

// Add User to Group via UserManager (using UserGroupCSV)
echo "Add User to Group via UserManager (UserGroupCSV)".$br;
if ($csvUser1ID > 0 && $csvGroup1ID > 0 && $csvGroup2ID > 0) {
    // Add User 1 to CSV groups
    $csvAddGroupResult = $userManagerCSV->addUserToGroup($csvUser1ID, $csvGroup1ID);
    echo "Add User 1 to CSV Admin group: " . ($csvAddGroupResult ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
    $csvAddGroupResult2 = $userManagerCSV->addUserToGroup($csvUser1ID, $csvGroup2ID);
    echo "Add User 1 to CSV Editor group: " . ($csvAddGroupResult2 ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
    echo $br;

    // Get User 1 groups
    $csvUserGroups = $userManagerCSV->getGroupsByUser($csvUser1ID);
    echo "User 1 groups: ".$br;
    $csvIsInAdminGroup = (is_array($csvUserGroups) && in_array($csvGroup1ID, $csvUserGroups));
    echo "Is User 1 in CSV Admin group: " . ($csvIsInAdminGroup ? "Yes" : "No") . $br;
    $csvIsInEditorGroup = (is_array($csvUserGroups) && in_array($csvGroup2ID, $csvUserGroups));
    echo "Is User 1 in CSV Editor group: " . ($csvIsInEditorGroup ? "Yes" : "No") . $br;
} else {
    echo "Failed to add User 1 to CSV Admin group: csvuser1ID or csvgroup1ID or csvgroup2ID is not valid" . $br;
}
$meow->profiler->record("Add user to group via UserManager CSV", "UserManager CSV Test");
echo $br;

// Set Permissions via UserManager (using UserPermCSV)
echo "Set Permissions via UserManager (UserPermCSV)".$br;
if ($csvUser1ID > 0) {
    $csvPermResult1 = $userManagerCSV->setUserPermission($csvUser1ID, "articles", "read", 1);
    echo "Set User 1 articles read permission (Articles read permission: 1): " . ($csvPermResult1 ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
    $csvPermResult2 = $userManagerCSV->setUserPermission($csvUser1ID, "articles", "write", 0);
    echo "Set User 1 articles write permission (Articles write permission: 0): " . ($csvPermResult2 ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
    $csvPermResult3 = $userManagerCSV->setUserPermission($csvUser1ID, "articles", "delete", 0);
    echo "Set User 1 articles write permission (Articles delete permission: 0): " . ($csvPermResult3 ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
}
$meow->profiler->record("Set user permissions via UserManager CSV", "UserManager CSV Test");
echo $br;

// Check Permissions via UserManager (using UserPermCSV)
echo "Check Permissions via UserManager (UserPermCSV)".$br;
if ($csvUser1ID > 0) {
    $csvHasReadPerm = $userManagerCSV->checkUserPermission("articles", "read", 0, $csvUser1ID);
    echo "User 1 has articles read permission: " . ($csvHasReadPerm ? "Yes" : "No") . $br;
    $csvHasWritePerm = $userManagerCSV->checkUserPermission("articles", "write", 0, $csvUser1ID);
    echo "User 1 has articles write permission: " . ($csvHasWritePerm ? "Yes" : "No") . $br;
    $csvHasDeletePerm = $userManagerCSV->checkUserPermission("articles", "delete", 0, $csvUser1ID);
    echo "User 1 has articles delete permission: " . ($csvHasDeletePerm ? "Yes" : "No") . $br;
    echo $br;
    $csvPermValue = $userManagerCSV->getUserPermissionValue("articles", "read", $csvUser1ID);
    echo "User 1 articles read permission value: " . print_r($csvPermValue, true) . $br;
    $csvPermValue = $userManagerCSV->getUserPermissionValue("articles", "write", $csvUser1ID);
    echo "User 1 articles write permission value: " . print_r($csvPermValue, true) . $br;
    $csvPermValue = $userManagerCSV->getUserPermissionValue("articles", "delete", $csvUser1ID);
    echo "User 1 articles delete permission value: " . print_r($csvPermValue, true) . $br;
} else {
    echo "Failed to check permissions for User 1: csvuser1ID is not valid" . $br;
}
$meow->profiler->record("Check user permissions via UserManager CSV", "UserManager CSV Test");
echo $br;

// Set Group Permissions via UserManager (using UserPermCSV)
echo "Set Group Permissions via UserManager (UserPermCSV)".$br;
if ($csvGroup1ID > 0 && $csvGroup2ID > 0) {
    $csvGroupPermResult1 = $userManagerCSV->setGroupPermission($csvGroup1ID, "articles", "read", 0);
    echo "Set CSV Admin group articles read permission (Articles read permission: 0): " . ($csvGroupPermResult1 ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
    $csvGroupPermResult2 = $userManagerCSV->setGroupPermission($csvGroup1ID, "articles", "write", 0);
    echo "Set CSV Admin group articles write permission (Articles write permission: 0): " . ($csvGroupPermResult2 ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
    $csvGroupPermResult3 = $userManagerCSV->setGroupPermission($csvGroup1ID, "articles", "delete", 1);
    echo "Set CSV Admin group articles delete permission (Articles delete permission: 1): " . ($csvGroupPermResult3 ? "Success" : "Failed - " . $userManagerCSV->lastError) . $br;
    
}
$meow->profiler->record("Set group permissions via UserManager CSV", "UserManager CSV Test");
echo $br;

// Check Permissions via UserManager (using UserPermCSV)
echo "Check Permissions via UserManager again with Group Permissions (UserPermCSV)".$br;
if ($csvUser1ID > 0) {
    $csvHasReadPerm = $userManagerCSV->checkUserPermission("articles", "read", 0, $csvUser1ID);
    echo "User 1 has articles read permission: " . ($csvHasReadPerm ? "Yes" : "No") . $br;
    $csvHasWritePerm = $userManagerCSV->checkUserPermission("articles", "write", 0, $csvUser1ID);
    echo "User 1 has articles write permission: " . ($csvHasWritePerm ? "Yes" : "No") . $br;
    $csvHasDeletePerm = $userManagerCSV->checkUserPermission("articles", "delete", 0, $csvUser1ID);
    echo "User 1 has articles delete permission: " . ($csvHasDeletePerm ? "Yes" : "No") . $br;
    echo $br;   
    $csvPermValue = $userManagerCSV->getUserPermissionValue("articles", "read", $csvUser1ID);
    echo "User 1 articles read permission value: " . print_r($csvPermValue, true) . $br;
    $csvPermValue = $userManagerCSV->getUserPermissionValue("articles", "write", $csvUser1ID);
    echo "User 1 articles write permission value: " . print_r($csvPermValue, true) . $br;
    $csvPermValue = $userManagerCSV->getUserPermissionValue("articles", "delete", $csvUser1ID);
    echo "User 1 articles delete permission value: " . print_r($csvPermValue, true) . $br;
} else {
    echo "Failed to check permissions for User 1: csvuser1ID is not valid" . $br;
}
$meow->profiler->record("Check permissions via UserManager CSV", "UserManager CSV Test");
echo $br;

// Logout via UserManager
echo "Logout via UserManager (UserCSV)".$br;
echo "Before logout:".$br;
echo "Is logged in after logout: " . ($userManagerCSV->isLoggedIn() ? "Yes" : "No") . $br;
echo "Current user:".$br;
var_dump($userManagerCSV->user);
echo "Current Groups:".$br;
var_dump($userManagerCSV->userGroup);
echo "Current Permissions:".$br;
var_dump($userManagerCSV->userPerm);
echo "Current Group Links:".$br;
var_dump($userManagerCSV->userGroupLink);
echo "Current Perm Group:".$br;
var_dump($userManagerCSV->userPermGroup);
echo "\$_SESSION[".$userManagerCSV->sessionVarName."]:".$br;
var_dump($_SESSION[$userManagerCSV->sessionVarName] ?? null) . $br;
$userManagerCSV->logout();
echo "After logout:".$br;
echo "Is logged in after logout: " . ($userManagerCSV->isLoggedIn() ? "Yes" : "No") . $br;
echo "Current user:".$br;
var_dump($userManagerCSV->user);
echo "Current Groups:".$br;
var_dump($userManagerCSV->userGroup);
echo "Current Permissions:".$br;
var_dump($userManagerCSV->userPerm);
echo "Current Group Links:".$br;
var_dump($userManagerCSV->userGroupLink);
echo "Current Perm Group:".$br;
var_dump($userManagerCSV->userPermGroup);
echo "\$_SESSION[".$userManagerCSV->sessionVarName."]:".$br;
var_dump($_SESSION[$userManagerCSV->sessionVarName] ?? null) . $br;

$meow->profiler->record("Logout via UserManager CSV", "UserManager CSV Test");
echo $br;

//--- Test UserManager with UserDB ---//
echo $br."Test UserManager with UserDB".$br;
echo "--------------------------------".$br;
$meow->profiler->record("UserManager DB Test Start", "UserManager DB Test");

// Initialize UserManager with UserDB
echo "Initializing UserManager with UserDB...".$br;

// Reuse existing $userDB instance
$userManagerGroupDB = new UserGroupDB($meow->db, $meow->configTree['user']['userGroup'] ?? []);
$userManagerPermDB  = new UserPermDB($meow->db, $meow->configTree['user']['userPerm'] ?? []);

// Reuse password config to stay aligned with CSV test
$userManagerConfigDB = $meow->configTree['user']['manager'] ?? [];
$userManagerDB       = new UserManager($userDB, $userManagerConfigDB, $password, $userManagerGroupDB, $userManagerPermDB);

echo "UserManager with UserDB initialized successfully".$br.$br;

// Create User via UserManager (using UserDB)
echo "Create User via UserManager (UserDB)".$br;
$dbUser1Data = [
    'userName' => "dbmgruser1",
    'email'    => "dbmgruser1@test.com",
];
$dbUser1ID = $userManagerDB->createUser("dbmgruser1", "DBPass123!", $dbUser1Data);
if ($dbUser1ID > 0) {
    echo "DB User 1 created via UserManager with ID: $dbUser1ID".$br;
} else {
    echo "Failed to create DB User 1: " . $userManagerDB->lastError . $br;
}

$meow->profiler->record("Create user via UserManager DB", "UserManager DB Test");
echo $br;

// Login Tests via UserManager (using UserDB)
echo "Login Tests via UserManager (UserDB)".$br;

// Test login with correct credentials
echo "Testing login with correct credentials...".$br;
$dbLoginResult = $userManagerDB->login("dbmgruser1", "DBPass123!");
if ($dbLoginResult) {
    echo "DB User 1 logged in successfully via UserManager".$br;
    $dbUserID = $userManagerDB->resolveUserID();
    echo "Logged in User ID: " . ($dbUserID ?? 'N/A') . $br;
    echo "Is logged in: " . ($userManagerDB->isLoggedIn() ? 'Yes' : 'No') . $br;
    echo "Current user:".$br;
    var_dump($userManagerDB->user);
    echo "\$_SESSION[".$userManagerDB->sessionVarName."]:".$br;
    var_dump($_SESSION[$userManagerDB->sessionVarName] ?? null) . $br;
} else {
    echo "DB User 1 login failed: " . $userManagerDB->lastError . $br;
}
echo $br;

// Logout first
echo "Logging out...".$br;
$userManagerDB->logout();
echo "Is logged in after logout: " . ($userManagerDB->isLoggedIn() ? "Yes" : "No") . $br;
echo "Current user:".$br;
var_dump($userManagerDB->user);
echo "\$_SESSION[".$userManagerDB->sessionVarName."]:".$br;
var_dump($_SESSION[$userManagerDB->sessionVarName] ?? null) . $br;
echo $br;

// Test login with non-exists user
echo "Testing login with non-exists user...".$br;
$dbWrongLoginResult = $userManagerDB->login("NonExistUser", "WrongPassword");
if ($dbWrongLoginResult) {
    echo "Login with non-exists user succeeded (unexpected)".$br;
} else {
    echo "Login with non-exists user failed as expected: " . $userManagerDB->lastError . $br;
}
echo $br;

// Test login with wrong password
echo "Testing login with wrong password...".$br;
$dbWrongLoginResult = $userManagerDB->login("dbmgruser1", "WrongPassword");
if ($dbWrongLoginResult) {
    echo "Login with wrong password succeeded (unexpected)".$br;
} else {
    echo "Login with wrong password failed as expected: " . $userManagerDB->lastError . $br;
}
echo $br;

// Test login again with correct credentials
echo "Testing login again with correct credentials...".$br;
$dbLoginResult2 = $userManagerDB->login("dbmgruser1", "DBPass123!");
if ($dbLoginResult2) {
    echo "DB User 1 logged in successfully again".$br;
    echo "Is logged in: " . ($userManagerDB->isLoggedIn() ? 'Yes' : 'No') . $br;
} else {
    echo "DB User 1 login failed: " . $userManagerDB->lastError . $br;
}
$meow->profiler->record("Login via UserManager DB", "UserManager DB Test");
echo $br;

// Continue Login via UserManager (using UserDB)
echo "Continue Login via UserManager (UserDB)".$br;
// Simulate page reload by creating new UserManager instance with same session
$userManagerDB2 = new UserManager($userDB, $userManagerConfigDB, $password, $userManagerGroupDB, $userManagerPermDB);
$dbContinueLoginResult = $userManagerDB2->continueLogin();
echo "Continue login after session: " . ($dbContinueLoginResult ? "Success" : "Failed - " . $userManagerDB2->lastError) . $br;
if ($dbContinueLoginResult) {
    echo "Is logged in after continueLogin: " . ($userManagerDB2->isLoggedIn() ? "Yes" : "No") . $br;
    $dbContinuedUserID = $userManagerDB2->resolveUserID();
    echo "Continued login User ID: " . ($dbContinuedUserID ?? 'N/A') . $br;
    echo "Current user:".$br;
    var_dump($userManagerDB2->user);
    echo "\$_SESSION[".$userManagerDB2->sessionVarName."]:".$br;
    var_dump($_SESSION[$userManagerDB2->sessionVarName] ?? null) . $br;
}
$meow->profiler->record("Continue Login via UserManager DB", "UserManager DB Test");
echo $br;

// Create UserGroup via UserManager (using UserGroupDB)
echo "Create UserGroup via UserManager (UserGroupDB)".$br;
$dbGroupAdminID = $userManagerDB->createUserGroup('dbadmin', ['groupDesc' => 'DB Admin group']);
if ($dbGroupAdminID > 0) {
    echo "DB Admin group created via UserManager with ID: $dbGroupAdminID".$br;
} else {
    echo "Failed to create DB Admin group: " . $userManagerDB->lastError . $br;
}
$dbGroupEditorID = $userManagerDB->createUserGroup('dbeditor', ['groupDesc' => 'DB Editor group']);
if ($dbGroupEditorID > 0) {
    echo "DB Editor group created via UserManager with ID: $dbGroupEditorID".$br;
} else {
    echo "Failed to create DB Editor group: " . $userManagerDB->lastError . $br;
}
$meow->profiler->record("Create group via UserManager DB", "UserManager DB Test");
echo $br;

// Add User to Group via UserManager (using UserGroupDB)
echo "Add User to Group via UserManager (UserGroupDB)".$br;
if ($dbUser1ID > 0 && $dbGroupAdminID > 0 && $dbGroupEditorID > 0) {
    // Add User 1 to DB groups
    $dbAddGroupResult = $userManagerDB->addUserToGroup($dbUser1ID, $dbGroupAdminID);
    echo "Add User 1 to DB Admin group: " . ($dbAddGroupResult ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
    $dbAddGroupResult2 = $userManagerDB->addUserToGroup($dbUser1ID, $dbGroupEditorID);
    echo "Add User 1 to DB Editor group: " . ($dbAddGroupResult2 ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
    echo $br;

    // Get User 1 groups
    $dbUserGroups = $userManagerDB->getGroupsByUser($dbUser1ID);
    echo "User 1 groups: ".$br;
    $dbIsInAdminGroup = (is_array($dbUserGroups) && in_array($dbGroupAdminID, $dbUserGroups));
    echo "Is User 1 in DB Admin group: " . ($dbIsInAdminGroup ? "Yes" : "No") . $br;
    $dbIsInEditorGroup = (is_array($dbUserGroups) && in_array($dbGroupEditorID, $dbUserGroups));
    echo "Is User 1 in DB Editor group: " . ($dbIsInEditorGroup ? "Yes" : "No") . $br;
} else {
    echo "Failed to add User 1 to DB groups: dbuser1ID or dbgroup IDs are not valid" . $br;
}
$meow->profiler->record("Add user to group via UserManager DB", "UserManager DB Test");
echo $br;

// Set Permissions via UserManager (using UserPermDB)
echo "Set Permissions via UserManager (UserPermDB)".$br;
if ($dbUser1ID > 0) {
    $dbPermResult1 = $userManagerDB->setUserPermission($dbUser1ID, "articles", "read", 1);
    echo "Set User 1 articles read permission (Articles read permission: 1): " . ($dbPermResult1 ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
    $dbPermResult2 = $userManagerDB->setUserPermission($dbUser1ID, "articles", "write", 0);
    echo "Set User 1 articles write permission (Articles write permission: 0): " . ($dbPermResult2 ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
    $dbPermResult3 = $userManagerDB->setUserPermission($dbUser1ID, "articles", "delete", 0);
    echo "Set User 1 articles delete permission (Articles delete permission: 0): " . ($dbPermResult3 ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
}
$meow->profiler->record("Set user permissions via UserManager DB", "UserManager DB Test");
echo $br;

// Check Permissions via UserManager (using UserPermDB)
echo "Check Permissions via UserManager (UserPermDB)".$br;
if ($dbUser1ID > 0) {
    $dbHasReadPerm = $userManagerDB->checkUserPermission("articles", "read", 0, $dbUser1ID);
    echo "User 1 has articles read permission: " . ($dbHasReadPerm ? "Yes" : "No") . $br;
    $dbHasWritePerm = $userManagerDB->checkUserPermission("articles", "write", 0, $dbUser1ID);
    echo "User 1 has articles write permission: " . ($dbHasWritePerm ? "Yes" : "No") . $br;
    $dbHasDeletePerm = $userManagerDB->checkUserPermission("articles", "delete", 0, $dbUser1ID);
    echo "User 1 has articles delete permission: " . ($dbHasDeletePerm ? "Yes" : "No") . $br;
    echo $br;
    $dbPermValue = $userManagerDB->getUserPermissionValue("articles", "read", $dbUser1ID);
    echo "User 1 articles read permission value: " . print_r($dbPermValue, true) . $br;
    $dbPermValue = $userManagerDB->getUserPermissionValue("articles", "write", $dbUser1ID);
    echo "User 1 articles write permission value: " . print_r($dbPermValue, true) . $br;
    $dbPermValue = $userManagerDB->getUserPermissionValue("articles", "delete", $dbUser1ID);
    echo "User 1 articles delete permission value: " . print_r($dbPermValue, true) . $br;
} else {
    echo "Failed to check permissions for User 1: dbuser1ID is not valid" . $br;
}
$meow->profiler->record("Check user permissions via UserManager DB", "UserManager DB Test");
echo $br;

// Set Group Permissions via UserManager (using UserPermDB)
echo "Set Group Permissions via UserManager (UserPermDB)".$br;
if ($dbGroupAdminID > 0 && $dbGroupEditorID > 0) {
    $dbGroupPermResult1 = $userManagerDB->setGroupPermission($dbGroupAdminID, "articles", "read", 0);
    echo "Set DB Admin group articles read permission (Articles read permission: 0): " . ($dbGroupPermResult1 ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
    $dbGroupPermResult2 = $userManagerDB->setGroupPermission($dbGroupAdminID, "articles", "write", 0);
    echo "Set DB Admin group articles write permission (Articles write permission: 0): " . ($dbGroupPermResult2 ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
    $dbGroupPermResult3 = $userManagerDB->setGroupPermission($dbGroupAdminID, "articles", "delete", 1);
    echo "Set DB Admin group articles delete permission (Articles delete permission: 1): " . ($dbGroupPermResult3 ? "Success" : "Failed - " . $userManagerDB->lastError) . $br;
}
$meow->profiler->record("Set group permissions via UserManager DB", "UserManager DB Test");
echo $br;

// Check Permissions via UserManager again with Group Permissions (UserPermDB)
echo "Check Permissions via UserManager again with Group Permissions (UserPermDB)".$br;
if ($dbUser1ID > 0) {
    $dbHasReadPerm = $userManagerDB->checkUserPermission("articles", "read", 0, $dbUser1ID);
    echo "User 1 has articles read permission: " . ($dbHasReadPerm ? "Yes" : "No") . $br;
    $dbHasWritePerm = $userManagerDB->checkUserPermission("articles", "write", 0, $dbUser1ID);
    echo "User 1 has articles write permission: " . ($dbHasWritePerm ? "Yes" : "No") . $br;
    $dbHasDeletePerm = $userManagerDB->checkUserPermission("articles", "delete", 0, $dbUser1ID);
    echo "User 1 has articles delete permission: " . ($dbHasDeletePerm ? "Yes" : "No") . $br;
    echo $br;   
    $dbPermValue = $userManagerDB->getUserPermissionValue("articles", "read", $dbUser1ID);
    echo "User 1 articles read permission value: " . print_r($dbPermValue, true) . $br;
    $dbPermValue = $userManagerDB->getUserPermissionValue("articles", "write", $dbUser1ID);
    echo "User 1 articles write permission value: " . print_r($dbPermValue, true) . $br;
    $dbPermValue = $userManagerDB->getUserPermissionValue("articles", "delete", $dbUser1ID);
    echo "User 1 articles delete permission value: " . print_r($dbPermValue, true) . $br;
} else {
    echo "Failed to check permissions for User 1: dbuser1ID is not valid" . $br;
}
$meow->profiler->record("Check permissions via UserManager DB", "UserManager DB Test");
echo $br;

// Logout via UserManager
echo "Logout via UserManager (UserDB)".$br;
echo "Before logout:".$br;
echo "Is logged in after logout: " . ($userManagerDB->isLoggedIn() ? "Yes" : "No") . $br;
echo "Current user:".$br;
var_dump($userManagerDB->user);
echo "Current Groups:".$br;
var_dump($userManagerDB->userGroup);
echo "Current Permissions:".$br;
var_dump($userManagerDB->userPerm);
echo "Current Group Links:".$br;
var_dump($userManagerDB->userGroupLink);
echo "Current Perm Group:".$br;
var_dump($userManagerDB->userPermGroup);
echo "\$_SESSION[".$userManagerDB->sessionVarName."]:".$br;
var_dump($_SESSION[$userManagerDB->sessionVarName] ?? null) . $br;
$userManagerDB->logout();
echo "After logout:".$br;
echo "Is logged in after logout: " . ($userManagerDB->isLoggedIn() ? "Yes" : "No") . $br;
echo "Current user:".$br;
var_dump($userManagerDB->user);
echo "Current Groups:".$br;
var_dump($userManagerDB->userGroup);
echo "Current Permissions:".$br;
var_dump($userManagerDB->userPerm);
echo "Current Group Links:".$br;
var_dump($userManagerDB->userGroupLink);
echo "Current Perm Group:".$br;
var_dump($userManagerDB->userPermGroup);
echo "\$_SESSION[".$userManagerDB->sessionVarName."]:".$br;
var_dump($_SESSION[$userManagerDB->sessionVarName] ?? null) . $br;

$meow->profiler->record("Logout via UserManager DB", "UserManager DB Test");
echo $br;

// Show report //
echo $br . $meow->profiler->report($isWeb);
