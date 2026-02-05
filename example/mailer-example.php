<?php
/**
 * Mailer Example
 * 
 * This example demonstrates how to use the Mailer class for sending emails
 * with PHPMailer integration, including attachments, embedded images, and async mode.
 * 
 * @author Vincent Leung <meow@paheon.com>
 * @version 1.3.1
 * @license MIT
 */

require(__DIR__.'/../vendor/autoload.php');

use Paheon\MeowBase\Config;
use Paheon\MeowBase\Tools\Mailer;

// Determine Web or CLI
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";

echo "Mailer Example".$br;
echo "==========================================".$br.$br;

// Example 1: Basic Mailer Setup
echo "Example 1: Basic Mailer Setup".$br;
echo "--------------------------------".$br;

$config = new Config();
$mailerConfig = $config->getConfigByPath("mailer") ?? [];

// Create Mailer instance
$mailer = new Mailer($mailerConfig);
echo "Mailer object created".$br;
echo "Mode: ".$mailer->mode.$br;
echo "Async mode: ".var_export($mailer->async, true).$br;
echo "Check DNS: ".var_export($mailer->checkDNS, true).$br.$br;

// Example 2: Email Validation
echo "Example 2: Email Validation".$br;
echo "--------------------------------".$br;

$testEmails = [
    'valid@example.com',
    'user.name@example.co.uk',
    'invalid.email',
    'missing@domain',
    'test@subdomain.example.com'
];

echo "Testing email validation:".$br;
foreach ($testEmails as $email) {
    $valid = $mailer->emailValidate($email, false); // Don't check DNS for speed
    echo "  '$email': ".($valid ? "Valid" : "Invalid").$br;
    if (!$valid) {
        echo "    Error: ".$mailer->lastError.$br;
    }
}
echo $br;

// Example 3: Get Valid Email Addresses
echo "Example 3: Get Valid Email Addresses".$br;
echo "--------------------------------".$br;

$addressList = [
    'valid1@example.com' => 'Valid User 1',
    'valid2@example.com' => 'Valid User 2',
    'invalid.email' => 'Invalid User',
    'another@example.com' => 'Another User'
];

$validAddresses = $mailer->getValidAddr($addressList, '', false);
echo "Valid addresses from list:".$br;
foreach ($validAddresses as $email => $name) {
    echo "  $email => $name".$br;
}
echo $br;

// Example 4: Set Email Addresses
echo "Example 4: Set Email Addresses".$br;
echo "--------------------------------".$br;

// Set From
$mailer->setFrom(['sender@example.com' => 'Test Sender']);
echo "From address set".$br;

// Set To
$mailer->setTo([
    'recipient1@example.com' => 'Recipient 1',
    'recipient2@example.com' => 'Recipient 2'
]);
echo "To addresses set".$br;

// Set CC
$mailer->setCC(['cc@example.com' => 'CC Recipient']);
echo "CC address set".$br;

// Set BCC
$mailer->setBCC(['bcc@example.com' => 'BCC Recipient']);
echo "BCC address set".$br;

// Set Reply-To
$mailer->setReplyTo(['reply@example.com' => 'Reply To']);
echo "Reply-To address set".$br.$br;

// Get addresses
echo "Retrieved addresses:".$br;
echo "  From: ".var_export($mailer->getFrom(), true).$br;
echo "  To: ".var_export($mailer->getTo(), true).$br;
echo "  CC: ".var_export($mailer->getCC(), true).$br;
echo "  BCC: ".var_export($mailer->getBCC(), true).$br;
echo "  Reply-To: ".var_export($mailer->getReplyTo(), true).$br.$br;

// Example 5: Set Subject and Body
echo "Example 5: Set Subject and Body".$br;
echo "--------------------------------".$br;

$mailer->setSubject("Test Email Subject");
$mailer->setBody(
    "<h1>Test Email</h1><p>This is a <b>test</b> email body with HTML.</p>",
    true, // HTML mode
    "This is a test email body (plain text version)." // Alt body
);

echo "Subject: ".$mailer->getSubject().$br;
echo "Body (HTML): ".substr($mailer->getBody(), 0, 50)."...".$br;
echo "Alt Body: ".$mailer->getAltBody().$br.$br;

// Example 6: Add Attachments
echo "Example 6: Add Attachments".$br;
echo "--------------------------------".$br;

// Create a temporary file for testing
$tempFile = tempnam(sys_get_temp_dir(), 'mail_test_');
file_put_contents($tempFile, "This is a test attachment content.");

// Regular attachment
if ($mailer->addAttachment($tempFile, 'test.txt')) {
    echo "Regular attachment added: test.txt".$br;
} else {
    echo "Failed to add attachment: ".$mailer->lastError.$br;
}

// String attachment
if ($mailer->addStringAttachment("This is string attachment content.", 'string.txt')) {
    echo "String attachment added: string.txt".$br;
} else {
    echo "Failed to add string attachment: ".$mailer->lastError.$br;
}

// Embedded image
if ($mailer->addEmbeddedImage($tempFile, 'test_image', 'test.jpg')) {
    echo "Embedded image added: test.jpg (CID: test_image)".$br;
} else {
    echo "Failed to add embedded image: ".$mailer->lastError.$br;
}

// Get attachments
$attachments = $mailer->getAttachments();
echo "Total attachments: ".count($attachments).$br.$br;

// Cleanup
if (file_exists($tempFile)) {
    unlink($tempFile);
}

// Example 7: Reset Mailer
echo "Example 7: Reset Mailer".$br;
echo "--------------------------------".$br;

echo "Before reset:".$br;
echo "  To addresses: ".count($mailer->getTo()).$br;
echo "  Attachments: ".count($mailer->getAttachments()).$br;

// Reset with options
$mailer->reset(false, false, false, true); // Keep attachments
echo "After reset (keeping attachments):".$br;
echo "  To addresses: ".count($mailer->getTo()).$br;
echo "  Attachments: ".count($mailer->getAttachments()).$br;

// Full reset
$mailer->reset();
echo "After full reset:".$br;
echo "  To addresses: ".count($mailer->getTo()).$br;
echo "  Attachments: ".count($mailer->getAttachments()).$br.$br;

// Example 8: SMTP Configuration
echo "Example 8: SMTP Configuration".$br;
echo "--------------------------------".$br;

$smtpConfig = [
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'user@example.com',
    'password' => 'password',
    'encryption' => 'tls',
    'auth' => true,
    'debug' => 0,
    'timeout' => 30
];

$mailer->setConfig($smtpConfig);
$smtpInfo = $mailer->getSMTP();
echo "SMTP Configuration:".$br;
echo "  Mode: ".$smtpInfo['mode'].$br;
echo "  Host: ".$smtpInfo['host'].$br;
echo "  Port: ".$smtpInfo['port'].$br;
echo "  Encryption: ".$smtpInfo['encryption'].$br;
echo "  Auth: ".var_export($smtpInfo['auth'], true).$br.$br;

// Example 9: Set Mailer Mode
echo "Example 9: Set Mailer Mode".$br;
echo "--------------------------------".$br;

$modes = [Mailer::MODE_MAIL, Mailer::MODE_SMTP, Mailer::MODE_SENDMAIL, Mailer::MODE_QMAIL];

foreach ($modes as $mode) {
    if ($mailer->setMode($mode)) {
        echo "Mode set to: $mode".$br;
    } else {
        echo "Failed to set mode to $mode: ".$mailer->lastError.$br;
    }
}
echo $br;

// Example 10: Async Mode (Spool)
echo "Example 10: Async Mode (Spool)".$br;
echo "--------------------------------".$br;

// Configure for async mode
$mailer->setConfig([
    'spoolPath' => __DIR__.'/../var/spool/mailer',
    'async' => true
]);

echo "Async mode enabled".$br;
echo "Spool path: ".$mailer->spoolPath.$br;

// Prepare email
$mailer->reset();
$mailer->setFrom(['sender@example.com' => 'Test Sender']);
$mailer->setTo(['recipient@example.com' => 'Test Recipient']);
$mailer->setSubject("Test Async Email");
$mailer->setBody("This is a test email for async mode.", false);

// Send (will be saved to spool instead of sending immediately)
echo "Sending email in async mode:".$br;
$result = $mailer->send();
if ($result) {
    echo "  - Email saved to spool successfully".$br;
    echo "  - Check spool directory for JSON files".$br;
} else {
    echo "  - Failed: ".$mailer->lastError.$br;
}
echo $br;

// Example 11: Process Async Emails
echo "Example 11: Process Async Emails".$br;
echo "--------------------------------".$br;

echo "Processing async emails from spool:".$br;
$results = $mailer->sendAsync();
echo "  - Success: ".$results['success'].$br;
echo "  - Failed: ".$results['failed'].$br;
if (count($results['errors']) > 0) {
    echo "  - Errors:".$br;
    foreach ($results['errors'] as $error) {
        echo "    * ".$error['file'].": ".$error['error'].$br;
    }
}
echo $br;

// Example 12: Headers
echo "Example 12: Headers".$br;
echo "--------------------------------".$br;

$mailer->reset();
$mailer->setFrom(['sender@example.com' => 'Test']);
$mailer->setTo(['recipient@example.com' => 'Test']);
$mailer->setSubject("Test");
$mailer->setBody("Test");

$headers = $mailer->getHeaders();
echo "Email headers (first 200 chars):".$br;
echo substr($headers, 0, 200)."...".$br.$br;

// Example 13: Debug Information
echo "Example 13: Debug Information".$br;
echo "--------------------------------".$br;

$debugInfo = $mailer->__debugInfo();
echo "Mailer debug info:".$br;
echo "  mode: ".$debugInfo['mode'].$br;
echo "  async: ".var_export($debugInfo['async'], true).$br;
echo "  checkDNS: ".var_export($debugInfo['checkDNS'], true).$br;
echo "  useHTML: ".var_export($debugInfo['useHTML'], true).$br;
echo "  attachments count: ".count($debugInfo['attachments']).$br;
echo "  embeddedImages count: ".count($debugInfo['embeddedImages']).$br.$br;

echo "Example completed!".$br;
echo $br;
echo "Note: To actually send emails, configure SMTP settings in config file.".$br;
