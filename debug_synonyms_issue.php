<?php
require_once __DIR__ . '/includes/functions.php';

$all_questions = parse_test_questions();

$synonym_questions = [];
$spelling_in_synonyms = [];
$subcat_counts = [];

foreach ($all_questions as $q) {
    $cat = $q['category']['name'] ?? '';
    $subcat = $q['category']['subcategory'] ?? '';
    $question = $q['question'] ?? '';

    // Count by subcategory
    if (!isset($subcat_counts[$subcat])) $subcat_counts[$subcat] = 0;
    $subcat_counts[$subcat]++;

    // Collect all Synonyms questions
    if (strtolower($subcat) === 'synonyms') {
        $synonym_questions[] = [
            'category' => $cat,
            'subcategory' => $subcat,
            'question' => $question
        ];
        // Check if this Synonyms question is actually a spelling question
        if (stripos($question, 'spelling') !== false) {
            $spelling_in_synonyms[] = [
                'category' => $cat,
                'subcategory' => $subcat,
                'question' => $question
            ];
        }
    }
}

// Limit output to first 10 for each
$max = 10;

echo "\n==== FIRST $max SYNONYMS QUESTIONS ====";
foreach (array_slice($synonym_questions, 0, $max) as $q) {
    echo "\nCategory: {$q['category']} | Subcategory: {$q['subcategory']}\n";
    echo "Q: {$q['question']}\n";
    echo "----------------------\n";
}

echo "\n==== FIRST $max SPELLING QUESTIONS MISCLASSIFIED AS SYNONYMS ====";
foreach (array_slice($spelling_in_synonyms, 0, $max) as $q) {
    echo "\nCategory: {$q['category']} | Subcategory: {$q['subcategory']}\n";
    echo "Q: {$q['question']}\n";
    echo "----------------------\n";
}

echo "\n==== SUBCATEGORY COUNTS ====";
foreach ($subcat_counts as $subcat => $count) {
    echo "$subcat: $count\n";
} 