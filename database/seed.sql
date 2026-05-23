/*
 * Visitor Management System - Seed Data
 * Pipe Manufacturing Company
 */

USE vms_pipe_manufacturing;

-- ============================================
-- ROLES
-- ============================================
INSERT INTO roles (id, name, description, permissions) VALUES
(1, 'Super Admin', 'Full system access and administration', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","generate_pass","view_records","export_records","create_meeting","approve_meeting","schedule_meeting","manage_users","manage_departments","manage_settings","view_audit_logs","manage_blacklist","view_incidents","create_incident"]'),
(2, 'Receptionist', 'Front desk operations and visitor management', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","generate_pass","view_records","create_meeting"]'),
(3, 'Security Guard', 'Security operations, check-in/out, and monitoring', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","view_records","manage_blacklist","view_incidents","create_incident"]'),
(4, 'HR Officer', 'Human resources and personnel management', '["view_dashboard","create_visitor","create_meeting","approve_meeting","schedule_meeting","view_records","export_records","create_incident"]'),
(5, 'Maintenance Supervisor', 'Maintenance and contractor management', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","create_meeting","schedule_meeting","view_records"]'),
(6, 'Department Manager', 'Department operations and approvals', '["view_dashboard","create_visitor","create_meeting","approve_meeting","schedule_meeting","view_records","export_records"]'),
(7, 'Employee', 'Basic user with meeting scheduling access', '["view_dashboard","create_visitor","create_meeting","schedule_meeting"]')
ON DUPLICATE KEY UPDATE name = VALUES(name), permissions = VALUES(permissions);

-- ============================================
-- DEPARTMENTS
-- ============================================
INSERT INTO departments (name, code, floor, building, phone, email, is_active) VALUES
('Administration', 'ADMIN', '1st', 'Main Office', '555-0100', 'admin@pipevms.com', TRUE),
('Production', 'PROD', 'Ground', 'Factory Hall A', '555-0200', 'production@pipevms.com', TRUE),
('Quality Control', 'QC', '2nd', 'Factory Hall A', '555-0300', 'qc@pipevms.com', TRUE),
('Maintenance', 'MAINT', 'Ground', 'Workshop', '555-0400', 'maintenance@pipevms.com', TRUE),
('Human Resources', 'HR', '2nd', 'Main Office', '555-0500', 'hr@pipevms.com', TRUE),
('Finance', 'FIN', '2nd', 'Main Office', '555-0600', 'finance@pipevms.com', TRUE),
('Sales & Marketing', 'SALES', '1st', 'Main Office', '555-0700', 'sales@pipevms.com', TRUE),
('Inventory & Warehouse', 'INV', 'Ground', 'Warehouse', '555-0800', 'inventory@pipevms.com', TRUE),
('Safety & Security', 'SAFETY', 'Ground', 'Security Office', '555-0900', 'safety@pipevms.com', TRUE),
('Engineering', 'ENG', '2nd', 'Main Office', '555-1000', 'engineering@pipevms.com', TRUE);

-- ============================================
-- VISITOR CATEGORIES
-- ============================================
INSERT INTO visitor_categories (id, name, description, color_code, requires_approval, requires_safety_induction, requires_nda, max_duration_minutes) VALUES
(1, 'Retail Customer', 'Customers visiting for retail purchases', '#3B82F6', FALSE, FALSE, FALSE, 120),
(2, 'Contractor', 'External contractors for maintenance/construction', '#F97316', TRUE, TRUE, TRUE, 480),
(3, 'Interview Candidate', 'Job applicants for interviews', '#8B5CF6', FALSE, FALSE, TRUE, 240),
(4, 'Supplier', 'Vendors and delivery personnel', '#10B981', FALSE, FALSE, FALSE, 180),
(5, 'VIP Guest', 'Important guests and executives', '#EF4444', TRUE, FALSE, FALSE, 360),
(6, 'Maintenance Team', 'Internal or external maintenance personnel', '#F59E0B', TRUE, TRUE, FALSE, 480),
(7, 'Delivery Personnel', 'Package and material delivery', '#6B7280', FALSE, FALSE, FALSE, 60),
(8, 'Inspector', 'Government and regulatory inspectors', '#EC4899', TRUE, FALSE, TRUE, 300),
(9, 'Staff Guest', 'Personal guests of employees', '#14B8A6', TRUE, FALSE, FALSE, 180)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================
-- RESTRICTED AREAS
-- ============================================
INSERT INTO restricted_areas (name, code, description, building, floor, security_level, requires_safety_induction) VALUES
('Production Floor', 'PROD-01', 'Main manufacturing area', 'Factory Hall A', 'Ground', 2, TRUE),
('Quality Lab', 'QC-01', 'Testing and quality control laboratory', 'Factory Hall A', '1st', 2, TRUE),
('Server Room', 'IT-01', 'IT infrastructure and servers', 'Main Office', '2nd', 4, FALSE),
('Finance Archive', 'FIN-01', 'Financial records storage', 'Main Office', '2nd', 3, FALSE),
('Hazardous Storage', 'HAZ-01', 'Chemical and hazardous material storage', 'Warehouse', 'Ground', 4, TRUE),
('Main Warehouse', 'WH-01', 'Finished goods and raw materials', 'Warehouse', 'Ground', 2, FALSE),
('Control Room', 'CTRL-01', 'Production control systems', 'Factory Hall A', '1st', 3, TRUE),
('R&D Lab', 'RND-01', 'Research and development laboratory', 'Main Office', '2nd', 3, TRUE);

-- ============================================
-- DEFAULT ADMIN USER
-- Password: Admin@123
-- ============================================
INSERT INTO users (employee_id, first_name, last_name, email, password, phone, role_id, department_id, is_active) VALUES
('EMP001', 'Maryanne', 'Wambui', 'devops@dancocapital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0001', 1, 1, TRUE),
('EMP002', 'Sarah', 'Johnson', 'reception@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0002', 2, 1, TRUE),
('EMP003', 'Michael', 'Brown', 'security@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0003', 3, 9, TRUE),
('EMP004', 'Emily', 'Davis', 'hr@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0004', 4, 5, TRUE),
('EMP005', 'Robert', 'Wilson', 'maint@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0005', 5, 4, TRUE),
('EMP006', 'David', 'Miller', 'prodmanager@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0006', 6, 2, TRUE),
('EMP007', 'Jennifer', 'Garcia', 'salesmanager@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0007', 6, 7, TRUE),
('EMP008', 'James', 'Martinez', 'eng1@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0008', 7, 10, TRUE),
('EMP009', 'Lisa', 'Anderson', 'eng2@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0009', 7, 10, TRUE),
('EMP010', 'John', 'Taylor', 'worker1@pipevms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0010', 7, 2, TRUE);

-- ============================================
-- SETTINGS
-- ============================================
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'Precision Pipe Manufacturing Co.', 'text', 'Company Name'),
('company_logo', '', 'text', 'Company Logo Path'),
('company_address', '123 Industrial Zone, Manufacturing District', 'text', 'Company Address'),
('company_phone', '+1 555-0100', 'text', 'Company Phone'),
('company_email', 'info@pipevms.com', 'text', 'Company Email'),
('work_start_time', '08:00', 'text', 'Work Day Start Time'),
('work_end_time', '17:00', 'text', 'Work Day End Time'),
('badge_expiry_hours', '8', 'number', 'Badge Validity Hours'),
('enable_photo_capture', 'true', 'boolean', 'Enable Photo Capture'),
('enable_signature', 'true', 'boolean', 'Enable Signature Capture'),
('enable_qr_badge', 'true', 'boolean', 'Enable QR Code on Badge'),
('auto_approval', 'false', 'boolean', 'Auto Approve Visitors'),
('overdue_minutes', '30', 'number', 'Minutes after scheduled time to mark overdue'),
('max_concurrent_visitors', '50', 'number', 'Maximum Concurrent Visitors'),
('emergency_contact_required', 'true', 'boolean', 'Emergency Contact Required'),
('terms_text', 'I acknowledge and agree to follow all safety guidelines and company policies during my visit.', 'text', 'Terms and Conditions Text'),
('max_upload_size', '5242880', 'number', 'Maximum Upload Size in Bytes'),
('session_timeout', '1800', 'number', 'Session Timeout in Seconds'),
('notify_host_on_arrival', 'true', 'boolean', 'Notify Host on Visitor Arrival'),
('notify_security_on_checkin', 'true', 'boolean', 'Notify Security on Check-in'),
('enable_sms_notifications', 'false', 'boolean', 'Enable SMS Notifications'),
('enable_email_notifications', 'true', 'boolean', 'Enable Email Notifications'),
('badge_template', 'default', 'text', 'Active Badge Template'),
('retention_days', '365', 'number', 'Data Retention Days'),
('archive_after_months', '12', 'number', 'Archive Records After Months');

-- ============================================
-- SAMPLE VISITORS
-- ============================================
INSERT INTO visitors (visitor_uid, first_name, last_name, email, phone, company, designation, id_type, id_number, nationality, frequent_visitor) VALUES
('VIS-001', 'John', 'Smith', 'john.smith@techcontractors.com', '555-1001', 'Tech Contractors Inc.', 'Project Manager', 'national_id', 'ID-10001', 'USA', TRUE),
('VIS-002', 'Maria', 'Garcia', 'maria@steelsupplies.com', '555-1002', 'Steel Supplies Ltd.', 'Sales Representative', 'passport', 'PP-20001', 'Mexico', TRUE),
('VIS-003', 'Robert', 'Chen', 'rchen@qualityaudit.com', '555-1003', 'Quality Auditors International', 'Lead Auditor', 'national_id', 'ID-30001', 'Canada', FALSE),
('VIS-004', 'Susan', 'Wilson', 'swilson@deliveryexpress.com', '555-1004', 'Delivery Express', 'Driver', 'drivers_license', 'DL-40001', 'USA', TRUE),
('VIS-005', 'Ahmed', 'Hassan', 'ahassan@pipetools.com', '555-1005', 'Pipe Tools Co.', 'Technician', 'national_id', 'ID-50001', 'UAE', FALSE),
('VIS-006', 'Emma', 'Thompson', 'ethompson@government.gov', '555-1006', 'Environmental Protection Agency', 'Inspector', 'national_id', 'ID-60001', 'UK', FALSE),
('VIS-007', 'Carlos', 'Rodriguez', 'crodriguez@maintops.com', '555-1007', 'Maintenance Ops', 'Lead Technician', 'work_permit', 'WP-70001', 'Spain', TRUE),
('VIS-008', 'Jennifer', 'Lee', 'jlee@candidate.com', '555-1008', '', 'Job Applicant', 'national_id', 'ID-80001', 'USA', FALSE),
('VIS-009', 'Michael', 'Brown', 'mbrown@investor.com', '555-1009', 'Industrial Investors LLC', 'Managing Director', 'passport', 'PP-90001', 'USA', FALSE),
('VIS-010', 'Lisa', 'Park', 'lpark@safetyfirst.com', '555-1010', 'Safety First Training', 'Safety Consultant', 'passport', 'PP-100001', 'Korea', FALSE);

-- ============================================
-- SAMPLE BLACKLIST ENTRIES
-- ============================================
INSERT INTO blacklist (visitor_id, first_name, last_name, id_number, email, reason, severity, added_by, status) VALUES
(NULL, 'Richard', 'Blacklist', 'ID-99999', 'rblacklist@scam.com', 'Previous attempt at corporate espionage', 'critical', 1, 'active'),
(NULL, 'Thomas', 'Smith', 'ID-88888', 'tsmith@fakecontractor.com', 'Failed to follow safety protocols, multiple incidents', 'high', 3, 'active');

-- ============================================
-- GATE ENTRIES
-- ============================================
INSERT INTO gate_logs (gate_name, gate_type) VALUES
('Main Gate A', 'main'),
('Main Gate B', 'main'),
('Loading Gate 1', 'loading'),
('Loading Gate 2', 'loading'),
('Side Gate East', 'side'),
('Emergency Gate', 'emergency');

ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
