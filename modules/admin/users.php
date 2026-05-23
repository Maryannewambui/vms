<?php
/**
 * User Management
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();

// Restrict admin access to Super Admin only
if ($_SESSION['user_role'] !== ROLE_SUPER_ADMIN) {
    http_response_code(403);
    die('Access Denied: Admin panel is restricted to Super Administrators only.');
}

requirePermission('manage_users');

$pageTitle = 'User Management';
$db = getDB();

// Get roles
$stmt = $db->query("SELECT * FROM roles ORDER BY id");
$roles = $stmt->fetchAll();

// Get departments
$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
$departments = $stmt->fetchAll();

// Handle user actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = createUser([
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'email' => sanitize($_POST['email']),
            'password' => $_POST['password'],
            'phone' => sanitize($_POST['phone'] ?? ''),
            'role_id' => (int)$_POST['role_id'],
            'department_id' => (int)($_POST['department_id'] ?: null),
            'employee_id' => sanitize($_POST['employee_id'] ?? '')
        ]);

        if ($result['success']) {
            $message = 'User created successfully.';
            logActivity('CREATE_USER', "Created user: {$_POST['email']}");
        } else {
            $error = $result['error'];
        }
    }

    if ($action === 'update' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        $result = updateUser($userId, [
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'email' => sanitize($_POST['email']),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'role_id' => (int)$_POST['role_id'],
            'department_id' => (int)($_POST['department_id'] ?: null),
            'employee_id' => sanitize($_POST['employee_id'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ]);

        if ($result['success']) {
            $message = 'User updated successfully.';
        } else {
            $error = $result['error'];
        }
    }

    if ($action === 'reset_password' && isset($_POST['user_id'])) {
        $newPassword = generatePassword(12);
        $result = resetPassword((int)$_POST['user_id'], $newPassword);

        if ($result['success']) {
            $message = "Password reset successfully. New password: $newPassword";
        } else {
            $error = $result['error'];
        }
    }
}

// Get users
$users = getUsers(['is_active' => null]); // Get all users including inactive

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">User Management</h1>
                <p class="text-slate-500 mt-1">Manage system users and permissions</p>
            </div>
            <button onclick="openCreateModal()" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">
                + Add User
            </button>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Employee ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Last Login</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-medium mr-3">
                                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= sanitize($user['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($user['employee_id'] ?: '-') ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($user['role_name']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($user['department_name'] ?: '-') ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full <?= $user['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= $user['last_login'] ? timeAgo($user['last_login']) : 'Never' ?>
                            </td>
                            <td class="px-6 py-4 space-x-2">
                                <button onclick='openEditModal(<?= json_encode($user) ?>)' class="text-primary-600 hover:text-primary-700 text-sm">Edit</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Reset password for this user?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-700 text-sm">Reset Password</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- User Modal -->
<div id="user-modal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg relative">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 id="modal-title" class="text-lg font-semibold text-slate-800">Add User</h2>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" id="user-form" class="p-6 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="user_id" id="user-id" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">First Name *</label>
                        <input type="text" name="first_name" id="first-name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Last Name *</label>
                        <input type="text" name="last_name" id="last-name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                    <input type="email" name="email" id="email" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Employee ID</label>
                        <input type="text" name="employee_id" id="employee-id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" id="phone" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Role *</label>
                        <select name="role_id" id="role-id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= sanitize($role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                        <select name="department_id" id="department-id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= sanitize($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="password-field">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password *</label>
                    <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>

                <div id="active-field" class="hidden">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="is-active" checked class="w-4 h-4 text-primary-600 border-slate-300 rounded">
                        <span class="ml-2 text-sm text-slate-700">Active User</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modal-title').textContent = 'Add User';
    document.getElementById('form-action').value = 'create';
    document.getElementById('user-id').value = '';
    document.getElementById('user-form').reset();
    document.getElementById('password-field').classList.remove('hidden');
    document.getElementById('password').required = true;
    document.getElementById('active-field').classList.add('hidden');

    document.getElementById('user-modal').classList.remove('hidden');
}

function openEditModal(user) {
    document.getElementById('modal-title').textContent = 'Edit User';
    document.getElementById('form-action').value = 'update';
    document.getElementById('user-id').value = user.id;
    document.getElementById('first-name').value = user.first_name;
    document.getElementById('last-name').value = user.last_name;
    document.getElementById('email').value = user.email;
    document.getElementById('employee-id').value = user.employee_id || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('role-id').value = user.role_id;
    document.getElementById('department-id').value = user.department_id || '';

    document.getElementById('password-field').classList.add('hidden');
    document.getElementById('password').required = false;
    document.getElementById('active-field').classList.remove('hidden');
    document.getElementById('is-active').checked = user.is_active == 1;

    document.getElementById('user-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('user-modal').classList.add('hidden');
}
</script>

<?php require_once '../../templates/footer.php'; ?>
