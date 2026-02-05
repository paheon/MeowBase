# DTree and DTreeIterator Usage Guide

## Overview

The `DTree` class provides a powerful way to manage hierarchical tree structures in PHP. It allows you to create, manipulate, and traverse tree nodes with full path support. The `DTreeIterator` class provides iterator support for traversing tree structures using either depth-first search (DFS) or breadth-first search (BFS).

## DTree Class

### Basic Usage

#### Creating a Root Node

```php
use Paheon\MeowBase\Tools\DTree;

// Create a root node (empty name, null parent)
$root = new DTree();
$root->data = "Root Node";
```

#### Creating Child Nodes

There are several ways to create child nodes:

**Method 1: Using `createNode()`**

```php
$nodeA = $root->createNode(['name' => 'A', 'data' => 'Node A']);
$nodeB = $root->createNode(['name' => 'B', 'data' => 'Node B']);
```

**Method 2: Using Constructor**

```php
$nodeC = new DTree('C', 'Node C', $root);
```

**Method 3: Using `createByPath()` with Absolute Path**

```php
$nodeD = $root->createByPath('/D', ['data' => 'Node D']);
```

**Method 4: Using `createByPath()` with Relative Path**

```php
$nodeA1 = $nodeA->createByPath('A1', ['data' => 'Node A1']);
```

### Path Navigation

#### Path Support

The `DTree` class supports path navigation with special directory references:

- `.` - Current directory (returns current node)
- `..` - Parent directory
- `/path/to/node` - Absolute path from root
- `relative/path` - Relative path from current node

```php
// Current node
$current = $node->findByPath('.');

// Parent node
$parent = $node->findByPath('..');

// Grandparent node
$grandparent = $node->findByPath('../..');

// Relative path navigation
$sibling = $node->findByPath('./../sibling-name');
```

#### Finding Nodes by Path

```php
// Absolute path from root
$found = $root->findByPath('/B/B2/B2X');

// Relative path from current node
$found = $nodeB->findByPath('B2/B2X');
```

#### Getting Node Path

```php
$path = $node->getPath(); // Returns: "/A/A1"
```

### Loading and Saving

#### Load from Array

```php
$tree = new DTree();
$tree->data = "Tree Root";

$nodeList = $tree->loadFromArray([
    '/F/F1' => ['data' => 'Node F1'],
    '/F/F2' => ['data' => 'Node F2'],
    '/G/G1/G1A' => ['data' => 'Node G1A'],
]);
```

#### Save to Array

```php
// Save entire tree from root
$savedArray = $root->saveToArray();

// Save from specific node
$savedArray = $nodeB->saveToArray($nodeB);
```

### Node Operations

#### Add Node

```php
// Create and add a node
$newNode = $parent->createNode([
    'name' => 'NewNode',
    'data' => 'New Node Data',
    'replace' => true  // Replace if exists (default: true)
]);
```

#### Delete Node

```php
if ($parent->delNode('NodeName')) {
    echo "Node deleted successfully";
} else {
    echo "Error: " . $parent->lastError;
}
```

#### Copy Node

```php
$copyNode = $source->copyNode('SourceName', $destination, 'NewName');
if ($copyNode) {
    echo "Node copied to: " . $copyNode->getPath();
}
```

#### Move Node

```php
$movedNode = $source->moveNode('SourceName', $destination);
if ($movedNode) {
    echo "Node moved to: " . $movedNode->getPath();
}
```

#### Rename Node

```php
if ($parent->renameNode('OldName', 'NewName')) {
    echo "Node renamed successfully";
}
```

### Sorting

```php
// Sort children ascending
$node->sortNode(true);

// Sort children descending
$node->sortNode(false);

// Sort with specific flag
$node->sortNode(true, SORT_NATURAL);
```

### Searching

#### Find by Data

```php
// Find all nodes with matching data
$nodes = $root->findByData("Special Data", false, true);
foreach ($nodes as $node) {
    echo $node->getPath() . "\n";
}

// Find single node with matching data
$node = $root->findByData("Special Data", true, true);
if ($node) {
    echo "Found: " . $node->getPath();
}
```

### Serialization

#### Serialize with HMAC Verification

```php
$secretKey = "my-secret-key";
$serialized = $root->serialize(false, $secretKey, 'sha256');

// Unserialize with verification
$unserialized = $root->unserialize($serialized, [], $secretKey, 'sha256');
if ($unserialized) {
    echo "Tree unserialized successfully";
}
```

### Utility Methods

#### Get Root Node

```php
$root = $anyNode->getRoot();
```

#### Get Child Node

```php
$child = $parent->getChild('ChildName');
```

#### Check if Root

```php
if ($node->isRoot()) {
    echo "This is the root node";
}
```

## DTreeIterator Class

The `DTreeIterator` class implements PHP's `Iterator` interface, allowing you to traverse tree structures using either depth-first search (DFS) or breadth-first search (BFS).

### Traversal Modes

#### Depth-First Search (DFS)

DFS traverses the tree by going as deep as possible before backtracking. This is the default mode.

```php
use Paheon\MeowBase\Tools\DTreeIterator;

// DFS (default)
$iterator = new DTreeIterator($root, true, true);
foreach ($iterator as $position => $node) {
    echo "$position: " . $node->getPath() . "\n";
}
```

**Traversal order (DFS):**
```
Root -> A -> A1 -> B -> B1 -> B2 -> B2X -> B2Y -> B3 -> C
```

#### Breadth-First Search (BFS)

BFS traverses the tree level by level, visiting all nodes at the current depth before moving to the next level.

```php
// BFS
$iterator = new DTreeIterator($root, true, false);
foreach ($iterator as $position => $node) {
    echo "$position: " . $node->getPath() . "\n";
}
```

**Traversal order (BFS):**
```
Root -> A -> B -> C -> D -> E -> A1 -> B1 -> B2 -> B3 -> B2X -> B2Y
```

### Global vs Local Iteration

#### Global Iteration

Iterates from the root of the entire tree:

```php
$iterator = new DTreeIterator($root, true);  // Global mode
foreach ($iterator as $node) {
    // Iterates entire tree starting from root
}
```

#### Local Iteration

Iterates only from the specified node and its children:

```php
$iterator = new DTreeIterator($nodeB, false);  // Local mode
foreach ($iterator as $node) {
    // Iterates only node B and its descendants
}
```

### Iterator Properties

You can access iterator properties for debugging:

```php
$iterator = new DTreeIterator($root, true, true);
$iterator->next(); // Move to next node

$debugInfo = $iterator->__debugInfo();
// Returns:
// - global: Whether iterating globally
// - deepFirst: Whether using DFS (true) or BFS (false)
// - position: Current position
// - currLevel: Current level (for BFS)
// - levelPosition: Position at each level (for BFS)
```

### Practical Examples

#### Example 1: Print Tree Structure

```php
function printTree(DTree $root) {
    $iterator = new DTreeIterator($root, true, true);
    foreach ($iterator as $node) {
        $indent = str_repeat("  ", substr_count($node->getPath(), '/') - 1);
        echo $indent . $node->name . " (" . $node->data . ")\n";
    }
}
```

#### Example 2: Find All Leaf Nodes

```php
function findLeafNodes(DTree $root) {
    $leafNodes = [];
    $iterator = new DTreeIterator($root, true, true);
    foreach ($iterator as $node) {
        if (empty($node->children)) {
            $leafNodes[] = $node;
        }
    }
    return $leafNodes;
}
```

#### Example 3: Level-by-Level Processing (BFS)

```php
function processByLevel(DTree $root) {
    $iterator = new DTreeIterator($root, true, false);
    $currentLevel = 0;
    
    foreach ($iterator as $node) {
        $level = substr_count($node->getPath(), '/') - 1;
        if ($level != $currentLevel) {
            echo "--- Level $level ---\n";
            $currentLevel = $level;
        }
        echo $node->getPath() . "\n";
    }
}
```

## Error Handling

All DTree operations set the `lastError` property on failure:

```php
$result = $parent->delNode('NonExistent');
if (!$result) {
    echo "Error: " . $parent->lastError;
}
```

## Debug Information

Both `DTree` and `DTreeIterator` support `__debugInfo()`:

```php
$debugInfo = $node->__debugInfo();
print_r($debugInfo);

$iteratorDebugInfo = $iterator->__debugInfo();
print_r($iteratorDebugInfo);
```

## Best Practices

1. **Use DFS for recursive operations**: DFS is better for operations that need to process parent-child relationships.

2. **Use BFS for level-based operations**: BFS is ideal when you need to process all nodes at the same depth together.

3. **Check `lastError`**: Always check `lastError` after operations that might fail.

4. **Path validation**: Use `findByPath()` to verify paths before operations.

5. **Root node management**: Keep a reference to the root node for global operations.

6. **Serialization security**: Always use HMAC verification when serializing sensitive data.

## API Reference

### DTree Methods

- `__construct(string $name = "", mixed $data = null, ?DTree $parent = null, bool $replace = true): void`
- `createNode(array $param): ?DTree`
- `createByPath(string $path, array $param): ?DTree`
- `loadFromArray(array $recList): array`
- `saveToArray(?DTree $startNode = null): array`
- `addNode(DTree $child, bool $clone = false, bool $replace = true): bool`
- `delNode(string $name): bool`
- `copyNode(string $srcName, DTree $dstNode, ?string $dstName = null, bool $replace = false): ?DTree`
- `moveNode(string $srcName, DTree $dstNode, bool $replace = false): ?DTree`
- `renameNode(string $srcName, string $dstName, bool $replace = true): bool`
- `sortNode(bool $asc = true, int $sortFlag = SORT_REGULAR): ?DTree`
- `findByPath(string $path): ?DTree`
- `findByData(mixed $data, bool $singleResult = false, bool $global = true): array|DTree|null`
- `getRoot(): DTree`
- `getChild(string $name): ?DTree`
- `isRoot(): bool`
- `getPath(): string`
- `serialize(bool $currentNode = false, string $key = 'secret', string $algo = 'sha256'): string`
- `unserialize(string $serializedData, array $options = [], string $key = 'secret', string $algo = 'sha256'): ?DTree`
- `__debugInfo(): array`

### DTreeIterator Methods

- `__construct(DTree $tree, bool $global = true, bool $deepFirst = true): void`
- `current(): mixed` (Iterator interface)
- `key(): mixed` (Iterator interface)
- `next(): void` (Iterator interface)
- `rewind(): void` (Iterator interface)
- `valid(): bool` (Iterator interface)
- `__debugInfo(): array`

## Example Files

- `dtree-example.php` - Comprehensive examples demonstrating all features
