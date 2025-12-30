<?php
/**
 * User for CSV storage class
 * 
 * This class is used to handle user information for CSV storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */

namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\Tools\CsvDB;
use Paheon\MeowBase\Tools\User;

// User for CSV storage //
class UserCSV extends User {

    // CSV Object //
    protected CsvDB     $userDB;

    // CSV Status //
    protected bool      $dbLoaded = false;

    // CSV File //
    protected string    $csvPath = "";
    protected string    $userTableFile = "";

    // Constructor //
    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->denyWrite = array_merge($this->denyWrite, ['userDB', 'dbLoaded', 'csvPath', 'userTableFile']);
        
        // Build CSV file path from config
        $this->csvPath = $config['csvDB']['path'] ?? ".";
        $prefix = rtrim($this->csvPath, '/\\') . "/";
        $this->userTableFile = $prefix . $this->userTable . ".csv";

        // Build CSV header //
        $this->userDB = new CsvDB($this->userTableFile, array_values($this->userFields));
    }

    // Normalize user record //
    protected function normUserRec(array $record, bool $load = true): array {
        $newRecord = [];
        $intField = ["userID", "status", "loginTime", "logoutTime", "lastActive"];
        if ($load) {
            // Load record //
            foreach($record as $fieldName => $value) {
                $key = array_search($fieldName, $this->userFields);
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
                if (isset($this->userFields[$fieldName])) {
                    if (in_array($fieldName, $intField)) {
                        $newRecord[$this->userFields[$fieldName]] = (empty($value)) ? 0 : (int)$value;
                    } else {
                        $newRecord[$this->userFields[$fieldName]] = $value;
                    }
                }
            }
        }
        return $newRecord;
    }

    // Load all user records from database //
    protected function loadUserRec(bool $forceLoad = false):bool {
        $this->lastError = "";

        // Load user database //
        if (!$this->dbLoaded || $forceLoad) {
            if ($this->userDB->load() !== 0) {
                $this->dbLoaded = false;
                $this->lastError = "Failed to load user data";
                $this->throwException($this->lastError, 1);
                return false;
            }
            $this->dbLoaded = true;
        }
        return true;
    }

    // Delete duplicated user record //
    protected function delDupUserRec(array $condition): bool {
        $this->lastError = "";

        // Search duplacted user record //
        $userRec = $this->userDB->search($condition, CSVDB::REC_ROW_ID);
        if (!$userRec) {
            $this->lastError = "Failed to get new user record";
            $this->throwException($this->lastError, 6);
            return false;
        }

        // Check login name duplication and remove duplication //
        if (count($userRec) > 1) {
            $uniqueRec[0] = end($userRec);
            $uniqueRecKey = key($userRec);
            unset($userRec[$uniqueRecKey]); // Prevent to remove the most updated record //
            foreach($userRec as $rec) {
                $this->userDB->queueDelete([CSVDB::REC_ROW_ID => $rec[CSVDB::REC_ROW_ID]]);
            }
            
            $result = $this->userDB->runQueue();
            if ($result['exitCode'] ?? 1 !== 0) {
                $this->lastError = "Failed to delete duplicated user record";
                $this->throwException($this->lastError, 11);
                return false;
            }
        }
        return true;
    }

    //---------- Sudo Abstract Methods  ----------//

    // Get user data by ID //
    public function getUserByID(int $userID, bool $forceLoad = false, bool $checkExists = false): ?array {
        $this->lastError = "";

        // Check User ID //
        if ($userID < 1) {
            $this->lastError = "User ID is invalid";
            $this->throwException($this->lastError, 1);
            return null;
        } 

        // Load database if not loaded //
        if (!$this->loadUserRec($forceLoad)) {
            $this->lastError = "Failed to load user data";
            $this->throwException($this->lastError, 10);
            return null;
        }

        // Search user data //
        $userIDField = $this->getUserField('userID', 'user_id');
        $data = $this->userDB->search([$userIDField => $userID]);
        if (!$data) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 2);
            return null;
        }

        // Normalize user record //
        $userRec = $this->normUserRec($data[0]);
        if ($checkExists) {
            return $userRec;
        }    
        if (count($data) > 1) {
            // No exception or error throw but leave message in lastError //
            $this->lastError = "Multiple user records found";
        }
        return $userRec;
    }

    // Get multiple user data by ID //
    public function getMultiUserByID(array $userID = [], bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Load database if not loaded //
        if (!$this->loadUserRec($forceLoad)) {
            $this->lastError = "Failed to load user data";
            $this->throwException($this->lastError, 10);
            return null;
        }

        // Search user data //
        $userIDField = $this->getUserField('userID', 'user_id');
        $criteria = (empty($userID)) ? [] : [$userIDField => $userID];
        $data = $this->userDB->search($criteria);
        if (!$data) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 2);
            return null;
        }

        // Normalize user record //
        $userRecList = [];
        foreach ($data as $idx => $rec) {
            $userRecList[$idx] = $this->normUserRec($rec);
        }
        return $userRecList;
    }
    
    // Get user by login Name //
    public function getUserByLoginName(string $loginName, bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Check login name //
        if (!$loginName) {
            $this->lastError = "Empty login name";
            $this->throwException($this->lastError, 5);
            return null;
        }

        // Load database if not loaded //
        if (!$this->loadUserRec($forceLoad)) {
            $this->lastError = "Failed to load user data";
            $this->throwException($this->lastError, 10);
            return null;
        }

        // Search user data //
        $loginNameField = $this->getUserField('loginName', 'login_name');
        $data = $this->userDB->search([ $loginNameField => $loginName ]);
        if ($data) {
            // Normalize user record //
            $userRec = $this->normUserRec($data[0]);
            if (count($data) > 1) {
                // No exception or error throw but leave message in lastError //
                $this->lastError = "Multiple user records found";
            }
        } else {
            $this->lastError = "Login name not found";
            $this->throwException($this->lastError, 4);
            return null;
        }

        return $userRec;
    }

    // Create user record to DB //
    public function createUser(string $loginName, string $passwordHash, array $fieldList = []):int {
        $this->lastError = "";
        // Check login name //
        if (!$loginName) {
            $this->lastError = "Empty login name";
            $this->throwException($this->lastError, 3);
            return -3;
        }

        // Check if login name is already exists //
        $userRec = $this->getUserByLoginName($loginName, true);
        if ($userRec) {
            $this->lastError = "Login name already exists";
            $this->throwException($this->lastError, 4);
            return -4;
        }

        // Get new user ID //
        $userIDField   = $this->getUserField('userID', 'user_id');
        $userID = (int)$this->userDB->getMax($userIDField) + 1;

        // Set new user record //
        $passwordField = $this->getUserField('password');
        $loginNameField = $this->getUserField('loginName', 'login_name');
        $newUser = [
            $userIDField    => $userID,
            $loginNameField => $loginName,
            $passwordField  => $passwordHash,
        ];
        // Convert field name from fieldList //
        $recordList = [];
        foreach($fieldList as $key => $value) {
            $newField = $this->getUserField($key);
            $recordList[$newField] = $value;
        }
        $newUserRec = array_merge($recordList, $newUser);

        // Append new record to DB //
        $this->userDB->clearQueue();
        $this->userDB->queueAppend($newUserRec);
        $result = $this->userDB->runQueue();
        if ($result['exitCode'] ?? 1 !== 0) {
            $this->lastError = "Failed to create user record: ". $this->userDB->lastError;
            $this->throwException($this->lastError, 5);
            return -5;
        }        

        // Reload user record again ensure all reocrds are updated //
        $userRec = $this->getUserByLoginName($loginName);
        if (!$userRec) {
            $this->lastError = "Failed to get new user record". ($this->lastError ? ", Error: ".$this->lastError : "");
            $this->throwException($this->lastError, 6);
            return -6;
        }        

        // Check duplication //
        if ($this->lastError) {
            // Remove duplicated user record //
            $loginNameField = $this->getUserField('loginName', 'login_name');
            $this->delDupUserRec([ "OR" => [ $loginNameField => $loginName, $userIDField => $userID ]]);
            // Reload user record again ensure all reocrds are updated //
            $userRec = $this->getUserByLoginName($loginName, true);
        }
        $userID = (int)($userRec['userID'] ?? -6);
        
        return $userID;
    }

    // Update user record to DB //
    public function updateUser(array $fieldList, ?int $userID = null): bool {
        $this->lastError = "";

        // No fields to update //
        if (empty($fieldList)) {
            return true;
        }
        
        // Get current user ID - must be provided //
        if ($userID === null) {
            $this->lastError = "User ID must be provided";
            $this->throwException($this->lastError, 10);
            return false;
        }

        // Check if user exists //
        $userIDField = $this->getUserField('userID', 'user_id');
        if (!$this->getUserByID($userID, true, true)) {
            return false;
        }

        // Normalize user record //
        $updateRec = $this->normUserRec($fieldList, false);
        $updateRec[$userIDField] = $userID;

        // Update User Record //
        $this->userDB->clearQueue();
        $this->userDB->queueUpdate([$userIDField => $userID], $updateRec);
        $result = $this->userDB->runQueue();
        if ($result['exitCode'] ?? 1 !== 0) {
            $this->lastError = "Failed to update user record". ($this->lastError ? ", Error: ".$this->lastError : "");
            $this->throwException($this->lastError, 7);
            return false;
        }

        // Note: User records are now managed by UserManager, not here

        return true;
    }

    // Delete user record from DB //
    public function delUser(?int $userID = null): bool {
        $this->lastError = "";

        // Get current user ID - must be provided //
        if ($userID === null) {
            $this->lastError = "User ID must be provided";
            $this->throwException($this->lastError, 8);
            return false;
        }

        // Check if user exists //
        if (!$this->getUserByID($userID, true, true)) {
            return false;
        }

        // Update User Record //
        $userIDField = $this->getUserField('userID', 'user_id');
        $this->userDB->clearQueue();
        $this->userDB->queueDelete([$userIDField => $userID]);
        $result = $this->userDB->runQueue();
        if ($result['exitCode'] ?? 1 !== 0) {
            // Just reflect the error from CsvDB //
            $this->lastError = "Failed to delete user record: ".$this->userDB->lastError;
            $this->throwException($this->lastError, 9);
            return false;
        }

        // Note: User records are now managed by UserManager, not here
        return true;
    }

    // Update password //
    public function updatePassword(string $passwordHash, ?int $userID = null): bool {
        // Set password Field //
        $updateRec = [
            "password" => $passwordHash,
        ];
        return $this->updateUser($updateRec, $userID);
    }

    // Update user status //
    public function updateUserStatus(bool $login, int $lastActiveTime, ?string $sessionID = null, ?int $loginTime = null, ?int $userID = null): bool {
        $this->lastError = "";

        // Get current user ID - must be provided //
        if ($userID === null) {
            $this->lastError = "User ID must be provided";
            $this->throwException($this->lastError, 10);
            return false;
        }

        // Check if user exists //
        if (!$this->getUserByID($userID, true, true)) {
            return false;
        }

        // Prepare update record //
        if ($login) {
            $updateRec = [
                "status"     => self::USER_STATUS_LOGIN,
                "lastActive" => $lastActiveTime,
            ];
            if ($sessionID !== null) {
                $updateRec["sessionID"] = $sessionID;
            }
            if ($loginTime !== null) {
                $updateRec["loginTime"] = $loginTime;
            }
        } else {
            $updateRec = [
                "status" => self::USER_STATUS_LOGOUT,
                "logoutTime" => $lastActiveTime,
            ];
        }

        // Update user from DB //
        $result = $this->updateUser($updateRec, $userID);

        return $result;
    }

}