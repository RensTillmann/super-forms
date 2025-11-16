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
		 * Lock duration in seconds (5 minutes)
		 */
		const LOCK_DURATION = 300;

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
		 * Version when EAV migration was introduced
		 * Migration only runs when upgrading FROM version < this TO version >= this
		 * This prevents migration from running on every version bump in production
		 *
		 * @since 6.4.126
		 */
		const MIGRATION_INTRODUCED_VERSION = '6.4.100';

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
			// 30-day retention cleanup hook
		add_action( 'superforms_cleanup_serialized_data', array( 'SUPER_Migration_Manager', 'cleanup_expired_serialized_data' ) );

		add_action( 'action_scheduler_before_execute', array( __CLASS__, 'debug_action_scheduler' ), 10, 2 );

			// Version-based detection: Check on init if version changed
			add_action( 'init', array( __CLASS__, 'check_version_and_schedule' ), 5 );
		// Hourly check for unmigrated entries (catches imported data)
		add_action( 'init', array( __CLASS__, 'hourly_unmigrated_check' ), 10 );
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
			// ACQUIRE SETUP LOCK - prevents race conditions during table creation
			// Lock handles duplicate prevention atomically, no need for pre-check
			set_transient( self::SETUP_LOCK_KEY, 'yes', self::SETUP_LOCK_DURATION );

			try {
				self::log( "Plugin upgraded: {$stored_version} → {$current_version}" );
				update_option( 'super_plugin_version', $current_version );

				// Check if this upgrade crosses the migration threshold
				// Only migrate when upgrading FROM pre-EAV TO post-EAV version
				$crosses_threshold = version_compare( $stored_version, self::MIGRATION_INTRODUCED_VERSION, '<' )
				                  && version_compare( $current_version, self::MIGRATION_INTRODUCED_VERSION, '>=' );

				// Check if migration was ever completed
				$migration_state = get_option( 'superforms_eav_migration', array() );
				$migration_completed = isset( $migration_state['status'] ) && $migration_state['status'] === 'completed';

				if ( $crosses_threshold && ! $migration_completed ) {
					// FIRST-TIME MIGRATION: User upgrading from pre-EAV to post-EAV version
					self::log( 'Crossing migration threshold - setting up EAV infrastructure' );

					// SELF-HEALING: Auto-create infrastructure
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

					// Schedule migration if serialized data exists
					self::schedule_if_needed( 'version_upgrade' );

					// Immediately process first batch to close activation gap
					// Uses dynamic batch sizing (10-100 entries based on dataset size and server capacity)
					if ( self::needs_migration() ) {
						$batch_size = self::calculate_batch_size();
						self::process_immediate_batch( $batch_size );
					}
				} else {
					// REGULAR UPDATE: Just ensure infrastructure exists (self-healing)
					// No migration needed - either already completed or not crossing threshold
					if ( ! $crosses_threshold ) {
						self::log( 'Regular update (not crossing migration threshold)' );
					}
					if ( $migration_completed ) {
						self::log( 'Migration already completed previously' );
					}

					// Still ensure tables exist (self-healing for edge cases)
					if ( class_exists( 'SUPER_Install' ) ) {
						SUPER_Install::ensure_tables_exist();
					}
				}
			} finally {
				// RELEASE SETUP LOCK - guaranteed cleanup even on fatal errors
				delete_transient( self::SETUP_LOCK_KEY );
			}
		}
	}

	/**
	 * Reset migration for fresh start (TESTING ONLY)
	 *
	 * Provides clean slate for testing migration system by:
	 * - Truncating EAV table (removes old migrated data)
	 * - Deleting migration state (will be reinitialized fresh)
	 * - Clearing Action Scheduler jobs and logs
	 * - Removing migration locks
	 * - Clearing dismissed notice flags
	 *
	 * WARNING: This is for testing/development only. Should only be called:
	 * 1. From Developer Tools page (when DEBUG_SF is enabled)
	 * 2. Never automatically on production version upgrades
	 *
	 * Source serialized data remains untouched, so migration can re-run.
	 *
	 * @since 6.4.124
	 */
	public static function reset_for_fresh_migration() {
		global $wpdb;

		self::log( 'Resetting for fresh migration start...' );

		// 1. Truncate EAV table (clean slate)
		$table_name = $wpdb->prefix . 'superforms_entry_data';

		// Verify table exists before truncating (additional safety)
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		if ( $table_exists === $table_name ) {
			// Use esc_sql for table names (prepare() doesn't support table names)
			$safe_table = esc_sql( $table_name );
			$wpdb->query( "TRUNCATE TABLE `{$safe_table}`" );
			self::log( 'EAV table truncated' );
		} else {
			self::log( 'EAV table does not exist, skipping truncate', 'warning' );
		}

		// 2. Delete migration state option (will be reinitialized fresh)
		delete_option( 'superforms_eav_migration' );
		self::log( 'Migration state deleted' );

		// 3. Clear Action Scheduler: Cancel pending migration actions
		if ( class_exists( 'ActionScheduler_Store' ) ) {
			$store = ActionScheduler_Store::instance();

			// Cancel all pending migration batch actions
			$pending_actions = as_get_scheduled_actions(
				array(
					'hook' => 'super_forms_migration_batch',
					'status' => ActionScheduler_Store::STATUS_PENDING,
					'per_page' => -1,
				),
				'ids'
			);

			foreach ( $pending_actions as $action_id ) {
				as_unschedule_action( 'super_forms_migration_batch', array(), 'super-forms-migration' );
			}

			if ( ! empty( $pending_actions ) ) {
				self::log( 'Cancelled ' . count( $pending_actions ) . ' pending Action Scheduler jobs' );
			}
		}

		// 4. Delete migration lock transients
		delete_transient( 'superforms_migration_lock' );
		delete_transient( self::SETUP_LOCK_KEY );
		self::log( 'Migration locks cleared' );

		// 5. Clear dismissed notice flags for all users
		$wpdb->query(
			"DELETE FROM {$wpdb->usermeta}
			WHERE meta_key IN (
				'super_migration_completed_dismissed',
				'super_migration_rollback_dismissed'
			)"
		);
		self::log( 'Dismissed notice flags cleared' );

		self::log( 'Fresh migration reset complete - ready for clean test' );
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
		// Get total from live database query
		$status = SUPER_Migration_Manager::get_migration_status();
		$total = isset( $status['total_entries'] ) ? (int) $status['total_entries'] : 0;

		// Get server limits
		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		if ( $memory_limit === false || $memory_limit <= 0 ) {
			// Fallback to safe default if wp_convert_hr_to_bytes() fails
			$memory_limit = SUPER_Migration_Manager::DEFAULT_MEMORY_LIMIT_MB * 1024 * 1024; // 256MB
		}
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
		// CACHE: Check transient cache first (60-second TTL)
		// This reduces overhead from multiple calls per page load
		$cache_key = 'superforms_needs_migration';
		$cached = get_transient( $cache_key );

		if ( $cached !== false ) {
			return $cached === 'yes';
		}

		global $wpdb;

		// Verify EAV table exists before attempting migration
		$table_name = $wpdb->prefix . 'superforms_entry_data';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		if ( $table_exists !== $table_name ) {
			// Table doesn't exist - can't migrate
			// This will be auto-created by version detection if needed
			set_transient( $cache_key, 'no', 60 ); // Cache for 60 seconds
			return false;
		}

		// ALWAYS check for actual unmigrated entries
		// Don't trust stored state - CSV imports might add serialized data after completion
		//
		// NOTE: This query implicitly excludes entries with only _cleanup_empty markers
		// because those entries have at least one row in EAV table (e.entry_id IS NOT NULL).
		// Only entries with NO EAV data at all (e.entry_id IS NULL) are counted as unmigrated.
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

		// Cache result for 60 seconds
		$needs_migration = $unmigrated > 0;
		set_transient( $cache_key, $needs_migration ? 'yes' : 'no', 60 );

		return $needs_migration;
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

			// Note: Counters are calculated live from database in get_migration_status()
			// No anomaly detection needed - database is always the source of truth

			// Count total entries if not already counted
			// Count ONLY valid entries (posts that exist AND have data)
			if ( ! isset( $status['total_entries'] ) || $status['total_entries'] === 0 ) {
				global $wpdb;
				$status['total_entries'] = $wpdb->get_var(
					"SELECT COUNT(DISTINCT p.ID)
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
					WHERE p.post_type = 'super_contact_entry'
					AND pm.meta_key = '_super_contact_entry_data'
					AND pm.meta_value != ''"
				);
				self::log( "Counted {$status['total_entries']} valid entries to migrate (excludes empty/orphaned)" );
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
			// ACQUIRE SCHEDULING LOCK: Prevents race condition where multiple processes
			// schedule duplicate batches between check and create operations
			if ( get_transient( 'super_migration_schedule_lock' ) ) {
				self::log( 'Schedule lock held by another process, skipping' );
				return false;
			}
			set_transient( 'super_migration_schedule_lock', 'locked', 60 ); // 1-minute lock

			try {
				// DUPLICATE CHECK: Prevent scheduling if batch already queued
			// This fixes race condition causing counter overflow from duplicate actions
			if ( function_exists( 'as_next_scheduled_action' ) ) {
				$next_scheduled = as_next_scheduled_action( self::AS_BATCH_HOOK, array(), 'superforms-migration' );

				if ( $next_scheduled ) {
					$next_run = date( 'Y-m-d H:i:s', $next_scheduled );
					self::log( "Batch already scheduled (next run: {$next_run}), preventing duplicate" );

					// Debug: Log queue depth to detect race conditions
					if ( function_exists( 'as_get_scheduled_actions' ) ) {
						$pending_count = count( as_get_scheduled_actions( array(
							'hook' => self::AS_BATCH_HOOK,
							'group' => 'superforms-migration',
							'status' => 'pending',
							'per_page' => 100,
						) ) );
						self::log( "Action Scheduler queue status: {$pending_count} pending batch actions" );
					}

					return false; // Don't schedule duplicate
				} else {
					self::log( 'No pending batch actions found, proceeding with scheduling' );
				}
			}

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
					self::log( "✓ Scheduled batch via Action Scheduler (async, size: {$batch_size})" );
					return true;
				} catch ( Exception $e ) {
					self::log( 'Action Scheduler scheduling failed: ' . $e->getMessage(), 'error' );
					// Fall through to WP-Cron
				}
			}

			// Fallback to WP-Cron (immediate execution via async)
			$cron_scheduled = wp_next_scheduled( 'super_migration_cron_batch' );
			if ( ! $cron_scheduled ) {
				wp_schedule_single_event( time(), 'super_migration_cron_batch', array( $batch_size ) );
				self::log( "✓ Scheduled batch via WP-Cron (fallback, size: {$batch_size})" );
				return true;
			} else {
				$next_run = date( 'Y-m-d H:i:s', $cron_scheduled );
				self::log( "Batch already scheduled in WP-Cron (next run: {$next_run}), preventing duplicate" );
				return false;
			}
		} finally {
			// GUARANTEED CLEANUP: Always release lock, even on exceptions
			delete_transient( 'super_migration_schedule_lock' );
		}
	}


	/**
	 * Process immediate batch on plugin activation/update
	 *
	 * Closes the gap between plugin activation and Action Scheduler queue processing
	 * by processing first batch synchronously during the init action.
	 *
	 * Uses same dynamic batch sizing as regular migration batches for consistency.
	 * Safe for activation because batch size is calculated based on server capacity.
	 *
	 * @param int $batch_size Batch size (calculated dynamically by calculate_batch_size())
	 * @return array Result with migrated/failed counts
	 * @since 6.4.127
	 */
	private static function process_immediate_batch( $batch_size ) {
		// Safety: Only run during early init (before wp_loaded)
		// Prevents running on regular page loads
		if ( did_action( 'wp_loaded' ) ) {
			self::log( 'Too late in request cycle for immediate batch, skipping' );
			return array( 'migrated' => 0, 'failed' => 0 );
		}

		self::log( "Processing immediate activation batch (size: {$batch_size})" );

		// Acquire lock (prevents duplicate processing)
		if ( ! self::acquire_lock() ) {
			self::log( 'Migration already locked, skipping immediate batch' );
			return array( 'migrated' => 0, 'failed' => 0 );
		}

		try {
			// Use existing process_batch() method
			$result = self::process_batch( $batch_size );

			self::log( sprintf(
				"Immediate batch complete: %d migrated, %d failed (%.2fs)",
				$result['migrated'],
				$result['failed'],
				$result['duration']
			) );

			return $result;

		} catch ( Exception $e ) {
			self::log( 'Immediate batch failed: ' . $e->getMessage(), 'error' );
			return array( 'migrated' => 0, 'failed' => 1 );

		} finally {
			// Always release lock
			self::release_lock();
		}
	}

	/**
	 * Recalculate migrated_entries counter from actual database
	 *
	 * Fixes counter overflow caused by race conditions where multiple batches
	 * processed the same entries and each incremented the counter.
	 *
	 * @return int Actual count of migrated entries
	 * @since 6.4.118
	 */
	public static function recalculate_migration_counter() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'superforms_entry_data';

		// Count unique entries in EAV table (source of truth)
		$actual_migrated = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT entry_id) FROM {$table_name}"
		);

		// Get current migration state
		$migration = get_option( 'superforms_eav_migration', array() );
		$old_count = isset( $migration['migrated_entries'] ) ? (int) $migration['migrated_entries'] : 0;

		// Only update if different (prevents unnecessary writes)
		if ( $old_count !== $actual_migrated ) {
			$migration['migrated_entries'] = $actual_migrated;
			update_option( 'superforms_eav_migration', $migration );

			$difference = $old_count - $actual_migrated;
			$overflow_pct = $old_count > 0 ? round( ( $difference / $old_count ) * 100, 1 ) : 0;

			self::log(
				"Counter recalculated: {$old_count} → {$actual_migrated} (corrected -{$difference} overflow, {$overflow_pct}%)",
				'warning'
			);
		} else {
			self::log( "Counter verified: {$actual_migrated} entries migrated (no correction needed)" );
		}
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
				$needs_migration = self::needs_migration();

				if ( ! $needs_migration ) {
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

				// Note: Counters are calculated live from database in get_migration_status()
				// No need to store or recalculate them here

				// Get server limits for real-time monitoring
				$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
				if ( $memory_limit === false || $memory_limit <= 0 ) {
					// Fallback to safe default if wp_convert_hr_to_bytes() fails
					$memory_limit = SUPER_Migration_Manager::DEFAULT_MEMORY_LIMIT_MB * 1024 * 1024; // 256MB
				}
				$max_execution = (int) ini_get( 'max_execution_time' );
				$time_start = microtime( true );


				// Process entries with real-time monitoring
				$processed = 0;
				$failed = 0;
				$stopped_early = false;
				$stop_reason = '';

				global $wpdb;
				$table_name = $wpdb->prefix . 'superforms_entry_data';

				// Infinite loop protection: Safety limit for iterations
				$max_iterations = $batch_size * 2; // Allow 2x batch size for safety
				$iteration = 0;

				while ( $processed < $batch_size && $iteration < $max_iterations ) {
					$iteration++;

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


					// CLEANUP MODE: If no entry found with ID filter, check if unmigrated entries exist elsewhere
					if ( ! $entry_id ) {
						// Check if any unmigrated entries exist (without ID filter)
						$total_unmigrated = $wpdb->get_var( $wpdb->prepare(
							"SELECT COUNT(DISTINCT p.ID)
							FROM {$wpdb->posts} p
							LEFT JOIN {$table_name} e ON e.entry_id = p.ID
							WHERE p.post_type = %s
							AND e.entry_id IS NULL",
							'super_contact_entry'
						) );

						if ( $total_unmigrated > 0 ) {
							// Cleanup mode: unmigrated entries exist but can't be found with ID > filter
							// This happens when entries have IDs lower than last_processed_id
							self::log( "Cleanup mode activated: {$total_unmigrated} unmigrated entries found with IDs < {$last_id}" );

							// Query without ID filter to find the entry
							$entry_id = $wpdb->get_var( $wpdb->prepare(
								"SELECT p.ID
								FROM {$wpdb->posts} p
								LEFT JOIN {$table_name} e ON e.entry_id = p.ID
								WHERE p.post_type = %s
								AND e.entry_id IS NULL
								ORDER BY p.ID ASC
								LIMIT 1",
								'super_contact_entry'
							) );

						}
					}

					// No more entries to migrate
					if ( ! $entry_id ) {
						self::log( 'No more entries to migrate' );
						break;
					}

					// Process single entry
					$entry_result = SUPER_Migration_Manager::migrate_entry( $entry_id );

					// CRITICAL FIX: Use strict comparison to avoid counting 'skipped' as success
					// migrate_entry() returns: true (success), 'skipped' (empty entry), WP_Error (failure)
					if ( $entry_result === true ) {
						// SUCCESS: Entry migrated
						$processed++;
						$last_id = $entry_id;
						// Note: Counters calculated live from database, not stored
					} elseif ( $entry_result === 'skipped' ) {
						// SKIPPED: Entry has no data
						$processed++;
						$last_id = $entry_id;
						// Note: Counters calculated live from database, not stored
						self::log( "Entry {$entry_id} skipped (no data)" );
					} else {
						// FAILED: Migration error
						$failed++;
						$last_id = $entry_id; // Still increment to avoid infinite loop

						$error_msg = is_wp_error( $entry_result ) ? $entry_result->get_error_message() : 'Unknown error';

						// Distinguish verification failures from actual migration failures
						if ( is_wp_error( $entry_result ) && $entry_result->get_error_code() === 'verification_failed' ) {
							// VERIFICATION FAILED: Entry migrated but data mismatch detected
							if ( ! isset( $migration['verification_failed'] ) ) {
								$migration['verification_failed'] = array();
							}
							$migration['verification_failed'][ $entry_id ] = $error_msg;
							self::log( "Entry {$entry_id} verification failed: {$error_msg}", 'warning' );
						} else {
							// MIGRATION FAILED: Entry not migrated successfully
							if ( ! isset( $migration['failed_entries'] ) ) {
								$migration['failed_entries'] = array();
							}
							$migration['failed_entries'][ $entry_id ] = $error_msg;
							self::log( "Entry {$entry_id} migration failed: {$error_msg}", 'warning' );
						}
					}
				}

				// Check if we hit max iterations limit (possible infinite loop)
				if ( $iteration >= $max_iterations ) {
					self::log( sprintf(
						'WARNING: Hit max iteration limit (%d) - possible infinite loop. Processed: %d, Failed: %d',
						$max_iterations,
						$processed,
						$failed
					), 'warning' );
					$stopped_early = true;
					$stop_reason = 'max_iterations';
				}

				// Update migration state with ACTUAL processed count
				$migration['last_processed_id'] = $last_id;
				$migration['last_batch_processed_at'] = current_time( 'mysql' );
				update_option( 'superforms_eav_migration', $migration );

				// Calculate remaining entries (entries with ANY EAV data are not remaining)
				$total_remaining = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID)
					FROM {$wpdb->posts} p
					LEFT JOIN (
					    SELECT DISTINCT entry_id
					    FROM {$table_name}
					) e ON e.entry_id = p.ID
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

				// Release lock BEFORE scheduling next batch to prevent race conditions
				self::release_lock();

				// Schedule next batch if needed
				if ( ! $is_complete && $total_remaining > 0 ) {
					self::schedule_batch();
					self::log( 'Migration incomplete, scheduled next batch' );
				} else {
					self::log( 'Migration completed successfully!' );
					$migration['status'] = 'completed';
					$migration['using_storage'] = 'eav';
					$migration['completed_at'] = current_time( 'mysql' );
					update_option( 'superforms_eav_migration', $migration );
					self::cleanup_on_completion();
				}

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
	 * Hourly check for unmigrated entries
	 *
	 * Runs once per hour to detect entries that were imported via MySQL dump or CSV.
	 * Uses transient cache to prevent checking on every page load.
	 *
	 * @since 6.4.127
	 */
	public static function hourly_unmigrated_check() {
		// Only run in admin to avoid frontend overhead
		if ( ! is_admin() ) {
			return;
		}

		// Run once per hour max (transient-based throttling)
		$last_check = get_transient( 'superforms_hourly_unmigrated_check' );
		if ( $last_check ) {
			return; // Already checked this hour
		}

		// Quick check: Any unmigrated entries?
		if ( self::needs_migration() ) {
			self::log( 'Hourly check: Found unmigrated entries, scheduling migration' );
			self::schedule_if_needed( 'hourly_check' );
		}

		// Don't check again for 1 hour
		set_transient( 'superforms_hourly_unmigrated_check', time(), HOUR_IN_SECONDS );
	}

		/**
		 * Debug Action Scheduler execution
		 * Hook for debugging Action Scheduler execution
		 *
		 * Can be enabled in production via filter:
		 * add_filter('super_forms_migration_debug', '__return_true');
		 *
		 * @param int $action_id The action ID
		 * @param string $context Execution context
		 * @since 6.0.0
		 */
		public static function debug_action_scheduler( $action_id, $context = '' ) {
			// Allow debugging via filter for production troubleshooting
			if ( ! apply_filters( 'super_forms_migration_debug', false ) ) {
				return;
			}

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
						error_log( '[SF Migration Debug]   Args: ' . print_r( $action->get_args(), true ) );
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

			// Explicit check: transient exists and is not false
			if ( $locked !== false ) {
				return false; // Already locked
			}

			// Set lock with expiration (use string 'locked' for clarity)
			set_transient( self::LOCK_KEY, 'locked', self::LOCK_DURATION );
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

			// Schedule daily cleanup for 30-day retention
		if ( ! wp_next_scheduled( 'superforms_cleanup_serialized_data' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'superforms_cleanup_serialized_data' );
			self::log( 'Scheduled daily cleanup for 30-day serialized data retention' );
		}

		// Cleanup Action Scheduler: Delete completed/failed migration actions
			if ( class_exists( 'ActionScheduler_Store' ) && class_exists( 'ActionScheduler_DBStore' ) ) {
				global $wpdb;

				// Get completed and failed action IDs for migration hooks
				$completed_actions = as_get_scheduled_actions(
					array(
						'hook'     => self::AS_BATCH_HOOK,
						'status'   => ActionScheduler_Store::STATUS_COMPLETE,
						'per_page' => -1,
					),
					'ids'
				);

				$failed_actions = as_get_scheduled_actions(
					array(
						'hook'     => self::AS_BATCH_HOOK,
						'status'   => ActionScheduler_Store::STATUS_FAILED,
						'per_page' => -1,
					),
					'ids'
				);

				$action_ids = array_merge( $completed_actions, $failed_actions );

				if ( ! empty( $action_ids ) ) {
					// Sanitize IDs to ensure they're all positive integers
					$action_ids = array_map( 'absint', $action_ids );

					// Create placeholders for prepare() - one %d for each ID
					$placeholders = implode( ',', array_fill( 0, count( $action_ids ), '%d' ) );

					// Delete from actionscheduler_actions table
					$wpdb->query( $wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}actionscheduler_actions
						WHERE action_id IN ({$placeholders})",
						...$action_ids
					) );

					// Delete from actionscheduler_logs table
					$wpdb->query( $wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}actionscheduler_logs
						WHERE action_id IN ({$placeholders})",
						...$action_ids
					) );

					self::log( sprintf( 'Deleted %d completed/failed Action Scheduler actions and logs', count( $action_ids ) ) );
				}
			}

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
