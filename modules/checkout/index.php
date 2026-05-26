<?php
/**
 * Visitor Check-Out System
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('checkout_visitor');

$pageTitle = 'Check-Out Visitor';
$db = getDB();

$visitId = (int)($_GET['id'] ?? 0);
$visit = null;
$error = '';
$success = '';

// Get visit details
if ($visitId) {
    $stmt = $db->prepare("
        SELECT v.*, vis.first_name, vis.last_name, vis.company, vis.email, vis.phone,
               vis.id as visitor_id, vc.name as category_name,
               u.first_name as host_name, u.last_name as host_last,
               d.name as department_name, vp.pass_number, vp.qr_code
        FROM visits v
        JOIN visitors vis ON v.visitor_id = vis.id
        LEFT JOIN visitor_categories vc ON v.category_id = vc.id
        LEFT JOIN users u ON v.host_user_id = u.id
        LEFT JOIN departments d ON v.department_id = d.id
        LEFT JOIN visitor_passes vp ON vp.visit_id = v.id
        WHERE v.id = ?
    ");
    $stmt->execute([$visitId]);
    $visit = $stmt->fetch();
    if (!$visit) {
        $error = 'Selected visit could not be found or may already be checked out.';
    }}

// Handle check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $visit) {
    verifyCSRF();

    try {
        $db->beginTransaction();

        // Calculate duration
        $actualCheckIn = new DateTime($visit['actual_check_in']);
        $actualCheckOut = new DateTime();
        $duration = $actualCheckIn->diff($actualCheckOut);
        $durationMinutes = ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i;
        $durationStr = $duration->format('%h hours %i minutes');

        // Update visit record
        $stmt = $db->prepare("
            UPDATE visits SET
                visit_status = 'checked_out',
                actual_check_out = NOW(),
                duration_minutes = ?,
                checked_out_by = ?,
                badge_returned = ?,
                badge_returned_at = CASE WHEN ? = 1 THEN NOW() ELSE NULL END,
                visit_rating = ?,
                visit_feedback = ?,
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $durationMinutes,
            $_SESSION['user_id'],
            isset($_POST['badge_returned']) ? 1 : 0,
            isset($_POST['badge_returned']) ? 1 : 0,
            (int)($_POST['rating'] ?? 0),
            sanitize($_POST['feedback'] ?? ''),
            sanitize($_POST['notes'] ?? ''),
            $visitId
        ]);

        // Create checkout record for audit trail
        $checkoutUID = generateUID('CHECKOUT-');
        $stmt = $db->prepare("
            INSERT INTO checkout_records (
                visit_id, checkout_uid, visitor_id, checked_out_by,
                check_in_time, check_out_time, duration_minutes,
                badge_returned, badge_returned_at, security_notes,
                visit_rating, visit_feedback, additional_notes, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $visitId,
            $checkoutUID,
            $visit['visitor_id'],
            $_SESSION['user_id'],
            $visit['actual_check_in'],
            $durationMinutes,
            isset($_POST['badge_returned']) ? 1 : 0,
            isset($_POST['badge_returned']) ? date('Y-m-d H:i:s') : null,
            sanitize($_POST['notes'] ?? ''),
            (int)($_POST['rating'] ?? 0),
            sanitize($_POST['feedback'] ?? ''),
            sanitize($_POST['notes'] ?? '')
        ]);

        // Update visitor pass - deactivate
        $stmt = $db->prepare("
            UPDATE visitor_passes SET
                is_active = 0
            WHERE visit_id = ?
        ");
        $stmt->execute([$visitId]);

        // Send notification to host
        sendNotification(
            $visit['host_user_id'],
            'visitor_checkout',
            'Visitor Checked Out',
            sanitize($visit['first_name'] . ' ' . $visit['last_name']) . ' has checked out. Duration: ' . $durationStr,
            'modules/checkout/index.php'
        );

        $db->commit();
        logActivity('VISITOR_CHECKOUT', "Visitor {$visit['first_name']} {$visit['last_name']} checked out - Duration: {$durationStr}", $_SESSION['user_id']);

        $_SESSION['checkout_success'] = true;
        $_SESSION['checkout_data'] = [
            'visitor_name' => sanitize($visit['first_name'] . ' ' . $visit['last_name']),
            'company' => sanitize($visit['company']),
            'duration' => $durationStr,
            'badge_returned' => isset($_POST['badge_returned']) ? 'Yes' : 'No',
            'rating' => (int)($_POST['rating'] ?? 0)
        ];
        header('Location: ./index.php?checkout_success=1');
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Check-out failed: ' . $e->getMessage();
        logActivity('CHECKOUT_ERROR', "Checkout failed: " . $e->getMessage(), $_SESSION['user_id']);
    }
}

// Get currently checked-in visitors
$stmt = $db->query("
    SELECT v.id, v.actual_check_in, v.visit_uid,
           vis.first_name, vis.last_name, vis.company, vis.phone,
           vc.name as category_name,
           u.first_name as host_name, u.last_name as host_last,
           d.name as department_name,
           TIMESTAMPDIFF(MINUTE, v.actual_check_in, NOW()) as duration_minutes,
           CASE WHEN TIMESTAMPDIFF(MINUTE, v.actual_check_in, NOW()) > 240 THEN 1 ELSE 0 END as is_overdue
    FROM visits v
    JOIN visitors vis ON v.visitor_id = vis.id
    JOIN visitor_categories vc ON v.category_id = vc.id
    JOIN users u ON v.host_user_id = u.id
    LEFT JOIN departments d ON v.department_id = d.id
    WHERE v.visit_status = 'checked_in'
    ORDER BY v.actual_check_in ASC
");
$checkedInVisitors = $stmt->fetchAll();

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Visitor Check-Out</h1>
            <p class="text-slate-500 mt-1">Check out visitors and complete visit records</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Checkout Success Message -->
        <?php if (isset($_GET['checkout_success']) && isset($_SESSION['checkout_data'])): ?>
        <div class="mb-6 p-6 bg-green-50 border-2 border-green-200 rounded-xl shadow-sm">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-green-800">Visitor Checked Out Successfully</h3>
            </div>
            <?php $data = $_SESSION['checkout_data']; ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white p-4 rounded-lg">
                <div>
                    <p class="text-xs text-slate-500 uppercase">Visitor Name</p>
                    <p class="font-semibold text-slate-800"><?= $data['visitor_name'] ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase">Company</p>
                    <p class="font-semibold text-slate-800"><?= $data['company'] ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase">Duration</p>
                    <p class="font-semibold text-green-600"><?= $data['duration'] ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase">Badge Returned</p>
                    <p class="font-semibold text-slate-800"><?= $data['badge_returned'] ?></p>
                </div>
            </div>
            <?php if ($data['rating'] > 0): ?>
            <div class="mt-4 flex items-center">
                <p class="text-sm text-slate-600 mr-3">Visitor Rating:</p>
                <div class="flex space-x-1">
                    <?php for ($i = 0; $i < $data['rating']; $i++): ?>
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <button onclick="window.location='?'" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Check Out Another Visitor
            </button>
        </div>
        <?php unset($_SESSION['checkout_data']); endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Check-Out Form or Visitor List -->
            <div class="lg:col-span-2">
                <?php if ($visit): ?>
                <!-- Check-Out Form -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-xl font-bold mr-4">
                            <?= strtoupper(substr($visit['first_name'], 0, 1) . substr($visit['last_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800"><?= sanitize($visit['first_name'] . ' ' . $visit['last_name']) ?></h2>
                            <p class="text-slate-500"><?= sanitize($visit['company']) ?> - <?= sanitize($visit['category_name']) ?></p>
                        </div>
                    </div>

                    <!-- Visit Details -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-slate-50 rounded-lg">
                        <div>
                            <p class="text-xs text-slate-500">Check-in Time</p>
                            <p class="font-medium text-slate-800"><?= formatDateTime($visit['actual_check_in']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Duration</p>
                            <p class="font-medium text-slate-800">
                                <?php
                                $checkIn = new DateTime($visit['actual_check_in']);
                                $now = new DateTime();
                                $diff = $checkIn->diff($now);
                                echo $diff->format('%h hours %i minutes');
                                ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Host</p>
                            <p class="font-medium text-slate-800"><?= sanitize($visit['host_name'] . ' ' . $visit['host_last']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Pass Number</p>
                            <p class="font-medium text-slate-800"><?= sanitize($visit['pass_number']) ?></p>
                        </div>
                    </div>

                    <form method="POST" action="?id=<?= $visitId ?>">
                        <?= csrfField() ?>

                        <!-- Badge Return -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="badge_returned" checked class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500">
                                <span class="ml-2 text-sm text-slate-700">Visitor badge returned</span>
                            </label>
                        </div>

                        <!-- Security Notes -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Security Notes</label>
                            <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Any security observations or notes..."></textarea>
                        </div>

                        <!-- Visitor Feedback -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Visitor Feedback (Optional)</label>
                            <div class="flex space-x-2 mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" onclick="setRating(<?= $i ?>)" class="rating-star w-8 h-8 text-slate-300 hover:text-yellow-400 focus:outline-none" data-rating="<?= $i ?>">
                                    <svg fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="rating-input" value="0">
                            <textarea name="feedback" rows="2" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Any feedback from the visitor..."></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end space-x-4">
                            <a href="?search" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Check Out Visitor
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <!-- Visitor List -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-800">Currently Checked-In (<?= count($checkedInVisitors) ?>)</h2>
                    </div>

                    <!-- Search -->
                    <div class="px-6 py-4 border-b border-slate-200">
                        <input type="text" id="search-input" placeholder="Search by name, company, or pass number..." class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                        <?php if (empty($checkedInVisitors)): ?>
                        <div class="p-8 text-center text-slate-500">
                            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="font-medium">No visitors currently checked in</p>
                            <p class="text-sm mt-1">All visitors have been checked out</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($checkedInVisitors as $visitor): ?>
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50 visitor-row" data-searchable="<?= strtolower(sanitize($visitor['first_name'] . ' ' . $visitor['last_name'] . ' ' . $visitor['company'])) ?>">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-medium mr-4">
                                    <?= strtoupper(substr($visitor['first_name'], 0, 1) . substr($visitor['last_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="flex items-center">
                                        <p class="font-medium text-slate-800"><?= sanitize($visitor['first_name'] . ' ' . $visitor['last_name']) ?></p>
                                        <?php if ($visitor['is_overdue']): ?>
                                        <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full">Overdue</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-slate-500"><?= sanitize($visitor['company']) ?></p>
                                    <div class="flex items-center text-xs text-slate-400 mt-1">
                                        <span><?= sanitize($visitor['category_name']) ?></span>
                                        <span class="mx-2">|</span>
                                        <span>Host: <?= sanitize($visitor['host_name']) ?></span>
                                        <span class="mx-2">|</span>
                                        <span>Duration: <?= floor($visitor['duration_minutes'] / 60) ?>h <?= $visitor['duration_minutes'] % 60 ?>m</span>
                                    </div>
                                </div>
                            </div>
                            <a href="./index.php?id=<?= $visitor['id'] ?>" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                                Check Out
                            </a>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Quick Stats -->
            <div class="space-y-6">
                <!-- Statistics -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Today's Statistics</h2>
                    <?php
                    $today = date('Y-m-d');
                    $stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date = ? AND visit_status = 'checked_out'");
                    $stmt->execute([$today]);
                    $checkedOutToday = $stmt->fetchColumn();

                    $stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date = ? AND visit_status = 'checked_in'");
                    $stmt->execute([$today]);
                    $stillOnsite = $stmt->fetchColumn();
                    ?>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Checked Out</span>
                            <span class="font-bold text-slate-800"><?= $checkedOutToday ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Still Onsite</span>
                            <span class="font-bold text-slate-800"><?= $stillOnsite ?></span>
                        </div>
                        <?php
                        $overdueCount = 0;
                        foreach ($checkedInVisitors as $v) {
                            if ($v['is_overdue']) $overdueCount++;
                        }
                        ?>
                        <div class="flex items-center justify-between">
                            <span class="text-red-600 font-medium">Overdue</span>
                            <span class="font-bold text-red-600"><?= $overdueCount ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <button onclick="checkoutAllOverdue()" class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                            Check Out All Overdue (<?= $overdueCount ?>)
                        </button>
                        <a href="../checkin/index.php" class="block w-full px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors text-sm font-medium text-center">
                            Check In New Visitor
                        </a>
                    </div>
                </div>

                <!-- Overdue Visitors -->
                <?php if ($overdueCount > 0): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                    <h2 class="text-lg font-semibold text-red-800 mb-4">Overdue Visitors</h2>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach ($checkedInVisitors as $v): ?>
                        <?php if ($v['is_overdue']): ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-red-700"><?= sanitize($v['first_name'] . ' ' . $v['last_name']) ?></span>
                            <span class="text-red-500"><?= floor($v['duration_minutes'] / 60) ?>h ago</span>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function setRating(rating) {
    document.getElementById('rating-input').value = rating;
    document.querySelectorAll('.rating-star').forEach((star, index) => {
        star.classList.toggle('text-yellow-400', index < rating);
        star.classList.toggle('text-slate-300', index >= rating);
    });
}

// Search functionality
document.getElementById('search-input')?.addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    document.querySelectorAll('.visitor-row').forEach(row => {
        const searchStr = row.dataset.searchable || '';
        row.style.display = searchStr.includes(query) ? '' : 'none';
    });
});

function checkoutAllOverdue() {
    if (confirm('Are you sure you want to check out all overdue visitors?')) {
        // Would implement bulk checkout via AJAX in production
        alert('Bulk checkout would be processed here.');
    }
}
</script>

<?php require_once '../../templates/footer.php'; ?>
