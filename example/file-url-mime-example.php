<?php
/**
 * File, Url, and Mime Example
 * 
 * This example demonstrates how to use File, Url, and Mime utility classes
 * for file operations, URL manipulation, and MIME type detection.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.1
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Tools\File;
use Paheon\MeowBase\Tools\Url;
use Paheon\MeowBase\Tools\Mime;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "File, Url, and Mime Example".$br;
echo "==========================================".$br.$br;

// ========== File Class Examples ==========
echo "File Class Examples".$br;
echo "==========================================".$br.$br;

// Example 1: Basic File Operations
echo "Example 1: Basic File Operations".$br;
echo "--------------------------------".$br;

$file = new File();
echo "File object created".$br;
echo "Home path: ".($file->home ?? "null").$br.$br;

// Set home to current directory
$file->setHomeToCurrent();
echo "Home set to current directory: ".$file->home.$br;

// Set home to specific directory
$file->setHome(__DIR__);
echo "Home set to __DIR__: ".$file->home.$br.$br;

// Example 2: Generate File Paths
echo "Example 2: Generate File Paths".$br;
echo "--------------------------------".$br;

// Simple path
$path1 = $file->genFile("test.txt");
echo "Simple path: ".$path1.$br;

// Path with substitution
$path2 = $file->genFile("[type]/[name].[ext]", [
    "type" => "documents",
    "name" => "report",
    "ext" => "pdf"
]);
echo "Path with substitution: ".$path2.$br.$br;

// Example 3: File Path Utilities
echo "Example 3: File Path Utilities".$br;
echo "--------------------------------".$br;

$testFile = __FILE__;
echo "Test file: ".$testFile.$br;
echo "File path: ".$file->getFilePath($testFile).$br;
echo "File name: ".$file->getFileName($testFile).$br;
echo "File extension: ".$file->getFileExt($testFile).$br.$br;

// Example 4: Temporary Files
echo "Example 4: Temporary Files".$br;
echo "--------------------------------".$br;

// Method 1: tempFile (returns file handle)
$tempFilePath = "";
$tempFile = $file->tempFile($tempFilePath);
if ($tempFile !== false) {
    echo "Temporary file created (handle): ".$tempFilePath.$br;
    fwrite($tempFile, "Test content");
    fseek($tempFile, 0);
    $content = fread($tempFile, 1024);
    echo "Content: ".$content.$br;
    fclose($tempFile); // Automatically deletes
    echo "File closed (auto-deleted)".$br;
} else {
    echo "Failed to create temp file: ".$file->lastError.$br;
}
echo $br;

// Method 2: genTempFile (returns file path)
$tempFile2 = $file->genTempFile("", "test_");
if ($tempFile2 !== false) {
    echo "Temporary file created (path): ".$tempFile2.$br;
    file_put_contents($tempFile2, "Test content 2");
    $content2 = file_get_contents($tempFile2);
    echo "Content: ".$content2.$br;
    if (file_exists($tempFile2)) {
        unlink($tempFile2);
        echo "File deleted".$br;
    }
} else {
    echo "Failed to create temp file: ".$file->lastError.$br;
}
echo $br;

// ========== Url Class Examples ==========
echo "Url Class Examples".$br;
echo "==========================================".$br.$br;

// Example 5: Basic URL Operations
echo "Example 5: Basic URL Operations".$br;
echo "--------------------------------".$br;

$url = new Url();
echo "Url object created".$br;
echo "Home URL: ".($url->home ?? "null").$br.$br;

// Set home URL
$url->setHome("https://example.com/app");
echo "Home URL set: ".$url->home.$br.$br;

// Example 6: Generate URLs
echo "Example 6: Generate URLs".$br;
echo "--------------------------------".$br;

// Full URL
$fullUrl = $url->genUrl("users/profile", ["id" => 123, "view" => "full"], "section1", true);
echo "Full URL: ".$fullUrl.$br;

// Relative URL
$relativeUrl = $url->genUrl("users/profile", ["id" => 123], "", false);
echo "Relative URL: ".$relativeUrl.$br;

// URL with fragment
$urlWithFragment = $url->genUrl("page", [], "section1", true);
echo "URL with fragment: ".$urlWithFragment.$br.$br;

// Example 7: Modify URLs
echo "Example 7: Modify URLs".$br;
echo "--------------------------------".$br;

$sourceUrl = "https://example.com/products?category=electronics&sort=price";
echo "Source URL: ".$sourceUrl.$br;

$modifiedUrl = $url->modifyUrl($sourceUrl, [
    "path" => "/services",
    "query" => ["category" => "software", "filter" => "new"]
]);
echo "Modified URL: ".$modifiedUrl.$br.$br;

// Example 8: URL Info (requires internet)
echo "Example 8: URL Info".$br;
echo "--------------------------------".$br;

echo "Getting URL info for https://example.com:".$br;
$urlInfo = $url->urlInfo("https://example.com");
if ($urlInfo !== false) {
    echo "  HTTP Code: ".$urlInfo["http_code"].$br;
    echo "  Content Type: ".$urlInfo["content_type"].$br;
    echo "  Content Length: ".($urlInfo["size_download"] ?? "unknown").$br;
} else {
    echo "  Failed: ".$url->lastError.$br;
}
echo $br;

// ========== Mime Class Examples ==========
echo "Mime Class Examples".$br;
echo "==========================================".$br.$br;

// Example 9: Basic MIME Operations
echo "Example 9: Basic MIME Operations".$br;
echo "--------------------------------".$br;

$mime = new Mime();
echo "Mime object created".$br;
echo "Globs2 file: ".$mime->globs2File.$br;
echo "Aliases file: ".$mime->aliasesFile.$br;
echo "Generic icons file: ".$mime->genericIconsFile.$br.$br;

// Example 10: File to MIME Type
echo "Example 10: File to MIME Type".$br;
echo "--------------------------------".$br;

$testFile = __FILE__;
echo "Testing file: ".$testFile.$br;
$mimeType = $mime->file2Mime($testFile);
if ($mimeType !== false) {
    echo "MIME type: ".$mimeType.$br;
} else {
    echo "Failed: ".$mime->lastError.$br;
}
echo $br;

// Test with file extension (file doesn't exist)
$virtualFile = "document.pdf";
echo "Testing virtual file: ".$virtualFile.$br;
$mimeType2 = $mime->file2Mime($virtualFile);
if ($mimeType2 !== false) {
    echo "MIME type: ".$mimeType2.$br;
} else {
    echo "Failed: ".$mime->lastError.$br;
}
echo $br;

// Example 11: MIME to Icon
echo "Example 11: MIME to Icon".$br;
echo "--------------------------------".$br;

if ($mimeType !== false) {
    echo "Getting icon for MIME type: ".$mimeType.$br;
    $icon = $mime->mime2Icon($mimeType);
    if ($icon !== false) {
        echo "Icon: ".$icon.$br;
    } else {
        echo "Failed: ".$mime->lastError.$br;
    }
}
echo $br;

// Example 12: Alias to MIME
echo "Example 12: Alias to MIME".$br;
echo "--------------------------------".$br;

$alias = "text/plain";
echo "Getting MIME type for alias: ".$alias.$br;
$mimeFromAlias = $mime->alias2Mime($alias);
if ($mimeFromAlias !== false) {
    echo "MIME type: ".$mimeFromAlias.$br;
} else {
    echo "Failed: ".$mime->lastError.$br;
}

// Reverse lookup
echo "Reverse lookup (MIME to alias):".$br;
$aliasFromMime = $mime->alias2Mime($mimeType, true);
if ($aliasFromMime !== false) {
    echo "Alias: ".$aliasFromMime.$br;
} else {
    echo "Failed: ".$mime->lastError.$br;
}
echo $br;

// Example 13: Debug Information
echo "Example 13: Debug Information".$br;
echo "--------------------------------".$br;

$fileDebug = $file->__debugInfo();
echo "File debug info:".$br;
echo "  home: ".var_export($fileDebug['home'], true).$br.$br;

$urlDebug = $url->__debugInfo();
echo "Url debug info:".$br;
echo "  home: ".var_export($urlDebug['home'], true).$br;
echo "  fullUrl: ".var_export($urlDebug['fullUrl'], true).$br.$br;

$mimeDebug = $mime->__debugInfo();
echo "Mime debug info:".$br;
echo "  globs2File: ".$mimeDebug['globs2File'].$br;
echo "  aliasesFile: ".$mimeDebug['aliasesFile'].$br;
echo "  genericIconsFile: ".$mimeDebug['genericIconsFile'].$br.$br;

echo "Example completed!".$br;
