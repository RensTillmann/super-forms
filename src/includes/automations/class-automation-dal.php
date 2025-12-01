<?php
/**
 * Automations Data Access Layer
 *
 * Provides database abstraction for automations system.
 * Node-level scope architecture: scope is configured in event nodes within workflow_graph JSON.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Automation_DAL
 * @version     2.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_DAL' ) ) :

	/**
	 * SUPER_Automation_DAL Class
	 *
	 * Static methods for database access following SUPER_Data_Access pattern.
	 */
	class SUPER_Automation_DAL {

		// ─────────────────────────────────────────────────────────
		// AUTOMATION CRUD OPERATIONS
		// ─────────────────────────────────────────────────────────

		/**
		 * Create new automation
		 *
		 * @param array $data Automation data array (name, type, workflow_graph, enabled)
		 * @return int|WP_Error Automation ID on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function create_automation( $data ) {
			global $wpdb;

			// Validate required fields
			if ( empty( $data['name'] ) ) {
				return new WP_Error(
					'missing_name',
					__( 'Automation name is required', 'super-forms' )
				);
			}

			// Set defaults
			$data = wp_parse_args(
				$data,
				array(
					'type'  => 'visual',
					'workflow_graph' => '',
					'enabled'        => 1,
				)
			);

			// Validate type
			$valid_types = array( 'visual', 'code' );
			if ( ! in_array( $data['type'], $valid_types, true ) ) {
				return new WP_Error(
					'invalid_type',
					sprintf(
						__( 'Invalid workflow type "%s". Must be "visual" or "code"', 'super-forms' ),
						$data['type']
					)
				);
			}

			// Prepare workflow_graph
			$workflow_graph = $data['workflow_graph'];
			if ( is_array( $workflow_graph ) ) {
				$workflow_graph = wp_json_encode( $workflow_graph );
			}

			// Insert automation
			$insert_data = array(
				'name'   => sanitize_text_field( $data['name'] ),
				'type'  => sanitize_text_field( $data['type'] ),
				'workflow_graph' => $workflow_graph,
				'enabled'        => absint( $data['enabled'] ),
				'created_at'     => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			);

			$result = $wpdb->insert(
				$wpdb->prefix . 'superforms_automations',
				$insert_data,
				array( '%s', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_insert_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to insert automation', 'super-forms' )
				);
			}

			return $wpdb->insert_id;
		}

		/**
		 * Get automation by ID
		 *
		 * @param int $automation_id Automation ID
		 * @return array|WP_Error Automation data or error
		 * @since 6.5.0
		 */
		public static function get_automation( $automation_id ) {
			global $wpdb;

			if ( empty( $automation_id ) || ! is_numeric( $automation_id ) ) {
				return new WP_Error(
					'invalid_automation_id',
					__( 'Invalid automation ID', 'super-forms' )
				);
			}

			$automation = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}superforms_automations WHERE id = %d",
					$automation_id
				),
				ARRAY_A
			);

			if ( null === $automation ) {
				return new WP_Error(
					'automation_not_found',
					__( 'Automation not found', 'super-forms' )
				);
			}

			// Decode workflow_graph JSON
			if ( ! empty( $trigger['workflow_graph'] ) ) {
				$trigger['workflow_graph'] = json_decode( $trigger['workflow_graph'], true );
			}

			return $trigger;
		}

		/**
		 * Get all triggers
		 *
		 * @param bool $enabled_only Only return enabled triggers
		 * @return array Array of triggers
		 * @since 6.5.0
		 */
		public static function get_all_automations( $enabled_only = true ) {
			global $wpdb;

			$where = $enabled_only ? 'WHERE enabled = 1' : '';

			$query = "SELECT * FROM {$wpdb->prefix}superforms_automations
					  {$where}
					  ORDER BY id ASC";

			$results = $wpdb->get_results( $query, ARRAY_A );

			// Decode workflow_graph for each automation
			foreach ( $results as &$automation ) {
				if ( ! empty( $automation['workflow_graph'] ) ) {
					$automation['workflow_graph'] = json_decode( $automation['workflow_graph'], true );
				}
			}

			return $results;
		}

		/**
		 * Update automation
		 *
		 * @param int   $automation_id Automation ID
		 * @param array $data       Data to update
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function update_automation( $automation_id, $data ) {
			global $wpdb;

			if ( empty( $automation_id ) || ! is_numeric( $automation_id ) ) {
				return new WP_Error(
					'invalid_automation_id',
					__( 'Invalid automation ID', 'super-forms' )
				);
			}

			// Verify automation exists
			$existing = self::get_automation( $automation_id );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Prepare update data
			$update_data   = array();
			$update_format = array();

			if ( isset( $data['name'] ) ) {
				$update_data['name'] = sanitize_text_field( $data['name'] );
				$update_format[]             = '%s';
			}

			if ( isset( $data['type'] ) ) {
				$update_data['type'] = sanitize_text_field( $data['type'] );
				$update_format[]              = '%s';
			}

			if ( isset( $data['workflow_graph'] ) ) {
				$workflow_graph = $data['workflow_graph'];
				if ( is_array( $workflow_graph ) ) {
					$workflow_graph = wp_json_encode( $workflow_graph );
				}
				$update_data['workflow_graph'] = $workflow_graph;
				$update_format[]               = '%s';
			}

			if ( isset( $data['enabled'] ) ) {
				$update_data['enabled'] = absint( $data['enabled'] );
				$update_format[]        = '%d';
			}

			// Always update timestamp
			$update_data['updated_at'] = current_time( 'mysql' );
			$update_format[]           = '%s';

			$result = $wpdb->update(
				$wpdb->prefix . 'superforms_automations',
				$update_data,
				array( 'id' => $automation_id ),
				$update_format,
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_update_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to update automation', 'super-forms' )
				);
			}

			return true;
		}

		/**
		 * Delete automation and its actions (cascade)
		 *
		 * @param int $automation_id Automation ID
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function delete_automation( $automation_id ) {
			global $wpdb;

			if ( empty( $automation_id ) || ! is_numeric( $automation_id ) ) {
				return new WP_Error(
					'invalid_automation_id',
					__( 'Invalid automation ID', 'super-forms' )
				);
			}

			// Verify automation exists
			$existing = self::get_automation( $automation_id );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Delete associated actions (manual cascade for compatibility)
			$wpdb->delete(
				$wpdb->prefix . 'superforms_automation_actions',
				array( 'automation_id' => $automation_id ),
				array( '%d' )
			);

			// Delete automation
			$result = $wpdb->delete(
				$wpdb->prefix . 'superforms_automations',
				array( 'id' => $automation_id ),
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_delete_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to delete automation', 'super-forms' )
				);
			}

			return true;
		}

		// ─────────────────────────────────────────────────────────
		// ACTION CRUD OPERATIONS
		// ─────────────────────────────────────────────────────────

		/**
		 * Create action for automation
		 *
		 * @param int   $automation_id Automation ID
		 * @param array $data       Action data
		 * @return int|WP_Error Action ID on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function create_action( $automation_id, $data ) {
			global $wpdb;

			// Validate automation exists
			$automation = self::get_automation( $automation_id );
			if ( is_wp_error( $automation ) ) {
				return $automation;
			}

			// Validate required fields
			if ( empty( $data['action_type'] ) ) {
				return new WP_Error(
					'missing_action_type',
					__( 'Action type is required', 'super-forms' )
				);
			}

			// Set defaults
			$data = wp_parse_args(
				$data,
				array(
					'action_config'   => '',
					'execution_order' => 10,
					'enabled'         => 1,
				)
			);

			$result = $wpdb->insert(
				$wpdb->prefix . 'superforms_automation_actions',
				array(
					'automation_id'      => absint( $automation_id ),
					'action_type'     => sanitize_text_field( $data['action_type'] ),
					'action_config'   => is_array( $data['action_config'] ) ? wp_json_encode( $data['action_config'] ) : $data['action_config'],
					'execution_order' => absint( $data['execution_order'] ),
					'enabled'         => absint( $data['enabled'] ),
					'created_at'      => current_time( 'mysql' ),
					'updated_at'      => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_insert_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to insert action', 'super-forms' )
				);
			}

			return $wpdb->insert_id;
		}

		/**
		 * Get all actions for an automation
		 *
		 * @param int  $automation_id   Automation ID
		 * @param bool $enabled_only Only return enabled actions
		 * @return array Array of actions
		 * @since 6.5.0
		 */
		public static function get_actions( $automation_id, $enabled_only = true ) {
			global $wpdb;

			$where = 'automation_id = %d';
			if ( $enabled_only ) {
				$where .= ' AND enabled = 1';
			}

			$query = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}superforms_automation_actions
				 WHERE {$where}
				 ORDER BY execution_order ASC, id ASC",
				$automation_id
			);

			$results = $wpdb->get_results( $query, ARRAY_A );

			// Decode action_config
			foreach ( $results as &$action ) {
				if ( ! empty( $action['action_config'] ) ) {
					$action['action_config'] = json_decode( $action['action_config'], true );
				}
			}

			return $results;
		}

		/**
		 * Update action
		 *
		 * @param int   $action_id Action ID
		 * @param array $data      Data to update
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function update_action( $action_id, $data ) {
			global $wpdb;

			if ( empty( $action_id ) || ! is_numeric( $action_id ) ) {
				return new WP_Error(
					'invalid_action_id',
					__( 'Invalid action ID', 'super-forms' )
				);
			}

			// Prepare update data
			$update_data   = array();
			$update_format = array();

			if ( isset( $data['action_type'] ) ) {
				$update_data['action_type'] = sanitize_text_field( $data['action_type'] );
				$update_format[]            = '%s';
			}

			if ( isset( $data['action_config'] ) ) {
				$update_data['action_config'] = is_array( $data['action_config'] ) ? wp_json_encode( $data['action_config'] ) : $data['action_config'];
				$update_format[]              = '%s';
			}

			if ( isset( $data['execution_order'] ) ) {
				$update_data['execution_order'] = absint( $data['execution_order'] );
				$update_format[]                = '%d';
			}

			if ( isset( $data['enabled'] ) ) {
				$update_data['enabled'] = absint( $data['enabled'] );
				$update_format[]        = '%d';
			}

			$update_data['updated_at'] = current_time( 'mysql' );
			$update_format[]           = '%s';

			$result = $wpdb->update(
				$wpdb->prefix . 'superforms_automation_actions',
				$update_data,
				array( 'id' => $action_id ),
				$update_format,
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_update_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to update action', 'super-forms' )
				);
			}

			return true;
		}

		/**
		 * Delete action
		 *
		 * @param int $action_id Action ID
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function delete_action( $action_id ) {
			global $wpdb;

			if ( empty( $action_id ) || ! is_numeric( $action_id ) ) {
				return new WP_Error(
					'invalid_action_id',
					__( 'Invalid action ID', 'super-forms' )
				);
			}

			$result = $wpdb->delete(
				$wpdb->prefix . 'superforms_automation_actions',
				array( 'id' => $action_id ),
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_delete_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to delete action', 'super-forms' )
				);
			}

			return true;
		}

		// ─────────────────────────────────────────────────────────
		// LOGGING METHODS (DUAL STORAGE)
		// ─────────────────────────────────────────────────────────

		/**
		 * Log automation/action execution
		 *
		 * Dual storage strategy:
		 * - Logs table: Admin debugging, analytics, compliance
		 * - Entry meta: Quick user-facing lookups
		 *
		 * @param array $log_data Log data array
		 * @return int|WP_Error Log ID on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function log_execution( $log_data ) {
			global $wpdb;

			// Validate required fields
			if ( empty( $log_data['automation_id'] ) ) {
				return new WP_Error(
					'missing_automation_id',
					__( 'Automation ID is required for logging', 'super-forms' )
				);
			}

			if ( empty( $log_data['event_id'] ) ) {
				return new WP_Error(
					'missing_event_id',
					__( 'Event ID is required for logging', 'super-forms' )
				);
			}

			// Set defaults
			$defaults = array(
				'action_id'           => null,
				'entry_id'            => null,
				'form_id'             => null,
				'status'              => 'success',
				'error_message'       => '',
				'execution_time_ms'   => 0,
				'context_data'        => '',
				'result_data'         => '',
				'user_id'             => get_current_user_id(),
				'scheduled_action_id' => null,
			);

			$log_data = wp_parse_args( $log_data, $defaults );

			// Insert into logs table
			$result = $wpdb->insert(
				$wpdb->prefix . 'superforms_automation_logs',
				array(
					'automation_id'          => absint( $log_data['automation_id'] ),
					'action_id'           => ! empty( $log_data['action_id'] ) ? absint( $log_data['action_id'] ) : null,
					'entry_id'            => ! empty( $log_data['entry_id'] ) ? absint( $log_data['entry_id'] ) : null,
					'form_id'             => ! empty( $log_data['form_id'] ) ? absint( $log_data['form_id'] ) : null,
					'event_id'            => sanitize_text_field( $log_data['event_id'] ),
					'status'              => sanitize_text_field( $log_data['status'] ),
					'error_message'       => sanitize_textarea_field( $log_data['error_message'] ),
					'execution_time_ms'   => absint( $log_data['execution_time_ms'] ),
					'context_data'        => is_array( $log_data['context_data'] ) ? wp_json_encode( $log_data['context_data'] ) : $log_data['context_data'],
					'result_data'         => is_array( $log_data['result_data'] ) ? wp_json_encode( $log_data['result_data'] ) : $log_data['result_data'],
					'user_id'             => absint( $log_data['user_id'] ),
					'scheduled_action_id' => ! empty( $log_data['scheduled_action_id'] ) ? absint( $log_data['scheduled_action_id'] ) : null,
					'executed_at'         => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_insert_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to insert log entry', 'super-forms' )
				);
			}

			$log_id = $wpdb->insert_id;

			// Dual storage: Also store in entry meta via Data Access Layer
			if ( ! empty( $log_data['entry_id'] ) && class_exists( 'SUPER_Data_Access' ) && method_exists( 'SUPER_Data_Access', 'update_entry_data' ) ) {
				SUPER_Data_Access::update_entry_data(
					$log_data['entry_id'],
					array(
						'_super_last_trigger_execution' => array(
							'log_id'     => $log_id,
							'automation_id' => $log_data['automation_id'],
							'event_id'   => $log_data['event_id'],
							'status'     => $log_data['status'],
							'timestamp'  => current_time( 'mysql' ),
						),
					)
				);
			}

			return $log_id;
		}

		/**
		 * Get execution logs with filters
		 *
		 * @param array $filters Filters (automation_id, form_id, status, etc.)
		 * @param int   $limit   Limit number of results
		 * @param int   $offset  Offset for pagination
		 * @return array Array of log entries
		 * @since 6.5.0
		 */
		public static function get_execution_logs( $filters = array(), $limit = 100, $offset = 0 ) {
			global $wpdb;

			$where_clauses = array();
			$where_values  = array();

			if ( ! empty( $filters['automation_id'] ) ) {
				$where_clauses[] = 'automation_id = %d';
				$where_values[]  = absint( $filters['automation_id'] );
			}

			if ( ! empty( $filters['form_id'] ) ) {
				$where_clauses[] = 'form_id = %d';
				$where_values[]  = absint( $filters['form_id'] );
			}

			if ( ! empty( $filters['entry_id'] ) ) {
				$where_clauses[] = 'entry_id = %d';
				$where_values[]  = absint( $filters['entry_id'] );
			}

			if ( ! empty( $filters['status'] ) ) {
				$where_clauses[] = 'status = %s';
				$where_values[]  = sanitize_text_field( $filters['status'] );
			}

			if ( ! empty( $filters['event_id'] ) ) {
				$where_clauses[] = 'event_id = %s';
				$where_values[]  = sanitize_text_field( $filters['event_id'] );
			}

			$where = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

			$query = "SELECT * FROM {$wpdb->prefix}superforms_automation_logs
					  {$where}
					  ORDER BY executed_at DESC
					  LIMIT %d OFFSET %d";

			$where_values[] = absint( $limit );
			$where_values[] = absint( $offset );

			$prepared_query = $wpdb->prepare( $query, $where_values );

			$results = $wpdb->get_results( $prepared_query, ARRAY_A );

			// Decode JSON fields
			foreach ( $results as &$log ) {
				if ( ! empty( $log['context_data'] ) ) {
					$log['context_data'] = json_decode( $log['context_data'], true );
				}
				if ( ! empty( $log['result_data'] ) ) {
					$log['result_data'] = json_decode( $log['result_data'], true );
				}
			}

			return $results;
		}

		/**
		 * Get automation execution statistics
		 *
		 * @param int $automation_id Automation ID
		 * @return array|WP_Error Statistics array or error
		 * @since 6.5.0
		 */
		public static function get_automation_stats( $automation_id ) {
			global $wpdb;

			if ( empty( $automation_id ) || ! is_numeric( $automation_id ) ) {
				return new WP_Error(
					'invalid_automation_id',
					__( 'Invalid automation ID', 'super-forms' )
				);
			}

			$stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) as total_executions,
						SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
						SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failure_count,
						AVG(execution_time_ms) as avg_execution_time_ms,
						MAX(executed_at) as last_executed_at
					FROM {$wpdb->prefix}superforms_automation_logs
					WHERE automation_id = %d",
					$automation_id
				),
				ARRAY_A
			);

			if ( null === $stats ) {
				return array(
					'total_executions'      => 0,
					'success_count'         => 0,
					'failure_count'         => 0,
					'avg_execution_time_ms' => 0,
					'last_executed_at'      => null,
				);
			}

			return $stats;
		}

	/**
	 * Execute callback within transaction (if supported)
	 *
	 * @param callable $callback Function to execute within transaction
	 * @return mixed Callback result
	 * @since 6.5.0
	 */
	public static function transaction($callback) {
		global $wpdb;

		// Check if InnoDB is in use
		$using_innodb = self::is_using_innodb();

		if ($using_innodb) {
			// Use transaction
			$wpdb->query('START TRANSACTION');

			try {
				$result = call_user_func($callback);
				$wpdb->query('COMMIT');
				return $result;

			} catch (Exception $e) {
				$wpdb->query('ROLLBACK');
				throw $e;
			}

		} else {
			// No transaction support - just execute with warning
			error_log('[Super Forms] MyISAM detected - executing without transaction support');

			return call_user_func($callback);
		}
	}

	/**
	 * Check if automation tables are using InnoDB (cached)
	 *
	 * @return bool True if using InnoDB
	 * @since 6.5.0
	 */
	private static function is_using_innodb() {
		static $cache = null;

		if ($cache !== null) {
			return $cache;
		}

		global $wpdb;

		$engine = $wpdb->get_var("
			SELECT ENGINE
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
				AND TABLE_NAME = '{$wpdb->prefix}superforms_automations'
		");

		$cache = (strtolower($engine) === 'innodb');
		return $cache;
	}
}
endif;
