<?php
// Mobile Navigation Bar - Bottom navigation system
// This file handles all mobile navigation functionality separately from desktop navbar

// Ensure session and user functions are available
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/session.php';
}

$is_logged_in = isLoggedIn();
$user_name = '';

if ($is_logged_in) {
    $user_id = getCurrentUserId();
    if ($user_id) {
        try {
            $user = fetchOne("SELECT full_name, username FROM users WHERE id = ?", [$user_id]);
            $user_name = $user['full_name'] ?? $user['username'] ?? 'User';
        } catch (Exception $e) {
            $user_name = 'User';
        }
    }
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-navbar fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 md:hidden" style="display: none;">
    <!-- Bottom Navigation Content -->
    <div class="mobile-nav-content flex items-center justify-around px-2 py-2">
        <!-- Home -->
        <a href="index.php" class="mobile-nav-item <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9,22 9,12 15,12 15,22"></polyline>
            </svg>
            <span class="mobile-nav-text">Home</span>
        </a>

        <?php if ($is_logged_in): ?>
        <!-- Quiz History -->
        <a href="quiz-history.php" class="mobile-nav-item <?php echo $current_page === 'quiz-history.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12,6 12,12 16,14"></polyline>
            </svg>
            <span class="mobile-nav-text">History</span>
        </a>

        <!-- Performance -->
        <a href="performance.php" class="mobile-nav-item <?php echo $current_page === 'performance.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M3 3v18h18"></path>
                <path d="m19 9-5 5-4-4-3 3"></path>
            </svg>
            <span class="mobile-nav-text">Performance</span>
        </a>

        <!-- User Menu -->
        <div class="mobile-nav-item user-menu-mobile" id="mobile-user-menu">
            <button class="mobile-user-button" id="mobile-user-button">
                <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span class="mobile-nav-text">Account</span>
            </button>
            
            <!-- Mobile User Dropdown -->
            <div class="mobile-user-dropdown" id="mobile-user-dropdown">
                <div class="mobile-dropdown-header">
                    <span class="mobile-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
                <a href="profile.php" class="mobile-dropdown-item">
                    <svg class="mobile-dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Profile</span>
                </a>
                <a href="quiz-history.php" class="mobile-dropdown-item">
                    <svg class="mobile-dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    <span>Quiz History</span>
                </a>
                <a href="performance.php" class="mobile-dropdown-item">
                    <svg class="mobile-dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M3 3v18h18"></path>
                        <path d="m19 9-5 5-4-4-3 3"></path>
                    </svg>
                    <span>Performance</span>
                </a>
                <div class="mobile-dropdown-divider"></div>
                <a href="logout.php" class="mobile-dropdown-item logout">
                    <svg class="mobile-dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16,17 21,12 16,7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Login -->
        <a href="login.php" class="mobile-nav-item <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                <polyline points="10,17 15,12 10,7"></polyline>
                <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
            <span class="mobile-nav-text">Login</span>
        </a>

        <!-- Quiz -->
        <a href="quiz.php" class="mobile-nav-item <?php echo $current_page === 'quiz.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="mobile-nav-text">Quiz</span>
        </a>
        <?php endif; ?>

        <!-- Theme Toggle -->
        <button class="mobile-nav-item theme-toggle-mobile" id="mobile-theme-toggle">
            <svg class="mobile-nav-icon theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <svg class="mobile-nav-icon theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <span class="mobile-nav-text">Theme</span>
        </button>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="mobile-menu-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden" aria-hidden="true"></div>

    <!-- Mobile Menu Panel -->
    <div id="mobile-menu-panel" class="mobile-menu-panel fixed top-0 right-0 h-full w-80 max-w-full bg-white dark:bg-gray-900 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out z-50">
        <!-- Menu Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Menu</h2>
            <button id="mobile-menu-close" class="touch-target p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Menu Content -->
        <div class="flex flex-col h-full">
            <!-- User Section -->
            <?php if ($is_logged_in): ?>
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold text-lg"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($user_name); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Navigation Links -->
            <div class="flex-1 py-4">
                <nav class="space-y-1">
                    <a href="/" class="mobile-nav-link flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="font-medium">Home</span>
                    </a>

                    <a href="/quiz.php" class="mobile-nav-link flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="font-medium">Take Quiz</span>
                    </a>

                    <a href="/performance.php" class="mobile-nav-link flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="font-medium">Performance</span>
                    </a>

                    <?php if ($is_logged_in): ?>
                    <a href="/profile.php" class="mobile-nav-link flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="font-medium">Profile</span>
                    </a>

                    <a href="/dashboard.php" class="mobile-nav-link flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Bottom Actions -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                <?php if ($is_logged_in): ?>
                <a href="/logout.php" class="mobile-nav-link flex items-center px-4 py-3 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="font-medium">Sign Out</span>
                </a>
                <?php else: ?>
                <div class="space-y-2">
                    <a href="/login.php" class="mobile-nav-link flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="font-medium">Sign In</span>
                    </a>
                    <a href="/quiz.php" class="mobile-nav-link flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="font-medium">Take Quiz</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Styles -->
<style>
/* Mobile Bottom Navigation Styles - Glassmorphism */
.mobile-bottom-navbar {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.1), 0 -4px 16px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.3);
    height: 70px;
    padding-bottom: env(safe-area-inset-bottom);
    z-index: 1000;
}

/* Dark mode for bottom navbar */
.dark .mobile-bottom-navbar {
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.3), 0 -4px 16px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

/* Navigation content */
.mobile-nav-content {
    height: 100%;
    max-width: 100%;
    margin: 0 auto;
}

/* Navigation items */
.mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 8px;
    min-width: 48px;
    min-height: 48px;
    text-decoration: none;
    color: #6b7280;
    transition: all 0.2s ease;
    border-radius: 8px;
    position: relative;
    flex: 1;
    max-width: 80px;
    touch-action: manipulation;
}

.mobile-nav-item:hover {
    color: #000000;
    background-color: rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.mobile-nav-item.active {
    color: #000000;
    background-color: rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3), 0 2px 8px rgba(0, 0, 0, 0.1);
}

.mobile-nav-item.active::before {
    content: '';
    position: absolute;
    top: -1px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 3px;
    background: linear-gradient(90deg, #000000, #333333);
    border-radius: 0 0 2px 2px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Dark mode for nav items */
.dark .mobile-nav-item {
    color: #e5e7eb;
}

.dark .mobile-nav-item:hover {
    color: #ffffff;
    background-color: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.dark .mobile-nav-item.active {
    color: #ffffff;
    background-color: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 2px 8px rgba(0, 0, 0, 0.3);
}

.dark .mobile-nav-item.active::before {
    background: linear-gradient(90deg, #ffffff, #e5e7eb);
    box-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
}

/* Navigation icons */
.mobile-nav-icon {
    width: 20px;
    height: 20px;
    stroke-width: 2;
    margin-bottom: 2px;
}

/* Navigation text */
.mobile-nav-text {
    font-size: 10px;
    font-weight: 500;
    line-height: 1;
    text-align: center;
}

/* User menu specific styles */
.user-menu-mobile {
    position: relative;
}

.mobile-user-button {
    background: none;
    border: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: inherit;
    cursor: pointer;
}

/* Mobile user dropdown - Opaque for better visibility */
.mobile-user-dropdown {
    position: absolute;
    bottom: 100%;
    right: 0;
    margin-bottom: 8px;
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    min-width: 200px;
    z-index: 1100;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.mobile-user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dark .mobile-user-dropdown {
    background: #111827;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
}

/* Dropdown header */
.mobile-dropdown-header {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    background: rgba(248, 250, 252, 1);
    border-radius: 12px 12px 0 0;
}

.dark .mobile-dropdown-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(31, 41, 55, 1);
}

.mobile-user-name {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
}

.dark .mobile-user-name {
    color: #f9fafb;
}

/* Dropdown items */
.mobile-dropdown-item {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    color: #374151;
    text-decoration: none;
    transition: background-color 0.2s ease;
    min-height: 48px;
    touch-action: manipulation;
}

.mobile-dropdown-item:hover {
    background: rgba(243, 244, 246, 1);
}

.mobile-dropdown-item.logout {
    color: #666666;
}

.mobile-dropdown-item.logout:hover {
    background: rgba(254, 242, 242, 1);
    color: #000000;
}

.dark .mobile-dropdown-item {
    color: #e5e7eb;
}

.dark .mobile-dropdown-item:hover {
    background: rgba(55, 65, 81, 1);
}

.dark .mobile-dropdown-item.logout {
    color: #cccccc;
}

.dark .mobile-dropdown-item.logout:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

/* Dropdown icons */
.mobile-dropdown-icon {
    width: 16px;
    height: 16px;
    margin-right: 12px;
    stroke-width: 2;
}

/* Dropdown divider */
.mobile-dropdown-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
    margin: 4px 0;
}

.dark .mobile-dropdown-divider {
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
}

/* Theme toggle specific styles */
.theme-toggle-mobile .theme-icon-dark {
    display: none;
}

.dark .theme-toggle-mobile .theme-icon-light {
    display: none;
}

.dark .theme-toggle-mobile .theme-icon-dark {
    display: block;
}

/* Touch-friendly targets */
.touch-target {
    min-width: 48px;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    touch-action: manipulation;
}

/* Mobile menu panel animations */
.mobile-menu-panel {
    will-change: transform;
}

.mobile-menu-panel.open {
    transform: translateX(0);
}

/* Mobile menu overlay */
.mobile-menu-overlay {
    transition: opacity 0.3s ease-in-out;
}

.mobile-menu-overlay.show {
    opacity: 1;
}

/* Navigation link hover effects */
.mobile-nav-link {
    position: relative;
    overflow: hidden;
}

.mobile-nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 0;
    height: 100%;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
    transition: width 0.3s ease;
}

.mobile-nav-link:hover::before {
    width: 100%;
}

/* Theme toggle animations */
.theme-icon-light,
.theme-icon-dark {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

/* Menu toggle animations */
.menu-icon-open,
.menu-icon-close {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

/* Active link styling */
.mobile-nav-link.active {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
    border-right: 3px solid #3b82f6;
}

/* Show mobile navbar only on mobile devices */
@media (max-width: 768px) {
    .mobile-bottom-navbar {
        display: block !important;
    }
}

/* Responsive adjustments */
@media (max-width: 320px) {
    .mobile-menu-panel {
        width: 100vw;
    }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .mobile-navbar {
        background: rgba(17, 24, 39, 0.95);
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .mobile-menu-panel,
    .mobile-menu-overlay,
    .mobile-nav-link::before,
    .theme-icon-light,
    .theme-icon-dark,
    .menu-icon-open,
    .menu-icon-close,
    .mobile-nav-item,
    .mobile-user-dropdown {
        transition: none;
    }
}

/* Focus styles for accessibility */
.touch-target:focus,
.mobile-nav-link:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .mobile-bottom-navbar {
        border-top-width: 2px;
    }
    
    .mobile-nav-item {
        border: 1px solid transparent;
    }
    
    .mobile-nav-item:hover,
    .mobile-nav-item.active {
        border-color: currentColor;
    }
    
    .mobile-nav-link {
        border: 1px solid transparent;
    }
    
    .mobile-nav-link:hover,
    .mobile-nav-link:focus {
        border-color: currentColor;
    }
}

/* Safe area adjustments for devices with notches */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    .mobile-bottom-navbar {
        padding-bottom: calc(8px + env(safe-area-inset-bottom));
    }
}
</style>

<!-- Mobile Navigation JavaScript -->
<script>
(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileNavbar);
    } else {
        initMobileNavbar();
    }
    
    function initMobileNavbar() {
        console.log('Initializing mobile navbar...');
        
        // Get elements
        const themeToggle = document.getElementById('mobile-theme-toggle');
        const userButton = document.getElementById('mobile-user-button');
        const userDropdown = document.getElementById('mobile-user-dropdown');
        const navItems = document.querySelectorAll('.mobile-nav-item');
        
        // Theme toggle functionality
        if (themeToggle) {
            // Initialize theme manager if not already done
            if (window.ThemeManager) {
                window.ThemeManager.init();
                
                function updateThemeIcons() {
                    const html = document.documentElement;
                    const isDark = html.classList.contains('dark');
                    const lightIcon = themeToggle.querySelector('.theme-icon-light');
                    const darkIcon = themeToggle.querySelector('.theme-icon-dark');
                    
                    if (lightIcon && darkIcon) {
                        if (isDark) {
                            lightIcon.style.display = 'none';
                            darkIcon.style.display = 'block';
                        } else {
                            lightIcon.style.display = 'block';
                            darkIcon.style.display = 'none';
                        }
                    }
                }
                
                // Update mobile icons when theme changes
                window.ThemeManager.addCallback(updateThemeIcons);
                
                // Initialize icons
                updateThemeIcons();
                
                themeToggle.addEventListener('click', function() {
                    window.ThemeManager.toggle();
                });
                themeToggle.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    window.ThemeManager.toggle();
                });
            } else {
                // Fallback if ThemeManager is not available
                function toggleTheme() {
                    console.log('Toggling theme');
                    const html = document.documentElement;
                    const isDark = html.classList.contains('dark');
                    
                    if (isDark) {
                        html.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    } else {
                        html.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    }
                    
                    updateThemeIcons();
                }
                
                function updateThemeIcons() {
                    const html = document.documentElement;
                    const isDark = html.classList.contains('dark');
                    const lightIcon = themeToggle.querySelector('.theme-icon-light');
                    const darkIcon = themeToggle.querySelector('.theme-icon-dark');
                    
                    if (lightIcon && darkIcon) {
                        if (isDark) {
                            lightIcon.style.display = 'none';
                            darkIcon.style.display = 'block';
                        } else {
                            lightIcon.style.display = 'block';
                            darkIcon.style.display = 'none';
                        }
                    }
                }
                
                // Initialize theme
                const savedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                
                if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
                
                updateThemeIcons();
                
                themeToggle.addEventListener('click', toggleTheme);
                themeToggle.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    toggleTheme();
                });
            }
        }
        
        // User dropdown functionality
        if (userButton && userDropdown) {
            userButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleUserDropdown();
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userButton.contains(e.target) && !userDropdown.contains(e.target)) {
                    closeUserDropdown();
                }
            });
            
            // Close dropdown on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeUserDropdown();
                }
            });
        }
        
        function toggleUserDropdown() {
            if (userDropdown.classList.contains('show')) {
                closeUserDropdown();
            } else {
                openUserDropdown();
            }
        }
        
        function openUserDropdown() {
            userDropdown.classList.add('show');
        }
        
        function closeUserDropdown() {
            userDropdown.classList.remove('show');
        }
        
        // Active link highlighting
        function updateActiveLink() {
            const currentPath = window.location.pathname;
            const currentPage = currentPath.split('/').pop() || 'index.php';
            
            navItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href.includes(currentPage)) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }
        
        // Initialize active link
        updateActiveLink();
        
        // Touch event handling for better mobile experience
        navItems.forEach(item => {
            // Add touch feedback
            item.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            }, { passive: true });
            
            item.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            }, { passive: true });
            
            item.addEventListener('touchcancel', function() {
                this.style.transform = 'scale(1)';
            }, { passive: true });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeUserDropdown();
            }
        });
        
        // Show mobile navbar on mobile devices
        if (window.innerWidth <= 768) {
            const mobileNavbar = document.querySelector('.mobile-bottom-navbar');
            if (mobileNavbar) {
                mobileNavbar.style.display = 'block';
            }
        }
        
        console.log('Mobile navbar initialized successfully');
    }
})();
</script>