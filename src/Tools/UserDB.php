<?php
/**
 * User for CacheDB storage class
 * 
 * This class is used to handle user information for CacheDB storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */

namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\CacheDB;
use Paheon\MeowBase\Tools\User;

// User for CacheDB storage //
class UserDB extends User {

    protected CacheDB $userDB;

    // Constructor //
    public function __construct(CacheDB $cacheDB, array $config = []) {
        parent::__construct($config);
        $this->denyWrite = array_merge($this->denyWrite, ['userDB', 'userTable', 'dbLoaded']);

        $this->userDB = $cacheDB;
    }

    // Append SQL error to lastError if exists //
    protected function appendSQLError(): void {
        $sqlError = $this->userDB->getSQLError();
        if ($sqlError) {
            $this->lastError .= " [SQL Error: " . $sqlError . "]";
        }
    }

    // Normalize user record //
    protected function normUserRec(array $record, bool $load = true): array {
        $newRecord = [];
        $intFields = ["userID", "status"];
        $dateFields = ["loginTime", "logoutTime", 'lastActive' ];
        if ($load) {
            // Load record - convert database field names to logical field names //
            foreach($record as $fieldName => $value) {
                $key = array_search($fieldName, $this->userFields);
                if ($key !== false) {
                    if (in_array($key, $intFields)) {
                        $newRecord[$key] = (int)$value;
                    } else if (in_array($key, $dateFields)) {
                        $newRecord[$key] = ($value == '0000-00-00 00:00:00') ? 0 : (int)strtotime($value);
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
                if (isset($this->userFields[$fieldName])) {
                    if (in_array($fieldName, $dateFields)) {
                        $newRecord[$this->userFields[$fieldName]] = (empty($value)) ? "0000-00-00 00:00:00" : date('Y-m-d H:i:s', $value);
                    } else {   
                        $newRecord[$this->userFields[$fieldName]] = $value;
                    }
                } else {
                    $newRecord[$fieldName] = $value;
                }
            }
        }
        return $newRecord;
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
        
		$userIDField = $this->getUserField('userID', 'user_id');
        if ($forceLoad) {
            // Disable cache for force load //
            $orgEnableCache = $this->userDB->enableCache;
            $this->userDB->enableCache = false;
        }    
        $result = $this->userDB->cachedGet($this->userTable, '*', [$userIDField => $userID]);
        if ($forceLoad) $this->userDB->enableCache = $orgEnableCache;  // Restore enable cache
        if (!$result) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 2);
            return null;
        }

        $userRec = $this->normUserRec($result);
        return $userRec;
    }

    // Get Multiple user data by ID //
    public function getMultiUserByID(array $userID = [], bool $forceLoad = false): ?array {
        $this->lastError = "";
        // Load user records //
		$userIDField = $this->getUserField('userID', 'user_id');
        $criteria = empty($userID) ? null : [$userIDField => $userID];
        if ($forceLoad) {
            // Disable cache for force load //
            $orgEnableCache = $this->userDB->enableCache;
            $this->userDB->enableCache = false;
        }
        $userRecList = [];
        $currObj = $this;
        $result = $this->userDB->cachedSelect($this->userTable, '*', $criteria, null, function ($data) use (&$userRecList, &$currObj) {
            $userRecList[] = $currObj->normUserRec($data);
        });
        if ($forceLoad) $this->userDB->enableCache = $orgEnableCache;  // Restore enable cache
        if (empty($userRecList)) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 2);
            return [];
        }
        return $userRecList;
    }

    // Get user by login Name //
    public function getUserByLoginName(string $loginName, bool $forceLoad = false): ?array {
        $this->lastError = "";

        // Check login name //
        if (!$loginName) {
            $this->lastError = "Empty login name";
            $this->throwException($this->lastError, 3);
            return null;
        }

		$loginNameField = $this->getUserField('loginName', 'login_name');
        if ($forceLoad) {
            // Disable cache for force load //
            $orgEnableCache = $this->userDB->enableCache;
            $this->userDB->enableCache = false;
        }    
        $result = $this->userDB->cachedGet($this->userTable, '*', [$loginNameField => $loginName]);
        if ($forceLoad) $this->userDB->enableCache = $orgEnableCache;  // Restore enable cache
        if (!$result) {
            $this->lastError = "Login name not found";
            $this->throwException($this->lastError, 4);
            return null;
        }

        $userRec = $this->normUserRec($result);
        return $userRec;
    }

    // Create user record to DB //
	public function createUser(string $loginName, string $passwordHash, array $fieldList = []): int {
        $this->lastError = "";

        // Check login name //
        if (!$loginName) {
            $this->lastError = "Empty login name";
            $this->throwException($this->lastError, 3);
            return -3;
        }

        // Check if login name is already exists //
        $loginNameField = $this->getUserField('loginName', 'login_name');
        $hasLoginName = $this->userDB->has($this->userTable, [$loginNameField => $loginName]);
        if ($hasLoginName) {
            $this->lastError = "Login name already exists";
            $this->throwException($this->lastError, 4);
            return -4;
        }

        $passwordField  = $this->getUserField('password');
        $userIDField    = $this->getUserField('userID', 'user_id');

        // Build record
        $newUser = [
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
        $newUserRec = $this->normUserRec($newUserRec, false);

        // Insert
        $stmt = $this->userDB->insert($this->userTable, $newUserRec);
        if ($stmt === null) {
            $this->lastError = 'Failed to create user record';
            $this->appendSQLError();
            $this->throwException($this->lastError, 5);
            return -5;
        }

        // Get new user id
        $userID = $this->userDB->pdo->lastInsertId();
        if (!$userID) {
            // Fallback: query by login name
            $rec = $this->userDB->cachedGet($this->userTable, [$userIDField], [$loginNameField => $loginName]);
            if (!$rec || !isset($rec[$userIDField])) {
                $this->lastError = 'Failed to get new user record';
                $this->throwException($this->lastError, 6);
                return -6;
            }
            $userID = $rec[$userIDField];
        }
        
        return (int)$userID;
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
            $this->throwException($this->lastError, 8);
            return false;
        }

        // Check if user exists //
        $userIDField = $this->getUserField('userID', 'user_id');
        if (!$this->getUserByID($userID, true, true)) {
            return false;
        }

        // Normalize user record //
        $updateRec = $this->normUserRec($fieldList, false);
        //$updateRec[$userIDField] = $userID;

        // Update User Record //
        $stmt = $this->userDB->update($this->userTable, $updateRec, [$userIDField => $userID]);
        if ($stmt === null) {
            $this->lastError = "Failed to update user record";
            $this->appendSQLError();
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

        // Delete User Record //
        $userIDField = $this->getUserField('userID', 'user_id');
        $stmt = $this->userDB->delete($this->userTable, [$userIDField => $userID]);
        if ($stmt === null || $stmt->rowCount() === 0) {
            $this->lastError = "Failed to delete user record";
            $this->appendSQLError();
            $this->throwException($this->lastError, 9);
            return false;
        }

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
            $this->throwException($this->lastError, 8);
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