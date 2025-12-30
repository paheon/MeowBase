<?php
/**
 * User for CSV storage class
 * 
 * This class is used to manage user information for CSV storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */

namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\Tools\CsvDB;
use Paheon\MeowBase\Tools\UserGroup;

class UserGroupCSV extends UserGroup {

    // CSV Object //
    protected CsvDB     $userGroupDB;
    protected CsvDB     $userGroupLinkDB;

    // CSV status //
    protected bool      $dbGroupLoaded = false;
    protected bool      $dbGroupLinkLoaded = false;

    // CSV File //
    protected string    $csvPath = "";
    protected string    $userGroupTableFile = "";
    protected string    $userGroupLinkTableFile = "";


    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->denyWrite = array_merge($this->denyWrite, ['userGroupDB', 'userGroupLinkDB', 'dbLoaded', 'csvPath', 'userGroupTableFile', 'userGroupLinkTableFile']);

        // Build CSV file path from parent class //
        $this->csvPath = $config['csvDB']['path'] ?? ".";
        $prefix = rtrim($this->csvPath, '/\\') . "/";
        $this->userGroupTableFile = $prefix . $this->userGroupTable . ".csv";
        $this->userGroupLinkTableFile = $prefix . $this->userGroupLinkTable . ".csv";

        // Build CSV header //
        $this->userGroupDB = new CsvDB($this->userGroupTableFile, array_values($this->userGroupFields));
        $this->userGroupLinkDB = new CsvDB($this->userGroupLinkTableFile, array_values($this->userGroupLinkFields));
    }

    // Normalize user grouprecord //
    protected function normUserGroupRec(array $record, bool $load = true): array {
        $newRecord = [];
        if ($load) {
            // Load record //
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
            // Save record //
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
            // Load record //
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
            // Save record //
            foreach($record as $fieldName => $value) {
                if (isset($this->userGroupLinkFields[$fieldName])) {
                    $newRecord[$this->userGroupLinkFields[$fieldName]] = $value;
                }
            }
        }
        return $newRecord;
    }

    // Load all user group records from database //
    protected function loadUserGroupRec(bool $forceLoad = false):bool {
        $this->lastError = "";

        // Load user database //
        if (!$this->dbGroupLoaded || $forceLoad) {
            if ($this->userGroupDB->load() != 0) {
                $this->dbGroupLoaded = false;
                $this->lastError = "Failed to load user group data";
                $this->throwException($this->lastError, 1);
                return false;
            }
            $this->dbGroupLoaded = true;
        }
        return true;
    }
    
    // Load all user group link records from database //
    protected function loadUserGroupLinkRec(bool $forceLoad = false):bool {
        $this->lastError = "";

        // Load user database //
        if (!$this->dbGroupLinkLoaded || $forceLoad) {
            if ($this->userGroupLinkDB->load() !== 0) {
                $this->dbGroupLinkLoaded = false;
                $this->lastError = "Failed to load user group link data";
                $this->throwException($this->lastError, 1);
                return false;
            }
            $this->dbGroupLinkLoaded = true;
        }
        return true;
    }

        // Delete duplicated user record //
    protected function delDupUserGroupRec(array $condition): bool {
        $this->lastError = "";

        // Search duplacted user record //
        $userGroupRec = $this->userGroupDB->search($condition, CSVDB::REC_ROW_ID);
        if (!$userGroupRec) {
            $this->lastError = "Failed to get new user group record";
            $this->throwException($this->lastError, 6);
            return false;
        }

        // Check user group duplication and remove duplication //
        if (count($userGroupRec) > 1) {
            $uniqueRec[0] = end($userGroupRec);
            $uniqueRecKey = key($userGroupRec);
            unset($userGroupRec[$uniqueRecKey]); // Prevent to remove the most updated record //
            foreach($userGroupRec as $rec) {
                $this->userGroupDB->queueDelete([CSVDB::REC_ROW_ID => $rec[CSVDB::REC_ROW_ID]]);
            }
            
            $result = $this->userGroupDB->runQueue();
            if ($result['exitCode'] ?? 1 !== 0) {
                $this->lastError = "Failed to delete duplicated user group record";
                $this->throwException($this->lastError, 15);
                return false;
            }
        }
        return true;
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

        // Load database if not loaded //
        if (!$this->loadUserGroupRec(true)) {
            $this->lastError = "Failed to load user group data";
            $this->throwException($this->lastError, 13);
            return null;
        }

        // Search user data //
        $userGroupIDField = $this->getUserGroupField('groupID', 'group_id');
        $data = $this->userGroupDB->search([ $userGroupIDField => $userGroupID ]);
        if ($data) {
            // Normalize user group record //
            $userGroupRec = $this->normUserGroupRec($data[0]);
            if ($checkExists) {
                return $userGroupRec;
            }    
            if (count($data) > 1) {
                // No exception or error throw but leave message in lastError //
                $this->lastError = "Multiple user group records found";
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

        // Load database if not loaded //
        if (!$this->loadUserGroupRec(true)) {
            $this->lastError = "Failed to load user group data";
            $this->throwException($this->lastError, 13);
            return null;
        }

        // Search user data //
        $userGroupNameField = $this->getUserGroupField('groupName', 'group_name');
        $data = $this->userGroupDB->search([ $userGroupNameField => $userGroupName ]);
        if ($data) {
            // Normalize user group record //
            $userGroupRec = $this->normUserGroupRec($data[0]);
            if (count($data) > 1) {
                // No exception or error throw but leave message in lastError //
                $this->lastError = "Multiple user group records found";
            }
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

        // Get new user group ID //
        $userGroupIDField   = $this->getUserGroupField('groupID', 'group_id');
        $userGroupID = (int)$this->userGroupDB->getMax($userGroupIDField) + 1;

        // Set new user grouprecord //
        $userGroupNameField = $this->getUserGroupField('groupName', 'group_name');
        $newUserGroup = [
            $userGroupIDField    => $userGroupID,
            $userGroupNameField => $userGroupName,
        ];
        // Convert field name from fieldList //
        $recordList = [];
        foreach($fieldList as $key => $value) {
            $newField = $this->getUserGroupField($key);
            $recordList[$newField] = $value;
        }
        $newUserGroupRec = array_merge($recordList, $newUserGroup);

        // Append new record to DB //
        $this->userGroupDB->clearQueue();
        $this->userGroupDB->queueAppend($newUserGroupRec);
        $result = $this->userGroupDB->runQueue();
        if ($result['exitCode'] ?? 1 !== 0) {
            $this->lastError = "Failed to create user group record";
            $this->throwException($this->lastError, 5);
            return -5;
        }

        // Reload user group record again ensure all reocrds are updated //
        $userGroupRec = $this->getUserGroupByName($userGroupName, true);
        if (!$userGroupRec) {
            $this->lastError = "Failed to get new user group record";
            $this->throwException($this->lastError, 6);
            return -6;
        }        

        // Check duplication //
        if (!$this->lastError) {
            // Remove duplicated user record //
            $userGroupNameField = $this->getUserGroupField('groupName', 'group_name');
            $this->delDupUserGroupRec([ "OR" => [ $userGroupNameField => $userGroupName, $userGroupIDField => $userGroupID ]]);
            // Reload user record again ensure all reocrds are updated //
            $userGroupRec = $this->getUserGroupByName($userGroupName, true);

        }
        $userGroupID = (int)($userGroupRec["groupID"] ?? -6);
        
        return $userGroupID;
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

        // Load user group database //
        if (!$this->loadUserGroupRec(true)) {
            $this->lastError = "Failed to load user group database";
            $this->throwException($this->lastError, 13);
            return false;
        }

        // Check if user group exists //        
        $userGroupIDField = $this->getUserGroupField('groupID', 'group_id');
        $existing = $this->userGroupDB->search([$userGroupIDField => $userGroupID]);
        if (!$existing) {
            $this->lastError = "User group not found";
            $this->throwException($this->lastError, 8);
            return false;
        }

        // Update user group record //
        $this->userGroupDB->clearQueue();
        $this->userGroupDB->queueUpdate([$userGroupIDField => $userGroupID], $fieldList);
        $result = $this->userGroupDB->runQueue();
        if (($result['exitCode'] ?? 1) !== 0) {
            $this->lastError = "Failed to update user group";
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

        // Load user group database //
        if (!$this->loadUserGroupRec(true)) {
            $this->lastError = "Failed to load user group database";
            $this->throwException($this->lastError, 13);
            return false;
        }

        // Check if user group exists //
        $userGroupIDField = $this->getUserGroupField('groupID', 'group_id');
        $existing = $this->userGroupDB->search([$userGroupIDField => $userGroupID]);
        if (!$existing) {
            $this->lastError = "User group not found";
            $this->throwException($this->lastError, 8);
            return false;
        }

        // Delete user group record //
        $this->userGroupDB->clearQueue();
        $this->userGroupDB->queueDelete([$userGroupIDField => $userGroupID]);
        $result = $this->userGroupDB->runQueue();
        if (($result['exitCode'] ?? 1) !== 0) {
            $this->lastError = "Failed to delete user group";
            $this->throwException($this->lastError, 10);
            return false;
        }

        // Delete user group link records //
        if (!$this->loadUserGroupLinkRec(true)) {
            $userGroupIDFieldLink = $this->getUserGroupLinkField('groupID', 'group_id');
            $this->userGroupLinkDB->clearQueue();
            $this->userGroupLinkDB->queueDelete([$userGroupIDFieldLink => $userGroupID]);
            $this->userGroupLinkDB->runQueue();
        }

        return true;
    }

    // Check if user is in group //
    public function isUserInGroup(int $userID, int $userGroupID, bool $forceLoad = false): bool {
        $this->lastError = "";

        // Load user group link database //
        if (!$this->loadUserGroupLinkRec($forceLoad)) {
            $this->lastError = "Failed to load user group link database";
            $this->throwException($this->lastError, 14);
            return false;
        }

        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        $userGroupIDField = $this->getUserGroupLinkField('groupID', 'group_id');

        $result = $this->userGroupLinkDB->search([
            $userIDField => $userID,
            $userGroupIDField => $userGroupID,
        ]);

        return $result !== false && !empty($result);
    }

    // Get users in group //
    public function getUsersInGroup(int $userGroupID, bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Load user group link database //
        if (!$this->loadUserGroupLinkRec($forceLoad)) {
            $this->lastError = "Failed to load user group link database";
            $this->throwException($this->lastError, 25);
            return null;
        }

        // Search user group link records //
        $userGroupIDField = $this->getUserGroupLinkField('groupID', 'group_id');
        $linkRecords = $this->userGroupLinkDB->search([ $userGroupIDField => $userGroupID ]);
        if ($linkRecords === false || empty($linkRecords)) {
            return [];
        }

        // Get user IDs //
        $userIDs = [];
        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        foreach ($linkRecords as $record) {
            $id = $record[$userIDField] ?? null;
            if ($id !== null) {
                $userIDs[] = $id;
            }
        }
        return array_unique($userIDs);

    }


    // Get groups by user //
    public function getGroupsByUser(int $userID, bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Load user group link database //
        if (!$this->loadUserGroupLinkRec($forceLoad)) {
            $this->lastError = "Failed to load user group link database";
            $this->throwException($this->lastError, 25);
            return null;
        }

        // Get group IDs //
        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        $linkRecords = $this->userGroupLinkDB->search([ $userIDField => $userID ]);
        if ($linkRecords === false || empty($linkRecords)) {
            return [];
        }

        $groupIDs = [];
        $groupIDField = $this->getUserGroupLinkField('groupID', 'group_id');
        foreach ($linkRecords as $record) {
            $normalized = $this->normUserGroupLinkRecord($record);
            $groupID = $normalized['groupID'] ?? null;
            if ($groupID !== null) {
                $groupIDs[] = $groupID;
            }
        }
        return array_unique($groupIDs);
    }

    // Add user to group //
    public function addUserToGroup(int $userID, int $userGroupID): bool {
        $this->lastError = "";

        // Check if user is already in group //
        if ($this->isUserInGroup($userID, $userGroupID, true)) {
            return true;
        }

        // Set criteria //
        $userIDField = $this->getUserGroupLinkField('userID', 'user_id');
        $userGroupIDField = $this->getUserGroupLinkField('groupID', 'user_group_id');
        $criteria = [
            $userIDField => $userID,
            $userGroupIDField => $userGroupID,
        ];

        // Append new record to DB //
        $this->userGroupLinkDB->clearQueue();
        $this->userGroupLinkDB->queueAppend($criteria);
        $result = $this->userGroupLinkDB->runQueue();
        if (($result['exitCode'] ?? 1) !== 0) {
            $this->lastError = "Failed to add user to group";
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
        $this->userGroupLinkDB->clearQueue();
        $this->userGroupLinkDB->queueDelete($criteria);
        $result = $this->userGroupLinkDB->runQueue();
        if (($result['exitCode'] ?? 1) !== 0) {
            $this->lastError = "Failed to remove user from group";
            $this->throwException($this->lastError, 12);
            return false;
        }

        return true;
    }

}