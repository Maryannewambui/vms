# VMS System Implementation & Setup Guide

## Overview

This document outlines all the fixes and improvements made to the Visitor Management System (VMS) for DANCO PLASTICS, including checkout functionality improvements, email integration, and approval workflows.

---

## Part 1: Database Setup

### Step 1: Apply Database Migrations

Run the following SQL migration files in your database in order:

```bash
# From phpMyAdmin or command line:
mysql -u root vms_pipe_manufacturing < database/migration_checkout_records.sql
mysql -u root vms_pipe_manufacturing < database/migration_notification_types.sql
mysql -u root vms_pipe_manufacturing < database/migration_email_logs.sql
```

Or execute the SQL directly in phpMyAdmin:
1. Go to http://localhost/phpmyadmin
2. Select the `vms_pipe_manufacturing` database
3. Go to "SQL" tab
4. Copy and paste the migration SQL code
5. Click "Go"

**What these migrations do:**
- `migration_checkout_records.sql`: Creates checkout records table for detailed checkout tracking
- `migration_notification_types.sql`: Adds new notification types
- `migration_email_logs.sql`: Creates email logs table for audit trail

---

## Part 2: PHP Mailer Setup (For Email Functionality)

### Step 1: Install Composer (if not already installed)

Download from: https://getcomposer.org/download/

### Step 2: Install PHPMailer

Open terminal/command prompt in your project folder (`c:\xampp\htdocs\vms`) and run:

```bash
composer require phpmailer/phpmailer
```

### Step 3: Configure Mailer

Edit `config/mailer.php` and update with your Office 365 credentials:

```php
define('MAIL_USERNAME', 'your-email@danco-plastics.com');    // Your Office 365 email
define('MAIL_PASSWORD', 'your-password-here');               // Your Office 365 password
define('MAIL_FROM_EMAIL', 'vms@danco-plastics.com');         // Company email
define('MAIL_FROM_NAME', 'DANCO PLASTICS - VMS');
```

### Step 4: Test Configuration

Run the test script:
```bash
php config/mailer-test.php
```

This will verify your SMTP connection and send a test email.

---

## Part 3: Fixed Issues & New Features

### Issue 1: Checkout Not Recording Data ✓ FIXED

**Problem:** Checkout marked success but no detailed records were kept

**Solution:** 
- Created new `checkout_records` table for detailed checkout tracking
- Updated `checkout/index.php` to:
  - Create checkout records with complete audit trail
  - Record duration in minutes
  - Display enhanced confirmation with visitor details
  - Send notifications to host on checkout

**How it works:**
1. Guard checks out visitor → Form submitted
2. System updates visit status to "checked_out"
3. Creates detailed checkout record
4. Calculates and stores duration
5. Shows beautiful confirmation with all details

### Issue 2: Table/Field Inconsistencies ✓ FIXED

**Problems Fixed:**
- Fixed `schedule.php` visitor creation (removed extra `designation` field)
- Fixed visits INSERT statement to use correct fields
- Removed broken approval insertion logic
- Added proper approval workflow after visit creation

**Files Updated:**
- `modules/meetings/schedule.php`
- `includes/functions.php`
- Database schema consistency verified

### Issue 3: PHP Mailer Not Integrated ✓ FIXED

**Solution Implemented:**
- Created `config/mailer.php` with full PHPMailer configuration
- Added email templates:
  - `templates/emails/meeting-invitation.php`
  - `templates/emails/approval-request.php`
  - `templates/emails/checkout-reminder.php`
- Integrated email sending in:
  - Meeting scheduling (sends to host)
  - Visitor pass approval (sends approval/rejection)
  - Checkout reminders (for overdue visitors)

---

## Part 4: Complete Workflow

### Workflow 1: Schedule Meeting with Pre-Registration

```
1. Receptionist visits: Modules → Schedule Meeting
2. Fills meeting details:
   - Meeting title, date, time
   - Department and host
   - Add visitor details (name, email, company)
   - Select visitor category
3. Submits form
   ↓
4. System:
   - Creates meeting record
   - Pre-registers visitor
   - Creates visit record
   - Generates visitor pass
   - Sends meeting invitation email to host
   - Sends in-app notification
5. Host receives email with meeting details
6. If approval required: pass goes to approval workflow
```

### Workflow 2: Approve Visitor Pass

```
1. Host receives notification about pending approval
2. Goes to: Modules → Approvals
3. Reviews visitor information
4. Clicks "Review" on pending pass
5. Chooses to Approve or Reject
   ↓ IF APPROVED:
   - Pass status changed to "approved"
   - Visitor notified (email + notification)
   - Pass ready for checkin
   ↓ IF REJECTED:
   - Pass revoked
   - Visit cancelled
   - Visitor notified with reason
```

### Workflow 3: Visitor Check-In

```
1. Visitor arrives
2. Guard/Receptionist:
   - Goes to Modules → Check-In
   - Searches for pre-registered visitor OR creates new checkin
3. If pre-registered:
   - System loads visitor details
   - Guard verifies approval status
   - If approved → confirms checkin
   - Pass activated
4. If walk-in:
   - Enter visitor details
   - Select category and host
   - Generate pass
5. Visitor checked in successfully
```

### Workflow 4: Visitor Check-Out

```
1. Visitor completes visit
2. Guard/Receptionist:
   - Goes to Modules → Check-Out
   - Finds visitor in "Checked-In" list
   - Clicks "Check Out"
3. Form shows:
   - Visitor details
   - Check-in time
   - Current duration
4. Guard fills:
   - Badge returned? (checkbox)
   - Security notes (if any)
   - Optional visitor feedback/rating
5. Submits checkout
   ↓
6. System:
   - Updates visit status to "checked_out"
   - Creates detailed checkout record
   - Deactivates visitor pass
   - Sends notification to host
   - Calculates and stores duration
   - Shows confirmation with all details
7. Guard sees checkout success screen
8. Visitor exits premises
```

---

## Part 5: File Structure

### New/Modified Files

```
config/
├── mailer.php                           # NEW - PHP Mailer configuration
└── mailer-test.php                      # NEW - Test script

modules/
├── approvals/
│   └── index.php                        # NEW - Approval workflow
├── checkout/
│   └── index.php                        # MODIFIED - Enhanced checkout
├── checkin/
│   └── index.php                        # Verified compatibility
└── meetings/
    └── schedule.php                     # FIXED - Corrected field mapping

templates/
├── emails/
│   ├── meeting-invitation.php           # NEW
│   ├── approval-request.php             # NEW
│   └── checkout-reminder.php            # NEW
└── [existing templates]

database/
├── migration_checkout_records.sql       # NEW - Checkout table
├── migration_notification_types.sql     # NEW - Notification types
├── migration_email_logs.sql             # NEW - Email logs
└── [existing migrations]
```

---

## Part 6: Testing the System

### Test Checklist

- [ ] Database migrations applied successfully
- [ ] PHP Mailer installed and configured
- [ ] Mailer test script passes
- [ ] Schedule a test meeting
- [ ] Meeting invitation email received
- [ ] Check visitor pass approval needed
- [ ] Approve/reject the pass
- [ ] Test visitor check-in
- [ ] Test visitor check-out
- [ ] Checkout details displayed correctly
- [ ] Checkout record created in database
- [ ] Notifications sent properly

### Test Scenarios

**Scenario 1: Pre-registered Meeting Visitor**
1. Schedule meeting for tomorrow
2. Add visitor details
3. Verify email sent to host
4. Check visitor pre-registration
5. On arrival: search and checkin visitor
6. Verify pass shows as approved
7. Checkout visitor
8. Verify duration recorded

**Scenario 2: Approval Workflow**
1. Schedule meeting with category requiring approval
2. Go to Approvals module
3. Review pending pass
4. Approve the pass
5. Verify visitor notification
6. Verify email sent

**Scenario 3: Walk-In Visitor**
1. Go to Check-In
2. Create new walk-in entry
3. Fill all required details
4. Generate pass
5. Verify visitor checked in
6. Checkout visitor
7. Check system records

---

## Part 7: Troubleshooting

### PHP Mailer Issues

**Error: "Composer autoload not found"**
- Solution: Run `composer require phpmailer/phpmailer` in project root

**Error: "SMTP connect() failed"**
- Check SMTP credentials in `config/mailer.php`
- Verify port 587 is accessible
- Check Office 365 account settings
- Ensure TLS is enabled

**Error: "SMTP AUTH failed"**
- Verify email and password
- Check if 2-factor authentication is enabled
- Use app password if required by your organization

### Checkout Issues

**Checkout shows success but no confirmation**
- Check browser JavaScript console for errors
- Verify checkout_records table exists
- Check database error logs

**Checkout data not appearing**
- Run the migrations if not done
- Verify database connection
- Check user permissions

### Approval Issues

**Approval module not showing**
- Check user permissions
- Verify approvals table exists
- Ensure visits have category_id set

---

## Part 8: Configuration Options

### Email Settings

Edit `config/mailer.php`:

```php
define('MAIL_HOST', 'smtp.office365.com');          // SMTP server
define('MAIL_PORT', 587);                           // SMTP port
define('MAIL_SECURE', 'tls');                       // Security type
define('MAIL_DEBUG', false);                        // Set to true for debugging
```

### Notification Types

New notification types available:
- `visitor_checkout` - Sent to host when visitor checks out
- `visitor_arrival` - When visitor arrives (existing)
- `approval_request` - For pass approvals
- `meeting_reminder` - Meeting notifications

---

## Part 9: Database Schema Summary

### New Tables

**checkout_records**
- Detailed record of every checkout
- Includes duration, badge return, ratings
- Audit trail with timestamps

**email_logs**
- Track all emails sent
- Log delivery status and errors
- Used for troubleshooting

### Modified Tables

**visitor_passes**
- Added `approval_status` field (pending/approved/rejected/cancelled)
- Added `approved_by` and `approved_at` fields
- Added `approver_notes` field

**visits**
- Added `duration_minutes` field (calculated at checkout)

**notifications**
- Added new type: `visitor_checkout`

---

## Part 10: Security Notes

1. **Never commit passwords** to version control
2. For production, use **environment variables**:
   ```php
   $mailPassword = getenv('MAIL_PASSWORD');
   ```
3. Restrict file permissions on `config/mailer.php`
4. Regularly rotate Office 365 passwords
5. Monitor email logs for suspicious activity
6. Ensure HTTPS is enabled in production

---

## Support & Maintenance

### Regular Tasks

- **Daily**: Check overdue visitors (automated reminders send)
- **Weekly**: Review rejection logs in approvals
- **Monthly**: Archive old checkout records
- **Quarterly**: Update email templates if needed

### Logs to Monitor

- `email_logs` table - Email delivery status
- `activity_logs` table - All user actions
- `checkout_records` table - Visitor durations
- `approvals` table - Pass approval history

---

## API/Integration Points

The following functions are now available for integration:

```php
// Send email
sendEmail($to, $subject, $htmlBody, $textBody, $attachments);

// Send bulk emails
sendEmailBulk($recipients, $subject, $htmlBody, $textBody, $attachments);

// Get email template
getEmailTemplate($templateName, $data);

// Log activity (existing but enhanced)
logActivity($action, $details, $userId);

// Send notification (existing but enhanced)
sendNotification($userId, $type, $title, $message, $link);
```

---

## Version History

- **v2.0.0** - Complete system overhaul
  - ✓ Checkout functionality enhanced
  - ✓ PHP Mailer integrated
  - ✓ Approval workflow added
  - ✓ Database inconsistencies fixed
  - ✓ Email templates added
  - ✓ Comprehensive audit trails

---

## Next Steps

1. Apply all database migrations
2. Install PHP Mailer via Composer
3. Configure mailer.php with your credentials
4. Test the mailer configuration
5. Run through test scenarios
6. Train staff on new approval workflow
7. Monitor system performance
8. Collect feedback for continuous improvement

---

For questions or issues, refer to the troubleshooting section or contact your system administrator.
