# MeowBase - lightweight PHP framework for Web and CLI

## Overview

MeowBase is a lightweight PHP framework that provides various functionalities including configuration management, caching, database operations, and performance profiling. It is designed to be simple yet powerful, supporting both CLI and Web environments. MeowBase is the foundation base of Meow Framework, which is a web-based framework for PHP. 

The MeowBase configuration is an array variable which returns from an anonymous function, `$getSysConfig`, and stored in a PHP file, `etc/config.php`. Developers may override the default configuration by `$localSettings`. The profiler records the time used for each process to help developers to find out the performance bottleneck. It also provided a ‘group’ option to record the process time for a group of process. These two features, configuration and profiler, are natively supported by MeowBase and can be used by any Meow Framework components. Other features are optional and can be loaded on demand.

Logging system, `SysLog` class, uses Katzgrau/KLogger package to provide logging service. It supports multiple log levels and file rotation. Details please refer to the [KLogger’s documentation](https://github.com/katzgrau/KLogger).

Cache system, `Cache` class, is based on Symfony Cache component and simplied the cache key building process. It also supports multiple cache adapters and keep the data in memory for faster access. However, only file adapter and memcached adapter are supported now. It may add more adapters in the future. Details please refer to the [Symfony Cache documentation](https://symfony.com/doc/current/components/cache.html).

The database system, `CachedDB`class, is inherited from Medoo and added caching and logging abilities on top of it. All cached functions have prefix, 'cached', such as `cachedSelect()`, `cachedGet()`, `cachedCalc()` and `cachedHas()`, to distinguish original non-cached functions. All original functions from Medoo, like `select()` and `get()`, don’t have cache access ability but they may use logging function to log the query statement and query result. For more details of Medoo, please refer to the [Medoo documentation](https://medoo.in/api/new).

The `DTree` class is a versatile and efficient tree data structure implementation in PHP. It is designed to handle hierarchical data with ease, providing a robust set of features for managing tree nodes. The class supports operations such as adding, replacing, deleting, and sorting nodes, making it suitable for a wide range of applications, from simple data organization to complex hierarchical data management.

## Getting Started
To use MeowBase, first initialize the `Config` object with user-defined configuration file in etc folder. Then pass the `Config` object to `MeowBase` constructor to generate the `MeowBase` object. `MeowBase` will load the configuration and initialize the other components automatically. Developer can used the `MeowBase` object to access all components, such as `config`, `log`, `cache`, `db` and `profiler`.

User may copy or rename the `config-example.php` to `config.php` and modify the configuration in it.

Here is a simple example to show how to use MeowBase:
```php
<?php
use Paheon\MeowBase\Config;
use Paheon\MeowBase\MeowBase;
use Psr\Log\LogLevel;

// Profiler will read this global variable for the application start time, 
//   so it should be run at the beginning of the application
$prgStartTime = microtime(true);    

require(__DIR__.'/vendor/autoload.php');

// Initialize with configuration
$config = new Config();

// Initialize MeowBase with configuration
$meow = new MeowBase($config);

// ---- Program starts here ---- //

// Determine Web or CLI //
$isWeb = $meow->configTree["sapi"] != "cli";             // Determine if it is web environment
$br = $isWeb ? "<br>\n" : "\n";

// Strt to log //
echo "Program started!".$br;
$meow->log->sysLog("Program started!", null, LogLevel::INFO);       // Log a INFO message

// log time zone from config //
$timeZone = $meow->config->getConfigByPath("general/timeZone");         // Get time zone from config
echo "Time zone: ".$timeZone.$br;
$meow->log->sysLog("Current time zone", [ "timeZone" => $timeZone ]);   // Log time zone for debug

// Use cached database system //
echo "Start to read student record\n";
$meow->profiler->record("Query started");                           // Start to record query time
$data = $meow->db->cachedSelect("student", "*", ["id" => 135]);     // Perform query with student id = 135
$meow->log->sysLog("Query result", [ "data" => $data ]);            // Log query result
$meow->profiler->record("Query completed");                         // End to record query time
var_dump($data);                                                    // Show query result

// End of log //
$meow->log->sysLog("Log demo completed!", null, LogLevel::INFO);

// Show profiler report //
echo $meow->profiler->report($isWeb);                               // Show profiler report
```

## Core Components

### 1. Fundamental class
`ClassBase` is a fundamental class that is used as base class for all MeowBase components. It mainly provides the properties access control, mass properties access ability and property name mapping for all MeowBase components.

#### Property Access Control
The class property `$denyRead` and `$denyWrite` are used to control the property access by putting the property name into these arrays. For example, the following statement denied write access to properties, `profiler`, `config`, `log`, `cache`, `db`, `configTree` and `lazyLoad`.
```php
$this->denyWrite = array_merge($this->denyWrite, [ 'profiler', 'config', 'log', 'cache', 'db', 'configTree', 'lazyLoad' ]);
```

#### Property Name Mapping
The property `$varMap` is used to map the property name to another name. For example, the `lastError` property is mapped to `$error` property:
```php
$meow->config->varMap['lastError'] = 'error';
$error = $meow->config->error;      // Same as $error = $meow->config->lastError;
```

#### Property Access
`ClassBase` use PHP magic methods `__get()` and `__set()` to access class properties and control property access. These methods lookup the property name in `$varMap` first, if not found, then lookup the property name directly. If property has 'get' and 'set' method, the method name has `get` and `set` followed by the property name, no matter the property is defined or not, `ClassBase` will call these 'get' and 'set' method to get and set the property value repestively. For example, to get the `siteID` property:
```php
$siteID = $meow->cache->siteID;
```
The above code is equivalent to:
```php
$siteID = $meow->cache->getSiteID();
```
The `getSiteID()` method is defined as:
```php
public function getSiteID():string {
    return $this->config['siteID'];
}
```

#### Mass Property Access
For mass property access, `ClassBase` provides `massGetter()` and `massSetter()` methods to get and set multiple properties in one function call.
```php
$propList = $meow->massGetter([ 'email', 'name', 'subject', 'message' ]);
$unsetList = $meow->massSetter([ 'email' => 'info@email.com', 'name' => 'Vincent Leung', 'subject' => 'Enquire for MeowBase', 'message' => 'Hello, is MeowBase good for your project?' ]);
```
The `massGetter()` method will return the list of properties value which are defined in the class; and the `massSetter()` method will return the list of properties that are not defined in the class.

For array property access, `ClassBase` provides the `getElemByPath()` and `setElemByPath()` methods to get and set the element of an array property by path string. For example, following code is used to get the `timeZone` element from `$configTree` array property:
```php
$timeZone = $meow->getElemByPath('configTree', 'general/timeZone');
```
The above code is equivalent to:
```php
$timeZone = $meow->configTree['general']['timeZone'];
```

#### Boolean Evaluation
The `isTrue()` method is used to check if a value is considered true. It is often used to check the return value of a method that may return null.
```php
if ($meow->isTrue($result)) {
    // Do something
}
```
`isTrue()` return true if `$result` is one of the following:
- Boolean: true
- Integer: Non-zero value
- Float: Non-zero value
- String: begin with "y", "t", "e", "a", ("o" and not followed by "f"), or is "0" (case insensitive), so "yes", "Yup", "true", "enable", "allow", "on" and "0" will return true but "no", "Nope", "false", "disable", "disallow", "off" will return false.
Other values will return false.
If `$result` is array, object, null and callable, this function will return false.

**Properties:**
- `$denyRead`: List of properties which denied for reading
- `$denyWrite`: List of properties which denied for writing
- `$varMap`: List of property name for mapping
- `$lastError`: Last error message

**Private Methods:**
- `_getProperty(string $prop, string $elem = ""): mixed`: Internal property getter
- `_setProperty(string $prop, mixed $value): void`: Internal property setter

**Public Methods:**
- `__get(string $prop): mixed`: Magic method for property access
- `__set(string $prop, mixed $value): void`: Magic method for property assignment
- `massGetter(array $propList): mixed`: Get multiple properties
- `massSetter(array $propList): array`: Set multiple properties
- `getElemByPath(string $prop, string $path = ""): mixed`: Get array property element by path
- `setElemByPath(string $prop, string $path, mixed $value): void`: Set array property element by path
- `isTrue(mixed $value, mixed $matchValue = null): bool`: Evaluate if value is considered to be true

### 2. Configuration Management

`Config` class handles application configuration with a PHP file, `etc/config.php`. The path of the config file is composed of three parts: `$docRoot`, `$etcPath` and `$file`. `$docRoot` is the document root of the application; `$etcPath` is the path of the etc folder; `$file` is the name of the config file. By default, `$docRoot` is the document path of website for Web and current working directory for CLI; `$etcPath` is `/etc`; `$file` is `config.php`. 

#### Path Setting
If user needs to execute the application from a different path, it may set the `$docRoot` property in the constructor of config class by passing the path to the second parameter of the constructor. For example:
```php
$config = new Config($localSetting, "/var/www/vhost/meow");
```
If there is no `$localSetting` provided, leave the $localsetting to be a empty array.
```php
$config = new Config([], "/var/www/vhost/meow");
```

#### Configuration Override
The configuration value may be overridden by `$localSetting` array by passing the array to the constructor of `Config` class. For example:
```php
$localSetting = [
    "general" => [
        "sessionName" => "meowTest",
    ],
    "log" => [
        "path" => "/var/log/myapp",
    ],
];  // override default config
$config = new Config($localSetting);
```

#### Change configuration
Developers may add their own configuration as array element to the return value of `$getSysConfig` anonymous function in configuration file, config.php. For example:
```php
// User defined setting //
"mySetting" => [
    "mySetting1" => "MySetting1",
    "mySetting2" => "MySetting2",
],
```

#### Access configuration
The configuration value can be accessed by path notation via `getConfigByPath()` method and directly access the `configTree` array in `MeowBase` object:
```php
// System setting //
$timeZone   = $meow->config->getConfigByPath("general/timeZone");
$logPath    = $meow->configTree['log']['path'];  // Alternative way to access config value

// User defined setting //
$mySetting1 = $meow->config->getConfigByPath("mySetting/mySetting1");
$mySetting2 = $meow->configTree['mySetting']['mySetting2'];
```
It allows to change the configuration value by `setConfigByPath()` method:
```php
$meow->config->setConfigByPath("mySetting/mySetting1", "NewValue1");
```
However, the `configTree` array is read only and any change to it will be ignored.

**Properties:**
- `$etcPath`: Path to etc directory
- `$varPath`: Path to var directory
- `$file`: Configuration file name
- `$docRoot`: Document root path
- `$config`: Configuration data array

**Public Methods:**
- `__construct(array $localSetting = [], ?string $docRoot = null, string $etcPath = "/etc", string $varPath = "/var", string $file = "config.php")`: Initializes configuration
- `loadConfig(?string $path = null, ?string $file = null): array`: Loads configuration from file
- `getConfigByPath(string $path): mixed`: Retrieves configuration by path
- `setConfigByPath(string $path, mixed $value): void`: Sets configuration by path

### 3. Profiler
The `Profiler` is a simple but powerful tool to record the time used for each process to help developers to find out the performance bottleneck. It provdes group function to record the process time for a group of process. There are two member functions in `Profiler` class:
- `record()`: Record the time used for a process
- `report()`: Generate the performance report

#### Application Start Time
When web server receive request or php start running in CLI mode, time is recorded and stored in `$_SERVER["REQUEST_TIME_FLOAT"]`. 
Profiler labelled this time as 'Request Init'. Then 'Application Init' is recorded when the first statment `$prgStartTime = microtime();` executed. For best practice, the first statement of the application should be `$prgStartTime = microtime(true);`. It helps to collect the correct application start time and increase the accuracy of the profiler report.

If both 'Application Init' and 'Request Init' are not available, the 'Profiler Init' time will be recorded as application start time when constructor of `Profiler` object is called.

#### Record Process Time and Generate Report
To record the time used for a process, call `record()` method with two parameters:
- `$tag`: The tag name of the process
- `$group`: The group name of the process, the default value is 'all'

All recorded process time will be accumulated in 'all' group. Developers may change the group by passing the group name to the second parameter of `record()` method.

For example, to record the time used for database preparation:
```php
// Record the time used for Process //
$meow->profiler->record("Process Start");    // Default group is "All" //
... Do something ...
$meow->profiler->record("Process End");     

// Record the time used for DB Test //
$meow->profiler->record("DB Test Start", "DB Test");
... DB Test part 1 ...
$meow->profiler->record("DB Test Part 1", "DB Test");
... DB test part 2 ...
$meow->profiler->record("DB Test Part 2", "DB Test");

// Show the performance report //
echo $meow->profiler->report(true);
```
The output will be like:
```
Performance Report:
-------------------
Group: all
00001 Request Init: time=1,733,207,647.3410s duration=0.0000s ratio=0.00%
00002 Application Init: time=1,733,207,647.3418s duration=0.0008s ratio=9.64%
00003 Process Start: time=1,733,207,647.3434s duration=0.0016s ratio=19.28%
00004 Process End: time=1,733,207,647.3456s duration=0.0022s ratio=26.51%
00005 DB Test Start: time=1,733,207,647.3478s duration=0.0001s ratio=0.12%
00006 DB Test Part 1: time=1,733,207,647.3509s duration=0.0031s ratio=39.40%
00007 DB Test Part 2: time=1,733,207,647.3514s duration=0.0005s ratio=6.35%
Total duration=0.0083s

Group: DB Test
00004 DB Test Start: time=1,733,207,647.3478s duration=0.0000s ratio=0.00%
00005 DB Test Part 1: time=1,733,207,647.3509s duration=0.0031s ratio=83.78%
00006 DB Test Part 2: time=1,733,207,647.3514s duration=0.0005s ratio=16.22%
Total duration=0.0037s
```
The `report()` method will generate the above performance report. The `$nlbr` parameter force the function to add line break with `<br>` html tag for web application. 

**Properties:**
- `$serial`: Serial number for timing records
- `$timeRec`: Array for storing timing records
- `$zeroPad`: Number of zero padding for serial numbers

**Private Methods:**
- `timeCmp($a, $b): int`: Internal comparison function for sorting timing records

**Public Methods:**
- `__construct(int $zeroPad = 5)`: Initializes profiler
- `record(string $tag, string $group = 'all', ?string $forceTime = null)`: Records a timing point
- `report(bool $nlbr = false): string`: Generates performance report

### 4. Log System
Logging system, `sysLog` class, is a extension of Katzgrau/KLogger package, so it support log message formatting, multiple log level and file rotation. `sysLog` class added two more functions, enable property (on/off switch) and tracking stack ability, to provide more flexible logging service.

For more details, like formatting, log level and file rotation, please refer to the [KLogger documentation](https://github.com/katzgrau/KLogger). 

#### Log Message
The `sysLog()` method is used to log message with tracking stack and it has 3 parameters:
- `$message`: The message to be logged
- `$data`: The data to be logged, it can be an array or null value
- `$level`: The log level, default is `LogLevel::DEBUG`

For example:
```php
// Debug message //
$meow->log->sysLog("Current time zone", [ "timeZone" => $meow->config->getConfigByPath("general/timeZone") ]);
// Error message //
$meow->log->sysLog("Error occured!", [ "error code" => 123 ], LogLevel::ERROR);
```
The output message will be like:
```
[2024-12-03 12:09:04.667884] [debug] Called from Line: 150 Function: sysLog (File: /var/www/clients/meow/web/test.php)
Current time zone
    timeZone: 'Asia/Hong_Kong'
[2024-12-03 12:09:04.668260] [error] Called from Line: 152 Function: sysLog (File: /var/www/clients/meow/web/test.php)
Error occured!
    error code: 123
```

#### Stack Tracking and Depth Control
The `stack` and `depth` properties are used to control stack tracking. The default value of `stack` is false, so no calling process information will be shown. If set `stack` to true, the calling process information will be shown. `depth` is used to set the stack depth, the default value is 0, so all calling process information will be shown. If set `depth` to a positive integer, the calling process information will be limited by the specified depth level.
Here is an example to show how to use stack tracking:
```php
function logFunc2(MeowBase $meow, string $data, string $br) {
    $data .= " -> logFunc2";
    echo "logFunc2 Called : data = ".$data.$br;
    logFunc1($meow, $data, $br);
    $meow->log->sysLog("logFunc2 -> Test Log function", [ "data" => $data ], LogLevel::INFO);
}
function logFunc1(MeowBase $meow, string $data, string $br) {
    $data .= " -> logFunc1";
    echo "logFunc1 Called : data = ".$data.$br;
    $meow->log->sysLog("logFunc1 -> Test Log function", [ "data" => $data ], LogLevel::DEBUG);
}
$isWeb = $meow->configTree["sapi"] != "cli";
$br = $isWeb ? "<br>\n" : "\n";
$meow->log->stack = true;                       // Enable stack tracking to show full calling process
logFunc2($meow, "Call stack enabled!", $br);    
$meow->log->stack = false;                      // Disable stack tracking to hide calling process
logFunc2($meow, "Call stack disabled!", $br);   
```
The performance report of the above code will be like:

```
[2024-12-03 12:09:04.668386] [debug] Called from Line: 140 Function: sysLog (File: /var/www/clients/meow/web/test.php)
#1 line: 134 Function: logFunc1 (File: /var/www/clients/meow/web/test.php)
#2 line: 157 Function: logFunc2 (File: /var/www/clients/meow/web/test.php)
logFunc1 -> Test Log function
    data: 'Call stack enabled! -> logFunc2 -> logFunc1'
[2024-12-03 12:09:04.668445] [info] Called from Line: 135 Function: sysLog (File: /var/www/clients/meow/web/test.php)
#1 line: 157 Function: logFunc2 (File: /var/www/clients/meow/web/test.php)
logFunc2 -> Test Log function
    data: 'Call stack enabled! -> logFunc2'
[2024-12-03 12:09:04.668498] [debug] Called from Line: 140 Function: sysLog (File: /var/www/clients/meow/web/test.php)
logFunc1 -> Test Log function
    data: 'Call stack disabled! -> logFunc2 -> logFunc1'
[2024-12-03 12:09:04.668546] [info] Called from Line: 135 Function: sysLog (File: /var/www/clients/meow/web/test.php)
logFunc2 -> Test Log function
    data: 'Call stack disabled! -> logFunc2'
```

#### Enable/Disable Logger
Logger is turned on by default. To turn off logger, simply set the `enable` property to false or change the setting `log/enable` to false in configuration file, config.php. It is very useful when debugging in loop, for example, to avoid logged large number of repeated messages.
```php
// Insert 1000 records //
$meow->db->enableLog = true;         // Enable logging for database operation
for ($i = 1; $i <= 1000; $i++) {
    $paddedNumber = str_pad($i, 5, '0', STR_PAD_LEFT);
    $meow->db->insert("test", [
        "name" => "name-" . $paddedNumber,
        "description" => $paddedNumber . "-Description",
        "value" => rand(1, 1000)
    ]);                              // insert() method will log the SQL statement automatically, so no need to call sysLog() here
    $meow->log->enable = false;      // Prevent log too much insert SQL statement, only log the first one
}
$meow->log->enable = true;           // Enable log again
```

**Properties:**
- `$enable`: Logging enable flag
- `$stack`: Show full stack tracking flag
- `$depth`: Tracking stack depth

**Private Methods:**
- `formatTrace(array $trace): string`: Formats debug backtrace information

**Public Methods:**
- `__construct(string $logDirectory, string $logLevelThreshold = LogLevel::DEBUG, array $options = [])`: Initializes logger
- `sysLog(string $msg, ?array $context = null, string $level = LogLevel::DEBUG): void`: Logs message with tracking information
- `setLogLevel(string $level): void`: Sets logging level
- `getLogLevel(): string`: Returns current log level

### 5. Cache System

Cache system in `MeowBase` is built on top of the Symfony Cache component, details refer to the [Symfony Cache documentation](https://symfony.com/doc/current/components/cache.html), providing a simplified cache key building process and support for multiple cache adapters. The system maintains data in memory for faster access and currently supports two adapters: file adapter and memcached adapter.

#### Cache Configuration
Cache configuration is specified in the config file. Developer may put setup information in `cache` section to specify how to connect to the cache system and select which cache adapter to be used. 

```php
"cache" => [
    "adapterList" => [
        "files" => [
            "path" => "var/cache",
            "namespace" => ""
        ],
        "memcached" => [
            "servers" => [
                "main" => [
                    "host" => "localhost",
                    "port" => 11211
                ]
            ]
        ]
    ]
    "adapter" => "memcached",    // Options: "memcached", "files"
    "enable" => true,            // Enable/disable caching
    "siteID" => "myApp",         // Site identifier for cache tags
    "lifeTime" => 86400,         // Default cache lifetime in seconds
]
```

If the specified adapter fails to initialize, the system will automatically fall back to the file adapter. This ensures that caching functionality remains available even if the preferred adapter is unavailable.

#### Safe Key
The safeKey is a mechanism to ensure that cache key is a valid string and safe for use across different cache adapters. It automatically sanitizes cache keys to prevent issues with different data types and special characters from breaking the cache system. Developers can use the `safeKey()` and `safeTag()` methods to build cache keys. `safeKey()` is used to build a single cache key from provided data and `safeTag()` is used to build a list of cache keys.

#### Cache Lifecycle
The cache lifecycle is as follows:
1. Cache Hit: Simply use `isHit()` member function to determine if a cache item exists. Developers may also use `findItem()` and `findItemBySafeKey()` member functions to get the cache item object in order to know whether cache hit or not.
2. Retrieve Data: When a cache hit occurs, use `get()` member function to retrieve the cached data item.
3. Generate and Save Data: Otherwise, cache miss occurs, generate new data item, set the data item by using `set()` member function, and save it with `save()` member function.
4. Cache Expire and Delete: The data item will be expired automatically, depending on the `lifeTime` setting. It can also be deleted by developer by calling `delItem()` to delete a single item with specified key or `clear()` function, completely clear all cache.

```php
<?php
$key = ['user', ['id' => 135]];           // Key can be string, integer, float, boolean, array or object

if ($meow->cache->isHit($key)) {          // Check cache hit (saveKey is generated in isHit() method)
    $value = $meow->cache->get();         // Retrieve cached value
} else {
    $value = fetchData();                 // Generate new data item
    $meow->cache->set($value);            // Set data item
    $meow->cache->expiresAfter(300);      // Optional: Set expiration to 5 mins (not to use default lifeTime)
    $meow->cache->save();                 // Save data item to cache
}
```
Optionally, developer can defer the save operation by setting the parameter `$defer` to true in `save()` function, and then call `commit()` function to write to the cache later.

**Properties:**
- `$config`: Cache configuration settings
- `$pool`: TagAwareAdapter instance for cache management
- `$item`: Current cache item being handled
- `$adapter`: Selected cache adapter name
- `$lifeTime`: Cache lifetime in seconds
- `$enable`: Cache enable/disable flag

**Public Methods:**
- `__construct(array $cacheConfig = [], string $adapter = "", int $lifeTime = -1)`: Initializes cache system
- `getSiteID(): string`: Returns site identifier
- `getKey(): ?string`: Returns current cache item key
- `getMetadata(): ?array`: Returns current cache item metadata
- `safeKey(mixed $key): ?string`: Generates safe cache keys
- `safeTag(mixed $tag): mixed`: Generates safe cache tags
- `findItem(mixed $key): ?CacheItem`: Gets cache item by key
- `findItemBySafeKey(string $key): ?CacheItem`: Gets cache item by safe key
- `isHit(mixed $key): ?bool`: Checks if cache key exists
- `isHitBySafeKey(string $key): ?bool`: Checks if safe cache key exists
- `get(): mixed`: Retrieves current cached item value
- `set(mixed $value): static`: Sets cache value
- `save(bool $defer = false): bool`: Saves cache item
- `tag(mixed $tags): static`: Adds tags to cache item
- `expiresAfter(mixed $time): static`: Sets expiration time relative to now
- `expiresAt(?\DateTimeInterface $expiration): static`: Sets absolute expiration time
- `commit(): bool`: Commits all deferred cache operations
- `clear(bool $prune = true): void`: Clears cache
- `clearSite(bool $prune = true): ?bool`: Clears site-specific cache
- `delItem(mixed $key = null, bool $prune = false): ?bool`: Deletes specific cache item
- `delItemByTag(mixed $tags, bool $prune = false): ?bool`: Deletes items by tag

### 6. Cached Database

Database system, `cachedDB`, is an enhanced version of Medoo. It is directly inherited from Medoo and added caching and logging abilities on top of it. 

The original functions from Medoo, like `select()` and `get()`, are not cached but may use logging function to log the query and result. Logging function allows to log the executed SQL statment and the result of query. It is controlled by two properties, `enableLog` and `logResult`, in `cachedDB` class. When `enableLog` is true, the executed SQL statment will be logged; if `logResult` is true, the result of query will be logged. These two properties are false by default and can be changed at any time.

Cached function has 'cached' prefix such as `cachedSelect()`, `cachedGet()`, `cachedCalc()` and `cachedHas()`. The cache key is automatically generated from the query parameters and clean up cache automatically if any update on the table. The cache operation is completely transparent to the developer.

For more details of Medoo, please refer to the [Medoo documentation](https://medoo.in/api/new).

#### Usage Example
Here is an example to show the proformance of `CachedDB`, the full program is on `test.php`. The first statment `$meow->db->select()` is same to Medoo's `select()` fuction and it does not access cache. The second statment `$meow->db->cachedSelect()` access the cache but, at that moment, the cache is empty. Therefore, `$meow->db->cachedSelect()` need to build cache key and save data to cache, so the execution time is more that the first statment. The third statment is same as second statment but it directly read the result from cache without execute any query. The execution time is much shorter than the first statment.
```php
// Select records //
$meow->profiler->record("Cached Select Test Start", "DB Cached Select Test");
$data0 = $meow->db->select("test", "*", [
    "value[>=]" => 500
]);
echo "select command readed ".count($data0)." records".$br;
$meow->profiler->record("Select Records where value >= 500 (By select)", "DB Cached Select Test");

// Select records and build cache //
$data1 = $meow->db->cachedSelect("test", "*", [
    "value[>=]" => 500
]);
echo "cachedSelect command readed ".count($data1)." records (first time)".$br;
$meow->profiler->record("Select Records where value >= 500 (By cachedSelect first time)", "DB Cached Select Test");

// Select records again and test cache hit //
$data2 = $meow->db->cachedSelect("test", "*", [
    "value[>=]" => 500
]);
echo "cachedSelect command readed ".count($data2)." records (second time)".$br;
$meow->profiler->record("Select Records where value >= 500 (By cachedSelect second time)", "DB Cached Select Test");

...

echo $meow->profiler->report($isWeb);
```
The profiler report will be like:
```
Group: DB Cached Select Test
00011 Cached Select Test Start: time=1,733,301,320.5318s duration=0.0000s ratio=0.00%
00012 Select Records where value >= 500 (By select): time=1,733,301,320.5337s duration=0.0019s ratio=38.22%
00013 Select Records where value >= 500 (By cachedSelect first time): time=1,733,301,320.5364s duration=0.0027s ratio=52.38%
00014 Select Records where value >= 500 (By cachedSelect second time): time=1,733,301,320.5369s duration=0.0005s ratio=9.40%
Total duration=0.0051s
```

**Properties:**
- `$cache`: Cache object reference
- `$log`: Logger object reference
- `$enableLog`: SQL logging enable flag
- `$logResult`: Enable logging of query results

**Protected Methods:**
- `buildTableTags(string $type, string $table, ?array $join, ?array $tags): array`: Builds cache tags for tables

**Public Methods:**
- `__construct(array &$dbConfig, Cache $cache, SysLog $log)`: Initializes cached database
- `getCacheKey(string $table, mixed $columns, ?array $where = null, ?array $join = null, string $method = "S"): string`: Generates cache key for queries
- `cachedSelect(string $table, mixed $columns, ?array $where = null, ?array $join = null, ?callable $fetchFunc = null, ?array $tags = null, ?int $expire = null): ?array`: Cached version of SELECT query
- `cachedGet(string $table, mixed $columns, ?array $where = null, ?array $join = null, ?array $tags = null, ?int $expire = null): ?array`: Cached version of GET query
- `cachedCalc(string $type, string $table, mixed $join = null, mixed $columns = null, mixed $where = null, ?array $tags = null, ?int $expire = null): ?int`: Cached calculations (count, avg, max, min, sum)
- `cachedHas(string $table, mixed $where = null, mixed $join = null, ?array $tags = null, ?int $expire = null): ?bool`: Cached existence check
- `delQueryCache()`: Clears all query caches
- `delTableCache(string $table)`: Deletes table cache
- `delTagCache(string $tag)`: Deletes cache by tag
*Other properties and methods same to Medoo's functions.*


### 7. DTree

The `DTree` class is a versatile and efficient tree data structure implementation in PHP. It is designed to handle hierarchical data with ease, providing a robust set of features for managing tree nodes. The class supports operations such as adding, replacing, deleting, and sorting nodes, making it suitable for a wide range of applications, from simple data organization to complex hierarchical data management.

#### Main Concepts

The `DTree` class revolves around the concept of node management, where each node can store data and have multiple child nodes. Nodes are identified by unique names within their parent node, allowing for clear and organized data structures. The tree structure facilitates the representation of hierarchical relationships, where each node can have a parent and multiple children, mirroring real-world hierarchical data. This design enables users to model complex data relationships in a straightforward manner.

Each node in the `DTree` has several key properties: `$data`, `$name`, `$parent`, and `$children`. The `$data` property holds the information associated with the node, while `$name` uniquely identifies the node within its parent. The `$parent` property references the node's parent, establishing the hierarchical relationship. The `$children` property is an array that stores the node's child nodes, allowing for dynamic expansion of the tree.

The root node is a special node in the `DTree` that serves as the top-most node in the hierarchy. It does not have a parent and name ($parent is null and $name is empty string) and may have data. It is the starting point for all path-based operations. The `getRoot()` method can be used to retrieve the root node from any node in the tree, ensuring easy access to the top of the hierarchy.

Additionally, the class supports path navigation, allowing nodes to be accessed using path strings similar to file system paths. This feature provides an intuitive way to navigate and manipulate the tree structure, making it easy to locate and manage nodes within the hierarchy. Paths can be absolute, starting from the root, or relative, starting from the current node. For example, you can find a node using a path like `/A/B/C` or `B/C` (relative path) from a specific node:

```php
$node = $tree->findByPath("/A/B/C");
if ($node) {
    echo "Node found: " . $node->name . " with data: " . $node->data;
} else {
    echo "Node not found.";
}
```

#### Features

The `DTree` class offers dynamic node creation, allowing nodes to be added to the tree at any level, providing flexibility in building and modifying the tree structure. Nodes can be added using the `createNode()` method, which creates and adds a new node, or the `addNode()` method, which adds an existing node as a child:

```php
// Create and add a new node
$newNode = $tree->createNode("D", "Data D");

// Add an existing node
$existingNode = new DTree("E", "Data E");
$tree->addNode($existingNode);
```

It includes a comprehensive set of node operations, such as renaming, duplicating, copying, and moving nodes within the tree, which enhances the ability to manage and reorganize data efficiently. Here are some examples of node operations:

```php
// Rename a node
$nodeA->renameNode("A1", "A1-renamed");

// Duplicate a node
$dupNode = $nodeA->dupNode("A1-renamed", null, "A1-dup");

// Copy a node to another parent
$nodeA->copyNode("A1-dup", $nodeB, "A1-copied");

// Move a node to another parent
$nodeA->moveNode("A1-renamed", $nodeB);
```

Serialization is another key feature, where nodes can be serialized with HMAC protection to ensure data integrity during storage or transmission. This ensures that the serialized data remains secure and unaltered. For example, you can serialize and unserialize a tree as follows:

```php
// Serialize the tree and save to a file
$serializedTree = $tree->serialize();
file_put_contents('tree_data.txt', $serializedTree);

// Read from the file and unserialize the tree
$serializedTreeFromFile = file_get_contents('tree_data.txt');
$unserializedTree = $tree->unserialize($serializedTreeFromFile);
if ($unserializedTree) {
    echo "Tree unserialized successfully.";
} else {
    echo "Failed to unserialize tree.";
}
```

The class also supports sorting of child nodes based on their names, either in ascending or descending order, which is useful for maintaining an organized structure:

```php
// Sort children of node B in ascending order
$nodeB->sortNode(true);
echo "Children of node B after ascending sort: " . implode(", ", array_keys($nodeB->children)) . "\n";
// Children of node B after ascending sort: B1, B2, B3

// Sort children of node B in descending order
$nodeB->sortNode(false);
echo "Children of node B after descending sort: " . implode(", ", array_keys($nodeB->children)) . "\n";
// Children of node B after descending sort: B3, B2, B1
```

Furthermore, the path-based access feature allows nodes to be accessed and manipulated using path strings, enabling intuitive navigation through the tree and simplifying the process of locating specific nodes.

#### How to Use

To use the `DTree` class, you start by creating a root node. From there, you can add child nodes, perform various operations on them, and navigate the tree using paths. The `DTreeIterator` class facilitates easy traversal of the tree, allowing you to iterate over all nodes in a structured manner.

#### Code Example

Below is a code example demonstrating how to use the `DTree` and `DTreeIterator` classes:

```php
// Create root node with following structure:
// Root
// |-A
// | |-A1
// | |-A2
// |-B
// | |-B1
// | |-B2
// | | |-B2X
// | | |-B2Y
// | |-B3
// |-C
// | |-C1
// |-D
//   |-D1
//   |-D2
$tree = new DTree();
$tree->data = "Root";

// Create first level nodes
$nodeA = $tree->createNode("A", "Data A");
$nodeB = $tree->createNode("B", "Data B");
$tree->createNode("C", "Data C");

// Create second level nodes under A
$nodeA->createByPath("A1", "Data A1");          // Relative path
$nodeB->createByPath("/A/A2", "Data A2");       // Absolute path

// Create second level nodes under B
$nodeB1 = new DTree("B1", "Data B1", $nodeB);   // Create node and hook to node B
$nodeB2 = new DTree("B2", "Data B2");           // Create node first
$nodeB->AddNode($nodeB2);                       // Hook node B2 to node B by AddNode()
$nodeB->createNode("B3", "Data B3");

// Create third level nodes under B2
$nodeB2->createNode("B2X", "Data B2X");
$nodeB2Y = new DTree("B2Y", "Data B2Y", $nodeB2);

// Create remained nodes by createByArray //
$nodeList = $tree->createByArray([
    "/C/C1" => "Data C1",
    "/D/D1" => "Data D1",
    "D/D2"  => "Data D2",
]);
$nodeC = $nodeList["/C/C1"]->parent;
$nodeD = $nodeList["/D/D1"]->parent;

// Iterate over the tree
$iterator = new DTreeIterator($tree);
foreach ($iterator as $position => $node) {
    echo $node->getPath() . " => " . $node->data . "\n";
}
```

#### Properties

- `$parent`: Reference to the parent node.
- `$data`: Data stored in the node.
- `$name`: Name of the node.
- `$sort`: Boolean indicating if the children should be sorted.
- `$children`: Array of child nodes.
- `$lastError`: Stores the last error message.

#### Methods

- `__construct(string $name = "", mixed $data = null, ?DTree $parent = null, bool $replace = true)`: Initializes a new node.
- `addNode(DTree $child, bool $clone = false, bool $replace = true): bool`: Adds a child node.
- `createNode(string $name, mixed $data = null, bool $replace = true): ?DTree`: Creates and adds a new child node.
- `createByPath(string $path, mixed $data = null, bool $replace = true):?DTree`: Creates and adds a new child node by path.
- `createByArray(array $recList, bool $replace = true):array`: Creates and adds number of new child nodes by array.
- `delNode(string $name): bool`: Deletes a child node.
- `renameNode(string $srcName, string $dstName): bool`: Renames a child node.
- `dupNode(string $srcName, ?DTree $dstNode = null, ?string $dstName = null, bool $clone = false, bool $replace = true): ?DTree`: Duplicates a node.
- `copyNode(string $srcName, DTree $dstNode, ?string $dstName = null, bool $replace = false): ?DTree`: Copies a node to another node.
- `moveNode(string $srcName, DTree $dstNode, bool $replace = false): ?DTree`: Moves a node to another node.
- `serialize(bool $currentNode = false, string $key = 'secret', string $algo = 'sha256'): string`: Serializes the node with HMAC.
- `unserialize(string $serializedData, array $options = [], string $key = 'secret', string $algo = 'sha256'): ?DTree`: Unserializes the node with HMAC verification.
- `sortNode(bool $asc = true, int $sortFlag = SORT_REGULAR): ?DTree`: Sorts the children of the node.
- `getRoot(): DTree`: Returns the root node.
- `getChild(string $name): ?DTree`: Retrieves a child node by name.
- `isRoot(): bool`: Checks if the node is the root node.
- `getPath(): string`: Gets the path from the root to the current node.
- `findByPath(string $path): ?DTree`: Finds a node by its path.
- `findByData(mixed $data, bool $singleResult = false, bool $global = true):array|DTree|null`: Finds single node or multiple nodes by data matching.
- `__toString(): string`: Returns the name of the node.
- `__debugInfo(): array`: Provides debug information about the node.

#### DTreeIterator

The `DTreeIterator` class implements the `Iterator` interface to allow traversal of a `DTree` structure. It provides a simple way to iterate over all nodes in the tree, maintaining the order of traversal. Here is an example of how to use the `DTreeIterator`:

```php
// Iterate over the tree using DTreeIterator
$iterator = new DTreeIterator($tree);
foreach ($iterator as $position => $node) {
    echo "Node at position $position: " . $node->getPath() . " => " . $node->data . "\n";
}
```

**Properties:**

- `$treeRoot`: The root node of the tree.
- `$treeCurr`: The current node in the iteration.
- `$nodeStack`: Stack to manage node traversal.
- `$position`: Current position in the iteration.

**Methods:**

- `__construct(DTree $tree, bool $global = true)`: Initializes the iterator with the root node.
- `current(): mixed`: Returns the current node.
- `key(): mixed`: Returns the current position.
- `next(): void`: Moves to the next node.
- `rewind(): void`: Rewinds the iterator to the start.
- `valid(): bool`: Checks if the current position is valid.

### 8. MeowBase

`MeowBase` is the core class of MeowBase Framework, it is responsible for initializing the system and providing a unified interface for accessing various components. It is a singleton class and can be accessed through the `$meow` variable. Besides `Config` class and `Profiler` class, `MeowBase` does not initialize `Log` class, `Cache` class and `CachedDB` class initially.  These classes are initialized internally on demand. It may help to save resources and improve the performance. If application required initialize `Log` class, `Cache` class and `CachedDB` class in the `MeowBase` constructor, setting `$preload` parameter to true when calling constructor. 

**Properties:**
- `$profiler`: Profiler object for performance tracking
- `$config`: Configuration management object
- `$log`: System logging object
- `$cache`: Cache management object
- `$db`: Database object with caching capabilities
- `$lazyLoad`: Array mapping objects to initialization methods for lazy loading
- `$configTree`: Virtual link to configuration data
- `$debug`: Debug mode flag

**Private Methods:**
- `initLogger(): void`: Initializes logging system internally
- `initCache(): void`: Sets up cache system internally
- `initCacheDB(): void`: Initializes database with caching internally
- `setConfigTree(array &$configTree): void`: Internal method to set configuration tree reference

**Public Methods:**
- `__construct(Config $config, bool $preload = false)`
- `__get(string $prop): mixed`: Magic method for property access and lazy loading

## System Requirements
- PHP: 8.2 or higher
- Required extensions depend on cache adapter and database configuration
- catfan/medoo: 2.1.6 or higher
- katzgrau/klogger: dev-master
- symfony/cache: 6.4.12 or higher

## License
MIT License
