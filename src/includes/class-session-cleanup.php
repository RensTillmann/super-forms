<?php
/**
 * Session Cleanup Handler
 *
 * Background jobs for cleaning up expired and abandoned sessions.
 * Uses Action Scheduler for reliable execution.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUPER_Session_Cleanup {

	/**
	 * Hook name for cleanup job
	 */
	const CLEANUP_HOOK = 'super_session_cleanup';

	/**
	 * Hook name for abandoned session check
	 */
	const ABANDONED_HOOK = 'super_session_check_abandoned';

	/**
	 * Batch size for cleanup operations
	 */
	const BATCH_SIZE = 100;

	/**
	 * Initialize cleanup jobs
	 */
	public static function init() {
		// Register hooks
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'run_cleanup' ) );
		add_action( self::ABANDONED_HOOK, array( __CLASS__, 'run_abandoned_check' ) );

		// Schedule recurring jobs on plugin activation
		add_action( 'super_activated', array( __CLASS__, 'schedule_jobs' ) );

		// Ensure jobs are scheduled
		add_action( 'init', array( __CLASS__, 'maybe_schedule_jobs' ), 20 );
	}

	/**
	 * Schedule cleanup jobs
	 */
	public static function schedule_jobs() {
		// Only run if Action Scheduler is available
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}

		// Clear existing schedules
		as_unschedule_all_actions( self::CLEANUP_HOOK );
		as_unschedule_all_actions( self::ABANDONED_HOOK );

		// Schedule cleanup every hour
		if ( ! as_next_scheduled_action( self::CLEANUP_HOOK ) ) {
			as_schedule_recurring_action(
				time(),
				HOUR_IN_SECONDS,
				self::CLEANUP_HOOK,
				array(),
				'super-forms'
			);
		}

		// Schedule abandoned check every 5 minutes
		if ( ! as_next_scheduled_action( self::ABANDONED_HOOK ) ) {
			as_schedule_recurring_action(
				time(),
				5 * MINUTE_IN_SECONDS,
				self::ABANDONED_HOOK,
				array(),
				'super-forms'
			);
		}
	}

	/**
	 * Ensure jobs are scheduled (runs on init)
	 */
	public static function maybe_schedule_jobs() {
		// Only run if Action Scheduler is available
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		// Only check occasionally
		$last_check = get_transient( 'super_session_cleanup_check' );
		if ( $last_check ) {
			return;
		}

		set_transient( 'super_session_cleanup_check', 1, HOUR_IN_SECONDS );

		// Schedule if not already scheduled
		if ( ! as_next_scheduled_action( self::CLEANUP_HOOK ) ) {
			as_schedule_recurring_action(
				time() + 60,
				HOUR_IN_SECONDS,
				self::CLEANUP_HOOK,
				array(),
				'super-forms'
			);
		}

		if ( ! as_next_scheduled_action( self::ABANDONED_HOOK ) ) {
			as_schedule_recurring_action(
				time() + 30,
				5 * MINUTE_IN_SECONDS,
				self::ABANDONED_HOOK,
				array(),
				'super-forms'
			);
		}
	}

	/**
	 * Run cleanup job
	 *
	 * Deletes expired sessions and fires events.
	 */
	public static function run_cleanup() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			return;
		}

		// Get expired sessions (batch)
		$expired = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, session_key, form_id, user_id, status, form_data, metadata
				FROM $table
				WHERE expires_at < NOW()
				AND status IN ('draft', 'abandoned')
				LIMIT %d",
				self::BATCH_SIZE
			),
			ARRAY_A
		);

		if ( empty( $expired ) ) {
			return;
		}

		$deleted_count = 0;

		foreach ( $expired as $session ) {
			// Fire session.expired event before deletion
			if ( class_exists( 'SUPER_Automation_Executor' ) ) {
				SUPER_Automation_Executor::fire_event(
					'session.expired',
					array(
						'form_id'         => absint( $session['form_id'] ),
						'session_id'      => absint( $session['id'] ),
						'session_key'     => $session['session_key'],
						'user_id'         => absint( $session['user_id'] ),
						'previous_status' => $session['status'],
						'form_data'       => ! empty( $session['form_data'] ) ? json_decode( $session['form_data'], true ) : array(),
						'metadata'        => ! empty( $session['metadata'] ) ? json_decode( $session['metadata'], true ) : array(),
						'timestamp'       => current_time( 'mysql' ),
					)
				);
			}

			// Delete session
			$wpdb->delete( $table, array( 'id' => $session['id'] ) );
			$deleted_count++;
		}

		// Log cleanup
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[Super Forms] Session cleanup: deleted %d expired sessions', $deleted_count ) );
		}

		// If we hit the batch limit, schedule another run immediately
		if ( $deleted_count >= self::BATCH_SIZE && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::CLEANUP_HOOK, array(), 'super-forms' );
		}
	}

	/**
	 * Run abandoned session check
	 *
	 * Marks sessions as abandoned if no activity for 30+ minutes.
	 */
	public static function run_abandoned_check() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			return;
		}

		// Find sessions with no activity for 30+ minutes
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-30 minutes' ) );

		$abandoned = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, session_key, form_id, user_id, form_data, metadata
				FROM $table
				WHERE status = 'draft'
				AND last_saved_at < %s
				LIMIT %d",
				$threshold,
				self::BATCH_SIZE
			),
			ARRAY_A
		);

		if ( empty( $abandoned ) ) {
			return;
		}

		$marked_count = 0;

		foreach ( $abandoned as $session ) {
			// Update status
			$wpdb->update(
				$table,
				array( 'status' => 'abandoned' ),
				array( 'id' => $session['id'] )
			);

			// Fire session.abandoned event
			if ( class_exists( 'SUPER_Automation_Executor' ) ) {
				SUPER_Automation_Executor::fire_event(
					'session.abandoned',
					array(
						'form_id'                => absint( $session['form_id'] ),
						'session_id'             => absint( $session['id'] ),
						'session_key'            => $session['session_key'],
						'user_id'                => absint( $session['user_id'] ),
						'form_data'              => ! empty( $session['form_data'] ) ? json_decode( $session['form_data'], true ) : array(),
						'metadata'               => ! empty( $session['metadata'] ) ? json_decode( $session['metadata'], true ) : array(),
						'abandoned_after_minutes' => 30,
						'timestamp'              => current_time( 'mysql' ),
					)
				);
			}

			$marked_count++;
		}

		// Log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $marked_count > 0 ) {
			error_log( sprintf( '[Super Forms] Session abandoned check: marked %d sessions', $marked_count ) );
		}
	}

	/**
	 * Get session statistics
	 *
	 * @return array Statistics
	 */
	public static function get_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			return array(
				'total'     => 0,
				'active'    => 0,
				'abandoned' => 0,
				'completed' => 0,
				'aborted'   => 0,
				'expired'   => 0,
			);
		}

		$result = $wpdb->get_row(
			"SELECT
				COUNT(*) as total,
				SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as active,
				SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned,
				SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
				SUM(CASE WHEN status = 'aborted' THEN 1 ELSE 0 END) as aborted,
				SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired
			FROM $table",
			ARRAY_A
		);

		return $result ? $result : array(
			'total'     => 0,
			'active'    => 0,
			'abandoned' => 0,
			'completed' => 0,
			'aborted'   => 0,
			'expired'   => 0,
		);
	}

	/**
	 * Manual cleanup trigger (for admin)
	 *
	 * @return array Results
	 */
	public static function manual_cleanup() {
		self::run_abandoned_check();
		self::run_cleanup();

		return self::get_stats();
	}

	/**
	 * Unschedule all jobs (for plugin deactivation)
	 */
	public static function unschedule_jobs() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::CLEANUP_HOOK );
			as_unschedule_all_actions( self::ABANDONED_HOOK );
		}
	}
}

// Initialize
SUPER_Session_Cleanup::init();
