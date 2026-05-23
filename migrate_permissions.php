<?php
/**
 * Database Migration Helper - Update Role Permissions
 * Run this once to set proper permissions for all roles
 * Access: http://localhost/vms/migrate_permissions.php
 */

require_once 'config/config.php';

// Security: Only allow from localhost or with special token
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ip !== '127.0.0.1' && $ip !== 'localhost' && ($argv[0] ?? '') === '') {
    die('Access denied');
}

$db = getDB();

// Define permissions for each role
$rolePermissions = [
    1 => [ // Super Admin
        'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor', 'generate_pass',
        'view_records', 'export_records', 'create_meeting', 'approve_meeting', 'schedule_meeting',
        'manage_users', 'manage_departments', 'manage_settings', 'view_audit_logs',
        'manage_blacklist', 'view_incidents', 'create_incident'
    ],
    2 => [ // Receptionist
        'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor', 'generate_pass',
        'view_records', 'create_meeting'
    ],
    3 => [ // Security Guard
        'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor',
        'view_records', 'manage_blacklist', 'view_incidents', 'create_incident'
    ],
    4 => [ // HR Officer
        'view_dashboard', 'create_visitor', 'create_meeting', 'approve_meeting', 'schedule_meeting',
        'view_records', 'export_records', 'create_incident'
    ],
    5 => [ // Maintenance Supervisor
        'view_dashboard', 'create_visitor', 'checkin_visitor', 'checkout_visitor',
        'create_meeting', 'schedule_meeting', 'view_records'
    ],
    6 => [ // Department Manager
        'view_dashboard', 'create_visitor', 'create_meeting', 'approve_meeting', 'schedule_meeting',
        'view_records', 'export_records'
    ],
    7 => [ // Employee
        'view_dashboard', 'create_visitor', 'create_meeting', 'schedule_meeting'
    ]
];

$updated = 0;
$errors = [];

try {
    foreach ($rolePermissions as $roleId => $permissions) {
        $permissionsJson = json_encode($permissions);
        
        $stmt = $db->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
        $result = $stmt->execute([$permissionsJson, $roleId]);
        
        if ($result) {
            $updated++;
            echo "[✓] Role $roleId updated with " . count($permissions) . " permissions\n";
        } else {
            $errors[] = "Failed to update role $roleId";
        }
    }
    
    echo "\n✓ Migration complete! Updated $updated roles.\n";
    
    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}

// Also update seed.sql for future reference
updateSeedFile();

function updateSeedFile() {
    $seedPath = __DIR__ . '/database/seed.sql';
    $content = file_get_contents($seedPath);
    
    // Check if permissions are already in seed file
    if (strpos($content, "JSON_ARRAY") !== false) {
        return; // Already updated
    }
    
    // Find the roles INSERT statement
    if (preg_match('/INSERT INTO `roles`[^;]+;/s', $content, $matches)) {
        $oldInsert = $matches[0];
        
        // Create new insert with permissions
        $newInsert = <<<'SQL'
INSERT INTO `roles` (`id`, `name`, `description`, `permissions`) VALUES
(1, 'Super Admin', 'Full system access and administration', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","generate_pass","view_records","export_records","create_meeting","approve_meeting","schedule_meeting","manage_users","manage_departments","manage_settings","view_audit_logs","manage_blacklist","view_incidents","create_incident"]'),
(2, 'Receptionist', 'Front desk operations and visitor management', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","generate_pass","view_records","create_meeting"]'),
(3, 'Security Guard', 'Security operations, check-in/out, and monitoring', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","view_records","manage_blacklist","view_incidents","create_incident"]'),
(4, 'HR Officer', 'Personnel management and incident tracking', '["view_dashboard","create_visitor","create_meeting","approve_meeting","schedule_meeting","view_records","export_records","create_incident"]'),
(5, 'Maintenance Supervisor', 'Maintenance operations and visitor tracking', '["view_dashboard","create_visitor","checkin_visitor","checkout_visitor","create_meeting","schedule_meeting","view_records"]'),
(6, 'Department Manager', 'Department operations and reporting', '["view_dashboard","create_visitor","create_meeting","approve_meeting","schedule_meeting","view_records","export_records"]'),
(7, 'Employee', 'Basic access for employees', '["view_dashboard","create_visitor","create_meeting","schedule_meeting"]');
SQL;
        
        $content = str_replace($oldInsert, $newInsert, $content);
        
        if (file_put_contents($seedPath, $content)) {
            echo "\n[✓] Updated seed.sql with permissions\n";
        }
    }
}
?>
