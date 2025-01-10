<?php
namespace Paheon\MeowBase;

use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\DTreeIterator;

// DTree class //
class DTree extends ClassBase {
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
        if ($name !== "" && $parent) {
            $parent->addNode($this, false, $replace);
        }
    }

    // Add child node //
    public function addNode(DTree $child, bool $clone = false, bool $replace = true):bool {
        $this->lastError = "";
        if (isset($this->children[$child->name])) {
            if (!$replace) {
                $this->lastError = "Child node '{$child->name}' already exists!";
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
    public function createNode(string $name, mixed $data = null, bool $replace = true):?DTree {
        $this->lastError = "";
        if ($name == "" && $this->parent === null) {
            $this->lastError = "Child node is root node!";
            return null;
        }
        if (isset($this->children[$name])) {
            if (!$replace) {
                $this->lastError = "Child node '$name'already exists!";
                return null;
            }
        }
        $newNode = new DTree($name, $data, $this, $replace);
        return $newNode;
    }

    // Create Node by Path // 
    public function createByPath(string $path, mixed $data = null, bool $replace = true):?DTree {
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
        foreach ($pathList as $idx =>$name) {
            if ($name === '') continue;
            if (isset($currNode->children[$name])) {
                if ($idx == $lastIdx) {
                    if ($replace) {
                        $currNode->children[$name]->data = $data;
                    } else {
                        $this->lastError = "Child node '$name' already exists!";
                    }    
                    return $currNode->children[$name];
                }
                $currNode = $currNode->children[$name];
            } else {
                if ($idx == $lastIdx) {
                    $newNode = new DTree($name, $data, $currNode, true);
                    return $newNode;
                } 
                $newNode = new DTree($name, null, $currNode, true);
                $currNode = $newNode;
            }
        }
        return null;
    }

    // Create node by array //
    // $recList = [ 'path' => 'data', ... ]
    public function createByArray(array $recList, bool $replace = true):array {
        $this->lastError = "";
        $result = [];
        foreach ($recList as $path => $data) {
            $result[$path] = $this->createByPath($path, $data, $replace);
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
        return false;
    }

    // Rename node //
    public function renameNode(string $srcName, string $dstName):bool {
        $this->lastError = "";
        if (!isset($this->children[$srcName])) {
            $this->lastError = "Child node '$srcName'not found!";
            return false;
        }    
        if (isset($this->children[$dstName])) {
            $this->lastError = "Child node '$dstName' already exists!";
            return false;
        }
        // Build a new node 
        $newNode = new DTree($dstName, $this->children[$srcName]->data, $this, true);
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
            return null;
        }
        if (!$dstName) $dstName = $srcName;
        if (!$replace && $dstNode && $dstNode->getChild($dstName)) {
            $this->lastError = "Child node '$srcName' in destination node is already exists!";
            return null;
        }
        if ($clone && is_object($this->children[$srcName]->data)) {
            $srcData = clone $this->children[$srcName]->data;
        } else {
            $srcData = $this->children[$srcName]->data;
        }
        $newNode = new DTree($dstName, $srcData, $dstNode, $replace);
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
            return null;
        }
        $data = $decoded['data'];
        $hash = $decoded['hash'];
        if ($key !== '' || $hash !== '') {
            $calculatedHash = hash_hmac($algo, $data, $key);
            if (!hash_equals($calculatedHash, $hash)) {
                $this->lastError = "Hash mismatch, data integrity compromised!";
                return null;
            }    
        }
        $newNode = unserialize($data, $options);
        if ($newNode) {
            $newNode->parent = null;
            return $newNode;
        }
        $this->lastError = "Failed to unserialize data!";
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
            'parent' => $this->parent->name,
            'children' => $this->children,
            'isRoot' => $this->isRoot()
        ];
    }
    
}
