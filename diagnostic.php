<?php
// diagnostic.php - Troubleshoot API issues

// Set headers to display plain text
header('Content-Type: text/plain; charset=utf-8');

echo "MyWorkHub API Diagnostics\n";
echo "========================\n\n";

// --- PHP Configuration & Extensions ---
echo "PHP Version: " . phpversion() . "\n";
echo "Error Reporting: display_errors = " . ini_get('display_errors') . ", error_reporting = " . error_reporting() . "\n";
echo "PDO available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "\n";
echo "PDO MySQL available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "\n\n";

// --- File Locations & Paths ---
echo "--- File System ---\n";
echo "Script location (diagnostic.php): " . __FILE__ . "\n";
echo "Document root (\$_SERVER['DOCUMENT_ROOT']): " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current working directory (getcwd()): " . getcwd() . "\n\n";

// --- API File Checks ---
echo "--- API File Existence ---\n";
$apiFiles = ['periods.php', 'tasks.php', 'subtasks.php']; // Added subtasks.php
$apiDirectory = 'api'; // Assuming API files are in an 'api' subdirectory

// Paths to check relative to diagnostic.php location
$pathsToCheck = [
    'Same directory as diagnostic.php' => dirname(__FILE__),
    'Parent directory of diagnostic.php' => dirname(__FILE__) . '/..',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'],
    "'api' subdir in diagnostic.php's dir" => dirname(__FILE__) . '/' . $apiDirectory,
    "'api' subdir in parent of diagnostic.php's dir" => dirname(__FILE__) . '/../' . $apiDirectory,
    "'api' subdir in Document Root" => $_SERVER['DOCUMENT_ROOT'] . '/' . $apiDirectory,
];

foreach ($pathsToCheck as $desc => $basePath) {
    echo "Checking in: $desc ($basePath)\n";
    foreach ($apiFiles as $file) {
        $filePath = rtrim($basePath, '/') . '/' . $file;
        echo "  - $file: " . (file_exists($filePath) ? "Found ($filePath)" : "Not found ($filePath)") . "\n";
    }
}
echo "\n";

// --- Config File Check ---
echo "--- Configuration File (config.php) ---\n";
// Paths to config.php relative to common API file locations or document root
$potentialConfigPaths = [
    $_SERVER['DOCUMENT_ROOT'] . '/config.php', // Root
    $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php', // Common includes folder
    dirname(__FILE__) . '/../config.php',      // If diagnostic is in /api, config is one level up
    dirname(__FILE__) . '/../../config.php',   // If diagnostic is in /api/v1, config is two levels up
    dirname(__FILE__) . '/config.php',         // If config is next to diagnostic.php
];
$configFoundPath = null;
foreach (array_unique($potentialConfigPaths) as $configFile) {
    if (file_exists($configFile)) {
        echo "  - Found: $configFile\n";
        $configFoundPath = $configFile;
        // Try to include it to check for syntax errors, but suppress output
        ob_start();
        $error = null;
        try {
            include $configFile; // Use include to avoid stopping script on warning if already included
        } catch (Throwable $t) { // Catch parse errors etc.
            $error = $t;
        }
        $output = ob_get_clean();
        if ($error) {
            echo "    - Error including config: " . $error->getMessage() . " in " . $error->getFile() . " on line " . $error->getLine() . "\n";
        } elseif (!empty($output)) {
            echo "    - Output during include (should ideally be none): " . substr(trim($output), 0, 100) . "...\n";
        } else {
            echo "    - Included successfully (no immediate errors or output).\n";
        }
        break; // Stop after finding one
    } else {
        echo "  - Not found: $configFile\n";
    }
}

if (!$configFoundPath) {
    echo "  - config.php NOT FOUND in common locations.\n";
}
echo "\n";

// --- Database Connection Test ---
echo "--- Database Connection & Basic Queries ---\n";
if ($configFoundPath && isset($db_config) && is_array($db_config)) {
    echo "  - Config loaded: Yes (from $configFoundPath)\n";
    echo "  - Host: " . ($db_config['host'] ?? 'Not set') . "\n";
    echo "  - Database: " . ($db_config['database'] ?? 'Not set') . "\n";
    echo "  - Username: " . ($db_config['username'] ?? 'Not set') . "\n";
    // Password should not be echoed

    if (empty($db_config['host']) || empty($db_config['database']) || empty($db_config['username'])) {
        echo "  - Error: One or more database configuration parameters (host, database, username) are empty.\n";
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4",
                $db_config['username'],
                $db_config['password'] ?? null, // Handle if password might not be set
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5 // 5 second timeout for connection
                ]
            );
            echo "  - PDO Connection: Success!\n";

            // Test Periods table
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM Periods");
                $count = $stmt->fetchColumn();
                echo "  - Periods table: Found, $count records.\n";
            } catch (PDOException $e) {
                echo "  - Periods table: Error querying - " . $e->getMessage() . "\n";
            }

            // Test MajorTasks table
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM MajorTasks");
                $count = $stmt->fetchColumn();
                echo "  - MajorTasks table: Found, $count records.\n";
            } catch (PDOException $e) {
                echo "  - MajorTasks table: Error querying - " . $e->getMessage() . "\n";
            }
            
            // Test SubTasks table (if it exists)
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM SubTasks"); // Assuming table name is SubTasks
                $count = $stmt->fetchColumn();
                echo "  - SubTasks table: Found, $count records.\n";
            } catch (PDOException $e) {
                echo "  - SubTasks table: Error querying - " . $e->getMessage() . " (This table might not exist or name is different)\n";
            }
             // Test Users table (if it exists and is relevant)
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM Users");
                $count = $stmt->fetchColumn();
                echo "  - Users table: Found, $count records.\n";
            } catch (PDOException $e) {
                echo "  - Users table: Error querying - " . $e->getMessage() . " (This table might not exist or name is different)\n";
            }


        } catch (PDOException $e) {
            echo "  - PDO Connection Error: " . $e->getMessage() . "\n";
            echo "    Please double-check database credentials, host, database name, and firewall settings.\n";
        }
    }
} elseif ($configFoundPath && (!isset($db_config) || !is_array($db_config))) {
    echo "  - Config file ($configFoundPath) was included, but \$db_config array is not set or is not an array. Check the contents of config.php.\n";
} else {
    echo "  - Config loaded: No (couldn't find or properly include config.php). Database test skipped.\n";
}
echo "\n";

echo "--- API Endpoint Basic Test (periods.php?action=list) ---\n";
// This test assumes diagnostic.php is in the root, and api files are in /api/
// Adjust the path to your periods.php if it's different.
$periodsApiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
// Try to determine a sensible path. If diagnostic.php is in 'api' dir, go up one.
if (basename(dirname(__FILE__)) === $apiDirectory) {
    $periodsApiUrl .= dirname($_SERVER['PHP_SELF'], 2) . "/{$apiDirectory}/periods.php?action=list";
} else { // Assume diagnostic is in root, api is in /api/
    $periodsApiUrl .= (dirname($_SERVER['PHP_SELF']) === '/' ? '' : dirname($_SERVER['PHP_SELF'])) . "/{$apiDirectory}/periods.php?action=list";
}
$periodsApiUrl = str_replace('//', '/', $periodsApiUrl); // Clean up double slashes if dirname is /
$periodsApiUrl = str_replace('/./', '/', $periodsApiUrl); // Clean up /./ if present


echo "  - Attempting to fetch: $periodsApiUrl\n";

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $periodsApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
    // If your site uses HTTPS with a self-signed certificate (development), you might need:
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "  - HTTP Status Code: $httpCode\n";
    if ($curlError) {
        echo "  - cURL Error: $curlError\n";
    }
    echo "  - Response (first 500 chars):\n";
    echo "    " . substr($response, 0, 500) . (strlen($response) > 500 ? "..." : "") . "\n";
    
    // Try to decode JSON
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "  - JSON Decoded Successfully.\n";
        if (isset($decoded['status']) && $decoded['status'] === 'success' || isset($decoded['success']) && $decoded['success'] === true) {
            echo "  - API Status: Success reported.\n";
            if (isset($decoded['data'])) {
                echo "  - Data items received: " . count($decoded['data']) . "\n";
            }
        } elseif(isset($decoded['status']) && $decoded['status'] === 'error' && isset($decoded['message'])) {
            echo "  - API Error Reported: " . $decoded['message'] . "\n";
        } else {
            echo "  - JSON structure unexpected or error not clearly reported by API.\n";
        }
    } else {
        echo "  - JSON Decode Error: " . json_last_error_msg() . ". The response is not valid JSON.\n";
    }

} else {
    echo "  - cURL extension is not available. Cannot perform HTTP request test.\n";
    echo "    Consider using file_get_contents if allow_url_fopen is On, but cURL is preferred.\n";
    // Example with file_get_contents (less reliable for debugging HTTP issues)
    // if (ini_get('allow_url_fopen')) {
    //     $response = @file_get_contents($periodsApiUrl);
    //     echo "  - Response (file_get_contents, first 500 chars):\n";
    //     echo "    " . substr($response, 0, 500) . (strlen($response) > 500 ? "..." : "") . "\n";
    // } else {
    //     echo "  - allow_url_fopen is Off.\n";
    // }
}
echo "\n";


echo "--- End of Diagnostics ---\n";
echo "Review the output above carefully. 'Not found' errors, connection errors, or PHP errors in the API response section are key indicators.\n";
echo "Ensure paths to 'config.php' inside your API files (periods.php, tasks.php) are correct relative to their location.\n";
?>
