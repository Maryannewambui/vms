<?php
/**
 * System Settings
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();

// Restrict admin access to Super Admin only
if ($_SESSION['user_role'] !== ROLE_SUPER_ADMIN) {
    http_response_code(403);
    die('Access Denied: Admin panel is restricted to Super Administrators only.');
}

requirePermission('manage_settings');

$pageTitle = 'System Settings';
$db = getDB();

// Get all settings
$stmt = $db->query("SELECT * FROM settings ORDER BY setting_key");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle updates
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8); // Remove 'setting_' prefix
            setSetting($settingKey, sanitize($value));
        }
    }

    logActivity('UPDATE_SETTINGS', 'System settings updated');
    $message = 'Settings updated successfully.';

    // Reload settings
    $stmt = $db->query("SELECT * FROM settings ORDER BY setting_key");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">System Settings</h1>
            <p class="text-slate-500 mt-1">Configure the visitor management system</p>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <?= csrfField() ?>

            <!-- Company Information -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Company Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Company Name</label>
                        <input type="text" name="setting_company_name" value="<?= sanitize($settings['company_name'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Company Email</label>
                        <input type="email" name="setting_company_email" value="<?= sanitize($settings['company_email'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Company Phone</label>
                        <input type="text" name="setting_company_phone" value="<?= sanitize($settings['company_phone'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Company Address</label>
                        <input type="text" name="setting_company_address" value="<?= sanitize($settings['company_address'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Working Hours -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Working Hours</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Work Start Time</label>
                        <input type="time" name="setting_work_start_time" value="<?= sanitize($settings['work_start_time'] ?? '08:00') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Work End Time</label>
                        <input type="time" name="setting_work_end_time" value="<?= sanitize($settings['work_end_time'] ?? '17:00') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Badge Settings -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Badge & Pass Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Badge Validity (Hours)</label>
                        <input type="number" name="setting_badge_expiry_hours" value="<?= sanitize($settings['badge_expiry_hours'] ?? 8) ?>" min="1" max="24" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Overdue Threshold (Minutes)</label>
                        <input type="number" name="setting_overdue_minutes" value="<?= sanitize($settings['overdue_minutes'] ?? 30) ?>" min="0" max="120" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Notification Settings</h2>
                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="setting_notify_host_on_arrival" value="true" <?= ($settings['notify_host_on_arrival'] ?? '') === 'true' ? 'checked' : '' ?> class="w-4 h-4 text-primary-600 border-slate-300 rounded">
                        <span class="ml-2 text-sm text-slate-700">Notify host on visitor arrival</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="setting_notify_security_on_checkin" value="true" <?= ($settings['notify_security_on_checkin'] ?? '') === 'true' ? 'checked' : '' ?> class="w-4 h-4 text-primary-600 border-slate-300 rounded">
                        <span class="ml-2 text-sm text-slate-700">Notify security on check-in</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="setting_enable_email_notifications" value="true" <?= ($settings['enable_email_notifications'] ?? '') === 'true' ? 'checked' : '' ?> class="w-4 h-4 text-primary-600 border-slate-300 rounded">
                        <span class="ml-2 text-sm text-slate-700">Enable email notifications</span>
                    </label>
                </div>
            </div>

            <!-- Terms & Conditions -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Terms & Conditions</h2>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Terms Text</label>
                    <textarea name="setting_terms_text" rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500"><?= sanitize($settings['terms_text'] ?? '') ?></textarea>
                    <p class="text-xs text-slate-500 mt-1">This text will be shown to visitors during check-in</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</main>

<?php require_once '../../templates/footer.php'; ?>
