<?php
/**
 * SUPER_Background_Migration
 *
 * Handles automatic background migration of contact entries from serialized to EAV storage
 * Uses Action Scheduler for reliable background processing with fallback to WP-Cron
 *
 * @since 6.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Background_Migration' ) ) :

	/**
	 * SUPER_Background_Migration Class
	 */
	class SUPER_Background_Migration {

		/**
		 * Migration lock key (WordPress transient)
		 */
		const LOCK_KEY = 'super_migration_lock';

		/**
		 * Lock duration in seconds (30 minutes)
		 */
		const LOCK_DURATION = 1800;

		/**
		 * Batch size for migration processing
		 */
		const BATCH_SIZE = 100;

		/**
		 * Delay between batches in seconds
		 */
		const BATCH_DELAY = 10;

		/**
		 * Action Scheduler hook names
		 */
		const AS_BATCH_HOOK = 'superforms_migrate_batch';
		const AS_HEALTH_CHECK_HOOK = 'superforms_migration_health_check';

		/**
		 * Initialize hooks
		 *
		 * @since 6.0.0
		 */
		public static function init() {
			// Register Action Scheduler hooks
			add_action( self::AS_BATCH_HOOK, array( __CLASS__, 'process_batch_action' ) );
			add_action( self::AS_HEALTH_CHECK_HOOK, array( __CLASS__, 'health_check_action' ) );

			// WP-Cron fallback hooks (if Action Scheduler not available)
			add_action( 'super_migration_cron_batch', array( __CLASS__, 'process_batch_action' ) );
			add_action( 'super_migration_cron_health', array( __CLASS__, 'health_check_action' ) );
		}

		/**
		 * Detect if migration is needed (unmigrated entries exist)
		 *
		 * @return bool True if unmigrated entries exist
		 * @since 6.0.0
		 */
		public static function needs_migration() {
			global $wpdb;

			// Get migration status
			$status = SUPER_Migration_Manager::get_migration_status();

			// If migration already completed, no need to run
			if ( $status && $status['status'] === 'completed' && $status['using_storage'] === 'eav' ) {
				return false;
			}

			// Count entries without EAV data
			$table_name = $wpdb->prefix . 'superforms_entry_data';
			$unmigrated = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID)
					FROM {$wpdb->posts} p
					LEFT JOIN {$table_name} e ON e.entry_id = p.ID
					WHERE p.post_type = %s
					AND e.entry_id IS NULL",
					'super_contact_entry'
				)
			);

			return $unmigrated > 0;
		}

		/**
		 * Schedule background migration if needed
		 *
		 * @param string $triggered_by What triggered this (activation|update|health_check)
		 * @return bool True if migration was scheduled, false if not needed
		 * @since 6.0.0
		 */
		public static function schedule_if_needed( $triggered_by = 'manual' ) {
			// Check if migration is needed
			if ( ! self::needs_migration() ) {
				self::log( "No migration needed (triggered by: {$triggered_by})" );
				return false;
			}

			// Check if already running
			if ( self::is_locked() ) {
				self::log( "Migration already running, skipping schedule (triggered by: {$triggered_by})" );
				return false;
			}

			// Update migration state
			$status = SUPER_Migration_Manager::get_migration_status();
			if ( ! $status ) {
				$status = array();
			}

			$status['background_enabled'] = true;
			$status['auto_triggered_by'] = $triggered_by;
			$status['last_schedule_attempt'] = current_time( 'mysql' );

			update_option( 'superforms_eav_migration', $status );

			// Schedule first batch
			self::schedule_batch();

			// Schedule daily health check (if not already scheduled)
			self::schedule_health_check();

			self::log( "Background migration scheduled successfully (triggered by: {$triggered_by})" );

			return true;
		}

		/**
		 * Schedule a single batch for processing
		 *
		 * @return bool True if scheduled successfully
		 * @since 6.0.0
		 */
		public static function schedule_batch() {
			// Try Action Scheduler first
			if ( function_exists( 'as_enqueue_async_action' ) ) {
				try {
					as_enqueue_async_action(
						self::AS_BATCH_HOOK,
						array( 'batch_size' => self::BATCH_SIZE ),
						'superforms-migration'
					);
					self::log( 'Scheduled batch via Action Scheduler' );
					return true;
				} catch ( Exception $e ) {
					self::log( 'Action Scheduler scheduling failed: ' . $e->getMessage(), 'error' );
					// Fall through to WP-Cron
				}
			}

			// Fallback to WP-Cron
			if ( ! wp_next_scheduled( 'super_migration_cron_batch' ) ) {
				wp_schedule_single_event( time() + self::BATCH_DELAY, 'super_migration_cron_batch' );
				self::log( 'Scheduled batch via WP-Cron (fallback)' );
				return true;
			}

			return false;
		}

		/**
		 * Schedule recurring health check
		 *
		 * @return bool True if scheduled successfully
		 * @since 6.0.0
		 */
		public static function schedule_health_check() {
			// Try Action Scheduler first
			if ( function_exists( 'as_schedule_recurring_action' ) ) {
				// Check if already scheduled
				if ( ! as_next_scheduled_action( self::AS_HEALTH_CHECK_HOOK, array(), 'superforms-migration' ) ) {
					try {
						as_schedule_recurring_action(
							strtotime( '+1 day' ),
							DAY_IN_SECONDS,
							self::AS_HEALTH_CHECK_HOOK,
							array(),
							'superforms-migration'
						);
						self::log( 'Scheduled daily health check via Action Scheduler' );
						return true;
					} catch ( Exception $e ) {
						self::log( 'Action Scheduler health check scheduling failed: ' . $e->getMessage(), 'error' );
						// Fall through to WP-Cron
					}
				}
			}

			// Fallback to WP-Cron
			if ( ! wp_next_scheduled( 'super_migration_cron_health' ) ) {
				wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'super_migration_cron_health' );
				self::log( 'Scheduled daily health check via WP-Cron (fallback)' );
				return true;
			}

			return false;
		}

		/**
		 * Process a batch of entries (Action/Cron callback)
		 *
		 * @param array $args Optional arguments (batch_size)
		 * @return array|WP_Error Processing results
		 * @since 6.0.0
		 */
		public static function process_batch_action( $args = array() ) {
			$batch_size = isset( $args['batch_size'] ) ? absint( $args['batch_size'] ) : self::BATCH_SIZE;

			self::log( "Starting batch processing (size: {$batch_size})" );

			// Acquire lock
			if ( ! self::acquire_lock() ) {
				self::log( 'Failed to acquire lock, batch aborted', 'warning' );
				return new WP_Error( 'locked', 'Migration is locked by another process' );
			}

			try {
				// Process batch using existing migration manager
				$result = SUPER_Migration_Manager::process_batch( $batch_size );

				// Update last processed timestamp
				self::update_last_processed();

				if ( is_wp_error( $result ) ) {
					self::log( 'Batch processing error: ' . $result->get_error_message(), 'error' );
					self::release_lock();
					return $result;
				}

				self::log( sprintf(
					'Batch completed: %d processed, %d failed, %d remaining',
					$result['processed'],
					$result['failed'],
					$result['remaining']
				) );

				// If migration is not complete, schedule next batch
				if ( ! $result['is_complete'] && $result['remaining'] > 0 ) {
					self::schedule_batch();
					self::log( 'Migration incomplete, scheduled next batch' );
				} else {
					self::log( 'Migration completed successfully!' );
					self::cleanup_on_completion();
				}

				// Release lock
				self::release_lock();

				return $result;

			} catch ( Exception $e ) {
				self::log( 'Exception during batch processing: ' . $e->getMessage(), 'error' );
				self::release_lock();
				return new WP_Error( 'exception', $e->getMessage() );
			}
		}

		/**
		 * Health check action (daily recurring)
		 * Detects stuck migrations and resumes them
		 *
		 * @since 6.0.0
		 */
		public static function health_check_action() {
			self::log( 'Running health check' );

			$status = SUPER_Migration_Manager::get_migration_status();

			if ( ! $status ) {
				self::log( 'Health check: No migration state found' );
				return;
			}

			// Check if migration needs to run
			if ( self::needs_migration() ) {
				// Check if migration is stuck (in_progress but no activity in 1 hour)
				$last_processed = isset( $status['last_batch_processed_at'] ) ? $status['last_batch_processed_at'] : '';

				if ( ! empty( $last_processed ) ) {
					$time_since_last = time() - strtotime( $last_processed );

					// If no activity in 1 hour, migration might be stuck
					if ( $time_since_last > 3600 ) {
						self::log( "Health check: Migration appears stuck (last activity: {$time_since_last}s ago), attempting resume" );

						// Release any stale locks
						self::release_lock();

						// Resume migration
						self::schedule_if_needed( 'health_check' );
					} else {
						self::log( "Health check: Migration in progress, last activity {$time_since_last}s ago" );
					}
				} else {
					// Migration needed but never started
					self::log( 'Health check: Migration needed but not started, scheduling' );
					self::schedule_if_needed( 'health_check' );
				}
			} else {
				self::log( 'Health check: No unmigrated entries found' );

				// If status shows in_progress but no unmigrated entries, mark as complete
				if ( $status['status'] === 'in_progress' ) {
					self::log( 'Health check: Forcing completion (status was in_progress but no entries remaining)' );
					SUPER_Migration_Manager::complete_migration();
					self::cleanup_on_completion();
				}
			}

			// Update health check count
			if ( ! isset( $status['health_check_count'] ) ) {
				$status['health_check_count'] = 0;
			}
			$status['health_check_count']++;
			$status['last_health_check'] = current_time( 'mysql' );
			update_option( 'superforms_eav_migration', $status );
		}

		/**
		 * Acquire migration lock
		 *
		 * @return bool True if lock acquired, false if already locked
		 * @since 6.0.0
		 */
		public static function acquire_lock() {
			$locked = get_transient( self::LOCK_KEY );

			if ( $locked ) {
				return false; // Already locked
			}

			// Set lock with expiration
			set_transient( self::LOCK_KEY, time(), self::LOCK_DURATION );
			return true;
		}

		/**
		 * Release migration lock
		 *
		 * @return bool True if released
		 * @since 6.0.0
		 */
		public static function release_lock() {
			delete_transient( self::LOCK_KEY );
			return true;
		}

		/**
		 * Check if migration is currently locked
		 *
		 * @return bool True if locked
		 * @since 6.0.0
		 */
		public static function is_locked() {
			$locked = get_transient( self::LOCK_KEY );
			return $locked !== false;
		}

		/**
		 * Update last processed timestamp in migration state
		 *
		 * @since 6.0.0
		 */
		private static function update_last_processed() {
			$status = SUPER_Migration_Manager::get_migration_status();
			if ( $status ) {
				$status['last_batch_processed_at'] = current_time( 'mysql' );
				update_option( 'superforms_eav_migration', $status );
			}
		}

		/**
		 * Cleanup after successful migration completion
		 *
		 * @since 6.0.0
		 */
		private static function cleanup_on_completion() {
			// Unschedule health checks
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::AS_HEALTH_CHECK_HOOK, array(), 'superforms-migration' );
			}

			wp_clear_scheduled_hook( 'super_migration_cron_health' );

			// Release lock
			self::release_lock();

			self::log( 'Cleanup completed, health checks unscheduled' );
		}

		/**
		 * Cancel all scheduled migration tasks
		 *
		 * @since 6.0.0
		 */
		public static function cancel_all() {
			// Cancel Action Scheduler tasks
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::AS_BATCH_HOOK, array(), 'superforms-migration' );
				as_unschedule_all_actions( self::AS_HEALTH_CHECK_HOOK, array(), 'superforms-migration' );
			}

			// Cancel WP-Cron tasks
			wp_clear_scheduled_hook( 'super_migration_cron_batch' );
			wp_clear_scheduled_hook( 'super_migration_cron_health' );

			// Release lock
			self::release_lock();

			self::log( 'All scheduled migration tasks cancelled' );
		}

		/**
		 * Get background migration status
		 *
		 * @return array Status information
		 * @since 6.0.0
		 */
		public static function get_status() {
			$migration_status = SUPER_Migration_Manager::get_migration_status();

			return array(
				'enabled' => isset( $migration_status['background_enabled'] ) ? $migration_status['background_enabled'] : false,
				'locked' => self::is_locked(),
				'using_action_scheduler' => function_exists( 'as_enqueue_async_action' ),
				'has_scheduled_batches' => self::has_scheduled_batches(),
				'migration_status' => $migration_status,
			);
		}

		/**
		 * Check if there are scheduled batches
		 *
		 * @return bool True if batches are scheduled
		 * @since 6.0.0
		 */
		private static function has_scheduled_batches() {
			if ( function_exists( 'as_next_scheduled_action' ) ) {
				return (bool) as_next_scheduled_action( self::AS_BATCH_HOOK, array(), 'superforms-migration' );
			}

			return (bool) wp_next_scheduled( 'super_migration_cron_batch' );
		}

		/**
		 * Log migration activity
		 *
		 * @param string $message Log message
		 * @param string $level Log level (info|warning|error)
		 * @since 6.0.0
		 */
		public static function log( $message, $level = 'info' ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				$prefix = '[Super Forms Migration]';
				$formatted_message = sprintf( '%s [%s] %s', $prefix, strtoupper( $level ), $message );
				error_log( $formatted_message );
			}
		}
	}

	// Initialize hooks
	SUPER_Background_Migration::init();

endif;
