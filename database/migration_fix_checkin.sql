-- ============================================
-- MIGRATION SCRIPT - UPDATE EXISTING TABLES
-- ============================================
-- Run this script if you already have these tables and need to update them

USE vms_pipe_manufacturing;

-- Step 1: Add missing columns to visitors table
ALTER TABLE visitors
ADD COLUMN IF NOT EXISTS created_by INT AFTER updated_at,
ADD INDEX IF NOT EXISTS idx_visitor_uid (visitor_uid);

-- Step 2: Remove emergency contact columns from visits table
ALTER TABLE visits
DROP COLUMN IF EXISTS emergency_contact_name,
DROP COLUMN IF EXISTS emergency_contact_phone,
DROP COLUMN IF EXISTS emergency_contact_relation;

-- Step 3: Add missing columns to visits table
ALTER TABLE visits
ADD COLUMN IF NOT EXISTS updated_by INT AFTER updated_at;

-- Step 4: Update character set and collation
ALTER TABLE visitors
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE visits
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Step 5: Add proper foreign key constraints if they don't exist
-- Note: Check constraints first with SHOW CREATE TABLE visits;
-- Then add if needed:
ALTER TABLE visits
ADD CONSTRAINT fk_visits_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_visits_checked_in_by FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_visits_checked_out_by FOREIGN KEY (checked_out_by) REFERENCES users(id) ON DELETE SET NULL;

-- Step 6: Add missing indexes
ALTER TABLE visits
ADD INDEX IF NOT EXISTS idx_visit_uid (visit_uid),
ADD INDEX IF NOT EXISTS idx_host_user_id (host_user_id),
ADD INDEX IF NOT EXISTS idx_department_id (department_id);

-- Verify changes
DESCRIBE visitors;
DESCRIBE visits;
