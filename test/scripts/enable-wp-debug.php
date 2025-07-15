<?php
/**
 * Enable WordPress debugging for development
 */

// Enable WordPress debugging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

echo "WordPress debugging enabled:\n";
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . "\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false') . "\n";
echo "WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'true' : 'false') . "\n";
echo "Debug log path: " . (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/debug.log' : '/var/www/html/wp-content/debug.log') . "\n";