<?php
// Helper: Sanitize user input
function sanitize_input($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Helper: List all .txt quiz files in quizzes/version_*/
function get_quiz_files($base_dir) {
    $quiz_files = [];
    foreach (glob($base_dir . '/version_*', GLOB_ONLYDIR) as $version_dir) {
        $version = basename($version_dir);
        foreach (glob($version_dir . '/*.txt') as $file) {
            if (basename($file) === 'metadata.txt') continue;
            $quiz_files[] = $version . '/' . basename($file);
        }
    }
    return $quiz_files;
}

// Helper: Convert 'version_001/synonyms.txt' to 'Version 001: Synonyms'
function quiz_title_from_filename($filename) {
    $parts = explode('/', $filename);
    if (count($parts) === 2) {
        $version = ucfirst(str_replace('_', ' ', $parts[0]));
        $quiz = preg_replace('/\\.txt$/', '', $parts[1]);
        $quiz = str_replace('_', ' ', $quiz);
        return $version . ': ' . ucwords($quiz);
    }
    // fallback
    $name = preg_replace('/\\.txt$/', '', $filename);
    $name = str_replace('_', ' ', $name);
    return ucwords($name);
}

// Parse quiz .txt file into array of questions
function parse_quiz($filename) {
    $content = file_get_contents($filename);
    $lines = preg_split('/\r?\n/', $content);
    $questions = [];
    $q = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^\d+\\./', $line)) {
            if (!empty($q)) {
                $questions[] = $q;
                $q = [];
            }
            $q['question'] = preg_replace('/^\d+\\.\s*/', '', $line);
            $q['options'] = [];
        } elseif (preg_match('/^[a-d]\)/i', $line)) {
            $q['options'][] = $line;
        } elseif (preg_match('/^Answer:\s*([a-d])/i', $line, $m)) {
            $q['answer'] = strtolower($m[1]);
        }
    }
    if (!empty($q)) {
        $questions[] = $q;
    }
    return $questions;
} 