<?php
/**
 * PHP Mailer Test Script
 * VMS - Pipe Manufacturing Company
 * 
 * Use this to test your PHP Mailer and Office 365 SMTP configuration
 * Run from command line: php config/mailer-test.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PHP MAILER CONFIGURATION TEST ===\n\n";

// Check if Composer autoload exists
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo "[ERROR] Composer autoload not found.\n";
    echo "Please run: composer require phpmailer/phpmailer\n";
    exit(1);
}

echo "[OK] Composer autoload found\n";

// Load mailer configuration
require_once __DIR__ . '/mailer.php';

echo "[OK] Mailer configuration loaded\n\n";

// Display configuration (without password)
echo "Configuration Settings:\n";
echo "  SMTP Host: " . MAIL_HOST . "\n";
echo "  SMTP Port: " . MAIL_PORT . "\n";
echo "  Security: " . MAIL_SECURE . "\n";
echo "  From Email: " . MAIL_FROM_EMAIL . "\n";
echo "  From Name: " . MAIL_FROM_NAME . "\n";
echo "  Username: " . MAIL_USERNAME . "\n";
echo "  Password: " . str_repeat('*', strlen(MAIL_PASSWORD)) . "\n\n";

// Test SMTP Connection
echo "=== TESTING SMTP CONNECTION ===\n";

try {
    $mail = initializeMailer();
    
    if (!$mail) {
        echo "[ERROR] Failed to initialize mailer\n";
        exit(1);
    }
    
    echo "[OK] Mailer initialized\n";
    
    // Test SMTP connection (without sending)
    if (@$mail->smtpConnect()) {
        echo "[OK] Successfully connected to SMTP server\n";
        $mail->smtpClose();
    } else {
        echo "[ERROR] Failed to connect to SMTP server\n";
        echo "Error: " . $mail->ErrorInfo . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== SENDING TEST EMAIL ===\n";

$testEmail = readline("Enter recipient email address for test: ");

if (empty($testEmail)) {
    echo "Email address required. Skipping send test.\n";
    exit(0);
}

try {
    $htmlBody = "
    <html>
        <body style='font-family: Arial; line-height: 1.6;'>
            <h2>PHP Mailer Test Email</h2>
            <p>This is a test email from the VMS (Visitor Management System).</p>
            <p><strong>Configuration Details:</strong></p>
            <ul>
                <li>Server: " . MAIL_HOST . "</li>
                <li>Port: " . MAIL_PORT . "</li>
                <li>From: " . MAIL_FROM_EMAIL . "</li>
            </ul>
            <p style='color: green;'><strong>✓ If you received this email, your PHP Mailer configuration is working correctly!</strong></p>
            <p>You can now proceed with integrating the email functionality into your VMS modules.</p>
        </body>
    </html>";
    
    $textBody = "PHP Mailer Test Email - If you received this, the configuration is working correctly!";
    
    $result = sendEmail($testEmail, 'VMS PHP Mailer Test', $htmlBody, $textBody);
    
    if ($result['success']) {
        echo "[OK] Test email sent successfully to: $testEmail\n";
        echo "\n✓ All tests passed! Your PHP Mailer is configured correctly.\n";
        echo "You can now integrate email functionality into your VMS modules.\n";
    } else {
        echo "[ERROR] Failed to send test email\n";
        echo "Error: " . $result['message'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== TEST COMPLETE ===\n";
