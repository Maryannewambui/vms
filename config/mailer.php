<?php

// Email Configuration Constants
define('MAIL_HOST', 'dancoplastics.com');
define('MAIL_PORT', 465);
define('MAIL_SECURE', 'ssl');
define('MAIL_USERNAME', 'visitors@dancoplastics.com');      // Change to your Office 365 email
define('MAIL_PASSWORD', 'Saniflex@123');                 // Change to your Office 365 password
define('MAIL_FROM_EMAIL', 'visitors@dancoplastics.com');            // Company email address
define('MAIL_FROM_NAME', 'DANCO PLASTICS - VMS');               // Display name

// Email Settings
define('MAIL_DEBUG', false);                                    // Set to true for debugging
define('MAIL_CHARSET', 'UTF-8');

// Auto-load Composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Initialize PHPMailer with Office 365 configuration
 */
function initializeMailer() {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->SMTPAuth = true;
        
        // Credentials
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        
        // Sender information
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        
        // Default settings
        $mail->isHTML(true);
        $mail->CharSet = MAIL_CHARSET;
        
        // Debug mode
        if (MAIL_DEBUG) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'html';
        }
        
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer Error: {$e->getMessage()}");
        return null;
    }
}

/**
 * Send email to single recipient
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '', $attachments = []) {
    try {
        $mail = initializeMailer();
        
        if (!$mail) {
            throw new Exception('Failed to initialize mailer');
        }
        
        // Add recipient
        $mail->addAddress($to);
        
        // Add attachments if provided
        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    $mail->addAttachment($file);
                }
            }
        }
        
        // Set subject and body
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        
        if (!empty($textBody)) {
            $mail->AltBody = $textBody;
        }
        
        // Send
        $result = $mail->send();
        
        // Log the email
        logEmailActivity($to, $subject, true);
        
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Failed to send email: {$e->getMessage()}");
        logEmailActivity($to ?? 'unknown', $subject ?? 'unknown', false, $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Send email to multiple recipients
 */
function sendEmailBulk($recipients, $subject, $htmlBody, $textBody = '', $attachments = []) {
    $results = [];
    
    foreach ($recipients as $email) {
        $results[$email] = sendEmail($email, $subject, $htmlBody, $textBody, $attachments);
    }
    
    return $results;
}

/**
 * Log email activity for audit trail
 */
function logEmailActivity($recipient, $subject, $sent = true, $error = '') {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO email_logs (recipient, subject, sent, error_message, sent_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $recipient,
            $subject,
            $sent ? 1 : 0,
            $error
        ]);
    } catch (Exception $e) {
        error_log("Failed to log email activity: " . $e->getMessage());
    }
}

/**
 * Get email template
 */
function getEmailTemplate($templateName, $data = []) {
    $templatePath = __DIR__ . '/../templates/emails/' . $templateName . '.php';
    
    if (!file_exists($templatePath)) {
        return null;
    }
    
    ob_start();
    extract($data);
    include $templatePath;
    return ob_get_clean();
}
