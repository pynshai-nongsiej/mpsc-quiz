<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';

// Logout the user with secure cleanup
try {
    destroySession();
} catch (Exception $e) {
    // Log error securely
    error_log("Logout error: " . $e->getMessage());
}

// Redirect to home page
header('Location: index.php');
exit();
?>