<?php
/**
 * User Permission for CacheDB storage class
 * 
 * This class is used to manage user permissions for CacheDB storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\Tools\UserPerm;
use Paheon\MeowBase\CacheDB;

// User Permission for Database storage //
class UserPermDB extends UserPerm {

    // CacheDB Object //
    protected ?CacheDB $userPermDB = null;

    // Constructor //
    public function __construct(CacheDB &$cacheDB, array $config = []) {
        // Call parent constructor //
        parent::__construct($config);
        $this->denyWrite = array_merge($this->denyWrite, [
            'userPermDB'
        ]);

        $this->userPermDB = &$cacheDB;
    }

    // Append SQL error to lastError if exists //
    protected function appendSQLError(): void {
        $sqlError = $this->userPermDB->getSQLError();
        if ($sqlError) {
            $this->lastError .= " [SQL Error: " . $sqlError . "]";
        }
    }

    // Normalize user permission record //
    protected function normUserPermRec(array $record, bool $load = true): array {
        $newRecord = [];
        if ($load) {
            // Load record - convert database field names to logical field names //
            $intField = ["userID", "value"];
            foreach($record as $fieldName => $value) {
                $key = array_search($fieldName, $this->userPermFields);
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
                if (isset($this->userPermFields[$fieldName])) {
                    $newRecord[$this->userPermFields[$fieldName]] = $value;
                }
            }
        }
        return $newRecord;
    }

    // Normalize group permission record //
    protected function normGroupPermRec(array $record, bool $load = true): array {
        $newRecord = [];
        if ($load) {
            // Load record - convert database field names to logical field names //
            $intField = ["groupID", "value"];
            foreach($record as $fieldName => $value) {
                $key = array_search($fieldName, $this->userGroupPermFields);
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
                if (isset($this->userGroupPermFields[$fieldName])) {
                    $newRecord[$this->userGroupPermFields[$fieldName]] = $value;
                }
            }
        }
        return $newRecord;
    }

    //---------- Sudo Abstract Methods  ----------//
    
    // Get user permission from storage (returns array: [permission => value])
    public function getUserPerm(int $userID, string $item): ?array {
        $this->lastError = "";

        // Map fields //
        $userIDField = $this->getUserPermField('userID', "user_id");
        $itemField = $this->getUserPermField('item');
        $permissionField = $this->getUserPermField('permission');
        $valueField = $this->getUserPermField('value');

        // Get user permissions for this item //
        $userPermRec = $this->userPermDB->cachedSelect(
            $this->userPermTable,
            [$permissionField, $valueField],
            [
                $userIDField => $userID,
                $itemField => $item
            ]
        );

        if ($userPermRec === false || !is_array($userPermRec) || empty($userPermRec)) {
            return [];
        }

        // Build permissions array //
        $permissions = [];
        foreach ($userPermRec as $perm) {
            // Normalize record to use logical field names
            $normPerm = $this->normUserPermRec($perm, true);
            $permName = $normPerm['permission'] ?? "";
            if ($permName !== "") {
                $permissions[$permName] = (int)($normPerm['value'] ?? 0);
            }
        }

        return $permissions;
    }

    // Save user permission to storage
    public function setUserPerm(int $userID, string $item, string $permission, int $value): bool {
        $this->lastError = "";

        // Check if record exists //
        $checkRec = $this->normUserPermRec([
            'userID' => $userID,
            'item' => $item,
            'permission' => $permission
        ], false);
        $valueField = $this->getUserPermField('value');
        $existing = $this->userPermDB->cachedGet(
            $this->userPermTable,
            [$valueField],
            $checkRec
        );

        if ($existing !== false && is_array($existing) && !empty($existing)) {
            // Update existing record //
            $updateRec = $this->normUserPermRec([
                'userID' => $userID,
                'item' => $item,
                'permission' => $permission,
                'value' => $value
            ], false);
            $stmt = $this->userPermDB->update(
                $this->userPermTable,
                [$valueField => $value],
                $checkRec
            );
            if ($stmt === null) {
                $this->lastError = "Failed to update user permission";
                $this->appendSQLError();
                $this->throwException($this->lastError, 2);
                return false;
            }
        } else {
            // Insert new record //
            $insertRec = $this->normUserPermRec([
                'userID' => $userID,
                'item' => $item,
                'permission' => $permission,
                'value' => $value
            ], false);
            $stmt = $this->userPermDB->insert(
                $this->userPermTable,
                $insertRec
            );
            if ($stmt === null) {
                $this->lastError = "Failed to insert user permission";
                $this->appendSQLError();
                $this->throwException($this->lastError, 1);
                return false;
            }
        }

        return true;
    }

    // Delete user permission from storage
    public function delUserPerm(int $userID, string $item, ?string $permission = null): bool {
        $this->lastError = "";
        
        // Build where condition //
        $deleteData = [
            'userID' => $userID,
            'item' => $item
        ];

        // If specific permission is provided, add it to where condition //
        if ($permission !== null) {
            $deleteData['permission'] = $permission;
        }

        // Normalize and delete records //
        $where = $this->normUserPermRec($deleteData, false);
        $stmt = $this->userPermDB->delete($this->userPermTable, $where);

        if ($stmt === null) {
            $this->lastError = "Failed to remove user permission";
            $this->appendSQLError();
            $this->throwException($this->lastError, 3);
            return false;
        }

        // Note: Cache is now managed by UserManager, not here

        return true;
    }

    // Get group permission from storage (returns array: [permission => value])
    public function getGroupPerm(int $userGroupID, string $item): ?array {
        $this->lastError = "";

        // Map fields //
        $groupIDField = $this->getUserGroupPermField('groupID', "group_id");
        $itemField = $this->getUserGroupPermField('item');
        $permissionField = $this->getUserGroupPermField('permission');
        $valueField = $this->getUserGroupPermField('value');

        // Get group permissions for this item //
        $groupPermRec = $this->userPermDB->cachedSelect(
            $this->userGroupPermTable,
            [$permissionField, $valueField],
            [
                $groupIDField => $userGroupID,
                $itemField => $item
            ]
        );

        if ($groupPermRec === false || !is_array($groupPermRec) || empty($groupPermRec)) {
            return [];
        }

        // Build permissions array //
        $permissions = [];
        foreach ($groupPermRec as $perm) {
            // Normalize record to use logical field names
            $normPerm = $this->normGroupPermRec($perm, true);
            $permName = $normPerm['permission'] ?? "";
            if ($permName !== "") {
                $permissions[$permName] = (int)($normPerm['value'] ?? 0);
            }
        }

        return $permissions;
    }

    // Save group permission to storage
    public function setGroupPerm(int $userGroupID, string $item, string $permission, int $value): bool {
        $this->lastError = "";

        // Check if record exists //
        $checkRec = $this->normGroupPermRec([
            'groupID' => $userGroupID,
            'item' => $item,
            'permission' => $permission
        ], false);
        $valueField = $this->getUserGroupPermField('value');
        $existing = $this->userPermDB->cachedGet(
            $this->userGroupPermTable,
            [$valueField],
            $checkRec
        );

        if ($existing !== false && is_array($existing) && !empty($existing)) {
            // Update existing record //
            $stmt = $this->userPermDB->update(
                $this->userGroupPermTable,
                [$valueField => $value],
                $checkRec
            );
            if ($stmt === null) {
                $this->lastError = "Failed to update group permission";
                $this->appendSQLError();
                $this->throwException($this->lastError, 5);
                return false;
            }
        } else {
            // Insert new record //
            $insertRec = $this->normGroupPermRec([
                'groupID' => $userGroupID,
                'item' => $item,
                'permission' => $permission,
                'value' => $value
            ], false);
            $stmt = $this->userPermDB->insert(
                $this->userGroupPermTable,
                $insertRec
            );
            if ($stmt === null) {
                $this->lastError = "Failed to insert group permission";
                $this->appendSQLError();
                $this->throwException($this->lastError, 4);
                return false;
            }
        }

        return true;
    }

    // Delete group permission from storage
    public function delGroupPerm(int $userGroupID, string $item, ?string $permission = null): bool {
        $this->lastError = '';
        
        // Build where condition //
        $deleteData = [
            'groupID' => $userGroupID,
            'item' => $item
        ];

        // If specific permission is provided, add it to where condition //
        if ($permission !== null) {
            $deleteData['permission'] = $permission;
        }

        // Normalize and delete records //
        $where = $this->normGroupPermRec($deleteData, false);
        $stmt = $this->userPermDB->delete($this->userGroupPermTable, $where);

        if ($stmt === null) {
            $this->lastError = "Failed to remove group permission";
            $this->appendSQLError();
            $this->throwException($this->lastError, 6);
            return false;
        }

        // Note: Cache is now managed by UserManager, not here

        return true;
    }

}
