# Visitors & Visits Table Schema Documentation

## Overview
Comprehensive database schema for the VMS checkin system supporting multiple visit scenarios.

---

## VISITORS TABLE

### Purpose
Stores information about all individuals visiting the company facility.

### Columns

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| **id** | INT | NO | AUTO_INCREMENT | Primary key |
| **visitor_uid** | VARCHAR(30) | NO | UNIQUE | Unique visitor identifier (e.g., VIS-20250525001) |
| **first_name** | VARCHAR(50) | NO | - | Visitor's first name |
| **last_name** | VARCHAR(50) | NO | - | Visitor's last name |
| **email** | VARCHAR(100) | YES | NULL | Email address |
| **phone** | VARCHAR(20) | YES | NULL | Phone number |
| **company** | VARCHAR(100) | YES | NULL | Organization/Company |
| **designation** | VARCHAR(100) | YES | NULL | Job title/Role |
| **id_type** | ENUM | YES | national_id | ID document type: passport, national_id, drivers_license, work_permit, other |
| **id_number** | VARCHAR(50) | YES | NULL | ID/Passport number |
| **id_expiry** | DATE | YES | NULL | ID expiration date |
| **nationality** | VARCHAR(50) | YES | NULL | Visitor's nationality |
| **photo** | VARCHAR(255) | YES | NULL | Path to visitor photo |
| **id_document_front** | VARCHAR(255) | YES | NULL | Path to ID front scan |
| **id_document_back** | VARCHAR(255) | YES | NULL | Path to ID back scan |
| **notes** | TEXT | YES | NULL | Additional notes about visitor |
| **frequent_visitor** | BOOLEAN | NO | FALSE | Flag for frequent visitors |
| **safety_induction_completed** | BOOLEAN | NO | FALSE | Safety training completed flag |
| **safety_induction_date** | TIMESTAMP | YES | NULL | When safety induction was completed |
| **nda_signed** | BOOLEAN | NO | FALSE | NDA signed flag |
| **nda_signed_date** | TIMESTAMP | YES | NULL | When NDA was signed |
| **created_at** | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| **updated_at** | TIMESTAMP | NO | CURRENT | Record last update timestamp |
| **created_by** | INT | YES | NULL | User ID who created this record |

### Indexes
- `idx_email` - For quick visitor lookup by email
- `idx_phone` - For quick visitor lookup by phone
- `idx_id_number` - For validation against ID numbers
- `idx_company` - For grouping by organization
- `idx_visitor_uid` - For quick lookup by visitor UID

### Example Insert
```sql
INSERT INTO visitors (
    visitor_uid, first_name, last_name, email, phone, company,
    id_type, id_number, created_by
) VALUES (
    'VIS-20250525001', 'John', 'Doe', 'john@example.com',
    '+254712345678', 'ABC Corp', 'national_id', '12345678', 1
);
```

---

## VISITS TABLE

### Purpose
Tracks each visit instance, including check-in/check-out times, purposes, and acknowledgments.

### Columns

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| **id** | INT | NO | AUTO_INCREMENT | Primary key |
| **visit_uid** | VARCHAR(30) | NO | UNIQUE | Unique visit identifier (e.g., VISIT-20250525001) |
| **visitor_id** | INT | NO | FK | Foreign key to visitors table |
| **meeting_id** | INT | YES | FK | Foreign key to meetings table (if visit is for a meeting) |
| **category_id** | INT | NO | FK | Foreign key to visitor_categories table |
| **host_user_id** | INT | NO | FK | Employee being visited (foreign key to users) |
| **department_id** | INT | YES | FK | Department being visited |
| **visit_date** | DATE | NO | - | Date of visit |
| **visit_status** | ENUM | NO | pre_registered | Status: pre_registered, approved, checked_in, checked_out, cancelled, overdue, no_show |
| **scheduled_arrival_time** | TIME | YES | NULL | Expected arrival time |
| **scheduled_departure_time** | TIME | YES | NULL | Expected departure time |
| **actual_check_in** | TIMESTAMP | YES | NULL | Actual check-in timestamp |
| **actual_check_out** | TIMESTAMP | YES | NULL | Actual check-out timestamp |
| **badge_number** | VARCHAR(30) | YES | NULL | Visitor badge number |
| **number_plate** | VARCHAR(50) | YES | NULL | Vehicle registration number |
| **people_count** | INT | NO | 1 | Number of people in group |
| **visit_location_type** | ENUM | NO | office | Location type: office, retail, delivery, meeting, audit, training, maintenance |
| **badge_printed** | BOOLEAN | NO | FALSE | Badge printed flag |
| **badge_printed_at** | TIMESTAMP | YES | NULL | When badge was printed |
| **badge_returned** | BOOLEAN | NO | FALSE | Badge returned flag |
| **badge_returned_at** | TIMESTAMP | YES | NULL | When badge was returned |
| **badge_returned_to** | INT | YES | NULL | User ID who received badge |
| **purpose** | VARCHAR(255) | YES | NULL | Visit purpose (Meeting, Delivery, Audit, Training, Maintenance, Material Delivery, Other) |
| **actual_purpose** | TEXT | YES | NULL | Actual purpose if different from scheduled |
| **safety_clearance_level** | INT | NO | 1 | Safety clearance level (1-5) |
| **safety_induction_acknowledged** | BOOLEAN | NO | FALSE | Visitor acknowledged safety rules |
| **safety_induction_at** | TIMESTAMP | YES | NULL | When safety induction was done |
| **nda_acknowledged** | BOOLEAN | NO | FALSE | NDA acknowledged flag |
| **nda_acknowledged_at** | TIMESTAMP | YES | NULL | When NDA was acknowledged |
| **terms_acknowledged** | BOOLEAN | NO | FALSE | Terms acknowledged flag |
| **terms_acknowledged_at** | TIMESTAMP | YES | NULL | When terms were acknowledged |
| **photo_taken** | VARCHAR(255) | YES | NULL | Path to check-in photo |
| **signature** | VARCHAR(255) | YES | NULL | Path to signature file |
| **notes** | TEXT | YES | NULL | Visit notes |
| **security_notes** | TEXT | YES | NULL | Security officer notes |
| **approved_by** | INT | YES | FK | User who approved visit |
| **approved_at** | TIMESTAMP | YES | NULL | When visit was approved |
| **checked_in_by** | INT | YES | FK | User who processed check-in |
| **checked_out_by** | INT | YES | FK | User who processed check-out |
| **visit_rating** | INT | YES | NULL | Visit rating (1-5) |
| **visit_feedback** | TEXT | YES | NULL | Feedback about visit |
| **created_at** | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| **created_by** | INT | YES | NULL | User ID who created visit record |
| **updated_at** | TIMESTAMP | NO | CURRENT | Record last update timestamp |
| **updated_by** | INT | YES | NULL | User ID who last updated record |

### Foreign Keys
- `visitor_id` → `visitors(id)` ON DELETE CASCADE
- `meeting_id` → `meetings(id)` ON DELETE SET NULL
- `category_id` → `visitor_categories(id)`
- `host_user_id` → `users(id)`
- `department_id` → `departments(id)`
- `approved_by` → `users(id)` ON DELETE SET NULL
- `checked_in_by` → `users(id)` ON DELETE SET NULL
- `checked_out_by` → `users(id)` ON DELETE SET NULL

### Indexes
- `idx_visit_date` - Query visits by date
- `idx_visitor_id` - Query visits by visitor
- `idx_status` - Query visits by status
- `idx_badge_number` - Badge lookup
- `idx_visit_uid` - Quick visit lookup
- `idx_host_user_id` - Visits for specific employee
- `idx_department_id` - Visits to specific department

### Example Insert (Walk-in Visitor)
```sql
INSERT INTO visits (
    visit_uid, visitor_id, category_id, host_user_id, department_id,
    visit_date, visit_status, actual_check_in, badge_number,
    number_plate, people_count, visit_location_type, purpose,
    safety_induction_acknowledged, nda_acknowledged, terms_acknowledged,
    checked_in_by, created_by
) VALUES (
    'VISIT-20250525001', 42, 1, 5, 3,
    CURDATE(), 'checked_in', NOW(), 'BADGE-001',
    'ABC-1234', 1, 'office', 'Meeting',
    1, 1, 1,
    1, 1
);
```

---

## Visit Purpose Options

The `purpose` column supports these predefined values:
- **Meeting** - Business meeting with employee/department
- **Delivery** - Goods/package delivery
- **Audit** - Internal or external audit
- **Training** - Training session/workshop
- **Maintenance** - Equipment or facility maintenance
- **Material Delivery** - Raw materials or supplies delivery
- **Other** - Any other purpose

---

## Visit Location Type Options

The `visit_location_type` ENUM supports:
- **office** - Office division/headquarters
- **retail** - Retail store/showroom
- **delivery** - Delivery at any location
- **meeting** - Meeting room access
- **audit** - Audit specific area
- **training** - Training facility
- **maintenance** - Maintenance area

---

## Checkin Workflow Example

### Walk-in Visitor Checkin Flow:

1. **Visitor Info Collected:**
   - First Name, Last Name, Phone, ID/Passport, Email, Company, Number Plate, People Count
   - Visit Location Type (Office/Retail), Purpose of Visit

2. **Host & Department Selected:**
   - Department, Person to Visit (host_user_id)

3. **Acknowledgments Accepted:**
   - Safety induction, NDA, Terms & Conditions

4. **Database Operations:**
   ```sql
   -- Step 1: Check if visitor exists (by email)
   SELECT id FROM visitors WHERE email = ?
   
   -- Step 2: If not exists, create visitor
   INSERT INTO visitors (visitor_uid, first_name, last_name, email, phone, ...)
   
   -- Step 3: Create visit record
   INSERT INTO visits (
       visit_uid, visitor_id, category_id, host_user_id, department_id,
       visit_date, visit_status, actual_check_in, checked_in_by,
       number_plate, people_count, visit_location_type, purpose,
       safety_induction_acknowledged, nda_acknowledged, terms_acknowledged
   ) VALUES (...)
   
   -- Step 4: Generate visitor pass
   INSERT INTO visitor_passes (visit_id, pass_number, qr_code, ...)
   ```

---

## Removed Columns

The following columns have been **removed** from the schema per requirements:
- `emergency_contact_name` - No longer needed
- `emergency_contact_phone` - No longer needed
- `emergency_contact_relation` - No longer needed

---

## Notes

1. **Character Set**: All tables use UTF8MB4 for proper support of international characters
2. **Timestamps**: All timestamp columns are set to update automatically
3. **Audit Trail**: `created_by`, `updated_by`, `created_at`, `updated_at` track changes
4. **Cascade Delete**: Deleting a visitor cascades to their visits for data integrity
5. **Soft References**: Set NULL on delete for optional foreign keys to maintain records
