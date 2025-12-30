<?php
/**
 * User Permission for CSV storage class
 * 
 * This class is used to manage user permissions for CSV storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */

namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\Tools\UserPerm;
use Paheon\MeowBase\Tools\CsvDB;

// User Permission for CSV storage //
class UserPermCSV extends UserPerm {

    protected CsvDB  $userPermDB;
    protected CsvDB  $userGroupPermDB;

    protected string $userPermTableFile = "";
    protected string $userGroupPermTableFile = "";
    protected string $csvPath = "";

    // Constructor //
    public function __construct(array $config = []) {
        // Call parent constructor //
        parent::__construct($config);

        $this->denyWrite = array_merge($this->denyWrite, [
            'userPermDB', 'userGroupPermDB', 'userPermTableFile', 'userGroupPermTableFile', 'csvPath',
        ]);
        
        // Setup configuration first //
        $this->csvPath = $config['csvDB']['path'] ?? ".";

        // Convert table name to CSV file paths //
        $prefix = rtrim($this->csvPath, '/\\') . "/";
        $this->userPermTableFile = $prefix . $this->userPermTable . ".csv";
        $this->userGroupPermTableFile = $prefix . $this->userGroupPermTable . ".csv";
        
        // Initialize CsvDB instances //
        $this->userPermDB = new CsvDB($this->userPermTableFile, array_values($this->userPermFields));
        $this->userGroupPermDB = new CsvDB($this->userGroupPermTableFile, array_values($this->userGroupPermFields));
    }

    // Normalize user permission record //
    protected function normUserPermRec(array $record, bool $load = true): array {
        $newRecord = [];
        if ($load) {
            // Load record - convert CSV field names to logical field names //
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
            // Save record - convert logical field names to CSV field names //
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
            // Load record - convert CSV field names to logical field names //
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
            // Save record - convert logical field names to CSV field names //
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

        // Load database //
        if ($this->userPermDB->load() !== 0) {
            $this->lastError = "Failed to load user permission database";
            $this->throwException($this->lastError, 1);
            return null;
        }

        // Map fields //
        $userIDField = $this->getUserPermField('userID', "user_id");
        $itemField = $this->getUserPermField('item');
        $permissionField = $this->getUserPermField('permission');
        $valueField = $this->getUserPermField('value');

        // Get user permissions for this item //
        $userPermRec = $this->userPermDB->search([
            $userIDField => (string)$userID,
            $itemField => $item
        ]);

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

        // Map fields //
        $userIDField = $this->getUserPermField('userID', "user_id");
        $itemField = $this->getUserPermField('item');
        $permissionField = $this->getUserPermField('permission');
        $valueField = $this->getUserPermField('value');

        // Load database //
        if ($this->userPermDB->load() !== 0) {
            $this->lastError = "Failed to load user permission database";
            $this->throwException($this->lastError, 3);
            return false;
        }

        // Clear queue //
        $this->userPermDB->clearQueue();

        // Delete existing record for this permission //
        $deleteRec = $this->normUserPermRec([
            'userID' => $userID,
            'item' => $item,
            'permission' => $permission
        ], false);
        $this->userPermDB->queueDelete($deleteRec);

        // Add new record //
        $appendRec = $this->normUserPermRec([
            'userID' => $userID,
            'item' => $item,
            'permission' => $permission,
            'value' => $value
        ], false);
        $this->userPermDB->queueAppend($appendRec);

        // Execute queue //
        $result = $this->userPermDB->runQueue();
        if ($result['exitCode'] !== 0) {
            $this->lastError = "Failed to save user permission";
            $this->throwException($this->lastError, 4);
            return false;
        }

        return true;
    }

    // Delete user permission from storage
    public function delUserPerm(int $userID, string $item, ?string $permission = null): bool {
        $this->lastError = "";
        
        // Map fields //
        $userIDField = $this->getUserPermField('userID', "user_id");
        $itemField = $this->getUserPermField('item');
        $permissionField = $this->getUserPermField('permission');

        // Load database //
        if ($this->userPermDB->load() !== 0) {
            $this->lastError = "Failed to load user permission database";
            $this->throwException($this->lastError, 1);
            return false;
        }

        // Clear queue //
        $this->userPermDB->clearQueue();

        // Build delete criteria //
        $deleteData = [
            'userID' => $userID,
            'item' => $item
        ];

        // If specific permission is provided, add it to criteria //
        if ($permission !== null) {
            $deleteData['permission'] = $permission;
        }

        // Normalize and queue delete //
        $criteria = $this->normUserPermRec($deleteData, false);
        $this->userPermDB->queueDelete($criteria);

        // Execute queue //
        $result = $this->userPermDB->runQueue();
        if ($result['exitCode'] !== 0) {
            $this->lastError = "Failed to remove user permission";
            $this->throwException($this->lastError, 3);
            return false;
        }

        // Note: Cache is now managed by UserManager, not here

        return true;
    }

    // Get group permission from storage (returns array: [permission => value])
    public function getGroupPerm(int $userGroupID, string $item): ?array {
        $this->lastError = "";

        // Load database //
        if ($this->userGroupPermDB->load() !== 0) {
            $this->lastError = "Failed to load group permission database";
            $this->throwException($this->lastError, 4);
            return null;
        }

        // Map fields //
        $groupIDField = $this->getUserGroupPermField('groupID', "group_id");
        $itemField = $this->getUserGroupPermField('item');

        // Get group permissions for this item //
        $groupPermRec = $this->userGroupPermDB->search([
            $groupIDField => (string)$userGroupID,
            $itemField => $item
        ]);

        if (empty($groupPermRec)) {
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

        // Map fields //
        $groupIDField = $this->getUserGroupPermField('groupID', "group_id");
        $itemField = $this->getUserGroupPermField('item');
        $permissionField = $this->getUserGroupPermField('permission');
        $valueField = $this->getUserGroupPermField('value');

        // Load database //
        if ($this->userGroupPermDB->load() !== 0) {
            $this->lastError = "Failed to load group permission database";
            $this->throwException($this->lastError, 4);
            return false;
        }

        // Clear queue //
        $this->userGroupPermDB->clearQueue();

        // Delete existing record for this permission //
        $deleteRec = $this->normGroupPermRec([
            'groupID' => $userGroupID,
            'item' => $item,
            'permission' => $permission
        ], false);
        $this->userGroupPermDB->queueDelete($deleteRec);

        // Add new record //
        $appendRec = $this->normGroupPermRec([
            'groupID' => $userGroupID,
            'item' => $item,
            'permission' => $permission,
            'value' => $value
        ], false);
        $this->userGroupPermDB->queueAppend($appendRec);

        // Execute queue //
        $result = $this->userGroupPermDB->runQueue();
        if ($result['exitCode'] !== 0) {
            $this->lastError = "Failed to save group permission";
            $this->throwException($this->lastError, 5);
            return false;
        }

        return true;
    }

    // Delete group permission from storage
    public function delGroupPerm(int $userGroupID, string $item, ?string $permission = null): bool {
        $this->lastError = "";
        
        // Map fields //
        $groupIDField = $this->getUserGroupPermField('groupID', "group_id");
        $itemField = $this->getUserGroupPermField('item');
        $permissionField = $this->getUserGroupPermField('permission');

        // Load database //
        if ($this->userGroupPermDB->load() !== 0) {
            $this->lastError = "Failed to load group permission database";
            $this->throwException($this->lastError, 4);
            return false;
        }

        // Clear queue //
        $this->userGroupPermDB->clearQueue();

        // Build delete criteria //
        $deleteData = [
            'groupID' => $userGroupID,
            'item' => $item
        ];

        // If specific permission is provided, add it to criteria //
        if ($permission !== null) {
            $deleteData['permission'] = $permission;
        }

        // Normalize and queue delete //
        $criteria = $this->normGroupPermRec($deleteData, false);
        $this->userGroupPermDB->queueDelete($criteria);

        // Execute queue //
        $result = $this->userGroupPermDB->runQueue();
        if ($result['exitCode'] !== 0) {
            $this->lastError = "Failed to remove group permission";
            $this->throwException($this->lastError, 6);
            return false;
        }

        // Note: Cache is now managed by UserManager, not here

        return true;
    }

}
