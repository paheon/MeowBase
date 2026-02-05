<?php
/**
 * User Management Example (CSV Storage)
 * 
 * This example demonstrates how to use UserCSV, UserGroupCSV, UserPermCSV
 * for user management with CSV storage.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.1
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\Tools\UserCSV;
use Paheon\MeowBase\Tools\UserGroupCSV;
use Paheon\MeowBase\Tools\UserPermCSV;
use Paheon\MeowBase\Tools\Password;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "User Management Example (CSV Storage)".$br;
echo "==========================================".$br.$br;

// Example 1: Setup UserCSV
echo "Example 1: Setup UserCSV".$br;
echo "--------------------------------".$br;

$config = new Config();
$userConfig = $config->getConfigByPath("user") ?? [];

// Initialize UserCSV
$userCSV = new UserCSV($userConfig['user'] ?? []);
echo "UserCSV initialized".$br;
echo "User table file: ".$userCSV->userTableFile.$br;
echo "User fields: ".implode(", ", array_keys($userCSV->userFields)).$br.$br;
if (file_exists($userCSV->userTableFile)) {
    echo "User table file exists: ".$userCSV->userTableFile.$br;
    unlink($userCSV->userTableFile);
    echo "User table file deleted!".$br;
}
echo $br;

// Example 2: Create Users
echo "Example 2: Create Users".$br;
echo "--------------------------------".$br;

// Initialize Password for password hashing
$password = new Password($userConfig['manager']['password'] ?? []);

// Create first user
$user1Data = [
    'userName' => 'testuser1',
    'email' => 'user1@example.com'
];
$user1ID = $userCSV->createUser('user1', 'Password123!', $user1Data);
if ($user1ID > 0) {
    echo "User 1 created with ID: $user1ID".$br;
} else {
    echo "Failed to create User 1: ".$userCSV->lastError.$br;
}

// Create second user
$user2Data = [
    'userName' => 'testuser2',
    'email' => 'user2@example.com'
];
$user2ID = $userCSV->createUser('user2', 'SecurePass456!', $user2Data);
if ($user2ID > 0) {
    echo "User 2 created with ID: $user2ID".$br;
} else {
    echo "Failed to create User 2: ".$userCSV->lastError.$br;
}
echo $br;

// Example 3: Get User Information
echo "Example 3: Get User Information".$br;
echo "--------------------------------".$br;

// Get user by ID
$user1 = $userCSV->getUserByID($user1ID);
if ($user1) {
    echo "User 1 by ID:".$br;
    echo "  ID: ".$user1['userID'].$br;
    echo "  Login Name: ".$user1['loginName'].$br;
    echo "  User Name: ".$user1['userName'].$br;
    echo "  Email: ".$user1['email'].$br;
    echo "  Status: ".$user1['status'].$br;
} else {
    echo "Failed to get User 1: ".$userCSV->lastError.$br;
}

// Get user by login name
$user2 = $userCSV->getUserByLoginName('user2');
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
    'email' => 'updated1@example.com'
];
if ($userCSV->updateUser($updateData, $user1ID)) {
    echo "User 1 updated successfully".$br;
    $updatedUser = $userCSV->getUserByID($user1ID);
    echo "  New name: ".$updatedUser['userName'].$br;
    echo "  New email: ".$updatedUser['email'].$br;
} else {
    echo "Failed to update User 1: ".$userCSV->lastError.$br;
}
echo $br;

// Example 5: Update Password
echo "Example 5: Update Password".$br;
echo "--------------------------------".$br;

$newPasswordHash = $password->getPasswordHash('NewPassword789!');
if ($userCSV->updatePassword($newPasswordHash, $user1ID)) {
    echo "Password updated for User 1".$br;
} else {
    echo "Failed to update password: ".$userCSV->lastError.$br;
}
echo $br;

// Example 6: User Status Management
echo "Example 6: User Status Management".$br;
echo "--------------------------------".$br;

// Update login status
$currentTime = time();
if ($userCSV->updateUserStatus(true, $currentTime, 'session123', $currentTime, $user1ID)) {
    echo "User 1 login status updated".$br;
    $user1 = $userCSV->getUserByID($user1ID);
    echo "  Session ID: ".$user1['sessionID'].$br;
    echo "  Login Time: ".date('Y-m-d H:i:s', $user1['loginTime']).$br;
}

// Update logout status
if ($userCSV->updateUserStatus(false, time(), null, null, $user1ID)) {
    echo "User 1 logout status updated".$br;
}
echo $br;

// Example 7: Setup UserGroupCSV
echo "Example 7: Setup UserGroupCSV".$br;
echo "--------------------------------".$br;

$userGroupCSV = new UserGroupCSV($userConfig['userGroup'] ?? []);
echo "UserGroupCSV initialized".$br;
echo "Group table file: ".$userGroupCSV->userGroupTableFile.$br;
echo "Group link table file: ".$userGroupCSV->userGroupLinkTableFile.$br.$br;
if (file_exists($userGroupCSV->userGroupTableFile)) {
    echo "Group table file exists: ".$userGroupCSV->userGroupTableFile.$br;
    unlink($userGroupCSV->userGroupTableFile);
    echo "User table file deleted!".$br;
}
echo $br;

// Example 8: Create User Groups
echo "Example 8: Create User Groups".$br;
echo "--------------------------------".$br;

$group1Data = ['groupDesc' => 'Administrators'];
$group1ID = $userGroupCSV->createUserGroup('admins', $group1Data);
if ($group1ID > 0) {
    echo "Group 'admins' created with ID: $group1ID".$br;
} else {
    echo "Failed to create group: ".$userGroupCSV->lastError.$br;
}

$group2ID = $userGroupCSV->createUserGroup('editors', ['groupDesc' => 'Content Editors']);
if ($group2ID > 0) {
    echo "Group 'editors' created with ID: $group2ID".$br;
}
echo $br;

// Example 9: Add Users to Groups
echo "Example 9: Add Users to Groups".$br;
echo "--------------------------------".$br;

if ($userGroupCSV->addUserToGroup($user1ID, $group1ID)) {
    echo "User 1 added to group 'admins'".$br;
} else {
    echo "Failed: ".$userGroupCSV->lastError.$br;
}

if ($userGroupCSV->addUserToGroup($user2ID, $group2ID)) {
    echo "User 2 added to group 'editors'".$br;
}
echo $br;

// Example 10: Get Groups by User
echo "Example 10: Get Groups by User".$br;
echo "--------------------------------".$br;

$userGroups = $userGroupCSV->getGroupsByUser($user1ID);
if ($userGroups) {
    echo "Groups for User 1:".$br;
    foreach ($userGroups as $groupID) {
        echo "  - ID: $groupID".$br;
    }
} else {
    echo "No groups found for User 1".$br;
}
echo $br;

// Example 11: Setup UserPermCSV
echo "Example 11: Setup UserPermCSV".$br;
echo "--------------------------------".$br;

$userPermCSV = new UserPermCSV($userConfig['userPerm'] ?? []);
echo "UserPermCSV initialized".$br;
echo "User perm table file: ".$userPermCSV->userPermTableFile.$br;
echo "Group perm table file: ".$userPermCSV->userGroupPermTableFile.$br.$br;

// Example 12: Set User Permissions
echo "Example 12: Set User Permissions".$br;
echo "--------------------------------".$br;

// Set user permission
if ($userPermCSV->setUserPerm($user1ID, 'article', 'read', 1)) {
    echo "Set permission: User 1 can read articles".$br;
}
if ($userPermCSV->setUserPerm($user1ID, 'article', 'write', 0)) {
    echo "Set permission: User 1 cannot write articles".$br;
}
if ($userPermCSV->setUserPerm($user1ID, 'article', 'delete', 1)) {
    echo "Set permission: User 1 can delete articles".$br;
}
echo $br;

// Example 13: Get User Permissions
echo "Example 13: Get User Permissions".$br;
echo "--------------------------------".$br;

$userPerms = $userPermCSV->getUserPerm($user1ID, 'article');
if ($userPerms) {
    echo "User 1 permissions for 'article':".$br;
    foreach ($userPerms as $perm => $value) {
        echo "  - $perm: ".($value ? "Yes" : "No").$br;
    }
} else {
    echo "No permissions found".$br;
}
echo $br;

// Example 14: Set Group Permissions
echo "Example 14: Set Group Permissions".$br;
echo "--------------------------------".$br;

if ($userPermCSV->setGroupPerm($group1ID, 'system', 'admin', 1)) {
    echo "Set group permission: 'admins' group has admin access".$br;
}
if ($userPermCSV->setGroupPerm($group2ID, 'article', 'read', 1)) {
    echo "Set group permission: 'editors' group can read articles".$br;
}
if ($userPermCSV->setGroupPerm($group2ID, 'article', 'write', 1)) {
    echo "Set group permission: 'editors' group can write articles".$br;
}
echo $br;

// Example 15: Get Group Permissions
echo "Example 15: Get Group Permissions".$br;
echo "--------------------------------".$br;

$groupPerms = $userPermCSV->getGroupPerm($group1ID, 'system');
if ($groupPerms) {
    echo "Group 'admins' permissions for 'system':".$br;
    foreach ($groupPerms as $perm => $value) {
        echo "  - $perm: ".($value ? "Yes" : "No").$br;
    }
}
echo $br;

// Example 16: Delete Permissions
echo "Example 16: Delete Permissions".$br;
echo "--------------------------------".$br;

// Delete specific permission
if ($userPermCSV->delUserPerm($user1ID, 'article', 'delete')) {
    echo "Deleted 'delete' permission for User 1".$br;
}

// Delete all permissions for an item
if ($userPermCSV->delGroupPerm($group2ID, 'article')) {
    echo "Deleted all 'article' permissions for 'editors' group".$br;
}
echo $br;

// Example 17: Delete User from Group
echo "Example 17: Delete User from Group".$br;
echo "--------------------------------".$br;

if ($userGroupCSV->delUserFromGroup($user2ID, $group2ID)) {
    echo "User 2 removed from 'editors' group".$br;
}
echo $br;

// Example 18: Delete User
echo "Example 18: Delete User".$br;
echo "--------------------------------".$br;

if ($userCSV->delUser($user2ID)) {
    echo "User 2 deleted successfully".$br;
} else {
    echo "Failed to delete User 2: ".$userCSV->lastError.$br;
}
echo $br;

// Example 19: Debug Information
echo "Example 19: Debug Information".$br;
echo "--------------------------------".$br;

$userDebug = $userCSV->__debugInfo();
echo "UserCSV debug info:".$br;
echo "  userTable: ".$userDebug['userTable'].$br;
echo "  userFields count: ".count($userDebug['userFields']).$br.$br;

$groupDebug = $userGroupCSV->__debugInfo();
echo "UserGroupCSV debug info:".$br;
echo "  userGroupTable: ".$groupDebug['userGroupTable'].$br;
echo "  userGroupFields count: ".count($groupDebug['userGroupFields']).$br.$br;

$permDebug = $userPermCSV->__debugInfo();
echo "UserPermCSV debug info:".$br;
echo "  userPermTable: ".$permDebug['userPermTable'].$br;
echo "  userGroupPermTable: ".$permDebug['userGroupPermTable'].$br.$br;

echo "Example completed!".$br;
