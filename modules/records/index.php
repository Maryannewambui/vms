<?php
/**
 * Visitor Records
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('view_records');

$pageTitle = 'Visitor Records';
$db = getDB();

// Filters
$filters = [];
$where = "WHERE 1=1";
$params = [];

if (!empty($_GET['date_from'])) {
    $where .= " AND v.visit_date >= ?";
    $params[] = sanitize($_GET['date_from']);
}

if (!empty($_GET['date_to'])) {
    $where .= " AND v.visit_date <= ?";
    $params[] = sanitize($_GET['date_to']);
}

if (!empty($_GET['department_id'])) {
    $where .= " AND v.department_id = ?";
    $params[] = (int)$_GET['department_id'];
}

if (isset($_GET['type']) && $_GET['type']) {
    $where .= " AND v.category_id = ?";
    $params[] = (int)$_GET['type'];
}

if (!empty($_GET['status'])) {
    $where .= " AND v.visit_status = ?";
    $params[] = sanitize($_GET['status']);
}

if (!empty($_GET['search'])) {
    $where .= " AND (vis.first_name LIKE ? OR vis.last_name LIKE ? OR vis.company LIKE ? OR v.badge_number LIKE ?)";
    $search = '%' . sanitize($_GET['search']) . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

// Count total
$stmt = $db->prepare("
    SELECT COUNT(*) FROM visits v
    JOIN visitors vis ON v.visitor_id = vis.id
    $where
");
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$pagination = getPagination($totalRecords, $page);
$offset = $pagination['offset'];

// Get records
$sql = "
    SELECT v.*, vis.first_name, vis.last_name, vis.company, vis.phone, vis.email,
           vc.name as category_name, vc.id as category_id,
           u.first_name as host_name, u.last_name as host_last,
           d.name as department_name,
           checker.first_name as checked_in_by_name,
           checkout_checker.first_name as checked_out_by_name
    FROM visits v
    JOIN visitors vis ON v.visitor_id = vis.id
    JOIN visitor_categories vc ON v.category_id = vc.id
    JOIN users u ON v.host_user_id = u.id
    LEFT JOIN departments d ON v.department_id = d.id
    LEFT JOIN users checker ON v.checked_in_by = checker.id
    LEFT JOIN users checkout_checker ON v.checked_out_by = checkout_checker.id
    $where
    ORDER BY v.visit_date DESC, v.actual_check_in DESC
    LIMIT $offset, {$pagination['per_page']}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get departments for filter
$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
$departments = $stmt->fetchAll();

// Get categories for filter
$stmt = $db->query("SELECT * FROM visitor_categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Export functionality
if (isset($_GET['export'])) {
    $exportFormat = sanitize($_GET['export']);

    // Get all records for export (no pagination)
    $stmt = $db->prepare(str_replace("LIMIT $offset, {$pagination['per_page']}", "", $sql));
    $stmt->execute($params);
    $exportRecords = $stmt->fetchAll();

    if ($exportFormat === 'csv') {
        $headers = ['Visit ID', 'Visitor Name', 'Company', 'Category', 'Department', 'Host', 'Visit Date', 'Check In', 'Check Out', 'Duration', 'Status', 'Badge Number'];
        $data = [];
        foreach ($exportRecords as $r) {
            $duration = '-';
            if ($r['actual_check_in'] && $r['actual_check_out']) {
                $in = new DateTime($r['actual_check_in']);
                $out = new DateTime($r['actual_check_out']);
                $dur = $in->diff($out);
                $duration = $dur->format('%h hours %i min');
            }

            $data[] = [
                $r['visit_uid'],
                $r['first_name'] . ' ' . $r['last_name'],
                $r['company'],
                $r['category_name'],
                $r['department_name'],
                $r['host_name'],
                $r['visit_date'],
                $r['actual_check_in'] ?? '-',
                $r['actual_check_out'] ?? '-',
                $duration,
                $r['visit_status'],
                $r['badge_number'] ?? '-'
            ];
        }

        exportCSV($data, 'visitor_records_' . date('Ymd') . '.csv', $headers);
    }
}

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <!-- Page Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Visitor Records</h1>
                <p class="text-slate-500 mt-1">View and manage all visitor records</p>
            </div>
            <?php if (hasPermission('export_records')): ?>
            <div class="flex gap-2">
                <a href="?<?= http_build_query($_GET) ?>&export=csv" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export CSV
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                    <input type="text" name="search" value="<?= sanitize($_GET['search'] ?? '') ?>" placeholder="Name, company, badge..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
                    <input type="date" name="date_from" value="<?= sanitize($_GET['date_from'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
                    <input type="date" name="date_to" value="<?= sanitize($_GET['date_to'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Department</label>
                    <select name="department_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($_GET['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= sanitize($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Status</option>
                        <option value="checked_in" <?= ($_GET['status'] ?? '') == 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                        <option value="checked_out" <?= ($_GET['status'] ?? '') == 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                        <option value="pre_registered" <?= ($_GET['status'] ?? '') == 'pre_registered' ? 'selected' : '' ?>>Pre-Registered</option>
                        <option value="overdue" <?= ($_GET['status'] ?? '') == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors text-sm">
                        Filter
                    </button>
                    <a href="?" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors text-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Records Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <p class="text-sm text-slate-600">
                    Showing <span class="font-medium"><?= count($records) ?></span> of <span class="font-medium"><?= $totalRecords ?></span> records
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Visitor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Host / Dept</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Visit Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Check-In</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Check-Out</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p>No records found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($records as $record): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-medium mr-3">
                                        <?= strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800"><?= sanitize($record['first_name'] . ' ' . $record['last_name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= sanitize($record['phone']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($record['company']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($record['category_name']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <p><?= sanitize($record['host_name'] . ' ' . $record['host_last']) ?></p>
                                <p class="text-xs text-slate-500"><?= sanitize($record['department_name']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= formatDate($record['visit_date']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= $record['actual_check_in'] ? formatDateTime($record['actual_check_in']) : '-' ?>
                                <?php if ($record['checked_in_by_name']): ?>
                                <p class="text-xs text-slate-400">by <?= sanitize($record['checked_in_by_name']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= $record['actual_check_out'] ? formatDateTime($record['actual_check_out']) : '-' ?>
                                <?php if ($record['actual_check_in'] && $record['actual_check_out']): ?>
                                <?php
                                $in = new DateTime($record['actual_check_in']);
                                $out = new DateTime($record['actual_check_out']);
                                $dur = $in->diff($out);
                                ?>
                                <p class="text-xs text-slate-400"><?= $dur->format('%h h %i min') ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= getStatusBadgeClass($record['visit_status']) ?>">
                                    <?= ucwords(str_replace('_', ' ', sanitize($record['visit_status']))) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="detail.php?id=<?= $record['id'] ?>" class="text-primary-600 hover:text-primary-700 text-sm">
                                    View
                                </a>
                                <?php if ($record['visit_status'] === 'checked_in' && hasPermission('checkout_visitor')): ?>
                                <a href="../checkout/index.php?id=<?= $record['id'] ?>" class="ml-2 text-red-600 hover:text-red-700 text-sm">
                                    Check Out
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="px-6 py-4 border-t border-slate-200 flex justify-between items-center">
                <p class="text-sm text-slate-600">
                    Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                </p>
                <div class="flex gap-2">
                    <?php if ($pagination['has_prev']): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="px-3 py-1 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 text-sm">
                        Previous
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-1 <?= $i == $pagination['current_page'] ? 'bg-primary-500 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?> rounded-lg text-sm">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="px-3 py-1 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 text-sm">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once '../../templates/footer.php'; ?>
