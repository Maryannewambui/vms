<?php
/**
 * Visitor Pass Approval System
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();

$pageTitle = 'Approve Visitor Passes';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$approvalId = (int)($_GET['approval_id'] ?? 0);
$error = '';
$success = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    
    $action = sanitize($_POST['action'] ?? 'list');
    $approvalId = (int)($_POST['approval_id'] ?? 0);
    
    try {
        $db->beginTransaction();
        
        // Get approval details
        $stmt = $db->prepare("
            SELECT a.*, v.id as visit_id, v.visitor_id, v.visit_uid,
                   vis.first_name, vis.last_name, vis.company, vis.email,
                   u.first_name as host_name, u.last_name as host_last, u.email as host_email,
                   vp.pass_number
            FROM approvals a
            JOIN visits v ON a.visit_id = v.id
            JOIN visitors vis ON v.visitor_id = vis.id
            JOIN users u ON v.host_user_id = u.id
            LEFT JOIN visitor_passes vp ON vp.visit_id = v.id
            WHERE a.id = ?
        ");
        $stmt->execute([$approvalId]);
        $approval = $stmt->fetch();
        
        if (!$approval) {
            throw new Exception('Approval request not found');
        }
        
        // Check permission - only host or admin can approve
        $canApprove = ($_SESSION['user_id'] == $approval['host_user_id'] || 
                      hasPermission('approve_visitor_pass'));
        
        if (!$canApprove) {
            throw new Exception('You do not have permission to approve this pass');
        }
        
        if ($action === 'approve') {
            // Update approval status
            $stmt = $db->prepare("
                UPDATE approvals SET
                    status = 'approved',
                    approved_by = ?,
                    approved_at = NOW(),
                    notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                sanitize($_POST['approval_notes'] ?? ''),
                $approvalId
            ]);
            
            // Update visitor pass approval status
            $stmt = $db->prepare("
                UPDATE visitor_passes SET
                    approval_status = 'approved',
                    approved_by = ?,
                    approved_at = NOW(),
                    approver_notes = ?
                WHERE visit_id = ?
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                sanitize($_POST['approval_notes'] ?? ''),
                $approval['visit_id']
            ]);
            
            // Send approval notification to visitor
            sendNotification(
                $approval['visitor_id'],
                'approval_request',
                'Visitor Pass Approved',
                'Your visitor pass has been approved. Please proceed with check-in.',
                'modules/checkout/index.php'
            );
            
            // Send approval email if mailer is available
            if (function_exists('sendEmail')) {
                try {
                    $htmlBody = "
                    <html><body>
                        <h2>Visitor Pass Approved</h2>
                        <p>Your visitor pass has been approved.</p>
                        <p><strong>Pass Number:</strong> " . htmlspecialchars($approval['pass_number']) . "</p>
                        <p>You can now proceed with check-in at the reception desk.</p>
                    </body></html>";
                    
                    sendEmail(
                        $approval['email'],
                        'Your Visitor Pass Has Been Approved',
                        $htmlBody
                    );
                } catch (Exception $e) {
                    error_log("Failed to send approval email: " . $e->getMessage());
                }
            }
            
            $db->commit();
            logActivity('VISITOR_PASS_APPROVED', "Pass {$approval['pass_number']} approved for {$approval['first_name']} {$approval['last_name']}", $_SESSION['user_id']);
            
            $_SESSION['success'] = 'Visitor pass approved successfully!';
            header('Location: index.php');
            exit();
            
        } elseif ($action === 'reject') {
            $rejectionReason = sanitize($_POST['rejection_reason'] ?? 'No reason provided');
            
            // Update approval status
            $stmt = $db->prepare("
                UPDATE approvals SET
                    status = 'rejected',
                    approved_by = ?,
                    approved_at = NOW(),
                    rejection_reason = ?,
                    notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $rejectionReason,
                sanitize($_POST['rejection_notes'] ?? ''),
                $approvalId
            ]);
            
            // Update visitor pass
            $stmt = $db->prepare("
                UPDATE visitor_passes SET
                    is_active = 0,
                    revoked_at = NOW(),
                    revoked_by = ?,
                    revoke_reason = ?
                WHERE visit_id = ?
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $rejectionReason,
                $approval['visit_id']
            ]);
            
            // Update visit status
            $stmt = $db->prepare("
                UPDATE visits SET
                    visit_status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$approval['visit_id']]);
            
            // Send rejection notification
            sendNotification(
                $approval['visitor_id'],
                'approval_request',
                'Visitor Pass Rejected',
                'Your visitor pass has been rejected. Reason: ' . $rejectionReason,
                'modules/meetings/index.php'
            );
            
            // Send rejection email if mailer is available
            if (function_exists('sendEmail')) {
                try {
                    $htmlBody = "
                    <html><body>
                        <h2>Visitor Pass Rejected</h2>
                        <p>We regret to inform you that your visitor pass has been rejected.</p>
                        <p><strong>Reason:</strong> " . htmlspecialchars($rejectionReason) . "</p>
                        <p>Please contact the reception desk for further information.</p>
                    </body></html>";
                    
                    sendEmail(
                        $approval['email'],
                        'Your Visitor Pass Has Been Rejected',
                        $htmlBody
                    );
                } catch (Exception $e) {
                    error_log("Failed to send rejection email: " . $e->getMessage());
                }
            }
            
            $db->commit();
            logActivity('VISITOR_PASS_REJECTED', "Pass rejected for {$approval['first_name']} {$approval['last_name']} - Reason: {$rejectionReason}", $_SESSION['user_id']);
            
            $_SESSION['success'] = 'Visitor pass rejected successfully!';
            header('Location: index.php');
            exit();
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Get pending approvals
$stmt = $db->prepare("
    SELECT a.id, a.created_at as requested_at, a.request_type,
           v.visit_uid, v.actual_check_in,
           vis.first_name, vis.last_name, vis.company, vis.email,
           u.first_name as host_name, u.last_name as host_last,
           vp.pass_number
    FROM approvals a
    JOIN visits v ON a.visit_id = v.id
    JOIN visitors vis ON v.visitor_id = vis.id
    JOIN users u ON v.host_user_id = u.id
    LEFT JOIN visitor_passes vp ON vp.visit_id = v.id
    WHERE a.status = 'pending'
    AND (u.id = ? OR ? = 1)
    ORDER BY a.created_at DESC
");

$isAdmin = hasPermission('approve_visitor_pass');
$stmt->execute([$_SESSION['user_id'], $isAdmin ? 1 : 0]);
$pendingApprovals = $stmt->fetchAll();

// Get approval details if viewing specific approval
$approvalDetail = null;
if ($action === 'review' && $approvalId) {
    $stmt = $db->prepare("
        SELECT a.*, v.id as visit_id, v.visitor_id,
               vis.first_name, vis.last_name, vis.company, vis.email, vis.phone, vis.designation,
               u.first_name as host_name, u.last_name as host_last,
               vp.pass_number, vp.valid_from, vp.valid_until
        FROM approvals a
        JOIN visits v ON a.visit_id = v.id
        JOIN visitors vis ON v.visitor_id = vis.id
        JOIN users u ON v.host_user_id = u.id
        LEFT JOIN visitor_passes vp ON vp.visit_id = v.id
        WHERE a.id = ?
    ");
    $stmt->execute([$approvalId]);
    $approvalDetail = $stmt->fetch();
    
    if (!$approvalDetail) {
        $error = 'Approval request not found';
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
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Approve Visitor Passes</h1>
            <p class="text-slate-500 mt-1">Review and approve visitor passes for scheduled meetings</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 flex items-center">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 flex items-center">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'review' && $approvalDetail): ?>
        <!-- Approval Review Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-slate-800">Review Visitor Pass</h2>
                        <a href="index.php" class="text-slate-500 hover:text-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Visitor Information -->
                    <div class="mb-6 pb-6 border-b border-slate-200">
                        <h3 class="font-semibold text-slate-800 mb-4">Visitor Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-500">Name</p>
                                <p class="font-medium text-slate-800"><?= sanitize($approvalDetail['first_name'] . ' ' . $approvalDetail['last_name']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Company</p>
                                <p class="font-medium text-slate-800"><?= sanitize($approvalDetail['company']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Email</p>
                                <p class="font-medium text-slate-800"><?= sanitize($approvalDetail['email']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Phone</p>
                                <p class="font-medium text-slate-800"><?= sanitize($approvalDetail['phone']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Pass Information -->
                    <div class="mb-6 pb-6 border-b border-slate-200">
                        <h3 class="font-semibold text-slate-800 mb-4">Pass Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-500">Pass Number</p>
                                <p class="font-medium text-slate-800"><?= sanitize($approvalDetail['pass_number']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Valid Period</p>
                                <p class="font-medium text-slate-800"><?= formatDateTime($approvalDetail['valid_from']) ?> - <?= formatDateTime($approvalDetail['valid_until']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Decision Form -->
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="approval_id" value="<?= $approvalDetail['id'] ?>">

                        <div class="mb-6">
                            <p class="text-sm text-slate-600 mb-4">Make your decision:</p>
                            <div class="space-y-3">
                                <!-- Approve -->
                                <label class="flex items-center p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-green-400 hover:bg-green-50" onclick="selectDecision('approve')">
                                    <input type="radio" name="approval_action" value="approve" id="approve-action" class="w-4 h-4 text-green-600">
                                    <span class="ml-3">
                                        <span class="font-medium text-slate-800">Approve Pass</span>
                                        <p class="text-sm text-slate-500">Allow visitor to proceed with check-in</p>
                                    </span>
                                </label>

                                <!-- Reject -->
                                <label class="flex items-center p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-red-400 hover:bg-red-50" onclick="selectDecision('reject')">
                                    <input type="radio" name="approval_action" value="reject" id="reject-action" class="w-4 h-4 text-red-600">
                                    <span class="ml-3">
                                        <span class="font-medium text-slate-800">Reject Pass</span>
                                        <p class="text-sm text-slate-500">Deny visitor access - provide reason</p>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Reason/Notes -->
                        <div id="notes-section" style="display: none;" class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Notes/Reason</label>
                            <textarea name="approval_notes" id="approval_notes" rows="3" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Optional notes..."></textarea>
                        </div>

                        <div id="rejection-section" style="display: none;" class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Rejection Reason *</label>
                            <select name="rejection_reason" id="rejection_reason" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Select a reason...</option>
                                <option value="Visitor not on approved list">Visitor not on approved list</option>
                                <option value="Meeting cancelled">Meeting cancelled</option>
                                <option value="Scheduling conflict">Scheduling conflict</option>
                                <option value="Safety concerns">Safety concerns</option>
                                <option value="Blacklisted visitor">Blacklisted visitor</option>
                                <option value="Other">Other - Please specify in notes</option>
                            </select>
                            <textarea name="rejection_notes" rows="2" class="w-full mt-2 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Additional details..."></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-4">
                            <a href="index.php" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors">Cancel</a>
                            <button type="submit" id="submit-btn" onclick="setAction()" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors" disabled>Make Decision</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Sidebar: Quick Info -->
            <div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">Quick Actions</h3>
                    <p class="text-sm text-slate-600 mb-4">Select your decision above (Approve or Reject) and click the button to proceed.</p>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Approvals List -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800">Pending Approvals (<?= count($pendingApprovals) ?>)</h2>
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium"><?= count($pendingApprovals) ?> pending</span>
            </div>

            <?php if (empty($pendingApprovals)): ?>
            <div class="p-8 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-slate-500 font-medium">No pending approvals</p>
                <p class="text-slate-400 text-sm">All visitor passes have been approved!</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($pendingApprovals as $approval): ?>
                <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div class="flex-1">
                        <div class="flex items-center">
                            <p class="font-medium text-slate-800"><?= sanitize($approval['first_name'] . ' ' . $approval['last_name']) ?></p>
                            <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">Pending</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1"><?= sanitize($approval['company']) ?></p>
                        <p class="text-xs text-slate-400 mt-1">Pass: <?= sanitize($approval['pass_number']) ?> | Requested: <?= formatDateTime($approval['requested_at']) ?></p>
                    </div>
                    <a href="?action=review&approval_id=<?= $approval['id'] ?>" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium">
                        Review
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function selectDecision(decision) {
    document.getElementById('submit-btn').disabled = false;
    document.getElementById('notes-section').style.display = decision === 'approve' ? 'block' : 'none';
    document.getElementById('rejection-section').style.display = decision === 'reject' ? 'block' : 'none';
}

function setAction() {
    const action = document.querySelector('input[name="approval_action"]:checked')?.value;
    document.querySelector('form').insertAdjacentHTML('afterbegin', '<input type="hidden" name="action" value="' + action + '">');
}

document.querySelectorAll('input[name="approval_action"]').forEach(radio => {
    radio.addEventListener('change', function() {
        selectDecision(this.value);
    });
});
</script>

<?php require_once '../../templates/footer.php'; ?>
