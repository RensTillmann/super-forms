<?php
/**
 * Trigger Scheduler - Action Scheduler Integration
 *
 * Wrapper class for Action Scheduler integration with the triggers system.
 * Provides scheduling, retry, and queue management for trigger actions.
 *
 * @author      WebRehab
 * @category    Core
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Trigger_Scheduler
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) :

	/**
	 * SUPER_Trigger_Scheduler Class
	 */
	class SUPER_Trigger_Scheduler {

		/**
		 * Action Scheduler group for trigger actions
		 *
		 * @var string
		 */
		const GROUP = 'super-forms-triggers';

		/**
		 * Default retry limit
		 *
		 * @var int
		 */
		const DEFAULT_RETRY_LIMIT = 3;

		/**
		 * Hook names
		 */
		const HOOK_EXECUTE_ACTION    = 'super_trigger_execute_scheduled_action';
		const HOOK_EXECUTE_DELAYED   = 'super_execute_delayed_trigger_actions';
		const HOOK_RETRY_ACTION      = 'super_trigger_retry_failed_action';
		const HOOK_EXECUTE_RECURRING = 'super_trigger_execute_recurring';

		/**
		 * Single instance
		 *
		 * @var SUPER_Trigger_Scheduler
		 */
		private static $instance = null;

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Trigger_Scheduler
		 * @since 6.5.0
		 */
		public static function get_instance() {
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
			$this->register_hooks();
		}

		/**
		 * Register Action Scheduler hooks
		 *
		 * @since 6.5.0
		 */
		private function register_hooks() {
			// Hook for executing scheduled trigger actions
			add_action( self::HOOK_EXECUTE_ACTION, array( $this, 'execute_scheduled_action' ), 10, 1 );

			// Hook for delayed actions (from delay_execution action)
			add_action( self::HOOK_EXECUTE_DELAYED, array( $this, 'execute_delayed_actions' ), 10, 1 );

			// Hook for retry mechanism
			add_action( self::HOOK_RETRY_ACTION, array( $this, 'execute_retry_action' ), 10, 1 );

			// Hook for recurring triggers
			add_action( self::HOOK_EXECUTE_RECURRING, array( $this, 'execute_recurring_trigger' ), 10, 1 );
		}

		/**
		 * Check if Action Scheduler is available
		 *
		 * @return bool
		 * @since 6.5.0
		 */
		public static function is_available() {
			return function_exists( 'as_schedule_single_action' ) && function_exists( 'as_get_scheduled_actions' );
		}

		/**
		 * Schedule a single action for later execution
		 *
		 * @param int    $timestamp Unix timestamp for execution
		 * @param string $hook      Hook name (defaults to HOOK_EXECUTE_ACTION)
		 * @param array  $args      Arguments to pass to the action
		 * @param bool   $unique    Whether to prevent duplicates
		 * @return int|false Action ID or false on failure
		 * @since 6.5.0
		 */
		public function schedule_action( $timestamp, $hook = null, $args = array(), $unique = false ) {
			if ( ! self::is_available() ) {
				return false;
			}

			if ( null === $hook ) {
				$hook = self::HOOK_EXECUTE_ACTION;
			}

			return as_schedule_single_action( $timestamp, $hook, array( $args ), self::GROUP, $unique );
		}

		/**
		 * Schedule a trigger action for async execution
		 *
		 * @param int   $trigger_id Trigger ID
		 * @param int   $action_id  Action ID
		 * @param array $context    Event context
		 * @param int   $delay      Delay in seconds (0 for immediate async)
		 * @return int|false Action ID or false on failure
		 * @since 6.5.0
		 */
		public function schedule_trigger_action( $trigger_id, $action_id, $context, $delay = 0 ) {
			$timestamp = time() + $delay;

			$args = array(
				'trigger_id' => $trigger_id,
				'action_id'  => $action_id,
				'context'    => $context,
				'attempt'    => 1,
			);

			return $this->schedule_action( $timestamp, self::HOOK_EXECUTE_ACTION, $args );
		}

		/**
		 * Schedule recurring execution
		 *
		 * @param int    $start_timestamp When to start
		 * @param int    $interval        Interval in seconds
		 * @param string $hook            Hook name
		 * @param array  $args            Arguments
		 * @param bool   $unique          Prevent duplicates
		 * @return int|false Action ID or false on failure
		 * @since 6.5.0
		 */
		public function schedule_recurring( $start_timestamp, $interval, $hook = null, $args = array(), $unique = false ) {
			if ( ! self::is_available() ) {
				return false;
			}

			if ( null === $hook ) {
				$hook = self::HOOK_EXECUTE_RECURRING;
			}

			return as_schedule_recurring_action( $start_timestamp, $interval, $hook, array( $args ), self::GROUP, $unique );
		}

		/**
		 * Cancel a scheduled action
		 *
		 * @param string $hook Hook name
		 * @param array  $args Arguments to match
		 * @return int Number of cancelled actions
		 * @since 6.5.0
		 */
		public function cancel_action( $hook, $args = array() ) {
			if ( ! self::is_available() ) {
				return 0;
			}

			$cancelled = 0;
			$actions   = as_get_scheduled_actions(
				array(
					'hook'   => $hook,
					'args'   => array( $args ),
					'group'  => self::GROUP,
					'status' => 'pending',
				),
				'ids'
			);

			foreach ( $actions as $action_id ) {
				as_unschedule_action( $hook, array( $args ), self::GROUP );
				$cancelled++;
			}

			return $cancelled;
		}

		/**
		 * Cancel all pending actions for a trigger
		 *
		 * @param int $trigger_id Trigger ID
		 * @return int Number of cancelled actions
		 * @since 6.5.0
		 */
		public function cancel_trigger_actions( $trigger_id ) {
			if ( ! self::is_available() ) {
				return 0;
			}

			global $wpdb;

			// Find all pending actions for this trigger
			$table     = $wpdb->prefix . 'actionscheduler_actions';
			$group_id  = $this->get_group_id();

			if ( ! $group_id ) {
				return 0;
			}

			$actions = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT action_id FROM {$table}
					WHERE group_id = %d
					AND status = 'pending'
					AND args LIKE %s",
					$group_id,
					'%"trigger_id":' . intval( $trigger_id ) . '%'
				)
			);

			$cancelled = 0;
			$store     = ActionScheduler_Store::instance();

			foreach ( $actions as $action_id ) {
				try {
					$store->cancel_action( $action_id );
					$cancelled++;
				} catch ( Exception $e ) {
					// Log but continue
					error_log( 'Failed to cancel action ' . $action_id . ': ' . $e->getMessage() );
				}
			}

			return $cancelled;
		}

		/**
		 * Get the group ID from Action Scheduler
		 *
		 * @return int|false Group ID or false if not found
		 * @since 6.5.0
		 */
		private function get_group_id() {
			global $wpdb;

			$table = $wpdb->prefix . 'actionscheduler_groups';
			return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT group_id FROM {$table} WHERE slug = %s",
					self::GROUP
				)
			);
		}

		/**
		 * Get next scheduled occurrence
		 *
		 * @param string $hook Hook name
		 * @param array  $args Arguments to match
		 * @return int|false Next timestamp or false if none
		 * @since 6.5.0
		 */
		public function get_next_scheduled( $hook, $args = array() ) {
			if ( ! self::is_available() ) {
				return false;
			}

			return as_next_scheduled_action( $hook, array( $args ), self::GROUP );
		}

		/**
		 * Check if an action is scheduled
		 *
		 * @param string $hook Hook name
		 * @param array  $args Arguments to match
		 * @return bool
		 * @since 6.5.0
		 */
		public function is_scheduled( $hook, $args = array() ) {
			if ( ! self::is_available() ) {
				return false;
			}

			return as_has_scheduled_action( $hook, array( $args ), self::GROUP );
		}

		/**
		 * Get pending actions count
		 *
		 * @return int
		 * @since 6.5.0
		 */
		public function get_pending_count() {
			if ( ! self::is_available() ) {
				return 0;
			}

			$result = as_get_scheduled_actions(
				array(
					'group'    => self::GROUP,
					'status'   => 'pending',
					'per_page' => -1,
				),
				'count'
			);

			// Handle array return (older versions) vs int return
			if ( is_array( $result ) ) {
				return count( $result );
			}

			return (int) $result;
		}

		/**
		 * Get scheduled actions with status counts
		 *
		 * @return array Status counts
		 * @since 6.5.0
		 */
		public function get_queue_stats() {
			if ( ! self::is_available() ) {
				return array(
					'pending'     => 0,
					'in_progress' => 0,
					'failed'      => 0,
					'complete'    => 0,
				);
			}

			return array(
				'pending'     => as_get_scheduled_actions(
					array(
						'group'    => self::GROUP,
						'status'   => 'pending',
						'per_page' => -1,
					),
					'count'
				),
				'in_progress' => as_get_scheduled_actions(
					array(
						'group'    => self::GROUP,
						'status'   => 'in-progress',
						'per_page' => -1,
					),
					'count'
				),
				'failed'      => as_get_scheduled_actions(
					array(
						'group'    => self::GROUP,
						'status'   => 'failed',
						'per_page' => -1,
					),
					'count'
				),
				'complete'    => as_get_scheduled_actions(
					array(
						'group'    => self::GROUP,
						'status'   => 'complete',
						'per_page' => -1,
					),
					'count'
				),
			);
		}

		/**
		 * Execute a scheduled trigger action
		 *
		 * Called by Action Scheduler when the scheduled time arrives.
		 *
		 * @param array $args Scheduled action arguments
		 * @since 6.5.0
		 */
		public function execute_scheduled_action( $args ) {
			if ( empty( $args['action_id'] ) || empty( $args['context'] ) ) {
				error_log( 'Super Forms: Invalid scheduled action arguments' );
				return;
			}

			$action_id = absint( $args['action_id'] );
			$context   = $args['context'];
			$attempt   = isset( $args['attempt'] ) ? absint( $args['attempt'] ) : 1;

			// Get the action from database
			$action = SUPER_Trigger_DAL::get_action( $action_id );
			if ( is_wp_error( $action ) ) {
				error_log( 'Super Forms: Scheduled action not found: ' . $action_id );
				return;
			}

			// Execute the action
			$result = SUPER_Trigger_Executor::execute_action( $action, $context );

			// Handle failures with retry
			if ( is_wp_error( $result ) || ( isset( $result['success'] ) && ! $result['success'] ) ) {
				$this->handle_action_failure( $args, $result, $attempt );
			}
		}

		/**
		 * Execute delayed actions (from delay_execution action)
		 *
		 * @param array $args Arguments from delay_execution action
		 * @since 6.5.0
		 */
		public function execute_delayed_actions( $args ) {
			if ( empty( $args['actions'] ) || empty( $args['context'] ) ) {
				error_log( 'Super Forms: Invalid delayed action arguments' );
				return;
			}

			$actions = $args['actions'];
			$context = $args['context'];

			// Mark context as delayed execution
			$context['_delayed_execution'] = true;
			$context['_original_scheduled'] = $args['trigger_id'] ?? 0;

			// Execute each delayed action
			foreach ( $actions as $action_config ) {
				if ( empty( $action_config['action_type'] ) ) {
					continue;
				}

				// Get action instance from registry
				$registry = SUPER_Trigger_Registry::get_instance();
				$instance = $registry->get_action_instance( $action_config['action_type'] );

				if ( null === $instance ) {
					error_log( 'Super Forms: Delayed action type not found: ' . $action_config['action_type'] );
					continue;
				}

				try {
					$result = $instance->execute( $context, $action_config['config'] ?? array() );

					// Log the execution
					SUPER_Trigger_DAL::log_execution(
						array(
							'trigger_id'        => $context['trigger_id'] ?? 0,
							'entry_id'          => $context['entry_id'] ?? null,
							'form_id'           => $context['form_id'] ?? null,
							'event_id'          => $context['event_id'] ?? 'delayed_execution',
							'status'            => is_wp_error( $result ) ? 'failed' : 'success',
							'error_message'     => is_wp_error( $result ) ? $result->get_error_message() : '',
							'context_data'      => $context,
							'result_data'       => is_wp_error( $result ) ? array() : $result,
						)
					);
				} catch ( Exception $e ) {
					error_log( 'Super Forms: Delayed action error: ' . $e->getMessage() );
				}
			}
		}

		/**
		 * Execute a retry action
		 *
		 * @param array $args Retry arguments
		 * @since 6.5.0
		 */
		public function execute_retry_action( $args ) {
			// Reuse the standard execution with incremented attempt
			$this->execute_scheduled_action( $args );
		}

		/**
		 * Execute a recurring trigger
		 *
		 * @param array $args Recurring trigger arguments
		 * @since 6.5.0
		 */
		public function execute_recurring_trigger( $args ) {
			if ( empty( $args['trigger_id'] ) ) {
				return;
			}

			$trigger_id = absint( $args['trigger_id'] );

			// Get the trigger
			$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
			if ( is_wp_error( $trigger ) ) {
				return;
			}

			// Check if trigger is still enabled
			if ( empty( $trigger['enabled'] ) ) {
				return;
			}

			// Build context for recurring execution
			$context = array(
				'event_id'             => 'recurring_trigger',
				'trigger_id'           => $trigger_id,
				'timestamp'            => current_time( 'mysql' ),
				'_recurring_execution' => true,
			);

			// Execute the trigger
			SUPER_Trigger_Executor::execute_trigger( $trigger_id, $context );
		}

		/**
		 * Handle action failure and schedule retry if applicable
		 *
		 * @param array            $original_args Original scheduled action args
		 * @param array|WP_Error   $result        Failure result
		 * @param int              $attempt       Current attempt number
		 * @since 6.5.0
		 */
		private function handle_action_failure( $original_args, $result, $attempt ) {
			$max_retries = $this->get_retry_limit( $original_args );

			// Log the failure
			$error_message = is_wp_error( $result ) ? $result->get_error_message() : ( $result['error'] ?? 'Unknown error' );
			error_log( sprintf(
				'Super Forms: Action %d failed (attempt %d/%d): %s',
				$original_args['action_id'] ?? 0,
				$attempt,
				$max_retries,
				$error_message
			) );

			// Check if we should retry
			if ( $attempt < $max_retries && $this->should_retry( $result ) ) {
				// Calculate delay with exponential backoff
				$delay = $this->calculate_retry_delay( $attempt );

				// Schedule retry
				$retry_args = array_merge(
					$original_args,
					array( 'attempt' => $attempt + 1 )
				);

				$this->schedule_action(
					time() + $delay,
					self::HOOK_RETRY_ACTION,
					$retry_args
				);

				error_log( sprintf(
					'Super Forms: Scheduled retry for action %d in %d seconds',
					$original_args['action_id'] ?? 0,
					$delay
				) );
			}
		}

		/**
		 * Get retry limit for an action
		 *
		 * @param array $args Action arguments
		 * @return int Retry limit
		 * @since 6.5.0
		 */
		private function get_retry_limit( $args ) {
			// Check if action has custom retry config
			if ( ! empty( $args['action_id'] ) ) {
				$action = SUPER_Trigger_DAL::get_action( $args['action_id'] );
				if ( ! is_wp_error( $action ) && isset( $action['action_config']['retry_limit'] ) ) {
					return absint( $action['action_config']['retry_limit'] );
				}
			}

			return self::DEFAULT_RETRY_LIMIT;
		}

		/**
		 * Check if we should retry the failed action
		 *
		 * @param array|WP_Error $result Action result
		 * @return bool
		 * @since 6.5.0
		 */
		private function should_retry( $result ) {
			// Don't retry successful results
			if ( is_array( $result ) && isset( $result['success'] ) && $result['success'] ) {
				return false;
			}

			// Don't retry certain error codes
			$no_retry_codes = array(
				'abort_submission',
				'stop_execution',
				'permission_denied',
				'invalid_config',
			);

			if ( is_wp_error( $result ) && in_array( $result->get_error_code(), $no_retry_codes, true ) ) {
				return false;
			}

			// Don't retry if explicitly disabled
			if ( is_array( $result ) && isset( $result['no_retry'] ) && $result['no_retry'] ) {
				return false;
			}

			return true;
		}

		/**
		 * Calculate retry delay with exponential backoff
		 *
		 * @param int $attempt Current attempt number
		 * @return int Delay in seconds
		 * @since 6.5.0
		 */
		private function calculate_retry_delay( $attempt ) {
			// Exponential backoff: 2^attempt * 60 seconds
			// Attempt 1: 120s (2 min)
			// Attempt 2: 240s (4 min)
			// Attempt 3: 480s (8 min)
			$base_delay = 60; // 1 minute
			$multiplier = pow( 2, $attempt );

			// Cap at 30 minutes
			return min( $base_delay * $multiplier, 1800 );
		}

		/**
		 * Rate limiting check
		 *
		 * Prevents too many actions of a certain type from executing in a time window.
		 *
		 * @param string $action_type Action type identifier
		 * @param int    $limit       Maximum executions allowed
		 * @param int    $window      Time window in seconds
		 * @return bool True if action can execute, false if rate limited
		 * @since 6.5.0
		 */
		public function can_execute_rate_limited( $action_type, $limit = 10, $window = 60 ) {
			$key   = 'super_trigger_rate_' . sanitize_key( $action_type );
			$count = get_transient( $key );

			if ( false === $count ) {
				$count = 0;
			}

			if ( $count >= $limit ) {
				return false;
			}

			set_transient( $key, $count + 1, $window );
			return true;
		}

		/**
		 * Get interval options for recurring actions
		 *
		 * @return array Interval options
		 * @since 6.5.0
		 */
		public static function get_interval_options() {
			return array(
				'every_minute'  => MINUTE_IN_SECONDS,
				'every_5_min'   => 5 * MINUTE_IN_SECONDS,
				'every_15_min'  => 15 * MINUTE_IN_SECONDS,
				'every_30_min'  => 30 * MINUTE_IN_SECONDS,
				'hourly'        => HOUR_IN_SECONDS,
				'twice_daily'   => 12 * HOUR_IN_SECONDS,
				'daily'         => DAY_IN_SECONDS,
				'weekly'        => WEEK_IN_SECONDS,
				'monthly'       => 30 * DAY_IN_SECONDS,
			);
		}
	}

endif;
