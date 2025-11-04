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

        error_log('[SF Migration Debug] SUPER_Migration_Manager::process_batch() ENTERED');
        error_log('[SF Migration Debug] $batch_size param: ' . var_export($batch_size, true));

        if ($batch_size === null) {
            $batch_size = self::BATCH_SIZE;
        }

        error_log('[SF Migration Debug] $batch_size after null check: ' . $batch_size);

        // Get current migration state
        $migration = get_option('superforms_eav_migration');
        error_log('[SF Migration Debug] Migration state retrieved');
        error_log('[SF Migration Debug] Migration status: ' . (isset($migration['status']) ? $migration['status'] : 'UNKNOWN'));

        if (empty($migration) || $migration['status'] !== 'in_progress') {
            error_log('[SF Migration Debug] Migration status check FAILED - returning WP_Error');
            return new WP_Error(
                'migration_not_in_progress',
                __('Migration is not in progress', 'super-forms')
            );
        }

        error_log('[SF Migration Debug] Migration status check PASSED');
        error_log('[SF Migration Debug] last_processed_id: ' . $migration['last_processed_id']);
        error_log('[SF Migration Debug] About to fetch next batch of entries');

        // Get next batch of entries
        $entries = $wpdb->get_results($wpdb->prepare("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
            AND ID > %d
            ORDER BY ID ASC
            LIMIT %d
        ", $migration['last_processed_id'], $batch_size), ARRAY_A);

        error_log('[SF Migration Debug] Query executed');
        error_log('[SF Migration Debug] Entries found: ' . count($entries));
        error_log('[SF Migration Debug] Entries: ' . print_r($entries, true));

        if (empty($entries)) {
            error_log('[SF Migration Debug] No entries found - completing migration');
            // No more entries to process, complete migration
            return self::complete_migration();
        }

        error_log('[SF Migration Debug] Starting to process ' . count($entries) . ' entries');
        $processed = 0;
        $failed = 0;
        $last_id = $migration['last_processed_id'];

        foreach ($entries as $entry) {
            $entry_id = $entry['ID'];
            $last_id = $entry_id;

            error_log('[SF Migration Debug] Processing entry ID: ' . $entry_id);

            // Migrate this entry
            $result = self::migrate_entry($entry_id);

            error_log('[SF Migration Debug] migrate_entry() returned for ID: ' . $entry_id);

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
        error_log('[SF Migration Debug] migrate_entry() ENTERED for ID: ' . $entry_id);

        // Read from serialized storage
        $data = get_post_meta($entry_id, '_super_contact_entry_data', true);

        error_log('[SF Migration Debug] get_post_meta returned, type: ' . gettype($data));
        error_log('[SF Migration Debug] Data empty check: ' . (empty($data) ? 'TRUE' : 'FALSE'));

        if (empty($data)) {
            error_log('[SF Migration Debug] Entry ' . $entry_id . ' has no data, skipping');
            // Entry has no data, skip
            return true;
        }

        error_log('[SF Migration Debug] Data is string: ' . (is_string($data) ? 'TRUE' : 'FALSE'));

        // Handle serialized data
        if (is_string($data)) {
            error_log('[SF Migration Debug] About to unserialize data for entry: ' . $entry_id);
            error_log('[SF Migration Debug] Data length: ' . strlen($data));
            error_log('[SF Migration Debug] First 100 chars: ' . substr($data, 0, 100));

            $data = @unserialize($data);

            error_log('[SF Migration Debug] Unserialize completed');

            if ($data === false) {
                error_log('[SF Migration Debug] Unserialize returned FALSE');
                return new WP_Error(
                    'corrupt_data',
                    'Failed to unserialize entry data'
                );
            }
        } else {
            error_log('[SF Migration Debug] Data is already unserialized (type: ' . gettype($data) . ')');
        }

        error_log('[SF Migration Debug] About to check if data is array');

        if (!is_array($data)) {
            error_log('[SF Migration Debug] Data is NOT array - returning error');
            return new WP_Error(
                'invalid_data',
                'Entry data is not an array'
            );
        }

        error_log('[SF Migration Debug] Data IS array - passed check');
        error_log('[SF Migration Debug] About to get global $wpdb');

        // Write to EAV tables using Data Access Layer's private method
        // We'll directly insert into EAV table here
        global $wpdb;

        error_log('[SF Migration Debug] Got $wpdb, setting table name');
        $table = $wpdb->prefix . 'superforms_entry_data';
        error_log('[SF Migration Debug] Table name: ' . $table);

        // Delete any existing EAV data for this entry
        error_log('[SF Migration Debug] About to delete existing EAV data for entry: ' . $entry_id);
        $wpdb->delete($table, array('entry_id' => $entry_id), array('%d'));
        error_log('[SF Migration Debug] Deleted existing EAV data, starting field loop');

        // Insert each field into EAV table
        foreach ($data as $field_name => $field_data) {
            error_log('[SF Migration Debug] Processing field: ' . $field_name);

            if (!is_array($field_data)) {
                error_log('[SF Migration Debug] Field ' . $field_name . ' is not array, skipping');
                continue; // Skip non-array entries
            }

            error_log('[SF Migration Debug] Extracting field data for: ' . $field_name);
            $field_value = isset($field_data['value']) ? $field_data['value'] : '';
            $field_type = isset($field_data['type']) ? $field_data['type'] : '';
            $field_label = isset($field_data['label']) ? $field_data['label'] : '';

            error_log('[SF Migration Debug] Field value type: ' . gettype($field_value));

            // Handle repeater fields (store as JSON)
            if (is_array($field_value)) {
                error_log('[SF Migration Debug] Field value is array, encoding to JSON');
                $field_value = wp_json_encode($field_value);
            }

            error_log('[SF Migration Debug] About to insert field ' . $field_name . ' into database');
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

            error_log('[SF Migration Debug] Insert result: ' . var_export($result, true));

            if ($result === false) {
                error_log('[SF Migration Debug] Insert FAILED for field: ' . $field_name . ', Error: ' . $wpdb->last_error);
                return new WP_Error(
                    'db_insert_failed',
                    'Failed to insert field into EAV table: ' . $wpdb->last_error
                );
            }

            error_log('[SF Migration Debug] Successfully inserted field: ' . $field_name);
        }

        error_log('[SF Migration Debug] Finished field loop, all fields inserted');
        error_log('[SF Migration Debug] Checking if SUPER_Data_Access class exists');

        // Verify migration was successful (compare EAV data with serialized data)
        if (class_exists('SUPER_Data_Access')) {
            error_log('[SF Migration Debug] SUPER_Data_Access exists, about to call validate_entry_integrity');
            $validation = SUPER_Data_Access::validate_entry_integrity($entry_id);
            error_log('[SF Migration Debug] validate_entry_integrity returned');

            if ($validation && isset($validation['valid']) && $validation['valid'] === true) {
                // Verification passed - serialized data can be deleted in future
                // NOTE: Keeping serialized data for now as additional safety measure
                // Uncomment when ready to enable automatic cleanup:
                // delete_post_meta($entry_id, '_super_contact_entry_data');

                // Log successful verification
                if (class_exists('SUPER_Background_Migration')) {
                    SUPER_Background_Migration::log("Entry {$entry_id} migrated and verified successfully (serialized data kept)");
                }
            } else {
                // Verification failed - keep both copies for manual review
                $error_msg = isset($validation['error']) ? $validation['error'] : 'Unknown validation error';

                // Log verification failure
                if (class_exists('SUPER_Background_Migration')) {
                    SUPER_Background_Migration::log("Entry {$entry_id} migration verification FAILED: {$error_msg}. Keeping both copies.", 'warning');
                }

                // Return error so it's tracked in failed_entries
                return new WP_Error(
                    'verification_failed',
                    "Verification failed: {$error_msg}. Entry migrated but serialized data kept as backup."
                );
            }
        } else {
            // No verification available - keep serialized data as safety measure
            if (class_exists('SUPER_Background_Migration')) {
                SUPER_Background_Migration::log("Entry {$entry_id} migrated but verification unavailable, keeping serialized data", 'warning');
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

    /**
     * Force complete migration without actually migrating data (for testing only)
     *
     * @since 6.0.0
     * @return array|WP_Error Migration state or error
     */
    public static function force_complete() {
        $migration = get_option('superforms_eav_migration', array());

        if (empty($migration)) {
            return new WP_Error('not_started', __('Migration not started', 'super-forms'));
        }

        // Count total entries
        global $wpdb;
        $total = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
            AND post_status IN ('publish', 'super_read', 'super_unread')
        ");

        $migration['status'] = 'completed';
        $migration['using_storage'] = 'eav';
        $migration['total_entries'] = $total;
        $migration['migrated_entries'] = $total;  // Pretend all migrated
        $migration['completed_at'] = current_time('mysql');

        update_option('superforms_eav_migration', $migration);

        // Log warning for debugging
        error_log('[Super Forms Developer Tools] Force completed migration without migrating data - FOR TESTING ONLY');

        return $migration;
    }

    /**
     * Force switch to EAV storage without migrating (for testing only)
     *
     * @since 6.0.0
     * @return array|WP_Error Migration state or error
     */
    public static function force_switch_eav() {
        $migration = get_option('superforms_eav_migration', array());

        if (empty($migration)) {
            return new WP_Error('not_started', __('Migration not started', 'super-forms'));
        }

        $migration['using_storage'] = 'eav';
        update_option('superforms_eav_migration', $migration);

        // Log warning for debugging
        error_log('[Super Forms Developer Tools] Force switched to EAV storage - FOR TESTING ONLY');

        return $migration;
    }
}

endif;
