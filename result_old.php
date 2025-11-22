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
        
        // Compare first character of answer
        $is_correct = (strtolower($user[0] ?? '') === strtolower($correct[0] ?? ''));
    }
    
    // Get answer texts from options
    if (!empty($q['options']) && is_array($q['options'])) {
        // Get user answer text
        if (!empty($user) && strlen($user) > 0) {
            $user_index = ord(strtoupper($user[0])) - 65;
            if ($user_index >= 0 && $user_index < count($q['options']) && isset($q['options'][$user_index])) {
                $user_answer_text = $q['options'][$user_index];
            }
        }
        
        // Get correct answer text
        if (!empty($correct) && strlen($correct) > 0) {
            $correct_index = ord(strtoupper($correct[0])) - 65;
            if ($correct_index >= 0 && $correct_index < count($q['options']) && isset($q['options'][$correct_index])) {
                $correct_answer_text = $q['options'][$correct_index];
            }
        }
    }
    
    // Add 2 marks for each correct answer
    if ($is_correct) {
        $score += 2;
    }
    
    $results[] = [
        'question' => $q['question'],
        'user' => $user,
        'correct' => $correct,
        'options' => $q['options'],
        'is_correct' => $is_correct,
        'user_text' => $user_answer_text,
        'correct_text' => $correct_answer_text
    ];
}

// Save quiz results to database if user is logged in and not already saved
if (isset($_SESSION['user_id']) && !isset($_SESSION['quiz_saved'])) {
    try {
        $correct_answers = array_sum(array_column($results, 'is_correct'));
        $score_percentage = ($correct_answers / $total) * 100;
        $quiz_type = $_SESSION['exam_type'] ?? ($mock_mode ? 'mock_test' : 'general');
        
        // Calculate time taken with validation
        $time_taken = 0;
        if (isset($_SESSION['quiz_start_time']) && is_numeric($_SESSION['quiz_start_time'])) {
            $calculated_time = time() - $_SESSION['quiz_start_time'];
            // Validate time: should be positive and reasonable (max 4 hours)
            if ($calculated_time > 0 && $calculated_time <= 14400) {
                $time_taken = $calculated_time;
            } else {
                error_log('Invalid quiz time calculated: ' . $calculated_time . ' seconds. Using 0.');
                $time_taken = 0;
            }
        } else {
            error_log('Quiz start time not set or invalid. Using 0 for time_taken.');
        }
        
        // Prepare quiz attempt data
        $quiz_attempt_data = [
            'user_id' => $_SESSION['user_id'],
            'quiz_type' => $quiz_type,
            'quiz_title' => $quiz_title,
            'total_questions' => $total,
            'correct_answers' => $correct_answers,
            'score' => $score,
            'max_score' => $max_score,
            'accuracy' => round($score_percentage, 2),
            'time_taken' => $time_taken,
            'started_at' => isset($_SESSION['quiz_start_time']) ? date('Y-m-d H:i:s', $_SESSION['quiz_start_time']) : date('Y-m-d H:i:s'),
            'completed_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert quiz attempt using helper function
        $attempt_id = insertRecord('quiz_attempts', $quiz_attempt_data);
        
        if ($attempt_id) {
            $_SESSION['quiz_attempt_id'] = $attempt_id;
            
            // Save individual responses
            foreach ($questions as $i => $question) {
                $result = $results[$i];
                $user_answer = $result['user'];
                $correct_answer = $result['correct'];
                $is_correct = $result['is_correct'];
                
                $response_data = [
                    'attempt_id' => $attempt_id,
                    'question_number' => $i + 1,
                    'question_text' => substr($question['question'] ?? '', 0, 1000), // Limit length
                    'user_answer' => $user_answer,
                    'correct_answer' => $correct_answer,
                    'is_correct' => $is_correct ? 1 : 0,
                    'category' => $question['category']['name'] ?? null,
                    'subcategory' => $question['category']['subcategory'] ?? null
                ];
                
                insertRecord('quiz_responses', $response_data);
            }
            
            // Update user statistics, daily performance, and category performance
            $primary_category = !empty($questions) ? ($questions[0]['category']['name'] ?? 'General') : 'General';
            $stats_updated = updateAllStatistics($_SESSION['user_id'], $correct_answers, $total, $primary_category);
            
            if (!$stats_updated) {
                error_log('Warning: Failed to update some statistics for user ' . $_SESSION['user_id']);
            }
            
            // Mark as saved to prevent duplicate saves
            $_SESSION['quiz_saved'] = true;
            
            // Clean up quiz session variables for next quiz
            unset($_SESSION['quiz_start_time']);
            unset($_SESSION['current_quiz_id']);
            error_log('Quiz session variables cleaned up after saving results');
        }
        
    } catch (Exception $e) {
        error_log('Error saving quiz attempt in result.php: ' . $e->getMessage());
        // Continue to display results even if database save fails
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="favicon.svg" type="image/svg+xml">
    <title>Quiz Results</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&amp;display=swap" rel="stylesheet">
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
            --result-correct: #10b981;
            --result-incorrect: #ef4444;
            --result-neutral: #9ca3af;
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
            --result-correct: #10b981;
            --result-incorrect: #ef4444;
            --result-neutral: #6b7280;
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
            --result-correct: #34d399;
            --result-incorrect: #f87171;
            --result-neutral: #9ca3af;
        }
        body {
            font-family: "Manrope", sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .card {
            background: var(--card-bg);
            border-radius: 24px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--card-border);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .button_primary {
            @apply bg-[var(--primary-color)] text-[var(--button-primary-text)] rounded-full px-8 py-3 font-bold hover:opacity-80 transition-opacity duration-300 text-center;
        }
        .button_secondary {
            @apply bg-[var(--accent-color)] text-[var(--text-primary)] rounded-full px-8 py-3 font-bold hover:opacity-80 transition-opacity duration-300 text-center;
        }

        .result-item {
            @apply mb-6 p-4 rounded-xl transition-colors duration-200;
        }
        .result-item.correct {
            @apply bg-green-500/10 border border-green-500/20;
        }
        .result-item.wrong {
            @apply bg-red-500/10 border border-red-500/20;
        }
        .option {
            @apply block py-2 px-4 my-1 rounded-lg transition-colors duration-200 text-left;
        }
        .correct-answer {
            @apply bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800;
        }
        .user-wrong {
            @apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800;
        }
        .light .result-item.correct {
            @apply bg-green-50 border-green-200;
        }
        .light .result-item.wrong {
            @apply bg-red-50 border-red-200;
        }
    </style>
</head>
<body class="bg-background-color text-text-primary font-sans">
    <div class="relative flex size-full min-h-screen flex-col overflow-x-hidden p-6">
        <?php include 'includes/navbar.php'; ?>
        <?php include 'includes/mobile_navbar.php'; ?>
        
        <div class="mt-20"></div>

        <main class="flex-1 flex items-center justify-center">
            <div class="w-full max-w-md">
                <div class="card p-8 md:p-12 mb-8">
                    <h2 class="mb-2 text-4xl font-extrabold tracking-tight text-center">Quiz Completed!</h2>
                    <p class="text-text-secondary text-lg text-center mb-8">You've successfully completed the quiz. Here's how you did:</p>
                    
                    <div class="mb-8 flex flex-col items-center justify-center rounded-2xl bg-[var(--score-bg)] p-8 transition-colors duration-300">
                        <p class="text-text-secondary text-lg mb-2">Your Score</p>
                        <div class="text-5xl font-bold">
                            <span class="text-[var(--result-correct)]"><?= $score ?></span>
                            <span class="text-text-secondary">/<?= $max_score ?></span>
                        </div>
                        <p class="mt-2 text-text-secondary">
                            (<?= $max_score > 0 ? round(($score / $max_score) * 100) : 0 ?>%)
                        </p>
                        <?php
                            $percentage = $max_score > 0 ? ($score / $max_score) * 100 : 0;
                            if ($percentage >= 80) {
                                echo "Excellent work! ";
                            } elseif ($percentage >= 60) {
                                echo "Good job! Keep it up! ";
                            } else {
                                echo "Keep practicing! You'll get better! ";
                            }
                            ?>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <a href="quiz.php?<?= $mock_mode ? 'mock=1' : 'quiz=' . urlencode($quiz_id) ?>" class="button_primary w-full">
                            Try Again
                        </a>
                        <a href="index.php" class="block w-full text-center py-3 px-4 rounded-lg text-sm font-medium text-[var(--text-primary)] hover:bg-[var(--card-bg)] transition-colors">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <footer class="mx-auto w-full max-w-md pb-4">
            <div class="flex flex-col gap-4">
                <?php if ($mock_mode): ?>
                    <a href="quiz.php?mock=1" class="button_primary">Retry Mock Test</a>
                <?php else: ?>
                    <a href="quiz.php?quiz=<?= urlencode($quiz_id) ?>" class="button_primary">Retry Quiz</a>
                <?php endif; ?>
                <a href="index.php" class="button_secondary">Back to Home</a>
            </div>
        </footer>
    </div>

    <script>
        // Initialize theme on page load
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        }
    </script>
</body>
</html>