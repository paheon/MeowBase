<?php
/**
 * User class
 * 
 * This class is a base class to handle user information.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */
namespace Paheon\MeowBase\Tools;
use Paheon\MeowBase\ClassBase;

class User {

    use ClassBase;

    // Constants //
    public const USER_STATUS_ACTIVE   = 0;
    public const USER_STATUS_LOGIN    = 1;
    public const USER_STATUS_LOGOUT   = 2;
    public const USER_STATUS_EXPIRED  = 3;
    public const USER_STATUS_DISABLED = 4;
    public const USER_STATUS_DELETED  = 5;

    // Data settings //
    protected string $userTable = "users";
    protected array  $userFields = [
        "userID" => "user_id",
        "userName" => "user_name",
        "loginName" => "login_name",
        "password" => "password",
        "email" => "email",
        "status" => "status",
        "loginTime" => "login_time",
        "logoutTime" => "logout_time",
        "lastActive" => "last_active",
        "sessionID" => "session_id",
    ];

    // Constructor //
    public function __construct(array $config = []) {

        $this->denyWrite = array_merge($this->denyWrite, ['userTable', 'userFields']);

        // User Table //
        if (isset($config['userTable'])) {
            $this->userTable = $config['userTable'];
        }
        if (isset($config['userFields']) && is_array($config['userFields'])) {
            $this->userFields = array_merge($this->userFields, $config['userFields']);
        }

    }
    
    // Get User field name //
    public function getUserField(string $fieldName, ?string $default = null): string {
        return $this->userFields[$fieldName] ?? ($default ?? $fieldName);
    }

    // Reset User data //
    public function reset(): void {
        // No record to reset - records are managed by UserManager
    }

    //---------- Sudo Abstract Methods (All dummy methods) ----------//

    // Get user data from storage
    public function getUserByID(int $userID, bool $forceLoad = false, bool $checkExists = false): ?array {
        return null;
    }

    // Get multiple user data by ID //
    public function getMultiUserByID(array $userID = [], bool $forceLoad = false): ?array {
        return null;
    }

    // Get user data from storage
    public function getUserByLoginName(string $loginName, bool $forceLoad = false): ?array {
        return null;
    }
    
    // Create user record to storage
    public function createUser(string $loginName, string $userPassword, array $fieldList = []):int {
        return -1;
    }

    // Update user record to storage
    public function updateUser(array $fieldList, ?int $userID = null): bool {
        return false;
    }

    // Delete user record from storage
    public function delUser(?int $userID = null): bool {
        return false;
    }

    // Update password
    public function updatePassword(string $passwordHash, ?int $userID = null): bool {
        return false;
    }

    // Update login time and last active time
     public function updateUserStatus(bool $login, int $lastActiveTime, ?string $sessionID = null, ?int $loginTime = null, ?int $userID = null): bool {
        return false;
    }

} 