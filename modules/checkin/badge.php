<?php
/**
 * Visitor Badge / Pass Display
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();

$passId = (int)($_GET['id'] ?? 0);
if (!$passId) {
    redirect('/modules/checkin/index.php');
}

$db = getDB();

// Get pass data
$stmt = $db->prepare("
    SELECT vp.*, v.id as visit_id, v.visit_uid, v.actual_check_in,
           vis.first_name, vis.last_name, vis.company, vis.email,
           u.first_name as host_name, u.last_name as host_last,
           d.name as department_name, vc.name as category_name,
           vc.color_code
    FROM visitor_passes vp
    JOIN visits v ON vp.visit_id = v.id
    JOIN visitors vis ON v.visitor_id = vis.id
    JOIN users u ON v.host_user_id = u.id
    LEFT JOIN departments d ON v.department_id = d.id
    JOIN visitor_categories vc ON v.category_id = vc.id
    WHERE vp.id = ?
");
$stmt->execute([$passId]);
$pass = $stmt->fetch();

if (!$pass) {
    redirect('/modules/checkin/index.php');
}

// Generate QR code data
$qrData = $pass['qr_code'];

// Safety level colors
$safetyColors = [
    1 => '#22c55e', // Green - Low
    2 => '#eab308', // Yellow - Medium
    3 => '#f97316', // Orange - High
    4 => '#ef4444'  // Red - Critical
];
$safetyColor = $safetyColors[$pass['safety_level']] ?? '#6b7280';

require_once '../../templates/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Pass - <?= sanitize($pass['first_name'] . ' ' . $pass['last_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            body * { visibility: hidden; }
            .print-badge, .print-badge * { visibility: visible; }
            .print-badge { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8">
    <!-- Success Message -->
    <?php if (isset($_GET['new'])): ?>
    <div class="max-w-4xl mx-auto mb-4">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-green-700 font-medium">Visitor checked in successfully! Pass generated below.</span>
            </div>
            <div class="flex gap-2">
                <a href="index.php" class="text-sm text-green-600 hover:text-green-700">Check in another</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Badge Container -->
    <div class="max-w-4xl mx-auto">
        <div class="print-badge bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Badge Design -->
            <div class="flex">
                <!-- Left Side - Company Logo and Badge Type -->
                <div class="w-24 flex-shrink-0 flex flex-col items-center justify-center py-6 text-white" style="background: linear-gradient(180deg, #f97316 0%, #ea580c 100%);">
                    <svg class="w-12 h-12 mb-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <p class="text-xs font-bold tracking-wider">PIPEVMS</p>
                    <div class="mt-4 px-2 py-1 bg-white bg-opacity-20 rounded text-xs">
                        <?= strtoupper($pass['pass_type']) ?>
                    </div>
                </div>

                <!-- Right Side - Visitor Details -->
                <div class="flex-1 p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h1 class="text-lg font-bold text-slate-800">VISITOR PASS</h1>
                            <p class="text-xs text-slate-500">Precision Pipe Manufacturing Co.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-600">Pass No:</p>
                            <p class="font-bold text-slate-800"><?= sanitize($pass['pass_number']) ?></p>
                        </div>
                    </div>

                    <!-- Visitor Photo and Info -->
                    <div class="flex gap-6 mb-4">
                        <!-- <div class="w-24 h-24 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden">
                            <?php if ($pass['photo_taken']): ?>
                            <img src="../../uploads/photos/<?= $pass['photo_taken'] ?>" class="w-full h-full object-cover" alt="Visitor Photo">
                            <?php else: ?>
                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <?php endif; ?>
                        </div> -->

                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-slate-800 mb-1"><?= sanitize($pass['first_name'] . ' ' . $pass['last_name']) ?></h2>
                            <p class="text-slate-600 mb-2"><?= sanitize($pass['company']) ?></p>
                            <span class="inline-block px-2 py-1 bg-slate-100 rounded text-sm text-slate-700">
                                <?= sanitize($pass['category_name']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500 mb-1">Department</p>
                            <p class="font-medium text-slate-800"><?= sanitize($pass['department_name']) ?></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500 mb-1">Host</p>
                            <p class="font-medium text-slate-800"><?= sanitize($pass['host_name'] . ' ' . $pass['host_last']) ?></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500 mb-1">Check-in Time</p>
                            <p class="font-medium text-slate-800"><?= formatDateTime($pass['actual_check_in']) ?></p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500 mb-1">Valid Until</p>
                            <p class="font-medium text-slate-800"><?= formatDateTime($pass['valid_until']) ?></p>
                        </div>
                    </div>

                    <!-- Safety Level & QR -->
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center mb-2">
                                <p class="text-xs text-slate-500 mr-2">Safety Level:</p>
                                <span class="px-2 py-1 rounded text-xs font-medium" style="background-color: <?= $safetyColor ?>20; color: <?= $safetyColor ?>;">
                                    <?= ['', 'Low', 'Medium', 'High', 'Critical'][$pass['safety_level']] ?? 'Unknown' ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col items-center">
                            <div id="qrcode" class="bg-white p-2 rounded-lg border border-slate-200"></div>
                            <p class="text-xs text-slate-500 mt-1">Scan to verify</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 border-t border-slate-200 px-6 py-3 text-center">
                <p class="text-xs text-slate-500">
                    This pass must be visible at all times while inside the facility.
                    Report to security before exiting. For emergencies, contact ext. 911.
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex justify-center gap-4">
            <button onclick="window.print()" class="px-6 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Badge
            </button>

            <a href="index.php" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors flex items-center">
                Check in Another Visitor
            </a>

            <a href="../../index.php" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors flex items-center">
                Back to Dashboard
            </a>
        </div>
    </div>

    <script>
    // Generate QR Code
    const qrData = <?= json_encode($qrData) ?>;
    QRCode.toCanvas(document.createElement('canvas'), qrData, {
        width: 100,
        margin: 0
    }, function(error, canvas) {
        if (error) {
            console.error(error);
            return;
        }
        document.getElementById('qrcode').appendChild(canvas);
    });
    </script>
</body>
</html>
