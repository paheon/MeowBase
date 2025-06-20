<?php
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\Tools\DTree;

// DTreeIterator class //
class DTreeIterator implements \Iterator {

    use ClassBase;

    protected   DTree   $treeRoot;
    protected   ?DTree  $treeCurr;
    protected   ?DTree  $rootParent;
    protected   array   $nodeStack = [];
    protected   int     $position = 0;
    protected   bool    $global = true;

    public function __construct(DTree $tree, bool $global = true) {
        $this->global = $global;
        if ($global) {
            $this->treeCurr = $this->treeRoot = $tree->getRoot();
            $this->rootParent = null;
        } else {
            $this->treeCurr = $this->treeRoot = $tree;
            $this->rootParent = $tree->parent;
        }
        $this->nodeStack = [$this->treeRoot];
    }

    // Required Iterator methods
    public function current(): mixed {
        return $this->treeCurr;
    }

    public function key(): mixed {
        return $this->position;
    }

    public function next(): void {
        $this->position++;
        // Get children of current node
        $children = array_values($this->treeCurr->children);
        if (!empty($children)) {
            // If current node has children, push first child to stack
            $this->treeCurr = $children[0];
            $this->nodeStack[] = $this->treeCurr;
        } else {
            // If no children, pop current node and try to get next sibling
            while (!empty($this->nodeStack)) {
                $current = array_pop($this->nodeStack);
                $parent = $current->parent;
                if ($parent) {
                    if (!$this->global && $parent === $this->rootParent) {
                        $this->treeCurr = null;
                        return;                        
                    }
                    $siblings = array_values($parent->children);
                    $currentIndex = array_search($current, $siblings);
                    if ($currentIndex !== false && isset($siblings[$currentIndex + 1])) {
                        // Found next sibling
                        $this->treeCurr = $siblings[$currentIndex + 1];
                        $this->nodeStack[] = $this->treeCurr;
                        return;
                    }
                }
            }
            // No more nodes to traverse
            $this->treeCurr = null;
        }
    }

    public function rewind(): void {
        $this->position = 0;
        $this->treeCurr = $this->treeRoot;
        $this->nodeStack = [$this->treeRoot];
    }

    public function valid(): bool {
        return $this->treeCurr !== null;
    }
}
