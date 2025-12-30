<?php
/**
 * User class
 * 
 * This class is used to manage user, group and permission information.
 * It supports both CSV and Cache storage.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\Tools\PHP;
use Paheon\MeowBase\Tools\User;
use Paheon\MeowBase\Tools\UserGroup;
use Paheon\MeowBase\Tools\UserPerm;
use Paheon\MeowBase\Tools\Password;

class UserManager {

    use ClassBase;

    // Password types
    const PASSWORD_PLAIN = "plain";
    const PASSWORD_ENCRYPTED = "encrypted";

    // Default login life time
    const LOGIN_LIFE_TIME = 3600;
    
    // Objects //
    protected ?User       $userObj = null;
    protected ?UserGroup  $userGroupObj = null;
    protected ?UserPerm   $userPermObj = null;
    protected ?Password   $passwordObj = null;

    // Logined user data //
    protected ?array $user = null;
    protected ?array $userGroup = null;
    protected ?array $userGroupLink = null;
    protected ?array $userPerm = null;
    protected ?array $userPermGroup = null;

    // Configuration //
    protected bool   $singleLogin = true;
    protected bool   $forceLogin = false;
    protected bool   $encrypted = true;    
    protected int    $lifeTime = self::LOGIN_LIFE_TIME;
    protected string $sessionID = "";
    protected string $sessionVarName = "";


    public function __construct(User $userObj, array $config = [], ?Password $pwdObj = null, ?UserGroup $userGroupObj = null, ?UserPerm $userPermObj = null) {
        $this->denyWrite = array_merge($this->denyWrite, [
            'config', 
            'user', 'userGroup', 'userGroupLink', 'userPerm', 'userPermGroup',
            'userObj', 'userGroupObj', 'userPermObj', 'passwordObj',
        ]);

        // Set User Object //
        $this->userObj = $userObj;

        // Set Password Object //
        if ($pwdObj) {
            $this->passwordObj = $pwdObj;
        } else {
            $this->passwordObj = null;
        }
        $this->encrypted = ($config['password']['type'] ?? self::PASSWORD_ENCRYPTED) !== self::PASSWORD_PLAIN;

        // Set User Group Object //
        if ($userGroupObj) {
            $this->userGroupObj = $userGroupObj;
        }

        // Set User Perm Object //
        if ($userPermObj) {
            $this->userPermObj = $userPermObj;
        }

        // Start session if not started //
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (PHP::isCLI()) {
                $newSessionID = PHP::startCLISession();
            } else {
                $newSessionID = session_id();
            }
            if ($newSessionID !== false) {
                $this->sessionID = $newSessionID;
            } else {
                $this->sessionID = "";
            }
            session_start();
        } else {
            $this->sessionID = session_id();
        }

        $this->sessionVarName = $config['sessionVarName'] ?? "meow_user";

        // Set configuration //
        $this->singleLogin = $config['singleLogin'] ?? true;
        $this->forceLogin = $config['forceLogin'] ?? false;
        $this->lifeTime = $config['lifeTime'] ?? self::LOGIN_LIFE_TIME;

    }

    // Login user //
    public function login(string $loginName, string $password, ?bool $forceLogin = null): bool {
        $this->lastError = "";

        // Set force login //
        $force = $forceLogin ?? ($this->forceLogin ?? false);
        $currTime = time();

        // Check if already logged in
        if (!$force && $this->isLoggedIn()) {
            // Update user status and session //
            if ($this->updateUserStatus(true, $currTime)) {
                $this->touchSession($currTime);    
            } else {
                $this->lastError = "Failed to update user status";
                $this->throwException($this->lastError, 1);
                return false;
            }
            return true;
        }

        // Get user data //
        $userRec = $this->getUserByLoginName($loginName, false);
        if (!$userRec) {
            $this->lastError = "User not found";
            $this->throwException($this->lastError, 2);
            return false;
        }

        // Check password 
        if (!$this->checkPassword($password, $userRec['password'])) {
            $this->lastError = "Invalid password";
            $this->throwException($this->lastError, 3);
            return false;
        }

        // Update user status and session //
        $userID = $userRec['userID'];
        if ($this->updateUserStatus(true, $currTime, $this->sessionID, $currTime, $userID)) {
            // Load user groups and permissions into cache for efficiency
            $_SESSION[$this->sessionVarName]['userID'] = $userID;
            $_SESSION[$this->sessionVarName]['loginName'] = $loginName;
            $_SESSION[$this->sessionVarName]['login'] = true;
            $_SESSION[$this->sessionVarName]['loginStartTime'] = $currTime;
            $this->loadCurrentUserData($userID);
        } else {
            $this->user = null;
            $this->lastError = "Failed to update user status";
            $this->throwException($this->lastError, 1);
            return false;
        }
        return true;
    }

    // Logout user
    public function logout(): void {
        $this->lastError = "";

        // Log last active time //
        $error = true;
        if ($this->updateUserStatus(false, time())) {
            $error = false;
        }

        // Remove session and user data //
        if (isset($_SESSION[$this->sessionVarName])) {
            unset($_SESSION[$this->sessionVarName]);
        }
        
        // Clear all user records in UserManager
        $this->user = null;
        $this->userGroup = null;
        $this->userGroupLink = null;
        $this->userPerm = null;
        $this->userPermGroup = null;

        if ($error) {
            $this->lastError = "Failed to update user status";
            $this->throwException($this->lastError, 1);
        }
    }

    // Continue Last Login
    public function continueLogin(): bool {
        $this->lastError = "";

        // Session exists? //
        $session = $_SESSION[$this->sessionVarName] ?? [];
        $loginLifeTime = $session['loginLifeTime'] ?? null;
        if ($loginLifeTime && $loginLifeTime < time() ) {
            $this->lastError = "Login life time expired";
            $this->throwException($this->lastError, 4);
            return false;
        }

        // Get login name from session
        $loginName = $session['loginName'] ?? null;
        $userID = $session['userID'] ?? null;
        if ($userID !== null) {
            $userID = (int)$userID;
        }
        if (!$loginName || !$userID) {
            $this->lastError = "Login name or user ID not found";
            $this->throwException($this->lastError, 5);
            return false;
        }
        // Get user data //
        if ($loginName) {
            $userRec = $this->getUserByLoginName($loginName, true);
        } else {
            $userRec = $this->getUserByID($userID, true, false);
        }
        if (!$userRec) {
            $this->lastError = "User not found";
            $this->throwException($this->lastError, 2);
            return false;
        }
        
        // Store user record in UserManager
        $this->user = $userRec;
        
        // Load user groups and permissions into cache for efficiency
        $this->loadCurrentUserData($userID);
        
        return true;
    }

    // Check if user is logged in
    public function isLoggedIn(bool $forceLogout = true): bool {
        $this->lastError = "";

        // Session exists? //
        $session = $_SESSION[$this->sessionVarName] ?? [];
        if (!$session || !$this->user) {
            $this->lastError = "Session not found";
            $this->throwException($this->lastError, 3);
            return false;
        }
        // Time out //
        $loginLifeTime = $session['loginLifeTime'] ?? null;
        if ($loginLifeTime && $loginLifeTime < time() ) {
            if ($forceLogout) {
                $this->logout();    // force logout
            }
            $this->lastError = "Login life time expired";
            $this->throwException($this->lastError, 3);
            return false;
        }

        // Single login //
        $userRec = $this->user;
        if ($this->singleLogin && (!isset($userRec['sessionID']) || $userRec['sessionID'] !== $this->sessionID)) {
            $this->lastError = "Session ID mismatch, sessionID in session=".$this->sessionID.", sessionID in DB=".($userRec['sessionID'] ?? 'null');
            if ($forceLogout) {
                $this->lastError .= ", force logout";
                $this->logout();
            } 
            $this->throwException($this->lastError, 3);
            return false;
        }

        return true;
    }

    public function resolveUserID(?int $userID = null): ?int {
        $this->lastError = "";

        if ($userID !== null) {
            return $userID;
        }
        if (!$this->isLoggedIn()) {
            $this->lastError = "User not logged in";
            $this->throwException($this->lastError, 6);
            return null;
        }

        $userRec = $this->user ?? null;
        if (!$userRec || !isset($userRec['userID'])) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 7);
            return null;
        }

        return (int)$userRec['userID'];
    }

    // Touch login time
    public function touchSession(?int $currTime = null): void {
        $currTime = $currTime ?? time();
        $_SESSION[$this->sessionVarName]['loginLastActive'] = $currTime;
        $_SESSION[$this->sessionVarName]['loginLifeTime'] = $currTime + ($this->lifeTime);
    }

    // Get session ID from DB //
    public function getSessionID(): ?string {
        $this->lastError = "";

        // Check if user is logged in //
        if (!$this->isLoggedIn()) {
            $this->lastError = "User not logged in";
            $this->throwException($this->lastError, 6);
            return null;
        }

        // Get session ID //
        $sessionField = $this->userObj->getUserField('sessionID', 'session_id');
        $userRec = $this->user ?? null;
        return $userRec[$sessionField] ?? null;
    }
       
    // Change password //
    public function changePassword(?string $newPassword = null, ?string $oldPassword = null):string|false {
        $this->lastError = "";

        // Verify old password //
        if ($oldPassword !== null) {

            if (!$this->isLoggedIn()) {
                $this->lastError = "User not logged in";
                $this->throwException($this->lastError, 6);
                return false;
            } 

            $userRec = $this->user ?? null;
            if (!$userRec || !$this->checkPassword($oldPassword, $userRec[$this->userObj->getUserField('password')])) {
                $this->lastError = "Invalid old password";
                $this->throwException($this->lastError, 8);
                return false;
            }    
        }

        // Generate new password //
        if ($newPassword === null) {
            $newPassword = $this->genPassword();
        }
        $newPasswordHash = $this->getPasswordHash($newPassword);

        // Update password //
        $currTime = time();
        if (!$this->updatePassword($newPasswordHash)) {
            $this->lastError = "Failed to update password";
            $this->throwException($this->lastError, 9);
            return false;
        }
        return $newPassword;
    }

    //---------- Helper Methods ----------//

    // Check password //
    public function checkPassword(string $password, string $hash): bool {
        if (!$this->passwordObj || $this->encrypted === self::PASSWORD_PLAIN) {
            // Plain text comparison //
            return $password === $hash;
        }
        return $this->passwordObj->checkPassword($password, $hash);
    }

    // Get password hash //
    public function getPasswordHash(string $password): string {
        if (!$this->passwordObj || $this->encrypted === self::PASSWORD_PLAIN) {
            return $password;
        }
        return $this->passwordObj->getPasswordHash($password);
    }

    // Generate password //
    public function genPassword(): string {
        if (!$this->passwordObj) {
            // Generate simple random password //
            return bin2hex(random_bytes(8));
        }
        return $this->passwordObj->genPassword();
    }

    // Get user by ID //
    public function getUserByID(int $userID, bool $forceLoad = false, bool $checkExists = false): ?array {
        return $this->userObj->getUserByID($userID, $forceLoad, $checkExists);
    }

    // Get user by login name //
    public function getUserByLoginName(string $loginName, bool $forceLoad = false): ?array {
        return $this->userObj->getUserByLoginName($loginName, $forceLoad);
    }

    // Update user status //
    public function updateUserStatus(bool $login, int $lastActiveTime, ?string $sessionID = null, ?int $loginTime = null, ?int $userID = null): bool {
        $this->lastError = "";
        $resolvedUserID = $userID ?? $this->resolveUserID();
        if ($resolvedUserID === null) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 7);
            return false;
        }
        $result = $this->userObj->updateUserStatus($login, $lastActiveTime, $sessionID ?? $this->sessionID, $loginTime, $resolvedUserID);
        if ($result) {
            // Update user record in UserManager
            $updateRec = $this->userObj->getUserByID($resolvedUserID, true); 
            if ($updateRec) {
                $this->user = $updateRec;
                $this->touchSession($loginTime);
            }
        }
        return $result;
    }

    // Update password //
    public function updatePassword(string $passwordHash, ?int $userID = null): bool {
        $this->lastError = "";
        $resolvedUserID = $userID ?? $this->resolveUserID();
        if ($resolvedUserID === null) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 7);
            return false;
        }
        return $this->userObj->updatePassword($passwordHash, $resolvedUserID);
    }

    //---------- User CRUD Methods ----------//

    // Create user //
    public function createUser(string $loginName, string $password, array $fieldList = []): int {
        $this->lastError = "";
        $passwordHash = $this->getPasswordHash($password);
        $result = $this->userObj->createUser($loginName, $passwordHash, $fieldList);
        if ($result <= 0) {
            $this->lastError = $this->userObj->lastError;
            $this->throwException($this->lastError, 10);
            return -10;
        }
        return $result;
    }

    // Update user //
    public function updateUser(array $fieldList, ?int $userID = null): bool {
        $this->lastError = "";
        if (isset($fieldList['password'])) {
            $fieldList['password'] = $this->getPasswordHash($fieldList['password']);
        }
        $currUserID = $this->resolveUserID();
        $resolvedUserID = $userID ?? $currUserID;
        if ($resolvedUserID === null) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 7);
            return false;
        }
        $result = $this->userObj->updateUser($fieldList, $resolvedUserID);
        if ($resolvedUserID === $currUserID && $result) {
            // Load user groups and permissions into cache for efficiency
            $this->loadCurrentUserData($currUserID);
        }
        return $result;
    }

    // Delete user //
    public function delUser(?int $userID = null): bool {
        $this->lastError = "";
        $currUserID = $this->resolveUserID();
        $resolvedUserID = $userID ?? $currUserID;
        if ($resolvedUserID === null) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 7);
            return false;
        }
        // Force logout if current user is being deleted
        if ($resolvedUserID === $currUserID) {
            $this->logout();
        }

        return $this->userObj->delUser($resolvedUserID);
    }

    //---------- Group CRUD Methods ----------//

    // Create user group //
    public function createUserGroup(string $userGroupName, array $fieldList = []): int {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return -11;
        }
        return $this->userGroupObj->createUserGroup($userGroupName, $fieldList);
    }

    // Get user group by ID //
    public function getUserGroupByID(int $userGroupID, bool $forceLoad = false, bool $checkExists = false): ?array {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return null;
        }
        if (isset($this->userGroup[$userGroupID])) {
            return $this->userGroup[$userGroupID];
        }
        $groupRec = $this->userGroupObj->getUserGroupByID($userGroupID, $forceLoad, $checkExists);
        if (in_array($userGroupID, $this->userGroupLink)) {
            $this->userGroup[$userGroupID] = $groupRec;
        }
        return $groupRec;
    }

    // Get user group by name //
    public function getUserGroupByName(string $userGroupName, bool $forceLoad = false): ?array {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return null;
        }
        return $this->userGroupObj->getUserGroupByName($userGroupName, $forceLoad);
    }

    // Update user group //
    public function updateUserGroup(array $fieldList, ?int $userGroupID = null): bool {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return false;
        }
        if (isset($this->userGroup[$userGroupID])) {
            unset($this->userGroup[$userGroupID]);
        }
        return $this->userGroupObj->updateUserGroup($fieldList, $userGroupID);
    }

    // Delete user group //
    public function delUserGroup(?int $userGroupID = null): bool {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return false;
        }
        if (isset($this->userGroup[$userGroupID])) {
            unset($this->userGroup[$userGroupID]);
        }
        return $this->userGroupObj->delUserGroup($userGroupID);
    }

    // Add user to group //
    public function addUserToGroup(int $userID, int $userGroupID): bool {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return false;
        }
        // Delete cache to force reload //
        $result = $this->userGroupObj->addUserToGroup($userID, $userGroupID);
        if ($result) {
            // Load user groups link again //
            $groups = $this->getGroupsByUser($userID, true);
            if (is_array($groups)) {
                // Store group links (array of group records)
                $this->userGroupLink = $groups;
            }        
        }    
        return $result;
    }

    // Remove user from group //
    public function delUserFromGroup(int $userID, ?int $userGroupID = null): bool {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return false;
        }
        
        return $this->userGroupObj->delUserFromGroup($userID, $userGroupID);
    }

    // Get users in group //
    public function getUsersInGroup(?int $userGroupID = null, bool $forceLoad = false): ?array {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return null;
        }
        return $this->userGroupObj->getUsersInGroup($userGroupID, $forceLoad);
    }

    // Get groups by user //
    public function getGroupsByUser(int $userID, bool $forceLoad = false): ?array {
        $this->lastError = "";
        if (!$this->userGroupObj) {
            $this->lastError = "UserGroup object not initialized";
            $this->throwException($this->lastError, 11);
            return null;
        }
        if (is_array($this->userGroupLink) && !$forceLoad) {
            return $this->userGroupLink;
        }    
        $groupLinkRec = $this->userGroupObj->getGroupsByUser($userID, $forceLoad);
        $currUserID = $this->resolveUserID();
        if ($currUserID === $userID) {
            $this->userGroupLink = $groupLinkRec;
        }    
        return $groupLinkRec;
    }

    //---------- Permission CRUD Methods ----------//

    // Get user permission //
    public function getUserPermission(int $userID, string $item): ?array {
        $this->lastError = "";
        if (!$this->userPermObj) {
            $this->lastError = "UserPerm object not initialized";
            $this->throwException($this->lastError, 12);
            return null;
        }
        if (isset($this->userPerm[$item])) {
            return $this->userPerm[$item];
        } 
        $userPerms = $this->userPermObj->getUserPerm($userID, $item);
        if (is_array($userPerms)) {
            $this->userPerm[$item] = $userPerms;
        }
        return $userPerms;
    }

    // Set user permission //
    public function setUserPermission(int $userID, string $item, string $permission, int $value): bool {
        $this->lastError = "";
        if (!$this->userPermObj) {
            $this->lastError = "UserPerm object not initialized";
            $this->throwException($this->lastError, 12);
            return false;
        }
        // Clear cache to force reload //
        unset($this->userPerm[$item]);

        return $this->userPermObj->setUserPerm($userID, $item, $permission, $value);
    }

    // Delete user permission //
    public function delUserPermission(int $userID, string $item, ?string $permission = null): bool {
        $this->lastError = "";
        if (!$this->userPermObj) {
            $this->lastError = "UserPerm object not initialized";
            $this->throwException($this->lastError, 12);
            return false;
        }
        // Clear cache to force reload //
        if (isset($this->userPerm[$item])) {
            unset($this->userPerm[$item]);
        }
        return $this->userPermObj->delUserPerm($userID, $item, $permission);
    }

    // Get group permission //
    public function getGroupPermission(int $userGroupID, string $item, ?int $userID = null): ?array {
        $this->lastError = "";
        if (!$this->userPermObj) {
            $this->lastError = "UserPerm object not initialized";
            $this->throwException($this->lastError, 12);
            return null;
        }
        if (isset($this->userPermGroup[$userGroupID][$item])) {
            return $this->userPermGroup[$userGroupID][$item];
        }
        $groupLinkRec = $this->userPermObj->getGroupPerm($userGroupID, $item);
        $currUserID = $this->resolveUserID();
        $userID = $userID ?? $currUserID;
        if ($userID !== null && $currUserID === $userID) {
            $this->userPermGroup[$userGroupID][$item] = $groupLinkRec;
        }    
        return $groupLinkRec;
    }

    // Set group permission //
    public function setGroupPermission(int $userGroupID, string $item, string $permission, int $value): bool {
        $this->lastError = "";
        if (!$this->userPermObj) {
            $this->lastError = "UserPerm object not initialized";
            $this->throwException($this->lastError, 12);
            return false;
        }
        // Clear cache to force reload //
        if (isset($this->userPermGroup[$userGroupID][$item])) {
            unset($this->userPermGroup[$userGroupID][$item]);
        }
        return $this->userPermObj->setGroupPerm($userGroupID, $item, $permission, $value);
    }

    // Delete group permission //
    public function delGroupPermission(int $userGroupID, string $item, ?string $permission = null): bool {
        $this->lastError = "";
        if (!$this->userPermObj) {
            $this->lastError = "UserPerm object not initialized";
            $this->throwException($this->lastError, 12);
            return false;
        }
        // Clear cache to force reload //
        if (isset($this->userPermGroup[$userGroupID][$item])) {
            unset($this->userPermGroup[$userGroupID][$item]);
        }
        return $this->userPermObj->delGroupPerm($userGroupID, $item, $permission);
    }

    //---------- Permission Check Methods (整合 userPerm 和 userGroup) ----------//

    // Check user permission (including group permissions) //
    public function checkUserPermission(string $item, string $permission, string|int $level = 0, ?int $userID = null): bool {
        $this->lastError = "";
        
        // Resolve user ID //
        $currentUserID = $this->resolveUserID();
        $resolvedUserID = $userID ?? $currentUserID;
        if ($resolvedUserID === null) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 7);
            return false;
        }

        // Check if this is current logged-in user to use cache
        $isCurrentUser = ($currentUserID !== null && $resolvedUserID === $currentUserID);

        // Check user-specific permission first //
        if ($this->userPermObj) {
            $userPerms = null;
            
            // Use cache if available for current user
            if ($isCurrentUser && isset($this->userPerm[$item]) && isset($this->userPerm[$item][$permission])) {
                $permValue = $this->userPerm[$item][$permission];
                if ($permValue > $level) {
                    return true;
                }
            } else {
                // Load from storage
                $userPerms = $this->getUserPermission($resolvedUserID, $item);
                if (isset($userPerms[$permission])) {
                    // Cache if current user
                    if ($isCurrentUser) {
                        $this->userPerm[$item][$permission] = (int)$userPerms[$permission];
                    }
                    $permValue = (int)$userPerms[$permission];
                    if ($permValue > $level) {
                        return true;
                    }
                }
            }
        }

        // Check group permissions if userGroup is available //
        if ($this->userGroupObj && $this->userPermObj) {
            if (is_array($this->userGroupLink)) {
                $groupIDList = $this->userGroupLink;
            } else {
                $groupIDList = $this->getGroupsByUser($resolvedUserID, false);
                if ($isCurrentUser) {
                    $this->userGroupLink = $groupIDList;
                }
            }
            if (is_array($groupIDList)) {
                foreach ($groupIDList as $groupID) {
                    // Get permission value from cache if available
                    if (isset($this->userPermGroup[$groupID][$item][$permission])) {
                        $permValue = (int)$this->userPermGroup[$groupID][$item][$permission];
                    } else {   
                        $this->userPermGroup[$groupID][$item] = $permValue = $this->getGroupPermission($groupID, $item);
                        if (isset($permValue[$permission])) {
                            $permValue = (int)$permValue[$permission];
                        } else {
                            continue;
                        }
                    }        
                    if ($permValue > $level) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Get user permission value (including group permissions) //
    public function getUserPermissionValue(string $item, string $permission, ?int $userID = null): array {
        $this->lastError = "";
        $permValueList = [];
        
        // Resolve user ID //
        $currentUserID = $this->resolveUserID();
        $resolvedUserID = $userID ?? $currentUserID;
        if ($resolvedUserID === null) {
            $this->lastError = "User ID not found";
            $this->throwException($this->lastError, 7);
            return $permValueList;
        }

        // Check if this is current logged-in user to use cache
        $isCurrentUser = ($currentUserID !== null && $resolvedUserID === $currentUserID);

        // Check user-specific permission first //
        $userPermValue = [];
        if ($this->userPermObj) {

            // Use cache if available for current user
            if ($isCurrentUser && isset($this->userPerm[$item][$permission])) {
                $permValueList[0] = $this->userPerm[$item][$permission];
            } else {
                // Load from storage
                $userPerms = $this->getUserPermission($resolvedUserID, $item);
                if (isset($userPerms[$permission])) {
                    // Cache if current user
                    if ($isCurrentUser) {
                        $this->userPerm[$item][$permission] = (int)$userPerms[$permission];
                    }
                    $permValueList[0] = (int)$userPerms[$permission];
                }
            }
        }

        // Check group permissions if userGroup is available //
        if ($this->userGroupObj && $this->userPermObj) {
            if (is_array($this->userGroupLink)) {
                $groupIDList = $this->userGroupLink;
            } else {
                $groupIDList = $this->getGroupsByUser($resolvedUserID, false);
                if ($isCurrentUser) {
                    $this->userGroupLink = $groupIDList;
                }
            }
            if (is_array($groupIDList)) {
                foreach ($groupIDList as $groupID) {
                    if (isset($this->userPermGroup[$groupID][$item][$permission])) {
                        $permValue = (int)$this->userPermGroup[$groupID][$item][$permission];
                    } else {
                        $this->userPermGroup[$groupID][$item] = $permValue = $this->getGroupPermission($groupID, $item);
                        if (isset($permValue[$permission])) {
                            $permValue = (int)$permValue[$permission];
                        } else {
                            continue;
                        }
                    }        
                    $permValueList[$groupID] = $permValue;
                }
            }
        }

        return $permValueList;
    }

    //---------- Internal Helper Methods ----------//

    // Load current user's groups and permissions into cache for efficiency
    protected function loadCurrentUserData(int $userID): void {
        // Load user groups if UserGroup is available
        $this->userGroupLink = null;
        $this->userGroup = [];
        if ($this->userGroupObj) {
            // Get groups for current user
            $groups = $this->getGroupsByUser($userID, false);
            if (is_array($groups)) {
                // Store group links (array of group records)
                $this->userGroupLink = $groups;
            }
        }
        
        // Initialize permission caches
        $this->userPerm = [];
        $this->userPermGroup = [];
        
        // Note: Permissions are loaded on-demand when needed
        // They will be cached in $this->userPerm and $this->userPermGroup
    }

}