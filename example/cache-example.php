<?php
/**
 * Cache Example
 * 
 * This example demonstrates how to use the Cache class for caching data
 * with support for multiple adapters (files, memcached) and tag-based invalidation.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.2
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\Cache;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "Cache Example".$br;
echo "==========================================".$br.$br;

// Example 1: Basic Cache Setup
echo "Example 1: Basic Cache Setup".$br;
echo "--------------------------------".$br;

$config = new Config();
$cacheConfig = $config->getConfigByPath("cache");

$cache = new Cache($cacheConfig);
echo "Cache initialized".$br;
echo "Site ID: ".$cache->getSiteID().$br;
echo "Cache enabled: ".var_export($cache->enable, true).$br;
echo "Cache adapter: ".$cache->adpater.$br;
echo "Cache lifetime: ".$cache->lifeTime." seconds".$br;
echo "Cache pool: ".(is_object($cache->pool) ? "Available" : "Not available").$br.$br;

// Example 2: Basic Cache Operations
echo "Example 2: Basic Cache Operations".$br;
echo "--------------------------------".$br;

if (!$cache->enable) {
    echo "Cache is disabled. Enable it in config to test cache operations.".$br.$br;
} else {
    $key = "test_key";
    $value = "test_value_123";
    
    echo "Testing cache with key: '$key'".$br;
    
    // Check if cache hit
    if ($cache->isHit($key)) {
        echo "Cache HIT!".$br;
        $cachedValue = $cache->get();
        echo "Cached value: ".var_export($cachedValue, true).$br;
    } else {
        echo "Cache MISS!".$br;
        
        // Find item to create cache entry
        $cache->findItem($key);
        if (is_object($cache->item)) {
            // Set value
            $cache->set($value);
            echo "Setting value: ".var_export($value, true).$br;
            
            // Save to cache
            if ($cache->save()) {
                echo "Value saved to cache successfully!".$br;
            } else {
                echo "Failed to save to cache!".$br;
            }
        }
    }
    echo $br;
    
    // Test cache hit after saving
    echo "Testing cache hit after saving:".$br;
    if ($cache->isHit($key)) {
        echo "Cache HIT!".$br;
        $cachedValue = $cache->get();
        echo "Retrieved value: ".var_export($cachedValue, true).$br;
    } else {
        echo "Cache MISS!".$br;
    }
    echo $br;
}

// Example 3: Cache with Tags
echo "Example 3: Cache with Tags".$br;
echo "--------------------------------".$br;

if ($cache->enable) {
    $key1 = "user_123";
    $key2 = "user_456";
    $key3 = "product_789";
    
    // Cache user data with tags
    echo "Caching user data with tags:".$br;
    $cache->findItem($key1);
    if (is_object($cache->item)) {
        $cache->set(["id" => 123, "name" => "John Doe"]);
        $cache->tag(["users", "user_123"]);
        $cache->save();
        echo "  - Cached user 123 with tags: users, user_123".$br;
    }
    
    $cache->findItem($key2);
    if (is_object($cache->item)) {
        $cache->set(["id" => 456, "name" => "Jane Smith"]);
        $cache->tag(["users", "user_456"]);
        $cache->save();
        echo "  - Cached user 456 with tags: users, user_456".$br;
    }
    
    // Cache product data with different tags
    $cache->findItem($key3);
    if (is_object($cache->item)) {
        $cache->set(["id" => 789, "name" => "Product X"]);
        $cache->tag(["products", "product_789"]);
        $cache->save();
        echo "  - Cached product 789 with tags: products, product_789".$br;
    }
    echo $br;
    
    // Invalidate by tag
    echo "Invalidating cache by tag 'users':".$br;
    if ($cache->delItemByTag("users")) {
        echo "  - All items tagged with 'users' have been invalidated".$br;
        
        // Check if items are still cached
        echo "  - Checking cache status:".$br;
        echo "    user_123: ".($cache->isHit($key1) ? "Cached" : "Invalidated").$br;
        echo "    user_456: ".($cache->isHit($key2) ? "Cached" : "Invalidated").$br;
        echo "    product_789: ".($cache->isHit($key3) ? "Cached" : "Invalidated").$br;
    }
    echo $br;
}

// Example 4: Cache Expiration
echo "Example 4: Cache Expiration".$br;
echo "--------------------------------".$br;

if ($cache->enable) {
    $expireKey = "expire_test";
    
    echo "Caching data with custom expiration (60 seconds):".$br;
    $cache->findItem($expireKey);
    if (is_object($cache->item)) {
        $cache->set("This will expire in 60 seconds");
        $cache->expiresAfter(60); // Expire in 60 seconds
        $cache->save();
        echo "  - Data cached with 60 second expiration".$br;
        
        // Get metadata
        $metadata = $cache->getMetadata();
        if ($metadata) {
            echo "  - Cache metadata available".$br;
        }
    }
    echo $br;
}

// Example 5: Site-wide Cache Clearing
echo "Example 5: Site-wide Cache Clearing".$br;
echo "--------------------------------".$br;

if ($cache->enable) {
    echo "Clearing all site cache:".$br;
    if ($cache->clearSite()) {
        echo "  - All site cache cleared successfully".$br;
    } else {
        echo "  - Failed to clear site cache".$br;
    }
    echo $br;
}

// Example 6: Safe Key Generation
echo "Example 6: Safe Key Generation".$br;
echo "--------------------------------".$br;

$testKeys = [
    "simple_key",
    "key with spaces",
    ["array", "key"],
    ["user" => 123, "action" => "view"],
    12345,
    null
];

echo "Testing safe key generation:".$br;
foreach ($testKeys as $key) {
    $safeKey = $cache->safeKey($key);
    echo "  Key: ".var_export($key, true)." => Safe key: ".var_export($safeKey, true).$br;
}
echo $br;

// Example 7: Cache Item Management
echo "Example 7: Cache Item Management".$br;
echo "--------------------------------".$br;

if ($cache->enable) {
    $itemKey = "item_test";
    
    echo "Managing cache item:".$br;
    $cache->findItem($itemKey);
    
    if (is_object($cache->item)) {
        echo "  - Item found/created".$br;
        echo "  - Item key: ".$cache->getKey().$br;
        
        // Set value
        $cache->set(["data" => "test", "timestamp" => time()]);
        $cache->save();
        echo "  - Value set and saved".$br;
        
        // Get value
        $value = $cache->get();
        echo "  - Retrieved value: ".var_export($value, true).$br;
        
        // Delete item
        if ($cache->delItem($itemKey)) {
            echo "  - Item deleted successfully".$br;
        }
    }
    echo $br;
}

// Example 8: Debug Information
echo "Example 8: Debug Information".$br;
echo "--------------------------------".$br;

$debugInfo = $cache->__debugInfo();
echo "Cache debug info:".$br;
echo "  adapter: ".$debugInfo['adpater'].$br;
echo "  enable: ".var_export($debugInfo['enable'], true).$br;
echo "  lifeTime: ".$debugInfo['lifeTime'].$br;
echo "  pool: ".(is_object($debugInfo['pool']) ? "Object" : "null").$br;
echo "  item: ".(is_object($debugInfo['item']) ? "Object" : "null").$br.$br;

echo "Example completed!".$br;
