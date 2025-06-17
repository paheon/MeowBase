<?php
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;

// File and Url Class //
class File {

    use ClassBase;

    // Properties //
    protected ?string   $home;                  // Home path

    // Constructor //
    public function __construct(?string $home = null) {
        //$this->denyWrite = array_merge($this->denyWrite, [ 'urlHome' ]);
        $this->setHome($home);
    }   

    // Setter //
    public function setHome(?string $home = null):void {
        if ($home) {
            $this->home = realpath($home);
            $this->home = preg_replace('/[\\\\|\/]+/', '/', $this->home);
            if (substr($this->home, -1) == "/") {   
                $this->home = substr($this->home, 0, -1);
            }
            if (file_exists($this->home)) {
                return;
            }
        } 
        $this->home = null;
    }

    // Set home to current directory //
    public function setHomeToCurrent():void {
        $this->home = getcwd();
    }

    // Build File //
    public function genFile(string $relativePath, array $substituteList = []):string {
        // Substitute variables //
        $out = "";
        $relativePath = trim($relativePath);
        foreach ($substituteList as $key => $value) {
            $relativePath = str_replace("[".$key."]", $value, $relativePath);
        }   

        // Build file path //
        $out = ($this->home ? $this->home."/" : "").$relativePath;
        $out = preg_replace('/[\\\\|\/]+/', '/', $out);

        return $out;
    }

    // Get file path //
    public function getFilePath(string $fileWithPath):string {
        $path_parts = pathinfo($fileWithPath);
        return $path_parts['dirname'] ?? "";
    }

    // Get file name //
    public function getFileName(string $fileWithPath):string {
        $path_parts = pathinfo($fileWithPath);
        return $path_parts['basename'] ?? "";
    }

    // Get file extension //
    public function getFileExt(string $fileWithPath):string {
        $path_parts = pathinfo($fileWithPath);
        return $path_parts['extension'] ?? "";
    }

    // Generate a temp file by path and prefix //
    public function genTempFile(string $path = "", string $prefix = ""):mixed {
        $this->lastError = "";
        $path = (trim($path) === "") ? sys_get_temp_dir() : $path;
        $tmpFile = tempnam($path, $prefix);
        if ($tmpFile === false) {
            $this->lastError = "Cannot create temp file '$tmpFile'!";
            $this->throwException($this->lastError, 1);
            return false;
        }
        return $tmpFile;
    }

    // Create temp file //
    public function tempFile(string &$filePath):mixed {
        $this->lastError = "";
        $tmpFile = tmpfile();
        if ($tmpFile === false) {
            $this->lastError = "Cannot create temp file!";
            $this->throwException($this->lastError, 1);
            return false;
        }
        $filePath = stream_get_meta_data($tmpFile)['uri'];
        return $tmpFile;
    }

    // Convert to string //
    public function __toString():string {
        return $this->home ?? "";
    }
}
