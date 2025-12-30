<?php
/**
 * User class
 * 
 * This class is used to handle password generation and verification.
 * 
 * @author Vincent Leung <vincent@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Tools
 * 
 */
namespace Paheon\MeowBase\Tools;
use Paheon\MeowBase\ClassBase;

class Password {

    use ClassBase;

    // Settings //
    protected int $minLength    = 8;
    protected int $maxLength    = 20;
    protected int $minUppercase = 1;
    protected int $minLowercase = 1;
    protected int $minNumber    = 1;
    protected int $minSpecial   = 1;

    // Algorithm and salt //
    protected string $algorithm = 'sha256';
    protected string $salt = '41a2c#3G42wY6x73!9';

    public function __construct(array $config) {
        
        if (isset($config['minLength'])) {
            $this->minLength = $config['minLength'];
        }
        if (isset($config['maxLength'])) {
            $this->maxLength = $config['maxLength'];
        }
        if (isset($config['minUppercase'])) {
            $this->minUppercase = $config['minUppercase'];
        }
        if (isset($config['minLowercase'])) {
            $this->minLowercase = $config['minLowercase'];
        }
        if (isset($config['minNumber'])) {
            $this->minNumber = $config['minNumber'];
        }
        if (isset($config['minSpecial'])) {
            $this->minSpecial = $config['minSpecial'];
        }
        if (isset($config['algorithm'])) {
            $this->algorithm = $config['algorithm'];
        }
        if (isset($config['salt'])) {
            $this->salt = $config['salt'];
        }
    }

    // Setter //
    public function setMinLength(int $minLength): void {
        if ($minLength < 1) {
            $minLength = 1;
        }
        $this->minLength = $minLength;
    }
    public function setMaxLength(int $maxLength): void {
        if ($maxLength < 1) {
            $maxLength = 1;
        }
        $this->maxLength = $maxLength;
    }
    public function setMinUppercase(int $minUppercase): void {
        if ($minUppercase < 0) {
            $minUppercase = 0;
        }
        $this->minUppercase = $minUppercase;
    }
    public function setMinLowercase(int $minLowercase): void {
        if ($minLowercase < 0) {
            $minLowercase = 0;
        }
        $this->minLowercase = $minLowercase;
    }
    public function setMinNumber(int $minNumber): void {
        if ($minNumber < 0) {
            $minNumber = 0;
        }
        $this->minNumber = $minNumber;
    }
    public function setMinSpecial(int $minSpecial): void {
        if ($minSpecial < 0) {
            $minSpecial = 0;
        }
        $this->minSpecial = $minSpecial;
    }
    public function setAlgorithm(string $algorithm): void {
        if (!in_array($algorithm, hash_algos())) {
            $algorithm = 'sha256';
        }
        $this->algorithm = $algorithm;
    }
    public function setSalt(string $salt = ""): void {
        if (strlen($salt) < 8) {
            $salt = $this->genSalt($salt);
        }
        $this->salt = $salt;
    }

    // Get password hash
    public function getPasswordHash(string $password, ?string $algorithm = null): string {
        if ($algorithm !== null && !in_array($algorithm, hash_algos())) {
            $algorithm = $this->algorithm;
        }
        return hash($algorithm ?? $this->algorithm, $password . $this->salt);
    }

    // Generate password
    public function genPassword(): string {
        
        // Calculate actual required characters //
        $requiredChars = $this->minUppercase + $this->minLowercase + $this->minNumber + $this->minSpecial;
        
        // Ensure minimum length is not less than required characters //
        $actualMinLength = max($this->minLength, $requiredChars);
        
        // Character set definition //
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        
        // Add required uppercase letters //
        for ($i = 0; $i < $this->minUppercase; $i++) {
            $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        }
        
        // Add required lowercase letters //
        for ($i = 0; $i < $this->minLowercase; $i++) {
            $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        }
        
        // Add required numbers //
        for ($i = 0; $i < $this->minNumber; $i++) {
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        }
        
        // Add required special characters //
        for ($i = 0; $i < $this->minSpecial; $i++) {
            $password .= $special[random_int(0, strlen($special) - 1)];
        }
        
        // Calculate remaining characters to add //
        $remainingLength = $actualMinLength - strlen($password);
        
        // If more characters are needed, select from all character sets randomly //
        if ($remainingLength > 0) {
            $allChars = $uppercase . $lowercase . $numbers . $special;
            for ($i = 0; $i < $remainingLength; $i++) {
                $password .= $allChars[random_int(0, strlen($allChars) - 1)];
            }
        }
        
        // Shuffle password characters //
        $password = str_shuffle($password);
        
        // If generated password is longer than maximum length, truncate to maximum length //
        if (strlen($password) > $this->maxLength) {
            $password = substr($password, 0, $this->maxLength);
        }
        
        return $password;
    }

    // Password validation
    public function validatePassword(string $password): bool {
        // Check password length //
        $passwordLength = strlen($password);
        if ($passwordLength < $this->minLength || $passwordLength > $this->maxLength) {
            return false;
        }
        
        // Count various characters //
        $uppercaseCount = 0;
        $lowercaseCount = 0;
        $numberCount = 0;
        $specialCount = 0;
        
        for ($i = 0; $i < $passwordLength; $i++) {
            $char = $password[$i];
            if (ctype_upper($char)) {
                $uppercaseCount++;
            } elseif (ctype_lower($char)) {
                $lowercaseCount++;
            } elseif (ctype_digit($char)) {
                $numberCount++;
            } else {
                $specialCount++;
            }
        }
        
        // Check if various characters meet the requirements //
        if ($uppercaseCount < $this->minUppercase || $lowercaseCount < $this->minLowercase || $numberCount < $this->minNumber || $specialCount < $this->minSpecial) {
            return false;
        }

        return true;
    }

    // check password
    public function checkPassword(string $password, string $hash): bool {
        // No hash, no verify //
        if ($hash === "")  return false;

        // Set Algorithm //
        $passwordHash = $this->getPasswordHash($password);
        $result = hash_equals($passwordHash, $hash);
        return $result;
    }

    // Generate salt //
    public function genSalt(int $length = 0): string {
        $allChars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|;:,.<>?";
        $salt = '';
        if ($length <= 0) {
            $length = $this->minLength;
        }
        for ($i = 0; $i < $length; $i++) {
            $salt .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        return $salt;
    }

}
