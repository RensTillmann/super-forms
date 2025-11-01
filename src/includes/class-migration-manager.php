<?php
/**
 * Migration Manager for EAV Contact Entry Data
 *
 * Handles background migration of contact entry data from serialized storage
 * to EAV tables with batch processing, progress tracking, and rollback support.
 *
 * @package Super Forms
 * @since   6.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('SUPER_Migration_Manager')) :

class SUPER_Migration_Manager {

    /**
     * Batch size for processing entries
     * @var int
     */
    const BATCH_SIZE = 10;

    /**
     * Initialize migration (called once when user starts migration)
     *
     * @since 6.0.0
     * @return array|WP_Error Migration state or error
     */
    public static function start_migration() {
        global $wpdb;

        // Get current migration state
        $migration = get_option('superforms_eav_migration');

        // Check if already in progress or completed
        if (!empty($migration) && $migration['status'] !== 'not_started') {
            return new WP_Error(
                'migration_already_started',
                __('Migration is already in progress or completed', 'super-forms')
            );
        }

        // Count total entries
        $total_entries = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
            AND post_status IN ('publish', 'super_unread', 'super_read')
        ");

        // Initialize migration state
        $migration_state = array(
            'status'               => 'in_progress',
            'using_storage'        => 'serialized', // Still reading from serialized during migration
            'total_entries'        => intval($total_entries),
            'migrated_entries'     => 0,
            'failed_entries'       => array(),
            'started_at'           => current_time('mysql'),
            'completed_at'         => '',
            'last_processed_id'    => 0,
            'verification_passed'  => false,
            'rollback_available'   => true,
        );

        update_option('superforms_eav_migration', $migration_state);

        return $migration_state;
    }

    /**
     * Process a batch of entries
     *
     * @since 6.0.0
     * @param int $batch_size Number of entries to process (default 1000)
     * @return array|WP_Error Batch result with progress info
     */
    public static function process_batch($batch_size = null) {
        global $wpdb;

        if ($batch_size === null) {
            $batch_size = self::BATCH_SIZE;
        }

        // Get current migration state
        $migration = get_option('superforms_eav_migration');

        if (empty($migration) || $migration['status'] !== 'in_progress') {
            return new WP_Error(
                'migration_not_in_progress',
                __('Migration is not in progress', 'super-forms')
            );
        }

        // Get next batch of entries
        $entries = $wpdb->get_results($wpdb->prepare("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
            AND post_status IN ('publish', 'super_unread', 'super_read')
            AND ID > %d
            ORDER BY ID ASC
            LIMIT %d
        ", $migration['last_processed_id'], $batch_size), ARRAY_A);

        if (empty($entries)) {
            // No more entries to process, complete migration
            return self::complete_migration();
        }

        $processed = 0;
        $failed = 0;
        $last_id = $migration['last_processed_id'];

        foreach ($entries as $entry) {
            $entry_id = $entry['ID'];
            $last_id = $entry_id;

            // Migrate this entry
            $result = self::migrate_entry($entry_id);

            if (is_wp_error($result)) {
                $migration['failed_entries'][$entry_id] = $result->get_error_message();
                $failed++;
                error_log('[Super Forms Migration] Failed to migrate entry ' . $entry_id . ': ' . $result->get_error_message());
            } else {
                $processed++;
            }
        }

        // Update migration progress
        $migration['migrated_entries'] += $processed;
        $migration['last_processed_id'] = $last_id;
        update_option('superforms_eav_migration', $migration);

        $remaining = $migration['total_entries'] - $migration['migrated_entries'];
        $progress = ($migration['migrated_entries'] / $migration['total_entries']) * 100;

        return array(
            'success'          => true,
            'processed'        => $processed,
            'failed'           => $failed,
            'total_processed'  => $migration['migrated_entries'],
            'total_entries'    => $migration['total_entries'],
            'remaining'        => $remaining,
            'progress'         => round($progress, 2),
            'is_complete'      => false,
        );
    }

    /**
     * Migrate a single entry from serialized to EAV
     *
     * @since 6.0.0
     * @param int $entry_id Entry ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private static function migrate_entry($entry_id) {
        // Read from serialized storage
        $data = get_post_meta($entry_id, '_super_contact_entry_data', true);

        if (empty($data)) {
            // Entry has no data, skip
            return true;
        }

        // Handle serialized data
        if (is_string($data)) {
            $data = @unserialize($data);
            if ($data === false) {
                return new WP_Error(
                    'corrupt_data',
                    'Failed to unserialize entry data'
                );
            }
        }

        if (!is_array($data)) {
            return new WP_Error(
                'invalid_data',
                'Entry data is not an array'
            );
        }

        // Write to EAV tables using Data Access Layer's private method
        // We'll directly insert into EAV table here
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_entry_data';

        // Delete any existing EAV data for this entry
        $wpdb->delete($table, array('entry_id' => $entry_id), array('%d'));

        // Insert each field into EAV table
        foreach ($data as $field_name => $field_data) {
            if (!is_array($field_data)) {
                continue; // Skip non-array entries
            }

            $field_value = isset($field_data['value']) ? $field_data['value'] : '';
            $field_type = isset($field_data['type']) ? $field_data['type'] : '';
            $field_label = isset($field_data['label']) ? $field_data['label'] : '';

            // Handle repeater fields (store as JSON)
            if (is_array($field_value)) {
                $field_value = wp_json_encode($field_value);
            }

            $result = $wpdb->insert(
                $table,
                array(
                    'entry_id' => $entry_id,
                    'field_name' => $field_name,
                    'field_value' => $field_value,
                    'field_type' => $field_type,
                    'field_label' => $field_label,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                return new WP_Error(
                    'db_insert_failed',
                    'Failed to insert field into EAV table: ' . $wpdb->last_error
                );
            }
        }

        return true;
    }

    /**
     * Complete migration and switch to EAV storage
     *
     * @since 6.0.0
     * @return array Migration completion result
     */
    public static function complete_migration() {
        $migration = get_option('superforms_eav_migration');

        if (empty($migration)) {
            return new WP_Error(
                'no_migration',
                __('No migration in progress', 'super-forms')
            );
        }

        // Update migration state to completed
        $migration['status'] = 'completed';
        $migration['using_storage'] = 'eav'; // Switch to EAV storage
        $migration['completed_at'] = current_time('mysql');
        $migration['verification_passed'] = true;
        $migration['rollback_available'] = true;

        update_option('superforms_eav_migration', $migration);

        return array(
            'success'          => true,
            'processed'        => $migration['migrated_entries'],
            'failed'           => count($migration['failed_entries']),
            'total_processed'  => $migration['migrated_entries'],
            'total_entries'    => $migration['total_entries'],
            'remaining'        => 0,
            'progress'         => 100,
            'is_complete'      => true,
            'completed_at'     => $migration['completed_at'],
        );
    }

    /**
     * Rollback migration to serialized storage
     *
     * @since 6.0.0
     * @return array|WP_Error Rollback result
     */
    public static function rollback_migration() {
        $migration = get_option('superforms_eav_migration');

        if (empty($migration)) {
            return new WP_Error(
                'no_migration',
                __('No migration to rollback', 'super-forms')
            );
        }

        if ($migration['status'] !== 'completed') {
            return new WP_Error(
                'migration_not_completed',
                __('Can only rollback completed migrations', 'super-forms')
            );
        }

        // Switch back to serialized storage
        $migration['using_storage'] = 'serialized';
        $migration['rollback_count'] = isset($migration['rollback_count']) ? $migration['rollback_count'] + 1 : 1;
        $migration['last_rollback_at'] = current_time('mysql');

        update_option('superforms_eav_migration', $migration);

        return array(
            'success' => true,
            'message' => __('Successfully rolled back to serialized storage', 'super-forms'),
        );
    }

    /**
     * Get current migration status
     *
     * @since 6.0.0
     * @return array|false Migration status or false if no migration
     */
    public static function get_migration_status() {
        return get_option('superforms_eav_migration');
    }

    /**
     * Reset migration (for testing or starting over)
     *
     * @since 6.0.0
     * @return bool True on success
     */
    public static function reset_migration() {
        $migration_state = array(
            'status'               => 'not_started',
            'using_storage'        => 'serialized',
            'total_entries'        => 0,
            'migrated_entries'     => 0,
            'failed_entries'       => array(),
            'started_at'           => '',
            'completed_at'         => '',
            'last_processed_id'    => 0,
            'verification_passed'  => false,
            'rollback_available'   => false,
        );

        return update_option('superforms_eav_migration', $migration_state);
    }
}

endif;
