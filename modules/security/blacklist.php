<?php
/**
 * Blacklist Management
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('manage_blacklist');

$pageTitle = 'Blacklist Management';
$db = getDB();

// Handle add to blacklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCSRF();

    $action = $_POST['action'];

    if ($action === 'add') {
        $stmt = $db->prepare("
            INSERT INTO blacklist (visitor_id, first_name, last_name, id_number, email, phone, company, reason, severity, added_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)($_POST['visitor_id'] ?: null),
            sanitize($_POST['first_name']),
            sanitize($_POST['last_name']),
            sanitize($_POST['id_number']),
            sanitize($_POST['email']),
            sanitize($_POST['phone']),
            sanitize($_POST['company']),
            sanitize($_POST['reason']),
            sanitize($_POST['severity']),
            $_SESSION['user_id']
        ]);

        logActivity('ADD_BLACKLIST', "Added {$$_POST['first_name']} {$_POST['last_name']} to blacklist");
        $_SESSION['success'] = 'Entry added to blacklist.';
        header('Location: blacklist.php');
        exit();
    }

    if ($action === 'remove' && isset($_POST['blacklist_id'])) {
        $stmt = $db->prepare("UPDATE blacklist SET status = 'removed', removed_by = ?, removed_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], (int)$_POST['blacklist_id']]);

        logActivity('REMOVE_BLACKLIST', "Removed blacklist entry ID: {$_POST['blacklist_id']}");
        $_SESSION['success'] = 'Entry removed from blacklist.';
        header('Location: blacklist.php');
        exit();
    }
}

// Get blacklist entries
$stmt = $db->query("
    SELECT b.*, u.first_name as added_by_name, u.last_name as added_by_last,
           vis.first_name as visitor_first, vis.last_name as visitor_last
    FROM blacklist b
    LEFT JOIN users u ON b.added_by = u.id
    LEFT JOIN visitors vis ON b.visitor_id = vis.id
    WHERE b.status = 'active'
    ORDER BY b.added_at DESC
");
$blacklist = $stmt->fetchAll();

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
                <h1 class="text-2xl font-bold text-slate-800">Blacklist Management</h1>
                <p class="text-slate-500 mt-1">Manage denied visitors and security alerts</p>
            </div>
            <button onclick="VMS.openModal('add-modal')" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                + Add to Blacklist
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Blacklist Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <p class="text-sm text-slate-600">
                    <span class="font-medium"><?= count($blacklist) ?></span> active entries
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">ID Number</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Added By</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($blacklist)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                No active blacklist entries
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($blacklist as $entry): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium text-slate-800">
                                <?= sanitize($entry['first_name'] . ' ' . $entry['last_name']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($entry['id_number']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($entry['company']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <span class="line-clamp-2"><?= sanitize($entry['reason']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $severityColors = ['low' => 'bg-green-100 text-green-700', 'medium' => 'bg-yellow-100 text-yellow-700', 'high' => 'bg-orange-100 text-orange-700', 'critical' => 'bg-red-100 text-red-700'];
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $severityColors[$entry['severity']] ?? 'bg-gray-100 text-gray-700' ?>">
                                    <?= ucfirst(sanitize($entry['severity'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= sanitize($entry['added_by_name'] . ' ' . $entry['added_by_last']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= formatDate($entry['added_at']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline" onsubmit="return confirm('Remove this entry from blacklist?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="blacklist_id" value="<?= $entry['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-700 text-sm">
                                        Remove
                                    </button>
                                </form>
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

<!-- Add Modal -->
<div id="add-modal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="VMS.closeModal('add-modal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg relative">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-slate-800">Add to Blacklist</h2>
                <button onclick="VMS.closeModal('add-modal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="visitor_id" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">First Name *</label>
                        <input type="text" name="first_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Last Name *</label>
                        <input type="text" name="last_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ID Number</label>
                    <input type="text" name="id_number" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company</label>
                    <input type="text" name="company" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Severity</label>
                    <select name="severity" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Reason *</label>
                    <textarea name="reason" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Explain why this visitor is blacklisted..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="VMS.closeModal('add-modal')" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Add to Blacklist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
