<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';

// getCurrentUser() function is now available from config/session.php

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        
        if (empty($fullName) || empty($email)) {
            $error = 'Full name and email are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            try {
                // Check if email is already taken by another user
                $existingUser = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
                if ($existingUser) {
                    $error = 'Email address is already in use';
                } else {
                    // Update profile
                    $result = updateRecord('users', 'id', $user['id'], [
                        'full_name' => $fullName,
                        'email' => $email
                    ], ['id' => $user['id']]);
                    
                    if ($result) {
                        $success = 'Profile updated successfully!';
                        $user = getCurrentUser(); // Refresh user data
                    } else {
                        $error = 'Failed to update profile';
                    }
                }
            } catch (Exception $e) {
                error_log('Profile update error: ' . $e->getMessage());
                $error = 'An error occurred while updating your profile';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            try {
                // Verify current password
                $userData = fetchOne("SELECT password FROM users WHERE id = ?", [$user['id']]);
                
                if (!$userData || !password_verify($currentPassword, $userData['password'])) {
                    $error = 'Current password is incorrect';
                } else {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
                    $result = updateRecord('users', 'id', $user['id'], [
                        'password' => $hashedPassword
                    ], ['id' => $user['id']]);
                    
                    if ($result) {
                        $success = 'Password changed successfully!';
                    } else {
                        $error = 'Failed to change password';
                    }
                }
            } catch (Exception $e) {
                error_log('Password change error: ' . $e->getMessage());
                $error = 'An error occurred while changing your password';
            }
        }
    }
}

// Get user statistics
try {
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_quizzes,
            AVG(score_percentage) as avg_score,
            MAX(score_percentage) as best_score,
            SUM(time_taken) as total_time
        FROM quiz_attempts 
        WHERE user_id = ?
    ", [$user['id']]);
    
    // Get recent activity
    $recentActivity = fetchAll("
        SELECT quiz_type, score_percentage, created_at
        FROM quiz_attempts 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ", [$user['id']]);
    
} catch (Exception $e) {
    $stats = ['total_quizzes' => 0, 'avg_score' => 0, 'best_score' => 0, 'total_time' => 0];
    $recentActivity = [];
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect"/>
<link as="style" href="https://fonts.googleapis.com/css2?display=swap&amp;family=Inter%3Awght%40400%3B500%3B600%3B700&amp;family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<style type="text/tailwindcss">
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: rgba(30, 41, 59, 0.1);
            --input-border: rgba(30, 41, 59, 0.3);
            --button-bg: rgba(30, 41, 59, 0.1);
            --button-hover: rgba(30, 41, 59, 0.2);
            --card-background: rgba(255, 255, 255, 0.9);
        }
        
        .dark {
            --bg-primary: #000000;
            --bg-secondary: #111827;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --input-border: rgba(255, 255, 255, 0.3);
            --button-bg: rgba(255, 255, 255, 0.1);
            --button-hover: rgba(255, 255, 255, 0.2);
            --card-background: rgba(0, 0, 0, 0.8);
        }
        
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        body {
            font-family: 'Inter', "Noto Sans", sans-serif;
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
            @apply absolute left-4 -top-2.5 text-xs px-1 transition-all duration-300;
            color: var(--text-secondary);
            background-color: var(--bg-primary);
        }
        
        .form-input:focus + .form-label,
        .form-input:not(:placeholder-shown) + .form-label {
            @apply -top-2.5 text-xs;
        }
        
        .form-input:placeholder-shown + .form-label {
            @apply top-3.5 text-base;
        }
        
        .save-button {
            @apply w-full font-bold py-3 rounded-lg transition-all duration-300;
            background-color: var(--button-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        
        .save-button:hover {
            background-color: var(--button-hover);
            box-shadow: 0 0 25px rgba(255, 255, 255, 0.3);
        }
        
        [data-theme="light"] .save-button:hover {
            box-shadow: 0 0 25px rgba(30, 41, 59, 0.3);
        }
        
        .nav-link {
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--text-primary);
        }
        
        .theme-container {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
        }
        

        .frosted-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dark .frosted-glass {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-input {
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            background-color: var(--input-bg);
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-label {
            position: absolute;
            top: -0.625rem;
            left: 0.75rem;
            background-color: var(--bg-primary);
            padding: 0 0.25rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .save-button {
            width: 100%;
            border-radius: 0.5rem;
            background-color: #3b82f6;
            padding: 0.75rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .save-button:hover {
            background-color: #2563eb;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.25);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/mobile_navbar.php'; ?>
<div class="flex min-h-screen items-center justify-center p-4" style="padding-top: 80px; background-color: var(--bg-primary);">
<div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuAqmmxsqndvetclHHbyCEFJXYSkFPaljLIcdg0LETPckkJYclFhvk2lcBOX7jtBCLPrCmvKniMl2MGBH7Oq9FPaMuq4rJVRPJvXAupRczrAMOWDKtzxSpsxH29NuW-KX46ktGZ3xhR8kPR8KinRF84b5IDHKghLbBOf3a8q1H4WZ7cQlg-1lULB9vOD0FecDo09mhSgGQdpdnWYL21us9GREUBWC3iiQC_tEJdk0AoxY1WZPv5OvImnGkJh3HpPM6-OL2ZQTNReVGs'); z-index: -1;"></div>
<div class="w-full max-w-sm rounded-2xl frosted-glass shadow-2xl theme-container">

        <!-- Error/Success Messages -->
<div class="p-8">
<h2 class="mb-6 text-center text-2xl font-bold" style="color: var(--text-primary);">Profile</h2>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 px-4 py-3 rounded-lg mb-6" style="color: #fecaca;">
                <?php echo htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-500/20 border border-green-500/50 px-4 py-3 rounded-lg mb-6" style="color: #bbf7d0;">
                <?php echo htmlspecialchars($success ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

<!-- Profile Form -->
<form method="POST" class="space-y-6">
<input type="hidden" name="update_profile" value="1">

<div class="relative">
<input type="text" id="full_name" name="full_name" 
       value="<?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
       class="form-input peer placeholder-transparent focus:outline-none" 
       placeholder="Full Name" required>
<label for="full_name" class="form-label peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-blue-500">
Full Name
</label>
</div>

<div class="relative">
<input type="email" id="email" name="email" 
       value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
       class="form-input peer placeholder-transparent focus:outline-none" 
       placeholder="Email" required>
<label for="email" class="form-label peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-blue-500">
Email
</label>
</div>



<button type="submit" class="save-button">
Update Profile
</button>
</form>

<!-- Change Password Form -->
<form method="POST" class="mt-8 space-y-6">
<input type="hidden" name="change_password" value="1">

<div class="relative">
<input type="password" id="current_password" name="current_password" 
       class="form-input peer placeholder-transparent focus:outline-none" 
       placeholder="Current Password" required>
<label for="current_password" class="form-label peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-blue-500">
Current Password
</label>
</div>

<div class="relative">
<input type="password" id="new_password" name="new_password" 
       class="form-input peer placeholder-transparent focus:outline-none" 
       placeholder="New Password" required minlength="6">
<label for="new_password" class="form-label peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-blue-500">
New Password
</label>
</div>

<div class="relative">
<input type="password" id="confirm_password" name="confirm_password" 
       class="form-input peer placeholder-transparent focus:outline-none" 
       placeholder="Confirm Password" required minlength="6">
<label for="confirm_password" class="form-label peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-blue-500">
Confirm Password
</label>
</div>

<button type="submit" class="save-button">
Change Password
</button>
</form>

<div class="mt-8 text-center">
<a href="logout.php" class="nav-link hover:text-red-500 transition-colors duration-200">
Logout
</a>
</div>
</div>
</div>
</div>
    </div>

    <script>

        
        // Add CSRF token to form if available
        <?php if (isset($_SESSION['csrf_token'])): ?>
        const form = document.querySelector('form');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $_SESSION['csrf_token']; ?>';
        form.appendChild(csrfInput);
        <?php endif; ?>
    </script>
</body>
</html>