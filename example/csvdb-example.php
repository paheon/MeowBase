<?php
/**
 * CsvDB Example
 * 
 * This example demonstrates how to use the CsvDB class for CSV file
 * database operations with advanced search capabilities.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.3
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\Tools\CsvDB;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "CsvDB Example".$br;
echo "==========================================".$br.$br;

// Example 1: Create CsvDB Instance
echo "Example 1: Create CsvDB Instance".$br;
echo "--------------------------------".$br;

$config = new Config();
$testCsvFile = $config->docRoot . $config->varPath . "/tmp/csvdb_example.csv";

// Remove old file if exists
if (file_exists($testCsvFile)) {
    unlink($testCsvFile);
}

// Create CsvDB with header
$csv = new CsvDB($testCsvFile, [
    "name",
    "age",
    "email",
    "status",
    "score"
]);

echo "CsvDB created with file: ".$testCsvFile.$br;
echo "Header fields: ".implode(", ", array_keys($csv->header)).$br.$br;

// Example 2: Add Records
echo "Example 2: Add Records".$br;
echo "--------------------------------".$br;

$records = [
    [
        "name" => "John Doe",
        "age" => "30",
        "email" => "john@example.com",
        "status" => "active",
        "score" => "85"
    ],
    [
        "name" => "Jane Smith",
        "age" => "25",
        "email" => "jane@mydomain.com",
        "status" => "active",
        "score" => "92"
    ],
    [
        "csvRowID" => 3,
        "name" => "Bob Johnson",
        "age" => "45",
        "email" => "bob@example.com",
        "status" => "pending",
        "score" => "78"
    ],
    [
        "name" => "Alice Brown",
        "age" => "28",
        "email" => "alice@example.com",
        "status" => "active",
        "score" => "95"
    ],
    [
        "name" => "Charlie Wilson",
        "age" => "35",
        "email" => "charlie@mydomain.com",
        "status" => "inactive",
        "score" => "65"
    ]
];

echo "Adding records:".$br;
foreach ($records as $record) {
    $rowID = $csv->setRow($record);
    echo "  - Added record with rowID: ".$rowID.$br;
}
echo $br;

// Example 3: Save and Load
echo "Example 3: Save and Load".$br;
echo "--------------------------------".$br;

// Save to file
$errCode = $csv->save();
echo "Saving to file: ".($errCode == 0 ? "Success" : "Failed($errCode) - ".$csv->lastError).$br;

// Clear and load from file
$csv->clearRec();
$errCode = $csv->load();
echo "Loading from file: ".($errCode == 0 ? "Success" : "Failed($errCode) - ".$csv->lastError).$br;

echo "Loaded records:".$br;
foreach ($csv as $idx => $row) {
    echo "  ID($idx): ".$row['csvRowID']." - ".$row['name'].", age=".$row['age'].", status=".$row['status'].$br;
}
echo $br;

// Example 4: Simple Search
echo "Example 4: Simple Search".$br;
echo "--------------------------------".$br;

$results = $csv->search(["status" => "active"]);
echo "Search for status = 'active': Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (age: ".$result['age'].", score: ".$result['score'].")".$br;
}
echo $br;

// Example 5: Comparison Operators
echo "Example 5: Comparison Operators".$br;
echo "--------------------------------".$br;

// Greater than
$results = $csv->search(["age[>]" => 30]);
echo "Search for age > 30: Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (age: ".$result['age'].")".$br;
}
echo $br;

// Greater than or equal
$results = $csv->search(["age[>=]" => 30]);
echo "Search for age >= 30: Found ".count($results)." records".$br;
echo $br;

// Less than
$results = $csv->search(["score[<]" => 80]);
echo "Search for score < 80: Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (score: ".$result['score'].")".$br;
}
echo $br;

// Example 6: LIKE and NOT LIKE
echo "Example 6: LIKE and NOT LIKE".$br;
echo "--------------------------------".$br;

// LIKE
$results = $csv->search(["email[~]" => "example.com"]);
echo "Search for email LIKE 'example.com': Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (".$result['email'].")".$br;
}
echo $br;

// NOT LIKE
$results = $csv->search(["email[!~]" => "example.com"]);
echo "Search for email NOT LIKE 'example.com': Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (".$result['email'].")".$br;
}
echo $br;

// Example 7: IN Operator
echo "Example 7: IN Operator".$br;
echo "--------------------------------".$br;

$results = $csv->search(["status" => ["active", "pending"]]);
echo "Search for status IN ['active', 'pending']: Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (status: ".$result['status'].")".$br;
}
echo $br;

// Example 8: BETWEEN Operator
echo "Example 8: BETWEEN Operator".$br;
echo "--------------------------------".$br;

// BETWEEN
$results = $csv->search(["age[<>]" => [25, 35]]);
echo "Search for age BETWEEN 25 AND 35: Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (age: ".$result['age'].")".$br;
}
echo $br;

// NOT BETWEEN
$results = $csv->search(["age[><]" => [25, 35]]);
echo "Search for age NOT BETWEEN 25 AND 35: Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (age: ".$result['age'].")".$br;
}
echo $br;

// Example 9: AND Operator
echo "Example 9: AND Operator".$br;
echo "--------------------------------".$br;

$results = $csv->search([
    'AND' => [
        "status" => "active",
        "age[>]" => 25
    ]
]);
echo "Search for status = 'active' AND age > 25: Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (status: ".$result['status'].", age: ".$result['age'].")".$br;
}
echo $br;

// Example 10: OR Operator
echo "Example 10: OR Operator".$br;
echo "--------------------------------".$br;

$results = $csv->search([
    'OR' => [
        "status" => "active",
        "age[>]" => 40
    ]
]);
echo "Search for status = 'active' OR age > 40: Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (status: ".$result['status'].", age: ".$result['age'].")".$br;
}
echo $br;

// Example 11: Complex AND/OR Combination
echo "Example 11: Complex AND/OR Combination".$br;
echo "--------------------------------".$br;

$results = $csv->search([
    'AND' => [
        "status" => "active",
        'OR' => [
            "age[>]" => 30,
            "score[>=]" => 90
        ]
    ]
]);
echo "Search for status = 'active' AND (age > 30 OR score >= 90): Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (age: ".$result['age'].", score: ".$result['score'].")".$br;
}
echo $br;

// Example 12: Search with Sorting
echo "Example 12: Search with Sorting".$br;
echo "--------------------------------".$br;

// Ascending
$results = $csv->search(["status" => "active"], "age", true);
echo "Search for status = 'active', sorted by age (ascending):".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (age: ".$result['age'].")".$br;
}
echo $br;

// Descending
$results = $csv->search(["status" => "active"], "score", false);
echo "Search for status = 'active', sorted by score (descending):".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (score: ".$result['score'].")".$br;
}
echo $br;

// Example 13: Queue Operations
echo "Example 13: Queue Operations".$br;
echo "--------------------------------".$br;

// Queue append
$csv->queueAppend([
    "name" => "New User",
    "age" => "22",
    "email" => "newuser@example.com",
    "status" => "active",
    "score" => "88"
]);
echo "Queued record for append".$br;

// Queue update
$csv->queueUpdate(
    ["name" => "John Doe"], // Criteria
    ["score" => "90"] // Update data
);
echo "Queued record for update".$br;

// Queue delete
$csv->queueDelete(["name" => "Bob Johnson"]);
echo "Queued record for delete".$br;

// Process queue
echo "Processing queue:".$br;
$result = $csv->runQueue();
echo "  - Added: ".count($result['add'])." record(s)".$br;
echo "  - Updated: ".count($result['update'])." record(s)".$br;
echo "  - Deleted: ".count($result['del'])." record(s)".$br;
echo "  - Exit code: ".$result['exitCode'].$br;
echo "  - Line count: ".$result['lineCount'].$br.$br;

// Reload to see changes
$csv->load();
echo "Records after queue processing:".$br;
foreach ($csv as $idx => $row) {
    echo "  ID($idx): ".$row['name'].", age=".$row['age'].", score=".$row['score'].$br;
}
echo $br;

// Example 14: Iterator Functionality
echo "Example 14: Iterator Functionality".$br;
echo "--------------------------------".$br;

echo "Iterating through all records:".$br;
foreach ($csv as $idx => $row) {
    echo "  Position $idx: ".$row['name']." (ID: ".$row['csvRowID'].")".$br;
}
echo $br;

// Example 15: Get Specific Row
echo "Example 15: Get Specific Row".$br;
echo "--------------------------------".$br;

$row = $csv->getRow(1);
if ($row !== false) {
    echo "Row with ID 1:".$br;
    echo "  Name: ".$row['name'].$br;
    echo "  Age: ".$row['age'].$br;
    echo "  Email: ".$row['email'].$br;
} else {
    echo "Row not found".$br;
}
echo $br;

// Example 16: Generate Empty Record
echo "Example 16: Generate Empty Record".$br;
echo "--------------------------------".$br;

$emptyRec = $csv->genEmptyRec();
echo "Empty record structure:".$br;
print_r($emptyRec);
echo $br;

// Example 17: Min/Max Operations
echo "Example 17: Min/Max Operations".$br;
echo "--------------------------------".$br;

$minAge = $csv->getMin("age");
$maxAge = $csv->getMax("age");
echo "Minimum age: ".$minAge.$br;
echo "Maximum age: ".$maxAge.$br.$br;

// Example 18: Custom Search
echo "Example 18: Custom Search".$br;
echo "--------------------------------".$br;

$results = $csv->customSearch(function($row) {
    return (int)$row['age'] >= 30 && (int)$row['score'] >= 80;
});
echo "Custom search (age >= 30 AND score >= 80): Found ".count($results)." records".$br;
foreach ($results as $result) {
    echo "  - ".$result['name']." (age: ".$result['age'].", score: ".$result['score'].")".$br;
}
echo $br;

// Example 19: Sort by RowID
echo "Example 19: Sort by RowID".$br;
echo "--------------------------------".$br;

echo "Before sorting:".$br;
foreach ($csv as $idx => $row) {
    echo "  Position $idx: RowID ".$row['csvRowID']." - ".$row['name'].$br;
}

$csv->sortByRowID(false); // Descending
echo "After sorting by RowID (descending):".$br;
foreach ($csv as $idx => $row) {
    echo "  Position $idx: RowID ".$row['csvRowID']." - ".$row['name'].$br;
}
echo $br;

// Example 20: Debug Information
echo "Example 20: Debug Information".$br;
echo "--------------------------------".$br;

$debugInfo = $csv->__debugInfo();
echo "CsvDB debug info:".$br;
echo "  csvFile: ".$debugInfo['csvFile'].$br;
echo "  header count: ".count($debugInfo['header']).$br;
echo "  data count: ".count($debugInfo['data']).$br;
echo "  seperator: ".$debugInfo['seperator'].$br;
echo "  enclosure: ".$debugInfo['enclosure'].$br;
echo "  queue operations: ".implode(", ", array_keys($debugInfo['queue'])).$br.$br;

// Cleanup
if (file_exists($testCsvFile)) {
    unlink($testCsvFile);
    echo "Test file cleaned up".$br;
}
echo $br;

echo "Example completed!".$br;
