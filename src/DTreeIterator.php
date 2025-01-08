<?php
namespace Paheon\MeowBase;

use Paheon\MeowBase\DTree;

// DTreeIterator class //
class DTreeIterator implements \Iterator {
    protected   DTree   $treeRoot;
    protected   ?DTree   $treeCurr;
    protected   array   $nodeStack = [];
    protected   int     $position = 0;

    public function __construct(DTree $tree) {
        $this->treeCurr = $this->treeRoot = $tree->getRoot();
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
