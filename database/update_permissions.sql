/**
 * Role Permissions Update
 * Sets permissions for each role based on the access matrix
 */

USE vms_pipe_manufacturing;

-- Super Admin - All permissions
UPDATE roles SET permissions = JSON_ARRAY(
    'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor', 'generate_pass',
    'view_records', 'export_records', 'create_meeting', 'approve_meeting', 'schedule_meeting',
    'manage_users', 'manage_departments', 'manage_settings', 'view_audit_logs',
    'manage_blacklist', 'view_incidents', 'create_incident', 'manage_blacklist'
) WHERE id = 1;

-- Receptionist - Reception operations
UPDATE roles SET permissions = JSON_ARRAY(
    'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor', 'generate_pass',
    'view_records', 'create_meeting'
) WHERE id = 2;

-- Security Guard - Security operations
UPDATE roles SET permissions = JSON_ARRAY(
    'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor',
    'view_records', 'manage_blacklist', 'view_incidents', 'create_incident'
) WHERE id = 3;

-- HR Officer - Personnel management
UPDATE roles SET permissions = JSON_ARRAY(
    'view_dashboard', 'create_visitor', 'create_meeting', 'approve_meeting', 'schedule_meeting',
    'view_records', 'export_records', 'create_incident'
) WHERE id = 4;

-- Maintenance Supervisor - Maintenance operations
UPDATE roles SET permissions = JSON_ARRAY(
    'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor',
    'create_meeting', 'schedule_meeting', 'view_records'
) WHERE id = 5;

-- Department Manager - Department operations
UPDATE roles SET permissions = JSON_ARRAY(
    'view_dashboard', 'create_visitor', 'create_meeting', 'approve_meeting', 'schedule_meeting',
    'view_records', 'export_records'
) WHERE id = 6;

-- Employee - Basic access
UPDATE roles SET permissions = JSON_ARRAY(
    'view_dashboard', 'create_visitor', 'create_meeting', 'schedule_meeting'
) WHERE id = 7;
