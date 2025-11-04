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
	 * Setup lock key (WordPress transient) - prevents race conditions during table creation
	 */
	const SETUP_LOCK_KEY = 'super_setup_lock';

	/**
	 * Setup lock duration in seconds (10 minutes)
	 */
	const SETUP_LOCK_DURATION = 600;

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

			// Debug: Hook into Action Scheduler to log action data before execution
			add_action( 'action_scheduler_before_execute', array( __CLASS__, 'debug_action_scheduler' ), 10, 2 );

			// Version-based detection: Check on init if version changed
			add_action( 'init', array( __CLASS__, 'check_version_and_schedule' ), 5 );
		}

	/**
	 * Check plugin version and schedule migration if needed
	 *
	 * Triggers automatic migration on:
	 * - Plugin version UPGRADES (catches FTP uploads, git pulls, manual updates)
	 * - Unmigrated entries detected
	 *
	 * RACE CONDITION PROTECTION: Uses setup lock to prevent multiple simultaneous
	 * table creation attempts when many visitors hit site after FTP upload.
	 *
	 * SELF-HEALING: Automatically creates missing tables and initializes state.
	 *
	 * @since 6.0.0
	 */
	public static function check_version_and_schedule() {
		// Only run in admin or during cron
		if ( ! is_admin() && ! wp_doing_cron() ) {
			return;
		}

		// Get stored version
		$stored_version = get_option( 'super_plugin_version', '0.0.0' );
		$current_version = defined( 'SUPER_VERSION' ) ? SUPER_VERSION : '0.0.0';

		// Only trigger on UPGRADES, not downgrades (safety measure)
		$is_upgrade = version_compare( $stored_version, $current_version, '<' );

		if ( $is_upgrade ) {
			// Check if setup is already running
			if ( self::is_setup_running() ) {
				return;
			}

			// ACQUIRE SETUP LOCK - prevents race conditions during table creation
			set_transient( self::SETUP_LOCK_KEY, 'yes', self::SETUP_LOCK_DURATION );

			try {
				self::log( "Plugin upgraded: {$stored_version} → {$current_version}" );
				update_option( 'super_plugin_version', $current_version );

				// SELF-HEALING: Auto-create infrastructure if missing
				if ( class_exists( 'SUPER_Install' ) ) {
					// Ensure EAV tables exist (creates if needed)
					if ( SUPER_Install::ensure_tables_exist() ) {
						self::log( "EAV database tables created automatically" );
					}

					// Ensure migration state initialized
					if ( SUPER_Install::ensure_migration_state_initialized() ) {
						self::log( "Migration state initialized automatically" );
					}
				}

				// Schedule migration if needed (will work now because tables exist)
				self::schedule_if_needed( 'version_upgrade' );
			} finally {
				// RELEASE SETUP LOCK - guaranteed cleanup even on fatal errors
				delete_transient( self::SETUP_LOCK_KEY );
			}
		}
	}

	/**
	 * Check if setup routine is currently running
	 *
	 * @return bool
	 * @since 6.4.111
	 */
	private static function is_setup_running() {
		return 'yes' === get_transient( self::SETUP_LOCK_KEY );
	}

	/**
	 * Calculate optimal batch size based on server resources and dataset size
	 *
	 * Uses resource-based calculation considering:
	 * - Available memory (50% of PHP memory_limit)
	 * - Max execution time (30% of max_execution_time)
	 * - Total entries to migrate (dataset-based scaling)
	 * - Recent failures (adaptive reduction)
	 *
	 * Hard caps: minimum 1, maximum 100 entries per batch
	 *
	 * @return int Optimal batch size
	 * @since 6.4.114
	 */
	private static function calculate_batch_size() {
		$migration = get_option( 'superforms_eav_migration', array() );
		$total = isset( $migration['total_entries'] ) ? (int) $migration['total_entries'] : 0;

		// Get server limits
		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$max_exec = (int) ini_get( 'max_execution_time' );

		// Estimate per-entry resource usage (conservative)
		$memory_per_entry = 100 * 1024; // 100KB per entry
		$time_per_entry = 0.1; // 0.1 seconds per entry

		// Calculate memory-based limit (use 50% of available memory)
		$available_memory = $memory_limit * 0.5;
		$memory_based_size = floor( $available_memory / $memory_per_entry );

		// Calculate time-based limit (use 30% of max execution time)
		if ( $max_exec > 0 ) {
			$safe_time = $max_exec * 0.3;
			$time_based_size = floor( $safe_time / $time_per_entry );
		} else {
			$time_based_size = PHP_INT_MAX; // No time limit
		}

		// Dataset-based scaling
		if ( $total < 100 ) {
			$dataset_size = 10;
		} elseif ( $total < 1000 ) {
			$dataset_size = 25;
		} elseif ( $total < 10000 ) {
			$dataset_size = 50;
		} else {
			$dataset_size = 100;
		}

		// Take minimum of all constraints
		$batch_size = min( $memory_based_size, $time_based_size, $dataset_size );

		// Hard limits
		$batch_size = max( 1, $batch_size );   // Minimum 1
		$batch_size = min( 100, $batch_size ); // Maximum 100

		// Check for recent failures - reduce batch size adaptively
		if ( ! empty( $migration['failed_entries'] ) ) {
			$recent_failures = count( $migration['failed_entries'] );
			if ( $recent_failures > 5 ) {
				$batch_size = max( 1, floor( $batch_size / 2 ) ); // Halve batch size
				self::log( "Recent failures detected ({$recent_failures}), reducing batch size to {$batch_size}" );
			}
		}

		// Filter for manual override
		$batch_size = apply_filters( 'super_forms_migration_batch_size', $batch_size, array(
			'total_entries' => $total,
			'memory_limit' => $memory_limit,
			'max_execution_time' => $max_exec,
			'calculated' => array(
				'memory_based' => $memory_based_size,
				'time_based' => $time_based_size,
				'dataset_based' => $dataset_size,
			)
		) );

		self::log( sprintf(
			'Calculated batch size: %d (total: %d, memory: %s, time: %ds, constraints: mem=%d, time=%d, dataset=%d)',
			$batch_size,
			$total,
			size_format( $memory_limit ),
			$max_exec,
			$memory_based_size,
			$time_based_size,
			$dataset_size
		) );

		return (int) $batch_size;
	}

	/**
	 * Detect if migration is needed (unmigrated entries exist)
	 *
	 * CRITICAL: Always checks for actual unmigrated entries, regardless of stored state.
	 * This catches edge cases like:
	 * - CSV imports with serialized data after migration completed
	 * - Manually created entries with serialized data
	 * - Database corruption/table truncation
	 *
	 * @return bool True if unmigrated entries exist
	 * @since 6.0.0
	 */
	public static function needs_migration() {
		global $wpdb;

		// Verify EAV table exists before attempting migration
		$table_name = $wpdb->prefix . 'superforms_entry_data';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		if ( $table_exists !== $table_name ) {
			// Table doesn't exist - can't migrate
			// This will be auto-created by version detection if needed
			return false;
		}

		// ALWAYS check for actual unmigrated entries
		// Don't trust stored state - CSV imports might add serialized data after completion
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

			// Count total entries if not already counted
			if ( ! isset( $status['total_entries'] ) || $status['total_entries'] === 0 ) {
				global $wpdb;
				$status['total_entries'] = $wpdb->get_var(
					"SELECT COUNT(*)
					FROM {$wpdb->posts}
					WHERE post_type = 'super_contact_entry'"
				);
				self::log( "Counted {$status['total_entries']} total entries to migrate" );
			}

			// Set status to in_progress before scheduling batches
			$status['status'] = 'in_progress';
			$status['started_at'] = current_time( 'mysql' );
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
			// Calculate optimal batch size dynamically
			$batch_size = self::calculate_batch_size();

			// Try Action Scheduler first
			if ( function_exists( 'as_enqueue_async_action' ) ) {
				try {
					as_enqueue_async_action(
						self::AS_BATCH_HOOK,
						array( $batch_size ),
						'superforms-migration'
					);
					self::log( "Scheduled batch via Action Scheduler (async, size: {$batch_size})" );
					return true;
				} catch ( Exception $e ) {
					self::log( 'Action Scheduler scheduling failed: ' . $e->getMessage(), 'error' );
					// Fall through to WP-Cron
				}
			}

			// Fallback to WP-Cron (immediate execution via async)
			if ( ! wp_next_scheduled( 'super_migration_cron_batch' ) ) {
				wp_schedule_single_event( time(), 'super_migration_cron_batch', array( $batch_size ) );
				self::log( "Scheduled batch via WP-Cron (fallback, size: {$batch_size})" );
				return true;
			}

			return false;
		}

		/**
		 * Schedule recurring health check
		 *
		 * Runs every hour to detect stuck migrations and resume them quickly.
		 * Reduces maximum stuck time from 24.5 hours to 1.5 hours.
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
							strtotime( '+1 hour' ),
							HOUR_IN_SECONDS,
							self::AS_HEALTH_CHECK_HOOK,
							array(),
							'superforms-migration'
						);
						self::log( 'Scheduled hourly health check via Action Scheduler' );
						return true;
					} catch ( Exception $e ) {
						self::log( 'Action Scheduler health check scheduling failed: ' . $e->getMessage(), 'error' );
						// Fall through to WP-Cron
					}
				}
			}

			// Fallback to WP-Cron
			if ( ! wp_next_scheduled( 'super_migration_cron_health' ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'super_migration_cron_health' );
				self::log( 'Scheduled hourly health check via WP-Cron (fallback)' );
				return true;
			}

			return false;
		}

		/**
		 * Process a batch of entries with real-time resource monitoring
		 *
		 * Dynamically calculates optimal batch size and monitors resources during execution.
		 * Stops processing early if memory (85%) or time (70%) thresholds are approached.
		 *
		 * RACE CONDITION FIX: Acquires lock BEFORE checking needs_migration()
		 *
		 * @param int $batch_size Number of entries to process (dynamically calculated if not provided)
		 * @return array|WP_Error Processing results or error
		 * @since 6.0.0
		 */
		public static function process_batch_action( $batch_size = null ) {
			// Calculate optimal batch size if not provided
			if ( $batch_size === null ) {
				$batch_size = self::calculate_batch_size();
			}

			$batch_size = absint( $batch_size );
			if ( $batch_size <= 0 ) {
				$batch_size = self::calculate_batch_size();
			}

			self::log( "Starting batch processing (target size: {$batch_size})" );

			// RACE CONDITION FIX: Acquire lock FIRST, before checking needs_migration()
			if ( ! self::acquire_lock() ) {
				self::log( 'Failed to acquire lock, batch aborted', 'warning' );
				return new WP_Error( 'locked', 'Migration is locked by another process' );
			}

			try {
				// Check if migration still needed (after acquiring lock)
				if ( ! self::needs_migration() ) {
					self::log( 'No migration needed, releasing lock' );
					self::release_lock();
					return array(
						'processed' => 0,
						'failed' => 0,
						'remaining' => 0,
						'is_complete' => true,
						'stopped_early' => false,
						'stop_reason' => 'no_migration_needed'
					);
				}

				$migration = get_option( 'superforms_eav_migration', array() );
				$last_id = isset( $migration['last_processed_id'] ) ? (int) $migration['last_processed_id'] : 0;

				// Get server limits for real-time monitoring
				$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
				$max_execution = (int) ini_get( 'max_execution_time' );
				$time_start = microtime( true );

				// Process entries with real-time monitoring
				$processed = 0;
				$failed = 0;
				$stopped_early = false;
				$stop_reason = '';

				global $wpdb;
				$table_name = $wpdb->prefix . 'superforms_entry_data';

				while ( $processed < $batch_size ) {
					// REAL-TIME MEMORY CHECK (before each entry)
					$current_memory = memory_get_usage( true );
					$memory_percent = $current_memory / $memory_limit;

					if ( $memory_percent > 0.85 ) { // 85% threshold
						self::log( sprintf(
							'Memory threshold reached (%.1f%%), stopping batch at %d/%d entries',
							$memory_percent * 100,
							$processed,
							$batch_size
						) );
						$stopped_early = true;
						$stop_reason = 'memory_threshold';
						break;
					}

					// REAL-TIME TIME CHECK (if execution limit set)
					if ( $max_execution > 0 ) {
						$elapsed = microtime( true ) - $time_start;
						$time_percent = $elapsed / $max_execution;

						if ( $time_percent > 0.70 ) { // 70% of max time
							self::log( sprintf(
								'Time threshold reached (%.1f%%), stopping batch at %d/%d entries',
								$time_percent * 100,
								$processed,
								$batch_size
							) );
							$stopped_early = true;
							$stop_reason = 'time_threshold';
							break;
						}
					}

					// Get next unmigrated entry
					$entry_id = $wpdb->get_var( $wpdb->prepare(
						"SELECT p.ID
						FROM {$wpdb->posts} p
						LEFT JOIN {$table_name} e ON e.entry_id = p.ID
						WHERE p.post_type = %s
						AND p.ID > %d
						AND e.entry_id IS NULL
						ORDER BY p.ID ASC
						LIMIT 1",
						'super_contact_entry',
						$last_id
					) );

					// No more entries to migrate
					if ( ! $entry_id ) {
						self::log( 'No more entries to migrate' );
						break;
					}

					// Process single entry
					$entry_result = SUPER_Migration_Manager::migrate_single_entry( $entry_id );

					if ( $entry_result && ! is_wp_error( $entry_result ) ) {
						$processed++;
						$last_id = $entry_id;

						// Update progress in state
						if ( ! isset( $migration['migrated_entries'] ) ) {
							$migration['migrated_entries'] = 0;
						}
						$migration['migrated_entries']++;
					} else {
						$failed++;
						$last_id = $entry_id; // Still increment to avoid infinite loop

						// Add to failed entries list
						if ( ! isset( $migration['failed_entries'] ) ) {
							$migration['failed_entries'] = array();
						}
						$migration['failed_entries'][] = $entry_id;

						$error_msg = is_wp_error( $entry_result ) ? $entry_result->get_error_message() : 'Unknown error';
						self::log( "Entry {$entry_id} failed: {$error_msg}", 'warning' );
					}
				}

				// Update migration state with ACTUAL processed count
				$migration['last_processed_id'] = $last_id;
				$migration['last_batch_processed_at'] = current_time( 'mysql' );
				update_option( 'superforms_eav_migration', $migration );

				// Calculate remaining entries
				$total_remaining = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID)
					FROM {$wpdb->posts} p
					LEFT JOIN {$table_name} e ON e.entry_id = p.ID
					WHERE p.post_type = %s
					AND e.entry_id IS NULL",
					'super_contact_entry'
				) );

				$is_complete = ( $total_remaining == 0 );

				self::log( sprintf(
					'Batch completed: %d processed, %d failed, %d remaining%s',
					$processed,
					$failed,
					$total_remaining,
					$stopped_early ? " (stopped early: {$stop_reason})" : ''
				) );

				// Schedule next batch if needed
				if ( ! $is_complete && $total_remaining > 0 ) {
					self::schedule_batch();
					self::log( 'Migration incomplete, scheduled next batch' );
				} else {
					self::log( 'Migration completed successfully!' );
					$migration['status'] = 'completed';
					$migration['completed_at'] = current_time( 'mysql' );
					update_option( 'superforms_eav_migration', $migration );
					self::cleanup_on_completion();
				}

				// Release lock
				self::release_lock();

				return array(
					'processed' => $processed,
					'failed' => $failed,
					'remaining' => $total_remaining,
					'is_complete' => $is_complete,
					'stopped_early' => $stopped_early,
					'stop_reason' => $stop_reason,
					'last_id' => $last_id
				);

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
		 * Debug Action Scheduler execution
		 * Logs action data before Action Scheduler tries to execute it
		 *
		 * @param int $action_id The action ID
		 * @param string $context Execution context
		 * @since 6.0.0
		 */
		public static function debug_action_scheduler( $action_id, $context = '' ) {
			// Only log for our migration actions
			if ( function_exists( 'ActionScheduler' ) ) {
				try {
					$action = ActionScheduler::store()->fetch_action( $action_id );
					$hook = $action->get_hook();

					if ( $hook === self::AS_BATCH_HOOK || $hook === self::AS_HEALTH_CHECK_HOOK ) {
						error_log( '[SF Migration Debug] Action Scheduler BEFORE_EXECUTE:' );
						error_log( '[SF Migration Debug]   Action ID: ' . $action_id );
						error_log( '[SF Migration Debug]   Hook: ' . $hook );
						error_log( '[SF Migration Debug]   Context: ' . $context );
						error_log( '[SF Migration Debug]   Args type: ' . gettype( $action->get_args() ) );
						error_log( '[SF Migration Debug]   Args value: ' . print_r( $action->get_args(), true ) );
					}
				} catch ( Exception $e ) {
					error_log( '[SF Migration Debug] Failed to fetch action: ' . $e->getMessage() );
				}
			}
		}

		/**
		 * Acquire migration lock
		 *
		 * @return bool True if lock acquired, false if already locked
		 * @since 6.0.0
		 */
		public static function acquire_lock() {
			$locked = get_transient( self::LOCK_KEY );

			// Debug logging
			error_log( '[SF Migration Debug] acquire_lock() called' );
			error_log( '[SF Migration Debug] LOCK_KEY: ' . self::LOCK_KEY );
			error_log( '[SF Migration Debug] $locked type: ' . gettype( $locked ) );
			error_log( '[SF Migration Debug] $locked value: ' . var_export( $locked, true ) );
			error_log( '[SF Migration Debug] $locked truthy check: ' . ( $locked ? 'TRUE' : 'FALSE' ) );

			if ( $locked ) {
				error_log( '[SF Migration Debug] Lock already held, returning false' );
				return false; // Already locked
			}

			// Set lock with expiration
			$result = set_transient( self::LOCK_KEY, time(), self::LOCK_DURATION );
			error_log( '[SF Migration Debug] set_transient() result: ' . var_export( $result, true ) );
			error_log( '[SF Migration Debug] Lock acquired, returning true' );
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
