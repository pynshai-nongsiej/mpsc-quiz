<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';

// Generate CSRF token
$csrf_token = generateCSRFToken();

// isLoggedIn function is now available from session.php

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password)) {
            $error = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
        } else {
            try {
                // Check if email already exists
                $existingUser = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
                
                if ($existingUser) {
                    $error = "Email already exists. Please use a different email.";
                } else {
                    // Hash password with stronger algorithm
                    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
                        'memory_cost' => 65536,
                        'time_cost' => 4,
                        'threads' => 3
                    ]);
                    
                    // Insert new user using helper function
                    $userData = [
                        'username' => $email, // Use email as username
                        'email' => $email,
                        'password_hash' => $hashedPassword,
                        'full_name' => $fullName,
                        'created_at' => date(DATE_FORMAT)
                    ];
                    
                    $userId = insertRecord('users', $userData);
                    
                    if ($userId) {
                        $success = "Registration successful! You can now login.";
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
            } catch (Exception $e) {
                $error = "Registration failed. Please try again.";
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900" onload="this.rel='stylesheet'" as="style">
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
        
        .floating-label {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 500;
            pointer-events: none;
            transition: all 0.3s ease;
            z-index: 10;
            background-color: var(--bg-secondary);
            padding: 0 4px;
        }
        
        .form-input:focus + .floating-label {
            top: 8px;
            font-size: 12px;
            color: #3b82f6;
            transform: translateY(0);
        }
        
        .form-input:not(:placeholder-shown) + .floating-label {
            top: 8px;
            font-size: 12px;
            color: var(--text-primary);
            transform: translateY(0);
        }
        
        .form-input {
            padding-top: 1.5rem;
            border: 1px solid var(--input-border);
            color: var(--text-primary) !important;
            background-color: var(--button-bg) !important;
        }
        
        .form-input:focus {
            border-color: var(--text-primary) !important;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2) !important;
            background-color: var(--button-hover) !important;
        }
        
        .form-input::placeholder {
            color: var(--text-secondary);
        }
        
        .glowing-button {
            background-color: var(--button-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            box-shadow: 0 0 5px rgba(255, 255, 255, 0.8), 0 0 10px rgba(255, 255, 255, 0.6), 0 0 20px rgba(255, 255, 255, 0.4), 0 0 40px rgba(255, 255, 255, 0.2);
        }
        
        .glowing-button {
                background-color: var(--button-bg) !important;
                color: var(--text-primary) !important;
                transition: all 0.3s ease;
            }
            
            .glowing-button:hover {
                background-color: var(--button-hover-bg) !important;
                box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
                transform: translateY(-2px);
            }
        
        [data-theme="light"] .glowing-button {
            box-shadow: 0 0 5px rgba(30, 41, 59, 0.8), 0 0 10px rgba(30, 41, 59, 0.6), 0 0 20px rgba(30, 41, 59, 0.4), 0 0 40px rgba(30, 41, 59, 0.2);
        }
        
        .theme-container {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
        }
        
        .main-title {
            color: var(--text-primary);
        }
        
        .overlay {
            background-color: var(--bg-primary);
            opacity: 0.5;
        }
        
        [data-theme="light"] .overlay {
            background-color: var(--bg-secondary);
            opacity: 0.3;
        }
        
        /* Success and Error Message Styling */
        .success-message {
            background-color: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.5);
            color: #10b981;
        }
        
        [data-theme="light"] .success-message {
            background-color: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
            color: #059669;
        }
        
        .success-link {
            color: #10b981;
            opacity: 0.8;
        }
        
        [data-theme="light"] .success-link {
            color: #059669;
            opacity: 0.8;
        }
        
        .error-message {
            background-color: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            color: #f87171;
        }
        
        [data-theme="light"] .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #dc2626;
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
<body class="antialiased font-inter">
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/mobile_navbar.php'; ?>
<div class="relative flex min-h-screen flex-col items-center justify-center pt-20">
<div class="absolute inset-0 overlay"></div>
<div class="w-full max-w-md p-6">
<div class="relative rounded-xl p-8 shadow-2xl backdrop-blur-lg theme-container">
<h2 class="text-center text-3xl font-bold tracking-tight main-title">
                Create an Account
            </h2>

<!-- Error/Success Messages -->
            <?php if (!empty($error)): ?>
                <div class="mb-4 rounded-lg border p-3 error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="mb-4 rounded-lg border p-3 success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-2">
                        <a href="login.php" class="underline success-link" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Click here to login</a>
                    </div>
                </div>
            <?php endif; ?>

<form method="POST" action="" class="mt-8 space-y-6" id="registerForm">
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
<div class="relative">
<input autocomplete="name" class="form-input peer h-14 w-full rounded-lg border-none px-4 pb-4 placeholder-transparent ring-2 ring-transparent transition-all focus:outline-none focus:ring-2 focus:ring-opacity-50" id="name" name="full_name" type="text" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
<label class="floating-label" for="name">Name</label>
</div>
<div class="relative">
<input autocomplete="email" class="form-input peer h-14 w-full rounded-lg border-none px-4 pb-4 placeholder-transparent ring-2 ring-transparent transition-all focus:outline-none focus:ring-2 focus:ring-opacity-50" id="email" name="email" type="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
<label class="floating-label" for="email">Email address</label>
</div>
<div class="relative password-input-container">
<input autocomplete="new-password" class="form-input peer h-14 w-full rounded-lg border-none px-4 pb-4 placeholder-transparent ring-2 ring-transparent transition-all focus:outline-none focus:ring-2 focus:ring-opacity-50" id="password" name="password" type="password" required>
<label class="floating-label" for="password">Password</label>
<button type="button" class="password-toggle" onclick="togglePassword('password', this)">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
<circle cx="12" cy="12" r="3"></circle>
</svg>
</button>
</div>
<div class="relative password-input-container">
<input autocomplete="new-password" class="form-input peer h-14 w-full rounded-lg border-none px-4 pb-4 placeholder-transparent ring-2 ring-transparent transition-all focus:outline-none focus:ring-2 focus:ring-opacity-50" id="confirm-password" name="confirm_password" type="password" required>
<label class="floating-label" for="confirm-password">Confirm Password</label>
<button type="button" class="password-toggle" onclick="togglePassword('confirm-password', this)">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
<circle cx="12" cy="12" r="3"></circle>
</svg>
</button>
</div>

<div>
<button class="glowing-button w-full rounded-lg px-4 py-3.5 text-center text-base font-bold shadow-lg transition-all hover:shadow-xl hover:scale-105" type="submit">
                        Register
                    </button>
</div>
</form>
<div class="flex items-center justify-center space-x-2 my-4">
                <hr class="w-full" style="border-color: var(--border-color);">
                <span class="px-2 text-sm" style="color: var(--text-secondary);">OR</span>
                <hr class="w-full" style="border-color: var(--border-color);">
            </div>
            <a class="w-full flex items-center justify-center gap-3 bg-transparent font-semibold py-3 rounded-lg transition-all duration-300" href="login.php" role="button" style="color: var(--text-primary); border: 1px solid var(--border-color);" onmouseover="this.style.backgroundColor='var(--button-hover)'" onmouseout="this.style.backgroundColor='transparent'">
                Login
            </a>
            <p class="text-center" style="color: var(--text-secondary);">
                Already have an account? 
                <a href="login.php" class="underline transition-colors" style="color: var(--text-primary);" onmouseover="this.style.color='var(--text-secondary)'" onmouseout="this.style.color='var(--text-primary)'">
                    Sign in here
                </a>
            </p>
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
        
        // CSRF token is now included directly in the form
    </script>
</body></html>