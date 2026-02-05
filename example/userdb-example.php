<?php
/**
 * User Management Example (Database Storage)
 * 
 * This example demonstrates how to use UserDB, UserGroupDB, UserPermDB
 * for user management with database storage.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.1
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\MeowBase;
use Paheon\MeowBase\Tools\UserDB;
use Paheon\MeowBase\Tools\UserGroupDB;
use Paheon\MeowBase\Tools\UserPermDB;
use Paheon\MeowBase\Tools\Password;
use Psr\Log\LogLevel;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "User Management Example (Using MeowBase Class)".$br;
echo "===============================================".$br.$br;

// Example 1: Setup MeowBase and UserDB
echo "Example 1: Setup MeowBase and UserDB".$br;
echo "--------------------------------".$br;

$config = new Config();
$userConfig = $config->getConfigByPath("user") ?? [];
$sqlConfig = $config->getConfigByPath("db/sql") ?? [];

// Check if database is configured
if (empty($sqlConfig['database'])) {
    echo "Database not configured. Please configure database in config file.".$br;
    echo "Skipping UserDB examples...".$br.$br;
    exit;
}

// Initialize MeowBase - it automatically creates Cache, SysLog, and CacheDB
$meow = new MeowBase($config, true);  // true = preload all components
$meow->debug = true;  // Enable debug mode for SQL logging

echo "MeowBase initialized".$br;
echo "  All user tables will be deleted before the example starts.".$br;
echo "  If you want to keep the user tables, please comment out the exit(0) line in the code.".$br;
echo "  If you don't have user database table, please create it manually by reference to doc/MeowDB.sql.".$br;
echo "  Components automatically loaded: MeowBase and CacheDB".$br;
echo "  Debug mode enabled (SQL logging active)".$br.$br;
//exit(0);

// Access CacheDB, Cache, and SysLog through MeowBase
$db = $meow->db;      // CacheDB object
$cache = $meow->cache; // Cache object
$log = $meow->log;     // SysLog object

echo "Components information:".$br;
echo "  Cache enabled: ".var_export($cache->enable, true).$br;
echo "  Cache site ID: ".$cache->getSiteID().$br;
echo "  Log path: ".$log->getLogFilePath().$br;
echo "  Log threshold: ".$log->getThreshold().$br;
echo "  Database: ".$sqlConfig['database'].$br;
echo "  CacheDB log enabled: ".var_export($db->enableLog, true).$br;
echo "  CacheDB cache enabled: ".var_export($db->enableCache, true).$br.$br;

// Initialize UserDB with MeowBase's database object
$userDB = new UserDB($db, $userConfig['user'] ?? []);
echo "UserDB initialized".$br;
echo "  User table: ".$userDB->userTable.$br;
echo "  User fields: ".implode(", ", array_keys($userDB->userFields)).$br.$br;
// Delete all record from user table
$db->delete($userDB->userTable, []);
echo "  User table record deleted".$br.$br;

// Example 2: Create Users
echo "Example 2: Create Users".$br;
echo "--------------------------------".$br;

// Initialize Password for password hashing
$password = new Password($userConfig['manager']['password'] ?? []);

// Create first user
$user1Data = [
    'userName' => 'Test User 1',
    'email' => 'user1@example.com'
];
$user1ID = $userDB->createUser('dbuser1', 'Password123!', $user1Data);
if ($user1ID > 0) {
    echo "User 1 created with ID: $user1ID".$br;
} else {
    echo "Failed to create User 1: ".$userDB->lastError.$br;
}

// Create second user
$user2Data = [
    'userName' => 'Test User 2',
    'email' => 'user2@example.com'
];
$user2ID = $userDB->createUser('dbuser2', 'SecurePass456!', $user2Data);
if ($user2ID > 0) {
    echo "User 2 created with ID: $user2ID".$br;
} else {
    echo "Failed to create User 2: ".$userDB->lastError.$br;
}
echo $br;

// Example 3: Get User Information
echo "Example 3: Get User Information".$br;
echo "--------------------------------".$br;

// Get user by ID
$user1 = $userDB->getUserByID($user1ID);
if ($user1) {
    echo "User 1 by ID:".$br;
    echo "  ID: ".$user1['userID'].$br;
    echo "  Login Name: ".$user1['loginName'].$br;
    echo "  User Name: ".$user1['userName'].$br;
    echo "  Email: ".$user1['email'].$br;
    echo "  Status: ".$user1['status'].$br;
} else {
    echo "Failed to get User 1: ".$userDB->lastError.$br;
}

// Get user by login name
$user2 = $userDB->getUserByLoginName('dbuser2');
if ($user2) {
    echo "User 2 by login name:".$br;
    echo "  ID: ".$user2['userID'].$br;
    echo "  Login Name: ".$user2['loginName'].$br;
}
echo $br;

// Example 4: Update User
echo "Example 4: Update User".$br;
echo "--------------------------------".$br;

$updateData = [
    'userName' => 'Updated User 1',
    'email' => 'updated_user1@example.com'
];
$updateResult = $userDB->updateUser($updateData, $user1ID);
if ($updateResult) {
    echo "User 1 updated successfully".$br;
    $updatedUser = $userDB->getUserByID($user1ID);
    echo "  New name: ".$updatedUser['userName'].$br;
    echo "  New email: ".$updatedUser['email'].$br;
} else {
    echo "Failed to update User 1: ".$userDB->lastError.$br;
}
echo $br;

// Example 5: Update Password
echo "Example 5: Update Password".$br;
echo "--------------------------------".$br;

$passwordResult = $userDB->updatePassword('NewPassword789!', $user1ID);
echo "Update password for User 1: ".($passwordResult ? "Success" : "Failed - ".$userDB->lastError).$br;
echo $br;

// Example 6: Manual Cache Management
echo "Example 6: Manual Cache Management".$br;
echo "--------------------------------".$br;

echo "Setting User 1 status to 'inactive':".$br;
$statusResult = $userDB->updateUser(['status' => 'inactive'], $user1ID);
echo "  Result: ".($statusResult ? "Success" : "Failed").$br;

$user1Status = $userDB->getUserByID($user1ID);
echo "  Current status: ".$user1Status['status'].$br;

echo "Setting User 1 status back to 'active':".$br;
$statusResult = $userDB->updateUser(['status' => 'active'], $user1ID);
echo "  Result: ".($statusResult ? "Success" : "Failed").$br;
echo $br;

// Example 7: Setup UserGroupDB
echo "Example 7: Setup UserGroupDB".$br;
echo "--------------------------------".$br;

$userGroupDB = new UserGroupDB($db, $userConfig['userGroup'] ?? []);
echo "UserGroupDB initialized".$br;
echo "  UserGroup table: ".$userGroupDB->userGroupTable.$br;
echo "  UserGroup link table: ".$userGroupDB->userGroupLinkTable.$br.$br;

// Delete all record from user group table
$db->delete($userGroupDB->userGroupTable, []);
$db->delete($userGroupDB->userGroupLinkTable, []);
echo "  UserGroup table record deleted".$br.$br;

// Example 8: Create User Groups
echo "Example 8: Create User Groups".$br;
echo "--------------------------------".$br;

$group1ID = $userGroupDB->createUserGroup('db_administrators', ['groupName' => 'DB Administrators']);
echo "Group 'db_administrators' created with ID: $group1ID".$br;

$group2ID = $userGroupDB->createUserGroup('db_editors', ['groupName' => 'DB Editors']);
echo "Group 'db_editors' created with ID: $group2ID".$br;
echo $br;

// Example 9: Add Users to Groups
echo "Example 9: Add Users to Groups".$br;
echo "--------------------------------".$br;

$addResult1 = $userGroupDB->addUserToGroup($user1ID, $group1ID);
echo "Add User 1 to db_administrators: ".($addResult1 ? "Success" : "Failed - ".$userGroupDB->lastError).$br;

$addResult2 = $userGroupDB->addUserToGroup($user2ID, $group2ID);
echo "Add User 2 to db_editors: ".($addResult2 ? "Success" : "Failed - ".$userGroupDB->lastError).$br;
echo $br;

// Example 10: Get Groups by User
echo "Example 10: Get Groups by User".$br;
echo "--------------------------------".$br;

$user1Groups = $userGroupDB->getUserGroupByID($group1ID);
echo "User 1 groups:".$br;
echo json_encode($user1Groups).$br;

$user2Groups = $userGroupDB->getUserGroupByID($group2ID);
echo "User 2 groups:".$br;
echo json_encode($user1Groups).$br;
echo $br;

// Example 11: Setup UserPermDB
echo "Example 11: Setup UserPermDB".$br;
echo "--------------------------------".$br;

$userPermDB = new UserPermDB($db, $userConfig['userPerm'] ?? []);
echo "UserPermDB initialized".$br;
echo "  UserPerm table: ".$userPermDB->userPermTable.$br;
echo "  GroupPerm table: ".$userPermDB->groupPermTable.$br.$br;

// Delete all record from user perm table
$db->delete($userPermDB->userPermTable, []);
$db->delete($userPermDB->userGroupPermTable, []);
echo "  UserPerm table record deleted".$br.$br;

// Example 12: Set User Permissions
echo "Example 12: Set User Permissions".$br;
echo "--------------------------------".$br;

$perm1 = $userPermDB->setUserPerm($user1ID, 'articles', 'read', 1);
echo "Set User 1 articles read permission: ".($perm1 ? "Success" : "Failed").$br;

$perm2 = $userPermDB->setUserPerm($user1ID, 'articles', 'write', 1);
echo "Set User 1 articles write permission: ".($perm2 ? "Success" : "Failed").$br;

$perm3 = $userPermDB->setUserPerm($user1ID, 'articles', 'delete', 0);
echo "Set User 1 articles delete permission (denied): ".($perm3 ? "Success" : "Failed").$br;
echo $br;

// Example 13: Get User Permissions
echo "Example 13: Get User Permissions".$br;
echo "--------------------------------".$br;

$user1Perms = $userPermDB->getUserPerm($user1ID, 'articles');
echo "User 1 articles permissions:".$br;
foreach ($user1Perms as $action => $value) {
    echo "  $action: $value".$br;
}
echo $br;

// Example 14: Set Group Permissions
echo "Example 14: Set Group Permissions".$br;
echo "--------------------------------".$br;

$groupPerm1 = $userPermDB->setGroupPerm($group1ID, 'system', 'admin', 1);
echo "Set db_administrators group system admin permission: ".($groupPerm1 ? "Success" : "Failed").$br;

$groupPerm2 = $userPermDB->setGroupPerm($group2ID, 'content', 'edit', 1);
echo "Set db_editors group content edit permission: ".($groupPerm2 ? "Success" : "Failed").$br;
echo $br;

// Example 15: Get Group Permissions
echo "Example 15: Get Group Permissions".$br;
echo "--------------------------------".$br;

$group1Perms = $userPermDB->getGroupPerm($group1ID, 'system');
echo "DB Administrators group system permissions:".$br;
foreach ($group1Perms as $action => $value) {
    echo "  $action: $value".$br;
}
echo $br;

// Example 16: Delete Permissions
echo "Example 16: Delete Permissions".$br;
echo "--------------------------------".$br;

$delPerm = $userPermDB->delUserPerm($user1ID, 'articles', 'delete');
echo "Delete User 1 articles delete permission: ".($delPerm ? "Success" : "Failed").$br;
echo $br;

// Example 17: Delete User from Group
echo "Example 17: Delete User from Group".$br;
echo "--------------------------------".$br;

$delFromGroup = $userGroupDB->delUserFromGroup($user2ID, $group2ID);
echo "Remove User 2 from db_editors: ".($delFromGroup ? "Success" : "Failed").$br;
echo $br;

// Example 18: Delete User
echo "Example 18: Delete User".$br;
echo "--------------------------------".$br;

$delUser = $userDB->delUser($user2ID);
echo "Delete User 2: ".($delUser ? "Success" : "Failed").$br;
echo $br;

// Example 19: Debug Information
echo "Example 19: Debug Information".$br;
echo "--------------------------------".$br;

$userDBDebug = $userDB->__debugInfo();
echo "UserDB debug info:".$br;
echo "  userTable: ".$userDBDebug['userTable'].$br;
echo "  userFields count: ".count($userDBDebug['userFields']).$br.$br;

$groupDBDebug = $userGroupDB->__debugInfo();
echo "UserGroupDB debug info:".$br;
echo "  userGroupTable: ".$groupDBDebug['userGroupTable'].$br;
echo "  userGroupLinkTable: ".$groupDBDebug['userGroupLinkTable'].$br.$br;

$permDBDebug = $userPermDB->__debugInfo();
echo "UserPermDB debug info:".$br;
echo "  userPermTable: ".$permDBDebug['userPermTable'].$br;
echo "  userGroupPermTable: ".$permDBDebug['userGroupPermTable'].$br.$br;

// Cleanup
echo "Cleanup:".$br;
$db->delete($userDB->userTable, []);
$db->delete($userGroupDB->userGroupTable, []);
$db->delete($userGroupDB->userGroupLinkTable, []);
$db->delete($userPermDB->userPermTable, []);
$db->delete($userPermDB->userGroupPermTable, []);
echo "  - All user tables record deleted".$br.$br;

echo "Example completed!".$br;
