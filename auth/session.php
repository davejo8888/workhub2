<?php
/**
 * Session management
 * This file should be included at the very beginning of all primary entry scripts.
 * It handles session configuration, loading core files, and basic session security.
 */

// 1. Configure session settings using ini_set (MUST be before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Determine if running over HTTPS for secure cookie flag
$is_https = false;
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) {
    $is_https = true;
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    // Check for reverse proxy
    $is_https = true;
}

if ($is_https) {
    ini_set('session.cookie_secure', 1);
} else {
    // For local development over HTTP, you might temporarily set this to 0.
    // On a live server that SHOULD be HTTPS, this being 0 is a security risk.
    // If your live setup uses HTTPS but PHP doesn't detect it, you may need to force 'session.cookie_secure' to 1.
    // Consider an environment variable or a config setting for this if auto-detection is unreliable.
    ini_set('session.cookie_secure', 0); // Review for production if HTTPS is used but not detected
}
ini_set('session.cookie_samesite', 'Lax'); // 'Lax' or 'Strict'. 'None' requires Secure attribute.

// 2. Load main application configuration
// This path assumes session.php is in 'auth/' and config.php is in 'includes/' at the same level as 'auth/'
$configPath = __DIR__ . '/../includes/config.php';
if (!file_exists($configPath)) {
    error_log("CRITICAL: Configuration file (includes/config.php) not found from auth/session.php. Path checked: " . realpath(__DIR__ . '/../includes/') . "/config.php");
    http_response_code(503); // Service Unavailable
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESSCNFNF)";
    exit;
}
require_once $configPath; // Defines constants like ROOT_PATH (if not already), SESSION_TIMEOUT, SITE_URL, etc.

// Ensure ROOT_PATH is defined (config.php should define it or check it)
if (!defined('ROOT_PATH')) {
    error_log("CRITICAL: ROOT_PATH is not defined after including config.php in auth/session.php.");
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESSRPND)";
    exit;
}

// 3. Load necessary core files and function libraries in correct order
//    (Database class, then general functions, then authentication functions)

// Load Database Class first, as other functions might depend on it
$dbPath = ROOT_PATH . '/includes/db.php';
if (file_exists($dbPath)) {
    require_once $dbPath; // Defines the Database class
} else {
    error_log("CRITICAL: db.php (Database class) not found from auth/session.php. Path: $dbPath");
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESSDBNF)";
    exit;
}

// Load General Functions
$functionsPath = ROOT_PATH . '/includes/functions.php';
if (file_exists($functionsPath)) {
    require_once $functionsPath; // For redirect, sanitize, isLoggedIn (if defined here), etc.
} else {
    error_log("CRITICAL: functions.php not found from auth/session.php. Path: $functionsPath");
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESSFNFNF)";
    exit;
}

// Load Authentication Functions (which may use Database and general functions)
$authFunctionsPath = ROOT_PATH . '/includes/auth.php';
if (file_exists($authFunctionsPath)) {
    require_once $authFunctionsPath; // For loginUser, validateSession, getCurrentUser, hasPermission, isLoggedIn (if defined here) etc.
} else {
    error_log("CRITICAL: auth.php (authentication functions library) not found from auth/session.php. Path: $authFunctionsPath");
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESSATHNF)";
    exit;
}

// 4. Set session cookie parameters (MUST be before session_start)
// Uses SESSION_TIMEOUT and SESSION_NAME from includes/config.php
$secureCookie = $is_https;
$httponlyCookie = true;
$samesiteCookie = 'Lax'; // Should match ini_set

if (defined('SESSION_TIMEOUT') && defined('SESSION_NAME')) {
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => '',  // Current domain or specific domain from config if needed
            'secure' => $secureCookie,
            'httponly' => $httponlyCookie,
            'samesite' => $samesiteCookie
        ]);
    } else {
        session_set_cookie_params(
            SESSION_TIMEOUT,
            '/; samesite=' . $samesiteCookie, // Path and samesite attribute for older PHP
            '',                               // Domain
            $secureCookie,
            $httponlyCookie
        );
    }
    session_name(SESSION_NAME); // Set session name before starting
} else {
    error_log("CRITICAL: SESSION_TIMEOUT or SESSION_NAME constant is not defined. Check includes/config.php.");
    // Fallback or error, as these are crucial.
}

// 5. Start session if not already active
// (This check helps prevent errors if something else inadvertently starts a session,
// though ideally, this script is the *only* place session_start() is called).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 6. Prevent session fixation (regenerate ID for new sessions)
if (!isset($_SESSION['initialized'])) {
    session_regenerate_id(true); // Regenerate ID and delete old session file
    $_SESSION['initialized'] = true;
}

// 7. Set/Refresh CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 8. Check session validity if a user appears to be logged in
// isLoggedIn() and validateSession() should be available from included files (functions.php/auth.php)
if (function_exists('isLoggedIn') && isLoggedIn()) {
    if (function_exists('validateSession') && !validateSession()) {
        // Session is invalid (e.g., timed out)
        // loginUser() in includes/auth.php handles destroying old session data on failed validation.
        // Here we just redirect.
        if (function_exists('redirect') && defined('SITE_URL')) {
             // Ensure redirect doesn't cause a loop if already on login.php
            if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
                redirect(SITE_URL . 'login.php?expired=1'); // SITE_URL should have trailing slash
            }
        } else {
            // Fallback redirect if function or SITE_URL isn't available
            if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
                 header('Location: login.php?expired=1'); // Adjust path if login.php isn't in root
                 exit;
            }
        }
    }
}
?>
