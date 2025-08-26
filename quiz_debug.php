<?php
// Comprehensive Quiz Diagnostic Script
// This script tests all components related to quiz saving functionality

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>\n<html lang='en'>\n<head>\n    <meta charset='UTF-8'>\n    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n    <title>Quiz Debug Report</title>\n    <style>\n        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }\n        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }\n        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }\n        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }\n        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }\n        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }\n        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }\n        h3 { color: #555; }\n        .status { font-weight: bold; padding: 2px 8px; border-radius: 3px; }\n        .pass { background: #28a745; color: white; }\n        .fail { background: #dc3545; color: white; }\n        .warn { background: #ffc107; color: #212529; }\n    </style>\n</head>\n<body>\n    <div class='container'>\n        <h1>🔍 Quiz System Diagnostic Report</h1>\n        <p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Test 1: Include required files
echo "<div class='test-section'>\n<h2>1. File Inclusion Test</h2>\n";

try {
    require_once __DIR__ . '/config/config.php';
    echo "<p><span class='status pass'>PASS</span> config.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p><span class='status fail'>FAIL</span> config.php: " . $e->getMessage() . "</p>";
}

try {
    require_once __DIR__ . '/config/database.php';
    echo "<p><span class='status pass'>PASS</span> database.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p><span class='status fail'>FAIL</span> database.php: " . $e->getMessage() . "</p>";
}

try {
    require_once __DIR__ . '/config/session.php';
    echo "<p><span class='status pass'>PASS</span> session.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p><span class='status fail'>FAIL</span> session.php: " . $e->getMessage() . "</p>";
}

try {
    require_once __DIR__ . '/includes/functions.php';
    echo "<p><span class='status pass'>PASS</span> functions.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p><span class='status fail'>FAIL</span> functions.php: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 2: Database Connection
echo "<div class='test-section'>\n<h2>2. Database Connection Test</h2>\n";

try {
    $pdo = getConnection();
    if ($pdo) {
        echo "<p><span class='status pass'>PASS</span> PDO connection established successfully</p>";
        
        // Test connection
        $stmt = $pdo->query('SELECT 1');
        if ($stmt) {
            echo "<p><span class='status pass'>PASS</span> Database connection is active</p>";
        } else {
            echo "<p><span class='status fail'>FAIL</span> Database query failed</p>";
        }
        
        // Show database info
        $stmt = $pdo->query('SELECT DATABASE() as db_name');
        if ($stmt) {
            $result = $stmt->fetch();
            echo "<p><span class='status pass'>INFO</span> Connected to database: " . ($result['db_name'] ?? 'Unknown') . "</p>";
        }
        
    } else {
        echo "<p><span class='status fail'>FAIL</span> Failed to establish database connection</p>";
    }
} catch (Exception $e) {
    echo "<p><span class='status fail'>FAIL</span> Database connection error: " . $e->getMessage() . "</p>";
    $pdo = null; // Set to null for subsequent tests
}

echo "</div>";

// Test 3: Table Existence and Structure
echo "<div class='test-section'>\n<h2>3. Database Tables Test</h2>\n";

$required_tables = ['quiz_attempts', 'quiz_responses', 'users', 'user_statistics', 'daily_performance', 'category_performance'];

if ($pdo) {
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt && $stmt->rowCount() > 0) {
                echo "<p><span class='status pass'>PASS</span> Table '$table' exists</p>";
                
                // Check table structure for critical tables
                if (in_array($table, ['quiz_attempts', 'quiz_responses'])) {
                    $stmt = $pdo->query("DESCRIBE $table");
                    if ($stmt) {
                        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        echo "<p><span class='status info'>INFO</span> $table columns: " . implode(', ', $columns) . "</p>";
                    }
                }
            } else {
                echo "<p><span class='status fail'>FAIL</span> Table '$table' does not exist</p>";
            }
        } catch (Exception $e) {
            echo "<p><span class='status fail'>FAIL</span> Error checking table '$table': " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p><span class='status fail'>SKIP</span> Cannot check tables - no database connection</p>";
}

echo "</div>";

// Test 4: Session Functionality
echo "<div class='test-section'>\n<h2>4. Session Test</h2>\n";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p><span class='status pass'>PASS</span> Session is active</p>";
    echo "<p><span class='status info'>INFO</span> Session ID: " . session_id() . "</p>";
    
    // Display current session variables
    if (!empty($_SESSION)) {
        echo "<h3>Current Session Variables:</h3>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } else {
        echo "<p><span class='status warn'>WARN</span> No session variables found</p>";
    }
} else {
    echo "<p><span class='status fail'>FAIL</span> Session is not active</p>";
}

echo "</div>";

// Test 5: User Authentication
echo "<div class='test-section'>\n<h2>5. User Authentication Test</h2>\n";

if (isset($_SESSION['user_id'])) {
    echo "<p><span class='status pass'>PASS</span> User is logged in with ID: " . $_SESSION['user_id'] . "</p>";
    
    // Verify user exists in database
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "<p><span class='status pass'>PASS</span> User found in database: " . $user['username'] . " (" . $user['email'] . ")</p>";
            } else {
                echo "<p><span class='status fail'>FAIL</span> User ID " . $_SESSION['user_id'] . " not found in database</p>";
            }
        } catch (Exception $e) {
            echo "<p><span class='status fail'>FAIL</span> Error verifying user: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><span class='status warn'>SKIP</span> Cannot verify user - no database connection</p>";
    }
} else {
    echo "<p><span class='status warn'>WARN</span> No user logged in (user_id not in session)</p>";
}

echo "</div>";

// Test 6: insertRecord Function Test
echo "<div class='test-section'>\n<h2>6. insertRecord Function Test</h2>\n";

if (function_exists('insertRecord')) {
    echo "<p><span class='status pass'>PASS</span> insertRecord function exists</p>";
    
    // Test with a simple insert (we'll use a test table or create a temporary one)
    if ($pdo) {
        try {
            // Create a temporary test table
            $pdo->exec("CREATE TEMPORARY TABLE test_insert (id INT AUTO_INCREMENT PRIMARY KEY, test_data VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            
            $test_data = [
                'test_data' => 'Debug test - ' . date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $result = insertRecord('test_insert', $test_data);
            
            if ($result) {
                echo "<p><span class='status pass'>PASS</span> insertRecord function works correctly (returned ID: $result)</p>";
            } else {
                echo "<p><span class='status fail'>FAIL</span> insertRecord function returned false</p>";
            }
            
        } catch (Exception $e) {
            echo "<p><span class='status fail'>FAIL</span> insertRecord function error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><span class='status warn'>SKIP</span> Cannot test insertRecord - no database connection</p>";
    }
} else {
    echo "<p><span class='status fail'>FAIL</span> insertRecord function does not exist</p>";
}

echo "</div>";

// Test 7: Quiz Attempt Simulation
echo "<div class='test-section'>\n<h2>7. Quiz Attempt Save Simulation</h2>\n";

if (isset($_SESSION['user_id'])) {
    try {
        $test_quiz_data = [
            'user_id' => $_SESSION['user_id'],
            'quiz_type' => 'debug_test',
            'quiz_title' => 'Debug Test Quiz',
            'total_questions' => 5,
            'correct_answers' => 3,
            'score' => 6,
            'max_score' => 10,
            'accuracy' => 60.00,
            'time_taken' => 120,
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => date('Y-m-d H:i:s')
        ];
        
        echo "<h3>Attempting to save test quiz attempt...</h3>";
        echo "<pre>" . print_r($test_quiz_data, true) . "</pre>";
        
        $attempt_id = insertRecord('quiz_attempts', $test_quiz_data);
        
        if ($attempt_id) {
            echo "<p><span class='status pass'>PASS</span> Test quiz attempt saved successfully with ID: $attempt_id</p>";
            
            // Test quiz response save
            $test_response_data = [
                'attempt_id' => $attempt_id,
                'question_number' => 1,
                'question_text' => 'Debug test question',
                'user_answer' => 'a',
                'correct_answer' => 'b',
                'is_correct' => 0,
                'category' => 'Debug',
                'subcategory' => 'Test'
            ];
            
            $response_id = insertRecord('quiz_responses', $test_response_data);
            
            if ($response_id) {
                echo "<p><span class='status pass'>PASS</span> Test quiz response saved successfully with ID: $response_id</p>";
            } else {
                echo "<p><span class='status fail'>FAIL</span> Failed to save test quiz response</p>";
            }
            
        } else {
            echo "<p><span class='status fail'>FAIL</span> Failed to save test quiz attempt</p>";
        }
        
    } catch (Exception $e) {
        echo "<p><span class='status fail'>FAIL</span> Quiz attempt simulation error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><span class='status warn'>SKIP</span> Cannot test quiz saving without logged in user</p>";
}

echo "</div>";

// Test 8: Recent Quiz Attempts Check
echo "<div class='test-section'>\n<h2>8. Recent Quiz Attempts Check</h2>\n";

if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM quiz_attempts');
        $total = $stmt->fetch()['total'];
        echo "<p><span class='status info'>INFO</span> Total quiz attempts in database: $total</p>";
        
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare('SELECT COUNT(*) as user_total FROM quiz_attempts WHERE user_id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user_total = $stmt->fetch()['user_total'];
            echo "<p><span class='status info'>INFO</span> Quiz attempts for current user: $user_total</p>";
            
            // Show recent attempts
            $stmt = $pdo->prepare('SELECT * FROM quiz_attempts WHERE user_id = ? ORDER BY completed_at DESC LIMIT 5');
            $stmt->execute([$_SESSION['user_id']]);
            $recent = $stmt->fetchAll();
            
            if ($recent) {
                echo "<h3>Recent Quiz Attempts:</h3>";
                echo "<pre>" . print_r($recent, true) . "</pre>";
            } else {
                echo "<p><span class='status warn'>WARN</span> No recent quiz attempts found for current user</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p><span class='status fail'>FAIL</span> Error checking quiz attempts: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><span class='status warn'>SKIP</span> Cannot check quiz attempts - no database connection</p>";
}

echo "</div>";

// Test 9: PHP Configuration
echo "<div class='test-section'>\n<h2>9. PHP Configuration</h2>\n";

echo "<p><span class='status info'>INFO</span> PHP Version: " . phpversion() . "</p>";
echo "<p><span class='status info'>INFO</span> Error Reporting: " . error_reporting() . "</p>";
echo "<p><span class='status info'>INFO</span> Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
echo "<p><span class='status info'>INFO</span> Log Errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "</p>";
echo "<p><span class='status info'>INFO</span> Error Log: " . (ini_get('error_log') ?: 'Default') . "</p>";

echo "</div>";

// Test 10: updateAllStatistics Function Test
echo "<div class='test-section'>\n<h2>10. Statistics Update Function Test</h2>\n";

if (function_exists('updateAllStatistics')) {
    echo "<p><span class='status pass'>PASS</span> updateAllStatistics function exists</p>";
    
    if (isset($_SESSION['user_id'])) {
        try {
            $result = updateAllStatistics($_SESSION['user_id'], 3, 5, 'Debug');
            if ($result) {
                echo "<p><span class='status pass'>PASS</span> updateAllStatistics function executed successfully</p>";
            } else {
                echo "<p><span class='status fail'>FAIL</span> updateAllStatistics function returned false</p>";
            }
        } catch (Exception $e) {
            echo "<p><span class='status fail'>FAIL</span> updateAllStatistics function error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><span class='status warn'>SKIP</span> Cannot test statistics update without logged in user</p>";
    }
} else {
    echo "<p><span class='status fail'>FAIL</span> updateAllStatistics function does not exist</p>";
}

echo "</div>";

echo "\n        <div class='test-section info'>\n            <h2>🎯 Summary</h2>\n            <p>This diagnostic script has tested all major components of the quiz system.</p>\n            <p>Look for any <span class='status fail'>FAIL</span> or <span class='status warn'>WARN</span> statuses above to identify issues.</p>\n            <p>If database connection is failing, that's likely the root cause of quiz saving issues.</p>\n        </div>\n    </div>\n</body>\n</html>";
?>