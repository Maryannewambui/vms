# PipeVMS - Visitor Management System

**Enterprise Visitor Management System for Pipe Manufacturing Company**

A comprehensive, security-focused visitor management solution designed for industrial and manufacturing environments.

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [System Architecture](#system-architecture)
4. [Installation Guide](#installation-guide)
5. [User Roles & Permissions](#user-roles--permissions)
6. [Workflow Process](#workflow-process)
7. [File Structure](#file-structure)
8. [Database Schema](#database-schema)
9. [API Endpoints](#api-endpoints)
10. [Security Features](#security-features)
11. [Configuration](#configuration)
12. [Troubleshooting](#troubleshooting)

---

## Overview

PipeVMS is a complete visitor management system tailored for pipe manufacturing and industrial environments. It handles different types of visitors including retail customers, contractors, interview candidates, suppliers, inspectors, and maintenance personnel.

### Key Objectives
- Pre-schedule visitors and meetings
- Check visitors in and out with badge generation
- Track visitor movement and visit history
- Improve security and accountability
- Maintain digital records and approvals
- Support industrial/manufacturing workflows

---

## Features

### Core Modules

| Module | Description |
|---------|-------------|
| **Dashboard** | Real-time statistics, charts, visitor status, activity feed |
| **Meeting Scheduler** | Pre-register visitors, schedule meetings, generate QR codes |
| **Check-In System** | Walk-in/pre-registered check-in, photo capture, badge printing |
| **Check-Out System** | Visitor checkout, duration tracking, badge return |
| **Visitor Passes** | Generate printable badges with QR codes and safety levels |
| **Records** | Full visitor history with filtering and CSV export |
| **Security** | Blacklist management, incident reporting, emergency evacuation |
| **Admin Panel** | User management, departments, system settings |
| **Reports** | Analytics, trends, department traffic, peak hours |

### Security Features
- Role-based access control (7 roles)
- CSRF protection on all forms
- SQL injection prevention (PDO prepared statements)
- Session management with timeout
- Password hashing (bcrypt)
- Login attempt limiting with account lockout
- Audit trail logging
- Blacklist checking

### Industrial-Specific Features
- Safety clearance levels (Low/Medium/High/Critical)
- NDA tracking
- Safety induction acknowledgment
- Contractor permit verification
- Equipment/tool declaration
- Vehicle access logging
- Restricted area permissions
- Emergency evacuation management

---

## System Architecture

### Technology Stack
| Layer | Technology |
|-------|------------|
| Backend | PHP 7.4+ (Procedural/Modular) |
| Database | MySQL 5.7+ |
| Frontend | HTML5, Tailwind CSS |
| JavaScript | Vanilla JS, Chart.js, QRCode.js |
| Server | Apache (XAMPP compatible) |

### Architecture Diagram
```
┌─────────────────────────────────────────────────────────────┐
│                        BROWSER                               │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐        │
│  │  HTML   │  │ Tailwind│  │   JS    │  │ Chart.js│        │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘        │
└───────┼────────────┼────────────┼────────────┼──────────────┘
        │            │            │            │
        ▼            ▼            ▼            ▼
┌─────────────────────────────────────────────────────────────┐
│                      APACHE SERVER                          │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    PHP APPLICATION                    │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐           │   │
│  │  │  Config  │  │ Includes │  │ Modules  │           │   │
│  │  └────┬─────┘  └────┬─────┘  └────┬─────┘           │   │
│  │       │             │             │                  │   │
│  │       └─────────────┼─────────────┘                  │   │
│  │                     ▼                                │   │
│  │              ┌──────────────┐                        │   │
│  │              │   Database   │                        │   │
│  │              │    Class     │                        │   │
│  │              └──────┬───────┘                        │   │
│  └─────────────────────┼───────────────────────────────┘   │
└────────────────────────┼───────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                      MySQL DATABASE                         │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐       │
│  │  users  │  │ visits  │  │ visitors│  │  logs   │       │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘       │
└─────────────────────────────────────────────────────────────┘
```

---

## Installation Guide

### Prerequisites
- XAMPP, WAMP, or LAMP stack
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled

### Step 1: Copy Files
Copy the project folder to your web server's document root:
```bash
# Windows XAMPP
C:\xampp\htdocs\vms\

# Linux
/var/www/html/vms/

# Mac
/Applications/XAMPP/htdocs/vms/
```

### Step 2: Create Database
1. Start Apache and MySQL in XAMPP
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Click **New** to create database: `vms_pipe_manufacturing`
4. Select the database, go to **Import** tab
5. Import `database/schema.sql` first (creates all tables)
6. Import `database/seed.sql` second (adds sample data)

### Step 3: Configure Application
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vms_pipe_manufacturing');
define('DB_USER', 'root');     // Your MySQL username
define('DB_PASS', '');         // Your MySQL password
```

Edit `config/config.php`:
```php
define('APP_URL', 'http://localhost/vms');  // Your base URL
```

### Step 4: Set Folder Permissions
Ensure these folders are writable:
```bash
chmod 755 uploads/
chmod 755 uploads/photos/
chmod 755 uploads/documents/
chmod 755 uploads/signatures/
```

### Step 5: Access Application
Open browser: `http://localhost/vms/`

### Default Login Credentials
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@pipevms.com | password |
| Receptionist | reception@pipevms.com | password |
| Security Guard | security@pipevms.com | password |
| HR Officer | hr@pipevms.com | password |
| Maintenance Supervisor | maint@pipevms.com | password |
| Department Manager | prodmanager@pipevms.com | password |
| Employee | eng1@pipevms.com | password |

---

## User Roles & Permissions

### Role Hierarchy
```
┌─────────────────┐
│  Super Admin (1) │ ◄─── Full system access
└────────┬────────┘
         │
    ┌────┴────┬─────────────┬─────────────┐
    │         │             │             │
┌───▼───┐ ┌───▼───┐    ┌────▼────┐  ┌─────▼─────┐
│Recep- │ │Security│    │  HR     │  │Maintenance│
│tionist│ │  (3)   │    │ Officer │  │Supervisor │
│  (2)  │ └────────┘    │  (4)    │  │   (5)     │
└───────┘               └─────────┘  └───────────┘
         │
    ┌────┴────┐
    │         │
┌───▼───────┐ │
│Department │ │
│  Manager  │ │
│   (6)     │ │
└───────────┘ │
              │
        ┌─────▼─────┐
        │  Employee  │
        │    (7)     │
        └───────────┘
```

### Permission Matrix
| Permission | Super Admin | Reception | Security | HR | Maint. | Mgr | Employee |
|------------|:-----------:|:---------:|:--------:|:--:|:------:|:---:|:--------:|
| View Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create Visitor | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Check-In Visitor | ✓ | ✓ | ✓ | - | ✓ | - | - |
| Check-Out Visitor | ✓ | ✓ | ✓ | - | ✓ | - | - |
| Generate Pass | ✓ | ✓ | ✓ | - | - | - | - |
| Schedule Meeting | ✓ | ✓ | - | ✓ | ✓ | ✓ | ✓ |
| Approve Meeting | ✓ | - | - | ✓ | - | ✓ | - |
| View Records | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | - |
| Export Records | ✓ | ✓ | - | ✓ | - | ✓ | - |
| Manage Blacklist | ✓ | - | ✓ | - | - | - | - |
| Create Incident | ✓ | - | ✓ | ✓ | - | - | - |
| Manage Users | ✓ | - | - | - | - | - | - |
| Manage Departments | ✓ | - | - | - | - | - | - |
| Manage Settings | ✓ | - | - | - | - | - | - |
| View Audit Logs | ✓ | - | - | - | - | - | - |

---

## Workflow Process

### 1. Pre-Registration Flow
```
┌─────────────────────────────────────────────────────────────┐
│                    PRE-REGISTRATION                         │
└─────────────────────────────────────────────────────────────┘

Employee                    System                    Host
    │                          │                        │
    │  1. Schedule Meeting      │                        │
    │ ─────────────────────►    │                        │
    │                          │                        │
    │  2. Enter Visitor Details │                        │
    │ ─────────────────────►    │                        │
    │                          │                        │
    │                          │  3. Generate           │
    │                          │     QR Code            │
    │                          │                        │
    │                          │  4. Send Notification  │
    │                          │ ─────────────────────► │
    │                          │                        │
    │                          │  5. Meeting Appears    │
    │                          │     in Dashboard       │
    │                          │                        │
```

### 2. Check-In Flow
```
┌─────────────────────────────────────────────────────────────┐
│                      CHECK-IN PROCESS                       │
└─────────────────────────────────────────────────────────────┘

Visitor      Receptionist            System           Host
    │              │                     │               │
    │  1. Arrive   │                     │               │
    │ ──────────►  │                     │               │
    │              │                     │               │
    │              │  2. Search/Scan     │               │
    │              │ ─────────────────► │               │
    │              │                     │               │
    │              │  3. Verify Details  │               │
    │              │ ─────────────────► │               │
    │              │                     │               │
    │  4. Capture Photo/Signature        │               │
    │ ──────────►  │                     │               │
    │              │                     │               │
    │              │  5. Check Blacklist │               │
    │              │ ─────────────────► │               │
    │              │                     │               │
    │              │  6. Generate Badge  │               │
    │              │ ─────────────────► │               │
    │              │                     │               │
    │              │                     │  7. Notify   │
    │              │                     │ ────────────► │
    │              │                     │               │
    │  8. Print Badge                    │               │
    │ ──────────►  │                     │               │
    │              │                     │               │
    │  9. Enter Facility                 │               │
    │ ─────────────────────────────────────────────────► │
```

### 3. Check-Out Flow
```
┌─────────────────────────────────────────────────────────────┐
│                     CHECK-OUT PROCESS                      │
└─────────────────────────────────────────────────────────────┘

Visitor       Security              System           Database
    │             │                    │                 │
    │  1. Exit    │                    │                 │
    │ ─────────►  │                    │                 │
    │             │                    │                 │
    │             │  2. Scan Badge/ID  │                 │
    │             │ ────────────────► │                 │
    │             │                    │                 │
    │             │  3. Record Time    │                 │
    │             │ ────────────────► │                 │
    │             │                    │                 │
    │             │                    │  4. Update     │
    │             │                    │ ─────────────► │
    │             │                    │                 │
    │  5. Return Badge                  │                 │
    │ ─────────►  │                    │                 │
    │             │                    │                 │
    │             │  6. Confirm Return │                 │
    │             │ ────────────────► │                 │
    │             │                    │                 │
    │             │  7. Collect Feedback │               │
    │             │ ────────────────► │                 │
    │             │                    │                 │
    │             │  8. Calculate Duration              │
    │             │ ────────────────► │                 │
    │             │                    │                 │
```

### 4. Emergency Evacuation Flow
```
┌─────────────────────────────────────────────────────────────┐
│              EMERGENCY EVACUATION                           │
└─────────────────────────────────────────────────────────────┘

Safety Officer         System              Security
      │                   │                    │
      │  1. Initiate      │                    │
      │ ───────────────► │                    │
      │                   │                    │
      │                   │  2. Alert All     │
      │                   │ ─────────────────► │
      │                   │                    │
      │                   │  3. Generate List │
      │                   │ ─────────────────► │
      │                   │                    │
      │                   │  4. Track Status  │
      │                   │ ─────────────────► │
      │                   │                    │
      │                   │  5. Mark Safe/Missing
      │                   │ ◄───────────────── │
      │                   │                    │
      │  6. View Status   │                    │
      │ ◄─────────────── │                    │
      │                   │                    │
      │  7. Declare All Clear                 │
      │ ───────────────► │                    │
      │                   │                    │
```

---

## File Structure

```
vms/
│
├── index.php                    # Main dashboard entry point
├── login.php                    # User login page
├── logout.php                   # Session termination
├── .htaccess                    # Apache security rules
├── .env                         # Environment variables (create this)
├── 403.html                     # Forbidden error page
├── 404.html                     # Not found error page
│
├── config/
│   ├── config.php               # Main app config, constants, helpers
│   ├── database.php             # Database connection class
│   └── constants.php            # Permission constants, APP_KEY
│
├── includes/
│   ├── functions.php            # Core utility functions
│   └── auth.php                 # Authentication functions
│
├── templates/
│   ├── header.php               # HTML head, CSS, JS includes
│   ├── footer.php               # Scripts, closing tags
│   ├── sidebar.php              # Navigation sidebar
│   └── topnav.php               # Top navigation bar
│
├── modules/
│   ├── checkin/
│   │   ├── index.php            # Check-in form & processing
│   │   └── badge.php            # Badge display & printing
│   │
│   ├── checkout/
│   │   └── index.php            # Check-out processing
│   │
│   ├── meetings/
│   │   └── schedule.php         # Meeting/visitor pre-registration
│   │
│   ├── records/
│   │   └── index.php            # Visitor records with filtering
│   │
│   ├── security/
│   │   ├── blacklist.php        # Blacklist management
│   │   ├── incidents.php        # Incident reporting
│   │   └── evacuation.php       # Emergency evacuation
│   │
│   ├── admin/
│   │   ├── users.php            # User management
│   │   ├── departments.php     # Department management
│   │   ├── settings.php         # System settings
│   │   └── logs.php             # Activity logs
│   │
│   ├── reports/
│   │   └── index.php            # Analytics dashboard
│   │
│   └── api/
│       ├── notifications.php    # Notifications API
│       └── search.php           # Global search API
│
├── database/
│   ├── schema.sql               # Database schema (all tables)
│   └── seed.sql                 # Sample data
│
├── uploads/                      # User uploads (create dirs)
│   ├── photos/
│   ├── documents/
│   └── signatures/
│
└── assets/                       # Static assets
    ├── css/
    ├── js/
    └── images/
```

### File Descriptions

#### Configuration Files

| File | Purpose |
|------|---------|
| `config/config.php` | Main application configuration, session setup, constants, helper functions. Sets up database connection and loads auth system. |
| `config/database.php` | Database class with singleton pattern. Contains connection settings and PDO setup with error handling. |
| `config/constants.php` | Application key for hashing, permission constants. |

#### Include Files

| File | Purpose | Key Functions |
|------|---------|--------------|
| `includes/functions.php` | Core utility functions | `sanitize()`, `csrfField()`, `verifyCSRF()`, `formatDate()`, `logActivity()`, `sendNotification()`, `uploadFile()`, `generateUID()`, `isBlacklisted()`, `exportCSV()` |
| `includes/auth.php` | Authentication system | `loginUser()`, `logoutUser()`, `isLoggedIn()`, `requireLogin()`, `createUser()`, `updateUser()`, `changePassword()`, `hasPermission()` |

#### Template Files

| File | Purpose |
|------|---------|
| `templates/header.php` | HTML document head, Tailwind config, fonts, CSS styles, loading overlay, toast container |
| `templates/footer.php` | JavaScript utilities (VMS object), form validation, session timeout timer |
| `templates/sidebar.php` | Left sidebar navigation with role-based menu filtering, user info display |
| `templates/topnav.php` | Top navigation with global search, notifications dropdown, profile menu, current time |

#### Core Pages

| Page | Description |
|------|-------------|
| `index.php` | Main dashboard with widgets: visitor stats, activity feed, charts, currently onsite list, expected visitors |
| `login.php` | Login form with remember me, demo credentials display, responsive two-column layout |
| `logout.php` | Destroys session and redirects to login |

#### Module: Check-In (`modules/checkin/`)

| File | Purpose |
|------|---------|
| `index.php` | Main check-in interface. Handles pre-registered and walk-in visitors, form validation, photo upload, blacklist check, pass generation |
| `badge.php` | Displays printable visitor badge with QR code, visitor info, safety level, host details |

#### Module: Check-Out (`modules/checkout/`)

| File | Purpose |
|------|---------|
| `index.php` | Lists checked-in visitors, processes checkout, collects badge, records feedback, calculates duration |

#### Module: Meetings (`modules/meetings/`)

| File | Purpose |
|------|---------|
| `schedule.php` | Pre-registration form for scheduling meetings. Multi-visitor support, vehicle/equipment declaration, QR code generation |

#### Module: Records (`modules/records/`)

| File | Purpose |
|------|---------|
| `index.php` | Full visitor history with date range, department, status filters. Pagination, CSV export functionality |

#### Module: Security (`modules/security/`)

| File | Purpose |
|------|---------|
| `blacklist.php` | Manage blacklisted visitors. Add/remove entries, severity levels, search functionality |
| `incidents.php` | Report and manage security/safety incidents. Status tracking, resolution workflow |
| `evacuation.php` | Emergency evacuation management. Start evacuation, mark visitors safe/missing, declare all clear |

#### Module: Admin (`modules/admin/`)

| File | Purpose |
|------|---------|
| `users.php` | Create/edit users, assign roles, reset passwords, activate/deactivate accounts |
| `departments.php` | Manage departments, assign managers, set contact details |
| `settings.php` | System configuration: company info, working hours, badge settings, notifications, terms |
| `logs.php` | View activity audit trail with user, action, timestamp, IP filters |

#### Module: Reports (`modules/reports/`)

| File | Purpose |
|------|---------|
| `index.php` | Analytics dashboard with daily visitor charts, peak hours, visitor type distribution, department traffic |

#### Module: API (`modules/api/`)

| File | Endpoint | Methods |
|------|----------|---------|
| `notifications.php` | `?action=list\|mark_read\|mark_all_read\|count` | GET/POST |
| `search.php` | `?q=query` | GET |

---

## Database Schema

### Entity Relationship Diagram

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│    roles     │       │    users     │       │ departments  │
│──────────────│       │──────────────│       │──────────────│
│ id (PK)      │◄──┐   │ id (PK)      │   ┌──►│ id (PK)      │
│ name         │   │   │ first_name   │   │   │ name         │
│ description  │   │   │ last_name    │   │   │ manager_id   │──┐
└──────────────┘   │   │ email        │   │   └──────────────┘  │
                   │   │ password     │   │                     │
                   └───│ role_id (FK) │   │                     │
                       │ department_id│───┘                     │
                       └──────────────┘                         │
                                          │                     │
┌──────────────┐       ┌──────────────┐  │                     │
│visitor_cats  │       │   visitors   │  │                     │
│──────────────│       │──────────────│  │                     │
│ id (PK)      │       │ id (PK)      │  │                     │
│ name         │       │ visitor_uid  │  │                     │
│ color_code   │       │ first_name   │  │                     │
│ requires_nda│       │ last_name    │  │                     │
│ requires_safe│      │ email        │  │                     │
└──────────────┘       │ id_number    │  │                     │
       │               └──────────────┘  │                     │
       │                      │          │                     │
       │                      │          │                     │
       ▼                      ▼          ▼                     │
┌──────────────────────────────────────────────────────────────│───┐
│                          visits                               │   │
│──────────────────────────────────────────────────────────────│───│
│ id (PK), visit_uid, visitor_id (FK), category_id (FK)       │   │
│ host_user_id (FK), department_id (FK)                       │   │
│ visit_date, visit_status, actual_check_in, actual_check_out│   │
│ badge_number, safety_clearance_level, purpose               │   │
└──────────────────────────────────────────────────────────────│───┘
       │                     │          │                     │
       │                     │          │                     │
       ▼                     ▼          ▼                     ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│visitor_passes│  │   meetings   │  │activity_logs │  │  blacklist   │
│──────────────│  │──────────────│  │──────────────│  │──────────────│
│ id (PK)      │  │ id (PK)      │  │ id (PK)      │  │ id (PK)      │
│ visit_id(FK) │  │ meeting_uid  │  │ user_id (FK) │  │ visitor_id   │
│ pass_number  │  │ host_user_id │  │ action       │  │ reason       │
│ qr_code      │  │ department_id│  │ details      │  │ severity     │
│ safety_level │  │ meeting_date │  │ ip_address   │  │ status       │
└──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘
```

### Table Descriptions

| Table | Records | Purpose |
|-------|---------|---------|
| `roles` | 7 | User role definitions |
| `departments` | 10 | Company departments |
| `users` | 10+ | System users |
| `visitor_categories` | 9 | Visitor type classifications |
| `visitors` | ∞ | Visitor master records |
| `visits` | ∞ | Individual visit records |
| `meetings` | ∞ | Scheduled meetings |
| `meeting_visitors` | ∞ | Meeting-visitor junction |
| `visitor_passes` | ∞ | Generated badges/passes |
| `blacklist` | 2+ | Denied visitors |
| `notifications` | ∞ | User notifications |
| `activity_logs` | ∞ | Audit trail |
| `incident_reports` | ∞ | Security incidents |
| `emergency_evacuations` | ∞ | Evacuation records |
| `evacuation_checks` | ∞ | Evacuation status per visitor |
| `vehicles` | ∞ | Vehicle access records |
| `equipment_declarations` | ∞ | Tools/equipment brought |
| `visitor_documents` | ∞ | Uploaded documents |
| `approvals` | ∞ | Approval requests |
| `restricted_areas` | 8 | Access-controlled zones |
| `visit_area_access` | ∞ | Area access logs |
| `settings` | 25+ | System configuration |
| `contractor_details` | ∞ | Contractor-specific data |
| `emergency_contacts` | ∞ | Visitor emergency contacts |
| `gate_logs` | ∞ | Gate entry/exit logs |

---

## API Endpoints

### Notifications API (`/modules/api/notifications.php`)

| Action | Method | Description | Response |
|--------|--------|-------------|----------|
| `list` | GET | Get user notifications | `{success, notifications[]}` |
| `mark_read` | POST | Mark single notification read | `{success}` |
| `mark_all_read` | POST | Mark all notifications read | `{success}` |
| `count` | GET | Get unread count | `{success, count}` |

**Example:**
```javascript
// Get notifications
fetch('/modules/api/notifications.php?action=list')
  .then(res => res.json())
  .then(data => console.log(data.notifications));
```

### Search API (`/modules/api/search.php`)

| Parameter | Method | Description |
|-----------|--------|-------------|
| `q` | GET | Search query (min 2 chars) |

**Response:**
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "type": "Visitor",
        "title": "John Smith",
        "subtitle": "ABC Company",
        "link": "modules/records/view.php?visitor_id=1"
      }
    ]
  }
}
```

---

## Security Features

### 1. Authentication Security
```php
// Password hashing
$password = password_hash($rawPassword, PASSWORD_DEFAULT);

// Verification
if (password_verify($inputPassword, $storedHash)) {
    // Success
}

// Account lockout after 5 failed attempts
if ($attempts >= 5) {
    $lockedUntil = date('Y-m-d H:i:s', time() + 1800); // 30 min
}
```

### 2. CSRF Protection
```php
// Generate token in form
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . 
           $_SESSION['csrf_token'] . '">';
}

// Verify token
function verifyCSRF() {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed.');
    }
}
```

### 3. SQL Injection Prevention
```php
// Always use prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// Never concatenate
// BAD: "SELECT * FROM users WHERE email = '" . $email . "'"
```

### 4. XSS Prevention
```php
// Sanitize all output
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// In HTML
<?= sanitize($visitor['name']) ?>
```

### 5. Session Security
```php
// Session configuration
session_name('VMS_SESSION');
session_start();
session_regenerate_id(true); // On login

// Session timeout (30 min)
if (time() - $_SESSION['login_time'] > 1800) {
    logoutUser();
}
```

### 6. File Upload Security
```php
// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);

if (!in_array($mimeType, ALLOWED_TYPES)) {
    return ['success' => false, 'error' => 'Invalid file type'];
}

// Generate unique filename
$filename = generateUID() . '.' . $extension;
```

### 7. Access Control
```php
// Require login
requireLogin();

// Require permission
requirePermission('manage_users');

// Check permission in code
if (!hasPermission('export_records')) {
    denyAccess();
}
```

---

## Configuration

### Required Environment Variables (.env)
```env
# Database
DB_HOST=localhost
DB_NAME=vms_pipe_manufacturing
DB_USER=root
DB_PASS=

# Application
APP_URL=http://localhost/vms
APP_KEY=your_random_32_char_key_here
```

### Settings (Stored in Database)

| Setting | Description | Default |
|---------|-------------|---------|
| `company_name` | Company display name | Precision Pipe Manufacturing Co. |
| `company_address` | Physical address | - |
| `work_start_time` | Workday start | 08:00 |
| `work_end_time` | Workday end | 17:00 |
| `badge_expiry_hours` | Badge validity (hours) | 8 |
| `overdue_minutes` | Minutes before overdue | 30 |
| `notify_host_on_arrival` | Notify host on check-in | true |
| `terms_text` | Terms & conditions text | - |

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed
```
Error: SQLSTATE[HY000] [2002] Connection refused
```
**Solution:** 
- Ensure MySQL is running in XAMPP
- Check credentials in `config/database.php`
- Verify database exists

#### 2. 404 Not Found
```
Error: Object not found! The requested URL was not found on this server.
```
**Solution:**
- Check APP_URL matches your actual URL
- Ensure mod_rewrite is enabled
- Verify .htaccess is in root

#### 3. Blank White Page
```
Error: No output, just white screen
```
**Solution:**
- Enable error reporting in php.ini: `display_errors = On`
- Check PHP error logs
- Verify file permissions

#### 4. Session Not Working
```
Error: Keeps redirecting to login
```
**Solution:**
- Ensure session_start() is called
- Check session.save_path is writable
- Clear browser cookies

#### 5. Permission Denied on Uploads
```
Error: move_uploaded_file(): Permission denied
```
**Solution:**
```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/  # Linux
```

#### 6. CSRF Token Failed
```
Error: CSRF token validation failed
```
**Solution:**
- Clear browser session/cookies
- Ensure form includes `<?= csrfField() ?>`
- Verify session is active

### Debug Mode

Enable in `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Requirements

Create `phpinfo.php`:
```php
<?php phpinfo(); ?>
```

Required extensions:
- PDO
- pdo_mysql
- session
- json
- mbstring
- gd (for images)

---

## License

This project is created for internal use at Precision Pipe Manufacturing Company.

---

## Support

For technical support, contact the IT Department.

**Version:** 1.0.0
**Last Updated:** 2024
