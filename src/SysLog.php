<?php
/**
 * SysLog Class
 * 
 * This class is used to manage the system logger.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.2
 * @license MIT
 * @package Paheon\MeowBase
 */
namespace Paheon\MeowBase;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;
use Paheon\MeowBase\ClassBase;

class SysLog extends Logger{

    use ClassBase;

    const DEBUG = LogLevel::DEBUG;
    const INFO = LogLevel::INFO;
    const WARNING = LogLevel::WARNING;
    const ERROR = LogLevel::ERROR;
    const CRITICAL = LogLevel::CRITICAL;
    const ALERT = LogLevel::ALERT;
    const EMERGENCY = LogLevel::EMERGENCY;

    // Properties //
    public	bool	$enable = true;	// Enable Log by default
    public	bool	$stack	= false;	// Show full tracking stack
    public	int		$depth	= 0;		// Tracking stack depth
    
    // Constructor //
    public function __construct(string $logDirectory, string $logLevelThreshold = LogLevel::DEBUG, array $options = []) {
        parent::__construct($logDirectory, $logLevelThreshold, $options);
        $this->denyWrite = array_merge($this->denyWrite, [ 'logLevelThreshold', 'options', 'logFilePath', 'logLevels' ]);
    }
    
    // Log message with tracking //
    public function sysLog(string $msg, ?array $context = null, string $level = LogLevel::DEBUG): void {
        if (!$this->enable || !isset($this->logLevels[$level])) {
            return;
        }
        // Get caller //
        $trace = debug_backtrace(0, ($this->depth >= 0) ? $this->depth : 0);
    
        // Compose log message //
        $out = $this->formatTrace($trace);
        $out .= "\n" . $msg;
        $this->log($level, $out, $context ?? []);
    }

    // Format caller //
    private function formatTrace(array $trace): string {
        $out = "";
        foreach ($trace as $level => $caller) {
            if ($level === 0) {
                $out .= "Called from Line: {$caller['line']} Function: {$caller['function']} (File: {$caller['file']})";
            } elseif ($this->stack) {
                $out .= "\n#{$level} line: {$caller['line']} Function: {$caller['function']} (File: {$caller['file']})";
            } else {
                break;
            }
        }
        return $out;
    }

    // Set log threshold level //
    public function setThreshold(string $level): void {
        if (isset($this->logLevels[$level])) {
            $this->logLevelThreshold = $level;
        }
    }

    // Get log threshold level //
    public function getThreshold(): string {
        return $this->logLevelThreshold;
    }

}
