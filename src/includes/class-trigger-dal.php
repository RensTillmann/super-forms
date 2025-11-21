<?php
/**
 * Triggers Data Access Layer
 *
 * Provides database abstraction for triggers/actions system with scope-aware queries.
 * All database operations for triggers, actions, and logs go through this class.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Trigger_DAL
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) :

	/**
	 * SUPER_Trigger_DAL Class
	 *
	 * Static methods for database access following SUPER_Data_Access pattern.
	 */
	class SUPER_Trigger_DAL {

		// ─────────────────────────────────────────────────────────
		// TRIGGER CRUD OPERATIONS
		// ─────────────────────────────────────────────────────────

		/**
		 * Create new trigger
		 *
		 * @param array $data Trigger data array
		 * @return int|WP_Error Trigger ID on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function create_trigger( $data ) {
			global $wpdb;

			// Validate required fields
			if ( empty( $data['trigger_name'] ) ) {
				return new WP_Error(
					'missing_trigger_name',
					__( 'Trigger name is required', 'super-forms' )
				);
			}

			if ( empty( $data['event_id'] ) ) {
				return new WP_Error(
					'missing_event_id',
					__( 'Event ID is required', 'super-forms' )
				);
			}

			// Set defaults
			$data = wp_parse_args(
				$data,
				array(
					'scope'           => 'form',
					'scope_id'        => null,
					'conditions'      => '',
					'enabled'         => 1,
					'execution_order' => 10,
				)
			);

			// Validate scope
			$valid_scopes = array( 'form', 'global', 'user', 'role', 'site', 'network' );
			if ( ! in_array( $data['scope'], $valid_scopes, true ) ) {
				return new WP_Error(
					'invalid_scope',
					sprintf(
						__( 'Invalid scope "%s". Must be one of: %s', 'super-forms' ),
						$data['scope'],
						implode( ', ', $valid_scopes )
					)
				);
			}

			// Validate scope_id requirement
			$requires_scope_id = array( 'form', 'user', 'site' );
			if ( in_array( $data['scope'], $requires_scope_id, true ) && empty( $data['scope_id'] ) ) {
				return new WP_Error(
					'missing_scope_id',
					sprintf(
						__( 'Scope "%s" requires a scope_id', 'super-forms' ),
						$data['scope']
					)
				);
			}

			// Insert trigger
			$result = $wpdb->insert(
				$wpdb->prefix . 'superforms_triggers',
				array(
					'trigger_name'    => sanitize_text_field( $data['trigger_name'] ),
					'event_id'        => sanitize_text_field( $data['event_id'] ),
					'scope'           => sanitize_text_field( $data['scope'] ),
					'scope_id'        => ! empty( $data['scope_id'] ) ? absint( $data['scope_id'] ) : null,
					'conditions'      => is_array( $data['conditions'] ) ? wp_json_encode( $data['conditions'] ) : $data['conditions'],
					'enabled'         => absint( $data['enabled'] ),
					'execution_order' => absint( $data['execution_order'] ),
					'created_at'      => current_time( 'mysql' ),
					'updated_at'      => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_insert_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to insert trigger', 'super-forms' )
				);
			}

			return $wpdb->insert_id;
		}

		/**
		 * Get trigger by ID
		 *
		 * @param int $trigger_id Trigger ID
		 * @return array|WP_Error Trigger data or error
		 * @since 6.5.0
		 */
		public static function get_trigger( $trigger_id ) {
			global $wpdb;

			if ( empty( $trigger_id ) || ! is_numeric( $trigger_id ) ) {
				return new WP_Error(
					'invalid_trigger_id',
					__( 'Invalid trigger ID', 'super-forms' )
				);
			}

			$trigger = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}superforms_triggers WHERE id = %d",
					$trigger_id
				),
				ARRAY_A
			);

			if ( null === $trigger ) {
				return new WP_Error(
					'trigger_not_found',
					__( 'Trigger not found', 'super-forms' )
				);
			}

			// Decode conditions JSON
			if ( ! empty( $trigger['conditions'] ) ) {
				$trigger['conditions'] = json_decode( $trigger['conditions'], true );
			}

			return $trigger;
		}

		/**
		 * Update trigger
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $data       Data to update
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function update_trigger( $trigger_id, $data ) {
			global $wpdb;

			if ( empty( $trigger_id ) || ! is_numeric( $trigger_id ) ) {
				return new WP_Error(
					'invalid_trigger_id',
					__( 'Invalid trigger ID', 'super-forms' )
				);
			}

			// Verify trigger exists
			$existing = self::get_trigger( $trigger_id );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Prepare update data
			$update_data   = array();
			$update_format = array();

			if ( isset( $data['trigger_name'] ) ) {
				$update_data['trigger_name'] = sanitize_text_field( $data['trigger_name'] );
				$update_format[]             = '%s';
			}

			if ( isset( $data['event_id'] ) ) {
				$update_data['event_id'] = sanitize_text_field( $data['event_id'] );
				$update_format[]         = '%s';
			}

			if ( isset( $data['scope'] ) ) {
				$update_data['scope'] = sanitize_text_field( $data['scope'] );
				$update_format[]      = '%s';
			}

			if ( isset( $data['scope_id'] ) ) {
				$update_data['scope_id'] = ! empty( $data['scope_id'] ) ? absint( $data['scope_id'] ) : null;
				$update_format[]         = '%d';
			}

			if ( isset( $data['conditions'] ) ) {
				$update_data['conditions'] = is_array( $data['conditions'] ) ? wp_json_encode( $data['conditions'] ) : $data['conditions'];
				$update_format[]           = '%s';
			}

			if ( isset( $data['enabled'] ) ) {
				$update_data['enabled'] = absint( $data['enabled'] );
				$update_format[]        = '%d';
			}

			if ( isset( $data['execution_order'] ) ) {
				$update_data['execution_order'] = absint( $data['execution_order'] );
				$update_format[]                = '%d';
			}

			// Always update timestamp
			$update_data['updated_at'] = current_time( 'mysql' );
			$update_format[]           = '%s';

			$result = $wpdb->update(
				$wpdb->prefix . 'superforms_triggers',
				$update_data,
				array( 'id' => $trigger_id ),
				$update_format,
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_update_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to update trigger', 'super-forms' )
				);
			}

			return true;
		}

		/**
		 * Delete trigger and its actions (cascade)
		 *
		 * @param int $trigger_id Trigger ID
		 * @return bool|WP_Error True on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function delete_trigger( $trigger_id ) {
			global $wpdb;

			if ( empty( $trigger_id ) || ! is_numeric( $trigger_id ) ) {
				return new WP_Error(
					'invalid_trigger_id',
					__( 'Invalid trigger ID', 'super-forms' )
				);
			}

			// Verify trigger exists
			$existing = self::get_trigger( $trigger_id );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Delete associated actions (manual cascade for compatibility)
			$wpdb->delete(
				$wpdb->prefix . 'superforms_trigger_actions',
				array( 'trigger_id' => $trigger_id ),
				array( '%d' )
			);

			// Delete trigger
			$result = $wpdb->delete(
				$wpdb->prefix . 'superforms_triggers',
				array( 'id' => $trigger_id ),
				array( '%d' )
			);

			if ( false === $result ) {
				return new WP_Error(
					'db_delete_error',
					$wpdb->last_error ? $wpdb->last_error : __( 'Failed to delete trigger', 'super-forms' )
				);
			}

			return true;
		}

		// ─────────────────────────────────────────────────────────
		// SCOPE-AWARE QUERIES (CORE FUNCTIONALITY)
		// ─────────────────────────────────────────────────────────

		/**
		 * Get triggers by scope
		 *
		 * Examples:
		 *   get_triggers_by_scope('form', 123)  → Form #123 triggers
		 *   get_triggers_by_scope('global')     → All global triggers
		 *   get_triggers_by_scope('user', 5)    → User #5 triggers
		 *
		 * @param string    $scope        Scope type (form, global, user, role, site, network)
		 * @param int|null  $scope_id     Scope identifier (form_id, user_id, etc.)
		 * @param bool      $enabled_only Only return enabled triggers
		 * @return array Array of triggers
		 * @since 6.5.0
		 */
		public static function get_triggers_by_scope( $scope, $scope_id = null, $enabled_only = true ) {
			global $wpdb;

			$where_clauses = array( 'scope = %s' );
			$where_values  = array( $scope );

			if ( null !== $scope_id ) {
				$where_clauses[] = 'scope_id = %d';
				$where_values[]  = absint( $scope_id );
			} else {
				$where_clauses[] = 'scope_id IS NULL';
			}

			if ( $enabled_only ) {
				$where_clauses[] = 'enabled = 1';
			}

			$where = implode( ' AND ', $where_clauses );

			$query = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}superforms_triggers
				 WHERE {$where}
				 ORDER BY execution_order ASC, id ASC",
				$where_values
			);

			$results = $wpdb->get_results( $query, ARRAY_A );

			// Decode conditions for each trigger
			foreach ( $results as &$trigger ) {
				if ( ! empty( $trigger['conditions'] ) ) {
					$trigger['conditions'] = json_decode( $trigger['conditions'], true );
				}
			}

			return $results;
		}

		/**
		 * Get triggers by event ID
		 *
		 * @param string    $event_id     Event identifier
		 * @param string    $scope        Optional scope filter
		 * @param int|null  $scope_id     Optional scope ID filter
		 * @param bool      $enabled_only Only return enabled triggers
		 * @return array Array of triggers
		 * @since 6.5.0
		 */
		public static function get_triggers_by_event( $event_id, $scope = null, $scope_id = null, $enabled_only = true ) {
			global $wpdb;

			$where_clauses = array( 'event_id = %s' );
			$where_values  = array( $event_id );

			if ( null !== $scope ) {
				$where_clauses[] = 'scope = %s';
				$where_values[]  = $scope;

				if ( null !== $scope_id ) {
					$where_clauses[] = 'scope_id = %d';
					$where_values[]  = absint( $scope_id );
				} else {
					$where_clauses[] = 'scope_id IS NULL';
				}
			}

			if ( $enabled_only ) {
				$where_clauses[] = 'enabled = 1';
			}

			$where = implode( ' AND ', $where_clauses );

			$query = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}superforms_triggers
				 WHERE {$where}
				 ORDER BY execution_order ASC, id ASC",
				$where_values
			);

			$results = $wpdb->get_results( $query, ARRAY_A );

			// Decode conditions
			foreach ( $results as &$trigger ) {
				if ( ! empty( $trigger['conditions'] ) ) {
					$trigger['conditions'] = json_decode( $trigger['conditions'], true );
				}
			}

			return $results;
		}

		/**
		 * Get all active triggers that apply to a given context
		 *
		 * This is the CORE query used during event firing.
		 *
		 * Logic:
		 * 1. Get form-specific triggers (scope='form', scope_id=form_id)
		 * 2. Get global triggers (scope='global')
		 * 3. Get user-specific triggers (scope='user', scope_id=user_id)
		 * 4. Get role-based triggers (scope='role') - Phase 1.5
		 * 5. Merge and sort by execution_order
		 *
		 * @param string $event_id Event identifier
		 * @param array  $context  Event context data
		 * @return array Array of applicable triggers
		 * @since 6.5.0
		 */
		public static function get_active_triggers_for_context( $event_id, $context ) {
			$all_triggers = array();

			// 1. Form-specific triggers
			if ( ! empty( $context['form_id'] ) ) {
				$form_triggers = self::get_triggers_by_event( $event_id, 'form', $context['form_id'] );
				$all_triggers  = array_merge( $all_triggers, $form_triggers );
			}

			// 2. Global triggers
			$global_triggers = self::get_triggers_by_event( $event_id, 'global' );
			$all_triggers    = array_merge( $all_triggers, $global_triggers );

			// 3. User-specific triggers
			if ( ! empty( $context['user_id'] ) ) {
				$user_triggers = self::get_triggers_by_event( $event_id, 'user', $context['user_id'] );
				$all_triggers  = array_merge( $all_triggers, $user_triggers );
			}

			// 4. Role-based triggers (Phase 1.5)
			// TODO: Implement role-based trigger resolution
			// Will check user capabilities against role conditions

			// Sort by execution_order
			usort(
				$all_triggers,
				function ( $a, $b ) {
					return absint( $a['execution_order'] ) - absint( $b['execution_order'] );
				}
			);

			return $all_triggers;
		}

		// ─────────────────────────────────────────────────────────
		// ACTION CRUD OPERATIONS
		// ─────────────────────────────────────────────────────────

		/**
		 * Create action for trigger
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $data       Action data
		 * @return int|WP_Error Action ID on success, WP_Error on failure
		 * @since 6.5.0
		 */
		public static function create_action( $trigger_id, $data ) {
			global $wpdb;

			// Validate trigger exists
			$trigger = self::get_trigger( $trigger_id );
			if ( is_wp_error( $trigger ) ) {
				return $trigger;
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
				$wpdb->prefix . 'superforms_trigger_actions',
				array(
					'trigger_id'      => absint( $trigger_id ),
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
		 * Get all actions for a trigger
		 *
		 * @param int  $trigger_id   Trigger ID
		 * @param bool $enabled_only Only return enabled actions
		 * @return array Array of actions
		 * @since 6.5.0
		 */
		public static function get_actions( $trigger_id, $enabled_only = true ) {
			global $wpdb;

			$where = 'trigger_id = %d';
			if ( $enabled_only ) {
				$where .= ' AND enabled = 1';
			}

			$query = $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}superforms_trigger_actions
				 WHERE {$where}
				 ORDER BY execution_order ASC, id ASC",
				$trigger_id
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
				$wpdb->prefix . 'superforms_trigger_actions',
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
				$wpdb->prefix . 'superforms_trigger_actions',
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
		 * Log trigger/action execution
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
			if ( empty( $log_data['trigger_id'] ) ) {
				return new WP_Error(
					'missing_trigger_id',
					__( 'Trigger ID is required for logging', 'super-forms' )
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
				$wpdb->prefix . 'superforms_trigger_logs',
				array(
					'trigger_id'          => absint( $log_data['trigger_id'] ),
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
							'trigger_id' => $log_data['trigger_id'],
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
		 * @param array $filters Filters (trigger_id, form_id, status, etc.)
		 * @param int   $limit   Limit number of results
		 * @param int   $offset  Offset for pagination
		 * @return array Array of log entries
		 * @since 6.5.0
		 */
		public static function get_execution_logs( $filters = array(), $limit = 100, $offset = 0 ) {
			global $wpdb;

			$where_clauses = array();
			$where_values  = array();

			if ( ! empty( $filters['trigger_id'] ) ) {
				$where_clauses[] = 'trigger_id = %d';
				$where_values[]  = absint( $filters['trigger_id'] );
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

			$query = "SELECT * FROM {$wpdb->prefix}superforms_trigger_logs
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
		 * Get trigger execution statistics
		 *
		 * @param int $trigger_id Trigger ID
		 * @return array|WP_Error Statistics array or error
		 * @since 6.5.0
		 */
		public static function get_trigger_stats( $trigger_id ) {
			global $wpdb;

			if ( empty( $trigger_id ) || ! is_numeric( $trigger_id ) ) {
				return new WP_Error(
					'invalid_trigger_id',
					__( 'Invalid trigger ID', 'super-forms' )
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
					FROM {$wpdb->prefix}superforms_trigger_logs
					WHERE trigger_id = %d",
					$trigger_id
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
	}

endif;
