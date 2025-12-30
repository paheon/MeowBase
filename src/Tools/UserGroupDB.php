<?php
/**
 * User Group DB class
 * 
 * This class is used to manage user group information for Database storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 */

namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\CacheDB;
use Paheon\MeowBase\Tools\UserGroup;

class UserGroupDB extends UserGroup {

    // CacheDB Object //
    protected CacheDB $userGroupDB;

    // Constructor //
    public function __construct(CacheDB $cacheDB, array $config = []) {
        parent::__construct($config);
        $this->denyWrite = array_merge($this->denyWrite, ['userGroupDB', 'userGroupTable', 'userGroupLinkTable', 'dbGroupLoaded', 'dbGroupLinkLoaded']);

        $this->userGroupDB = $cacheDB;
    }

    // Append SQL error to lastError if exists //
    protected function appendSQLError(): void {
        $sqlError = $this->userGroupDB->getSQLError();
        if ($sqlError) {
            $this->lastError .= " [SQL Error: " . $sqlError . "]";
        }
    }

    // Normalize user group record //
    protected function normUserGroupRec(array $record, bool $load = true): array {
        $newRecord = [];
        if ($load) {
            // Load record - convert database field names to logical field names //
            $intField = ["groupID"];
            foreach($record as $fieldName => $value) {
                $key = array_search($fieldName, $this->userGroupFields);
                if ($key !== false) {
                    if (in_array($key, $intField)) {
                        $newRecord[$key] = (int)$value;
                    } else {
                        $newRecord[$key] = $value;
                    }
                } else {
                    $newRecord[$fieldName] = $record[$fieldName];
                }
            }    
        } else {
            // Save record - convert logical field names to database field names //
            foreach($record as $fieldName => $value) {
                if (isset($this->userGroupFields[$fieldName])) {
                    $newRecord[$this->userGroupFields[$fieldName]] = $value;
                }
            }
        }
        return $newRecord;
    }

    // Normalize user group link record //
    protected function normUserGroupLinkRecord(array $record, bool $load = true): array {
        $newRecord = [];
        if ($load) {
            // Load record - convert database field names to logical field names //
            $intField = ["userID", "groupID"];
            foreach($record as $fieldName => $value) {
                $key = array_search($fieldName, $this->userGroupLinkFields);
                if ($key !== false) {
                    if (in_array($key, $intField)) {
                        $newRecord[$key] = (int)$value;
                    } else {
                        $newRecord[$key] = $value;
                    }
                } else {
                    $newRecord[$fieldName] = $record[$fieldName];
                }
            }    
        } else {
            // Save record - convert logical field names to database field names //
            foreach($record as $fieldName => $value) {
                if (isset($this->userGroupLinkFields[$fieldName])) {
                    $newRecord[$this->userGroupLinkFields[$fieldName]] = $value;
                }
            }
        }
        return $newRecord;
    }

    //---------- Sudo Abstract Methods  ----------//
    
    // Get group data by ID //
    public function getUserGroupByID(int $userGroupID, bool $forceLoad = false, bool $checkExists = false): ?array {
        $this->lastError = "";

        // Check User ID //
        if ($userGroupID < 0) {
            $this->lastError = "User group ID is invalid";
            $this->throwException($this->lastError, 1);
            return null;
        }

        // Search user group data //
        $userGroupIDField = $this->getUserGroupField('groupID', 'group_id');
        $result = $this->userGroupDB->cachedGet($this->userGroupTable, '*', [$userGroupIDField => $userGroupID]);
        if ($result) {
            // Normalize user group record //
            $userGroupRec = $this->normUserGroupRec($result);
            if ($checkExists) {
                return $userGroupRec;
            }
        } else {
            $this->lastError = "User group ID not found";
            $this->throwException($this->lastError, 2);
            return null;
        }

        return $userGroupRec;
    }

    // Get user group by name //
    public function getUserGroupByName(string $userGroupName, bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Check login name //
        if (!$userGroupName) {
            $this->lastError = "Empty user group name";
            $this->throwException($this->lastError, 3);
            return null;
        }

        // Search user group data //
        $userGroupNameField = $this->getUserGroupField('groupName', 'group_name');
        $result = $this->userGroupDB->cachedGet($this->userGroupTable, '*', [$userGroupNameField => $userGroupName]);
        if ($result) {
            // Normalize user group record //
            $userGroupRec = $this->normUserGroupRec($result);
        } else {
            $this->lastError = "User group name not found";
            $this->throwException($this->lastError, 4);
            return null;
        }

        return $userGroupRec;
    }

    // Create user group record to storage
    public function createUserGroup(string $userGroupName, array $fieldList = []): int {
        $this->lastError = "";

        // Check login name //
        if (!$userGroupName) {
            $this->lastError = "Empty user group name";
            $this->throwException($this->lastError, 3);
            return -3;
        }

        // Check if login name is already exists //
        $userGroupRec = $this->getUserGroupByName($userGroupName, true);
        if ($userGroupRec) {
            $this->lastError = "User group name already exists";
            $this->throwException($this->lastError, 4);
            return -4;
        }

        // Set new user grouprecord //
        $userGroupNameField = $this->getUserGroupField('groupName', 'group_name');
        $userGroupIDField   = $this->getUserGroupField('groupID', 'group_id');
        
        // Build record
        $newUserGroup = [
            $userGroupNameField => $userGroupName,
        ];
        // Convert field name from fieldList //
        $recordList = [];
        foreach($fieldList as $key => $value) {
            $newField = $this->getUserGroupField($key);
            $recordList[$newField] = $value;
        }
        $newUserGroupRec = array_merge($recordList, $newUserGroup);

        // Insert new record to DB //
        $stmt = $this->userGroupDB->insert($this->userGroupTable, $newUserGroupRec);
        if ($stmt === null) {
            $this->lastError = "Failed to create user group record";
            $this->appendSQLError();
            $this->throwException($this->lastError, 5);
            return -5;
        }

        // Get new user group id
        $userGroupID = $this->userGroupDB->pdo->lastInsertId();
        if (!$userGroupID) {
            // Fallback: query by group name
            $rec = $this->userGroupDB->cachedGet($this->userGroupTable, [$userGroupIDField], [$userGroupNameField => $userGroupName]);
            if (!$rec || !isset($rec[$userGroupIDField])) {
                $this->lastError = 'Failed to get new user group record';
                $this->throwException($this->lastError, 6);
                return -6;
            }
            $userGroupID = $rec[$userGroupIDField];
        }

        // Note: User group records are now managed by UserManager, not here
        
        return (int)$userGroupID;
    }

    public function updateUserGroup(array $fieldList, ?int $userGroupID = null): bool {
        $this->lastError = "";

        // No fields to update //
        if (empty($fieldList)) {
            return true;
        }

        // User group ID must be provided //
        if ($userGroupID === null) {
            $this->lastError = "User group ID must be provided";
            $this->throwException($this->lastError, 7);
            return false;
        }

        // Check if user group exists //        
        $userGroupIDField = $this->getUserGroupField('groupID', 'group_id');
        $existing = $this->getUserGroupByID($userGroupID, true, true);
        if (!$existing) {
            $this->lastError = "User group not found";
            $this->throwException($this->lastError, 8);
            return false;
        }

        // Normalize user group record //
        $updateRec = $this->normUserGroupRec($fieldList, false);

        // Update user group record //
        $stmt = $this->userGroupDB->update($this->userGroupTable, $updateRec, [$userGroupIDField => $userGroupID]);
        if ($stmt === null) {
            $this->lastError = "Failed to update user group";
            $this->appendSQLError();
            $this->throwException($this->lastError, 9);
            return false;
        }

        return true;
    }

    public function delUserGroup(?int $userGroupID = null): bool {
        $this->lastError = "";

        // Check if user group ID is provided //
        if ($userGroupID === null) {
            $this->lastError = "User group ID not provided";
            $this->throwException($this->lastError, 7);
            return false;
        }

        // Check if user group exists //
        $userGroupIDField = $this->getUserGroupField('groupID', 'group_id');
        $existing = $this->getUserGroupByID($userGroupID, true, true);
        if (!$existing) {
            $this->lastError = "User group not found";
            $this->throwException($this->lastError, 8);
            return false;
        }

        // Delete user group record //
        $stmt = $this->userGroupDB->delete($this->userGroupTable, [$userGroupIDField => $userGroupID]);
        if ($stmt === null || $stmt->rowCount() === 0) {
            $this->lastError = "Failed to delete user group";
            $this->appendSQLError();
            $this->throwException($this->lastError, 10);
            return false;
        }

        // Delete user group link records //
        $userGroupIDFieldLink = $this->getUserGroupLinkField('groupID', 'group_id');
        $this->userGroupDB->delete($this->userGroupLinkTable, [$userGroupIDFieldLink => $userGroupID]);

        return true;
    }

    // Check if user is in group //
    public function isUserInGroup(int $userID, int $userGroupID, bool $forceLoad = false): bool {
        $this->lastError = "";

        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        $userGroupIDField = $this->getUserGroupLinkField('groupID', 'group_id');

        $result = $this->userGroupDB->cachedHas($this->userGroupLinkTable, [
            $userIDField => $userID,
            $userGroupIDField => $userGroupID,
        ]);

        return $result === true;
    }

    // Get users in group //
    public function getUsersInGroup(int $userGroupID, bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Search user group link records //
        $userGroupIDField = $this->getUserGroupLinkField('groupID', 'group_id');
        $linkRecords = $this->userGroupDB->cachedSelect($this->userGroupLinkTable, '*', [$userGroupIDField => $userGroupID]);
        if ($linkRecords === false || empty($linkRecords)) {
            return [];
        }

        // Get user IDs //
        $userIDs = [];
        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        foreach ($linkRecords as $record) {
            $normalized = $this->normUserGroupLinkRecord($record);
            $id = $normalized['userID'] ?? null;
            if ($id !== null) {
                $userIDs[] = (int)$id;
            }
        }
        return array_unique($userIDs);
    }

    // Get groups by user //
    public function getGroupsByUser(int $userID, bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Get group IDs //
        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        $groupIDField = $this->getUserGroupLinkField('groupID', 'group_id');
        $linkRecords = $this->userGroupDB->cachedSelect($this->userGroupLinkTable, [ $groupIDField ], [$userIDField => $userID]);
        if ($linkRecords === false || empty($linkRecords)) {
            return [];
        }

        $groupIDs = [];
        foreach ($linkRecords as $record) {
            $normalized = $this->normUserGroupLinkRecord($record);
            $groupID = $normalized['groupID'] ?? null;
            if ($groupID !== null) {
                $groupIDs[] = (int)$groupID;
            }
        }

        $groupIDs = array_unique($groupIDs);
        return (empty($groupIDs)) ? [] : $groupIDs ;
    }

    // Add user to group //
    public function addUserToGroup(int $userID, int $userGroupID): bool {
        $this->lastError = "";

        // User group ID must be provided //
        if ($userGroupID === null) {
            $this->lastError = "User group ID must be provided";
            $this->throwException($this->lastError, 7);
            return false;
        }

        // Check if user is already in group //
        if ($this->isUserInGroup($userID, $userGroupID, true)) {
            return true;
        }

        // Set criteria //
        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        $userGroupIDField = $this->getUserGroupLinkField('groupID', 'group_id');
        $criteria = [
            $userIDField => $userID,
            $userGroupIDField => $userGroupID,
        ];

        // Append new record to DB //
        $stmt = $this->userGroupDB->insert($this->userGroupLinkTable, $criteria);
            if ($stmt === null) {
                $this->lastError = "Failed to add user to group";
                $this->appendSQLError();
                $this->throwException($this->lastError, 11);
                return false;
            }

        return true;
    }

    // Remove user from group //
    public function delUserFromGroup(int $userID, ?int $userGroupID = null): bool {
        $this->lastError = "";

        // User group ID must be provided //
        if ($userGroupID === null) {
            $this->lastError = "User group ID must be provided";
            $this->throwException($this->lastError, 7);
            return false;
        }

        // Check if user is in group //
        if (!$this->isUserInGroup($userID, $userGroupID, true)) {
            return true;
        }

        // Set criteria //
        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        $userGroupIDField = $this->getUserGroupLinkField('groupID', 'group_id');
        $criteria = [
            $userIDField => $userID,
            $userGroupIDField => $userGroupID,
        ];

        // Delete record from DB //
        $stmt = $this->userGroupDB->delete($this->userGroupLinkTable, $criteria);
            if ($stmt === null) {
                $this->lastError = "Failed to remove user from group";
                $this->appendSQLError();
                $this->throwException($this->lastError, 12);
                return false;
            }

        return true;
    }

}