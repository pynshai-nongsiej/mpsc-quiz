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

// Get formatted quiz categories from a version directory
function get_quiz_categories($version_dir) {
    $categories = [];
    $base_dir = __DIR__ . '/../quizzes/' . $version_dir;
    
    foreach (glob($base_dir . '/*.txt') as $file) {
        $filename = basename($file);
        if ($filename === 'metadata.txt') continue;
        
        $category_name = str_replace('.txt', '', $filename);
        $category_name = str_replace('_', ' ', $category_name);
        $category_name = ucwords($category_name);
        
        // Format specific category names
        $category_name = str_ireplace(['Gk', 'It'], ['GK', 'IT'], $category_name);
        $category_name = str_ireplace('And', 'and', $category_name);
        
        $categories[] = [
            'id' => sanitize_string_for_url($category_name),
            'name' => $category_name,
            'file' => $filename,
            'path' => $version_dir . '/' . $filename
        ];
    }
    
    // Sort categories alphabetically
    usort($categories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $categories;
}

// Helper function to sanitize strings for URLs
function sanitize_string_for_url($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Get category metadata (icon, colors) based on category name
function get_category_meta($category_name) {
    $category_lower = strtolower($category_name);
    
    // Define all categories with their metadata
    $category_map = [
        // Language and Grammar
        'antonyms' => ['icon' => '🔄', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'synonyms' => ['icon' => '📖', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'change of speech' => ['icon' => '🎭', 'bg_color' => 'bg-indigo-100', 'text_color' => 'text-indigo-800'],
        'change of voice' => ['icon' => '🗣️', 'bg_color' => 'bg-indigo-100', 'text_color' => 'text-indigo-800'],
        'error spotting' => ['icon' => '⚠️', 'bg_color' => 'bg-red-100', 'text_color' => 'text-red-800'],
        'fill in the blanks' => ['icon' => '📝', 'bg_color' => 'bg-teal-100', 'text_color' => 'text-teal-800'],
        'idioms and phrases' => ['icon' => '💬', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'one word substitutes' => ['icon' => '🔤', 'bg_color' => 'bg-cyan-100', 'text_color' => 'text-cyan-800'],
        'spellings' => ['icon' => '✏️', 'bg_color' => 'bg-green-100', 'text_color' => 'text-green-800'],
        
        // General Knowledge
        'books and authors' => ['icon' => '📚', 'bg_color' => 'bg-amber-100', 'text_color' => 'text-amber-800'],
        'days and years' => ['icon' => '📅', 'bg_color' => 'bg-yellow-100', 'text_color' => 'text-yellow-800'],
        'famous places in india' => ['icon' => '🏛️', 'bg_color' => 'bg-orange-100', 'text_color' => 'text-orange-800'],
        'general science' => ['icon' => '🔬', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'honours and awards' => ['icon' => '🏆', 'bg_color' => 'bg-yellow-100', 'text_color' => 'text-yellow-800'],
        'indian culture' => ['icon' => '🎎', 'bg_color' => 'bg-rose-100', 'text_color' => 'text-rose-800'],
        'indian geography' => ['icon' => '🗺️', 'bg_color' => 'bg-emerald-100', 'text_color' => 'text-emerald-800'],
        'indian history' => ['icon' => '🏺', 'bg_color' => 'bg-amber-100', 'text_color' => 'text-amber-800'],
        'technology' => ['icon' => '💻', 'bg_color' => 'bg-indigo-100', 'text_color' => 'text-indigo-800'],
        
        // Default fallback
        'default' => ['icon' => '📚', 'bg_color' => 'bg-gray-100', 'text_color' => 'text-gray-800']
    ];
    
    // Try to find the best matching category
    foreach ($category_map as $key => $meta) {
        if ($key !== 'default' && strpos($category_lower, $key) !== false) {
            return $meta;
        }
    }
    
    // Check for partial matches
    if (strpos($category_lower, 'spell') !== false) {
        return $category_map['correct_spelling'];
    }
    if (strpos($category_lower, 'synonym') !== false || strpos($category_lower, 'antonym') !== false) {
        return $category_map['synonyms'];
    }
    if (strpos($category_lower, 'gk') !== false || strpos($category_lower, 'general') !== false) {
        return $category_map['general_knowledge'];
    }
    if (strpos($category_lower, 'it') !== false || strpos($category_lower, 'tech') !== false) {
        return $category_map['technology_and_it'];
    }
    if (strpos($category_lower, 'env') !== false || strpos($category_lower, 'geo') !== false) {
        return $category_map['environment_and_geography'];
    }
    
    return $category_map['default'];
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

// Parse questions from TestQnA directory
function parse_test_questions() {
    $questions = [];
    $test_dir = __DIR__ . '/../TestQnA';

    if (!is_dir($test_dir)) {
        error_log("ERROR: TestQnA directory not found at: " . $test_dir);
        return [];
    }

    $files = glob($test_dir . '/*.txt');

    if (empty($files)) {
        error_log("ERROR: No .txt files found in directory: " . $test_dir);
        return [];
    }

    foreach ($files as $file) {
        if (!is_readable($file)) {
            error_log("ERROR: File is not readable: " . $file);
            continue;
        }

        $content = file_get_contents($file);
        if (empty(trim($content))) {
            error_log("WARNING: File is empty: " . $file);
            continue;
        }

        // Split content into question blocks based on question numbers (e.g., '1.', '2.')
        $question_blocks_raw = preg_split('/^\d+\.\s*/m', $content, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($question_blocks_raw as $block_text) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $block_text))));

            if (count($lines) < 3) { // Must have at least 2 options and 1 answer
                continue;
            }

            $q = [];

            // Extract category from filename and set metadata
            $category_name = pathinfo($file, PATHINFO_FILENAME);
            $category_name = ucwords(str_replace('-', ' ', $category_name));
            $category_meta = get_category_meta($category_name);
            $q['category'] = [
                'name' => $category_name,
                'icon' => $category_meta['icon'],
                'bg_color' => $category_meta['bg_color'],
                'text_color' => $category_meta['text_color']
            ];

            $is_error_spotting = ($category_name === 'Error Spotting');
            $is_spellings = ($category_name === 'Spellings');

            $question_lines = [];
            $option_lines = [];

            // Separate question text from options/answer
            foreach ($lines as $line) {
                if (preg_match('/^([a-d])[\)\.]/i', $line) || preg_match('/^Answer:/i', $line)) {
                    $option_lines[] = $line;
                } else {
                    $question_lines[] = $line;
                }
            }

            // Set question text
            if ($is_error_spotting) {
                $q['question'] = 'Find the part of the sentence that has an error.';
            } else if ($is_spellings) {
                $q['question'] = 'Choose the correctly spelled word.';
            } else {
                $q['question'] = implode(' ', $question_lines);
            }

            $found_answer = false;
            $found_options = [];

            // Parse options and answer from the filtered lines
            foreach ($option_lines as $line) {
                if (preg_match('/^Answer:\s*([a-d])\)?/i', $line, $matches)) {
                    $q['answer'] = strtolower(trim($matches[1]));
                    $found_answer = true;
                } elseif (preg_match('/^([a-d])[\)\.]\s*(.*)/i', $line, $matches)) {
                    $option_letter = strtoupper($matches[1]);
                    $option_text = trim($matches[2]);
                    $found_options[$option_letter] = "$option_letter) $option_text";
                }
            }

            if (!$found_answer || count($found_options) < 2) {
                error_log("Skipping invalid question in file " . basename($file) . ": missing answer or not enough options.");
                continue;
            }

            ksort($found_options);
            $q['options'] = array_values($found_options);

            $questions[] = $q;
        }
    }

    return $questions;
}

// Parse quiz .txt file into array of questions
function parse_quiz($filename) {
    $content = file_get_contents($filename);
    $lines = preg_split('/\r?\n/', $content);
    $questions = [];
    $q = [];
    $is_error_spotting = false;
    
    // Extract and format category from filename
    $base_category = pathinfo($filename, PATHINFO_FILENAME);
    
    // Format category name for display
    $category = str_replace('_', ' ', $base_category);
    $category = ucwords(strtolower($category));
    
    // Handle special cases
    $category = str_ireplace(['Gk', 'It'], ['GK', 'IT'], $category);
    $category = str_replace(' And ', ' and ', $category);
    
    // Clean up any remaining underscores
    $category = str_replace('_', ' ', $category);
    
    // Get icon and color based on category
    $category_meta = get_category_meta($base_category);
    
    // Ensure we have a proper category name
    if (empty(trim($category))) {
        $category = 'General Knowledge';
    }
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^\d+\./', $line)) {
            if (!empty($q)) {
                $questions[] = $q;
                $q = [];
            }
            $q['question'] = preg_replace('/^\d+\.\s*/', '', $line);
            $q['options'] = [];
            
            // Add category information
            $q['category'] = [
                'name' => $category,
                'icon' => $category_meta['icon'],
                'bg_color' => $category_meta['bg_color'],
                'text_color' => $category_meta['text_color']
            ];
            
            // Check if this is an error-spotting question by looking for the specific format
            $q['is_error_spotting'] = (strpos($q['question'], '(a)') !== false || 
                                     strpos($q['question'], '(b)') !== false ||
                                     strpos($q['question'], '(c)') !== false ||
                                     strpos($q['question'], '(d)') !== false) &&
                                     preg_match('/\([a-d]\)/', $q['question']);
            $is_error_spotting = $q['is_error_spotting'];
            $q['full_sentence'] = ''; // Initialize full_sentence for all questions
        } elseif (preg_match('/^[a-d]\)/i', $line)) {
            $q['options'][] = $line;
        } elseif (preg_match('/^\([a-d]\)/', $line)) {
            // Handle error spotting options in the format (a), (b), etc.
            $q['options'][] = $line;
        } elseif (preg_match('/^Answer:\s*([a-d])/i', $line, $m)) {
            $q['answer'] = strtolower($m[1]);
        } elseif (!empty($line) && $is_error_spotting && empty($q['options'])) {
            // For error spotting questions, the first line after the question is the full sentence
            $q['full_sentence'] = $line;
            // Extract options from the sentence
            if (preg_match_all('/\(([a-d])\)/', $line, $matches)) {
                $q['options'] = array_map(function($m) { return "$m)"; }, $matches[1]);
            }
        }
    }
    if (!empty($q)) {
        $questions[] = $q;
    }
    return $questions;
} 