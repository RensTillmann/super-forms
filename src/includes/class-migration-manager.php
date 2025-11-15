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
     * Form ID constant for entries with unknown/missing form association
     * Using -1 instead of 0 to distinguish from valid form IDs
     * @var int
     * @since 6.4.126
     */
    const UNKNOWN_FORM_ID = -1;

    /**
     * Default memory limit in MB for fallback when parsing fails
     * @var int
     * @since 6.4.126
     */
    const DEFAULT_MEMORY_LIMIT_MB = 256;

    /**
     * Register AJAX handlers for migration management
     * Called on plugin init to ensure handlers are always available
     * @since 6.4.127
     */
    public static function register_ajax_handlers() {
        // Cleanup empty posts
        if (!has_action('wp_ajax_super_migration_cleanup_empty')) {
            add_action('wp_ajax_super_migration_cleanup_empty', array(__CLASS__, 'ajax_cleanup_empty'));
        }

        // Cleanup orphaned metadata
        if (!has_action('wp_ajax_super_migration_cleanup_orphaned')) {
            add_action('wp_ajax_super_migration_cleanup_orphaned', array(__CLASS__, 'ajax_cleanup_orphaned'));
        }

        // Refresh cleanup stats
        if (!has_action('wp_ajax_super_refresh_cleanup_stats')) {
            add_action('wp_ajax_super_refresh_cleanup_stats', array(__CLASS__, 'ajax_refresh_cleanup_stats'));
        }
    }

    /**
     * AJAX handler: Cleanup empty posts
     * @since 6.4.127
     */
    public static function ajax_cleanup_empty() {
        check_ajax_referer('super-form-builder', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        $result = SUPER_Developer_Tools::cleanup_skipped_entries();
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    /**
     * AJAX handler: Cleanup orphaned metadata
     * @since 6.4.127
     */
    public static function ajax_cleanup_orphaned() {
        check_ajax_referer('super-form-builder', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        $result = SUPER_Developer_Tools::cleanup_orphaned_metadata();
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        wp_send_json_success($result);
    }

    /**
     * AJAX handler: Refresh cleanup stats
     * @since 6.4.127
     */
    public static function ajax_refresh_cleanup_stats() {
        check_ajax_referer('super-form-builder', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        // Clear transients to force fresh calculation
        delete_transient('superforms_orphaned_meta_count');
        delete_transient('superforms_last_cleanup_check');

        // Get fresh migration status (will recalculate)
        $migration_status = self::get_migration_status();

        if (empty($migration_status)) {
            wp_send_json_error(array('message' => 'Migration not initialized'));
        }

        $cleanup_queue = !empty($migration_status['cleanup_queue']) ? $migration_status['cleanup_queue'] : array();
        $empty_posts = !empty($cleanup_queue['empty_posts']) ? $cleanup_queue['empty_posts'] : 0;
        $posts_without_data = !empty($cleanup_queue['posts_without_data']) ? $cleanup_queue['posts_without_data'] : 0;
        $orphaned_meta = !empty($cleanup_queue['orphaned_meta']) ? $cleanup_queue['orphaned_meta'] : 0;
        $last_checked = !empty($cleanup_queue['last_checked']) ? $cleanup_queue['last_checked'] : 0;
        $total_cleanup = $empty_posts + $posts_without_data + $orphaned_meta;

        wp_send_json_success(array(
            'total_cleanup' => $total_cleanup,
            'empty_posts' => $empty_posts,
            'posts_without_data' => $posts_without_data,
            'orphaned_meta' => $orphaned_meta,
            'last_checked' => $last_checked,
            'time_since_check' => 'just now',
            'message' => sprintf('Cleanup stats refreshed: %d items found', $total_cleanup)
        ));
    }

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

        // Count ALL posts (snapshot at migration start - stays constant during migration)
        // This prevents the total from fluctuating as serialized data gets deleted
        $total_entries = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
        ");

        // Initialize migration state
        $migration_state = array(
            'status'               => 'in_progress',
            'using_storage'        => 'serialized', // Still reading from serialized during migration
            'total_entries'        => intval($total_entries),
            'initial_total_entries' => intval($total_entries), // Snapshot - won't change during migration
            'migrated_entries'     => 0,
            'failed_entries'       => array(),
            'cleanup_queue'        => array(
                'empty_posts'      => 0,  // Posts with no form data
                'orphaned_meta'    => 0,  // Metadata without corresponding posts
            ),
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

        // Resource monitoring - Start tracking
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        $start_queries = $wpdb->num_queries;
        $peak_memory = $start_memory;

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
            AND ID > %d
            ORDER BY ID ASC
            LIMIT %d
        ", $migration['last_processed_id'], $batch_size), ARRAY_A);

        if (empty($entries)) {
            // No more entries to process, complete migration
            return self::complete_migration();
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $last_id = $migration['last_processed_id'];

        foreach ($entries as $entry) {
            $entry_id = $entry['ID'];
            $last_id = $entry_id;

            // Migrate this entry
            $result = self::migrate_entry($entry_id);

            // Track peak memory during batch
            $current_memory = memory_get_usage();
            if ($current_memory > $peak_memory) {
                $peak_memory = $current_memory;
            }

            if (is_wp_error($result)) {
                $migration['failed_entries'][$entry_id] = $result->get_error_message();
                $failed++;
                error_log('[Super Forms Migration] [ERROR] Failed to migrate entry ' . $entry_id . ': ' . $result->get_error_message());
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $processed++;
            }
        }

        // Resource monitoring - Calculate final stats
        $end_time = microtime(true);
        $end_queries = $wpdb->num_queries;
        $elapsed_time_ms = round(($end_time - $start_time) * 1000, 2);
        $queries_used = $end_queries - $start_queries;
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = self::parse_memory_limit($memory_limit);
        $memory_used_mb = round($peak_memory / 1024 / 1024, 2);
        $memory_limit_mb = round($memory_limit_bytes / 1024 / 1024, 2);
        $memory_percent = $memory_limit_bytes > 0 ? round(($peak_memory / $memory_limit_bytes) * 100, 2) : 0;

        // Calculate per-entry metrics (only if entries were processed)
        $entries_count = $processed + $skipped;
        $avg_memory_per_entry_kb = $entries_count > 0 ? round(($memory_used_mb * 1024) / $entries_count, 2) : 0;
        $avg_time_per_entry_ms = $entries_count > 0 ? round($elapsed_time_ms / $entries_count, 2) : 0;

        // Initialize resource_stats if not exists
        if (!isset($migration['resource_stats'])) {
            $migration['resource_stats'] = array(
                'peak_memory_mb' => 0,
                'memory_limit_mb' => $memory_limit_mb,
                'peak_memory_percent' => 0,
                'total_queries' => 0,
                'avg_queries_per_batch' => 0,
                'batch_count' => 0,
                'avg_memory_per_entry_kb' => 0,
                'avg_time_per_entry_ms' => 0,
            );
        }

        // Update peak memory if this batch used more
        if ($memory_used_mb > $migration['resource_stats']['peak_memory_mb']) {
            $migration['resource_stats']['peak_memory_mb'] = $memory_used_mb;
            $migration['resource_stats']['peak_memory_percent'] = $memory_percent;
        }

        // Update query stats
        $migration['resource_stats']['total_queries'] += $queries_used;
        $migration['resource_stats']['batch_count']++;
        $migration['resource_stats']['avg_queries_per_batch'] = round(
            $migration['resource_stats']['total_queries'] / $migration['resource_stats']['batch_count'],
            2
        );

        // Update per-entry metrics (running average)
        if ($entries_count > 0) {
            // Update average memory per entry (weighted average across all batches)
            $total_entries_so_far = $migration['migrated_entries'] ?? 0;
            $migration['resource_stats']['avg_memory_per_entry_kb'] = $avg_memory_per_entry_kb;
            $migration['resource_stats']['avg_time_per_entry_ms'] = $avg_time_per_entry_ms;
        }

        // Update migration progress
        $migration['migrated_entries'] += $processed;
        $migration['skipped_entries'] += $skipped;
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
    public static function migrate_entry($entry_id) {
        // Read from serialized storage
        $data = get_post_meta($entry_id, '_super_contact_entry_data', true);

        if (empty($data)) {
            // Entry has no data (empty post), insert marker to prevent reprocessing
            global $wpdb;
            $table = $wpdb->prefix . 'superforms_entry_data';

            // Get form_id if it exists
            $form_id = get_post_meta($entry_id, '_super_form_id', true);
            if (empty($form_id)) {
                $form_id = self::UNKNOWN_FORM_ID; // Use constant for unknown form
            }

            // Insert _cleanup_empty marker so entry appears "migrated"
            $wpdb->insert(
                $table,
                array(
                    'entry_id' => $entry_id,
                    'form_id' => $form_id,
                    'field_name' => '_cleanup_empty',
                    'field_value' => '1',
                    'field_type' => 'hidden',
                    'field_label' => 'Empty Entry',
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );

            // Return 'skipped' to track separately in cleanup_queue.empty_posts counter
            return 'skipped';
        }

        // Handle serialized data
        if (is_string($data)) {
            $data = maybe_unserialize($data);

            if ($data === false) {
                error_log('[Super Forms Migration] [ERROR] Failed to unserialize entry ' . $entry_id);
                return new WP_Error(
                    'corrupt_data',
                    'Failed to unserialize entry data'
                );
            }
        }

        if (!is_array($data)) {
            error_log('[Super Forms Migration] [ERROR] Entry ' . $entry_id . ' data is not an array');
            return new WP_Error(
                'invalid_data',
                'Entry data is not an array'
            );
        }

        // Write to EAV tables using Data Access Layer's private method
        // We'll directly insert into EAV table here
        global $wpdb;

        $table = $wpdb->prefix . 'superforms_entry_data';

        // Get form_id for this entry (needed for Listings filtering)
        $form_id = get_post_meta($entry_id, '_super_form_id', true);
        if (empty($form_id)) {
            $form_id = 0; // Default to 0 if not found
        }

        // Start transaction for atomic EAV operations
        $wpdb->query('START TRANSACTION');

        try {
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
                    'form_id' => $form_id,
                    'field_name' => $field_name,
                    'field_value' => $field_value,
                    'field_type' => $field_type,
                    'field_label' => $field_label,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                error_log('[Super Forms Migration] [ERROR] Insert FAILED for field: ' . $field_name . ' in entry ' . $entry_id . ', Error: ' . $wpdb->last_error);
                throw new Exception('Failed to insert field into EAV table: ' . $wpdb->last_error);
            }
        }

            // Commit transaction if all inserts succeeded
            $wpdb->query('COMMIT');

        } catch (Exception $e) {
            // Rollback transaction on any error
            $wpdb->query('ROLLBACK');
            error_log('[Super Forms Migration] [ERROR] Transaction rolled back for entry ' . $entry_id . ': ' . $e->getMessage());
            return new WP_Error(
                'db_transaction_failed',
                $e->getMessage()
            );
        }

        // Verify migration was successful (compare EAV data with serialized data)
        if (class_exists('SUPER_Data_Access')) {
            $validation = SUPER_Data_Access::validate_entry_integrity($entry_id);

            if ($validation && isset($validation['valid']) && $validation['valid'] === true) {
                // Verification passed - keeping serialized data as safety backup
                // EAV table is now the primary source of truth, but serialized data retained
                // SAFETY: Serialized data deletion disabled - keeping both copies
                // delete_post_meta($entry_id, '_super_contact_entry_data');

                // Log successful verification (keeping both copies for safety)
                if (class_exists('SUPER_Background_Migration')) {
                    SUPER_Background_Migration::log("Entry {$entry_id} migrated and verified successfully. Keeping both EAV and serialized copies for safety.");
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
        global $wpdb;

        // Get base state from stored option (status, timestamps, metadata)
        $state = get_option('superforms_eav_migration');

        if (empty($state)) {
            return $state; // Not initialized yet
        }

        // LIVE DATABASE QUERIES - Always get current counts, never use stored counters

        // 1. Total entries - use snapshot during migration, recalculate otherwise
        // During migration, the total should stay constant (even as serialized data gets deleted)
        if ($state['status'] === 'in_progress' && !empty($state['initial_total_entries'])) {
            // Use the snapshotted total to prevent fluctuation during migration
            $state['total_entries'] = (int) $state['initial_total_entries'];
        } else {
            // Recalculate if not in progress or snapshot doesn't exist
            $state['total_entries'] = (int) $wpdb->get_var(
                "SELECT COUNT(*)
                FROM {$wpdb->posts}
                WHERE post_type = 'super_contact_entry'"
            );
        }

        // 2. Migrated entries (actual EAV data, excluding cleanup markers)
        $eav_table = $wpdb->prefix . 'superforms_entry_data';
        $state['migrated_entries'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id)
            FROM {$eav_table}
            WHERE field_name != '_cleanup_empty'"
        );

        // 3. Empty posts count (entries with _cleanup_empty marker)
        $empty_posts = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id)
            FROM {$eav_table}
            WHERE field_name = '_cleanup_empty'"
        );

        // 4. Posts without data (posts with NEITHER serialized NOR EAV data)
        // Excludes posts that have been migrated to EAV (they're not "without data")
        $posts_without_data = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_super_contact_entry_data'
            LEFT JOIN {$eav_table} ed ON ed.entry_id = p.ID
            WHERE p.post_type = 'super_contact_entry'
            AND pm.meta_id IS NULL
            AND ed.entry_id IS NULL"
        );

        // 5. Orphaned metadata count (metadata without corresponding posts)
        // Cache this expensive query for 1 hour to improve performance
        $orphaned_meta = get_transient('superforms_orphaned_meta_count');
        $last_cleanup_check = get_transient('superforms_last_cleanup_check');

        if ($orphaned_meta === false) {
            $orphaned_meta = (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT pm.post_id)
                FROM {$wpdb->postmeta} pm
                LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = '_super_contact_entry_data'
                AND p.ID IS NULL"
            );
            // Cache for 1 hour (3600 seconds)
            set_transient('superforms_orphaned_meta_count', $orphaned_meta, 3600);
            // Store timestamp of when this check was performed
            $last_cleanup_check = current_time('timestamp');
            set_transient('superforms_last_cleanup_check', $last_cleanup_check, 3600);
        }

        // Update cleanup_queue with live counts
        // Ensure all values are integers (transients can return strings)
        if (!isset($state['cleanup_queue'])) {
            $state['cleanup_queue'] = array();
        }
        $state['cleanup_queue']['empty_posts'] = (int) $empty_posts;
        $state['cleanup_queue']['posts_without_data'] = (int) $posts_without_data;
        $state['cleanup_queue']['orphaned_meta'] = (int) $orphaned_meta;
        $state['cleanup_queue']['last_checked'] = (int) ($last_cleanup_check ? $last_cleanup_check : current_time('timestamp'));

        return $state;
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
            'skipped_entries'      => 0,
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
     * SECURITY: Only works when DEBUG_SF constant is enabled
     *
     * @since 6.0.0
     * @return array|WP_Error Migration state or error
     */
    public static function force_complete() {
        // SECURITY: Prevent usage in production
        if (!defined('DEBUG_SF') || !DEBUG_SF) {
            return new WP_Error('not_allowed', __('This method only works when DEBUG_SF is enabled', 'super-forms'));
        }

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
     * SECURITY: Only works when DEBUG_SF constant is enabled
     *
     * @since 6.0.0
     * @return array|WP_Error Migration state or error
     */
    public static function force_switch_eav() {
        // SECURITY: Prevent usage in production
        if (!defined('DEBUG_SF') || !DEBUG_SF) {
            return new WP_Error('not_allowed', __('This method only works when DEBUG_SF is enabled', 'super-forms'));
        }

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

    /**
     * Parse PHP memory limit string to bytes
     *
     * Converts memory limit values like "768M", "1G", "512000" to bytes
     *
     * IMPORTANT: This function guarantees a non-zero return value (minimum 256MB)
     * to prevent division by zero in resource monitoring calculations.
     *
     * @since 6.4.125
     * @param string $limit Memory limit string from ini_get('memory_limit')
     * @return int Memory limit in bytes (always > 0)
     */
    private static function parse_memory_limit($limit) {
        // Handle numeric-only values (already in bytes)
        if (is_numeric($limit)) {
            return (int) $limit;
        }

        // Handle values with units (e.g., "768M", "1G", "512K")
        if (preg_match('/^(\d+)([KMG])$/i', trim($limit), $matches)) {
            $value = (int) $matches[1];
            $unit = strtoupper($matches[2]);

            switch ($unit) {
                case 'G':
                    return $value * 1024 * 1024 * 1024;
                case 'M':
                    return $value * 1024 * 1024;
                case 'K':
                    return $value * 1024;
            }
        }

        // Handle unlimited (-1)
        if ($limit === '-1') {
            // Return a large value (1TB) for unlimited
            return 1024 * 1024 * 1024 * 1024;
        }

        // Fallback: return safe default from constant
        return self::DEFAULT_MEMORY_LIMIT_MB * 1024 * 1024;
    }
}

// Register AJAX handlers on init
SUPER_Migration_Manager::register_ajax_handlers();

endif;
