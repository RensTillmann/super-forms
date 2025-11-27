<?php
/**
 * Trigger Executor - Action Execution Engine
 *
 * Handles both synchronous and asynchronous execution of trigger actions.
 * Uses Action Scheduler for async execution, retry, and scheduled actions.
 *
 * Execution Modes:
 * - SYNC: Execute immediately during request (blocks until complete)
 * - ASYNC: Queue for background execution via Action Scheduler
 * - AUTO: Let the action decide based on its configuration
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Trigger_Executor
 * @version     1.1.0
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
		 * Execution mode constants
		 */
		const MODE_SYNC  = 'sync';   // Execute immediately (blocking)
		const MODE_ASYNC = 'async';  // Queue for background execution
		const MODE_AUTO  = 'auto';   // Let action decide

		/**
		 * Actions that must run synchronously (affect form submission flow)
		 *
		 * @var array
		 */
		private static $sync_only_actions = array(
			'abort_submission',
			'stop_execution',
			'redirect_user',
			'set_variable',
			'conditional_action',
		);

		/**
		 * Actions that prefer async execution (external requests, slow operations)
		 *
		 * @var array
		 */
		private static $async_preferred_actions = array(
			'webhook',
			'send_email',
			'create_post',
			'update_post_meta',
			'update_user_meta',
			'modify_user',
		);

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
			// Start performance timing
			$timer_key = 'event_' . $event_id . '_' . microtime( true );
			if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
				SUPER_Trigger_Performance::start_timer( $timer_key );
			}

			// Log to debugger
			if ( class_exists( 'SUPER_Trigger_Debugger' ) ) {
				SUPER_Trigger_Debugger::log_event_fired( $event_id, $context );
			}

			/**
			 * Fires when a trigger event occurs
			 *
			 * Main event hook for third-party integrations to listen to.
			 *
			 * @param string $event_id Event identifier
			 * @param array  $context  Event context
			 * @since 6.5.0
			 */
			do_action( 'super_trigger_event', $event_id, $context );

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
				// Log info about no triggers
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::instance()->debug(
						sprintf( 'No triggers found for event: %s', $event_id ),
						array( 'event_id' => $event_id, 'context' => $context )
					);
				}

				/**
				 * Fires when no triggers found for event
				 *
				 * @param string $event_id Event identifier
				 * @param array  $context  Event context
				 * @since 6.5.0
				 */
				do_action( 'super_no_triggers_for_event', $event_id, $context );

				// End timing
				if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
					SUPER_Trigger_Performance::end_timer( $timer_key, array(
						'event_id'       => $event_id,
						'triggers_found' => 0,
					) );
				}

				return array();
			}

			// Log trigger resolution
			if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
				SUPER_Trigger_Logger::instance()->info(
					sprintf( 'Event %s resolved %d trigger(s)', $event_id, count( $triggers ) ),
					array( 'event_id' => $event_id, 'trigger_count' => count( $triggers ) )
				);
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

			// End timing
			if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
				SUPER_Trigger_Performance::end_timer( $timer_key, array(
					'event_id'        => $event_id,
					'triggers_found'  => count( $triggers ),
					'triggers_executed' => count( $results ),
				) );
			}

			return $results;
		}

		/**
		 * Execute a trigger (all its actions in order)
		 *
		 * @param int    $trigger_id     Trigger ID
		 * @param array  $context        Event context
		 * @param string $execution_mode Override execution mode (sync/async/auto)
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function execute_trigger( $trigger_id, $context, $execution_mode = self::MODE_AUTO ) {
			// Start performance timing
			$timer_key = 'trigger_' . $trigger_id . '_' . microtime( true );
			if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
				SUPER_Trigger_Performance::start_timer( $timer_key );
			}

			// Get trigger
			$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
			if ( is_wp_error( $trigger ) ) {
				// Log error
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::instance()->error(
						sprintf( 'Failed to load trigger %d: %s', $trigger_id, $trigger->get_error_message() ),
						array( 'trigger_id' => $trigger_id )
					);
				}

				return array(
					'success' => false,
					'error'   => $trigger->get_error_message(),
				);
			}

			// Log to debugger
			if ( class_exists( 'SUPER_Trigger_Debugger' ) ) {
				SUPER_Trigger_Debugger::log_trigger_evaluated( $trigger_id, $trigger['trigger_name'], true, array() );
			}

			// Add trigger ID to context
			$context['trigger_id'] = $trigger_id;

			/**
			 * Filter the execution mode for a trigger
			 *
			 * @param string $execution_mode Execution mode (sync/async/auto)
			 * @param int    $trigger_id     Trigger ID
			 * @param array  $context        Event context
			 * @since 6.5.0
			 */
			$execution_mode = apply_filters( 'super_trigger_execution_mode', $execution_mode, $trigger_id, $context );

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
			$action_results  = array();
			$all_successful  = true;
			$actions_queued  = 0;
			$actions_sync    = 0;

			foreach ( $actions as $action ) {
				// Determine execution mode for this specific action
				$action_mode = self::get_action_execution_mode( $action, $execution_mode, $context );

				if ( self::MODE_ASYNC === $action_mode && SUPER_Trigger_Scheduler::is_available() ) {
					// Queue for async execution
					$queue_result = self::queue_action( $trigger_id, $action, $context );

					$action_results[ $action['id'] ] = $queue_result;
					$actions_queued++;

					// Queued actions are considered successful at this point
					if ( is_wp_error( $queue_result ) || ( isset( $queue_result['success'] ) && ! $queue_result['success'] ) ) {
						$all_successful = false;
					}
				} else {
					// Execute synchronously
					$result = self::execute_action( $action, $context );
					$actions_sync++;

					$action_results[ $action['id'] ] = $result;

					if ( is_wp_error( $result ) || ( isset( $result['success'] ) && ! $result['success'] ) ) {
						$all_successful = false;

						// Check if we should stop on failure
						if ( self::should_stop_on_failure( $result ) ) {
							break;
						}
					}
				}
			}

			// End performance timing
			$execution_time = 0;
			if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
				$metrics = SUPER_Trigger_Performance::end_timer( $timer_key, array(
					'trigger_id'      => $trigger_id,
					'actions_count'   => count( $actions ),
					'actions_sync'    => $actions_sync,
					'actions_queued'  => $actions_queued,
				) );
				$execution_time = $metrics ? $metrics['duration_ms'] : 0;
			}

			// Log overall trigger execution
			self::log_trigger_execution( $trigger_id, $context, $action_results, $execution_time );

			// Log summary to Logger
			if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
				$log_level = $all_successful ? 'info' : 'warning';
				$logger    = SUPER_Trigger_Logger::instance();
				$logger->$log_level(
					sprintf(
						'Trigger %d completed: %d actions (%d sync, %d queued), %.2fms',
						$trigger_id,
						count( $actions ),
						$actions_sync,
						$actions_queued,
						$execution_time
					),
					array(
						'trigger_id'   => $trigger_id,
						'success'      => $all_successful,
						'actions_sync' => $actions_sync,
						'actions_queued' => $actions_queued,
					)
				);
			}

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
				'actions_sync'    => $actions_sync,
				'actions_queued'  => $actions_queued,
				'actions_results' => $action_results,
				'execution_time'  => $execution_time,
			);
		}

		/**
		 * Determine the execution mode for a specific action
		 *
		 * @param array  $action         Action data
		 * @param string $trigger_mode   Trigger-level execution mode
		 * @param array  $context        Event context
		 * @return string Execution mode (sync or async)
		 * @since 6.5.0
		 */
		private static function get_action_execution_mode( $action, $trigger_mode, $context ) {
			$action_type = $action['action_type'];

			// Check for forced sync execution in context
			if ( ! empty( $context['_force_sync'] ) ) {
				return self::MODE_SYNC;
			}

			// Sync-only actions must run synchronously
			if ( in_array( $action_type, self::$sync_only_actions, true ) ) {
				return self::MODE_SYNC;
			}

			// If trigger mode is explicitly set (not auto), use it
			if ( self::MODE_SYNC === $trigger_mode ) {
				return self::MODE_SYNC;
			}

			if ( self::MODE_ASYNC === $trigger_mode ) {
				// Even if trigger says async, some actions can't be async
				return self::MODE_ASYNC;
			}

			// AUTO mode: check action configuration
			$action_config = $action['action_config'] ?? array();

			// Check if action has explicit execution_mode config
			if ( isset( $action_config['execution_mode'] ) ) {
				if ( self::MODE_ASYNC === $action_config['execution_mode'] ) {
					return self::MODE_ASYNC;
				}
				if ( self::MODE_SYNC === $action_config['execution_mode'] ) {
					return self::MODE_SYNC;
				}
			}

			// Check if action type prefers async
			if ( in_array( $action_type, self::$async_preferred_actions, true ) ) {
				return self::MODE_ASYNC;
			}

			// Default to sync for safety
			return self::MODE_SYNC;
		}

		/**
		 * Queue an action for async execution via Action Scheduler
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $action     Action data
		 * @param array $context    Event context
		 * @return array Queue result
		 * @since 6.5.0
		 */
		public static function queue_action( $trigger_id, $action, $context ) {
			$scheduler = SUPER_Trigger_Scheduler::get_instance();

			// Schedule for immediate async execution (delay = 0)
			$scheduled_id = $scheduler->schedule_trigger_action(
				$trigger_id,
				$action['id'],
				$context,
				0 // Immediate (but async)
			);

			if ( false === $scheduled_id ) {
				// Fallback to sync if scheduling fails
				error_log( 'Super Forms: Failed to queue action, falling back to sync: ' . $action['id'] );
				return self::execute_action( $action, $context );
			}

			return array(
				'success'      => true,
				'queued'       => true,
				'scheduled_id' => $scheduled_id,
				'action_id'    => $action['id'],
				'action_type'  => $action['action_type'],
				'message'      => __( 'Action queued for background execution', 'super-forms' ),
			);
		}

		/**
		 * Force synchronous execution of a trigger
		 *
		 * Useful for testing or when you need immediate results.
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $context    Event context
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function execute_trigger_sync( $trigger_id, $context ) {
			$context['_force_sync'] = true;
			return self::execute_trigger( $trigger_id, $context, self::MODE_SYNC );
		}

		/**
		 * Force asynchronous execution of a trigger
		 *
		 * All eligible actions will be queued for background execution.
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param array $context    Event context
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function execute_trigger_async( $trigger_id, $context ) {
			return self::execute_trigger( $trigger_id, $context, self::MODE_ASYNC );
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
			// Start performance timing
			$timer_key = 'action_' . $action['id'] . '_' . $action['action_type'] . '_' . microtime( true );
			if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
				SUPER_Trigger_Performance::start_timer( $timer_key );
			}

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

				// Log error
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::instance()->error(
						sprintf( 'Action type "%s" not found in registry', $action['action_type'] ),
						array( 'action_id' => $action['id'], 'action_type' => $action['action_type'] )
					);
				}

				// End timer
				$execution_time = 0;
				if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
					$metrics = SUPER_Trigger_Performance::end_timer( $timer_key );
					$execution_time = $metrics ? $metrics['duration_ms'] : 0;
				}

				self::log_action_execution(
					$action['id'],
					$context,
					$error,
					$execution_time
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

				// Log exception
				if ( class_exists( 'SUPER_Trigger_Logger' ) ) {
					SUPER_Trigger_Logger::instance()->error(
						sprintf( 'Action %s threw exception: %s', $action['action_type'], $e->getMessage() ),
						array(
							'action_id'   => $action['id'],
							'action_type' => $action['action_type'],
							'exception'   => $e->getMessage(),
							'trace'       => $e->getTraceAsString(),
						)
					);
				}
			}

			// End performance timing
			$execution_time = 0;
			if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
				$metrics = SUPER_Trigger_Performance::end_timer( $timer_key, array(
					'action_id'   => $action['id'],
					'action_type' => $action['action_type'],
					'success'     => ! is_wp_error( $result ),
				) );
				$execution_time = $metrics ? $metrics['duration_ms'] : 0;
			}

			// Log to debugger
			if ( class_exists( 'SUPER_Trigger_Debugger' ) ) {
				SUPER_Trigger_Debugger::log_action_executed(
					$action['id'],
					$action['action_type'],
					! is_wp_error( $result ),
					is_wp_error( $result ) ? $result->get_error_message() : null
				);
			}

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

			/**
			 * Fires after trigger action is executed
			 *
			 * Provides action type and config for easier test hookup.
			 *
			 * @param string       $action_type Action type identifier
			 * @param array|WP_Error $result     Execution result
			 * @param array        $context    Event context
			 * @param array        $config     Action configuration
			 * @since 6.5.0
			 */
			do_action( 'super_trigger_action_executed', $action['action_type'], $result, $context, $action['action_config'] ?? array() );

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
				if ( is_wp_error( $result ) ) {
					$all_successful = false;
					$errors[] = $result->get_error_message();
				} elseif ( isset( $result['success'] ) && ! $result['success'] ) {
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
		 * Determine execution mode for an action type
		 *
		 * Public wrapper for testing execution mode logic.
		 *
		 * @param string $action_type Action type identifier
		 * @param array  $config      Action configuration (optional)
		 * @return string Execution mode (sync or async)
		 * @since 6.5.0
		 */
		public static function determine_execution_mode( $action_type, $config = array() ) {
			// Build minimal action array for internal method
			$action = array(
				'action_type'   => $action_type,
				'action_config' => $config,
			);

			return self::get_action_execution_mode( $action, self::MODE_AUTO, array() );
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
