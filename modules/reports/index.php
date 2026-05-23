<?php
/**
 * Reports & Analytics
 * VMS - Pipe Manufacturing Company
 */

require_once '../../config/config.php';
requireLogin();
requirePermission('view_reports');

$pageTitle = 'Reports & Analytics';
$db = getDB();

// Date range
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-t');

// Daily visitor stats
$stmt = $db->prepare("
    SELECT DATE(actual_check_in) as date, COUNT(*) as count
    FROM visits
    WHERE actual_check_in BETWEEN ? AND ?
    GROUP BY DATE(actual_check_in)
    ORDER BY date
");
$stmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$dailyStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Visitor type breakdown
$stmt = $db->prepare("
    SELECT vc.name as category, COUNT(*) as count
    FROM visits v
    JOIN visitor_categories vc ON v.category_id = vc.id
    WHERE v.visit_date BETWEEN ? AND ?
    GROUP BY vc.name
    ORDER BY count DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$visitorTypes = $stmt->fetchAll();

// Department breakdown
$stmt = $db->prepare("
    SELECT COALESCE(d.name, 'Unknown') as department, COUNT(*) as count
    FROM visits v
    LEFT JOIN departments d ON v.department_id = d.id
    WHERE v.visit_date BETWEEN ? AND ?
    GROUP BY d.name
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$departmentStats = $stmt->fetchAll();

// Peak hours analysis
$stmt = $db->prepare("
    SELECT HOUR(actual_check_in) as hour, COUNT(*) as count
    FROM visits
    WHERE actual_check_in BETWEEN ? AND ?
    AND actual_check_in IS NOT NULL
    GROUP BY HOUR(actual_check_in)
    ORDER BY hour
");
$stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$hourlyStats = $stmt->fetchAll();

// Summary stats
$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$totalVisits = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT visitor_id) FROM visits WHERE visit_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$uniqueVisitors = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE visit_date BETWEEN ? AND ? AND badge_returned = 0");
$stmt->execute([$dateFrom, $dateTo]);
$missingBadges = $stmt->fetchColumn();

require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../templates/topnav.php';
?>

<!-- Main Content -->
<main class="pt-16 lg:pt-16 lg:ml-64 min-h-screen bg-slate-100">
    <div class="p-4 lg:p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Reports & Analytics</h1>
                <p class="text-slate-500 mt-1">Visitor statistics and insights</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date_from" value="<?= $dateFrom ?>" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                <span class="text-slate-500">to</span>
                <input type="date" name="date_to" value="<?= $dateTo ?>" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600">Update</button>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <p class="text-sm text-slate-500">Total Visits</p>
                <p class="text-2xl font-bold text-slate-800"><?= number_format($totalVisits) ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <p class="text-sm text-slate-500">Unique Visitors</p>
                <p class="text-2xl font-bold text-slate-800"><?= number_format($uniqueVisitors) ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <p class="text-sm text-slate-500">Avg. Daily Visits</p>
                <p class="text-2xl font-bold text-slate-800"><?= $totalVisits > 0 ? round($totalVisits / max(1, count($dailyStats))) : 0 ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <p class="text-sm text-slate-500">Missing Badges</p>
                <p class="text-2xl font-bold text-slate-800"><?= $missingBadges ?></p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Daily Visitor Trend -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Daily Visitor Trend</h2>
                <div class="h-64">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>

            <!-- Peak Hours -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Peak Check-In Hours</h2>
                <div class="h-64">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Visitor Types -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Visitor Type Distribution</h2>
                <div class="h-64">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <!-- Department Traffic -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-800 mb-4">Department Traffic</h2>
                <div class="space-y-3">
                    <?php foreach ($departmentStats as $dept): ?>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm text-slate-700"><?= sanitize($dept['department']) ?></span>
                            <span class="text-sm font-medium text-slate-800"><?= $dept['count'] ?></span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-primary-500 rounded-full h-2" style="width: <?= min(100, ($dept['count'] / max(1, $totalVisits)) * 100) ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Daily Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyLabels = <?= json_encode(array_keys($dailyStats)) ?>;
const dailyData = <?= json_encode(array_values($dailyStats)) ?>;

new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyLabels.map(d => new Date(d).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})),
        datasets: [{
            label: 'Visitors',
            data: dailyData,
            borderColor: '#f97316',
            backgroundColor: 'rgba(249, 115, 22, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true },
            x: { grid: { display: false } }
        }
    }
});

// Hourly Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
const hourlyLabels = <?= json_encode(array_column($hourlyStats, 'hour')) ?>;
const hourlyData = <?= json_encode(array_column($hourlyStats, 'count')) ?>;

new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: hourlyLabels.map(h => h + ':00'),
        datasets: [{
            label: 'Check-ins',
            data: hourlyData,
            backgroundColor: '#f97316',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true },
            x: { grid: { display: false } }
        }
    }
});

// Type Chart
const typeCtx = document.getElementById('typeChart').getContext('2d');
const typeLabels = <?= json_encode(array_column($visitorTypes, 'category')) ?>;
const typeData = <?= json_encode(array_column($visitorTypes, 'count')) ?>;

new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{
            data: typeData,
            backgroundColor: ['#f97316', '#3b82f6', '#22c55e', '#eab308', '#ef4444', '#8b5cf6', '#ec4899', '#6b7280', '#14b8a6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        },
        cutout: '60%'
    }
});
</script>

<?php require_once '../../templates/footer.php'; ?>
