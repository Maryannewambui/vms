<?php
/**
 * Emergency Evacuation Management
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('manage_blacklist');

$pageTitle = 'Emergency Evacuation';
$db = getDB();

$message = '';

// Check for active evacuation
$stmt = $db->prepare("SELECT * FROM emergency_evacuations WHERE status = 'active' ORDER BY evacuation_time DESC LIMIT 1");
$stmt->execute();
$activeEvacuation = $stmt->fetch();

// Handle evacuation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();

    $action = $_POST['action'] ?? '';

    if ($action === 'start') {
        // Start new evacuation
        $stmt = $db->prepare("INSERT INTO emergency_evacuations (evacuation_type, assembly_point, initiated_by, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([
            sanitize($_POST['evacuation_type'] ?? 'other'),
            sanitize($_POST['assembly_point'] ?? ''),
            $_SESSION['user_id']
        ]);
        $evacId = $db->lastInsertId();

        // Add all current visitors
        $stmt = $db->prepare("
            INSERT INTO evacuation_checks (evacuation_id, visit_id, status)
            SELECT ?, id, 'unaccounted'
            FROM visits WHERE visit_status = 'checked_in'
        ");
        $stmt->execute([$evacId]);

        logActivity('EVACUATION_START', "Emergency evacuation initiated");
        $message = 'Emergency evacuation started! All visitors must be accounted for.';

        // Refresh active evacuation
        $stmt->execute();
        $activeEvacuation = $stmt->fetch();
    }

    if ($action === 'mark_safe' && isset($_POST['check_id'])) {
        $stmt = $db->prepare("UPDATE evacuation_checks SET status = 'safe', marked_by = ?, marked_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], (int)$_POST['check_id']]);
        $message = 'Visitor marked as safe.';
    }

    if ($action === 'mark_missing' && isset($_POST['check_id'])) {
        $stmt = $db->prepare("UPDATE evacuation_checks SET status = 'missing', marked_by = ?, marked_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], (int)$_POST['check_id']]);
        $message = 'Visitor marked as missing.';
    }

    if ($action === 'all_clear' && isset($_POST['evac_id'])) {
        $stmt = $db->prepare("UPDATE emergency_evacuations SET status = 'all_clear', cleared_by = ?, cleared_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], (int)$_POST['evac_id']]);
        logActivity('EVACUATION_CLEAR', "Emergency evacuation cleared");
        $message = 'All clear declared.';

        $activeEvacuation = null;
    }

    if ($action === 'cancel' && isset($_POST['evac_id'])) {
        $stmt = $db->prepare("DELETE FROM emergency_evacuations WHERE id = ?");
        $stmt->execute([(int)$_POST['evac_id']]);
        $stmt = $db->prepare("DELETE FROM evacuation_checks WHERE evacuation_id = ?");
        $stmt->execute([(int)$_POST['evac_id']]);
        $message = 'Evacuation cancelled (drill).';
        $activeEvacuation = null;
    }
}

// Get evacuation statistics
if ($activeEvacuation) {
    $stmt = $db->prepare("
        SELECT ec.*, vis.first_name, vis.last_name, vis.company, vis.phone,
               v.actual_check_in, d.name as department_name
        FROM evacuation_checks ec
        JOIN visits v ON ec.visit_id = v.id
        JOIN visitors vis ON v.visitor_id = vis.id
        LEFT JOIN departments d ON v.department_id = d.id
        WHERE ec.evacuation_id = ?
        ORDER BY ec.status, vis.first_name
    ");
    $stmt->execute([$activeEvacuation['id']]);
    $evacuees = $stmt->fetchAll();

    $safeCount = count(array_filter($evacuees, fn($e) => $e['status'] === 'safe'));
    $missingCount = count(array_filter($evacuees, fn($e) => $e['status'] === 'missing'));
    $unaccountedCount = count(array_filter($evacuees, fn($e) => $e['status'] === 'unaccounted'));
}

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Emergency Evacuation</h1>
            <p class="text-slate-500 mt-1">Manage emergency evacuations and account for visitors</p>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-700"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($activeEvacuation): ?>
        <!-- Active Evacuation Screen -->
        <div class="bg-red-600 text-white rounded-xl p-6 mb-6 animate-pulse">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-12 h-12 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h2 class="text-2xl font-bold">EMERGENCY EVACUATION IN PROGRESS</h2>
                        <p class="opacity-90">Started: <?= formatDateTime($activeEvacuation['evacuation_time']) ?></p>
                        <p class="opacity-90">Type: <?= ucfirst(sanitize($activeEvacuation['evacuation_type'])) ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold"><?= count($evacuees ?? []) ?></p>
                    <p class="text-sm opacity-90">Visitors to account for</p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-green-500 text-white rounded-xl p-4 text-center">
                <p class="text-3xl font-bold"><?= $safeCount ?? 0 ?></p>
                <p class="text-sm opacity-90">Safe</p>
            </div>
            <div class="bg-yellow-500 text-white rounded-xl p-4 text-center">
                <p class="text-3xl font-bold"><?= $unaccountedCount ?? 0 ?></p>
                <p class="text-sm opacity-90">Unaccounted</p>
            </div>
            <div class="bg-red-700 text-white rounded-xl p-4 text-center">
                <p class="text-3xl font-bold"><?= $missingCount ?? 0 ?></p>
                <p class="text-sm opacity-90">Missing</p>
            </div>
            <div class="bg-blue-600 text-white rounded-xl p-4 text-center">
                <p class="text-3xl font-bold"><?= count($evacuees ?? []) ?></p>
                <p class="text-sm opacity-90">Total</p>
            </div>
        </div>

        <!-- Visitor List -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Visitor Evacuation Check</h2>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Visitor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Location (if safe)</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($evacuees ?? [] as $e): ?>
                        <tr class="hover:bg-slate-50 <?= $e['status'] === 'missing' ? 'bg-red-50' : ($e['status'] === 'safe' ? 'bg-green-50' : '') ?>">
                            <td class="px-6 py-4 font-medium text-slate-800"><?= sanitize($e['first_name'] . ' ' . $e['last_name']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($e['company'] ?: '-') ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full font-medium <?= $e['status'] === 'safe' ? 'bg-green-100 text-green-700' : ($e['status'] === 'missing' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                    <?= ucfirst($e['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($e['department_name'] ?: '-') ?></td>
                            <td class="px-6 py-4 space-x-2">
                                <?php if ($e['status'] === 'unaccounted'): ?>
                                <form method="POST" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="mark_safe">
                                    <input type="hidden" name="check_id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600">Safe</button>
                                </form>
                                <form method="POST" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="mark_missing">
                                    <input type="hidden" name="check_id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600">Missing</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <form method="POST" class="inline" onsubmit="return confirm('Declare ALL CLEAR? All visitors have been accounted for.')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="all_clear">
                <input type="hidden" name="evac_id" value="<?= $activeEvacuation['id'] ?>">
                <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Declare All Clear
                </button>
            </form>
            <form method="POST" class="inline" onsubmit="return confirm('Cancel evacuation? This was a drill.')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="evac_id" value="<?= $activeEvacuation['id'] ?>">
                <button type="submit" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                    Cancel (Drill)
                </button>
            </form>
        </div>

        <?php else: ?>
        <!-- No Active Evacuation -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center mb-6">
            <svg class="w-16 h-16 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="text-xl font-semibold text-green-800 mb-2">No Active Evacuation</h2>
            <p class="text-green-600">All clear. No emergency evacuation in progress.</p>
        </div>

        <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="start">

            <h2 class="text-lg font-semibold text-slate-800 mb-4">Start Emergency Evacuation</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Evacuation Type</label>
                    <select name="evacuation_type" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                        <option value="fire">Fire</option>
                        <option value="chemical">Chemical Spill</option>
                        <option value="security_threat">Security Threat</option>
                        <option value="drill">Evacuation Drill</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Assembly Point</label>
                    <input type="text" name="assembly_point" class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="e.g., North Parking Lot">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Start EMERGENCY EVACUATION? This will alert all personnel.')">
                        Start Evacuation
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../../templates/footer.php'; ?>
