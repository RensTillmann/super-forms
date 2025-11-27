<?php
/**
 * Entries List Table Admin Page
 *
 * Custom WP_List_Table implementation for displaying contact entries
 * from the custom table (wp_superforms_entries). Provides:
 * - Filterable entry list with search, status, form filtering
 * - Bulk actions (delete, trash, restore, change status)
 * - Inline actions (view, edit, delete, restore)
 * - Sorting by date, form, status
 * - CSV export functionality
 *
 * @author      WebRehab
 * @category    Admin
 * @package     SUPER_Forms/Admin
 * @class       SUPER_Entries_List_Table
 * @version     1.0.0
 * @since       6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Include WP_List_Table if not already loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'SUPER_Entries_List_Table_Page' ) ) :

	/**
	 * SUPER_Entries_List_Table_Page Class
	 *
	 * Handles the admin page for custom table entries listing
	 */
	class SUPER_Entries_List_Table_Page {

		/**
		 * Initialize admin page
		 *
		 * @since 6.5.0
		 */
		public static function init() {
			// Only initialize if custom table storage is active
			if ( ! self::should_use_custom_table() ) {
				return;
			}

			add_action( 'admin_menu', array( __CLASS__, 'modify_menu' ), 100 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_super_entry_quick_edit', array( __CLASS__, 'ajax_quick_edit' ) );
			add_action( 'wp_ajax_super_entries_bulk_action', array( __CLASS__, 'ajax_bulk_action' ) );
			add_action( 'wp_ajax_super_export_entries', array( __CLASS__, 'ajax_export_entries' ) );
		}

		/**
		 * Check if custom table storage should be used
		 *
		 * @since 6.5.0
		 * @return bool
		 */
		private static function should_use_custom_table() {
			// Check if Entry DAL class exists and custom table is in use
			if ( ! class_exists( 'SUPER_Entry_DAL' ) ) {
				return false;
			}

			$storage_mode = SUPER_Entry_DAL::get_storage_mode();
			// Use custom list table when storage is 'custom_table' or 'both' (during migration)
			return in_array( $storage_mode, array( 'custom_table', 'both' ), true );
		}

		/**
		 * Modify menu to point to custom entries page
		 *
		 * @since 6.5.0
		 */
		public static function modify_menu() {
			global $submenu;

			if ( ! isset( $submenu['super_forms'] ) ) {
				return;
			}

			// Find and replace the Contact Entries menu item
			foreach ( $submenu['super_forms'] as $key => $item ) {
				if ( isset( $item[2] ) && $item[2] === 'edit.php?post_type=super_contact_entry' ) {
					$submenu['super_forms'][ $key ][2] = 'super_entries';
					break;
				}
			}

			// Add the custom entries page (hidden from menu since we replaced the link)
			add_submenu_page(
				'super_forms',
				__( 'Contact Entries', 'super-forms' ),
				__( 'Contact Entries', 'super-forms' ),
				'manage_options',
				'super_entries',
				array( __CLASS__, 'render_page' )
			);
		}

		/**
		 * Enqueue page scripts and styles
		 *
		 * @param string $hook Current admin page hook
		 * @since 6.5.0
		 */
		public static function enqueue_scripts( $hook ) {
			if ( $hook !== 'super-forms_page_super_entries' ) {
				return;
			}

			wp_enqueue_style( 'super-entries-list', SUPER_PLUGIN_FILE . 'assets/css/admin/entries-list.css', array(), SUPER_VERSION );
			wp_enqueue_script( 'super-entries-list', SUPER_PLUGIN_FILE . 'assets/js/admin/entries-list.js', array( 'jquery' ), SUPER_VERSION, true );

			wp_localize_script( 'super-entries-list', 'superEntriesList', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'super_entries_list' ),
				'i18n'    => array(
					'loading'       => __( 'Loading...', 'super-forms' ),
					'error'         => __( 'An error occurred', 'super-forms' ),
					'confirmDelete' => __( 'Are you sure you want to permanently delete this entry?', 'super-forms' ),
					'confirmBulk'   => __( 'Are you sure you want to perform this action on the selected entries?', 'super-forms' ),
					'exporting'     => __( 'Exporting...', 'super-forms' ),
					'noSelection'   => __( 'Please select at least one entry', 'super-forms' ),
				),
			) );
		}

		/**
		 * Render the admin page
		 *
		 * @since 6.5.0
		 */
		public static function render_page() {
			// Handle export request
			if ( isset( $_GET['export'] ) && $_GET['export'] === 'csv' ) {
				check_admin_referer( 'super_export_entries' );
				self::export_csv();
				return;
			}

			// Process bulk actions from form submission
			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'bulk-entries' ) ) {
				self::process_bulk_action();
			}

			// Get stats
			$stats = self::get_entry_stats();

			// Create list table
			$list_table = new SUPER_Entries_List_Table();
			$list_table->prepare_items();

			?>
			<div class="wrap super-entries-wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Contact Entries', 'super-forms' ); ?></h1>

				<?php if ( SUPER_Entry_DAL::get_storage_mode() === 'both' ) : ?>
					<div class="notice notice-info">
						<p>
							<?php esc_html_e( 'Entry migration is in progress. Both post type and custom table entries are displayed.', 'super-forms' ); ?>
						</p>
					</div>
				<?php endif; ?>

				<!-- Stats Dashboard -->
				<div class="super-entries-stats">
					<div class="stat-box">
						<span class="stat-value"><?php echo esc_html( $stats['total'] ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total Entries', 'super-forms' ); ?></span>
					</div>
					<div class="stat-box stat-unread">
						<span class="stat-value"><?php echo esc_html( $stats['unread'] ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Unread', 'super-forms' ); ?></span>
					</div>
					<div class="stat-box stat-read">
						<span class="stat-value"><?php echo esc_html( $stats['read'] ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Read', 'super-forms' ); ?></span>
					</div>
					<div class="stat-box stat-trash">
						<span class="stat-value"><?php echo esc_html( $stats['trash'] ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Trash', 'super-forms' ); ?></span>
					</div>
				</div>

				<!-- Status Filter Tabs -->
				<ul class="subsubsub">
					<?php
					$current_status = isset( $_GET['entry_status'] ) ? sanitize_text_field( wp_unslash( $_GET['entry_status'] ) ) : '';
					$base_url = admin_url( 'admin.php?page=super_entries' );

					$tabs = array(
						''           => array( 'label' => __( 'All', 'super-forms' ), 'count' => $stats['total'] ),
						'publish'    => array( 'label' => __( 'Unread', 'super-forms' ), 'count' => $stats['unread'] ),
						'super_read' => array( 'label' => __( 'Read', 'super-forms' ), 'count' => $stats['read'] ),
						'trash'      => array( 'label' => __( 'Trash', 'super-forms' ), 'count' => $stats['trash'] ),
					);

					$tab_links = array();
					foreach ( $tabs as $status => $data ) {
						$class = ( $current_status === $status ) ? ' class="current"' : '';
						$url = $status ? add_query_arg( 'entry_status', $status, $base_url ) : $base_url;
						$tab_links[] = sprintf(
							'<li><a href="%s"%s>%s <span class="count">(%s)</span></a></li>',
							esc_url( $url ),
							$class,
							esc_html( $data['label'] ),
							esc_html( $data['count'] )
						);
					}
					echo implode( ' | ', $tab_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</ul>

				<!-- Search and Filters -->
				<form method="get" action="" class="super-entries-filters">
					<input type="hidden" name="page" value="super_entries">
					<?php if ( $current_status ) : ?>
						<input type="hidden" name="entry_status" value="<?php echo esc_attr( $current_status ); ?>">
					<?php endif; ?>

					<p class="search-box">
						<label class="screen-reader-text" for="entry-search-input"><?php esc_html_e( 'Search Entries', 'super-forms' ); ?></label>
						<input type="search"
							   id="entry-search-input"
							   name="s"
							   value="<?php echo isset( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : ''; ?>"
							   placeholder="<?php esc_attr_e( 'Search entries...', 'super-forms' ); ?>">
						<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search', 'super-forms' ); ?>">
					</p>

					<?php
					// Form filter dropdown
					$forms = self::get_forms_for_filter();
					$selected_form = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
					?>
					<select name="form_id" id="filter-by-form">
						<option value=""><?php esc_html_e( 'All Forms', 'super-forms' ); ?></option>
						<?php foreach ( $forms as $form ) : ?>
							<option value="<?php echo esc_attr( $form->ID ); ?>" <?php selected( $selected_form, $form->ID ); ?>>
								<?php echo esc_html( $form->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<input type="submit" class="button action" value="<?php esc_attr_e( 'Filter', 'super-forms' ); ?>">

					<?php
					// Export button
					$export_args = array_filter( array(
						'export'       => 'csv',
						'entry_status' => $current_status,
						'form_id'      => $selected_form ? $selected_form : '',
						's'            => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
					) );
					$export_url = wp_nonce_url(
						add_query_arg( $export_args, $base_url ),
						'super_export_entries'
					);
					?>
					<a href="<?php echo esc_url( $export_url ); ?>" class="button">
						<?php esc_html_e( 'Export CSV', 'super-forms' ); ?>
					</a>
				</form>

				<!-- Entries Table -->
				<form method="post" id="super-entries-form">
					<?php
					wp_nonce_field( 'bulk-entries' );
					$list_table->display();
					?>
				</form>
			</div>

			<style>
				.super-entries-wrap { max-width: 1400px; }
				.super-entries-stats {
					display: flex;
					gap: 15px;
					margin: 20px 0;
				}
				.super-entries-stats .stat-box {
					background: #fff;
					padding: 15px 25px;
					border-radius: 4px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					text-align: center;
				}
				.super-entries-stats .stat-value {
					display: block;
					font-size: 28px;
					font-weight: 600;
					color: #1d2327;
				}
				.super-entries-stats .stat-label {
					color: #646970;
					font-size: 12px;
				}
				.super-entries-stats .stat-unread .stat-value { color: #2271b1; }
				.super-entries-stats .stat-read .stat-value { color: #00a32a; }
				.super-entries-stats .stat-trash .stat-value { color: #d63638; }
				.super-entries-filters {
					display: flex;
					gap: 10px;
					align-items: center;
					margin: 20px 0 10px;
					flex-wrap: wrap;
				}
				.super-entries-filters .search-box {
					margin: 0;
				}
				.super-entries-filters select,
				.super-entries-filters input[type="search"] {
					height: 32px;
				}
				/* Entry status badges */
				.entry-status-badge {
					display: inline-block;
					padding: 3px 8px;
					border-radius: 3px;
					font-size: 11px;
					font-weight: 600;
				}
				.entry-status-badge.status-publish { background: #e0e0e0; color: #50575e; }
				.entry-status-badge.status-super_read { background: #d6f5d6; color: #006600; }
				.entry-status-badge.status-trash { background: #ffd6d6; color: #990000; }
				/* Row actions */
				.super-entries-wrap .row-actions {
					visibility: visible;
				}
				.super-entries-wrap tr:hover .row-actions {
					visibility: visible;
				}
				.super-entries-wrap .column-title .row-title {
					font-weight: 600;
				}
				.super-entries-wrap .column-title .row-title:hover {
					color: #2271b1;
				}
			</style>
			<?php
		}

		/**
		 * Get entry statistics
		 *
		 * @since 6.5.0
		 * @return array
		 */
		private static function get_entry_stats() {
			return array(
				'total'  => SUPER_Entry_DAL::count(),
				'unread' => SUPER_Entry_DAL::count( array( 'wp_status' => 'publish' ) ),
				'read'   => SUPER_Entry_DAL::count( array( 'wp_status' => 'super_read' ) ),
				'trash'  => SUPER_Entry_DAL::count( array( 'wp_status' => 'trash' ) ),
			);
		}

		/**
		 * Get forms for filter dropdown
		 *
		 * @since 6.5.0
		 * @return array
		 */
		private static function get_forms_for_filter() {
			$forms = get_posts( array(
				'post_type'      => 'super_form',
				'posts_per_page' => 100,
				'post_status'    => array( 'publish', 'draft' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );
			return $forms ? $forms : array();
		}

		/**
		 * Process bulk actions
		 *
		 * @since 6.5.0
		 */
		private static function process_bulk_action() {
			$action = '';
			if ( isset( $_POST['action'] ) && $_POST['action'] !== '-1' ) {
				$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
			} elseif ( isset( $_POST['action2'] ) && $_POST['action2'] !== '-1' ) {
				$action = sanitize_text_field( wp_unslash( $_POST['action2'] ) );
			}

			if ( ! $action || ! isset( $_POST['entry_ids'] ) || ! is_array( $_POST['entry_ids'] ) ) {
				return;
			}

			$entry_ids = array_map( 'absint', $_POST['entry_ids'] );

			switch ( $action ) {
				case 'trash':
					foreach ( $entry_ids as $entry_id ) {
						SUPER_Entry_DAL::update( $entry_id, array( 'wp_status' => 'trash' ) );
					}
					break;

				case 'restore':
					foreach ( $entry_ids as $entry_id ) {
						SUPER_Entry_DAL::restore( $entry_id );
					}
					break;

				case 'delete':
					foreach ( $entry_ids as $entry_id ) {
						SUPER_Entry_DAL::delete( $entry_id, true ); // Force delete
					}
					break;

				case 'mark_read':
					foreach ( $entry_ids as $entry_id ) {
						SUPER_Entry_DAL::update( $entry_id, array( 'wp_status' => 'super_read' ) );
					}
					break;

				case 'mark_unread':
					foreach ( $entry_ids as $entry_id ) {
						SUPER_Entry_DAL::update( $entry_id, array( 'wp_status' => 'publish' ) );
					}
					break;
			}

			// Redirect to avoid form resubmission
			wp_safe_redirect( remove_query_arg( array( 'action', 'action2', 'entry_ids' ) ) );
			exit;
		}

		/**
		 * AJAX handler for quick edit
		 *
		 * @since 6.5.0
		 */
		public static function ajax_quick_edit() {
			check_ajax_referer( 'super_entries_list', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ) );
			}

			$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
			$field = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';
			$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

			if ( ! $entry_id || ! $field ) {
				wp_send_json_error( array( 'message' => 'Invalid request' ) );
			}

			$allowed_fields = array( 'title', 'wp_status', 'entry_status' );
			if ( ! in_array( $field, $allowed_fields, true ) ) {
				wp_send_json_error( array( 'message' => 'Field not allowed' ) );
			}

			$result = SUPER_Entry_DAL::update( $entry_id, array( $field => $value ) );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => 'Updated successfully' ) );
		}

		/**
		 * AJAX handler for bulk actions
		 *
		 * @since 6.5.0
		 */
		public static function ajax_bulk_action() {
			check_ajax_referer( 'super_entries_list', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ) );
			}

			$action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
			$entry_ids = isset( $_POST['entry_ids'] ) ? array_map( 'absint', (array) $_POST['entry_ids'] ) : array();

			if ( ! $action || empty( $entry_ids ) ) {
				wp_send_json_error( array( 'message' => 'Invalid request' ) );
			}

			$processed = 0;
			foreach ( $entry_ids as $entry_id ) {
				switch ( $action ) {
					case 'trash':
						SUPER_Entry_DAL::update( $entry_id, array( 'wp_status' => 'trash' ) );
						$processed++;
						break;
					case 'restore':
						SUPER_Entry_DAL::restore( $entry_id );
						$processed++;
						break;
					case 'delete':
						SUPER_Entry_DAL::delete( $entry_id, true );
						$processed++;
						break;
				}
			}

			wp_send_json_success( array(
				'message'   => sprintf( __( '%d entries processed', 'super-forms' ), $processed ),
				'processed' => $processed,
			) );
		}

		/**
		 * AJAX handler for export
		 *
		 * @since 6.5.0
		 */
		public static function ajax_export_entries() {
			check_ajax_referer( 'super_entries_list', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Unauthorized' );
			}

			self::export_csv();
		}

		/**
		 * Export entries as CSV
		 *
		 * @since 6.5.0
		 */
		private static function export_csv() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Unauthorized' );
			}

			$args = array(
				'limit' => 10000, // Reasonable limit
			);

			if ( isset( $_GET['entry_status'] ) && $_GET['entry_status'] ) {
				$args['wp_status'] = sanitize_text_field( wp_unslash( $_GET['entry_status'] ) );
			}
			if ( isset( $_GET['form_id'] ) && $_GET['form_id'] ) {
				$args['form_id'] = absint( $_GET['form_id'] );
			}
			if ( isset( $_GET['s'] ) && $_GET['s'] ) {
				$args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			}

			$entries = SUPER_Entry_DAL::query( $args );

			// Set headers for CSV download
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=contact-entries-' . gmdate( 'Y-m-d' ) . '.csv' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$output = fopen( 'php://output', 'w' );

			// Collect all unique field names from entries
			$all_fields = array( 'ID', 'Title', 'Form ID', 'Form Name', 'Status', 'Entry Status', 'IP Address', 'Created At' );
			$field_names = array();

			// First pass: collect all field names
			foreach ( $entries as $entry ) {
				$entry_data = SUPER_Data_Access::get_entry_data( $entry['id'] );
				if ( is_array( $entry_data ) ) {
					foreach ( $entry_data as $key => $value ) {
						if ( is_array( $value ) && isset( $value['name'] ) && ! in_array( $value['name'], $field_names, true ) ) {
							$field_names[] = $value['name'];
						}
					}
				}
			}

			// Write CSV headers
			fputcsv( $output, array_merge( $all_fields, $field_names ) );

			// Second pass: write data
			foreach ( $entries as $entry ) {
				$form_name = get_the_title( $entry['form_id'] );
				$entry_data = SUPER_Data_Access::get_entry_data( $entry['id'] );

				$row = array(
					$entry['id'],
					$entry['title'],
					$entry['form_id'],
					$form_name,
					$entry['wp_status'],
					$entry['entry_status'],
					$entry['ip_address'],
					$entry['created_at'],
				);

				// Add field values
				foreach ( $field_names as $field_name ) {
					$value = '';
					if ( is_array( $entry_data ) ) {
						foreach ( $entry_data as $field ) {
							if ( is_array( $field ) && isset( $field['name'] ) && $field['name'] === $field_name ) {
								$value = isset( $field['value'] ) ? $field['value'] : '';
								break;
							}
						}
					}
					$row[] = $value;
				}

				fputcsv( $output, $row );
			}

			fclose( $output );
			exit;
		}
	}

	/**
	 * SUPER_Entries_List_Table Class
	 *
	 * Extends WP_List_Table for displaying contact entries from custom table
	 */
	class SUPER_Entries_List_Table extends WP_List_Table {

		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct( array(
				'singular' => 'entry',
				'plural'   => 'entries',
				'ajax'     => false,
			) );
		}

		/**
		 * Get columns
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'cb'           => '<input type="checkbox" />',
				'title'        => __( 'Title', 'super-forms' ),
				'form'         => __( 'Form', 'super-forms' ),
				'wp_status'    => __( 'Status', 'super-forms' ),
				'entry_status' => __( 'Entry Status', 'super-forms' ),
				'ip_address'   => __( 'IP Address', 'super-forms' ),
				'created_at'   => __( 'Date', 'super-forms' ),
			);
		}

		/**
		 * Get sortable columns
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			return array(
				'title'      => array( 'title', false ),
				'form'       => array( 'form_id', false ),
				'wp_status'  => array( 'wp_status', false ),
				'created_at' => array( 'created_at', true ), // Default sort
			);
		}

		/**
		 * Get bulk actions
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			$current_status = isset( $_GET['entry_status'] ) ? sanitize_text_field( wp_unslash( $_GET['entry_status'] ) ) : '';

			$actions = array();

			if ( $current_status === 'trash' ) {
				$actions['restore'] = __( 'Restore', 'super-forms' );
				$actions['delete']  = __( 'Delete Permanently', 'super-forms' );
			} else {
				$actions['trash']       = __( 'Move to Trash', 'super-forms' );
				$actions['mark_read']   = __( 'Mark as Read', 'super-forms' );
				$actions['mark_unread'] = __( 'Mark as Unread', 'super-forms' );
			}

			return $actions;
		}

		/**
		 * Prepare items for display
		 */
		public function prepare_items() {
			$per_page = 20;
			$current_page = $this->get_pagenum();

			// Build query args
			$args = array(
				'limit'  => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
			);

			// Status filter
			if ( isset( $_GET['entry_status'] ) && $_GET['entry_status'] ) {
				$args['wp_status'] = sanitize_text_field( wp_unslash( $_GET['entry_status'] ) );
			}

			// Form filter
			if ( isset( $_GET['form_id'] ) && $_GET['form_id'] ) {
				$args['form_id'] = absint( $_GET['form_id'] );
			}

			// Search
			if ( isset( $_GET['s'] ) && $_GET['s'] ) {
				$args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			}

			// Sorting
			$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
			$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

			$allowed_orderby = array( 'title', 'form_id', 'wp_status', 'created_at' );
			if ( in_array( $orderby, $allowed_orderby, true ) ) {
				$args['orderby'] = $orderby;
				$args['order'] = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
			}

			// Get entries
			$this->items = SUPER_Entry_DAL::query( $args );

			// Get total count for pagination
			$count_args = array_diff_key( $args, array( 'limit' => '', 'offset' => '', 'orderby' => '', 'order' => '' ) );
			$total_items = SUPER_Entry_DAL::count( $count_args );

			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			) );

			$this->_column_headers = array(
				$this->get_columns(),
				array(), // hidden columns
				$this->get_sortable_columns(),
			);
		}

		/**
		 * Default column output
		 *
		 * @param array  $item        Row data
		 * @param string $column_name Column name
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
		}

		/**
		 * Checkbox column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_cb( $item ) {
			return sprintf( '<input type="checkbox" name="entry_ids[]" value="%s" />', esc_attr( $item['id'] ) );
		}

		/**
		 * Title column with row actions
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_title( $item ) {
			$title = ! empty( $item['title'] ) ? $item['title'] : __( '(no title)', 'super-forms' );
			$view_url = admin_url( 'admin.php?page=super_contact_entry&id=' . $item['id'] );
			$current_status = isset( $_GET['entry_status'] ) ? sanitize_text_field( wp_unslash( $_GET['entry_status'] ) ) : '';

			// Row actions
			$actions = array();

			if ( $current_status === 'trash' ) {
				$actions['restore'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( wp_nonce_url(
						add_query_arg( array( 'action' => 'restore', 'entry_ids' => array( $item['id'] ) ) ),
						'bulk-entries'
					) ),
					esc_html__( 'Restore', 'super-forms' )
				);
				$actions['delete'] = sprintf(
					'<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
					esc_url( wp_nonce_url(
						add_query_arg( array( 'action' => 'delete', 'entry_ids' => array( $item['id'] ) ) ),
						'bulk-entries'
					) ),
					esc_js( __( 'Are you sure you want to permanently delete this entry?', 'super-forms' ) ),
					esc_html__( 'Delete Permanently', 'super-forms' )
				);
			} else {
				$actions['view'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $view_url ),
					esc_html__( 'View', 'super-forms' )
				);
				$actions['trash'] = sprintf(
					'<a href="%s" class="submitdelete">%s</a>',
					esc_url( wp_nonce_url(
						add_query_arg( array( 'action' => 'trash', 'entry_ids' => array( $item['id'] ) ) ),
						'bulk-entries'
					) ),
					esc_html__( 'Trash', 'super-forms' )
				);
			}

			return sprintf(
				'<a class="row-title" href="%s">%s</a>%s',
				esc_url( $view_url ),
				esc_html( $title ),
				$this->row_actions( $actions )
			);
		}

		/**
		 * Form column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_form( $item ) {
			if ( ! $item['form_id'] ) {
				return '—';
			}

			$form_title = get_the_title( $item['form_id'] );
			if ( ! $form_title ) {
				return sprintf( '#%d', $item['form_id'] );
			}

			return sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=super_create_form&id=' . $item['form_id'] ) ),
				esc_html( $form_title )
			);
		}

		/**
		 * Status column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_wp_status( $item ) {
			$statuses = array(
				'publish'    => __( 'Unread', 'super-forms' ),
				'super_read' => __( 'Read', 'super-forms' ),
				'trash'      => __( 'Trash', 'super-forms' ),
			);

			$status = isset( $item['wp_status'] ) ? $item['wp_status'] : 'publish';
			$label = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;

			return sprintf(
				'<span class="entry-status-badge status-%s">%s</span>',
				esc_attr( $status ),
				esc_html( $label )
			);
		}

		/**
		 * Entry status column (custom status)
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_entry_status( $item ) {
			if ( empty( $item['entry_status'] ) ) {
				return '—';
			}

			// Get custom statuses from settings
			$global_settings = SUPER_Common::get_global_settings();
			$statuses = SUPER_Settings::get_entry_statuses( $global_settings );

			if ( isset( $statuses[ $item['entry_status'] ] ) ) {
				$status_data = $statuses[ $item['entry_status'] ];
				return sprintf(
					'<span class="entry-status-badge" style="background-color: %s; color: %s;">%s</span>',
					esc_attr( $status_data['color'] ?? '#e0e0e0' ),
					esc_attr( $status_data['text_color'] ?? '#000' ),
					esc_html( $status_data['name'] ?? $item['entry_status'] )
				);
			}

			return esc_html( $item['entry_status'] );
		}

		/**
		 * IP Address column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_ip_address( $item ) {
			return ! empty( $item['ip_address'] ) ? esc_html( $item['ip_address'] ) : '—';
		}

		/**
		 * Created at column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_created_at( $item ) {
			if ( empty( $item['created_at'] ) ) {
				return '—';
			}

			$timestamp = strtotime( $item['created_at'] );
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );

			return sprintf(
				'<span title="%s">%s<br><small>%s</small></span>',
				esc_attr( $item['created_at'] ),
				esc_html( date_i18n( $date_format, $timestamp ) ),
				esc_html( date_i18n( $time_format, $timestamp ) )
			);
		}

		/**
		 * Message when no items found
		 */
		public function no_items() {
			$status = isset( $_GET['entry_status'] ) ? sanitize_text_field( wp_unslash( $_GET['entry_status'] ) ) : '';

			if ( $status === 'trash' ) {
				esc_html_e( 'No entries found in Trash.', 'super-forms' );
			} else {
				esc_html_e( 'No contact entries found.', 'super-forms' );
			}
		}
	}

	// Initialize when in admin
	add_action( 'init', array( 'SUPER_Entries_List_Table_Page', 'init' ) );

endif;
