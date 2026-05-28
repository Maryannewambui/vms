<?php
/**
 * Visitor Check-In System
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('checkin_visitor');

$pageTitle = 'Check-In Visitor';
$db = getDB();

// Check for pre-registered visitor
$preRegistered = null;
if (isset($_GET['id'])) {
    $stmt = $db->prepare("
        SELECT v.*, vis.*, vc.name as category_name, vc.requires_nda, vc.requires_safety_induction,
               u.first_name as host_name, u.last_name as host_last, d.name as department_name
        FROM visits v
        JOIN visitors vis ON v.visitor_id = vis.id
        JOIN visitor_categories vc ON v.category_id = vc.id
        JOIN users u ON v.host_user_id = u.id
        LEFT JOIN departments d ON v.department_id = d.id
        WHERE v.id = ?
    ");
    $stmt->execute([(int)$_GET['id']]);
    $preRegistered = $stmt->fetch();
}

// Get visitor categories
$stmt = $db->query("SELECT * FROM visitor_categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Get departments
$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
$departments = $stmt->fetchAll();

// Get users for host selection
$stmt = $db->query("SELECT id, first_name, last_name, department_id FROM users WHERE is_active = 1 ORDER BY first_name");
$users = $stmt->fetchAll();

// Handle check-in form submission
$error = '';
$success = '';
$passData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();

    try {
        $db->beginTransaction();

        $isPreRegistered = !empty($_POST['visit_id']);
        $visitId = (int)($_POST['visit_id'] ?? 0);
        $visitorId = (int)($_POST['visitor_id'] ?? 0);

        // Check blacklist
        $idNumber = sanitize($_POST['id_number'] ?? '');
        $email = sanitize($_POST['email'] ?? '');

        if (isBlacklisted($visitorId, $idNumber, $email)) {
            throw new Exception('Visitor is on the blacklist. Check-in denied.');
        }

        if ($isPreRegistered) {
            // Update existing visit
            $stmt = $db->prepare("
                UPDATE visits SET
                    visit_status = 'checked_in',
                    actual_check_in = NOW(),
                    checked_in_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $visitId]);

            // Get visitor ID from visit
            $stmt = $db->prepare("SELECT visitor_id FROM visits WHERE id = ?");
            $stmt->execute([$visitId]);
            $visitorId = $stmt->fetchColumn();

        } else {
            // Walk-in visitor
            $firstName = sanitize($_POST['first_name'] ?? '');
            $lastName = sanitize($_POST['last_name'] ?? '');

            // Check if visitor exists
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT id, visitor_uid FROM visitors WHERE email = ?");
                $stmt->execute([$email]);
                $existingVisitor = $stmt->fetch();

                if ($existingVisitor) {
                    $visitorId = $existingVisitor['id'];
                }
            }

            // Create new visitor if not exists
            if (!$visitorId) {
                $visitorUID = generateUID('VIS-');
                $stmt = $db->prepare("
                    INSERT INTO visitors (visitor_uid, first_name, last_name, email, phone, company, designation, id_type, id_number, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $visitorUID,
                    $firstName,
                    $lastName,
                    $email,
                    sanitize($_POST['phone'] ?? ''),
                    sanitize($_POST['company'] ?? ''),
                    sanitize($_POST['designation'] ?? ''),
                    sanitize($_POST['id_type'] ?? 'national_id'),
                    $idNumber
                ]);
                $visitorId = $db->lastInsertId();
            }

            // Create new visit
            $visitUID = generateUID('VISIT-');
            $badgeNumber = generateBadgeNumber();

            $stmt = $db->prepare("
                INSERT INTO visits (
                    visit_uid, visitor_id, category_id, host_user_id, department_id,
                    visit_date, visit_status, actual_check_in, badge_number,
                    number_plate, people_count, visit_location_type, purpose,
                    safety_induction_acknowledged, nda_acknowledged, terms_acknowledged,
                    checked_in_by, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, CURDATE(), 'checked_in', NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $visitUID,
                $visitorId,
                (int)$_POST['category_id'],
                (int)$_POST['host_user_id'],
                (int)($_POST['department_id'] ?? null),
                $badgeNumber,
                sanitize($_POST['number_plate'] ?? ''),
                (int)($_POST['people_count'] ?? 1),
                sanitize($_POST['visit_location_type'] ?? 'office'),
                sanitize($_POST['purpose'] ?? ''),
                isset($_POST['safety_acknowledge']) ? 1 : 0,
                isset($_POST['nda_acknowledge']) ? 1 : 0,
                isset($_POST['terms_acknowledge']) ? 1 : 0,
                $_SESSION['user_id'],
                $_SESSION['user_id']
            ]);

            $visitId = $db->lastInsertId();
        }

        // Generate visitor pass
        $passNumber = generateBadgeNumber();
        $qrData = generateQRData($visitId);

        $stmt = $db->prepare("
            INSERT INTO visitor_passes (visit_id, pass_number, pass_type, qr_code, issued_at, issued_by, valid_from, valid_until, safety_level)
            VALUES (?, ?, 'temporary', ?, NOW(), ?, NOW(), DATE_ADD(NOW(), INTERVAL 8 HOUR), ?)
        ");

        $passType = 'temporary';
        if ($preRegistered) {
            switch ($preRegistered['category_id']) {
                case 2: $passType = 'contractor'; break;
                case 5: $passType = 'vip'; break;
                case 7: $passType = 'delivery'; break;
                case 3: $passType = 'interview'; break;
            }
        } else {
            $categoryId = (int)$_POST['category_id'];
            if ($categoryId == 2) $passType = 'contractor';
            elseif ($categoryId == 5) $passType = 'vip';
            elseif ($categoryId == 7) $passType = 'delivery';
            elseif ($categoryId == 3) $passType = 'interview';
        }

        $stmt->execute([
            $visitId,
            $passNumber,
            $qrData,
            $_SESSION['user_id'],
            (int)($_POST['safety_level'] ?? 1)
        ]);

        $passId = $db->lastInsertId();

        // Get pass data for display
        $stmt = $db->prepare("
            SELECT vp.*, v.badge_number, vis.first_name, vis.last_name, vis.company,
                   u.first_name as host_name, d.name as department_name, vc.name as category_name
            FROM visitor_passes vp
            JOIN visits v ON vp.visit_id = v.id
            JOIN visitors vis ON v.visitor_id = vis.id
            JOIN users u ON v.host_user_id = u.id
            LEFT JOIN departments d ON v.department_id = d.id
            JOIN visitor_categories vc ON v.category_id = vc.id
            WHERE vp.id = ?
        ");
        $stmt->execute([$passId]);
        $passData = $stmt->fetch();

        // Notify host
        if (!empty($_POST['host_user_id'])) {
            $hostId = (int)$_POST['host_user_id'];
            sendNotification(
                $hostId,
                'visitor_arrival',
                'Visitor Arrived',
                "Your visitor {$passData['first_name']} {$passData['last_name']} has checked in.",
                "/modules/checkin/badge.php?id={$passId}"
            );
        }

        $db->commit();
        logActivity('CHECK_IN', "Visitor checked in - Pass: $passNumber", $_SESSION['user_id']);

        // Redirect to badge page
        header('Location: badge.php?id=' . $passId . '&new=1');
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Check-in failed: ' . $e->getMessage();
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
            <h1 class="text-2xl font-bold text-slate-800">Visitor Check-In</h1>
            <p class="text-slate-500 mt-1">Check in visitors and generate visitor passes</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center text-red-700">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Check-In Form -->
            <div class="lg:col-span-2">
                <?php if ($preRegistered): ?>
                <!-- Pre-registered visitor info -->
                <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-green-800">Pre-Registered Visitor</h3>
                            <p class="text-sm text-green-600">This visitor was pre-registered. Verify details before check-in.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="checkin-form" class="space-y-6">
                    <?= csrfField() ?>
                    <input type="hidden" name="visitor_id" value="<?= $preRegistered ? sanitize($preRegistered['visitor_id']) : sanitize($_POST['visitor_id'] ?? '') ?>">
                    <?php if ($preRegistered): ?>
                    <input type="hidden" name="visit_id" value="<?= $preRegistered['id'] ?>">
                    <?php endif; ?>

                    <!-- Visitor Information -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Visitor Information
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">First Name *</label>
                                <input type="text" name="first_name" value="<?= $preRegistered ? sanitize($preRegistered['first_name']) : sanitize($_POST['first_name'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Last Name *</label>
                                <input type="text" name="last_name" value="<?= $preRegistered ? sanitize($preRegistered['last_name']) : sanitize($_POST['last_name'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Visitor Type *</label>
                                <select name="category_id" id="category-select" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($preRegistered && $preRegistered['category_id'] == $cat['id']) ? 'selected' : ((isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '') ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Company / Organization</label>
                                <input type="text" name="company" value="<?= $preRegistered ? sanitize($preRegistered['company']) : sanitize($_POST['company'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                                <input type="email" name="email" value="<?= $preRegistered ? sanitize($preRegistered['email']) : sanitize($_POST['email'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Phone Number *</label>
                                <input type="tel" name="phone" value="<?= $preRegistered ? sanitize($preRegistered['phone']) : sanitize($_POST['phone'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">ID Type</label>
                                <select name="id_type" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="national_id" <?= $preRegistered && $preRegistered['id_type'] == 'national_id' ? 'selected' : ((isset($_POST['id_type']) && $_POST['id_type'] == 'national_id') ? 'selected' : '') ?>>National ID</option>
                                    <option value="passport" <?= $preRegistered && $preRegistered['id_type'] == 'passport' ? 'selected' : ((isset($_POST['id_type']) && $_POST['id_type'] == 'passport') ? 'selected' : '') ?>>Passport</option>
                                    <option value="drivers_license" <?= $preRegistered && $preRegistered['id_type'] == 'drivers_license' ? 'selected' : ((isset($_POST['id_type']) && $_POST['id_type'] == 'drivers_license') ? 'selected' : '') ?>>Driver's License</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">ID Number / Passport *</label>
                                <input type="text" name="id_number" value="<?= $preRegistered ? sanitize($preRegistered['id_number']) : sanitize($_POST['id_number'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Number Plate</label>
                                <input type="text" name="number_plate" value="<?= $preRegistered ? sanitize($preRegistered['number_plate'] ?? '') : sanitize($_POST['number_plate'] ?? '') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., ABC-1234">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">People Count</label>
                                <input type="number" min="1" name="people_count" value="<?= $preRegistered ? sanitize($preRegistered['people_count'] ?? '1') : sanitize($_POST['people_count'] ?? '1') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Visit Location Type *</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-3 cursor-pointer">
                                        <input type="radio" name="visit_location_type" value="office" <?= ($preRegistered && $preRegistered['visit_location_type'] == 'office') || (!isset($_POST['visit_location_type']) || $_POST['visit_location_type'] === 'office') ? 'checked' : '' ?> class="mr-2" required>
                                        Office
                                    </label>
                                    <label class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-3 cursor-pointer">
                                        <input type="radio" name="visit_location_type" value="retail" <?= ($preRegistered && $preRegistered['visit_location_type'] == 'retail') || (isset($_POST['visit_location_type']) && $_POST['visit_location_type'] === 'retail') ? 'checked' : '' ?> class="mr-2">
                                        Retail
                                    </label>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Purpose of Visit *</label>
                                <select name="purpose" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                                    <?php
                                    $purposeOptions = [
                                        'Meeting' => 'Meeting',
                                        'Delivery' => 'Delivery',
                                        'Audit' => 'Audit',
                                        'Training' => 'Training',
                                        'Maintenance' => 'Maintenance',
                                        'Material Delivery' => 'Material Delivery',
                                        'Other' => 'Other'
                                    ];
                                    $selectedPurpose = $preRegistered ? $preRegistered['purpose'] : ($_POST['purpose'] ?? '');
                                    ?>
                                    <?php foreach ($purposeOptions as $value => $label): ?>
                                    <option value="<?= sanitize($value) ?>" <?= $selectedPurpose === $value ? 'selected' : '' ?>>
                                        <?= sanitize($label) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Host & Department -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Host & Department
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Department *</label>
                                <select name="department_id" id="dept-select" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= ($preRegistered && $preRegistered['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                                        <?= sanitize($dept['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Host (Person to Visit) *</label>
                                <select name="host_user_id" id="host-select" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                                    <option value="">-- Select Host --</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" data-dept="<?= $user['department_id'] ?>" <?= ($preRegistered && $preRegistered['host_user_id'] == $user['id']) ? 'selected' : '' ?>>
                                        <?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Acknowledgments -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Acknowledgments
                        </h2>

                        <div class="space-y-4">
                            <label class="inline-flex items-center gap-3">
                                <input type="checkbox" name="safety_acknowledge" value="1" <?= isset($_POST['safety_acknowledge']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500" required>
                                <span class="text-sm text-slate-700">I acknowledge the safety rules and induction for this visit.</span>
                            </label>

                            <label class="inline-flex items-center gap-3">
                                <input type="checkbox" name="terms_acknowledge" value="1" <?= isset($_POST['terms_acknowledge']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500" required>
                                <span class="text-sm text-slate-700">I agree to comply with visitor terms and campus policies.</span>
                            </label>

                            <label class="inline-flex items-center gap-3">
                                <input type="checkbox" name="nda_acknowledge" value="1" <?= isset($_POST['nda_acknowledge']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-slate-700">I agree to the non-disclosure and confidentiality requirements.</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end space-x-4">
                        <a href="javascript:searchVisitors()" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors">
                            Search Existing
                        </a>
                        <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Check In Visitor
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Quick Actions -->
            <div class="space-y-6">
                <!-- QR Scanner -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Quick Check-In</h2>
                    <button onclick="scanQR()" class="w-full px-4 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Scan QR Code
                    </button>
                </div>

                <!-- Expected Visitors -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-800">Expected Today</h2>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        <?php
                        $stmt = $db->prepare("
                            SELECT v.id as visit_id, v.scheduled_arrival_time,
                                   vis.first_name, vis.last_name, vis.company,
                                   vc.name as category_name, u.first_name as host_name
                            FROM visits v
                            JOIN visitors vis ON v.visitor_id = vis.id
                            JOIN visitor_categories vc ON v.category_id = vc.id
                            JOIN users u ON v.host_user_id = u.id
                            WHERE v.visit_date = CURDATE()
                            AND v.visit_status IN ('pre_registered', 'approved')
                            ORDER BY COALESCE(v.scheduled_arrival_time, '99:99:99')
                            LIMIT 10
                        ");
                        $stmt->execute();
                        $expected = $stmt->fetchAll();
                        ?>

                        <?php if (empty($expected)): ?>
                        <div class="p-6 text-center text-slate-500">
                            <p>No expected visitors today</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($expected as $visitor): ?>
                        <div class="p-4 hover:bg-slate-50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-slate-800"><?= sanitize($visitor['first_name'] . ' ' . $visitor['last_name']) ?></p>
                                    <p class="text-sm text-slate-500"><?= sanitize($visitor['company'] ?: $visitor['category_name']) ?></p>
                                </div>
                                <a href="?id=<?= $visitor['visit_id'] ?>" class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200">
                                    Check In
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Visitors -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-800">Recent Visitors</h2>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto">
                        <?php
                        $stmt = $db->query("
                            SELECT vis.id, vis.first_name, vis.last_name, vis.company, vis.phone, vis.email
                            FROM visitors vis
                            ORDER BY vis.created_at DESC
                            LIMIT 5
                        ");
                        $recentVisitors = $stmt->fetchAll();
                        ?>

                        <?php if (empty($recentVisitors)): ?>
                        <div class="p-6 text-center text-slate-500">
                            <p>No recent visitors</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recentVisitors as $visitor): ?>
                        <div class="p-4 hover:bg-slate-50 cursor-pointer" onclick="fillVisitorInfo(<?= htmlspecialchars(json_encode($visitor)) ?>)">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-medium mr-3">
                                    <?= strtoupper(substr($visitor['first_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800 text-sm"><?= sanitize($visitor['first_name'] . ' ' . $visitor['last_name']) ?></p>
                                    <p class="text-xs text-slate-500"><?= sanitize($visitor['company'] ?: 'No company') ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Fill visitor info
function fillVisitorInfo(visitor) {
    document.querySelector('[name="first_name"]').value = visitor.first_name || '';
    document.querySelector('[name="last_name"]').value = visitor.last_name || '';
    document.querySelector('[name="email"]').value = visitor.email || '';
    document.querySelector('[name="phone"]').value = visitor.phone || '';
    document.querySelector('[name="company"]').value = visitor.company || '';
    const visitorIdInput = document.querySelector('[name="visitor_id"]');
    if (visitorIdInput) {
        visitorIdInput.value = visitor.id;
    }
}

// Filter users by department
document.getElementById('dept-select').addEventListener('change', function() {
    const deptId = this.value;
    const hostSelect = document.getElementById('host-select');

    hostSelect.querySelectorAll('option').forEach(option => {
        const optionDept = option.dataset.dept || option.getAttribute('data-dept');
        option.style.display = (!deptId || optionDept === deptId) ? '' : 'none';
    });
});

// Trigger initial filter
document.getElementById('dept-select').dispatchEvent(new Event('change'));

// Scan QR (placeholder)
function scanQR() {
    VMS.showToast('QR Scanner would open here. Use a camera-enabled device.', 'info');
}

// Search visitors
function searchVisitors() {
    const search = prompt('Enter visitor name or phone:');
    if (search) {
        // Would implement AJAX search in production
        VMS.showToast('Searching for: ' + search, 'info');
    }
}
</script>

<?php require_once '../../templates/footer.php'; ?>
