<?php
/**
 * MeowBase.php - MeowBase Class
 * 
 * This class is the main class for MeowBase.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase
 * 
 */
namespace Paheon\MeowBase;

use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\Config;
use Paheon\MeowBase\SysLog;
use Paheon\MeowBase\Cache;
use Paheon\MeowBase\CacheDB;
use Paheon\MeowBase\Profiler;

class MeowBase {

    use ClassBase {
        ClassBase::__get as _getBase;
    }

    // Objects //
    protected   ?Profiler        $profiler = null;              // Performance Profiler Object
    protected   ?Config          $config = null;                // Config Object
    protected   ?SysLog          $log = null;                   // System Logger Object
    protected   ?Cache           $cache = null;                 // Cache Object
    protected   ?CacheDB         $db = null;                    // System DB Object (Cached Medoo)

    protected   array            $lazyLoad = [ "db"=>"initCacheDB", "cache"=>"initCache", "log"=>"initLogger"];

    // Config //
    protected   array           $configTree = [];               // Read only, virtural linkup with $config->config

    // Debug //
    protected   bool            $debug = false;                 // Debug Mode

    // Constructor //
    public function __construct(Config $config, bool $preload = true) {

        $this->denyWrite = array_merge($this->denyWrite, [ 'profiler', 'config', 'log', 'cache', 'db', 'image', 'configTree', 'lazyLoad' ]);
        
        // Init Profiler //
        $this->profiler = new Profiler();

        // Get Config //
        $this->config = $config;
        //$this->profiler->record("Config Loaded");
		
        // Set time zone //
        $timeZone = $this->config->config['general']['timeZone'];
        if (!is_null($timeZone))  date_default_timezone_set($timeZone);

        // Init Session //

        // Preload objects (no lazy loading)//
        if ($preload) {
            $this->initLogger();
            $this->initCache();
            $this->initCacheDB();
        }
    }


    // Init Logger //
    private function initLogger():void {
        // Create Logger //
        $this->log = new SysLog($this->config->config['log']['path'], $this->config->config['log']['level'], $this->config->config['log']['option']);
        $this->log->enable = $this->config->config['log']['enable'];
        //$this->profiler->record("Logger loaded");
    }

    // Init Cache //
    private function initCache():void {
        // Create Cache Controller //
        $this->cache = new Cache($this->config->config['cache']);
        //$apt = $this->cache->getAdpater();             // Get to know what adapter is used
        //$this->profiler->record("Cache controller loaded");
    }

    // Init Cache DB //
    private function initCacheDB():void {
        // Set Cached Medoo Database //
        if (is_null($this->cache)) $this->initCache();
        if (is_null($this->log)) $this->initLogger();
        $sqlConfig = $this->config->config['db']['sql'];
        $this->db = new CacheDB($sqlConfig, $this->cache, $this->log);
        $this->db->enableLog = $this->debug;    		// Enable SQL Log
        //$this->db->enableCache(false);        // Disable Cache
        //$this->profiler->record("Cache DB loaded");
    }

    // Set Config Tree //
    private function setConfigTree(array &$configTree):void {
        $this->configTree = &$configTree;
    }

    // Getter for handling lazy loading //
	public function __get(string $prop):mixed {
        if (isset($this->lazyLoad[$prop])) {
            if (is_null($this->$prop)) $this->{$this->lazyLoad[$prop]}();
            return $this->$prop;
        } 
        if ($prop == "configTree") {
            return $this->config->config;
        }
        return $this->_getBase($prop);
    }
        
}
