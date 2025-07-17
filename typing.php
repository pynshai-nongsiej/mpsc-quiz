<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/typing_texts.php';

// Get the difficulty level from the URL or use default
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'letters_only';
$valid_difficulties = ['letters_only', 'letters_numbers', 'letters_punctuation', 'letters_numbers_punctuation'];

// Validate difficulty level
if (!in_array($difficulty, $valid_difficulties)) {
    $difficulty = 'letters_only';
}

// Get a random text for the selected difficulty
$typing_text = get_random_typing_text($difficulty);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="favicon.svg" type="image/svg+xml">
    <title>MPSC Quiz Portal - Typing Test</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style type="text/tailwindcss">
        :root {
            --background-color: #0a0a0a;
            --glass: rgba(255, 255, 255, 0.05);
            --text-primary: #ffffff;
            --text-secondary: #a3a3a3;
            --accent-color: #ffffff;
            --primary-color: #141414;
            --button-glow: rgba(255, 255, 255, 0.2);
            --correct-char: #4ade80;
            --incorrect-char: #f87171;
            --cursor-color: #facc15;
        }
        .light-mode {
            --background-color: #f0f0f0;
            --glass: rgba(255, 255, 255, 0.5);
            --text-primary: #141414;
            --text-secondary: #525252;
            --accent-color: #141414;
            --primary-color: #ffffff;
            --button-glow: rgba(20, 20, 20, 0.2);
            --correct-char: #16a34a;
            --incorrect-char: #dc2626;
            --cursor-color: #eab308;
        }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            transition: background-color 0.5s ease, color 0.5s ease;
        }
        .main_container {
            @apply container mx-auto px-4 py-8 flex items-center justify-center min-h-screen;
        }
        .glass_panel {
            @apply bg-[var(--glass)] backdrop-blur-lg rounded-2xl p-8 shadow-2xl w-full max-w-3xl border border-white/10;
            transition: background-color 0.5s ease, border-color 0.5s ease;
        }
        .light-mode .glass_panel {
            @apply border border-black/10;
        }
        .typing_content {
            @apply text-lg md:text-xl font-sans text-[var(--text-secondary)] mb-8 p-6 bg-black/20 rounded-lg text-left cursor-text;
            transition: background-color 0.5s ease, color 0.5s ease;
            height: 300px;
            max-height: 50vh;
            line-height: 1.8;
            overflow-y: auto;
            text-align: left;
            position: relative;
            scroll-behavior: smooth;
            white-space: pre-wrap;
            word-break: break-word;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }
        
        .typing_content.blurred {
            filter: blur(4px);
            user-select: none;
            pointer-events: none;
        }
        
        .typing_content .line {
            display: block;
            white-space: pre-wrap;
            word-break: keep-all;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .typing_content .current-line {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        .light-mode .typing_content {
            @apply bg-white/50;
        }
        .typing_content .char {
            display: inline;
            position: relative;
            transition: color 0.2s ease;
            white-space: pre-wrap;
            word-break: keep-all;
            word-wrap: break-word;
        }
        .typing_content .char.correct {
            color: var(--correct-char);
            position: relative;
        }
        .typing_content .char.incorrect {
            color: var(--incorrect-char);
            text-decoration: underline;
            text-decoration-color: var(--incorrect-char);
            text-underline-offset: 2px;
            text-decoration-thickness: 1px;
            position: relative;
        }
        .typing_content .char.current {
            position: relative;
        }
        .typing_content .char.current::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--cursor-color);
            animation: blink 1s infinite;
            border-radius: 2px;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .toggles_container {
            @apply flex justify-center items-center flex-wrap gap-2 md:gap-4 mb-8;
        }
        .start_button {
            @apply bg-[var(--accent-color)] text-[var(--primary-color)] px-8 py-3 rounded-lg font-bold hover:opacity-90 transition-all duration-300 shadow-[0_0_15px_var(--button-glow),0_0_30px_var(--button-glow)] focus:outline-none focus:ring-4 focus:ring-white/50;
        }
        .light-mode .start_button {
            @apply focus:ring-black/50;
        }
        .stats_container {
            @apply flex justify-around mt-8 text-center;
        }
        .countdown_timer {
            @apply text-6xl font-bold text-[var(--text-primary)] tracking-tighter;
        }
        .theme-toggle {
            @apply absolute top-8 right-8 cursor-pointer;
        }
        .theme-icon {
            @apply text-2xl text-[var(--text-primary)];
        }
        .checkbox-label {
            @apply flex items-center cursor-pointer text-sm text-[var(--text-secondary)];
        }
        .checkbox-input {
            @apply sr-only;
        }
        .checkbox-box {
            @apply w-5 h-5 mr-2 rounded border border-white/20 flex items-center justify-center transition-all duration-300;
        }
        .light-mode .checkbox-box {
            @apply border-black/20;
        }
        .checkbox-input:checked + .checkbox-box {
            @apply bg-[var(--accent-color)] border-[var(--accent-color)];
        }
        .checkbox-box svg {
            @apply w-3 h-3 text-[var(--primary-color)] hidden;
        }
        .checkbox-input:checked + .checkbox-box svg {
            @apply block;
        }
        .difficulty-btn {
            @apply px-4 py-2 rounded-lg bg-[var(--glass)] text-[var(--text-primary)] hover:bg-[var(--primary-color)] transition-colors duration-200;
            border: 1px solid var(--glass);
        }
        
        .difficulty-active {
            @apply bg-[var(--primary-color)] text-[var(--accent-color)] font-medium;
            border-color: var(--accent-color);
        }
        
        .result-stats {
            display: none;
        }
    </style>
</head>
<body class="antialiased" id="body-element">
    <div class="main_container">
        <div class="glass_panel relative">
            <div class="theme-toggle" id="theme-toggle-button">
                <span class="material-icons theme-icon" id="theme-icon-sun">light_mode</span>
                <span class="material-icons theme-icon hidden" id="theme-icon-moon">dark_mode</span>
            </div>
            <h1 class="text-4xl font-bold text-center mb-2 text-[var(--text-primary)]">Typing Test</h1>
            <p class="text-center text-[var(--text-secondary)] mb-10">Test your typing speed and accuracy.</p>
            
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="flex items-center">
                        <span class="mr-2 text-sm">Time Left:</span>
                        <span class="text-xl font-bold" id="timer">60</span>s
                    </div>
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                    <div class="flex items-center">
                        <span class="mr-2 text-sm">WPM:</span>
                        <span class="text-xl font-bold" id="wpm">0</span>
                    </div>
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                    <div class="flex items-center">
                        <span class="mr-2 text-sm">Accuracy:</span>
                        <span class="text-xl font-bold" id="accuracy">0%</span>
                    </div>
                </div>
                <button class="start_button" id="start-button">Start Test</button>
            </div>
            
            <div class="typing-content" id="sample-text" style="display: none;">
                <?php echo htmlspecialchars($typing_text); ?>
            </div>
            
            <!-- Difficulty Selector -->
            <div class="mt-6 flex justify-center gap-4">
                <button class="difficulty-btn <?= $difficulty === 'letters_only' ? 'difficulty-active' : '' ?>" data-difficulty="letters_only">Letters Only</button>
                <button class="difficulty-btn <?= $difficulty === 'letters_numbers' ? 'difficulty-active' : '' ?>" data-difficulty="letters_numbers">Letters & Numbers</button>
                <button class="difficulty-btn <?= $difficulty === 'letters_punctuation' ? 'difficulty-active' : '' ?>" data-difficulty="letters_punctuation">Letters & Punctuation</button>
                <button class="difficulty-btn <?= $difficulty === 'letters_numbers_punctuation' ? 'difficulty-active' : '' ?>" data-difficulty="letters_numbers_punctuation">All Characters</button>
            </div>
            
            <div class="typing_content" id="typing-area" tabindex="0">
                <!-- Text will be inserted here by JavaScript -->
            </div>
            
            <div class="stats_container">
                <div>
                    <p class="text-sm text-[var(--text-secondary)] uppercase tracking-widest">WPM</p>
                    <p class="text-4xl font-bold text-[var(--text-primary)]" id="wpm">0</p>
                </div>
                <div class="flex items-center">
                    <div class="h-12 w-px bg-white/10"></div>
                </div>
                <div>
                    <p class="text-sm text-[var(--text-secondary)] uppercase tracking-widest">Accuracy</p>
                    <p class="text-4xl font-bold text-[var(--text-primary)]" id="accuracy">0%</p>
                </div>
                <div class="flex items-center">
                    <div class="h-12 w-px bg-white/10"></div>
                </div>
                <div>
                    <p class="text-sm text-[var(--text-secondary)] uppercase tracking-widest">Timer</p>
                    <p class="countdown_timer text-4xl font-bold text-[var(--text-primary)]" id="timer">60</p>
                </div>
            </div>
            
            <div class="result-stats mt-8 text-center" id="result-stats">
                <div class="glass_panel bg-[var(--glass)] backdrop-blur-lg rounded-2xl p-8 shadow-2xl max-w-2xl mx-auto">
                    <h3 class="text-2xl font-bold mb-6">Test Complete!</h3>
                    <div class="grid grid-cols-2 gap-6 max-w-md mx-auto">
                        <div class="p-4 rounded-lg">
                            <p class="text-sm text-[var(--text-secondary)]">Words Per Minute</p>
                            <p class="text-3xl font-bold" id="final-wpm">0</p>
                        </div>
                        <div class="p-4 rounded-lg">
                            <p class="text-sm text-[var(--text-secondary)]">Accuracy</p>
                            <p class="text-3xl font-bold" id="final-accuracy">0%</p>
                        </div>
                        <div class="p-4 rounded-lg">
                            <p class="text-sm text-[var(--text-secondary)]">Correct Characters</p>
                            <p class="text-3xl font-bold" id="correct-chars">0</p>
                        </div>
                        <div class="p-4 rounded-lg">
                            <p class="text-sm text-[var(--text-secondary)]">Incorrect Characters</p>
                            <p class="text-3xl font-bold" id="incorrect-chars">0</p>
                        </div>
                    </div>
                    <button class="mt-6 start_button" id="try-again">Try Again</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
        const themeToggleButton = document.getElementById('theme-toggle-button');
        const bodyElement = document.getElementById('body-element');
        const sunIcon = document.getElementById('theme-icon-sun');
        const moonIcon = document.getElementById('theme-icon-moon');
        
        // Check for saved theme preference or use system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'light' || (!savedTheme && !prefersDark)) {
            bodyElement.classList.add('light-mode');
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
        } else {
            bodyElement.classList.remove('light-mode');
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        }
        
        themeToggleButton.addEventListener('click', () => {
            bodyElement.classList.toggle('light-mode');
            const isLight = bodyElement.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            sunIcon.classList.toggle('hidden');
            moonIcon.classList.toggle('hidden');
        });

        // Typing Test Functionality
        const typingArea = document.getElementById('typing-area');
        const startButton = document.getElementById('start-button');
        const tryAgainButton = document.getElementById('try-again');
        const wpmElement = document.getElementById('wpm');
        const accuracyElement = document.getElementById('accuracy');
        const timerElement = document.getElementById('timer');
        const resultStats = document.getElementById('result-stats');
        const finalWpmElement = document.getElementById('final-wpm');
        const finalAccuracyElement = document.getElementById('final-accuracy');
        const correctCharsElement = document.getElementById('correct-chars');
        const incorrectCharsElement = document.getElementById('incorrect-chars');
        
        let timer;
        let timeLeft = 60; // 60 seconds test
        let isTestRunning = false;
        let startTime;
        let correctChars = 0;
        let incorrectChars = 0;
        let totalTyped = 0;
        let currentCharIndex = 0;
        let chars = [];
        
        // Handle difficulty change
        document.querySelectorAll('.difficulty-btn').forEach(button => {
            button.addEventListener('click', function() {
                const difficulty = this.getAttribute('data-difficulty');
                window.location.href = `typing.php?difficulty=${difficulty}`;
            });
        });

        // Scroll to keep current line in view
        function scrollToCurrentLine() {
            const typingArea = document.getElementById('typing-area');
            const currentCharElement = document.querySelector('.char.current');
            if (currentCharElement) {
                const containerRect = typingArea.getBoundingClientRect();
                const elementRect = currentCharElement.getBoundingClientRect();
                
                // If the current character is not in view, scroll to it
                if (elementRect.bottom > containerRect.bottom - 50) {
                    currentCharElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (elementRect.top < containerRect.top + 50) {
                    currentCharElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }

        // Initialize the test
        function initTest() {
            // Reset variables
            timeLeft = 60;
            isTestRunning = false;
            correctChars = 0;
            incorrectChars = 0;
            totalTyped = 0;
            currentCharIndex = 0;
            
            // Reset UI
            timerElement.textContent = timeLeft;
            wpmElement.textContent = '0';
            accuracyElement.textContent = '0%';
            resultStats.style.display = 'none';
            startButton.textContent = 'Start Test';
            
            // Get sample text
            const sampleText = document.getElementById('sample-text').textContent.trim();
            // Split the text into words and join with a single space to normalize whitespace
            const normalizedText = sampleText.split(/\s+/).join(' ');
            chars = Array.from(normalizedText).map(char => ({
                char,
                typed: false,
                correct: false
            }));
            
            // Render the text
            renderText();
            
            // Add blurred class initially
            typingArea.classList.add('blurred');
        }
        
        // Render the text with proper line breaks and styling
        function renderText() {
            typingArea.innerHTML = '';
            
            // Split text into lines based on newline characters
            const text = chars.map(c => c.char).join('');
            const lines = text.split('\n');
            
            let charIndex = 0;
            
            lines.forEach((line, lineIndex) => {
                const lineDiv = document.createElement('div');
                lineDiv.className = 'line';
                
                // Process each character in the line
                for (let i = 0; i < line.length; i++) {
                    const charSpan = document.createElement('span');
                    charSpan.textContent = line[i];
                    charSpan.className = 'char';
                    
                    if (charIndex < currentCharIndex) {
                        charSpan.classList.add(chars[charIndex].correct ? 'correct' : 'incorrect');
                    } else if (charIndex === currentCharIndex) {
                        charSpan.classList.add('current');
                    }
                    
                    lineDiv.appendChild(charSpan);
                    charIndex++;
                }
                
                // Add the line to the container
                typingArea.appendChild(lineDiv);
                
                // Add a space for the newline character (except after the last line)
                if (lineIndex < lines.length - 1) {
                    const newlineSpan = document.createElement('span');
                    newlineSpan.textContent = '\n';
                    newlineSpan.className = 'char';
                    
                    if (charIndex < currentCharIndex) {
                        newlineSpan.classList.add(chars[charIndex].correct ? 'correct' : 'incorrect');
                    } else if (charIndex === currentCharIndex) {
                        newlineSpan.classList.add('current');
                    }
                    
                    const newlineDiv = document.createElement('div');
                    newlineDiv.className = 'line';
                    newlineDiv.appendChild(newlineSpan);
                    typingArea.appendChild(newlineDiv);
                    
                    charIndex++;
                }
            });
            
            // Scroll to current position
            scrollToCurrentLine();
        }
        
        // Start the test
        function startTest() {
            if (isTestRunning) return;
            
            isTestRunning = true;
            startTime = new Date().getTime();
            startButton.textContent = 'Test in Progress...';
            startButton.disabled = true;
            
            // Remove blurred class to show the text
            typingArea.classList.remove('blurred');
            
            // Start the timer
            timer = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    endTest();
                }
            }, 1000);
            
            // Focus the typing area
            typingArea.focus();
        }
        
        // End the test
        function endTest() {
            clearInterval(timer);
            isTestRunning = false;
            startButton.disabled = false;
            
            // Calculate final stats
            const timeElapsed = (60 - timeLeft) || 1; // Avoid division by zero
            const wpm = Math.round((correctChars / 5) / (timeElapsed / 60));
            const accuracy = Math.round((correctChars / (correctChars + incorrectChars)) * 100) || 0;
            
            // Update result stats
            finalWpmElement.textContent = wpm;
            finalAccuracyElement.textContent = `${accuracy}%`;
            correctCharsElement.textContent = correctChars;
            incorrectCharsElement.textContent = incorrectChars;
            
            // Show result stats
            resultStats.style.display = 'block';
            
            // Create and show completion popup
            const popup = document.createElement('div');
            popup.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50';
            
            const popupContent = document.createElement('div');
            popupContent.className = 'glass_panel bg-[var(--glass)] backdrop-blur-lg rounded-2xl p-8 shadow-2xl max-w-md w-full';
            
            popupContent.innerHTML = `
                <h3 class="text-2xl font-bold mb-4">Test Complete!</h3>
                <div class="text-center mb-6">
                    <p class="text-xl font-semibold mb-2">Your Results:</p>
                    <p class="text-3xl font-bold mb-4">${wpm} WPM</p>
                    <p class="text-xl font-semibold">${accuracy}% Accuracy</p>
                </div>
                <button class="start_button w-full" onclick="this.parentElement.parentElement.remove();">Close</button>
            `;
            
            popup.appendChild(popupContent);
            document.body.appendChild(popup);
            
            // Scroll to results
            resultStats.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Handle key presses
        typingArea.addEventListener('keydown', (e) => {
            if (!isTestRunning && currentCharIndex === 0) {
                startTest();
                // Prevent the first key from being typed
                e.preventDefault();
                return;
            }
            
            // Prevent default action for all keys except backspace
            if (e.key !== 'Backspace') {
                e.preventDefault();
            }
            
            // Ignore modifier keys
            if (e.ctrlKey || e.altKey || e.metaKey) return;
            
            // Handle backspace
            if (e.key === 'Backspace') {
                if (currentCharIndex > 0) {
                    currentCharIndex--;
                    const charData = chars[currentCharIndex];
                    
                    // Update stats
                    if (charData.typed) {
                        totalTyped--;
                        if (charData.correct) {
                            correctChars--;
                        } else {
                            incorrectChars--;
                        }
                        charData.typed = false;
                        charData.correct = false;
                    }
                    
                    // Update accuracy
                    const accuracy = Math.round((correctChars / (correctChars + incorrectChars)) * 100) || 0;
                    accuracyElement.textContent = `${accuracy}%`;
                    
                    renderText();
                }
                return;
            }
            
            // Ignore other special keys
            if (e.key.length > 1) return;
            
            // Get current character data
            const charData = chars[currentCharIndex];
            
            // Check if character is correct
            const isCorrect = e.key === charData.char;
            
            // Update the character
            const currentChar = chars[currentCharIndex];
            currentChar.typed = true;
            currentChar.correct = (e.key === currentChar.char);
            
            if (currentChar.correct) {
                correctChars++;
            } else {
                incorrectChars++;
            }
            
            // Scroll to keep current line in view
            scrollToCurrentLine();
            
            // Calculate WPM (words are 5 characters on average)
            const timeElapsed = (new Date().getTime() - startTime) / 1000 / 60; // in minutes
            const wpm = Math.round((correctChars / 5) / timeElapsed) || 0;
            
            // Calculate accuracy
            const accuracy = Math.round((correctChars / (correctChars + incorrectChars)) * 100) || 0;
            
            // Update UI
            wpmElement.textContent = wpm;
            accuracyElement.textContent = `${accuracy}%`;
            
            // Move to next character
            currentCharIndex++;
            
            // Check if test is complete
            if (currentCharIndex >= chars.length) {
                endTest();
                return;
            }
            
            // Re-render the text
            renderText();
        });
        
        // Start button click handler
        startButton.addEventListener('click', () => {
            initTest();
            startTest();
        });

        // Restart button click handler
        const restartButton = document.getElementById('restart-button');
        restartButton.addEventListener('click', () => {
            initTest();
        });

        // Try again button click handler
        tryAgainButton.addEventListener('click', () => {
            initTest();
            startTest();
        });
        
        // Try again button click handler
        tryAgainButton.addEventListener('click', () => {
            initTest();
            startTest();
        });
        
        // Initialize the test
        initTest();
    </script>
</body>
</html>
