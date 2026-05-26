<?php
/**
 * Email Template: Approval Request for Visitor Pass
 *
 * @var string $hostName
 * @var string $visitorName
 * @var string $visitorCompany
 * @var string $visitorEmail
 * @var string $visitorPhone
 * @var string $passNumber
 * @var string $expectedArrival
 * @var string $purpose
 * @var string $visitorCategory
 * @var string $approveLink
 * @var string $rejectLink
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d9534f; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { background: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background: #d9534f; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .approve { background: #5cb85c; }
        .detail-row { margin: 10px 0; }
        .detail-label { font-weight: bold; display: inline-block; width: 120px; }
        .alert { background: #f2dede; border: 1px solid #ebccd1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .button-group { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Visitor Pass Approval Required</h1>
            <p>DANCO PLASTICS - Visitor Management System</p>
        </div>
        
        <div class="content">
            <p>Dear <?= htmlspecialchars($hostName) ?>,</p>
            
            <p>A visitor pass has been created and requires your approval before the visitor can be checked in.</p>
            
            <div class="alert">
                <strong>Approval Required:</strong> Please review and approve or reject the pass before the visitor arrives.
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
            
            <div class="detail-row">
                <span class="detail-label">Contact:</span>
                <strong><?= htmlspecialchars($visitorEmail) ?> / <?= htmlspecialchars($visitorPhone) ?></strong>
            </div>
            
            <h3>Visit Details</h3>
            <div class="detail-row">
                <span class="detail-label">Pass Number:</span>
                <strong><?= htmlspecialchars($passNumber) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Expected Arrival:</span>
                <strong><?= htmlspecialchars($expectedArrival) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Purpose:</span>
                <strong><?= htmlspecialchars($purpose) ?></strong>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Category:</span>
                <strong><?= htmlspecialchars($visitorCategory) ?></strong>
            </div>
            
            <div class="button-group">
                <a href="<?= $approveLink ?>" class="button approve">Approve Pass</a>
                <a href="<?= $rejectLink ?>" class="button">Reject Pass</a>
            </div>
            
            <p>Please review this request and approve or reject accordingly. If you have any questions about this visitor, you may reject the pass and contact the reception desk.</p>
            
            <p>Thank you,<br>
            <strong>DANCO PLASTICS - Visitor Management System</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message. Use the buttons above to approve or reject.</p>
            <p>&copy; DANCO PLASTICS <?= date('Y') ?> - All rights reserved</p>
        </div>
    </div>
</body>
</html>
