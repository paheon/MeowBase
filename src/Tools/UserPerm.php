<?php
/**
 * User Permission class
 * 
 * This class is a base class to handle user permission information.
 * It supports both CSV and Database storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;

// Permission support class //
class UserPerm {

    use ClassBase;

    // Table/File names //
    protected string $userPermTable = "users_perm";
    protected string $userGroupPermTable = "users_groups_perm";

    // Field mappings //
    protected array $userPermFields = [
        "userID" => "user_id",
        "item" => "item",
        "permission" => "permission",
        "value" => "value",
    ];
    protected array $userGroupPermFields = [
        "groupID" => "group_id",
        "item" => "item",
        "permission" => "permission",
        "value" => "value",
    ];
   
    // Constructor //
    public function __construct(array $config = []) {
        $this->denyWrite = array_merge($this->denyWrite, [
            'userPermTable', 'userGroupPermTable', 'userPermFields', 'userGroupPermFields'
        ]);

        // Table names //
        if (isset($config['userPermTable'])) {
            $this->userPermTable = $config['userPermTable'];
        }
        if (isset($config['userGroupPermTable'])) {
            $this->userGroupPermTable = $config['userGroupPermTable'];
        }
        
        // Field mappings //
        if (isset($config['userPermFields']) && is_array($config['userPermFields'])) {
            $this->userPermFields = array_merge($this->userPermFields, $config['userPermFields']);
        }   
        if (isset($config['userGroupPermFields']) && is_array($config['userGroupPermFields'])) {
            $this->userGroupPermFields = array_merge($this->userGroupPermFields, $config['userGroupPermFields']);
        }
    }

    // Get permission field name //
    public function getUserPermField(string $fieldName, ?string $default = null): string {
        return $this->userPermFields[$fieldName] ?? ($default ?? $fieldName);
    }

    public function getUserGroupPermField(string $fieldName, ?string $default = null): string {
        return $this->userGroupPermFields[$fieldName] ?? ($default ?? $fieldName);
    }

    // Reset permission records //
    public function reset(): void {
        // No records to reset - records are managed by UserManager
    }

    //---------- Sudo Abstract Methods (All dummy methods) ----------//

    // Load user permission from storage (returns array of permissions: [permission => value])
    public function getUserPerm(int $userID, string $item): ?array {
        return null;
    }

    // Save user permission to storage
    public function setUserPerm(int $userID, string $item, string $permission, int $value): bool {
        return false;
    }

    // Delete user permission from storage
    public function delUserPerm(int $userID, string $item, ?string $permission = null): bool {
        return false;
    }

    // Load group permission from storage (returns array of permissions: [permission => value])
    public function getGroupPerm(int $userGroupID, string $item): ?array {
        return null;
    }

    // Save group permission to storage
    public function setGroupPerm(int $userGroupID, string $item, string $permission, int $value): bool {
        return false;
    }

    // Delete group permission from storage
    public function delGroupPerm(int $userGroupID, string $item, ?string $permission = null): bool {
        return false;
    }
}
