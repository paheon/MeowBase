<?php
//
// Cache.php - Cache class
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
namespace Paheon\MeowBase;

use Paheon\MeowBase\ClassBase;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

class Cache {

	use ClassBase;

    public const DEFAULT_LIFETIME	= 86400;					// Cache lift time = 1 day (in seconds)
	
    // Cache Config //
    protected   array   $config = [
		"adapterList"	=> [
			"files"     => [
				"path"  	=> __DIR__.'/cache',
				"namespace"	=> '',
			],
			"memcached" => [
				"namespace" => '',
				"servers" => [
					"main" => [
						"host"  	=> 'localhost',				// Default Memcached host 
						"port"  	=> 11211,					// Default Memcached port
						"options"	=> '',						// options : format var1=val1&var2=val2
						"user"		=> '',						// Default no user
						"password"	=> '',						// Default no password
					],	
				],
			],	
        ],
        "enable"    => false,
		"lifeTime"	=> Cache::DEFAULT_LIFETIME,					// Default one week lift time
        "siteID"    => "meow",                                  // site ID
        "adapter"   => "files",                                 // Selectedadapter
    ];

    // Cache pool and item objects //
    protected   ?TagAwareAdapter	$pool 	= null;				// Cache Pool object
	protected	?CacheItem			$item	= null;				// Cache Item object

    // Cache parameters //
	protected	string		$adpater	= "files";				        // Filesystem adapter
    protected   int     	$lifeTime   = Cache::DEFAULT_LIFETIME;      // Default cache lift time
    public      bool    	$enable     = true;         		        // Cache enable
	
    // Constructor //
    public function __construct(array $cacheConfig = [], string $adpater = "", int $lifeTime = -1) {

		$this->denyWrite = array_merge($this->denyWrite, [ 'config', 'pool', 'item', 'adpater', 'lifeTime' ]);

        // Load config //
        $cacheConfig = array_intersect_key($cacheConfig, $this->config);
        $this->config = array_replace_recursive($this->config, $cacheConfig);

        // Set default adpater //
        $this->adpater = (isset($this->config["adapterList"][$adpater])) ? $adpater : $this->config["adapter"];

		// Set cache life time //
        $this->lifeTime = ($lifeTime >= 0) ? $lifeTime : $this->config['lifeTime'];

		// Set cache object //
        $adapter = false;
		if ($this->adpater == "memcached") {
			$dsn = [];
			foreach($this->config["adapterList"]["memcached"]["servers"] as $serverInfo) {
				// Host //
				$host = $serverInfo["host"] ?? "";
				if ($host != "") {
					//SASL username and password
					$pwd  = $serverInfo["password"] ?? "";
					$name = $serverInfo["user"] ?? "";
                    $port = $serverInfo["port"] ?? "";
                    $opt  = $serverInfo["options"] ?? "";
                    if ($name != "") $name = $name.$pwd."@";
					if ($port != "") $port = ":".$port;
                    if ($opt != "") $opt = "?".$opt;
					$dsn[] = "memcached://$name$host$port$opt";
				}	
			}
			if (count($dsn) > 0) {
				// Connect to host //
				//if (count($dns) == 1) $dns = reset($dns);
				$client = MemcachedAdapter::createConnection($dsn);
				
				// Build memcached adapter //
				$adapter = new MemcachedAdapter(
					$client,
					$this->config["adapterList"]["memcached"]["namespace"],
					$this->lifeTime,
				);
            }    
		}	
		
		// Default adapter : FilesystemAdapter //
		if (is_null($adapter) || $adapter === false) {
			// Build file adapter //
			$this->adpater = "files";       
			$adapter = new FilesystemAdapter(
				$this->config["adapterList"]["files"]["namespace"],
				$this->lifeTime,
				$this->config["adapterList"]["files"]["path"],
			);	
		}
		
		// Create pool //
		if (is_object($adapter)) {
			$this->pool = new TagAwareAdapter($adapter);
            $this->enable = $this->config['enable'];
		} else {
            $this->enable = false;
        }	
    }

    // Getter //
    public function getSiteID():string {
        return $this->config['siteID'];
    }

	public function getKey():?string {
		if (is_null($this->item)) return null;
		return $this->item->getKey();
	}
	public function getMetadata():?array {
		if (is_null($this->item)) return null;
		return $this->item->getMetadata();
	}

    // Clear cache //
    public function clear(bool $prune = true): void {
        if (is_object($this->pool))  { 
			$this->pool->clear();
			if ($prune) {
			    $this->pool->prune();
			}
		}
    }
    
    // Clear site cache //
	public function clearSite(bool $prune = true):?bool {
	    return $this->delItemByTag($this->config["siteID"], $prune);
	}

    // Safe Key //
    public function safeKey(mixed $key):?string {
		if (is_null($key) || is_resource($key)) {
			return null;
		} 
		if (is_array($key) || is_object($key)) {
			$key = json_encode($key);
			if ($key === false) return null;
		} 
        return md5($this->config['siteID'].$key);
    }
	
    public function safeTag(mixed $tag):mixed {
		if (is_array($tag)) {
			$newTag = [];
			foreach($tag as $idx => $value) {
				$safeTag = $this->safeKey($value);
				if (!is_null($safeTag)) {
					$newTag[$idx] =  $safeTag;
				} else {
					return null;
				}
			}
		} else {
			$newTag = $this->safeKey($tag);
		}
        return $newTag;
    }

	// Get cache item by key //
	public function findItem(mixed $key):?CacheItem {
        if (!$this->enable || !is_object($this->pool)) return null;
		$key = $this->safeKey($key);	
		if (is_null($key)) return null;
		$this->item = $this->pool->getItem($key);				
		return $this->item;
	}
	public function findItemBySafeKey(string $key):?CacheItem {
        if (!$this->enable || !is_object($this->pool) || $key == "") return null;
		$this->item = $this->pool->getItem($key);				
		return $this->item;
	}

	// Delete cache item //
	public function delItem(mixed $key = null, bool $prune = false):?bool {
        if (!$this->enable || !is_object($this->pool)) return null;
        $result = null;
		if (is_null($key)) {
			$key = $this->getKey();
			if (!is_null($key)) {
			    $result = $this->pool->deleteItem($key);
			}    
		} else {
			$key = $this->safeKey($key);
			$result = $this->pool->deleteItem($key);
		}
	    if ($prune) {
			$this->pool->prune();
	    }
        return $result;
	}

	// Delete cache item by tags //
	public function delItemByTag(mixed $tags, bool $prune = false):?bool {
        if (!$this->enable || !is_object($this->pool)) return null;
		$result = null;
		$tags = $this->safeTag($tags);
		if (!is_null($tags)) {
			if (!is_array($tags)) $tags = [ $tags ];
			$result = $this->pool->invalidateTags($tags);
		}
	    if ($prune) {
			$this->pool->prune();
	    }
		return $result;
	}
	
	// Check cache hit //
    public function isHit(mixed $key = null):?bool {
        if (!$this->enable || !is_object($this->pool) || is_null($key)) return null;
		
		// Auto find item by key //
		$this->findItem($key);
		
		if (is_null($this->item)) { return null; }
	
		return $this->item->isHit();
	}
	
    public function isHitBySafeKey(string $key):?bool {
        if (!$this->enable || !is_object($this->pool) || $key == "") return null;

		// Auto find item by key //
		$this->findItemBySafeKey($key);
		
		if (is_null($this->item)) { return null; }
	
		return $this->item->isHit();
	}
	
    // Get Data //
	public function get():mixed {
        if (!$this->enable || !is_object($this->pool) || !is_object($this->item)) return null;
		return $this->item->get();
	}	

	// Set Data //
    public function set(mixed $value):static {
		if ($this->enable && is_object($this->pool) && is_object($this->item)) {
			$this->item->set($value);
		}
        return $this;
    }
	
	// Set Tag //
    public function tag(mixed $tags):static {
		if ($this->enable && is_object($this->pool) && is_object($this->item)) {
			$tags = $this->safeTag($tags);
			$this->item->tag($tags);
		}
		return $this;
	}
	
	// Set expire time //
	public function expiresAfter(mixed $time):static {	
		if ($this->enable && is_object($this->pool) && is_object($this->item)) {
			$this->item->expiresAfter($time);
		}
		return $this;
	}
    public function expiresAt(?\DateTimeInterface $expiration):static {
		if ($this->enable && is_object($this->pool) && is_object($this->item)) {
			$this->item->expiresAt($expiration);
		}
		return $this;
	}

	// Save data to cache //
	public function save(bool $defer = false):bool {
		$result = false;
		if ($this->enable && is_object($this->pool) && is_object($this->item)) {
		    // Add site tag //
			$tags = $this->safeTag($this->config["siteID"]);
			$this->item->tag($tags);
		    // Save data //
			if ($defer) {
				$result = $this->pool->saveDeferred($this->item);
			} else {
				$result = $this->pool->save($this->item);
			}
		}

		return $result;
	}

	// Commit defered cache data //
	public function commit():bool {
		$result = false;
		if ($this->enable && is_object($this->pool)) {
			$result = $this->pool->commit();
		}
		return $result;
	}
}	
