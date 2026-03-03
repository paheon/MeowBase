<?php
/**
 * JsonAPI.php - JSON API Class
 * 
 * A class to handle JSON API requests and responses.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */
namespace Paheon\MeowBase\Tools;
use Paheon\MeowBase\MeowBase;
use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\SysLog;

class JsonAPI {

    use ClassBase;

    // Method List //
    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const METHOD_PUT = "PUT";
    const METHOD_DELETE = "DELETE";
    const METHOD_PATCH = "PATCH";
    const METHOD_OPTIONS = "OPTIONS";
    const METHOD_HEAD = "HEAD";
    const METHOD_CONNECT = "CONNECT";
    const METHOD_TRACE = "TRACE";
    const METHOD_MERGE = "MERGE";

    const DEFAULT_API_KEY_HEADER = 'x-api-key';

    protected   MeowBase $meow;

    protected   string    $apiHost;
    protected   ?Url      $apiURL = null;
    protected   ?string   $apiKey = null;
    protected   string    $apiKeyHeader = '';
    protected   ?string   $userAgent = null;
    protected   int       $timeout;
    protected   int       $maxRetry;
    protected   string    $encoding;
    protected   bool      $followLocation;
    protected   string    $httpVersion;
    protected   bool      $sslVerifyPeer;
    protected   bool      $sslVerifyHost;

    public function __construct(MeowBase $meow, array $config = []) {
        $this->meow = $meow;
        $this->denyWrite = array_merge($this->denyWrite, ['apiURL']);
        $this->setApiHost($config['apiHost'] ?? '');
        $this->setApiKey($config['apiKey'] ?? null);
        $this->userAgent = $config['userAgent'] ?? null;
        $this->timeout = intval($config['timeout'] ?? 30);
        $this->maxRetry = intval($config['maxRetry'] ?? 10);
        $this->encoding = $config['encoding'] ?? '';
        $this->followLocation = boolval($config['followLocation'] ?? true);
        $this->httpVersion = $config['httpVersion'] ?? CURL_HTTP_VERSION_1_1;
        $this->sslVerifyPeer = boolval($config['sslVerifyPeer'] ?? false);
        $this->sslVerifyHost = boolval($config['sslVerifyHost'] ?? false);
        $this->apiKeyHeader = $config['apiKeyHeader'] ?? self::DEFAULT_API_KEY_HEADER;
    }

    // Set API host //
    public function setApiHost(string $apiHost): void {
        $this->apiHost = $apiHost;
        $this->apiURL = new Url($this->apiHost, true);
    }

    // Simple setter for API key operations (e.g. encryption, etc.)//
    public function setApiKey(?string $apiKey): void {
        if (!is_null($apiKey)) {
            $apiKey = hash('sha256', $apiKey);
        }
        $this->apiKey = $apiKey;
        if (!$this->apiKeyHeader) {
            $this->apiKeyHeader = self::DEFAULT_API_KEY_HEADER;
        }
    }

    // Compare API key with user's API key //
    public function compareApiKey(): bool {
        $apiKey = $this->getUserApiKey();
        return ($apiKey) ? $this->apiKey === $apiKey : false;
    }

    // Get user's API key from headers //
    public function getUserApiKey(): ?string {
        $headerList = getallheaders();
        return $headerList[$this->apiKeyHeader] ?? null;
    }

    // Send API Response //
    public function response(string|array $data = [], string|array $header = 'Content-Type: application/json', bool $die = true): void {
        if ($header) {
            if (is_array($header)) {
                foreach ($header as $value) {
                    header($value);
                }    
            } else {
                header($header);
            }
        }
        if (is_array($data)) {
            $data = json_encode($data);
        }
        echo $data;
        if ($die) die(0);
    }
    // Get Page Content by URL //
    public function request(string $path = '', string $method = self::METHOD_GET, string|array $params = "", string|array $headers = []): ?array {
        // Get API configuration //
        $url = $this->apiURL->genUrl($path);
        if (is_array($params)) {
            $fieldList = http_build_query($params);
        } else {
            $fieldList = $params;
        }
        $postField = false;
        if ($method == self::METHOD_GET || $method == self::METHOD_HEAD) {
            $url = $url . "?" . $fieldList;
        } else {
            $postField = true;
        }
        $curlOptList = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,             // Return the response as a string
            CURLOPT_HEADER         => true,             // Get header information
            CURLOPT_ENCODING       => $this->encoding,
            CURLOPT_MAXREDIRS      => $this->maxRetry,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => $this->followLocation,
            CURLOPT_HTTP_VERSION   => $this->httpVersion,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerifyPeer,
            CURLOPT_SSL_VERIFYHOST => $this->sslVerifyHost,
        ];
        if ($postField) {
            $curlOptList[CURLOPT_POST] = true;
            $curlOptList[CURLOPT_POSTFIELDS] = $fieldList;
        }

        if ($this->userAgent) {
            $curlOptList[CURLOPT_USERAGENT] = $this->userAgent;
        }
        if ($headers) {
            if (!is_array($headers)) {
                $headers = [$headers];
            }
        }
        if ($this->apiKey && $this->apiKeyHeader) {
            $headers[] = $this->apiKeyHeader.': '.$this->apiKey;
        }
        if ($headers) {
            $curlOptList[CURLOPT_HTTPHEADER] = $headers;
        }

        // Initialize curl //
        $ch = curl_init();
        curl_setopt_array($ch, $curlOptList);

        // By default, these are true and should remain so for security:
        $response = curl_exec($ch);
        $curlError = curl_errno($ch);
        $curlMsg   = curl_error($ch);
        curl_close($ch);

        // Check if curl error occurred //
        if (curl_errno($ch)) {
            $this->lastError = "Curl Error: $curlMsg($curlError)";
            $this->meow->log->sysLog(__METHOD__ . " - " . $this->lastError, null, SysLog::ERROR);
            $this->throwException($this->lastError, 1);
            return null;
        } 

        // Get response header and body //
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        return [ "header" => $header, "body" => $body ];
    }
}   