<?php
//
// Config.php - Configuration class
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
namespace Paheon\MeowBase;

//use Paheon\MeowBase\ClassBase;
use Psr\Log\LogLevel;
use Paheon\MeowBase\ClassBase;

class Config {

    use ClassBase;

    // Properties //
	protected string  $etcPath;
    protected string  $varPath;
    protected string  $file;
    protected string  $docRoot = "";

    // Default Config //
	protected array   $config = [
        "general" => [
            "timeZone" => "Asia/Hong_Kong",
            "sessionName" => "meow",
            "debug" => true
        ],
        "db" => [
            "csv" => [
                "path" => "var/db",
                "prefix" => "meow_",
            ],
            "sql" => [
                "type" => "mysql",
                "database" => "",
                "server" => "localhost",
                "username" => "",
                "password" => "",
                "prefix" => "meow_",
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_general_ci",
                "port" => 3306
            ]
        ],
        "log" => [
            "path" => "var/log",
            "level" => LogLevel::DEBUG,
            "enable" => true,
            "option" => []
        ],
        "cache" => [
            "adapterList" => [
                "files" => [
                    "namespace" => "",
                    "path" => "var/cache"
                ],	
                "memcached" => [
                    "namespace" => "",
                    "servers" => [
                        "main" => [
                            "host" => "localhost",		
                            "port" => 11211,
                            "options" => "",
                            'user' => '',						// Default no user
                            'password' => ''						// Default no password
                        ]
                    ]
                ]
            ],
            "enable" => false,
            "siteID" => "Meow",
            "lifeTime" => 86400,
            "adapter" => "files"
        ],
        "image" => [
            "driver" => "Intervention\Image\Drivers\Gd\Driver", // Intervention\Image\Drivers\Vips\Driver, Intervention\Image\Drivers\Imagick\Driver
            "options" => [
                "autoOrientation" => true,
                "decodeAnimation" => true,
                "driver" => "ffffff",
                "strip" => false,
            ],
        ],
        "sapi" => "unknown",
	];	

    // Constructor //
	public function __construct(array $localSetting = [], ?string $docRoot = null, string $etcPath = "/etc", string $varPath = "/var", string $file = "config.php") {

        // Session stat //
        session_start();
        $this->denyWrite = array_merge($this->denyWrite, [ 'config', 'etcPath', 'varPath', 'file', 'docRoot' ]);

        // cli mode or web mode mode? //
        $this->config['sapi'] = php_sapi_name();    // In cli-mode or fpt/cgi mode?

        if (file_exists("configdir.php")) {
            include_once "configdir.php";
        }
        $this->docRoot = $configDocRoot ?? $docRoot ?? $_SERVER['DOCUMENT_ROOT'];
        $this->etcPath = $configEtcPath ?? $etcPath ?? "/etc";
        $this->varPath = $configVarPath ?? $varPath ?? "/var";
        $this->file    = $configFile ?? $file ?? "config.php";

        if (!is_dir($this->docRoot)) {
            $this->docRoot = getcwd();
        }

        // Set config path //
        $this->config["db"]["csv"]["path"] = $this->docRoot . $this->varPath ."/db";
        $this->config["log"]["path"] = $this->docRoot . $this->varPath . "/log";
        $this->config["cache"]["adapterList"]["files"]["path"] = $this->docRoot . $this->varPath . "/cache";

        // Load config //   
        $config = $this->loadConfig();
        $this->config = array_replace_recursive($this->config, $config, $localSetting);
	}

    // Load config //
    public function loadConfig(?string $path = null, ?string $file = null):array {
        $fileName = ($path ?? $this->docRoot . $this->etcPath) . "/" . ($file ?? $this->file);
        $config = [];
        if (file_exists($fileName)) {
            require $fileName;
            if (isset($getSysConfig) && is_callable($getSysConfig)) {
                $config = $getSysConfig($this->docRoot, $this->etcPath, $this->varPath);
                if (!is_array($config)) {
                    $config = [];
                }
            }
        }        
        return $config;
    }

    // Get config //
    public function getConfigByPath(string $path):mixed {
        $this->lastError = "";
        $keyList = explode("/", $path);
        $config = &$this->config;
        foreach ($keyList as $key) {
            $config = &$config[$key] ?? null;
            if (is_null($config)) {
                $this->lastError = "Config path not found: ".$path;
                $this->throwException($this->lastError, 1);
                break;
            }
        }
        return $config;
    }

    // Set config
    public function setConfigByPath(string $path, mixed $value): void {
        $this->lastError = "";
        $keyList = explode("/", $path);
        $config = &$this->config;
        $cnt = count($keyList);
        $i = 1;
        foreach ($keyList as $key) {
            $prevArray = &$config;
            $config = &$config[$key] ?? null;
            if (is_null($config)) {
                $this->lastError = "Config path not found: ".$path;
                $this->throwException($this->lastError, 1);
                break;
            } else {
                if ($i == $cnt) {
                    $prevArray[$key] = $value;
                }
            }
            $i++;
        }
    }
};	
