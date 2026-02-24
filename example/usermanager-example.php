<?php
/**
 * UserManager Integration Example
 * 
 * This example demonstrates how to use UserManager for complete user
 * authentication and permission management with both CSV and database storage.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.2
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\CacheDB;
use Paheon\MeowBase\SysLog;
use Paheon\MeowBase\Tools\UserManager;

// Start session
session_start();

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "UserManager Integration Example".$br;
echo "==========================================".$br.$br;

// ==========================================
// Part 1: UserManager with CSV Storage
// ==========================================

echo "Part 1: UserManager with CSV Storage".$br;
echo "==========================================".$br.$br;

// Example 1: Setup UserManager with CSV
echo "Example 1: Setup UserManager with CSV".$br;
echo "--------------------------------".$br;

$config = new Config();
$userConfig = $config->getConfigByPath("user") ?? [];

// Initialize UserManager with CSV storage
$userManagerCSV = new UserManager('csv', $userConfig);
echo "UserManager initialized with CSV storage".$br;
echo "Storage type: ".$userManagerCSV->storageType.$br;
echo "Session variable name: ".$userManagerCSV->sessionVarName.$br.$br;

// Example 2: Create Test Users (CSV)
echo "Example 2: Create Test Users (CSV)".$br;
echo "--------------------------------".$br;

// Create users
$csvUser1ID = $userManagerCSV->createUser('csvuser1', 'Password123!', [
    'userName' => 'CSV User 1',
    'email' => 'csvuser1@example.com'
]);
echo "CSV User 1 created with ID: $csvUser1ID".$br;

$csvUser2ID = $userManagerCSV->createUser('csvuser2', 'Password456!', [
    'userName' => 'CSV User 2',
    'email' => 'csvuser2@example.com'
]);
echo "CSV User 2 created with ID: $csvUser2ID".$br;
echo $br;

// Example 3: Create User Groups (CSV)
echo "Example 3: Create User Groups (CSV)".$br;
echo "--------------------------------".$br;

$csvAdminGroupID = $userManagerCSV->createGroup('csv_admin', ['groupName' => 'CSV Administrators']);
echo "CSV Admin group created with ID: $csvAdminGroupID".$br;

$csvEditorGroupID = $userManagerCSV->createGroup('csv_editor', ['groupName' => 'CSV Editors']);
echo "CSV Editor group created with ID: $csvEditorGroupID".$br;
echo $br;

// Example 4: Add Users to Groups (CSV)
echo "Example 4: Add Users to Groups (CSV)".$br;
echo "--------------------------------".$br;

$addToGroup1 = $userManagerCSV->addUserToGroup($csvUser1ID, $csvAdminGroupID);
echo "Add CSV User 1 to admin group: ".($addToGroup1 ? "Success" : "Failed").$br;

$addToGroup2 = $userManagerCSV->addUserToGroup($csvUser2ID, $csvEditorGroupID);
echo "Add CSV User 2 to editor group: ".($addToGroup2 ? "Success" : "Failed").$br;
echo $br;

// Example 5: Set User Permissions (CSV)
echo "Example 5: Set User Permissions (CSV)".$br;
echo "--------------------------------".$br;

$setPerm1 = $userManagerCSV->setUserPermission($csvUser1ID, 'articles', 'read', 1);
echo "Set User 1 articles read permission: ".($setPerm1 ? "Success" : "Failed").$br;

$setPerm2 = $userManagerCSV->setUserPermission($csvUser1ID, 'articles', 'write', 1);
echo "Set User 1 articles write permission: ".($setPerm2 ? "Success" : "Failed").$br;

$setPerm3 = $userManagerCSV->setUserPermission($csvUser1ID, 'articles', 'delete', 0);
echo "Set User 1 articles delete permission (denied): ".($setPerm3 ? "Success" : "Failed").$br;
echo $br;

// Example 6: Set Group Permissions (CSV)
echo "Example 6: Set Group Permissions (CSV)".$br;
echo "--------------------------------".$br;

$setGroupPerm1 = $userManagerCSV->setGroupPermission($csvAdminGroupID, 'system', 'admin', 1);
echo "Set admin group system admin permission: ".($setGroupPerm1 ? "Success" : "Failed").$br;

$setGroupPerm2 = $userManagerCSV->setGroupPermission($csvEditorGroupID, 'content', 'edit', 1);
echo "Set editor group content edit permission: ".($setGroupPerm2 ? "Success" : "Failed").$br;
echo $br;

// Example 7: User Login (CSV)
echo "Example 7: User Login (CSV)".$br;
echo "--------------------------------".$br;

echo "Before login:".$br;
echo "  Is logged in: ".($userManagerCSV->isLoggedIn() ? "Yes" : "No").$br;

$loginResult = $userManagerCSV->login('csvuser1', 'Password123!');
echo "Login as csvuser1: ".($loginResult ? "Success" : "Failed - ".$userManagerCSV->lastError).$br;

echo "After login:".$br;
echo "  Is logged in: ".($userManagerCSV->isLoggedIn() ? "Yes" : "No").$br;
if ($userManagerCSV->user) {
    echo "  Current user ID: ".$userManagerCSV->user['userID'].$br;
    echo "  Current user login name: ".$userManagerCSV->user['loginName'].$br;
}
echo $br;

// Example 8: Check Permissions (CSV)
echo "Example 8: Check Permissions (CSV)".$br;
echo "--------------------------------".$br;

$canRead = $userManagerCSV->checkUserPermission('articles', 'read');
echo "Current user can read articles: ".($canRead ? "Yes" : "No").$br;

$canWrite = $userManagerCSV->checkUserPermission('articles', 'write');
echo "Current user can write articles: ".($canWrite ? "Yes" : "No").$br;

$canDelete = $userManagerCSV->checkUserPermission('articles', 'delete');
echo "Current user can delete articles: ".($canDelete ? "Yes" : "No").$br;

$canAdmin = $userManagerCSV->checkUserPermission('system', 'admin');
echo "Current user has system admin permission: ".($canAdmin ? "Yes" : "No").$br;
echo $br;

// Example 9: Get Permission Value (CSV)
echo "Example 9: Get Permission Value (CSV)".$br;
echo "--------------------------------".$br;

$readValue = $userManagerCSV->getUserPermissionValue('articles', 'read');
echo "Articles read permission value: ".print_r($readValue, true).$br;

$writeValue = $userManagerCSV->getUserPermissionValue('articles', 'write');
echo "Articles write permission value: ".print_r($writeValue, true).$br;
echo $br;

// Example 10: User Logout (CSV)
echo "Example 10: User Logout (CSV)".$br;
echo "--------------------------------".$br;

$userManagerCSV->logout();
echo "User logged out".$br;
echo "Is logged in after logout: ".($userManagerCSV->isLoggedIn() ? "Yes" : "No").$br;
echo $br;

// ==========================================
// Part 2: UserManager with Database Storage
// ==========================================

echo "Part 2: UserManager with Database Storage".$br;
echo "==========================================".$br.$br;

// Example 11: Setup UserManager with Database
echo "Example 11: Setup UserManager with Database".$br;
echo "--------------------------------".$br;

$sqlConfig = $config->getConfigByPath("db/sql");

// Check if database is configured
if (empty($sqlConfig['database'])) {
    echo "Database not configured. Skipping database examples.".$br.$br;
    echo "Example completed!".$br;
    exit;
}

// Initialize database
$db = new CacheDB($sqlConfig);

// Initialize UserManager with database storage
$userManagerDB = new UserManager('db', $userConfig, null, null, null, $db);
echo "UserManager initialized with database storage".$br;
echo "Storage type: ".$userManagerDB->storageType.$br.$br;

// Create database tables
echo "Creating database tables...".$br;
$db->drop($userManagerDB->user->userTable);
$db->drop($userManagerDB->userGroup->userGroupTable);
$db->drop($userManagerDB->userGroup->userGroupLinkTable);
$db->drop($userManagerDB->userPerm->userPermTable);
$db->drop($userManagerDB->userPerm->groupPermTable);
$userManagerDB->user->createUserTable();
$userManagerDB->userGroup->createUserGroupTable();
$userManagerDB->userPerm->createUserPermTable();
echo "Tables created".$br.$br;

// Example 12: Create Test Users (DB)
echo "Example 12: Create Test Users (DB)".$br;
echo "--------------------------------".$br;

$dbUser1ID = $userManagerDB->createUser('dbuser1', 'Password123!', [
    'userName' => 'DB User 1',
    'email' => 'dbuser1@example.com'
]);
echo "DB User 1 created with ID: $dbUser1ID".$br;

$dbUser2ID = $userManagerDB->createUser('dbuser2', 'Password456!', [
    'userName' => 'DB User 2',
    'email' => 'dbuser2@example.com'
]);
echo "DB User 2 created with ID: $dbUser2ID".$br;
echo $br;

// Example 13: Create User Groups (DB)
echo "Example 13: Create User Groups (DB)".$br;
echo "--------------------------------".$br;

$dbAdminGroupID = $userManagerDB->createGroup('db_admin', ['groupName' => 'DB Administrators']);
echo "DB Admin group created with ID: $dbAdminGroupID".$br;

$dbEditorGroupID = $userManagerDB->createGroup('db_editor', ['groupName' => 'DB Editors']);
echo "DB Editor group created with ID: $dbEditorGroupID".$br;
echo $br;

// Example 14: Add Users to Groups (DB)
echo "Example 14: Add Users to Groups (DB)".$br;
echo "--------------------------------".$br;

$addToGroup3 = $userManagerDB->addUserToGroup($dbUser1ID, $dbAdminGroupID);
echo "Add DB User 1 to admin group: ".($addToGroup3 ? "Success" : "Failed").$br;

$addToGroup4 = $userManagerDB->addUserToGroup($dbUser2ID, $dbEditorGroupID);
echo "Add DB User 2 to editor group: ".($addToGroup4 ? "Success" : "Failed").$br;
echo $br;

// Example 15: Set Permissions (DB)
echo "Example 15: Set Permissions (DB)".$br;
echo "--------------------------------".$br;

$setPerm4 = $userManagerDB->setUserPermission($dbUser1ID, 'pages', 'read', 1);
echo "Set User 1 pages read permission: ".($setPerm4 ? "Success" : "Failed").$br;

$setPerm5 = $userManagerDB->setUserPermission($dbUser1ID, 'pages', 'write', 1);
echo "Set User 1 pages write permission: ".($setPerm5 ? "Success" : "Failed").$br;

$setGroupPerm3 = $userManagerDB->setGroupPermission($dbAdminGroupID, 'database', 'manage', 1);
echo "Set admin group database manage permission: ".($setGroupPerm3 ? "Success" : "Failed").$br;
echo $br;

// Example 16: User Login (DB)
echo "Example 16: User Login (DB)".$br;
echo "--------------------------------".$br;

$loginResultDB = $userManagerDB->login('dbuser1', 'Password123!');
echo "Login as dbuser1: ".($loginResultDB ? "Success" : "Failed - ".$userManagerDB->lastError).$br;

if ($userManagerDB->user) {
    echo "Current user ID: ".$userManagerDB->user['userID'].$br;
    echo "Current user login name: ".$userManagerDB->user['loginName'].$br;
}
echo $br;

// Example 17: Check Permissions (DB)
echo "Example 17: Check Permissions (DB)".$br;
echo "--------------------------------".$br;

$canReadPages = $userManagerDB->checkUserPermission('pages', 'read');
echo "Current user can read pages: ".($canReadPages ? "Yes" : "No").$br;

$canWritePages = $userManagerDB->checkUserPermission('pages', 'write');
echo "Current user can write pages: ".($canWritePages ? "Yes" : "No").$br;

$canManageDB = $userManagerDB->checkUserPermission('database', 'manage');
echo "Current user can manage database: ".($canManageDB ? "Yes" : "No").$br;
echo $br;

// Example 18: User Logout (DB)
echo "Example 18: User Logout (DB)".$br;
echo "--------------------------------".$br;

$userManagerDB->logout();
echo "User logged out".$br;
echo "Is logged in after logout: ".($userManagerDB->isLoggedIn() ? "Yes" : "No").$br;
echo $br;

// Cleanup
echo "Cleanup:".$br;
$db->drop($userManagerDB->user->userTable);
$db->drop($userManagerDB->userGroup->userGroupTable);
$db->drop($userManagerDB->userGroup->userGroupLinkTable);
$db->drop($userManagerDB->userPerm->userPermTable);
$db->drop($userManagerDB->userPerm->groupPermTable);
echo "  - All test tables dropped".$br.$br;

// Example 19: Debug Information
echo "Example 19: Debug Information".$br;
echo "--------------------------------".$br;

$debugInfo = $userManagerDB->__debugInfo();
echo "UserManager debug info:".$br;
echo "  storageType: ".$debugInfo['storageType'].$br;
echo "  sessionVarName: ".$debugInfo['sessionVarName'].$br;
echo "  user object: ".(is_object($debugInfo['user']) ? "Exists" : "null").$br;
echo "  userGroup object: ".(is_object($debugInfo['userGroup']) ? "Exists" : "null").$br;
echo "  userPerm object: ".(is_object($debugInfo['userPerm']) ? "Exists" : "null").$br.$br;

echo "Example completed!".$br;
