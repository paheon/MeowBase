<?php
//
// SysLog.php - System Logger class
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
namespace Paheon\MeowBase;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class SysLog extends Logger{

    // Properties //
    public	bool	$enable = true;	// Enable Log by default
    public	bool	$stack	= false;	// Show full tracking stack
    public	int		$depth	= 0;		// Tracking stack depth
    
    // Constructor //
    public function __construct(string $logDirectory, string $logLevelThreshold = LogLevel::DEBUG, array $options = []) {
        parent::__construct($logDirectory, $logLevelThreshold, $options);
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

    // Set log level //
    public function setLogLevel(string$level): void {
        if (in_array($level, $this->logLevels)) {
            $this->logLevelThreshold = $level;
        }
    }

    // Get log level //
    public function getLogLevel(): string {
        return $this->logLevelThreshold;
    }
}
