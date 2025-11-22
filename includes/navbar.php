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

<style>
:root {
    --bg-light: #ffffff;
    --fg-light: #000000;
    --bg-dark: #000000;
    --fg-dark: #ffffff;
    --glass-bg-light: rgba(255, 255, 255, 0.5);
    --glass-border-light: rgba(0, 0, 0, 0.1);
    --glass-bg-dark: rgba(29, 29, 29, 0.5);
    --glass-border-dark: rgba(255, 255, 255, 0.2);
    --subtle-text: #374151;
    --header-glass-bg: rgba(255, 255, 255, 0.75);
    --header-glass-border: rgba(0, 0, 0, 0.08);
}
html.light {
    --bg-color: var(--bg-light);
    --fg-color: var(--fg-light);
    --glass-bg: var(--glass-bg-light);
    --glass-border: var(--glass-border-light);
    --subtle-text: #374151;
    --header-glass-bg: rgba(255, 255, 255, 0.75);
    --header-glass-border: rgba(0, 0, 0, 0.08);
}
html.dark {
    --bg-color: var(--bg-dark);
    --fg-color: var(--fg-dark);
    --glass-bg: var(--glass-bg-dark);
    --glass-border: var(--glass-border-dark);
    --subtle-text: #d1d5db;
    --header-glass-bg: rgba(17, 17, 17, 0.75);
    --header-glass-border: rgba(255, 255, 255, 0.12);
}

</style>

<!-- Desktop Navigation -->
<header class="hidden md:flex fixed top-0 left-0 right-0 z-50 items-center justify-between whitespace-nowrap px-4 sm:px-10 lg:px-20 py-5 bg-[var(--header-glass-bg)] backdrop-blur-md border-b border-[var(--header-glass-border)] transition-colors duration-500">
<div class="flex items-center gap-3">
<a href="index.php" class="flex items-center gap-3 text-[var(--fg-color)]">
<div class="w-8 h-8 flex items-center justify-center">
<svg fill="currentColor" height="100%" viewBox="0 0 24 24" width="100%" xmlns="http://www.w3.org/2000/svg">
<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path>
<path d="M12 4c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"></path>
</svg>
</div>
<span class="text-2xl font-bold tracking-widest">MPSC</span>
</a>
</div>

<!-- Desktop Navigation Links -->
<div class="flex items-center gap-2">
<a href="index.php" class="relative flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 text-sm font-bold leading-normal tracking-[0.015em] transition-all duration-300 group">
<div class="absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-md border border-[var(--glass-border)] rounded-lg transition-all duration-300 group-hover:bg-[rgba(255,255,255,0.2)] dark:group-hover:bg-[rgba(0,0,0,0.2)]"></div>
<span class="truncate z-10">Home</span>
</a>
<?php if ($is_logged_in): ?>
<a href="quiz-history.php" class="relative flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 text-sm font-bold leading-normal tracking-[0.015em] transition-all duration-300 group">
<div class="absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-md border border-[var(--glass-border)] rounded-lg transition-all duration-300 group-hover:bg-[rgba(255,255,255,0.2)] dark:group-hover:bg-[rgba(0,0,0,0.2)]"></div>
<span class="truncate z-10">History</span>
</a>
<a href="performance.php" class="relative flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 text-sm font-bold leading-normal tracking-[0.015em] transition-all duration-300 group">
<div class="absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-md border border-[var(--glass-border)] rounded-lg transition-all duration-300 group-hover:bg-[rgba(255,255,255,0.2)] dark:group-hover:bg-[rgba(0,0,0,0.2)]"></div>
<span class="truncate z-10">Performance</span>
</a>
<?php endif; ?>
</div>

<div class="flex items-center justify-end gap-2">
<?php if ($is_logged_in): ?>
<span class="text-sm text-[var(--subtle-text)] hidden lg:block">Welcome, <?= htmlspecialchars($user_name) ?></span>
<a href="logout.php" class="relative flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 text-sm font-bold leading-normal tracking-[0.015em] transition-all duration-300 group">
<div class="absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-md border border-[var(--glass-border)] rounded-lg transition-all duration-300 group-hover:bg-[rgba(255,255,255,0.2)] dark:group-hover:bg-[rgba(0,0,0,0.2)]"></div>
<span class="truncate z-10">Logout</span>
</a>
<?php else: ?>
<a href="login.php" class="relative flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 text-sm font-bold leading-normal tracking-[0.015em] transition-all duration-300 group">
<div class="absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-md border border-[var(--glass-border)] rounded-lg transition-all duration-300 group-hover:bg-[rgba(255,255,255,0.2)] dark:group-hover:bg-[rgba(0,0,0,0.2)]"></div>
<span class="truncate z-10">Log In</span>
</a>
<a href="register.php" class="relative flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 text-sm font-bold leading-normal tracking-[0.015em] transition-all duration-300 group">
<div class="absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-md border border-[var(--glass-border)] rounded-lg transition-all duration-300 group-hover:bg-[rgba(255,255,255,0.2)] dark:group-hover:bg-[rgba(0,0,0,0.2)]"></div>
<span class="truncate z-10">Register</span>
</a>
<?php endif; ?>
<button class="relative flex items-center justify-center size-10 rounded-lg group" onclick="toggleTheme()">
<div class="absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-md border border-[var(--glass-border)] rounded-lg transition-all duration-300 group-hover:bg-[rgba(255,255,255,0.2)] dark:group-hover:bg-[rgba(0,0,0,0.2)]"></div>
<span class="material-symbols-outlined !text-2xl z-10 block dark:hidden">dark_mode</span>
<span class="material-symbols-outlined !text-2xl z-10 hidden dark:block">light_mode</span>
</button>
</div>
</header>

<?php 
// Include mobile navigation for mobile devices only
if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT'])) {
    include __DIR__ . '/mobile_navbar.php';
} else {
    // Simple mobile fallback for responsive design
    echo '<div class="md:hidden">';
    include __DIR__ . '/mobile_navbar.php';
    echo '</div>';
}
?>

<script>
function toggleTheme() {
    document.documentElement.classList.toggle('dark');
    document.documentElement.classList.toggle('light');
    // Save theme preference
    const isDark = document.documentElement.classList.contains('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// Load saved theme on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.classList.remove('dark', 'light');
        document.documentElement.classList.add(savedTheme);
    }
    
});
</script>
