<?php
/**
 * Trigger Performance - Metrics Tracking System
 *
 * Provides performance monitoring for triggers/actions:
 * - Execution timing with start/end timers
 * - Memory usage tracking
 * - Slow execution detection and alerting
 * - Performance statistics aggregation
 * - Bottleneck identification
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Automation_Performance
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Performance' ) ) :

	/**
	 * SUPER_Automation_Performance Class
	 */
	class SUPER_Automation_Performance {

		/**
		 * Default slow execution threshold in seconds
		 */
		const DEFAULT_SLOW_THRESHOLD = 1.0;

		/**
		 * Critical slow threshold (triggers admin notification)
		 */
		const CRITICAL_SLOW_THRESHOLD = 5.0;

		/**
		 * Active timers storage
		 *
		 * @var array
		 */
		private static $timers = array();

		/**
		 * Completed metrics for this request
		 *
		 * @var array
		 */
		private static $metrics = array();

		/**
		 * Slow execution threshold (configurable)
		 *
		 * @var float
		 */
		private static $slow_threshold = null;

		/**
		 * Start a performance timer
		 *
		 * @param string $key Unique identifier for this timer
		 * @return void
		 * @since 6.5.0
		 */
		public static function start_timer( $key ) {
			self::$timers[ $key ] = array(
				'start_time'   => microtime( true ),
				'start_memory' => memory_get_usage(),
			);
		}

		/**
		 * End a timer and record metrics
		 *
		 * @param string $key   Timer key
		 * @param array  $meta  Additional metadata to store
		 * @return array|null Metrics array or null if timer not found
		 * @since 6.5.0
		 */
		public static function end_timer( $key, $meta = array() ) {
			if ( ! isset( self::$timers[ $key ] ) ) {
				return null;
			}

			$start = self::$timers[ $key ];
			$end_time = microtime( true );
			$end_memory = memory_get_usage();

			$metrics = array(
				'key'           => $key,
				'duration'      => $end_time - $start['start_time'],
				'duration_ms'   => round( ( $end_time - $start['start_time'] ) * 1000, 2 ),
				'memory_used'   => $end_memory - $start['start_memory'],
				'memory_peak'   => memory_get_peak_usage(),
				'start_time'    => $start['start_time'],
				'end_time'      => $end_time,
				'meta'          => $meta,
			);

			// Store for aggregation
			self::$metrics[ $key ] = $metrics;

			// Remove from active timers
			unset( self::$timers[ $key ] );

			// Check for slow execution
			self::check_slow_execution( $key, $metrics['duration'], $meta );

			// Log to debugger if available
			if ( class_exists( 'SUPER_Automation_Debugger' ) && SUPER_Automation_Debugger::is_debug_mode() ) {
				SUPER_Automation_Debugger::debug(
					sprintf( 'Timer completed: %s (%.2fms, %s memory)', $key, $metrics['duration_ms'], size_format( $metrics['memory_used'] ) ),
					$metrics,
					'info'
				);
			}

			return $metrics;
		}

		/**
		 * Get elapsed time without stopping timer
		 *
		 * @param string $key Timer key
		 * @return float|null Elapsed time in seconds
		 * @since 6.5.0
		 */
		public static function get_elapsed( $key ) {
			if ( ! isset( self::$timers[ $key ] ) ) {
				return null;
			}

			return microtime( true ) - self::$timers[ $key ]['start_time'];
		}

		/**
		 * Time a callback function
		 *
		 * @param string   $key      Timer key
		 * @param callable $callback Function to execute
		 * @param array    $args     Arguments to pass to callback
		 * @return array Array with 'result' and 'metrics' keys
		 * @since 6.5.0
		 */
		public static function time_callback( $key, $callback, $args = array() ) {
			self::start_timer( $key );

			$result = call_user_func_array( $callback, $args );

			$metrics = self::end_timer( $key, array(
				'callback' => is_array( $callback ) ? get_class( $callback[0] ) . '::' . $callback[1] : $callback,
			) );

			return array(
				'result'  => $result,
				'metrics' => $metrics,
			);
		}

		/**
		 * Check if execution was slow and take action
		 *
		 * @param string $key      Timer key
		 * @param float  $duration Duration in seconds
		 * @param array  $meta     Additional context
		 * @since 6.5.0
		 */
		private static function check_slow_execution( $key, $duration, $meta = array() ) {
			$threshold = self::get_slow_threshold();

			if ( $duration < $threshold ) {
				return;
			}

			// Log slow execution
			if ( class_exists( 'SUPER_Automation_Logger' ) ) {
				$logger = SUPER_Automation_Logger::instance();
				$logger->warning(
					sprintf(
						'Slow automation execution: %s took %.2f seconds (threshold: %.2f)',
						$key,
						$duration,
						$threshold
					),
					array_merge( $meta, array(
						'duration'  => $duration,
						'threshold' => $threshold,
						'key'       => $key,
					) )
				);
			}

			// For critical slowness, send admin notification
			if ( $duration > self::CRITICAL_SLOW_THRESHOLD ) {
				self::notify_admin_slow_execution( $key, $duration, $meta );
			}
		}

		/**
		 * Notify admin of critically slow execution
		 *
		 * Uses transient to prevent notification spam
		 *
		 * @param string $key      Timer key
		 * @param float  $duration Duration in seconds
		 * @param array  $meta     Context data
		 * @since 6.5.0
		 */
		private static function notify_admin_slow_execution( $key, $duration, $meta = array() ) {
			// Rate limit notifications (max 1 per hour per key)
			$transient_key = 'super_slow_notify_' . md5( $key );
			if ( get_transient( $transient_key ) ) {
				return;
			}
			set_transient( $transient_key, time(), HOUR_IN_SECONDS );

			$admin_email = get_option( 'admin_email' );
			$site_name = get_bloginfo( 'name' );

			$subject = sprintf(
				/* translators: 1: Site name, 2: Timer key */
				__( '[%1$s] Slow Automation Execution Alert: %2$s', 'super-forms' ),
				$site_name,
				$key
			);

			$message = sprintf(
				/* translators: 1: Timer key, 2: Duration in seconds, 3: Critical threshold */
				__( "An automation execution exceeded the critical threshold:\n\nKey: %1\$s\nDuration: %.2f seconds\nThreshold: %.2f seconds\n\nThis may indicate a performance issue that needs attention.", 'super-forms' ),
				$key,
				$duration,
				self::CRITICAL_SLOW_THRESHOLD
			);

			if ( ! empty( $meta ) ) {
				$message .= "\n\n" . __( 'Additional Context:', 'super-forms' ) . "\n";
				foreach ( $meta as $k => $v ) {
					$message .= sprintf( "%s: %s\n", $k, is_scalar( $v ) ? $v : wp_json_encode( $v ) );
				}
			}

			$message .= "\n\n" . sprintf(
				/* translators: URL to logs page */
				__( 'View logs: %s', 'super-forms' ),
				admin_url( 'admin.php?page=super-automation-logs' )
			);

			wp_mail( $admin_email, $subject, $message );
		}

		/**
		 * Get slow execution threshold
		 *
		 * @return float Threshold in seconds
		 * @since 6.5.0
		 */
		public static function get_slow_threshold() {
			if ( self::$slow_threshold === null ) {
				self::$slow_threshold = (float) get_option( 'super_automations_slow_threshold', self::DEFAULT_SLOW_THRESHOLD );
			}
			return self::$slow_threshold;
		}

		/**
		 * Set slow execution threshold
		 *
		 * @param float $seconds Threshold in seconds
		 * @since 6.5.0
		 */
		public static function set_slow_threshold( $seconds ) {
			self::$slow_threshold = (float) $seconds;
		}

		/**
		 * Get all metrics recorded in this request
		 *
		 * @return array
		 * @since 6.5.0
		 */
		public static function get_all_metrics() {
			return self::$metrics;
		}

		/**
		 * Get specific metric by key
		 *
		 * @param string $key Timer key
		 * @return array|null
		 * @since 6.5.0
		 */
		public static function get_metric( $key ) {
			return isset( self::$metrics[ $key ] ) ? self::$metrics[ $key ] : null;
		}

		/**
		 * Get total execution time across all completed timers
		 *
		 * @return float Total time in seconds
		 * @since 6.5.0
		 */
		public static function get_total_time() {
			$total = 0;
			foreach ( self::$metrics as $metric ) {
				$total += $metric['duration'];
			}
			return $total;
		}

		/**
		 * Get summary statistics
		 *
		 * @return array Summary stats
		 * @since 6.5.0
		 */
		public static function get_summary() {
			$durations = array_column( self::$metrics, 'duration' );
			$memory_used = array_column( self::$metrics, 'memory_used' );

			if ( empty( $durations ) ) {
				return array(
					'count'        => 0,
					'total_time'   => 0,
					'avg_time'     => 0,
					'max_time'     => 0,
					'min_time'     => 0,
					'total_memory' => 0,
					'peak_memory'  => memory_get_peak_usage(),
				);
			}

			return array(
				'count'        => count( $durations ),
				'total_time'   => array_sum( $durations ),
				'avg_time'     => array_sum( $durations ) / count( $durations ),
				'max_time'     => max( $durations ),
				'min_time'     => min( $durations ),
				'total_memory' => array_sum( $memory_used ),
				'peak_memory'  => memory_get_peak_usage(),
			);
		}

		/**
		 * Get historical performance data
		 *
		 * Aggregates from log table for trend analysis
		 *
		 * @param int $days Number of days to analyze
		 * @return array Performance data
		 * @since 6.5.0
		 */
		public static function get_historical_stats( $days = 7 ) {
			global $wpdb;

			$table = $wpdb->prefix . 'superforms_automation_logs';
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

			// Get daily averages
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$daily_stats = $wpdb->get_results( $wpdb->prepare(
				"SELECT
					DATE(executed_at) as date,
					COUNT(*) as executions,
					AVG(execution_time_ms) as avg_time_ms,
					MAX(execution_time_ms) as max_time_ms,
					SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
					SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
				FROM {$table}
				WHERE executed_at >= %s
				GROUP BY DATE(executed_at)
				ORDER BY date ASC",
				$cutoff
			), ARRAY_A );

			// Get slowest executions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$slowest = $wpdb->get_results( $wpdb->prepare(
				"SELECT
					automation_id,
					event_id,
					execution_time_ms,
					executed_at
				FROM {$table}
				WHERE executed_at >= %s
					AND execution_time_ms IS NOT NULL
				ORDER BY execution_time_ms DESC
				LIMIT 10",
				$cutoff
			), ARRAY_A );

			// Get action type performance
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$by_event = $wpdb->get_results( $wpdb->prepare(
				"SELECT
					event_id,
					COUNT(*) as executions,
					AVG(execution_time_ms) as avg_time_ms,
					SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failures
				FROM {$table}
				WHERE executed_at >= %s
				GROUP BY event_id
				ORDER BY executions DESC",
				$cutoff
			), ARRAY_A );

			return array(
				'daily'   => $daily_stats,
				'slowest' => $slowest,
				'by_event' => $by_event,
			);
		}

		/**
		 * Clear all metrics (useful for testing)
		 *
		 * @since 6.5.0
		 */
		public static function reset() {
			self::$timers = array();
			self::$metrics = array();
		}

		/**
		 * Check if any timers are still running
		 *
		 * @return bool
		 * @since 6.5.0
		 */
		public static function has_running_timers() {
			return ! empty( self::$timers );
		}

		/**
		 * Get running timer keys
		 *
		 * @return array Timer keys
		 * @since 6.5.0
		 */
		public static function get_running_timers() {
			return array_keys( self::$timers );
		}

		/**
		 * Record a metric directly without using timers
		 *
		 * Useful for externally timed operations
		 *
		 * @param string $key      Metric key
		 * @param float  $duration Duration in seconds
		 * @param array  $meta     Additional metadata
		 * @since 6.5.0
		 */
		public static function record_metric( $key, $duration, $meta = array() ) {
			$now = microtime( true );

			self::$metrics[ $key ] = array(
				'key'           => $key,
				'duration'      => $duration,
				'duration_ms'   => round( $duration * 1000, 2 ),
				'memory_used'   => 0,
				'memory_peak'   => memory_get_peak_usage(),
				'start_time'    => $now - $duration,
				'end_time'      => $now,
				'meta'          => $meta,
			);

			self::check_slow_execution( $key, $duration, $meta );
		}
	}

endif;
