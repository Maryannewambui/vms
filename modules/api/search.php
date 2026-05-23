<?php
/**
 * Search API Endpoint
 * VMS - Pipe Manufacturing Company
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
requireLogin();

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    jsonResponse(true, []);
}

$db = getDB();
$results = [];

// Search visitors
$stmt = $db->prepare("
    SELECT id, first_name, last_name, company, phone, email
    FROM visitors
    WHERE first_name LIKE ? OR last_name LIKE ? OR company LIKE ? OR phone LIKE ? OR email LIKE ?
    LIMIT 5
");
$searchTerm = "%$query%";
$stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
$visitors = $stmt->fetchAll();

foreach ($visitors as $v) {
    $results[] = [
        'type' => 'Visitor',
        'title' => $v['first_name'] . ' ' . $v['last_name'],
        'subtitle' => $v['company'] ?: 'No company',
        'link' => 'modules/records/view.php?visitor_id=' . $v['id']
    ];
}

// Search visits
$stmt = $db->prepare("
    SELECT v.id, v.visit_uid, v.visit_status, v.visit_date, v.badge_number,
           vis.first_name, vis.last_name
    FROM visits v
    JOIN visitors vis ON v.visitor_id = vis.id
    WHERE v.visit_uid LIKE ? OR v.badge_number LIKE ? OR vis.first_name LIKE ? OR vis.last_name LIKE ?
    ORDER BY v.visit_date DESC
    LIMIT 5
");
$stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
$visits = $stmt->fetchAll();

foreach ($visits as $v) {
    $results[] = [
        'type' => 'Visit',
        'title' => $v['first_name'] . ' ' . $v['last_name'],
        'subtitle' => 'Status: ' . $v['visit_status'] . ' | Date: ' . formatDate($v['visit_date']),
        'link' => 'modules/records/detail.php?id=' . $v['id']
    ];
}

// Search meetings
$stmt = $db->prepare("
    SELECT id, meeting_uid, title, meeting_date, status
    FROM meetings
    WHERE title LIKE ? OR meeting_uid LIKE ?
    ORDER BY meeting_date DESC
    LIMIT 3
");
$stmt->execute([$searchTerm, $searchTerm]);
$meetings = $stmt->fetchAll();

foreach ($meetings as $m) {
    $results[] = [
        'type' => 'Meeting',
        'title' => $m['title'] ?: 'Meeting ' . $m['meeting_uid'],
        'subtitle' => 'Date: ' . formatDate($m['meeting_date']) . ' | Status: ' . $m['status'],
        'link' => 'modules/meetings/view.php?id=' . $m['id']
    ];
}

// Search users (admin only)
if (hasPermission('manage_users')) {
    $stmt = $db->prepare("
        SELECT id, first_name, last_name, email
        FROM users
        WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ?
        LIMIT 3
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $users = $stmt->fetchAll();

    foreach ($users as $u) {
        $results[] = [
            'type' => 'User',
            'title' => $u['first_name'] . ' ' . $u['last_name'],
            'subtitle' => $u['email'],
            'link' => 'modules/admin/users.php?edit=' . $u['id']
        ];
    }
}

jsonResponse(true, ['results' => $results]);
