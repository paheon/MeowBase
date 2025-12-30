<?php
/**
 * DTree class
 * 
 * This class is used to manage a tree structure.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\Tools\DTreeIterator;

// DTree class //
class DTree {

    use ClassBase;
    
    protected   ?DTree  $parent;
    protected   mixed   $data;
    protected   string  $name;
    protected   bool    $sort = false;
    protected   array   $children = [];

    // Constructor //
    public function __construct(string $name = "", mixed $data = null, ?DTree $parent = null, bool $replace = true) {
        $this->denyWrite = array_merge($this->denyWrite, [ 'name', 'children' ]);
        // Empty name and null data and parent indicated that this is a root node //
        $this->name = $name;
        $this->data = $data;
        $this->parent = $parent;
        if ($this->name !== "" && $parent) {
            $this->parent->addNode($this, false, $replace);
        }
    }

    // Build node //
    public function buildNode(array $param):?DTree {
        $name = $param['name'] ?? "";
        $data = $param['data'] ?? null;
        $parent = ($param['parent'] instanceof DTree) ? $param['parent'] : null;
        $replace = $param['replace'] ?? true;
        return new DTree($name, $data, $parent, $replace);
    }

    // Set node properties //
    protected function setNode(DTree $node, array $param):void {
        $node->name = $param['name'] ?? "";
        $node->data = $param['data'] ?? null;
        $node->parent = $param['parent'] ?? null;
    }

    // Get node properties //
    protected function getNode(DTree $node):array {
        return [ 'name' => $node->name, 'data' => $node->data ];
    }

    // Add child node //
    public function addNode(DTree $child, bool $clone = false, bool $replace = true):bool {
        $this->lastError = "";
        if (isset($this->children[$child->name])) {
            if (!$replace) {
                $this->lastError = "Child node '{$child->name}' already exists!";
                $this->throwException($this->lastError, 2);
                return false;
            }
        }
        if ($clone) {
            $child = clone $child;
        }
        $child->parent = $this;
        $this->children[$child->name] = $child;
        if ($this->sort) {
            $this->sortNode();
        }
        return true;
    }

    // create child node //
    public function createNode(array $param):?DTree {
        $this->lastError = "";
        $param['name'] = $param['name'] ?? "";
        $param['replace'] = $param['replace'] ?? true;
        if ($param['name'] == "" && $this->parent === null) {
            $this->lastError = "Child node is root node!";
            $this->throwException($this->lastError, 1);
            return null;
        }
        if (isset($this->children[$param['name']])) {
            if (!$param['replace']) {
                $this->lastError = "Child node '{$param['name']}' already exists!";
                $this->throwException($this->lastError, 2);
                return null;
            }
        }
        $param['data'] = $param['data'] ?? null;
        $param['parent'] = $this;
        $newNode = $this->buildNode($param);
        return $newNode;
    }

    // Create Node by Path // 
    public function createByPath(string $path, array $param):?DTree {
        $this->lastError = "";
        $path = trim($path);
        if (substr($path, 0, 1) === '/') {
            $currNode = $this->getRoot();
            $path = substr($path, 1);
        } else {
            $currNode = $this;
        }
        $pathList = explode('/', $path);
        $lastIdx = count($pathList) - 1;
        $param['data'] = $param['data'] ?? null;
        $param['replace'] = $param['replace'] ?? true;
        foreach ($pathList as $idx =>$name) {
            if ($name === '') continue;
            if (isset($currNode->children[$name])) {
                // has child node //
                if ($idx == $lastIdx) {
                    if ($param['replace']) {
                        $param['parent'] = $currNode;
                        $param['name'] = $name;
                        $this->setNode($currNode->children[$name], $param);
                    } else {
                        $this->lastError = "Child node '{$param['name']}' already exists!";
                        $this->throwException($this->lastError, 2);
                    }    
                    return $currNode->children[$name];
                }
                $currNode = $currNode->children[$name];
            } else {
                // Child node not found //
                if ($idx == $lastIdx) {
                    // New child node //
                    $param['parent'] = $currNode;
                    $param['name'] = $name;
                    $newNode = $this->buildNode($param);
                    return $newNode;
                } 
                // Create a new parent node //
                $newNode = $this->buildNode([ 'name' => $name, 'data' => null, 'parent' => $currNode, 'replace' => true ]);
                $currNode = $newNode;
            }
        }
        return null;
    }

    // load node from array //
    // $recList = [ 'path' => [ 'data' => 'value1', 'name' => 'value2', ... ], 'path2' => [ 'data' => 'value3', 'name' => 'value4', ... ], ... ]
    public function loadFromArray(array $recList):array {
        $this->lastError = "";
        $result = [];
        foreach ($recList as $path => $param) {
            $result[$path] = $this->createByPath($path, $param);
        }
        return $result;
    }
    
    // Save node to array //
    public function saveToArray(?DTree $startNode = null):array {
        $global = true;
        if ($startNode) {
            if (!$startNode->isRoot()) {
                $global = false;
            } 
        } else {    
            $startNode = $this->getRoot();
        }
        $result = [];
        $iterator = new DTreeIterator($startNode, $global);
        foreach ($iterator as $node) {
            $result[$node->getPath()] = $this->getNode($node);
        }
        return $result;
    }

    // Delete child node //
    public function delNode(string $name):bool {
        $this->lastError = "";
        if (isset($this->children[$name])) {
            unset($this->children[$name]);
            return true;
        } 
        $this->lastError = "Child node '$name' not found!";
        $this->throwException($this->lastError, 3);
        return false;
    }

    // Rename node //
    public function renameNode(string $srcName, string $dstName, bool $replace = true):bool {
        $this->lastError = "";
        if (!isset($this->children[$srcName])) {
            $this->lastError = "Child node '$srcName'not found!";
            $this->throwException($this->lastError, 3);
            return false;
        }    
        if (isset($this->children[$dstName])) {
            $this->lastError = "Child node '$dstName' already exists!";
            $this->throwException($this->lastError, 2);
            return false;
        }
        // Build a new node 
        $this->buildNode([ 'name' => $dstName, 'data' => $this->children[$srcName]->data, 'parent' => $this, 'replace' => $replace ]);
        unset($this->children[$srcName]);
        if ($this->sort) {
            $this->sortNode();
        }
        return true;
    }

    // Duplicate node (Get new node) //
    public function dupNode(string $srcName, ?DTree $dstNode = null, ?string $dstName = null, bool $clone = false, bool $replace = true):?DTree {
        $this->lastError = "";
        if (!isset($this->children[$srcName])) {
            $this->lastError = "Child node '$srcName'not found!";
            $this->throwException($this->lastError, 3);
            return null;
        }
        if (!$dstName) $dstName = $srcName;
        if (!$replace && $dstNode && $dstNode->getChild($dstName)) {
            $this->lastError = "Child node '$srcName' in destination node is already exists!";
            $this->throwException($this->lastError, 4);
            return null;
        }
        if ($clone && is_object($this->children[$srcName]->data)) {
            $srcData = clone $this->children[$srcName]->data;
        } else {
            $srcData = $this->children[$srcName]->data;
        }
        $newNode = $this->buildNode([ 'name' => $dstName, 'data' => $srcData, 'parent' => $dstNode, 'replace' => $replace ]);
        return $newNode;
    }   

    //  Copy Node //
    public function copyNode(string $srcName, DTree $dstNode, ?string $dstName = null, bool $replace = false):?DTree {
        return $this->dupNode($srcName, $dstNode, $dstName, true, $replace);
    }   

    // Move node //
    public function moveNode(string $srcName, DTree $dstNode, bool $replace = false):?DTree {
        $newNode = $this->dupNode($srcName, $dstNode, null, false, $replace);
        if (!$newNode) return null;
        $this->delNode($srcName);
        return $newNode;
    }

    // Serialize node with HMAC //
    public function serialize(bool $currentNode = false, string $key = 'secret', string $algo = 'sha256'): string {
        // Temporary remove parent //
        if ($currentNode) {
            $parent = $this->parent;
            $this->parent = null;
        }
        // Serialize //
        $data = $currentNode ? serialize($this) : serialize($this->getRoot());
        // Restore parent //
        if ($currentNode) $this->parent = $parent;
        // Hash //
        if ($key === '') {
            $hash = '';
        } else {
            $hash = hash_hmac($algo, $data, $key);
        }
        
        return json_encode(['data' => $data, 'hash' => $hash]);
    }

    // Unserialize node with HMAC verification //
    public function unserialize(string $serializedData, array $options = [], string $key = 'secret', string $algo = 'sha256'): ?DTree {
        $this->lastError = "";
        $decoded = json_decode($serializedData, true);
        if (!is_array($decoded) || !isset($decoded['data'], $decoded['hash'])) {
            $this->lastError = "Invalid serialized data!";
            $this->throwException($this->lastError, 5);
            return null;
        }
        $data = $decoded['data'];
        $hash = $decoded['hash'];
        if ($key !== '' || $hash !== '') {
            $calculatedHash = hash_hmac($algo, $data, $key);
            if (!hash_equals($calculatedHash, $hash)) {
                $this->lastError = "Hash mismatch, data integrity compromised!";
                $this->throwException($this->lastError, 6);
                return null;
            }    
        }
        $newNode = unserialize($data, $options);
        if ($newNode) {
            $newNode->parent = null;
            return $newNode;
        }
        $this->lastError = "Failed to unserialize data!";
        $this->throwException($this->lastError, 7);
        return null;
    }

    // Sort children //
    public function sortNode(bool $asc = true, int $sortFlag = SORT_REGULAR):?DTree {
        $this->lastError = "";
        if ($asc) {
            ksort($this->children, $sortFlag);
        } else {
            krsort($this->children, $sortFlag);
        }
        return $this;
    }

    // Get root node //
    public function getRoot():DTree {
        $node = $this;
        while ($node->parent) {
            $node = $node->parent;
        }
        return $node;
    }

    // Get child node //
    public function getChild(string $name):?DTree {
        return $this->children[$name] ?? null;
    }

    // Check whether this is a root node //
    public function isRoot():bool {
        return $this->name === "" && $this->parent === null;
    }

    // Get path from root to current node //
    public function getPath():string {
        $node = $this;
        $path = [];
        while ($node->parent) {
            $path[] = $node->name;
            $node = $node->parent;
        }
        return "/" . implode('/', array_reverse($path));
    }

    // Find node by path //
    public function findByPath(string $path):?DTree {
        $path = trim($path);
        if (substr($path, 0, 1) === '/') {
            $currNode = $this->getRoot();
            $path = substr($path, 1);
        } else {
            $currNode = $this;
        }
        $pathList = explode('/', $path);
        foreach ($pathList as $name) {
            if ($name === '') continue;
            if (isset($currNode->children[$name])) {
                $currNode = $currNode->children[$name];
            } else {
                return null;
            }
        }
        return $currNode;
    }

    // Seearch node by data //
    public function findByData(mixed $data, bool $singleResult = false, bool $global = true):array|DTree|null {
        $nodes = new DTreeIterator($this, $global);
        $result = [];
        foreach($nodes as $node) {
            if ($node->data === $data) {
                if ($singleResult) return $node;
                $result[] = $node;
            }    
        }
        if (count($result) == 0) return null;
        return $result;
    }

    public function __toString():string {
        return $this->name;
    }

    public function __debugInfo():array {
        return [
            'path' => $this->getPath(),
            'name' => $this->name,
            'data' => $this->data,
            'isRoot' => $this->isRoot(),
            'parent' => is_null($this->parent) ? null : $this->parent->name,
            'children' => $this->children,
        ];
    }
    
}
