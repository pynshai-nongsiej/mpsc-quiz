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
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
            --progress-bar-bg: #e5e7eb;
            --progress-bar-fill: #1f2937;
            --progress-bar-glow: 0 0 8px rgba(0, 0, 0, 0.1);
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
            --option-hover-bg: rgba(0, 0, 0, 0.05);
            --option-checked-bg: #1f2937;
            --option-checked-text: #ffffff;
            --correct-bg: #d1fae5;
            --correct-border: #10b981;
            --correct-text: #065f46;
            --incorrect-bg: #fee2e2;
            --incorrect-border: #ef4444;
            --incorrect-text: #991b1b;
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
            --progress-bar-bg: #374151;
            --progress-bar-fill: #f9fafb;
            --progress-bar-glow: 0 0 8px rgba(255, 255, 255, 0.1);
            --option-hover-bg: rgba(255, 255, 255, 0.1);
            --option-checked-bg: #f9fafb;
            --option-checked-text: #1f2937;
            --correct-bg: #064e3b;
            --correct-border: #34d399;
            --correct-text: #d1fae5;
            --incorrect-bg: #7f1d1d;
            --incorrect-border: #f87171;
            --incorrect-text: #fee2e2;
        }
        @keyframes fadeIn {
          from {
            opacity: 0;
            transform: translateY(10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        .fade-in {
          animation: fadeIn 0.5s ease-in-out forwards;
        }
        .glassmorphism-panel {
          background: var(--card-bg);
          backdrop-filter: blur(20px);
          border: 1px solid var(--card-border);
          border-radius: 1.5rem;
        }
        .glass-button {
          background: var(--primary-color);
          color: var(--button-primary-text);
          transition: all 0.3s ease;
        }
        .glass-button:hover {
          opacity: 0.9;
          transform: scale(1.02);
        }
        .progress-glow {
          box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }
        #theme-toggle:checked+label div {
          transform: translateX(100%);
        }
        .quiz-option {
          transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease-out, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .quiz-option:hover:not(.disabled) {
          background-color: var(--option-hover-bg);
          transform: translateY(-2px);
        }
        .quiz-option.selected {
          background-color: var(--option-checked-bg);
          color: var(--option-checked-text);
          box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .quiz-option.selected:hover {
            background-color: var(--option-checked-bg);
            color: var(--option-checked-text);
        }
        .quiz-option.correct {
          background-color: var(--correct-bg);
          border-color: var(--correct-border);
          color: var(--correct-text);
          box-shadow: 0 0 15px -3px var(--correct-border);
        }
        .quiz-option.incorrect {
          background-color: var(--incorrect-bg);
          border-color: var(--incorrect-border);
          color: var(--incorrect-text);
          box-shadow: 0 0 15px -3px var(--incorrect-border);
        }
        .quiz-option.disabled {
          cursor: not-allowed;
          pointer-events: none;
        }
    </style>
    <style>
        body {
          min-height: max(884px, 100dvh);
          font-family: 'Manrope', sans-serif;
          transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
</head>
<body class="bg-[var(--background-color)] text-[var(--text-primary)]">
    <div class="relative flex flex-col min-h-screen justify-between overflow-hidden">
        <form method="post" action="quiz.php<?= $query_string ?>" class="flex-grow flex flex-col">
            <?php
            // Generate CSRF token if not exists
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="quiz_type" value="<?php echo htmlspecialchars($quiz_type ?? 'general'); ?>">
            <input type="hidden" name="hidden_timer_elapsed" id="hidden_timer_elapsed" value="0">
            <?php include 'includes/navbar.php'; ?>
            <?php include 'includes/mobile_navbar.php'; ?>
            
            <div class="mt-20"></div>

            <div class="mb-6 fade-in px-2">
                <div class="flex justify-between items-center mb-2">
                    <p class="text-sm font-semibold text-[var(--text-secondary)]">Question <span id="current-question">1</span> of <?= count($questions) ?></p>
                </div>
                <div class="w-full bg-[var(--progress-bar-bg)] rounded-full h-2">
                    <div class="h-2 rounded-full progress-glow" id="progress-bar" style="width: 0%; background-color: var(--progress-bar-fill); box-shadow: var(--progress-bar-glow);"></div>
                </div>
            </div>

            <div class="glassmorphism-panel p-6 sm:p-8 flex-grow flex flex-col justify-center fade-in" style="animation-delay: 0.2s;">
                <?php foreach ($questions as $i => $q): ?>
                    <div class="question-container <?= $i === 0 ? 'active' : 'hidden' ?>" data-question="<?= $i ?>" data-correct-answer="<?= strtolower($q['answer'] ?? 'a') ?>">
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
                        <div class="mb-4">
                            <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full <?= $bg_color ?> <?= $text_color ?> space-x-2">
                                <span class="text-base"><?= $icon ?></span>
                                <span><?= htmlspecialchars($category_name) ?></span>
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold leading-tight mb-8 text-center"><?= htmlspecialchars($q['question']) ?></h2>
                        <?php if (isset($q['is_error_spotting']) && $q['is_error_spotting']): ?>
                            <div class="mb-6 p-4 bg-white/10 rounded-lg">
                                <p class="text-lg mb-4"><?= nl2br(htmlspecialchars($q['full_sentence'] ?? '')) ?></p>
                            </div>
                            <div class="space-y-3">
                                <?php 
                                // Handle TestQnA format where options are associative array with letter keys
                                foreach ($q['options'] as $opt_letter => $opt_text): 
                                    $display_letter = strtoupper($opt_letter);
                                ?>
                                    <div class="quiz-option p-4 rounded-xl cursor-pointer border border-transparent flex justify-between items-center" 
                                         data-question="<?= $i ?>" 
                                         data-option="<?= $opt_letter ?>">
                                        <span class="text-base font-semibold flex-1"><?= $display_letter ?>) Part (<?= $display_letter ?>) contains the error</span>
                                        <div class="indicator hidden">
                                            <svg class="w-6 h-6 text-[var(--correct-text)]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"></path>
                                            </svg>
                                            <svg class="w-6 h-6 text-[var(--incorrect-text)] hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" fill-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <input type="radio" name="answers[<?= $i ?>]" value="<?= $opt_letter ?>" class="hidden" required>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php 
                                // Handle TestQnA format where options are associative array with letter keys
                                foreach ($q['options'] as $opt_letter => $opt_text): 
                                    $display_letter = strtoupper($opt_letter);
                                ?>
                                    <div class="quiz-option p-4 rounded-xl cursor-pointer border border-transparent flex justify-between items-center" 
                                         data-question="<?= $i ?>" 
                                         data-option="<?= $opt_letter ?>">
                                        <span class="text-base font-semibold flex-1"><?= $display_letter ?>) <?= htmlspecialchars($opt_text) ?></span>
                                        <div class="indicator hidden">
                                            <svg class="w-6 h-6 text-[var(--correct-text)]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"></path>
                                            </svg>
                                            <svg class="w-6 h-6 text-[var(--incorrect-text)] hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" fill-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <input type="radio" name="answers[<?= $i ?>]" value="<?= $opt_letter ?>" class="hidden" required>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <footer class="p-4 md:p-6 sticky bottom-0 bg-[var(--background-color)]/80 backdrop-blur-sm pb-20 md:pb-6">
                <div class="flex justify-end">
                    <button type="button" class="glass-button w-full sm:w-auto flex items-center justify-center rounded-full h-14 px-8 text-lg font-bold hidden" id="next-button">
                        <span>Next</span>
                        <svg class="ml-2" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </footer>
        </form>
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

        // Define applyTheme function first
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
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        } else {
            // Default to dark theme
            applyTheme(true);
        } 

        // Quiz functionality with debugging
        console.log('Initializing quiz functionality...');
        
        // Add visual indicator that JavaScript is working
        document.body.style.border = '3px solid green';
        setTimeout(() => {
            document.body.style.border = 'none';
        }, 2000);
        
        const questionContainers = document.querySelectorAll('.question-container');
        const nextButton = document.getElementById('next-button');
        const progressBar = document.getElementById('progress-bar');
        const currentQuestionSpan = document.getElementById('current-question');
        let currentQuestion = 0;
        let answerLocked = false;

        console.log('Found elements:', {
            questionContainers: questionContainers.length,
            nextButton: !!nextButton,
            progressBar: !!progressBar,
            currentQuestionSpan: !!currentQuestionSpan
        });

        // Initialize progress
        updateProgress();

        // Add click handlers to options
        const quizOptions = document.querySelectorAll('.quiz-option');
        console.log('Found quiz options:', quizOptions.length);
        
        quizOptions.forEach((option, index) => {
            console.log(`Adding click handler to option ${index}:`, option.textContent.trim());
            option.addEventListener('click', () => {
                console.log('Option clicked!', option.textContent.trim(), 'Answer locked:', answerLocked);
                if (answerLocked) {
                    console.log('Answer locked, ignoring click');
                    return;
                }
                
                const questionIndex = parseInt(option.dataset.question);
                console.log('Question index:', questionIndex);
                const options = document.querySelectorAll(`.quiz-option[data-question="${questionIndex}"]`);
                console.log('Found options for question:', options.length);
                
                // Remove selected class from all options in this question
                options.forEach(opt => {
                    opt.classList.remove('selected');
                    opt.querySelector('input[type="radio"]').checked = false;
                });
                
                // Select clicked option
                option.classList.add('selected');
                const radio = option.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Lock the answer and show feedback immediately
                answerLocked = true;
                const correctAnswer = document.querySelector(`.question-container[data-question="${questionIndex}"]`).dataset.correctAnswer;
                const isCorrect = radio.value === correctAnswer;
                
                // Show feedback
                const indicator = option.querySelector('.indicator');
                if (indicator) {
                    indicator.classList.remove('hidden');
                    
                    if (isCorrect) {
                        option.classList.add('correct');
                        const correctSvg = indicator.querySelector('svg:first-child');
                        if (correctSvg) {
                            correctSvg.classList.remove('hidden');
                        }
                    } else {
                        option.classList.add('incorrect');
                        const incorrectSvg = indicator.querySelector('svg:last-child');
                        if (incorrectSvg) {
                            incorrectSvg.classList.remove('hidden');
                        }
                        
                        // Highlight correct answer (only if it's different from selected option)
                        const correctInput = document.querySelector(`.question-container[data-question="${questionIndex}"] input[value="${correctAnswer}"]`);
                        if (correctInput && correctInput.parentNode) {
                            const correctOption = correctInput.parentNode;
                            if (correctOption && correctOption !== option) {
                                correctOption.classList.add('correct');
                                const correctIndicator = correctOption.querySelector('.indicator');
                                if (correctIndicator) {
                                    correctIndicator.classList.remove('hidden');
                                    const correctSvg = correctIndicator.querySelector('svg:first-child');
                                    if (correctSvg) {
                                        correctSvg.classList.remove('hidden');
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Disable all options
                options.forEach(opt => {
                    opt.classList.add('disabled');
                });
                
                // Auto-advance to next question after 1 second
                console.log('Setting timeout for auto-advance...');
                setTimeout(() => {
                    console.log('Timeout executed! Current question:', currentQuestion, 'Total:', questionContainers.length);
                    currentQuestion++;
                    if (currentQuestion < questionContainers.length) {
                        console.log('Moving to next question:', currentQuestion);
                        showQuestion(currentQuestion);
                        answerLocked = false;
                        updateProgress();
                    } else {
                        console.log('Quiz completed - submitting form');
                        // Quiz completed - submit form
                        const form = document.querySelector('form');
                        if (form) {
                            form.submit();
                        } else {
                            console.error('Form not found!');
                        }
                    }
                }, 1000);
            });
        });

        // Next button handler (kept for compatibility but hidden)
        // Questions now auto-advance after selection

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
        }

        function updateProgress() {
            const progress = ((currentQuestion + 1) / questionContainers.length) * 100;
            progressBar.style.width = `${progress}%`;
            currentQuestionSpan.textContent = currentQuestion + 1;
        }
    </script>
</body>
</html>