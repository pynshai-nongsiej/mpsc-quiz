<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/includes/functions.php';

$mock_mode = isset($_GET['mock']) && $_GET['mock'] == 1;

if ($mock_mode) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // On form submission, use the previously stored questions and titles
        $questions   = $_SESSION['questions'] ?? [];
        $quiz_title  = $_SESSION['quiz_title'] ?? 'Mock Test';
        $quiz_id     = $_SESSION['quiz_file'] ?? 'mock_test';
    } else {
    // Get all questions from TestQnA directory
    $all_questions = parse_test_questions();
    
    // Debug: Check if we got any questions
    if (empty($all_questions)) {
        error_log('No questions found in parse_test_questions() output');
        die('No questions found. Please check the error logs for more information.');
    }
    
    error_log('Successfully loaded ' . count($all_questions) . ' questions from all categories');
    
    // Define English-related categories
    $english_categories = [
        'Antonyms', 'Synonyms', 'Spellings', 'Idioms and Phrases', 
        'One Word Substitutes', 'Change of Speech', 'Change of Voice',
        'Error Spotting', 'Fill in the Blanks'
    ];
    
    // Separate questions into English and other categories
    $english_questions = [];
    $other_questions = [];
    
    foreach ($all_questions as $question) {
        if (in_array($question['category']['name'], $english_categories)) {
            $english_questions[] = $question;
        } else {
            $other_questions[] = $question;
        }
    }
    
    // Shuffle both sets of questions
    shuffle($english_questions);
    shuffle($other_questions);
    
    // Take 25 from each category (or all available if less than 25)
    $english_selected = array_slice($english_questions, 0, 25);
    $other_selected = array_slice($other_questions, 0, 25);
    
    // Combine the selected questions
    $questions = array_merge($english_selected, $other_selected);
    
    // Shuffle the combined questions to mix English and other questions
    shuffle($questions);
    
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
    
    // Debug: Check the first question
    error_log('First question: ' . print_r($questions[0], true));
    
    $quiz_title = 'Mock Test (50 Questions - 2 Marks Each)';
    $quiz_id = 'mock_test_' . time(); // Unique ID for each mock test
    } // end generation branch
} else {
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
    $quiz_title = quiz_title_from_filename($quiz_file);
    $quiz_id = $quiz_file;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['answers'] = $_POST['answers'] ?? [];
    $_SESSION['quiz_file'] = $quiz_id;
    $_SESSION['mock_mode'] = $mock_mode;
    $_SESSION['quiz_title'] = $quiz_title;
    header('Location: result.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Quiz: <?= htmlspecialchars($quiz_title) ?></title>
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
        <form method="post" action="quiz.php<?= $mock_mode ? '?mock=1' : '' ?>" class="flex-grow flex flex-col">
            <header class="flex items-center justify-between p-4 md:p-6">
                <a href="index.php" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition-colors">
                    <svg fill="currentColor" height="24" viewBox="0 0 256 256" width="24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M224,128a8,8,0,0,1-8,8H59.31l58.35,58.34a8,8,0,0,1-11.32,11.32l-72-72a8,8,0,0,1,0-11.32l72-72a8,8,0,0,1,11.32,11.32L59.31,120H216A8,8,0,0,1,224,128Z"></path>
                    </svg>
                </a>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <input class="hidden" id="theme-toggle" type="checkbox"/>
                    <label class="relative flex items-center cursor-pointer w-10 h-6 rounded-full p-1 bg-gray-300 dark:bg-gray-700 transition-colors" for="theme-toggle">
                        <div class="absolute w-4 h-4 rounded-full bg-white transition-transform"></div>
                    </label>
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>
            </header>

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
                        // Get category information from the question data
                        $category = $q['category'] ?? [
                            'name' => 'General',
                            'icon' => '📚',
                            'bg_color' => 'bg-purple-100',
                            'text_color' => 'text-purple-800'
                        ];
                        ?>
                        <div class="mb-4">
                            <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full <?= $category['bg_color'] ?> <?= $category['text_color'] ?> space-x-2">
                                <span class="text-base"><?= $category['icon'] ?></span>
                                <span><?= htmlspecialchars($category['name']) ?></span>
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold leading-tight mb-8 text-center"><?= htmlspecialchars($q['question']) ?></h2>
                        <?php if (isset($q['is_error_spotting']) && $q['is_error_spotting']): ?>
                            <div class="mb-6 p-4 bg-white/10 rounded-lg">
                                <p class="text-lg mb-4"><?= nl2br(htmlspecialchars($q['full_sentence'] ?? '')) ?></p>
                            </div>
                            <div class="space-y-3">
                                <?php 
                                $letters = range('A', 'D'); // Only A-D for error spotting
                                foreach ($q['options'] as $j => $opt_letter): 
                                    $opt_letter = strtoupper($opt_letter[0]); // Get just the letter part
                                ?>
                                    <div class="quiz-option p-4 rounded-xl cursor-pointer border border-transparent flex justify-between items-center" 
                                         data-question="<?= $i ?>" 
                                         data-option="<?= $j ?>">
                                        <span class="text-base font-semibold flex-1"><?= $opt_letter ?>) Part (<?= strtoupper($opt_letter) ?>) contains the error</span>
                                        <div class="indicator hidden">
                                            <svg class="w-6 h-6 text-[var(--correct-text)]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"></path>
                                            </svg>
                                            <svg class="w-6 h-6 text-[var(--incorrect-text)] hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" fill-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <input type="radio" name="answers[<?= $i ?>]" value="<?= strtolower($opt_letter) ?>" class="hidden" required>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php 
                                $letters = range('A', 'Z');
                                foreach ($q['options'] as $j => $opt): 
                                    $opt_letter = $letters[$j];
                                ?>
                                    <div class="quiz-option p-4 rounded-xl cursor-pointer border border-transparent flex justify-between items-center" 
                                         data-question="<?= $i ?>" 
                                         data-option="<?= $j ?>">
                                        <span class="text-base font-semibold flex-1"><?= htmlspecialchars($opt) ?></span>
                                        <div class="indicator hidden">
                                            <svg class="w-6 h-6 text-[var(--correct-text)]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"></path>
                                            </svg>
                                            <svg class="w-6 h-6 text-[var(--incorrect-text)] hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path clip-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" fill-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <input type="radio" name="answers[<?= $i ?>]" value="<?= strtolower($opt_letter) ?>" class="hidden" required>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <footer class="p-4 md:p-6 sticky bottom-0 bg-[var(--background-color)]/80 backdrop-blur-sm">
                <div class="flex justify-end">
                    <button type="button" class="glass-button w-full sm:w-auto flex items-center justify-center rounded-full h-14 px-8 text-lg font-bold" id="next-button">
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

        // Quiz functionality
        const questionContainers = document.querySelectorAll('.question-container');
        const nextButton = document.getElementById('next-button');
        const progressBar = document.getElementById('progress-bar');
        const currentQuestionSpan = document.getElementById('current-question');
        let currentQuestion = 0;
        let answerLocked = false;

        // Initialize progress
        updateProgress();

        // Add click handlers to options
        document.querySelectorAll('.quiz-option').forEach(option => {
            option.addEventListener('click', () => {
                if (answerLocked) return;
                
                const questionIndex = parseInt(option.dataset.question);
                const options = document.querySelectorAll(`.quiz-option[data-question="${questionIndex}"]`);
                
                // Remove selected class from all options in this question
                options.forEach(opt => {
                    opt.classList.remove('selected');
                    opt.querySelector('input[type="radio"]').checked = false;
                });
                
                // Select clicked option
                option.classList.add('selected');
                const radio = option.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Enable next button
                nextButton.disabled = false;
            });
        });

        // Next button handler
        nextButton.addEventListener('click', () => {
            if (answerLocked) {
                // Move to next question
                currentQuestion++;
                if (currentQuestion < questionContainers.length) {
                    showQuestion(currentQuestion);
                    answerLocked = false;
                    nextButton.innerHTML = '<span>Next</span><svg class="ml-2" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                    updateProgress();
                } else {
                    // Submit the form if it's the last question
                    document.querySelector('form').submit();
                }
            } else {
                // Lock the answer and show feedback
                const selectedOption = document.querySelector(`.question-container[data-question="${currentQuestion}"] .selected`);
                if (!selectedOption) return;
                
                answerLocked = true;
                const questionIndex = selectedOption.closest('.question-container').dataset.question;
                const correctAnswer = document.querySelector(`.question-container[data-question="${questionIndex}"]`).dataset.correctAnswer;
                const isCorrect = selectedOption.querySelector('input[type="radio"]').value === correctAnswer;
                
                // Show feedback
                const indicator = selectedOption.querySelector('.indicator');
                indicator.classList.remove('hidden');
                
                if (isCorrect) {
                    selectedOption.classList.add('correct');
                    indicator.querySelector('svg:first-child').classList.remove('hidden');
                } else {
                    selectedOption.classList.add('incorrect');
                    indicator.querySelector('svg:last-child').classList.remove('hidden');
                    
                    // Highlight correct answer
                    const correctAnswer = document.querySelector(`.question-container[data-question="${currentQuestion}"]`).dataset.correctAnswer;
                    const correctOption = document.querySelector(`.question-container[data-question="${currentQuestion}"] input[value="${correctAnswer}"]`).parentNode;
                    correctOption.classList.add('correct');
                    correctOption.querySelector('.indicator').classList.remove('hidden');
                    correctOption.querySelector('svg:first-child').classList.remove('hidden');
                }
                
                // Disable all options
                document.querySelectorAll(`.quiz-option[data-question="${currentQuestion}"]`).forEach(opt => {
                    opt.classList.add('disabled');
                });
                
                // Update button text
                if (currentQuestion === questionContainers.length - 1) {
                    nextButton.innerHTML = '<span>Submit</span>';
                } else {
                    nextButton.innerHTML = '<span>Next Question</span><svg class="ml-2" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><polyline points="9 18 15 12 9 6"></polyline></svg>';
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
            
            // Reset next button state
            nextButton.disabled = true;
        }

        function updateProgress() {
            const progress = ((currentQuestion + 1) / questionContainers.length) * 100;
            progressBar.style.width = `${progress}%`;
            currentQuestionSpan.textContent = currentQuestion + 1;
        }
    </script>
</body>
</html> 