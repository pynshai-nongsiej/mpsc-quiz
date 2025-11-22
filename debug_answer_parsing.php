<?php
require_once __DIR__ . '/includes/functions.php';

// Test answer parsing for General English files
$test_answers = [
    "C",
    "D",
    "C (Present Perfect Active to Present Perfect Passive)",
    "A (explanation here)",
    "b",
    "a)",
    "B.",
    "-",
    ""
];

echo "Testing answer parsing:\n";
foreach ($test_answers as $original_answer) {
    $answer = $original_answer;
    if (preg_match('/^([a-dA-D])\s*[\(\.]/', $answer, $matches)) {
        // Extract letter from formats like "C (explanation)" or "c."
        $answer = strtolower($matches[1]);
    } elseif (preg_match('/^([a-dA-D])$/', $answer, $matches)) {
        // Just a single letter
        $answer = strtolower($matches[1]);
    } else {
        // If no letter found, try to extract first letter
        $first_char = substr(trim($answer), 0, 1);
        if (preg_match('/[a-dA-D]/', $first_char)) {
            $answer = strtolower($first_char);
        } else {
            // Log problematic answers for debugging
            echo "PROBLEM: Could not extract answer letter from: '$original_answer'\n";
            $answer = 'INVALID';
        }
    }
    
    echo "Original: '$original_answer' -> Processed: '$answer'\n";
}

// Test loading a General English file
echo "\nTesting actual file loading:\n";
$questions = load_questions_from_testqna(__DIR__ . '/TestQnA/general-english/vocabulary_synonyms.json', 5);
foreach ($questions as $i => $q) {
    echo "Question " . ($i + 1) . ": Answer = '{$q['answer']}'\n";
}
?>
