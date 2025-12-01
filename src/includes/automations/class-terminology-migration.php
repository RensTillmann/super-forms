<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Terminology_Migration' ) ) :

    /**
     * SUPER_Terminology_Migration Class
     *
     * Handles the database migration from 'triggers' to 'automations' terminology.
     * This ensures backward compatibility and smooth transition for existing users.
     *
     * @since 6.5.0
     */
    class SUPER_Terminology_Migration {

        /**
         * Run the migration
         *
         * @return void
         */
        public static function run() {
            if ( get_option( 'super_terminology_migration_completed' ) ) {
                return;
            }

            self::migrate_tables();
            self::migrate_options();
            self::migrate_post_meta();
            self::migrate_scheduled_actions();

            update_option( 'super_terminology_migration_completed', true );

            // Log completion
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( '[Super Forms] Terminology migration (Triggers -> Automations) completed successfully' );
            }
        }

        /**
         * Rename database tables and columns
         *
         * @return void
         */
        private static function migrate_tables() {
            global $wpdb;

            // Check if old tables exist (need migration)
            $old_table = $wpdb->prefix . 'superforms_triggers';
            $new_table = $wpdb->prefix . 'superforms_automations';

            $old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) ) === $old_table;
            $new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) ) === $new_table;

            // If old table exists and new table doesn't, rename tables
            if ( $old_exists && ! $new_exists ) {
                // Rename main triggers table
                $wpdb->query( "RENAME TABLE `{$wpdb->prefix}superforms_triggers` TO `{$wpdb->prefix}superforms_automations`" );

                // Rename columns in automations table
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}superforms_automations` CHANGE `trigger_name` `name` VARCHAR(255) NOT NULL" );
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}superforms_automations` CHANGE `workflow_type` `type` VARCHAR(50) NOT NULL DEFAULT 'visual'" );

                // Rename trigger_actions table
                $old_actions = $wpdb->prefix . 'superforms_trigger_actions';
                if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_actions ) ) === $old_actions ) {
                    $wpdb->query( "RENAME TABLE `{$wpdb->prefix}superforms_trigger_actions` TO `{$wpdb->prefix}superforms_automation_actions`" );
                    $wpdb->query( "ALTER TABLE `{$wpdb->prefix}superforms_automation_actions` CHANGE `trigger_id` `automation_id` BIGINT(20) UNSIGNED NOT NULL" );
                }

                // Rename trigger_logs table
                $old_logs = $wpdb->prefix . 'superforms_trigger_logs';
                if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_logs ) ) === $old_logs ) {
                    $wpdb->query( "RENAME TABLE `{$wpdb->prefix}superforms_trigger_logs` TO `{$wpdb->prefix}superforms_automation_logs`" );
                    $wpdb->query( "ALTER TABLE `{$wpdb->prefix}superforms_automation_logs` CHANGE `trigger_id` `automation_id` BIGINT(20) UNSIGNED NOT NULL" );
                }

                // Rename trigger_events table if it existed (from early dev versions)
                $old_events = $wpdb->prefix . 'superforms_trigger_events';
                if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_events ) ) === $old_events ) {
                    $wpdb->query( "RENAME TABLE `{$wpdb->prefix}superforms_trigger_events` TO `{$wpdb->prefix}superforms_automation_events`" );
                    $wpdb->query( "ALTER TABLE `{$wpdb->prefix}superforms_automation_events` CHANGE `trigger_id` `automation_id` BIGINT(20) UNSIGNED NOT NULL" );
                }
            }
        }

        /**
         * Migrate options
         *
         * @return void
         */
        private static function migrate_options() {
            // Migrate log retention option
            $old_retention = get_option( 'super_trigger_log_retention_days' );
            if ( $old_retention !== false ) {
                update_option( 'super_automation_log_retention_days', $old_retention );
                delete_option( 'super_trigger_log_retention_days' );
            }

            // Migrate email migration status option
            $old_email_migration = get_option( 'superforms_email_trigger_migration' );
            if ( $old_email_migration !== false ) {
                update_option( 'superforms_email_automation_migration', $old_email_migration );
                delete_option( 'superforms_email_trigger_migration' );
            }
        }

        /**
         * Migrate post meta keys
         *
         * @return void
         */
        private static function migrate_post_meta() {
            global $wpdb;

            // Rename scheduled action timestamp meta key
            $wpdb->query(
                "UPDATE {$wpdb->postmeta}
                 SET meta_key = '_super_scheduled_automation_action_timestamp'
                 WHERE meta_key = '_super_scheduled_trigger_action_timestamp'"
            );

            // Rename scheduled action data meta key
            $wpdb->query(
                "UPDATE {$wpdb->postmeta}
                 SET meta_key = '_super_scheduled_automation_action_data'
                 WHERE meta_key = '_super_scheduled_trigger_action_data'"
            );

            // Rename entry meta for last execution
            $wpdb->query(
                "UPDATE {$wpdb->postmeta}
                 SET meta_key = '_super_last_automation_execution'
                 WHERE meta_key = '_super_last_trigger_execution'"
            );
        }

        /**
         * Migrate scheduled actions (Action Scheduler)
         *
         * @return void
         */
        private static function migrate_scheduled_actions() {
            if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
                return;
            }

            // Find pending actions with old hook name
            $actions = as_get_scheduled_actions( array(
                'hook' => 'super_scheduled_trigger_actions',
                'status' => \ActionScheduler_Store::STATUS_PENDING,
                'per_page' => 100
            ) );

            foreach ( $actions as $action_id => $action ) {
                $args = $action->get_args();
                $schedule = $action->get_schedule();

                // Reschedule with new hook name
                as_schedule_single_action(
                    $schedule->get_date()->getTimestamp(),
                    'super_scheduled_automation_execution',
                    $args
                );

                // Delete old action
                as_unschedule_action( 'super_scheduled_trigger_actions', $args );
            }

            // Clean up old log cleanup cron
            $timestamp = wp_next_scheduled( 'super_trigger_log_cleanup' );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, 'super_trigger_log_cleanup' );

                // Schedule new one immediately if not present
                if ( ! wp_next_scheduled( 'super_automation_log_cleanup' ) ) {
                    wp_schedule_event( time(), 'daily', 'super_automation_log_cleanup' );
                }
            }
        }
    }
endif;
