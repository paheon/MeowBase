<?php
/**
 * User Group class
 * 
 * This class is a base class to handle user group information.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */
namespace Paheon\MeowBase\Tools;
use Paheon\MeowBase\ClassBase;

class UserGroup {

    use ClassBase;

    // Settings //
    protected array $userGroupFields = [
        "groupID" => "group_id",
        "groupName" => "group_name",
        "groupDesc" => "group_desc",
    ];
    protected string $userGroupTable = "users_groups";
    protected string $userGroupLinkTable = "users_groups_link";
    protected array $userGroupLinkFields = [
        "userID" => "user_id",
        "groupID" => "group_id",
    ];

    // Constructor //
    public function __construct(array $config = []) {
        $this->denyWrite = array_merge($this->denyWrite, ['userGroupFields', 'userGroupLinkTable', 'userGroupLinkFields']);

        // User Group Table //
        if (isset($config['userGroupTable'])) {
            $this->userGroupTable = $config['userGroupTable'];
        }
        if (isset($config['userGroupFields']) && is_array($config['userGroupFields'])) {
            $this->userGroupFields = array_merge($this->userGroupFields, $config['userGroupFields']);
        }

        // User Group Link Table //
        if (isset($config['userGroupLinkTable'])) {
            $this->userGroupLinkTable = $config['userGroupLinkTable'];
        }
        if (isset($config['userGroupLinkFields']) && is_array($config['userGroupLinkFields'])) {
            $this->userGroupLinkFields = array_merge($this->userGroupLinkFields, $config['userGroupLinkFields']);
        }
    }

    // Get Group field name //
    public function getUserGroupField(string $fieldName, ?string $default = null): string {
        return $this->userGroupFields[$fieldName] ?? ($default ?? $fieldName);
    }

    // Get Group link field name //
    public function getUserGroupLinkField(string $fieldName, ?string $default = null): string {
        return $this->userGroupLinkFields[$fieldName] ?? ($default ?? $fieldName);
    }

    //---------- Sudo Abstract Methods (All dummy methods) ----------//

    // Copy user group from storage
    public function getUserGroupByID(int $userGroupID, bool $forceLoad = false, bool $checkExists = false): ?array {
        return null;
    }

    // Get user group by name //
    public function getUserGroupByName(string $userGroupName, bool $forceLoad = false): ?array {
        return null;
    }

    // Create user group record to storage
    public function createUserGroup(string $userGroupName, array $fieldList = []):int { 
        return -1;
    }

    // Update user group record to storage
    public function updateUserGroup(array $fieldList, ?int $userGroupID = null): bool { 
        return false;
    }

    // Delete user group record from storage
    public function delUserGroup(?int $userGroupID = null): bool { 
        return false;
    }

    // Check if user is in group
    public function isUserInGroup(int $userID, int $userGroupID, bool $forceLoad = false): bool { 
        return false;
    }

    // Get users in group
    public function getUsersInGroup(int $userGroupID, bool $forceLoad = false): ?array {
        return null; 
    }

    // Get groups by user
    public function getGroupsByUser(int $userID, bool $forceLoad = false): ?array {
        return null;
    }

    // Add user to group
    public function addUserToGroup(int $userID, int $userGroupID): bool {
        return false;
    }

    // Remove user from group
    public function delUserFromGroup(int $userID, ?int $userGroupID = null): bool {
        return false;
    }

}