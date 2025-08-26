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
            100.0 as completion_rate
        FROM quiz_attempts 
        WHERE user_id = ?" . $date_condition . "
    ");
    $stmt->execute($date_params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $overall_stats = $result;
    }
    
    // Get daily performance for the selected time period
    $daily_period = $time_period === 'all' ? '90' : $time_period; // Default to 90 days for 'all'
    $stmt = $pdo->prepare("
        SELECT 
            DATE(completed_at) as quiz_date,
            AVG(accuracy) as daily_accuracy
        FROM quiz_attempts 
        WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(completed_at)
        ORDER BY quiz_date ASC
    ");
    $stmt->execute([$user_id, (int)$daily_period]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($result) {
        $daily_performance = $result;
    }
    
    // Get category performance from quiz_responses table
    $category_date_condition = str_replace('completed_at', 'qa.completed_at', $date_condition);
    $stmt = $pdo->prepare("
        SELECT 
            qr.category,
            AVG(CASE WHEN qr.is_correct THEN 100 ELSE 0 END) as avg_accuracy,
            COUNT(DISTINCT qa.id) as quiz_count
        FROM quiz_responses qr
        JOIN quiz_attempts qa ON qr.attempt_id = qa.id
        WHERE qa.user_id = ? AND qr.category IS NOT NULL" . $category_date_condition . "
        GROUP BY qr.category
        ORDER BY avg_accuracy DESC
    ");
    $stmt->execute($date_params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($result) {
        $category_performance = $result;
    }
    
    // Get recent quiz results
    $stmt = $pdo->prepare("
        SELECT 
            quiz_title,
            completed_at as created_at,
            accuracy,
            time_taken,
            'completed' as status
        FROM quiz_attempts 
        WHERE user_id = ?" . $date_condition . "
        ORDER BY completed_at DESC
        LIMIT 10
    ");
    $stmt->execute($date_params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($result) {
        $recent_quizzes = $result;
    }
    
} catch (PDOException $e) {
    $error_message = 'Error fetching performance data: ' . $e->getMessage();
}

// Helper function to format time
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

// Helper function to get status color
function getStatusColor($status) {
    switch ($status) {
        case 'completed':
            return 'text-[var(--success-color)]';
        case 'failed':
            return 'text-[var(--danger-color)]';
        case 'in_progress':
            return 'text-[var(--warning-color)]';
        default:
            return 'text-[var(--text-secondary)]';
    }
}

// Helper function to get accuracy color
function getAccuracyColor($accuracy) {
    if ($accuracy >= 80) {
        return 'var(--success-color)';
    } elseif ($accuracy >= 60) {
        return 'var(--warning-color)';
    } else {
        return 'var(--danger-color)';
    }
}

// Prepare daily performance data for chart
$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$daily_data = array_fill(0, 7, 0);

if (!empty($daily_performance)) {
    foreach ($daily_performance as $day) {
        if (isset($day['quiz_date']) && isset($day['daily_accuracy'])) {
            $day_of_week = date('N', strtotime($day['quiz_date'])) - 1; // 0-6 for Mon-Sun
            if ($day_of_week >= 0 && $day_of_week < 7) {
                $daily_data[$day_of_week] = round($day['daily_accuracy'], 1);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quiz Analytics Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
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
            background: linear-gradient(135deg, var(--background-color) 0%, #1a1a1a 100%);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .main_container {
            @apply container mx-auto px-4 py-8;
        }
        .card {
            @apply bg-[var(--card-background)] rounded-lg p-6 border border-[var(--card-border)] backdrop-blur-sm transition-all duration-300 hover:bg-[rgba(255,255,255,0.15)] hover:border-[rgba(255,255,255,0.3)];
        }
        .card_title {
            @apply text-lg font-semibold mb-2 text-[var(--text-secondary)];
        }
        .kpi_value {
            @apply text-3xl font-bold text-[var(--text-primary)];
        }
        .kpi_change {
            @apply text-sm ml-2;
        }
        .chart_container {
            @apply h-64;
        }
        .filter_container {
            @apply flex items-center justify-between mb-6;
        }
        .filter_group {
            @apply flex space-x-2;
        }
        .filter_toggle {
            background-color: var(--card-bg);
            color: var(--text-primary);
            @apply px-4 py-2 rounded-lg text-sm font-medium transition-colors;
            border: 1px solid var(--card-border);
        }
        .filter_toggle:hover {
            background-color: var(--card-hover-shadow);
            border-color: var(--text-secondary);
        }
        .filter_toggle.active {
            background-color: var(--text-primary);
            color: var(--background-color);
            border-color: var(--text-primary);
        }
        .dropdown_button {
            background-color: var(--card-bg);
            color: var(--text-primary);
            @apply px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2;
            border: 1px solid var(--card-border);
        }
        .dropdown_button:hover {
            background-color: var(--card-hover-shadow);
            border-color: var(--text-secondary);
        }
        .typography_h1 {
            @apply text-3xl font-bold mb-6 text-[var(--text-primary)];
        }
        .typography_h2 {
            @apply text-2xl font-semibold mb-4 text-[var(--text-primary)];
        }
        .typography_body {
            @apply text-base text-[var(--text-secondary)];
        }
        .progress_bar_bg {
            @apply bg-gray-700 rounded-full h-2.5;
        }
        .progress_bar {
            @apply h-2.5 rounded-full;
        }
        .table_row {
            @apply border-b border-[var(--card-border)];
        }
        .table_cell {
            @apply py-3 px-2 text-sm;
        }
    </style>
</head>
<body class="bg-[var(--background-color)]">
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/mobile_navbar.php'; ?>
<div class="min-h-screen flex flex-col">
<main class="main_container flex-1">
<div class="filter_container">
<div class="relative">
<button class="dropdown_button quiz-dropdown">
                    <?php echo $quiz_type === 'all' ? 'All Quizzes' : ucfirst($quiz_type) . ' Quizzes'; ?>
                    <svg class="bi bi-chevron-down" fill="currentColor" height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg">
<path d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z" fill-rule="evenodd"></path>
</svg>
</button>
<div class="dropdown-content absolute top-full left-0 mt-1 bg-[var(--card-bg)] border border-[var(--card-border)] rounded-lg shadow-lg z-10 min-w-[150px] backdrop-blur-sm" style="display: none;">
    <a href="#" class="block px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--card-hover-shadow)] transition-colors" data-type="all">All Quizzes</a>
    <a href="#" class="block px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--card-hover-shadow)] transition-colors" data-type="practice">Practice Quizzes</a>
    <a href="#" class="block px-4 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--card-hover-shadow)] transition-colors" data-type="mock">Mock Tests</a>
</div>
</div>
<div class="filter_group">
<button class="filter_toggle <?php echo $time_period === '7' ? 'active' : ''; ?>" data-period="7">Last 7 days</button>
<button class="filter_toggle <?php echo $time_period === '30' ? 'active' : ''; ?>" data-period="30">Last 30 days</button>
<button class="filter_toggle <?php echo $time_period === '90' ? 'active' : ''; ?>" data-period="90">Last 90 days</button>
<button class="filter_toggle <?php echo $time_period === 'all' ? 'active' : ''; ?>" data-period="all">All Time</button>
</div>
</div>
<?php if ($error_message): ?>
<div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6">
    <p class="text-red-400"><?php echo htmlspecialchars($error_message); ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
<div class="card">
<p class="card_title">Total Quizzes Taken</p>
<p class="kpi_value"><?php echo $overall_stats['total_quizzes'] ?? 0; ?></p>
</div>
<div class="card">
<p class="card_title">Average Accuracy</p>
<p class="kpi_value"><?php echo $overall_stats['avg_accuracy'] ? round($overall_stats['avg_accuracy'], 1) . '%' : '0%'; ?></p>
</div>
<div class="card">
<p class="card_title">Total Time Spent</p>
<p class="kpi_value"><?php echo $overall_stats['total_time'] ? formatTime($overall_stats['total_time']) : '0s'; ?></p>
</div>
<div class="card">
<p class="card_title">Completion Rate</p>
<p class="kpi_value"><?php echo $overall_stats['completion_rate'] ? round($overall_stats['completion_rate'], 1) . '%' : '0%'; ?></p>
</div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
<div class="card lg:col-span-2">
<h2 class="typography_h2">Daily Accuracy Breakdown</h2>
<div class="chart_container flex items-end justify-between gap-2 pt-4">
<?php for ($i = 0; $i < 7; $i++): 
    $accuracy = $daily_data[$i];
    $height = $accuracy > 0 ? $accuracy : 5; // Minimum height for visibility
    $color = 'bg-gray-700';
    $hoverColor = 'var(--success-color)';
    
    if ($accuracy >= 80) {
        $hoverColor = 'var(--success-color)';
    } elseif ($accuracy >= 60) {
        $hoverColor = 'var(--warning-color)';
    } elseif ($accuracy > 0) {;
        $hoverColor = 'var(--danger-color)';
    }
?>
<div class="group relative flex h-full w-full flex-col-reverse items-center gap-2 text-center">
<div class="h-[75%] w-1/2 rounded-t-lg bg-gray-700 transition-colors group-hover:bg-[<?php echo $hoverColor; ?>]" style="height: <?php echo $height; ?>%"></div>
<div class="absolute -top-8 hidden rounded-md bg-gray-900/80 px-2 py-1 text-xs font-bold text-white group-hover:block"><?php echo $accuracy > 0 ? $accuracy . '%' : 'No data'; ?></div>
<p class="text-xs text-[var(--text-secondary)]"><?php echo $days[$i]; ?></p>
</div>
<?php endfor; ?>
</div>
</div>
<div class="card">
<h2 class="typography_h2">Topic Performance</h2>
<div class="space-y-4">
<?php if (empty($category_performance)): ?>
    <p class="text-[var(--text-secondary)] text-center py-4">No category performance data available</p>
<?php else: ?>
    <?php foreach ($category_performance as $category): 
        $accuracy = round($category['avg_accuracy'], 1);
        $progressColor = getAccuracyColor($accuracy);
    ?>
    <div>
    <div class="flex justify-between mb-1 text-sm font-medium text-[var(--text-secondary)]">
    <span><?php echo htmlspecialchars($category['category'] ?: 'General'); ?></span>
    <span class="text-[var(--text-primary)]"><?php echo $accuracy; ?>%</span>
    </div>
    <div class="progress_bar_bg">
    <div class="progress_bar" style="width: <?php echo $accuracy; ?>%; background-color: <?php echo $progressColor; ?>"></div>
    </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>
<div class="card">
<h2 class="typography_h2">Recent Quiz Results</h2>
<div class="overflow-x-auto">
<table class="w-full text-left text-[var(--text-secondary)]">
<thead class="text-xs uppercase">
<tr class="border-b border-[var(--card-border)]">
<th class="table_cell">Quiz Name</th>
<th class="table_cell">Date</th>
<th class="table_cell">Accuracy</th>
<th class="table_cell">Time Spent</th>
<th class="table_cell">Status</th>
</tr>
</thead>
<tbody>
<?php if (empty($recent_quizzes)): ?>
<tr class="table_row">
<td colspan="5" class="table_cell text-center text-[var(--text-secondary)] py-8">No recent quiz results found</td>
</tr>
<?php else: ?>
<?php foreach ($recent_quizzes as $quiz): 
    $accuracy = round($quiz['accuracy'], 1);
    $accuracyColor = getAccuracyColor($accuracy);
    $statusColor = getStatusColor($quiz['status']);
    $timeAgo = date('M j, Y', strtotime($quiz['created_at']));
?>
<tr class="table_row">
<td class="table_cell text-[var(--text-primary)]"><?php echo htmlspecialchars($quiz['quiz_title'] ?: 'Untitled Quiz'); ?></td>
<td class="table_cell"><?php echo $timeAgo; ?></td>
<td class="table_cell" style="color: <?php echo $accuracyColor; ?>"><?php echo $accuracy; ?>%</td>
<td class="table_cell"><?php echo $quiz['time_taken'] ? formatTime($quiz['time_taken']) : '-'; ?></td>
<td class="table_cell <?php echo $statusColor; ?>"><?php echo ucfirst($quiz['status']); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</main>
</div>
<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter_toggle');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const period = this.getAttribute('data-period');
            
            // Update URL with new period parameter
            const url = new URL(window.location);
            url.searchParams.set('period', period);
            
            // Reload page with new parameters
            window.location.href = url.toString();
        });
    });
    
    // Quiz type dropdown functionality
    const quizDropdown = document.querySelector('.quiz-dropdown');
    const dropdownContent = document.querySelector('.dropdown-content');
    
    if (quizDropdown && dropdownContent) {
        quizDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!quizDropdown.contains(e.target)) {
                dropdownContent.style.display = 'none';
            }
        });
        
        // Handle dropdown item clicks
        const dropdownItems = dropdownContent.querySelectorAll('a');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const type = this.getAttribute('data-type') || 'all';
                
                // Update URL with new type parameter
                const url = new URL(window.location);
                url.searchParams.set('type', type);
                
                // Reload page with new parameters
                window.location.href = url.toString();
            });
        });
    }
});
</script>

</body></html>