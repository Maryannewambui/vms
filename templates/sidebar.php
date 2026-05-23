<?php
/**
 * Sidebar Navigation
 * Requires session to be active
 */

// Current page detection
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg z-40 sidebar-transition lg:translate-x-0 -translate-x-full">
    <!-- Logo and Branding -->
    <div class="h-16 flex items-center px-6 border-b border-slate-200 bg-gradient-to-r from-primary-500 to-primary-600">
        <a href="index.php" class="flex items-center text-white">
            <svg class="w-8 h-8 mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <span class="text-xl font-bold">PipeVMS</span>
        </a>
    </div>

    <!-- User Info -->
    <?php if (isset($_SESSION['user_name'])): ?>
    <div class="px-4 py-4 border-b border-slate-200 bg-slate-50">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-medium">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-700"><?= sanitize($_SESSION['user_name']) ?></p>
                <p class="text-xs text-slate-500"><?= getRoleName($_SESSION['user_role']) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-2">
            <!-- Dashboard -->
            <li>
                <a href="index.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= $currentPage === 'index.php' ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
            </li>

            <!-- Pre-Registration -->
            <?php if (hasPermission('create_meeting')): ?>
            <li>
                <a href="modules/meetings/schedule.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/meetings/') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-medium">Schedule Meeting</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Check-In -->
            <?php if (hasPermission('checkin_visitor')): ?>
            <li>
                <a href="modules/checkin/index.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/checkin/') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <span class="font-medium">Check-In</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Check-Out -->
            <?php if (hasPermission('checkout_visitor')): ?>
            <li>
                <a href="modules/checkout/index.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/checkout/') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="font-medium">Check-Out</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Visitor Passes -->
            <?php if (hasPermission('generate_pass')): ?>
            <li>
                <a href="modules/passes/index.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/passes/') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="font-medium">Visitor Passes</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Visitor Records -->
            <?php if (hasPermission('view_records')): ?>
            <li>
                <a href="modules/records/index.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/records/') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="font-medium">Visitor Records</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Security -->
            <?php if (hasPermission('manage_blacklist') || hasPermission('view_incidents')): ?>
            <li>
                <button onclick="toggleSubmenu('security-menu')" class="w-full sidebar-link group flex items-center justify-between px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="font-medium">Security</span>
                    </div>
                    <svg class="w-4 h-4 transform transition-transform" id="security-menu-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul id="security-menu" class="hidden ml-8 mt-1 space-y-1">
                    <li>
                        <a href="modules/security/blacklist.php" class="block px-4 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-100 <?= strpos($currentPath, '/security/blacklist') !== false ? 'bg-slate-100 text-primary-600' : '' ?>">Blacklist</a>
                    </li>
                    <li>
                        <a href="modules/security/incidents.php" class="block px-4 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-100 <?= strpos($currentPath, '/security/incidents') !== false ? 'bg-slate-100 text-primary-600' : '' ?>">Incident Reports</a>
                    </li>
                    <li>
                        <a href="modules/security/evacuation.php" class="block px-4 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-100 <?= strpos($currentPath, '/security/evacuation') !== false ? 'bg-slate-100 text-primary-600' : '' ?>">Emergency Evacuation</a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Reports -->
            <?php if (hasPermission('view_reports')): ?>
            <li>
                <a href="modules/reports/index.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/reports/') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="font-medium">Reports</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Admin Section -->
            <?php if (hasPermission('manage_users') || hasPermission('manage_settings')): ?>
            <li class="pt-4">
                <span class="px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Administration</span>
            </li>
            <?php endif; ?>

            <!-- User Management -->
            <?php if (hasPermission('manage_users')): ?>
            <li>
                <a href="modules/admin/users.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/admin/users') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-medium">Manage Users</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Departments -->
            <?php if (hasPermission('manage_departments')): ?>
            <li>
                <a href="modules/admin/departments.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/admin/departments') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="font-medium">Departments</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Settings -->
            <?php if (hasPermission('manage_settings')): ?>
            <li>
                <a href="modules/admin/settings.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/admin/settings') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-medium">Settings</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Activity Logs -->
            <?php if (hasPermission('view_audit_logs')): ?>
            <li>
                <a href="modules/admin/logs.php" class="sidebar-link group flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors <?= strpos($currentPath, '/admin/logs') !== false ? 'bg-primary-50 text-primary-600' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M-.01 16h.01"/>
                    </svg>
                    <span class="font-medium">Activity Logs</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Logout Section -->
    <div class="border-t border-slate-200 p-4">
        <a href="<?= APP_URL ?>/logout.php" class="flex items-center px-4 py-2 text-slate-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>

<!-- Sidebar Overlay for Mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden" onclick="closeSidebar()"></div>

<script>
function toggleSubmenu(id) {
    const menu = document.getElementById(id);
    const arrow = document.getElementById(id + '-arrow');
    if (menu) {
        menu.classList.toggle('hidden');
        if (arrow) {
            arrow.classList.toggle('rotate-180');
        }
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>
