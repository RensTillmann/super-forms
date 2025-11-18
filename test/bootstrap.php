<?php
/**
 * Test Suite Bootstrap - Securely Loads WordPress
 *
 * This file provides a portable, secure way for test scripts to load WordPress
 * across different environments (wp-env Docker, f4d.nl server, local installs).
 *
 * SECURITY NOTE:
 * - No user input involved (deterministic path search)
 * - Safety limits prevent infinite loops
 * - Matches WordPress core pattern (wp-load.php searches for wp-config.php)
 * - Verifies successful load via ABSPATH constant
 *
 * USAGE:
 * - From test/ root:     require_once(__DIR__ . '/bootstrap.php');
 * - From test/scripts/:  require_once(dirname(__DIR__) . '/bootstrap.php');
 *
 * @package Super_Forms
 * @since   6.4.127
 */

// Already loaded by WordPress (e.g., when called via Developer Tools page)
if (defined('ABSPATH')) {
    return;
}

// Find WordPress installation by searching upward for wp-load.php
// Starting from: /wp-content/plugins/super-forms/test/
// Target:        /wp-load.php (WordPress root)
$wp_root = __DIR__;
$max_depth = 10; // Safety limit (prevents infinite loop)

for ($i = 0; $i < $max_depth; $i++) {
    if (file_exists($wp_root . '/wp-load.php')) {
        require_once($wp_root . '/wp-load.php');
        break;
    }
    $wp_root = dirname($wp_root);
}

// Verify WordPress loaded successfully
if (!defined('ABSPATH')) {
    die('Error: WordPress installation not found. Please ensure this file is within a WordPress plugin directory.');
}
