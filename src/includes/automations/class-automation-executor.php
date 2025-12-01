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
 * @class       SUPER_Automation_Executor
 * @version     1.1.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Executor' ) ) :

	/**
	 * SUPER_Automation_Executor Class
	 */
	class SUPER_Automation_Executor {

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
			if ( class_exists( 'SUPER_Automation_Performance' ) ) {
				SUPER_Automation_Performance::start_timer( $timer_key );
			}

			// Log to debugger
			if ( class_exists( 'SUPER_Automation_Debugger' ) ) {
				SUPER_Automation_Debugger::log_event_fired( $event_id, $context );
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
			do_action( 'super_automation_event', $event_id, $context );

			/**
			 * Fires before trigger event processing
			 *
			 * @param string $event_id Event identifier
			 * @param array  $context  Event context
			 * @since 6.5.0
			 */
			do_action( 'super_before_automation_event', $event_id, $context );

			// Add event metadata to context
			$context['event_id'] = $event_id;
			if ( ! isset( $context['timestamp'] ) ) {
				$context['timestamp'] = current_time( 'mysql' );
			}

			// Resolve applicable triggers
			$automations = SUPER_Automation_Manager::resolve_automations_for_event( $event_id, $context );

			if ( empty( $automations ) ) {
				// Log info about no automations
				if ( class_exists( 'SUPER_Automation_Logger' ) ) {
					SUPER_Automation_Logger::instance()->debug(
						sprintf( 'No automations found for event: %s', $event_id ),
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
				do_action( 'super_no_automations_for_event', $event_id, $context );

				// End timing
				if ( class_exists( 'SUPER_Automation_Performance' ) ) {
					SUPER_Automation_Performance::end_timer( $timer_key, array(
						'event_id'       => $event_id,
						'automations_found' => 0,
					) );
				}

				return array();
			}

			// Log automation resolution
			if ( class_exists( 'SUPER_Automation_Logger' ) ) {
				SUPER_Automation_Logger::instance()->info(
					sprintf( 'Event %s resolved %d automation(s)', $event_id, count( $automations ) ),
					array( 'event_id' => $event_id, 'automation_count' => count( $automations ) )
				);
			}

			// Execute each automation
			$results = array();
			foreach ( $automations as $automation ) {
				$results[ $automation['id'] ] = self::execute_automation( $automation['id'], $context );
			}

			/**
			 * Fires after trigger event processing
			 *
			 * @param string $event_id Event identifier
			 * @param array  $context  Event context
			 * @param array  $results  Execution results
			 * @since 6.5.0
			 */
			do_action( 'super_after_automation_event', $event_id, $context, $results );

			// End timing
			if ( class_exists( 'SUPER_Automation_Performance' ) ) {
				SUPER_Automation_Performance::end_timer( $timer_key, array(
					'event_id'        => $event_id,
					'automations_found'  => count( $triggers ),
					'automations_executed' => count( $results ),
				) );
			}

			return $results;
		}

		/**
		 * Execute a trigger (all its actions in order)
		 *
		 * @param int    $automation_id     Trigger ID
		 * @param array  $context        Event context
		 * @param string $execution_mode Override execution mode (sync/async/auto)
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function execute_automation( $automation_id, $context, $execution_mode = self::MODE_AUTO ) {
			// Start performance timing
			$timer_key = 'automation_' . $automation_id . '_' . microtime( true );
			if ( class_exists( 'SUPER_Automation_Performance' ) ) {
				SUPER_Automation_Performance::start_timer( $timer_key );
			}

			// Get automation
			$automation = SUPER_Automation_DAL::get_automation( $automation_id );
			if ( is_wp_error( $automation ) ) {
				// Log error
				if ( class_exists( 'SUPER_Automation_Logger' ) ) {
					SUPER_Automation_Logger::instance()->error(
						sprintf( 'Failed to load automation %d: %s', $automation_id, $automation->get_error_message() ),
						array( 'automation_id' => $automation_id )
					);
				}

				return array(
					'success' => false,
					'error'   => $automation->get_error_message(),
				);
			}

			// Log to debugger
			if ( class_exists( 'SUPER_Automation_Debugger' ) ) {
				SUPER_Automation_Debugger::log_automation_evaluated( $automation_id, $automation['name'], true, array() );
			}

			// Add trigger ID to context
			$context['automation_id'] = $automation_id;

			// Check if this is a visual workflow
			if ( isset( $automation['workflow_type'] ) && 'visual' === $automation['workflow_type'] && ! empty( $automation['workflow_graph'] ) ) {
				// Execute visual workflow
				// Note: form_id comes from context (node-level scope architecture)
				if ( class_exists( 'SUPER_Workflow_Executor' ) ) {
					$result = SUPER_Workflow_Executor::execute(
						$automation['workflow_graph'],
						$context,
						isset( $context['form_id'] ) ? $context['form_id'] : 0
					);

					// End performance timing
					$execution_time = 0;
					if ( class_exists( 'SUPER_Automation_Performance' ) ) {
						$metrics = SUPER_Automation_Performance::end_timer( $timer_key, array(
							'automation_id'    => $automation_id,
							'workflow_type' => 'visual',
							'nodes_count'   => count( $result['executed_nodes'] ?? array() ),
						) );
						$execution_time = $metrics ? $metrics['duration_ms'] : 0;
					}

					// Log visual workflow execution
					if ( class_exists( 'SUPER_Automation_Logger' ) ) {
						SUPER_Automation_Logger::instance()->info(
							sprintf(
								'Visual workflow %d completed: %d nodes executed, %.2fms',
								$automation_id,
								count( $result['executed_nodes'] ?? array() ),
								$execution_time
							),
							array(
								'automation_id'   => $automation_id,
								'success'      => 'success' === $result['status'] || 'aborted' === $result['status'],
								'status'       => $result['status'],
								'executed_nodes' => $result['executed_nodes'],
							)
						);
					}

					return array(
						'success'        => 'success' === $result['status'] || 'aborted' === $result['status'],
						'status'         => $result['status'],
						'executed_nodes' => $result['executed_nodes'],
						'execution_time' => $execution_time,
					);
				}
			}

			/**
			 * Filter the execution mode for an automation
			 *
			 * @param string $execution_mode Execution mode (sync/async/auto)
			 * @param int    $automation_id     Automation ID
			 * @param array  $context        Event context
			 * @since 6.5.0
			 */
			$execution_mode = apply_filters( 'super_automation_execution_mode', $execution_mode, $automation_id, $context );

			/**
			 * Fires before trigger execution
			 *
			 * @param int   $automation_id Automation ID
			 * @param array $automation    Trigger data
			 * @param array $context    Event context
			 * @since 6.5.0
			 */
			do_action( 'super_before_execute_automation', $automation_id, $automation, $context );

			// Get actions for this automation
			$actions = SUPER_Automation_DAL::get_actions( $automation_id, true );

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

				if ( self::MODE_ASYNC === $action_mode && SUPER_Automation_Scheduler::is_available() ) {
					// Queue for async execution
					$queue_result = self::queue_action( $automation_id, $action, $context );

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
			$all_successful = $all_successful && is_wp_error($result);

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
			if ( class_exists( 'SUPER_Automation_Performance' ) ) {
				$metrics = SUPER_Automation_Performance::end_timer( $timer_key, array(
					'automation_id'      => $automation_id,
					'actions_count'   => count( $actions ),
					'actions_sync'    => $actions_sync,
					'actions_queued'  => $actions_queued,
				) );
				$execution_time = $metrics ? $metrics['duration_ms'] : 0;
			}

			// Log overall automation execution
			self::log_automation_execution( $automation_id, $context, $action_results, $execution_time );

			// Log summary to Logger
			if ( class_exists( 'SUPER_Automation_Logger' ) ) {
				$log_level = $all_successful ? 'info' : 'warning';
				$logger    = SUPER_Automation_Logger::instance();
				$logger->$log_level(
					sprintf(
						'Automation %d completed: %d actions (%d sync, %d queued), %.2fms',
						$automation_id,
						count( $actions ),
						$actions_sync,
						$actions_queued,
						$execution_time
					),
					array(
						'automation_id'   => $automation_id,
						'success'      => $all_successful,
						'actions_sync' => $actions_sync,
						'actions_queued' => $actions_queued,
					)
				);
			}

			/**
			 * Fires after trigger execution
			 *
			 * @param int   $automation_id     Trigger ID
			 * @param array $action_results Action results
			 * @param array $context        Event context
			 * @since 6.5.0
			 */
			do_action( 'super_after_execute_automation', $automation_id, $action_results, $context );

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
		 * @param string $automation_mode   Automation-level execution mode
		 * @param array  $context        Event context
		 * @return string Execution mode (sync or async)
		 * @since 6.5.0
		 */
		private static function get_action_execution_mode( $action, $automation_mode, $context ) {
			$action_type = $action['action_type'];

			// Check for forced sync execution in context
			if ( ! empty( $context['_force_sync'] ) ) {
				return self::MODE_SYNC;
			}

			// Sync-only actions must run synchronously
			if ( in_array( $action_type, self::$sync_only_actions, true ) ) {
				return self::MODE_SYNC;
			}

			// If automation mode is explicitly set (not auto), use it
			if ( self::MODE_SYNC === $automation_mode ) {
				return self::MODE_SYNC;
			}

			if ( self::MODE_ASYNC === $automation_mode ) {
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
		 * @param int   $automation_id Automation ID
		 * @param array $action     Action data
		 * @param array $context    Event context
		 * @return array Queue result
		 * @since 6.5.0
		 */
		public static function queue_action( $automation_id, $action, $context ) {
			$scheduler = SUPER_Automation_Scheduler::get_instance();

			// Schedule for immediate async execution (delay = 0)
			$scheduled_id = $scheduler->schedule_automation_action(
				$automation_id,
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
		 * @param int   $automation_id Automation ID
		 * @param array $context    Event context
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function execute_automation_sync( $automation_id, $context ) {
			$context['_force_sync'] = true;
			return self::execute_automation( $automation_id, $context, self::MODE_SYNC );
		}

		/**
		 * Force asynchronous execution of a trigger
		 *
		 * All eligible actions will be queued for background execution.
		 *
		 * @param int   $automation_id Automation ID
		 * @param array $context    Event context
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function execute_automation_async( $automation_id, $context ) {
			return self::execute_automation( $automation_id, $context, self::MODE_ASYNC );
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
			if ( class_exists( 'SUPER_Automation_Performance' ) ) {
				SUPER_Automation_Performance::start_timer( $timer_key );
			}

			// Get action instance from registry
			$registry = SUPER_Automation_Registry::get_instance();
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
				if ( class_exists( 'SUPER_Automation_Logger' ) ) {
					SUPER_Automation_Logger::instance()->error(
						sprintf( 'Action type "%s" not found in registry', $action['action_type'] ),
						array( 'action_id' => $action['id'], 'action_type' => $action['action_type'] )
					);
				}

				// End timer
				$execution_time = 0;
				if ( class_exists( 'SUPER_Automation_Performance' ) ) {
					$metrics = SUPER_Automation_Performance::end_timer( $timer_key );
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
				if ( class_exists( 'SUPER_Automation_Logger' ) ) {
					SUPER_Automation_Logger::instance()->error(
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
			if ( class_exists( 'SUPER_Automation_Performance' ) ) {
				$metrics = SUPER_Automation_Performance::end_timer( $timer_key, array(
					'action_id'   => $action['id'],
					'action_type' => $action['action_type'],
					'success'     => ! is_wp_error( $result ),
				) );
				$execution_time = $metrics ? $metrics['duration_ms'] : 0;
			}

			// Log to debugger
			if ( class_exists( 'SUPER_Automation_Debugger' ) ) {
				SUPER_Automation_Debugger::log_action_executed(
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
			 * Fires after automation action is executed
			 *
			 * Provides action type and config for easier test hookup.
			 *
			 * @param string       $action_type Action type identifier
			 * @param array|WP_Error $result     Execution result
			 * @param array        $context    Event context
			 * @param array        $config     Action configuration
			 * @since 6.5.0
			 */
			do_action( 'super_automation_action_executed', $action['action_type'], $result, $context, $action['action_config'] ?? array() );

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
		 * Log automation execution
		 *
		 * @param int   $automation_id     Automation ID
		 * @param array $context        Event context
		 * @param array $action_results Action results
		 * @param float $execution_time Execution time in milliseconds
		 * @since 6.5.0
		 */
		private static function log_automation_execution( $automation_id, $context, $action_results, $execution_time ) {
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
			SUPER_Automation_DAL::log_execution(
				array(
					'automation_id'        => $automation_id,
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

			SUPER_Automation_DAL::log_execution(
				array(
					'automation_id'        => $context['automation_id'] ?? 0,
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
		 * For development/debugging - does NOT log to database.
		 * Supports both visual workflows and code workflows.
		 *
		 * @param int   $automation_id Automation ID
		 * @param array $mock_context Mock context data
		 * @return array Execution results
		 * @since 6.5.0
		 */
		public static function test_automation( $automation_id, $mock_context = array() ) {
			// Requires manage_options capability
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'permission_denied',
					__( 'You do not have permission to test automations', 'super-forms' )
				);
			}

			// Get automation
			$automation = SUPER_Automation_DAL::get_automation( $automation_id );
			if ( is_wp_error( $automation ) ) {
				return $automation;
			}

			// Extract event_id from workflow_graph nodes (node-level scope architecture)
			$event_id = '';
			if ( ! empty( $automation['workflow_graph']['nodes'] ) ) {
				foreach ( $automation['workflow_graph']['nodes'] as $node ) {
					// Find first event node (automation entry point)
					if ( strpos( $node['type'], 'form.' ) === 0 ||
						 strpos( $node['type'], 'entry.' ) === 0 ||
						 strpos( $node['type'], 'payment.' ) === 0 ||
						 strpos( $node['type'], 'session.' ) === 0 ) {
						$event_id = $node['type'];
						break;
					}
				}
			}

			// Build mock context
			$context = array_merge(
				array(
					'event_id'   => $event_id,
					'automation_id' => $automation_id,
					'form_id'    => $mock_context['form_id'] ?? 0,
					'entry_id'   => $mock_context['entry_id'] ?? 0,
					'user_id'    => get_current_user_id(),
					'timestamp'  => current_time( 'mysql' ),
					'form_data'  => array(),
					'_test_mode' => true,
				),
				$mock_context
			);

			$start_time = microtime( true );

			// Handle visual workflows
			if ( 'visual' === $automation['workflow_type'] && ! empty( $automation['workflow_graph'] ) ) {
				if ( class_exists( 'SUPER_Workflow_Executor' ) ) {
					$result = SUPER_Workflow_Executor::execute(
						$automation['workflow_graph'],
						$context,
						$context['form_id']
					);

					return array(
						'test_mode'       => true,
						'automation_id'      => $automation_id,
						'name'    => $automation['name'],
						'workflow_type'   => 'visual',
						'event_id'        => $event_id,
						'status'          => $result['status'],
						'executed_nodes'  => $result['executed_nodes'] ?? array(),
						'execution_time'  => ( microtime( true ) - $start_time ) * 1000,
						'context_used'    => $context,
					);
				}
			}

			// Handle code workflows (legacy action-based)
			$actions = SUPER_Automation_DAL::get_actions( $automation_id, true );

			$action_results = array();
			foreach ( $actions as $action ) {
				$action_start = microtime( true );

				$registry = SUPER_Automation_Registry::get_instance();
				$instance = $registry->get_action_instance( $action['action_type'] );

				if ( null === $instance ) {
					$action_results[ $action['id'] ] = array(
						'success' => false,
						'error'   => sprintf( 'Action type "%s" not found', $action['action_type'] ),
					);
					continue;
				}

				try {
					$result = $instance->execute( $context, $action['action_config'] ?? array() );
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
				'automation_id'      => $automation_id,
				'name'    => $automation['name'],
				'workflow_type'   => 'code',
				'event_id'        => $event_id,
				'actions_count'   => count( $actions ),
				'actions_results' => $action_results,
				'execution_time'  => ( microtime( true ) - $start_time ) * 1000,
				'context_used'    => $context,
			);
		}
	}

endif;
