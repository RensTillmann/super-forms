<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Form_DAL' ) ) :

/**
 * SUPER_Form_DAL Class
 *
 * Handles all data access for the wp_superforms_forms table.
 *
 * @since 6.6.0
 */
class SUPER_Form_DAL {

    /**
     * The table name for forms.
     *
     * @var string
     */
    private static $table_name = 'superforms_forms';

    /**
     * Get the full table name with WordPress prefix.
     *
     * @return string
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::$table_name;
    }

    /**
     * Retrieve a single form from the database.
     *
     * @param int $form_id The ID of the form to retrieve.
     * @return object|null The form object, or null if not found.
     */
    public static function get( $form_id ) {
        global $wpdb;
        $table = self::get_table_name();

        $form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $form_id ) );

        if ( ! $form ) {
            return null;
        }

        // Decode JSON columns
        $form->elements = json_decode( $form->elements, true );
        $form->settings = json_decode( $form->settings, true );
        $form->translations = json_decode( $form->translations, true );

        return $form;
    }

    /**
     * Create a new form.
     *
     * @param array $data The data for the new form.
     * @return int|WP_Error The new form ID, or WP_Error on failure.
     */
    public static function create( $data ) {
        global $wpdb;
        $table = self::get_table_name();

        $defaults = array(
            'name' => '',
            'status' => 'publish',
            'elements' => array(),
            'settings' => array(),
            'translations' => array(),
        );
        $data = wp_parse_args( $data, $defaults );

        $now = current_time( 'mysql' );

        $result = $wpdb->insert(
            $table,
            array(
                'name' => $data['name'],
                'status' => $data['status'],
                'elements' => wp_json_encode( $data['elements'] ),
                'settings' => wp_json_encode( $data['settings'] ),
                'translations' => wp_json_encode( $data['translations'] ),
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array(
                '%s', // name
                '%s', // status
                '%s', // elements
                '%s', // settings
                '%s', // translations
                '%s', // created_at
                '%s', // updated_at
            )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_insert_error', __( 'Could not insert form into the database.', 'super-forms' ), $wpdb->last_error );
        }

        return $wpdb->insert_id;
    }

    /**
     * Update an existing form.
     *
     * @param int $form_id The ID of the form to update.
     * @param array $data The new data for the form.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function update( $form_id, $data ) {
        global $wpdb;
        $table = self::get_table_name();

        $update_data = array();
        $update_formats = array();

        if ( isset( $data['name'] ) ) {
            $update_data['name'] = $data['name'];
            $update_formats[] = '%s';
        }
        if ( isset( $data['status'] ) ) {
            $update_data['status'] = $data['status'];
            $update_formats[] = '%s';
        }
        if ( isset( $data['elements'] ) ) {
            $update_data['elements'] = wp_json_encode( $data['elements'] );
            $update_formats[] = '%s';
        }
        if ( isset( $data['settings'] ) ) {
            $update_data['settings'] = wp_json_encode( $data['settings'] );
            $update_formats[] = '%s';
        }
        if ( isset( $data['translations'] ) ) {
            $update_data['translations'] = wp_json_encode( $data['translations'] );
            $update_formats[] = '%s';
        }

        if ( empty( $update_data ) ) {
            return true; // Nothing to update
        }

        // Always update the updated_at timestamp
        $update_data['updated_at'] = current_time( 'mysql' );
        $update_formats[] = '%s';

        $result = $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $form_id ),
            $update_formats,
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_update_error', __( 'Could not update form in the database.', 'super-forms' ), $wpdb->last_error );
        }

        return true;
    }

    /**
     * Delete a form.
     *
     * @param int $form_id The ID of the form to delete.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete( $form_id ) {
        global $wpdb;
        $table = self::get_table_name();

        $result = $wpdb->delete( $table, array( 'id' => $form_id ), array( '%d' ) );

        if ( false === $result ) {
            return new WP_Error( 'db_delete_error', __( 'Could not delete form from the database.', 'super-forms' ), $wpdb->last_error );
        }

        return true;
    }

    /**
     * Query forms.
     *
     * @param array $args Query arguments.
     * @return array The found forms.
     */
    public static function query( $args = array() ) {
        global $wpdb;
        $table = self::get_table_name();

        $defaults = array(
            'status' => 'publish',
            'number' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        $where = '';
        if ( ! empty( $args['status'] ) ) {
            $where = $wpdb->prepare( " WHERE status = %s", $args['status'] );
        }

        $orderby = in_array( $args['orderby'], array( 'id', 'name', 'created_at', 'updated_at' ) ) ? $args['orderby'] : 'id';
        $order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $args['order'] ) : 'DESC';

        // Build query - handle -1 for unlimited results
        if ( $args['number'] == -1 ) {
            $sql = "SELECT * FROM {$table}{$where} ORDER BY {$orderby} {$order}";
            $results = $wpdb->get_results( $sql );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table}{$where} ORDER BY {$orderby} {$order} LIMIT %d, %d",
                    $args['offset'],
                    $args['number']
                )
            );
        }

        foreach ( $results as $key => $form ) {
            $results[ $key ]->elements = json_decode( $form->elements, true );
            $results[ $key ]->settings = json_decode( $form->settings, true );
            $results[ $key ]->translations = json_decode( $form->translations, true );
        }

        return $results;
    }

    /**
     * Count forms matching the query.
     *
     * @since 6.6.1
     * @param array $args Query arguments (only 'status' is used).
     * @return int The number of forms matching the query.
     */
    public static function count( $args = array() ) {
        global $wpdb;
        $table = self::get_table_name();

        $where = '';
        if ( ! empty( $args['status'] ) ) {
            $where = $wpdb->prepare( " WHERE status = %s", $args['status'] );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}{$where}" );
    }

    /**
     * Duplicate a form
     *
     * @param int $form_id The ID of the form to duplicate.
     * @return int|WP_Error The new form ID, or WP_Error on failure.
     */
    public static function duplicate( $form_id ) {
        $original_form = self::get( $form_id );

        if ( ! $original_form ) {
            return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ) );
        }

        // Generate new name with "(Copy)" suffix
        $new_name = $original_form->name . ' (Copy)';

        // Check if name already exists with copy suffix, increment if needed
        global $wpdb;
        $table = self::get_table_name();
        $base_name = $new_name;
        $counter = 1;

        while ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE name = %s", $new_name ) ) > 0 ) {
            $counter++;
            $new_name = $base_name . ' ' . $counter;
        }

        // Create new form with duplicated data
        $new_form_data = array(
            'name' => $new_name,
            'status' => 'draft', // Duplicates start as draft
            'elements' => $original_form->elements,
            'settings' => $original_form->settings,
            'translations' => $original_form->translations,
        );

        $new_form_id = self::create( $new_form_data );

        if ( is_wp_error( $new_form_id ) ) {
            return $new_form_id;
        }

        // Allow add-ons to duplicate their settings
        do_action( 'super_form_duplicated', $new_form_id, $form_id );

        return $new_form_id;
    }

    /**
     * Search forms by name or settings
     *
     * @param string $query Search query string.
     * @param array $args Additional query arguments.
     * @return array Array of matching forms.
     */
    public static function search( $query, $args = array() ) {
        global $wpdb;
        $table = self::get_table_name();

        if ( empty( $query ) ) {
            return self::query( $args );
        }

        $defaults = array(
            'status' => '',
            'number' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        $where_clauses = array();
        $search_term = '%' . $wpdb->esc_like( $query ) . '%';

        // Search in name
        $where_clauses[] = $wpdb->prepare( 'name LIKE %s', $search_term );

        // Search in settings JSON
        $where_clauses[] = $wpdb->prepare( 'settings LIKE %s', $search_term );

        $where = '(' . implode( ' OR ', $where_clauses ) . ')';

        // Add status filter if specified
        if ( ! empty( $args['status'] ) ) {
            $where .= $wpdb->prepare( ' AND status = %s', $args['status'] );
        }

        $orderby = in_array( $args['orderby'], array( 'id', 'name', 'created_at', 'updated_at' ) ) ? $args['orderby'] : 'id';
        $order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $args['order'] ) : 'DESC';

        // Build query - handle -1 for unlimited results
        if ( $args['number'] == -1 ) {
            $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order}";
            $results = $wpdb->get_results( $sql );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d, %d",
                    $args['offset'],
                    $args['number']
                )
            );
        }

        foreach ( $results as $key => $form ) {
            $results[ $key ]->elements = json_decode( $form->elements, true );
            $results[ $key ]->settings = json_decode( $form->settings, true );
            $results[ $key ]->translations = json_decode( $form->translations, true );
        }

        return $results;
    }

    /**
     * Archive a form (soft delete)
     *
     * @param int $form_id The ID of the form to archive.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function archive( $form_id ) {
        $result = self::update( $form_id, array( 'status' => 'archived' ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        do_action( 'super_form_archived', $form_id );

        return true;
    }

    /**
     * Restore an archived form
     *
     * @param int $form_id The ID of the form to restore.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function restore( $form_id ) {
        $result = self::update( $form_id, array( 'status' => 'publish' ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        do_action( 'super_form_restored', $form_id );

        return true;
    }

    // =========================================================================
    // Version Management (Phase 27 - Operations-Based Versioning)
    // =========================================================================

    /**
     * Get the versions table name
     *
     * @return string
     */
    private static function get_versions_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'superforms_form_versions';
    }

    /**
     * Create a new version snapshot
     *
     * @param int $form_id Form ID
     * @param array $snapshot Full form state
     * @param array $operations Operations applied since last version (optional)
     * @param string $message Commit message (optional)
     * @param int $user_id User creating the version (defaults to current user)
     * @return int|WP_Error Version ID or error
     */
    public static function create_version( $form_id, $snapshot, $operations = array(), $message = '', $user_id = null ) {
        global $wpdb;
        $table = self::get_versions_table_name();

        if ( $user_id === null ) {
            $user_id = get_current_user_id();
        }

        // Get next version number
        $version_number = self::get_next_version_number( $form_id );

        $result = $wpdb->insert(
            $table,
            array(
                'form_id' => $form_id,
                'version_number' => $version_number,
                'snapshot' => wp_json_encode( $snapshot ),
                'operations' => ! empty( $operations ) ? wp_json_encode( $operations ) : null,
                'created_by' => $user_id,
                'created_at' => current_time( 'mysql' ),
                'message' => $message,
            ),
            array(
                '%d', // form_id
                '%d', // version_number
                '%s', // snapshot
                '%s', // operations
                '%d', // created_by
                '%s', // created_at
                '%s', // message
            )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_insert_error', __( 'Could not create version.', 'super-forms' ), $wpdb->last_error );
        }

        // Auto-cleanup old versions
        self::cleanup_old_versions( $form_id );

        return $wpdb->insert_id;
    }

    /**
     * Get next version number for a form
     *
     * @param int $form_id Form ID
     * @return int Next version number (starts at 1)
     */
    private static function get_next_version_number( $form_id ) {
        global $wpdb;
        $table = self::get_versions_table_name();

        $max_version = $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(version_number) FROM {$table} WHERE form_id = %d",
            $form_id
        ) );

        return $max_version ? ( (int) $max_version + 1 ) : 1;
    }

    /**
     * Get all versions for a form
     *
     * @param int $form_id Form ID
     * @param int $limit Maximum versions to return (default 20)
     * @return array Array of version objects
     */
    public static function get_versions( $form_id, $limit = 20 ) {
        global $wpdb;
        $table = self::get_versions_table_name();

        $versions = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE form_id = %d ORDER BY version_number DESC LIMIT %d",
            $form_id,
            $limit
        ) );

        // Decode JSON columns
        foreach ( $versions as $version ) {
            $version->snapshot = json_decode( $version->snapshot, true );
            if ( $version->operations ) {
                $version->operations = json_decode( $version->operations, true );
            }
        }

        return $versions;
    }

    /**
     * Get a specific version
     *
     * @param int $version_id Version ID
     * @return object|null Version object or null
     */
    public static function get_version( $version_id ) {
        global $wpdb;
        $table = self::get_versions_table_name();

        $version = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $version_id
        ) );

        if ( ! $version ) {
            return null;
        }

        // Decode JSON columns
        $version->snapshot = json_decode( $version->snapshot, true );
        if ( $version->operations ) {
            $version->operations = json_decode( $version->operations, true );
        }

        return $version;
    }

    /**
     * Get a specific version by form ID and version number
     *
     * @param int $form_id Form ID
     * @param int $version_number Version number
     * @return object|null Version object or null
     */
    public static function get_version_by_number( $form_id, $version_number ) {
        global $wpdb;
        $table = self::get_versions_table_name();

        $version = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE form_id = %d AND version_number = %d",
            $form_id,
            $version_number
        ) );

        if ( ! $version ) {
            return null;
        }

        // Decode JSON columns
        $version->snapshot = json_decode( $version->snapshot, true );
        if ( $version->operations ) {
            $version->operations = json_decode( $version->operations, true );
        }

        return $version;
    }

    /**
     * Cleanup old versions keeping only the latest N versions
     *
     * @param int $form_id Form ID
     * @param int $keep_count Number of versions to keep (default 20)
     * @return int Number of versions deleted
     */
    public static function cleanup_old_versions( $form_id, $keep_count = 20 ) {
        global $wpdb;
        $table = self::get_versions_table_name();

        // Get IDs of versions to keep
        $keep_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE form_id = %d ORDER BY version_number DESC LIMIT %d",
            $form_id,
            $keep_count
        ) );

        if ( empty( $keep_ids ) ) {
            return 0; // No versions to delete
        }

        // Delete versions not in keep list
        $placeholders = implode( ',', array_fill( 0, count( $keep_ids ), '%d' ) );
        $query = $wpdb->prepare(
            "DELETE FROM {$table} WHERE form_id = %d AND id NOT IN ({$placeholders})",
            array_merge( array( $form_id ), $keep_ids )
        );

        $result = $wpdb->query( $query );

        return $result === false ? 0 : (int) $result;
    }

    /**
     * Apply operations to form data using SUPER_Form_Operations
     *
     * @param int $form_id Form ID
     * @param array $operations Array of JSON Patch operations
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function apply_operations( $form_id, $operations ) {
        // Load operations handler
        if ( ! class_exists( 'SUPER_Form_Operations' ) ) {
            require_once SUPER_PLUGIN_DIR . '/includes/class-form-operations.php';
        }

        // Validate operations first
        try {
            SUPER_Form_Operations::validate_operations( $operations );
        } catch ( Exception $e ) {
            return new WP_Error( 'invalid_operations', $e->getMessage() );
        }

        // Get current form data
        $form = self::get( $form_id );
        if ( ! $form ) {
            return new WP_Error( 'form_not_found', __( 'Form not found.', 'super-forms' ) );
        }

        // Build complete form data structure
        $form_data = array(
            'elements' => $form->elements ?? array(),
            'settings' => $form->settings ?? array(),
            'translations' => $form->translations ?? array(),
        );

        // Apply operations
        try {
            $modified_data = SUPER_Form_Operations::apply_operations( $form_data, $operations );
        } catch ( Exception $e ) {
            return new WP_Error( 'operation_failed', $e->getMessage() );
        }

        // Update form with modified data
        $update_result = self::update( $form_id, $modified_data );

        if ( is_wp_error( $update_result ) ) {
            return $update_result;
        }

        return true;
    }

    /**
     * Revert form to a specific version
     *
     * @param int $form_id Form ID
     * @param int $version_id Version ID to revert to
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function revert_to_version( $form_id, $version_id ) {
        $version = self::get_version( $version_id );

        if ( ! $version ) {
            return new WP_Error( 'version_not_found', __( 'Version not found.', 'super-forms' ) );
        }

        if ( (int) $version->form_id !== (int) $form_id ) {
            return new WP_Error( 'version_mismatch', __( 'Version does not belong to this form.', 'super-forms' ) );
        }

        // Update form with snapshot data
        $update_result = self::update( $form_id, $version->snapshot );

        if ( is_wp_error( $update_result ) ) {
            return $update_result;
        }

        // Create a new version to mark the revert action
        self::create_version(
            $form_id,
            $version->snapshot,
            array(),
            sprintf( __( 'Reverted to version %d', 'super-forms' ), $version->version_number )
        );

        return true;
    }
}

endif;
