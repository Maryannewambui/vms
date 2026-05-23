<?php
/**
 * Notifications API Endpoint
 * VMS - Pipe Manufacturing Company
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        // Get unread notifications
        $stmt = $db->prepare("
            SELECT id, type, title, message, link, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll();

        foreach ($notifications as &$n) {
            $n['created_at'] = timeAgo($n['created_at']);
        }

        jsonResponse(true, ['notifications' => $notifications]);
        break;

    case 'mark_read':
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $_SESSION['user_id']]);
            jsonResponse(true);
        }
        jsonResponse(false, [], 'Invalid notification ID');
        break;

    case 'mark_all_read':
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        jsonResponse(true);
        break;

    case 'count':
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        jsonResponse(true, ['count' => (int)$stmt->fetchColumn()]);
        break;

    default:
        jsonResponse(false, [], 'Invalid action');
}
