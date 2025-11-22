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

// Check if user is logged in
$is_logged_in = isLoggedIn();
$user_name = null;
if ($is_logged_in) {
    $current_user = getCurrentUser();
    $user_name = $current_user ? ($current_user['full_name'] ?? $current_user['username'] ?? 'User') : null;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>MPSC Quiz - Master Your Knowledge</title>
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<link rel="shortcut icon" href="favicon.svg" type="image/svg+xml">
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
            --cursor-light: rgba(0, 0, 0, 0.15);
            --cursor-dark: rgba(255, 255, 255, 0.1);
        }
        html.light {
            --bg-color: var(--bg-light);
            --fg-color: var(--fg-light);
            --glass-bg: var(--glass-bg-light);
            --glass-border: var(--glass-border-light);
            --glass-border-cta: var(--glass-border-light);
            --subtle-text: #374151;
            --category-text: #1f2937;
            --cursor-color: var(--cursor-light);
            --header-glass-bg: rgba(255, 255, 255, 0.75);
            --header-glass-border: rgba(0, 0, 0, 0.08);
        }
        html.dark {
            --bg-color: var(--bg-dark);
            --fg-color: var(--fg-dark);
            --glass-bg: var(--glass-bg-dark);
            --glass-border: var(--glass-border-dark);
            --glass-border-cta: rgba(255, 255, 255, 0.3);
            --subtle-text: #d1d5db;
            --category-text: #e5e7eb;
            --cursor-color: var(--cursor-dark);
            --header-glass-bg: rgba(17, 17, 17, 0.75);
            --header-glass-border: rgba(255, 255, 255, 0.12);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 40;
        }
        @keyframes generative-flow {
            0% {
                transform: translateX(0) translateY(0) rotate(0deg) scale(1);
            }
            25% {
                transform: translateX(20px) translateY(-15px) rotate(45deg) scale(1.05);
            }
            50% {
                transform: translateX(-10px) translateY(25px) rotate(-30deg) scale(0.95);
            }
            75% {
                transform: translateX(15px) translateY(10px) rotate(60deg) scale(1.1);
            }
            100% {
                transform: translateX(0) translateY(0) rotate(0deg) scale(1);
            }
        }
        .generative-1 { animation: generative-flow 25s cubic-bezier(0.42, 0, 0.58, 1) infinite; }
        .generative-2 { animation: generative-flow 30s cubic-bezier(0.42, 0, 0.58, 1) infinite reverse; }
        .generative-3 { animation: generative-flow 22s cubic-bezier(0.42, 0, 0.58, 1) infinite; }
        .generative-4 { animation: generative-flow 35s cubic-bezier(0.42, 0, 0.58, 1) infinite reverse; }
        .generative-5 { animation: generative-flow 28s cubic-bezier(0.42, 0, 0.58, 1) infinite; }
        .generative-6 { animation: generative-flow 32s cubic-bezier(0.42, 0, 0.58, 1) infinite reverse; }
        .group\:hover\/cta .cta-bg { transform: translateZ(-20px) scale(0.95); }
        .group\:hover\/cta .cta-text { transform: translateZ(20px); }
        #cursor-glow {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--cursor-color) 0%, transparent 70%);
            pointer-events: none;
            transform: translate(-50%, -50%);
            transition: background 0.3s ease, transform 0.1s ease-out;
            z-index: 9999;
        }
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
      function toggleTheme() {
        document.documentElement.classList.toggle('dark');
        document.documentElement.classList.toggle('light');
        // Save theme preference
        const isDark = document.documentElement.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
      }
    </script>
</head>
<body class="font-display bg-[var(--bg-color)] text-[var(--fg-color)] transition-colors duration-500 light">
<div id="cursor-glow"></div>
<?php include 'includes/navbar.php'; ?>
<div class="relative flex min-h-screen w-full flex-col overflow-hidden">
<main class="relative flex flex-1 flex-col items-center justify-center text-center py-20 px-4 min-h-screen" id="parallax-container" style="perspective: 1000px;">
<div class="absolute inset-[-10%] z-0 flex items-center justify-center opacity-100 transition-opacity duration-500" style="transform-style: preserve-3d;">
<div class="parallax-element generative-1 absolute top-[10%] left-[15%] size-48 rounded-full border border-[var(--glass-border)] bg-[var(--glass-bg)] backdrop-blur-md" data-depth="0.3"></div>
<div class="parallax-element generative-2 absolute bottom-[15%] right-[10%] size-64 rounded-2xl border border-[var(--glass-border)] bg-[var(--glass-bg)] backdrop-blur-sm" data-depth="0.6"></div>
<div class="parallax-element generative-3 absolute top-[20%] right-[20%] size-32 rounded-full border border-[var(--glass-border)] bg-[var(--glass-bg)] backdrop-blur-lg shadow-2xl dark:shadow-white/5" data-depth="-0.5"></div>
<div class="parallax-element generative-4 absolute bottom-[25%] left-[5%] size-24 rounded-lg border border-[var(--glass-border)] bg-[var(--glass-bg)] backdrop-blur-sm" data-depth="0.2"></div>
<div class="parallax-element generative-5 absolute top-[50%] left-[40%] size-16 rounded-full border border-[var(--glass-border)] bg-[var(--glass-bg)] backdrop-blur-xl" data-depth="-0.2"></div>
<div class="parallax-element generative-6 absolute bottom-[5%] right-[35%] size-40 rounded-xl border border-[var(--glass-border)] bg-[var(--glass-bg)] backdrop-blur-sm" data-depth="0.4"></div>
</div>
<div class="z-10 flex flex-col items-center gap-12 w-full" style="transform-style: preserve-3d;">
<div class="flex flex-col gap-4 max-w-3xl" style="transform: translateZ(40px)">
<h1 class="text-6xl md:text-8xl font-bold leading-tight tracking-tighter">Master Your Quizzes Daily</h1>
<p class="text-[var(--subtle-text)] text-lg md:text-xl font-normal leading-relaxed tracking-wider max-w-2xl mx-auto">Engage with beautifully crafted quizzes designed for modern learning.</p>
</div>
<?php if (!$is_logged_in): ?>
<a class="group/cta relative flex min-w-[200px] cursor-pointer items-center justify-center overflow-hidden rounded-xl h-14 px-8 transition-transform duration-300 hover:scale-105" href="register.php" style="transform-style: preserve-3d;">
<div class="cta-bg absolute inset-0 bg-[var(--glass-bg)] backdrop-blur-2xl border border-[var(--glass-border-cta)] rounded-xl shadow-lg transition-transform duration-300 ease-in-out"></div>
<span class="cta-text truncate text-lg font-bold tracking-wider transition-transform duration-300 ease-in-out">Start Learning</span>
</a>
<?php endif; ?>
</div>
</main>
<section class="relative w-full py-20 px-4 sm:px-6 lg:px-8">
<div class="absolute inset-[-20%] z-0 overflow-hidden">
<div class="absolute -top-16 -right-16 size-72 rounded-full bg-[var(--glass-bg)] backdrop-blur-xl border border-[var(--glass-border)] opacity-100"></div>
<div class="absolute top-1/2 -left-24 size-80 rounded-full bg-[var(--glass-bg)] backdrop-blur-xl border border-[var(--glass-border)] opacity-100"></div>
</div>
<div class="relative z-10 max-w-7xl mx-auto flex flex-col items-center gap-12">
<div class="text-center">
<h2 class="text-4xl md:text-5xl font-bold tracking-tight">Main Quiz Categories</h2>
<p class="mt-4 max-w-2xl text-base text-[var(--category-text)]">Choose from our comprehensive quiz categories designed for MPSC preparation.</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 w-full">
<a href="quiz.php?category=mixed-english" class="flex flex-col p-8 bg-[var(--glass-bg)] backdrop-blur-2xl border border-[var(--glass-border)] rounded-xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
<div class="flex-grow">
<span class="material-symbols-outlined !text-4xl">language</span>
<h3 class="mt-6 text-xl font-bold">Mixed English</h3>
<p class="mt-2 text-sm text-[var(--category-text)]">Comprehensive English language test covering grammar, vocabulary, and comprehension.</p>
</div>
<div class="mt-6">
<span class="text-xs font-medium px-3 py-1 bg-[rgba(255,255,255,0.1)] dark:bg-[rgba(0,0,0,0.1)] rounded-full border border-[var(--glass-border)]">25 randomized questions</span>
</div>
</a>
<a href="quiz.php?category=mixed-gk" class="flex flex-col p-8 bg-[var(--glass-bg)] backdrop-blur-2xl border border-[var(--glass-border)] rounded-xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
<div class="flex-grow">
<span class="material-symbols-outlined !text-4xl">public</span>
<h3 class="mt-6 text-xl font-bold">Mixed GK</h3>
<p class="mt-2 text-sm text-[var(--category-text)]">General knowledge test covering current affairs, history, geography, and awareness.</p>
</div>
<div class="mt-6">
<span class="text-xs font-medium px-3 py-1 bg-[rgba(255,255,255,0.1)] dark:bg-[rgba(0,0,0,0.1)] rounded-full border border-[var(--glass-border)]">25 randomized questions</span>
</div>
</a>
<a href="quiz.php?category=mixed-aptitude" class="flex flex-col p-8 bg-[var(--glass-bg)] backdrop-blur-2xl border border-[var(--glass-border)] rounded-xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
<div class="flex-grow">
<span class="material-symbols-outlined !text-4xl">calculate</span>
<h3 class="mt-6 text-xl font-bold">Mixed Aptitude</h3>
<p class="mt-2 text-sm text-[var(--category-text)]">Logical reasoning, quantitative aptitude, and analytical thinking skills.</p>
</div>
<div class="mt-6">
<span class="text-xs font-medium px-3 py-1 bg-[rgba(255,255,255,0.1)] dark:bg-[rgba(0,0,0,0.1)] rounded-full border border-[var(--glass-border)]">25 randomized questions</span>
</div>
</a>
<a href="quiz.php?category=meghalaya-gk" class="flex flex-col p-8 bg-[var(--glass-bg)] backdrop-blur-2xl border border-[var(--glass-border)] rounded-xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
<div class="flex-grow">
<span class="material-symbols-outlined !text-4xl">location_on</span>
<h3 class="mt-6 text-xl font-bold">Meghalaya GK</h3>
<p class="mt-2 text-sm text-[var(--category-text)]">Dedicated section for Meghalaya-specific general knowledge and current affairs.</p>
</div>
<div class="mt-6">
<span class="text-xs font-medium px-3 py-1 bg-[rgba(255,255,255,0.1)] dark:bg-[rgba(0,0,0,0.1)] rounded-full border border-[var(--glass-border)]">25 randomized questions</span>
</div>
</a>
</div>
</div>
</section>
<footer class="text-center py-6">
<p class="text-sm text-[var(--subtle-text)]">Created by Pynshailang Nongsiej</p>
</footer>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(savedTheme);
        }
        
        const parallaxContainer = document.getElementById('parallax-container');
        if (parallaxContainer) {
            parallaxContainer.addEventListener('mousemove', function (e) {
                const elements = parallaxContainer.querySelectorAll('.parallax-element');
                const centerX = window.innerWidth / 2;
                const centerY = window.innerHeight / 2;
                const mouseX = e.clientX - centerX;
                const mouseY = e.clientY - centerY;
                elements.forEach(el => {
                    const depth = el.getAttribute('data-depth');
                    const moveX = -(mouseX * depth) / 100;
                    const moveY = -(mouseY * depth) / 100;
                    el.style.transform = `translateX(${moveX}px) translateY(${moveY}px)`;
                });
            });
        }
        const cursorGlow = document.getElementById('cursor-glow');
        if (cursorGlow) {
            document.addEventListener('mousemove', function(e) {
                cursorGlow.style.transform = `translate(${e.clientX - 250}px, ${e.clientY - 250}px)`;
            });
        }
    });
</script>
</div>
</body>
</html>
