<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$quiz_files = get_quiz_files(__DIR__ . '/quizzes');

// Group quizzes by version
$grouped = [];
foreach ($quiz_files as $qf) {
    [$ver, $file] = explode('/', $qf, 2);
    $grouped[$ver][] = $qf;
}
$versions = array_keys($grouped);

// No need for subcategories in the simplified structure

// Check if user is logged in
$is_logged_in = isLoggedIn();
$user_name = null;
if ($is_logged_in) {
    $current_user = getCurrentUser();
    $user_name = $current_user ? ($current_user['full_name'] ?? $current_user['username'] ?? 'User') : null;
}
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
            @apply text-3xl md:text-4xl font-bold tracking-tight text-[var(--text-primary)];
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
            max-width: 100%;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 640px) {
            .animate-typing-h1 {
                font-size: 1.875rem; /* 30px */
                line-height: 2.25rem; /* 36px */
            }
            
            .typography_body {
                @apply text-sm;
            }
        }
    </style>
</head>
<body class="bg-[var(--background-color)] text-[var(--text-primary)]">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <?php include __DIR__ . '/includes/mobile_navbar.php'; ?>
    
    <div class="relative flex size-full min-h-screen flex-col justify-between overflow-x-hidden p-6 md:p-8 pt-20">
        
        <main class="flex flex-col items-center justify-center flex-grow text-center">
            <h1 class="typography_h1 mb-3 animate-typing-h1 inline-block">MPSC Quiz Portal</h1>
            <p class="typography_body mb-12 max-w-sm">Enhance your skills with our curated tests designed for aspiring MPSC candidates.</p>
            <div class="w-full max-w-4xl space-y-8">
                <!-- Main Quiz Categories Section -->
                <div class="glass-card p-6">
                    <h2 class="typography_h2 mb-4 flex items-center text-indigo-600 dark:text-indigo-400">
                        <span class="material-icons mr-2">quiz</span>
                        Main Quiz Categories
                    </h2>
                    <p class="typography_body mb-6">Choose from our comprehensive quiz categories designed for MPSC preparation.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Mixed English Test -->
                        <a class="glass-card block p-6 text-left hover:bg-[var(--card-bg)] transition-all duration-300 border-l-4 border-blue-500 hover:shadow-lg" 
                           href="quiz.php?category=mixed-english">
                            <div class="flex items-center mb-3">
                                <span class="material-icons text-blue-500 mr-3 text-2xl">language</span>
                                <h3 class="font-semibold text-lg text-[var(--text-primary)]">Mixed English</h3>
                            </div>
                            <p class="text-sm text-[var(--text-secondary)] mb-2">Comprehensive English language test covering grammar, vocabulary, and comprehension</p>
                            <p class="text-xs text-blue-500 font-medium">20 randomized questions</p>
                        </a>

                        <!-- Mixed GK Test -->
                        <a class="glass-card block p-6 text-left hover:bg-[var(--card-bg)] transition-all duration-300 border-l-4 border-green-500 hover:shadow-lg" 
                           href="quiz.php?category=mixed-gk">
                            <div class="flex items-center mb-3">
                                <span class="material-icons text-green-500 mr-3 text-2xl">public</span>
                                <h3 class="font-semibold text-lg text-[var(--text-primary)]">Mixed GK</h3>
                            </div>
                            <p class="text-sm text-[var(--text-secondary)] mb-2">General knowledge test covering current affairs, history, geography, and awareness</p>
                            <p class="text-xs text-green-500 font-medium">20 randomized questions</p>
                        </a>

                        <!-- Mixed Aptitude Test -->
                        <a class="glass-card block p-6 text-left hover:bg-[var(--card-bg)] transition-all duration-300 border-l-4 border-purple-500 hover:shadow-lg" 
                           href="quiz.php?category=mixed-aptitude">
                            <div class="flex items-center mb-3">
                                <span class="material-icons text-purple-500 mr-3 text-2xl">calculate</span>
                                <h3 class="font-semibold text-lg text-[var(--text-primary)]">Mixed Aptitude</h3>
                            </div>
                            <p class="text-sm text-[var(--text-secondary)] mb-2">Logical reasoning, quantitative aptitude, and analytical thinking skills</p>
                            <p class="text-xs text-purple-500 font-medium">20 randomized questions</p>
                        </a>

                        <!-- Meghalaya GK Test -->
                        <a class="glass-card block p-6 text-left hover:bg-[var(--card-bg)] transition-all duration-300 border-l-4 border-orange-500 hover:shadow-lg" 
                           href="quiz.php?category=meghalaya-gk">
                            <div class="flex items-center mb-3">
                                <span class="material-icons text-orange-500 mr-3 text-2xl">location_on</span>
                                <h3 class="font-semibold text-lg text-[var(--text-primary)]">Meghalaya GK</h3>
                            </div>
                            <p class="text-sm text-[var(--text-secondary)] mb-2">Dedicated section for Meghalaya-specific general knowledge and current affairs</p>
                            <p class="text-xs text-orange-500 font-medium">Comprehensive Meghalaya questions</p>
                        </a>
                    </div>
                </div>

                <!-- Legacy Tests Section -->
                <div class="glass-card p-6 bg-gray-50 dark:bg-gray-800/50">
                    <h2 class="typography_h2 mb-4 flex items-center text-gray-600 dark:text-gray-400">
                        <span class="material-icons mr-2">assignment</span>
                        MPSC Specific Tests
                    </h2>
                    <p class="typography_body mb-4">Practice with exam-specific mock tests for MPSC positions.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a class="glass-card block p-4 text-left hover:bg-[var(--card-bg)] transition-colors duration-300" 
                           href="quiz.php?exam=mpsc_lda">
                            <h3 class="font-semibold mb-1 text-[var(--text-primary)]">MPSC LDA Mock Test</h3>
                            <p class="text-sm text-[var(--text-secondary)]">Full-length Lower Division Assistant exam</p>
                        </a>
                        <a class="glass-card block p-4 text-left hover:bg-[var(--card-bg)] transition-colors duration-300" 
                           href="quiz.php?exam=dsc_lda">
                            <h3 class="font-semibold mb-1 text-[var(--text-primary)]">DSC LDA Mock Test</h3>
                            <p class="text-sm text-[var(--text-secondary)]">District Selection Committee LDA exam</p>
                        </a>
                        <a class="glass-card block p-4 text-left hover:bg-[var(--card-bg)] transition-colors duration-300" 
                           href="quiz.php?exam=mpsc_typist">
                            <h3 class="font-semibold mb-1 text-[var(--text-primary)]">MPSC Typist Test</h3>
                            <p class="text-sm text-[var(--text-secondary)]">Specialized test for typist positions</p>
                        </a>
                        <a class="glass-card block p-4 text-left hover:bg-[var(--card-bg)] transition-colors duration-300" 
                           href="quiz.php?mock=1">
                            <h3 class="font-semibold mb-1 text-[var(--text-primary)]">General Mock Test</h3>
                            <p class="text-sm text-[var(--text-secondary)]">50-question comprehensive test</p>
                        </a>
                    </div>
                </div>
            </div>
        </main>
        <footer class="text-center py-6">
            <p class="text-sm text-gray-400 dark:text-gray-500">Created by Pynshailang Nongsiej</p>
        </footer>
    </div>
    <script>
        // Debug script
        console.log('Index page loaded');
        
        // Add click handler to mock test link
        document.addEventListener('DOMContentLoaded', function() {
            const mockTestLink = document.querySelector('a[href*="quiz.php?mock=1"]');
            if (mockTestLink) {
                console.log('Mock test link found');
                mockTestLink.addEventListener('click', function(e) {
                    console.log('Mock test link clicked');
                    // Let the default action proceed
                });
            } else {
                console.error('Mock test link not found!');
            }
            
            // User dropdown functionality is now handled by navbar.php
        });

        // Theme toggle functionality is now handled by navbar.php
    </script>
</body>
</html>