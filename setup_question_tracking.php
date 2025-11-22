<?php
/**
 * Setup script for question tracking system
 * Run this once to create the necessary database tables
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    
    echo "Setting up question tracking system...\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database/question_tracking.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n🎉 Question tracking system setup complete!\n";
    echo "\nFeatures enabled:\n";
    echo "- Question repetition prevention\n";
    echo "- Automatic category reset when 95% complete\n";
    echo "- Performance statistics tracking\n";
    echo "- Reset notifications with stats\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up question tracking: " . $e->getMessage() . "\n";
    exit(1);
}
?>
