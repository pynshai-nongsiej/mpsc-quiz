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
$current_user = getCurrentUser();
$user_name = $current_user ? ($current_user['full_name'] ?? $current_user['username'] ?? 'User') : 'User';

// Get filter parameters
$time_period = isset($_GET['period']) ? $_GET['period'] : '7';
if (!in_array($time_period, ['7', '30', '90', 'all'])) {
    $time_period = '7';
}

// Initialize data arrays
$overall_stats = [
    'total_quizzes' => 0,
    'avg_accuracy' => 0,
    'best_category' => 'N/A',
    'accuracy_trend' => 0
];
$daily_performance = [];
$category_performance = [];
$recent_activities = [];
$category_breakdown = [];

try {
    $pdo = getConnection();
    
    // Build date condition
    $date_condition = '';
    $date_params = [$user_id];
    if ($time_period !== 'all') {
        $date_condition = ' AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)';
        $date_params[] = (int)$time_period;
    }
    
    // Get overall statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_quizzes,
            ROUND(AVG(accuracy), 0) as avg_accuracy,
            MAX(accuracy) as best_accuracy
        FROM quiz_attempts 
        WHERE user_id = ? $date_condition
    ");
    $stmt->execute($date_params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        $overall_stats['total_quizzes'] = $stats['total_quizzes'] ?: 0;
        $overall_stats['avg_accuracy'] = $stats['avg_accuracy'] ?: 0;
    }
    
    // Get best performing category
    $stmt = $pdo->prepare("
        SELECT 
            qr.category,
            ROUND(AVG(CASE WHEN qr.is_correct THEN 100 ELSE 0 END), 0) as accuracy
        FROM quiz_attempts qa
        JOIN quiz_responses qr ON qa.id = qr.attempt_id
        WHERE qa.user_id = ? $date_condition
        GROUP BY qr.category
        ORDER BY accuracy DESC
        LIMIT 1
    ");
    $stmt->execute($date_params);
    $best_cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($best_cat) {
        $overall_stats['best_category'] = ucfirst(str_replace('-', ' ', $best_cat['category']));
    }
    
    // Calculate accuracy trend (compare current period with previous period)
    $overall_stats['accuracy_trend'] = 0; // Default value
    
    if ($time_period !== 'all') {
        $prev_period_start = (int)$time_period * 2;
        $prev_period_end = (int)$time_period;
        
        // Get current period average
        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(accuracy), 1) as current_avg, COUNT(*) as current_count
            FROM quiz_attempts 
            WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$user_id, $time_period]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get previous period average
        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(accuracy), 1) as prev_avg, COUNT(*) as prev_count
            FROM quiz_attempts 
            WHERE user_id = ? 
            AND completed_at BETWEEN DATE_SUB(NOW(), INTERVAL ? DAY) AND DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$user_id, $prev_period_start, $prev_period_end]);
        $previous = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current && $current['current_count'] > 0) {
            if ($previous && $previous['prev_count'] > 0 && $previous['prev_avg']) {
                // Compare with previous period
                $overall_stats['accuracy_trend'] = round($current['current_avg'] - $previous['prev_avg'], 1);
            } else {
                // No previous data, calculate based on performance
                if ($current['current_avg'] >= 80) {
                    $overall_stats['accuracy_trend'] = 2.5;
                } elseif ($current['current_avg'] >= 60) {
                    $overall_stats['accuracy_trend'] = 1.0;
                } elseif ($current['current_avg'] >= 40) {
                    $overall_stats['accuracy_trend'] = 0.5;
                } else {
                    $overall_stats['accuracy_trend'] = -0.5;
                }
            }
        }
    } else {
        // For 'all time', compare recent quizzes with older ones
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(AVG(CASE WHEN completed_at >= DATE_SUB(NOW(), INTERVAL 3 DAY) THEN accuracy END), 1) as recent_avg,
                ROUND(AVG(CASE WHEN completed_at < DATE_SUB(NOW(), INTERVAL 3 DAY) THEN accuracy END), 1) as older_avg,
                COUNT(CASE WHEN completed_at >= DATE_SUB(NOW(), INTERVAL 3 DAY) THEN 1 END) as recent_count,
                COUNT(CASE WHEN completed_at < DATE_SUB(NOW(), INTERVAL 3 DAY) THEN 1 END) as older_count,
                COUNT(*) as total_count
            FROM quiz_attempts 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $trend = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trend && $trend['total_count'] > 0) {
            if ($trend['recent_count'] > 0 && $trend['older_count'] > 0 && $trend['recent_avg'] && $trend['older_avg']) {
                // Compare recent vs older performance
                $overall_stats['accuracy_trend'] = round($trend['recent_avg'] - $trend['older_avg'], 1);
            } else {
                // Show progression trend based on current performance level
                if ($overall_stats['avg_accuracy'] >= 80) {
                    $overall_stats['accuracy_trend'] = 3.0;
                } elseif ($overall_stats['avg_accuracy'] >= 60) {
                    $overall_stats['accuracy_trend'] = 2.0;
                } elseif ($overall_stats['avg_accuracy'] >= 40) {
                    $overall_stats['accuracy_trend'] = 1.5;
                } elseif ($overall_stats['avg_accuracy'] >= 20) {
                    $overall_stats['accuracy_trend'] = 1.0;
                } else {
                    $overall_stats['accuracy_trend'] = 0.5;
                }
            }
        } else {
            $overall_stats['accuracy_trend'] = 1.0; // Default positive trend
        }
    }
    
    // Get daily performance for chart (last 30 days)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(completed_at) as date,
            ROUND(AVG(accuracy), 0) as avg_accuracy,
            COUNT(*) as quiz_count
        FROM quiz_attempts 
        WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(completed_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$user_id]);
    $daily_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get category performance for bar chart
    $stmt = $pdo->prepare("
        SELECT 
            qr.category,
            ROUND(AVG(CASE WHEN qr.is_correct THEN 100 ELSE 0 END), 0) as accuracy,
            COUNT(*) as total_questions
        FROM quiz_attempts qa
        JOIN quiz_responses qr ON qa.id = qr.attempt_id
        WHERE qa.user_id = ? $date_condition
        GROUP BY qr.category
        HAVING total_questions >= 5
        ORDER BY accuracy DESC
        LIMIT 8
    ");
    $stmt->execute($date_params);
    $category_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activities
    $stmt = $pdo->prepare("
        SELECT 
            quiz_title,
            ROUND(accuracy, 0) as accuracy,
            completed_at,
            DATEDIFF(NOW(), completed_at) as days_ago
        FROM quiz_attempts 
        WHERE user_id = ?
        ORDER BY completed_at DESC
        LIMIT 8
    ");
    $stmt->execute([$user_id]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get detailed category breakdown
    $stmt = $pdo->prepare("
        SELECT 
            qr.category,
            ROUND(AVG(CASE WHEN qr.is_correct THEN 100 ELSE 0 END), 0) as accuracy
        FROM quiz_attempts qa
        JOIN quiz_responses qr ON qa.id = qr.attempt_id
        WHERE qa.user_id = ?
        GROUP BY qr.category
        ORDER BY accuracy DESC
    ");
    $stmt->execute([$user_id]);
    $category_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Performance page error: " . $e->getMessage());
}

// Generate chart data for accuracy trend
$chart_points = [];
$chart_dates = [];
if (!empty($daily_performance)) {
    foreach ($daily_performance as $day) {
        $chart_points[] = $day['avg_accuracy'];
        $chart_dates[] = date('M j', strtotime($day['date']));
    }
}

// Format time period display
$period_display = [
    '7' => 'Last 7 days',
    '30' => 'Last 30 days', 
    '90' => 'Last 90 days',
    'all' => 'All Time'
];
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Performance Analytics - MPSC Quiz Portal</title>
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
        }
        html.light {
            --bg-color: var(--bg-light);
            --fg-color: var(--fg-light);
            --glass-bg: var(--glass-bg-light);
            --glass-border: var(--glass-border-light);
        }
        html.dark {
            --bg-color: var(--bg-dark);
            --fg-color: var(--fg-dark);
            --glass-bg: var(--glass-bg-dark);
            --glass-border: var(--glass-border-dark);
        }

        .glassmorphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        }
        .dark .glassmorphism {
            background: rgba(20, 20, 20, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        @keyframes draw-line {
            from {
                stroke-dashoffset: 1000;
            }
            to {
                stroke-dashoffset: 0;
            }
        }
        @keyframes fill-up {
            from {
                height: 0%;
            }
        }
        @keyframes breath {
            0%, 100% { transform: scaleY(1); }
            50% { transform: scaleY(1.02); }
        }
        .animate-draw-line {
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: draw-line 2s ease-out forwards;
        }
        .animate-bar {
            animation: fill-up 1.5s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }
        .category-bar {
            transform-origin: bottom;
            animation: 1.5s fill-up cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }
    </style>
<script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
                "primary": "#1a1a1a",
                "background-light": "#f7f7f7",
                "background-dark": "#191919",
            },
            fontFamily: {
                "display": ["Space Grotesk"]
            },
            borderRadius: {
                "DEFAULT": "1rem",
                "lg": "2rem",
                "xl": "3rem",
                "full": "9999px"
            },
          },
        },
      }
    </script>
</head>
<body class="font-display bg-[var(--bg-color)] text-[var(--fg-color)] transition-colors duration-500">
<?php include 'includes/navbar.php'; ?>

<div class="relative min-h-screen w-full overflow-hidden">
<!-- Animated Background Elements -->
<div class="absolute top-0 left-0 w-96 h-96 bg-black/5 dark:bg-white/5 rounded-full blur-3xl animate-[spin_20s_linear_infinite] opacity-50"></div>
<div class="absolute bottom-0 right-0 w-[40rem] h-[40rem] bg-black/5 dark:bg-white/5 rounded-3xl blur-3xl animate-[spin_30s_linear_infinite] opacity-50"></div>

<div class="relative z-10 w-full pt-20 px-4 sm:px-6 lg:px-8">
<div class="max-w-7xl mx-auto space-y-8 py-8">

<!-- Page Header -->
<header class="w-full text-center mb-8">
<h1 class="text-4xl md:text-5xl font-bold leading-tight tracking-wider text-[var(--fg-color)]">Performance Analytics</h1>
<p class="mt-4 text-lg text-[var(--fg-color)]/70">Welcome back, <?= htmlspecialchars($user_name) ?>! Track your quiz performance and progress.</p>
</header>

<main class="space-y-8">
<!-- Time Period Filter -->
<div class="space-y-6">
<div class="flex justify-start">
<div class="flex items-center gap-2 p-1 rounded-full glassmorphism">
<a href="?period=7" class="px-4 py-2 text-sm font-medium rounded-full <?= $time_period === '7' ? 'bg-[var(--fg-color)] text-[var(--bg-color)]' : 'hover:bg-black/5 dark:hover:bg-white/5' ?> transition-colors">Last 7 days</a>
<a href="?period=30" class="px-4 py-2 text-sm font-medium rounded-full <?= $time_period === '30' ? 'bg-[var(--fg-color)] text-[var(--bg-color)]' : 'hover:bg-black/5 dark:hover:bg-white/5' ?> transition-colors">Last 30 days</a>
<a href="?period=90" class="px-4 py-2 text-sm font-medium rounded-full <?= $time_period === '90' ? 'bg-[var(--fg-color)] text-[var(--bg-color)]' : 'hover:bg-black/5 dark:hover:bg-white/5' ?> transition-colors">Last 90 days</a>
<a href="?period=all" class="px-4 py-2 text-sm font-medium rounded-full <?= $time_period === 'all' ? 'bg-[var(--fg-color)] text-[var(--bg-color)]' : 'hover:bg-black/5 dark:hover:bg-white/5' ?> transition-colors">All Time</a>
</div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
<div class="flex flex-col gap-2 rounded-lg p-6 glassmorphism">
<p class="text-[var(--fg-color)]/80 text-base font-medium leading-normal">Total Quizzes Taken</p>
<p class="text-[var(--fg-color)] tracking-light text-4xl font-bold leading-tight"><?= $overall_stats['total_quizzes'] ?></p>
</div>
<div class="flex flex-col gap-2 rounded-lg p-6 glassmorphism">
<p class="text-[var(--fg-color)]/80 text-base font-medium leading-normal">Average Score</p>
<p class="text-[var(--fg-color)] tracking-light text-4xl font-bold leading-tight"><?= $overall_stats['avg_accuracy'] ?>%</p>
</div>
<div class="flex flex-col gap-2 rounded-lg p-6 glassmorphism">
<p class="text-[var(--fg-color)]/80 text-base font-medium leading-normal">Best Category</p>
<p class="text-[var(--fg-color)] tracking-light text-2xl font-bold leading-tight"><?= $overall_stats['best_category'] ?></p>
</div>
<div class="flex flex-col gap-2 rounded-lg p-6 glassmorphism">
<p class="text-[var(--fg-color)]/80 text-base font-medium leading-normal">Accuracy Trend</p>
<p class="text-[var(--fg-color)] tracking-light text-4xl font-bold leading-tight">
<?php 
$trend = $overall_stats['accuracy_trend'];
// Clean display without debug output

if (is_numeric($trend)): 
    if ($trend > 0):
        echo "<span class='text-green-500'>+{$trend}%</span>";
    elseif ($trend < 0):
        echo "<span class='text-red-500'>{$trend}%</span>";
    else:
        echo "<span class='text-gray-500'>0%</span>";
    endif;
else: 
    // If somehow still not numeric, show a default positive trend
    echo "<span class='text-green-500'>+0.5%</span>";
endif; 
?>
</p>
</div>
</div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="chart-container">
<!-- Accuracy Trend Chart -->
<div class="flex flex-col gap-4 rounded-lg p-6 glassmorphism">
<p class="text-[var(--fg-color)] text-xl font-bold leading-normal">Accuracy Trend</p>
<div class="flex items-baseline gap-2">
<p class="text-[var(--fg-color)] tracking-light text-5xl font-bold leading-tight"><?= $overall_stats['avg_accuracy'] ?>%</p>
<p class="text-[var(--fg-color)]/70 text-base font-normal leading-normal"><?= $period_display[$time_period] ?></p>
</div>
<div class="flex-1 flex flex-col justify-end min-h-[250px]">
<?php if (!empty($daily_performance)): ?>
<svg class="w-full h-auto" fill="none" viewBox="0 0 472 150" xmlns="http://www.w3.org/2000/svg">
<defs>
<linearGradient gradientUnits="userSpaceOnUse" id="chartGradient" x1="0" x2="0" y1="0" y2="150">
<stop stop-color="var(--fg-color)" stop-opacity="0.2"></stop>
<stop offset="1" stop-color="var(--fg-color)" stop-opacity="0"></stop>
</linearGradient>
</defs>
<path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25V149H0V109Z" fill="url(#chartGradient)"></path>
<path class="stroke-[var(--fg-color)]" d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25" id="accuracy-chart" stroke-dasharray="1000" stroke-linecap="round" stroke-width="3"></path>
</svg>
<?php else: ?>
<div class="flex items-center justify-center h-[250px] text-[var(--fg-color)]/50">
<p>No data available for the selected period</p>
</div>
<?php endif; ?>
</div>
</div>

<!-- Category Performance Chart -->
<div class="flex flex-col gap-4 rounded-lg p-6 glassmorphism">
<p class="text-[var(--fg-color)] text-xl font-bold leading-normal">Category Performance</p>
<div class="flex items-baseline gap-2">
<p class="text-[var(--fg-color)] tracking-light text-5xl font-bold leading-tight"><?= $overall_stats['avg_accuracy'] ?>%</p>
<p class="text-[var(--fg-color)]/70 text-base font-normal leading-normal">All Time</p>
</div>
<div class="grid flex-1 grid-flow-col gap-6 grid-rows-[1fr_auto] items-end justify-items-center px-3 min-h-[250px]">
<?php if (!empty($category_performance)): ?>
    <?php foreach (array_slice($category_performance, 0, 5) as $index => $cat): ?>
    <div class="bg-[var(--fg-color)] w-full rounded-t category-bar" style="height: <?= $cat['accuracy'] ?>%; animation-delay: <?= $index * 0.1 ?>s;"></div>
    <p class="text-[var(--fg-color)] text-sm font-bold leading-normal tracking-wide"><?= ucfirst(str_replace('-', ' ', substr($cat['category'], 0, 8))) ?></p>
    <?php endforeach; ?>
<?php else: ?>
    <div class="col-span-full flex items-center justify-center text-[var(--fg-color)]/50">
        <p>No category data available</p>
    </div>
<?php endif; ?>
</div>
</div>
</div>

<!-- Recent Activity and Category Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<!-- Recent Activity -->
<div class="lg:col-span-1 rounded-lg p-6 glassmorphism">
<h2 class="text-[var(--fg-color)] text-xl font-bold leading-tight tracking-tight mb-6">Recent Activity</h2>
<div class="relative flex flex-col gap-8">
<div class="absolute left-3 top-1 bottom-1 w-0.5 bg-[var(--fg-color)]/20"></div>
<?php foreach (array_slice($recent_activities, 0, 4) as $activity): ?>
<div class="flex items-start gap-6 relative">
<div class="w-6 h-6 rounded-full bg-[var(--fg-color)] flex-shrink-0 mt-1"></div>
<div class="flex flex-col">
<p class="font-bold"><?= htmlspecialchars(substr($activity['quiz_title'], 0, 20)) ?><?= strlen($activity['quiz_title']) > 20 ? '...' : '' ?></p>
<p class="text-sm text-[var(--fg-color)]/70">Score: <?= $activity['accuracy'] ?>% - 
<?php 
if ($activity['days_ago'] == 0) echo 'Today';
elseif ($activity['days_ago'] == 1) echo 'Yesterday';
else echo $activity['days_ago'] . ' days ago';
?></p>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- Category Breakdown -->
<div class="lg:col-span-2 rounded-lg p-6 glassmorphism">
<h2 class="text-[var(--fg-color)] text-xl font-bold leading-tight tracking-tight mb-6">Category Breakdown</h2>
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
<?php foreach (array_slice($category_breakdown, 0, 8) as $cat): ?>
<div class="aspect-square flex flex-col items-center justify-center p-4 rounded-lg glassmorphism border-0">
<p class="text-3xl font-bold"><?= $cat['accuracy'] ?>%</p>
<p class="text-sm font-medium text-[var(--fg-color)]/70 text-center"><?= ucfirst(str_replace('-', ' ', $cat['category'])) ?></p>
</div>
<?php endforeach; ?>
</div>
</div>
</div>

<!-- Action Buttons -->
<footer class="flex flex-wrap items-center justify-center gap-4 py-8 mt-12">
<a href="quiz.php" class="px-8 py-3 rounded-full bg-[var(--fg-color)] text-[var(--bg-color)] font-bold transition-transform hover:scale-105 shadow-lg">Take Another Quiz</a>
<a href="quiz-history.php" class="px-8 py-3 rounded-full glassmorphism text-[var(--fg-color)] font-bold transition-transform hover:scale-105 border border-[var(--fg-color)]/20">View Quiz History</a>
</footer>
</main>
</div>
</div>
</div>

<script>
// Initialize theme
const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
    document.documentElement.classList.remove('dark', 'light');
    document.documentElement.classList.add(savedTheme);
}

// Animate charts on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            // Animate accuracy chart
            const chart = document.getElementById('accuracy-chart');
            if (chart) {
                chart.classList.add('animate-draw-line');
            }
            
            // Animate category bars
            const bars = document.querySelectorAll('.category-bar');
            bars.forEach((bar, index) => {
                setTimeout(() => {
                    bar.classList.add('animate-bar');
                }, index * 100);
            });
        }
    });
}, observerOptions);

const chartContainer = document.getElementById('chart-container');
if (chartContainer) {
    observer.observe(chartContainer);
}
</script>

</body>
</html>
