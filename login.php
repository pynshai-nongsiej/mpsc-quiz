<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            // Find user by email or username
            $user = fetchOne("SELECT * FROM users WHERE email = ? OR username = ?", [$username, $username]);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful - use session helper function
                loginUser($user['id'], $user);
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid email/username or password';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style type="text/tailwindcss">
        :root {
            --bg-primary: #000000;
            --bg-secondary: #000000;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --input-border: rgba(255, 255, 255, 0.3);
            --button-bg: rgba(255, 255, 255, 0.1);
            --button-hover: rgba(255, 255, 255, 0.2);
        }
        
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: rgba(30, 41, 59, 0.1);
            --input-border: rgba(30, 41, 59, 0.3);
            --button-bg: rgba(30, 41, 59, 0.1);
            --button-hover: rgba(30, 41, 59, 0.2);
        }
        
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .form-input {
            @apply w-full bg-transparent rounded-lg py-3 px-4 focus:ring-1 transition-all duration-300;
            border: 1px solid var(--input-border);
            color: var(--text-primary);
        }
        
        .form-input::placeholder {
            color: var(--text-secondary);
        }
        
        .form-input:focus {
            border-color: var(--input-border);
            box-shadow: 0 0 0 1px var(--input-border);
        }
        
        .form-label {
            @apply absolute left-4 -top-2.5 text-xs px-1 transition-all duration-300 font-medium;
            color: var(--text-primary);
            background-color: var(--bg-secondary);
            z-index: 10;
        }
        
        .form-input:focus + .form-label {
            @apply -top-2.5 text-xs;
            color: #3b82f6;
        }
        
        .form-input:not(:placeholder-shown) + .form-label {
            @apply -top-2.5 text-xs;
            color: var(--text-primary);
        }
        
        .form-input:placeholder-shown + .form-label {
            @apply top-3.5 text-base;
            color: var(--text-secondary);
        }
        
        .login-button {
            @apply w-full font-bold py-3 rounded-lg transition-all duration-300;
            background-color: var(--button-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        
        .login-button:hover {
            background-color: var(--button-hover);
            box-shadow: 0 0 25px rgba(255, 255, 255, 0.3);
        }
        
        [data-theme="light"] .login-button:hover {
            box-shadow: 0 0 25px rgba(30, 41, 59, 0.3);
        }
        
        .theme-container {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
        }
        
        /* Password Toggle Styling */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.3s ease;
            z-index: 20;
        }
        
        .password-toggle:hover {
            color: var(--text-primary);
        }
        
        .password-input-container {
            position: relative;
        }
        
        .password-input-container .form-input {
            padding-right: 3rem;
        }

    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/mobile_navbar.php'; ?>
    <div class="flex items-center justify-center min-h-screen pt-20">
        <div class="w-full max-w-md p-8 space-y-8 rounded-2xl shadow-2xl theme-container">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-2">Welcome Back</h1>
            <p class="text-white/70">Sign in to continue</p>
        </div>

        
        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 px-4 py-3 rounded-lg mb-6" style="color: #fecaca;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-500/20 border border-green-500/50 px-4 py-3 rounded-lg mb-6" style="color: #bbf7d0;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-8">
            <div class="relative">
                <input class="form-input peer" id="username" name="username" placeholder=" " type="text" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <label class="form-label" for="username">Email</label>
            </div>
            <div class="relative password-input-container">
                <input class="form-input peer" id="password" name="password" placeholder=" " type="password" required>
                <label class="form-label" for="password">Password</label>
                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
            <div class="flex items-center justify-between">
                <a class="text-sm font-thin transition-colors" href="#" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">Forgot Password?</a>
            </div>
            <button class="login-button" type="submit">Login</button>
            <div class="flex items-center justify-center space-x-2 my-4">
                <hr class="w-full" style="border-color: var(--border-color);">
                <span class="px-2 text-sm" style="color: var(--text-secondary);">OR</span>
                <hr class="w-full" style="border-color: var(--border-color);">
            </div>
            <a class="w-full flex items-center justify-center gap-3 bg-transparent font-semibold py-3 rounded-lg transition-all duration-300" href="register.php" role="button" style="color: var(--text-primary); border: 1px solid var(--border-color);" onmouseover="this.style.backgroundColor='var(--button-hover)'" onmouseout="this.style.backgroundColor='transparent'">
                Register
            </a>
        </form>
        </div>
    </div>

    <script>
        // Theme initialization
        document.addEventListener('DOMContentLoaded', function() {
            const html = document.documentElement;
            
            // Check for saved theme preference or default to light mode
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                html.classList.toggle('dark', savedTheme === 'dark');
                document.body.setAttribute('data-theme', savedTheme);
            } else {
                // Check system preference
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                html.classList.toggle('dark', prefersDark);
                document.body.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
                localStorage.setItem('theme', prefersDark ? 'dark' : 'light');
            }
            
            // Listen for theme changes from navbar
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme') {
                    const newTheme = e.newValue;
                    html.classList.toggle('dark', newTheme === 'dark');
                    document.body.setAttribute('data-theme', newTheme);
                }
            });
            
            // Also listen for theme changes within the same page
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const isDark = html.classList.contains('dark');
                        document.body.setAttribute('data-theme', isDark ? 'dark' : 'light');
                    }
                });
            });
            
            observer.observe(html, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
        
        // Password visibility toggle function
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            
            // Toggle input type
            input.type = isPassword ? 'text' : 'password';
            
            // Toggle icon
            const svg = button.querySelector('svg');
            if (isPassword) {
                // Show eye-off icon
                svg.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                // Show eye icon
                svg.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        }
        
        // Add CSRF token to form
        const form = document.querySelector('form');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo generateCSRFToken(); ?>';
        form.appendChild(csrfInput);
    </script>
</body>
</html>