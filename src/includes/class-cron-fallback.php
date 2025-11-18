<?php
/**
 * WP-Cron Fallback System
 *
 * Ensures background jobs process reliably even when WP-Cron fails
 *
 * @package   Super Forms
 * @author    WebRehab
 * @since     6.4.127
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Cron_Fallback' ) ) :

    /**
     * Class SUPER_Cron_Fallback
     *
     * Handles detection and automatic remediation of WP-Cron failures
     *
     * @since 6.4.127
     */
    final class SUPER_Cron_Fallback {

        /**
         * Option key for tracking last queue run
         */
        const LAST_RUN_OPTION = 'superforms_last_queue_run';

        /**
         * Option key for async processing state
         */
        const ASYNC_ENABLED_OPTION = 'superforms_async_processing_enabled';

        /**
         * Staleness threshold in seconds (15 minutes)
         */
        const STALENESS_THRESHOLD = 900;

        /**
         * Initialize the cron fallback system
         *
         * @since 6.4.127
         */
        public static function init() {
            // Track queue runs for staleness detection (only if Action Scheduler available)
            if ( function_exists( 'ActionScheduler' ) ) {
                add_action( 'action_scheduler_after_process_queue', array( __CLASS__, 'track_queue_run' ) );
            }

            // Health check on admin init
            add_action( 'admin_init', array( __CLASS__, 'check_cron_health' ), 20 );

            // Auto-enable async mode if needed
            add_action( 'init', array( __CLASS__, 'maybe_enable_async_mode' ), 5 );
        }

        /**
         * Track successful queue runs
         *
         * Called after Action Scheduler processes queue
         *
         * @since 6.4.127
         */
        public static function track_queue_run() {
            update_option( self::LAST_RUN_OPTION, current_time( 'mysql' ), false );
        }

        /**
         * Check if WP-Cron is disabled
         *
         * @since 6.4.127
         * @return bool
         */
        public static function is_cron_disabled() {
            return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON === true;
        }

        /**
         * Check if queue is stale (no processing in 15+ minutes)
         *
         * @since 6.4.127
         * @return bool
         */
        public static function is_queue_stale() {
            $last_run = get_option( self::LAST_RUN_OPTION );

            if ( ! $last_run ) {
                // No run recorded yet - consider stale if work is pending
                // This catches fresh installations with broken WP-Cron
                return self::has_pending_work();
            }

            $time_since_last = time() - strtotime( $last_run );
            return $time_since_last > self::STALENESS_THRESHOLD;
        }

        /**
         * Check if migration or background work is pending
         *
         * @since 6.4.127
         * @return bool
         */
        public static function has_pending_work() {
            // Check if migration is needed
            if ( class_exists( 'SUPER_Background_Migration' ) ) {
                if ( SUPER_Background_Migration::needs_migration() ) {
                    return true;
                }
            }

            // Check for other pending Action Scheduler jobs
            if ( function_exists( 'as_get_scheduled_actions' ) ) {
                $pending = as_get_scheduled_actions( array(
                    'group' => 'superforms-migration',
                    'status' => 'pending',
                    'per_page' => 1,
                ) );

                if ( ! empty( $pending ) ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Check if system intervention is needed
         *
         * Returns true when both queue is stale AND work is pending,
         * indicating WP-Cron appears broken and fallback should activate
         *
         * @since 6.4.127
         * @return bool
         */
        public static function needs_intervention() {
            return self::is_queue_stale() && self::has_pending_work();
        }

        /**
         * Auto-enable async processing mode when WP-Cron is disabled
         *
         * @since 6.4.127
         */
        public static function maybe_enable_async_mode() {
            // Only enable if WP-Cron is disabled
            if ( ! self::is_cron_disabled() ) {
                return;
            }

            // Check if already enabled
            if ( get_option( self::ASYNC_ENABLED_OPTION ) ) {
                return;
            }

            // Mark as enabled (for tracking purposes)
            update_option( self::ASYNC_ENABLED_OPTION, true, false );

            // Action Scheduler's async mode is already active on admin pages
            // The async_request->maybe_dispatch() is called automatically on 'shutdown'
            // Just ensure we're using appropriate batch sizes for better performance
            add_filter( 'action_scheduler_queue_runner_batch_size', array( __CLASS__, 'async_batch_size' ) );
        }

        /**
         * Set batch size for async processing
         *
         * @since 6.4.127
         * @return int
         */
        public static function async_batch_size() {
            return 25; // Process 25 items per async request
        }

        /**
         * Check cron health and detect failures
         *
         * Runs on admin_init to detect stalled background jobs
         *
         * @since 6.4.127
         */
        public static function check_cron_health() {
            // Don't check if no work is pending
            if ( ! self::has_pending_work() ) {
                return;
            }

            // Check if queue is stale
            if ( ! self::is_queue_stale() ) {
                return; // Queue running fine
            }

            // Queue is stale with pending work - cron appears broken
            // Admin notice will be shown via show_admin_notices()
        }

        /**
         * Check if we should show the cron warning notice
         *
         * @since 6.4.127
         * @return bool
         */
        public static function should_show_notice() {
            // Only show to administrators
            if ( ! current_user_can( 'manage_options' ) ) {
                return false;
            }

            // Check if pending work exists
            if ( ! self::has_pending_work() ) {
                return false;
            }

            // Check if queue is stale
            if ( ! self::is_queue_stale() ) {
                return false;
            }

            // Check if user dismissed recently (last 1 hour)
            $dismissed = get_user_meta( get_current_user_id(), 'super_cron_notice_dismissed', true );
            if ( $dismissed && ( time() - $dismissed ) < 3600 ) {
                return false;
            }

            // Check if migration is currently locked (actively running)
            if ( class_exists( 'SUPER_Background_Migration' ) ) {
                if ( SUPER_Background_Migration::is_locked() ) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Attempt async processing
         *
         * Triggers Action Scheduler async dispatch
         *
         * @since 6.4.127
         * @return bool True if async dispatch was triggered successfully
         */
        public static function try_async_processing() {
            // CLI can't do HTTP async requests - no HTTP server listening
            if ( php_sapi_name() === 'cli' ) {
                return false;
            }

            // Check if Action Scheduler available
            if ( ! function_exists( 'ActionScheduler' ) || ! class_exists( 'ActionScheduler_QueueRunner' ) ) {
                return false;
            }

            // Safe to try async (we're in HTTP context)
            $queue_runner = ActionScheduler_QueueRunner::instance();
            if ( isset( $queue_runner->async_request ) ) {
                $queue_runner->async_request->maybe_dispatch();
                return true; // Assume it will work, sync fallback handles failures
            }

            return false;
        }

    }

endif;

// Initialize cron fallback system
SUPER_Cron_Fallback::init();
