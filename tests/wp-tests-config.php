<?php
/**
 * WordPress PHPUnit Test Configuration
 *
 * This file is used by the WordPress test framework to configure the test environment.
 * No paths or database credentials live in this repository: they are read from the
 * environment (WP_TESTS_*), so the file can stay public.
 *
 * @package Super_Forms
 */

$super_forms_tests_env = static function ( $name, $default = '' ) {
	$value = getenv( $name );
	return ( false === $value || '' === $value ) ? $default : $value;
};

// Path to the WordPress installation the tests run against.
define( 'ABSPATH', rtrim( $super_forms_tests_env( 'WP_TESTS_ABSPATH', '/path/to/wordpress/' ), '/' ) . '/' );

// Database settings - a separate test prefix is used in that database.
define( 'DB_NAME', $super_forms_tests_env( 'WP_TESTS_DB_NAME', 'wordpress_test' ) );
define( 'DB_USER', $super_forms_tests_env( 'WP_TESTS_DB_USER', 'root' ) );
define( 'DB_PASSWORD', $super_forms_tests_env( 'WP_TESTS_DB_PASSWORD' ) );
define( 'DB_HOST', $super_forms_tests_env( 'WP_TESTS_DB_HOST', 'localhost' ) );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// Test table prefix (different from production to avoid conflicts)
$table_prefix = 'wptests_';


// Test domain
define( 'WP_TESTS_DOMAIN', 'f4d.nl' );
define( 'WP_TESTS_EMAIL', 'admin@f4d.nl' );
define( 'WP_TESTS_TITLE', 'Super Forms Tests' );

// PHP path (optional)
define( 'WP_PHP_BINARY', 'php' );

// Debug settings for tests
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// Disable multisite for tests
define( 'WP_TESTS_MULTISITE', false );

// Skip installing themes/plugins beyond our plugin
define( 'WP_TESTS_SKIP_INSTALL', false );

// Drop superforms test tables BEFORE WordPress loads to ensure fresh schema
// This is needed because dbDelta doesn't reliably add new columns
$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
if ( ! $mysqli->connect_error ) {
	$mysqli->query( 'SET FOREIGN_KEY_CHECKS = 0' );
	$mysqli->query( "DROP TABLE IF EXISTS {$table_prefix}superforms_automation_logs" );
	$mysqli->query( "DROP TABLE IF EXISTS {$table_prefix}superforms_automation_actions" );
	$mysqli->query( "DROP TABLE IF EXISTS {$table_prefix}superforms_automations" );
	$mysqli->query( "DROP TABLE IF EXISTS {$table_prefix}superforms_entry_data" );
	$mysqli->query( 'SET FOREIGN_KEY_CHECKS = 1' );

	// Clear migration state BEFORE WordPress loads to prevent backwards compat hooks
	// from intercepting get_post_meta() calls during initialization
	// If migration status is 'completed', the filter tries to query EAV tables that
	// were just dropped, causing issues and memory exhaustion
	$mysqli->query( "DELETE FROM {$table_prefix}options WHERE option_name = 'superforms_eav_migration'" );
	$mysqli->query( "DELETE FROM {$table_prefix}options WHERE option_name LIKE '%superforms_needs_migration%'" );

	$mysqli->close();
}
