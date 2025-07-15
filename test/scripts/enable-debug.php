<?php
/**
 * Enable WordPress debugging and logging
 */

if (!defined('ABSPATH')) {
    die('Must be run in WordPress context');
}

// Enable debug logging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Ensure log directory exists
$log_dir = WP_CONTENT_DIR . '/debug.log';
if (!file_exists(dirname($log_dir))) {
    wp_mkdir_p(dirname($log_dir));
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $log_dir);

echo "WordPress debugging enabled. Log file: " . $log_dir;