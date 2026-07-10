<?php
/**
 * File and Url Class //
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;

// File and Url Class //
class File {

    use ClassBase;

    // Properties //
    protected ?string     $home;                  // Home path
    protected ?\ZipArchive $zip = null;          // Zip archive object

    // Constructor //
    public function __construct(?string $home = null) {
        //$this->denyWrite = array_merge($this->denyWrite, [ 'urlHome' ]);
        $this->setHome($home);
        $this->zip = class_exists('ZipArchive') ? new \ZipArchive() : null;
    }   

    // Setter //
    public function setHome(?string $home = null):void {
        if ($home) {
            $resolved = realpath($home);
            if ($resolved === false) {
                $this->home = null;
                return;
            }
            $this->home = $this->trimPath($resolved);
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

    // Trim sperators from path //
    public static function trimPath(string $path):string {
        return preg_replace('/[\\\\|\/]+/', '/', trim($path));
    }

    // Check if path is absolute //
    public static function isAbsolutePath(string $path):bool {
        $path = trim($path);
        return ($path !== '' && $path[0] === '/') || (strlen($path) > 1 && preg_match('/^[a-zA-Z]:/i', $path));
    }

    // Build File with home path //
    public function genFile(string $relativePath, array $substituteList = []):string {
        // Substitute variables //
        $out = "";
        $relativePath = trim($relativePath);
        foreach ($substituteList as $key => $value) {
            $relativePath = str_replace("[".$key."]", $value, $relativePath);
        }   

        // Build file path //
        $out = ($this->home ? $this->home."/" : "").$relativePath;
        $out = $this->trimPath($out);

        return $out;
    }

    // Get file path //
    public static function getFilePath(string $fileWithPath):string {
        $path_parts = pathinfo($fileWithPath);
        return $path_parts['dirname'] ?? "";
    }

    // Get file name //
    public static function getFileName(string $fileWithPath):string {
        $path_parts = pathinfo($fileWithPath);
        return $path_parts['basename'] ?? "";
    }

    // Get file extension //
    public static function getFileExt(string $fileWithPath):string {
        $path_parts = pathinfo($fileWithPath);
        return $path_parts['extension'] ?? "";
    }

    // Remove all files in directory and sub-directories //
    public function removeAll(string $directory = "", bool $removeDir = true, bool $recursive = true, bool $underHome = true):bool {
        $this->lastError = "";

        // Resolve directory path //
        $directory = rtrim($underHome ? $this->genFile($directory) : $this->trimPath($directory), "/");
        if (!$directory || !is_dir($directory)) {
            $this->lastError = "Directory not found: $directory";
            $this->throwException($this->lastError, 1);
            return false;
        } if (is_file($directory)) {
            // Remove single file //
            unlink($directory);
            return true;
        }

        // Remove all files and sub-directories recursively //
        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    if ($removeDir) {
                        rmdir($item->getPathname());
                    }
                } else {
                    unlink($item->getPathname());
                }
            }
        } else {
            $files = glob($directory . "/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Finally, remove the main directory itself //
        if ($removeDir) {
            rmdir($directory);
        }
        return true;
    }

    // Generate a temp file by path and prefix //
    public function uniqueFile(string $path = "", string $prefix = ""):mixed {
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

    // Create temp file (remove the temp file after close) //
    public function tempFile(?string &$filePath = null):mixed {
        $this->lastError = "";
        $tmpFile = tmpfile();
        if ($tmpFile === false) {
            $this->lastError = "Cannot create temp file!";
            $this->throwException($this->lastError, 1);
            return false;
        }
        if (!is_null($filePath)) {
            $filePath = stream_get_meta_data($tmpFile)['uri'];
        }
        return $tmpFile;
    }


    // Zip files or directories into an archive //
    public function zip(string|array $sources, string $destination, bool $overwrite = true): bool {
        $this->lastError = "";

        // Check if ZipArchive class is available //
        if (!$this->zip) {
            $this->lastError = "ZipArchive class is not available. Enable the zip extension.";
            $this->throwException($this->lastError, 2);
            return false;
        }

        // Create destination directory if it doesn't exist //
        $destination = $this->trimPath($destination);
        $destDir = self::getFilePath($destination);
        if ($destDir && !is_dir($destDir)) {
            if (!@mkdir($destDir, 0755, true)) {
                $this->lastError = "Cannot create destination directory: $destDir";
                $this->throwException($this->lastError, 3);
                return false;
            }
        }

        // Create zip file //
        $flags = \ZipArchive::CREATE | ($overwrite ? \ZipArchive::OVERWRITE : 0);
        if ($this->zip->open($destination, $flags) !== true) {
            $this->lastError = "Cannot create or open zip file: $destination";
            $this->throwException($this->lastError, 4);
            return false;
        }

        // Add sources to zip file //
        $sources = is_array($sources) ? $sources : [$sources];
        foreach ($sources as $source) {
            $source = $this->trimPath($source);
            if (!file_exists($source)) {
                $this->lastError = "Source not found: $source";
                $this->zip->close();
                $this->throwException($this->lastError, 5);
                return false;
            }
            if (is_file($source)) {
                $localName = self::getFileName($source);
                if (!$this->zip->addFile($source, $localName)) {
                    $this->lastError = "Cannot add file to zip: $source";
                    $this->zip->close();
                    $this->throwException($this->lastError, 6);
                    return false;
                }
            } else {
                $src = rtrim($source, '/');
                $basePath = $src . '/';
                $baseName = self::getFileName($src);
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $item) {
                    $path = $this->trimPath($item->getPathname());
                    $localPath = $baseName . '/' . substr($path, strlen($basePath));
                    if ($item->isDir()) {
                        $this->zip->addEmptyDir($localPath . '/');
                    } else {
                        $this->zip->addFile($path, $localPath);
                    }
                }
            }
        }
        if (!$this->zip->close()) {
            $this->lastError = "Cannot close zip file: $destination";
            $this->throwException($this->lastError, 7);
            return false;
        }
        return true;
    }

    // Unzip archive to destination directory //
    public function unzip(string $zipFile, string $destination, array|string|null $fileList = null, bool $createDir = true): bool {
        $this->lastError = "";

        // Check if ZipArchive class is available //
        if (!$this->zip) {
            $this->lastError = "ZipArchive class is not available. Enable the zip extension.";
            $this->throwException($this->lastError, 2);
            return false;
        }

        // Resolve paths: use as-is if absolute, otherwise resolve via genFile (home)
        $zipFile = trim($zipFile);
        $destination = rtrim($destination);
        if (!$this->isAbsolutePath($zipFile)) {
            $zipFile = $this->genFile($zipFile);
        } else {
            $zipFile = $this->trimPath($zipFile);
        }
        if (!$this->isAbsolutePath($destination)) {
            $destination = $this->genFile($destination);
        } else {
            $destination = $this->trimPath($destination);
        }
        if (!file_exists($zipFile)) {
            $this->lastError = "Zip file not found: $zipFile";
            $this->throwException($this->lastError, 8);
            return false;
        }

        // Check if zip file is a file //
        if (!is_file($zipFile)) {
            $this->lastError = "Not a file: $zipFile";
            $this->throwException($this->lastError, 9);
            return false;
        }

        // Create destination directory if it doesn't exist //
        if ($createDir && !is_dir($destination)) {
            if (!@mkdir($destination, 0755, true)) {
                $this->lastError = "Cannot create destination directory: $destination";
                $this->throwException($this->lastError, 10);
                return false;
            }
        }

        $errorCode = $this->zip->open($zipFile);
        if ($errorCode !== true) {
            $this->lastError = "Cannot open zip file: $zipFile [Error Code: $errorCode]";
            $this->throwException($this->lastError, 11);
            return false;
        }

        if (!$this->zip->extractTo($destination, $fileList)) {
            $this->zip->close();
            $this->lastError = "Cannot extract zip to: $destination";
            $this->throwException($this->lastError, 12);
            return false;
        }

        $this->zip->close();
        return true;
    }

    // Unzip archive from string //
    public function unzipFromString(string $zipString, string $destination, array|string|null $fileList = null, bool $createDir = true): bool {

        // create a temporary file //
        $tmpFile = $this->uniqueFile("", "zip_");
        if ($tmpFile === false) {
            return false;
        }

        // write the zip string to the temporary file //
        file_put_contents($tmpFile, $zipString);

        // unzip the temporary file //
        $result = $this->unzip($tmpFile, $destination, $fileList, $createDir);
        unlink($tmpFile);
        return $result;
    }

    // Convert to string //
    public function __toString():string {
        return $this->home ?? "";
    }

}
