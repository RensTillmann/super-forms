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
     * @param int    $entry_id     Entry ID
     * @param array  $data         Entry data array
     * @param string $force_format Storage format: 'eav' (default), 'serialized', or null for legacy auto-detect
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function save_entry_data($entry_id, $data, $force_format = null) {
        if (empty($entry_id) || !is_numeric($entry_id)) {
            return new WP_Error('invalid_entry_id', __('Invalid entry ID', 'super-forms'));
        }

        if (!is_array($data)) {
            return new WP_Error('invalid_data', __('Entry data must be an array', 'super-forms'));
        }

        // OVERRIDE: Force specific format (used for test entry generation)
        if ($force_format === 'serialized') {
            return self::save_to_serialized($entry_id, $data);
        }
        if ($force_format === 'eav') {
            return self::save_to_eav_tables($entry_id, $data);
        }

        // AUTO-DETECT: Based on migration status
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

        // Use WordPress's maybe_unserialize() which safely handles both serialized strings
        // and already-unserialized data (WordPress auto-unserializes meta values)
        $data = maybe_unserialize($serialized);

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

            // Handle JSON-encoded fields (decode any valid JSON array)
            // This matches the write logic in save_to_eav_tables() which JSON encodes
            // complex arrays regardless of field name. We only decode arrays to avoid
            // converting simple JSON values like "123" or "true" to integers/booleans.
            if (is_string($field_value)) {
                // Try to decode if it looks like JSON
                $decoded = json_decode($field_value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $field_value = $decoded;
                }
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

        // Get form_id for this entry
        $form_id = get_post_meta($entry_id, '_super_form_id', true);
        if (empty($form_id)) {
            $form_id = 0; // Default to 0 if not found
        }

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
                    'form_id' => $form_id,
                    'field_name' => $field_name,
                    'field_value' => $field_value,
                    'field_type' => isset($field_data['type']) ? $field_data['type'] : '',
                    'field_label' => isset($field_data['label']) ? $field_data['label'] : '',
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
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

    /**
     * Bulk fetch entry data for multiple entries (optimized for list views)
     *
     * Fetches data for multiple entries in a single query to avoid N+1 problem.
     * Returns data indexed by entry_id for easy lookup.
     *
     * @since 6.0.0
     * @param array $entry_ids Array of entry IDs to fetch
     * @return array Entry data indexed by entry_id
     */
    public static function get_bulk_entry_data($entry_ids) {
        if (empty($entry_ids) || !is_array($entry_ids)) {
            return array();
        }

        // Sanitize entry IDs
        $entry_ids = array_map('absint', $entry_ids);
        $entry_ids = array_filter($entry_ids);

        if (empty($entry_ids)) {
            return array();
        }

        $migration = get_option('superforms_eav_migration');

        // Determine storage method
        $use_eav = false;
        if (!empty($migration) && $migration['status'] === 'completed') {
            $use_eav = ($migration['using_storage'] === 'eav');
        }

        $results = array();

        if ($use_eav) {
            // Fetch from EAV tables using single query
            global $wpdb;
            $table = $wpdb->prefix . 'superforms_entry_data';
            $ids_placeholder = implode(',', array_fill(0, count($entry_ids), '%d'));

            $query = "SELECT entry_id, field_name, field_value, field_type, field_label
                      FROM $table
                      WHERE entry_id IN ($ids_placeholder)
                      ORDER BY entry_id, field_name";

            $rows = $wpdb->get_results($wpdb->prepare($query, $entry_ids));

            // Group by entry_id
            foreach ($rows as $row) {
                if (!isset($results[$row->entry_id])) {
                    $results[$row->entry_id] = array();
                }

                $field_value = $row->field_value;

                // Decode JSON for repeater fields
                if (strpos($row->field_name, '[') !== false || self::looks_like_json($field_value)) {
                    $decoded = json_decode($field_value, true);
                    if ($decoded !== null) {
                        $field_value = $decoded;
                    }
                }

                $results[$row->entry_id][$row->field_name] = array(
                    'value' => $field_value,
                    'type' => $row->field_type,
                    'label' => $row->field_label,
                );
            }

            // Fallback to serialized for entries with no EAV data
            foreach ($entry_ids as $entry_id) {
                if (!isset($results[$entry_id])) {
                    $serialized = self::get_from_serialized($entry_id);
                    if (!empty($serialized)) {
                        $results[$entry_id] = $serialized;
                    }
                }
            }
        } else {
            // Fetch from serialized (pre-migration or rolled back)
            foreach ($entry_ids as $entry_id) {
                $data = self::get_from_serialized($entry_id);
                if (!empty($data)) {
                    $results[$entry_id] = $data;
                }
            }
        }

        return $results;
    }

    /**
     * Check if string looks like JSON
     *
     * @param string $value Value to check
     * @return bool True if looks like JSON
     */
    private static function looks_like_json($value) {
        if (!is_string($value)) {
            return false;
        }
        return (strpos($value, '{') === 0 || strpos($value, '[') === 0);
    }

    /**
     * Validate data integrity between EAV and serialized storage
     *
     * Compares entry data from both storage methods to ensure migration
     * was successful and no data was lost or corrupted.
     *
     * @since 6.0.0
     * @param int $entry_id Entry ID to validate
     * @return array Validation result with status and details
     */
    public static function validate_entry_integrity($entry_id) {
        if (empty($entry_id) || !is_numeric($entry_id)) {
            return array(
                'valid' => false,
                'error' => 'Invalid entry ID'
            );
        }

        // Get data from both storage methods
        $eav_data        = self::get_from_eav_tables($entry_id);
        $serialized_data = self::get_from_serialized($entry_id);

        // If both empty, entry has no data (valid but empty)
        if (empty($eav_data) && empty($serialized_data)) {
            return array(
                'valid' => true,
                'message' => 'Entry has no data in either storage'
            );
        }

        // If one is empty but not the other, data mismatch
        if (empty($eav_data) && !empty($serialized_data)) {
            return array(
                'valid' => false,
                'error' => 'Data exists in serialized but not in EAV',
                'missing_fields' => count($serialized_data)
            );
        }

        if (empty($serialized_data) && !empty($eav_data)) {
            return array(
                'valid' => false,
                'error' => 'Data exists in EAV but not in serialized',
                'extra_fields' => count($eav_data)
            );
        }

        // Compare field counts
        $eav_count = count($eav_data);
        $ser_count = count($serialized_data);

        if ($eav_count !== $ser_count) {
            return array(
                'valid' => false,
                'error' => 'Field count mismatch',
                'eav_count' => $eav_count,
                'serialized_count' => $ser_count,
                'difference' => abs($eav_count - $ser_count)
            );
        }

        // Compare field values
        $mismatches = array();
        foreach ($serialized_data as $field_name => $field_data) {
            if (!isset($eav_data[$field_name])) {
                $mismatches[] = array(
                    'field' => $field_name,
                    'issue' => 'Missing in EAV'
                );
                continue;
            }

            // Compare values (handle arrays/objects)
            $ser_value = isset($field_data['value']) ? $field_data['value'] : '';
            $eav_value = isset($eav_data[$field_name]['value']) ? $eav_data[$field_name]['value'] : '';

            // Normalize for comparison
            $ser_normalized = is_array($ser_value) ? json_encode($ser_value) : (string)$ser_value;
            $eav_normalized = is_array($eav_value) ? json_encode($eav_value) : (string)$eav_value;

            if ($ser_normalized !== $eav_normalized) {
                $mismatches[] = array(
                    'field' => $field_name,
                    'issue' => 'Value mismatch',
                    'serialized_value' => substr($ser_normalized, 0, 100),
                    'eav_value' => substr($eav_normalized, 0, 100)
                );
            }
        }

        // Check for extra fields in EAV
        foreach ($eav_data as $field_name => $field_data) {
            if (!isset($serialized_data[$field_name])) {
                $mismatches[] = array(
                    'field' => $field_name,
                    'issue' => 'Extra field in EAV'
                );
            }
        }

        if (!empty($mismatches)) {
            return array(
                'valid' => false,
                'error' => 'Data mismatches found',
                'mismatches' => $mismatches,
                'mismatch_count' => count($mismatches)
            );
        }

        return array(
            'valid' => true,
            'message' => 'Data matches perfectly',
            'field_count' => $eav_count
        );
    }

    /**
     * Bulk validate entry integrity for multiple entries
     *
     * @since 6.0.0
     * @param array $entry_ids Array of entry IDs to validate
     * @return array Validation summary with results
     */
    public static function bulk_validate_integrity($entry_ids) {
        if (empty($entry_ids) || !is_array($entry_ids)) {
            return array(
                'success' => false,
                'error' => 'Invalid entry IDs'
            );
        }

        $results = array(
            'total' => count($entry_ids),
            'valid' => 0,
            'invalid' => 0,
            'errors' => array()
        );

        foreach ($entry_ids as $entry_id) {
            $validation = self::validate_entry_integrity($entry_id);

            if ($validation['valid']) {
                $results['valid']++;
            } else {
                $results['invalid']++;
                $results['errors'][$entry_id] = $validation;
            }
        }

        return $results;
    }
}

endif;
