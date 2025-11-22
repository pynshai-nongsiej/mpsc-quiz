<?php
// Helper: Sanitize user input
function sanitize_input($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// ===== NEW TESTQNA-BASED FUNCTIONS =====

// Get all TestQnA categories by scanning the TestQnA folder structure
function get_testqna_categories() {
    $testqna_path = __DIR__ . '/../TestQnA';
    $categories = [];
    
    if (!is_dir($testqna_path)) {
        return $categories;
    }
    
    // Scan for main category directories
    $main_dirs = ['aptitude', 'general-english', 'general-knowledge'];
    
    foreach ($main_dirs as $dir) {
        $dir_path = $testqna_path . '/' . $dir;
        if (is_dir($dir_path)) {
            $category_name = str_replace('-', ' ', $dir);
            $category_name = ucwords($category_name);
            
            $subcategories = get_testqna_subcategories($dir);
            
            $categories[$dir] = [
                'name' => $category_name,
                'folder' => $dir,
                'path' => $dir_path,
                'subcategories' => $subcategories,
                'count' => count($subcategories)
            ];
        }
    }
    
    return $categories;
}

// Get subcategories for a specific TestQnA main category
function get_testqna_subcategories($category) {
    // Add type checking to prevent array to string conversion
    if (!is_string($category)) {
        error_log('get_testqna_subcategories called with non-string: ' . print_r($category, true));
        return [];
    }
    
    $testqna_path = __DIR__ . '/../TestQnA/' . $category;
    $subcategories = [];
    
    if (!is_dir($testqna_path)) {
        return $subcategories;
    }
    
    // Handle different file types based on category
    if ($category === 'general-english') {
        // For general-english, use JSON files
        $files = glob($testqna_path . '/*.json');
        
        // Exclude categories_summary.json
        $files = array_filter($files, function($file) {
            return basename($file) !== 'categories_summary.json';
        });
        
        foreach ($files as $file) {
            $filename = basename($file, '.json');
            
            // Read JSON to get the category name
            $json_content = file_get_contents($file);
            $data = json_decode($json_content, true);
            
            if ($data && isset($data['category'])) {
                $subcategory_name = $data['category'];
            } else {
                // Fallback to filename conversion
                $subcategory_name = str_replace(['_', '-'], ' ', $filename);
                $subcategory_name = ucwords($subcategory_name);
            }
            
            $subcategories[] = [
                'name' => $subcategory_name,
                'filename' => $filename,
                'file_path' => $file,
                'category' => $category,
                'file_type' => 'json'
            ];
        }
    } else {
        // For other categories, use TXT files
        $files = glob($testqna_path . '/*.txt');
        
        foreach ($files as $file) {
            $filename = basename($file, '.txt');
            $subcategory_name = str_replace(['_', '-'], ' ', $filename);
            $subcategory_name = ucwords($subcategory_name);
            
            $subcategories[] = [
                'name' => $subcategory_name,
                'filename' => $filename,
                'file_path' => $file,
                'category' => $category,
                'file_type' => 'txt'
            ];
        }
    }
    
    // Sort subcategories alphabetically
    usort($subcategories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $subcategories;
}

// Parse a TestQnA file and extract questions
function parse_testqna_file($filepath) {
    if (!file_exists($filepath)) {
        return [];
    }
    
    $content = file_get_contents($filepath);
    if (empty($content)) {
        return [];
    }
    
    $questions = [];
    $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
    
    if ($file_extension === 'json') {
        // Parse JSON file
        $data = json_decode($content, true);
        if (!$data || !isset($data['questions'])) {
            return [];
        }
        
        foreach ($data['questions'] as $q) {
            if (!isset($q['question']) || !isset($q['options']) || !isset($q['answer'])) {
                continue;
            }
            
            // Clean the question text - remove contextual file references and category indicators
            $question_text = preg_replace('/\(File\s+\d+(\s*-\s*[^)]+)?\)\s*/', '', $q['question']);
            // Remove category indicators like (Antonym), (Synonym), (Active), (Passive), etc.
            $question_text = preg_replace('/\s*\([^)]*(?:Antonym|Synonym|Active|Passive|Grammar|Vocabulary|Idiom|Phrase|Spelling|Comprehension|Reading|Writing)[^)]*\)\s*$/i', '', $question_text);
            $question_text = trim($question_text);
            
            // Convert options array to associative array with letters
            $options = [];
            foreach ($q['options'] as $index => $option) {
                // Remove the letter prefix if it exists (e.g., "a) Text", "(a) Text", "(A) Text" -> "Text")
                $option_text = preg_replace('/^(\([a-eA-E]\)|[a-eA-E]\))\s*/', '', $option);
                // Also remove file references from options
                $option_text = preg_replace('/\(File\s+\d+(\s*-\s*[^)]+)?\)\s*/', '', $option_text);
                $option_text = trim($option_text);
                $letter = chr(97 + $index); // 97 is ASCII for 'a'
                $options[$letter] = $option_text;
            }
            
            // Extract the letter from the answer (handle formats like "C (explanation)" or just "c")
            $original_answer = $q['answer'];
            $answer = $q['answer'];
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
                    error_log("DEBUG: Could not extract answer letter from: '$original_answer' in file: " . basename($filepath));
                    $answer = 'a'; // Default fallback
                }
            }
            
            
            // Get the category name from JSON data or use filename
            $category_name = isset($data['category']) ? $data['category'] : (str_replace(['_', '-'], ' ', basename($filepath, '.json')));
            
            $questions[] = [
                'question' => $question_text,
                'options' => $options,
                'answer' => $answer,
                'category' => $category_name,
                'subcategory' => basename($filepath, '.json')
            ];
        }
    } else {
        // Parse TXT file (existing logic)
        $lines = explode("\n", $content);
        $current_question = null;
        $current_options = [];
        $current_answer = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Check for question number (starts with number followed by period)
            if (preg_match('/^(\d+)\s*\.\s*(.+)/', $line, $matches)) {
                // Save previous question if exists
                if ($current_question !== null && !empty($current_options) && $current_answer !== null) {
                    $questions[] = [
                        'question' => $current_question,
                        'options' => $current_options,
                        'answer' => $current_answer,
                        'category' => basename(dirname($filepath)),
                        'subcategory' => basename($filepath, '.txt')
                    ];
                }
                
                // Start new question
                $current_question = trim($matches[2]);
                $current_options = [];
                $current_answer = null;
            }
            // Check for options (a), b), c), d), e))
            elseif (preg_match('/^([a-e])\)\s*(.+)/', $line, $matches)) {
                $option_letter = strtolower($matches[1]);
                $option_text = trim($matches[2]);
                $current_options[$option_letter] = $option_text;
            }
            // Check for answer line
            elseif (preg_match('/^Answer\s*:\s*([a-e])/i', $line, $matches)) {
                $current_answer = strtolower($matches[1]);
            }
            // If line doesn't match patterns but we have a current question, append to question text
            elseif ($current_question !== null && !preg_match('/^[a-e]\)/', $line) && !preg_match('/^Answer\s*:/i', $line)) {
                $current_question .= ' ' . $line;
            }
        }
        
        // Don't forget the last question
        if ($current_question !== null && !empty($current_options) && $current_answer !== null) {
            $questions[] = [
                'question' => $current_question,
                'options' => $current_options,
                'answer' => $current_answer,
                'category' => basename(dirname($filepath)),
                'subcategory' => basename($filepath, '.txt')
            ];
        }
    }
    
    return $questions;
}

// Load randomized questions from TestQnA files
function load_questions_from_testqna($category, $subcategory = null, $count = 20) {
    $all_questions = [];
    
    if ($subcategory !== null) {
        // Load from specific subcategory
        // Determine file extension based on category
        $file_extension = ($category === 'general-english') ? '.json' : '.txt';
        $filepath = __DIR__ . '/../TestQnA/' . $category . '/' . $subcategory . $file_extension;
        $questions = parse_testqna_file($filepath);
        $all_questions = array_merge($all_questions, $questions);
    } else {
        // Load from all subcategories in the category
        $subcategories = get_testqna_subcategories($category);
        foreach ($subcategories as $subcat) {
            $questions = parse_testqna_file($subcat['file_path']);
            $all_questions = array_merge($all_questions, $questions);
        }
    }
    
    // Shuffle questions for randomization
    shuffle($all_questions);
    
    // Return requested number of questions
    return array_slice($all_questions, 0, $count);
}

// Get TestQnA category metadata for display
function get_testqna_category_meta($category_folder) {
    $meta_map = [
        'aptitude' => [
            'icon' => '🧮',
            'bg_color' => 'bg-purple-100',
            'text_color' => 'text-purple-800',
            'border_color' => 'border-purple-500'
        ],
        'general-english' => [
            'icon' => '📚',
            'bg_color' => 'bg-blue-100',
            'text_color' => 'text-blue-800',
            'border_color' => 'border-blue-500'
        ],
        'general-knowledge' => [
            'icon' => '🌍',
            'bg_color' => 'bg-green-100',
            'text_color' => 'text-green-800',
            'border_color' => 'border-green-500'
        ]
    ];
    
    return $meta_map[$category_folder] ?? [
        'icon' => '📖',
        'bg_color' => 'bg-gray-100',
        'text_color' => 'text-gray-800',
        'border_color' => 'border-gray-500'
    ];
}

// Load mixed questions from all subcategories within a main category
function load_mixed_questions($category, $count = 20) {
    $all_questions = [];
    
    // Get all subcategories for the main category
    $subcategories = get_testqna_subcategories($category);
    
    // Load questions from all subcategories
    foreach ($subcategories as $subcat) {
        $questions = parse_testqna_file($subcat['file_path']);
        $all_questions = array_merge($all_questions, $questions);
    }
    
    // Shuffle questions for randomization
    shuffle($all_questions);
    
    // Return requested number of questions
    return array_slice($all_questions, 0, $count);
}

// Load questions specifically from Meghalaya-GK.txt file
function load_meghalaya_questions($count = 20) {
    $filepath = __DIR__ . '/../TestQnA/general-knowledge/Meghalaya-GK.txt';
    
    if (!file_exists($filepath)) {
        return [];
    }
    
    $questions = parse_testqna_file($filepath);
    
    // Shuffle questions for randomization
    shuffle($questions);
    
    // Return requested number of questions
    return array_slice($questions, 0, $count);
}

// ===== END TESTQNA-BASED FUNCTIONS =====

// Get exam configuration based on exam type (Updated to use TestQnA structure)
function get_exam_config($exam_type) {
    // Map exam types to TestQnA categories
    $type_mapping = [
        'english' => 'general-english',
        'gk' => 'general-knowledge', 
        'math' => 'aptitude',
        'aptitude' => 'aptitude',
        'general-english' => 'general-english',
        'general-knowledge' => 'general-knowledge'
    ];
    
    // Get TestQnA categories
    $testqna_categories = get_testqna_categories();
    
    // Handle TestQnA-based exam types
    if (isset($type_mapping[$exam_type])) {
        $testqna_category = $type_mapping[$exam_type];
        
        if (isset($testqna_categories[$testqna_category])) {
            $category_data = $testqna_categories[$testqna_category];
            
            // Build subcategories array
            $subcategories = [];
            foreach ($category_data['subcategories'] as $subcat) {
                $subcategories[$subcat['filename']] = $subcat['name'];
            }
            
            return [
                'name' => $category_data['name'],
                'subcategories' => $subcategories,
                'testqna_category' => $testqna_category,
                'count' => $category_data['count']
            ];
        }
    }
    
    // Legacy exam configurations for backward compatibility
    $legacy_configs = [
        'mpsc_lda' => [
            'name' => 'MPSC LDA Mock Test (300 Marks)',
            'categories' => [
                'General English' => [
                    'count' => 100,
                    'marks' => 100,
                    'testqna_category' => 'general-english',
                    'description' => '100 marks covering English Language & Grammar'
                ],
                'General Knowledge & Aptitude' => [
                    'count' => 75,
                    'marks' => 100,
                    'testqna_category' => 'general-knowledge',
                    'description' => '100 marks covering General Knowledge'
                ],
                'Arithmetic' => [
                    'count' => 50,
                    'marks' => 100,
                    'testqna_category' => 'aptitude',
                    'description' => '100 marks covering Mathematics & Aptitude'
                ]
            ],
            'total_marks' => 300,
            'total_questions' => 225,
            'description' => 'MPSC Lower Division Assistant (LDA) Examination Pattern: 225 questions for 300 marks'
        ],
        'dsc_lda' => [
            'name' => 'DSC LDA Mock Test (300 Marks)',
            'categories' => [
                'General English' => [
                    'count' => 100,
                    'marks' => 100,
                    'testqna_category' => 'general-english',
                    'description' => '100 marks covering English Language & Grammar'
                ],
                'Elementary Mathematics & Science' => [
                    'count' => 70,
                    'marks' => 70,
                    'testqna_category' => 'aptitude',
                    'description' => '70 marks covering Elementary Mathematics & Science'
                ],
                'General Knowledge' => [
                    'count' => 70,
                    'marks' => 70,
                    'testqna_category' => 'general-knowledge',
                    'description' => '70 marks covering General Knowledge'
                ],
                'Aptitude' => [
                    'count' => 30,
                    'marks' => 30,
                    'testqna_category' => 'aptitude',
                    'description' => '30 marks covering Aptitude'
                ],
                'Interview' => [
                    'count' => 1,
                    'marks' => 30,
                    'subcategories' => ['Interview'],
                    'description' => '30 marks (Interview)'
                ]
            ],
            'total_marks' => 300,
            'total_questions' => 241,
            'description' => 'DSC Lower Division Assistant (LDA) Examination Pattern: 240 marks written + 30 marks interview'
        ],
        'mpsc_typist' => [
            'name' => 'MPSC Typist Test (50 Marks)',
            'categories' => [
                'English' => [
                    'count' => 50,
                    'marks' => 50,
                    'testqna_category' => 'general-english',
                    'description' => '50 marks covering English Language & Grammar'
                ]
            ],
            'total_marks' => 50,
            'total_questions' => 50,
            'description' => 'MPSC Typist Examination Pattern: English paper (50 marks)'
        ]
    ];

    return $legacy_configs[$exam_type] ?? [
        'name' => 'General Mock Test',
        'categories' => [
            'All Categories' => [
                'count' => 50,
                'marks' => 50,
                'subcategories' => 'all',
                'description' => '50 questions for 50 marks'
            ]
        ],
        'total_marks' => 50,
        'total_questions' => 50,
        'description' => 'General Mock Test: 50 questions for 50 marks'
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
        
        // Aptitude Categories
        'aptitude' => ['icon' => '🧮', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'quantitative aptitude' => ['icon' => '🔢', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'logical reasoning' => ['icon' => '🧩', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'data interpretation' => ['icon' => '📊', 'bg_color' => 'bg-teal-100', 'text_color' => 'text-teal-800'],
        'number series' => ['icon' => '🔢', 'bg_color' => 'bg-indigo-100', 'text_color' => 'text-indigo-800'],
        'percentage' => ['icon' => '📈', 'bg_color' => 'bg-green-100', 'text_color' => 'text-green-800'],
        'profit and loss' => ['icon' => '💰', 'bg_color' => 'bg-yellow-100', 'text_color' => 'text-yellow-800'],
        'simple interest' => ['icon' => '💵', 'bg_color' => 'bg-green-100', 'text_color' => 'text-green-800'],
        'compound interest' => ['icon' => '💲', 'bg_color' => 'bg-green-100', 'text_color' => 'text-green-800'],
        'time and work' => ['icon' => '⏱️', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'time and distance' => ['icon' => '🛣️', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'average' => ['icon' => '📊', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'ratio and proportion' => ['icon' => '📐', 'bg_color' => 'bg-red-100', 'text_color' => 'text-red-800'],
        'algebra' => ['icon' => 'x²', 'bg_color' => 'bg-indigo-100', 'text_color' => 'text-indigo-800'],
        'geometry' => ['icon' => '△', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'trigonometry' => ['icon' => 'θ', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'probability' => ['icon' => '🎲', 'bg_color' => 'bg-red-100', 'text_color' => 'text-red-800'],
        'permutation and combination' => ['icon' => '🔀', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'puzzles' => ['icon' => '🧩', 'bg_color' => 'bg-yellow-100', 'text_color' => 'text-yellow-800'],
        'blood relations' => ['icon' => '👨‍👩‍👧‍👦', 'bg_color' => 'bg-pink-100', 'text_color' => 'text-pink-800'],
        'coding decoding' => ['icon' => '🔣', 'bg_color' => 'bg-indigo-100', 'text_color' => 'text-indigo-800'],
        'direction sense' => ['icon' => '🧭', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'seating arrangement' => ['icon' => '🪑', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'syllogism' => ['icon' => '⊂', 'bg_color' => 'bg-red-100', 'text_color' => 'text-red-800'],
        'analogy' => ['icon' => '⇄', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'classification' => ['icon' => '🗂️', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'series completion' => ['icon' => '🔢', 'bg_color' => 'bg-teal-100', 'text_color' => 'text-teal-800'],
        'clock and calendar' => ['icon' => '🕒', 'bg_color' => 'bg-yellow-100', 'text_color' => 'text-yellow-800'],
        'mathematical operations' => ['icon' => '➕', 'bg_color' => 'bg-green-100', 'text_color' => 'text-green-800'],
        'data sufficiency' => ['icon' => '📋', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'statement and conclusions' => ['icon' => '💭', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'statement and assumptions' => ['icon' => '💡', 'bg_color' => 'bg-yellow-100', 'text_color' => 'text-yellow-800'],
        'course of action' => ['icon' => '🛤️', 'bg_color' => 'bg-blue-100', 'text_color' => 'text-blue-800'],
        'cause and effect' => ['icon' => '⚡', 'bg_color' => 'bg-red-100', 'text_color' => 'text-red-800'],
        'statement and arguments' => ['icon' => '💬', 'bg_color' => 'bg-purple-100', 'text_color' => 'text-purple-800'],
        'logical deduction' => ['icon' => '🧠', 'bg_color' => 'bg-indigo-100', 'text_color' => 'text-indigo-800'],
        
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
    
    // Aptitude category fallbacks
    if (strpos($category_lower, 'quant') !== false || strpos($category_lower, 'math') !== false) {
        return $category_map['quantitative aptitude'];
    }
    if (strpos($category_lower, 'logic') !== false || strpos($category_lower, 'reason') !== false) {
        return $category_map['logical reasoning'];
    }
    if (strpos($category_lower, 'data') !== false || strpos($category_lower, 'interpret') !== false) {
        return $category_map['data interpretation'];
    }
    if (strpos($category_lower, 'number') !== false || strpos($category_lower, 'series') !== false) {
        return $category_map['number series'];
    }
    if (strpos($category_lower, 'percent') !== false) {
        return $category_map['percentage'];
    }
    if (strpos($category_lower, 'profit') !== false || strpos($category_lower, 'loss') !== false) {
        return $category_map['profit and loss'];
    }
    if (strpos($category_lower, 'interest') !== false) {
        if (strpos($category_lower, 'simple') !== false) {
            return $category_map['simple interest'];
        } elseif (strpos($category_lower, 'compound') !== false) {
            return $category_map['compound interest'];
        }
        return $category_map['simple interest']; // default to simple interest
    }
    if (strpos($category_lower, 'time') !== false) {
        if (strpos($category_lower, 'work') !== false) {
            return $category_map['time and work'];
        } elseif (strpos($category_lower, 'dist') !== false) {
            return $category_map['time and distance'];
        }
    }
    if (strpos($category_lower, 'average') !== false) {
        return $category_map['average'];
    }
    if (strpos($category_lower, 'ratio') !== false || strpos($category_lower, 'proportion') !== false) {
        return $category_map['ratio and proportion'];
    }
    if (strpos($category_lower, 'algebra') !== false) {
        return $category_map['algebra'];
    }
    if (strpos($category_lower, 'geo') !== false || strpos($category_lower, 'shape') !== false) {
        return $category_map['geometry'];
    }
    if (strpos($category_lower, 'trigo') !== false) {
        return $category_map['trigonometry'];
    }
    if (strpos($category_lower, 'probab') !== false) {
        return $category_map['probability'];
    }
    if (strpos($category_lower, 'permut') !== false || strpos($category_lower, 'combinat') !== false) {
        return $category_map['permutation and combination'];
    }
    if (strpos($category_lower, 'puzzle') !== false) {
        return $category_map['puzzles'];
    }
    if (strpos($category_lower, 'blood') !== false || strpos($category_lower, 'relation') !== false) {
        return $category_map['blood relations'];
    }
    if (strpos($category_lower, 'code') !== false || strpos($category_lower, 'decod') !== false) {
        return $category_map['coding decoding'];
    }
    if (strpos($category_lower, 'direct') !== false) {
        return $category_map['direction sense'];
    }
    if (strpos($category_lower, 'seat') !== false) {
        return $category_map['seating arrangement'];
    }
    if (strpos($category_lower, 'syllog') !== false) {
        return $category_map['syllogism'];
    }
    if (strpos($category_lower, 'analog') !== false) {
        return $category_map['analogy'];
    }
    if (strpos($category_lower, 'classif') !== false) {
        return $category_map['classification'];
    }
    if (strpos($category_lower, 'clock') !== false || strpos($category_lower, 'calendar') !== false) {
        return $category_map['clock and calendar'];
    }
    if (strpos($category_lower, 'math') !== false || strpos($category_lower, 'operat') !== false) {
        return $category_map['mathematical operations'];
    }
    if (strpos($category_lower, 'suffi') !== false) {
        return $category_map['data sufficiency'];
    }
    if (strpos($category_lower, 'conclu') !== false) {
        return $category_map['statement and conclusions'];
    }
    if (strpos($category_lower, 'assum') !== false) {
        return $category_map['statement and assumptions'];
    }
    if (strpos($category_lower, 'course') !== false || strpos($category_lower, 'action') !== false) {
        return $category_map['course of action'];
    }
    if (strpos($category_lower, 'cause') !== false || strpos($category_lower, 'effect') !== false) {
        return $category_map['cause and effect'];
    }
    if (strpos($category_lower, 'argu') !== false) {
        return $category_map['statement and arguments'];
    }
    if (strpos($category_lower, 'deduc') !== false) {
        return $category_map['logical deduction'];
    }
    if (strpos($category_lower, 'aptitude') !== false) {
        return $category_map['aptitude'];
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
        
        // Get files based on category type
        if ($category_dir === 'general-english') {
            // For general-english, get JSON files (excluding categories_summary.json)
            $files = glob($category_path . '/*.json');
            $files = array_filter($files, function($file) {
                return basename($file) !== 'categories_summary.json';
            });
            error_log("DEBUG: Found " . count($files) . " JSON files in category: " . $category_name);
        } else {
            // For other categories, get TXT files
            $files = glob($category_path . '/*.txt');
            error_log("DEBUG: Found " . count($files) . " TXT files in category: " . $category_name);
        }
        
        if (empty($files)) {
            error_log("WARNING: No files found in category: " . $category_name);
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
        } // End of foreach ($question_blocks_raw as $block_index => $block_text)
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


// Update daily performance after quiz completion
function updateDailyPerformance($user_id, $score, $total_questions, $category) {
    global $pdo;
    
    try {
        $today = date('Y-m-d');
        $accuracy = ($score / $total_questions) * 100;
        
        // Check if record exists for today
        if (!$pdo) {
            throw new Exception("Database connection not established");
        }
        $stmt = $pdo->prepare("SELECT * FROM daily_performance WHERE user_id = ? AND date = ?");
        $stmt->execute([$user_id, $today]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing && is_array($existing)) {
            // Update existing record
            $new_quizzes_taken = $existing['quizzes_taken'] + 1;
            $new_total_questions = $existing['total_questions'] + $total_questions;
            $new_correct_answers = $existing['correct_answers'] + $score;
            $new_accuracy = ($new_correct_answers / $new_total_questions) * 100;
            
            if (!$pdo) {
                throw new Exception("Database connection not established");
            }
            $stmt = $pdo->prepare("
                UPDATE daily_performance 
                SET quizzes_taken = ?, total_questions = ?, correct_answers = ?, 
                    accuracy_percentage = ? 
                WHERE user_id = ? AND date = ?
            ");
            $stmt->execute([$new_quizzes_taken, $new_total_questions, $new_correct_answers, 
                           $new_accuracy, $user_id, $today]);
        } else {
            // Create new record
            if (!$pdo) {
                throw new Exception("Database connection not established");
            }
            $stmt = $pdo->prepare("
                INSERT INTO daily_performance 
                (user_id, date, quizzes_taken, total_questions, correct_answers, accuracy_percentage) 
                VALUES (?, ?, 1, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $today, $total_questions, $score, $accuracy]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating daily performance: " . $e->getMessage());
        return false;
    }
}

// Update category performance after quiz completion
function updateCategoryPerformance($user_id, $score, $total_questions, $category) {
    global $pdo;
    
    try {
        // Check if record exists for this user and category
        if (!$pdo) {
            throw new Exception("Database connection not established");
        }
        $stmt = $pdo->prepare("SELECT * FROM category_performance WHERE user_id = ? AND category_name = ?");
        $stmt->execute([$user_id, $category]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing && is_array($existing)) {
            // Update existing record
            $new_quizzes_taken = $existing['quizzes_taken'] + 1;
            $new_total_questions = $existing['total_questions'] + $total_questions;
            $new_correct_answers = $existing['correct_answers'] + $score;
            $new_accuracy = ($new_correct_answers / $new_total_questions) * 100;
            
            // Update best score if current score is better
            $current_percentage = ($score / $total_questions) * 100;
            $new_best_score = max($existing['best_score'], $current_percentage);
            
            if (!$pdo) {
                throw new Exception("Database connection not established");
            }
            $stmt = $pdo->prepare("
                UPDATE category_performance 
                SET quizzes_taken = ?, total_questions = ?, correct_answers = ?, 
                    accuracy_percentage = ?, best_score = ?, last_attempt_date = NOW() 
                WHERE user_id = ? AND category_name = ?
            ");
            $stmt->execute([$new_quizzes_taken, $new_total_questions, $new_correct_answers, 
                           $new_accuracy, $new_best_score, $user_id, $category]);
        } else {
            // Create new record
            $accuracy = ($score / $total_questions) * 100;
            if (!$pdo) {
                throw new Exception("Database connection not established");
            }
            $stmt = $pdo->prepare("
                INSERT INTO category_performance 
                (user_id, category_name, quizzes_taken, total_questions, correct_answers, 
                 accuracy_percentage, best_score, last_attempt_date) 
                VALUES (?, ?, 1, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $category, $total_questions, $score, $accuracy, $accuracy]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating category performance: " . $e->getMessage());
        return false;
    }
}

// Master function to update all statistics after quiz completion
function updateAllStatistics($user_id, $score, $total_questions, $category) {
    $results = [
        'user_statistics' => updateUserStatistics($user_id, $score, $total_questions, $category),
        'daily_performance' => updateDailyPerformance($user_id, $score, $total_questions, $category),
        'category_performance' => updateCategoryPerformance($user_id, $score, $total_questions, $category)
    ];
    
    // Log any failures
    foreach ($results as $type => $success) {
        if (!$success) {
            error_log("Failed to update $type for user $user_id");
        }
    }
    
    // Return true if all updates succeeded
    return array_reduce($results, function($carry, $item) {
        return $carry && $item;
    }, true);
}

// Performance Analytics Helper Functions

/**
 * Calculate performance trends for a user (without stored procedures)
 */
function calculatePerformanceTrends($user_id, $days_back = 30) {
    try {
        $pdo = getConnection();
        
        // Calculate daily trends for the specified period
        $start_date = date('Y-m-d', strtotime("-$days_back days"));
        $end_date = date('Y-m-d');
        
        // Get daily performance data
        $stmt = $pdo->prepare("
            SELECT 
                DATE(completed_at) as date,
                AVG(accuracy) as avg_accuracy,
                COUNT(*) as quiz_count
            FROM quiz_attempts 
            WHERE user_id = ? 
            AND DATE(completed_at) BETWEEN ? AND ?
            GROUP BY DATE(completed_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$user_id, $start_date, $end_date]);
        $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate trends and insert/update performance_trends table
        $prev_accuracy = null;
        foreach ($daily_data as $day) {
            $trend_value = 0;
            if ($prev_accuracy !== null) {
                $trend_value = $day['avg_accuracy'] - $prev_accuracy;
            }
            
            // Insert or update trend data
            $stmt = $pdo->prepare("
                INSERT INTO performance_trends (user_id, trend_period, period_start, period_end, avg_accuracy, accuracy_trend, calculated_at)
                VALUES (?, 'daily', ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    avg_accuracy = VALUES(avg_accuracy),
                    accuracy_trend = VALUES(accuracy_trend),
                    calculated_at = NOW()
            ");
            $stmt->execute([$user_id, $day['date'], $day['date'], $day['avg_accuracy'], $trend_value]);
            
            $prev_accuracy = $day['avg_accuracy'];
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error calculating performance trends: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user statistics after quiz completion (without stored procedures)
 */
function updateUserStatistics($user_id) {
    try {
        $pdo = getConnection();
        
        // Calculate totals from quiz_attempts
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_quizzes,
                SUM(total_questions) as total_questions_answered,
                SUM(correct_answers) as total_correct_answers,
                ROUND(AVG(accuracy), 2) as average_accuracy,
                MAX(accuracy) as best_accuracy,
                SUM(time_taken) as total_time_spent,
                MAX(DATE(completed_at)) as last_quiz_date
            FROM quiz_attempts 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stats || $stats['total_quizzes'] == 0) {
            return false;
        }
        
        // Calculate current streak (consecutive days with quizzes)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as streak_count
            FROM (
                SELECT DATE(completed_at) as quiz_date
                FROM quiz_attempts 
                WHERE user_id = ? 
                AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(completed_at)
                ORDER BY quiz_date DESC
            ) consecutive_days
        ");
        $stmt->execute([$user_id]);
        $streak_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_streak = $streak_data['streak_count'] ?? 0;
        
        // Insert or update user statistics
        $stmt = $pdo->prepare("
            INSERT INTO user_statistics (
                user_id, total_quizzes, total_questions_answered, total_correct_answers,
                average_accuracy, best_accuracy, total_time_spent, current_streak, 
                longest_streak, last_quiz_date, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                total_quizzes = VALUES(total_quizzes),
                total_questions_answered = VALUES(total_questions_answered),
                total_correct_answers = VALUES(total_correct_answers),
                average_accuracy = VALUES(average_accuracy),
                best_accuracy = GREATEST(best_accuracy, VALUES(best_accuracy)),
                total_time_spent = VALUES(total_time_spent),
                current_streak = VALUES(current_streak),
                longest_streak = GREATEST(longest_streak, VALUES(current_streak)),
                last_quiz_date = VALUES(last_quiz_date),
                updated_at = NOW()
        ");
        
        $stmt->execute([
            $user_id,
            $stats['total_quizzes'],
            $stats['total_questions_answered'] ?? 0,
            $stats['total_correct_answers'] ?? 0,
            $stats['average_accuracy'] ?? 0,
            $stats['best_accuracy'] ?? 0,
            $stats['total_time_spent'] ?? 0,
            $current_streak,
            $current_streak, // Will be compared with existing longest_streak
            $stats['last_quiz_date']
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating user statistics: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user performance summary (using cache table)
 */
function getUserPerformanceSummary($user_id) {
    try {
        $pdo = getConnection();
        
        // First try to get from cache
        $stmt = $pdo->prepare("SELECT * FROM user_performance_cache WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If cache is recent (less than 1 hour old), return it
        if ($cached && strtotime($cached['cache_updated_at']) > (time() - 3600)) {
            return $cached;
        }
        
        // Otherwise, calculate fresh data
        $stmt = $pdo->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                u.full_name,
                COALESCE(COUNT(qa.id), 0) as total_quizzes,
                COALESCE(ROUND(AVG(qa.accuracy), 2), 0) as average_accuracy,
                COALESCE(MAX(qa.accuracy), 0) as best_accuracy,
                MAX(DATE(qa.completed_at)) as last_quiz_date
            FROM users u
            LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
            WHERE u.id = ?
            GROUP BY u.id, u.username, u.full_name
        ");
        $stmt->execute([$user_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$summary) {
            return null;
        }
        
        // Get categories attempted
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT qr.category) as categories_attempted
            FROM quiz_attempts qa 
            JOIN quiz_responses qr ON qa.id = qr.attempt_id 
            WHERE qa.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $categories = $stmt->fetch(PDO::FETCH_ASSOC);
        $summary['categories_attempted'] = $categories['categories_attempted'] ?? 0;
        
        // Get best category
        $stmt = $pdo->prepare("
            SELECT qr.category 
            FROM quiz_attempts qa 
            JOIN quiz_responses qr ON qa.id = qr.attempt_id 
            WHERE qa.user_id = ? 
            GROUP BY qr.category 
            ORDER BY AVG(CASE WHEN qr.is_correct THEN 100 ELSE 0 END) DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $best_cat = $stmt->fetch(PDO::FETCH_ASSOC);
        $summary['best_category'] = $best_cat['category'] ?? 'N/A';
        
        // Get recent accuracy
        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(accuracy), 0) as last_7_days_accuracy
            FROM quiz_attempts 
            WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$user_id]);
        $recent = $stmt->fetch(PDO::FETCH_ASSOC);
        $summary['last_7_days_accuracy'] = $recent['last_7_days_accuracy'] ?? 0;
        
        // Update cache
        $stmt = $pdo->prepare("
            INSERT INTO user_performance_cache (
                user_id, total_quizzes, average_accuracy, best_accuracy, 
                categories_attempted, best_category, last_7_days_accuracy, last_quiz_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_quizzes = VALUES(total_quizzes),
                average_accuracy = VALUES(average_accuracy),
                best_accuracy = VALUES(best_accuracy),
                categories_attempted = VALUES(categories_attempted),
                best_category = VALUES(best_category),
                last_7_days_accuracy = VALUES(last_7_days_accuracy),
                last_quiz_date = VALUES(last_quiz_date),
                cache_updated_at = NOW()
        ");
        $stmt->execute([
            $user_id, $summary['total_quizzes'], $summary['average_accuracy'], 
            $summary['best_accuracy'], $summary['categories_attempted'], 
            $summary['best_category'], $summary['last_7_days_accuracy'], 
            $summary['last_quiz_date']
        ]);
        
        return $summary;
        
    } catch (Exception $e) {
        error_log("Error getting user performance summary: " . $e->getMessage());
        return null;
    }
}

/**
 * Get category performance for a user (using cache table)
 */
function getCategoryPerformance($user_id, $limit = null) {
    try {
        $pdo = getConnection();
        
        // Try to get from cache first
        $sql = "SELECT * FROM category_performance_cache WHERE user_id = ? ORDER BY accuracy DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $cached = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we have cached data that's recent, return it
        if (!empty($cached)) {
            $latest_cache = max(array_column($cached, 'cache_updated_at'));
            if (strtotime($latest_cache) > (time() - 3600)) { // 1 hour cache
                return $cached;
            }
        }
        
        // Otherwise, calculate fresh data
        $sql = "
            SELECT 
                qa.user_id,
                qr.category,
                COUNT(*) as total_questions,
                SUM(CASE WHEN qr.is_correct THEN 1 ELSE 0 END) as correct_answers,
                ROUND(AVG(CASE WHEN qr.is_correct THEN 100 ELSE 0 END), 2) as accuracy,
                AVG(qr.time_spent) as avg_time_per_question,
                MAX(qa.completed_at) as last_attempted,
                COUNT(DISTINCT qa.id) as quiz_attempts
            FROM quiz_attempts qa
            JOIN quiz_responses qr ON qa.id = qr.attempt_id
            WHERE qa.user_id = ?
            GROUP BY qa.user_id, qr.category
            ORDER BY accuracy DESC
        ";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update cache for each category
        foreach ($performance as $cat) {
            $strength_level = 'Needs Improvement';
            if ($cat['accuracy'] >= 80) $strength_level = 'Strong';
            elseif ($cat['accuracy'] >= 60) $strength_level = 'Average';
            
            $stmt = $pdo->prepare("
                INSERT INTO category_performance_cache (
                    user_id, category, total_questions, correct_answers, accuracy,
                    avg_time_per_question, quiz_attempts, last_attempted, strength_level
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_questions = VALUES(total_questions),
                    correct_answers = VALUES(correct_answers),
                    accuracy = VALUES(accuracy),
                    avg_time_per_question = VALUES(avg_time_per_question),
                    quiz_attempts = VALUES(quiz_attempts),
                    last_attempted = VALUES(last_attempted),
                    strength_level = VALUES(strength_level),
                    cache_updated_at = NOW()
            ");
            $stmt->execute([
                $user_id, $cat['category'], $cat['total_questions'], $cat['correct_answers'],
                $cat['accuracy'], $cat['avg_time_per_question'], $cat['quiz_attempts'],
                $cat['last_attempted'], $strength_level
            ]);
        }
        
        return $performance;
        
    } catch (Exception $e) {
        error_log("Error getting category performance: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent performance trends (using cache table)
 */
function getRecentPerformanceTrends($user_id, $days = 30) {
    try {
        $pdo = getConnection();
        
        // Get from daily performance cache
        $stmt = $pdo->prepare("
            SELECT * FROM daily_performance_cache 
            WHERE user_id = ? AND quiz_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY quiz_date DESC
        ");
        $stmt->execute([$user_id, $days]);
        $cached = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If cache is empty or old, refresh it
        if (empty($cached)) {
            refreshDailyPerformanceCache($user_id, $days);
            
            // Try again
            $stmt->execute([$user_id, $days]);
            $cached = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $cached;
        
    } catch (Exception $e) {
        error_log("Error getting recent performance trends: " . $e->getMessage());
        return [];
    }
}

/**
 * Refresh daily performance cache
 */
function refreshDailyPerformanceCache($user_id, $days = 90) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO daily_performance_cache (
                user_id, quiz_date, quizzes_taken, total_questions, correct_answers,
                avg_accuracy, total_time_spent, best_accuracy
            )
            SELECT 
                user_id,
                DATE(completed_at) as quiz_date,
                COUNT(*) as quizzes_taken,
                SUM(total_questions) as total_questions,
                SUM(correct_answers) as correct_answers,
                ROUND(AVG(accuracy), 2) as avg_accuracy,
                SUM(time_taken) as total_time_spent,
                MAX(accuracy) as best_accuracy
            FROM quiz_attempts
            WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY user_id, DATE(completed_at)
            ON DUPLICATE KEY UPDATE
                quizzes_taken = VALUES(quizzes_taken),
                total_questions = VALUES(total_questions),
                correct_answers = VALUES(correct_answers),
                avg_accuracy = VALUES(avg_accuracy),
                total_time_spent = VALUES(total_time_spent),
                best_accuracy = VALUES(best_accuracy),
                cache_updated_at = NOW()
        ");
        $stmt->execute([$user_id, $days]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error refreshing daily performance cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced quiz completion tracking with performance analytics
 */
function recordQuizCompletionWithAnalytics($user_id, $quiz_title, $quiz_type, $total_questions, $correct_answers, $accuracy, $time_taken, $questions_data = []) {
    try {
        $pdo = getConnection();
        
        // Record the quiz attempt
        $stmt = $pdo->prepare("
            INSERT INTO quiz_attempts (user_id, quiz_title, quiz_type, total_questions, correct_answers, score, max_score, accuracy, time_taken, started_at, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? SECOND, NOW())
        ");
        
        $score = $correct_answers * 2; // 2 points per correct answer
        $max_score = $total_questions * 2;
        
        $stmt->execute([
            $user_id, $quiz_title, $quiz_type, $total_questions, 
            $correct_answers, $score, $max_score, $accuracy, $time_taken, $time_taken
        ]);
        
        $quiz_attempt_id = $pdo->lastInsertId();
        
        // Record individual question responses if provided
        if (!empty($questions_data)) {
            $stmt = $pdo->prepare("
                INSERT INTO quiz_responses (attempt_id, question_number, question_text, user_answer, correct_answer, is_correct, category, subcategory)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($questions_data as $index => $question) {
                $stmt->execute([
                    $quiz_attempt_id,
                    $index + 1,
                    $question['question'],
                    $question['user_answer'] ?? '',
                    $question['correct_answer'],
                    $question['is_correct'] ? 1 : 0,
                    $question['category'] ?? '',
                    $question['subcategory'] ?? ''
                ]);
            }
        }
        
        return $quiz_attempt_id;
        
    } catch (Exception $e) {
        error_log("Error recording quiz completion: " . $e->getMessage());
        return false;
    }
}

