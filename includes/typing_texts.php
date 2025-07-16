<?php
function get_random_typing_text($difficulty = 'letters_only') {
    $base_dir = __DIR__ . '/../typing_test/' . $difficulty;
    
    // Get all text files from the directory
    $files = glob($base_dir . '/*.txt');
    
    if (empty($files)) {
        return "The quick brown fox jumps over the lazy dog."; // Fallback text
    }
    
    // Select a random file
    $random_file = $files[array_rand($files)];
    
    // Read and return the file content
    $content = file_get_contents($random_file);
    
    // Clean up the content (remove extra spaces, newlines, etc.)
    $content = preg_replace('/\s+/', ' ', $content); // Replace multiple spaces with single space
    $content = trim($content);
    
    return $content;
}

// Get a random text for the typing test
$typing_text = get_random_typing_text('letters_only'); // Default to letters_only difficulty
?>
