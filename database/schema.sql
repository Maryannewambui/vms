/*
 * Visitor Management System - Pipe Manufacturing Company
 * Database Schema
 *
 * This schema supports a complete enterprise visitor management system
 * for a pipe manufacturing industrial environment.
 */

-- Database creation
CREATE DATABASE IF NOT EXISTS vms_pipe_manufacturing
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE vms_pipe_manufacturing;

-- ============================================
-- ROLES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    permissions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (name)
) ENGINE=InnoDB;

-- ============================================
-- DEPARTMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    floor VARCHAR(50),
    building VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(20),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    department_id INT,
    profile_photo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

-- Add foreign key for manager after users table exists
ALTER TABLE departments
ADD FOREIGN KEY (manager_id) REFERENCES users(id);

-- ============================================
-- VISITOR CATEGORIES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visitor_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    color_code VARCHAR(20) DEFAULT '#gray',
    badge_template VARCHAR(100),
    requires_approval BOOLEAN DEFAULT FALSE,
    requires_safety_induction BOOLEAN DEFAULT FALSE,
    requires_nda BOOLEAN DEFAULT FALSE,
    max_duration_minutes INT DEFAULT 480,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- VISITORS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_uid VARCHAR(30) UNIQUE,
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
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_id_number (id_number),
    INDEX idx_company (company)
) ENGINE=InnoDB;

-- ============================================
-- MEETINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS meetings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    meeting_uid VARCHAR(30) UNIQUE,
    title VARCHAR(200),
    purpose TEXT,
    description TEXT,
    host_user_id INT NOT NULL,
    department_id INT,
    location VARCHAR(100),
    meeting_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    expected_duration INT DEFAULT 60,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    safety_clearance_level INT DEFAULT 1,
    requires_nda BOOLEAN DEFAULT FALSE,
    requires_safety_induction BOOLEAN DEFAULT FALSE,
    restricted_areas TEXT,
    notes TEXT,
    internal_participants TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (host_user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

-- ============================================
-- MEETING VISITORS (Junction Table)
-- ============================================
CREATE TABLE IF NOT EXISTS meeting_visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    meeting_id INT NOT NULL,
    visitor_id INT NOT NULL,
    status ENUM('invited', 'confirmed', 'attended', 'absent', 'cancelled') DEFAULT 'invited',
    invitation_sent BOOLEAN DEFAULT FALSE,
    invitation_sent_at TIMESTAMP NULL,
    qr_code VARCHAR(255),
    qr_generated_at TIMESTAMP NULL,
    response_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id),
    UNIQUE KEY unique_meeting_visitor (meeting_id, visitor_id)
) ENGINE=InnoDB;

-- ============================================
-- VISITS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_uid VARCHAR(30) UNIQUE,
    visitor_id INT NOT NULL,
    meeting_id INT,
    category_id INT NOT NULL,
    host_user_id INT NOT NULL,
    department_id INT,
    visit_location_type ENUM('office', 'retail') DEFAULT 'office',
    vehicle_plate VARCHAR(50),
    people_count INT DEFAULT 1,
    visit_date DATE NOT NULL,
    visit_status ENUM('pre_registered', 'approved', 'checked_in', 'checked_out', 'cancelled', 'overdue', 'no_show') DEFAULT 'pre_registered',
    scheduled_arrival_time TIME,
    scheduled_departure_time TIME,
    actual_check_in TIMESTAMP NULL,
    actual_check_out TIMESTAMP NULL,
    badge_number VARCHAR(30),
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
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relation VARCHAR(50),
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
    FOREIGN KEY (visitor_id) REFERENCES visitors(id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id),
    FOREIGN KEY (category_id) REFERENCES visitor_categories(id),
    FOREIGN KEY (host_user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    INDEX idx_visit_date (visit_date),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_status (visit_status),
    INDEX idx_badge_number (badge_number)
) ENGINE=InnoDB;

-- ============================================
-- VEHICLES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    vehicle_type ENUM('car', 'truck', 'van', 'motorcycle', 'other') DEFAULT 'car',
    vehicle_make VARCHAR(50),
    vehicle_model VARCHAR(50),
    vehicle_color VARCHAR(30),
    driver_name VARCHAR(100),
    driver_license VARCHAR(50),
    parking_slot VARCHAR(20),
    gate_in VARCHAR(20),
    gate_out VARCHAR(20),
    check_in_time TIMESTAMP NULL,
    check_out_time TIMESTAMP NULL,
    is_company_vehicle BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    INDEX idx_vehicle_number (vehicle_number)
) ENGINE=InnoDB;

-- ============================================
-- VISITOR DOCUMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visitor_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT,
    visitor_id INT NOT NULL,
    document_type ENUM('id_document', 'nda', 'safety_certificate', 'contract', 'other') NOT NULL,
    document_name VARCHAR(100),
    file_path VARCHAR(255),
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    expiry_date DATE,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id)
) ENGINE=InnoDB;

-- ============================================
-- EQUIPMENT/TOOLS DECLARATION TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS equipment_declarations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    equipment_type VARCHAR(100) NOT NULL,
    description TEXT,
    quantity INT DEFAULT 1,
    serial_number VARCHAR(100),
    brought_in_time TIMESTAMP,
    taken_out_time TIMESTAMP,
    security_verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- VISITOR PASSES/ACCESS RIGHTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visitor_passes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    pass_number VARCHAR(30) UNIQUE,
    pass_type ENUM('temporary', 'contractor', 'vip', 'delivery', 'interview', 'emergency') DEFAULT 'temporary',
    qr_code VARCHAR(255),
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    issued_by INT,
    valid_from TIMESTAMP,
    valid_until TIMESTAMP,
    print_count INT DEFAULT 0,
    last_printed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    revoked_at TIMESTAMP NULL,
    revoked_by INT,
    revoke_reason TEXT,
    access_areas TEXT,
    safety_level INT DEFAULT 1,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id),
    INDEX idx_pass_number (pass_number)
) ENGINE=InnoDB;

-- ============================================
-- BLACKLIST TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS blacklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_id INT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    id_number VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    company VARCHAR(100),
    reason TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    status ENUM('active', 'expired', 'removed') DEFAULT 'active',
    removed_by INT,
    removed_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id),
    INDEX idx_id_number (id_number),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('visitor_arrival', 'meeting_reminder', 'approval_request', 'overdue_alert',
              'checkout_reminder', 'blacklist_alert', 'system', 'incident') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    sent_email BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB;

-- ============================================
-- ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    related_entity_type VARCHAR(50),
    related_entity_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================
-- INCIDENT REPORTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS incident_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    incident_uid VARCHAR(30) UNIQUE,
    visit_id INT,
    visitor_id INT,
    incident_type ENUM('security', 'safety', 'behavior', 'unauthorized_access', 'theft', 'vandalism', 'other') NOT NULL,
    severity ENUM('minor', 'moderate', 'major', 'critical') DEFAULT 'minor',
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(100),
    incident_date DATE NOT NULL,
    incident_time TIME,
    action_taken TEXT,
    status ENUM('open', 'investigating', 'resolved', 'closed') DEFAULT 'open',
    reported_by INT NOT NULL,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_to INT,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    involved_parties TEXT,
    witnesses TEXT,
    evidence_files TEXT,
    FOREIGN KEY (visit_id) REFERENCES visits(id),
    FOREIGN KEY (visitor_id) REFERENCES visitors(id),
    FOREIGN KEY (reported_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_incident_date (incident_date)
) ENGINE=InnoDB;

-- ============================================
-- SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT
) ENGINE=InnoDB;

-- ============================================
-- SETTINGS METADATA TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS settings_metadata (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_name VARCHAR(50) NOT NULL,
    setting_name VARCHAR(100) NOT NULL,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    default_value TEXT,
    hint VARCHAR(255),
    field_type VARCHAR(20) NOT NULL,
    options JSON,
    validation_rules JSON,
    sort_order INT DEFAULT 0,
    is_required BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (setting_key) REFERENCES settings(setting_key)
) ENGINE=InnoDB;

-- ============================================
-- APPROVAL REQUESTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT,
    meeting_id INT,
    request_type ENUM('visit', 'meeting', 'access', 'equipment') NOT NULL,
    requested_by INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    rejection_reason TEXT,
    notes TEXT,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    FOREIGN KEY (visit_id) REFERENCES visits(id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- RESTRICTED AREAS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS restricted_areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE,
    description TEXT,
    building VARCHAR(50),
    floor VARCHAR(20),
    security_level INT DEFAULT 1,
    requires_safety_induction BOOLEAN DEFAULT FALSE,
    requires_special_clearance BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- VISIT AREA ACCESS LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS visit_area_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visit_id INT NOT NULL,
    area_id INT NOT NULL,
    access_type ENUM('entry', 'exit') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT,
    notes TEXT,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES restricted_areas(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================
-- EMERGENCY EVACUATION TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_evacuations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evacuation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    evacuation_type ENUM('fire', 'chemical', 'security_threat', 'drill', 'other'),
    assembly_point VARCHAR(100),
    initiated_by INT NOT NULL,
    status ENUM('active', 'all_clear') DEFAULT 'active',
    cleared_by INT,
    cleared_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (initiated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================
-- EVACUATION CHECK TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS evacuation_checks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evacuation_id INT NOT NULL,
    visit_id INT NOT NULL,
    status ENUM('unaccounted', 'safe', 'missing') DEFAULT 'unaccounted',
    marked_by INT,
    marked_at TIMESTAMP NULL,
    location_safe VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (evacuation_id) REFERENCES emergency_evacuations(id),
    FOREIGN KEY (visit_id) REFERENCES visits(id)
) ENGINE=InnoDB;

-- ============================================
-- GATE LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS gate_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    gate_name VARCHAR(50) NOT NULL,
    gate_type ENUM('main', 'side', 'loading', 'emergency') DEFAULT 'main',
    visit_id INT,
    vehicle_id INT,
    access_type ENUM('entry', 'exit') NOT NULL,
    access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guard_id INT,
    notes TEXT,
    FOREIGN KEY (visit_id) REFERENCES visits(id),
    INDEX idx_access_time (access_time)
) ENGINE=InnoDB;

-- ============================================
-- CONTRACTOR DETAILS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS contractor_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    contract_number VARCHAR(50),
    contract_start_date DATE,
    contract_end_date DATE,
    work_permit_number VARCHAR(50),
    work_permit_expiry DATE,
    insurance_verified BOOLEAN DEFAULT FALSE,
    insurance_expiry DATE,
    safety_training_certified BOOLEAN DEFAULT FALSE,
    safety_training_date DATE,
    safety_training_expiry DATE,
    approved_supervisor INT,
    approved_work_areas TEXT,
    status ENUM('active', 'expired', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id)
) ENGINE=InnoDB;

-- ============================================
-- EMERGENCY CONTACTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    relation VARCHAR(50),
    phone VARCHAR(20) NOT NULL,
    secondary_phone VARCHAR(20),
    email VARCHAR(100),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE
) ENGINE=InnoDB;
