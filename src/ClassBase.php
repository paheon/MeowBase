<?php
//
// ClassBase.php - MeowBase Component Fundamental Class
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
namespace Paheon\MeowBase;

trait ClassBase {
    protected	array	$denyRead  = [];                // Read deny list
    protected	array	$denyWrite = ["lastError"];     // Write deny list
    protected	array	$varMap    = [];                // Variable Mapping [ "srcProp" => "destProp" ]
    protected	string	$lastError = "";                // Last error message
    protected	bool	$useException = false;          // Enable exception
    protected   string  $exceptionClass = "\Exception"; // Name of exception class

    // Get Property //
	private function _getProperty(string $prop, string $elem = ""):mixed {
        $this->lastError = "";
        // Field Mapping //
        if (isset($this->varMap[$prop])) $prop = $this->varMap[$prop]; 
        // Deny read //
        if (in_array($prop, $this->denyRead)) {
            $this->lastError = "Property '$prop' read denied!";
            $this->throwException($this->lastError, 1);
            return null;
        }
        // Get property data //
        $method = "get".ucfirst($prop);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else if (property_exists($this, $prop)) {
	        if (is_array($this->$prop) && $elem != "") {
	            if (isset($this->$prop[$elem])) {
	                return $this->$prop[$elem];
	            } else {
	                $this->lastError = "Property '$prop' element '$elem' not exist!";
                    $this->throwException($this->lastError, 3);
                    return null;
	            }    
	        }
	        return $this->$prop;
	    }
	    $this->lastError = "Property '$prop' not exist!";
        $this->throwException($this->lastError, 4);
	    return null;
	}

    // Set Property //
    private function _setProperty(string $prop, mixed $value):void {
        $this->lastError = "";
        // Field Mapping //
        if (isset($this->varMap[$prop])) $prop = $this->varMap[$prop]; 
        // Deny write //
        if (in_array($prop, $this->denyWrite)) {
            $this->lastError = "Property '$prop' write denied!";
            $this->throwException($this->lastError, 2);
            return;
        }
        // Set data //
        $method = "set".ucfirst($prop);
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else if (property_exists($this, $prop)) {
            if (is_array($this->$prop)) {
                if (is_array($value)) {
                    $this->$prop = $value;
                    return;
                } else if (is_string($value)) {    
                    $arrayVal = json_decode($value, true);
                    if (is_array($arrayVal)) {
                        $this->$prop = $arrayVal;
                    } else {
                        $this->lastError = "Property '$prop' assignment ignored! Cannot decode json value '$value'";
                        $this->throwException($this->lastError, 5);
                    }
                } 
            } else if (is_bool($this->$prop)) {
                $this->$prop = $this->isTrue($value);
            } else {
                $propType = gettype($this->$prop);
                $valType  = gettype($value);
                if ($propType == "NULL" || $propType == $valType) {
                    $this->$prop = $value;
                } else if (is_int($this->$prop)) {
                    $valType  = gettype($value);
                    if ($valType == "integer" || $valType == "string" || $valType == "double" || $valType == "array" || $valType == "boolean") {
                        $this->$prop = (int)$value;
                    } else {
                        $this->lastError = "Property '$prop' assignment ignored! Type mismatch (property = integer, value = $valType)!";
                        $this->throwException($this->lastError, 6);
                    }
                } else if (is_float($this->$prop)) {
                    $valType  = gettype($value);
                    if ($valType == "integer" || $valType == "string" || $valType == "double" || $valType == "array" || $valType == "boolean") {
                        $this->$prop = (float)$value;
                    } else {
                        $this->lastError = "Property '$prop' assignment ignored! Type mismatch (property = double, value = $valType)!";
                        $this->throwException($this->lastError, 6);
                    }
                } else {
                    $this->lastError = "Property '$prop' assignment ignored! Type mismatch (property = $propType, value = $valType)!";
                    $this->throwException($this->lastError, 6);
                }
            }	
        } else {
            $this->lastError = "Property '$prop' not exist!";
            $this->throwException($this->lastError, 4);
        }
        return;
    }

    // Getter //
	public function __get(string $prop):mixed {
        if ($prop == "lastError") return $this->lastError;
        return $this->_getProperty($prop);
    }

    // Get properties by array //
    public function massGetter(array $propList):mixed {
        $this->lastError = "";
        $hasError = false;
        foreach($propList as $prop => $def) {
            $elem = $this->_getProperty($prop);
            if ($this->lastError == "") {
                $propList[$prop] = $elem;
            } else {
                $hasError = true;
            }
        }
        if ($hasError) {
            $this->lastError = "Some properties not exist!";
            $this->throwException($this->lastError, 4);
        }
        return $propList;
    }

	// Setter //
	public function __set(string $prop, mixed $value):void {
        $this->_setProperty($prop, $value);
    }

    // Set properties from array //
	public function massSetter(array $propList):array {
        $this->lastError = "";
		$unsetList = [];
        $hasError = false;
		foreach($propList as $prop => $value) {
            $this->_setProperty($prop, $value);
            if ($this->lastError != "") {
                $hasError = true;
                $unsetList[$prop] = $this->lastError;
            }
        }
        if ($hasError) {
            $this->lastError = "Some assignments failed!";
            $this->throwException($this->lastError, 8);
        }
        return $unsetList;
    }            

    // Get array element by path //
    public function getElemByPath(string $prop, string $path = ""):mixed {
        $this->lastError = "";
        if (!property_exists($this, $prop) || !is_array($this->$prop)) {
            $this->lastError = "Property '$prop' not exist or not array!";
            $this->throwException($this->lastError, 9);
            return null;
        }
        
        // Deny write //
        if (in_array($prop, $this->denyRead)) {
            $this->lastError = "Property '$prop' read denied!";
            $this->throwException($this->lastError, 1);
            return null;
        }

        // Find property by path //
        $property = &$this->$prop;
        $pathList = explode("/", $path);
        $cnt = 0;
        foreach($pathList as $pathElem) {
            $cnt++;
            if ($pathElem == "") continue;      // Skip empty path //
            // Read array element //
            $property = &$property[$pathElem] ?? null;
            if ($cnt != count($pathList) && !is_array($property)) {
                $this->lastError = "Path '$pathElem' not exist!";
                $this->throwException($this->lastError, 7);
                return null;
            }
        }
        return $property;
    }

    // Set array element by path //
    public function setElemByPath(string $prop, string $path, mixed $value): void {
        $this->lastError = "";
        if (!property_exists($this, $prop) || !is_array($this->$prop)) {
            $this->lastError = "Property '$prop' not exist or not array!";
            $this->throwException($this->lastError, 9);
            return;
        }
        
        // Deny write //
        if (in_array($prop, $this->denyWrite)) {
            $this->lastError = "Property '$prop' write denied!";
            $this->throwException($this->lastError, 2);
            return;
        }

        // Find property by path //
        $property = &$this->$prop;
        $pathList = explode("/", $path);
        $cnt = 0;
        foreach($pathList as $pathElem) {
            $cnt++;
            if ($pathElem == "") continue;      // Skip empty path //
            // Read array element //
            $property = &$property[$pathElem] ?? null;
            if ($cnt != count($pathList) && !is_array($property)) {
                $this->lastError = "Path '$pathElem' not exist!";
                $this->throwException($this->lastError, 7);
                return;
            }
        }
        $property = $value;
    }

	// Test for truvalue //
	public function isTrue(mixed $value, mixed $matchValue = null):bool {
		if (is_bool($value)) return $value;
		// Return false if value is array and object //
		if (is_array($value) || is_object($value) || is_null($value) || is_callable($value)) return false;   
		// Convert value //
		$strValue = strtolower((string)$value);
        $firstChar = substr($strValue, 0, 1);
        $secondChar = substr($strValue, 1, 1);
        if ($firstChar == 'y' || $firstChar == 't' || $firstChar == 'e' || $firstChar == 'a' || ($firstChar == 'o' && $secondChar != 'f')  || (is_numeric($value) && $strValue != "0")) {
			return true;
		} else if ($matchValue !== null) {
			if (is_array($matchValue)) {
				return in_array($value, $matchValue);
			} else if ($matchValue === $value) {
				return true;
			}
		}	
		return false;
	}

    // Throw exception //
    public function throwException(string $message = "", int $code = 0, ?\Throwable $previous = null):void {
        if ($this->useException) {
            if (!class_exists($this->exceptionClass)) {
                $this->lastError = "Exception class '$this->exceptionClass' not exist!";
                return;
            }
            
            // Get caller information
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $caller = $backtrace[0];
            
            // Create exception with caller's context
            $exception = new $this->exceptionClass($message, $code, $previous);
            
            // Set file and line to match the caller
            $reflection = new \ReflectionObject($exception);
            
            if ($reflection->hasProperty('file')) {
                $prop = $reflection->getProperty('file');
                $prop->setAccessible(true);
                $prop->setValue($exception, $caller['file']);
            }
            
            if ($reflection->hasProperty('line')) {
                $prop = $reflection->getProperty('line');
                $prop->setAccessible(true);
                $prop->setValue($exception, $caller['line']);
            }
            
            throw $exception;
        }
    }
}

