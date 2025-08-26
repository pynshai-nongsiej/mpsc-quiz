<?php
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

<!-- Desktop Navigation -->
<nav class="navbar-desktop">
    <div class="navbar-content">
        <!-- Logo/Brand -->
        <div class="navbar-brand">
            <a href="index.php" class="brand-link">
                <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polygon points="10,8 16,12 10,16"></polygon>
                </svg>
                <span class="brand-text">MPSC Quiz Portal</span>
            </a>
        </div>

        <!-- Main Navigation -->
        <div class="navbar-nav">
            <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9,22 9,12 15,12 15,22"></polyline>
                </svg>
                <span class="nav-text">Home</span>
            </a>
            <?php if ($is_logged_in): ?>
                <a href="quiz-history.php" class="nav-link <?php echo $current_page === 'quiz-history.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    <span class="nav-text">History</span>
                </a>
                <a href="performance.php" class="nav-link <?php echo $current_page === 'performance.php' ? 'active' : ''; ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M3 3v18h18"></path>
                        <path d="m19 9-5 5-4-4-3 3"></path>
                    </svg>
                    <span class="nav-text">Performance</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- User Actions -->
        <div class="navbar-actions">
            <?php if ($is_logged_in): ?>
                <!-- User Menu -->
                <div class="user-menu" id="user-menu">
                    <button class="user-menu-button" id="user-menu-button">
                        <svg class="user-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="profile.php" class="dropdown-item">
                            <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="dropdown-text">Profile</span>
                        </a>
                        <a href="quiz-history.php" class="dropdown-item">
                            <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12,6 12,12 16,14"></polyline>
                            </svg>
                            <span class="dropdown-text">Quiz History</span>
                        </a>
                        <a href="performance.php" class="dropdown-item">
                            <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M3 3v18h18"></path>
                                <path d="m19 9-5 5-4-4-3 3"></path>
                            </svg>
                            <span class="dropdown-text">Performance</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item logout">
                            <svg class="dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16,17 21,12 16,7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span class="dropdown-text">Logout</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Auth Buttons -->
                <div class="auth-buttons">
                    <a href="login.php" class="auth-button login-button">
                        <svg class="auth-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10,17 15,12 10,7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        <span class="auth-text">Login</span>
                    </a>
                    <a href="register.php" class="auth-button register-button">
                        <svg class="auth-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="auth-text">Register</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Theme Toggle -->
            <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
                <svg id="sun-icon" class="theme-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg id="moon-icon" class="theme-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </button>
        </div>
    </div>
    

</nav>



<style>
/* Reset and Base Styles */
* {
    box-sizing: border-box;
}

/* Desktop Navigation */
.navbar-desktop {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 1000;
    display: block;
}

.navbar-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 4rem;
}

/* Brand Styles */
.navbar-brand {
    display: flex;
    align-items: center;
}

.brand-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #000000;
    font-weight: 600;
    font-size: 1.25rem;
    transition: all 0.2s ease;
}

.brand-link:hover {
    color: #374151;
}

.brand-icon {
    width: 1.5rem;
    height: 1.5rem;
    margin-right: 0.5rem;
    stroke-width: 2;
}

.brand-text {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Navigation Links */
.navbar-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    text-decoration: none;
    color: #6b7280;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    font-weight: 500;
}

.nav-link:hover {
    background: #f3f4f6;
    color: #000000;
}

.nav-link.active {
    background: #000000;
    color: #ffffff;
}

.nav-icon {
    width: 1.1rem;
    height: 1.1rem;
    margin-right: 0.5rem;
    stroke-width: 2;
}

.nav-text {
    font-size: 0.9rem;
}

/* User Actions */
.navbar-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* User Menu */
.user-menu {
    position: relative;
}

.user-menu-button {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    color: #000000;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.user-menu-button:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.user-icon {
    width: 1.1rem;
    height: 1.1rem;
    margin-right: 0.5rem;
    stroke-width: 2;
}

.user-name {
    margin-right: 0.5rem;
    font-size: 0.9rem;
}

.dropdown-arrow {
    width: 0.8rem;
    height: 0.8rem;
    stroke-width: 2;
    transition: transform 0.2s ease;
}

.user-menu.active .dropdown-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1000;
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: #f9fafb;
    color: #000000;
}

.dropdown-item.logout:hover {
    background: #fef2f2;
    color: #dc2626;
}

.dropdown-icon {
    width: 1rem;
    height: 1rem;
    margin-right: 0.75rem;
    stroke-width: 2;
}

.dropdown-text {
    font-size: 0.9rem;
    font-weight: 500;
}

.dropdown-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 0.5rem 0;
}

/* Auth Buttons */
.auth-buttons {
    display: flex;
    gap: 0.5rem;
}

.auth-button {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    text-decoration: none;
    border-radius: 0.375rem;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.login-button {
    color: #6b7280;
    background: #f9fafb;
    border-color: #e5e7eb;
}

.login-button:hover {
    background: #f3f4f6;
    color: #000000;
}

.register-button {
    color: #ffffff;
    background: #000000;
    border-color: #000000;
}

.register-button:hover {
    background: #374151;
    border-color: #374151;
}

.auth-icon {
    width: 1rem;
    height: 1rem;
    margin-right: 0.5rem;
    stroke-width: 2;
}

.auth-text {
    font-size: 0.9rem;
}

/* Theme Toggle */
.theme-toggle {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.5rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.theme-toggle:hover {
    background: #f3f4f6;
    color: #000000;
}

.theme-icon {
    width: 1.2rem;
    height: 1.2rem;
    stroke-width: 2;
}









/* Responsive Design */
@media (max-width: 768px) {
    .navbar-desktop {
        display: none;
    }
}

@media (max-width: 640px) {
    .navbar-content {
        padding: 0 0.5rem;
    }
    
    .brand-text {
        display: none;
    }
    
    .nav-text {
        display: none;
    }
    
    .auth-text {
        display: none;
    }
    
    .user-name {
        display: none;
    }
    
    .navbar-nav {
        gap: 0.25rem;
    }
    
    .nav-link {
        padding: 0.5rem;
    }
    
    .auth-button {
        padding: 0.5rem;
    }
}

@media (min-width: 1024px) {
    .navbar-content {
        padding: 0 2rem;
    }
    
    .navbar-nav {
        gap: 1rem;
    }
}

/* Dark theme support */
.dark .navbar-desktop {
    background: #000000;
    border-bottom-color: #374151;
}

.dark .brand-link {
    color: #ffffff;
}

.dark .brand-link:hover {
    color: #d1d5db;
}

.dark .nav-link {
    color: #9ca3af;
}

.dark .nav-link:hover {
    background: #1f2937;
    color: #ffffff;
}

.dark .nav-link.active {
    background: #ffffff;
    color: #000000;
}

.dark .user-menu-button {
    background: #1f2937;
    border-color: #374151;
    color: #ffffff;
}

.dark .user-menu-button:hover {
    background: #374151;
    border-color: #4b5563;
}

.dark .user-dropdown {
    background: #1f2937;
    border-color: #374151;
}

.dark .dropdown-item {
    color: #d1d5db;
    border-bottom-color: #374151;
}

.dark .dropdown-item:hover {
    background: #374151;
    color: #ffffff;
}

.dark .dropdown-item.logout:hover {
    background: #7f1d1d;
    color: #fca5a5;
}

.dark .dropdown-divider {
    background: #374151;
}

.dark .login-button {
    background: #1f2937;
    border-color: #374151;
    color: #d1d5db;
}

.dark .login-button:hover {
    background: #374151;
    color: #ffffff;
}

.dark .register-button {
    background: #ffffff;
    border-color: #ffffff;
    color: #000000;
}

.dark .register-button:hover {
    background: #d1d5db;
    border-color: #d1d5db;
}

.dark .theme-toggle {
    background: #1f2937;
    border-color: #374151;
    color: #9ca3af;
}

.dark .theme-toggle:hover {
    background: #374151;
    color: #ffffff;
}
</style>

<script>
// User dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');
    const userMenu = document.getElementById('user-menu');

    if (userMenuButton && userDropdown) {
        userMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            userMenu.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target)) {
                userDropdown.classList.remove('show');
                userMenu.classList.remove('active');
            }
        });

        // Close dropdown when pressing escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userDropdown.classList.remove('show');
                userMenu.classList.remove('active');
            }
        });
    }

    // Desktop-only user dropdown functionality can be added here if needed
});

// Global Theme Management System
window.ThemeManager = window.ThemeManager || {
    initialized: false,
    callbacks: [],
    
    init: function() {
        if (this.initialized) return;
        
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        
        if (savedTheme) {
            html.classList.toggle('dark', savedTheme === 'dark');
        } else {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            html.classList.toggle('dark', prefersDark);
            localStorage.setItem('theme', prefersDark ? 'dark' : 'light');
        }
        
        this.initialized = true;
        this.notifyCallbacks();
    },
    
    toggle: function() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        html.classList.toggle('dark');
        const newTheme = isDark ? 'light' : 'dark';
        localStorage.setItem('theme', newTheme);
        this.notifyCallbacks();
    },
    
    isDark: function() {
        return document.documentElement.classList.contains('dark');
    },
    
    addCallback: function(callback) {
        this.callbacks.push(callback);
        if (this.initialized) {
            callback(this.isDark());
        }
    },
    
    notifyCallbacks: function() {
        const isDark = this.isDark();
        this.callbacks.forEach(callback => callback(isDark));
    }
};

// Desktop Theme Toggle Implementation
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const sunIcon = document.getElementById('sun-icon');
    const moonIcon = document.getElementById('moon-icon');
    
    // Initialize theme manager
    window.ThemeManager.init();
    
    // Update desktop icons when theme changes
    window.ThemeManager.addCallback(function(isDark) {
        if (sunIcon && moonIcon) {
            sunIcon.style.display = isDark ? 'none' : 'block';
            moonIcon.style.display = isDark ? 'block' : 'none';
        }
    });
    
    // Desktop theme toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            window.ThemeManager.toggle();
        });
    }
});
</script>