<?php
/**
 * DTree Example
 * 
 * This example demonstrates how to use the DTree class for managing
 * hierarchical tree structures with nodes, paths, and operations.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.2
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Tools\DTree;
use Paheon\MeowBase\Tools\DTreeIterator;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "DTree Example".$br;
echo "==========================================".$br.$br;

// Example 1: Create Root Node
echo "Example 1: Create Root Node".$br;
echo "--------------------------------".$br;

$root = new DTree();
$root->data = "Root Node";
echo "Root node created".$br;
echo "Root path: ".$root->getPath().$br;
echo "Is root: ".var_export($root->isRoot(), true).$br.$br;

// Example 2: Create Child Nodes
echo "Example 2: Create Child Nodes".$br;
echo "--------------------------------".$br;

// Method 1: Using createNode
$nodeA = $root->createNode(['name' => 'A', 'data' => 'Node A']);
$nodeB = $root->createNode(['name' => 'B', 'data' => 'Node B']);
$nodeC = $root->createNode(['name' => 'C', 'data' => 'Node C']);

echo "Created nodes A, B, C using createNode()".$br;
echo "Node A path: ".$nodeA->getPath().$br;
echo "Node B path: ".$nodeB->getPath().$br;
echo "Node C path: ".$nodeC->getPath().$br.$br;

// Method 2: Using constructor
$nodeD = new DTree('D', 'Node D', $root);
echo "Created node D using constructor".$br;
echo "Node D path: ".$nodeD->getPath().$br.$br;

// Method 3: Using createByPath (absolute path)
$nodeE = $root->createByPath('/E', ['data' => 'Node E']);
echo "Created node E using createByPath with absolute path".$br;
echo "Node E path: ".$nodeE->getPath().$br.$br;

// Method 4: Using createByPath (relative path)
$nodeA1 = $nodeA->createByPath('A1', ['data' => 'Node A1']);
echo "Created node A1 using createByPath with relative path".$br;
echo "Node A1 path: ".$nodeA1->getPath().$br.$br;

// Example 3: Create Nested Structure
echo "Example 3: Create Nested Structure".$br;
echo "--------------------------------".$br;

// Create a structure: Root -> B -> B1, B2 -> B2X, B2Y
$nodeB1 = $nodeB->createNode(['name' => 'B1', 'data' => 'Node B1']);
$nodeB2 = $nodeB->createNode(['name' => 'B2', 'data' => 'Node B2']);
$nodeB3 = $nodeB->createNode(['name' => 'B3', 'data' => 'Node B3']);

$nodeB2X = $nodeB2->createNode(['name' => 'B2X', 'data' => 'Node B2X']);
$nodeB2Y = $nodeB2->createNode(['name' => 'B2Y', 'data' => 'Node B2Y']);

echo "Created nested structure:".$br;
echo "  Root".$br;
echo "    ├── A".$br;
echo "    │   └── A1".$br;
echo "    ├── B".$br;
echo "    │   ├── B1".$br;
echo "    │   ├── B2".$br;
echo "    │   │   ├── B2X".$br;
echo "    │   │   └── B2Y".$br;
echo "    │   └── B3".$br;
echo "    ├── C".$br;
echo "    ├── D".$br;
echo "    └── E".$br.$br;

// Example 4: Load from Array
echo "Example 4: Load from Array".$br;
echo "--------------------------------".$br;

$tree2 = new DTree();
$tree2->data = "Tree 2 Root";

$nodeList = $tree2->loadFromArray([
    '/F/F1' => ['data' => 'Node F1'],
    '/F/F2' => ['data' => 'Node F2'],
    '/G/G1/G1A' => ['data' => 'Node G1A'],
]);

echo "Loaded tree from array:".$br;
foreach ($nodeList as $path => $node) {
    echo "  Path: $path, Data: ".$node->data.$br;
}
echo $br;

// Example 5: Save to Array
echo "Example 5: Save to Array".$br;
echo "--------------------------------".$br;

$savedArray = $root->saveToArray();
echo "Saved tree to array (showing first 5 entries):".$br;
$count = 0;
foreach ($savedArray as $path => $data) {
    if ($count++ < 5) {
        echo "  $path => ".var_export($data, true).$br;
    }
}
echo "  ... (total ".count($savedArray)." nodes)".$br.$br;

// Example 6: Find Node by Path
echo "Example 6: Find Node by Path".$br;
echo "--------------------------------".$br;

$testPaths = [
    '/B',
    '/B/B2',
    '/B/B2/B2X',
    '/A/A1',
    '/NonExistent'
];

foreach ($testPaths as $path) {
    $found = $root->findByPath($path);
    if ($found) {
        echo "  Path '$path': Found - Data: ".$found->data.$br;
    } else {
        echo "  Path '$path': Not found".$br;
    }
}
echo $br;

// Example 7: Tree Iteration - Depth First Search (DFS)
echo "Example 7: Tree Iteration - Depth First Search (DFS)".$br;
echo "--------------------------------".$br;

echo "Iterating entire tree using DFS (default):".$br;
$iterator = new DTreeIterator($root, true, true);
foreach ($iterator as $position => $node) {
    $indent = str_repeat("  ", substr_count($node->getPath(), '/') - 1);
    echo $indent.$node->getPath()." => ".$node->data.$br;
}
echo $br;

// Example 8: Tree Iteration - Breadth First Search (BFS)
echo "Example 8: Tree Iteration - Breadth First Search (BFS)".$br;
echo "--------------------------------".$br;

echo "Iterating entire tree using BFS:".$br;
$iteratorBFS = new DTreeIterator($root, true, false);
foreach ($iteratorBFS as $position => $node) {
    $indent = str_repeat("  ", substr_count($node->getPath(), '/') - 1);
    echo $indent.$node->getPath()." => ".$node->data.$br;
}
echo $br;

// Example 9: Tree Iteration (Local)
echo "Example 9: Tree Iteration (Local)".$br;
echo "--------------------------------".$br;

echo "Iterating from node B (local, DFS):".$br;
$iteratorLocal = new DTreeIterator($nodeB, false, true);
foreach ($iteratorLocal as $position => $node) {
    echo "  Position $position: ".$node->getPath()." => ".$node->data.$br;
}
echo $br;

// Example 10: Path Navigation with . and ..
echo "Example 10: Path Navigation with . and ..".$br;
echo "--------------------------------".$br;

// Test relative path navigation
$testNode = $nodeB2X;
echo "Current node: ".$testNode->getPath().$br;

// Using . (current directory)
$currentDir = $testNode->findByPath('.');
echo "findByPath('.'): ".($currentDir ? $currentDir->getPath() : "Not found").$br;

// Using .. (parent directory)
$parentDir = $testNode->findByPath('..');
echo "findByPath('..'): ".($parentDir ? $parentDir->getPath() : "Not found").$br;

// Using ../.. (grandparent)
$grandparentDir = $testNode->findByPath('../..');
echo "findByPath('../..'): ".($grandparentDir ? $grandparentDir->getPath() : "Not found").$br;

// Using ./../B1 (relative path)
$relativePath = $testNode->findByPath('./../B1');
echo "findByPath('./../B1'): ".($relativePath ? $relativePath->getPath() : "Not found").$br;
echo $br;

// Example 11: Node Operations - Add
echo "Example 11: Node Operations - Add".$br;
echo "--------------------------------".$br;

// Add node (will fail if exists and replace=false)
$result = $nodeA->createNode(['name' => 'A2', 'data' => 'Node A2', 'replace' => false]);
echo "Added node A2: ".($result ? "Success" : "Failed - ".$nodeA->lastError).$br;

// Add duplicate (will replace if replace=true)
$result = $nodeA->createNode(['name' => 'A1', 'data' => 'Replaced A1', 'replace' => true]);
echo "Replaced node A1: ".($result ? "Success" : "Failed - ".$nodeA->lastError).$br.$br;

// Example 12: Node Operations - Delete
echo "Example 12: Node Operations - Delete".$br;
echo "--------------------------------".$br;

echo "Deleting node A2:".$br;
if ($nodeA->delNode('A2')) {
    echo "  - Successfully deleted".$br;
} else {
    echo "  - Failed: ".$nodeA->lastError.$br;
}
echo $br;

// Example 13: Node Operations - Copy
echo "Example 13: Node Operations - Copy".$br;
echo "--------------------------------".$br;

$copyNode = $nodeA->copyNode('A1', $nodeB, 'A1-copy');
if ($copyNode) {
    echo "Copied A1 to B as A1-copy: Success".$br;
    echo "  - Original path: ".$nodeA->getChild('A1')->getPath().$br;
    echo "  - Copy path: ".$copyNode->getPath().$br;
} else {
    echo "Copy failed: ".$nodeA->lastError.$br;
}
echo $br;

// Example 14: Node Operations - Move
echo "Example 14: Node Operations - Move".$br;
echo "--------------------------------".$br;

$moveNode = $nodeA->moveNode('A1', $nodeC);
if ($moveNode) {
    echo "Moved A1 to C: Success".$br;
    echo "  - New path: ".$moveNode->getPath().$br;
} else {
    echo "Move failed: ".$nodeA->lastError.$br;
}
echo $br;

// Example 15: Node Operations - Rename
echo "Example 15: Node Operations - Rename".$br;
echo "--------------------------------".$br;

if ($nodeB->renameNode('B1', 'B1-renamed')) {
    echo "Renamed B1 to B1-renamed: Success".$br;
    echo "  - New path: ".$nodeB->getChild('B1-renamed')->getPath().$br;
} else {
    echo "Rename failed: ".$nodeB->lastError.$br;
}
echo $br;

// Example 16: Node Sorting
echo "Example 16: Node Sorting".$br;
echo "--------------------------------".$br;

echo "Children of node B before sorting:".$br;
foreach ($nodeB->children as $name => $child) {
    echo "  - $name".$br;
}

$nodeB->sortNode(true); // Ascending
echo "After ascending sort:".$br;
foreach ($nodeB->children as $name => $child) {
    echo "  - $name".$br;
}

$nodeB->sortNode(false); // Descending
echo "After descending sort:".$br;
foreach ($nodeB->children as $name => $child) {
    echo "  - $name".$br;
}
echo $br;

// Example 17: Find by Data
echo "Example 17: Find by Data".$br;
echo "--------------------------------".$br;

// Set some nodes with same data
$nodeB->getChild('B2')->data = "Special Data";
$nodeC->data = "Special Data";

$foundNodes = $root->findByData("Special Data", false, true);
echo "Found ".count($foundNodes)." node(s) with data 'Special Data':".$br;
foreach ($foundNodes as $node) {
    echo "  - ".$node->getPath().$br;
}

$singleNode = $root->findByData("Special Data", true, true);
if ($singleNode) {
    echo "First node found: ".$singleNode->getPath().$br;
}
echo $br;

// Example 18: Serialization with HMAC
echo "Example 18: Serialization with HMAC".$br;
echo "--------------------------------".$br;

$secretKey = "my-secret-key";
$serialized = $root->serialize(false, $secretKey, 'sha256');
echo "Serialized tree (length: ".strlen($serialized)." bytes)".$br;

// Unserialize
$unserialized = $root->unserialize($serialized, [], $secretKey, 'sha256');
if ($unserialized) {
    echo "Unserialized successfully".$br;
    echo "Unserialized root path: ".$unserialized->getPath().$br;
    echo "Unserialized root data: ".$unserialized->data.$br;
} else {
    echo "Unserialize failed: ".$root->lastError.$br;
}
echo $br;

// Example 19: Get Root Node
echo "Example 19: Get Root Node".$br;
echo "--------------------------------".$br;

echo "Current node: ".$nodeB2X->getPath().$br;
$foundRoot = $nodeB2X->getRoot();
echo "Root node: ".$foundRoot->getPath().$br;
echo "Root data: ".$foundRoot->data.$br.$br;

// Example 20: Debug Information
echo "Example 20: Debug Information".$br;
echo "--------------------------------".$br;

$debugInfo = $root->__debugInfo();
echo "Root node debug info:".$br;
echo "  path: ".$debugInfo['path'].$br;
echo "  name: ".$debugInfo['name'].$br;
echo "  data: ".var_export($debugInfo['data'], true).$br;
echo "  isRoot: ".var_export($debugInfo['isRoot'], true).$br;
echo "  parent: ".var_export($debugInfo['parent'], true).$br;
echo "  children count: ".count($debugInfo['children']).$br.$br;

// Example 21: DTreeIterator Debug Information
echo "Example 21: DTreeIterator Debug Information".$br;
echo "--------------------------------".$br;

echo "Creating iterator and showing debug info:".$br;
$debugIterator = new DTreeIterator($root, true, true);
$debugIterator->next(); // Move to next node to populate state
$iteratorDebugInfo = $debugIterator->__debugInfo();
echo "Iterator debug info:".$br;
echo "  global: ".var_export($iteratorDebugInfo['global'], true).$br;
echo "  deepFirst: ".var_export($iteratorDebugInfo['deepFirst'], true).$br;
echo "  position: ".$iteratorDebugInfo['position'].$br;
echo "  currLevel: ".$iteratorDebugInfo['currLevel'].$br;
echo "  levelPosition: ".var_export($iteratorDebugInfo['levelPosition'], true).$br;
echo $br;

echo "Example completed!".$br;
