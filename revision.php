<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
$userId = getCurrentUserId();

// Get attempt ID from URL
$attemptId = $_GET['attempt_id'] ?? null;

if (!$attemptId || !is_numeric($attemptId)) {
    header('Location: quiz-history.php');
    exit;
}

// Fetch the original quiz attempt details
try {
    $originalAttempt = fetchOne("
        SELECT 
            qa.id,
            qa.quiz_type,
            qa.quiz_title,
            qa.total_questions,
            qa.completed_at
        FROM quiz_attempts qa
        WHERE qa.id = ? AND qa.user_id = ?
    ", [$attemptId, $userId]);
    
    if (!$originalAttempt) {
        header('Location: quiz-history.php');
        exit;
    }
    
    // Fetch all questions from the original attempt with complete options
    $originalQuestions = fetchAll("
        SELECT 
            qr.question_number,
            qr.question_text,
            qr.option_a,
            qr.option_b,
            qr.option_c,
            qr.option_d,
            qr.user_answer,
            qr.correct_answer,
            qr.is_correct,
            qr.category,
            qr.subcategory,
            qr.question_type
        FROM quiz_responses qr
        WHERE qr.attempt_id = ?
        ORDER BY qr.question_number
    ", [$attemptId]);
    
    if (empty($originalQuestions)) {
        header('Location: quiz-history.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log('Error fetching revision quiz data: ' . $e->getMessage());
    header('Location: quiz-history.php');
    exit;
}

// Convert the stored database questions back to quiz format
// This ensures we get the exact same questions with their original options
$questions = [];

foreach ($originalQuestions as $dbQuestion) {
    // Check if we have stored options (for newer quiz attempts)
    if ($dbQuestion['option_a'] || $dbQuestion['option_b'] || $dbQuestion['option_c'] || $dbQuestion['option_d']) {
        // Use the stored options
        $options = [];
        if ($dbQuestion['option_a']) $options['a'] = $dbQuestion['option_a'];
        if ($dbQuestion['option_b']) $options['b'] = $dbQuestion['option_b'];
        if ($dbQuestion['option_c']) $options['c'] = $dbQuestion['option_c'];
        if ($dbQuestion['option_d']) $options['d'] = $dbQuestion['option_d'];
        
        $questions[] = [
            'question' => $dbQuestion['question_text'],
            'options' => $options,
            'answer' => strtolower($dbQuestion['correct_answer']),
            'category' => $dbQuestion['category'] ?? 'General',
            'subcategory' => $dbQuestion['subcategory'] ?? null,
            'marks' => 2,
            'type' => $dbQuestion['question_type'] ?? 'multiple_choice'
        ];
        
        error_log('Using stored options for question: ' . substr($dbQuestion['question_text'], 0, 50));
    } else {
        // Fallback for older quiz attempts without stored options
        // Create generic options but keep the original question and correct answer
        $questions[] = [
            'question' => $dbQuestion['question_text'],
            'options' => [
                'a' => 'Option A',
                'b' => 'Option B',
                'c' => 'Option C',
                'd' => 'Option D'
            ],
            'answer' => strtolower($dbQuestion['correct_answer']),
            'category' => $dbQuestion['category'] ?? 'General',
            'subcategory' => $dbQuestion['subcategory'] ?? null,
            'marks' => 2,
            'type' => $dbQuestion['question_type'] ?? 'multiple_choice'
        ];
        
        error_log('Using fallback options for older question: ' . substr($dbQuestion['question_text'], 0, 50));
    }
}

error_log("Loaded " . count($questions) . " exact revision questions from database");

// Store questions in session for form submission
$_SESSION['questions'] = $questions;
$_SESSION['quiz_title'] = 'Revision: ' . $originalAttempt['quiz_title'];
$_SESSION['quiz_file'] = 'revision_' . $attemptId . '_' . time();
$_SESSION['revision_mode'] = true;
$_SESSION['original_attempt_id'] = $attemptId;

// Set quiz start time
$_SESSION['quiz_start_time'] = time();
$_SESSION['current_quiz_id'] = 'revision_' . $attemptId;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['answers'] = $_POST['answers'] ?? [];
    $_SESSION['revision_mode'] = true;
    
    // Save revision attempt to database if user is logged in
    if (isset($_SESSION['user_id'])) {
        try {
            // Calculate score
            $total_questions = count($questions);
            $correct_answers = 0;
            $user_answers = $_POST['answers'] ?? [];
            
            foreach ($questions as $i => $question) {
                $correct_answer = strtolower($question['answer'] ?? 'a');
                $user_answer = strtolower($user_answers[$i] ?? '');
                
                if ($user_answer === $correct_answer) {
                    $correct_answers++;
                }
            }
            
            $score_percentage = ($correct_answers / $total_questions) * 100;
            $quiz_type = 'revision_' . $originalAttempt['quiz_type'];
            
            // Calculate time taken
            $time_taken = 0;
            if (isset($_POST['hidden_timer_elapsed']) && is_numeric($_POST['hidden_timer_elapsed'])) {
                $hidden_timer_elapsed = (int)$_POST['hidden_timer_elapsed'];
                if ($hidden_timer_elapsed > 0 && $hidden_timer_elapsed <= 14400) {
                    $time_taken = $hidden_timer_elapsed;
                }
            }
            
            if ($time_taken === 0 && isset($_SESSION['quiz_start_time'])) {
                $calculated_time = time() - $_SESSION['quiz_start_time'];
                if ($calculated_time > 0 && $calculated_time <= 14400) {
                    $time_taken = $calculated_time;
                }
            }
            
            // Prepare quiz attempt data
            $quiz_attempt_data = [
                'user_id' => $_SESSION['user_id'],
                'quiz_type' => $quiz_type,
                'quiz_title' => $_SESSION['quiz_title'],
                'total_questions' => $total_questions,
                'correct_answers' => $correct_answers,
                'score' => $correct_answers * 2,
                'max_score' => $total_questions * 2,
                'accuracy' => round($score_percentage, 2),
                'time_taken' => $time_taken,
                'started_at' => isset($_SESSION['quiz_start_time']) ? date(DATE_FORMAT, $_SESSION['quiz_start_time']) : date(DATE_FORMAT),
                'completed_at' => date(DATE_FORMAT)
            ];
            
            // Insert quiz attempt
            $attempt_id = insertRecord('quiz_attempts', $quiz_attempt_data);
            
            if ($attempt_id) {
                $_SESSION['quiz_attempt_id'] = $attempt_id;
                
                // Save individual responses
                foreach ($questions as $i => $question) {
                    $user_answer = $user_answers[$i] ?? null;
                    $correct_answer = $question['answer'] ?? 'a';
                    $is_correct = strtolower($user_answer) === strtolower($correct_answer);
                    
                    $response_data = [
                        'attempt_id' => $attempt_id,
                        'question_number' => $i + 1,
                        'question_text' => substr($question['question'] ?? '', 0, 1000),
                        'user_answer' => $user_answer,
                        'correct_answer' => $correct_answer,
                        'is_correct' => $is_correct ? 1 : 0,
                        'category' => $question['category'] ?? null,
                        'subcategory' => $question['subcategory'] ?? null
                    ];
                    
                    insertRecord('quiz_responses', $response_data);
                }
                
                // Update user statistics
                $primary_category = !empty($questions) ? ($questions[0]['category'] ?? 'General') : 'General';
                updateAllStatistics($_SESSION['user_id'], $correct_answers, $total_questions, $primary_category);
                
                $_SESSION['quiz_saved'] = true;
            }
            
        } catch (Exception $e) {
            error_log('Error saving revision quiz attempt: ' . $e->getMessage());
        }
    }
    
    header('Location: result.php');
    exit;
}

$quiz_title = $_SESSION['quiz_title'] ?? 'Revision Quiz';
$query_string = '?revision=1&attempt_id=' . urlencode($attemptId);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($quiz_title) ?> - MPSC Quiz Portal</title>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0,
            'wght' 400,
            'GRAD' 0,
            'opsz' 24
        }
        .light .glassmorphic {
            background-color: rgba(255, 255, 255, 0.15);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        }
        .dark .glassmorphic {
            background-color: rgba(16, 16, 16, 0.15);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }
        .light .glassmorphic-darker {
            background-color: rgba(255, 255, 255, 0.05);
            -webkit-backdrop-filter: blur(30px);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .dark .glassmorphic-darker {
            background-color: rgba(0, 0, 0, 0.1);
            -webkit-backdrop-filter: blur(30px);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .parallax-shape {
            position: absolute;
            border-radius: 9999px;
            filter: blur(80px);
        }
        .light .parallax-shape {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .dark .parallax-shape {
            background-color: rgba(255, 255, 255, 0.03);
        }
        .light .option-radio:checked+.option-label {
            background-color: #000000;
            color: #ffffff;
            border-color: #000000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }
        .dark .option-radio:checked+.option-label {
            background-color: #ffffff;
            color: #000000;
            border-color: #ffffff;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
        }
        .light .hollow-button {
            background-color: transparent;
            border: 1px solid #000000;
            color: #000000;
            transition: all 200ms ease-in-out;
        }
        .light .hollow-button:hover {
            background-color: #000000;
            color: #ffffff;
        }
        .dark .hollow-button {
            border: 1px solid #ffffff;
            color: #ffffff;
        }
        .dark .hollow-button:hover {
            background-color: #ffffff;
            color: #000000;
        }
        .light .primary-button {
            background-color: #000000;
            color: #ffffff;
            transition: all 200ms ease-in-out;
        }
        .light .primary-button:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .dark .primary-button {
            background-color: #ffffff;
            color: #000000;
        }
        .dark .primary-button:hover {
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.15);
        }
        .primary-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        .hollow-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .quiz-option {
            transition: all 200ms ease-in-out;
        }
        .quiz-option:hover:not(.disabled) {
            border-color: rgba(0, 0, 0, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .dark .quiz-option:hover:not(.disabled) {
            border-color: rgba(255, 255, 255, 0.3);
        }
        .quiz-option.selected {
            background-color: #000000;
            color: #ffffff;
            border-color: #000000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }
        .dark .quiz-option.selected {
            background-color: #ffffff;
            color: #000000;
            border-color: #ffffff;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
        }
        .quiz-option.disabled {
            cursor: not-allowed;
            pointer-events: none;
        }
        /* Responsive question text sizing */
        .question-text {
            font-size: clamp(1.1rem, 3.5vw, 2rem);
            line-height: 1.4;
        }
        .question-text.long-question {
            font-size: clamp(0.95rem, 2.8vw, 1.4rem);
            line-height: 1.3;
        }
        .question-text.very-long-question {
            font-size: clamp(0.85rem, 2.2vw, 1.2rem);
            line-height: 1.2;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#000000",
                        "background-light": "#ffffff",
                        "background-dark": "#000000",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
                        "full": "9999px"
                    },
                },
            },
        }
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            document.documentElement.classList.toggle('light');
        }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-primary dark:text-background-light transition-colors duration-300">
<div class="relative flex h-screen min-h-[700px] w-full flex-col items-center justify-center overflow-hidden p-4 sm:p-6 md:p-8">
    <!-- Parallax Background Shapes -->
    <div class="parallax-shape -top-1/4 -left-1/4 h-1/2 w-1/2" data-alt="Abstract blurred circular shape, monochrome, low opacity"></div>
    <div class="parallax-shape -bottom-1/4 -right-1/4 h-2/3 w-2/3" data-alt="Abstract blurred circular shape, monochrome, low opacity"></div>
    <div class="parallax-shape top-1/2 -right-1/3 h-1/2 w-1/2" data-alt="Abstract blurred circular shape, monochrome, low opacity"></div>
    
    <div class="relative z-10 flex h-full w-full max-w-4xl flex-col">
        <form method="post" action="revision.php<?= $query_string ?>" class="flex h-full flex-col">
            <input type="hidden" name="hidden_timer_elapsed" id="hidden_timer_elapsed" value="0">

            <!-- Header with Progress and Timer -->
            <header class="w-full pb-8 pt-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-1 flex-col gap-3">
                        <p class="text-sm font-medium leading-normal text-black/80 dark:text-white/80">Question <span id="current-question">1</span> of <?= count($questions) ?></p>
                        <div class="h-1.5 w-full rounded-full glassmorphic-darker">
                            <div class="h-1.5 rounded-full bg-black dark:bg-white" id="progress-bar" style="width: 0%;"></div>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center justify-center gap-4">
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full border border-black/20 dark:border-white/20">
                            <svg class="absolute inset-0 h-full w-full -rotate-90 transform" fill="none" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                                <circle class="text-black/10 dark:text-white/10" cx="18" cy="18" r="16" stroke="currentColor" stroke-width="2"></circle>
                                <circle class="text-black dark:text-white" cx="18" cy="18" r="16" stroke="currentColor" stroke-dasharray="100" stroke-dashoffset="53" stroke-linecap="round" stroke-width="2" id="timer-circle"></circle>
                            </svg>
                            <span class="text-lg font-bold text-black dark:text-white" id="timer-display">--</span>
                        </div>
                        <button type="button" aria-label="Toggle theme" class="flex h-12 w-12 items-center justify-center rounded-full glassmorphic transition-transform duration-200 hover:scale-110" onclick="toggleTheme()">
                            <span class="material-symbols-outlined text-black dark:hidden">dark_mode</span>
                            <span class="material-symbols-outlined hidden text-white dark:inline">light_mode</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Quiz Content -->
            <main class="flex flex-1 flex-col items-center justify-center gap-12">
                <?php foreach ($questions as $i => $q): ?>
                    <div class="question-container w-full <?= $i === 0 ? 'active' : 'hidden' ?>" data-question="<?= $i ?>" data-correct-answer="<?= strtolower($q['answer'] ?? 'a') ?>">
                        <!-- Question Card -->
                        <div class="w-full max-w-3xl rounded-xl glassmorphic p-8 md:p-12 mx-auto">
                            <?php 
                            // Get the category from the question data
                            $category_name = $q['category'] ?? 'General';
                            
                            // Get the category metadata for this category
                            $category_meta = get_category_meta($category_name);
                            
                            // Set default values if not found
                            $bg_color = $category_meta['bg_color'] ?? 'bg-gray-100';
                            $text_color = $category_meta['text_color'] ?? 'text-gray-800';
                            $icon = $category_meta['icon'] ?? '📚';
                            ?>
                            <?php if ($category_name !== 'General'): ?>
                            <div class="mb-6">
                                <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full <?= $bg_color ?> <?= $text_color ?> space-x-2">
                                    <span class="text-base"><?= $icon ?></span>
                                    <span><?= htmlspecialchars($category_name) ?></span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <p class="question-text text-center font-bold leading-relaxed tracking-tight text-black dark:text-white" data-question-length="<?= strlen($q['question']) ?>">
                                <?= htmlspecialchars($q['question']) ?>
                            </p>
                            <p class="mt-4 text-center text-base font-normal leading-normal text-black/60 dark:text-white/60">Select the correct option below.</p>
                        </div>
                        <!-- Options -->
                        <div class="w-full max-w-2xl mx-auto mt-8">
                            <fieldset class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <legend class="sr-only">Quiz Options</legend>
                                <?php 
                                // Use the actual question options
                                foreach ($q['options'] as $opt_letter => $opt_text): 
                                    $display_letter = strtoupper($opt_letter);
                                ?>
                                    <div>
                                        <input class="option-radio peer hidden" id="option<?= $i ?>_<?= $opt_letter ?>" name="answers[<?= $i ?>]" type="radio" value="<?= $opt_letter ?>" required/>
                                        <label class="quiz-option option-label group flex cursor-pointer items-center justify-center rounded-lg border border-solid border-black/10 bg-white/50 p-4 text-center text-black transition-all duration-200 ease-in-out hover:border-black/30 hover:shadow-lg dark:border-white/10 dark:bg-black/10 dark:text-white dark:hover:border-white/30" for="option<?= $i ?>_<?= $opt_letter ?>" data-question="<?= $i ?>" data-option="<?= $opt_letter ?>">
                                            <span class="text-base font-medium leading-normal">
                                                <?= $display_letter ?>) <?= htmlspecialchars($opt_text) ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </fieldset>
                        </div>
                    </div>
                <?php endforeach; ?>
            </main>
            
            <!-- Footer with Navigation -->
            <footer class="sticky bottom-0 z-20 w-full py-4 sm:py-6">
                <div class="mx-auto w-full max-w-3xl rounded-xl glassmorphic p-2">
                    <div class="flex items-center justify-between gap-2 sm:gap-4">
                        <button type="button" id="back-button" class="flex h-12 min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg px-5 text-base font-bold leading-normal tracking-wide hollow-button" style="display: none;">
                            <span class="truncate">Back</span>
                        </button>
                        <button type="button" id="next-button" class="flex h-12 flex-1 min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg px-5 text-base font-bold leading-normal tracking-wide primary-button" disabled>
                            <span class="truncate">Next Question</span>
                        </button>
                        <button type="button" id="quit-button" class="flex h-12 min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg px-5 text-base font-bold leading-normal tracking-wide hollow-button" onclick="if(confirm('Are you sure you want to quit the quiz?')) { window.location.href='quiz-history.php'; }">
                            <span class="truncate">Quit</span>
                        </button>
                    </div>
                </div>
            </footer>
        </form>
    </div>
</div>

    <script>
        // Hidden Timer Implementation - starts immediately when quiz loads
        let quizStartTime = Date.now();
        let hiddenTimer = {
            startTime: quizStartTime,
            getElapsedTime: function() {
                return Math.floor((Date.now() - this.startTime) / 1000); // Return elapsed time in seconds
            },
            reset: function() {
                this.startTime = Date.now();
            }
        };
        
        // Log timer start for debugging
        console.log('Hidden timer started at:', new Date(quizStartTime).toISOString());
        
        // Handle page visibility changes to account for user navigating away
        let timeAwayStart = null;
        let totalTimeAway = 0;
        
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // User navigated away or minimized tab
                timeAwayStart = Date.now();
            } else {
                // User returned to tab
                if (timeAwayStart) {
                    totalTimeAway += Date.now() - timeAwayStart;
                    timeAwayStart = null;
                }
            }
        });
        
        // Function to get accurate elapsed time (excluding time away)
        function getAccurateElapsedTime() {
            let totalElapsed = hiddenTimer.getElapsedTime();
            let timeAwaySeconds = Math.floor(totalTimeAway / 1000);
            
            // If user is currently away, add current away time
            if (timeAwayStart) {
                timeAwaySeconds += Math.floor((Date.now() - timeAwayStart) / 1000);
            }
            
            // Return total time minus time away (but ensure it's not negative)
            return Math.max(0, totalElapsed - timeAwaySeconds);
        }

        // Timer display functionality
        let timerStartTime = Date.now();
        let timerInterval;
        
        function updateTimerDisplay() {
            const elapsed = Math.floor((Date.now() - timerStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            const timerDisplay = document.getElementById('timer-display');
            const timerCircle = document.getElementById('timer-circle');
            
            if (timerDisplay) {
                if (elapsed < 60) {
                    timerDisplay.textContent = seconds.toString().padStart(2, '0');
                } else {
                    timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }
            
            // Update circular progress (optional - can be used for time limits)
            if (timerCircle) {
                // For now, just keep it as a visual element
                // You can add time limit functionality here if needed
            }
        }
        
        // Start timer display
        timerInterval = setInterval(updateTimerDisplay, 1000);
        updateTimerDisplay(); // Initial call
        
        // Define applyTheme function
        function applyTheme(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }

        // Initialize theme on page load
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            applyTheme(true);
        } else {
            // Default to light theme
            applyTheme(false);
        } 

        // Quiz functionality
        console.log('Initializing new glassmorphism quiz interface...');
        
        const questionContainers = document.querySelectorAll('.question-container');
        const nextButton = document.getElementById('next-button');
        const backButton = document.getElementById('back-button');
        const progressBar = document.getElementById('progress-bar');
        const currentQuestionSpan = document.getElementById('current-question');
        let currentQuestion = 0;
        let answerLocked = false;
        let selectedAnswers = {};

        console.log('Found elements:', {
            questionContainers: questionContainers.length,
            nextButton: !!nextButton,
            backButton: !!backButton,
            progressBar: !!progressBar,
            currentQuestionSpan: !!currentQuestionSpan
        });

        // Initialize progress and navigation
        updateProgress();
        updateNavigationButtons();

        // Option selection handling - listen to radio input changes
        document.querySelectorAll('input[type="radio"][name^="answers"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const questionIndex = parseInt(this.name.match(/\[(\d+)\]/)[1]);
                    const optionValue = this.value;
                    
                    // Clear all visual selections for this question
                    document.querySelectorAll(`.quiz-option[data-question="${questionIndex}"]`).forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Add selected class to the corresponding label
                    const label = document.querySelector(`label[for="${this.id}"]`);
                    if (label) {
                        label.classList.add('selected');
                    }
                    
                    // Store the answer
                    selectedAnswers[questionIndex] = optionValue;
                    
                    // Update navigation buttons
                    updateNavigationButtons();
                    
                    console.log('Answer selected:', questionIndex, optionValue);
                }
            });
        });
        
        // Navigation button handlers
        if (nextButton) {
            nextButton.addEventListener('click', function() {
                // Don't proceed if button is disabled
                if (this.disabled) return;
                
                if (currentQuestion < questionContainers.length - 1) {
                    currentQuestion++;
                    showQuestion(currentQuestion);
                    updateProgress();
                    updateNavigationButtons();
                } else {
                    // Submit form on last question
                    const form = document.querySelector('form');
                    if (form) form.submit();
                }
            });
        }
        
        if (backButton) {
            backButton.addEventListener('click', function() {
                if (currentQuestion > 0) {
                    currentQuestion--;
                    showQuestion(currentQuestion);
                    updateProgress();
                    updateNavigationButtons();
                }
            });
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && currentQuestion > 0) {
                backButton.click();
            } else if (e.key === 'ArrowRight' && selectedAnswers[currentQuestion] && !nextButton.disabled) {
                nextButton.click();
            } else if (e.key >= '1' && e.key <= '4') {
                // Select option by number key
                const optionIndex = parseInt(e.key) - 1;
                const options = document.querySelectorAll(`.quiz-option[data-question="${currentQuestion}"]`);
                if (options[optionIndex]) {
                    options[optionIndex].click();
                }
            }
        });

        function showQuestion(index) {
            questionContainers.forEach((container, i) => {
                if (i === index) {
                    container.classList.remove('hidden');
                    container.classList.add('active');
                } else {
                    container.classList.add('hidden');
                    container.classList.remove('active');
                }
            });
            
            // Apply responsive font sizing based on question length
            applyQuestionSizing(index);
            
            // Restore selected state for the current question
            restoreSelectedState(index);
        }
        
        function applyQuestionSizing(questionIndex) {
            const questionText = document.querySelector(`.question-container[data-question="${questionIndex}"] .question-text`);
            if (questionText) {
                const length = parseInt(questionText.dataset.questionLength) || 0;
                
                // Remove existing length classes
                questionText.classList.remove('long-question', 'very-long-question');
                
                // Apply appropriate class based on length (more aggressive thresholds)
                if (length > 120) {
                    questionText.classList.add('very-long-question');
                } else if (length > 60) {
                    questionText.classList.add('long-question');
                }
                
                console.log(`Question ${questionIndex} length: ${length}, applied class:`, 
                    length > 120 ? 'very-long-question' : length > 60 ? 'long-question' : 'normal');
            }
        }
        
        function restoreSelectedState(questionIndex) {
            // Clear all visual selections for this question first
            document.querySelectorAll(`.quiz-option[data-question="${questionIndex}"]`).forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // If there's a saved answer, restore the visual state
            if (selectedAnswers[questionIndex] !== undefined) {
                const savedValue = selectedAnswers[questionIndex];
                const radio = document.querySelector(`input[name="answers[${questionIndex}]"][value="${savedValue}"]`);
                if (radio) {
                    radio.checked = true;
                    const label = document.querySelector(`label[for="${radio.id}"]`);
                    if (label) {
                        label.classList.add('selected');
                    }
                }
            }
        }

        function updateProgress() {
            const progress = ((currentQuestion + 1) / questionContainers.length) * 100;
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }
            if (currentQuestionSpan) {
                currentQuestionSpan.textContent = currentQuestion + 1;
            }
        }
        
        function updateNavigationButtons() {
            const hasAnswer = selectedAnswers[currentQuestion] !== undefined;
            const isFirstQuestion = currentQuestion === 0;
            const isLastQuestion = currentQuestion === questionContainers.length - 1;
            
            // Show/hide back button
            if (backButton) {
                backButton.style.display = isFirstQuestion ? 'none' : 'flex';
            }
            
            // Always show next button, but enable/disable based on answer
            if (nextButton) {
                nextButton.style.display = 'flex';
                nextButton.disabled = !hasAnswer;
                nextButton.style.opacity = hasAnswer ? '1' : '0.5';
                nextButton.style.cursor = hasAnswer ? 'pointer' : 'not-allowed';
                
                const nextButtonText = nextButton.querySelector('span');
                if (nextButtonText) {
                    nextButtonText.textContent = isLastQuestion ? 'Submit Quiz' : 'Next Question';
                }
            }
        }
        
        // Auto-save answers to hidden timer field
        function updateHiddenTimer() {
            const hiddenTimerField = document.getElementById('hidden_timer_elapsed');
            if (hiddenTimerField) {
                hiddenTimerField.value = getAccurateElapsedTime();
            }
        }
        
        // Update hidden timer every 5 seconds
        setInterval(updateHiddenTimer, 5000);
        
        // Update on form submission
        document.querySelector('form').addEventListener('submit', updateHiddenTimer);
        
        // Initialize the interface
        showQuestion(0);
        
        // Apply sizing to all questions on load
        for (let i = 0; i < questionContainers.length; i++) {
            applyQuestionSizing(i);
        }
        
        console.log('Glassmorphism quiz interface initialized successfully');
    </script>

</body>
</html>
