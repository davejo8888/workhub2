<?php
/**
 * MyWorkHub - Debug Script
 * 
 * This file helps diagnose issues with the application.
 * REMOVE THIS FILE IN PRODUCTION!
 * 
 * @author Dr. Ahmed AL-sadi
 * @version 1.0
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define ROOT_PATH
define('ROOT_PATH', __DIR__);

echo "<h1>MyWorkHub Debug Report</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// Test 1: PHP Version
echo "<h2>1. PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test 2: Required Extensions
echo "<h2>2. Required Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'session'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✓ Available' : '✗ Missing';
    $class = extension_loaded($ext) ? 'success' : 'error';
    echo "<span class='{$class}'>{$ext}: {$status}</span><br>";
}

// Test 3: File Permissions
echo "<h2>3. File System</h2>";
echo "Root Path: " . ROOT_PATH . "<br>";
echo "Current Directory: " . getcwd() . "<br>";

// Test 4: Config File
echo "<h2>4. Configuration</h2>";
if (file_exists(ROOT_PATH . '/includes/config.php')) {
    echo "<span class='success'>✓ config.php found</span><br>";
    try {
        require_once ROOT_PATH . '/includes/config.php';
        echo "<span class='success'>✓ config.php loaded successfully</span><br>";
        echo "Site Name: " . (defined('SITE_NAME') ? SITE_NAME : 'Not defined') . "<br>";
    } catch (Exception $e) {
        echo "<span class='error'>✗ Error loading config.php: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>✗ config.php not found</span><br>";
}

// Test 5: Database Connection
echo "<h2>5. Database Connection</h2>";
if (defined('DB_HOST')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT 1");
        echo "<span class='success'>✓ Database connection successful</span><br>";
        
        // Test table existence
        $tables = ['Users', 'Periods', 'MajorTasks', 'SubTasks'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
                $count = $stmt->fetchColumn();
                echo "<span class='success'>✓ Table {$table}: {$count} records</span><br>";
            } catch (Exception $e) {
                echo "<span class='error'>✗ Table {$table}: " . $e->getMessage() . "</span><br>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<span class='error'>✗ Database connection failed: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>✗ Database constants not defined</span><br>";
}

echo "<h2>6. Recommendations</h2>";
echo "<ol>";
echo "<li>Update config.php with your actual database credentials</li>";
echo "<li>Import the complete SQL schema to your database</li>";
echo "<li>Make sure all files are uploaded to the correct directory</li>";
echo "<li>Delete this debug.php file when everything works</li>";
echo "</ol>";

?>