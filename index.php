<?php
require_once __DIR__ . '/includes/functions.php';
$quiz_files = get_quiz_files(__DIR__ . '/quizzes');

// Group quizzes by version
$grouped = [];
foreach ($quiz_files as $qf) {
    [$ver, $file] = explode('/', $qf, 2);
    $grouped[$ver][] = $qf;
}
$versions = array_keys($grouped);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="favicon.svg" type="image/svg+xml">
    <title>MPSC Quiz Portal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
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
            background-color: var(--background-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            min-height: 100vh;
        }
        .glass-card {
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }
        .typography_h1 {
            @apply text-4xl font-bold tracking-tight text-[var(--text-primary)];
        }
        .typography_h2 {
            @apply text-xl font-semibold text-[var(--text-primary)];
        }
        .typography_body {
            @apply text-base font-normal text-[var(--text-secondary)];
        }
        #theme-toggle:checked + .theme-toggle-label .theme-toggle-ball {
            transform: translateX(100%);
        }
        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }
        .animate-typing-h1 {
            overflow: hidden;
            white-space: nowrap;
            border-right: 0.1em solid transparent;
            animation: 
                typing 3.5s steps(40, end) infinite;
            animation-timing-function: cubic-bezier(0.65, 0.05, 0.36, 1);
        }
    </style>
</head>
<body class="bg-[var(--background-color)] text-[var(--text-primary)]">
    <div class="relative flex size-full min-h-screen flex-col justify-between overflow-x-hidden p-6 md:p-8">
        <header class="absolute top-6 right-6 md:top-8 md:right-8">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <input class="hidden" id="theme-toggle" type="checkbox"/>
                <label class="relative flex items-center cursor-pointer w-10 h-6 rounded-full p-1 bg-gray-300 dark:bg-gray-700 transition-colors" for="theme-toggle">
                    <div class="absolute w-4 h-4 rounded-full bg-white transition-transform duration-200 ease-in-out" style="transform: translateX(0)" id="toggle-ball"></div>
                </label>
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
        </header>
        <main class="flex flex-col items-center justify-center flex-grow text-center">
            <h1 class="typography_h1 mb-3 animate-typing-h1 inline-block">MPSC Quiz Portal</h1>
            <p class="typography_body mb-12 max-w-sm">Enhance your skills with our curated tests designed for aspiring MPSC candidates.</p>
            <div class="w-full max-w-sm space-y-5">
                <a class="glass-card block p-6 text-left hover:bg-[var(--card-bg)] transition-colors duration-300" href="typing.php">
                    <h2 class="typography_h2 mb-1.5 flex items-center">
                        <span class="material-icons mr-2">keyboard</span>
                        Typing Test
                    </h2>
                    <p class="typography_body">Assess your typing speed and accuracy with our comprehensive test.</p>
                </a>
                <a class="glass-card block p-6 text-left" href="quiz.php?mock=1">
                    <h2 class="typography_h2 mb-1.5 flex items-center">
                        <span class="material-icons mr-2">book</span>
                        Mock Test
                    </h2>
                    <p class="typography_body">Take a 50-question test with randomized questions from all categories.</p>
                </a>
            </div>
        </main>
        <footer class="text-center py-6">
            <p class="text-sm text-gray-400 dark:text-gray-500">Created by Pynshailang Nongsiej</p>
        </footer>
    </div>
    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const toggleBall = document.getElementById('toggle-ball');

        // Function to apply theme
        function applyTheme(isLight) {
            if (isLight) {
                html.classList.add('light');
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                toggleBall.style.transform = 'translateX(0)';
            } else {
                html.classList.add('dark');
                html.classList.remove('light');
                localStorage.setItem('theme', 'dark');
                toggleBall.style.transform = 'translateX(20px)';
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