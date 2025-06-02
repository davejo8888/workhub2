<?php
/**
 * MyWorkHub - Main Configuration File
 *
 * This file contains all the critical configuration settings for the application.
 * It should be included at the very beginning of any script that needs access
 * to these settings or initiates core functionalities like database connections or sessions.
 * This is the single, authoritative configuration file for the application.
 *
 * @author Dr. Ahmed AL-sadi (enhanced by AI)
 * @version 1.3 (Merged)
 */

// -----------------------------------------------------------------------------
// ERROR REPORTING & ENVIRONMENT
// -----------------------------------------------------------------------------

/**
 * Environment Mode
 * Set to 'development' for detailed error reporting during development.
 * Set to 'production' to suppress errors and log them instead for live sites.
 */
define('ENVIRONMENT', 'development'); // Options: 'development', 'production'

if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    // For production, consider setting up a proper error logging mechanism:
    // ini_set('log_errors', 1);
    // ini_set('error_log', ROOT_PATH . '/logs/php_error.log'); // Ensure this path exists and is writable by the server
}

/**
 * Default Timezone
 * Set this to your server's or application's primary timezone.
 * See: https://www.php.net/manual/en/timezones.php
 */
date_default_timezone_set('Australia/Sydney'); // Example: 'UTC', 'America/New_York'

// -----------------------------------------------------------------------------
// PATHS & URLS
// -----------------------------------------------------------------------------

/**
 * ROOT_PATH
 * This constant MUST be defined by the script including this config file *before* it's included.
 * It should be the absolute path to the main application directory (e.g., /home/user/public_html/workhub2).
 * This check prevents direct access to this config file and ensures paths are correct.
 */
if (!defined('ROOT_PATH')) {
    // Attempt to log the error
    error_log('CRITICAL: config.php included without ROOT_PATH defined. Access attempt from: ' . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown script'));
    
    // Send a generic error response
    http_response_code(503); // Service Unavailable
    if (php_sapi_name() !== 'cli' && !headers_sent()) { // Avoid "headers already sent" if possible
        header('Content-Type: application/json');
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Configuration error: Application root path not defined. Direct access to configuration or misconfiguration in the including file is suspected.'
    ]);
    exit; // Stop execution immediately
}

/**
 * Site URL
 * The full base URL of your application, with a trailing slash.
 * Example: 'https://www.yourdomain.com/workhub2/' or 'http://localhost/workhub2/'
 */
define('SITE_URL', 'https://workhub.gotoaus.com/'); // Ensure this is correct for your setup

/**
 * Assets URL
 * URL to your public assets folder (CSS, JS, images).
 * Typically SITE_URL . 'assets/'.
 */
define('ASSETS_URL', SITE_URL . 'assets/');

// -----------------------------------------------------------------------------
// SITE CONFIGURATION
// -----------------------------------------------------------------------------

/**
 * Site Name
 * The name of your application, used in titles, emails, etc.
 */
define('SITE_NAME', 'MyWorkHub');

/**
 * Site Email
 * The default email address for administrative purposes or 'from' address for system emails.
 */
define('SITE_EMAIL', 'admin@workhub.gotoaus.com');

// -----------------------------------------------------------------------------
// DATABASE CONFIGURATION
// -----------------------------------------------------------------------------
// These constants are used by includes/db.php (Database class)

/** Database Host (e.g., 'localhost' or an IP address) */
define('DB_HOST', 'localhost');

/** Database Name */
define('DB_NAME', 'gotoa957_my_work_hub_db');

/** Database Username */
define('DB_USER', 'gotoa957_admin');

/** Database Password - IMPORTANT: Use a strong, unique password! */
define('DB_PASS', 'medo123My@'); // Replace with your actual secure password

/** Database Character Set */
define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------------------
// SESSION CONFIGURATION CONSTANTS
// -----------------------------------------------------------------------------
// These constants are used by auth/session.php

/**
 * Session Lifetime
 * How long a session should remain active (in seconds) before expiring.
 * Default: 3600 seconds (1 hour).
 */
define('SESSION_TIMEOUT', 3600); // 1 hour

/**
 * Session Name
 * The name of the session cookie. Using a custom name can slightly improve security.
 * Default: 'MyWorkHubSession'
 */
define('SESSION_NAME', 'MyWorkHubSess'); // Example: 'YourAppSessID'

// -----------------------------------------------------------------------------
// SECURITY CONSTANTS
// -----------------------------------------------------------------------------

/**
 * Secret Key / Salt
 * A long, random string used for hashing, CSRF tokens, or other security functions.
 * IMPORTANT: Change this to a unique, cryptographically secure random string for your application!
 * You can generate one from a site like: https://randomkeygen.com/ or use a password manager.
 */
define('SECRET_KEY', 'kz1n0WjcSKBUBKbtTpPftAUHAycVnlZ0'); // !!! CHANGE THIS !!!

/**
 * Password Hashing Cost
 * The cost factor for the bcrypt algorithm used in password_hash().
 * Higher values are more secure but take longer to process.
 * Default: 10 or 12 is generally a good balance.
 */
define('HASH_COST', 10);

/**
 * Maximum Login Attempts
 * Number of failed login attempts before an account or IP is temporarily locked.
 */
define('MAX_LOGIN_ATTEMPTS', 5);

/**
 * Login Lockout Time
 * Duration (in seconds) for which an account or IP is locked out after too many failed login attempts.
 * Default: 900 seconds (15 minutes).
 */
define('LOGIN_LOCKOUT_TIME', 900);

// -----------------------------------------------------------------------------
// FILE UPLOAD CONSTANTS
// -----------------------------------------------------------------------------

/**
 * Upload Path
 * The absolute server path to the directory where uploaded files will be stored.
 * Ensure this directory exists and is writable by the web server.
 * Example: ROOT_PATH . '/public/uploads/' or a path outside the web root for better security.
 */
define('UPLOAD_PATH', ROOT_PATH . '/uploads/'); // Ensure 'uploads' directory exists at this location

/**
 * Maximum Upload File Size (in bytes)
 * Example: 10 * 1024 * 1024 = 10MB
 */
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

/**
 * Allowed File Extensions for Uploads
 * A comma-separated string of allowed extensions (e.g., 'jpg,jpeg,png,gif,pdf,doc,docx').
 * Keep this list restrictive to necessary file types.
 */
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip');


// -----------------------------------------------------------------------------
// LOAD CORE FUNCTION LIBRARIES
// -----------------------------------------------------------------------------
// These are essential helper functions used throughout the application.
// Loading them here ensures they are available after config is processed.

$functionsPath = ROOT_PATH . '/includes/functions.php';
if (file_exists($functionsPath)) {
    require_once $functionsPath;
} else {
    error_log("CRITICAL: Core functions.php not found at $functionsPath. Application may not run correctly.");
    // Depending on severity, you might want to die here or display a more user-friendly error.
    // For now, we'll let it proceed but log the error.
}

// The includes/auth.php file contains authentication-related functions like
// registerUser, loginUser, validateSession, getCurrentUser etc.
// These functions themselves might depend on the database and other functions.
// It's a library of functions, not an init script.
// It will be included by scripts that specifically need these functions, or by auth/session.php.

// -----------------------------------------------------------------------------
// DO NOT START SESSION HERE
// -----------------------------------------------------------------------------
// Session initialization (ini_set, session_set_cookie_params, session_start)
// is handled by 'auth/session.php' AFTER this configuration file is loaded.
// This ensures all session parameters (using constants from this file) are set
// *before* the session is actually started, preventing warnings.

?>