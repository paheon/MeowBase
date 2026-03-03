<?php
/**
 * JsonAPI Example
 * 
 * This example demonstrates how to use the JsonAPI class for:
 * - Making API requests (Client mode)
 * - Handling API requests and sending responses (Server mode)
 * - API key authentication
 * - Various HTTP methods (GET, POST, PUT, DELETE)
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\MeowBase;
use Paheon\MeowBase\Tools\JsonAPI;
use Paheon\MeowBase\Tools\PHP;

// Determine Web or CLI
$isWeb = !PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

// Initialize MeowBase
$config = new Config();
$meow = new MeowBase($config);

echo "JsonAPI Example".$br;
echo "==========================================".$br.$br;

// ========== Part 1: JsonAPI as API Client ==========
echo "Part 1: JsonAPI as API Client".$br;
echo "==========================================".$br.$br;

// Example 1: Basic Configuration
echo "Example 1: Basic JsonAPI Client Setup".$br;
echo "--------------------------------".$br;

$apiConfig = [
    'apiHost' => 'https://jsonplaceholder.typicode.com',
    'timeout' => 30,
    'maxRetry' => 3,
    'followLocation' => true,
    'sslVerifyPeer' => false,
    'sslVerifyHost' => false,
];

$jsonAPI = new JsonAPI($meow, $apiConfig);
echo "JsonAPI client created".$br;
echo "API Host: ".$jsonAPI->apiHost.$br;
echo "Timeout: ".$jsonAPI->timeout." seconds".$br;
echo "Max Retry: ".$jsonAPI->maxRetry.$br.$br;

// Example 2: GET Request
echo "Example 2: GET Request (Fetch Posts)".$br;
echo "--------------------------------".$br;

$response = $jsonAPI->request('/posts/1', JsonAPI::METHOD_GET);
if ($response !== null) {
    echo "Response received successfully".$br;
    echo "Header length: ".strlen($response['header'])." bytes".$br;
    echo "Body length: ".strlen($response['body'])." bytes".$br;
    echo "Body preview:".$br;
    $data = json_decode($response['body'], true);
    if ($data) {
        echo "Title: ".$data['title'].$br;
        echo "User ID: ".$data['userId'].$br;
        echo "Post ID: ".$data['id'].$br;
    }
} else {
    echo "Error: ".$jsonAPI->lastError.$br;
}
echo $br;

// Example 3: GET Request with Parameters
echo "Example 3: GET Request with Query Parameters".$br;
echo "--------------------------------".$br;

$params = [
    'userId' => 1,
];

$response = $jsonAPI->request('/posts', JsonAPI::METHOD_GET, $params);
if ($response !== null) {
    echo "Response received successfully".$br;
    $data = json_decode($response['body'], true);
    if ($data && is_array($data)) {
        echo "Found ".count($data)." posts for user ID 1".$br;
        echo "First post title: ".$data[0]['title'].$br;
    }
} else {
    echo "Error: ".$jsonAPI->lastError.$br;
}
echo $br;

// Example 4: POST Request
echo "Example 4: POST Request (Create New Post)".$br;
echo "--------------------------------".$br;

$postData = [
    'title' => 'Test Post from MeowBase',
    'body' => 'This is a test post created using JsonAPI class',
    'userId' => 1,
];

$response = $jsonAPI->request('/posts', JsonAPI::METHOD_POST, $postData);
if ($response !== null) {
    echo "POST request successful".$br;
    $data = json_decode($response['body'], true);
    if ($data) {
        echo "Created post ID: ".$data['id'].$br;
        echo "Title: ".$data['title'].$br;
    }
} else {
    echo "Error: ".$jsonAPI->lastError.$br;
}
echo $br;

// Example 5: PUT Request
echo "Example 5: PUT Request (Update Post)".$br;
echo "--------------------------------".$br;

$updateData = [
    'id' => 1,
    'title' => 'Updated Title',
    'body' => 'Updated body content',
    'userId' => 1,
];

$response = $jsonAPI->request('/posts/1', JsonAPI::METHOD_PUT, $updateData);
if ($response !== null) {
    echo "PUT request successful".$br;
    $data = json_decode($response['body'], true);
    if ($data) {
        echo "Updated post ID: ".$data['id'].$br;
        echo "New title: ".$data['title'].$br;
    }
} else {
    echo "Error: ".$jsonAPI->lastError.$br;
}
echo $br;

// Example 6: DELETE Request
echo "Example 6: DELETE Request (Delete Post)".$br;
echo "--------------------------------".$br;

$response = $jsonAPI->request('/posts/1', JsonAPI::METHOD_DELETE);
if ($response !== null) {
    echo "DELETE request successful".$br;
    echo "Response: ".$response['body'].$br;
} else {
    echo "Error: ".$jsonAPI->lastError.$br;
}
echo $br;

// Example 7: Custom Headers
echo "Example 7: Request with Custom Headers".$br;
echo "--------------------------------".$br;

$customHeaders = [
    'X-Custom-Header: CustomValue',
    'Accept: application/json',
];

$response = $jsonAPI->request('/posts/1', JsonAPI::METHOD_GET, '', $customHeaders);
if ($response !== null) {
    echo "Request with custom headers successful".$br;
    $data = json_decode($response['body'], true);
    if ($data) {
        echo "Post title: ".$data['title'].$br;
    }
} else {
    echo "Error: ".$jsonAPI->lastError.$br;
}
echo $br;

// ========== Part 2: JsonAPI with API Key Authentication ==========
echo "Part 2: JsonAPI with API Key Authentication".$br;
echo "==========================================".$br.$br;

// Example 8: Setup API with API Key
echo "Example 8: API Client with API Key".$br;
echo "--------------------------------".$br;

$secureApiConfig = [
    'apiHost' => 'https://api.example.com',
    'apiKey' => 'my-secret-api-key-12345',
    'apiKeyHeader' => 'X-API-Key',
    'timeout' => 30,
];

$secureAPI = new JsonAPI($meow, $secureApiConfig);
echo "Secure API client created".$br;
echo "API Key configured: ".($secureAPI->apiKey ? "Yes (hashed)" : "No").$br;
echo "API Key Header: ".$secureAPI->apiKeyHeader.$br;
echo "Note: When making requests, the API key will be automatically included in headers".$br;
echo $br;

// Example 9: Compare API Keys (Server-side usage)
echo "Example 9: API Key Comparison (Server-side)".$br;
echo "--------------------------------".$br;

// Simulate setting an API key for server
$serverAPI = new JsonAPI($meow, ['apiKey' => 'my-secret-key']);
echo "Server API key set".$br;

// In a real scenario, the client would send this key in headers
// Here we simulate by manually setting the header (for demonstration only)
// In production, getallheaders() would automatically capture the client's headers
echo "Note: In production, use \$serverAPI->compareApiKey() to validate client's API key".$br;
echo "The client's API key is retrieved from request headers using getUserApiKey()".$br;
echo $br;

// ========== Part 3: JsonAPI as API Server ==========
echo "Part 3: JsonAPI as API Server (Response Examples)".$br;
echo "==========================================".$br.$br;

// Example 10: Sending JSON Response
echo "Example 10: Sending JSON Response".$br;
echo "--------------------------------".$br;

$serverAPI = new JsonAPI($meow);

// Prepare response data
$responseData = [
    'status' => 'success',
    'message' => 'Data retrieved successfully',
    'data' => [
        'id' => 123,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ],
    'timestamp' => time(),
];

echo "Response data prepared:".$br;
echo "Note: In production, you would call \$serverAPI->response(\$responseData) to send the response".$br;
echo "Example response structure:".$br;
echo json_encode($responseData, JSON_PRETTY_PRINT).$br;
echo $br;

// Example 11: Sending Error Response
echo "Example 11: Sending Error Response".$br;
echo "--------------------------------".$br;

$errorResponse = [
    'status' => 'error',
    'message' => 'Resource not found',
    'error_code' => 404,
    'timestamp' => time(),
];

echo "Error response prepared:".$br;
echo json_encode($errorResponse, JSON_PRETTY_PRINT).$br;
echo "Note: You can also send custom HTTP status headers with the response".$br;
echo $br;

// Example 12: Custom Response Headers
echo "Example 12: Sending Response with Custom Headers".$br;
echo "--------------------------------".$br;

$customResponseHeaders = [
    'Content-Type: application/json',
    'X-API-Version: 1.3.3',
    'X-RateLimit-Remaining: 100',
    'Access-Control-Allow-Origin: *',
];

echo "Custom headers prepared:".$br;
foreach ($customResponseHeaders as $header) {
    echo "  ".$header.$br;
}
echo "Note: Pass headers array as second parameter to response() method".$br;
echo $br;

// ========== Part 4: Complete API Server Example ==========
echo "Part 4: Complete API Server Implementation Pattern".$br;
echo "==========================================".$br.$br;

echo "Example 13: API Server Pattern".$br;
echo "--------------------------------".$br;
echo "Here's a typical pattern for implementing an API server:".$br.$br;

echo "<?php".$br;
echo "// api.php - Your API endpoint file".$br.$br;
echo "// 1. Initialize JsonAPI".$br;
echo "\$apiConfig = ['apiKey' => 'your-secret-key'];".$br;
echo "\$api = new JsonAPI(\$meow, \$apiConfig);".$br.$br;
echo "// 2. Check API key authentication".$br;
echo "if (!\$api->compareApiKey()) {".$br;
echo "    \$api->response(['status' => 'error', 'message' => 'Invalid API key'], 'HTTP/1.1 401 Unauthorized');".$br;
echo "    exit;".$br;
echo "}".$br.$br;
echo "// 3. Get request method and route".$br;
echo "\$method = \$_SERVER['REQUEST_METHOD'];".$br;
echo "\$route = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);".$br.$br;
echo "// 4. Handle different routes".$br;
echo "if (\$route === '/api/users' && \$method === 'GET') {".$br;
echo "    \$users = getUsersFromDatabase();".$br;
echo "    \$api->response(['status' => 'success', 'data' => \$users]);".$br;
echo "} elseif (\$route === '/api/user' && \$method === 'POST') {".$br;
echo "    \$input = json_decode(file_get_contents('php://input'), true);".$br;
echo "    \$result = createUser(\$input);".$br;
echo "    \$api->response(['status' => 'success', 'data' => \$result]);".$br;
echo "} else {".$br;
echo "    \$api->response(['status' => 'error', 'message' => 'Route not found'], 'HTTP/1.1 404 Not Found');".$br;
echo "}".$br;
echo $br;

// ========== Summary ==========
echo "Summary".$br;
echo "==========================================".$br;
echo "JsonAPI class provides:".$br;
echo "- Client mode: Make API requests with full HTTP method support".$br;
echo "- Server mode: Handle API requests and send JSON responses".$br;
echo "- API key authentication (SHA-256 hashed)".$br;
echo "- Custom headers and SSL configuration".$br;
echo "- Automatic retry and timeout handling".$br;
echo "- Support for GET, POST, PUT, DELETE, and other HTTP methods".$br;
echo $br;

echo "For more information, see JsonAPI class documentation and test.php".$br;
