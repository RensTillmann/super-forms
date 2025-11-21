<?php
/**
 * Test Database Logger
 *
 * Logs test execution data to a dedicated database table for inspection and debugging.
 * Table is dropped and recreated before each test run for isolation.
 *
 * @package Super_Forms
 * @subpackage Tests
 * @since 6.5.0
 */

class SUPER_Test_DB_Logger {

	/**
	 * Current test run ID (UUID)
	 *
	 * @var string
	 */
	private static $run_id = null;

	/**
	 * Table name
	 *
	 * @var string
	 */
	private static $table_name = null;

	/**
	 * Start time for current test
	 *
	 * @var float
	 */
	private static $test_start_time = null;

	/**
	 * Current test class
	 *
	 * @var string
	 */
	private static $current_test_class = null;

	/**
	 * Current test method
	 *
	 * @var string
	 */
	private static $current_test_method = null;

	/**
	 * Initialize logger and setup database
	 */
	public static function init() {
		global $wpdb;

		self::$table_name = $wpdb->prefix . 'superforms_test_log';
		self::$run_id = self::generate_run_id();

		// Drop and recreate table for fresh start
		self::setup_table();

		// Log test run start
		self::log(
			array(
				'log_type'       => 'run_start',
				'context_data'   => wp_json_encode(
					array(
						'php_version' => PHP_VERSION,
						'wp_version'  => get_bloginfo( 'version' ),
						'timestamp'   => current_time( 'mysql' ),
					)
				),
				'execution_time_ms' => 0,
			)
		);
	}

	/**
	 * Drop and recreate test log table
	 */
	public static function setup_table() {
		global $wpdb;

		$table_name = self::$table_name;
		$charset_collate = $wpdb->get_charset_collate();

		// Drop existing table
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		// Create fresh table
		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			test_run_id VARCHAR(50) NOT NULL,
			test_class VARCHAR(100) DEFAULT NULL,
			test_method VARCHAR(100) DEFAULT NULL,
			log_type ENUM('run_start', 'run_end', 'test_start', 'test_end', 'event', 'assertion', 'error', 'performance') NOT NULL,
			event_id VARCHAR(100) DEFAULT NULL,
			context_data LONGTEXT DEFAULT NULL,
			assertion_type VARCHAR(50) DEFAULT NULL,
			expected_value TEXT DEFAULT NULL,
			actual_value TEXT DEFAULT NULL,
			status ENUM('pass', 'fail', 'skip') DEFAULT NULL,
			execution_time_ms FLOAT DEFAULT NULL,
			error_message TEXT DEFAULT NULL,
			stack_trace TEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY test_run_id (test_run_id),
			KEY test_class_method (test_class, test_method),
			KEY log_type (log_type),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Generate unique test run ID
	 *
	 * @return string UUID
	 */
	private static function generate_run_id() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		);
	}

	/**
	 * Get current test run ID
	 *
	 * @return string
	 */
	public static function get_run_id() {
		return self::$run_id;
	}

	/**
	 * Set current test context
	 *
	 * @param string $class Test class name
	 * @param string $method Test method name
	 */
	public static function set_test_context( $class, $method ) {
		self::$current_test_class = $class;
		self::$current_test_method = $method;
		self::$test_start_time = microtime( true );

		self::log(
			array(
				'log_type'  => 'test_start',
				'test_class' => $class,
				'test_method' => $method,
			)
		);
	}

	/**
	 * Clear current test context
	 *
	 * @param string $status Test status (pass/fail/skip)
	 */
	public static function clear_test_context( $status = 'pass' ) {
		$execution_time = ( microtime( true ) - self::$test_start_time ) * 1000;

		self::log(
			array(
				'log_type'          => 'test_end',
				'test_class'        => self::$current_test_class,
				'test_method'       => self::$current_test_method,
				'status'            => $status,
				'execution_time_ms' => $execution_time,
			)
		);

		self::$current_test_class = null;
		self::$current_test_method = null;
		self::$test_start_time = null;
	}

	/**
	 * Log event firing
	 *
	 * @param string $event_id Event identifier
	 * @param array  $context  Event context data
	 */
	public static function log_event( $event_id, $context ) {
		self::log(
			array(
				'log_type'     => 'event',
				'event_id'     => $event_id,
				'context_data' => wp_json_encode( $context, JSON_PRETTY_PRINT ),
			)
		);
	}

	/**
	 * Log assertion
	 *
	 * @param string $type     Assertion type (assertEquals, assertCount, etc.)
	 * @param mixed  $expected Expected value
	 * @param mixed  $actual   Actual value
	 * @param bool   $passed   Whether assertion passed
	 * @param string $message  Optional message
	 */
	public static function log_assertion( $type, $expected, $actual, $passed, $message = '' ) {
		self::log(
			array(
				'log_type'        => 'assertion',
				'assertion_type'  => $type,
				'expected_value'  => self::value_to_string( $expected ),
				'actual_value'    => self::value_to_string( $actual ),
				'status'          => $passed ? 'pass' : 'fail',
				'error_message'   => $message,
			)
		);
	}

	/**
	 * Log error
	 *
	 * @param string $message Error message
	 * @param string $trace   Stack trace
	 */
	public static function log_error( $message, $trace = '' ) {
		self::log(
			array(
				'log_type'      => 'error',
				'status'        => 'fail',
				'error_message' => $message,
				'stack_trace'   => $trace,
			)
		);
	}

	/**
	 * Log performance metric
	 *
	 * @param string $metric_name  Metric name
	 * @param float  $value        Metric value
	 * @param string $unit         Unit (ms, bytes, etc.)
	 */
	public static function log_performance( $metric_name, $value, $unit = 'ms' ) {
		self::log(
			array(
				'log_type'          => 'performance',
				'assertion_type'    => $metric_name,
				'actual_value'      => $value . ' ' . $unit,
				'execution_time_ms' => $value,
			)
		);
	}

	/**
	 * Generic log method
	 *
	 * @param array $data Log data
	 */
	private static function log( $data ) {
		global $wpdb;

		$defaults = array(
			'test_run_id'  => self::$run_id,
			'test_class'   => self::$current_test_class,
			'test_method'  => self::$current_test_method,
			'created_at'   => current_time( 'mysql' ),
		);

		$data = array_merge( $defaults, $data );

		$wpdb->insert( self::$table_name, $data );
	}

	/**
	 * Convert value to string for logging
	 *
	 * @param mixed $value Value to convert
	 * @return string
	 */
	private static function value_to_string( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return wp_json_encode( $value, JSON_PRETTY_PRINT );
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_null( $value ) ) {
			return 'null';
		}
		return (string) $value;
	}

	/**
	 * Get summary of current test run
	 *
	 * @return array
	 */
	public static function get_run_summary() {
		global $wpdb;
		$table = self::$table_name;
		$run_id = self::$run_id;

		$stats = array(
			'run_id'        => $run_id,
			'total_tests'   => 0,
			'passed_tests'  => 0,
			'failed_tests'  => 0,
			'total_events'  => 0,
			'total_assertions' => 0,
			'passed_assertions' => 0,
			'failed_assertions' => 0,
			'total_errors'  => 0,
			'total_time_ms' => 0,
		);

		// Count tests
		$test_counts = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(DISTINCT CONCAT(test_class, '::', test_method)) as total,
					SUM(CASE WHEN status = 'pass' THEN 1 ELSE 0 END) as passed,
					SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) as failed
				FROM {$table}
				WHERE test_run_id = %s AND log_type = 'test_end'",
				$run_id
			),
			ARRAY_A
		);

		if ( $test_counts ) {
			$stats['total_tests'] = (int) $test_counts['total'];
			$stats['passed_tests'] = (int) $test_counts['passed'];
			$stats['failed_tests'] = (int) $test_counts['failed'];
		}

		// Count events
		$stats['total_events'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE test_run_id = %s AND log_type = 'event'",
				$run_id
			)
		);

		// Count assertions
		$assertion_counts = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) as total,
					SUM(CASE WHEN status = 'pass' THEN 1 ELSE 0 END) as passed,
					SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) as failed
				FROM {$table}
				WHERE test_run_id = %s AND log_type = 'assertion'",
				$run_id
			),
			ARRAY_A
		);

		if ( $assertion_counts ) {
			$stats['total_assertions'] = (int) $assertion_counts['total'];
			$stats['passed_assertions'] = (int) $assertion_counts['passed'];
			$stats['failed_assertions'] = (int) $assertion_counts['failed'];
		}

		// Count errors
		$stats['total_errors'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE test_run_id = %s AND log_type = 'error'",
				$run_id
			)
		);

		// Total execution time
		$stats['total_time_ms'] = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(execution_time_ms) FROM {$table} WHERE test_run_id = %s AND log_type = 'test_end'",
				$run_id
			)
		);

		return $stats;
	}

	/**
	 * Get all events from current test run
	 *
	 * @return array
	 */
	public static function get_events() {
		global $wpdb;
		$table = self::$table_name;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE test_run_id = %s AND log_type = 'event'
				ORDER BY id ASC",
				self::$run_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get all failed assertions
	 *
	 * @return array
	 */
	public static function get_failed_assertions() {
		global $wpdb;
		$table = self::$table_name;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE test_run_id = %s AND log_type = 'assertion' AND status = 'fail'
				ORDER BY id ASC",
				self::$run_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get all errors
	 *
	 * @return array
	 */
	public static function get_errors() {
		global $wpdb;
		$table = self::$table_name;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE test_run_id = %s AND log_type = 'error'
				ORDER BY id ASC",
				self::$run_id
			),
			ARRAY_A
		);
	}

	/**
	 * Print run summary to console
	 */
	public static function print_summary() {
		$stats = self::get_run_summary();

		echo "\n";
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
		echo "📊 TEST DATABASE LOG SUMMARY\n";
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
		echo "Run ID: {$stats['run_id']}\n";
		echo "\n";
		echo "Tests:      {$stats['total_tests']} total, {$stats['passed_tests']} passed, {$stats['failed_tests']} failed\n";
		echo "Assertions: {$stats['total_assertions']} total, {$stats['passed_assertions']} passed, {$stats['failed_assertions']} failed\n";
		echo "Events:     {$stats['total_events']} fired\n";
		echo "Errors:     {$stats['total_errors']}\n";
		echo "Time:       " . number_format( $stats['total_time_ms'], 2 ) . " ms\n";
		echo "\n";
		echo "📂 Inspect test data:\n";
		echo "   ./inspect-test-db.sh --events     # View all events\n";
		echo "   ./inspect-test-db.sh --failures   # View failed assertions\n";
		echo "   ./inspect-test-db.sh --errors     # View errors\n";
		echo "   ./inspect-test-db.sh --summary    # View full summary\n";
		echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
		echo "\n";
	}
}
