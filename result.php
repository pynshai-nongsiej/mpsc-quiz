<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$mock_mode = isset($_SESSION['mock_mode']) && $_SESSION['mock_mode'];

if (!isset($_SESSION['quiz_file']) || !isset($_SESSION['answers'])) {
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
    $user = isset($user_answers[$i]) ? trim($user_answers[$i]) : '';
    
    // Handle different answer formats
    $is_correct = false;
    if (!empty($user)) {
        // Extract just the letter if it's in format 'a)' or 'a.'
        if (preg_match('/^([a-d])[\)\.]?/i', $user, $matches)) {
            $user = strtolower($matches[1]);
        }
        
        // Compare first character of answer
        $is_correct = (strtolower($user[0] ?? '') === strtolower($correct[0] ?? ''));
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
        'is_correct' => $is_correct
    ];
    $_SESSION['quiz_results']['results'][] = $results[count($results) - 1];
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
        #theme-toggle:checked + .theme-toggle-label .theme-toggle-ball {
            transform: translateX(1.25rem);
        }
        #theme-toggle:checked + .theme-toggle-label .dark-icon {
            opacity: 0;
        }
        #theme-toggle:not(:checked) + .theme-toggle-label .light-icon {
            opacity: 0;
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
        <header class="flex items-center justify-between">
            <a href="index.php" class="text-text-primary">
                <svg fill="currentColor" height="28" viewBox="0 0 256 256" width="28" xmlns="http://www.w3.org/2000/svg">
                    <path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path>
                </svg>
            </a>
            <h1 class="text-xl font-bold">Quiz Results</h1>
            <div class="w-7">
                <div class="flex items-center">
                    <input class="sr-only" id="theme-toggle" type="checkbox"/>
                    <label class="theme-toggle-label flex cursor-pointer items-center" for="theme-toggle">
                        <div class="relative">
                            <div class="h-6 w-12 rounded-full bg-[var(--accent-color)] shadow-inner"></div>
                            <div class="theme-toggle-ball absolute left-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-[var(--primary-color)] shadow transition-transform duration-300 ease-in-out">
                                <svg class="dark-icon h-3 w-3 text-[var(--background-color)] opacity-100 transition-opacity duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
                                </svg>
                                <svg class="light-icon absolute h-3 w-3 text-[var(--background-color)] opacity-0 transition-opacity duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M12 12a5 5 0 100-10 5 5 0 000 10z"></path>
                                </svg>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </header>

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
                        <a href="quiz_review.php" class="button_primary w-full">
                            Review Answers
                        </a>
                        <a href="quiz.php?<?= $mock_mode ? 'mock=1' : 'quiz=' . urlencode($quiz_id) ?>" class="button_secondary w-full">
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
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;

        // Function to apply theme
        function applyTheme(isLight) {
            if (isLight) {
                html.classList.add('light');
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                html.classList.remove('light');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Theme toggle event listener
        themeToggle.addEventListener('change', () => {
            applyTheme(themeToggle.checked);
        });

        // Set initial theme based on saved preference or system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        
        // Initialize with dark theme by default
        html.classList.add('dark');
        
        if (savedTheme === 'light' || (!savedTheme && prefersLight)) {
            themeToggle.checked = true;
            applyTheme(true);
        } else {
            themeToggle.checked = false;
            applyTheme(false);
        }

        // Listen for changes in system preference
        window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) { // Only if user hasn't set a preference
                themeToggle.checked = e.matches;
                applyTheme(e.matches);
            }
        });
    </script>
</body>
</html>