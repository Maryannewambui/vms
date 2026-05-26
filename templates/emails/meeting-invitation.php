<?php
/**
 * Email Template: Meeting Invitation
 *
 * @var string $hostName
 * @var string $visitorName
 * @var string $visitorCompany
 * @var string $visitorEmail
 * @var string $visitorPhone
 * @var string $meetingDate
 * @var string $meetingTime
 * @var string $meetingLocation
 * @var string $meetingPurpose
 * @var string $confirmLink
 * @var bool $requiresApproval
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #003366; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background: #003366; color: white; text-decoration: none; border-radius: 5px; }
        .detail-row { margin: 10px 0; }
        .detail-label { font-weight: bold; display: inline-block; width: 120px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Meeting Scheduled - DANCO PLASTICS</h1>
            <p>Visitor Management System</p>
        </div>
        
        <div class="content">
            <p>Dear <?= htmlspecialchars($hostName) ?>,</p>
            
            <p>A meeting has been scheduled for you with an external visitor. Please review the details below:</p>
            
            <div class="detail-row">
                <span class="detail-label">Visitor:</span>
                <strong><?= htmlspecialchars($visitorName) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Company:</span>
                <strong><?= htmlspecialchars($visitorCompany) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <strong><?= htmlspecialchars($visitorEmail) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <strong><?= htmlspecialchars($visitorPhone) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Meeting Date:</span>
                <strong><?= htmlspecialchars($meetingDate) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Meeting Time:</span>
                <strong><?= htmlspecialchars($meetingTime) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Location:</span>
                <strong><?= htmlspecialchars($meetingLocation) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Purpose:</span>
                <strong><?= htmlspecialchars($meetingPurpose) ?></strong>
            </div>
            
            <?php if (!empty($requiresApproval)): ?>
            <div class="warning">
                <strong>Action Required:</strong> The visitor pass requires your approval before the visitor can be checked in. You will receive an approval request separately.
            </div>
            <?php endif; ?>
            
            <p style="margin-top: 20px;">
                <a href="<?= $confirmLink ?>" class="button">View Meeting Details</a>
            </p>
            
            <p>Please prepare accordingly and ensure you are available at the scheduled time.</p>
            
            <p>If you have any questions or need to reschedule, please contact the reception desk or reply to this email.</p>
            
            <p>Thank you,<br>
            <strong>DANCO PLASTICS - Visitor Management System</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message. Contact the reception desk for assistance.</p>
            <p>&copy; DANCO PLASTICS <?= date('Y') ?> - All rights reserved</p>
        </div>
    </div>
</body>
</html>
