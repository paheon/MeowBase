<?php
/**
 * DTreeIterator Class
 * 
 * This class is used to iterate through a DTree structure.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\Tools\DTree;

// DTreeIterator class //
class DTreeIterator implements \Iterator {

    use ClassBase;

    protected   DTree   $treeRoot;
    protected   ?DTree  $treeCurr;
    protected   ?DTree  $rootParent;
    protected   array   $nodeStack;
    protected   int     $position;
    protected   bool    $global;
    protected   bool    $deepFirst;
    protected   array   $levelPosition = [];
    protected   int     $currLevel = 0;

    public function __construct(DTree $tree, bool $global = true, bool $deepFirst = true) {
        $this->denyWrite = array_merge($this->denyWrite, [ 
            'treeRoot', 'treeCurr', 'rootParent', 
            'nodeStack', 'position', 'global', 'deepFirst', 
            'levelPosition', 'currLevel' 
        ]);
        $this->global = $global;
        if ($global) {
            $this->treeCurr = $this->treeRoot = $tree->getRoot();
            $this->rootParent = null;
        } else {
            $this->treeCurr = $this->treeRoot = $tree;
            $this->rootParent = $tree->parent;
        }
        $this->deepFirst = $deepFirst;
        $this->rewind();
    }

    // Required Iterator methods
    public function current(): mixed {
        return $this->treeCurr;
    }

    public function key(): mixed {
        return $this->position;
    }

    public function next(): void {
        // Exist if treeCurr reach to the end of node //
        if ($this->treeCurr === null) {
            return;
        }

        $this->position++;
       
        if ($this->deepFirst) {

            //----- Deep first Search (DFS) -----//

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
        } else {
            //----- Breadth first Search (BFS) -----//
            
            // Exist if no node exists in stack //
            if (empty($this->nodeStack)) {
                $this->treeCurr = null;
                return;
            }
            
            // Get current node (first in queue)
            $current = $this->nodeStack[0];
            
            // Calculate current node's level
            $nodeLevel = 0;
            $node = $current;
            while ($node->parent && ($this->global || $node->parent !== $this->rootParent)) {
                $nodeLevel++;
                $node = $node->parent;
            }
            
            // Initialize level position for current level if not set
            if (!isset($this->levelPosition[$nodeLevel])) {
                $this->levelPosition[$nodeLevel] = 0;
            }
            
            // Add children of current node to the end of queue
            $children = array_values($current->children);
            foreach ($children as $child) {
                // Check if we should skip this child (non-global mode)
                if (!$this->global && $current === $this->rootParent) {
                    // Don't add children beyond root parent
                    continue;
                }
                $this->nodeStack[] = $child;
            }
            
            // Remove current node from queue
            array_shift($this->nodeStack);
            
            // Update level position for the level we just processed
            $this->levelPosition[$nodeLevel]++;
            
            // Get next node from queue
            if (!empty($this->nodeStack)) {
                $this->treeCurr = $this->nodeStack[0];
                
                // Update current level based on the depth of the new current node
                $depth = 0;
                $node = $this->treeCurr;
                while ($node->parent && ($this->global || $node->parent !== $this->rootParent)) {
                    $depth++;
                    $node = $node->parent;
                }
                $this->currLevel = $depth;
                
                // Initialize level position for new level if not set
                if (!isset($this->levelPosition[$this->currLevel])) {
                    $this->levelPosition[$this->currLevel] = 0;
                }
            } else {
                $this->treeCurr = null;
            }
        }
    }

    public function rewind(): void {
        $this->position = 0;
        $this->treeCurr = $this->treeRoot;
        $this->nodeStack = [$this->treeRoot];
        $this->levelPosition = [];
        $this->currLevel = 0;
    }

    public function valid(): bool {
        return $this->treeCurr !== null;
    }

}
