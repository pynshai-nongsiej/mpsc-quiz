<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$userId = getCurrentUserId();

// Helper function to format time consistently
function formatTime($seconds) {
    // Ensure we have a valid number
    $seconds = (int)$seconds;
    
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }
}

// Fetch quiz history for the current user
try {
    $quizHistory = fetchAll("
        SELECT 
            qa.id,
            qa.quiz_title,
            qa.score,
            qa.max_score,
            qa.accuracy,
            qa.total_questions,
            qa.correct_answers,
            qa.time_taken,
            qa.completed_at,
            qa.quiz_type
        FROM quiz_attempts qa
        WHERE qa.user_id = ?
        ORDER BY qa.completed_at DESC
        LIMIT 20
    ", [$userId]);
} catch (Exception $e) {
    error_log('Error fetching quiz history: ' . $e->getMessage());
    $quizHistory = [];
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quiz History - MPSC Quiz Portal</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<style type="text/tailwindcss">
        :root {
            --background-color-light: #f5f5f5;
            --text-primary-light: #121212;
            --text-secondary-light: #6B7280;
            --card-bg-light: rgba(255, 255, 255, 0.6);
            --card-border-light: rgba(0, 0, 0, 0.05);
            --card-hover-shadow-light: rgba(0, 0, 0, 0.05);
            --toggle-bg-light: #E5E7EB;
            --toggle-indicator-light: #ffffff;
            --background-color-dark: #121212;
            --text-primary-dark: #f5f5f5;
            --text-secondary-dark: #9CA3AF;
            --card-bg-dark: rgba(31, 31, 31, 0.6);
            --card-border-dark: rgba(255, 255, 255, 0.1);
            --card-hover-shadow-dark: rgba(255, 255, 255, 0.05);
            --toggle-bg-dark: #374151;
            --toggle-indicator-dark: #1F2937;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }
        .light {
            --background-color: var(--background-color-light);
            --text-primary: var(--text-primary-light);
            --text-secondary: var(--text-secondary-light);
            --card-bg: var(--card-bg-light);
            --card-border: var(--card-border-light);
            --card-hover-shadow: var(--card-hover-shadow-light);
            --toggle-bg: var(--toggle-bg-light);
            --toggle-indicator: var(--toggle-indicator-light);
        }
        .dark {
            --background-color: var(--background-color-dark);
            --text-primary: var(--text-primary-dark);
            --text-secondary: var(--text-secondary-dark);
            --card-bg: var(--card-bg-dark);
            --card-border: var(--card-border-dark);
            --card-hover-shadow: var(--card-hover-shadow-dark);
            --toggle-bg: var(--toggle-bg-dark);
            --toggle-indicator: var(--toggle-indicator-dark);
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
        }
        .glassmorphism-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/mobile_navbar.php'; ?>
<div class="w-full max-w-4xl px-4 py-12 mx-auto sm:px-6 lg:px-8">
<header class="w-full pb-8 mb-8 text-left border-b" style="border-color: var(--card-border);">
<h1 class="text-4xl font-bold tracking-tight" style="color: var(--text-primary);">Quiz History</h1>
<p class="mt-2" style="color: var(--text-secondary);">Welcome back, <?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? 'User'); ?>!</p>
</header>

<div class="w-full space-y-6">
<?php if (empty($quizHistory)): ?>
    <div class="p-8 text-center rounded-2xl glassmorphism-card">
        <h2 class="text-xl font-semibold mb-2" style="color: var(--text-primary);">No Quiz History Found</h2>
        <p class="mb-4" style="color: var(--text-secondary);">You haven't completed any quizzes yet. Start your learning journey!</p>
        <a href="index.php" class="inline-block px-6 py-3 text-sm font-medium transition-colors duration-200 border border-transparent rounded-lg" style="background-color: var(--card-bg); color: var(--text-primary); border-color: var(--card-border);" onmouseover="this.style.backgroundColor='var(--card-hover-bg)'" onmouseout="this.style.backgroundColor='var(--card-bg)'">
            Take Your First Quiz
        </a>
    </div>
<?php else: ?>
    <?php foreach ($quizHistory as $quiz): ?>
        <?php 
            $completedDate = new DateTime($quiz['completed_at']);
            $formattedDate = $completedDate->format('M j, Y');
            $timeFormatted = $quiz['time_taken'] ? formatTime($quiz['time_taken']) : 'N/A';
        ?>
        <div class="flex items-center justify-between p-6 rounded-2xl glassmorphism-card">
            <div class="flex-grow">
                <h2 class="text-xl font-semibold" style="color: var(--text-primary);"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h2>
                <p class="mt-1 text-sm" style="color: var(--text-secondary);">
                    <?php echo $formattedDate; ?> · 
                    <span class="font-medium" style="color: var(--text-primary);">Score: <?php echo $quiz['correct_answers']; ?>/<?php echo $quiz['total_questions']; ?></span> · 
                    <span class="font-medium" style="color: var(--text-primary);">Accuracy: <?php echo number_format($quiz['accuracy'], 1); ?>%</span>
                    <?php if ($quiz['time_taken']): ?>
                        · <span class="font-medium" style="color: var(--text-primary);">Time: <?php echo $timeFormatted; ?></span>
                    <?php endif; ?>
                </p>
                <div class="mt-2">
                    <span class="inline-block px-2 py-1 text-xs font-medium text-white bg-blue-600/50 rounded-full">
                        <?php echo ucfirst(str_replace('_', ' ', $quiz['quiz_type'])); ?>
                    </span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <button onclick="viewQuizDetails(<?php echo $quiz['id']; ?>)" class="px-5 py-2 text-sm font-medium text-black transition-colors duration-200 bg-white/80 border border-transparent rounded-lg hover:bg-white">
                    View Details
                </button>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="mt-8 text-center">
    <a href="index.php" class="inline-block px-6 py-3 text-sm font-medium text-white transition-colors duration-200 bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">
        Take Another Quiz
    </a>
    <a href="performance.php" class="inline-block ml-4 px-6 py-3 text-sm font-medium text-white transition-colors duration-200 bg-green-600 border border-transparent rounded-lg hover:bg-green-700">
        View Performance Analytics
    </a>
</div>
</div>

<script>
function viewQuizDetails(attemptId) {
    // You can implement a modal or redirect to a detailed view
    alert('Quiz details feature coming soon! Attempt ID: ' + attemptId);
}
</script>

</body>
</html>