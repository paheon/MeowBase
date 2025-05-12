<?php
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;

// File and Url Class //
class Mime extends ClassBase {

    // Properties //

    // Shared MIME-Info database // 
    // For windows, we need to obtain the files, globs, aliases, and generic-icons, for non-exist files and set the path in config file.
    // Details about Shared MIME-Info database, please see: https://www.freedesktop.org/wiki/Specifications/shared-mime-info-spec/
    protected string    $globs2File  = "";                 // MIME globs2 file from Shared MIME-Info database (globs is also accepted)
    protected string    $aliasesFile   = "";               // MIME icons file from Shared MIME-Info database
    protected string    $genericIconsFile = "";            // MIME generic icons file from Shared MIME-Info database

    protected array     $globs2 = [];                      // Data from globs2 file 
    protected array     $aliases = [];                     // Data from icons file
    protected array     $genericIcons = [];                // Data from generic icons file

    // Constructor //
    public function __construct(string $globs2File = "/usr/share/mime/globs2", string $aliasesFile = "/usr/share/mime/aliases", string $genericIconsFile = "/usr/share/mime/generic-icons") {
        $this->denyWrite = array_merge($this->denyWrite, [ 'globs2', 'aliases', 'genericIcons' ]);
        $this->globs2File = $globs2File;
        $this->aliasesFile = $aliasesFile;
        $this->genericIconsFile = $genericIconsFile;
    }   

    // Load Mime Data //
    private function loadMimeData(string $file, array &$data, bool $extractExt = false, string $sep = ":"):bool {
        $this->lastError = "";
        if (!$data) {
            $fileError = "";
            set_error_handler(function($errno, $errstr, $errfile, $errline) use(&$fileError) {
                $fileError = "(Error:$errno)$errstr";
            });
            if (!file_exists($file)) {
                if ($fileError != "") {
                    $this->lastError = "File '".$file."' read error: ".$fileError;
                } else {
                    $this->lastError = "File '".$file."' not found!";
                }
                restore_error_handler();
                $this->throwException($this->lastError, 1);
                return false;
            }
            $fileError = "";
            $srcdata = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            restore_error_handler();
            if (!$srcdata) {
                $this->lastError = "Cannot read file '".$file."'! ".$fileError;
                return false;
            }
            foreach ($srcdata as $line) {
                $line = trim($line);
                // Skip comments
                if (substr($line, 0, 1) == "#") {
                    continue;
                }
                // Get data from globs2 / globs //
                $partList = explode($sep, $line);
                $cnt = count($partList);
                if ($cnt == 3 || $cnt == 2) {
                    if ($extractExt) {
                        $ext = substr($partList[$cnt - 2], 2);
                        $data[$ext] = $partList[$cnt - 1];
                    } else {
                        $data[$partList[0]] = $partList[1];
                    }
                }
            }
        }
        return true;
    }
    
    // Get Mime type from file //
    public function file2Mime(mixed $file):string|false {
        $this->lastError = "";
        $mime = false;
        if (is_resource($file) || file_exists($file)) {

            $mime = @mime_content_type($file);
            if ($mime === false) {
                $this->lastError = "Cannot resolve mime type by mime_content_type() function!";
                $this->throwException($this->lastError, 2);
            }
        } else {
            // When file not exists, try to find mime type from aliases
            if ($this->loadMimeData($this->globs2File, $this->globs2, true)) {
                $mime = $this->globs2[pathinfo($file, PATHINFO_EXTENSION)] ?? false;
                if ($mime === false) {
                    $this->lastError = "Cannot resolve mime type by globs/globs2 file!";
                    $this->throwException($this->lastError, 3);
                }
            } 
        }
        return $mime;
    }

    // Get the generic icon from file name //
    public function mime2Icon(string $mime):string|false {
        $this->lastError = "";
        $icon = false;
        if ($this->loadMimeData($this->genericIconsFile, $this->genericIcons)) {
            $icon = $this->genericIcons[$mime] ?? false;
            if ($icon === false) {
                $this->lastError = "Cannot resolve icon by generic-icons!";
                $this->throwException($this->lastError, 4);
            }
        } 
        return $icon;
    }
	
    // Get the Mime type from file name //
    public function alias2Mime(string $alias, bool $reverse = false):string|false {
        $this->lastError = "";
        $mime = false;
        if ($this->loadMimeData($this->aliasesFile, $this->aliases, false, " ")) {
            if ($reverse) {
                $mime = array_search($alias, $this->aliases);
            } else {
                $mime = $this->aliases[$alias] ?? false;
            }
            if ($mime === false) {
                $this->lastError = "Cannot resolve mime type by aliases!";
                $this->throwException($this->lastError, 5);
            }
        } 
        return $mime;
    }
}
