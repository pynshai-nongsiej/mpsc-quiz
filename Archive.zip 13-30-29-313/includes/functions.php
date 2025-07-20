<?php
// Helper: Sanitize user input
function sanitize_input($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Get exam configuration based on exam type
function get_exam_config($exam_type) {
    $configs = [
        'mpsc_lda' => [
            'name' => 'MPSC LDA Mock Test',
            'categories' => [
                'General English' => [
                    'count' => 100,
                    'subcategories' => [
                        'Antonyms', 'Synonyms', 'Spellings', 'Idioms and Phrases',
                        'One Word Substitutes', 'Change of Speech', 'Change of Voice',
                        'Error Spotting', 'Fill in the Blanks'
                    ]
                ],
                'General Knowledge & Aptitude' => [
                    'count' => 75,
                    'subcategories' => [
                        'General Knowledge', 'Meghalaya GK', 'Books and Authors',
                        'Days and Years', 'Famous Places in India', 'General Science',
                        'Honours and Awards', 'Indian Culture', 'Indian Geography',
                        'Indian History', 'Technology'
                    ]
                ],
                'Arithmetic' => [
                    'count' => 50,
                    'subcategories' => [
                        'Average', 'Boats and Streams', 'Calendar', 'Clock',
                        'Compound Interest', 'Interest', 'Percentage', 'Problems on Ages',
                        'Problems on HCF and LCM', 'Problems on Trains', 'Profit and Loss',
                        'Ratio', 'Speed Time and Distance', 'Time and Work'
                    ]
                ]
            ]
        ],
        'dsc_lda' => [
            'name' => 'DSC LDA Mock Test',
            'categories' => [
                'General English' => [
                    'count' => 100,
                    'subcategories' => [
                        'Antonyms', 'Synonyms', 'Spellings', 'Idioms and Phrases',
                        'One Word Substitutes', 'Change of Speech', 'Change of Voice',
                        'Error Spotting', 'Fill in the Blanks', 'Precis Writing',
                        'Essay Writing', 'Drafting'
                    ]
                ],
                'Elementary Mathematics & Science' => [
                    'count' => 70,
                    'subcategories' => [
                        'Average', 'Calendar', 'Clock', 'Compound Interest', 'Interest',
                        'Percentage', 'Problems on Ages', 'Problems on HCF and LCM',
                        'Profit and Loss', 'Ratio', 'Time and Work',
                        'General Science', 'Elementary Science'
                    ]
                ],
                'General Knowledge' => [
                    'count' => 70,
                    'subcategories' => [
                        'General Knowledge', 'Meghalaya GK', 'Books and Authors',
                        'Days and Years', 'Famous Places in India', 'General Science',
                        'Honours and Awards', 'Indian Culture', 'Indian Geography',
                        'Indian History', 'Technology'
                    ]
                ],
                'Aptitude' => [
                    'count' => 30,
                    'subcategories' => [
                        'Average', 'Boats and Streams', 'Calendar', 'Clock',
                        'Compound Interest', 'Interest', 'Percentage', 'Problems on Ages',
                        'Problems on HCF and LCM', 'Problems on Trains', 'Profit and Loss',
                        'Ratio', 'Speed Time and Distance', 'Time and Work'
                    ]
                ]
            ]
        ],
        'mpsc_typist' => [
            'name' => 'MPSC Typist Test',
            'categories' => [
                'English' => [
                    'count' => 50,
                    'subcategories' => [
                        'Antonyms', 'Synonyms', 'Spellings', 'Idioms and Phrases',
                        'One Word Substitutes', 'Change of Speech', 'Change of Voice',
                        'Error Spotting', 'Fill in the Blanks', 'General English'
                    ]
                ]
            ]
        ]
    ];

    return $configs[$exam_type] ?? [
        'name' => 'General Mock Test',
        'categories' => [
            'All Categories' => [
                'count' => 50,
                'subcategories' => 'all'
            ]
        ]
    ];
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
        'general knowledge' => ['icon' => '🌍', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'meghalaya gk' => ['icon' => '🏞️', 'bg_color' => 'bg-green-100', 'text_color' => 'text-green-800'],
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
        return $category_map['spellings'];
    }
    if (strpos($category_lower, 'synonym') !== false || strpos($category_lower, 'antonym') !== false) {
        return $category_map['synonyms'];
    }
    if (strpos($category_lower, 'general knowledge') !== false || strpos($category_lower, 'general-knowledge') !== false) {
        return $category_map['general knowledge'];
    }
    if (strpos($category_lower, 'meghalaya') !== false || strpos($category_lower, 'megha') !== false) {
        return $category_map['meghalaya gk'];
    }
    if (strpos($category_lower, 'gk') !== false) {
        if (strpos($category_lower, 'meghalaya') !== false) {
            return $category_map['meghalaya gk'];
        }
        return $category_map['general knowledge'];
    }
    if (strpos($category_lower, 'it') !== false || strpos($category_lower, 'tech') !== false) {
        return $category_map['technology'];
    }
    if (strpos($category_lower, 'env') !== false || strpos($category_lower, 'geo') !== false) {
        return $category_map['indian geography'];
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
    
    // Debug: Log the test directory being used
    error_log("DEBUG: Looking for questions in directory: " . $test_dir);

    if (!is_dir($test_dir)) {
        error_log("ERROR: TestQnA directory not found at: " . $test_dir);
        return [];
    }

    // Define categories and their display names
    $categories = [
        'general-english' => 'General English',
        'general-knowledge' => 'General Knowledge',
        'aptitude' => 'Aptitude'
    ];
    
    // Process each category directory
    foreach ($categories as $category_dir => $category_name) {
        $category_path = $test_dir . '/' . $category_dir;
        
        if (!is_dir($category_path)) {
            error_log("WARNING: Category directory not found: " . $category_path);
            continue;
        }
        
        // Get all .txt files in the category directory
        $files = glob($category_path . '/*.txt');
        error_log("DEBUG: Found " . count($files) . " files in category: " . $category_name);
        
        if (empty($files)) {
            error_log("WARNING: No .txt files found in category: " . $category_name);
            continue;
        }
        
        foreach ($files as $file) {
            $filename = basename($file);
            error_log("DEBUG: Processing file: " . $filename);
            
            if (!is_readable($file)) {
                error_log("ERROR: File is not readable: " . $file);
                continue;
            }
            
            // Special debug for General Knowledge and Meghalaya GK files
            if (stripos($filename, 'General-Knowledge') !== false || stripos($filename, 'Meghalaya-GK') !== false) {
                error_log("DEBUG: Found special file: " . $filename);
            }

            $content = file_get_contents($file);
            if (empty(trim($content))) {
                error_log("WARNING: File is empty: " . $file);
                continue;
            }
            
            // Get the subcategory from filename
            $subcategory_name = pathinfo($file, PATHINFO_FILENAME);
            $subcategory_name = ucwords(str_replace('-', ' ', $subcategory_name));
            
            // Special handling for General Knowledge and Meghalaya GK
            if (stripos($subcategory_name, 'General Knowledge') !== false) {
                $subcategory_name = 'General Knowledge';
            } elseif (stripos($subcategory_name, 'Meghalaya GK') !== false || stripos($subcategory_name, 'Meghalaya-GK') !== false) {
                $subcategory_name = 'Meghalaya GK';
            }
            
            $category_meta = get_category_meta($subcategory_name);
            
            // Debug: Log category information
            error_log("DEBUG: Processing category: $category_name, Subcategory: $subcategory_name");
            error_log("DEBUG: Category meta: " . print_r($category_meta, true));

            // Split content into question blocks based on question numbers (e.g., '1.', '2.')
            $question_blocks_raw = preg_split('/^\d+\.\s*/m', $content, -1, PREG_SPLIT_NO_EMPTY);
            error_log("DEBUG: Found " . count($question_blocks_raw) . " question blocks in " . $filename);

            foreach ($question_blocks_raw as $block_index => $block_text) {
                $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $block_text))));
                
                // Debug: Log the first question block lines for inspection
                if ($block_index === 0) {
                    error_log("DEBUG: First question block lines in $filename (first 5): " . print_r(array_slice($lines, 0, 5), true));
                    
                    // Special debug for General Knowledge and Meghalaya GK files
                    if (stripos($filename, 'General-Knowledge') !== false || stripos($filename, 'Meghalaya-GK') !== false) {
                        error_log("DEBUG: First question block in $filename: " . substr($block_text, 0, 200) . "...");
                        error_log("DEBUG: First question lines in $filename: " . print_r($lines, true));
                    }
                }

                if (count($lines) < 3) { // Must have at least 2 options and 1 answer
                    error_log("WARNING: Skipping question block with insufficient lines in " . $filename . ", block " . ($block_index + 1));
                    continue;
                }

                $q = [];
                
                // Set category and subcategory information for this question
                $q['category'] = [
                    'name' => $category_name,
                    'subcategory' => $subcategory_name,
                    'icon' => $category_meta['icon'],
                    'bg_color' => $category_meta['bg_color'],
                    'text_color' => $category_meta['text_color']
                ];
                
                // Debug: Log the assigned category and subcategory
                error_log("DEBUG: Assigned category to question: " . $q['category']['name'] . ", Subcategory: " . $q['category']['subcategory']);

                $is_error_spotting = (stripos($subcategory_name, 'Error Spotting') !== false);
                $is_spellings = (stripos($subcategory_name, 'Spellings') !== false);

                $question_lines = [];
                $option_lines = [];
                $answer_line = null;

                // Separate question text from options/answer
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    // Check for answer line (case-insensitive)
                    if (preg_match('/^Answer:\s*([a-d])/i', $line, $matches) || 
                        preg_match('/^[aA]nswer\s*[\:\-]\s*([a-d])/i', $line, $matches) ||
                        preg_match('/^[aA]nswer\s*[\:\-]\s*option\s*[\:\-]?\s*([a-d])/i', $line, $matches)) {
                        $answer_line = strtoupper(trim($matches[1]));
                        continue;
                    }
                    
                    // Check for option lines (a), b), etc. or a. b. etc.
                    if (preg_match('/^([a-d])[\)\.]\s*(.*)/i', $line, $matches)) {
                        $option_letter = strtoupper($matches[1]);
                        $option_text = trim($matches[2]);
                        $option_lines[$option_letter] = $option_text;
                    } 
                    // Also handle options with spaces after the letter, like "a )" or "a. "
                    else if (preg_match('/^([a-d])\s*[)\.]\s*(.*)/i', $line, $matches)) {
                        $option_letter = strtoupper($matches[1]);
                        $option_text = trim($matches[2]);
                        $option_lines[$option_letter] = $option_text;
                    } else {
                        // If it's not an option or answer, it's part of the question
                        $question_lines[] = $line;
                    }
                }
            
                // Debug: Log the parsed components
                error_log("DEBUG: Question lines: " . print_r($question_lines, true));
                error_log("DEBUG: Option lines: " . print_r($option_lines, true));
                error_log("DEBUG: Answer line: " . $answer_line);

                // Set question text
                if ($is_error_spotting) {
                    $q['question'] = 'Find the part of the sentence that has an error.';
                } else if ($is_spellings) {
                    $q['question'] = 'Choose the correctly spelled word.';
                } else {
                    $q['question'] = implode(' ', $question_lines);
                }
            
            // Debug: Log the constructed question
            error_log("DEBUG: Constructed question: " . $q['question']);

            $found_answer = !is_null($answer_line);
            $found_options = [];
            
            // Format the options
            foreach ($option_lines as $letter => $text) {
                $found_options[$letter] = "$letter) $text";
            }

            if (!$found_answer) {
                error_log("WARNING: Missing answer for question in " . $filename . ". Question: " . substr($q['question'], 0, 100) . "...");
                // Try to extract answer from the question text as a fallback
                if (preg_match('/\(([a-d])\)\s*$/i', $q['question'], $matches)) {
                    $q['answer'] = strtolower($matches[1]);
                    $found_answer = true;
                    $q['question'] = preg_replace('/\s*\([a-d]\)\s*$/i', '', $q['question']);
                    error_log("DEBUG: Extracted answer from question text: " . $q['answer']);
                }
            } else {
                $q['answer'] = strtolower($answer_line);
            }
            
            // If we still don't have an answer, try to extract from the question text
            if (!$found_answer) {
                // Try to find answer in format [A], (A), [Answer: A], etc.
                if (preg_match('/\[(?:Answer|Ans)\.?\s*:?\s*([a-d])\]/i', $q['question'], $matches) ||
                    preg_match('/\((?:Answer|Ans)\.?\s*:?\s*([a-d])\)/i', $q['question'], $matches) ||
                    preg_match('/\b(?:Answer|Ans)\.?\s*:?\s*([a-d])\b/i', $q['question'], $matches)) {
                    $q['answer'] = strtolower($matches[1]);
                    $found_answer = true;
                    // Clean up the question text
                    $q['question'] = preg_replace('/\s*\[(?:Answer|Ans)\.?\s*:?\s*[a-d]\]\s*/i', ' ', $q['question']);
                    $q['question'] = preg_replace('/\s*\((?:Answer|Ans)\.?\s*:?\s*[a-d]\)\s*/i', ' ', $q['question']);
                    $q['question'] = preg_replace('/\s*\b(?:Answer|Ans)\.?\s*:?\s*[a-d]\b\s*/i', ' ', $q['question']);
                    $q['question'] = trim(preg_replace('/\s+/', ' ', $q['question']));
                    error_log("DEBUG: Extracted answer from question text: " . $q['answer']);
                } else {
                    // If still no answer, use the first option as default
                    $q['answer'] = 'a';
                    error_log("WARNING: Using default answer 'a' for question in " . $filename);
                }
            }
            
            // Ensure we have at least 2 options
            if (count($found_options) < 2) {
                error_log("WARNING: Not enough options (" . count($found_options) . ") for question in " . $filename . ". Question: " . substr($q['question'], 0, 100) . "...");
                
                // For General Knowledge and Meghalaya GK, try to find options in the question text
                if (in_array($category_name, ['General Knowledge', 'Meghalaya GK'])) {
                    // Look for patterns like "a) Option 1 b) Option 2 c) Option 3 d) Option 4" in the question
                    if (preg_match_all('/([a-d])\)\s*([^a-d\)]+)(?=\s+[a-d]\)|$)/i', $q['question'], $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $option_letter = strtoupper(trim($match[1]));
                            $option_text = trim($match[2]);
                            $found_options[$option_letter] = "$option_letter) $option_text";
                        }
                        // Remove the options from the question text
                        $q['question'] = preg_replace('/([a-d])\)\s*[^a-d\)]+(?:\s+[a-d]\)|$)/i', '', $q['question']);
                        $q['question'] = trim(preg_replace('/\s+/', ' ', $q['question']));
                        $q['question'] = rtrim($q['question'], ':');
                        error_log("DEBUG: Extracted options from question text: " . print_r($found_options, true));
                    }
                }
                
                // If we still don't have enough options, add dummy ones
                if (count($found_options) < 2) {
                    $option_letters = ['A', 'B', 'C', 'D'];
                    foreach ($option_letters as $letter) {
                        if (!isset($found_options[$letter])) {
                            $found_options[$letter] = "$letter) [Option $letter]";
                        }
                        if (count($found_options) >= 4) break;
                    }
                }
            }
            
            ksort($found_options);
            $q['options'] = array_values($found_options);
            
            // Debug: Log the final question data
            error_log("DEBUG: Final question data - Answer: " . $q['answer'] . ", Options: " . count($q['options']));
            
            $questions[] = $q;
            
            // Debug: Log success
            // Log the first few questions from special files
            if ((stripos($filename, 'General-Knowledge') !== false || stripos($filename, 'Meghalaya-GK') !== false) && count($questions) <= 3) {
                error_log("DEBUG: Parsed question from $filename - " . 
                         "Category: " . $q['category']['name'] . 
                         ", Question: " . substr($q['question'], 0, 100) . 
                         "..., Answer: " . $q['answer']);
            }
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