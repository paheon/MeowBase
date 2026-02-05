# Dynamic Autoload Configuration Guide

## Overview

When certain namespace locations are not configured in `composer.json`, you can dynamically add or modify autoload rules in your program.

## Usage Methods

### Method 1: Use Autoload Class (Recommended)

```php
use Paheon\Meow\Autoload;

// Add PSR-4 namespace mapping
Autoload::addPsr4('MyApp\\Controllers\\', __DIR__ . '/../app/controllers/');

// Use relative path (automatically finds project root)
Autoload::addNamespace('MyApp\\Models\\', 'app/models/');

// Add multiple paths to the same namespace
Autoload::addPsr4('MyApp\\Services\\', [
    __DIR__ . '/../app/services/',
    __DIR__ . '/../app/custom-services/'
]);
```

### Method 2: Directly Use Composer's ClassLoader

```php
use Paheon\Meow\Autoload;

$loader = Autoload::getComposerLoader();
if ($loader) {
    // Add PSR-4 mapping
    $loader->addPsr4('Vendor\\Package\\', __DIR__ . '/../vendor/custom/src/');
    
    // Add PSR-0 mapping (legacy)
    $loader->add('OldStyle_', __DIR__ . '/../legacy/');
    
    // Add class map (direct mapping of class name to file)
    $loader->addClassMap([
        'MyClass' => __DIR__ . '/../classes/MyClass.php',
    ]);
}
```

### Method 3: Register Custom Autoload Function

```php
use Paheon\Meow\Autoload;

$customAutoloader = function ($className) {
    $file = __DIR__ . '/../custom/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
};

// Register autoloader (true = execute first)
Autoload::register($customAutoloader, true);
```

## Practical Use Cases

### Use Case 1: Dynamic Loading from Config File

```php
// Read autoload rules from config file
$config = [
    'autoload' => [
        'MyApp\\Controllers\\' => 'app/controllers/',
        'MyApp\\Models\\' => 'app/models/',
        'MyApp\\Services\\' => 'app/services/',
    ]
];

foreach ($config['autoload'] as $namespace => $path) {
    Autoload::addNamespace($namespace, $path);
}
```

### Use Case 2: Dynamic Addition Based on Environment Variables

```php
$environment = getenv('APP_ENV') ?: 'production';

if ($environment === 'development') {
    Autoload::addPsr4('DevTools\\', __DIR__ . '/../dev-tools/');
}
```

### Use Case 3: Read Rules from Database or API

```php
// Assuming reading autoload rules from database
$rules = $db->query("SELECT namespace, path FROM autoload_rules");

foreach ($rules as $rule) {
    Autoload::addNamespace($rule['namespace'], $rule['path']);
}
```

## API Reference

### Autoload::addPsr4(string $prefix, string|array $paths, bool $prepend = false): bool

Add PSR-4 namespace mapping.

- `$prefix`: Namespace prefix (e.g., `"MyApp\\Controllers\\"`)
- `$paths`: Path or array of paths
- `$prepend`: Whether to load first (default false)

### Autoload::addNamespace(string $namespace, string $basePath): bool

Add namespace mapping (uses relative path, automatically converted to absolute path).

- `$namespace`: Namespace (e.g., `"MyApp\\Controllers\\"`)
- `$basePath`: Path relative to project root

### Autoload::getComposerLoader(): ?\Composer\Autoload\ClassLoader

Get Composer's ClassLoader instance for direct manipulation.

### Autoload::register(callable $autoloadFunction, bool $prepend = false): bool

Register custom autoload function.

### Autoload::getRegisteredLoaders(): array

Get all registered autoload functions.

## Notes

1. **Timing**: Recommended to set immediately after `require vendor/autoload.php`
2. **Paths**: Use absolute paths or paths relative to project root
3. **Namespace Format**: PSR-4 namespaces should end with `\\`
4. **Priority**: Later added rules will override earlier ones (unless using `prepend = true`)

## Example Files

- `autoload-simple-example.php` - Simple usage example
- `autoload-usage.php` - Complete feature demonstration
