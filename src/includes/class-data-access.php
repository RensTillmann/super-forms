<?php
/**
 * Data Access Layer for Contact Entry Data
 *
 * Abstracts storage format (serialized vs EAV) providing backwards-compatible
 * API for all entry data operations during and after EAV migration.
 *
 * @package Super Forms
 * @since   6.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('SUPER_Data_Access')) :

class SUPER_Data_Access {

    /**
     * Get entry data from storage
     *
     * Automatically detects storage method based on migration state
     * and returns data in consistent array format.
     *
     * @since 6.0.0
     * @param int $entry_id WordPress post ID of contact entry
     * @return array|WP_Error Entry data array or WP_Error on failure
     */
    public static function get_entry_data($entry_id) {
        if (empty($entry_id) || !is_numeric($entry_id)) {
            return new WP_Error(
                'invalid_entry_id',
                __('Invalid entry ID provided', 'super-forms')
            );
        }

        // Check if entry exists
        $post = get_post($entry_id);
        if (!$post || $post->post_type !== 'super_contact_entry') {
            return new WP_Error(
                'entry_not_found',
                __('Contact entry not found', 'super-forms')
            );
        }

        $migration = get_option('superforms_eav_migration');

        // Determine storage method
        $use_eav = false;
        if (!empty($migration) && $migration['status'] === 'completed') {
            $use_eav = ($migration['using_storage'] === 'eav');
        }

        // Read from appropriate storage
        if ($use_eav) {
            $data = self::get_from_eav_tables($entry_id);

            // Fallback to serialized if EAV empty (safety)
            if (empty($data)) {
                $data = self::get_from_serialized($entry_id);
            }
        } else {
            $data = self::get_from_serialized($entry_id);
        }

        return $data;
    }

    /**
     * Save entry data to storage
     *
     * Implements phase-based write strategy:
     * - Before migration: Serialized only
     * - During migration: BOTH (dual-write)
     * - After migration: EAV only (optimized)
     * - Rolled back: Serialized only
     *
     * @since 6.0.0
     * @param int   $entry_id Entry ID
     * @param array $data     Entry data array
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function save_entry_data($entry_id, $data) {
        if (empty($entry_id) || !is_numeric($entry_id)) {
            return new WP_Error('invalid_entry_id', __('Invalid entry ID', 'super-forms'));
        }

        if (!is_array($data)) {
            return new WP_Error('invalid_data', __('Entry data must be an array', 'super-forms'));
        }

        $migration = get_option('superforms_eav_migration');

        // PHASE 1: Before migration starts
        if (empty($migration) || $migration['status'] === 'not_started') {
            return self::save_to_serialized($entry_id, $data);
        }

        // PHASE 2: During migration (dual-write for safety)
        if ($migration['status'] === 'in_progress') {
            $eav_result = self::save_to_eav_tables($entry_id, $data);
            $serialized_result = self::save_to_serialized($entry_id, $data);

            return ($eav_result && $serialized_result);
        }

        // PHASE 3: After migration (rolled back)
        if ($migration['using_storage'] === 'serialized') {
            return self::save_to_serialized($entry_id, $data);
        }

        // PHASE 4: After migration complete (EAV only - optimized)
        return self::save_to_eav_tables($entry_id, $data);
    }

    /**
     * Update single field value
     *
     * More efficient than loading entire entry, modifying, and re-saving.
     *
     * @since 6.0.0
     * @param int    $entry_id    Entry ID
     * @param string $field_name  Field name to update
     * @param mixed  $field_value New value
     * @return bool|WP_Error
     */
    public static function update_entry_field($entry_id, $field_name, $field_value) {
        $data = self::get_entry_data($entry_id);

        if (is_wp_error($data)) {
            return $data;
        }

        // Update field value
        if (isset($data[$field_name])) {
            $data[$field_name]['value'] = $field_value;
        } else {
            $data[$field_name] = array(
                'name' => $field_name,
                'value' => $field_value,
            );
        }

        return self::save_entry_data($entry_id, $data);
    }

    /**
     * Delete entry data from storage
     *
     * Removes data from BOTH storage methods (EAV and serialized).
     * Called when entry is being permanently deleted.
     *
     * @since 6.0.0
     * @param int $entry_id Entry ID
     * @return bool True on success
     */
    public static function delete_entry_data($entry_id) {
        global $wpdb;

        // Delete from EAV table
        $wpdb->delete(
            $wpdb->prefix . 'superforms_entry_data',
            array('entry_id' => $entry_id),
            array('%d')
        );

        // Delete serialized meta
        delete_post_meta($entry_id, '_super_contact_entry_data');

        return true;
    }

    /**
     * Get entry data from serialized storage
     *
     * @since 6.0.0
     * @param int $entry_id Entry ID
     * @return array Entry data array
     */
    private static function get_from_serialized($entry_id) {
        $serialized = get_post_meta($entry_id, '_super_contact_entry_data', true);

        if (empty($serialized)) {
            return array();
        }

        $data = @unserialize($serialized);

        // Handle corrupt serialized data
        if ($data === false && $serialized !== 'b:0;') {
            error_log('[Super Forms] Corrupt serialized data for entry ' . $entry_id);
            return array();
        }

        return is_array($data) ? $data : array();
    }

    /**
     * Save entry data to serialized storage
     *
     * @since 6.0.0
     * @param int   $entry_id Entry ID
     * @param array $data     Entry data
     * @return bool True on success
     */
    private static function save_to_serialized($entry_id, $data) {
        $serialized = serialize($data);
        update_post_meta($entry_id, '_super_contact_entry_data', $serialized);
        return true;
    }

    /**
     * Get entry data from EAV tables
     *
     * Reconstructs array structure from EAV rows, including nested
     * repeater fields.
     *
     * @since 6.0.0
     * @param int $entry_id Entry ID
     * @return array Entry data array
     */
    private static function get_from_eav_tables($entry_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'superforms_entry_data';

        // Get all fields for this entry
        $rows = $wpdb->get_results($wpdb->prepare("
            SELECT field_name, field_value, field_type, field_label
            FROM $table
            WHERE entry_id = %d
            ORDER BY id ASC
        ", $entry_id), ARRAY_A);

        if (empty($rows)) {
            return array();
        }

        $data = array();

        foreach ($rows as $row) {
            $field_name = $row['field_name'];
            $field_value = $row['field_value'];

            // Handle repeater fields (stored as JSON)
            if (self::is_repeater_field($field_name)) {
                $field_value = json_decode($field_value, true);
            }

            $data[$field_name] = array(
                'name' => $field_name,
                'value' => $field_value,
                'type' => $row['field_type'],
                'label' => $row['field_label'],
            );
        }

        return $data;
    }

    /**
     * Save entry data to EAV tables
     *
     * Deletes existing rows and inserts fresh data.
     * Handles repeater fields by storing as JSON.
     *
     * @since 6.0.0
     * @param int   $entry_id Entry ID
     * @param array $data     Entry data
     * @return bool True on success
     */
    private static function save_to_eav_tables($entry_id, $data) {
        global $wpdb;

        $table = $wpdb->prefix . 'superforms_entry_data';

        // Delete existing data for this entry
        $wpdb->delete($table, array('entry_id' => $entry_id), array('%d'));

        // Insert new data
        foreach ($data as $field_name => $field_data) {
            $field_value = $field_data['value'];

            // Handle repeater fields (store as JSON)
            if (is_array($field_value) && self::is_repeater_field_value($field_value)) {
                $field_value = wp_json_encode($field_value);
            }

            $wpdb->insert(
                $table,
                array(
                    'entry_id' => $entry_id,
                    'field_name' => $field_name,
                    'field_value' => $field_value,
                    'field_type' => isset($field_data['type']) ? $field_data['type'] : '',
                    'field_label' => isset($field_data['label']) ? $field_data['label'] : '',
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
        }

        return true;
    }

    /**
     * Check if field name indicates repeater field
     *
     * @param string $field_name Field name
     * @return bool True if repeater field
     */
    private static function is_repeater_field($field_name) {
        // Repeater fields contain brackets: customer[0][name]
        return (strpos($field_name, '[') !== false);
    }

    /**
     * Check if field value is repeater field array
     *
     * @param mixed $value Field value
     * @return bool True if repeater value
     */
    private static function is_repeater_field_value($value) {
        if (!is_array($value)) {
            return false;
        }

        // Check if array contains nested field arrays
        foreach ($value as $item) {
            if (is_array($item) && isset($item['value'])) {
                return true;
            }
        }

        return false;
    }
}

endif;
