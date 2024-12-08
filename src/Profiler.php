<?php
//
// Profiler.php - Profiler class
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
namespace Paheon\MeowBase;

class Profiler {

    // Properties //
    protected int     $serial = 0;
    protected array   $timeRec = [];
    protected int     $zeroPad;

    // Constructor //
    function __construct(int $zeroPad = 5) {
        $this->zeroPad = $zeroPad;
        $this->timeRec['all'] = [];
        $prgStartTime = $GLOBALS['prgStartTime'] ?? null;;
        if (isset($_SERVER["REQUEST_TIME_FLOAT"])) {
            // For Web mode only
            $this->record("Request Init", "all", $_SERVER["REQUEST_TIME_FLOAT"]);
        } 
        if (!is_null($prgStartTime)) {
            // Web and CLI mode
            $this->record("Application Init", "all", $prgStartTime);
        } else {
            // No program start time set
            $this->record("Profiler Init", "all");
        }
    }

    // timeRec Time Compare //
    private function timeCmp($a, $b) {
        if ($a == $b) return 0;
        return ($a < $b) ? -1 : 1;
    }
    
    // Record timeRec //
    public function record(string $tag, string $group = 'all', ?string $forceTime = null) {
        $m_time = $forceTime ?? microtime(true);
        $tag = str_pad(++$this->serial, $this->zeroPad, "0", STR_PAD_LEFT)." ".$tag;
        $this->timeRec[$group][$tag] = $m_time;
        if ($group != 'all') {
            $this->timeRec['all'][$tag] = $m_time;
        }
    }    

    // Performance Report //
    public function report(bool $nlbr = false) {
        $out = "Performance Report:\n-------------------\n";
        if (count($this->timeRec['all']) > 0) {            
            foreach($this->timeRec as $group => $performList) {
                $out .= "Group: $group\n";
                uasort($performList, array($this, 'timeCmp'));
                $lastTime   = -1;
                $startTime  = reset($performList);
                $endTime    = end($performList);
                $totalTime  = $endTime - $startTime;
                $keys       = array_keys($performList);
                $strLen     = array_map('strlen', $keys);
                $maxStrLen  = max($strLen);
                
                // Calculate duration //
                foreach($performList as $tag => $time) {
                    if ($lastTime < 0) {
                        $lastTime = $time;
                    }
                    $duration = $time - $lastTime;
                    $ratio = ($totalTime > 0) ? $duration / $totalTime * 100 : false;

                    $out .= str_pad($tag.":", $maxStrLen+1);
                    $out .= " time=".number_format($time, 4)."s";
                    $out .= " duration=".number_format($duration, 4)."s";
                    if (is_float($ratio)) {
                        $out .= " ratio=".number_format($ratio, 2)."%";
                    } else {
                        $out .= " ratio=N/A";
                    }    
                    $out .= "\n";    
                    $lastTime = $time;
                }
                $out .= "Total duration=".number_format($totalTime, 4)."s\n\n";
            }
        } else {
            $out = "No performance record!";
        }
        
        // nl to br convertion //
        if ($nlbr) {
            $out = nl2br($out);
        }
        return $out;
    }
}