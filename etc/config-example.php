<?php
/**
 * Config-example.php - Configuration file example
 * 
 * This file is a template for the configuration file.
 * Copy this file to config.php and update the values according to your environment.
 * 
 * IMPORTANT: Never commit config.php to version control as it contains sensitive information.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.0
 * @license MIT
 * @package Paheon\MeowBase\Config
 */
use Psr\Log\LogLevel;
use Paheon\MeowBase\Tools\Mailer;
use Paheon\MeowBase\Tools\User;
use Paheon\MeowBase\Tools\UserManager;

// Config //
// This function returns the system configuration array
// Parameters are automatically provided by MeowBase:
//   - $docRoot: Document root path
//   - $etcPath: Configuration directory path
//   - $varPath: Variable data directory path
$getSysConfig = function (string $docRoot, string $etcPath, string $varPath): array {
    return [
        // System setting //
        "general" => [
            "timeZone" => "Asia/Hong_Kong",        // PHP timezone identifier (e.g., "Asia/Hong_Kong", "America/New_York")
            "sessionName" => "meow",                // Session cookie name
            "sessionLifeTime" => 604800,            // Session lifetime in seconds (default: 7 days = 604800)
            "debug" => true,                        // Enable/disable debug mode (set to false in production)
        ],
        "db" => [
            // CSV Database settings (for simple file-based storage)
            "csv" => [
                "path" => $docRoot.$varPath."/db",  // Path to CSV database files
                "prefix" => "my_",                   // Table name prefix for CSV files
            ],
            // SQL Database settings
            "sql" => [
                "type" => "mysql",                  // Database type: "mysql", "pgsql", "sqlite", etc.
                "database" => "your_database_name", // Database name - CHANGE THIS
                "server" => "localhost",            // Database server hostname
                "username" => "your_username",      // Database username - CHANGE THIS
                "password" => "your_password",      // Database password - CHANGE THIS
                "prefix" => "my_",                  // Table name prefix
                "charset" => "utf8mb4",             // Character set
                "collation" => "utf8mb4_general_ci", // Collation
                "port" => 3306,                     // Database port (default: 3306 for MySQL)
            ],
            "engine" => "sql",                      // Database engine: "sql" or "csv"
        ],
        "log" => [
            "path" => $docRoot.$varPath."/log",
            "level" => LogLevel::DEBUG,
            "enable" => true,
            "option" => [],
        ],
        "cache" => [
            "adapterList" => [
                // File-based cache adapter
                "files" => [
                    "namespace" => "",              // Cache namespace (optional)
                    "path" => $docRoot.$varPath."/cache", // Path to cache files
                ],	
                // Memcached adapter
                "memcached" => [
                    "namespace" => "",              // Cache namespace (optional)
                    "servers" => [
                        "main" => [
                            "host" => "localhost",  // Memcached server hostname
                            "port" => 11211,        // Memcached server port
                            "options" => "",        // Additional options (optional)
                            "user" => "",           // Memcached username (if using SASL)
                            "password" => "",       // Memcached password (if using SASL)
                        ],
                        // Add more servers if needed:
                        // "secondary" => [
                        //     "host" => "memcached2.example.com",
                        //     "port" => 11211,
                        // ],
                    ],
                ],
            ],
            "enable" => true,                       // Enable/disable cache
            "siteID" => "Meow",                    // Site identifier for cache isolation
            "lifeTime" => 604800,                   // Default cache lifetime in seconds (7 days)
            "adapter" => "files",                  // Default cache adapter: "files" or "memcached"
        ],
        "mailer" => [
            // PHPMailer settings //
            "host" => "localhost",                  // SMTP server hostname
            "port" => 25,                           // SMTP server port
            "auth" => false,                        // Enable SMTP authentication
            
            // SMTP settings (uncomment and configure for SMTP authentication)
            /*
            "helo" => "your-server.com",            // HELO domain name
            "host" => "smtp.example.com",            // SMTP server hostname
            "port" => 587,                           // SMTP port (587 for TLS, 465 for SSL)
            "auth" => true,                          // Enable authentication
            "username" => "your-email@example.com", // SMTP username - CHANGE THIS
            "password" => "your-smtp-password",     // SMTP password - CHANGE THIS
            "encryption" => "tls",                   // Encryption: "tls" or "ssl"
            */
            
            "debug" => 0,                           // PHPMailer debug level (0 = off, 1-4 = various levels)
            "exceptions" => true,                    // Throw exceptions on errors
            
            // Mailer class settings //
            "mode" => Mailer::MODE_MAIL,            // Mail mode: MODE_MAIL, MODE_SMTP, MODE_SENDMAIL, MODE_QMAIL
            "async" => true,                        // Enable asynchronous mail sending
            "logPath" => $docRoot.$varPath."/log",  // Path to mail log files
            "logPrefix" => "mail_",                 // Prefix for mail log files
            "spoolPath" => $docRoot.$varPath."/spool/mailer", // Path to mail spool directory (for async mode)
            "checkDNS" => false,                    // Check DNS for email addresses
        ],
        "user" => [
            "manager" => [
                "sessionPath" => $docRoot.$varPath."/session", // Path to session files
                'sessionVarName' => 'meowUser',                // Session variable name
                "singleLogin" => false,                         // Allow only one active session per user
                "forceLogin" => false,                          // Force login even if user is already logged in
                'lifeTime' => UserManager::LOGIN_LIFE_TIME,    // Login session lifetime in seconds (default: 3600)
                'password' => [
                    'type' => 'encrypted',                      // Password type: 'encrypted' or 'plain'
                    "salt" => "CHANGE_THIS_TO_A_RANDOM_STRING", // Salt for password encryption - CHANGE THIS!
                    /* 
                    // Default password validation rules (commented out - using defaults)
                    "algorithm" => 'sha256',                    // Hash algorithm
                    "minLength" => 8,                           // Minimum password length
                    "maxLength" => 20,                          // Maximum password length
                    "minUppercase" => 1,                        // Minimum uppercase letters
                    "minLowercase" => 1,                        // Minimum lowercase letters
                    "minNumber" => 1,                           // Minimum numbers
                    "minSpecial" => 1,                          // Minimum special characters
                    */
                ],
            ],    
            "user" => [
                // Extra Fields Mapping//
                "userFields" => [
                    "extraData" => "extra_data",
                ],
                // For CsvDB storage only //
                "csvDB" => [
                    "path" => $docRoot.$varPath."/db",
                ],
                /* Following is default setting in User class 
                // DB Tables //
                "userTable" => "users",

                // DB Fields Mapping//
                "userFields" => [
                    "userID" => "user_id",
                    "username" => "username",
                    "loginName" => "login_name",
                    "password" => "password",
                    "email" => "email",
                    "status" => "status",
                    "loginTime" => "login_time",
                    "logoutTime" => "logout_time",
                    "lastActive" => "last_active",
                    "sessionID" => "session_id",
                ],
                */
            ],    
            "userGroup" => [
                "csvDB" => [
                    "path" => $docRoot.$varPath."/db",
                ],
                /* Following is default setting in UserGroup class 
                // DB Tables //
                "userGroupTable" => "users_groups",
                "userGroupLinkTable" => "users_groups_link",

                // DB Fields Mapping//
                "userGroupFields" => [
                    "groupID" => "group_id",
                    "groupName" => "group_name",
                    "groupDesc" => "group_desc",
                ],
                "userGroupLinkFields" => [
                    "userID" => "user_id",
                    "groupID" => "group_id",
                ],
                */
            ],
            "userPerm" => [
                "csvDB" => [
                    "path" => $docRoot.$varPath."/db",
                ],
                /* Following is default setting in UserPerm class
    
                // DB Tables //
                "userPermTable" => "users_perm",
                "userGroupPermTable" => "users_groups_perm",
    
                // DB Fields Mapping//
                "userPermFields" => [
                    "userID" => "user_id",
                    "item" => "item",
                    "permission" => "permission",
                    "value" => "value",
                ],
                "userGroupPermFields" => [
                    "groupID" => "group_id",
                    "item" => "item",
                    "permission" => "permission",
                    "value" => "value",
                ],
                */
            ],
        ],
        
        // User defined settings - Add your custom configuration here
        "mySetting" => [
            "mySetting1" => "MySetting1",
            "mySetting2" => "MySetting2",
        ],
    ];
};

