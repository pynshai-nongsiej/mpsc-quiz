<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';

// Get current user if logged in
$currentUser = null;
if (isLoggedIn()) {
    $currentUser = fetchOne("SELECT id, username, email, full_name, created_at FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// Get leaderboard data
try {
    // All-time leaderboard
    $allTimeLeaderboard = fetchAll("
        SELECT 
            u.id,
            u.username,
            u.full_name,
            COUNT(qa.id) as total_quizzes,
            AVG(qa.score_percentage) as avg_score,
            MAX(qa.score_percentage) as best_score,
            SUM(qa.time_taken) as total_time,
            ROW_NUMBER() OVER (ORDER BY AVG(qa.score_percentage) DESC, COUNT(qa.id) DESC) as rank_position
        FROM users u
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
        WHERE qa.id IS NOT NULL
        GROUP BY u.id, u.username, u.full_name
        HAVING COUNT(qa.id) > 0
        ORDER BY avg_score DESC, total_quizzes DESC
        LIMIT 50
    ");
    
    // Weekly leaderboard
    $weeklyLeaderboard = fetchAll("
        SELECT 
            u.id,
            u.username,
            u.full_name,
            COUNT(qa.id) as total_quizzes,
            AVG(qa.score_percentage) as avg_score,
            MAX(qa.score_percentage) as best_score,
            SUM(qa.time_taken) as total_time,
            ROW_NUMBER() OVER (ORDER BY AVG(qa.score_percentage) DESC, COUNT(qa.id) DESC) as rank_position
        FROM users u
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
        WHERE qa.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY u.id, u.username, u.full_name
        HAVING COUNT(qa.id) > 0
        ORDER BY avg_score DESC, total_quizzes DESC
        LIMIT 50
    ");
    
    // Monthly leaderboard
    $monthlyLeaderboard = fetchAll("
        SELECT 
            u.id,
            u.username,
            u.full_name,
            COUNT(qa.id) as total_quizzes,
            AVG(qa.score_percentage) as avg_score,
            MAX(qa.score_percentage) as best_score,
            SUM(qa.time_taken) as total_time,
            ROW_NUMBER() OVER (ORDER BY AVG(qa.score_percentage) DESC, COUNT(qa.id) DESC) as rank_position
        FROM users u
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
        WHERE qa.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY u.id, u.username, u.full_name
        HAVING COUNT(qa.id) > 0
        ORDER BY avg_score DESC, total_quizzes DESC
        LIMIT 50
    ");
    
    // Get current user's rank if logged in
    $userRank = null;
    if ($currentUser) {
        $userRankResult = fetchOne("
            SELECT rank_position FROM (
                SELECT 
                    u.id,
                    ROW_NUMBER() OVER (ORDER BY AVG(qa.score_percentage) DESC, COUNT(qa.id) DESC) as rank_position
                FROM users u
                LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
                WHERE qa.id IS NOT NULL
                GROUP BY u.id
                HAVING COUNT(qa.id) > 0
            ) ranked_users
            WHERE id = ?
        ", [$currentUser['id']]);
        $userRank = $userRankResult ? $userRankResult['rank_position'] : null;
    }
    
} catch (Exception $e) {
    $allTimeLeaderboard = [];
    $weeklyLeaderboard = [];
    $monthlyLeaderboard = [];
    $userRank = null;
}

function getRankIcon($position) {
    switch ($position) {
        case 1: return '🥇';
        case 2: return '🥈';
        case 3: return '🥉';
        default: return '#' . $position;
    }
}

function getRankClass($position) {
    switch ($position) {
        case 1: return 'rank-gold';
        case 2: return 'rank-silver';
        case 3: return 'rank-bronze';
        default: return 'rank-default';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - MPSC Quiz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: rgba(30, 41, 59, 0.1);
            --input-border: rgba(30, 41, 59, 0.3);
            --button-bg: rgba(30, 41, 59, 0.1);
            --button-hover: rgba(30, 41, 59, 0.2);
        }
        
        html.dark {
            --bg-primary: #000000;
            --bg-secondary: #000000;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --input-border: rgba(255, 255, 255, 0.3);
            --button-bg: rgba(255, 255, 255, 0.1);
            --button-hover: rgba(255, 255, 255, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .frosted-row {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .frosted-row:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .glow-border {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .glow-border:hover {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .tab-button {
            background: var(--button-bg);
            color: var(--text-primary);
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .tab-button:hover {
            background: var(--button-hover);
            transform: translateY(-1px);
        }
        
        .tab-button.active {
            background: var(--button-hover);
            border-color: var(--input-border);
            color: var(--text-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        

    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/mobile_navbar.php'; ?>
    <div class="min-h-screen flex items-center justify-center p-4 pt-20">
        <div class="w-full max-w-4xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2">🏆 Leaderboard</h1>
                <p class="text-gray-400">See how you rank against other quiz takers</p>
            </div>

            <!-- Leaderboard Container -->
            <div class="glow-border rounded-2xl p-8">
                <!-- Tab Navigation -->
                <div class="flex justify-center gap-4 mb-8">
                    <button class="tab-button active" onclick="showTab('weekly')" id="weekly-tab">
                        Week
                    </button>
                    <button class="tab-button" onclick="showTab('monthly')" id="monthly-tab">
                        Month
                    </button>
                    <button class="tab-button" onclick="showTab('all-time')" id="all-time-tab">
                        All-Time
                    </button>
                </div>
            
                <!-- Weekly Leaderboard -->
                <div id="weekly-content" class="tab-content active">
                    <?php if (empty($weeklyLeaderboard)): ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📊</div>
                            <h4 class="text-xl font-semibold mb-2">No Activity This Week</h4>
                            <p class="text-gray-400">Take a quiz to appear on this week's leaderboard!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($weeklyLeaderboard as $index => $user): ?>
                                <div class="frosted-row rounded-xl p-4 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center font-bold">
                                            <?php echo $user['rank_position']; ?>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                            <p class="text-gray-400 text-sm">@<?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold"><?php echo number_format($user['avg_score'], 1); ?>%</div>
                                        <div class="text-sm text-gray-400"><?php echo $user['total_quizzes']; ?> quiz<?php echo $user['total_quizzes'] != 1 ? 'es' : ''; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            
                <!-- Monthly Leaderboard -->
                <div id="monthly-content" class="tab-content">
                    <?php if (empty($monthlyLeaderboard)): ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📅</div>
                            <h4 class="text-xl font-semibold mb-2">No Activity This Month</h4>
                            <p class="text-gray-400">Take a quiz to appear on this month's leaderboard!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($monthlyLeaderboard as $index => $user): ?>
                                <div class="frosted-row rounded-xl p-4 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center font-bold">
                                            <?php echo $user['rank_position']; ?>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                            <p class="text-gray-400 text-sm">@<?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold"><?php echo number_format($user['avg_score'], 1); ?>%</div>
                                        <div class="text-sm text-gray-400"><?php echo $user['total_quizzes']; ?> quiz<?php echo $user['total_quizzes'] != 1 ? 'es' : ''; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            
                <!-- All-Time Leaderboard -->
                <div id="all-time-content" class="tab-content">
                    <?php if (empty($allTimeLeaderboard)): ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">🏆</div>
                            <h4 class="text-xl font-semibold mb-2">No Champions Yet</h4>
                            <p class="text-gray-400">Be the first to take a quiz and claim your spot!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($allTimeLeaderboard as $index => $user): ?>
                                <div class="frosted-row rounded-xl p-4 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center font-bold">
                                            <?php echo $user['rank_position']; ?>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                            <p class="text-gray-400 text-sm">@<?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold"><?php echo number_format($user['avg_score'], 1); ?>%</div>
                                        <div class="text-sm text-gray-400"><?php echo $user['total_quizzes']; ?> quiz<?php echo $user['total_quizzes'] != 1 ? 'es' : ''; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tab buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName + '-content').classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }
        
        // Theme initialization - sync with navbar
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
</body>
</html>