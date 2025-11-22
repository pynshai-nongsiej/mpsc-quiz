<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

// Session is already started in db_config.php

// Clear quiz_saved flag when starting a new quiz to prevent duplicate saves
if (isset($_SESSION['quiz_saved'])) {
    unset($_SESSION['quiz_saved']);
}

$mock_mode = isset($_GET['mock']) && ($_GET['mock'] === 'true' || $_GET['mock'] === '1');
$exam_type = $_GET['exam'] ?? '';
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';

if ($mock_mode || $exam_type || $category) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['questions'])) {
        // On form submission, use the previously stored questions and titles
        $questions   = $_SESSION['questions'];
        $quiz_title  = $_SESSION['quiz_title'] ?? 'Mock Test';
        $quiz_id     = $_SESSION['quiz_file'] ?? 'mock_test';
    } else {
        // Get exam configuration based on category
        if ($category) {
            switch ($category) {
                case 'mixed-english':
                    $exam_config = [
                        'name' => 'Mixed English Test',
                        'categories' => [
                            'General English' => [
                                'count' => 20,
                                'testqna_category' => 'general-english',
                                'subcategory' => null
                            ]
                        ],
                        'total_questions' => 20
                    ];
                    break;
                case 'mixed-gk':
                    $exam_config = [
                        'name' => 'Mixed General Knowledge Test',
                        'categories' => [
                            'General Knowledge' => [
                                'count' => 20,
                                'testqna_category' => 'general-knowledge',
                                'subcategory' => null
                            ]
                        ],
                        'total_questions' => 20
                    ];
                    break;
                case 'mixed-aptitude':
                    $exam_config = [
                        'name' => 'Mixed Aptitude Test',
                        'categories' => [
                            'General Aptitude' => [
                                'count' => 20,
                                'testqna_category' => 'aptitude',
                                'subcategory' => null
                            ]
                        ],
                        'total_questions' => 20
                    ];
                    break;
                case 'meghalaya-gk':
                    $exam_config = [
                        'name' => 'Meghalaya General Knowledge Test',
                        'categories' => [
                            'Meghalaya GK' => [
                                'count' => 20,
                                'testqna_category' => 'general-knowledge',
                                'subcategory' => 'meghalaya'
                            ]
                        ],
                        'total_questions' => 20
                    ];
                    break;
                default:
                    die('Invalid category specified.');
            }
        } else {
            // Original exam configuration
            $exam_config = $exam_type ? get_exam_config($exam_type) : [
                'name' => 'General Mock Test',
                'categories' => [
                    'All Categories' => [
                        'count' => 50,
                        'subcategories' => 'all'
                    ]
                ]
            ];
        }
        
        // Use appropriate loading function based on category
        if ($category === 'meghalaya-gk') {
            $selected_questions = load_meghalaya_questions(20);
            error_log('Loaded ' . count($selected_questions) . ' Meghalaya GK questions');
        } else {
            // Use new TestQnA functions to load questions
            $selected_questions = [];
            
            // Process each category in the exam configuration
            foreach ($exam_config['categories'] as $category_name => $category_config) {
                $target_count = $category_config['count'];
                $testqna_category = $category_config['testqna_category'] ?? null;
                $target_subcategory = $category_config['subcategory'] ?? null;
                
                // Map category names to TestQnA categories if not explicitly set
                if (!$testqna_category) {
                    $category_mapping = [
                        'General English' => 'general-english',
                        'General Knowledge' => 'general-knowledge', 
                        'General Aptitude' => 'aptitude',
                        'Aptitude' => 'aptitude'
                    ];
                    $testqna_category = $category_mapping[$category_name] ?? 'general-english';
                }
                
                // For Typist test, only use English category
                if ($exam_type === 'mpsc_typist') {
                    $testqna_category = 'general-english';
                }
                
                // Load questions from TestQnA using the new function
                $category_questions = load_questions_from_testqna($testqna_category, $target_subcategory, $target_count);
                
                if (empty($category_questions)) {
                    error_log("WARNING: No questions found for category: $testqna_category" . ($target_subcategory ? ", subcategory: $target_subcategory" : ""));
                    continue;
                }
                
                $selected_questions = array_merge($selected_questions, $category_questions);
                
                error_log(sprintf(
                    'Selected %d questions for %s from TestQnA category: %s%s',
                    count($category_questions),
                    $category_name,
                    $testqna_category,
                    $target_subcategory ? " (subcategory: $target_subcategory)" : ""
                ));
            }
        }
        
        $questions = $selected_questions;
        
        // Set quiz title based on exam type or default to Mock Test
        $quiz_title = $exam_config['name'] . ' (' . count($questions) . ' Questions)' ?? 'Mock Test';
        $quiz_id = $exam_type ?? 'mock_test_' . time(); // Unique ID for each mock test
        
        // Debug: Check the first question
        if (!empty($questions)) {
            error_log('First question: ' . print_r($questions[0], true));
        } else {
            error_log('WARNING: No questions were selected for the test');
        }
        
        // Check if we got any questions
        if (empty($questions)) {
            error_log('No questions found from TestQnA functions');
            die('No questions found. Please check the error logs for more information.');
        }
        
        // Get the total expected questions from the exam config
        $total_expected = $exam_config['total_questions'] ?? count($questions);
        
        // If we need more questions to reach the expected total, load from all categories
        $remaining = $total_expected - count($questions);
        if ($remaining > 0) {
            // Load additional questions from all categories
            $additional_questions = [];
            foreach (['general-english', 'general-knowledge', 'aptitude'] as $cat) {
                $extra = load_questions_from_testqna($cat, null, ceil($remaining / 3));
                $additional_questions = array_merge($additional_questions, $extra);
            }
            
            // Remove duplicates and shuffle
            $additional_questions = array_udiff($additional_questions, $questions, function($a, $b) {
                return strcmp(serialize($a), serialize($b));
            });
            shuffle($additional_questions);
            
            // Add the needed amount
            $questions = array_merge($questions, array_slice($additional_questions, 0, $remaining));
            
            error_log(sprintf(
                'Added %d more questions from other categories to reach total of %d',
                min($remaining, count($additional_questions)),
                count($questions)
            ));
        }
        
        // Shuffle the final set of questions
        shuffle($questions);
        
        error_log('Successfully loaded ' . count($questions) . ' questions from TestQnA functions');
        
        if (empty($questions)) {
            error_log('No questions available after filtering');
            die('No valid questions found. Please check the question format in the TestQnA directory.');
        }
        
        // Set marks per question (2 marks per question)
        foreach ($questions as &$question) {
            $question['marks'] = 2;
        }
        unset($question); // Break the reference
        
        // Store questions in session for review
        $_SESSION['questions'] = $questions;
        $_SESSION['mock_mode'] = true;
        $_SESSION['quiz_title'] = $quiz_title;
        if ($category) {
            $_SESSION['quiz_file'] = $category . '_' . time();
        } else {
            $_SESSION['quiz_file'] = $exam_type ?? 'mock_test_' . time();
        }
        
        // Debug: Check the first question
        if (!empty($questions[0])) {
            error_log('First question: ' . print_r($questions[0], true));
        } else {
            error_log('WARNING: No questions available in the quiz');
        }
    }
}

// Handle non-mock quiz mode and ensure $quiz_title is defined
if (!$mock_mode && !$exam_type && !$category) {
    if (!isset($_GET['quiz'])) {
        header('Location: index.php');
        exit;
    }
    $quiz_file = $_GET['quiz'];
    $quiz_path = __DIR__ . '/quizzes/' . $quiz_file;
    if (!file_exists($quiz_path)) {
        die('Quiz not found.');
    }
    $questions = parse_quiz($quiz_path);
    if (empty($questions)) {
        die('No questions found in the quiz file.');
    }
    $quiz_title = quiz_title_from_filename($quiz_file);
    $quiz_id = $quiz_file;
    
    // Store quiz data in session for form submission
    $_SESSION['questions'] = $questions;
    $_SESSION['quiz_title'] = $quiz_title;
    $_SESSION['quiz_file'] = $quiz_file;
    $quiz_id = $quiz_file;
}

// Ensure quiz_title is always defined
if (!isset($quiz_title)) {
    $quiz_title = 'Quiz';
}

// Define query string for form action
$query_string = '';
if ($mock_mode) {
    $query_string = '?mock=1';
} elseif ($exam_type) {
    $query_string = '?exam=' . urlencode($exam_type);
} elseif ($category) {
    $query_string = '?category=' . urlencode($category);
} elseif (isset($_GET['quiz'])) {
    $query_string = '?quiz=' . urlencode($_GET['quiz']);
}

// Reset and set quiz start time for new quiz session
// Always reset to ensure accurate timing for each quiz attempt
if (!isset($_SESSION['current_quiz_id']) || $_SESSION['current_quiz_id'] !== $quiz_id) {
    $_SESSION['quiz_start_time'] = time();
    $_SESSION['current_quiz_id'] = $quiz_id;
    error_log('Quiz timer started for quiz: ' . $quiz_id . ' at ' . date('Y-m-d H:i:s'));
} elseif (!isset($_SESSION['quiz_start_time'])) {
    // Fallback if quiz_start_time is missing
    $_SESSION['quiz_start_time'] = time();
    error_log('Quiz timer reset due to missing start time');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['answers'] = $_POST['answers'] ?? [];
    $_SESSION['mock_mode'] = $mock_mode;
    $_SESSION['quiz_title'] = $quiz_title;
    
    // Ensure quiz_file is set for result.php
    if (!isset($_SESSION['quiz_file']) && isset($quiz_file)) {
        $_SESSION['quiz_file'] = $quiz_file;
    }
    
    // Debug: Log session variables before redirect
    error_log('DEBUG quiz.php POST: quiz_file=' . ($_SESSION['quiz_file'] ?? 'NOT_SET'));
    error_log('DEBUG quiz.php POST: answers_count=' . count($_SESSION['answers']));
    error_log('DEBUG quiz.php POST: user_id=' . ($_SESSION['user_id'] ?? 'NOT_SET'));
    
    // Save quiz attempt to database if user is logged in
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
            $quiz_type = $exam_type ?? ($mock_mode ? 'mock_test' : 'general');
            
            // Calculate time taken with validation - prioritize hidden timer
            $time_taken = 0;
            
            // First, try to use the hidden timer elapsed time (more accurate)
            if (isset($_POST['hidden_timer_elapsed']) && is_numeric($_POST['hidden_timer_elapsed'])) {
                $hidden_timer_elapsed = (int)$_POST['hidden_timer_elapsed'];
                // Validate hidden timer: should be positive and reasonable (max 4 hours)
                if ($hidden_timer_elapsed > 0 && $hidden_timer_elapsed <= 14400) {
                    $time_taken = $hidden_timer_elapsed;
                    error_log('Using hidden timer elapsed time: ' . $hidden_timer_elapsed . ' seconds');
                } else {
                    error_log('Invalid hidden timer elapsed time: ' . $hidden_timer_elapsed . ' seconds. Falling back to server-side calculation.');
                }
            }
            
            // Fallback to server-side calculation if hidden timer is not available or invalid
            if ($time_taken === 0) {
                if (isset($_SESSION['quiz_start_time']) && is_numeric($_SESSION['quiz_start_time'])) {
                    $calculated_time = time() - $_SESSION['quiz_start_time'];
                    // Validate time: should be positive and reasonable (max 4 hours)
                    if ($calculated_time > 0 && $calculated_time <= 14400) {
                        $time_taken = $calculated_time;
                        error_log('Using server-side calculated time: ' . $calculated_time . ' seconds');
                    } else {
                        error_log('Invalid quiz time calculated in quiz.php: ' . $calculated_time . ' seconds. Using 0.');
                        $time_taken = 0;
                    }
                } else {
                    error_log('Quiz start time not set or invalid in quiz.php. Using 0 for time_taken.');
                }
            }
            
            // Prepare quiz attempt data
            $quiz_attempt_data = [
                'user_id' => $_SESSION['user_id'],
                'quiz_type' => $quiz_type,
                'quiz_title' => $quiz_title,
                'total_questions' => $total_questions,
                'correct_answers' => $correct_answers,
                'score' => $correct_answers * 2, // Assuming 2 marks per question
                'max_score' => $total_questions * 2,
                'accuracy' => round($score_percentage, 2),
                'time_taken' => $time_taken,
                'started_at' => isset($_SESSION['quiz_start_time']) ? date(DATE_FORMAT, $_SESSION['quiz_start_time']) : date(DATE_FORMAT),
                'completed_at' => date(DATE_FORMAT)
            ];
            
            // Insert quiz attempt using helper function
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
                        'question_text' => substr($question['question'] ?? '', 0, 1000), // Limit length
                        'user_answer' => $user_answer,
                        'correct_answer' => $correct_answer,
                        'is_correct' => $is_correct ? 1 : 0,
                        'category' => $question['category'] ?? null,
                        'subcategory' => $question['subcategory'] ?? null
                    ];
                    
                    insertRecord('quiz_responses', $response_data);
                }
                
                // Update user statistics, daily performance, and category performance
                $primary_category = !empty($questions) ? ($questions[0]['category'] ?? 'General') : 'General';
                $stats_updated = updateAllStatistics($_SESSION['user_id'], $correct_answers, $total_questions, $primary_category);
                
                if (!$stats_updated) {
                    error_log('Warning: Failed to update some statistics for user ' . $_SESSION['user_id']);
                }
                
                // Mark as saved to prevent duplicate saves in result.php
                $_SESSION['quiz_saved'] = true;
                error_log('Quiz attempt saved successfully in quiz.php, setting quiz_saved flag');
            }
            
        } catch (Exception $e) {
            error_log('Error saving quiz attempt: ' . $e->getMessage());
            // Continue to result page even if database save fails
        }
    }
    
    // Final debug before redirect
    error_log('DEBUG quiz.php REDIRECT: All session vars - ' . print_r($_SESSION, true));
    
    header('Location: result.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quiz: <?= htmlspecialchars($quiz_title ?? 'Quiz') ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
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
        <form method="post" action="quiz.php<?= $query_string ?>" class="flex h-full flex-col">
            <?php
            // Generate CSRF token if not exists
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="quiz_type" value="<?php echo htmlspecialchars($quiz_type ?? 'general'); ?>">
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
                            <?php if (isset($q['is_error_spotting']) && $q['is_error_spotting']): ?>
                                <div class="mb-6 p-4 glassmorphic rounded-lg">
                                    <p class="text-lg mb-4 text-black dark:text-white"><?= nl2br(htmlspecialchars($q['full_sentence'] ?? '')) ?></p>
                                </div>
                            <?php endif; ?>
                            <fieldset class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <legend class="sr-only">Quiz Options</legend>
                                <?php 
                                // Handle TestQnA format where options are associative array with letter keys
                                foreach ($q['options'] as $opt_letter => $opt_text): 
                                    $display_letter = strtoupper($opt_letter);
                                ?>
                                    <div>
                                        <input class="option-radio peer hidden" id="option<?= $i ?>_<?= $opt_letter ?>" name="answers[<?= $i ?>]" type="radio" value="<?= $opt_letter ?>" required/>
                                        <label class="quiz-option option-label group flex cursor-pointer items-center justify-center rounded-lg border border-solid border-black/10 bg-white/50 p-4 text-center text-black transition-all duration-200 ease-in-out hover:border-black/30 hover:shadow-lg dark:border-white/10 dark:bg-black/10 dark:text-white dark:hover:border-white/30" for="option<?= $i ?>_<?= $opt_letter ?>" data-question="<?= $i ?>" data-option="<?= $opt_letter ?>">
                                            <span class="text-base font-medium leading-normal">
                                                <?php if (isset($q['is_error_spotting']) && $q['is_error_spotting']): ?>
                                                    <?= $display_letter ?>) Part (<?= $display_letter ?>) contains the error
                                                <?php else: ?>
                                                    <?= $display_letter ?>) <?= htmlspecialchars($opt_text) ?>
                                                <?php endif; ?>
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
                        <button type="button" id="quit-button" class="flex h-12 min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg px-5 text-base font-bold leading-normal tracking-wide hollow-button" onclick="if(confirm('Are you sure you want to quit the quiz?')) { window.location.href='index.php'; }">
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