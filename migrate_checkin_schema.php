<?php
/**
 * Migration helper for Check-In schema updates
 * Run this file once after updating the check-in module.
 * Example: php migrate_checkin_schema.php
 */

require_once 'config/config.php';

$db = getDB();
$table = 'visits';
$columns = [
    'visit_location_type' => "ENUM('office', 'retail') DEFAULT 'office'",
    'vehicle_plate' => 'VARCHAR(50)',
    'people_count' => 'INT DEFAULT 1'
];

function columnExists(PDO $db, $table, $column) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

foreach ($columns as $column => $definition) {
    if (columnExists($db, $table, $column)) {
        echo "- Column '$column' already exists in '$table'.\n";
        continue;
    }
    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
    try {
        $db->exec($sql);
        echo "[✓] Added column '$column' to '$table'.\n";
    } catch (Exception $e) {
        echo "[!] Failed to add column '$column': " . $e->getMessage() . "\n";
    }
}

echo "Migration complete.\n";
