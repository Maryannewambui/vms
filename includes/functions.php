<?php
/**
 * Core Functions
 * VMS - Pipe Manufacturing Company
 */

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token field
 */
function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $_SESSION[CSRF_TOKEN_NAME] . '">';
}

/**
 * Verify CSRF token
 */
function verifyCSRF() {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || $_POST[CSRF_TOKEN_NAME] !== $_SESSION[CSRF_TOKEN_NAME]) {
        die('CSRF token validation failed.');
    }
}

/**
 * Redirect helper
 */
function redirect($url) {
    header('Location: ' . APP_URL . $url);
    exit();
}

/**
 * Format date
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd M Y H:i') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Format time
 */
function formatTime($time) {
    if (empty($time)) {
        return '-';
    }
    return date('H:i', strtotime($time));
}

/**
 * Calculate duration
 */
function calculateDuration($start, $end) {
    $startTime = new DateTime($start);
    $endTime = new DateTime($end);
    $interval = $startTime->diff($endTime);

    if ($interval->h > 0) {
        return $interval->h . 'h ' . $interval->i . 'm';
    }
    return $interval->i . ' minutes';
}

/**
 * Time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) {
        return 'just now';
    }

    $units = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute'
    ];

    foreach ($units as $seconds => $unit) {
        $value = floor($time / $seconds);
        if ($value > 0) {
            return $value . ' ' . $unit . ($value > 1 ? 's' : '') . ' ago';
        }
    }

    return 'just now';
}

/**
 * Generate unique ID
 */
function generateUID($prefix = '') {
    return $prefix . strtoupper(bin2hex(random_bytes(8)));
}

/**
 * Generate QR Code data
 */
function generateQRData($visitId) {
    return json_encode([
        'visit_id' => $visitId,
        'timestamp' => time(),
        'hash' => hash('sha256', $visitId . APP_KEY . time())
    ]);
}

/**
 * Generate random password
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Log activity
 */
function logActivity($action, $details = '', $userId = null) {
    $db = getDB();

    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }

    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Send notification
 */
function sendNotification($userId, $type, $title, $message, $link = '') {
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, type, title, message, link, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([$userId, $type, $title, $message, $link]);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    $db = getDB();

    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);

    return $stmt->fetchColumn();
}

/**
 * Upload file
 */
function uploadFile($file, $directory, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateUID() . '.' . $extension;
    $filepath = UPLOAD_PATH . $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to move file'];
    }

    return ['success' => true, 'filename' => $filename, 'path' => $filepath];
}

/**
 * Delete file
 */
function deleteFile($filename, $directory) {
    $filepath = UPLOAD_PATH . $directory . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get setting
 */
function getSetting($key) {
    $db = getDB();

    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);

    return $stmt->fetchColumn() ?: null;
}

/**
 * Set setting
 */
function setSetting($key, $value) {
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value, updated_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");

    $stmt->execute([$key, $value, $value]);
}

/**
 * JSON response
 */
function jsonResponse($success, $data = [], $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return $phone;
}

/**
 * Generate badge number
 */
function generateBadgeNumber() {
    return 'VMS' . date('Ymd') . '-' . rand(10000, 99999);
}

/**
 * Check if visitor is blacklisted
 */
function isBlacklisted($visitorId = null, $idNumber = null, $email = null) {
    $db = getDB();

    $conditions = [];
    $params = [];

    if ($visitorId) {
        $conditions[] = 'visitor_id = ?';
        $params[] = $visitorId;
    }
    if ($idNumber) {
        $conditions[] = 'id_number = ?';
        $params[] = $idNumber;
    }
    if ($email) {
        $conditions[] = 'email = ?';
        $params[] = $email;
    }

    if (empty($conditions)) {
        return false;
    }

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM blacklist
        WHERE (" . implode(' OR ', $conditions) . ") AND status = 'active'
    ");
    $stmt->execute($params);

    return $stmt->fetchColumn() > 0;
}

/**
 * Get department name
 */
function getDepartmentName($deptId) {
    $db = getDB();

    $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$deptId]);

    return $stmt->fetchColumn() ?: 'Unknown';
}

/**
 * Get user name
 */
function getUserName($userId) {
    $db = getDB();

    $stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    return $stmt->fetchColumn() ?: 'Unknown';
}

/**
 * Export to CSV
 */
function exportCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if (!empty($headers)) {
        fputcsv($output, $headers);
    }

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/**
 * Generate pagination
 */
function getPagination($total, $page, $perPage = RECORDS_PER_PAGE) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Check if today is workday
 */
function isWorkday($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    $dayOfWeek = date('N', strtotime($date));
    return $dayOfWeek >= 1 && $dayOfWeek <= 5; // Monday to Friday
}

/**
 * Generate access token
 */
function generateAccessToken() {
    return bin2hex(random_bytes(32));
}
