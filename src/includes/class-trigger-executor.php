<?php
/**
 * Trigger Executor - Action Execution Engine
 *
 * Handles synchronous execution of trigger actions.
 * Phase 2 will add Action Scheduler integration for async execution.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Trigger_Executor
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) :

	/**
	 * SUPER_Trigger_Executor Class
	 */
	class SUPER_Trigger_Executor {

		/**
		 * Fire an event and execute all matching triggers
		 *
		 * This is the main entry point for the triggers system.
		 * Called when events occur (form submission, payment, etc.)
		 *
		 * @param string $event_id Event identifier
		 * @param array  $context  Event context data
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function fire_event( $event_id, $context = array() ) {
			/**
			 * Fires before trigger event processing
			 *
			 * @param string $event_id Event identifier
			 * @param array  $context  Event context
			 * @since 6.5.0
			 */
			do_action( 'super_before_trigger_event', $event_id, $context );

			// Add event metadata to context
			$context['event_id'] = $event_id;
			if ( ! isset( $context['timestamp'] ) ) {
				$context['timestamp'] = current_time( 'mysql' );
			}

			// Resolve applicable triggers
			$triggers = SUPER_Trigger_Manager::resolve_triggers_for_event( $event_id, $context );

			if ( empty( $triggers ) ) {
				/**
				 * Fires when no triggers found for event
				 *
				 * @param string $event_id Event identifier
				 * @param array  $context  Event context
				 * @since 6.5.0
				 */
				do_action( 'super_no_triggers_for_event', $event_id, $context );
				return array();
			}

			// Execute each trigger
			$results = array();
			foreach ( $triggers as $trigger ) {
				$results[ $trigger['id'] ] = self::execute_trigger( $trigger['id'], $context );
			}

			/**
			 * Fires after trigger event processing
			 *
			 * @param string $event_id Event identifier
			 * @param array  $context  Event context
			 * @param array  $results  Execution results
			 * @since 6.5.0
			 */
			do_action( 'super_after_trigger_event', $event_id, $context, $results );

			return $results;
		}

		/**
		 * Execute a trigger (all its actions in order)
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $context    Event context
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function execute_trigger( $trigger_id, $context ) {
			$start_time = microtime( true );

			// Get trigger
			$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
			if ( is_wp_error( $trigger ) ) {
				return array(
					'success' => false,
					'error'   => $trigger->get_error_message(),
				);
			}

			// Add trigger ID to context
			$context['trigger_id'] = $trigger_id;

			/**
			 * Fires before trigger execution
			 *
			 * @param int   $trigger_id Trigger ID
			 * @param array $trigger    Trigger data
			 * @param array $context    Event context
			 * @since 6.5.0
			 */
			do_action( 'super_before_execute_trigger', $trigger_id, $trigger, $context );

			// Get actions for this trigger
			$actions = SUPER_Trigger_DAL::get_actions( $trigger_id, true );

			if ( empty( $actions ) ) {
				return array(
					'success'         => true,
					'actions_count'   => 0,
					'execution_time'  => ( microtime( true ) - $start_time ) * 1000,
				);
			}

			// Execute each action in order
			$action_results = array();
			$all_successful = true;

			foreach ( $actions as $action ) {
				$result = self::execute_action( $action, $context );

				$action_results[ $action['id'] ] = $result;

				if ( is_wp_error( $result ) || ( isset( $result['success'] ) && ! $result['success'] ) ) {
					$all_successful = false;

					// Check if we should stop on failure
					if ( self::should_stop_on_failure( $result ) ) {
						break;
					}
				}
			}

			$execution_time = ( microtime( true ) - $start_time ) * 1000;

			// Log overall trigger execution
			self::log_trigger_execution( $trigger_id, $context, $action_results, $execution_time );

			/**
			 * Fires after trigger execution
			 *
			 * @param int   $trigger_id     Trigger ID
			 * @param array $action_results Action results
			 * @param array $context        Event context
			 * @since 6.5.0
			 */
			do_action( 'super_after_execute_trigger', $trigger_id, $action_results, $context );

			return array(
				'success'         => $all_successful,
				'actions_count'   => count( $actions ),
				'actions_results' => $action_results,
				'execution_time'  => $execution_time,
			);
		}

		/**
		 * Execute a single action
		 *
		 * @param array $action  Action data from database
		 * @param array $context Event context
		 * @return array|WP_Error Execution result
		 * @since 6.5.0
		 */
		public static function execute_action( $action, $context ) {
			$start_time = microtime( true );

			// Get action instance from registry
			$registry = SUPER_Trigger_Registry::get_instance();
			$instance = $registry->get_action_instance( $action['action_type'] );

			if ( null === $instance ) {
				$error = new WP_Error(
					'action_not_found',
					sprintf(
						/* translators: %s: action type */
						__( 'Action type "%s" not found in registry', 'super-forms' ),
						$action['action_type']
					)
				);

				self::log_action_execution(
					$action['id'],
					$context,
					$error,
					( microtime( true ) - $start_time ) * 1000
				);

				return $error;
			}

			/**
			 * Fires before action execution
			 *
			 * @param int    $action_id Action ID
			 * @param string $action_type Action type
			 * @param array  $context   Event context
			 * @since 6.5.0
			 */
			do_action( 'super_before_execute_action', $action['id'], $action['action_type'], $context );

			// Execute action
			try {
				$result = $instance->execute( $context, $action['action_config'] ?? array() );
			} catch ( Exception $e ) {
				$result = new WP_Error(
					'action_execution_error',
					$e->getMessage()
				);
			}

			$execution_time = ( microtime( true ) - $start_time ) * 1000;

			// Log action execution
			self::log_action_execution( $action['id'], $context, $result, $execution_time );

			/**
			 * Fires after action execution
			 *
			 * @param int          $action_id Action ID
			 * @param array|WP_Error $result     Execution result
			 * @param array        $context   Event context
			 * @since 6.5.0
			 */
			do_action( 'super_after_execute_action', $action['id'], $result, $context );

			// Format result for consistency
			if ( is_wp_error( $result ) ) {
				return array(
					'success'        => false,
					'error'          => $result->get_error_message(),
					'error_code'     => $result->get_error_code(),
					'execution_time' => $execution_time,
				);
			}

			// Ensure result is array with success flag
			if ( ! is_array( $result ) ) {
				$result = array( 'data' => $result );
			}

			if ( ! isset( $result['success'] ) ) {
				$result['success'] = true;
			}

			$result['execution_time'] = $execution_time;

			return $result;
		}

		/**
		 * Log trigger execution
		 *
		 * @param int   $trigger_id     Trigger ID
		 * @param array $context        Event context
		 * @param array $action_results Action results
		 * @param float $execution_time Execution time in milliseconds
		 * @since 6.5.0
		 */
		private static function log_trigger_execution( $trigger_id, $context, $action_results, $execution_time ) {
			// Determine overall status
			$all_successful = true;
			$errors         = array();

			foreach ( $action_results as $result ) {
				if ( is_wp_error( $result ) || ( isset( $result['success'] ) && ! $result['success'] ) ) {
					$all_successful = false;
					if ( isset( $result['error'] ) ) {
						$errors[] = $result['error'];
					}
				}
			}

			$status = $all_successful ? 'success' : 'failed';

			// Log to database
			SUPER_Trigger_DAL::log_execution(
				array(
					'trigger_id'        => $trigger_id,
					'entry_id'          => $context['entry_id'] ?? null,
					'form_id'           => $context['form_id'] ?? null,
					'event_id'          => $context['event_id'] ?? '',
					'status'            => $status,
					'error_message'     => ! empty( $errors ) ? implode( '; ', $errors ) : '',
					'execution_time_ms' => (int) $execution_time,
					'context_data'      => $context,
					'result_data'       => $action_results,
				)
			);
		}

		/**
		 * Log action execution
		 *
		 * @param int            $action_id      Action ID
		 * @param array          $context        Event context
		 * @param array|WP_Error $result         Execution result
		 * @param float          $execution_time Execution time in milliseconds
		 * @since 6.5.0
		 */
		private static function log_action_execution( $action_id, $context, $result, $execution_time ) {
			$status        = is_wp_error( $result ) ? 'failed' : ( $result['success'] ?? true ? 'success' : 'failed' );
			$error_message = is_wp_error( $result ) ? $result->get_error_message() : ( $result['error'] ?? '' );

			SUPER_Trigger_DAL::log_execution(
				array(
					'trigger_id'        => $context['trigger_id'] ?? 0,
					'action_id'         => $action_id,
					'entry_id'          => $context['entry_id'] ?? null,
					'form_id'           => $context['form_id'] ?? null,
					'event_id'          => $context['event_id'] ?? '',
					'status'            => $status,
					'error_message'     => $error_message,
					'execution_time_ms' => (int) $execution_time,
					'context_data'      => $context,
					'result_data'       => is_wp_error( $result ) ? array() : $result,
				)
			);
		}

		/**
		 * Check if execution should stop on failure
		 *
		 * @param array|WP_Error $result Action result
		 * @return bool True if should stop, false otherwise
		 * @since 6.5.0
		 */
		private static function should_stop_on_failure( $result ) {
			// Check for stop execution flag
			if ( is_array( $result ) && isset( $result['stop_execution'] ) && $result['stop_execution'] ) {
				return true;
			}

			// Check for critical error code
			if ( is_wp_error( $result ) ) {
				$critical_codes = array( 'abort_submission', 'fatal_error', 'stop_execution' );
				if ( in_array( $result->get_error_code(), $critical_codes, true ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Test trigger with mock context
		 *
		 * For development/debugging - does NOT log to database
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $mock_context Mock context data
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function test_trigger( $trigger_id, $mock_context = array() ) {
			// Requires manage_options capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to test triggers', 'super-forms' )
				);
			}

			// Get trigger
			$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
			if ( is_wp_error( $trigger ) ) {
				return $trigger;
			}

			// Build mock context
			$context = array_merge(
				array(
					'event_id'   => $trigger['event_id'],
					'trigger_id' => $trigger_id,
					'form_id'    => $trigger['scope_id'] ?? 0,
					'entry_id'   => 0,
					'user_id'    => get_current_user_id(),
					'timestamp'  => current_time( 'mysql' ),
					'form_data'  => array(),
					'_test_mode' => true,
				),
				$mock_context
			);

			// Execute trigger WITHOUT logging
			$start_time = microtime( true );

			$actions = SUPER_Trigger_DAL::get_actions( $trigger_id, true );

			$action_results = array();
			foreach ( $actions as $action ) {
				$action_start = microtime( true );

				$registry = SUPER_Trigger_Registry::get_instance();
				$instance = $registry->get_action_instance( $action['action_type'] );

				if ( null === $instance ) {
					$action_results[ $action['id'] ] = array(
						'success' => false,
						'error'   => sprintf( 'Action type "%s" not found', $action['action_type'] ),
					);
					continue;
				}

				try {
					$result                            = $instance->execute( $context, $action['action_config'] ?? array() );
					$action_results[ $action['id'] ] = is_wp_error( $result )
						? array(
							'success' => false,
							'error'   => $result->get_error_message(),
						)
						: $result;
				} catch ( Exception $e ) {
					$action_results[ $action['id'] ] = array(
						'success' => false,
						'error'   => $e->getMessage(),
					);
				}

				$action_results[ $action['id'] ]['execution_time'] = ( microtime( true ) - $action_start ) * 1000;
			}

			return array(
				'test_mode'       => true,
				'trigger_id'      => $trigger_id,
				'trigger_name'    => $trigger['trigger_name'],
				'event_id'        => $trigger['event_id'],
				'actions_count'   => count( $actions ),
				'actions_results' => $action_results,
				'execution_time'  => ( microtime( true ) - $start_time ) * 1000,
				'context_used'    => $context,
			);
		}
	}

endif;
