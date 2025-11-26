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

// Fetch quiz history for the current user with improvement tracking
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
            qa.quiz_type,
            DATE(qa.completed_at) as quiz_date
        FROM quiz_attempts qa
        WHERE qa.user_id = ?
        ORDER BY qa.completed_at DESC
    ", [$userId]);
    
    // Group quiz history by date and calculate improvements for revisions
    $groupedHistory = [];
    $originalAttempts = []; // Store original attempts for comparison
    
    foreach ($quizHistory as $quiz) {
        $date = $quiz['quiz_date'];
        
        // Check if this is a revision attempt
        $isRevision = strpos($quiz['quiz_type'], 'revision_') === 0;
        
        if ($isRevision) {
            // Extract original attempt ID from revision quiz type
            $originalAttemptId = str_replace('revision_', '', $quiz['quiz_type']);
            
            // Find the original attempt to calculate improvement
            $originalAttempt = null;
            foreach ($quizHistory as $original) {
                if ($original['id'] == $originalAttemptId) {
                    $originalAttempt = $original;
                    break;
                }
            }
            
            if ($originalAttempt) {
                $quiz['original_attempt'] = $originalAttempt;
                $quiz['accuracy_improvement'] = $quiz['accuracy'] - $originalAttempt['accuracy'];
                $quiz['score_improvement'] = $quiz['score'] - $originalAttempt['score'];
                $quiz['time_improvement'] = $originalAttempt['time_taken'] - $quiz['time_taken']; // Negative means took longer
            }
        } else {
            // Store original attempts for revision comparison
            $originalAttempts[$quiz['id']] = $quiz;
        }
        
        if (!isset($groupedHistory[$date])) {
            $groupedHistory[$date] = [];
        }
        $groupedHistory[$date][] = $quiz;
    }
    
} catch (Exception $e) {
    error_log('Error fetching quiz history: ' . $e->getMessage());
    $quizHistory = [];
    $groupedHistory = [];
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

<!-- Revision Info Banner -->
<div class="mb-8">
<div class="glassmorphic mx-auto max-w-3xl rounded-xl p-6 shadow-sm">
<div class="flex items-center gap-3 mb-3">
<span class="text-2xl">📚</span>
<h2 class="text-xl font-bold text-black dark:text-white">Revision Mode</h2>
</div>
<p class="text-sm font-normal leading-relaxed text-black/80 dark:text-white/80">
Click the "📚 Revise" button next to any quiz to retake it with the same questions. This helps reinforce your learning and improve memory retention. Your revision attempts are tracked separately from original attempts.
</p>
</div>
</div>

<!-- Quiz History List -->
<main class="flex flex-col gap-8">
<?php if (empty($groupedHistory)): ?>
    <div class="glassmorphic w-full rounded-xl p-8 @container shadow-md text-center">
        <h2 class="text-2xl font-bold leading-tight tracking-wide text-black dark:text-white mb-4">No Quiz History Found</h2>
        <p class="text-base font-normal text-black/80 dark:text-white/80 mb-6">You haven't completed any quizzes yet. Start your learning journey!</p>
        <a href="index.php" class="inline-block px-6 py-3 text-sm font-bold text-white bg-black dark:bg-white dark:text-black rounded-lg transition-colors hover:bg-black/80 dark:hover:bg-white/80">
            Take Your First Quiz
        </a>
    </div>
<?php else: ?>
    <?php foreach ($groupedHistory as $date => $quizzes): ?>
        <?php 
            $dateObj = new DateTime($date);
            $formattedDate = $dateObj->format('F j, Y');
            $dayOfWeek = $dateObj->format('l');
        ?>
        
        <!-- Date Group Header -->
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-black dark:text-white"><?php echo $formattedDate; ?></h2>
                <span class="text-sm font-medium text-black/60 dark:text-white/60"><?php echo $dayOfWeek; ?></span>
                <div class="flex-1 h-px bg-black/20 dark:bg-white/20"></div>
                <span class="text-sm font-medium text-black/60 dark:text-white/60"><?php echo count($quizzes); ?> quiz<?php echo count($quizzes) > 1 ? 'es' : ''; ?></span>
            </div>
            
            <!-- Quizzes for this date -->
            <div class="flex flex-col gap-4">
                <?php foreach ($quizzes as $quiz): ?>
                    <?php 
                        $completedTime = new DateTime($quiz['completed_at']);
                        $timeFormatted = $quiz['time_taken'] ? formatTime($quiz['time_taken']) : 'N/A';
                        $isRevision = strpos($quiz['quiz_type'], 'revision_') === 0;
                    ?>
                    
                    <div class="glassmorphic w-full rounded-xl p-6 shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-lg <?php echo $isRevision ? 'border-l-4 border-purple-500' : ''; ?>">
                        <div class="flex flex-col gap-4">
                            <!-- Quiz Header -->
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <?php if ($isRevision): ?>
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded-full">
                                                📚 Revision
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-xs font-normal text-black/60 dark:text-white/60">
                                            <?php echo $completedTime->format('g:i A'); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-xl font-bold leading-tight tracking-wide text-black dark:text-white">
                                        <?php echo htmlspecialchars($quiz['quiz_title']); ?>
                                    </h3>
                                </div>
                            </div>
                            
                            <!-- Quiz Stats -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="text-center sm:text-left">
                                    <p class="text-sm font-medium text-black/60 dark:text-white/60">Score</p>
                                    <p class="text-lg font-bold text-black dark:text-white">
                                        <?php echo $quiz['correct_answers']; ?>/<?php echo $quiz['total_questions']; ?>
                                    </p>
                                </div>
                                <div class="text-center sm:text-left">
                                    <p class="text-sm font-medium text-black/60 dark:text-white/60">Accuracy</p>
                                    <p class="text-lg font-bold text-black dark:text-white">
                                        <?php echo number_format($quiz['accuracy'], 1); ?>%
                                    </p>
                                </div>
                                <div class="text-center sm:text-left">
                                    <p class="text-sm font-medium text-black/60 dark:text-white/60">Time</p>
                                    <p class="text-lg font-bold text-black dark:text-white"><?php echo $timeFormatted; ?></p>
                                </div>
                            </div>
                            
                            <!-- Improvement Metrics for Revisions -->
                            <?php if ($isRevision && isset($quiz['original_attempt'])): ?>
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-purple-800 dark:text-purple-200 mb-2">📈 Improvement from Original</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                        <div>
                                            <span class="text-purple-600 dark:text-purple-300">Accuracy:</span>
                                            <span class="font-semibold <?php echo $quiz['accuracy_improvement'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                                <?php echo ($quiz['accuracy_improvement'] >= 0 ? '+' : '') . number_format($quiz['accuracy_improvement'], 1); ?>%
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-purple-600 dark:text-purple-300">Score:</span>
                                            <span class="font-semibold <?php echo $quiz['score_improvement'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                                <?php echo ($quiz['score_improvement'] >= 0 ? '+' : '') . $quiz['score_improvement']; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-purple-600 dark:text-purple-300">Time:</span>
                                            <span class="font-semibold <?php echo $quiz['time_improvement'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                                <?php 
                                                if ($quiz['time_improvement'] >= 0) {
                                                    echo '-' . formatTime(abs($quiz['time_improvement'])) . ' faster';
                                                } else {
                                                    echo '+' . formatTime(abs($quiz['time_improvement'])) . ' slower';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <button onclick="viewQuizDetails(<?php echo $quiz['id']; ?>)" 
                                        class="flex-1 px-4 py-2 text-sm font-medium text-black dark:text-white bg-white/20 dark:bg-black/20 border border-black/20 dark:border-white/20 rounded-lg hover:bg-white/30 dark:hover:bg-black/30 transition-colors">
                                    👁️ View Details
                                </button>
                                
                                <?php if (!$isRevision): ?>
                                    <a href="revision.php?attempt_id=<?php echo $quiz['id']; ?>" 
                                       class="flex-1 px-4 py-2 text-sm font-medium text-center text-white bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 rounded-lg transition-colors">
                                        📚 Revise
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
    // Redirect to result page to view detailed quiz results
    window.location.href = 'result.php?attempt_id=' + attemptId;
}

// Theme is now handled by the navbar
</script>

</body>
</html>
