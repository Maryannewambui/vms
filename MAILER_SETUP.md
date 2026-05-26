# PHP Mailer Setup Guide for VMS

## Installation Steps

### 1. Install PHPMailer via Composer

Open a terminal/command prompt in your project root (`c:\xampp\htdocs\vms`) and run:

```bash
composer require phpmailer/phpmailer
```

If you don't have Composer installed, download it from: https://getcomposer.org/download/

### 2. Configuration

The configuration files are set up to use **Office 365 / Microsoft 365 Outlook SMTP**.

**Server Details:**
- SMTP Server: `smtp.office365.com`
- SMTP Port: `587`
- Security: TLS (STARTTLS)
- Username: Your Office 365 email address
- Password: Your Office 365 password

**Configuration File:** `config/mailer.php`

### 3. Update Configuration with Your Credentials

Edit `config/mailer.php` and update:
- `MAIL_FROM_EMAIL` - Your email address
- `MAIL_FROM_NAME` - Your company name (DANCO PLASTICS)
- `MAIL_USERNAME` - Your Office 365 email
- `MAIL_PASSWORD` - Your Office 365 password

### 4. Test the Setup

Run the test script:
```bash
php config/mailer-test.php
```

## Security Notes

1. **Never commit passwords** to version control
2. For production, consider using **environment variables**:
   ```php
   $mailUsername = getenv('MAIL_USERNAME');
   $mailPassword = getenv('MAIL_PASSWORD');
   ```

3. Set appropriate SMTP permissions in Office 365:
   - Enable "Less secure app access" if needed
   - Or use OAuth2 authentication for better security

## Integration Points

The following modules will use PHP Mailer:

1. **Meeting Scheduling** (`modules/meetings/schedule.php`)
   - Send meeting invitation to host
   - Send confirmation to visitor
   
2. **Visitor Approval** (`modules/approvals/`)
   - Send approval request to host
   - Send approval notification to visitor

3. **Checkout Reminders** (scheduled tasks)
   - Send overdue checkout reminders

## Email Templates

Email templates are located in: `templates/emails/`

- `meeting-invitation.php` - Meeting invitation
- `approval-request.php` - Approval request
- `checkout-reminder.php` - Checkout reminder

## Troubleshooting

**Error: "SMTP connect() failed"**
- Check SMTP credentials
- Verify port 587 is open
- Ensure TLS is enabled

**Error: "SMTP AUTH failed"**
- Verify username and password
- Check if app password is required (2FA)
- Try "Allow less secure apps" in Office 365 settings

**Error: "Composer not found"**
- Install Composer from https://getcomposer.org
- Add to system PATH

---

After setup, integrate the mailer functions into your modules.
