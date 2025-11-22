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
        return sprintf('%dm %02ds', $minutes, $remainingSeconds);
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%dh %02dm %02ds', $hours, $minutes, $remainingSeconds);
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
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quiz History</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "primary": "#ec5b13",
              "background-light": "#ffffff",
              "background-dark": "#000000",
            },
            fontFamily: {
              "display": ["Space Grotesk", "sans-serif"]
            },
            borderRadius: { "DEFAULT": "0.5rem", "lg": "1rem", "xl": "1.5rem", "full": "9999px" },
          },
        },
      }
    </script>
<style>
      .glassmorphic {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(0, 0, 0, 0.1);
      }
      .dark .glassmorphic {
        background-color: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
      }
      .glass-btn-hover:hover {
        background-color: rgba(255, 255, 255, 0.2);
      }
      .dark .glass-btn-hover:hover {
        background-color: rgba(255, 255, 255, 0.15);
      }
    </style>
</head>
<body class="bg-[var(--bg-color)] text-[var(--fg-color)] font-display transition-colors duration-500 light">
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
    --category-text: #1f2937;
    --header-glass-bg: rgba(255, 255, 255, 0.75);
    --header-glass-border: rgba(0, 0, 0, 0.08);
}
html.light {
    --bg-color: var(--bg-light);
    --fg-color: var(--fg-light);
    --glass-bg: var(--glass-bg-light);
    --glass-border: var(--glass-border-light);
    --subtle-text: #374151;
    --category-text: #1f2937;
    --header-glass-bg: rgba(255, 255, 255, 0.75);
    --header-glass-border: rgba(0, 0, 0, 0.08);
}
html.dark {
    --bg-color: var(--bg-dark);
    --fg-color: var(--fg-dark);
    --glass-bg: var(--glass-bg-dark);
    --glass-border: var(--glass-border-dark);
    --subtle-text: #d1d5db;
    --category-text: #e5e7eb;
    --header-glass-bg: rgba(17, 17, 17, 0.75);
    --header-glass-border: rgba(255, 255, 255, 0.12);
}
</style>';
include 'includes/navbar.php'; 
?>
<div class="relative flex min-h-screen w-full flex-col items-center overflow-hidden pt-20">
<!-- Animated Background Elements -->
<div class="absolute top-[-20%] left-[-15%] w-96 h-96 bg-black/5 dark:bg-white/5 rounded-full blur-3xl animate-[spin_20s_linear_infinite] opacity-50"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-[40rem] h-[40rem] bg-black/5 dark:bg-white/5 rounded-3xl blur-3xl animate-[spin_30s_linear_infinite] opacity-50"></div>
<div class="relative z-10 flex h-full w-full max-w-4xl grow flex-col px-4 py-12 sm:px-6 lg:px-8">

<!-- Page Header -->
<header class="w-full text-center mb-8">
<h1 class="text-5xl font-bold leading-tight tracking-wider text-black dark:text-white sm:text-6xl">Quiz History</h1>
</header>

<!-- Welcome Banner -->
<div class="mb-12">
<div class="glassmorphic mx-auto max-w-xl rounded-xl p-4 shadow-sm">
<p class="text-center text-base font-normal leading-normal text-black dark:text-white">Welcome back, <?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? 'User'); ?>!</p>
</div>
</div>

<!-- Quiz History List -->
<main class="flex flex-col gap-6">
<?php if (empty($quizHistory)): ?>
    <div class="glassmorphic w-full rounded-xl p-8 @container shadow-md text-center">
        <h2 class="text-2xl font-bold leading-tight tracking-wide text-black dark:text-white mb-4">No Quiz History Found</h2>
        <p class="text-base font-normal text-black/80 dark:text-white/80 mb-6">You haven't completed any quizzes yet. Start your learning journey!</p>
        <a href="index.php" class="inline-block px-6 py-3 text-sm font-bold text-white bg-black dark:bg-white dark:text-black rounded-lg transition-colors hover:bg-black/80 dark:hover:bg-white/80">
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
        <div class="glassmorphic w-full rounded-xl p-6 @container shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
            <div class="flex flex-col items-stretch justify-start gap-4 md:flex-row md:items-start">
                <div class="flex w-full flex-col items-stretch justify-center gap-2">
                    <p class="text-xs font-normal uppercase tracking-widest text-black/60 dark:text-white/60"><?php echo $formattedDate; ?></p>
                    <p class="text-xl font-bold leading-tight tracking-wide text-black dark:text-white"><?php echo htmlspecialchars($quiz['quiz_title']); ?></p>
                    <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-normal text-black/80 dark:text-white/80">
                            Score: <?php echo $quiz['correct_answers']; ?>/<?php echo $quiz['total_questions']; ?> · 
                            Accuracy: <?php echo number_format($quiz['accuracy'], 1); ?>% · 
                            Time: <?php echo $timeFormatted; ?>
                        </p>
                        <a class="text-sm font-medium text-black underline decoration-black/50 underline-offset-4 transition-colors hover:decoration-black dark:text-white dark:decoration-white/50 dark:hover:decoration-white" href="#" onclick="viewQuizDetails(<?php echo $quiz['id']; ?>)">View Details</a>
                    </div>
                    <div class="mt-2">
                        <span class="inline-block px-3 py-1 text-xs font-medium rounded-full glassmorphic">
                            <?php echo ucfirst(str_replace('_', ' ', $quiz['quiz_type'])); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</main>

<!-- Action Buttons -->
<footer class="mt-12">
<div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
<a href="index.php" class="flex h-14 min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-xl bg-black px-8 text-base font-bold text-white transition-transform duration-200 hover:scale-105 dark:bg-white dark:text-black">
<span class="truncate">Take Another Quiz</span>
</a>
<a href="performance.php" class="glassmorphic glass-btn-hover flex h-14 min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-black/20 px-8 text-base font-bold text-black transition-transform duration-200 hover:scale-105 dark:border-white/20 dark:text-white">
<span class="truncate">View Performance Analytics</span>
</a>
</div>
</footer>
</div>
</div>

<script>
function viewQuizDetails(attemptId) {
    // You can implement a modal or redirect to a detailed view
    alert('Quiz details feature coming soon! Attempt ID: ' + attemptId);
}

// Theme is now handled by the navbar
</script>

</body>
</html>
