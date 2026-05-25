# VISITORS & VISITS TABLE - QUICK REFERENCE

## VISITORS TABLE - 25 Columns

```sql
CREATE TABLE visitors (
    id (PK),                                    -- Auto-increment
    visitor_uid (UNIQUE),                       -- VIS-20250525001
    first_name, last_name,                      -- Required
    email, phone, company, designation,         -- Optional
    id_type (ENUM), id_number, id_expiry,      -- ID info
    nationality, photo,                         -- Additional info
    id_document_front, id_document_back,       -- Document scans
    notes,                                      -- General notes
    frequent_visitor (BOOLEAN),                 -- Flag
    safety_induction_completed (BOOLEAN),      -- Training flag
    safety_induction_date (TIMESTAMP),         -- When trained
    nda_signed (BOOLEAN),                       -- NDA flag
    nda_signed_date (TIMESTAMP),               -- When signed
    created_at (TIMESTAMP),                    -- Auto
    updated_at (TIMESTAMP),                    -- Auto
    created_by (INT FK)                        -- Audit
);
```

### Sample Insert
```sql
INSERT INTO visitors (visitor_uid, first_name, last_name, phone, id_number, created_by)
VALUES ('VIS-001', 'John', 'Doe', '+254712345678', '12345678', 1);
```

---

## VISITS TABLE - 43 Columns

```sql
CREATE TABLE visits (
    id (PK),                                    -- Auto-increment
    visit_uid (UNIQUE),                         -- VISIT-20250525001
    visitor_id (FK),                            -- Link to visitors
    meeting_id (FK nullable),                   -- Link to meetings
    category_id (FK),                           -- Visitor category
    host_user_id (FK),                          -- Employee being visited
    department_id (FK),                         -- Department
    visit_date (DATE),                          -- Visit date
    
    -- STATUS & TIMING
    visit_status (ENUM),                        -- pre_reg/approved/checked_in/checked_out/cancelled/overdue/no_show
    scheduled_arrival_time (TIME),              -- Expected arrival
    scheduled_departure_time (TIME),            -- Expected departure
    actual_check_in (TIMESTAMP),                -- When they checked in
    actual_check_out (TIMESTAMP),               -- When they checked out
    
    -- VISIT INFO
    badge_number (VARCHAR),                     -- Visitor badge
    number_plate (VARCHAR),                     -- Vehicle plate
    people_count (INT),                         -- Number of visitors
    visit_location_type (ENUM),                 -- office/retail/delivery/meeting/audit/training/maintenance
    purpose (VARCHAR),                          -- Visit purpose
    actual_purpose (TEXT),                      -- If different
    
    -- BADGE TRACKING
    badge_printed (BOOLEAN),                    -- Badge printed?
    badge_printed_at (TIMESTAMP),               -- When printed
    badge_returned (BOOLEAN),                   -- Returned?
    badge_returned_at (TIMESTAMP),              -- When returned
    badge_returned_to (INT FK),                 -- Who received it
    
    -- SAFETY & ACKNOWLEDGMENTS
    safety_clearance_level (INT),               -- 1-5
    safety_induction_acknowledged (BOOLEAN),    -- Acknowledged?
    safety_induction_at (TIMESTAMP),            -- When
    nda_acknowledged (BOOLEAN),                 -- NDA agreed?
    nda_acknowledged_at (TIMESTAMP),            -- When
    terms_acknowledged (BOOLEAN),               -- Terms agreed?
    terms_acknowledged_at (TIMESTAMP),          -- When
    
    -- DOCUMENTATION
    photo_taken (VARCHAR),                      -- Check-in photo path
    signature (VARCHAR),                        -- Signature path
    
    -- NOTES & APPROVAL
    notes (TEXT),                               -- General notes
    security_notes (TEXT),                      -- Security notes
    approved_by (INT FK nullable),              -- Who approved
    approved_at (TIMESTAMP),                    -- When approved
    
    -- WHO DID WHAT
    checked_in_by (INT FK nullable),            -- Who checked in
    checked_out_by (INT FK nullable),           -- Who checked out
    
    -- FEEDBACK
    visit_rating (INT),                         -- 1-5 rating
    visit_feedback (TEXT),                      -- Feedback
    
    -- AUDIT
    created_at (TIMESTAMP),                     -- Auto
    created_by (INT FK),                        -- Who created
    updated_at (TIMESTAMP),                     -- Auto
    updated_by (INT FK nullable)                -- Who updated
);
```

### Sample Insert (Walk-in)
```sql
INSERT INTO visits (
    visit_uid, visitor_id, category_id, host_user_id, department_id,
    visit_date, visit_status, actual_check_in, badge_number,
    number_plate, people_count, visit_location_type, purpose,
    safety_induction_acknowledged, nda_acknowledged, terms_acknowledged,
    checked_in_by, created_by
) VALUES (
    'VISIT-001', 42, 1, 5, 3,
    CURDATE(), 'checked_in', NOW(), 'BADGE-001',
    'ABC-1234', 1, 'office', 'Meeting',
    1, 1, 1,
    1, 1
);
```

---

## ENUM VALUES

### id_type
- national_id (default)
- passport
- drivers_license
- work_permit
- other

### visit_status
- pre_registered (default)
- approved
- checked_in
- checked_out
- cancelled
- overdue
- no_show

### visit_location_type
- office (default)
- retail
- delivery
- meeting
- audit
- training
- maintenance

### purpose (VARCHAR, not ENUM)
- Meeting
- Delivery
- Audit
- Training
- Maintenance
- Material Delivery
- Other

---

## KEY RELATIONSHIPS

```
visitors (1) ──── (N) visits
meetings  (1) ──── (N) visits (optional)
visitor_categories (1) ──── (N) visits
users (1-host) ──── (N) visits
users (1-approved_by) ──── (N) visits
users (1-checked_in_by) ──── (N) visits
users (1-checked_out_by) ──── (N) visits
departments (1) ──── (N) visits
```

---

## IMPORTANT NOTES

✅ **NO** `checked_in_at` column - Use `actual_check_in`
✅ **NO** emergency_contact_* columns - Removed per requirements
✅ **NO** photo in form - But `photo_taken` path supported in DB
✅ **YES** Proper audit trail with created_by/updated_by
✅ **YES** All foreign keys properly defined
✅ **YES** UTF8MB4 charset for international support

---

## QUERY EXAMPLES

### Find visitor by email
```sql
SELECT * FROM visitors WHERE email = ?;
```

### Get all visits for a visitor
```sql
SELECT * FROM visits WHERE visitor_id = ? ORDER BY visit_date DESC;
```

### Get today's checked-in visitors
```sql
SELECT v.*, vis.first_name, vis.last_name, u.first_name as host_name
FROM visits v
JOIN visitors vis ON v.visitor_id = vis.id
JOIN users u ON v.host_user_id = u.id
WHERE v.visit_date = CURDATE() AND v.visit_status = 'checked_in'
ORDER BY v.actual_check_in DESC;
```

### Get pending checkouts
```sql
SELECT v.*, vis.first_name, vis.last_name
FROM visits v
JOIN visitors vis ON v.visitor_id = vis.id
WHERE v.visit_date = CURDATE() 
AND v.visit_status = 'checked_in' 
AND v.actual_check_out IS NULL
ORDER BY v.actual_check_in ASC;
```

### Check-in statistics for today
```sql
SELECT 
    COUNT(*) as total_visits,
    SUM(CASE WHEN visit_status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
    SUM(CASE WHEN visit_status = 'checked_out' THEN 1 ELSE 0 END) as checked_out,
    SUM(CASE WHEN visit_status = 'pre_registered' THEN 1 ELSE 0 END) as pending
FROM visits
WHERE visit_date = CURDATE();
```

---

## FILES & LOCATIONS

- **Schema**: `/database/schema.sql` (lines 100-245)
- **Migration**: `/database/migration_fix_checkin.sql`
- **Reference**: `/database/visitors_visits_schema.sql`
- **Docs**: `/database/SCHEMA_DOCUMENTATION.md`
- **Form Code**: `/modules/checkin/index.php`
- **Badge Display**: `/modules/checkin/badge.php`
