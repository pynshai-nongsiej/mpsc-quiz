<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Debug: Log session variables at start of result.php
error_log('DEBUG result.php START: quiz_file=' . ($_SESSION['quiz_file'] ?? 'NOT_SET'));
error_log('DEBUG result.php START: answers=' . (isset($_SESSION['answers']) ? 'SET(' . count($_SESSION['answers']) . ')' : 'NOT_SET'));
error_log('DEBUG result.php START: user_id=' . ($_SESSION['user_id'] ?? 'NOT_SET'));
error_log('DEBUG result.php START: All session vars - ' . print_r($_SESSION, true));

$mock_mode = isset($_SESSION['mock_mode']) && $_SESSION['mock_mode'];

if (!isset($_SESSION['quiz_file']) || !isset($_SESSION['answers'])) {
    error_log('DEBUG result.php REDIRECT: Missing session variables - redirecting to index.php');
    header('Location: index.php');
    exit;
}

// Use questions from session for both mock and exam modes
$questions = $_SESSION['questions'] ?? [];
$quiz_title = $_SESSION['quiz_title'] ?? 'Quiz';
$quiz_id = $_SESSION['quiz_file'] ?? 'quiz';

$user_answers = $_SESSION['answers'];
$score = 0;
$total = count($questions);
$max_score = $total * 2; // Each question is worth 2 marks
$results = [];
$_SESSION['quiz_results'] = ['questions' => $questions, 'results' => []];

foreach ($questions as $i => $q) {
    $correct = strtolower(trim($q['answer']));
    $user_raw = isset($user_answers[$i]) ? trim($user_answers[$i]) : '';
    $user = $user_raw;
    
    // Handle different answer formats
    $is_correct = false;
    $user_answer_text = '';
    $correct_answer_text = '';
    
    if (!empty($user)) {
        // Extract just the letter if it's in format 'a)' or 'a.'
        if (preg_match('/^([a-d])[\\)\.]?/i', $user, $matches)) {
            $user = strtolower($matches[1]);
        } else {
            // If it's just a letter, convert to lowercase
            $user = strtolower($user[0] ?? '');
        }
        
        // Get user answer text
        if (isset($q['options'][$user])) {
            $user_answer_text = $q['options'][$user];
        }
    }
    
    // Get correct answer text
    if (isset($q['options'][$correct])) {
        $correct_answer_text = $q['options'][$correct];
    }
    
    $is_correct = ($user === $correct);
    
    
    if ($is_correct) {
        $score += 2;
    }
    
    $results[] = [
        'question' => $q['question'],
        'options' => $q['options'],
        'user_answer' => $user,
        'correct_answer' => $correct,
        'is_correct' => $is_correct,
        'user_answer_text' => $user_answer_text,
        'correct_answer_text' => $correct_answer_text
    ];
}

$_SESSION['quiz_results']['results'] = $results;

// Save to database if user is logged in
if (isset($_SESSION['user_id']) && !$mock_mode) {
    try {
        $pdo = getConnection();
        $accuracy = $max_score > 0 ? ($score / $max_score) * 100 : 0;
        $time_taken = $_SESSION['time_taken'] ?? 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO quiz_attempts (user_id, quiz_title, quiz_type, total_questions, correct_answers, accuracy, time_taken, completed_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $quiz_title,
            $quiz_id,
            $total,
            $score / 2, // Convert back to number of correct answers
            $accuracy,
            $time_taken
        ]);
        
        error_log('DEBUG result.php: Quiz attempt saved to database');
    } catch (Exception $e) {
        error_log('DEBUG result.php: Error saving to database: ' . $e->getMessage());
    }
}

// Calculate performance metrics
$percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
$correct_count = $score / 2;
$wrong_count = $total - $correct_count;

// Performance message
$performance_message = '';
$performance_color = '';
if ($percentage >= 80) {
    $performance_message = "Excellent work! Outstanding performance! 🎉";
    $performance_color = 'text-green-500';
} elseif ($percentage >= 60) {
    $performance_message = "Good job! Keep up the great work! 👍";
    $performance_color = 'text-blue-500';
} else {
    $performance_message = "Keep practicing! You'll improve with time! 💪";
    $performance_color = 'text-orange-500';
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Quiz Results - MPSC Quiz Portal</title>
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
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

        .glassmorphic {
            background-color: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
        }

        .score-circle {
            background: conic-gradient(
                from 0deg,
                #10b981 0deg <?= $percentage * 3.6 ?>deg,
                rgba(255, 255, 255, 0.1) <?= $percentage * 3.6 ?>deg 360deg
            );
            border-radius: 50%;
            padding: 4px;
        }

        .score-inner {
            background: var(--bg-color);
            border-radius: 50%;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        .stagger-4 { animation-delay: 0.4s; }
    </style>
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
</head>
<body class="font-display bg-[var(--bg-color)] text-[var(--fg-color)] transition-colors duration-500 light">
<?php include 'includes/navbar.php'; ?>

<div class="relative flex min-h-screen w-full flex-col overflow-hidden pt-20">
<!-- Animated Background Elements -->
<div class="absolute top-[-20%] left-[-15%] w-96 h-96 bg-black/5 dark:bg-white/5 rounded-full blur-3xl animate-[spin_20s_linear_infinite] opacity-50"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-[40rem] h-[40rem] bg-black/5 dark:bg-white/5 rounded-3xl blur-3xl animate-[spin_30s_linear_infinite] opacity-50"></div>

<div class="relative z-10 flex h-full w-full max-w-4xl mx-auto grow flex-col px-4 py-12 sm:px-6 lg:px-8">

<!-- Page Header -->
<header class="w-full text-center mb-8 fade-in-up">
<h1 class="text-5xl font-bold leading-tight tracking-wider text-black dark:text-white sm:text-6xl">Quiz Complete!</h1>
<p class="mt-4 text-lg text-[var(--subtle-text)]">Here's how you performed on "<?= htmlspecialchars($quiz_title) ?>"</p>
</header>

<!-- Score Section -->
<div class="mb-12 fade-in-up stagger-1">
<div class="glassmorphic mx-auto max-w-2xl rounded-xl p-8 shadow-lg">
<div class="flex flex-col lg:flex-row items-center gap-8">
<!-- Score Circle -->
<div class="flex-shrink-0">
<div class="score-circle w-48 h-48">
<div class="score-inner">
<div class="text-4xl font-bold text-[var(--fg-color)]"><?= round($percentage) ?>%</div>
<div class="text-sm text-[var(--subtle-text)] mt-1"><?= $score ?>/<?= $max_score ?></div>
</div>
</div>
</div>

<!-- Score Details -->
<div class="flex-1 text-center lg:text-left">
<h2 class="text-3xl font-bold text-[var(--fg-color)] mb-4">Your Performance</h2>
<div class="grid grid-cols-2 gap-6 mb-6">
<div class="glassmorphic rounded-lg p-4">
<div class="text-2xl font-bold text-green-500"><?= $correct_count ?></div>
<div class="text-sm text-[var(--subtle-text)]">Correct</div>
</div>
<div class="glassmorphic rounded-lg p-4">
<div class="text-2xl font-bold text-red-500"><?= $wrong_count ?></div>
<div class="text-sm text-[var(--subtle-text)]">Wrong</div>
</div>
</div>
<p class="text-lg <?= $performance_color ?> font-medium"><?= $performance_message ?></p>
</div>
</div>
</div>
</div>

<!-- Action Buttons -->
<div class="mb-12 fade-in-up stagger-2">
<div class="flex flex-col gap-4 sm:flex-row sm:justify-center">
<a href="quiz.php?<?= $mock_mode ? 'mock=1' : 'quiz=' . urlencode($quiz_id) ?>" class="flex h-14 min-w-[140px] cursor-pointer items-center justify-center overflow-hidden rounded-xl bg-black px-8 text-base font-bold text-white transition-transform duration-200 hover:scale-105 dark:bg-white dark:text-black">
<span class="truncate">Try Again</span>
</a>
<a href="index.php" class="glassmorphic flex h-14 min-w-[140px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-black/20 px-8 text-base font-bold text-black transition-transform duration-200 hover:scale-105 dark:border-white/20 dark:text-white hover:bg-black/5 dark:hover:bg-white/5">
<span class="truncate">Back to Home</span>
</a>
<?php if (isLoggedIn()): ?>
<a href="quiz-history.php" class="glassmorphic flex h-14 min-w-[140px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-black/20 px-8 text-base font-bold text-black transition-transform duration-200 hover:scale-105 dark:border-white/20 dark:text-white hover:bg-black/5 dark:hover:bg-white/5">
<span class="truncate">View History</span>
</a>
<?php endif; ?>
</div>
</div>

<!-- Detailed Results (Optional - can be toggled) -->
<div class="fade-in-up stagger-3">
<div class="glassmorphic rounded-xl p-6 shadow-lg">
<div class="flex items-center justify-between mb-6">
<h3 class="text-2xl font-bold text-[var(--fg-color)]">Detailed Results</h3>
<button id="toggle-details" class="glassmorphic px-4 py-2 rounded-lg text-sm font-medium text-[var(--fg-color)] hover:bg-black/5 dark:hover:bg-white/5 transition-colors">
Show Details
</button>
</div>

<div id="detailed-results" class="hidden space-y-4">
<?php foreach ($results as $index => $result): ?>
<div class="glassmorphic rounded-lg p-4 <?= $result['is_correct'] ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' ?>">
<div class="mb-3">
<span class="text-sm font-medium text-[var(--subtle-text)]">Question <?= $index + 1 ?></span>
<h4 class="text-lg font-medium text-[var(--fg-color)] mt-1"><?= htmlspecialchars($result['question']) ?></h4>
</div>
<div class="grid gap-2">
<div class="flex items-center gap-2">
<span class="text-sm text-[var(--subtle-text)]">Your answer:</span>
<span class="px-2 py-1 rounded text-sm <?= $result['is_correct'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' ?>">
<?= $result['user_answer_text'] ?: 'No answer' ?>
</span>
</div>
<?php if (!$result['is_correct']): ?>
<div class="flex items-center gap-2">
<span class="text-sm text-[var(--subtle-text)]">Correct answer:</span>
<span class="px-2 py-1 rounded text-sm bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
<?= $result['correct_answer_text'] ?>
</span>
</div>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>

<!-- Performance Tips -->
<div class="mt-12 fade-in-up stagger-4">
<div class="glassmorphic rounded-xl p-6 shadow-lg">
<h3 class="text-xl font-bold text-[var(--fg-color)] mb-4">💡 Tips for Improvement</h3>
<div class="grid md:grid-cols-2 gap-4 text-sm text-[var(--subtle-text)]">
<?php if ($percentage < 60): ?>
<div class="flex items-start gap-2">
<span class="text-blue-500">📚</span>
<span>Review the topics you found challenging and practice more questions.</span>
</div>
<div class="flex items-start gap-2">
<span class="text-green-500">⏰</span>
<span>Take your time to read each question carefully before answering.</span>
</div>
<?php elseif ($percentage < 80): ?>
<div class="flex items-start gap-2">
<span class="text-blue-500">🎯</span>
<span>Focus on accuracy - you're doing well, just need to be more precise.</span>
</div>
<div class="flex items-start gap-2">
<span class="text-purple-500">📖</span>
<span>Review the questions you got wrong to avoid similar mistakes.</span>
</div>
<?php else: ?>
<div class="flex items-start gap-2">
<span class="text-gold-500">🏆</span>
<span>Excellent work! Keep practicing to maintain this high standard.</span>
</div>
<div class="flex items-start gap-2">
<span class="text-blue-500">🚀</span>
<span>Try more challenging quizzes to further improve your skills.</span>
</div>
<?php endif; ?>
</div>
</div>
</div>

</div>
</div>

<script>
// Initialize theme on page load
const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
    document.documentElement.classList.remove('dark', 'light');
    document.documentElement.classList.add(savedTheme);
}

// Toggle detailed results
document.getElementById('toggle-details').addEventListener('click', function() {
    const details = document.getElementById('detailed-results');
    const button = this;
    
    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        button.textContent = 'Hide Details';
    } else {
        details.classList.add('hidden');
        button.textContent = 'Show Details';
    }
});

// Add stagger animation to elements
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.fade-in-up');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            el.style.transition = 'all 0.6s ease-out';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

</body>
</html>
