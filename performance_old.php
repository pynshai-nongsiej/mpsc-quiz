<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = getCurrentUserId();
$error_message = '';
$performance_data = [];

// Get filter parameters from URL
$time_period = isset($_GET['period']) ? $_GET['period'] : '7';
$quiz_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Validate time period
if (!in_array($time_period, ['7', '30', '90', 'all'])) {
    $time_period = '7';
}

// Build date condition based on time period
$date_condition = '';
$date_params = [$user_id];
if ($time_period !== 'all') {
    $date_condition = ' AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)';
    $date_params[] = (int)$time_period;
}

// Initialize variables with default values
$overall_stats = [
    'total_quizzes' => 0,
    'avg_accuracy' => 0,
    'total_time' => 0,
    'completion_rate' => 0
];
$daily_performance = [];
$category_performance = [];
$recent_quizzes = [];

try {
    // Get overall performance statistics
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_quizzes,
            AVG(accuracy) as avg_accuracy,
            SUM(time_taken) as total_time,
            AVG(CASE WHEN accuracy >= 60 THEN 1 ELSE 0 END) * 100 as completion_rate
        FROM quiz_attempts 
        WHERE user_id = ? $date_condition
    ");
    $stmt->execute($date_params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        $overall_stats = [
            'total_quizzes' => (int)$stats['total_quizzes'],
            'avg_accuracy' => round($stats['avg_accuracy'] ?: 0, 1),
            'total_time' => (int)$stats['total_time'],
            'completion_rate' => round($stats['completion_rate'] ?: 0, 1)
        ];
    }

    // Get category performance
    $stmt = $pdo->prepare("
        SELECT 
            quiz_type as category,
            COUNT(*) as attempts,
            AVG(accuracy) as avg_accuracy,
            AVG(time_taken) as avg_time
        FROM quiz_attempts 
        WHERE user_id = ? $date_condition
        GROUP BY quiz_type
        ORDER BY avg_accuracy DESC
    ");
    $stmt->execute($date_params);
    $category_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent quiz results
    $stmt = $pdo->prepare("
        SELECT 
            quiz_title,
            accuracy,
            time_taken,
            completed_at,
            CASE 
                WHEN accuracy >= 80 THEN 'excellent'
                WHEN accuracy >= 60 THEN 'good'
                ELSE 'needs_improvement'
            END as status
        FROM quiz_attempts 
        WHERE user_id = ? $date_condition
        ORDER BY completed_at DESC
        LIMIT 10
    ");
    $stmt->execute($date_params);
    $recent_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = 'Error loading performance data: ' . $e->getMessage();
    error_log($error_message);
}

// Helper functions
function formatTime($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%dm %02ds', $minutes, $remainingSeconds);
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%dh %02dm', $hours, $minutes);
    }
}

function getAccuracyColor($accuracy) {
    if ($accuracy >= 80) return '#10b981'; // green
    if ($accuracy >= 60) return '#f59e0b'; // yellow
    return '#ef4444'; // red
}

function getStatusColor($status) {
    switch ($status) {
        case 'excellent': return 'text-green-500';
        case 'good': return 'text-yellow-500';
        default: return 'text-red-500';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Performance Analytics - MPSC Quiz Portal</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script>
      tailwind.config = {
        darkMode: "class",
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
<div class="relative flex min-h-screen w-full flex-col overflow-hidden pt-20">
<!-- Animated Background Elements -->
<div class="absolute top-[-20%] left-[-15%] w-96 h-96 bg-black/5 dark:bg-white/5 rounded-full blur-3xl animate-[spin_20s_linear_infinite] opacity-50"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-[40rem] h-[40rem] bg-black/5 dark:bg-white/5 rounded-3xl blur-3xl animate-[spin_30s_linear_infinite] opacity-50"></div>

<div class="relative z-10 flex h-full w-full max-w-6xl mx-auto grow flex-col px-4 py-12 sm:px-6 lg:px-8">

<!-- Page Header -->
<header class="w-full text-center mb-8">
<h1 class="text-5xl font-bold leading-tight tracking-wider text-black dark:text-white sm:text-6xl">Performance Analytics</h1>
<p class="mt-4 text-lg text-[var(--subtle-text)]">Track your quiz performance and identify areas for improvement</p>
</header>

<!-- Time Period Filter -->
<div class="mb-8">
<div class="glassmorphic mx-auto max-w-2xl rounded-xl p-4 shadow-sm">
<div class="flex flex-wrap justify-center gap-2">
<a href="?period=7" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $time_period === '7' ? 'bg-black text-white dark:bg-white dark:text-black' : 'text-[var(--fg-color)] hover:bg-black/10 dark:hover:bg-white/10' ?>">Last 7 Days</a>
<a href="?period=30" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $time_period === '30' ? 'bg-black text-white dark:bg-white dark:text-black' : 'text-[var(--fg-color)] hover:bg-black/10 dark:hover:bg-white/10' ?>">Last 30 Days</a>
<a href="?period=90" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $time_period === '90' ? 'bg-black text-white dark:bg-white dark:text-black' : 'text-[var(--fg-color)] hover:bg-black/10 dark:hover:bg-white/10' ?>">Last 90 Days</a>
<a href="?period=all" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $time_period === 'all' ? 'bg-black text-white dark:bg-white dark:text-black' : 'text-[var(--fg-color)] hover:bg-black/10 dark:hover:bg-white/10' ?>">All Time</a>
</div>
</div>
</div>

<?php if ($error_message): ?>
<div class="mb-8">
<div class="glassmorphic rounded-xl p-6 border-red-500/50 bg-red-500/10">
<p class="text-red-400 text-center"><?= htmlspecialchars($error_message) ?></p>
</div>
</div>
<?php endif; ?>

<!-- Overall Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
<div class="glassmorphic rounded-xl p-6 text-center">
<div class="text-3xl font-bold text-[var(--fg-color)] mb-2"><?= $overall_stats['total_quizzes'] ?></div>
<div class="text-sm text-[var(--subtle-text)]">Total Quizzes</div>
</div>
<div class="glassmorphic rounded-xl p-6 text-center">
<div class="text-3xl font-bold text-[var(--fg-color)] mb-2"><?= $overall_stats['avg_accuracy'] ?>%</div>
<div class="text-sm text-[var(--subtle-text)]">Average Accuracy</div>
</div>
<div class="glassmorphic rounded-xl p-6 text-center">
<div class="text-3xl font-bold text-[var(--fg-color)] mb-2"><?= formatTime($overall_stats['total_time']) ?></div>
<div class="text-sm text-[var(--subtle-text)]">Total Time Spent</div>
</div>
<div class="glassmorphic rounded-xl p-6 text-center">
<div class="text-3xl font-bold text-[var(--fg-color)] mb-2"><?= $overall_stats['completion_rate'] ?>%</div>
<div class="text-sm text-[var(--subtle-text)]">Success Rate (≥60%)</div>
</div>
</div>

<!-- Category Performance -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
<div class="glassmorphic rounded-xl p-6">
<h2 class="text-2xl font-bold text-[var(--fg-color)] mb-6">Category Performance</h2>
<?php if (empty($category_performance)): ?>
<p class="text-[var(--subtle-text)] text-center py-8">No category data available</p>
<?php else: ?>
<div class="space-y-4">
<?php foreach ($category_performance as $category): 
    $accuracy = round($category['avg_accuracy'], 1);
    $progressColor = getAccuracyColor($accuracy);
?>
<div class="space-y-2">
<div class="flex justify-between items-center">
<span class="text-[var(--fg-color)] font-medium"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $category['category'] ?: 'General'))) ?></span>
<span class="text-[var(--fg-color)] font-bold"><?= $accuracy ?>%</span>
</div>
<div class="w-full bg-black/10 dark:bg-white/10 rounded-full h-2">
<div class="h-2 rounded-full transition-all duration-300" style="width: <?= $accuracy ?>%; background-color: <?= $progressColor ?>"></div>
</div>
<div class="text-xs text-[var(--subtle-text)]"><?= $category['attempts'] ?> attempts</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- Recent Quiz Results -->
<div class="glassmorphic rounded-xl p-6">
<h2 class="text-2xl font-bold text-[var(--fg-color)] mb-6">Recent Results</h2>
<?php if (empty($recent_quizzes)): ?>
<p class="text-[var(--subtle-text)] text-center py-8">No recent quiz results found</p>
<?php else: ?>
<div class="space-y-3">
<?php foreach (array_slice($recent_quizzes, 0, 5) as $quiz): 
    $accuracy = round($quiz['accuracy'], 1);
    $accuracyColor = getAccuracyColor($accuracy);
    $statusColor = getStatusColor($quiz['status']);
    $timeAgo = date('M j', strtotime($quiz['completed_at']));
?>
<div class="flex justify-between items-center p-3 rounded-lg bg-black/5 dark:bg-white/5">
<div class="flex-1">
<div class="font-medium text-[var(--fg-color)]"><?= htmlspecialchars($quiz['quiz_title'] ?: 'Untitled Quiz') ?></div>
<div class="text-xs text-[var(--subtle-text)]"><?= $timeAgo ?> • <?= formatTime($quiz['time_taken'] ?: 0) ?></div>
</div>
<div class="text-right">
<div class="font-bold" style="color: <?= $accuracyColor ?>"><?= $accuracy ?>%</div>
<div class="text-xs <?= $statusColor ?>"><?= ucfirst($quiz['status']) ?></div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>

<!-- Action Buttons -->
<footer class="mt-12">
<div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
<a href="index.php" class="flex h-14 min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-xl bg-black px-8 text-base font-bold text-white transition-transform duration-200 hover:scale-105 dark:bg-white dark:text-black">
<span class="truncate">Take Another Quiz</span>
</a>
<a href="quiz-history.php" class="glassmorphic glass-btn-hover flex h-14 min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-black/20 px-8 text-base font-bold text-black transition-transform duration-200 hover:scale-105 dark:border-white/20 dark:text-white">
<span class="truncate">View Quiz History</span>
</a>
</div>
</footer>

</div>
</div>

</body>
</html>
