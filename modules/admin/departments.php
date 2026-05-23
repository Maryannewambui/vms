<?php
/**
 * Department Management
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();

// Restrict admin access to Super Admin only
if ($_SESSION['user_role'] !== ROLE_SUPER_ADMIN) {
    http_response_code(403);
    die('Access Denied: Admin panel is restricted to Super Administrators only.');
}

requirePermission('manage_departments');

$pageTitle = 'Department Management';
$db = getDB();

// Get all departments
$stmt = $db->query("SELECT d.*, u.first_name as manager_name, u.last_name as manager_last FROM departments d LEFT JOIN users u ON d.manager_id = u.id ORDER BY d.name");
$departments = $stmt->fetchAll();

// Get users for manager selection
$stmt = $db->query("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY first_name");
$users = $stmt->fetchAll();

// Handle department actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO departments (name, code, floor, building, phone, email, manager_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            sanitize($_POST['name']),
            sanitize($_POST['code'] ?? ''),
            sanitize($_POST['floor'] ?? ''),
            sanitize($_POST['building'] ?? ''),
            sanitize($_POST['phone'] ?? ''),
            sanitize($_POST['email'] ?? ''),
            (int)($_POST['manager_id'] ?: null)
        ]);
        $message = 'Department created successfully.';
        logActivity('CREATE_DEPARTMENT', "Created department: {$_POST['name']}");
    }

    if ($action === 'update' && isset($_POST['dept_id'])) {
        $stmt = $db->prepare("UPDATE departments SET name = ?, code = ?, floor = ?, building = ?, phone = ?, email = ?, manager_id = ?, is_active = ? WHERE id = ?");
        $stmt->execute([
            sanitize($_POST['name']),
            sanitize($_POST['code'] ?? ''),
            sanitize($_POST['floor'] ?? ''),
            sanitize($_POST['building'] ?? ''),
            sanitize($_POST['phone'] ?? ''),
            sanitize($_POST['email'] ?? ''),
            (int)($_POST['manager_id'] ?: null),
            isset($_POST['is_active']) ? 1 : 0,
            (int)$_POST['dept_id']
        ]);
        $message = 'Department updated successfully.';
    }

    // Reload data
    $stmt = $db->query("SELECT d.*, u.first_name as manager_name, u.last_name as manager_last FROM departments d LEFT JOIN users u ON d.manager_id = u.id ORDER BY d.name");
    $departments = $stmt->fetchAll();
}

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Department Management</h1>
                <p class="text-slate-500 mt-1">Manage company departments and supervisors</p>
            </div>
            <button onclick="openModal('create')" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">+ Add Department</button>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700"><?= $message ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Manager</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($departments as $dept): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-800"><?= sanitize($dept['name']) ?></p>
                            <p class="text-xs text-slate-500"><?= sanitize($dept['code']) ?></p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?= sanitize(trim(($dept['building'] ?? '') . ' ' . ($dept['floor'] ?? ''))) ?: '-' ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <p><?= sanitize($dept['phone'] ?: '-') ?></p>
                            <p class="text-xs"><?= sanitize($dept['email'] ?: '') ?></p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?= sanitize($dept['manager_name'] ? $dept['manager_name'] . ' ' . $dept['manager_last'] : '-') ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= $dept['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $dept['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick='openModal("edit", <?= json_encode($dept) ?>)' class="text-primary-600 hover:text-primary-700 text-sm">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="dept-modal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg relative">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 id="modal-title" class="text-lg font-semibold text-slate-800">Add Department</h2>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="dept_id" id="dept-id" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name *</label>
                        <input type="text" name="name" id="dept-name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                        <input type="text" name="code" id="dept-code" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Building</label>
                        <input type="text" name="building" id="dept-building" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Floor</label>
                        <input type="text" name="floor" id="dept-floor" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" id="dept-phone" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" id="dept-email" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Manager</label>
                    <select name="manager_id" id="dept-manager" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <option value="">Select Manager</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="active-field" class="hidden">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="dept-active" checked class="w-4 h-4 text-primary-600 border-slate-300 rounded">
                        <span class="ml-2 text-sm text-slate-700">Active Department</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(mode, dept = null) {
    document.getElementById('modal-title').textContent = mode === 'create' ? 'Add Department' : 'Edit Department';
    document.getElementById('form-action').value = mode;
    document.getElementById('active-field').classList.toggle('hidden', mode === 'create');

    if (dept) {
        document.getElementById('dept-id').value = dept.id;
        document.getElementById('dept-name').value = dept.name;
        document.getElementById('dept-code').value = dept.code || '';
        document.getElementById('dept-building').value = dept.building || '';
        document.getElementById('dept-floor').value = dept.floor || '';
        document.getElementById('dept-phone').value = dept.phone || '';
        document.getElementById('dept-email').value = dept.email || '';
        document.getElementById('dept-manager').value = dept.manager_id || '';
        document.getElementById('dept-active').checked = dept.is_active == 1;
    } else {
        document.getElementById('dept-id').value = '';
        document.querySelector('form').reset();
    }

    document.getElementById('dept-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('dept-modal').classList.add('hidden');
}
</script>

<?php require_once '../../templates/footer.php'; ?>
