<?php
// Site Configuration
define('BASE_URL', 'https://mpsc-quiz.rf.gd/');
define('SITE_NAME', 'MPSC Quiz Site');
define('SITE_DESCRIPTION', 'Meghalaya Public Service Commission Quiz Platform');

// Database Configuration
define('DB_HOST', 'sql308.infinityfree.com');
define('DB_NAME', 'if0_39478438_mpsc_quiz_portal');
define('DB_USER', 'if0_39478438');
define('DB_PASS', 'DariDaling1');
define('DB_CHARSET', 'utf8mb4');

// File Paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Security Settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Quiz Settings
define('QUIZ_TIME_LIMIT', 1800); // 30 minutes
define('QUESTIONS_PER_QUIZ', 10);
define('PASSING_SCORE', 60); // percentage
define('MAX_QUIZ_ATTEMPTS', 3);

// Pagination
define('ITEMS_PER_PAGE', 10);
define('QUIZ_HISTORY_LIMIT', 20);

// Email Settings (if needed)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@mpscquiz.com');
define('FROM_NAME', 'MPSC Quiz Site');

// Image/File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('PROFILE_IMAGE_PATH', UPLOADS_PATH . '/profiles');

// Application Settings
define('TIMEZONE', 'Asia/Kolkata');
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y H:i');

// Debug Settings
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', ROOT_PATH . '/logs/error.log');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Create necessary directories if they don't exist
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}
if (!file_exists(PROFILE_IMAGE_PATH)) {
    mkdir(PROFILE_IMAGE_PATH, 0755, true);
}
if (!file_exists(dirname(ERROR_LOG_PATH))) {
    mkdir(dirname(ERROR_LOG_PATH), 0755, true);
}
?>