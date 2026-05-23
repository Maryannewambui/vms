<?php
/**
 * Incident Reports
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('view_incidents');

$pageTitle = 'Incident Reports';
$db = getDB();

// Handle incident actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasPermission('create_incident')) {
    verifyCSRF();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $db->prepare("
            INSERT INTO incident_reports (
                incident_uid, incident_type, severity, title, description,
                location, incident_date, incident_time, action_taken,
                status, reported_by, reported_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, NOW())
        ");
        $stmt->execute([
            generateUID('INC-'),
            sanitize($_POST['incident_type']),
            sanitize($_POST['severity']),
            sanitize($_POST['title']),
            sanitize($_POST['description']),
            sanitize($_POST['location'] ?? ''),
            sanitize($_POST['incident_date']),
            sanitize($_POST['incident_time'] ?? ''),
            sanitize($_POST['action_taken'] ?? ''),
            $_SESSION['user_id']
        ]);

        $message = 'Incident reported successfully.';
        logActivity('CREATE_INCIDENT', "Created incident: {$_POST['title']}");

        // Notify security
        $stmt = $db->query("SELECT id FROM users WHERE role_id = 3");
        $securityUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($securityUsers as $secUser) {
            sendNotification($secUser, 'incident', 'New Incident Reported', $_POST['title'], '/modules/security/incidents.php');
        }
    }

    if ($action === 'resolve' && isset($_POST['incident_id'])) {
        $stmt = $db->prepare("UPDATE incident_reports SET status = 'resolved', resolved_by = ?, resolved_at = NOW(), resolution_notes = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], sanitize($_POST['resolution_notes'] ?? ''), (int)$_POST['incident_id']]);
        $message = 'Incident marked as resolved.';
        logActivity('RESOLVE_INCIDENT', "Resolved incident ID: {$_POST['incident_id']}");
    }
}

// Get incidents
$statusFilter = sanitize($_GET['status'] ?? '');
$where = "WHERE 1=1";
$params = [];

if ($statusFilter) {
    $where .= " AND i.status = ?";
    $params[] = $statusFilter;
}

$stmt = $db->prepare("
    SELECT i.*, u.first_name as reporter_name, u.last_name as reporter_last
    FROM incident_reports i
    JOIN users u ON i.reported_by = u.id
    $where
    ORDER BY i.created_at DESC
");
$stmt->execute($params);
$incidents = $stmt->fetchAll();

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Incident Reports</h1>
                <p class="text-slate-500 mt-1">Security and safety incident management</p>
            </div>
            <?php if (hasPermission('create_incident')): ?>
            <button onclick="VMS.openModal('incident-modal')" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">+ Report Incident</button>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700"><?= $message ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
            <form method="GET" class="flex gap-4">
                <select name="status" class="px-4 py-2 border border-slate-300 rounded-lg text-sm">
                    <option value="">All Status</option>
                    <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="investigating" <?= $statusFilter === 'investigating' ? 'selected' : '' ?>>Investigating</option>
                    <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm">Filter</button>
                <a href="incidents.php" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm">Reset</a>
            </form>
        </div>

        <!-- Incidents Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Severity</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Reported By</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($incidents)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-slate-500">No incidents reported</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($incidents as $inc): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 text-sm font-mono text-slate-700"><?= sanitize($inc['incident_uid']) ?></td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= ucfirst(sanitize($inc['incident_type'])) ?></td>
                        <td class="px-6 py-4 font-medium text-slate-800"><?= sanitize($inc['title']) ?></td>
                        <td class="px-6 py-4">
                            <?php $severityColors = ['minor' => 'bg-green-100 text-green-700', 'moderate' => 'bg-yellow-100 text-yellow-700', 'major' => 'bg-orange-100 text-orange-700', 'critical' => 'bg-red-100 text-red-700']; ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $severityColors[$inc['severity']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst(sanitize($inc['severity'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($inc['location'] ?: '-') ?></td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= formatDate($inc['incident_date']) ?></td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= sanitize($inc['reporter_name'] . ' ' . $inc['reporter_last']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= $inc['status'] === 'open' ? 'bg-red-100 text-red-700' : ($inc['status'] === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= ucfirst(sanitize($inc['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($inc['status'] !== 'resolved' && hasPermission('create_incident')): ?>
                            <button onclick="showResolveModal(<?= $inc['id'] ?>)" class="text-green-600 hover:text-green-700 text-sm">Resolve</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Incident Modal -->
<div id="incident-modal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="VMS.closeModal('incident-modal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg relative">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-slate-800">Report Incident</h2>
                <button onclick="VMS.closeModal('incident-modal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Title *</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type *</label>
                        <select name="incident_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            <option value="security">Security</option>
                            <option value="safety">Safety</option>
                            <option value="behavior">Behavior</option>
                            <option value="unauthorized_access">Unauthorized Access</option>
                            <option value="theft">Theft</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Severity *</label>
                        <select name="severity" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            <option value="minor">Minor</option>
                            <option value="moderate">Moderate</option>
                            <option value="major">Major</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date *</label>
                        <input type="date" name="incident_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Time</label>
                        <input type="time" name="incident_time" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                    <input type="text" name="location" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="e.g., Main Gate, Production Floor">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description *</label>
                    <textarea name="description" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Describe what happened..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Action Taken</label>
                    <textarea name="action_taken" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Immediate actions taken..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="VMS.closeModal('incident-modal')" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Resolve Modal -->
<div id="resolve-modal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="document.getElementById('resolve-modal').classList.add('hidden')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-md relative">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Resolve Incident</h2>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="incident_id" id="resolve-incident-id">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Resolution Notes</label>
                    <textarea name="resolution_notes" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Describe how the incident was resolved..."></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('resolve-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg">Mark Resolved</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResolveModal(incidentId) {
    document.getElementById('resolve-incident-id').value = incidentId;
    document.getElementById('resolve-modal').classList.remove('hidden');
}
</script>

<?php require_once '../../templates/footer.php'; ?>
