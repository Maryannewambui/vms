-- ============================================
-- CHECKOUT RECORDS TABLE
-- ============================================
-- This table keeps detailed checkout records separate from visits
-- to maintain a complete audit trail of all checkouts

CREATE TABLE IF NOT EXISTS checkout_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    checkout_uid VARCHAR(30) UNIQUE NOT NULL,
    visitor_id INT NOT NULL,
    checked_out_by INT NOT NULL,
    checkout_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    check_in_time DATETIME NULL,
    check_out_time DATETIME NULL,
    duration_minutes INT,
    badge_returned BOOLEAN DEFAULT FALSE,
    badge_returned_at DATETIME NULL,
    badge_returned_to INT,
    security_notes TEXT,
    visit_rating INT DEFAULT 0,
    visit_feedback TEXT,
    additional_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    FOREIGN KEY (checked_out_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (badge_returned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_visit_id (visit_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_checkout_time (checkout_time),
    INDEX idx_checkout_uid (checkout_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Make visitor_passes compatible with strict timestamp mode before altering
ALTER TABLE visitor_passes
    MODIFY COLUMN valid_from DATETIME NULL,
    MODIFY COLUMN valid_until DATETIME NULL;

ALTER TABLE visitor_passes
    ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending' COMMENT 'Approval status from host',
    ADD COLUMN approved_by INT COMMENT 'User who approved the pass',
    ADD COLUMN approved_at DATETIME NULL COMMENT 'When the pass was approved',
    ADD COLUMN approver_notes TEXT COMMENT 'Notes from approver';

ALTER TABLE visitor_passes
    ADD CONSTRAINT fk_visitor_passes_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add missing duration_minutes field to visits
ALTER TABLE visits
    ADD COLUMN duration_minutes INT DEFAULT NULL COMMENT 'Duration of visit in minutes (auto-calculated on checkout)';

-- Add indexes for better query performance
ALTER TABLE visits
    ADD INDEX idx_checked_out_by (checked_out_by),
    ADD INDEX idx_actual_check_out (actual_check_out);
