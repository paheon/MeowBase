# MeowBase - lightweight PHP framework for Web and CLI

[![Latest Version](https://img.shields.io/packagist/v/paheon/meowbase.svg)](https://packagist.org/packages/paheon/meowbase)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/paheon/meowbase.svg)](https://packagist.org/packages/paheon/meowbase)

## Overview

MeowBase is a lightweight PHP framework that provides various functionalities including configuration management, caching, database operations, and performance profiling. It is designed to be simple yet powerful, supporting both CLI and Web environments. MeowBase is the foundational base of the Meow Framework, which is a web-based framework for PHP. 

MeowBase introduces two types of classes: Core Classes and Tool Classes. Core Classes are foundation classes for the whole application, creating a single instance for the entire application (though they may create multiple instances in some cases). Tool Classes are used to extend the functionality of the MeowBase framework and can create multiple instances as needed. All tool classes are stored in the Tools sub-directory (Tools namespace).

## Core Classes

Among the Core Classes, `Config` and `Profiler` are mandatory and will be loaded automatically when MeowBase is initialized. Other core classes like `SysLog`, `Cache`, and `CachedDB` are optional and can be loaded on demand. This design allows for better resource management and performance optimization. When these optional classes are needed, they can be initialized either through preloading during MeowBase initialization or lazily when first accessed.

The preload behavior can be controlled by setting the `$preload` parameter to true in the MeowBase constructor. When preloaded, these classes will be initialized immediately and stored in MeowBase's data members. If not preloaded, they will be initialized only when first accessed through the lazy loading mechanism, which calls `initLogger()`, `initCache()`, or `initCacheDB()` as needed.

This flexible loading approach helps optimize resource usage - if your application doesn't need logging, caching, or database functionality, these components won't be loaded, resulting in faster execution and lower memory usage.

The MeowBase configuration is an array variable that is returned from an anonymous function, `$getSysConfig`, and stored in a PHP file, `etc/config.php`. Developers may override the default configuration using `$localSettings`. The profiler records the time used for each process to help developers find performance bottlenecks. It also provides a 'group' option to record the process time for a group of processes. These two features, configuration and profiler, are natively supported by MeowBase and can be used by any Meow Framework components. Other features are optional and can be loaded on demand.

The logging system, `SysLog` class, uses the Katzgrau/KLogger package to provide logging services. It supports multiple log levels and file rotation. For details, please refer to the [KLogger documentation](https://github.com/katzgrau/KLogger).

The cache system, `Cache` class, is based on the Symfony Cache component and simplifies the cache key building process. It also supports multiple cache adapters and keeps data in memory for faster access. However, only the file adapter and memcached adapter are currently supported. More adapters may be added in the future. For details, please refer to the [Symfony Cache documentation](https://symfony.com/doc/current/components/cache.html).

The database system, `CachedDB` class, inherits from Medoo and adds caching and logging capabilities on top of it. All cached functions have the prefix 'cached', such as `cachedSelect()`, `cachedGet()`, `cachedCalc()` and `cachedHas()`, to distinguish them from the original non-cached functions. All original functions from Medoo, like `select()` and `get()`, do not have caching capabilities but they may use the logging function to log query statements and query results. For more details about Medoo, please refer to the [Medoo documentation](https://medoo.in/api/new).

## Tools Classes 

Unlike Core Classes, Tools Classes are designed to be instantiated on demand and can create multiple instances as needed. These classes are stored in the Tools sub-directory and can be instantiated anywhere in your application. Each instantiation creates a new instance, allowing for different configurations and states to be maintained simultaneously.

The `DTree` class is a versatile and efficient tree data structure implementation in PHP. It is designed to handle hierarchical data with ease, providing a robust set of features for managing tree nodes. The class supports operations such as adding, replacing, deleting, and sorting nodes, making it suitable for a wide range of applications, from simple data organization to complex hierarchical data management.

The `Mailer` class provides email functionality through PHPMailer integration, supporting both direct mode and asynchronous mode email sending, with features for handling attachments, embedded images, and HTML content. For PHPMailer details, please refer to [PHPMailer](https://github.com/PHPMailer/PHPMailer).

The `File` class offers file system operations with path management and temporary file handling capabilities.

The `Url` class provides URL manipulation and validation features, including URL building, modification, and information retrieval.

The `Mime` class handles MIME type detection and conversion, supporting file-to-MIME type mapping and icon association. It integrates with the Shared MIME-Info database for accurate MIME type detection.

The `PHP` class provides utility functions for PHP environment checks. All member functions are static functions that may be called directly.

The `CsvDB` class provides an efficient solution for managing CSV files as a database system for small datasets. For large datasets, please consider using a more robust database system. 


## Getting Started

To use MeowBase, first initialize the `Config` object with a user-defined configuration file in the etc folder. Then pass the `Config` object to the `MeowBase` constructor to generate the `MeowBase` object. `MeowBase` will load the configuration and initialize the other components automatically. Developers can use the `MeowBase` object to access all core components, such as `config`, `log`, `cache`, `db` and `profiler`.

Users may copy or rename `config-example.php` to `config.php` and modify the configuration in it.

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

// Start to log //
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

### 1. Core of MeowBase Framework - MeowBase

`MeowBase` class serves as the core component of the MeowBase Framework, managing the initialization and lifecycle of all core components. As a singleton class, it provides a unified interface for accessing various system components while ensuring efficient resource management.

During initialization, `MeowBase` automatically loads the mandatory `Config` and `Profiler` components. The optional components - `SysLog`, `Cache`, and `CachedDB` - can be loaded in two ways: either through preloading during initialization by setting `$preload = true` in the constructor, or through lazy loading when first accessed via the `$meow` object. This flexible loading approach allows applications to optimize resource usage by only loading components when they are actually needed.

The class maintains references to all core components through its properties, with the `$lazyLoad` array managing the initialization of optional components. The `$configTree` property provides a virtual link to the configuration data, while the `$debug` flag controls debug mode functionality.

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
- `__construct(Config $config, bool $preload = false)`: Initializes MeowBase with configuration
- `__get(string $prop): mixed`: Magic method for property access and lazy loading

### 2. Fundamental class - ClassBase
`ClassBase` is a trait that provides property access control, mass property access capability, and property name mapping for all MeowBase components. It is designed to be used as a trait rather than a base class, allowing for multiple inheritance and more flexible code organization.

#### Using ClassBase as a Trait
To use ClassBase in your own classes, simply use the trait in your class definition:

```php
use Paheon\MeowBase\ClassBase;

class MyClass {

    use ClassBase;          // Add ClassBase here 

    protected   array   $sensitiveData;
    protected   array   $readOnlyData;
    
    public function __construct() {
        $this->denyRead = array_merge($this->denyWrite, ['sensitiveData']);
        $this->denyWrite = array_merge($this->denyWrite, ['readOnlyData']); 
        // Initialize your class    
    }
}
```

This approach allows you to:
1. Use ClassBase functionality in any class
2. Combine ClassBase with other traits or classes
3. Maintain better code organization and flexibility

* Since v1.2.1, ClassBase is no longer a fundamental class; it has become a trait for multiple inheritance. 

#### Property Access Control
The trait properties `$denyRead` and `$denyWrite` are used to control property access by putting property names into these arrays. For example, the following statement denies write access to properties `profiler`, `config`, `log`, `cache`, `db`, `configTree` and `lazyLoad`:
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
`ClassBase` uses PHP magic methods `__get()` and `__set()` to access class properties and control property access. These methods look up the property name in `$varMap` first; if not found, they then look up the property name directly. If a property has 'get' and 'set' methods, the method names have `get` and `set` followed by the property name. Regardless of whether the property is defined, `ClassBase` will call these 'get' and 'set' methods to get and set the property value respectively. For example, to get the `siteID` property:
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
The `massGetter()` method will return the list of property values that are defined in the class, and the `massSetter()` method will return the list of properties that are not defined in the class.

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
- Array: Non-empty array

Other values will return false.

If `$result` is an object, null and callable, this function will return false.

#### Exception Handling
The `useException` property controls whether exceptions should be thrown when errors occur. When set to true, errors will be thrown as exceptions; when false, errors will be stored in the `lastError` property. The `exceptionClass` property allows customization of the exception class to be used.

The `throwException()` method is used to handle error conditions consistently across the framework. It either throws an exception or sets the `lastError` property based on the `useException` setting.

Here's an example of how to use exception handling in your code:

```php
// Create a class that extends ClassBase
class MyClass extends ClassBase {
    public function processData($data) {
        if (empty($data)) {
            // This will either throw an exception or set lastError
            $this->throwException("Data cannot be empty", 1001);
            return false;
        }
        return true;
    }
}

// Using the class with exception handling
$obj = new MyClass();

// Method 1: Using exceptions
$obj->useException = true;
try {
    $obj->processData(null);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

// Method 2: Using lastError
$obj->useException = false;
if (!$obj->processData(null)) {
    echo "Error: " . $obj->lastError . "\n";
}

// Method 3: Using custom exception class
$obj->exceptionClass = 'MyCustomException';
$obj->useException = true;
try {
    $obj->processData(null);
} catch (MyCustomException $e) {
    echo "Custom Error: " . $e->getMessage() . "\n";
}
```

**Properties:**
- `$denyRead`: List of properties which denied for reading
- `$denyWrite`: List of properties which denied for writing
- `$varMap`: List of property name for mapping
- `$lastError`: Last error message
- `$useException`: Flag to control exception throwing
- `$exceptionClass`: Custom exception class name

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
- `throwException(string $message, int $code = 0): void`: Handle error conditions consistently

### 3. Configuration Management - Config
`Config` class handles application configuration with a PHP file, `etc/config.php`. The path of the config file is composed of three parts: `$docRoot`, `$etcPath` and `$file`. `$docRoot` is the document root of the application; `$etcPath` is the path of the etc folder; `$file` is the name of the config file. By default, `$docRoot` is the document path of website for Web and current working directory for CLI; `$etcPath` is `/etc`; `$file` is `config.php`.

#### Configuration Directory Setup

The `Config` class automatically checks for a `configdir.php` file in the current directory during initialization. If found, this file is loaded and can define four important variables to customize the configuration paths:

- `$configDocRoot`: Overrides the document root path
- `$configEtcPath`: Overrides the etc directory path
- `$configVarPath`: Overrides the var directory path
- `$configFile`: Overrides the configuration file name

This feature is particularly useful when running scripts outside the document root or when you need to maintain different configuration paths for different environments. Here's an example of a `configdir.php` file:

```php
<?php
// Example configdir.php
$configDocRoot = "/var/www/myapp";     // Custom document root
$configEtcPath = "/var/www/config";    // Custom etc directory
$configVarPath = "/data";              // Custom var directory
$configFile = "myconfig.php";          // Custom config file name
```

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

### 4. Performance turning - Profiler
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

### 5. Log System - SysLog
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

### 6. Cache System - Cache

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

### 7. Cached Database - CachedDB
Database system, `CachedDB`, is an enhanced version of Medoo. It is directly inherited from Medoo and added caching and logging abilities on top of it. 

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


## Tools Components

### 1. Hierarchical Data Handling - DTree and DTreeIterator
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
$newNode = $tree->createNode(['name' => "D", 'data' => "Data D"]);

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
$nodeA = $tree->createNode(['name' => "A", 'data' => "Data A"]);
$nodeB = $tree->createNode(['name' => "B", 'data' => "Data B"]);
$tree->createNode(['name' => "C", 'data' => "Data C"]);

// Create second level nodes under A
$nodeA->createByPath("A1", ['data' => "Data A1"]);          // Relative path
$nodeB->createByPath("/A/A2", ['data' => "Data A2"]);       // Absolute path

// Create second level nodes under B
$nodeB1 = new DTree("B1", "Data B1", $nodeB);   // Create node and hook to node B
$nodeB2 = new DTree("B2", "Data B2");           // Create node first
$nodeB->addNode($nodeB2);                       // Hook node B2 to node B by AddNode()
$nodeB->createNode(['name' => "B3", 'data' => "Data B3"]);

// Create third level nodes under B2
$nodeB2->createNode(['name' => "B2X", 'data' => "Data B2X"]);
$nodeB2Y = new DTree("B2Y", "Data B2Y", $nodeB2);

// Create remained nodes by createByArray //
$nodeList = $tree->loadFromArray([
    "/C/C1" => ['data' => "Data C1"],
    "/D/D1" => ['data' => "Data D1"],
    "D/D2"  => ['data' => "Data D2"],
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
- `buildNode(array $param): ?DTree`: Builds a new node from parameter array without adding to tree.
- `addNode(DTree $child, bool $clone = false, bool $replace = true): bool`: Adds a child node.
- `createNode(array $param): ?DTree`: Creates and adds a new child node.
- `createByPath(string $path, array $param): ?DTree`: Creates and adds a new child node by path.
- `loadFromArray(array $recList): array`: Creates and adds multiple new child nodes from an array.
- `saveToArray(?DTree $startNode = null): array`: Saves the tree structure to an array.
- `delNode(string $name): bool`: Deletes a child node.
- `renameNode(string $srcName, string $dstName, bool $replace = true): bool`: Renames a child node.
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
- `findByData(mixed $data, bool $singleResult = false, bool $global = true): array|DTree|null`: Finds single node or multiple nodes by data matching.
- `__toString(): string`: Returns the name of the node.
- `__debugInfo(): array`: Provides debug information about the node.
- *Parameters of some methods is changed since MeowBase v1.1.2 and these functions are not backward compatible.*

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

### 2. File Operations - File
The `File` class provides path management and temporary file handling capabilities. It is designed to simplify file path handling and provide a consistent interface for file operations.

#### Features
The `File` class offers path management through a home directory concept, allowing for relative path resolution and path normalization. It supports temporary file creation and provides methods for building file paths with variable substitution.

#### Usage Example
```php
// Create a File object with a home directory
$file = new File('/var/www/html');

// Generate file name with by substitution
$fileName = $file->genFile("myfile.txt");
// $fileName = "/var/www/html/myfile.txt"

// Generate file name by substitution
$fileName = $file->genFile("[type]/[name].[ext]", [
    "type" => "documents",
    "name" => "report",
    "ext" => "pdf"
]);
// $fileName = "/var/www/html/documents/report.pdf"

// Create a temporary file (return the resource)
$tempFile = $file->tempFile($filePath);
if ($tempFile !== false) {
    // Use the temporary file
    fwrite($tempFile, "Temporary content");
    fclose($tempFile);
    // $filePath contains the path to the temporary file
}

// Another way to create temporary file (return file name)
$tempFile = $file->genTempFile("", "MyApp_");
if ($tempFile !== false) {
    file_put_contents($tempFile, "Temporary content");
    // $tempFile = "/tmp/MyApp_ce0JDh"
}
```
**Properties:**
- `$home`: Home directory path

**Public Methods:**
- `__construct(?string $home = null)`: Initializes file handler
- `setHome(?string $home = null): void`: Sets home directory
- `setHomeToCurrent(): void`: Sets home to current directory
- `genFile(string $relativePath, array $substituteList = []): string`: Builds file path with variable substitution
- `tempFile(string &$filePath): mixed`: Creates temporary file and returns resource
- `genTempFile(string $path = "", string $prefix = ""): mixed`: Creates temporary file and returns path

### 3. URL Handling - Url
The `Url` class provides URL manipulation and validation features, including URL building, modification, and information retrieval. It supports both relative and absolute URL handling.

#### Features
The `Url` class offers URL building with query parameters and fragments, URL modification capabilities, and real URL information retrieval through cURL. It maintains a home URL for relative path resolution and supports full URL generation.

#### Usage Example
```php
// Create a Url object with a home URL
$url = new Url('https://example.com', true);

// Build a URL with query parameters
$path = $url->genUrl('api/users', ['page' => 1, 'limit' => 10]);
// $path = "https://example.com/api/users?page=1&limit=10"

// Modify an existing URL
$modifiedUrl = $url->modifyUrl('https://example.com/api', [
    'path' => '/api/v2',
    'query' => ['version' => '2.0']
]);
// $modifiedUrl = "https://example.com/api/v2?version=2.0"

// Get URL information
$info = $url->urlInfo('https://example.com/api');
if ($info !== false) {
    echo "HTTP Code: " . $info['http_code']; // The repond code from web server
}
```

**Properties:**
- `$home`: Home base URL
- `$fullUrl`: Flag for full URL generation

**Public Methods:**
- `__construct(?string $home = null, bool $fullUrl = false)`: Initializes URL handler
- `setHome(?string $home = null): void`: Sets home URL
- `genUrl(string $path, array $query = [], string $fragment = "", ?bool $fullUrl = null): string`: Builds URL
- `modifyUrl(string $srcUrl, array $replace): string`: Modifies URL
- `urlInfo(string $url, array $curlOptList = []): array|false`: Gets URL information

### 4. MIME Type Handling - Mime
The `Mime` class handles MIME type detection and conversion, supporting file-to-MIME type mapping and icon association. It integrates with the Shared MIME-Info database for accurate MIME type detection.

#### System Requirements and File Access

The `Mime` class attempts to access three system files for MIME type information:
- `/usr/share/mime/globs2`: Contains file extension to MIME type mappings
- `/usr/share/mime/aliases`: Contains MIME type aliases
- `/usr/share/mime/generic-icons`: Contains icon associations

Some systems may not have these files or may restrict access to them. In such cases, you can provide your own copies of these files in a different location. The `file2Mime()` method will continue to work even if these files are not available, as it uses PHP's built-in MIME type detection as a fallback.

#### Features
The `Mime` class provides MIME type detection from files and extensions, MIME type to icon mapping, and MIME type alias resolution. It supports the Shared MIME-Info database format and can be configured with custom database paths.

#### Usage Example
```php
// Create a Mime object with custom database paths
$mime = new Mime();

// Get MIME type from file
$mimeType = $mime->file2Mime('/path/to/file.jpg');

// Get icon for MIME type
$icon = $mime->mime2Icon('image/jpeg');

// Get MIME type from alias
$mimeType = $mime->alias2Mime('text/plain');
```

**Properties:**
- `$globs2File`: Path to MIME globs2 file
- `$aliasesFile`: Path to MIME aliases file
- `$genericIconsFile`: Path to MIME generic icons file
- `$globs2`: Cached globs2 data
- `$aliases`: Cached aliases data
- `$genericIcons`: Cached generic icons data

**Public Methods:**
- `__construct(string $globs2File = "/usr/share/mime/globs2", string $aliasesFile = "/usr/share/mime/aliases", string $genericIconsFile = "/usr/share/mime/generic-icons")`: Initializes MIME handler
- `file2Mime(mixed $file): string|false`: Gets MIME type from file
- `mime2Icon(string $mime): string|false`: Gets icon for MIME type
- `alias2Mime(string $alias, bool $reverse = false): string|false`: Gets MIME type from alias

### 5. Email Sending - Mailer
The `Mailer` class provides comprehensive email functionality through integration with the [PHPMailer](https://github.com/PHPMailer/PHPMailer) library. It offers a robust solution for sending emails in both direct and asynchronous modes, with extensive support for various email features including HTML content, attachments, and embedded images.

#### Configuration Options
The `Mailer` class supports various configuration options that can be set in the constructor or through the `setConfig()` method:

```php
$mailer->setConfig([
    'host' => 'smtp.example.com',       // SMTP host
    'port' => 587,                      // SMTP port
    'username' => 'user@example.com',   // Login user
    'password' => 'password',           // Login password
    'encryption' => 'tls',              // Encryption type 
    'async' => true,                    // Using async mode
    'autoTLS' => true,                  // Enable automatic TLS encryption
    'keepalive' => true,                // Keep SMTP connection alive
    'timeout' => 30,                    // Connection timeout in seconds
    'debug' => 0                        // Debug level (0-4)
]);
```

#### Sending Modes
The `Mailer` class supports two distinct sending modes to accommodate different use cases. In direct mode, emails are sent immediately when the `send()` method is called, providing immediate feedback on the sending status. This mode is ideal for critical emails that require immediate delivery confirmation but the process will be blocked when sending out mail. 

The asynchronous mode, on the other hand, stores emails in a spool directory as JSON files which allowing for deferred or scheduled processing, by execute the function `sendAsync()`. The process would not be blocked when the email is sent. It is better for quick respond application and handling of high-volume email sending scenarios.

#### Email Sending Process

The process of sending an email involves several steps:

1. **Initialization**: Create a Mailer instance with the appropriate configuration settings. The configuration can include SMTP server details, authentication credentials, and paths for logging and spooling.

2. **Email Composition**: Set up the email content using various methods:
   - Set the sender address using `setFrom()`
   - Add recipients using `setTo()`, `setCC()`, or `setBCC()`
   - Define the email subject with `setSubject()`
   - Create the email body using `setBody()`, supporting both HTML and plain text formats
   - Optionally add attachments or embedded images

3. **Sending the Email**: 
   - In direct mode, call the `send()` method to immediately transmit the email
   - In async mode, the email is stored in the spool directory for later processing
   - The `sendAsync()` method can be used to process queued emails in the spool directory

4. **Error Handling**: The class provides comprehensive error handling through the `lastError` property and exception throwing capabilities, allowing for proper error management in your application.

#### Usage Example

```php
// Create a Mailer object with configuration
$mailer = new Mailer($meow->configTree["mailer"]); 
/* Where $meow->configTree["mailer"] = [
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'user@example.com',
    'password' => 'password',
    'encryption' => 'tls',
    'from' => ['user@example.com' => 'User Name'],
    'logPath' => '/var/log/mailer.log',
    'spoolPath' => '/var/spool/mailer',  // Required for async mode
    'async' => true                      // Enable async mode
]; */

// Set email properties
$mailer->setTo(['recipient@example.com' => 'Recipient Name']);
$mailer->setSubject('Test Email');
$mailer->setBody('<h1>Test</h1><p>This is a test email.</p>', true, 'This is a test email.');
// To and Subject can be set by passing value to virtual properties
// $mailer->to = ['recipient@example.com' => 'Recipient Name'];
// $mailer->subject = 'Test Email';

// Add attachments
$mailer->addAttachment('/path/to/file.pdf', 'document.pdf');
$mailer->addStringAttachment('Content of string attachment', 'string.txt');

// Send email (in async mode, this will store the email in spool)
if ($mailer->send()) {
    echo "Email queued successfully";
} else {
    echo "Failed to queue email: " . $mailer->lastError;
}

// Process async emails (can be called by other separated process)
$results = $mailer->sendAsync();
echo "Processed " . $results['success'] . " emails successfully";
```

**Properties:**
- `$mailer`: PHPMailer instance
- `$logger`: Logger instance
- `$spoolPath`: Path for async email spooling
- `$logPath`: Path for email logging
- `$mode`: Email sending mode
- `$async`: Async mode flag
- `$checkDNS`: DNS check flag
- `$useHTML`: HTML email flag
- `$embeddedImages`: Embedded images information
- `$attachments`: Attachment information
- `$stringAttachments`: String attachment information

**Public Methods:**
- `__construct(array $config = [])`: Initializes mailer
- `reset(bool $keepFrom = false, bool $keepSubject = false, bool $keepBody = false, bool $keepAttachments = false): void`: Resets mailer
- `setConfig(array $setting = []): void`: Sets mailer configuration
- `setMode(string $mode): bool`: Sets sending mode
- `setTo(array $address): void`: Sets To addresses
- `setCC(array $address): void`: Sets CC addresses
- `setBCC(array $address): void`: Sets BCC addresses
- `setReplyTo(array $address): void`: Sets Reply-To addresses
- `setFrom(array $address): void`: Sets From address
- `setSubject(string $subject): void`: Sets email subject
- `setBody(string $body, bool $isHTML = true, string $altBody = ''): void`: Sets email body
- `setAltBody(string $altBody): void`: Sets alternative body
- `addAddress(string $type, array $address, bool $add = true): bool`: Adds email addresses
- `addAttachment(string $path, string $name = '', string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', string $disposition = 'attachment'): bool`: Adds file attachment
- `addStringAttachment(string $string, string $filename, string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', string $disposition = 'attachment'): bool`: Adds string attachment
- `addEmbeddedImage(string $path, string $cid, string $name = '', string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', string $disposition = 'inline'): bool`: Adds embedded image
- `send(): bool`: Sends email
- `sendAsync(): array`: Processes async emails
- `emailValidate(string $email, bool $checkDNS = false): bool`: Validates email address
- `getValidAddr(array|string $address, string $name = "", bool $checkDNS = false): array`: Gets valid email addresses

### 6. PHP Utilities - PHP

The `PHP` class is a support class to provide utility functions for PHP environment checks and configuration. It helps in determining the availability and status of PHP functions and features.

#### Features

The `PHP` class provides comprehensive utility functions for PHP environment checks and configuration. It offers function availability checking, password hashing cost calculation, CLI environment detection, session management for CLI, debugging tools for class inspection, and error display control.

#### Usage Examples

```php
// Check if a function is available
$status = PHP::chkDisabledFunction('exec');
switch ($status) {
    case 0:
        echo "Function is available";
        break;
    case 1:
        echo "Function does not exist";
        break;
    case 2:
        echo "Function exists but is disabled";
        break;
}

// Check if running in CLI mode
if (PHP::isCLI()) {
    echo "Running in CLI mode";
    // Start CLI session
    $sessionID = PHP::startCLISession();
}

// Get optimal password hash cost
$cost = PHP::checkPwdHashCost(0.25);
echo "Recommended cost: $cost";

// Dump class structure for debugging
echo PHP::classDump(MyClass::class);

// Get value type as string
$type = PHP::valueType($myVariable);
```

**Public Static Methods:**
- `chkDisabledFunction(string $funcName): int`: Checks if a function is disabled (returns 0=available, 1=not exists, 2=disabled)
- `checkPwdHashCost(float $timeTarget = 0.350): string`: Calculates optimal password_hash cost for target execution time
- `showPHPError(bool $show = false): void`: Controls PHP error display settings
- `valueType(mixed $value): string`: Returns detailed type information of a value
- `classDump(object|string $class): string`: Generates formatted dump of class structure including properties and methods
- `startCLISession(?string $sessionID = null, ?string $savePath = null): string|false`: Starts session in CLI environment
- `isCLI(): bool`: Checks if running in CLI mode
- `getSessionInfo(): array`: Retrieves current session information

### 7. CSV Database - CsvDB

The `CsvDB` class offers an efficient solution for managing CSV files as a lightweight database system for small datasets. For larger datasets, it is recommended to use a more robust database system. This class is designed to handle CSV files with automatic metadata management, ensuring data integrity and providing search capabilities.

#### Features

The `CsvDB` class significantly elevates the management of CSV files by introducing a set of advanced, thoughtfully designed features. One of its most essential capabilities is automatic metadata management. Upon loading CSV files, the system seamlessly appends and handles three key metadata columns: `csvRowID`, a unique identifier for each row; `csvCreate`, which captures the timestamp of a record's creation; and `csvUpdate`, which logs the timestamp of the most recent update. This not only enhances traceability but also adds valuable structure to otherwise flat data.

Another hallmark of the `CsvDB` class is its efficient queue-based system for handling Create, Update, and Delete (CUD) operations. Instead of writing changes directly to disk with every modification, these operations are queued and executed in batches. This strategic approach minimizes file I/O, reduces strain on system resources, and improves overall performance.

The class also excels in search functionality, enabling users to craft sophisticated queries using a variety of operators and conditional logic. The resulting data set can be sorted in either ascending or descending order.

To safeguard data accuracy and consistency, concurrency control mechanisms are built in. These mechanisms include file locking to prevent race conditions and data corruption when the CSV is accessed by multiple processes at the same time.

Moreover, the `CsvDB` class supports custom search filtering through user-defined functions. This feature allows developers to tailor the behavior of searches according to specific project needs or complex application logic, granting an exceptional level of customization.

Finally, by implementing the Iterator interface, the class offers seamless compatibility with standard PHP iteration constructs. This not only simplifies record traversal but also aligns neatly with familiar programming patterns, making the class both powerful and intuitive for developers.

#### Search Criteria Format

The search functionality supports a rich set of operators and logical conditions:

**Basic Operators:**
- `=` : Equal to
- `!=` : Not equal to
- `>` : Greater than
- `>=` : Greater than or equal to
- `<` : Less than
- `<=` : Less than or equal to
- `~` : Contains (LIKE)
- `!~` : Does not contain (NOT LIKE)
- `<>` : Between
- `><` : Not between

**Logical Operators:**
The system supports complex logical conditions using `AND` and `OR` operators. These can be nested to create sophisticated search criteria:

```php
$results = $csv->search([
    'OR' => [
        "status" => "active",
        'AND' => [
            "age[>]" => 40,
            "email[~]" => "example.com"
        ],
        "csvCreate[<>]" => ["2024-01-01", "2024-12-31"]
    ]
]);
```

In this example:
- The `AND` operator requires all conditions within its array to be true
- The nested `OR` operator allows either condition to be true
- The `<>` operator checks if the value falls between the specified range

#### Usage Example

```php
// Initialize CsvDB with field definitions
$csv = new CsvDB('/path/to/data.csv', [
    "name",
    "age",
    "email",
    "status"
]);

// Add records with automatic metadata
$records = [
    [
        "name" => "John Doe",
        "age" => "30",
        "email" => "john@example.com",
        "status" => "active"
    ],
    [
        "name" => "Jane Smith",
        "age" => "25",
        "email" => "jane@example.com",
        "status" => "inactive"
    ]
];

foreach ($records as $record) {
    $rowID = $csv->setRow($record);
    echo "Added record with rowID: " . $rowID;
}

// Save changes to file
$csv->save();

// Queue operations for batch processing
$csv->queueAppend([
    "name" => "Paul John",
    "age" => "35",
    "email" => "new@example.com",
    "status" => "pending"
]);

$csv->queueUpdate(
    ["email" => "john@example.com"],
    ["status" => "inactive"]
);

$csv->queueDelete(["email" => "jane@example.com"]);

// Execute queued operations
$results = $csv->runQueue();

// Perform complex search
$results = $csv->search([
    'OR' => [
        "age[>]" => 20,
        "status" => "active",
    ],
    'AND' => [
        "email[~]" => "example.com",
        "name[~]" => "John"
    ]
]);

```

#### Properties

- `$csvFile` (string): The path to the CSV file being managed.
- `$header` (array): Array containing the field definitions for the CSV file.
- `$data` (array): Array storing all records with their metadata.
- `$queue` (array): Queue for batch operations (Create, Update, Delete).

#### Protected Methods

- `_loadHeader()`: Internal method to load and validate the CSV file header.
- `_saveHeader()`: Internal method to save the header to the CSV file.
- `_lockFile()`: Internal method to acquire file lock for safe operations.
- `_unlockFile()`: Internal method to release file lock.
- `_validateRow()`: Internal method to validate row data against header definitions.
- `_updateMetadata()`: Internal method to update row metadata (create/update timestamps).

#### Public Methods

**Initialization and Configuration:**
- `__construct(string $csvFile, ?array $header = null)`: Initializes the CSV database with file path and optional header definition
- `setCsvFile(string $fileName): void`: Sets the CSV file path
- `setSeperator(string $seperator): void`: Sets the field separator character (default: comma)
- `setEnclosure(string $enclosure): void`: Sets the field enclosure character (default: double quote)
- `setEscape(string $escape): void`: Sets the escape character (default: backslash)
- `setTerminator(string $terminator): void`: Sets the line terminator (default: newline)
- `createCSVFile(bool $overwrite = false): bool`: Creates a new CSV file with current header

**Header Management:**
- `setHeader(?array $header = null): void`: Sets or updates the CSV header
- `appendHeader(string $label): void`: Adds a new field to the header
- `removeHeader(string $label): void`: Removes a field from the header

**Record Operations:**
- `getRow(int $rowID = 0): array|false`: Retrieves a record by its row ID
- `setRow(array $rowRec, ?int $rowID = null): int`: Adds or updates a record, returns the row ID
- `genEmptyRec(mixed $value = ""): array`: Generates an empty record with all header fields
- `clearRec(): void`: Clears all records from memory

**Queue Operations (Batch Processing):**
- `queueAppend(array $rowRec): void`: Queues a record for addition
- `queueUpdate(array $criteria, array $rowRec): void`: Queues a record for update
- `queueDelete(array $criteria): void`: Queues a record for deletion
- `clearQueue(): void`: Clears the operation queue
- `runQueue(bool $forceUseHeader = false): array`: Executes all queued operations

**Search and Query:**
- `search(array $criteria, ?string $field = null, bool $asc = true): array|false`: Performs search with complex criteria and sorting
- `customSearch(callable $function): array|false`: Performs custom search using user-defined filter function
- `getMin(string $field): mixed`: Gets the minimum value of a field
- `getMax(string $field): mixed`: Gets the maximum value of a field
- `sortByRowID(bool $asc = true): void`: Sorts records by row ID

**File Operations:**
- `load(?string $fileName = null): int`: Loads data from CSV file
- `save(?string $fileName = null): int`: Saves current data to CSV file

**Iterator Interface Methods:**
- `current(): mixed`: Returns the current record in iteration
- `key(): mixed`: Returns the current row ID in iteration
- `next(): void`: Moves to the next record in iteration
- `rewind(): void`: Resets iterator to the first record
- `valid(): bool`: Checks if current iterator position is valid

### 8. User Management System - UserManager and Related Classes

The User Management System in MeowBase provides a comprehensive, flexible solution for managing users, groups, and permissions. At its core is the `UserManager` class, which acts as a centralized management layer that coordinates user authentication, session management, group membership, and permission checking. The system is designed with a flexible architecture that allows developers to inject different storage implementations (CSV or Database) without changing application code, making it easy to switch between storage backends or even use different storage types for different components.

#### Architecture Overview

The User Management System follows a layered architecture pattern that separates concerns and provides maximum flexibility. At the top level, the **UserManager class** serves as the main entry point that provides a unified API for all user management operations. UserManager acts as a coordinator that manages sessions, handles authentication flows, and orchestrates interactions between different components. It provides high-level methods for authentication, authorization, and user data management, shielding application code from the complexities of underlying storage implementations.

Beneath UserManager, there are **parent classes** that define the interface contracts for different aspects of user management. The `User` class serves as the abstract base class for user data access operations, defining methods like `getUserByID()`, `createUser()`, and `updateUser()` that must be implemented by concrete storage classes. Similarly, the `UserGroup` class defines the interface for group management operations such as creating groups, adding users to groups, and querying group membership. The `UserPerm` class establishes the contract for permission management, including methods for setting, getting, and deleting both user-specific and group-based permissions.

The actual data storage is handled by **storage implementation classes** that extend these parent classes. For user data, you can choose between `UserCSV` which stores data in CSV files using the CsvDB class, or `UserDB` which uses database storage through the CacheDB class (which extends Medoo). The same pattern applies to groups and permissions: `UserGroupCSV` and `UserGroupDB` handle group storage, while `UserPermCSV` and `UserPermDB` manage permission storage. Each implementation class inherits the interface from its parent class but provides storage-specific implementations.

A **support class** called `Password` handles all password-related operations including encryption using configurable algorithms (default SHA-256), password strength validation, secure password generation, and password verification. This class is used by UserManager to ensure consistent password handling regardless of which storage implementation is chosen.

This architecture provides developers with tremendous flexibility. You can mix and match storage implementations based on your application's specific needs. You could start with CSV storage during development and prototyping, then seamlessly switch to database storage for production without changing any application code that uses UserManager.

#### How UserManager Works

The `UserManager` class employs dependency injection pattern to accept storage implementations at construction time. When you create a UserManager instance, you must provide a `User` implementation (either UserCSV or UserDB) as the first parameter, which handles all user data storage operations. The second parameter is a configuration array that controls UserManager's behavior such as session settings, login policies, and password encryption options. The third parameter is a `Password` object that handles password encryption, validation, and generation according to your security requirements.

Optionally, you can provide a `UserGroup` implementation as the fourth parameter if your application needs group management functionality. Similarly, a `UserPerm` implementation can be passed as the fifth parameter to enable permission management features. These optional parameters allow you to use UserManager even if you only need basic user authentication without groups or permissions.

Once initialized, UserManager delegates all storage operations to these injected objects while maintaining its own session state in PHP's `$_SESSION` superglobal. It provides a unified API that abstracts away the differences between CSV and database storage, so your application code doesn't need to know or care about the underlying storage mechanism. When you call `createUser()` on UserManager, it internally calls the appropriate method on the injected User object (whether it's UserCSV or UserDB), handles password encryption through the Password object, manages session creation, and returns a consistent result.

This design provides several key benefits. First, you can switch from CSV to Database storage (or vice versa) by simply changing which classes you instantiate and pass to UserManager - all your application code that uses UserManager remains completely unchanged. Second, UserManager maintains a cache of the current user's groups and permissions in memory, reducing the need for repeated storage queries and improving performance. Third, UserManager automatically handles the complex logic of combining user-specific permissions with group-based permissions when checking access rights, ensuring consistent permission evaluation across your application.

#### Features

The User Management System provides comprehensive functionality covering all aspects of user authentication, authorization, and data management. Let's explore each area in detail.

**Authentication & Session Management** forms the foundation of the system. UserManager implements secure login mechanisms with password encryption using SHA-256 algorithm with customizable salt, ensuring that passwords are never stored in plain text. The authentication system is session-based and works seamlessly in both CLI and Web environments, automatically handling the differences between command-line scripts and web applications. One particularly useful feature is the continue login capability, which allows users to restore their previous sessions when they return to the application, making the user experience smooth and seamless. For applications requiring strict security, UserManager supports single login enforcement, ensuring that only one active session exists per user at any given time. Administrators can use the force login option to override existing sessions when necessary, such as when resetting a user's account. Session lifetime is fully configurable, and UserManager automatically handles session timeout, logging out users whose sessions have expired.

**User Management** capabilities are comprehensive and intuitive. You can create, read, update, and delete user accounts through UserManager's unified API. The system includes a flexible field mapping system that allows you to customize column names to match your existing data structures, making it easy to integrate with legacy databases or CSV files. Password strength validation is built-in with configurable rules for minimum length, uppercase letters, lowercase letters, numbers, and special characters. When passwords are created or updated, the system automatically encrypts them according to your configuration, so you never need to manually handle password hashing. UserManager tracks user status including login times, logout times, and last active timestamps, which is invaluable for implementing features like automatic logout after inactivity or displaying online user lists. The system also supports additional custom user fields beyond the standard authentication credentials, allowing you to store any user-related data your application needs.

**Group Management** enables you to organize users into logical groups and manage group membership efficiently. You can create user groups with descriptive names and additional metadata, add users to groups or remove them as needed, and query which users belong to specific groups or which groups a user belongs to. The real power of groups becomes apparent when combined with permissions, as groups enable permission inheritance - users automatically inherit permissions assigned to their groups, reducing the need to set permissions for each individual user.

**Permission Management** provides a flexible and powerful authorization system. Permissions can be set at both the user level and group level, with UserManager automatically combining both when checking access rights. The permission system uses a two-level structure: items (like "articles" or "users") and actions (like "read", "write", "delete"), allowing for fine-grained control over what users can do. Permission values are numeric, enabling you to implement permission levels (for example, read permission might be 1, write permission 2, and admin permission 3). When checking permissions, UserManager first checks user-specific permissions, then checks group permissions, returning true if either source grants the required permission level. This design allows you to grant broad permissions through groups while still allowing individual users to have specific overrides.

**Storage Flexibility** is one of the system's greatest strengths. CSV storage is perfect for simple, file-based data management, ideal for small applications with fewer than a few hundred users, CLI tools, or rapid prototyping where you want to avoid database setup complexity. CSV files are human-readable, making debugging and data inspection straightforward. Database storage, on the other hand, provides excellent performance with large user bases, robust concurrent access handling, transaction support for data integrity, and powerful query capabilities through SQL. The system supports multiple database engines including MySQL, PostgreSQL, SQLite, and SQL Server. The beauty of the architecture is that switching between storage types requires no code changes - you simply instantiate different classes and pass them to UserManager. You can even mix storage types, using CSV for one component and database for another, optimizing your storage choices based on each component's specific requirements.

#### Configuration

The User Management System requires configuration for each component. Here's a complete configuration example:

```php
$config['user'] = [
    // UserManager configuration
    'manager' => [
        'sessionVarName' => 'meow_user',
        'singleLogin' => true,
        'forceLogin' => false,
        'lifeTime' => 3600,
        'password' => [
            'type' => 'encrypted',        // 'encrypted' or 'plain'
            'algorithm' => 'sha256',
            'salt' => 'your-secret-salt',
            'minLength' => 8,
            'maxLength' => 20,
            'minUppercase' => 1,
            'minLowercase' => 1,
            'minNumber' => 1,
            'minSpecial' => 1,
        ],
    ],
    
    // User storage configuration
    'user' => [
        'userTable' => 'user',
        'userFields' => [
            'userID' => 'user_id',
            'userName' => 'user_name',
            'loginName' => 'login_name',
            'password' => 'password',
            'email' => 'email',
            'status' => 'status',
            'loginTime' => 'login_time',
            'logoutTime' => 'logout_time',
            'lastActive' => 'last_active',
            'sessionID' => 'session_id',
        ],
        'csvDB' => [                       // For CSV storage
            'path' => '/path/to/csv/files',
        ],
    ],
    
    // UserGroup storage configuration
    'userGroup' => [
        'userGroupTable' => 'users_groups',
        'userGroupLinkTable' => 'users_groups_link',
        'userGroupFields' => [
            'groupID' => 'group_id',
            'groupName' => 'group_name',
            'groupDesc' => 'group_desc',
        ],
        'userGroupLinkFields' => [
            'userID' => 'user_id',
            'groupID' => 'group_id',
        ],
        'csvDB' => [                       // For CSV storage
            'path' => '/path/to/csv/files',
        ],
    ],
    
    // UserPerm storage configuration
    'userPerm' => [
        'userPermTable' => 'users_perm',
        'userGroupPermTable' => 'users_groups_perm',
        'userPermFields' => [
            'userID' => 'user_id',
            'item' => 'item',
            'permission' => 'permission',
            'value' => 'value',
        ],
        'userGroupPermFields' => [
            'groupID' => 'group_id',
            'item' => 'item',
            'permission' => 'permission',
            'value' => 'value',
        ],
        'csvDB' => [                       // For CSV storage
            'path' => '/path/to/csv/files',
        ],
    ],
];
```

#### Usage Examples

Understanding how to use the User Management System effectively requires seeing it in action. Let's walk through practical examples that demonstrate common use cases and best practices.

**Initializing UserManager with CSV Storage**

When starting a new project or building a small application, CSV storage provides the quickest path to getting user management working. The following example from `test.php` demonstrates how to initialize UserManager with CSV storage for all components. This setup is ideal for development, testing, or small applications where you want to avoid database setup.

First, you create a Password object that will be shared across all storage implementations. This ensures consistent password handling whether you're using CSV or database storage. The Password object reads its configuration from your config file, including encryption algorithm, salt, and password strength requirements.

Next, you instantiate the storage classes for users, groups, and permissions, all using CSV storage. Each class reads its specific configuration from the config tree, including table names, field mappings, and CSV file paths. Finally, you create the UserManager instance, passing in all these components along with the UserManager-specific configuration that controls session behavior and login policies.

```php
use Paheon\MeowBase\Tools\UserManager;
use Paheon\MeowBase\Tools\UserCSV;
use Paheon\MeowBase\Tools\UserGroupCSV;
use Paheon\MeowBase\Tools\UserPermCSV;
use Paheon\MeowBase\Tools\Password;

// Initialize Password (shared between different storage types)
$password = new Password($meow->configTree['user']['manager']['password'] ?? []);

// Initialize User, UserGroup, UserPerm with CSV storage
$userManagerUserCSV = new UserCSV($meow->configTree['user']['user'] ?? []);
$userManagerGroupCSV = new UserGroupCSV($meow->configTree['user']['userGroup'] ?? []);
$userManagerPermCSV = new UserPermCSV($meow->configTree['user']['userPerm'] ?? []);

// Initialize UserManager with CSV components
$userManagerConfig = $meow->configTree['user']['manager'] ?? [];
$userManagerCSV = new UserManager($userManagerUserCSV, $userManagerConfig, $password, $userManagerGroupCSV, $userManagerPermCSV);
```

**Initializing UserManager with Database Storage**

When your application grows or you need better performance and scalability, switching to database storage is straightforward. The process is nearly identical to CSV initialization - you simply change which classes you instantiate. Notice how the Password object remains the same, and the UserManager configuration is identical. This demonstrates the power of the dependency injection pattern: your application logic doesn't need to change at all.

The key difference is that database storage classes require a database connection object (typically `$meow->db` which is a CacheDB instance) as their first parameter. This connection is used for all database operations, and the CacheDB layer provides automatic query caching to improve performance. Once initialized, UserManager works exactly the same way regardless of whether it's using CSV or database storage.

```php
use Paheon\MeowBase\Tools\UserManager;
use Paheon\MeowBase\Tools\UserDB;
use Paheon\MeowBase\Tools\UserGroupDB;
use Paheon\MeowBase\Tools\UserPermDB;
use Paheon\MeowBase\Tools\Password;

// Initialize Password (same as CSV)
$password = new Password($meow->configTree['user']['manager']['password'] ?? []);

// Initialize User, UserGroup, UserPerm with Database storage
$userDB = new UserDB($meow->db, $meow->configTree['user']['user'] ?? []);
$userGroupDB = new UserGroupDB($meow->db, $meow->configTree['user']['userGroup'] ?? []);
$userPermDB = new UserPermDB($meow->db, $meow->configTree['user']['userPerm'] ?? []);

// Initialize UserManager with Database components
$userManagerConfig = $meow->configTree['user']['manager'] ?? [];
$userManagerDB = new UserManager($userDB, $userManagerConfig, $password, $userGroupDB, $userPermDB);
```

**Practical Example: User Registration Flow**

In a real web application, user registration typically involves creating a user account, sending a confirmation email, and perhaps assigning default permissions. Here's how you would implement this using UserManager:

```php
// User registration example
function registerNewUser($userManager, $loginName, $password, $email, $userName) {
    // Prepare user data
    $userData = [
        'userName' => $userName,
        'email' => $email,
    ];
    
    // Create the user account
    $userID = $userManager->createUser($loginName, $password, $userData);
    
    if ($userID > 0) {
        // User created successfully
        // You might want to assign them to a default group
        $defaultGroup = $userManager->getUserGroupByName('members');
        if ($defaultGroup) {
            $userManager->addUserToGroup($userID, $defaultGroup['groupID']);
        }
        
        // Set default permissions for new users
        $userManager->setUserPermission($userID, "profile", "read", 1);
        $userManager->setUserPermission($userID, "profile", "write", 1);
        
        return $userID;
    } else {
        // Registration failed
        error_log("User registration failed: " . $userManager->lastError);
        return false;
    }
}
```

**Practical Example: Login and Session Management**

Handling user login in a web application requires checking credentials, creating a session, and potentially redirecting the user. Here's a complete example:

```php
// Login handler example
function handleUserLogin($userManager, $loginName, $password) {
    // Attempt login
    $loginResult = $userManager->login($loginName, $password);
    
    if ($loginResult) {
        // Login successful
        $userID = $userManager->resolveUserID();
        $user = $userManager->user;
        
        // Update last login time (optional - UserManager does this automatically)
        $userManager->touchSession();
        
        // You might want to log the login event
        error_log("User logged in: $loginName (ID: $userID)");
        
        // Return success with user information
        return [
            'success' => true,
            'userID' => $userID,
            'userName' => $user['userName'] ?? $loginName,
            'sessionID' => $userManager->getSessionID()
        ];
    } else {
        // Login failed
        return [
            'success' => false,
            'error' => $userManager->lastError
        ];
    }
}

// On subsequent page loads, restore session
function restoreUserSession($userManager) {
    if ($userManager->continueLogin()) {
        // Session restored successfully
        return $userManager->resolveUserID();
    }
    return null;
}
```

**Practical Example: Permission-Based Access Control**

One of the most powerful features of UserManager is its ability to check permissions that combine user-specific and group-based permissions. Here's how you would use this in a content management system:

```php
// Check if user can edit an article
function canUserEditArticle($userManager, $articleID, $userID = null) {
    // Check if user has write permission for articles
    $hasWritePerm = $userManager->checkUserPermission("articles", "write", 0, $userID);
    
    if (!$hasWritePerm) {
        return false;
    }
    
    // You might also check if this is the user's own article
    // (additional business logic beyond permissions)
    $user = $userManager->getUserByID($userID ?? $userManager->resolveUserID());
    // ... additional checks ...
    
    return true;
}

// Use in your application
if ($userManager->isLoggedIn()) {
    $currentUserID = $userManager->resolveUserID();
    
    if (canUserEditArticle($userManager, $articleID, $currentUserID)) {
        // Show edit button
        echo "<a href='edit.php?id=$articleID'>Edit Article</a>";
    }
    
    // Check delete permission (might come from group)
    if ($userManager->checkUserPermission("articles", "delete", 0, $currentUserID)) {
        echo "<a href='delete.php?id=$articleID'>Delete Article</a>";
    }
}
```

**Creating Users via UserManager**

UserManager provides a unified API regardless of storage backend. The following example from `test.php` works identically for both CSV and Database storage:

```php
// Create User via UserManager
$userData = [
    'userName' => "csvmgruser1",
    'email' => "csvmgruser1@test.com",
];
$userID = $userManagerCSV->createUser("csvmgruser1", "CSVPass123!", $userData);
if ($userID > 0) {
    echo "User created successfully with ID: $userID";
} else {
    echo "Failed to create user: " . $userManagerCSV->lastError;
}
```

**User Authentication and Login**

The login process is handled by UserManager, which coordinates with the injected User object:

```php
// Login Tests via UserManager
$loginResult = $userManagerCSV->login("csvmgruser1", "CSVPass123!");
if ($loginResult) {
    echo "User logged in successfully";
    $userID = $userManagerCSV->resolveUserID();
    echo "Logged in User ID: " . ($userID ?? 'N/A');
    echo "Is logged in: " . ($userManagerCSV->isLoggedIn() ? 'Yes' : 'No');
    echo "Current user:";
    var_dump($userManagerCSV->user);
    echo "\$_SESSION[".$userManagerCSV->sessionVarName."]:";
    var_dump($_SESSION[$userManagerCSV->sessionVarName] ?? null);
} else {
    echo "Login failed: " . $userManagerCSV->lastError;
}
```

**Continue Login (Session Restoration)**

UserManager can restore previous sessions, useful for maintaining login state across page reloads:

```php
// Simulate page reload by creating new UserManager instance with same session
$userManagerCSV2 = new UserManager($userCSV, $userManagerConfig, $password, $userManagerGroupCSV, $userManagerPermCSV);
$continueLoginResult = $userManagerCSV2->continueLogin();
if ($continueLoginResult) {
    echo "Continue login after session: Success";
    echo "Is logged in after continueLogin: " . ($userManagerCSV2->isLoggedIn() ? "Yes" : "No");
    $continuedUserID = $userManagerCSV2->resolveUserID();
    echo "Continued login User ID: " . ($continuedUserID ?? 'N/A');
} else {
    echo "Continue login failed: " . $userManagerCSV2->lastError;
}
```

**Group Management via UserManager**

UserManager provides methods for managing groups and group membership:

```php
// Create UserGroup via UserManager
$group1ID = $userManagerCSV->createUserGroup('csvadmin', ['groupDesc' => 'CSV Admin group']);
if ($group1ID > 0) {
    echo "Admin group created via UserManager with ID: $group1ID";
}

// Add User to Group via UserManager
if ($userID > 0 && $group1ID > 0) {
    $addGroupResult = $userManagerCSV->addUserToGroup($userID, $group1ID);
    echo "Add User to Admin group: " . ($addGroupResult ? "Success" : "Failed - " . $userManagerCSV->lastError);
    
    // Get User groups
    $userGroups = $userManagerCSV->getGroupsByUser($userID);
    echo "User groups: ";
    var_dump($userGroups);
}
```

**Permission Management via UserManager**

UserManager handles both user-specific and group-based permissions:

```php
// Set User Permissions via UserManager
if ($userID > 0) {
    $permResult1 = $userManagerCSV->setUserPermission($userID, "articles", "read", 1);
    echo "Set User articles read permission: " . ($permResult1 ? "Success" : "Failed - " . $userManagerCSV->lastError);
    
    $permResult2 = $userManagerCSV->setUserPermission($userID, "articles", "write", 0);
    echo "Set User articles write permission: " . ($permResult2 ? "Success" : "Failed - " . $userManagerCSV->lastError);
}

// Set Group Permissions via UserManager
if ($group1ID > 0) {
    $groupPermResult = $userManagerCSV->setGroupPermission($group1ID, "articles", "delete", 1);
    echo "Set Group articles delete permission: " . ($groupPermResult ? "Success" : "Failed - " . $userManagerCSV->lastError);
}

// Check Permissions via UserManager (combines user and group permissions)
if ($userID > 0) {
    $hasReadPerm = $userManagerCSV->checkUserPermission("articles", "read", 0, $userID);
    echo "User has articles read permission: " . ($hasReadPerm ? "Yes" : "No");
    
    // User inherits delete permission from group even though direct permission is 0
    $hasDeletePerm = $userManagerCSV->checkUserPermission("articles", "delete", 0, $userID);
    echo "User has articles delete permission (from group): " . ($hasDeletePerm ? "Yes" : "No");
    
    // Get permission values (returns array with user permission [0] and group permissions [groupID])
    $permValue = $userManagerCSV->getUserPermissionValue("articles", "read", $userID);
    echo "User articles read permission value: " . print_r($permValue, true);
}
```

**Logout via UserManager**

UserManager handles logout and clears all session data:

```php
// Logout via UserManager
echo "Before logout:";
echo "Is logged in: " . ($userManagerCSV->isLoggedIn() ? "Yes" : "No");
echo "Current user:";
var_dump($userManagerCSV->user);
echo "Current Groups:";
var_dump($userManagerCSV->userGroup);
echo "Current Permissions:";
var_dump($userManagerCSV->userPerm);

$userManagerCSV->logout();

echo "After logout:";
echo "Is logged in: " . ($userManagerCSV->isLoggedIn() ? "Yes" : "No");
echo "Current user:";
var_dump($userManagerCSV->user);
// All user data is cleared
```

#### Parent Classes and Implementation Classes

Understanding the relationship between parent classes and their implementations is crucial for effectively using the User Management System. The architecture follows a pattern where abstract parent classes define interfaces, and concrete implementation classes provide storage-specific functionality.

**User Class Hierarchy**

The `User` class serves as an abstract base class that defines the interface contract for all user data operations. It provides method signatures for essential operations like `getUserByID()`, `createUser()`, `updateUser()`, `delUser()`, and others, but these methods return null or false by default. This design forces concrete implementation classes to provide actual functionality while ensuring a consistent API.

When you create `UserCSV`, it extends the `User` class and implements CSV file-based storage using the `CsvDB` class. All user data operations are translated into CSV file reads and writes, with the CsvDB layer handling file locking, data parsing, and search operations. Similarly, `UserDB` extends `User` but implements database storage using `CacheDB` (which extends Medoo), translating operations into SQL queries that leverage database indexing and transaction support.

Both `UserCSV` and `UserDB` inherit common functionality from the `User` parent class, including session management logic, password handling methods, and field mapping utilities. This inheritance means that whether you're using CSV or database storage, you get the same high-level methods for session operations, password validation, and user status tracking. The only difference is in how the data is actually stored and retrieved.

**UserGroup Class Hierarchy**

The `UserGroup` class follows the same pattern as `User`. It defines the interface for group management operations including `createUserGroup()`, `addUserToGroup()`, `getGroupsByUser()`, `getUsersInGroup()`, and related methods. The parent class provides field mapping utilities and method signatures, but concrete implementations must provide the actual storage logic.

`UserGroupCSV` implements group storage by maintaining two CSV files: one for group definitions (group ID, name, description) and another for user-group relationships (which users belong to which groups). This simple structure makes it easy to inspect and modify group data directly in text editors during development. `UserGroupDB` uses database tables with proper foreign key relationships, enabling efficient queries like "find all users in multiple groups" or "find all groups a user belongs to" through SQL joins.

**UserPerm Class Hierarchy**

Permission management follows the same architectural pattern. The `UserPerm` parent class defines methods for setting, getting, and deleting permissions at both user and group levels. Permissions are organized by items (like "articles" or "users") and actions (like "read", "write", "delete"), with numeric values allowing for permission levels.

`UserPermCSV` stores permissions in CSV files, with separate files for user permissions and group permissions. Each row represents a single permission assignment, making it straightforward to see all permissions at a glance. `UserPermDB` uses database tables with indexes on user/group ID and item columns, enabling fast permission lookups even with thousands of permission records.

**Password Class**

The `Password` class stands apart from the hierarchy pattern as a standalone utility class. It doesn't extend any parent class or have multiple implementations because password operations are storage-agnostic - the same encryption algorithm and validation rules apply regardless of where user data is stored. The Password class handles password hashing using configurable algorithms (default SHA-256 with salt), validates passwords against strength rules you configure, generates secure random passwords that meet your requirements, and verifies passwords against stored hashes. UserManager uses this class internally, so you typically only need to instantiate it once and pass it to UserManager during initialization.

#### Storage Implementation Comparison

Choosing between CSV and database storage depends on your application's specific requirements, scale, and operational constraints. Each approach has distinct advantages and trade-offs that make them suitable for different scenarios.

**CSV Storage (UserCSV, UserGroupCSV, UserPermCSV)** offers simplicity and ease of setup that makes it ideal for certain use cases. The most significant advantage is that no database server is required - you simply specify a directory path where CSV files will be stored, and the system handles the rest. This makes CSV storage perfect for small applications with fewer than a thousand users, CLI tools that need user management without database dependencies, or rapid prototyping where you want to focus on application logic rather than infrastructure setup.

CSV files are human-readable, which means you can open them in any text editor or spreadsheet application to inspect, debug, or manually modify data during development. This transparency is invaluable when troubleshooting issues or understanding how the system stores data. Data migration is also straightforward - you can simply copy CSV files between environments or backup locations without needing database export/import tools.

However, CSV storage has limitations that become apparent as applications grow. Performance degrades noticeably with large datasets because operations may require scanning entire files. Concurrent write operations can be problematic in high-traffic web applications, as file locking mechanisms are less robust than database-level locking. There's no built-in transaction support, so complex multi-step operations can't be rolled back if something fails partway through. Query capabilities are limited compared to SQL - you can't easily perform complex joins or aggregations that might be needed for administrative dashboards or reporting.

**Database Storage (UserDB, UserGroupDB, UserPermDB)** provides enterprise-grade capabilities that scale with your application. Performance remains excellent even with large user bases because databases use indexes to locate records quickly, and query optimization ensures fast response times. The system handles concurrent access robustly through database-level locking mechanisms, allowing multiple web server processes to safely read and write user data simultaneously without corruption risks.

Transaction support ensures data integrity in complex operations. For example, when creating a user account with default group membership and initial permissions, the database can ensure that either all records are created successfully or none at all, preventing partial data states. SQL's powerful query capabilities enable sophisticated operations like finding all users who belong to multiple specific groups, generating reports on user activity patterns, or performing bulk updates efficiently.

The CacheDB layer provides automatic query caching, storing frequently accessed user data in memory and dramatically reducing database load in high-traffic applications. Database storage scales to millions of users while maintaining consistent performance, and supports multiple database engines (MySQL, PostgreSQL, SQLite, SQL Server) so you can choose based on your infrastructure preferences.

The trade-off is increased complexity. Database storage requires a database server to be installed, configured, and maintained. Database credentials must be managed securely, and schema migrations need to be planned when changing data structures. However, for production applications expecting growth or requiring high reliability, this added complexity is typically well worth the benefits. Many teams start with CSV storage during development and prototyping, then migrate to database storage for production, taking advantage of UserManager's architecture that makes this transition seamless.

#### Class Reference

**UserManager Class**

**Properties:**
- `$userObj`: User object instance (UserCSV or UserDB)
- `$userGroupObj`: UserGroup object instance (UserGroupCSV or UserGroupDB)
- `$userPermObj`: UserPerm object instance (UserPermCSV or UserPermDB)
- `$passwordObj`: Password object instance
- `$user`: Current logged-in user data array
- `$userGroup`: Cached user group data array
- `$userGroupLink`: Cached user-group link data array
- `$userPerm`: Cached user permission data array
- `$userPermGroup`: Cached group permission data array
- `$singleLogin`: Boolean flag for single login enforcement
- `$forceLogin`: Boolean flag for force login option
- `$encrypted`: Boolean flag for password encryption
- `$lifeTime`: Session lifetime in seconds
- `$sessionID`: Current session identifier
- `$sessionVarName`: Session variable name
- `$lastError`: Last error message

**Protected Methods:**
- `loadCurrentUserData(int $userID): void`: Loads current user's groups and permissions into cache

**Public Methods:**
- `__construct(User $userObj, array $config = [], ?Password $pwdObj = null, ?UserGroup $userGroupObj = null, ?UserPerm $userPermObj = null)`: Initializes UserManager with injected components
- `login(string $loginName, string $password, ?bool $forceLogin = null): bool`: Authenticates user and creates session
- `logout(): void`: Logs out current user and clears session
- `continueLogin(): bool`: Continues previous login session
- `isLoggedIn(bool $forceLogout = true): bool`: Checks if user is currently logged in
- `resolveUserID(?int $userID = null): ?int`: Resolves user ID (current user or provided ID)
- `touchSession(?int $currTime = null): void`: Updates session activity timestamp
- `getSessionID(): ?string`: Retrieves session ID
- `changePassword(?string $newPassword = null, ?string $oldPassword = null): string|false`: Changes user password with optional validation
- `checkPassword(string $password, string $hash): bool`: Verifies password against hash
- `getPasswordHash(string $password): string`: Generates password hash
- `genPassword(): string`: Generates a secure password following configured rules
- `getUserByID(int $userID, bool $forceLoad = false, bool $checkExists = false): ?array`: Retrieves user data by ID
- `getUserByLoginName(string $loginName, bool $forceLoad = false): ?array`: Retrieves user data by login name
- `updateUserStatus(bool $login, int $lastActiveTime, ?string $sessionID = null, ?int $loginTime = null, ?int $userID = null): bool`: Updates user login status
- `updatePassword(string $passwordHash, ?int $userID = null): bool`: Updates user password
- `createUser(string $loginName, string $password, array $fieldList = []): int`: Creates new user and returns user ID
- `updateUser(array $fieldList, ?int $userID = null): bool`: Updates user information
- `delUser(?int $userID = null): bool`: Deletes user account
- `createUserGroup(string $userGroupName, array $fieldList = []): int`: Creates new user group and returns group ID
- `getUserGroupByID(int $userGroupID, bool $forceLoad = false, bool $checkExists = false): ?array`: Retrieves group data by ID
- `getUserGroupByName(string $userGroupName, bool $forceLoad = false): ?array`: Retrieves group data by name
- `updateUserGroup(array $fieldList, ?int $userGroupID = null): bool`: Updates group information
- `delUserGroup(?int $userGroupID = null): bool`: Deletes group
- `addUserToGroup(int $userID, int $userGroupID): bool`: Adds user to group
- `delUserFromGroup(int $userID, ?int $userGroupID = null): bool`: Removes user from group
- `getUsersInGroup(?int $userGroupID = null, bool $forceLoad = false): ?array`: Gets users in group
- `getGroupsByUser(int $userID, bool $forceLoad = false): ?array`: Gets groups for user
- `getUserPermission(int $userID, string $item): ?array`: Gets user permissions for item
- `setUserPermission(int $userID, string $item, string $permission, int $value): bool`: Sets user permission
- `delUserPermission(int $userID, string $item, ?string $permission = null): bool`: Deletes user permission
- `getGroupPermission(int $userGroupID, string $item, ?int $userID = null): ?array`: Gets group permissions for item
- `setGroupPermission(int $userGroupID, string $item, string $permission, int $value): bool`: Sets group permission
- `delGroupPermission(int $userGroupID, string $item, ?string $permission = null): bool`: Deletes group permission
- `checkUserPermission(string $item, string $permission, string|int $level = 0, ?int $userID = null): bool`: Checks if user has permission (combines user and group permissions)
- `getUserPermissionValue(string $item, string $permission, ?int $userID = null): array`: Gets permission values (user and group permissions)

**User Class (Parent)**

**Properties:**
- `$userTable`: User table name
- `$userFields`: User field mappings array

**Public Methods:**
- `__construct(array $config = [])`: Initializes User with configuration
- `getUserField(string $fieldName, ?string $default = null): string`: Gets mapped field name
- `reset(): void`: Resets user data (dummy method)
- `getUserByID(int $userID, bool $forceLoad = false, bool $checkExists = false): ?array`: Gets user by ID (dummy - must be overridden)
- `getMultiUserByID(array $userID = [], bool $forceLoad = false): ?array`: Gets multiple users by ID (dummy - must be overridden)
- `getUserByLoginName(string $loginName, bool $forceLoad = false): ?array`: Gets user by login name (dummy - must be overridden)
- `createUser(string $loginName, string $userPassword, array $fieldList = []): int`: Creates user (dummy - must be overridden)
- `updateUser(array $fieldList, ?int $userID = null): bool`: Updates user (dummy - must be overridden)
- `delUser(?int $userID = null): bool`: Deletes user (dummy - must be overridden)
- `updatePassword(string $passwordHash, ?int $userID = null): bool`: Updates password (dummy - must be overridden)
- `updateUserStatus(bool $login, int $lastActiveTime, ?string $sessionID = null, ?int $loginTime = null, ?int $userID = null): bool`: Updates user status (dummy - must be overridden)

**UserCSV Class**

**Properties:**
- `$userDB`: CsvDB instance for user data storage
- `$dbLoaded`: Flag indicating whether the CSV database has been loaded
- `$csvPath`: Path to the CSV storage directory
- `$userTableFile`: Full path to user CSV file
- `$config`: User configuration array (inherited from User)
- `$user`: Current user data array (inherited from User)
- `$sessionID`: Current session identifier (inherited from User)
- `$sessionVarName`: Session variable name (inherited from User)
- `$userTable`: User table name (inherited from User)
- `$userFields`: User field mappings (inherited from User)
- `$encrypted`: Password encryption flag (inherited from User)
- `$lastError`: Last error message (inherited from User)

**Protected Methods:**
- `copyUserFromDB(?string $userID = null): bool`: Copies user data from CSV database to current user object

**Public Methods:**
- `__construct(array $config)`: Initializes UserCSV with configuration and sets up CSV database connection
- `loadUserRec(bool $forceLoad = false): bool`: Loads user records from CSV file into memory
- `getField(string $field, ?string $default = null): string`: Gets mapped field name from configuration
- `getUserByID(int $userID, bool $forceLoad = false): ?array`: Retrieves user data by ID
- `getUserByLoginName(string $loginName, bool $forceLoad = false): ?array`: Retrieves user data by login name
- `createUser(string $loginName, string $userPassword, array $fieldList = []): int`: Creates new user and returns user ID
- `updateUser(array $fieldList, ?string $userID = null): bool`: Updates user information
- `delUser(?string $userID = null): bool`: Deletes user account
- `updatePassword(string $password, ?string $userID = null): bool`: Updates user password
- `updateUserStatus(bool $login, int $lastActiveTime, ?int $loginTime = null, ?string $userID = null): bool`: Updates user login status

**Inherited Public Methods from User class:**
- `login(string $loginName, string $password, ?bool $forceLogin = null): bool`: Authenticates user and creates session
- `continueLogin(): bool`: Continues previous login session
- `logout(): void`: Logs out current user and clears session
- `isLoggedIn(bool $forceLogout = false): bool`: Checks if user is currently logged in
- `touchSession(int $currTime): void`: Updates session activity timestamp
- `getSessionID(): ?string`: Retrieves session ID from database
- `validatePassword(string $password): bool`: Validates password against configured rules
- `genPassword(): string`: Generates a secure password following configured rules
- `getPasswordHash(string $password, ?string $algorithm = null): string`: Generates password hash
- `checkPassword(string $password, string $hash): bool`: Verifies password against hash
- `changePassword(?string $newPassword = null, ?string $oldPassword = null): string|false`: Changes user password with optional validation

**UserDB Class**

**Properties:**
- `$userDB`: CacheDB instance for database operations
- `$userTable`: Database table name for user data
- `$dbLoaded`: Flag indicating database load status (always true for database backend)
- `$config`: User configuration array (inherited from User)
- `$user`: Current user data array (inherited from User)
- `$sessionID`: Current session identifier (inherited from User)
- `$sessionVarName`: Session variable name (inherited from User)
- `$userFields`: User field mappings (inherited from User)
- `$encrypted`: Password encryption flag (inherited from User)
- `$lastError`: Last error message (inherited from User)

**Protected Methods:**
- `copyUserFromDB(?string $userID = null): bool`: Copies user data from database to current user object

**Public Methods:**
- `__construct(array $config, CacheDB &$cacheDB)`: Initializes UserDB with configuration and database connection
- `getUserByID(int $userID, bool $forceLoad = false): ?array`: Retrieves user data from database by ID
- `getUserByLoginName(string $loginName, bool $forceLoad = false): ?array`: Retrieves user data from database by login name
- `createUser(string $loginName, string $userPassword, array $fieldList = []): int`: Creates new user in database and returns user ID
- `updateUser(array $fieldList, ?string $userID = null): bool`: Updates user information in database
- `delUser(?string $userID = null): bool`: Deletes user account from database
- `updatePassword(string $password, ?string $userID = null): bool`: Updates user password in database
- `updateUserStatus(bool $login, int $lastActiveTime, ?int $loginTime = null, ?string $userID = null): bool`: Updates user login status in database

**Inherited Public Methods from User class:**
- `login(string $loginName, string $password, ?bool $forceLogin = null): bool`: Authenticates user and creates session
- `continueLogin(): bool`: Continues previous login session from database
- `logout(): void`: Logs out current user and clears session
- `isLoggedIn(bool $forceLogout = false): bool`: Checks if user is currently logged in
- `touchSession(int $currTime): void`: Updates session activity timestamp
- `getSessionID(): ?string`: Retrieves session ID from database
- `getField(string $field, ?string $default = null): string`: Gets mapped field name from configuration
- `validatePassword(string $password): bool`: Validates password against configured rules
- `genPassword(): string`: Generates a secure password following configured rules
- `getPasswordHash(string $password, ?string $algorithm = null): string`: Generates password hash
- `checkPassword(string $password, string $hash): bool`: Verifies password against hash
- `changePassword(?string $newPassword = null, ?string $oldPassword = null): string|false`: Changes user password with optional validation

**UserGroup Class (Parent)**

**Properties:**
- `$userGroupFields`: Group field mappings array
- `$userGroupTable`: Group table name
- `$userGroupLinkTable`: User-group link table name
- `$userGroupLinkFields`: Link field mappings array

**Public Methods:**
- `__construct(array $config = [])`: Initializes UserGroup with configuration
- `getUserGroupField(string $fieldName, ?string $default = null): string`: Gets mapped group field name
- `getUserGroupLinkField(string $fieldName, ?string $default = null): string`: Gets mapped link field name
- `getUserGroupByID(int $userGroupID, bool $forceLoad = false, bool $checkExists = false): ?array`: Gets group by ID (dummy - must be overridden)
- `getUserGroupByName(string $userGroupName, bool $forceLoad = false): ?array`: Gets group by name (dummy - must be overridden)
- `createUserGroup(string $userGroupName, array $fieldList = []): int`: Creates group (dummy - must be overridden)
- `updateUserGroup(array $fieldList, ?int $userGroupID = null): bool`: Updates group (dummy - must be overridden)
- `delUserGroup(?int $userGroupID = null): bool`: Deletes group (dummy - must be overridden)
- `isUserInGroup(int $userID, int $userGroupID, bool $forceLoad = false): bool`: Checks if user is in group (dummy - must be overridden)
- `getUsersInGroup(int $userGroupID, bool $forceLoad = false): ?array`: Gets users in group (dummy - must be overridden)
- `getGroupsByUser(int $userID, bool $forceLoad = false): ?array`: Gets groups for user (dummy - must be overridden)
- `addUserToGroup(int $userID, int $userGroupID): bool`: Adds user to group (dummy - must be overridden)
- `delUserFromGroup(int $userID, ?int $userGroupID = null): bool`: Removes user from group (dummy - must be overridden)

**UserPerm Class (Parent)**

**Properties:**
- `$userPermTable`: User permission table name
- `$userGroupPermTable`: Group permission table name
- `$userPermFields`: User permission field mappings array
- `$userGroupPermFields`: Group permission field mappings array

**Public Methods:**
- `__construct(array $config = [])`: Initializes UserPerm with configuration
- `getUserPermField(string $fieldName, ?string $default = null): string`: Gets mapped user permission field name
- `getUserGroupPermField(string $fieldName, ?string $default = null): string`: Gets mapped group permission field name
- `reset(): void`: Resets permission records (dummy method)
- `getUserPerm(int $userID, string $item): ?array`: Gets user permissions for item (dummy - must be overridden)
- `setUserPerm(int $userID, string $item, string $permission, int $value): bool`: Sets user permission (dummy - must be overridden)
- `delUserPerm(int $userID, string $item, ?string $permission = null): bool`: Deletes user permission (dummy - must be overridden)
- `getGroupPerm(int $userGroupID, string $item): ?array`: Gets group permissions for item (dummy - must be overridden)
- `setGroupPerm(int $userGroupID, string $item, string $permission, int $value): bool`: Sets group permission (dummy - must be overridden)
- `delGroupPerm(int $userGroupID, string $item, ?string $permission = null): bool`: Deletes group permission (dummy - must be overridden)

**Password Class**

**Properties:**
- `$minLength`: Minimum password length
- `$maxLength`: Maximum password length
- `$minUppercase`: Minimum uppercase letters required
- `$minLowercase`: Minimum lowercase letters required
- `$minNumber`: Minimum numbers required
- `$minSpecial`: Minimum special characters required
- `$algorithm`: Hash algorithm (default: 'sha256')
- `$salt`: Salt for password hashing

**Public Methods:**
- `__construct(array $config)`: Initializes Password with configuration
- `setMinLength(int $minLength): void`: Sets minimum password length
- `setMaxLength(int $maxLength): void`: Sets maximum password length
- `setMinUppercase(int $minUppercase): void`: Sets minimum uppercase letters
- `setMinLowercase(int $minLowercase): void`: Sets minimum lowercase letters
- `setMinNumber(int $minNumber): void`: Sets minimum numbers
- `setMinSpecial(int $minSpecial): void`: Sets minimum special characters
- `setAlgorithm(string $algorithm): void`: Sets hash algorithm
- `setSalt(string $salt = ""): void`: Sets salt for hashing
- `getPasswordHash(string $password, ?string $algorithm = null): string`: Generates password hash
- `genPassword(): string`: Generates a secure password following configured rules
- `validatePassword(string $password): bool`: Validates password against configured rules
- `checkPassword(string $password, string $hash): bool`: Verifies password against hash
- `genSalt(int $length = 0): string`: Generates a random salt string

#### Features

The `UserCSV` class provides a complete user management system built on top of the efficient `CsvDB` class. At its core, the class handles authentication through secure login mechanisms that support both plain text and encrypted passwords. The default encryption uses SHA-256 algorithm with customizable salt, ensuring password security while maintaining flexibility for different security requirements. The authentication system is session-based with configurable lifetime, automatically managing sessions for both CLI and Web environments. This means developers don't need to worry about the underlying session handling differences between command-line scripts and web applications.

One of the standout features is the continue login capability, which allows users to seamlessly restore their previous sessions. This is particularly useful for web applications where users might close their browsers and return later, or for CLI scripts that need to maintain authentication across multiple executions. The class also supports single login enforcement, ensuring that only one active session exists per user at any given time, which is critical for applications requiring strict access control. Additionally, a force login option allows administrators to override existing sessions when necessary.

User data management is comprehensive and intuitive. The class supports creating new user accounts with customizable fields beyond the standard authentication credentials, allowing applications to store additional user information such as display names, email addresses, and any custom data needed. User information can be retrieved efficiently either by unique user ID or by login name, with the underlying CsvDB providing fast search capabilities. Updating user records is flexible – the system can update the currently logged-in user's information, or administrators can update any user by specifying their ID. When users are deleted, the system ensures proper cleanup of associated sessions and data.

Password management is robust and user-friendly. The class automatically handles password encryption when the encrypted mode is enabled, and includes built-in password strength validation. Developers can configure minimum requirements for password length, uppercase letters, lowercase letters, numbers, and special characters. When passwords are updated, the system automatically re-encrypts them, maintaining security without requiring additional code.

Session and status tracking provides valuable insights into user behavior. The class automatically tracks login and logout times as Unix timestamps (integer values), monitors the last active time for detecting idle sessions, associates each login with a unique session ID for security, and maintains user status flags to quickly determine if a user is currently logged in or logged out. This information is crucial for implementing features like automatic logout after inactivity or displaying online user lists.

The flexible field mapping system is one of the class's most powerful features. Instead of forcing developers to use predefined column names in their CSV files, the system allows complete customization through configuration. Default mappings include `userID` for unique user identification, `userName` for display names, `loginName` for authentication credentials, `password` for encrypted or plain passwords, `email` for email addresses, `status` for tracking login/logout state, `loginTime` and `logoutTime` for session timestamps, `lastActive` for activity monitoring, and `sessionID` for session association. Developers can override any of these mappings to match their existing data structures, making the class adaptable to various project requirements.

#### Configuration

The UserCSV class requires configuration similar to the User class. Here's an example configuration:

```php
$userConfig = [
    'sessionVarName' => 'meowUser',          // Session variable name
    'sessionPath' => '/path/to/sessions',    // Session storage path
    'singleLogin' => false,                  // Allow multiple sessions
    'forceLogin' => false,                   // Don't force login
    'lifeTime' => 3600,                      // Session lifetime in seconds
    'password' => [
        'type' => 'encrypted',               // 'encrypted' or 'plain'
        'algorithm' => 'sha256',             // Hash algorithm
        'salt' => 'your-secret-salt',        // Salt for encryption
        'minLength' => 8,                    // Minimum password length
        'maxLength' => 20,                   // Maximum password length
        'minUppercase' => 1,                 // Minimum uppercase letters
        'minLowercase' => 1,                 // Minimum lowercase letters
        'minNumber' => 1,                    // Minimum numbers
        'minSpecial' => 1,                   // Minimum special characters
    ],
    'csvDB' => [
        'path' => '/path/to/csv/files',      // CSV storage directory
    ],
    'userTable' => 'user',                   // CSV filename (without .csv)
    'userFields' => [                        // Field name mapping
        'userID' => 'user_id',
        'userName' => 'user_name',
        'loginName' => 'login_name',
        'password' => 'password',
        'email' => 'email',
        'status' => 'status',
        'loginTime' => 'login_time',
        'logoutTime' => 'logout_time',
        'lastActive' => 'last_active',
        'sessionID' => 'session_id',
    ],
];
```

#### Usage Examples

**Initialization and Setup**

Before using any user management features, you need to initialize the UserCSV class with your configuration. The configuration is typically stored in your main config file and includes settings for password encryption, session management, and field mappings.

```php
use Paheon\MeowBase\Tools\UserCSV;

// Initialize UserCSV with configuration from MeowBase
$userCSV = new UserCSV($meow->configTree['user']);

// Get field mappings for easy access
$userFields = $userCSV->config['userFields'];
```

**Creating New Users**

Creating a new user account is straightforward. You provide a login name, password, and any additional user data fields. The system automatically handles password encryption if enabled in your configuration. The method returns the new user's ID on success, or -1 on failure with error details available in `lastError`.

```php
// Prepare user data with additional fields
$userData = [
    $userFields['userName'] => "John Doe",
    $userFields['email'] => "john@example.com",
];

// Create the user account
$userID = $userCSV->createUser("johndoe", "SecurePass123!", $userData);
if ($userID > 0) {
    echo "User created successfully with ID: $userID";
} else {
    echo "Failed to create user: " . $userCSV->lastError;
}
```

**User Authentication and Login**

The login process verifies credentials against the stored data and establishes a session. Upon successful login, the user's information is loaded into the `$user` property, and the session is maintained automatically. You can check login status at any time using the `isLoggedIn()` method.

```php
// Attempt to login with credentials
$loginResult = $userCSV->login("johndoe", "SecurePass123!");
if ($loginResult) {
    echo "Login successful!";
    echo "User ID: " . $userCSV->user[$userFields['userID']];
    echo "Username: " . $userCSV->user[$userFields['userName']];
    echo "Is logged in: " . ($userCSV->isLoggedIn() ? 'Yes' : 'No');
} else {
    echo "Login failed: " . $userCSV->lastError;
}
```

**Retrieving User Information**

You can retrieve user information either by user ID or by login name. This is useful for displaying user profiles, validating user existence, or loading user data for administrative purposes. Both methods return an associative array with all user fields, or null if the user is not found.

```php
// Get user by ID
$user = $userCSV->getUserByID($userID);
if ($user) {
    echo "Username: " . $user[$userFields['userName']];
    echo "Email: " . $user[$userFields['email']];
    echo "Status: " . $user[$userFields['status']];
}

// Get user by login name
$user = $userCSV->getUserByLoginName("johndoe");
if ($user) {
    echo "Found user: " . $user[$userFields['userName']];
    echo "User ID: " . $user[$userFields['userID']];
}
```

**Updating User Information**

User updates can be performed in two ways: updating the currently logged-in user (by calling `updateUser()` without a user ID parameter), or updating any specific user by providing their ID. When updating the current user, the system automatically refreshes the `$user` property with the new data from the CSV file.

```php
// Update current logged-in user
$updateData = [
    $userFields['userName'] => "John Updated",
    $userFields['email'] => "john.updated@example.com",
];
if ($userCSV->updateUser($updateData)) {
    echo "User updated successfully";
    // Current user object is automatically refreshed
    echo "New username: " . $userCSV->user[$userFields['userName']];
}

// Update specific user by ID (administrative function)
$updateData = [
    $userFields['email'] => "newemail@example.com"
];
if ($userCSV->updateUser($updateData, $userID)) {
    echo "User $userID updated successfully";
}
```

**Password Management**

Passwords can be updated securely using the `updatePassword()` method. The system automatically handles encryption based on your configuration settings, so you never need to manually encrypt passwords. After a password update, the user will need to use the new password for subsequent logins.

```php
// Update password for a specific user
if ($userCSV->updatePassword("NewSecurePass456!", $userID)) {
    echo "Password updated successfully";
    
    // User can now login with the new password
    if ($userCSV->login("johndoe", "NewSecurePass456!")) {
        echo "Login with new password successful";
    }
}
```

**Session Management and Logout**

The class provides methods to check login status and properly log out users. When a user logs out, the session is cleared and the user's status is updated in the CSV file. The continue login feature allows users to restore their sessions across page loads or script executions.

```php
// Check current login status
if ($userCSV->isLoggedIn()) {
    echo "User is currently logged in";
    echo "Session ID: " . $userCSV->sessionID;
    echo "Login Name: " . $userCSV->user[$userFields['loginName']];
}

// Logout current user
$userCSV->logout();
echo "User logged out successfully";

// Later, restore previous session
if ($userCSV->continueLogin()) {
    echo "Previous session restored";
    echo "Logged in as: " . $userCSV->user[$userFields['loginName']];
}
```

**Deleting User Accounts**

Deleting a user removes their record from the CSV file and cleans up any associated session data. If the deleted user is currently logged in, the system automatically logs them out. You can delete either the current user or specify a user by their ID.

```php
// Delete a specific user
if ($userCSV->delUser($userID)) {
    echo "User deleted successfully";
} else {
    echo "Failed to delete user: " . $userCSV->lastError;
}

// If you want to delete the currently logged-in user
if ($userCSV->isLoggedIn()) {
    if ($userCSV->delUser()) {
        echo "Current user account deleted";
    }
}
```

**Properties:**
- `$userDB`: CsvDB instance for user data storage
- `$dbLoaded`: Flag indicating whether the CSV database has been loaded
- `$csvPath`: Path to the CSV storage directory
- `$userTableFile`: Full path to user CSV file
- `$config`: User configuration array (inherited from User)
- `$user`: Current user data array (inherited from User)
- `$sessionID`: Current session identifier (inherited from User)
- `$sessionVarName`: Session variable name (inherited from User)
- `$userTable`: User table name (inherited from User)
- `$userFields`: User field mappings (inherited from User)
- `$encrypted`: Password encryption flag (inherited from User)
- `$lastError`: Last error message (inherited from User)

**Protected Methods:**
- `copyUserFromDB(?string $userID = null): bool`: Copies user data from CSV database to current user object

**Public Methods:**
- `__construct(array $config)`: Initializes UserCSV with configuration and sets up CSV database connection
- `loadUserRec(bool $forceLoad = false): bool`: Loads user records from CSV file into memory
- `getField(string $field, ?string $default = null): string`: Gets mapped field name from configuration
- `getUserByID(int $userID, bool $forceLoad = false): ?array`: Retrieves user data by ID
- `getUserByLoginName(string $loginName, bool $forceLoad = false): ?array`: Retrieves user data by login name
- `createUser(string $loginName, string $userPassword, array $fieldList = []): int`: Creates new user and returns user ID
- `updateUser(array $fieldList, ?string $userID = null): bool`: Updates user information
- `delUser(?string $userID = null): bool`: Deletes user account
- `updatePassword(string $password, ?string $userID = null): bool`: Updates user password
- `updateUserStatus(bool $login, int $lastActiveTime, ?int $loginTime = null, ?string $userID = null): bool`: Updates user login status

**Inherited Public Methods from User class:**
- `login(string $loginName, string $password, ?bool $forceLogin = null): bool`: Authenticates user and creates session
- `continueLogin(): bool`: Continues previous login session
- `logout(): void`: Logs out current user and clears session
- `isLoggedIn(bool $forceLogout = false): bool`: Checks if user is currently logged in
- `touchSession(int $currTime): void`: Updates session activity timestamp
- `getSessionID(): ?string`: Retrieves session ID from database
- `validatePassword(string $password): bool`: Validates password against configured rules
- `genPassword(): string`: Generates a secure password following configured rules
- `getPasswordHash(string $password, ?string $algorithm = null): string`: Generates password hash
- `checkPassword(string $password, string $hash): bool`: Verifies password against hash
- `changePassword(?string $newPassword = null, ?string $oldPassword = null): string|false`: Changes user password with optional validation



## Directory Structure
The framework uses the following directory structure:
- `/etc`: Configuration directory
- `/src`: Core class directory
  - `/src/Tools`: Tools components directory
- `/var`: Variable data directory
  - `/var/cache`: Cache files
  - `/var/db` : CsvDB data files
  - `/var/log`: Log files
  - `/var/session` : Session files
  - `/var/spool`: Spool directory 
  - `/var/spool/mailer`: Mail spool (for Async mode)
  - `/var/tmp`: Local temporary folder

## System Requirements
- PHP: 8.2 or higher
- catfan/medoo: 2.1.6 or higher
- katzgrau/klogger: 1.2.2 or higher
- symfony/cache: 6.4.12 or higher
- phpmailer/phpmailer: 6.9.3 or higher
- guzzlehttp/guzzle: 7.9.2 or higher

## License
MIT License
