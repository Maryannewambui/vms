<?php
/**
 * Activity Logs
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();

// Restrict admin access to Super Admin only
if ($_SESSION['user_role'] !== ROLE_SUPER_ADMIN) {
    http_response_code(403);
    die('Access Denied: Admin panel is restricted to Super Administrators only.');
}

requirePermission('view_audit_logs');

$pageTitle = 'Activity Logs';
$db = getDB();

// Filters
$where = "WHERE 1=1";
$params = [];

if (!empty($_GET['user_id'])) {
    $where .= " AND al.user_id = ?";
    $params[] = (int)$_GET['user_id'];
}

if (!empty($_GET['action'])) {
    $where .= " AND al.action LIKE ?";
    $params[] = '%' . sanitize($_GET['action']) . '%';
}

if (!empty($_GET['date_from'])) {
    $where .= " AND DATE(al.created_at) >= ?";
    $params[] = sanitize($_GET['date_from']);
}

if (!empty($_GET['date_to'])) {
    $where .= " AND DATE(al.created_at) <= ?";
    $params[] = sanitize($_GET['date_to']);
}

// Get logs
$stmt = $db->prepare("
    SELECT al.*, u.first_name, u.last_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $where
    ORDER BY al.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for filter
$stmt = $db->query("SELECT id, first_name, last_name FROM users ORDER BY first_name");
$users = $stmt->fetchAll();

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Activity Logs</h1>
            <p class="text-slate-500 mt-1">System audit trail and activity history</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <select name="user_id" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= ($_GET['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                        <?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="action" value="<?= sanitize($_GET['action'] ?? '') ?>" placeholder="Action..." class="px-3 py-2 border border-slate-300 rounded-lg text-sm">

                <input type="date" name="date_from" value="<?= sanitize($_GET['date_from'] ?? '') ?>" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">

                <input type="date" name="date_to" value="<?= sanitize($_GET['date_to'] ?? '') ?>" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm">Filter</button>
                    <a href="logs.php" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm">Reset</a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <p class="text-sm text-slate-600"><span class="font-medium"><?= count($logs) ?></span> entries found</p>
            </div>
            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Details</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">No logs found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 text-sm text-slate-600 whitespace-nowrap">
                                <?= formatDateTime($log['created_at'], 'datetime') ?>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-800">
                                <?= sanitize($log['first_name'] ? $log['first_name'] . ' ' . $log['last_name'] : 'System') ?>
                            </td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-slate-100 text-slate-700">
                                    <?= sanitize($log['action']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600 max-w-xs truncate">
                                <?= sanitize($log['details'] ?: '-') ?>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-500 font-mono">
                                <?= sanitize($log['ip_address'] ?: '-') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once '../../templates/footer.php'; ?>
