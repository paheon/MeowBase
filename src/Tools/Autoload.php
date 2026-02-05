<?php
/**
 * Autoload.php - Dynamic Autoload Manager
 * 
 * A utility class to dynamically add or modify autoload rules in PHP.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.1
 * @license MIT
 */
namespace Paheon\MeowBase\Tools;

class Autoload {

    /**
     * Get Composer's ClassLoader instance
     * 
     * @return \Composer\Autoload\ClassLoader|null
     */
    public static function getComposerLoader(): ?\Composer\Autoload\ClassLoader {
        static $loader = null;
        
        if ($loader === null) {
            // First, try to get from already registered Autoloads
            $loaders = spl_autoload_functions();
            foreach ($loaders as $Autoload) {
                if (is_array($Autoload) && 
                    isset($Autoload[0]) && 
                    $Autoload[0] instanceof \Composer\Autoload\ClassLoader) {
                    $loader = $Autoload[0];
                    return $loader;
                }
            }
            
            // If not found, try to load from Composer's autoload
            $projectRoot = self::findProjectRoot();
            if ($projectRoot && file_exists($projectRoot . '/vendor/autoload.php')) {
                // The require statement returns the ClassLoader instance
                // But only on first call, so we check if it's already loaded
                if (!class_exists('Composer\Autoload\ClassLoader', false)) {
                    $loader = require $projectRoot . '/vendor/autoload.php';
                } else {
                    // ClassLoader exists but we need the instance
                    // Try to get it from the autoload_real.php
                    $autoloadRealFile = $projectRoot . '/vendor/composer/autoload_real.php';
                    if (file_exists($autoloadRealFile)) {
                        // Include to get access to the loader instance
                        // The autoload_real.php defines a class that has getLoader() method
                        $hash = md5($projectRoot);
                        $AutoloadClass = 'ComposerAutoload' . $hash;
                        if (class_exists($AutoloadClass)) {
                            $loader = $AutoloadClass::getLoader();
                        }
                    }
                }
            }
        }
        
        return $loader;
    }

    /**
     * Find the project root directory (where composer.json is located)
     * 
     * @return string|null
     */
    private static function findProjectRoot(): ?string {
        $dir = __DIR__;
        $maxDepth = 10; // Prevent infinite loop
        $depth = 0;
        
        while ($depth < $maxDepth) {
            if (file_exists($dir . '/composer.json')) {
                return $dir;
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break; // Reached filesystem root
            }
            $dir = $parent;
            $depth++;
        }
        
        return null;
    }

    /**
     * Add PSR-4 namespace mapping dynamically
     * 
     * @param string $prefix The namespace prefix (e.g., "MyApp\\")
     * @param string|array $paths The path(s) to map to this namespace
     * @param bool $prepend Whether to prepend the path instead of appending
     * @return bool Success status
     */
    public static function addPsr4(string $prefix, string|array $paths, bool $prepend = false): bool {
        $loader = self::getComposerLoader();
        if ($loader) {
            $loader->addPsr4($prefix, $paths, $prepend);
            return true;
        }
        return false;
    }

    /**
     * Add PSR-0 namespace mapping dynamically
     * 
     * @param string $prefix The namespace prefix
     * @param string|array $paths The path(s) to map to this namespace
     * @param bool $prepend Whether to prepend the path instead of appending
     * @return bool Success status
     */
    public static function addPsr0(string $prefix, string|array $paths, bool $prepend = false): bool {
        $loader = self::getComposerLoader();
        if ($loader) {
            $loader->add($prefix, $paths, $prepend);
            return true;
        }
        return false;
    }

    /**
     * Add class map dynamically
     * 
     * @param array $classMap Array of class name => file path mappings
     * @return bool Success status
     */
    public static function addClassMap(array $classMap): bool {
        $loader = self::getComposerLoader();
        if ($loader) {
            $loader->addClassMap($classMap);
            return true;
        }
        return false;
    }

    /**
     * Register a custom autoload function
     * 
     * @param callable $autoloadFunction The autoload function to register
     * @param bool $prepend Whether to prepend this Autoload
     * @return bool Success status
     */
    public static function register(callable $autoloadFunction, bool $prepend = false): bool {
        return spl_autoload_register($autoloadFunction, true, $prepend);
    }

    /**
     * Unregister an autoload function
     * 
     * @param callable $autoloadFunction The autoload function to unregister
     * @return bool Success status
     */
    public static function unregister(callable $autoloadFunction): bool {
        return spl_autoload_unregister($autoloadFunction);
    }

    /**
     * Get all registered autoload functions
     * 
     * @return array Array of registered autoload functions
     */
    public static function getRegisteredLoaders(): array {
        return spl_autoload_functions() ?: [];
    }

    /**
     * Add namespace mapping with automatic path resolution
     * 
     * @param string $namespace The namespace (e.g., "MyApp\\Controllers\\")
     * @param string $basePath The base path relative to project root
     * @return bool Success status
     */
    public static function addNamespace(string $namespace, string $basePath): bool {
        // Ensure namespace ends with backslash
        if (substr($namespace, -1) !== '\\') {
            $namespace .= '\\';
        }

        // Find project root
        $projectRoot = self::findProjectRoot();
        if (!$projectRoot) {
            return false;
        }

        // Convert relative path to absolute
        $absolutePath = realpath($projectRoot . '/' . ltrim($basePath, '/\\'));
        if ($absolutePath === false) {
            // If path doesn't exist, use constructed path
            $absolutePath = $projectRoot . '/' . ltrim($basePath, '/\\');
        }
        
        // Ensure path ends with directory separator
        if (substr($absolutePath, -1) !== DIRECTORY_SEPARATOR) {
            $absolutePath .= DIRECTORY_SEPARATOR;
        }

        return self::addPsr4($namespace, $absolutePath);
    }

}
