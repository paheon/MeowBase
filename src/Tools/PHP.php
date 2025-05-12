<?php
namespace Paheon\MeowBase\Tools;

// PHP Class //
class PHP {

    // Check if a function is disabled //
    // Return 0 if function is exists, 1 if function not exists, 2 if function exists but disabled //
    public static function chkDisabledFunction(string $funcName):int {
        $allowExec = function_exists($funcName) ? 0 : 1;
        if ($allowExec && function_exists('ini_get')) {
            $disFuncStr = ini_get('disable_functions') ?? "";
            $disFuncArr = explode(',', $disFuncStr);
            if (in_array($function, $disFuncArr)) $allowExec = 1;
        }
        return $allowExec; 
    }
    
}