#!/bin/bash

# MPSC Quiz Portal - Deployment Package Creator
# This script creates a zip file with all updated files for deployment

echo "=== MPSC Quiz Portal Deployment Package Creator ==="
echo "Creating deployment package..."

# Create deployment directory
DEPLOY_DIR="mpsc_quiz_deployment_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$DEPLOY_DIR"

echo "✓ Created deployment directory: $DEPLOY_DIR"

# Copy updated core files
echo "📁 Copying core application files..."
cp quiz.php "$DEPLOY_DIR/"
cp includes/functions.php "$DEPLOY_DIR/functions.php"
cp database_migration.php "$DEPLOY_DIR/"

# Copy all JSON data files
echo "📁 Copying JSON data files..."
mkdir -p "$DEPLOY_DIR/TestQnA/general-english"
cp -r TestQnA/general-english/*.json "$DEPLOY_DIR/TestQnA/general-english/"

# Create deployment instructions
cat > "$DEPLOY_DIR/DEPLOYMENT_INSTRUCTIONS.md" << 'EOF'
# MPSC Quiz Portal - Deployment Instructions

## Overview
This package contains updates for the MPSC Quiz Portal with new JSON-based general-english data structure and improved quiz functionality.

## What's New
- ✅ JSON-based general-english question data (replaces old TXT files)
- ✅ Specific category display (shows "Grammar - Active Voice" instead of "General")
- ✅ Auto-advance quiz functionality (1-second delay after answer selection)
- ✅ Improved error handling and performance
- ✅ Mixed file format support (JSON for general-english, TXT for other categories)

## Files Included
- `quiz.php` - Updated quiz interface with auto-advance
- `functions.php` - Updated data loading functions for JSON/TXT support
- `database_migration.php` - Database schema updates
- `TestQnA/general-english/*.json` - New JSON question data files

## Deployment Steps

### 1. Backup Current System
```bash
# Backup your current files
cp quiz.php quiz.php.backup
cp includes/functions.php includes/functions.php.backup
tar -czf testqna_backup.tar.gz TestQnA/general-english/
```

### 2. Upload New Files
```bash
# Upload the new files to your server
# Replace the following files:
- quiz.php
- includes/functions.php

# Upload new directory structure:
- TestQnA/general-english/ (all JSON files)
```

### 3. Run Database Migration
```bash
# On your server, run the database migration:
php database_migration.php
```

### 4. Set Permissions
```bash
# Ensure proper permissions
chmod 644 quiz.php includes/functions.php
chmod 644 TestQnA/general-english/*.json
chmod +x database_migration.php
```

### 5. Test the System
1. Visit your quiz portal
2. Start a "Mixed English" quiz
3. Verify categories show specific names (e.g., "Vocabulary - Synonyms")
4. Verify auto-advance works (1-second delay after selecting answer)
5. Complete a full quiz to test database integration

## Rollback Instructions
If you need to rollback:
```bash
# Restore backup files
cp quiz.php.backup quiz.php
cp includes/functions.php.backup includes/functions.php
tar -xzf testqna_backup.tar.gz
```

## Support
- Check browser console for JavaScript errors
- Check PHP error logs for server-side issues
- Verify file permissions and paths
- Ensure database connection is working

## Technical Notes
- JSON files are loaded for general-english category only
- TXT files continue to work for general-knowledge and aptitude
- Database schema is backward compatible
- Auto-advance can be adjusted in quiz.php (setTimeout value)

EOF

# Create a simple verification script
cat > "$DEPLOY_DIR/verify_deployment.php" << 'EOF'
<?php
/**
 * Deployment Verification Script
 * Run this after deployment to verify everything is working
 */

echo "=== MPSC Quiz Portal Deployment Verification ===\n";

// Check if files exist
$requiredFiles = [
    'quiz.php',
    'includes/functions.php',
    'TestQnA/general-english/vocabulary_synonyms.json',
    'TestQnA/general-english/categories_summary.json'
];

echo "Checking required files...\n";
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file MISSING\n";
    }
}

// Test JSON loading
echo "\nTesting JSON data loading...\n";
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
    
    $subcategories = get_testqna_subcategories('general-english');
    if (!empty($subcategories)) {
        echo "✓ JSON subcategories loaded: " . count($subcategories) . " found\n";
        echo "  Sample: " . $subcategories[0]['name'] . "\n";
    } else {
        echo "✗ Failed to load JSON subcategories\n";
    }
    
    $questions = load_questions_from_testqna('general-english', null, 3);
    if (!empty($questions)) {
        echo "✓ JSON questions loaded: " . count($questions) . " sample questions\n";
        echo "  Sample category: " . $questions[0]['category'] . "\n";
    } else {
        echo "✗ Failed to load JSON questions\n";
    }
} else {
    echo "✗ Cannot test - functions.php not found\n";
}

// Test database connection
echo "\nTesting database connection...\n";
if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        $pdo = new PDO($dsn, $username, $password, $options);
        echo "✓ Database connection successful\n";
        
        // Check if migration was run
        $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT * FROM migrations WHERE migration_name = 'json_general_english_migration'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Database migration completed\n";
            } else {
                echo "⚠ Database migration not found - run database_migration.php\n";
            }
        } else {
            echo "⚠ Migrations table not found - run database_migration.php\n";
        }
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ Database config not found - cannot test connection\n";
}

echo "\n=== Verification Complete ===\n";
echo "If all items show ✓, your deployment is successful!\n";
?>
EOF

# Create file list
echo "📋 Creating file manifest..."
find "$DEPLOY_DIR" -type f > "$DEPLOY_DIR/FILE_MANIFEST.txt"

# Create the zip file
ZIP_NAME="${DEPLOY_DIR}.zip"
echo "📦 Creating zip package..."
zip -r "$ZIP_NAME" "$DEPLOY_DIR"

# Clean up temporary directory
rm -rf "$DEPLOY_DIR"

echo ""
echo "✅ Deployment package created successfully!"
echo "📦 Package: $ZIP_NAME"
echo "📊 Package size: $(du -h "$ZIP_NAME" | cut -f1)"
echo ""
echo "🚀 Ready for deployment!"
echo ""
echo "Next steps:"
echo "1. Upload $ZIP_NAME to your server"
echo "2. Extract the files"
echo "3. Follow DEPLOYMENT_INSTRUCTIONS.md"
echo "4. Run database_migration.php"
echo "5. Run verify_deployment.php to test"
echo ""
