<?php
//
// Config-example.php - Configuration file example
//
// Version: 1.0.0   - 2024-12-02
// Author: Vincent Leung
// Copyright: 2023-2024 Vincent Leung
// License: MIT
//
use Psr\Log\LogLevel;

// Config //
$getSysConfig = function (string $docRoot, string $etcPath, string $varPath) {
    return [
        // System setting //
        "general" => [
            "timeZone" => "Asia/Hong_Kong",
            "sessionName" => "meow",
            "debug" => true,
        ],
        "db" => [
            "csv" => [
                "path" => $docRoot.$varPath."/db",
                "prefix" => "my_",
            ],
            "sql" => [
                "type" => "mysql",
                "database" => "MyDatabase",
                "server" => "localhost",
                "username" => "MyUsername",
                "password" => "MyPassword",
                "prefix" => "my_",
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_general_ci",
                "port" => 3306,
            ],
            "engine" => "sql",
        ],
        "log" => [
            "path" => $docRoot.$varPath."/log",
            "level" => LogLevel::DEBUG,
            "enable" => true,
            "option" => [],
        ],
        "cache" => [
            "adapterList" => [
                "files" => [
                    "namespace" => "",
                    "path" => $docRoot.$varPath."/cache",
                ],	
                "memcached" => [
                    "namespace" => "",
                    "servers" => [
                        "main" => [
                            "host" => "localhost",		
                            "port" => 11211,
                            "options" => "",
                            "user" => "",
                            "password" => "",
                        ],
                    ],
                ],
            ],
            "enable" => true,
            "siteID" => "Meow",
            "lifeTime" => 604800,				
            "adapter" => "memcached",
        ],
        "mailer" => [
            // PHPMailer settings //
            "host" => "localhost",
            "port" => 25,
            "auth" => false,
            // SMTP settings //
            /*
            "helo" => "my.server.com",
            "host" => "my.smtp-server.com",
            "port" => 587,
            "auth" => true,
            "username" => "my.username",
            "password" => "my.password",
            "encryption" => "tls",
            */
            "debug" => 0,
            "exceptions" => true,
            // Mailer class settings //
            "mode" => Mailer::MODE_MAIL,           // Mailer::MODE_MAIL, Mailer::MODE_SMTP, Mailer::MODE_SENDMAIL, Mailer::MODE_QMAIL
            "async" => true,
            "logPath" => $docRoot.$varPath."/log",
            "logPrefix" => "mail_",
            "spoolPath" => $docRoot.$varPath."/spool/mailer",
            "checkDNS" => false,
        ],
        // User defined setting //
        "mySetting" => [
            "mySetting1" => "MySetting1",
            "mySetting2" => "MySetting2",
        ],
    ];
};

