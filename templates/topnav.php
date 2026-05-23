<?php
/**
 * Top Navigation Bar
 */

$unreadNotifications = isset($_SESSION['user_id']) ? getUnreadNotificationCount($_SESSION['user_id']) : 0;
?>

<!-- Top Navigation -->
<header class="bg-white shadow-sm border-b border-slate-200 fixed top-0 right-0 left-0 lg:left-64 z-20 h-16">
    <div class="flex items-center justify-between h-full px-4 lg:px-6">
        <!-- Left Section - Mobile Menu & Page Title -->
        <div class="flex items-center">
            <!-- Mobile menu button -->
            <button onclick="toggleSidebar()" class="lg:hidden mr-4 p-2 rounded-lg hover:bg-slate-100">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Page Title -->
            <?php if (isset($pageTitle)): ?>
            <h1 class="text-lg font-semibold text-slate-800"><?= sanitize($pageTitle) ?></h1>
            <?php endif; ?>
        </div>

        <!-- Right Section - Search, Notifications, Profile -->
        <div class="flex items-center space-x-4">
            <!-- Global Search -->
            <div class="hidden md:block relative">
                <input type="text"
                       id="global-search"
                       placeholder="Search visitors, meetings..."
                       class="w-64 px-4 py-2 pl-10 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                       autocomplete="off">
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>

                <!-- Search Results Dropdown -->
                <div id="search-results" class="hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-xl border border-slate-200 max-h-96 overflow-y-auto">
                </div>
            </div>

            <!-- Quick Actions -->
            <?php if (hasPermission('checkin_visitor')): ?>
            <a href="modules/checkin/index.php" class="hidden sm:flex items-center px-3 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span class="text-sm font-medium">Quick Check-In</span>
            </a>
            <?php endif; ?>

            <!-- Notifications -->
            <div class="relative">
                <button onclick="toggleNotifications()" class="relative p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <?php if ($unreadNotifications > 0): ?>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                        <?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?>
                    </span>
                    <?php endif; ?>
                </button>

                <!-- Notifications Dropdown -->
                <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                        <h3 class="font-semibold text-slate-800">Notifications</h3>
                        <a href="modules/notifications/index.php" class="text-sm text-primary-600 hover:text-primary-700">View All</a>
                    </div>
                    <div id="notifications-list" class="max-h-96 overflow-y-auto">
                        <div class="p-4 text-center text-slate-500">
                            <p>Loading...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative">
                <button onclick="toggleProfileMenu()" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-medium">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Profile Menu -->
                <div id="profile-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200">
                        <p class="text-sm font-medium text-slate-800"><?= sanitize($_SESSION['user_name'] ?? 'User') ?></p>
                        <p class="text-xs text-slate-500"><?= sanitize($_SESSION['user_email'] ?? '') ?></p>
                    </div>
                    <ul class="py-1">
                        <li>
                            <a href="modules/admin/profile.php" class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-100">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                My Profile
                            </a>
                        </li>
                        <li>
                            <a href="modules/admin/profile.php?tab=settings" class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-100">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Account Settings
                            </a>
                        </li>
                        <li class="border-t border-slate-200">
                            <a href="<?= APP_URL ?>/logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Current Time/Date -->
            <div class="hidden lg:block text-right">
                <p class="text-sm font-medium text-slate-800" id="current-time">--:--</p>
                <p class="text-xs text-slate-500" id="current-date">Loading...</p>
            </div>
        </div>
    </div>
</header>

<script>
// Update time
function updateClock() {
    const now = new Date();
    document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
    document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
}
updateClock();
setInterval(updateClock, 1000);

// Toggle notifications dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    dropdown.classList.toggle('hidden');

    if (!dropdown.classList.contains('hidden')) {
        // Close other dropdowns
        document.getElementById('profile-menu').classList.add('hidden');
        // Load notifications
        loadNotifications();
    }
}

// Toggle profile menu
function toggleProfileMenu() {
    const menu = document.getElementById('profile-menu');
    menu.classList.toggle('hidden');

    if (!menu.classList.contains('hidden')) {
        // Close other dropdowns
        document.getElementById('notifications-dropdown').classList.add('hidden');
    }
}

// Load notifications via AJAX
function loadNotifications() {
    fetch('modules/api/notifications.php?action=list')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('notifications-list');
            if (data.success && data.notifications.length > 0) {
                list.innerHTML = data.notifications.map(n => `
                    <div class="px-4 py-3 border-b border-slate-100 hover:bg-slate-50 cursor-pointer ${n.is_read ? 'opacity-60' : ''}">
                        <div class="flex items-start">
                            <div class="w-2 h-2 rounded-full mt-2 mr-3 ${n.is_read ? 'bg-slate-300' : 'bg-primary-500'}"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-slate-800">${VMS.escapeHtml(n.title)}</p>
                                <p class="text-xs text-slate-500 mt-1">${VMS.escapeHtml(n.message)}</p>
                                <p class="text-xs text-slate-400 mt-1">${n.created_at}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                list.innerHTML = '<div class="p-4 text-center text-slate-500"><p>No notifications</p></div>';
            }
        })
        .catch(() => {
            document.getElementById('notifications-list').innerHTML = '<div class="p-4 text-center text-red-500"><p>Failed to load</p></div>';
        });
}

// Global search
const searchInput = document.getElementById('global-search');
const searchResults = document.getElementById('search-results');
let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();

    if (query.length < 2) {
        searchResults.classList.add('hidden');
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`modules/api/search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.results.length > 0) {
                    searchResults.innerHTML = data.results.map(r => `
                        <a href="${r.link}" class="block px-4 py-3 hover:bg-slate-50">
                            <div class="flex items-center">
                                <span class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-600 mr-2">${r.type}</span>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">${VMS.escapeHtml(r.title)}</p>
                                    <p class="text-xs text-slate-500">${VMS.escapeHtml(r.subtitle)}</p>
                                </div>
                            </div>
                        </a>
                    `).join('');
                    searchResults.classList.remove('hidden');
                } else {
                    searchResults.innerHTML = '<div class="p-4 text-center text-slate-500">No results found</div>';
                    searchResults.classList.remove('hidden');
                }
            });
    }, 300);
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#notifications-dropdown') && !e.target.closest('[onclick*="toggleNotifications"]')) {
        document.getElementById('notifications-dropdown').classList.add('hidden');
    }
    if (!e.target.closest('#profile-menu') && !e.target.closest('[onclick*="toggleProfileMenu"]')) {
        document.getElementById('profile-menu').classList.add('hidden');
    }
    if (!e.target.closest('#global-search') && !e.target.closest('#search-results')) {
        searchResults.classList.add('hidden');
    }
});
</script>
