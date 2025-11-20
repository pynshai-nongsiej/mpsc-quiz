<?php
/**
 * Database Migration Script for MPSC Quiz Portal
 * Run this script on your deployment server to update the database schema
 * for the new JSON-based general-english data structure
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MPSC Quiz Portal Database Migration ===\n";
echo "Starting migration at: " . date('Y-m-d H:i:s') . "\n\n";

// Initialize database variables
$pdo = null;
$dsn = null;
$username = null;
$password = null;
$options = [];

try {
    // Try different database configuration approaches
    if (file_exists('config/config.php')) {
        echo "Loading database config from config/config.php...\n";
        require_once 'config/config.php';
        
        // Use the defined constants from config.php
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;
        $charset = DB_CHARSET;
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        echo "✓ Using database: $dbname on $host\n";
        
    } elseif (file_exists('config/database.php')) {
        echo "Loading database config from config/database.php...\n";
        require_once 'config/database.php';
    } elseif (file_exists('includes/db_config.php')) {
        echo "Loading database config from includes/db_config.php...\n";
        require_once 'includes/db_config.php';
    } else {
        // Fallback manual configuration for InfinityFree
        echo "No config file found, using InfinityFree configuration...\n";
        
        $host = 'sql308.infinityfree.com';
        $dbname = 'if0_39478438_mpsc_quiz_portal';
        $username = 'if0_39478438';
        $password = 'DariDaling1';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        echo "✓ Using fallback InfinityFree configuration\n";
    }
    
    // Validate required variables
    if (empty($dsn) || empty($username)) {
        throw new Exception("Database configuration incomplete. DSN: $dsn, Username: $username");
    }
    
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✓ Database connection established\n";

    // Note: DDL operations (CREATE, ALTER) auto-commit in MySQL
    // We'll handle transactions per operation type
    echo "✓ Ready to run migrations\n";

    // Migration 1: Update quiz_responses table to handle new category structure
    echo "\n--- Migration 1: Update quiz_responses table ---\n";
    
    // Check if category column needs to be expanded
    $stmt = $pdo->query("DESCRIBE quiz_responses category");
    $categoryColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($categoryColumn && strpos($categoryColumn['Type'], 'varchar(50)') !== false) {
        $pdo->exec("ALTER TABLE quiz_responses MODIFY COLUMN category VARCHAR(100)");
        echo "✓ Extended category column to VARCHAR(100)\n";
    } else {
        echo "✓ Category column already properly sized\n";
    }

    // Migration 2: Add index for better performance on category queries
    echo "\n--- Migration 2: Add performance indexes ---\n";
    
    try {
        $pdo->exec("CREATE INDEX idx_quiz_responses_category ON quiz_responses(category)");
        echo "✓ Added index on quiz_responses.category\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ Index on category already exists\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("CREATE INDEX idx_quiz_responses_subcategory ON quiz_responses(subcategory)");
        echo "✓ Added index on quiz_responses.subcategory\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ Index on subcategory already exists\n";
        } else {
            throw $e;
        }
    }

    // Migration 3: Update category performance tracking
    echo "\n--- Migration 3: Update category performance tracking ---\n";
    
    // Check if category_performance table exists and update it
    $stmt = $pdo->query("SHOW TABLES LIKE 'category_performance'");
    if ($stmt->rowCount() > 0) {
        // Update existing records to use new category names
        $categoryMappings = [
            'general-english' => [
                'Grammar - Active & Passive Voice',
                'Grammar - Direct & Indirect Speech', 
                'Grammar - Error Detection & Correction',
                'Grammar - Parts of Speech',
                'Grammar - Prepositions & Fill in the Blanks',
                'Grammar - Question Tags',
                'Grammar - Sentence Arrangement',
                'Grammar - Sentence Structure',
                'Grammar - Tenses & Verb Forms',
                'Reading Comprehension - Cloze Test',
                'Vocabulary - Analogies',
                'Vocabulary - Antonyms',
                'Vocabulary - Idioms & Phrases',
                'Vocabulary - One Word Substitution',
                'Vocabulary - Spelling',
                'Vocabulary - Synonyms'
            ]
        ];

        // Extend category column in category_performance if needed
        $stmt = $pdo->query("DESCRIBE category_performance category_name");
        $catPerfColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($catPerfColumn && strpos($catPerfColumn['Type'], 'varchar(50)') !== false) {
            $pdo->exec("ALTER TABLE category_performance MODIFY COLUMN category_name VARCHAR(100)");
            echo "✓ Extended category_performance.category_name to VARCHAR(100)\n";
        }

        echo "✓ Category performance table updated\n";
    } else {
        echo "✓ Category performance table not found (will be created automatically)\n";
    }

    // Migration 4: Clean up old data references
    echo "\n--- Migration 4: Data cleanup ---\n";
    
    // Update any existing quiz responses that might have old category names
    $updateQueries = [
        "UPDATE quiz_responses SET category = 'Vocabulary - Synonyms' WHERE category = 'Synonyms' OR category = 'synonyms'",
        "UPDATE quiz_responses SET category = 'Vocabulary - Antonyms' WHERE category = 'Antonyms' OR category = 'antonyms'",
        "UPDATE quiz_responses SET category = 'Grammar - Error Detection & Correction' WHERE category = 'Error Spotting' OR category = 'error-spotting'",
        "UPDATE quiz_responses SET category = 'Vocabulary - Idioms & Phrases' WHERE category = 'Idioms and Phrases' OR category = 'idioms-and-phrases'",
        "UPDATE quiz_responses SET category = 'Vocabulary - One Word Substitution' WHERE category = 'One Word Substitutes' OR category = 'one-word-substitutes'"
    ];

    foreach ($updateQueries as $query) {
        $stmt = $pdo->prepare($query);
        $affected = $stmt->execute();
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
            echo "✓ Updated $rowCount records: " . substr($query, 0, 50) . "...\n";
        }
    }

    // Migration 5: Add migration tracking
    echo "\n--- Migration 5: Migration tracking ---\n";
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            version VARCHAR(50) DEFAULT '1.0'
        )");
        echo "✓ Created migrations tracking table\n";
    } catch (PDOException $e) {
        echo "✓ Migrations table already exists\n";
    }

    // Record this migration
    $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (migration_name, version) VALUES (?, ?)");
    $stmt->execute(['json_general_english_migration', '2.0']);
    echo "✓ Recorded migration in tracking table\n";

    echo "\n✓ All migrations completed successfully!\n";

    // Migration summary
    echo "\n=== Migration Summary ===\n";
    echo "• Updated quiz_responses table schema\n";
    echo "• Added performance indexes\n";
    echo "• Updated category performance tracking\n";
    echo "• Cleaned up old category references\n";
    echo "• Added migration tracking\n";
    echo "\nMigration completed at: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
        echo "\n✗ Transaction rolled back due to error\n";
    }
    
    echo "\n✗ Migration failed with error:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Migration Complete ===\n";
echo "Your database is now ready for the new JSON-based general-english data!\n";
?>
