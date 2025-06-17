<?php
namespace Paheon\MeowBase\Tools;

use Paheon\MeowBase\ClassBase;
use Paheon\MeowBase\SysLog;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LogLevel;

// HTML Static Class //
class Mailer {

    use ClassBase;
    
    const MODE_MAIL = "mail";
    const MODE_SMTP = "smtp";
    const MODE_SENDMAIL = "sendmail";
    const MODE_QMAIL = "qmail";
    
    // Member variables //
    protected ?PHPMailer    $mailer = null;
    protected ?SysLog       $logger = null;
    protected string        $spoolPath = "";
    protected string        $logPath = "";
    protected string        $logPrefix = "";
    protected string        $mode = self::MODE_MAIL;
    protected bool          $async = false;
    protected bool          $checkDNS = false;
    protected bool          $useHTML = true;
    protected array         $embeddedImages = [];  // Store embedded images information
    protected array         $attachments = [];      // Store attachment information
    protected array         $stringAttachments = []; // Store string attachment information

    // Constructor //
    public function __construct(array $config = []) {
        $this->denyWrite = array_merge($this->denyWrite, [ 'mailer', 'logger', "mode", "headers", 'attachments', 'SMTP', 'stringAttachments', 'attachments', 'embeddedImages' ]);
        $this->denyRead = array_merge($this->denyRead, [ 'config' ]);
        if (isset($config['exceptions'])) $this->useException = $config['exceptions'];
        $this->mailer = new PHPMailer($this->useException);
        $this->setConfig($config);
        
        // Initialize logger if logPath is set
        if (!empty($this->logPath)) {
            if (!empty($this->logPrefix)) {
                $options = [
                    'prefix' => $this->logPrefix
                ];
            }
            $this->logger = new SysLog($this->logPath, LogLevel::DEBUG, $options);
        }
    }

    // Reset PHPMailer //
    public function reset(bool $keepFrom = false, bool $keepSubject = false, bool $keepBody = false, bool $keepAttachments = false):void {
        $this->mailer->clearAddresses();
        $this->mailer->clearAllRecipients();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        $this->mailer->clearCustomHeaders();
        $this->mailer->clearAttachments();
        if (!$keepFrom) {
            $this->mailer->From = '';
            $this->mailer->FromName = '';
        }
        if (!$keepSubject) {
            $this->mailer->Subject = '';
        }
        if (!$keepBody) {
            $this->mailer->Body = '';
            $this->mailer->AltBody = '';
        }
        if (!$keepAttachments) {
            $this->mailer->clearAttachments();
            $this->attachments = [];
            $this->stringAttachments = [];
            $this->embeddedImages = [];  
        }
    }

    // Setters //

    // Set Mailer Config //
    public function setConfig(array $setting = []):void {
        // PHPMailer settings //
        if (isset($setting['host'])) $this->mailer->Host = $setting['host'];
        if (isset($setting['port'])) $this->mailer->Port = $setting['port'];
        if (isset($setting['username'])) $this->mailer->Username = $setting['username'];
        if (isset($setting['password'])) $this->mailer->Password = $setting['password'];
        if (isset($setting['encryption'])) $this->mailer->SMTPSecure = $setting['encryption'];
        if (isset($setting['auth'])) $this->mailer->SMTPAuth = $setting['auth'];
        if (isset($setting['helo'])) $this->mailer->Helo = $setting['helo'];
        if (isset($setting['debug'])) $this->mailer->SMTPDebug = $setting['debug'];
        if (isset($setting['autoTLS'])) $this->mailer->SMTPAutoTLS = $setting['autoTLS'];
        if (isset($setting['timeout'])) $this->mailer->Timeout = $setting['timeout'];
        if (isset($setting['keepalive'])) $this->mailer->SMTPKeepAlive = $setting['keepalive'];

        // Local settings //
        if (isset($setting['spoolPath'])) $this->spoolPath = $setting['spoolPath'];
        if (isset($setting['logPath'])) $this->logPath = $setting['logPath'];
        if (isset($setting['logPrefix'])) $this->logPrefix = $setting['logPrefix'];
        if (isset($setting['mode'])) $this->setMode($setting['mode']);
        if (isset($setting['async'])) $this->async = $setting['async'];
        if (isset($setting['checkDNS'])) $this->checkDNS = $setting['checkDNS'];
    }    

    // Set Mailer Mode //
    public function setMode(string $mode):bool {
        $this->lastError = "";
        if (in_array($mode, [self::MODE_MAIL, self::MODE_SMTP, self::MODE_SENDMAIL, self::MODE_QMAIL])) {
            $this->mode = $mode;
            if ($this->mode == self::MODE_MAIL) {
                $this->mailer->isMail();
            } else if ($this->mode == self::MODE_SMTP) {
                $this->mailer->isSMTP();
            } else if ($this->mode == self::MODE_SENDMAIL) {
                $this->mailer->isSendmail();
            } else if ($this->mode == self::MODE_QMAIL) {
                $this->mailer->isQmail();
            }
        } else {            
            $this->mailer->isMail();
            $this->lastError = "Invalid mode: $mode, using default mode: " . self::MODE_MAIL;
            $this->throwException($this->lastError, 6);
            return false;
        }
        return true;
    }

    // Set To Address //
    public function setTo(array $address):void {
        $this->addAddress("to", $address, false);
    }

    // Set CC Address //
    public function setCC(array $address):void {
        $this->addAddress("cc", $address, false);
    }

    // Set BCC Address //
    public function setBCC(array $address):void {
        $this->addAddress("bcc", $address, false);
    }

    // Set Reply To Address //
    public function setReplyTo(array $address):void {
        $this->addAddress("replyto", $address, false);
    }

    // Set From Address //
    public function setFrom(array $address):void {
        $this->addAddress("from", $address, false);
    }

    // Mail Email Subject //
    public function setSubject(string $subject = ""):void {
        $this->mailer->Subject = $subject;
    }

    // Mail Email Body //
    public function setBody(string $body, bool $isHtml = true, string $altBody = ""):void {
        $this->mailer->Body = $body;
        $this->mailer->isHTML($isHtml);
        $this->useHTML = $isHtml;
        if ($altBody) {
            $this->mailer->AltBody = $altBody;
        }
    }

    public function setAltBody(string $altBody):void {
        $this->mailer->AltBody = $altBody;
    }

    // Add Address //
    // Type : to, cc, bcc, from, replyto
    // Address Format : ["email1" => "name1", "email2" => "name2", ... ]
    public function addAddress(string $type, array $address, bool $add = true):bool {
        $this->lastError = "";
        $type = strtolower($type);
        $success = true;
        try {
            if ($type == "to") {
                foreach($address as $email => $name) {
                    if (!$add) $this->mailer->clearAddresses();
                    $this->mailer->addAddress($email, $name);
                }
            } else if ($type == "from") {
                foreach($address as $email => $name) {
                    $this->mailer->setFrom($email, $name, true);
                    $this->mailer->Sender = $email;
                    break;
                }
            } else if ($type == "cc") {
                foreach($address as $email => $name) {
                    if (!$add) $this->mailer->clearCCs();
                    $this->mailer->addCC($email, $name);
                }
            } else if ($type == "bcc") {
                foreach($address as $email => $name) {
                    if (!$add) $this->mailer->clearBCCs();
                    $this->mailer->addBCC($email, $name);
                }
            } else if ($type == "replyto") {
                foreach($address as $email => $name) {
                    if (!$add) $this->mailer->clearReplyTos();
                    $this->mailer->addReplyTo($email, $name);
                }
            }
        } catch (\Exception $e) {
            $success = false;
            $this->lastError = $e->getMessage();
            $this->throwException($this->lastError, 3);
        }
        return $success;
    }

    // Add Attachment //
    public function addAttachment(string $path, string $name = '', string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', string $disposition = 'attachment'): bool {
        $this->lastError = "";
        try {
            $result = $this->mailer->addAttachment($path, $name, $encoding, $type, $disposition);
            if ($result) {
                // Store attachment information
                $this->attachments[] = [
                    'path' => $path,
                    'name' => $name,
                    'encoding' => $encoding,
                    'type' => $type,
                    'disposition' => $disposition
                ];
            }
            return $result;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->throwException($this->lastError, 4);
            return false;
        }
        return true;
    }

    // Add String Attachment //
    public function addStringAttachment(string $string, string $filename, string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', string $disposition = 'attachment'): bool {
        $this->lastError = "";
        try {
            $result = $this->mailer->addStringAttachment($string, $filename, $encoding, $type, $disposition);
            if ($result) {
                // Store embedded image information
                $this->stringAttachments[] = [
                    'string' => $string,
                    'filename' => $filename,
                    'encoding' => $encoding,
                    'type' => $type,
                    'disposition' => $disposition
                ];
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->throwException($this->lastError, 4);
            return false;
        }
        return true;
    }

    // Add Embedded Attachment //
    public function addEmbeddedImage(string $path, string $cid, string $name = '', string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', string $disposition = 'inline'): bool {
        $this->lastError = "";
        try {
            $result = $this->mailer->addEmbeddedImage($path, $cid, $name, $encoding, $type, $disposition);
            if ($result) {
                // Store embedded image information
                $this->embeddedImages[] = [
                    'path' => $path,
                    'cid' => $cid,
                    'name' => $name,
                    'encoding' => $encoding,
                    'type' => $type,
                    'disposition' => $disposition
                ];
            }
            return $result;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->throwException($this->lastError, 4);
            return false;
        }
        return true;
    }


    // Getters //

    // Get Email Addresses //
    public function getTo():array {
        $addrList = $this->mailer->getToAddresses();
        $result = [];
        foreach ($addrList as $addr) {
            $result[$addr[0]] = $addr[1] ?? "";
        }
        return $result;
    }

    public function getCC():array {
        $addrList = $this->mailer->getCCAddresses();
        $result = [];
        foreach ($addrList as $addr) {
            $result[$addr[0]] = $addr[1] ?? "";
        }
        return $result;
    }

    public function getBCC():array {
        $addrList = $this->mailer->getBCCAddresses();
        $result = [];
        foreach ($addrList as $addr) {
            $result[$addr[0]] = $addr[1] ?? "";
        }
        return $result;
    }   

    public function getReplyTo():array {
        $addrList = $this->mailer->getReplyToAddresses( );
        $result = [];
        foreach ($addrList as $addr) {
            $result[$addr[0]] = $addr[1] ?? "";
        }
        return $result;
    }   

    public function getFrom():array {
        if (empty($this->mailer->From)) {
            return [];
        }
        return [ $this->mailer->From => $this->mailer->FromName ];
    }

    public function getSubject():string {
        return $this->mailer->Subject;
    }

    public function getBody():string {
        return $this->mailer->Body;
    }

    public function getAltBody():string {
        return $this->mailer->AltBody;
    }   

    public function getAttachments():array {
        return $this->mailer->getAttachments();
    }       

    public function getHeaders():string {
        return $this->mailer->getMailMIME();
    }   
    
    public function getSMTP():array {
        return [
            'mode' => $this->mode,
            'host' => $this->mailer->Host,
            'port' => $this->mailer->Port,
            'username' => $this->mailer->Username,
            'password' => $this->mailer->Password,
            'encryption' => $this->mailer->SMTPSecure,
            'auth' => $this->mailer->SMTPAuth,
            'helo' => $this->mailer->Helo,
            'debug' => $this->mailer->SMTPDebug
        ];
    }   

    // Single Email Validate //
    public function emailValidate(string $email, bool $checkDNS = false):bool {
        $this->lastError = "";
        if (!PHPMailer::validateAddress($email)) { // !filter_var($email, FILTER_VALIDATE_EMAIL)
            $this->lastError = "Invalid email address: $email";
            return false;
        }
        if ($checkDNS) {
            // Check if the domain exists (slow operation) //
            $domain = explode('@', $email);
            if (!checkdnsrr($domain[1], 'MX')) {
                $this->lastError = "Domain '".$domain[1]."' does not exist!";
                return false;
            }
        }
        return true;
    }

    // Get Valid Email address //
    // String Format : getValidAddr("email", "name");
    // Array Format : getValidAddr(["email1" => "name1", "email2" => "name2", ... ])
    public function getValidAddr(array|string $address, string $name = "", bool $checkDNS = false):array {
        $this->lastError = "";
        $validAddr = [];
        if (!is_array($address)) $address = [$address => $name];

        // Disable exception //
        $prevException = $this->useException;
        $this->useException = false;

        // Check each address //
        foreach ($address as $emailAddr => $name) {
            if ($this->emailValidate($emailAddr, $checkDNS)) {
                $validAddr[$emailAddr] = $name;
            }
        }
        // Restore exception //
        $this->useException = $prevException;

        return $validAddr;
    }

    // Extract email data to JSON file //
    protected function extractToJSON(): bool {
        $this->lastError = "";
        if (empty($this->spoolPath)) {
            $this->lastError = "Spool path not set!";
            $this->throwException($this->lastError, 5);
            return false;
        }

        try {
            // Create spool directory if not exists
            if (!is_dir($this->spoolPath)) {
                mkdir($this->spoolPath, 0755, true);
                if (!is_dir($this->spoolPath)) {
                    $this->lastError = "Failed to create spool directory!";
                    $this->throwException($this->lastError, 5);
                    return false;
                }
            }

            // Prepare email data
            $emailData = [
                // Add SMTP settings
                'smtp' => $this->getSMTP(),

                // Add email data
                'from' => $this->getFrom(),
                'to' => $this->getTo(),
                'cc' => $this->getCC(),
                'bcc' => $this->getBCC(),
                'replyTo' => $this->getReplyTo(),
                'subject' => $this->getSubject(),
                'body' => $this->getBody(),
                'altBody' => $this->getAltBody(),
                'timestamp' => time(),
                'headers' => $this->getHeaders(),
                'useHTML' => $this->useHTML,

                // Add embedded images from our stored information
                'attachments'       => $this->attachments,
                'embeddedImages'    => $this->embeddedImages,
                'stringAttachments' => $this->stringAttachments
            ];

            // Generate unique filename
            $filename = $this->spoolPath . '/mail_' . uniqid() . '.json';
            
            // Save to file
            if (file_put_contents($filename, json_encode($emailData, JSON_PRETTY_PRINT))) {
                // Log the extraction
                if ($this->logger) {
                    $this->logger->sysLog("Email send to spool", [
                        'filename' => $filename,
                        'to' => $this->mailer->getToAddresses(),
                        'from' => $this->mailer->From,
                        'subject' => $this->mailer->Subject,
                    ], LogLevel::INFO);
                }
                return true;
            }
            
            $this->lastError = "Failed to write email data to spool file";
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->throwException($this->lastError, 6);
            return false;
        }
    }

    // Send Mail //
    public function send() {
        $this->lastError = "";
        try {
            // Extract email data instead of sending in async mode
            if ($this->async) {
                return $this->extractToJSON();
            }

            // Normal sending process (direct mode)
            if (!$this->mailer->send()) {
                $this->lastError = $this->mailer->ErrorInfo;
                if ($this->logger) {
                    $this->logger->sysLog("Failed to send email", [
                        'error' => $this->lastError,
                        'to' => $this->getTo(),
                        'from' => $this->getFrom(),
                        'subject' => $this->getSubject(),
                    ], LogLevel::ERROR);
                }
                $this->throwException($this->lastError, 7);
                return false;
            }

            if ($this->logger) {
                $this->logger->sysLog("Email sent successfully", [
                    'to' => $this->getTo(),
                    'from' => $this->getFrom(),
                    'subject' => $this->getSubject(),
                ], LogLevel::INFO);
            }
            return true;

        } catch (\Exception $e) {

            $this->lastError = $e->getMessage();
            if ($this->logger) {
                $this->logger->sysLog("Exception while sending email", [
                    'error' => $this->lastError,
                    'subject' => $this->getSubject(),
                    'to' => $this->getTo()
                ], LogLevel::ERROR);
            }
            $this->throwException($this->lastError, 7);
            return false;

        }
    }
        
    // Send Async Emails //
    public function sendAsync(): array {
        $this->lastError = "";
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (empty($this->spoolPath) || !is_dir($this->spoolPath)) {
            $this->lastError = "Spool path not set or invalid";
            $this->throwException($this->lastError, 8);
            return $results;
        }

        // Backup current SMTP config and async mode
        $prevSMTP = $this->getSMTP();
        $prevAsync = $this->async;

        // Get all JSON files in spool directory
        $fileList = glob($this->spoolPath . '/mail_*.json');
        foreach ($fileList as $file) {
            try {
                // Read and decode email data
                $emailData = json_decode(file_get_contents($file), true);
                if (!$emailData) {
                    throw new \Exception("Invalid JSON data in file: " . $file);
                }

                // Reset mailer
                $this->reset();

                // Configure SMTP settings
                if (isset($emailData['smtp'])) {
                    $this->setConfig($emailData['smtp']);
                }

                // Set addresses
                if (isset($emailData['from'])) {
                    $this->addAddress('from', $emailData['from']);
                }
                if (isset($emailData['to'])) {
                    $this->addAddress('to', $emailData['to']);
                }
                if (isset($emailData['cc'])) {
                    $this->addAddress('cc', $emailData['cc']);
                }
                if (isset($emailData['bcc'])) {
                    $this->addAddress('bcc', $emailData['bcc']);
                }
                if (isset($emailData['replyTo'])) {
                    $this->addAddress('replyto', $emailData['replyTo']);
                }

                // Set subject and body
                $this->setSubject($emailData['subject']);
                $this->setBody($emailData['body'], $emailData['useHTML'] ?? true, $emailData['altBody'] ?? '');

                // Add attachments
                if (isset($emailData['attachments'])) {
                    foreach ($emailData['attachments'] as $attachment) {
                        $this->addAttachment(
                            $attachment['path'],
                            $attachment['name'],
                            $attachment['encoding'],
                            $attachment['type'],
                            $attachment['disposition']
                        );
                    }
                }

                // Add string attachments
                if (isset($emailData['stringAttachments'])) {
                    foreach ($emailData['stringAttachments'] as $attachment) {
                        $this->addStringAttachment(
                            $attachment['string'],
                            $attachment['filename'],
                            $attachment['encoding'],
                            $attachment['type'],
                            $attachment['disposition']
                        );
                    }
                }

                // Add embedded images
                if (isset($emailData['embeddedImages'])) {
                    foreach ($emailData['embeddedImages'] as $image) {
                        $this->addEmbeddedImage(
                            $image['path'],
                            $image['cid'],
                            $image['name'],
                            $image['encoding'],
                            $image['type'],
                            $image['disposition']
                        );
                    }
                }

                // Send email //
                $this->async = false;                           // Force to send in direct mode
                $this->mailer->SMTPDebug = $prevSMTP['debug'];  // Keep to use current debug level
                
                if ($this->send()) {
                    $results['success']++;
                    // Delete the file after successful sending
                    if (file_exists($file)) unlink($file);
                    
                    if ($this->logger) {
                        $this->logger->sysLog("Async email sent successfully", [
                            'file' => $file,
                            'to' => $emailData['to'],
                            'from' => $emailData['from'],
                            'subject' => $emailData['subject'],
                        ], LogLevel::INFO);
                    }
                } else {
                    $this->lastError = "Failed to send async email";
                    $results['failed']++;
                    $results['errors'][] = [
                        'file' => $file,
                        'error' => $this->lastError
                    ];
                    if ($this->logger) {
                        $this->logger->sysLog("Failed to send async email", [
                            'file' => $file,
                            'subject' => $emailData['subject'],
                            'to' => $emailData['to']
                        ], LogLevel::ERROR);
                    }
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'file' => $file,
                    'error' => $e->getMessage()
                ];
                if ($this->logger) {
                    $this->logger->sysLog("Exception while processing async email", [
                        'file' => $file,
                        'error' => $e->getMessage()
                    ], LogLevel::ERROR);
                }
            }
        }

        // Restore SMTP config and async mode
        $this->setConfig($prevSMTP);
        $this->async = $prevAsync;

        return $results;
    }
}

