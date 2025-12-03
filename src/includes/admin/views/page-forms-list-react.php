<?php
/**
 * Super Forms - Forms List Page (React Version)
 *
 * @package SUPER_Forms
 * @since   6.6.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'super-forms' ) );
}

// Handle bulk actions (server-side processing)
if ( isset( $_POST['action'] ) && check_admin_referer( 'bulk-forms' ) ) {
    $action = sanitize_text_field( $_POST['action'] );
    $form_ids = isset( $_POST['form_ids'] ) ? array_map( 'absint', $_POST['form_ids'] ) : array();

    if ( ! empty( $form_ids ) ) {
        $deleted = 0;
        $errors = array();

        foreach ( $form_ids as $form_id ) {
            switch ( $action ) {
                case 'delete':
                    $result = SUPER_Form_DAL::delete( $form_id );
                    if ( is_wp_error( $result ) ) {
                        $errors[] = sprintf( 'Form #%d: %s', $form_id, $result->get_error_message() );
                    } else {
                        $deleted++;
                    }
                    break;

                case 'archive':
                    $result = SUPER_Form_DAL::update( $form_id, array( 'status' => 'archived' ) );
                    if ( is_wp_error( $result ) ) {
                        $errors[] = sprintf( 'Form #%d: %s', $form_id, $result->get_error_message() );
                    } else {
                        $deleted++;
                    }
                    break;

                case 'restore':
                    $result = SUPER_Form_DAL::update( $form_id, array( 'status' => 'publish' ) );
                    if ( is_wp_error( $result ) ) {
                        $errors[] = sprintf( 'Form #%d: %s', $form_id, $result->get_error_message() );
                    } else {
                        $deleted++;
                    }
                    break;
            }
        }

        // Redirect with success message
        if ( $deleted > 0 ) {
            $message = sprintf( '%d form(s) processed successfully.', $deleted );
            wp_redirect( add_query_arg( array( 'message' => 'success' ), remove_query_arg( array( 'action', 'form_ids', '_wpnonce' ) ) ) );
            exit;
        }
    }
}

// Handle single form actions
if ( isset( $_GET['action'] ) && isset( $_GET['form_id'] ) && check_admin_referer( 'form-action' ) ) {
    $action = sanitize_text_field( $_GET['action'] );
    $form_id = absint( $_GET['form_id'] );

    switch ( $action ) {
        case 'delete':
            SUPER_Form_DAL::delete( $form_id );
            wp_redirect( remove_query_arg( array( 'action', 'form_id', '_wpnonce' ) ) );
            exit;

        case 'duplicate':
            SUPER_Form_DAL::duplicate( $form_id );
            wp_redirect( remove_query_arg( array( 'action', 'form_id', '_wpnonce' ) ) );
            exit;

        case 'archive':
            SUPER_Form_DAL::update( $form_id, array( 'status' => 'archived' ) );
            wp_redirect( remove_query_arg( array( 'action', 'form_id', '_wpnonce' ) ) );
            exit;

        case 'restore':
            SUPER_Form_DAL::update( $form_id, array( 'status' => 'publish' ) );
            wp_redirect( remove_query_arg( array( 'action', 'form_id', '_wpnonce' ) ) );
            exit;
    }
}

// Get current status filter
$current_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// Get status counts using optimized count() method
$status_counts = array(
    'all'      => SUPER_Form_DAL::count(),
    'publish'  => SUPER_Form_DAL::count( array( 'status' => 'publish' ) ),
    'draft'    => SUPER_Form_DAL::count( array( 'status' => 'draft' ) ),
    'archived' => SUPER_Form_DAL::count( array( 'status' => 'archived' ) ),
);

// Build query args
$query_args = array(
    'number'  => -1,
    'orderby' => 'updated_at',
    'order'   => 'DESC',
);

if ( $current_status !== 'all' ) {
    $query_args['status'] = $current_status;
}

// Get forms
if ( ! empty( $search_query ) ) {
    $forms = SUPER_Form_DAL::search( $search_query );
} else {
    $forms = SUPER_Form_DAL::query( $query_args );
}

// Get entry counts for all forms
$entry_counts = array();
if ( ! empty( $forms ) ) {
    global $wpdb;
    $form_ids = array_map( function( $form ) { return $form->id; }, $forms );

    // Only fetch counts if we have forms (optimization)
    if ( count( $form_ids ) < 500 ) { // Safeguard against huge queries
        $form_ids_placeholder = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
        $counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT form_id, COUNT(*) as count FROM {$wpdb->prefix}superforms_entries WHERE form_id IN ($form_ids_placeholder) GROUP BY form_id",
                ...$form_ids
            )
        );

        foreach ( $counts as $count ) {
            $entry_counts[ $count->form_id ] = (int) $count->count;
        }
    }
}

// Add entry counts to forms
foreach ( $forms as $key => $form ) {
    $forms[ $key ]->entry_count = isset( $entry_counts[ $form->id ] ) ? $entry_counts[ $form->id ] : 0;
}

// Convert forms to array for JSON
$forms_data = array_map( function( $form ) {
    return array(
        'id'          => (int) $form->id,
        'name'        => $form->name,
        'status'      => $form->status,
        'shortcode'   => $form->shortcode,
        'created_at'  => $form->created_at,
        'updated_at'  => $form->updated_at,
        'entry_count' => $form->entry_count,
    );
}, $forms );

// Prepare data for React
$react_data = array(
    'forms'         => $forms_data,
    'statusCounts'  => $status_counts,
    'currentStatus' => $current_status,
    'searchQuery'   => $search_query,
    'ajaxUrl'       => admin_url( 'admin.php?page=super_forms_list' ),
    'nonce'         => wp_create_nonce( 'bulk-forms' ),
);

// Enqueue React app
wp_enqueue_script(
    'super-forms-list',
    SUPER_PLUGIN_FILE . 'assets/js/backend/forms-list.js',
    array(),
    SUPER_VERSION,
    true
);

wp_enqueue_style(
    'super-forms-admin',
    SUPER_PLUGIN_FILE . 'assets/css/backend/admin.css',
    array(),
    SUPER_VERSION
);

// Pass data to JavaScript
?>
<div class="wrap">
    <div id="sfui-admin-root"></div>
    <script>
        window.sfuiData = <?php echo wp_json_encode( $react_data ); ?>;
    </script>
</div>
