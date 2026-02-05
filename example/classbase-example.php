<?php
/**
 * ClassBase Example
 * 
 * This example demonstrates how to use the ClassBase trait to add property
 * access control, mass property operations, and other utilities to your classes.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.1
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\ClassBase;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "ClassBase Example".$br;
echo "==========================================".$br.$br;

// Example 1: Basic ClassBase Usage
echo "Example 1: Basic ClassBase Usage".$br;
echo "--------------------------------".$br;

class MyClass {
    use ClassBase;
    
    protected string $name = "Default";
    protected int $age = 0;
    protected bool $active = false;
}

$obj = new MyClass();

// Access protected properties using magic methods
echo "Setting name to 'John':".$br;
$obj->name = "John";
echo "Name: ".$obj->name.$br;

echo "Setting age to 25:".$br;
$obj->age = 25;
echo "Age: ".$obj->age.$br;

echo "Setting active to true:".$br;
$obj->active = true;
echo "Active: ".var_export($obj->active, true).$br.$br;

// Example 2: Property Protection (denyRead and denyWrite)
echo "Example 2: Property Protection".$br;
echo "--------------------------------".$br;

class ProtectedClass {
    use ClassBase;
    
    protected string $publicInfo = "Public Info";
    protected string $secretInfo = "Secret Info";
    
    public function __construct() {
        // Deny read access to secretInfo
        $this->denyRead = ['secretInfo'];
        // Deny write access to publicInfo
        $this->denyWrite = array_merge($this->denyWrite, ['publicInfo']);
    }
}

$protected = new ProtectedClass();

// Try to read publicInfo (allowed)
echo "Reading publicInfo: ".$protected->publicInfo.$br;

// Try to read secretInfo (denied)
echo "Trying to read secretInfo:".$br;
$result = $protected->secretInfo;
echo "Result: ".var_export($result, true).$br;
echo "Error: ".$protected->lastError.$br;

// Try to write publicInfo (denied)
echo "Trying to write publicInfo:".$br;
$protected->publicInfo = "New Value";
echo "publicInfo after write: ".$protected->publicInfo.$br;
echo "Error: ".$protected->lastError.$br.$br;

// Example 3: Mass Getter and Setter
echo "Example 3: Mass Getter and Setter".$br;
echo "--------------------------------".$br;

class DataClass {
    use ClassBase;
    
    protected string $firstName = "";
    protected string $lastName = "";
    protected string $email = "";
    protected int $score = 0;
}

$data = new DataClass();

// Mass set multiple properties
echo "Setting multiple properties at once:".$br;
$unsetList = $data->massSetter([
    'firstName' => 'John',
    'lastName' => 'Doe',
    'email' => 'john.doe@example.com',
    'score' => 95,
    'invalidProp' => 'This will fail'
]);

echo "After massSetter:".$br;
echo "  firstName: ".$data->firstName.$br;
echo "  lastName: ".$data->lastName.$br;
echo "  email: ".$data->email.$br;
echo "  score: ".$data->score.$br;
echo "  Error: ".$data->lastError.$br;
echo "  Unset list: ".var_export($unsetList, true).$br.$br;

// Mass get multiple properties
echo "Getting multiple properties at once:".$br;
$propList = $data->massGetter([
    'firstName' => '',
    'lastName' => '',
    'email' => '',
    'score' => 0,
    'invalidProp' => 'default'
]);

echo "Mass getter result:".$br;
print_r($propList);
echo "Error: ".$data->lastError.$br.$br;

// Example 4: isTrue() Method
echo "Example 4: isTrue() Method".$br;
echo "--------------------------------".$br;

$testValues = [
    'y', 'yes', 'true', '1', 'on', 'enable', 1, 100,
    'n', 'no', 'false', '0', 'off', 'disable', 0, -1
];

echo "Testing isTrue() with various values:".$br;
foreach ($testValues as $value) {
    $result = $data->isTrue($value);
    echo "  isTrue(".var_export($value, true).") = ".var_export($result, true).$br;
}
echo $br;

// Example 5: Variable Mapping
echo "Example 5: Variable Mapping".$br;
echo "--------------------------------".$br;

class MappedClass {
    use ClassBase;
    
    protected string $internalName = "";
    
    public function __construct() {
        // Map external property name to internal property name
        $this->varMap = [
            'name' => 'internalName'
        ];
    }
}

$mapped = new MappedClass();

// Use mapped property name
echo "Setting 'name' (mapped to 'internalName'):".$br;
$mapped->name = "Mapped Value";
echo "Reading 'name': ".$mapped->name.$br;
echo "Reading 'internalName': ".$mapped->internalName.$br.$br;

// Example 6: Array Element Access
echo "Example 6: Array Element Access".$br;
echo "--------------------------------".$br;

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

$arrayObj = new ArrayClass();

// Get element by path
echo "Getting element by path 'user/profile/name':".$br;
$name = $arrayObj->getElemByPath('data', 'user/profile/name');
echo "  Result: ".$name.$br;

// Set element by path
echo "Setting element by path 'user/profile/age' to 31:".$br;
$arrayObj->setElemByPath('data', 'user/profile/age', 31);
$age = $arrayObj->getElemByPath('data', 'user/profile/age');
echo "  New age: ".$age.$br.$br;

// Example 7: Event System
echo "Example 7: Event System".$br;
echo "--------------------------------".$br;

class EventClass {
    use ClassBase;
}

$eventObj = new EventClass();

// Register event handlers
echo "Registering event handlers:".$br;
$handler1 = $eventObj->registerEvent('user.created', function($args) use ($br) {
    echo "  Handler 1: User created - ".$args['caller']['username'].$br;
});

$handler2 = $eventObj->registerEvent('user.created', function($args) use ($br) {
    echo "  Handler 2: Welcome ".$args['caller']['username'].$br;
});

// Trigger event
echo "Triggering 'user.created' event:".$br;
$eventObj->triggerEvent('user.created', [
    'username' => 'john_doe',
    'email' => 'john@example.com'
]);

// Unregister one handler
echo "Unregistering handler 1:".$br;
$eventObj->unregisterEvent('user.created', $handler1);

// Trigger again
echo "Triggering event again:".$br;
$eventObj->triggerEvent('user.created', [
    'username' => 'jane_doe',
    'email' => 'jane@example.com'
]);
echo $br;

// Example 8: Exception Handling
echo "Example 8: Exception Handling".$br;
echo "--------------------------------".$br;

class ExceptionClass {
    use ClassBase;
    
    protected string $data = "test";
    
    public function __construct() {
        $this->useException = true;
    }
}

$exceptionObj = new ExceptionClass();
$exceptionObj->denyRead = ['data'];

echo "Trying to read protected property (with exception enabled):".$br;
try {
    $result = $exceptionObj->data;
} catch (Exception $e) {
    echo "  Exception caught: ".$e->getMessage().$br;
    echo "  File: ".$e->getFile().$br;
    echo "  Line: ".$e->getLine().$br;
}
echo $br;

// Example 9: __debugInfo() Method
echo "Example 9: __debugInfo() Method".$br;
echo "--------------------------------".$br;

$debugObj = new MyClass();
$debugObj->name = "Debug Test";
$debugObj->age = 42;

echo "Debug info:".$br;
$debugInfo = $debugObj->__debugInfo();
print_r($debugInfo);
echo $br;

echo "Example completed!".$br;
