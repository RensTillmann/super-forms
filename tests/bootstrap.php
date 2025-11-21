<?php
/**
 * PHPUnit bootstrap file for Super Forms
 *
 * Sets up WordPress test environment and loads the plugin.
 *
 * @package Super_Forms
 */

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/src/super-forms.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';

// Load test helpers
require_once dirname( __FILE__ ) . '/class-test-helpers.php';
require_once dirname( __FILE__ ) . '/class-test-db-logger.php';

// Initialize test database logger
SUPER_Test_DB_Logger::init();

// Register shutdown function to print summary
register_shutdown_function( array( 'SUPER_Test_DB_Logger', 'print_summary' ) );
