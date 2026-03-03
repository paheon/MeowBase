# ClassBase Trait Usage Guide

## Overview

The `ClassBase` trait provides fundamental functionality for all MeowBase classes, including property access control, mass property operations, event handling, exception management, and debugging support. It's a trait, so you can use it in any class to add these capabilities.

## Basic Usage

### Adding ClassBase to Your Class

```php
use Paheon\MeowBase\ClassBase;

class MyClass {
    use ClassBase;
    
    protected string $name = "Default";
    protected int $age = 0;
    protected bool $active = false;
}
```

### Property Access via Magic Methods

Once you use the `ClassBase` trait, you can access protected properties using magic getters and setters:

```php
$obj = new MyClass();

// Set properties
$obj->name = "John";
$obj->age = 25;
$obj->active = true;

// Get properties
echo $obj->name;    // "John"
echo $obj->age;     // 25
echo $obj->active;  // true
```

## Property Protection

### Read Protection (denyRead)

Prevent reading of sensitive properties:

```php
class ProtectedClass {
    use ClassBase;
    
    protected string $publicInfo = "Public Info";
    protected string $secretInfo = "Secret Info";
    
    public function __construct() {
        // Deny read access to secretInfo
        $this->denyRead = ['secretInfo'];
    }
}

$obj = new ProtectedClass();
echo $obj->publicInfo;  // "Public Info"
echo $obj->secretInfo;  // null, and $obj->lastError is set
```

### Write Protection (denyWrite)

Prevent modification of certain properties:

```php
class ReadOnlyClass {
    use ClassBase;
    
    protected string $readOnly = "Cannot Change";
    
    public function __construct() {
        // Deny write access (lastError is already protected by default)
        $this->denyWrite = array_merge($this->denyWrite, ['readOnly']);
    }
}

$obj = new ReadOnlyClass();
$obj->readOnly = "New Value";  // Ignored, and $obj->lastError is set
```

## Mass Property Operations

### Mass Setter

Set multiple properties at once:

```php
class DataClass {
    use ClassBase;
    
    protected string $firstName = "";
    protected string $lastName = "";
    protected string $email = "";
    protected int $score = 0;
}

$data = new DataClass();
$unsetList = $data->massSetter([
    'firstName' => 'John',
    'lastName' => 'Doe',
    'email' => 'john.doe@example.com',
    'score' => 95,
    'invalidProp' => 'This will fail'
]);

// $unsetList contains properties that couldn't be set
if (!empty($unsetList)) {
    print_r($unsetList);
}
```

### Mass Getter

Get multiple properties at once:

```php
$propList = $data->massGetter([
    'firstName' => '',
    'lastName' => '',
    'email' => '',
    'score' => 0
]);

// $propList now contains the values
print_r($propList);
```

## Variable Mapping

Map external property names to internal property names:

```php
class MappedClass {
    use ClassBase;
    
    protected string $internalName = "";
    
    public function __construct() {
        // Map external 'name' to internal 'internalName'
        $this->varMap = [
            'name' => 'internalName'
        ];
    }
}

$obj = new MappedClass();
$obj->name = "Mapped Value";  // Sets internalName
echo $obj->name;               // Returns internalName value
```

## Array Element Access by Path

Access nested array elements using path notation:

```php
class ArrayClass {
    use ClassBase;
    
    protected array $data = [
        'user' => [
            'profile' => [
                'name' => 'John',
                'age' => 30
            ]
        ]
    ];
}

$obj = new ArrayClass();

// Get element by path
$name = $obj->getElemByPath('data', 'user/profile/name');
echo $name;  // "John"

// Set element by path
$obj->setElemByPath('data', 'user/profile/age', 31);
$age = $obj->getElemByPath('data', 'user/profile/age');
echo $age;   // 31
```

## Event System

The ClassBase trait provides a built-in event system for observer pattern implementation.

### Registering Event Handlers

```php
class EventClass {
    use ClassBase;
}

$obj = new EventClass();

// Register event handlers
$handler1 = $obj->registerEvent('user.created', function($args) {
    echo "Handler 1: User created - " . $args['caller']['username'] . "\n";
});

$handler2 = $obj->registerEvent('user.created', function($args) {
    echo "Handler 2: Welcome " . $args['caller']['username'] . "\n";
});
```

### Triggering Events

```php
// Trigger event with arguments
$results = $obj->triggerEvent('user.created', [
    'username' => 'john_doe',
    'email' => 'john@example.com'
]);

// Event arguments automatically include:
// - event: Event name
// - caller: Object that triggered the event
// - handlerID: Handler ID
```

### Unregistering Event Handlers

```php
// Unregister specific handler
$obj->unregisterEvent('user.created', $handler1);

// Unregister all handlers for an event
$obj->unregisterEvent('user.created');
```

### Event Arguments

When an event is triggered, the following arguments are automatically added:

- `event`: The event name
- `caller`: The object that triggered the event
- `handlerID`: The ID of the handler being called

Your custom arguments come after these.

## Exception Handling

### Enabling Exception Mode

By default, ClassBase only sets `lastError`. You can enable exception throwing:

```php
class ExceptionClass {
    use ClassBase;
    
    protected string $data = "test";
    
    public function __construct() {
        $this->useException = true;
        $this->denyRead = ['data'];
    }
}

$obj = new ExceptionClass();

try {
    $result = $obj->data;  // Throws exception
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
```

### Custom Exception Class

```php
class MyCustomException extends Exception {}

class CustomExceptionClass {
    use ClassBase;
    
    public function __construct() {
        $this->useException = true;
        $this->exceptionClass = MyCustomException::class;
    }
}
```

## Boolean Conversion (isTrue)

The `isTrue()` method provides flexible boolean conversion:

```php
$obj = new MyClass();

// Returns true for: 'y', 'yes', 'true', '1', 'on', 'enable', positive numbers
$obj->isTrue('yes');    // true
$obj->isTrue('true');   // true
$obj->isTrue('1');      // true
$obj->isTrue('on');     // true
$obj->isTrue(1);        // true
$obj->isTrue(100);      // true

// Returns false for: 'n', 'no', 'false', '0', 'off', 'disable', 0, negative
$obj->isTrue('no');     // false
$obj->isTrue('false');  // false
$obj->isTrue('0');      // false
$obj->isTrue(0);        // false
$obj->isTrue(-1);       // false

// Array matching
$obj->isTrue('value', ['value1', 'value2', 'value']);  // true
```

## Debug Information

### Using __debugInfo()

The `__debugInfo()` method returns debug information about the object:

```php
$obj = new MyClass();
$obj->name = "Debug Test";
$obj->age = 42;

$debugInfo = $obj->__debugInfo();
print_r($debugInfo);

// Returns:
// - denyRead: Array of read-protected properties
// - denyWrite: Array of write-protected properties
// - varMap: Variable mapping array
// - lastError: Last error message
// - useException: Whether exceptions are enabled
// - exceptionClass: Exception class name
// - eventList: Registered events
// - eventSerial: Next event handler ID
```

### For Sub-Classes

Sub-classes can override `customDebugInfo()` to add custom debug information. The returned array will be merged with the automatically collected property information:

```php
class MySubClass {
    use ClassBase;
    
    protected string $customProp = "custom";
    
    private function customDebugInfo(): array {
        return [
            'customProp' => $this->customProp,
            'computedValue' => $this->customProp . '_computed',
        ];
    }
}
```

Note: `customDebugInfo()` is a private method. Each class that uses the ClassBase trait can define its own implementation. The `__debugInfo()` method automatically discovers and invokes it via reflection.

## Error Handling

All operations set `lastError` on failure:

```php
$obj = new MyClass();
$obj->denyRead = ['secret'];

$result = $obj->secret;
if ($obj->lastError) {
    echo "Error: " . $obj->lastError;
}
```

## Best Practices

1. **Always check `lastError`**: After operations that might fail, check `lastError`.

2. **Use massSetter/massGetter**: For setting/getting multiple properties, use mass operations for better performance.

3. **Protect sensitive properties**: Use `denyRead` and `denyWrite` to protect sensitive data.

4. **Event naming convention**: Use dot notation for events (e.g., `user.created`, `order.updated`).

5. **Exception mode**: Enable exception mode (`useException = true`) when you want strict error handling.

6. **Custom getters/setters**: If you need custom logic, implement `getPropertyName()` or `setPropertyName($value)` methods.

7. **Debug info**: Override `customDebugInfo()` in sub-classes to add custom properties to the debug output.

## Property Access Methods

ClassBase supports custom getter and setter methods. If you define methods like `getName()` or `setName($value)`, they will be called automatically:

```php
class MyClass {
    use ClassBase;
    
    protected string $name = "";
    
    // Custom getter
    public function getName(): string {
        return strtoupper($this->name);
    }
    
    // Custom setter
    public function setName(string $value): void {
        $this->name = trim($value);
    }
}

$obj = new MyClass();
$obj->name = "  john  ";  // Calls setName(), trims value
echo $obj->name;          // Calls getName(), returns "JOHN"
```

## Type Conversion

ClassBase automatically handles type conversion:

```php
$obj = new MyClass();
$obj->intProperty = "123";      // Converts to int(123)
$obj->floatProperty = "45.67";  // Converts to float(45.67)
$obj->boolProperty = "yes";     // Converts to bool(true) via isTrue()
$obj->arrayProperty = '{"key":"value"}';  // Converts JSON string to array
```

## API Reference

### Properties

- `$denyRead`: Array of property names that cannot be read
- `$denyWrite`: Array of property names that cannot be written
- `$varMap`: Array mapping external names to internal property names
- `$lastError`: Last error message
- `$useException`: Whether to throw exceptions on errors
- `$exceptionClass`: Exception class name to use
- `$eventList`: Array of registered events
- `$eventSerial`: Next event handler ID

### Methods

- `__get(string $prop): mixed` - Magic getter
- `__set(string $prop, mixed $value): void` - Magic setter
- `massGetter(array $propList): mixed` - Get multiple properties
- `massSetter(array $propList): array` - Set multiple properties
- `getElemByPath(string $prop, string $path = ""): mixed` - Get array element by path
- `setElemByPath(string $prop, string $path, mixed $value): void` - Set array element by path
- `isTrue(mixed $value, mixed $matchValue = null): bool` - Boolean conversion
- `throwException(string $message = "", int $code = 0, ?\Throwable $previous = null): void` - Throw exception
- `registerEvent(string $event, callable $callback): int` - Register event handler
- `triggerEvent(string $event, array $args = []): array` - Trigger event
- `unregisterEvent(string $event, int $handlerID = 0): bool` - Unregister event handler
- `customDebugInfo(): array` - Override to add custom debug info (for sub-classes)
- `__debugInfo(): array` - Get debug information (automatically includes properties and customDebugInfo)

## Example Files

- `classbase-example.php` - Comprehensive examples demonstrating all features
