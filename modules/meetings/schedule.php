<?php
/**
 * Meeting Scheduler / Visitor Pre-Registration
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('create_meeting');

$pageTitle = 'Schedule Meeting';
$db = getDB();

// Get departments
$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
$departments = $stmt->fetchAll();

// Get users by department
$users = [];
if (!empty($departments)) {
    $stmt = $db->query("SELECT id, first_name, last_name, department_id, role_id FROM users WHERE is_active = 1 ORDER BY first_name");
    $users = $stmt->fetchAll();
}

// Get visitor categories
$stmt = $db->query("SELECT * FROM visitor_categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();

    try {
        $db->beginTransaction();

        // Gather meeting data
        $meetingData = [
            'title' => sanitize($_POST['meeting_title'] ?? ''),
            'purpose' => sanitize($_POST['purpose'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'host_user_id' => (int)($_POST['host_user_id'] ?? $_SESSION['user_id']),
            'department_id' => (int)($_POST['department_id'] ?? $_SESSION['department_id']),
            'location' => sanitize($_POST['location'] ?? ''),
            'meeting_date' => sanitize($_POST['meeting_date'] ?? ''),
            'start_time' => sanitize($_POST['start_time'] ?? ''),
            'end_time' => sanitize($_POST['end_time'] ?? ''),
            'expected_duration' => (int)($_POST['duration'] ?? 60),
            'safety_clearance_level' => (int)($_POST['safety_level'] ?? 1),
            'requires_nda' => isset($_POST['requires_nda']) ? 1 : 0,
            'requires_safety_induction' => isset($_POST['requires_safety']) ? 1 : 0,
            'restricted_areas' => sanitize($_POST['restricted_areas'] ?? ''),
            'internal_participants' => sanitize($_POST['internal_participants'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'created_by' => $_SESSION['user_id']
        ];

        // Generate meeting UID
        $meetingData['meeting_uid'] = generateUID('MTG-');

        // Insert meeting
        $stmt = $db->prepare("\
            INSERT INTO meetings (
                meeting_uid, title, purpose, description, host_user_id,
                department_id, location, meeting_date, start_time, end_time,
                expected_duration, safety_clearance_level, requires_nda,
                requires_safety_induction, restricted_areas, internal_participants,
                notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())\
        ");

        $stmt->execute([
            $meetingData['meeting_uid'],
            $meetingData['title'],
            $meetingData['purpose'],
            $meetingData['description'],
            $meetingData['host_user_id'],
            $meetingData['department_id'],
            $meetingData['location'],
            $meetingData['meeting_date'],
            $meetingData['start_time'],
            $meetingData['end_time'],
            $meetingData['expected_duration'],
            $meetingData['safety_clearance_level'],
            $meetingData['requires_nda'],
            $meetingData['requires_safety_induction'],
            $meetingData['restricted_areas'],
            $meetingData['internal_participants'],
            $meetingData['notes'],
            $meetingData['created_by']
        ]);

        $meetingId = $db->lastInsertId();

        // Process visitors
        $visitorNames = $_POST['visitor_name'] ?? [];
        $visitorCompanies = $_POST['visitor_company'] ?? [];
        $visitorEmails = $_POST['visitor_email'] ?? [];
        $visitorPhones = $_POST['visitor_phone'] ?? [];
        $visitorIdTypes = $_POST['visitor_id_type'] ?? [];
        $visitorIdNumbers = $_POST['visitor_id_number'] ?? [];
        $visitorCategoriesArr = $_POST['visitor_category'] ?? [];

        foreach ($visitorNames as $index => $name) {
            if (empty(trim($name))) continue;

            $name = trim(sanitize($name));
            $nameParts = explode(' ', $name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            $email = sanitize($visitorEmails[$index] ?? '');
            $phone = sanitize($visitorPhones[$index] ?? '');

            // Check if visitor exists
            $visitorId = null;
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT id FROM visitors WHERE email = ?");
                $stmt->execute([$email]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $visitorId = $existing['id'];
                }
            }

            // Create new visitor if not exists
            if (!$visitorId) {
                $visitorUID = generateUID('VIS-');
                $stmt = $db->prepare("
                    INSERT INTO visitors (visitor_uid, first_name, last_name, email, phone, company, id_type, id_number, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $visitorUID,
                    $firstName,
                    $lastName,
                    $email,
                    $phone,
                    sanitize($visitorCompanies[$index] ?? ''),
                    sanitize($visitorIdTypes[$index] ?? 'national_id'),
                    sanitize($visitorIdNumbers[$index] ?? '')
                ]);
                $visitorId = $db->lastInsertId();
            }

            // Create pre-registered visit
            $visitUID = generateUID('VISIT-');
            $categoryId = (int)($visitorCategoriesArr[$index] ?? 1);

            // Check if approval is required for this category
            $stmt = $db->prepare("SELECT requires_approval FROM visitor_categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $categoryInfo = $stmt->fetch();

            $visitStatus = 'pre_registered';

            $stmt = $db->prepare("
                INSERT INTO visits (
                    visit_uid, visitor_id, meeting_id, category_id, host_user_id, department_id,
                    visit_date, scheduled_arrival_time, scheduled_departure_time, visit_status,
                    safety_clearance_level, purpose, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $visitUID,
                $visitorId,
                $meetingId,
                $categoryId,
                $meetingData['host_user_id'],
                $meetingData['department_id'],
                $meetingData['meeting_date'],
                $meetingData['start_time'],
                $meetingData['end_time'],
                $visitStatus,
                $meetingData['safety_clearance_level'],
                $meetingData['purpose'],
                $_SESSION['user_id']
            ]);

            $visitId = $db->lastInsertId();

            // Create approval request if required for this category
            $stmt = $db->prepare("SELECT requires_approval FROM visitor_categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $categoryInfo = $stmt->fetch();
            
            if ($categoryInfo && $categoryInfo['requires_approval']) {
                $stmt = $db->prepare("
                    INSERT INTO approvals (visit_id, request_type, requested_by, status, requested_at, priority)
                    VALUES (?, 'visit', ?, 'pending', NOW(), 'normal')
                ");
                $stmt->execute([$visitId, $_SESSION['user_id']]);
            }

            // Create meeting_visitors entry
            $qrCode = generateQRData($visitId);
            $stmt = $db->prepare("
                INSERT INTO meeting_visitors (meeting_id, visitor_id, status, qr_code, qr_generated_at)
                VALUES (?, ?, 'invited', ?, NOW())
            ");
            $stmt->execute([$meetingId, $visitorId, $qrCode]);

            // Handle vehicle details if provided
            if (!empty($_POST['vehicle_number'][$index] ?? '')) {
                $stmt = $db->prepare("
                    INSERT INTO vehicles (visit_id, vehicle_number, vehicle_type, vehicle_make, vehicle_model, vehicle_color)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $visitId,
                    sanitize($_POST['vehicle_number'][$index]),
                    sanitize($_POST['vehicle_type'][$index] ?? 'car'),
                    sanitize($_POST['vehicle_make'][$index] ?? ''),
                    sanitize($_POST['vehicle_model'][$index] ?? ''),
                    sanitize($_POST['vehicle_color'][$index] ?? '')
                ]);
            }

            // Handle equipment declaration if provided
            if (!empty($_POST['equipment_text'][$index] ?? '')) {
                $equipments = explode("\n", $_POST['equipment_text'][$index]);
                foreach ($equipments as $equipment) {
                    $equipment = trim(sanitize($equipment));
                    if (!empty($equipment)) {
                        $stmt = $db->prepare("INSERT INTO equipment_declarations (visit_id, equipment_type, description) VALUES (?, 'equipment', ?)");
                        $stmt->execute([$visitId, $equipment]);
                    }
                }
            }

            // Notify host
            if ($meetingData['host_user_id'] != $_SESSION['user_id']) {
                // Get host information
                $stmt = $db->prepare("
                    SELECT first_name, last_name, email
                    FROM users
                    WHERE id = ?
                ");
                $stmt->execute([$meetingData['host_user_id']]);
                $host = $stmt->fetch();
                
                // Send in-app notification
                sendNotification(
                    $meetingData['host_user_id'],
                    'meeting_reminder',
                    'New Meeting Scheduled',
                    "A meeting has been scheduled for {$meetingData['meeting_date']} with {$name}",
                    "/modules/meetings/view.php?id={$meetingId}"
                );
                
                // Send email if PHP Mailer is configured
                if (function_exists('getEmailTemplate')) {
                    try {
                        $emailData = [
                            'hostName' => $host['first_name'] . ' ' . $host['last_name'],
                            'visitorName' => $name,
                            'visitorCompany' => sanitize($_POST['visitor_company'][0] ?? 'Not specified'),
                            'visitorEmail' => sanitize($_POST['visitor_email'][0] ?? 'Not provided'),
                            'visitorPhone' => sanitize($_POST['visitor_phone'][0] ?? 'Not provided'),
                            'meetingDate' => formatDate($meetingData['meeting_date']),
                            'meetingTime' => formatTime($meetingData['start_time']),
                            'meetingLocation' => sanitize($meetingData['location']),
                            'meetingPurpose' => sanitize($meetingData['purpose']),
                            'confirmLink' => APP_URL . '/modules/meetings/view.php?id=' . $meetingId,
                            'requiresApproval' => false
                        ];
                        
                        $htmlBody = getEmailTemplate('meeting-invitation', $emailData);
                        
                        if ($htmlBody) {
                            sendEmail(
                                $host['email'],
                                'Meeting Scheduled - ' . $meetingData['title'],
                                $htmlBody
                            );
                        }
                    } catch (Exception $emailError) {
                        error_log("Failed to send meeting email: " . $emailError->getMessage());
                        // Continue even if email fails - notification is sent
                    }
                }
            }
        }

        $db->commit();
        logActivity('CREATE_MEETING', "Created meeting ID: $meetingId");

        $_SESSION['success'] = 'Meeting scheduled successfully! Visitors have been pre-registered.';
        header('Location: view.php?id=' . $meetingId);
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Failed to schedule meeting: ' . $e->getMessage();
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Schedule Meeting / Pre-Register Visitors</h1>
                    <p class="text-slate-500 mt-1">Schedule a meeting and pre-register visitors before arrival</p>
                </div>
                <a href="list.php" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors">
                    View All Meetings
                </a>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <strong>Error:</strong> <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" id="schedule-form" class="space-y-6">
            <?= csrfField() ?>

            <!-- Meeting Details -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Meeting Details
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Meeting Title</label>
                        <input type="text" name="meeting_title" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Project Review Meeting" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Department</label>
                        <select name="department_id" id="department-select" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $_SESSION['department_id'] ? 'selected' : '' ?>>
                                <?= sanitize($dept['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Host (Person to Visit)</label>
                        <select name="host_user_id" id="host-select" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                            <option value="">Select Host</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" data-dept="<?= $user['department_id'] ?>" <?= $user['id'] == $_SESSION['user_id'] ? 'selected' : '' ?>>
                                <?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Meeting Date</label>
                        <input type="date" name="meeting_date" min="<?= date('Y-m-d') ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Start Time</label>
                            <input type="time" name="start_time" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">End Time</label>
                            <input type="time" name="end_time" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Location</label>
                        <input type="text" name="location" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Conference Room A">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Purpose</label>
                        <select name="purpose" id="purpose-select" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="business">Business Meeting</option>
                            <option value="maintenance">Maintenance Work</option>
                            <option value="inspection">Inspection</option>
                            <option value="interview">Interview</option>
                            <option value="delivery">Delivery</option>
                            <option value="training">Training</option>
                            <option value="consultation">Consultation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Description / Notes</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Additional details about the meeting..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Safety & Security -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Safety & Security Requirements
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Safety Clearance Level</label>
                        <select name="safety_level" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="1">Low - General Access</option>
                            <option value="2">Medium - Production Areas</option>
                            <option value="3">High - Restricted Areas</option>
                            <option value="4">Critical - Hazardous Areas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Restricted Areas</label>
                        <input type="text" name="restricted_areas" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Production Floor, Warehouse">
                    </div>

                    <div class="flex flex-col justify-end space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="requires_nda" class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500">
                            <span class="ml-2 text-sm text-slate-600">NDA Required</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="requires_safety" class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500">
                            <span class="ml-2 text-sm text-slate-600">Safety Induction Required</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Visitors Section -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Visitors
                    </h2>
                    <button type="button" onclick="addVisitorRow()" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors text-sm">
                        + Add Visitor
                    </button>
                </div>

                <div id="visitors-container" class="space-y-4">
                    <!-- Initial visitor row -->
                    <div class="visitor-row border border-slate-200 rounded-lg p-4" data-index="0">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-sm font-medium text-slate-600">Visitor 1</span>
                            <button type="button" onclick="removeVisitorRow(this)" class="text-red-500 hover:text-red-700 text-sm hidden">Remove</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Full Name *</label>
                                <input type="text" name="visitor_name[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="John Smith" required>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Company</label>
                                <input type="text" name="visitor_company[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="ABC Corp">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
                                <input type="email" name="visitor_email[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="john@example.com">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Phone</label>
                                <input type="tel" name="visitor_phone[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="+1 234 567 8900">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Category *</label>
                                <select name="visitor_category[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" required>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">ID Type</label>
                                <select name="visitor_id_type[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                    <option value="national_id">National ID</option>
                                    <option value="passport">Passport</option>
                                    <option value="drivers_license">Driver's License</option>
                                    <option value="work_permit">Work Permit</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">ID Number</label>
                                <input type="text" name="visitor_id_number[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="ID-123456">
                            </div>

                            <div class="flex items-end">
                                <label class="flex items-center mb-1">
                                    <input type="checkbox" name="repeat_visitor[]" class="w-4 h-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-xs text-slate-600">Repeat Visitor</span>
                                </label>
                            </div>
                        </div>

                        <!-- Vehicle & Equipment (collapsed by default) -->
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <button type="button" onclick="toggleDetails(this)" class="text-sm text-primary-600 hover:text-primary-700">
                                + Add Vehicle & Equipment Details
                            </button>
                            <div class="vehicle-equipment-details hidden mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Vehicle Number</label>
                                    <input type="text" name="vehicle_number[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="ABC-123">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Vehicle Type</label>
                                    <select name="vehicle_type[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                        <option value="car">Car</option>
                                        <option value="truck">Truck</option>
                                        <option value="van">Van</option>
                                        <option value="motorcycle">Motorcycle</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Vehicle Color</label>
                                    <input type="text" name="vehicle_color[]" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="White">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Equipment/Tools to Bring</label>
                                    <textarea name="equipment_text[]" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="List each item on a new line..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <a href="list.php" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Schedule Meeting
                </button>
            </div>
        </form>
    </div>
</main>

<script>
let visitorCount = 1;

function addVisitorRow() {
    visitorCount++;
    const container = document.getElementById('visitors-container');
    const template = container.querySelector('.visitor-row').cloneNode(true);

    // Update labels and clear values
    template.dataset.index = visitorCount - 1;
    template.querySelector('span').textContent = `Visitor ${visitorCount}`;

    // Clear all inputs
    template.querySelectorAll('input').forEach(input => {
        if (input.type !== 'checkbox') {
            input.value = '';
        }
    });

    // Show remove button
    template.querySelector('button').classList.remove('hidden');

    // Hide vehicle/equipment details
    template.querySelector('.vehicle-equipment-details').classList.add('hidden');

    container.appendChild(template);
}

function removeVisitorRow(btn) {
    const row = btn.closest('.visitor-row');
    row.remove();
}

function toggleDetails(btn) {
    const details = btn.nextElementSibling;
    details.classList.toggle('hidden');
    btn.textContent = details.classList.contains('hidden') ? '+ Add Vehicle & Equipment Details' : '- Hide Vehicle & Equipment Details';
}

// Filter users by department
document.getElementById('department-select').addEventListener('change', function() {
    const deptId = this.value;
    const hostSelect = document.getElementById('host-select');

    hostSelect.querySelectorAll('option').forEach(option => {
        if (option.value === '') {
            option.style.display = '';
        } else {
            const optionDept = option.dataset.dept || option.getAttribute('data-dept');
            option.style.display = (!deptId || optionDept === deptId) ? '' : 'none';
        }
    });
});
</script>

<?php require_once '../../templates/footer.php'; ?>
