<?php
/**
 * PHPUnit bootstrap file for Super Forms
 *
 * Sets up WordPress test environment and loads the plugin.
 *
 * @package Super_Forms
 */

// Get the plugin root directory
$plugin_root = dirname( dirname( __FILE__ ) );

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once $plugin_root . '/vendor/autoload.php';

// Set WP_PHPUNIT__TESTS_CONFIG if not already set
if ( ! getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . dirname( __FILE__ ) . '/wp-tests-config.php' );
}

// Get WP_PHPUNIT__DIR from composer autoload (set by wp-phpunit package)
$wp_phpunit_dir = getenv( 'WP_PHPUNIT__DIR' );
if ( ! $wp_phpunit_dir ) {
	// Fallback: check vendor directory
	$wp_phpunit_dir = $plugin_root . '/vendor/wp-phpunit/wp-phpunit';
}

// Give access to tests_add_filter() function.
require_once $wp_phpunit_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	$plugin_root = dirname( dirname( __FILE__ ) );

	// Handle both local (src/) and server (no src/) directory structures
	if ( file_exists( $plugin_root . '/src/super-forms.php' ) ) {
		require $plugin_root . '/src/super-forms.php';
	} elseif ( file_exists( $plugin_root . '/super-forms.php' ) ) {
		require $plugin_root . '/super-forms.php';
	}
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Force Action Scheduler initialization for tests.
 *
 * Action Scheduler registers at plugins_loaded priority 0, then initializes at priority 1.
 * We run at priority 2 to ensure both steps have completed.
 */
function _init_action_scheduler_for_tests() {
	if ( class_exists( 'ActionScheduler_Versions' ) ) {
		ActionScheduler_Versions::initialize_latest_version();
	}
}
tests_add_filter( 'plugins_loaded', '_init_action_scheduler_for_tests', 2 );

/**
 * Drop and recreate plugin tables after WordPress is loaded.
 * This ensures tables are created with the test prefix (wptests_) and fresh schema.
 * Runs at priority 1 to execute before other init hooks.
 */
function _init_plugin_tables() {
	global $wpdb;

	// Reset migration state to prevent backwards compat hooks from interfering
	// Migration status can persist between test runs in wptests_options,
	// causing the intercept_get_entry_data filter to activate and query EAV tables
	// that may not exist yet, leading to memory exhaustion
	delete_option( 'superforms_eav_migration' );
	delete_transient( 'superforms_needs_migration' );

	// Drop existing plugin tables to ensure fresh schema (order matters for FK constraints)
	$tables = array(
		$wpdb->prefix . 'superforms_trigger_logs',
		$wpdb->prefix . 'superforms_trigger_actions',
		$wpdb->prefix . 'superforms_triggers',
		$wpdb->prefix . 'superforms_entry_data',
		$wpdb->prefix . 'superforms_entry_meta',
		$wpdb->prefix . 'superforms_entries',
	);

	// Set entries migration state to 'completed' since Entry DAL now always writes to custom table
	// This ensures tests use the custom table for both reads and writes
	update_option( 'superforms_entries_migration', array(
		'state' => 'completed',
		'started_at' => time(),
		'completed_at' => time(),
	) );

	// Disable FK checks temporarily for clean drop
	$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' ); // phpcs:ignore
	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore
	}
	$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' ); // phpcs:ignore

	if ( class_exists( 'SUPER_Install' ) ) {
		SUPER_Install::install();
	}
}
// Run at priority 1 (before default 10) to ensure fresh tables before tests
tests_add_filter( 'init', '_init_plugin_tables', 1 );

// Start up the WP testing environment.
require $wp_phpunit_dir . '/includes/bootstrap.php';

// Load test helpers
require_once dirname( __FILE__ ) . '/class-test-helpers.php';
require_once dirname( __FILE__ ) . '/class-test-db-logger.php';

// Load trigger action classes for testing
$plugin_dir = defined( 'SUPER_PLUGIN_DIR' ) ? SUPER_PLUGIN_DIR : dirname( dirname( __FILE__ ) );
$actions_dir = $plugin_dir . '/includes/triggers/actions';
if ( is_dir( $actions_dir ) ) {
	$action_files = glob( $actions_dir . '/class-action-*.php' );
	foreach ( $action_files as $file ) {
		require_once $file;
	}
}

// Load test fixtures
$fixtures_dir = dirname( __FILE__ ) . '/fixtures';
if ( is_dir( $fixtures_dir ) ) {
	$fixture_files = glob( $fixtures_dir . '/class-*.php' );
	foreach ( $fixture_files as $file ) {
		require_once $file;
	}
}

// Initialize test database logger
SUPER_Test_DB_Logger::init();

// Register shutdown function to print summary
register_shutdown_function( array( 'SUPER_Test_DB_Logger', 'print_summary' ) );
