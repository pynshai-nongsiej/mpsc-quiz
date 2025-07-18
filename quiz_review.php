<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['quiz_results'])) {
    header('Location: index.php');
    exit;
}

$quiz_title = $_SESSION['quiz_title'] ?? 'Quiz Review';
$results = $_SESSION['quiz_results']['results'];
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
                $user_letter = $result['user'] ?? '';
                $correct_letter = $result['correct'] ?? '';
                $options = $result['options'] ?? [];
                
                // Get the full text of the answers
                $user_answer_text = $result['user_text'] ?? '';
                $correct_answer_text = '';
                
                // Find the correct answer text from options
                if (!empty($correct_letter) && !empty($options)) {
                    $correct_index = ord(strtoupper($correct_letter[0])) - 65;
                    if (isset($options[$correct_index])) {
                        $correct_answer_text = $options[$correct_index];
                    } else {
                        // Fallback: use the first option if index is invalid
                        $correct_answer_text = $options[0] ?? '';
                    }
                }
                
                // If user answer text is empty but we have a user letter, try to get the text
                if (empty($user_answer_text) && !empty($user_letter) && !empty($options)) {
                    $user_index = ord(strtoupper($user_letter[0])) - 65;
                    if (isset($options[$user_index])) {
                        $user_answer_text = $options[$user_index];
                    }
                }
            ?>
                <div class="glassmorphism rounded-2xl p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[var(--text-secondary)] text-sm">Question <?= $index + 1 ?> of <?= count($results) ?></p>
                            <p class="text-base font-medium mt-1"><?= htmlspecialchars($result['question'] ?? '') ?></p>
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
                    
                    <?php if ($is_correct): ?>
                        <div>
                            <p class="text-sm text-[var(--text-secondary)]">Your answer:</p>
                            <p class="text-base font-medium text-[var(--correct)]"><?= htmlspecialchars($user_answer_text) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php if (!empty($user_answer_text)): ?>
                                <div>
                                    <p class="text-sm text-[var(--text-secondary)]">Your answer:</p>
                                    <p class="text-base font-medium text-[var(--incorrect)] line-through"><?= htmlspecialchars($user_answer_text) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($correct_answer_text)): ?>
                                <div>
                                    <p class="text-sm text-[var(--text-secondary)]">Correct answer:</p>
                                    <p class="text-base font-medium text-[var(--correct)]"><?= htmlspecialchars($correct_answer_text) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($options)): ?>
                        <div class="mt-2">
                            <p class="text-sm text-[var(--text-secondary)] mb-1">Options:</p>
                            <div class="space-y-2">
                                <?php foreach ($options as $i => $option): 
                                    $is_user_answer = (strtoupper($user_letter[0] ?? '') === chr(65 + $i));
                                    $is_correct_answer = (strtoupper($correct_letter[0] ?? '') === chr(65 + $i));
                                    $bg_color = $is_correct_answer ? 'bg-[var(--correct)]/10' : ($is_user_answer ? 'bg-[var(--incorrect)]/10' : 'bg-[var(--glass-bg)]');
                                    $border_color = $is_correct_answer ? 'border-[var(--correct)]' : ($is_user_answer ? 'border-[var(--incorrect)]' : 'border-[var(--glass-border)]');
                                ?>
                                    <div class="p-2 rounded-lg border <?= $border_color ?> <?= $bg_color ?>">
                                        <p class="text-sm"><?= htmlspecialchars($option) ?></p>
                                    </div>
                                <?php endforeach; ?>
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
