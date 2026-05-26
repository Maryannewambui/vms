<?php
/**
 * Email Template: Checkout Reminder for Host
 *
 * @var string $hostName
 * @var string $visitorName
 * @var string $visitorCompany
 * @var string $checkInTime
 * @var string $scheduledCheckOut
 * @var string $overdueTime
 * @var string $checkoutLink
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ff9800; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background: #ff9800; color: white; text-decoration: none; border-radius: 5px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 10px 0; font-size: 16px; }
        .detail-row { margin: 10px 0; }
        .detail-label { font-weight: bold; display: inline-block; width: 120px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Visitor Checkout Reminder</h1>
            <p>DANCO PLASTICS - Visitor Management System</p>
        </div>
        
        <div class="content">
            <p>Dear <?= htmlspecialchars($hostName) ?>,</p>
            
            <div class="warning">
                <strong>Reminder:</strong> A visitor has exceeded their scheduled visit duration and has not checked out.
            </div>
            
            <h3>Visitor Information</h3>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <strong><?= htmlspecialchars($visitorName) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Company:</span>
                <strong><?= htmlspecialchars($visitorCompany) ?></strong>
            </div>
            
            <h3>Visit Timeline</h3>
            <div class="detail-row">
                <span class="detail-label">Check-In Time:</span>
                <strong><?= htmlspecialchars($checkInTime) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Scheduled Checkout:</span>
                <strong><?= htmlspecialchars($scheduledCheckOut) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Overdue by:</span>
                <strong style="color: #d9534f;"><?= htmlspecialchars($overdueTime) ?></strong>
            </div>
            
            <p style="margin: 20px 0;">Please ensure the visitor completes their check-out at the reception desk immediately, or contact security if there are any concerns.</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="<?= $checkoutLink ?>" class="button">Process Checkout</a>
            </div>
            
            <p>If you believe this is an error or have questions, please contact the reception desk or security team.</p>
            
            <p>Thank you,<br>
            <strong>DANCO PLASTICS - Visitor Management System</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated reminder email from the Visitor Management System.</p>
            <p>&copy; DANCO PLASTICS <?= date('Y') ?> - All rights reserved</p>
        </div>
    </div>
</body>
</html>
