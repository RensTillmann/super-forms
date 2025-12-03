<?php
/**
 * Automation Logger - Centralized Logging System
 *
 * Provides comprehensive logging for the automations system with:
 * - Log levels (ERROR, WARNING, INFO, DEBUG)
 * - Database storage with structured data
 * - Debug mode with verbose output
 * - Optional browser console integration
 * - Performance timing integration
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Automation_Logger
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Logger' ) ) :

	/**
	 * SUPER_Automation_Logger Class
	 */
	class SUPER_Automation_Logger {

		/**
		 * Log level constants
		 */
		const LOG_LEVEL_ERROR   = 1;
		const LOG_LEVEL_WARNING = 2;
		const LOG_LEVEL_INFO    = 3;
		const LOG_LEVEL_DEBUG   = 4;

		/**
		 * Status constants for database storage
		 */
		const STATUS_SUCCESS = 'success';
		const STATUS_FAILED  = 'failed';
		const STATUS_SKIPPED = 'skipped';
		const STATUS_PENDING = 'pending';
		const STATUS_RETRYING = 'retrying';

		/**
		 * Singleton instance
		 *
		 * @var SUPER_Automation_Logger
		 */
		private static $instance = null;

		/**
		 * Current log level threshold
		 *
		 * @var int
		 */
		private $log_level;

		/**
		 * Debug mode flag
		 *
		 * @var bool
		 */
		private $debug_mode;

		/**
		 * Console output buffer for browser integration
		 *
		 * @var array
		 */
		private $console_buffer = array();

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Automation_Logger
		 * @since 6.5.0
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 6.5.0
		 */
		private function __construct() {
			$this->debug_mode = defined( 'SUPER_AUTOMATIONS_DEBUG' ) && SUPER_AUTOMATIONS_DEBUG;
			$this->log_level  = $this->debug_mode ? self::LOG_LEVEL_DEBUG : self::LOG_LEVEL_INFO;

			// Allow log level override via option
			$configured_level = get_option( 'super_automations_log_level', null );
			if ( $configured_level !== null && is_numeric( $configured_level ) ) {
				$this->log_level = (int) $configured_level;
			}

			// Hook into admin footer for console output
			if ( $this->debug_mode && is_admin() ) {
				add_action( 'admin_footer', array( $this, 'output_console_buffer' ), 999 );
			}
		}

		/**
		 * Prevent cloning
		 */
		private function __clone() {}

		/**
		 * Prevent unserialization
		 */
		public function __wakeup() {
			throw new Exception( 'Cannot unserialize singleton' );
		}

		/**
		 * Log an automation execution
		 *
		 * Main logging method for automation executions. Stores to database
		 * and optionally outputs to error_log and browser console.
		 *
		 * @param string $automation_name  Automation name or ID
		 * @param string $action_name      Action name or type
		 * @param string $status        Execution status (success/failed/skipped/pending/retrying)
		 * @param string $message       Log message
		 * @param array  $data          Additional data (form_id, entry_id, event, etc.)
		 * @param float  $execution_time Execution time in seconds (optional)
		 * @return int|false Insert ID on success, false on failure
		 * @since 6.5.0
		 */
		public function log_execution( $automation_name, $action_name, $status, $message, $data = array(), $execution_time = null ) {
			global $wpdb;

			// Determine log level based on status
			$log_level = $this->get_level_for_status( $status );

			// Skip if below configured threshold
			if ( $log_level > $this->log_level ) {
				return false;
			}

			// Build log entry
			$log_data = array(
				'automation_id'          => isset( $data['automation_id'] ) ? absint( $data['automation_id'] ) : 0,
				'action_id'           => isset( $data['action_id'] ) ? absint( $data['action_id'] ) : null,
				'entry_id'            => isset( $data['entry_id'] ) ? absint( $data['entry_id'] ) : null,
				'form_id'             => isset( $data['form_id'] ) ? absint( $data['form_id'] ) : null,
				'event_id'            => isset( $data['event_id'] ) ? sanitize_text_field( $data['event_id'] ) : 'unknown',
				'status'              => sanitize_text_field( $status ),
				'error_message'       => $status === self::STATUS_FAILED ? $message : null,
				'execution_time_ms'   => $execution_time !== null ? round( $execution_time * 1000 ) : null,
				'context_data'        => wp_json_encode( $this->sanitize_context_data( $data ) ),
				'result_data'         => isset( $data['result'] ) ? wp_json_encode( $data['result'] ) : null,
				'user_id'             => get_current_user_id() ?: null,
				'scheduled_action_id' => isset( $data['scheduled_action_id'] ) ? absint( $data['scheduled_action_id'] ) : null,
				'executed_at'         => current_time( 'mysql' ),
			);

			// Insert into database
			$table = $wpdb->prefix . 'superforms_automation_logs';
			$result = $wpdb->insert( $table, $log_data );

			$insert_id = $result ? $wpdb->insert_id : false;

			// Log to error_log in debug mode
			if ( $this->debug_mode ) {
				$this->write_to_error_log( $automation_name, $action_name, $status, $message, $log_level );
			}

			// Buffer for console output
			if ( $this->debug_mode && is_admin() ) {
				$this->buffer_for_console( array(
					'automation'     => $automation_name,
					'action'         => $action_name,
					'status'         => $status,
					'message'        => $message,
					'execution_time' => $execution_time,
					'timestamp'      => current_time( 'c' ),
				) );
			}

			return $insert_id;
		}

		/**
		 * Log an error
		 *
		 * @param string $message Error message
		 * @param array  $data    Additional context data
		 * @return int|false
		 * @since 6.5.0
		 */
		public function error( $message, $data = array() ) {
			if ( self::LOG_LEVEL_ERROR > $this->log_level ) {
				return false;
			}

			$automation = isset( $data['name'] ) ? $data['name'] : 'System';
			$action  = isset( $data['action_name'] ) ? $data['action_name'] : 'Error';

			return $this->log_execution( $automation, $action, self::STATUS_FAILED, $message, $data );
		}

		/**
		 * Log a warning
		 *
		 * @param string $message Warning message
		 * @param array  $data    Additional context data
		 * @return int|false
		 * @since 6.5.0
		 */
		public function warning( $message, $data = array() ) {
			if ( self::LOG_LEVEL_WARNING > $this->log_level ) {
				return false;
			}

			$automation = isset( $data['name'] ) ? $data['name'] : 'System';
			$action  = isset( $data['action_name'] ) ? $data['action_name'] : 'Warning';

			// Use a warning-specific status
			return $this->log_execution( $automation, $action, 'warning', $message, $data );
		}

		/**
		 * Log info message
		 *
		 * @param string $message Info message
		 * @param array  $data    Additional context data
		 * @return int|false
		 * @since 6.5.0
		 */
		public function info( $message, $data = array() ) {
			if ( self::LOG_LEVEL_INFO > $this->log_level ) {
				return false;
			}

			$automation = isset( $data['name'] ) ? $data['name'] : 'System';
			$action  = isset( $data['action_name'] ) ? $data['action_name'] : 'Info';

			return $this->log_execution( $automation, $action, self::STATUS_SUCCESS, $message, $data );
		}

		/**
		 * Log debug message
		 *
		 * Only logged when SUPER_AUTOMATIONS_DEBUG is true
		 *
		 * @param string $message Debug message
		 * @param array  $data    Additional context data
		 * @return int|false
		 * @since 6.5.0
		 */
		public function debug( $message, $data = array() ) {
			if ( self::LOG_LEVEL_DEBUG > $this->log_level ) {
				return false;
			}

			$automation = isset( $data['name'] ) ? $data['name'] : 'System';
			$action  = isset( $data['action_name'] ) ? $data['action_name'] : 'Debug';

			return $this->log_execution( $automation, $action, 'debug', $message, $data );
		}

		/**
		 * Get logs with filtering
		 *
		 * @param array $args Query arguments
		 * @return array Array of log entries
		 * @since 6.5.0
		 */
		public function get_logs( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'form_id'    => null,
				'entry_id'   => null,
				'automation_id' => null,
				'event_id'   => null,
				'status'     => null,
				'date_from'  => null,
				'date_to'    => null,
				'search'     => null,
				'limit'      => 100,
				'offset'     => 0,
				'orderby'    => 'executed_at',
				'order'      => 'DESC',
			);

			$args = wp_parse_args( $args, $defaults );
			$table = $wpdb->prefix . 'superforms_automation_logs';

			// Build WHERE clause
			$where = array( '1=1' );
			$values = array();

			if ( $args['form_id'] !== null ) {
				$where[] = 'form_id = %d';
				$values[] = $args['form_id'];
			}

			if ( $args['entry_id'] !== null ) {
				$where[] = 'entry_id = %d';
				$values[] = $args['entry_id'];
			}

			if ( $args['automation_id'] !== null ) {
				$where[] = 'automation_id = %d';
				$values[] = $args['automation_id'];
			}

			if ( $args['event_id'] !== null ) {
				$where[] = 'event_id = %s';
				$values[] = $args['event_id'];
			}

			if ( $args['status'] !== null ) {
				$where[] = 'status = %s';
				$values[] = $args['status'];
			}

			if ( $args['date_from'] !== null ) {
				$where[] = 'executed_at >= %s';
				$values[] = $args['date_from'];
			}

			if ( $args['date_to'] !== null ) {
				$where[] = 'executed_at <= %s';
				$values[] = $args['date_to'];
			}

			if ( $args['search'] !== null ) {
				$where[] = '(error_message LIKE %s OR context_data LIKE %s)';
				$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
				$values[] = $search_term;
				$values[] = $search_term;
			}

			// Validate orderby to prevent SQL injection
			$allowed_orderby = array( 'id', 'automation_id', 'form_id', 'entry_id', 'status', 'execution_time_ms', 'executed_at' );
			$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'executed_at';
			$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			// Build query
			$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where );
			$sql .= " ORDER BY {$orderby} {$order}";
			$sql .= ' LIMIT %d OFFSET %d';

			$values[] = $args['limit'];
			$values[] = $args['offset'];

			if ( count( $values ) > 2 ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
			} else {
				// Only limit and offset
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
			}
		}

		/**
		 * Get total count of logs matching criteria
		 *
		 * @param array $args Query arguments (same as get_logs)
		 * @return int Total count
		 * @since 6.5.0
		 */
		public function get_logs_count( $args = array() ) {
			global $wpdb;

			$table = $wpdb->prefix . 'superforms_automation_logs';

			// Build WHERE clause (same logic as get_logs)
			$where = array( '1=1' );
			$values = array();

			if ( isset( $args['form_id'] ) && $args['form_id'] !== null ) {
				$where[] = 'form_id = %d';
				$values[] = $args['form_id'];
			}

			if ( isset( $args['entry_id'] ) && $args['entry_id'] !== null ) {
				$where[] = 'entry_id = %d';
				$values[] = $args['entry_id'];
			}

			if ( isset( $args['status'] ) && $args['status'] !== null ) {
				$where[] = 'status = %s';
				$values[] = $args['status'];
			}

			if ( isset( $args['date_from'] ) && $args['date_from'] !== null ) {
				$where[] = 'executed_at >= %s';
				$values[] = $args['date_from'];
			}

			if ( isset( $args['date_to'] ) && $args['date_to'] !== null ) {
				$where[] = 'executed_at <= %s';
				$values[] = $args['date_to'];
			}

			$sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );

			if ( ! empty( $values ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				return (int) $wpdb->get_var( $wpdb->prepare( $sql, $values ) );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $sql );
		}

		/**
		 * Delete logs older than specified days
		 *
		 * @param int $days Number of days to retain
		 * @return int Number of deleted rows
		 * @since 6.5.0
		 */
		public function delete_old_logs( $days = 30 ) {
			global $wpdb;

			$table = $wpdb->prefix . 'superforms_automation_logs';
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE executed_at < %s",
					$cutoff
				)
			);

			return $deleted;
		}

		/**
		 * Delete logs for a specific entry (GDPR compliance)
		 *
		 * @param int $entry_id Entry ID
		 * @return int Number of deleted rows
		 * @since 6.5.0
		 */
		public function delete_entry_logs( $entry_id ) {
			global $wpdb;

			$table = $wpdb->prefix . 'superforms_automation_logs';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->delete( $table, array( 'entry_id' => $entry_id ), array( '%d' ) );
		}

		/**
		 * Get log level for a status
		 *
		 * @param string $status Status string
		 * @return int Log level
		 * @since 6.5.0
		 */
		private function get_level_for_status( $status ) {
			switch ( $status ) {
				case self::STATUS_FAILED:
					return self::LOG_LEVEL_ERROR;
				case self::STATUS_SKIPPED:
				case self::STATUS_RETRYING:
				case 'warning':
					return self::LOG_LEVEL_WARNING;
				case self::STATUS_SUCCESS:
				case self::STATUS_PENDING:
					return self::LOG_LEVEL_INFO;
				case 'debug':
				default:
					return self::LOG_LEVEL_DEBUG;
			}
		}

		/**
		 * Sanitize context data for storage
		 *
		 * Removes sensitive data and limits size
		 *
		 * @param array $data Raw context data
		 * @return array Sanitized data
		 * @since 6.5.0
		 */
		private function sanitize_context_data( $data ) {
			// Remove internal keys not needed in logs
			$exclude_keys = array( 'result', 'password', 'credit_card', 'ssn', 'api_key', 'secret' );

			$sanitized = array();
			foreach ( $data as $key => $value ) {
				// Skip excluded keys
				if ( in_array( strtolower( $key ), $exclude_keys, true ) ) {
					continue;
				}

				// Truncate very long string values
				if ( is_string( $value ) && strlen( $value ) > 1000 ) {
					$value = substr( $value, 0, 1000 ) . '... [truncated]';
				}

				// Skip large arrays
				if ( is_array( $value ) && count( $value ) > 100 ) {
					$value = '[Array with ' . count( $value ) . ' items - truncated]';
				}

				$sanitized[ $key ] = $value;
			}

			return $sanitized;
		}

		/**
		 * Write to PHP error log
		 *
		 * @param string $automation_name Automation name
		 * @param string $action_name     Action name
		 * @param string $status       Status
		 * @param string $message      Message
		 * @param int    $log_level    Log level
		 * @since 6.5.0
		 */
		private function write_to_error_log( $automation_name, $action_name, $status, $message, $log_level ) {
			$level_labels = array(
				self::LOG_LEVEL_ERROR   => 'ERROR',
				self::LOG_LEVEL_WARNING => 'WARNING',
				self::LOG_LEVEL_INFO    => 'INFO',
				self::LOG_LEVEL_DEBUG   => 'DEBUG',
			);

			$label = isset( $level_labels[ $log_level ] ) ? $level_labels[ $log_level ] : 'LOG';

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf(
				'[Super Forms Automation] [%s] %s - %s: %s (%s)',
				$label,
				$automation_name,
				$action_name,
				$message,
				$status
			) );
		}

		/**
		 * Buffer log entry for console output
		 *
		 * @param array $entry Log entry data
		 * @since 6.5.0
		 */
		private function buffer_for_console( $entry ) {
			$this->console_buffer[] = $entry;
		}

		/**
		 * Output console buffer as JavaScript
		 *
		 * Hooked to admin_footer when debug mode is enabled
		 *
		 * @since 6.5.0
		 */
		public function output_console_buffer() {
			if ( empty( $this->console_buffer ) ) {
				return;
			}

			?>
			<script type="text/javascript">
			(function() {
				var logs = <?php echo wp_json_encode( $this->console_buffer ); ?>;
				if (logs && logs.length) {
					console.group('%c[Super Forms Automation Debug]', 'color: #0073aa; font-weight: bold;');
					logs.forEach(function(log) {
						var style = log.status === 'failed' ? 'color: #dc3232;' :
									log.status === 'success' ? 'color: #46b450;' :
									log.status === 'warning' ? 'color: #ffb900;' : 'color: #666;';
						console.log(
							'%c[' + log.status.toUpperCase() + '] %c' + log.automation + ' → ' + log.action + ': ' + log.message,
							style,
							'color: inherit;'
						);
						if (log.execution_time) {
							console.log('  Execution time: ' + (log.execution_time * 1000).toFixed(2) + 'ms');
						}
					});
					console.groupEnd();
				}
			})();
			</script>
			<?php
		}

		/**
		 * Get console buffer contents
		 *
		 * @return array Console buffer entries
		 * @since 6.5.0
		 */
		public function get_console_buffer() {
			return $this->console_buffer;
		}

		/**
		 * Clear console buffer
		 *
		 * @return void
		 * @since 6.5.0
		 */
		public function clear_console_buffer() {
			$this->console_buffer = array();
		}

		/**
		 * Check if debug mode is enabled
		 *
		 * @return bool
		 * @since 6.5.0
		 */
		public function is_debug_mode() {
			return $this->debug_mode;
		}

		/**
		 * Get current log level
		 *
		 * @return int
		 * @since 6.5.0
		 */
		public function get_log_level() {
			return $this->log_level;
		}

		/**
		 * Set log level dynamically
		 *
		 * @param int $level Log level constant
		 * @since 6.5.0
		 */
		public function set_log_level( $level ) {
			if ( $level >= self::LOG_LEVEL_ERROR && $level <= self::LOG_LEVEL_DEBUG ) {
				$this->log_level = $level;
			}
		}

		/**
		 * Get log level label
		 *
		 * @param int $level Log level constant
		 * @return string Human-readable label
		 * @since 6.5.0
		 */
		public static function get_level_label( $level ) {
			$labels = array(
				self::LOG_LEVEL_ERROR   => __( 'Error', 'super-forms' ),
				self::LOG_LEVEL_WARNING => __( 'Warning', 'super-forms' ),
				self::LOG_LEVEL_INFO    => __( 'Info', 'super-forms' ),
				self::LOG_LEVEL_DEBUG   => __( 'Debug', 'super-forms' ),
			);

			return isset( $labels[ $level ] ) ? $labels[ $level ] : __( 'Unknown', 'super-forms' );
		}

		/**
		 * Get all available log levels
		 *
		 * @return array Array of level => label
		 * @since 6.5.0
		 */
		public static function get_log_levels() {
			return array(
				self::LOG_LEVEL_ERROR   => __( 'Errors Only', 'super-forms' ),
				self::LOG_LEVEL_WARNING => __( 'Warnings & Errors', 'super-forms' ),
				self::LOG_LEVEL_INFO    => __( 'Info, Warnings & Errors', 'super-forms' ),
				self::LOG_LEVEL_DEBUG   => __( 'All (Debug Mode)', 'super-forms' ),
			);
		}

		/**
		 * Get statistics summary
		 *
		 * @param int $days Number of days to include
		 * @return array Statistics
		 * @since 6.5.0
		 */
		public function get_statistics( $days = 7 ) {
			global $wpdb;

			$table = $wpdb->prefix . 'superforms_automation_logs';
			$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

			// Get counts by status
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$status_counts = $wpdb->get_results( $wpdb->prepare(
				"SELECT status, COUNT(*) as count FROM {$table} WHERE executed_at >= %s GROUP BY status",
				$cutoff_date
			), ARRAY_A );

			$stats = array(
				'total'           => 0,
				'success'         => 0,
				'failed'          => 0,
				'skipped'         => 0,
				'avg_execution_ms' => 0,
			);

			foreach ( $status_counts as $row ) {
				$stats['total'] += $row['count'];
				if ( isset( $stats[ $row['status'] ] ) ) {
					$stats[ $row['status'] ] = $row['count'];
				}
			}

			// Get average execution time
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$avg_time = $wpdb->get_var( $wpdb->prepare(
				"SELECT AVG(execution_time_ms) FROM {$table} WHERE executed_at >= %s AND execution_time_ms IS NOT NULL",
				$cutoff_date
			) );

			$stats['avg_execution_ms'] = $avg_time ? round( $avg_time, 2 ) : 0;

			return $stats;
		}

	/**
	 * Get log retention period in days (configurable via filter)
	 *
	 * @return int Number of days to retain logs
	 * @since 6.5.0
	 */
	public static function get_retention_period() {
		// Default: 30 days
		$default = 30;

		// Allow customization via filter
		$days = apply_filters('super_automation_log_retention_days', $default);

		// Minimum 7 days (for debugging recent issues)
		// Maximum 365 days (prevents infinite growth)
		return max(7, min(365, (int)$days));
	}

	/**
	 * Delete logs older than retention period
	 * Called daily via WP Cron hook
	 *
	 * @since 6.5.0
	 */
	public static function cleanup_old_logs() {
		global $wpdb;

		$retention_days = self::get_retention_period();

		// Delete old execution logs
		$deleted = $wpdb->query($wpdb->prepare("
			DELETE FROM {$wpdb->prefix}superforms_automation_logs
			WHERE executed_at < DATE_SUB(NOW(), INTERVAL %d DAY)
		", $retention_days));

		// Delete old compliance audit logs (separate retention - 90 days minimum for GDPR)
		$compliance_retention = max(90, $retention_days);
		$wpdb->query($wpdb->prepare("
			DELETE FROM {$wpdb->prefix}superforms_compliance_audit
			WHERE performed_at < DATE_SUB(NOW(), INTERVAL %d DAY)
		", $compliance_retention));

		// Log cleanup stats (meta-logging)
		if ($deleted > 0) {
			self::info('Automatic log cleanup completed', [
				'rows_deleted' => $deleted,
				'retention_days' => $retention_days,
				'next_cleanup' => wp_next_scheduled('super_automation_log_cleanup')
			]);
		}

		// Optimize table after large deletions (improves performance)
		if ($deleted > 10000) {
			$wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}superforms_automation_logs");
		}
	}

	/**
	 * Show admin notice if log table is getting large
	 *
	 * @since 6.5.0
	 */
	public static function maybe_show_size_warning() {
		global $wpdb;

		// Check table size
		$table_name = $wpdb->prefix . 'superforms_automation_logs';
		$size = $wpdb->get_var("
			SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
			FROM information_schema.TABLES
			WHERE table_schema = DATABASE()
				AND table_name = '{$table_name}'
		");

		// Warn if table > 100 MB
		if ($size > 100) {
			add_action('admin_notices', function() use ($size) {
				$retention_days = self::get_retention_period();
				?>
				<div class="notice notice-warning">
					<p>
						<strong>Super Forms:</strong> Automation execution logs are using
						<?php echo esc_html($size); ?> MB of database space.
						Current retention: <?php echo esc_html($retention_days); ?> days.
						<a href="<?php echo admin_url('admin.php?page=super_settings&tab=automations'); ?>">
							Reduce retention period
						</a> or
						<a href="#" onclick="if(confirm('Delete logs older than 7 days?')) { /* AJAX call */ }">
							clear old logs now
						</a>.
					</p>
				</div>
				<?php
			});
		}
	}
	/**
	 * Show InnoDB warning if detected
	 *
	 * @since 6.5.0
	 */
	public static function maybe_show_innodb_warning() {
		// Only show if warning flag is set
		if (get_option('super_innodb_warning')) {
			add_action('admin_notices', function() {
				?>
				<div class="notice notice-error">
					<p>
						<strong>Super Forms:</strong> InnoDB storage engine is not available.
						Your automation system is falling back to MyISAM, which means
						transactions will not work properly. Please contact your
						hosting provider to enable InnoDB in MySQL configuration.
					</p>
					<p>
						<a href="<?php echo admin_url('admin.php?page=super_settings&tab=automations'); ?>">
							Review Automation Settings
						</a> |
						<a href="#" onclick="if(confirm('Clear InnoDB warning and continue with MyISAM?')) { document.getElementById('super-innodb-warning').style.display='none'; }">
							Dismiss Warning
						</a>
					</p>
				</div>
				<?php
			});
		}
	}
}

// Register cleanup hook
add_action('super_automation_log_cleanup', ['SUPER_Automation_Logger', 'cleanup_old_logs']);

// Check on admin pages
add_action('admin_init', ['SUPER_Automation_Logger', 'maybe_show_size_warning']);

// Register InnoDB warning check
add_action('admin_init', ['SUPER_Automation_Logger', 'maybe_show_innodb_warning']);

endif;
