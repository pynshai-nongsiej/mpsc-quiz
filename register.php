<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';

// Generate CSRF token
$csrf_token = generateCSRFToken();

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
                    $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
                        'memory_cost' => 65536, // 64 MB
                        'time_cost' => 4,       // 4 iterations
                        'threads' => 3,         // 3 threads
                    ]);
                    
                    // Generate username from email (before @ symbol)
                    $username = explode('@', $email)[0];
                    
                    // Insert user into database
                    $result = execute(
                        "INSERT INTO users (username, email, full_name, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())",
                        [$username, $email, $fullName, $passwordHash]
                    );
                    
                    if ($result) {
                        $success = "Registration successful! You can now log in.";
                        // Clear form data on success
                        $fullName = $email = '';
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
<html class="" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Register - MPSC</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            fontFamily: {
              "display": ["Space Grotesk", "sans-serif"]
            },
          },
        },
      }
    </script>
<style>
        :root {
            --bg-light: #FFFFFF;
            --bg-dark: #000000;
            --text-light: #000000;
            --text-dark: #FFFFFF;
            --glass-bg-light: rgba(255, 255, 255, 0.2);
            --glass-border-light: rgba(255, 255, 255, 0.9);
            --glass-bg-dark: rgba(20, 20, 20, 0.2);
            --glass-border-dark: rgba(255, 255, 255, 0.1);
        }
        .glass-card {
            background: var(--glass-bg-light);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border-light);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        }
        .dark .glass-card {
            background: var(--glass-bg-dark);
            border: 1px solid var(--glass-border-dark);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .glass-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.7);
        }
        .dark .glass-input {
            background: rgba(20, 20, 20, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-input:focus {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8);
        }
        .dark .glass-input:focus {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
        }
        .glass-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.2);
        }
        .dark .glass-button {
            background: rgba(20, 20, 20, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .glass-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .dark .glass-button:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        .dark .floating-shape {
            background: rgba(20, 20, 20, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }.glass-card > div > .relative > div > span {
            background: inherit;
        }
    </style>
</head>
<body class="font-display bg-[var(--bg-color)] text-[var(--fg-color)] transition-colors duration-500 light">
<?php 
// Add CSS variables for glassmorphism theme
echo '<style>
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
    --header-glass-bg: rgba(255, 255, 255, 0.75);
    --header-glass-border: rgba(0, 0, 0, 0.08);
}
html.dark {
    --bg-color: var(--bg-dark);
    --fg-color: var(--fg-dark);
    --glass-bg: var(--glass-bg-dark);
    --glass-border: var(--glass-border-dark);
    --header-glass-bg: rgba(17, 17, 17, 0.75);
    --header-glass-border: rgba(255, 255, 255, 0.12);
}
</style>';
include 'includes/navbar.php'; 
?>
<div class="relative flex min-h-screen w-full flex-col overflow-hidden pt-20">
<div class="absolute inset-0 z-0">
<div class="floating-shape -left-20 -top-20 h-64 w-64 rounded-full"></div>
<div class="floating-shape -right-24 bottom-1/4 h-56 w-56 rounded-full" style="animation-delay: 2s;"></div>
<div class="floating-shape right-1/4 top-1/3 h-40 w-80 rounded-full" style="animation-delay: 4s;"></div>
</div>
<div class="relative z-10 flex h-full grow flex-col">
<main class="flex flex-1 items-center justify-center p-4">
<div class="w-full max-w-md rounded-2xl glass-card p-8 md:p-12 space-y-8">
<div class="text-center">
<h1 class="text-4xl font-bold tracking-tighter text-black dark:text-white">Create an Account</h1>
</div>

<!-- Error/Success Messages -->
<?php if ($error): ?>
    <div class="bg-red-500/20 border border-red-500/50 px-4 py-3 rounded-lg mb-6 text-red-300">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-500/20 border border-green-500/50 px-4 py-3 rounded-lg mb-6 text-green-300">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<form method="POST" action="" class="space-y-6">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
<label class="flex flex-col">
<input class="h-12 w-full flex-1 resize-none overflow-hidden rounded-lg p-3 text-sm text-black placeholder:text-black/50 dark:text-white dark:placeholder:text-white/50 glass-input focus:outline-none transition-shadow duration-200" placeholder="Name" type="text" name="full_name" required value="<?php echo htmlspecialchars($fullName ?? ''); ?>"/>
</label>
<label class="flex flex-col">
<input class="h-12 w-full flex-1 resize-none overflow-hidden rounded-lg p-3 text-sm text-black placeholder:text-black/50 dark:text-white dark:placeholder:text-white/50 glass-input focus:outline-none transition-shadow duration-200" placeholder="Email Address" type="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>"/>
</label>
<label class="flex flex-col">
<input class="h-12 w-full flex-1 resize-none overflow-hidden rounded-lg p-3 text-sm text-black placeholder:text-black/50 dark:text-white dark:placeholder:text-white/50 glass-input focus:outline-none transition-shadow duration-200" placeholder="Password" type="password" name="password" required/>
</label>
<label class="flex flex-col">
<input class="h-12 w-full flex-1 resize-none overflow-hidden rounded-lg p-3 text-sm text-black placeholder:text-black/50 dark:text-white dark:placeholder:text-white/50 glass-input focus:outline-none transition-shadow duration-200" placeholder="Confirm Password" type="password" name="confirm_password" required/>
</label>
<button class="flex h-12 w-full min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-black px-4 text-sm font-bold text-white transition-all hover:border hover:border-black hover:bg-white hover:text-black focus:outline-none focus:ring-2 focus:ring-black/50 focus:ring-offset-2 focus:ring-offset-white dark:bg-white dark:text-black dark:hover:border-white dark:hover:bg-black dark:hover:text-white dark:focus:ring-white/50 dark:focus:ring-offset-black" type="submit">
<span class="truncate">Register</span>
</button>
</form>
<div class="relative my-2 flex items-center justify-center">
<div class="absolute inset-0 flex items-center">
<div class="w-full border-t border-black/10 dark:border-white/10"></div>
</div>
<div class="relative flex justify-center text-sm">
<span class="px-2 text-xs uppercase text-black dark:text-white" style="background: inherit;">OR</span>
</div>
</div>
<a href="login.php" class="glass-button flex h-12 w-full min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg text-sm font-bold text-black transition-colors dark:text-white">
<span class="truncate">Login</span>
</a>
<div class="text-center text-sm text-black dark:text-white">
                    Already have an account? 
                    <a class="font-bold underline decoration-1 underline-offset-2 transition-colors hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black" href="login.php">Sign in here</a>
</div>
</div>
</main>
</div>
</div>
</body>
</html>
