<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Form_Background_Migration' ) ) :

/**
 * SUPER_Form_Background_Migration Class
 *
 * Handles the background migration of forms from the wp_posts table
 * to the custom wp_superforms_forms table.
 *
 * @since 6.6.0
 */
class SUPER_Form_Background_Migration {

    /**
     * Migration state option name.
     *
     * @var string
     */
    const MIGRATION_OPTION = 'superforms_form_migration_state';

    /**
     * Action Scheduler hook for processing a batch.
     *
     * @var string
     */
    const AS_BATCH_HOOK = 'superforms_form_migrate_batch';

    /**
     * Lock transient key to prevent concurrent batches.
     *
     * @var string
     */
    const LOCK_KEY = 'superforms_form_migration_lock';

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( self::AS_BATCH_HOOK, array( __CLASS__, 'process_batch' ) );
    }

    /**
     * Schedule the migration if it's needed.
     * Can be called from an upgrade routine.
     */
    public static function schedule_if_needed() {
        self::init_migration_state();
        $state = get_option( self::MIGRATION_OPTION );

        if ( 'completed' === $state['status'] ) {
            return;
        }

        $count = self::count_forms_to_migrate();
        if ( $count > 0 && 'in_progress' !== $state['status'] ) {
            $state['status'] = 'in_progress';
            $state['total_to_migrate'] = $count;
            $state['started_at'] = current_time( 'mysql' );
            update_option( self::MIGRATION_OPTION, $state );
            self::schedule_batch();
        }
    }

    /**
     * Process a batch of forms to migrate.
     * This is triggered by Action Scheduler.
     */
    public static function process_batch() {
        if ( get_transient( self::LOCK_KEY ) ) {
            return; // Another batch is already running.
        }
        set_transient( self::LOCK_KEY, time(), MINUTE_IN_SECONDS * 2 );

        $state = get_option( self::MIGRATION_OPTION );
        $batch_size = apply_filters( 'super_form_migration_batch_size', 25 );

        $forms_to_migrate = self::get_forms_to_migrate( $state['last_processed_id'], $batch_size );

        if ( empty( $forms_to_migrate ) ) {
            $state['status'] = 'completed';
            $state['completed_at'] = current_time( 'mysql' );
            update_option( self::MIGRATION_OPTION, $state );
            delete_transient( self::LOCK_KEY );
            return;
        }

        foreach ( $forms_to_migrate as $form_post ) {
            $result = self::migrate_form( $form_post );
            if ( is_wp_error( $result ) ) {
                $state['failed_forms'][ $form_post->ID ] = $result->get_error_message();
            } else {
                $state['migrated_count']++;
            }
            $state['last_processed_id'] = $form_post->ID;
        }

        update_option( self::MIGRATION_OPTION, $state );
        self::schedule_batch(); // Schedule the next batch.
        delete_transient( self::LOCK_KEY );
    }

    /**
     * Migrate a single form from post to custom table.
     *
     * @param WP_Post $form_post The form post object.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private static function migrate_form( $form_post ) {
        $settings = get_post_meta( $form_post->ID, '_super_form_settings', true );
        $settings = is_array( $settings ) ? $settings : array();

        $elements = isset( $settings['elements'] ) ? $settings['elements'] : array();
        unset( $settings['elements'] );

        $translations = isset( $settings['translations'] ) ? $settings['translations'] : array();
        unset( $settings['translations'] );

        $new_form_data = array(
            'id' => $form_post->ID, // Attempt to preserve the original ID
            'name' => $form_post->post_title,
            'status' => $form_post->post_status,
            'elements' => $elements,
            'settings' => $settings,
            'translations' => $translations,
            'created_at' => $form_post->post_date,
            'updated_at' => $form_post->post_modified,
        );

        // Check if a form with this ID already exists in the new table
        if( SUPER_Form_DAL::get( $form_post->ID ) ) {
            // If it exists, update it instead of creating
            $result = SUPER_Form_DAL::update( $form_post->ID, $new_form_data );
        } else {
            // Otherwise, create it
            $result = SUPER_Form_DAL::create( $new_form_data );
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        update_post_meta( $form_post->ID, '_super_migrated_to_table', true );

        return true;
    }

    /**
     * Get a batch of forms to migrate from the wp_posts table.
     *
     * @param int $last_processed_id The ID of the last processed form.
     * @param int $limit The number of forms to retrieve.
     * @return array An array of WP_Post objects.
     */
    private static function get_forms_to_migrate( $last_processed_id, $limit ) {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT p.* FROM {$wpdb->posts} as p
            LEFT JOIN {$wpdb->postmeta} as pm ON p.ID = pm.post_id AND pm.meta_key = '_super_migrated_to_table'
            WHERE p.post_type = 'super_form' AND p.ID > %d AND pm.meta_value IS NULL
            ORDER BY p.ID ASC
            LIMIT %d",
            $last_processed_id,
            $limit
        );
        return $wpdb->get_results( $query );
    }

    /**
     * Count how many forms are left to migrate.
     *
     * @return int
     */
    private static function count_forms_to_migrate() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(p.ID) FROM {$wpdb->posts} as p
            LEFT JOIN {$wpdb->postmeta} as pm ON p.ID = pm.post_id AND pm.meta_key = '_super_migrated_to_table'
            WHERE p.post_type = 'super_form' AND pm.meta_value IS NULL"
        );
    }

    /**
     * Schedule a new batch to be processed by Action Scheduler.
     */
    private static function schedule_batch() {
        if ( ! as_next_scheduled_action( self::AS_BATCH_HOOK ) ) {
            as_schedule_single_action( time() + 5, self::AS_BATCH_HOOK, array(), 'superforms_migration' );
        }
    }

    /**
     * Initialize the migration state in the options table.
     */
    private static function init_migration_state() {
        $state = get_option( self::MIGRATION_OPTION );
        if ( false === $state ) {
            $state = array(
                'status'             => 'not_started',
                'last_processed_id'  => 0,
                'total_to_migrate'   => self::count_forms_to_migrate(),
                'migrated_count'     => 0,
                'failed_forms'       => array(),
                'started_at'         => '',
                'completed_at'       => '',
            );
            update_option( self::MIGRATION_OPTION, $state );
        }
    }

    /**
     * Get migration status information
     *
     * @return array Migration state with computed values
     */
    public static function get_migration_status() {
        $state = get_option( self::MIGRATION_OPTION );

        if ( false === $state ) {
            self::init_migration_state();
            $state = get_option( self::MIGRATION_OPTION );
        }

        $remaining = $state['total_to_migrate'] - $state['migrated_count'];
        $progress = $state['total_to_migrate'] > 0
            ? round( ( $state['migrated_count'] / $state['total_to_migrate'] ) * 100, 2 )
            : 100;

        return array(
            'status'           => $state['status'],
            'is_complete'      => $state['status'] === 'completed',
            'total_forms'      => $state['total_to_migrate'],
            'migrated'         => $state['migrated_count'],
            'remaining'        => $remaining,
            'failed_count'     => count( $state['failed_forms'] ),
            'failed_forms'     => $state['failed_forms'],
            'progress_percent' => $progress,
            'started_at'       => $state['started_at'],
            'completed_at'     => $state['completed_at'],
        );
    }

    /**
     * Check if migration is complete
     *
     * @return bool True if migration is complete
     */
    public static function is_migration_complete() {
        $state = get_option( self::MIGRATION_OPTION );
        return $state && $state['status'] === 'completed';
    }
}

endif;
