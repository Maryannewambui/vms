<?php
/**
 * Dashboard - Main Landing Page
 * VMS - Pipe Manufacturing Company
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Dashboard';
$db = getDB();

// Get dashboard statistics
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Total visitors checked in today
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date = ? AND visit_status = 'checked_in'");
$stmt->execute([$today]);
$totalCheckedInToday = $stmt->fetchColumn() ?: 0;

// Visitors currently onsite
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_status = 'checked_in' AND actual_check_in IS NOT NULL");
$stmt->execute();
$currentOnsite = $stmt->fetchColumn() ?: 0;

// Pending approvals
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_status = 'pre_registered' AND visit_date >= ?");
$stmt->execute([$today]);
$pendingApprovals = $stmt->fetchColumn() ?: 0;

// Expected visitors today
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date = ? AND visit_status IN ('pre_registered', 'approved')");
$stmt->execute([$today]);
$expectedToday = $stmt->fetchColumn() ?: 0;

// Overdue checkouts
$overdueThreshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_status = 'checked_in' AND actual_check_in < ?");
$stmt->execute([$overdueThreshold]);
$overdueCheckouts = $stmt->fetchColumn() ?: 0;

// Contractor visits today
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date = ? AND category_id = 2");
$stmt->execute([$today]);
$contractorVisits = $stmt->fetchColumn() ?: 0;

// Interviews today
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date = ? AND category_id = 3");
$stmt->execute([$today]);
$interviewsToday = $stmt->fetchColumn() ?: 0;

// Recent activity (last 20)
$stmt = $db->prepare("
    SELECT al.*, u.first_name, u.last_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 20
");
$stmt->execute();
$recentActivity = $stmt->fetchAll();

// Visitors checked in today (list)
$stmt = $db->prepare("
    SELECT v.*, vis.first_name, vis.last_name, vis.company, vc.name as category_name,
           u.first_name as host_name, d.name as department_name
    FROM visits v
    JOIN visitors vis ON v.visitor_id = vis.id
    JOIN visitor_categories vc ON v.category_id = vc.id
    JOIN users u ON v.host_user_id = u.id
    LEFT JOIN departments d ON v.department_id = d.id
    WHERE v.visit_date = ? AND v.visit_status = 'checked_in'
    ORDER BY v.actual_check_in DESC
    LIMIT 10
");
$stmt->execute([$today]);
$visitorsOnsite = $stmt->fetchAll();

// Expected visitors today
$stmt = $db->prepare("
    SELECT v.*, vis.first_name, vis.last_name, vis.company, vc.name as category_name,
           u.first_name as host_name, d.name as department_name
    FROM visits v
    JOIN visitors vis ON v.visitor_id = vis.id
    JOIN visitor_categories vc ON v.category_id = vc.id
    JOIN users u ON v.host_user_id = u.id
    LEFT JOIN departments d ON v.department_id = d.id
    WHERE v.visit_date = ? AND v.visit_status IN ('pre_registered', 'approved')
    ORDER BY v.scheduled_arrival_time ASC
    LIMIT 10
");
$stmt->execute([$today]);
$expectedVisitors = $stmt->fetchAll();

// Weekly visitor trends (last 7 days)
$stmt = $db->prepare("
    SELECT DATE(actual_check_in) as visit_date, COUNT(*) as count
    FROM visits
    WHERE actual_check_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(actual_check_in)
    ORDER BY visit_date
");
$stmt->execute();
$weeklyTrends = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Department-wise visitors today
$stmt = $db->prepare("
    SELECT d.name as department, COUNT(*) as count
    FROM visits v
    LEFT JOIN departments d ON v.department_id = d.id
    WHERE v.visit_date = ?
    GROUP BY d.name
    ORDER BY count DESC
    LIMIT 5
");
$stmt->execute([$today]);
$departmentStats = $stmt->fetchAll();

// Visitor type distribution today
$stmt = $db->prepare("
    SELECT vc.name as category, COUNT(*) as count
    FROM visits v
    JOIN visitor_categories vc ON v.category_id = vc.id
    WHERE v.visit_date = ?
    GROUP BY vc.name
    ORDER BY count DESC
");
$stmt->execute([$today]);
$visitorTypeStats = $stmt->fetchAll();

// Blacklisted visitors alert
$stmt = $db->prepare("SELECT COUNT(*) FROM blacklist WHERE status = 'active'");
$stmt->execute();
$blacklistCount = $stmt->fetchColumn() ?: 0;

// Open incident reports
$stmt = $db->prepare("SELECT COUNT(*) FROM incident_reports WHERE status = 'open'");
$stmt->execute();
$openIncidents = $stmt->fetchColumn() ?: 0;

require_once '../vms/templates/header.php';
require_once '../vms/templates/sidebar.php';
require_once '../vms/templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen">
    <div class="p-4 lg:p-6">
        <!-- Welcome Banner -->
        <div class="mb-6 bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-6 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold mb-2">Welcome, <?= sanitize($_SESSION['user_name']) ?></h1>
                    <p class="opacity-90">Here's what's happening today at <?= getSetting('company_name') ?: COMPANY_NAME ?></p>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-3xl font-bold"><?= date('H:i') ?></p>
                    <p class="opacity-90"><?= date('l, d M Y') ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- Checked In Today -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Checked In Today</p>
                        <p class="text-2xl font-bold text-slate-800"><?= $totalCheckedInToday ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Currently Onsite -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Currently Onsite</p>
                        <p class="text-2xl font-bold text-slate-800"><?= $currentOnsite ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Expected Today -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Expected Today</p>
                        <p class="text-2xl font-bold text-slate-800"><?= $expectedToday ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Overdue Checkouts -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow <?= $overdueCheckouts > 0 ? 'border-red-200' : '' ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Overdue Checkouts</p>
                        <p class="text-2xl font-bold <?= $overdueCheckouts > 0 ? 'text-red-600' : 'text-slate-800' ?>"><?= $overdueCheckouts ?></p>
                    </div>
                    <div class="w-12 h-12 <?= $overdueCheckouts > 0 ? 'bg-red-100' : 'bg-slate-100' ?> rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 <?= $overdueCheckouts > 0 ? 'text-red-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <a href="modules/records/index.php?type=2" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow cursor-pointer">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Contractors</p>
                        <p class="text-lg font-bold text-slate-800"><?= $contractorVisits ?></p>
                    </div>
                </div>
            </a>

            <a href="modules/records/index.php?type=3" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow cursor-pointer">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Interviews</p>
                        <p class="text-lg font-bold text-slate-800"><?= $interviewsToday ?></p>
                    </div>
                </div>
            </a>

            <a href="modules/admin/approvals.php" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow cursor-pointer">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Pending Approvals</p>
                        <p class="text-lg font-bold text-slate-800"><?= $pendingApprovals ?></p>
                    </div>
                </div>
            </a>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Blacklisted</p>
                        <p class="text-lg font-bold text-slate-800"><?= $blacklistCount ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Panels -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Visitors Onsite & Expected -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Currently Onsite -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-slate-800">Currently Onsite</h2>
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                            <?= count($visitorsOnsite) ?> visitors
                        </span>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                        <?php if (empty($visitorsOnsite)): ?>
                        <div class="p-6 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p>No visitors currently onsite</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($visitorsOnsite as $visitor): ?>
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-medium text-sm">
                                    <?= strtoupper(substr($visitor['first_name'], 0, 1) . substr($visitor['last_name'], 0, 1)) ?>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium text-slate-800"><?= sanitize($visitor['first_name'] . ' ' . $visitor['last_name']) ?></p>
                                    <p class="text-sm text-slate-500"><?= sanitize($visitor['company'] ?: $visitor['category_name']) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-slate-700">Visiting: <?= sanitize($visitor['host_name']) ?></p>
                                <p class="text-xs text-slate-500">Since: <?= formatTime($visitor['actual_check_in']) ?></p>
                            </div>
                            <?php if (hasPermission('checkout_visitor')): ?>
                            <a href="modules/checkout/index.php?id=<?= $visitor['id'] ?>" class="ml-4 px-3 py-1 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                Check Out
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (count($visitorsOnsite) >= 10): ?>
                    <div class="px-6 py-3 border-t border-slate-200 text-center">
                        <a href="modules/records/index.php?status=checked_in" class="text-sm text-primary-600 hover:text-primary-700">View all visitors</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Expected Visitors Today -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-slate-800">Expected Visitors Today</h2>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                            <?= count($expectedVisitors) ?> expected
                        </span>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto">
                        <?php if (empty($expectedVisitors)): ?>
                        <div class="p-6 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p>No visitors expected today</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($expectedVisitors as $visitor): ?>
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-medium text-sm">
                                    <?= strtoupper(substr($visitor['first_name'], 0, 1) . substr($visitor['last_name'], 0, 1)) ?>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium text-slate-800"><?= sanitize($visitor['first_name'] . ' ' . $visitor['last_name']) ?></p>
                                    <p class="text-sm text-slate-500"><?= sanitize($visitor['company'] ?: $visitor['category_name']) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-slate-700"><?= $visitor['scheduled_arrival_time'] ? formatTime($visitor['scheduled_arrival_time']) : '--:--' ?></p>
                                <p class="text-xs text-slate-500">Host: <?= sanitize($visitor['host_name']) ?></p>
                            </div>
                            <?php if (hasPermission('checkin_visitor')): ?>
                            <a href="modules/checkin/index.php?id=<?= $visitor['id'] ?>" class="ml-4 px-3 py-1 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">
                                Check In
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Weekly Visitor Trends Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Weekly Visitor Trends</h2>
                    <div class="h-64">
                        <canvas id="visitorTrendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Column - Stats & Activity -->
            <div class="space-y-6">
                <!-- Department Stats -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Department Traffic Today</h2>
                    <div class="space-y-3">
                        <?php if (empty($departmentStats)): ?>
                        <p class="text-sm text-slate-500 text-center py-4">No data for today</p>
                        <?php else: ?>
                        <?php foreach ($departmentStats as $dept): ?>
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-slate-600"><?= sanitize($dept['department']) ?></span>
                                    <span class="text-sm font-medium text-slate-800"><?= $dept['count'] ?></span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <?php $percentage = min(100, ($dept['count'] / max(1, $totalCheckedInToday)) * 100); ?>
                                    <div class="bg-primary-500 rounded-full h-2 transition-all" style="width: <?= $percentage ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visitor Type Distribution -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Visitor Types Today</h2>
                    <div class="h-48">
                        <canvas id="visitorTypeChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-800">Recent Activity</h2>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="p-4">
                            <div class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center mr-3 flex-shrink-0">
                                    <?php
                                    $icon = 'M13 16h-1v-4';
                                    if (strpos($activity['action'], 'LOGIN') !== false) $icon = 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1';
                                    elseif (strpos($activity['action'], 'CHECK') !== false) $icon = 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                                    ?>
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-800"><?= sanitize($activity['action']) ?></p>
                                    <p class="text-xs text-slate-500 truncate"><?= sanitize($activity['first_name'] . ' ' . $activity['last_name'] . ($activity['details'] ? ' - ' . $activity['details'] : '')) ?></p>
                                </div>
                                <span class="text-xs text-slate-400 ml-2"><?= timeAgo($activity['created_at']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Weekly Visitor Trends Chart
const trendsCtx = document.getElementById('visitorTrendsChart').getContext('2d');
const last7Days = [];
const visitorData = [];
<?php
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    echo "last7Days.push('" . date('D', strtotime($date)) . "');\n";
    echo "visitorData.push(" . ($weeklyTrends[$date] ?? 0) . ");\n";
}
?>

new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: last7Days,
        datasets: [{
            label: 'Visitors',
            data: visitorData,
            borderColor: '#f97316',
            backgroundColor: 'rgba(249, 115, 22, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#f97316',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Visitor Type Distribution Chart
const typeCtx = document.getElementById('visitorTypeChart').getContext('2d');
const visitorTypes = <?= json_encode(array_column($visitorTypeStats, 'category')) ?>;
const typeCounts = <?= json_encode(array_column($visitorTypeStats, 'count')) ?>;

new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: visitorTypes,
        datasets: [{
            data: typeCounts,
            backgroundColor: [
                '#f97316',
                '#3b82f6',
                '#22c55e',
                '#eab308',
                '#ef4444',
                '#8b5cf6',
                '#ec4899',
                '#6b7280',
                '#14b8a6'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12,
                    padding: 8,
                    font: {
                        size: 11
                    }
                }
            }
        },
        cutout: '60%'
    }
});
</script>

<?php require_once 'templates/footer.php'; ?>
