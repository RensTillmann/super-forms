<?php
/**
 * Force enable proper debugging and error reporting
 */

if (!defined('ABSPATH')) {
    die('Must be run in WordPress context');
}

// Force PHP error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Ensure log directory and file exist with proper permissions
$log_file = WP_CONTENT_DIR . '/debug.log';
if (!file_exists($log_file)) {
    touch($log_file);
    chmod($log_file, 0666);
}

// Set custom error log
ini_set('error_log', $log_file);

// Add WordPress debug constants if not already defined
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', true); // Show errors for debugging
}
if (!defined('SCRIPT_DEBUG')) {
    define('SCRIPT_DEBUG', true);
}

// Custom error handler to ensure errors get logged
function super_forms_error_handler($errno, $errstr, $errfile, $errline) {
    $error_message = "[" . date('Y-m-d H:i:s') . "] PHP Error ($errno): $errstr in $errfile on line $errline" . PHP_EOL;
    error_log($error_message);
    
    // Also echo for immediate visibility
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 10px;'>";
    echo "<strong>PHP Error:</strong> $errstr<br>";
    echo "<strong>File:</strong> $errfile<br>";
    echo "<strong>Line:</strong> $errline<br>";
    echo "</div>";
    
    return false; // Don't suppress the error
}

// Custom fatal error handler
function super_forms_fatal_handler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $error_message = "[" . date('Y-m-d H:i:s') . "] FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}" . PHP_EOL;
        error_log($error_message);
        
        // Also create a visible error page
        echo "<!DOCTYPE html><html><head><title>Fatal Error Detected</title></head><body>";
        echo "<h1 style='color: red;'>Fatal PHP Error Detected</h1>";
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; margin: 20px;'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($error['message']) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($error['file']) . "<br>";
        echo "<strong>Line:</strong> " . htmlspecialchars($error['line']) . "<br>";
        echo "</div>";
        echo "<p>Check the debug log at: " . WP_CONTENT_DIR . "/debug.log</p>";
        echo "</body></html>";
    }
}

// Register error handlers
set_error_handler('super_forms_error_handler');
register_shutdown_function('super_forms_fatal_handler');

echo "Enhanced debugging enabled. Errors will be logged to: $log_file";
echo "\nPHP error reporting level: " . error_reporting();
echo "\nLog errors: " . (ini_get('log_errors') ? 'ON' : 'OFF');
echo "\nDisplay errors: " . (ini_get('display_errors') ? 'ON' : 'OFF');