<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session (after loading constants)
if (session_status() === PHP_SESSION_NONE) {
    if (defined('SESSION_NAME')) {
        session_name(SESSION_NAME);
    }
    session_start();
}

// CSRF Token generation
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

/**
 * Application Constants
 */

// User Roles
define('ROLE_SUPER_ADMIN', 1);
define('ROLE_RECEPTIONIST', 2);
define('ROLE_SECURITY', 3);
define('ROLE_HR_OFFICER', 4);
define('ROLE_MAINTENANCE_SUPERVISOR', 5);
define('ROLE_DEPARTMENT_MANAGER', 6);
define('ROLE_EMPLOYEE', 7);

// Visitor Types
define('VISITOR_RETAIL_CUSTOMER', 1);
define('VISITOR_CONTRACTOR', 2);
define('VISITOR_INTERVIEW', 3);
define('VISITOR_SUPPLIER', 4);
define('VISITOR_VIP_GUEST', 5);
define('VISITOR_MAINTENANCE', 6);
define('VISITOR_DELIVERY', 7);
define('VISITOR_INSPECTOR', 8);
define('VISITOR_STAFF_GUEST', 9);

// Visit Status
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_CHECKED_IN', 'checked_in');
define('STATUS_CHECKED_OUT', 'checked_out');
define('STATUS_CANCELLED', 'cancelled');
define('STATUS_OVERDUE', 'overdue');

// Safety Levels
define('SAFETY_LOW', 1);
define('SAFETY_MEDIUM', 2);
define('SAFETY_HIGH', 3);
define('SAFETY_CRITICAL', 4);

/**
 * Helper function to get role name
 */
function getRoleName($roleId) {
    $roles = [
        ROLE_SUPER_ADMIN => 'Super Admin',
        ROLE_RECEPTIONIST => 'Receptionist',
        ROLE_SECURITY => 'Security Guard',
        ROLE_HR_OFFICER => 'HR Officer',
        ROLE_MAINTENANCE_SUPERVISOR => 'Maintenance Supervisor',
        ROLE_DEPARTMENT_MANAGER => 'Department Manager',
        ROLE_EMPLOYEE => 'Employee'
    ];
    return $roles[$roleId] ?? 'Unknown';
}

/**
 * Helper function to get visitor type name
 */
function getVisitorTypeName($typeId) {
    $types = [
        VISITOR_RETAIL_CUSTOMER => 'Retail Customer',
        VISITOR_CONTRACTOR => 'Contractor',
        VISITOR_INTERVIEW => 'Interview Candidate',
        VISITOR_SUPPLIER => 'Supplier',
        VISITOR_VIP_GUEST => 'VIP Guest',
        VISITOR_MAINTENANCE => 'Maintenance Team',
        VISITOR_DELIVERY => 'Delivery Personnel',
        VISITOR_INSPECTOR => 'Inspector',
        VISITOR_STAFF_GUEST => 'Staff Guest'
    ];
    return $types[$typeId] ?? 'Unknown';
}

/**
 * Get safety level class for Tailwind
 */
function getSafetyLevelClass($level) {
    $classes = [
        SAFETY_LOW => 'bg-green-100 text-green-800 border-green-200',
        SAFETY_MEDIUM => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        SAFETY_HIGH => 'bg-orange-100 text-orange-800 border-orange-200',
        SAFETY_CRITICAL => 'bg-red-100 text-red-800 border-red-200'
    ];
    return $classes[$level] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
        STATUS_APPROVED => 'bg-blue-100 text-blue-800',
        STATUS_CHECKED_IN => 'bg-green-100 text-green-800',
        STATUS_CHECKED_OUT => 'bg-gray-100 text-gray-800',
        STATUS_CANCELLED => 'bg-red-100 text-red-800',
        STATUS_OVERDUE => 'bg-red-200 text-red-900'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}
