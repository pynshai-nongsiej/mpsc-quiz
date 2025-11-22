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

<!-- Mobile Top Navigation Bar -->
<nav class="mobile-top-navbar fixed top-0 left-0 right-0 z-50 md:hidden glassmorphic-mobile border-b border-white/20 dark:border-white/10" style="display: block;">
    <!-- Top Navigation Header -->
    <div class="flex items-center justify-between px-4 py-4">
        <!-- Logo -->
        <a href="index.php" class="flex items-center gap-2 text-black dark:text-white">
            <div class="w-6 h-6 flex items-center justify-center">
                <svg fill="currentColor" height="100%" viewBox="0 0 24 24" width="100%" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path>
                    <path d="M12 4c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"></path>
                </svg>
            </div>
            <span class="text-lg font-bold tracking-wider">MPSC</span>
        </a>

        <!-- Controls -->
        <div class="flex items-center gap-2">
            <!-- Theme Toggle -->
            <button class="mobile-glass-btn theme-toggle-mobile" id="mobile-theme-toggle">
                <svg class="w-5 h-5 theme-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg class="w-5 h-5 theme-icon-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </button>

            <!-- Menu Button -->
            <button class="mobile-glass-btn" id="mobile-menu-button">
                <svg class="w-6 h-6" id="menu-icon-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <svg class="w-6 h-6 hidden" id="menu-icon-close" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="hidden glassmorphic-mobile border-t border-white/20 dark:border-white/10 absolute top-full left-0 right-0 shadow-xl">
        <div class="px-4 py-4 space-y-2">
            <!-- Home -->
            <a href="index.php" class="mobile-nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Home</span>
            </a>
            
            <?php if ($is_logged_in): ?>
            <!-- Quiz History -->
            <a href="quiz-history.php" class="mobile-nav-link <?php echo $current_page === 'quiz-history.php' ? 'active' : ''; ?>">
                <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Quiz History</span>
            </a>
            
            <!-- Performance -->
            <a href="performance.php" class="mobile-nav-link <?php echo $current_page === 'performance.php' ? 'active' : ''; ?>">
                <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span>Performance</span>
            </a>
            
            <div class="border-t border-white/20 dark:border-white/10 my-3"></div>
            
            <div class="px-4 py-2 text-sm text-black/60 dark:text-white/60">
                Welcome, <?= htmlspecialchars($user_name) ?>
            </div>
            
            <!-- Logout -->
            <a href="logout.php" class="mobile-nav-link logout">
                <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Logout</span>
            </a>
            
            <?php else: ?>
            <!-- Login -->
            <a href="login.php" class="mobile-nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">
                <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span>Log In</span>
            </a>
            
            <!-- Register -->
            <a href="register.php" class="mobile-nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                <svg class="mobile-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>Register</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

</nav>

<!-- Mobile Navigation Styles -->
<style>
/* Glassmorphism Mobile Navigation Styles */
.glassmorphic-mobile {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
}

.dark .glassmorphic-mobile {
    background: rgba(20, 20, 20, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}

.mobile-glass-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: inherit;
    transition: all 0.3s ease;
    cursor: pointer;
}

.mobile-glass-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

.dark .mobile-glass-btn {
    background: rgba(20, 20, 20, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.dark .mobile-glass-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Navigation content */
.mobile-nav-content {
    height: 100%;
    max-width: 100%;
    margin: 0 auto;
}

/* Mobile Navigation Links */
.mobile-nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    color: inherit;
    text-decoration: none;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.mobile-nav-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(4px);
}

.mobile-nav-link.active {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.mobile-nav-link.logout {
    color: #ef4444;
}

.mobile-nav-link.logout:hover {
    background: rgba(239, 68, 68, 0.2);
}

.dark .mobile-nav-link {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.dark .mobile-nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
}

.dark .mobile-nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
}

/* Navigation icons */
.mobile-nav-icon {
    width: 1.25rem;
    height: 1.25rem;
    margin-right: 0.75rem;
    stroke-width: 2;
}

/* Mobile menu animations */
#mobile-menu {
    transition: all 0.3s ease-in-out;
    transform: translateY(-10px);
    opacity: 0;
}

#mobile-menu.show {
    transform: translateY(0);
    opacity: 1;
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
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIconOpen = document.getElementById('menu-icon-open');
        const menuIconClose = document.getElementById('menu-icon-close');
        
        // Theme toggle functionality
        if (themeToggle) {
            function toggleTheme() {
                const html = document.documentElement;
                const isDark = html.classList.contains('dark');
                
                if (isDark) {
                    html.classList.remove('dark');
                    html.classList.add('light');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.remove('light');
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
            if (savedTheme) {
                document.documentElement.classList.remove('dark', 'light');
                document.documentElement.classList.add(savedTheme);
            }
            
            updateThemeIcons();
            
            themeToggle.addEventListener('click', toggleTheme);
        }
        
        // Mobile menu functionality
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isOpen = mobileMenu.classList.contains('show');
                
                if (isOpen) {
                    closeMobileMenu();
                } else {
                    openMobileMenu();
                }
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!mobileMenuButton.contains(e.target) && !mobileMenu.contains(e.target)) {
                    closeMobileMenu();
                }
            });
            
            // Close mobile menu when clicking on menu items
            const menuLinks = mobileMenu.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    closeMobileMenu();
                });
            });
            
            // Close mobile menu when window is resized to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    closeMobileMenu();
                }
            });
            
            function openMobileMenu() {
                mobileMenu.classList.remove('hidden');
                setTimeout(() => {
                    mobileMenu.classList.add('show');
                }, 10);
                
                if (menuIconOpen && menuIconClose) {
                    menuIconOpen.classList.add('hidden');
                    menuIconClose.classList.remove('hidden');
                }
            }
            
            function closeMobileMenu() {
                mobileMenu.classList.remove('show');
                setTimeout(() => {
                    mobileMenu.classList.add('hidden');
                }, 300);
                
                if (menuIconOpen && menuIconClose) {
                    menuIconOpen.classList.remove('hidden');
                    menuIconClose.classList.add('hidden');
                }
            }
        }
        
        // Show mobile navbar on mobile devices
        if (window.innerWidth <= 768) {
            const mobileNavbar = document.querySelector('.mobile-top-navbar');
            if (mobileNavbar) {
                mobileNavbar.style.display = 'block';
            }
        }
        
        console.log('Mobile navbar initialized successfully');
    }
})();
</script>