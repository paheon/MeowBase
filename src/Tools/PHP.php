<?php
/**
 * PHP Class
 * 
 * This class stores some useful functions for PHP environment and performance.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */
namespace Paheon\MeowBase\Tools;
use Paheon\MeowBase\CacheDB;
use Paheon\MeowBase\Cache;
use Paheon\MeowBase\Tools\CsvDB;

// PHP Class //
class PHP {

    // Check if a function is disabled //
    // Return 0 if function is exists, 1 if function not exists, 2 if function exists but disabled //
    public static function chkDisabledFunction(string $funcName):int {
        $allowExec = function_exists($funcName) ? 0 : 1;
        if ($allowExec && function_exists('ini_get')) {
            $disFuncStr = ini_get('disable_functions') ?? "";
            $disFuncArr = explode(',', $disFuncStr);
            if (in_array($funcName, $disFuncArr)) $allowExec = 1;
        }
        return $allowExec; 
    }

    // The code below aims for ≤ 350 milliseconds stretching time, which is an appropriate delay for systems handling interactive logins.
    public static function checkPwdHashCost(float $timeTarget = 0.350):string {
        $cost = 11;
        do {
            $cost++;
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
            $end = microtime(true);
        } while (($end - $start) < $timeTarget);
        return $cost - 1;
    }

	// Set display PHP Error Message //
	public static function showPHPError(bool $show = false):void {
	    $report = ($show) ? E_ALL : 0;
	    $flag = ($show) ? 1: 0;
	    ini_set('display_errors', $flag);
        ini_set('display_startup_errors', $flag);
        error_reporting($report);
	}

    public static function valueType(mixed $value):string {        
        $out = "[unknown]";
        $type = gettype($value);
        try {
            if ($type == "object") {
                if (is_callable($value)) {
                    $out = "[callable]";
                } else if (is_iterable($value)) {
                    $out = "[iterable]";
                } else if (is_countable($value)) {
                    $out = "[countable]";
                } else {
                    $out = "[object]";
                }
                $out .= " ".get_class($value);
            } else if ($type == "boolean") {
                $out = "[bool] ".($value ? "true" : "false");
            } else if ($type == "array") {
                if (count($value) > 0) {
                    $out = "[array] ".print_r($value, true);
                } else {
                    $out = "[array] Array()";
                }    
            } else if ($type == "string") {
                $out = "[string] \"".$value."\"";
            } else if ($type == "integer") {
                $out = "[int] ".$value;
            } else if ($type == "double") {
                $out = "[float] ".$value;
            } else if ($type == "NULL") {
                $out = "[null]";
            } else if ($type == "resource") {
                $out = "[resource] ".get_resource_type($value)."(".get_resource_id($value).")";
            }
        } catch (\Exception $ex) {
            // return error message //
            $errMsg = __METHOD__." - Error, Code=".$ex->getCode().", Message=".$ex->getMessage();
            return $errMsg;
        }
        return $out;
    }


    // Dump class / object information //
    public static function classDump(object|string $class, bool $html = false):string {
        $errMsg = "";
        // HTML format //
        $hlStart = $html ? "<strong>" : "";
        $hlEnd = $html ? "</strong>" : "";
        $out = "";
        if ($html) {
            $out = "<pre class=\"class-dump\">\n<code>\n";
        }

        try {
            $reflection = new \ReflectionClass($class);

            // Class name, namespace and parent //
            $classAttrList = [ 
                "abstract" => $reflection->isAbstract(),
                "anonymous" => $reflection->isAnonymous(),
                "cloneable" => $reflection->isCloneable(),
                "enum" => $reflection->isEnum(),
                "final" => $reflection->isFinal(),
                "instantiable" => $reflection->isInstantiable(),
                "interface" => $reflection->isInterface(),
                "trait" => $reflection->isTrait(),
                "iterable" => $reflection->isIterable(),
                "readOnly" => $reflection->isReadOnly(),
                "userDefined" => $reflection->isUserDefined(),
            ];            
            $attributeList = [];
            foreach($classAttrList as $attr => $hasAttr) {
                if ($hasAttr) $attributeList[] = $attr;
            }

            // Class name, namespace and parent //
            $out .= $hlStart."Class:".$hlEnd." ".$reflection->getShortName()."\n";
            if (count($attributeList) > 0) $out .= $hlStart."Class Attribute(s):".$hlEnd."\n  ".implode("\n  ", $attributeList)."\n";
            $namespace = $reflection->getNamespaceName();
            if ($namespace)     $out .= $hlStart."Namespace:".$hlEnd."\n  ".$namespace."\n";
            $parentClass = $reflection->getParentClass();
            if ($parentClass)   $out .= $hlStart."Parent:".$hlEnd."\n  ".$parentClass->getName()."\n";

            // Interfaces //
            $interfaceList = $reflection->getInterfaceNames();
            if (count($interfaceList) > 0)  $out .= $hlStart."Interfaces:".$hlEnd."\n  ".implode("\n  ", $interfaceList)."\n";

            // Traits //
            $traitList = $reflection->getTraitNames();
            if (count($traitList) > 0)  $out .= $hlStart."Trait List:".$hlEnd."\n  ".implode("\n  ", $traitList)."\n";

            // Method Modifiers //
            $methodModList = [
                "static"    => \ReflectionMethod::IS_STATIC, 
                "public"    => \ReflectionMethod::IS_PUBLIC, 
                "protected" => \ReflectionMethod::IS_PROTECTED, 
                "private"   => \ReflectionMethod::IS_PRIVATE, 
                "abstract"  => \ReflectionMethod::IS_ABSTRACT,
                "final"     => \ReflectionMethod::IS_FINAL,
            ];

            // Methods //
            $methodList = $reflection->getMethods();
            if (count($methodList) > 0) {
                $out .= $hlStart."Methods:".$hlEnd."\n";
                foreach ($methodList as $method) {
                    $methodName = $method->getName();
                    $methodModifierList = [];
                    // Check method modifiers //
                    $methodModifiers = $method->getModifiers();
                    foreach ($methodModList as $modifierName => $modifierValue) {
                        if ($methodModifiers & $modifierValue) {
                            $methodModifierList[] = $modifierName;
                        }
                    }
                    $out .= "  " . (count($methodModifierList) > 0 ? implode(" ", $methodModifierList) . " " : "") . $methodName . "()" . "\n";
                }
            }

            // Constants //
            $constantList = $reflection->getConstants();
            if (count($constantList) > 0) {
                $out .= $hlStart."Constants:".$hlEnd."\n";    
                foreach ($constantList as $constantName => $constantValue) {
                    $out .= "  " . $constantName . " = " . self::valueType($constantValue) . "\n";
                }
            } 

            // Properties Modifiers //
            $propModList = [];
            if (phpversion() >= "8.4.0") {
                $propModList += [
                    "final"     => \ReflectionProperty::IS_FINAL,
                    "abstract"  => \ReflectionProperty::IS_ABSTRACT,
                    "virtual"   => \ReflectionProperty::IS_VIRTUAL,
                    "protectedSet" => \ReflectionProperty::IS_PROTECTED_SET,
                    "privateSet"   => \ReflectionProperty::IS_PRIVATE_SET,
                ];
            } 
            if (phpversion() >= "8.1.0") {
                $propModList += [
                    "readOnly"  => \ReflectionProperty::IS_READONLY,
                ];
            }
            $propModList += [
                "static"    => \ReflectionProperty::IS_STATIC, 
                "public"    => \ReflectionProperty::IS_PUBLIC, 
                "protected" => \ReflectionProperty::IS_PROTECTED, 
                "private"   => \ReflectionProperty::IS_PRIVATE, 
            ];
            
            // Properties //
            $staticPropList = $reflection->getStaticProperties();
            $propList = $reflection->getProperties();
            if (count($propList) > 0) {
                $out .= $hlStart."Properties:".$hlEnd."\n";
                foreach ($propList as $propItem) {
                    $propName = $propItem->getName();
                    $propModifierList = [];
                    $propModifiers = $propItem->getModifiers();
                    $propValue = "";
                    $showValue = false;
                    $propType = $propItem->getType() ? $propItem->getType()->getName() : "";
                    if (isset($staticPropList[$propName])) {
                        $propValue = $staticPropList[$propName];
                        $showValue = true;
                    } else if (is_object($class)) {
                        if (isset($class->$propName)) {
                            $propValue = $propItem->getValue($class);
                            $showValue = true;
                        } else if (method_exists($class, "__get")) {
                            $propValue = $class->__get($propName);
                            $showValue = true;
                        } else {    
                            $propValue = "(* non-accessable *)";
                            $showValue = true;
                        }   
                    }
                    foreach($propModList as $modifierName => $modifierValue) {
                        if ($propModifiers & $modifierValue) {
                            $propModifierList[] = $modifierName;
                        }
                    }
                    $out .= "  " . (count($propModifierList) > 0 ? implode(" ", $propModifierList) . " " : "") . $propType . " \$" . $propName . ($showValue ? " = " . self::valueType($propValue) : ""). "\n";
                }
            }
        } catch (\Exception $ex) {
            // return error message //
            $errMsg = __METHOD__." - Error, Code=".$ex->getCode().", Message=".$ex->getMessage();
            return $errMsg;
        }
        $out .= $html ? "</code>\n</pre>\n" : "";
        return $out;    
    }

    // CLI Session Support //
    public static function startCLISession(?string $sessionID = null, ?string $savePath = null):string|false {
        try {
            // Check if running in CLI environment
            if (php_sapi_name() !== 'cli') {
                return false;
            }
            
            // Set Session storage path
            if ($savePath) {
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0755, true);
                }
                ini_set('session.save_path', $savePath);
            }
            
            // Set Session handler and name
            ini_set('session.save_handler', 'files');
            ini_set('session.name', 'CLI_SESSION_ID');
            
            // Set Session ID
            if ($sessionID) {
                $newSessionID = session_id($sessionID);
            } else {
                // Generate a unique Session ID
                $sessionID = 'cli_' . uniqid() . '_' . getmypid();
                $newSessionID = session_id($sessionID);
            }
            
            // 启动 Session
            session_start();
            
            return $newSessionID;
        } catch (\Exception $ex) {
            return false;
        }
    }
    
    // Check if running in CLI //
    public static function isCLI():bool {
        return php_sapi_name() === 'cli';
    }
    
    // Get CLI Session info //
    public static function getSessionInfo():array {
        $info = [
            'cli' => self::isCLI(),
            'pid' => getmypid(),
            'php_sapi' => php_sapi_name(),
            'session_status' => session_status(),
            'session_id' => session_id(),
            'session_name' => session_name(),       // ini_get('session.name'),
            'session_save_path' => session_save_path(), // ini_get('session.save_path'),
            'session_save_handler' => ini_get('session.save_handler'),
        ];
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $info['session_data'] = $_SESSION;
        }
        
        return $info;
    }
}