<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// Session configuration - must be set before session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    // Start session after configuration
    session_start();
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current admin ID
 * @return int|null
 */
function getCurrentAdminId() {
    return isAdminLoggedIn() ? (int)$_SESSION['admin_id'] : null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = getCurrentUserId();
    return fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
}

/**
 * Get current admin data
 * @return array|null
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $adminId = getCurrentAdminId();
    return fetchOne("SELECT * FROM admins WHERE id = ?", [$adminId]);
}

/**
 * Get session ID
 * @return string
 */
function getSessionId() {
    return session_id();
}

/**
 * Login user
 * @param int $userId User ID
 * @param array $userData Additional user data to store in session
 * @return bool
 */
function loginUser($userId, $userData = []) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
    
    // Store additional user data if provided
    foreach ($userData as $key => $value) {
        $_SESSION['user_' . $key] = $value;
    }
    
    // Update last login time in database
    updateRecord('users', ['last_login' => date(DATE_FORMAT)], 'id', $userId);
    
    return true;
}

/**
 * Login admin
 * @param int $adminId Admin ID
 * @param array $adminData Additional admin data to store in session
 * @return bool
 */
function loginAdmin($adminId, $adminData = []) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_login_time'] = time();
    
    // Store additional admin data if provided
    foreach ($adminData as $key => $value) {
        $_SESSION['admin_' . $key] = $value;
    }
    
    // Update last login time in database
    updateRecord('admins', ['last_login' => date(DATE_FORMAT)], 'id', $adminId);
    
    return true;
}

/**
 * Logout user
 * @return bool
 */
function logoutUser() {
    // Remove user-specific session variables
    $keysToRemove = [];
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'user_') === 0 || $key === 'user_id' || $key === 'login_time') {
            $keysToRemove[] = $key;
        }
    }
    
    foreach ($keysToRemove as $key) {
        unset($_SESSION[$key]);
    }
    
    return true;
}

/**
 * Logout admin
 * @return bool
 */
function logoutAdmin() {
    // Remove admin-specific session variables
    $keysToRemove = [];
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'admin_') === 0 || $key === 'admin_id') {
            $keysToRemove[] = $key;
        }
    }
    
    foreach ($keysToRemove as $key) {
        unset($_SESSION[$key]);
    }
    
    return true;
}

/**
 * Regenerate session ID
 * @return bool
 */
function regenerateSession() {
    return session_regenerate_id(true);
}

/**
 * Destroy session completely
 * @return bool
 */
function destroySession() {
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    return session_destroy();
}

/**
 * Check if session is expired
 * @return bool
 */
function isSessionExpired() {
    if (isset($_SESSION['login_time'])) {
        return (time() - $_SESSION['login_time']) > SESSION_LIFETIME;
    }
    if (isset($_SESSION['admin_login_time'])) {
        return (time() - $_SESSION['admin_login_time']) > SESSION_LIFETIME;
    }
    return false;
}

/**
 * Require user login (redirect if not logged in)
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireLogin($redirectUrl = '/login.php') {
    if (!isLoggedIn() || isSessionExpired()) {
        if (isSessionExpired()) {
            destroySession();
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Require admin login (redirect if not logged in)
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireAdminLogin($redirectUrl = '/admin/login.php') {
    if (!isAdminLoggedIn() || isSessionExpired()) {
        if (isSessionExpired()) {
            destroySession();
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Set flash message
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Check if there are flash messages
 * @return bool
 */
function hasFlashMessages() {
    return !empty($_SESSION['flash_messages']);
}
?>