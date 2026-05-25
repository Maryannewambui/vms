-- ============================================
-- CORRECTED VISITORS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_uid VARCHAR(30) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    company VARCHAR(100),
    designation VARCHAR(100),
    id_type ENUM('passport', 'national_id', 'drivers_license', 'work_permit', 'other') DEFAULT 'national_id',
    id_number VARCHAR(50),
    id_expiry DATE,
    nationality VARCHAR(50),
    photo VARCHAR(255),
    id_document_front VARCHAR(255),
    id_document_back VARCHAR(255),
    notes TEXT,
    frequent_visitor BOOLEAN DEFAULT FALSE,
    safety_induction_completed BOOLEAN DEFAULT FALSE,
    safety_induction_date TIMESTAMP NULL,
    nda_signed BOOLEAN DEFAULT FALSE,
    nda_signed_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_id_number (id_number),
    INDEX idx_company (company),
    INDEX idx_visitor_uid (visitor_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CORRECTED VISITS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_uid VARCHAR(30) UNIQUE NOT NULL,
    visitor_id INT NOT NULL,
    meeting_id INT,
    category_id INT NOT NULL,
    host_user_id INT NOT NULL,
    department_id INT,
    visit_date DATE NOT NULL,
    visit_status ENUM('pre_registered', 'approved', 'checked_in', 'checked_out', 'cancelled', 'overdue', 'no_show') DEFAULT 'pre_registered',
    scheduled_arrival_time TIME,
    scheduled_departure_time TIME,
    actual_check_in TIMESTAMP NULL,
    actual_check_out TIMESTAMP NULL,
    badge_number VARCHAR(30),
    number_plate VARCHAR(50),
    people_count INT DEFAULT 1,
    visit_location_type ENUM('office', 'retail', 'delivery', 'meeting', 'audit', 'training', 'maintenance') DEFAULT 'office',
    badge_printed BOOLEAN DEFAULT FALSE,
    badge_printed_at TIMESTAMP NULL,
    badge_returned BOOLEAN DEFAULT FALSE,
    badge_returned_at TIMESTAMP NULL,
    badge_returned_to INT,
    purpose VARCHAR(255),
    actual_purpose TEXT,
    safety_clearance_level INT DEFAULT 1,
    safety_induction_acknowledged BOOLEAN DEFAULT FALSE,
    safety_induction_at TIMESTAMP NULL,
    nda_acknowledged BOOLEAN DEFAULT FALSE,
    nda_acknowledged_at TIMESTAMP NULL,
    terms_acknowledged BOOLEAN DEFAULT FALSE,
    terms_acknowledged_at TIMESTAMP NULL,
    photo_taken VARCHAR(255),
    signature VARCHAR(255),
    notes TEXT,
    security_notes TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    checked_in_by INT,
    checked_out_by INT,
    visit_rating INT,
    visit_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES visitor_categories(id),
    FOREIGN KEY (host_user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (checked_out_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_visit_date (visit_date),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_status (visit_status),
    INDEX idx_badge_number (badge_number),
    INDEX idx_visit_uid (visit_uid),
    INDEX idx_host_user_id (host_user_id),
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- KEY CHANGES MADE:
-- ============================================
-- VISITORS TABLE:
-- ✓ Added visitor_uid index for quick lookups
-- ✓ Added created_by column for audit trail
-- ✓ Removed emergency_contact columns (as per requirements)
-- ✓ Confirmed charset and collation

-- VISITS TABLE:
-- ✓ Removed non-existent column: checked_in_at (timestamp is actual_check_in)
-- ✓ Removed emergency contact columns: emergency_contact_name, emergency_contact_phone, emergency_contact_relation
-- ✓ Added foreign key constraints with proper ON DELETE rules
-- ✓ Added checked_in_by, checked_out_by, approved_by as foreign keys to users table
-- ✓ Added updated_by column for audit trail
-- ✓ Added more indexes for better query performance
-- ✓ Confirmed purpose column for capturing visit type
-- ✓ Confirmed visit_location_type supports all scenarios
-- ✓ Confirmed charset and collation
