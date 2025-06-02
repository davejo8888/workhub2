<?php
/**
 * Logout Handler
 * * Process user logout
 * * @author Dr. Ahmed AL-sadi
 * @version 1.0
 */

// Adjust paths as per your final structure
require_once '../config.php'; 
require_once '../functions.php';
require_once 'session.php'; // Assumes session.php is in the auth folder

// Destroy user session
destroyUserSession();

// Redirect to login page with message
redirect('../login.php', [
    'type' => 'info',
    'message' => 'You have been logged out.'
]);
?>
