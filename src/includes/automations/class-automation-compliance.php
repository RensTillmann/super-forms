<?php
/**
 * Trigger Compliance - GDPR and Audit Trail System
 *
 * Provides compliance features for the triggers/actions system:
 * - GDPR right to deletion (delete user data)
 * - GDPR data export (export user data)
 * - PII scrubbing from logs
 * - Compliance audit trail
 * - Configuration change tracking
 * - Credential access logging
 * - Log retention policy enforcement
 * - WordPress privacy hooks integration
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Automation_Compliance
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Compliance' ) ) :

	/**
	 * SUPER_Automation_Compliance Class
	 */
	class SUPER_Automation_Compliance {

		/**
		 * Singleton instance
		 *
		 * @var SUPER_Automation_Compliance
		 */
		private static $instance = null;

		/**
		 * Audit table name (without prefix)
		 */
		const AUDIT_TABLE = 'superforms_compliance_audit';

		/**
		 * Default PII field patterns
		 *
		 * @var array
		 */
		private static $default_pii_patterns = array(
			'email',
			'phone',
			'ssn',
			'social_security',
			'credit_card',
			'card_number',
			'cvv',
			'password',
			'secret',
			'token',
			'api_key',
			'address',
			'zip',
			'postal',
			'birth',
			'dob',
		);

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Automation_Compliance
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
			// Hook into WordPress privacy system
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_data_exporter' ) );
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_data_eraser' ) );

			// Hook into entry deletion
			add_action( 'super_before_delete_entry', array( $this, 'on_entry_delete' ), 10, 1 );
		}

		/**
		 * Initialize compliance system
		 *
		 * Creates audit table if not exists
		 *
		 * @since 6.5.0
		 */
		public static function init() {
			$instance = self::instance();
			$instance->maybe_create_audit_table();
		}

		/**
		 * Create audit table if not exists
		 *
		 * @since 6.5.0
		 */
		private function maybe_create_audit_table() {
			global $wpdb;

			$table_name = $wpdb->prefix . self::AUDIT_TABLE;

			// Check if table exists
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

			if ( $table_exists === $table_name ) {
				return; // Table already exists
			}

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				action_type VARCHAR(50) NOT NULL,
				user_id BIGINT(20) UNSIGNED,
				object_type VARCHAR(50),
				object_id BIGINT(20) UNSIGNED,
				details LONGTEXT,
				ip_address VARCHAR(45),
				user_agent TEXT,
				performed_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY idx_user_id (user_id),
				KEY idx_action_type (action_type),
				KEY idx_object (object_type, object_id),
				KEY idx_performed_at (performed_at)
			) ENGINE=InnoDB {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		/**
		 * GDPR: Delete all logs for a specific entry
		 *
		 * Implements right to deletion for trigger execution logs
		 *
		 * @param int $entry_id Entry ID to delete logs for
		 * @return int Number of deleted rows
		 * @since 6.5.0
		 */
		public function delete_entry_logs( $entry_id ) {
			global $wpdb;

			$table = $wpdb->prefix . 'superforms_automation_logs';

			// Delete execution logs
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete( $table, array( 'entry_id' => $entry_id ), array( '%d' ) );

			// Log the deletion for audit trail
			$this->log_compliance_action(
				'gdpr_deletion',
				array(
					'entry_id'     => $entry_id,
					'logs_deleted' => $deleted,
					'reason'       => 'Entry deletion',
				),
				'entry',
				$entry_id
			);

			return $deleted;
		}

		/**
		 * GDPR: Export all trigger logs for a specific entry
		 *
		 * @param int    $entry_id Entry ID
		 * @param string $format   Export format (json, csv)
		 * @return string Formatted export data
		 * @since 6.5.0
		 */
		public function export_entry_logs( $entry_id, $format = 'json' ) {
			global $wpdb;

			$table = $wpdb->prefix . 'superforms_automation_logs';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$logs = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE entry_id = %d ORDER BY executed_at DESC",
				$entry_id
			), ARRAY_A );

			// Optionally scrub PII
			if ( $this->should_scrub_pii() ) {
				$logs = $this->scrub_pii_from_logs( $logs );
			}

			// Log the export
			$this->log_compliance_action(
				'data_export',
				array(
					'entry_id'    => $entry_id,
					'log_count'   => count( $logs ),
					'format'      => $format,
				),
				'entry',
				$entry_id
			);

			if ( $format === 'csv' ) {
				return $this->format_as_csv( $logs );
			}

			return wp_json_encode( $logs, JSON_PRETTY_PRINT );
		}

		/**
		 * Scrub PII from log data
		 *
		 * @param array $logs Array of log entries
		 * @return array Scrubbed logs
		 * @since 6.5.0
		 */
		public function scrub_pii_from_logs( $logs ) {
			$pii_patterns = $this->get_pii_patterns();

			foreach ( $logs as &$log ) {
				// Scrub context_data
				if ( ! empty( $log['context_data'] ) ) {
					$data = json_decode( $log['context_data'], true );
					if ( is_array( $data ) ) {
						$data = $this->recursively_scrub_pii( $data, $pii_patterns );
						$log['context_data'] = wp_json_encode( $data );
					}
				}

				// Scrub result_data
				if ( ! empty( $log['result_data'] ) ) {
					$data = json_decode( $log['result_data'], true );
					if ( is_array( $data ) ) {
						$data = $this->recursively_scrub_pii( $data, $pii_patterns );
						$log['result_data'] = wp_json_encode( $data );
					}
				}

				// Scrub error_message if it contains PII
				if ( ! empty( $log['error_message'] ) ) {
					foreach ( $pii_patterns as $pattern ) {
						if ( stripos( $log['error_message'], $pattern ) !== false ) {
							$log['error_message'] = '[REDACTED - may contain PII]';
							break;
						}
					}
				}
			}

			return $logs;
		}

		/**
		 * Recursively scrub PII from array
		 *
		 * @param array $data         Data array
		 * @param array $pii_patterns PII field patterns
		 * @return array Scrubbed data
		 * @since 6.5.0
		 */
		private function recursively_scrub_pii( $data, $pii_patterns ) {
			foreach ( $data as $key => &$value ) {
				// Check if key matches PII pattern
				$key_lower = strtolower( $key );
				foreach ( $pii_patterns as $pattern ) {
					if ( strpos( $key_lower, strtolower( $pattern ) ) !== false ) {
						$value = '[REDACTED]';
						break;
					}
				}

				// Recurse for nested arrays
				if ( is_array( $value ) ) {
					$value = $this->recursively_scrub_pii( $value, $pii_patterns );
				}
			}

			return $data;
		}

		/**
		 * Get PII patterns for scrubbing
		 *
		 * @return array PII patterns
		 * @since 6.5.0
		 */
		private function get_pii_patterns() {
			$custom = get_option( 'super_triggers_pii_fields', array() );

			if ( is_string( $custom ) ) {
				$custom = array_filter( array_map( 'trim', explode( "\n", $custom ) ) );
			}

			return array_merge( self::$default_pii_patterns, $custom );
		}

		/**
		 * Check if PII scrubbing is enabled
		 *
		 * @return bool
		 * @since 6.5.0
		 */
		private function should_scrub_pii() {
			return (bool) get_option( 'super_triggers_scrub_pii', false );
		}

		/**
		 * Log a compliance action to audit trail
		 *
		 * @param string      $action_type Action type identifier
		 * @param array       $details     Action details
		 * @param string|null $object_type Object type (trigger, entry, credential, etc.)
		 * @param int|null    $object_id   Object ID
		 * @since 6.5.0
		 */
		public function log_compliance_action( $action_type, $details = array(), $object_type = null, $object_id = null ) {
			global $wpdb;

			$table = $wpdb->prefix . self::AUDIT_TABLE;

			$data = array(
				'action_type'  => sanitize_text_field( $action_type ),
				'user_id'      => get_current_user_id() ?: null,
				'object_type'  => $object_type ? sanitize_text_field( $object_type ) : null,
				'object_id'    => $object_id ? absint( $object_id ) : null,
				'details'      => wp_json_encode( $details ),
				'ip_address'   => $this->get_client_ip(),
				'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 500 ) : '',
				'performed_at' => current_time( 'mysql' ),
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $table, $data );
		}

		/**
		 * Log credential access
		 *
		 * @param string $credential_type Type of credential accessed
		 * @param string $purpose         Purpose of access
		 * @param int    $action_id       Related action ID
		 * @since 6.5.0
		 */
		public function log_credential_access( $credential_type, $purpose = 'action_execution', $action_id = null ) {
			$this->log_compliance_action(
				'credential_access',
				array(
					'credential_type' => $credential_type,
					'purpose'         => $purpose,
					'action_id'       => $action_id,
				),
				'credential',
				null
			);
		}

		/**
		 * Log configuration change
		 *
		 * @param int    $automation_id      Trigger ID
		 * @param array  $changes         Changes made
		 * @param array  $previous_values Previous configuration values
		 * @since 6.5.0
		 */
		public function log_configuration_change( $automation_id, $changes, $previous_values = array() ) {
			$this->log_compliance_action(
				'configuration_change',
				array(
					'automation_id'      => $automation_id,
					'changes'         => $changes,
					'previous_values' => $previous_values,
				),
				'trigger',
				$automation_id
			);
		}

		/**
		 * Enforce retention policy
		 *
		 * Deletes logs older than retention period
		 *
		 * @return array Deleted counts by table
		 * @since 6.5.0
		 */
		public function enforce_retention_policy() {
			global $wpdb;

			$results = array();

			// Get retention settings
			$log_retention = (int) get_option( 'super_triggers_log_retention', 30 );
			$audit_retention = (int) get_option( 'super_triggers_audit_retention', 90 );

			// Delete old execution logs
			$logs_table = $wpdb->prefix . 'superforms_automation_logs';
			$log_cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$log_retention} days" ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results['trigger_logs'] = $wpdb->query( $wpdb->prepare(
				"DELETE FROM {$logs_table} WHERE executed_at < %s",
				$log_cutoff
			) );

			// Delete old audit logs
			$audit_table = $wpdb->prefix . self::AUDIT_TABLE;
			$audit_cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$audit_retention} days" ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results['audit_logs'] = $wpdb->query( $wpdb->prepare(
				"DELETE FROM {$audit_table} WHERE performed_at < %s",
				$audit_cutoff
			) );

			// Log cleanup
			if ( array_sum( $results ) > 0 ) {
				$this->log_compliance_action(
					'retention_cleanup',
					array(
						'trigger_logs_deleted' => $results['trigger_logs'],
						'audit_logs_deleted'   => $results['audit_logs'],
						'log_retention_days'   => $log_retention,
						'audit_retention_days' => $audit_retention,
					)
				);
			}

			return $results;
		}

		/**
		 * Export audit logs
		 *
		 * @param string $start_date Start date (Y-m-d)
		 * @param string $end_date   End date (Y-m-d)
		 * @param string $format     Export format (json, csv)
		 * @return string Formatted export data
		 * @since 6.5.0
		 */
		public function export_audit_logs( $start_date, $end_date, $format = 'json' ) {
			global $wpdb;

			$table = $wpdb->prefix . self::AUDIT_TABLE;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$logs = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE performed_at BETWEEN %s AND %s
				ORDER BY performed_at DESC",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			), ARRAY_A );

			if ( $format === 'csv' ) {
				return $this->format_as_csv( $logs );
			}

			return wp_json_encode( $logs, JSON_PRETTY_PRINT );
		}

		/**
		 * Get audit log entries
		 *
		 * @param array $args Query arguments
		 * @return array Audit log entries
		 * @since 6.5.0
		 */
		public function get_audit_logs( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'action_type' => null,
				'user_id'     => null,
				'object_type' => null,
				'object_id'   => null,
				'date_from'   => null,
				'date_to'     => null,
				'limit'       => 100,
				'offset'      => 0,
			);

			$args = wp_parse_args( $args, $defaults );
			$table = $wpdb->prefix . self::AUDIT_TABLE;

			$where = array( '1=1' );
			$values = array();

			if ( $args['action_type'] !== null ) {
				$where[] = 'action_type = %s';
				$values[] = $args['action_type'];
			}

			if ( $args['user_id'] !== null ) {
				$where[] = 'user_id = %d';
				$values[] = $args['user_id'];
			}

			if ( $args['object_type'] !== null ) {
				$where[] = 'object_type = %s';
				$values[] = $args['object_type'];
			}

			if ( $args['object_id'] !== null ) {
				$where[] = 'object_id = %d';
				$values[] = $args['object_id'];
			}

			if ( $args['date_from'] !== null ) {
				$where[] = 'performed_at >= %s';
				$values[] = $args['date_from'];
			}

			if ( $args['date_to'] !== null ) {
				$where[] = 'performed_at <= %s';
				$values[] = $args['date_to'];
			}

			$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where );
			$sql .= ' ORDER BY performed_at DESC';
			$sql .= ' LIMIT %d OFFSET %d';

			$values[] = $args['limit'];
			$values[] = $args['offset'];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
		}

		/**
		 * Format data as CSV
		 *
		 * @param array $data Data to format
		 * @return string CSV formatted string
		 * @since 6.5.0
		 */
		private function format_as_csv( $data ) {
			if ( empty( $data ) ) {
				return '';
			}

			$output = fopen( 'php://temp', 'r+' );

			// Headers
			fputcsv( $output, array_keys( $data[0] ) );

			// Data rows
			foreach ( $data as $row ) {
				fputcsv( $output, $row );
			}

			rewind( $output );
			$csv = stream_get_contents( $output );
			fclose( $output );

			return $csv;
		}

		/**
		 * Get client IP address
		 *
		 * @return string IP address
		 * @since 6.5.0
		 */
		private function get_client_ip() {
			$ip_keys = array(
				'HTTP_CF_CONNECTING_IP', // Cloudflare
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_REAL_IP',
				'REMOTE_ADDR',
			);

			foreach ( $ip_keys as $key ) {
				if ( ! empty( $_SERVER[ $key ] ) ) {
					$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
					// Handle comma-separated IPs (X-Forwarded-For)
					if ( strpos( $ip, ',' ) !== false ) {
						$ip = trim( explode( ',', $ip )[0] );
					}
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						return $ip;
					}
				}
			}

			return '0.0.0.0';
		}

		/**
		 * Register WordPress data exporter
		 *
		 * @param array $exporters Existing exporters
		 * @return array Modified exporters
		 * @since 6.5.0
		 */
		public function register_data_exporter( $exporters ) {
			$exporters['super-forms-trigger-logs'] = array(
				'exporter_friendly_name' => __( 'Super Forms Trigger Logs', 'super-forms' ),
				'callback'               => array( $this, 'wp_privacy_exporter' ),
			);

			return $exporters;
		}

		/**
		 * Register WordPress data eraser
		 *
		 * @param array $erasers Existing erasers
		 * @return array Modified erasers
		 * @since 6.5.0
		 */
		public function register_data_eraser( $erasers ) {
			$erasers['super-forms-trigger-logs'] = array(
				'eraser_friendly_name' => __( 'Super Forms Trigger Logs', 'super-forms' ),
				'callback'             => array( $this, 'wp_privacy_eraser' ),
			);

			return $erasers;
		}

		/**
		 * WordPress privacy exporter callback
		 *
		 * @param string $email_address Email address to export data for
		 * @param int    $page          Page number
		 * @return array Export data
		 * @since 6.5.0
		 */
		public function wp_privacy_exporter( $email_address, $page = 1 ) {
			$user = get_user_by( 'email', $email_address );

			if ( ! $user ) {
				return array(
					'data' => array(),
					'done' => true,
				);
			}

			global $wpdb;
			$table = $wpdb->prefix . 'superforms_automation_logs';
			$per_page = 100;
			$offset = ( $page - 1 ) * $per_page;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$logs = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d LIMIT %d OFFSET %d",
				$user->ID,
				$per_page,
				$offset
			), ARRAY_A );

			$export_items = array();
			foreach ( $logs as $log ) {
				$export_items[] = array(
					'group_id'    => 'super-forms-trigger-logs',
					'group_label' => __( 'Trigger Execution Logs', 'super-forms' ),
					'item_id'     => 'trigger-log-' . $log['id'],
					'data'        => array(
						array(
							'name'  => __( 'Event', 'super-forms' ),
							'value' => $log['event_id'],
						),
						array(
							'name'  => __( 'Status', 'super-forms' ),
							'value' => $log['status'],
						),
						array(
							'name'  => __( 'Date', 'super-forms' ),
							'value' => $log['executed_at'],
						),
					),
				);
			}

			return array(
				'data' => $export_items,
				'done' => count( $logs ) < $per_page,
			);
		}

		/**
		 * WordPress privacy eraser callback
		 *
		 * @param string $email_address Email address to erase data for
		 * @param int    $page          Page number
		 * @return array Erasure result
		 * @since 6.5.0
		 */
		public function wp_privacy_eraser( $email_address, $page = 1 ) {
			$user = get_user_by( 'email', $email_address );

			if ( ! $user ) {
				return array(
					'items_removed'  => false,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			}

			global $wpdb;
			$table = $wpdb->prefix . 'superforms_automation_logs';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete( $table, array( 'user_id' => $user->ID ), array( '%d' ) );

			$this->log_compliance_action(
				'gdpr_erasure',
				array(
					'user_id'      => $user->ID,
					'email'        => $email_address,
					'logs_deleted' => $deleted,
				),
				'user',
				$user->ID
			);

			return array(
				'items_removed'  => $deleted > 0,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		/**
		 * Hook callback for entry deletion
		 *
		 * @param int $entry_id Entry ID being deleted
		 * @since 6.5.0
		 */
		public function on_entry_delete( $entry_id ) {
			$this->delete_entry_logs( $entry_id );
		}
	}

endif;
