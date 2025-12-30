<?php
/**
 * Url Class
 * 
 * This class is used to manage URLs.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;

// File and Url Class //
class Url  {

    use ClassBase;
    
    // Properties //
    protected ?string   $home;              // Home base Url
    protected bool      $fullUrl;           // Default not to use full Url

    // Constructor //
    public function __construct(?string $home = null, bool $fullUrl = false) {
        //$this->denyWrite = array_merge($this->denyWrite, [ 'home' ]);
        $this->setHome($home);
        $this->fullUrl = $fullUrl;
    }


    // Setter //
    public function setHome(?string $home = null):void {
        if ($home) {
            // Recons
            $url = parse_url($home);
            $this->home  = isset($url["scheme"])   ? $url["scheme"]."://"  : "";
            $this->home .= isset($url["host"])     ? $url["host"]          : "";
            $this->home .= isset($url["port"])     ? ":".$url["port"]      : "";
            if (isset($url["path"])) {
                $this->home .= preg_replace('/[\\\\]|[\/]{2,}/', '/', $url["path"]);
            }
        } else {
            $this->home = null;
        }
    }

    // Build Url //
    public function genUrl(string $path, array $query = [], string $fragment = "", ?bool $fullUrl = null):string {
        $fullUrl = $fullUrl ?? $this->fullUrl;
        if ($fullUrl && $this->home) {
            $path = preg_replace('/[\\\\]|[\/]{2,}/', '/', $path);
            $path = $this->home.((substr($path, 0, 1) != "/") ? "/" : "").$path;
        }
        $path .= (count($query) > 0) ? "?".http_build_query($query) : "";
        $path .= ($fragment) ? "#".$fragment : "";
        return $path;
    }

    // Modify Url //
    public function modifyUrl(string $srcUrl, array $replace):string {

        $url = parse_url($srcUrl);

        // Re-buld url //
        $out = "";

        if (isset($url["scheme"]) || isset($replace["scheme"])) {
            $out = ($replace["scheme"] ?? $url["scheme"])."://";
        }    
        if (isset($url["user"]) || isset($replace["user"])) {
            $out .= $option["user"] ?? $url["user"];
            if (isset($url["pass"]) || isset($replace["pass"])) {
                $out .= ":".($replace["pass"] ?? $url["pass"]);
            }
            $out .= "@";
        }
        if (isset($url["host"]) || isset($replace["host"])) {
            $out .= $replace["host"] ?? $url["host"];
        }    
        if (isset($url["port"]) || isset($replace["port"])) {
            $out .= ":".($replace["port"] ?? $url["port"]);
        }
        
        if (isset($url["path"]) || isset($replace["path"])) {
            $out .= $replace["path"] ?? $url["path"];
        }
    
        if (isset($url["query"])) {
            if (isset($replace["query"])) {
                parse_str($url["query"], $queryList);
                $queryList = array_replace($queryList, $replace["query"]);
                $out .= "?".http_build_query($queryList);
            } else {
                $out .= "?".$url["query"];
            }
        } else {
            if (isset($replace["query"])) {
                $out .= "?".http_build_query($replace["query"]);
            }
        }    

        if (isset($url["fragment"]) || isset($replace["fragment"])) {
            $out .= "#".$replace["fragment"] ?? $url["fragment"];
        }

        return $out;
    } 

    // Get url info //
    public function urlInfo(string $url, array $curlOptList = []):array|false {
        $this->lastError = "";
        $header = "";
        // Prepare to load header from url //
        $curl = curl_init($url);
        if ($curl === false) {
            $this->lastError = "Cannot initialize curl!";
            $this->throwException($this->lastError, 1);
            return false;
        }
        $curlDefOptList = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.3",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];
        $optList = array_replace($curlDefOptList, $curlOptList);
        foreach($optList as $opt => $value) {
            curl_setopt($curl, $opt, $value);
        }
        $header = curl_exec($curl);
        if ($header === false) {
            $this->lastError = "Cannot get header from url '$url'! Error(".curl_errno($curl)."): ".curl_error($curl);
            curl_close($curl);
            $this->throwException($this->lastError, 2);
            return false;
        }
        $result = curl_getinfo($curl);
        curl_close($curl);
        return $result;
    }

    // Convert to string //
    public function __toString():string {
        return $this->home ?? "";
    }
}
