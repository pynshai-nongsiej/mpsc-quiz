<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['quiz_file']) || !isset($_SESSION['answers'])) {
    header('Location: index.php');
    exit;
}

$mock_mode = isset($_SESSION['mock_mode']) && $_SESSION['mock_mode'];

if ($mock_mode) {
    $version = $_SESSION['mock_version'];
    $base_dir = __DIR__ . '/quizzes/' . $version;
    $quiz_files = [];
    foreach (glob($base_dir . '/*.txt') as $file) {
        if (basename($file) === 'metadata.txt') continue;
        $quiz_files[] = $file;
    }
    $questions = [];
    foreach ($quiz_files as $qf) {
        $questions = array_merge($questions, parse_quiz($qf));
    }
    $quiz_title = ucfirst(str_replace('_',' ',$version)) . ' Mock Test';
    $quiz_id = $version . '_mock';
} else {
    $quiz_file = $_SESSION['quiz_file'];
    $quiz_path = __DIR__ . '/quizzes/' . $quiz_file;
    if (!file_exists($quiz_path)) {
        die('Quiz not found.');
    }
    $questions = parse_quiz($quiz_path);
    $quiz_title = quiz_title_from_filename($quiz_file);
    $quiz_id = $quiz_file;
}

$user_answers = $_SESSION['answers'];
$score = 0;
$total = count($questions);
$results = [];

foreach ($questions as $i => $q) {
    $correct = $q['answer'];
    $user = $user_answers[$i] ?? '';
    $is_correct = ($user === $correct);
    if ($is_correct) $score++;
    $results[] = [
        'question' => $q['question'],
        'user' => $user,
        'correct' => $correct,
        'options' => $q['options'],
        'is_correct' => $is_correct
    ];
}
$score = 0;
$total = count($questions);
$results = [];

foreach ($questions as $i => $q) {
    $correct = $q['answer'];
    $user = $user_answers[$i] ?? '';
    $is_correct = ($user === $correct);
    if ($is_correct) $score++;
    $results[] = [
        'question' => $q['question'],
        'user' => $user,
        'correct' => $correct,
        'options' => $q['options'],
        'is_correct' => $is_correct
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Quiz Review</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        :root {
            --background-color-dark: #121212;
            --text-primary-dark: #ffffff;
            --text-secondary-dark: #a1a1aa;
            --glass-bg-dark: rgba(255, 255, 255, 0.05);
            --glass-border-dark: rgba(255, 255, 255, 0.1);
            --background-color-light: #f5f5f5;
            --text-primary-light: #18181b;
            --text-secondary-light: #71717a;
            --glass-bg-light: rgba(255, 255, 255, 0.5);
            --glass-border-light: rgba(255, 255, 255, 0.9);
            --correct: #34d399;
            --incorrect: #f87171;
        }
        .dark-mode {
            --background-color: var(--background-color-dark);
            --text-primary: var(--text-primary-dark);
            --text-secondary: var(--text-secondary-dark);
            --glass-bg: var(--glass-bg-dark);
            --glass-border: var(--glass-border-dark);
        }
        .light-mode {
            --background-color: var(--background-color-light);
            --text-primary: var(--text-primary-light);
            --text-secondary: var(--text-secondary-light);
            --glass-bg: var(--glass-bg-light);
            --glass-border: var(--glass-border-light);
        }
        body {
            font-family: "Manrope", sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
        }
        .glassmorphism {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            transition: background 0.3s, border 0.3s;
        }
        #theme-toggle:checked + label div:first-child {
            transform: translateX(100%);
        }
    </style>
    <style>
        body {
            min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col dark-mode" id="body">
    <div class="flex-grow">
        <header class="p-4 flex items-center justify-between sticky top-0 bg-[var(--background-color)]/80 backdrop-blur-sm z-10">
            <a href="result.php" class="text-[var(--text-primary)]">
                <svg fill="currentColor" height="24" viewBox="0 0 256 256" width="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M224,128a8,8,0,0,1-8,8H59.31l58.35,58.34a8,8,0,0,1-11.32,11.32l-72-72a8,8,0,0,1,0-11.32l72-72a8,8,0,0,1,11.32,11.32L59.31,120H216A8,8,0,0,1,224,128Z"></path>
                </svg>
            </a>
            <h1 class="text-xl font-bold text-center flex-1">Quiz Review</h1>
            <div class="w-12">
                <input class="hidden" id="theme-toggle" type="checkbox"/>
                <label class="relative inline-block w-12 h-6 cursor-pointer" for="theme-toggle">
                    <span class="absolute inset-0 rounded-full bg-zinc-700/50 dark:bg-zinc-200/50 transition-colors"></span>
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform duration-300 ease-in-out flex items-center justify-center">
                        <svg class="h-4 w-4 text-zinc-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707" id="sun-icon" style="display: none;"></path>
                            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" id="moon-icon"></path>
                        </svg>
                    </div>
                </label>
            </div>
        </header>

        <main class="px-4 py-6 space-y-6">
            <?php foreach ($results as $index => $result): 
                $is_correct = $result['is_correct'];
                $user_letter = '';
                $correct_letter = '';
                
                // Find user's answer letter
                foreach ($result['options'] as $i => $option) {
                    if ($option === $result['user']) {
                        $user_letter = chr(65 + $i);
                    }
                    if ($option === $result['correct']) {
                        $correct_letter = chr(65 + $i);
                    }
                }
            ?>
                <div class="glassmorphism rounded-2xl p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[var(--text-secondary)] text-sm">Question <?= $index + 1 ?> of <?= count($results) ?></p>
                            <p class="text-base font-medium mt-1"><?= htmlspecialchars($result['question']) ?></p>
                        </div>
                        <div class="flex items-center gap-2 text-[var(<?= $is_correct ? '--correct' : '--incorrect' ?>)]">
                            <?php if ($is_correct): ?>
                                <svg fill="currentColor" height="20" viewBox="0 0 256 256" width="20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M229.66,77.66l-128,128a8,8,0,0,1-11.32,0l-56-56a8,8,0,0,1,11.32-11.32L96,188.69,218.34,66.34a8,8,0,0,1,11.32,11.32Z"></path>
                                </svg>
                            <?php else: ?>
                                <svg fill="currentColor" height="20" viewBox="0 0 256 256" width="20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path>
                                </svg>
                            <?php endif; ?>
                            <span class="font-semibold text-sm"><?= $is_correct ? 'Correct' : 'Incorrect' ?></span>
                        </div>
                    </div>
                    
                    <?php 
                    // Get the full text of the user's answer
                    $user_answer_letter = $result['user'];
                    $correct_answer_letter = $result['correct'];
                    $options = $result['options'];
                    
                    // Initialize with the letters as fallback
                    $user_answer_text = $user_answer_letter;
                    $correct_answer_text = $correct_answer_letter;
                    
                    // Find the index of the answer (A=0, B=1, etc.)
                    $user_index = ord(strtoupper($user_answer_letter)) - 65; // A->0, B->1, etc.
                    $correct_index = ord(strtoupper($correct_answer_letter)) - 65;
                    
                    // Get the full text for user's answer if index is valid
                    if (isset($options[$user_index])) {
                        $user_answer_text = $options[$user_index];
                    }
                    
                    // Get the full text for correct answer if index is valid
                    if (isset($options[$correct_index])) {
                        $correct_answer_text = $options[$correct_index];
                    }
                    ?>
                    
                    <?php if ($is_correct): ?>
                        <div>
                            <p class="text-sm text-[var(--text-secondary)]">Your answer:</p>
                            <p class="text-base font-medium text-[var(--correct)]"><?= htmlspecialchars($user_answer_text) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <div>
                                <p class="text-sm text-[var(--text-secondary)]">Your answer:</p>
                                <p class="text-base font-medium text-[var(--incorrect)] line-through"><?= htmlspecialchars($user_answer_text) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-[var(--text-secondary)]">Correct answer:</p>
                                <p class="text-base font-medium text-[var(--correct)]"><?= htmlspecialchars($correct_answer_text) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </main>
    </div>
    
    <footer class="p-4 sticky bottom-0 bg-[var(--background-color)]/80 backdrop-blur-sm">
        <a href="index.php" class="block w-full h-12 px-5 rounded-full bg-[var(--text-primary)] text-[var(--background-color)] text-base font-bold flex items-center justify-center">
            Back to Home
        </a>
    </footer>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.getElementById('body');
        const sunIcon = document.getElementById('sun-icon');
        const moonIcon = document.getElementById('moon-icon');

        // Check for saved theme preference or use system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Apply theme on page load
        if (savedTheme === 'light' || (!savedTheme && !prefersDark)) {
            body.classList.remove('dark-mode');
            body.classList.add('light-mode');
            themeToggle.checked = false;
            if (sunIcon) sunIcon.style.display = 'none';
            if (moonIcon) moonIcon.style.display = 'block';
        } else {
            body.classList.remove('light-mode');
            body.classList.add('dark-mode');
            themeToggle.checked = true;
            if (sunIcon) sunIcon.style.display = 'block';
            if (moonIcon) moonIcon.style.display = 'none';
        }

        // Toggle theme
        themeToggle.addEventListener('change', () => {
            if (themeToggle.checked) {
                body.classList.remove('light-mode');
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                if (sunIcon) sunIcon.style.display = 'block';
                if (moonIcon) moonIcon.style.display = 'none';
            } else {
                body.classList.remove('dark-mode');
                body.classList.add('light-mode');
                localStorage.setItem('theme', 'light');
                if (sunIcon) sunIcon.style.display = 'none';
                if (moonIcon) moonIcon.style.display = 'block';
            }
        });
    </script>
</body>
</html>
