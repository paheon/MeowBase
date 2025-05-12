<?php
//
// CacheDB.php - CacheDB class
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
namespace Paheon\MeowBase;

use Medoo\Medoo;
use PDOStatement;
use Paheon\MeowBase\Cache;
use Paheon\MeowBase\SysLog;

class CacheDB extends Medoo {

    // Object //
    protected   Cache   $cache;                         // Cache Object
    protected   SysLog  $log;                           // Log Object
	
	// Log //
    public      bool    $enableLog      = false;        // Enable flag for Log
	public		bool	$logResult		= false;		// Enable show query result

    // Constructor //
    function __construct(array &$dbConfig, Cache $cache, SysLog $log) {
        parent::__construct($dbConfig);
        $this->cache    = $cache;
        $this->log      = $log;
    }

    // Extract join tables //
    protected function buildTableTags(string $type, string $table, ?array $join, ?array $tags):array {
        if (is_null($tags)) $tags = [];
        if (!in_array("SQL".$type, $tags))              $tags[] = "SQL".$type;
        if (!in_array("SQLTbl-".trim($table), $tags))   $tags[] = "SQLTbl-".trim($table);
        if (!is_null($join)) {
            $re = '/^\s*\[[^\]]*\]\s*([^\s]+)/i';
            $tableList = array_keys($join);
            foreach($tableList as $tableName) {
                preg_match($re, $tableName, $matches);
                if (count($matches)> 0 && isset($matches[1])) {
                    $tagTable = trim($matches[1]);
                    if (!in_array("SQLTbl-".$tagTable, $tags)) $tags[] = "SQLTbl-".$tagTable;
                }
            }
        }
        return $tags;
    }

    // Generate cache key by SQL query //
    public function getCacheKey(string $table, mixed $columns, ?array $where = null, ?array $join = null, string $method = "S"):string {
        $sep = ".";
        $key  = $method;
        $key .= $sep.$table;
        $key .= $sep.(is_string($columns) ? $columns : (is_array($columns) ? json_encode($columns) : ""));
        $key .= $sep.(is_array($where) ? json_encode($where) : "");
        $key .= $sep.(is_array($join) ? json_encode($join) : "");
        $key = urlencode($key);
        return $key;
    }

    // Cached select statement //
    public function cachedSelect(string $table, mixed $columns, ?array $where = null, ?array $join = null, ?callable $fetchFunc = null, ?array $tags = null, ?int $expire = null):?array {
        $thisFunc = __METHOD__." - ";
        $key = $this->getCacheKey($table, $columns, $where, $join, "Sel");
        if ($this->cache->isHit($key)) {
            // Cache hit, get data //
            $data = $this->cache->get();
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Hit!", $this->logResult ? $data : null);
        } else {
            // Cache miss, load data //
            $args = [$table];
            if (is_array($join) && count($join) > 0) $args[] = $join;
            if (!is_null($columns)) $args[] = $columns;
            if (!is_null($where)) $args[] = $where;
            if (!is_null($fetchFunc)) $args[] = $fetchFunc;

            $data = call_user_func_array([get_parent_class($this), 'select'], $args);
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Miss! Select Statement: ".$this->last(), $this->logResult ? $data : null);

            // Add data to cache //
            $this->cache->set($data);

            // Set tags //
            $tags = $this->buildTableTags("Sel", $table, $join, $tags);
            $this->cache->tag($tags);

            // Set expire time //
            if (!is_null($expire)) $this->cache->expiresAfter($expire);

            // Save data to cache //
            if ($this->cache->save()) {
                if ($this->enableLog) $this->log->sysLog($thisFunc."Data is successfully saved to cache!");
            } else {
                if ($this->enableLog) $this->log->sysLog($thisFunc."Fail to save data to cache!");
            }
        }
        return $data;
    }

    // Cached get statement //
    public function cachedGet(string $table, mixed $columns, ?array $where = null, ?array $join = null, ?array $tags = null, ?int $expire = null):?array {
        $thisFunc = __METHOD__." - ";
        $key = $this->getCacheKey($table, $columns, $where, $join, "Get");
        if ($this->cache->isHit($key)) {
            // Cache hit, get data //
            $data = $this->cache->get();
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Hit!", $this->logResult ? $data : null);
        } else {
            // Cache miss, load data //
            $args = [$table];
            if (is_array($join) && count($join) > 0) $args[] = $join;
            if (!is_null($columns)) $args[] = $columns;
            if (!is_null($where)) $args[] = $where;

            $data = call_user_func_array([get_parent_class($this), 'get'], $args);
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Miss! Get Statement: ".$this->last(), $this->logResult ? $data : null);

            // Add data to cache //
            $this->cache->set($data);

            // Set tags //
            $tags = $this->buildTableTags("Get", $table, $join, $tags);
            $this->cache->tag($tags);

            // Set expire time //
            if (!is_null($expire)) $this->cache->expiresAfter($expire);

            // Save data to cache //
            if ($this->cache->save()) {    
                if ($this->enableLog) $this->log->sysLog($thisFunc."Data is successfully saved to cache!");
            } else {
                if ($this->enableLog) $this->log->sysLog($thisFunc."Fail to save data to cache!");
            }
        }
        return $data;
    }

    // Delete cache //
    public function delQueryCache() {
        $tags = ["SQLSel", "SQLGet", "SQLCnt", "SQLAvg", "SQLMax", "SQLMin", "SQLSum", "SQLHas"];
        return $this->cache->delItemByTag($tags);
    }
    public function delTableCache(string $table) {
        return $this->cache->delItemByTag(["SQLTbl-".$table]);
    }
    public function delTagCache(string $tag) {
        return $this->cache->delItemByTag($tag);
    }

    // Select statement with log //
    public function select(string $table, mixed $join, mixed $columns = null, mixed $where = null):?array {
        $thisFunc = __METHOD__." - ";
        $result = parent::select($table, $join, $columns, $where);        
        if ($this->enableLog)   $this->log->sysLog($thisFunc."Select Statement: ".$this->last(), (($this->logResult) ? $result : null));
        return $result;
    }

    // Get statement with log //
    public function get(string $table, mixed $join = null, mixed $columns = null, mixed $where = null) {
        $thisFunc = __METHOD__." - ";
        $result = parent::get($table, $join, $columns, $where);
        if ($this->enableLog)   $this->log->sysLog($thisFunc."Get Statement: ".$this->last(), (($this->logResult) ? $result : null));
        return $result;
    }

    // Insert statement with log //
    public function insert(string $table, array $values, ?string $primaryKey = null, ?int &$rowInsert = null, bool $autoClear = true): ?PDOStatement {
        $thisFunc = __METHOD__." - ";
        $result = parent::insert($table, $values, $primaryKey);
        $rowCount = 0;
        if (!is_null($result)) 		$rowCount = $result->rowCount();
		if (!is_null($rowInsert))	$rowInsert = $rowCount;
		// Auto clear cache //
		if ($rowCount > 0 && $autoClear) {
			$this->delTableCache($table);
            $this->log->sysLog($thisFunc."Clear cache for table: ".$table);
		}
		// Log Action //
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Insert Statement: ".$this->last()."\n".$rowCount." row(s) inserted.");
        }
        return $result;
    }

    // Update statement with log //
    public function update(string $table, mixed $data, mixed $where = null, ?int &$rowUpdate = null, bool $autoClear = true): ?PDOStatement {
        $thisFunc = __METHOD__." - ";
        $result = parent::update($table, $data, $where);
        $rowCount = 0;
        if (!is_null($result)) 		$rowCount = $result->rowCount();
		if (!is_null($rowUpdate))	$rowUpdate = $rowCount;
		// Auto clear cache //
		if ($rowCount > 0 && $autoClear) {
			$this->delTableCache($table);
            $this->log->sysLog($thisFunc."Clear cache for table: ".$table);
		}
		// Log Action //
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Update Statement: ".$this->last()."\n".$rowCount." row(s) updated.");
        }
        return $result;
    }

    // Delete statement with log //
    public function delete(string $table, mixed $where, ?int &$rowDelete = null, bool $autoClear = true): ?PDOStatement {
        $thisFunc = __METHOD__." - ";
        $result = parent::delete($table, $where);
        $rowCount = 0;
        if (!is_null($result)) 		$rowCount = $result->rowCount();
		if (!is_null($rowDelete)) 	$rowDelete = $rowCount;
		// Auto clear cache //
		if ($rowCount > 0 && $autoClear) {
			$this->delTableCache($table);
		}
		// Log Action //
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Delete Statement: ".$this->last()."\n".$rowCount." row(s) deleted.");
        }
        return $result;
    }

    // Replace statment with log //
    public function replace(string $table, array $columns, $where = null, ?int &$rowReplace = null, bool $autoClear = true): ?PDOStatement {
        $thisFunc = __METHOD__." - ";
        $result = parent::replace($table, $columns, $where);
        $rowCount = 0;
        if (!is_null($result)) 		$rowCount = $result->rowCount();
		if (!is_null($rowReplace)) 	$rowReplace = $rowCount;
		// Auto clear cache //
		if ($rowCount > 0 && $autoClear) {
			$this->delTableCache($table);
		}
		// Log Action //
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Replace Statement: ".$this->last()."\n".$rowCount." row(s) replaced.");
        }
        return $result;
    }

    // Create table with log //
    public function create(string $table, $columns, $options = null): ?PDOStatement  {
        $thisFunc = __METHOD__." - ";
        $result = parent::create($table, $columns, $options);
        if ($this->enableLog) {
            $this->log->sysLog("Create Table Statement: ".$this->last());
        }
        return $result;
    }

    // Drop table with log //
    public function drop(string $table): ?PDOStatement {
        $thisFunc = __METHOD__." - ";
        $result = parent::drop($table);
		$this->delTableCache($table);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Drop table Statement: ".$this->last());
        }
        return $result;
    }

	public function query(string $statement, array $map = [], ?array &$data = null): ?PDOStatement {
		$thisFunc = __METHOD__." - ";
		$result = parent::query($statement, $map);
		$hasData = !is_null($data);
		if ($hasData && !is_null($result)) {
			$data = $result->fetchAll();
		}
		if ($this->enableLog) {
			$this->log->sysLog($thisFunc."Execute query statment: ".$this->last(), (($this->logResult && $hasData) ? $data : null));
		}
		return $result;
	}

    // Cached value of count, avg, max, min, sum //
    public function cachedCalc(string $type, string $table, mixed $join = null, mixed $columns = null, mixed $where = null, ?array $tags = null, ?int $expire = null): ?int {
        $thisFunc = __METHOD__;
        $typeList = ["count" => "Cnt", "avg" => "Avg", "max" => "Max", "min" => "Min", "sum" => "Sum"];
        if (!isset($typeList[$type])) {
            if ($this->enableLog) $this->log->sysLog($thisFunc." - Invalid value type '$type'!");
            return null;
        }
        $thisFunc .= ">$type - ";

        $key = $this->getCacheKey($table, $columns, $where, $join, $typeList[$type]);
        if ($this->cache->isHit($key)) {
            // Cache hit, get data //
            $data = $this->cache->get();
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Hit!", $this->logResult ? $data : null);
        } else {
            // Cache miss, load data //
            $args = [$table];
            if (is_array($join) && count($join) > 0) $args[] = $join;
            if (!is_null($columns)) $args[] = $columns;
            if (!is_null($where)) $args[] = $where;

            $data = call_user_func_array([get_parent_class($this), $type], $args);
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Miss! Count Statement: ".$this->last(), $this->logResult ? $data : null);

            // Add data to cache //
            $this->cache->set($data);

            // Set tags //
            $tags = $this->buildTableTags($typeList[$type], $table, [], $join);
            $this->cache->tag($tags);

            // Set expire time //
            if (!is_null($expire)) $this->cache->expiresAfter($expire);

            // Save data to cache //
            if ($this->cache->save()) {
                if ($this->enableLog) $this->log->sysLog($thisFunc."Data is successfully saved to cache!");
            } else {
                if ($this->enableLog) $this->log->sysLog($thisFunc."Fail to save data to cache!");
            }
        }

        return $data;
    }

    // Count with log //
    public function count(string $table, mixed $join = null, mixed $column = null, mixed $where = null): ?int {
        $thisFunc = __METHOD__." - ";
        $result = parent::count($table, $join, $column, $where);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Count Statement: ".$this->last()."\ncount=".$result);
        }
        return $result;
    }

    // Avg with log //
    public function avg(string $table, mixed $join, mixed $columns = null, mixed $where = null): ?string {
        $thisFunc = __METHOD__." - ";
        $result = parent::avg($table, $join, $columns, $where);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Avg Statement: ".$this->last()."\navg=".$result);
        }
        return $result;
    }

    // Max with log //
    public function max(string $table, mixed $join, mixed $columns = null, mixed $where = null): ?string {
        $thisFunc = __METHOD__." - ";
        $result = parent::max($table, $join, $columns, $where);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Max Statement: ".$this->last()."\nmax=".$result);
        }
        return $result;
    }

    // Min with log //
    public function min(string $table, mixed $join, mixed $columns = null, mixed $where = null): ?string {
        $thisFunc = __METHOD__." - ";
        $result = parent::min($table, $join, $columns, $where);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Min Statement: ".$this->last()."\nmin=".$result);
        }
        return $result;
    }

    // Sum with log //
    public function sum(string $table, mixed $join, mixed $columns = null, mixed $where = null): ?string {
        $thisFunc = __METHOD__." - ";
        $result = parent::sum($table, $join, $columns, $where);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Sum Statement: ".$this->last()."\nsum=".$result);
        }
        return $result;
    }

    public function cachedHas(string $table, mixed $where = null, mixed $join = null, ?array $tags = null, ?int $expire = null): ?bool {
        $thisFunc = __METHOD__." - ";
        $key = $this->getCacheKey($table, null, $where, $join, "Has");
        if ($this->cache->isHit($key)) {
            // Cache hit, get data //
            $data = $this->cache->get();
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Hit!", $this->logResult ? $data : null);
        } else {
            // Cache miss, load data //
            $args = [$table];
            if (is_array($join) && count($join) > 0) $args[] = $join;
            if (!is_null($where)) $args[] = $where;

            $data = call_user_func_array([get_parent_class($this), "has"], $args);
            if ($this->enableLog) $this->log->sysLog($thisFunc."Cache Miss! Count Statement: ".$this->last(), $this->logResult ? $data : null);

            // Add data to cache //
            $this->cache->set($data);

            // Set tags //
            $tags = $this->buildTableTags('Has', $table, [], $join);
            $this->cache->tag($tags);

            // Set expire time //
            if (!is_null($expire)) $this->cache->expiresAfter($expire);

            // Save data to cache //
            if ($this->cache->save()) {
                if ($this->enableLog) $this->log->sysLog($thisFunc."Data is successfully saved to cache!");
            } else {
                if ($this->enableLog) $this->log->sysLog($thisFunc."Fail to save data to cache!");
            }
        }

        return $data;
    }


    // Has statement with log //
    public function has(string $table, $join, $where = null): bool {
        $thisFunc = __METHOD__." - ";
        $result = parent::has($table, $join, $where);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Has Statement: ".$this->last()."\nhas=".($result ? "true" : "false"));
        }
        return $result; 
    }

   // Rand with log //
   public function rand(string $table, $join = null, $columns = null, $where = null): array  {
        $thisFunc = __METHOD__." - ";
        $result = parent::rand($table, $columns, $where);
        if ($this->enableLog) {
            $this->log->sysLog($thisFunc."Rand Statement: ".$this->last(), (($this->logResult) ? $result : null));
        }
        return $result;
    }

 
}