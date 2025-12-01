<?php
/**
 * Automation Logs Admin Page
 *
 * Provides admin interface for viewing and managing trigger execution logs:
 * - Filterable log list using WP_List_Table
 * - Status, date range, form, and entry filtering
 * - CSV export functionality
 * - Log detail modal
 * - Statistics dashboard
 *
 * @author      WebRehab
 * @category    Admin
 * @package     SUPER_Forms/Admin
 * @class       SUPER_Automation_Logs_Page
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

if ( ! class_exists( 'SUPER_Automation_Logs_Page' ) ) :

	/**
	 * SUPER_Automation_Logs_Page Class
	 */
	class SUPER_Automation_Logs_Page {

		/**
		 * Initialize admin page
		 *
		 * @since 6.5.0
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ), 20 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_super_get_log_details', array( __CLASS__, 'ajax_get_log_details' ) );
			add_action( 'wp_ajax_super_export_trigger_logs', array( __CLASS__, 'ajax_export_logs' ) );
		}

		/**
		 * Add submenu page under Super Forms
		 *
		 * @since 6.5.0
		 */
		public static function add_menu_page() {
			add_submenu_page(
				'super_forms',
				__( 'Automation Logs', 'super-forms' ),
				__( 'Automation Logs', 'super-forms' ),
				'manage_options',
				'super-trigger-logs',
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
			if ( $hook !== 'super-forms_page_super-trigger-logs' ) {
				return;
			}

			wp_enqueue_style( 'super-trigger-logs', SUPER_PLUGIN_FILE . 'assets/css/admin/trigger-logs.css', array(), SUPER_VERSION );
			wp_enqueue_script( 'super-trigger-logs', SUPER_PLUGIN_FILE . 'assets/js/admin/trigger-logs.js', array( 'jquery' ), SUPER_VERSION, true );

			wp_localize_script( 'super-trigger-logs', 'superTriggerLogs', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'super_automation_logs' ),
				'i18n'    => array(
					'loading'    => __( 'Loading...', 'super-forms' ),
					'error'      => __( 'Error loading details', 'super-forms' ),
					'confirm'    => __( 'Are you sure?', 'super-forms' ),
					'exporting'  => __( 'Exporting...', 'super-forms' ),
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
				check_admin_referer( 'super_export_logs' );
				self::export_csv();
				return;
			}

			// Get stats for dashboard
			$logger = class_exists( 'SUPER_Automation_Logger' ) ? SUPER_Automation_Logger::instance() : null;
			$stats = $logger ? $logger->get_statistics( 7 ) : array();

			// Create list table
			$list_table = new SUPER_Automation_Logs_List_Table();
			$list_table->prepare_items();

			?>
			<div class="wrap super-trigger-logs-wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Trigger Execution Logs', 'super-forms' ); ?></h1>

				<!-- Stats Dashboard -->
				<div class="super-logs-stats">
					<div class="stat-box">
						<span class="stat-value"><?php echo esc_html( $stats['total'] ?? 0 ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total (7 days)', 'super-forms' ); ?></span>
					</div>
					<div class="stat-box stat-success">
						<span class="stat-value"><?php echo esc_html( $stats['success'] ?? 0 ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Successful', 'super-forms' ); ?></span>
					</div>
					<div class="stat-box stat-failed">
						<span class="stat-value"><?php echo esc_html( $stats['failed'] ?? 0 ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Failed', 'super-forms' ); ?></span>
					</div>
					<div class="stat-box">
						<span class="stat-value"><?php echo esc_html( round( $stats['avg_execution_ms'] ?? 0 ) ); ?>ms</span>
						<span class="stat-label"><?php esc_html_e( 'Avg. Time', 'super-forms' ); ?></span>
					</div>
				</div>

				<!-- Filters -->
				<form method="get" action="" class="super-logs-filters">
					<input type="hidden" name="page" value="super-trigger-logs">

					<select name="status">
						<option value=""><?php esc_html_e( 'All Statuses', 'super-forms' ); ?></option>
						<option value="success" <?php selected( isset( $_GET['status'] ) && $_GET['status'] === 'success' ); ?>><?php esc_html_e( 'Success', 'super-forms' ); ?></option>
						<option value="failed" <?php selected( isset( $_GET['status'] ) && $_GET['status'] === 'failed' ); ?>><?php esc_html_e( 'Failed', 'super-forms' ); ?></option>
						<option value="skipped" <?php selected( isset( $_GET['status'] ) && $_GET['status'] === 'skipped' ); ?>><?php esc_html_e( 'Skipped', 'super-forms' ); ?></option>
					</select>

					<input type="text"
						   name="date_from"
						   class="super-datepicker"
						   placeholder="<?php esc_attr_e( 'From Date', 'super-forms' ); ?>"
						   value="<?php echo isset( $_GET['date_from'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) ) : ''; ?>">

					<input type="text"
						   name="date_to"
						   class="super-datepicker"
						   placeholder="<?php esc_attr_e( 'To Date', 'super-forms' ); ?>"
						   value="<?php echo isset( $_GET['date_to'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) ) : ''; ?>">

					<input type="text"
						   name="form_id"
						   placeholder="<?php esc_attr_e( 'Form ID', 'super-forms' ); ?>"
						   value="<?php echo isset( $_GET['form_id'] ) ? esc_attr( absint( $_GET['form_id'] ) ) : ''; ?>"
						   style="width: 80px;">

					<input type="text"
						   name="entry_id"
						   placeholder="<?php esc_attr_e( 'Entry ID', 'super-forms' ); ?>"
						   value="<?php echo isset( $_GET['entry_id'] ) ? esc_attr( absint( $_GET['entry_id'] ) ) : ''; ?>"
						   style="width: 80px;">

					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'super-forms' ); ?>">

					<?php
					$export_url = wp_nonce_url(
						add_query_arg(
							array_merge(
								array( 'export' => 'csv' ),
								array_filter( array(
									'status'    => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
									'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
									'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
									'form_id'   => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '',
								) )
							),
							admin_url( 'admin.php?page=super-trigger-logs' )
						),
						'super_export_logs'
					);
					?>
					<a href="<?php echo esc_url( $export_url ); ?>" class="button">
						<?php esc_html_e( 'Export CSV', 'super-forms' ); ?>
					</a>
				</form>

				<!-- Logs Table -->
				<form method="post">
					<?php
					$list_table->display();
					?>
				</form>

				<!-- Log Details Modal -->
				<div id="super-log-details-modal" class="super-modal" style="display:none;">
					<div class="super-modal-overlay"></div>
					<div class="super-modal-content">
						<button type="button" class="super-modal-close">&times;</button>
						<h2><?php esc_html_e( 'Log Details', 'super-forms' ); ?></h2>
						<div class="super-modal-body">
							<!-- Loaded via AJAX -->
						</div>
					</div>
				</div>
			</div>

			<style>
				.super-trigger-logs-wrap { max-width: 1400px; }
				.super-logs-stats {
					display: flex;
					gap: 15px;
					margin: 20px 0;
				}
				.stat-box {
					background: #fff;
					padding: 15px 25px;
					border-radius: 4px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					text-align: center;
				}
				.stat-box .stat-value {
					display: block;
					font-size: 28px;
					font-weight: 600;
					color: #1d2327;
				}
				.stat-box .stat-label {
					color: #646970;
					font-size: 12px;
				}
				.stat-box.stat-success .stat-value { color: #00a32a; }
				.stat-box.stat-failed .stat-value { color: #d63638; }
				.super-logs-filters {
					display: flex;
					gap: 10px;
					align-items: center;
					margin: 20px 0;
					flex-wrap: wrap;
				}
				.super-logs-filters select,
				.super-logs-filters input[type="text"] {
					height: 32px;
				}
				/* Status badges */
				.status-badge {
					display: inline-block;
					padding: 3px 8px;
					border-radius: 3px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
				}
				.status-badge.status-success { background: #d6f5d6; color: #006600; }
				.status-badge.status-failed { background: #ffd6d6; color: #990000; }
				.status-badge.status-skipped { background: #fff3cd; color: #856404; }
				.status-badge.status-pending { background: #e0e0e0; color: #666; }
				/* Modal */
				.super-modal {
					position: fixed;
					top: 0;
					left: 0;
					right: 0;
					bottom: 0;
					z-index: 100000;
					display: flex;
					align-items: center;
					justify-content: center;
				}
				.super-modal-overlay {
					position: absolute;
					top: 0;
					left: 0;
					right: 0;
					bottom: 0;
					background: rgba(0,0,0,0.6);
				}
				.super-modal-content {
					position: relative;
					background: #fff;
					padding: 25px;
					border-radius: 6px;
					max-width: 800px;
					max-height: 80vh;
					overflow-y: auto;
					min-width: 500px;
				}
				.super-modal-close {
					position: absolute;
					top: 10px;
					right: 15px;
					background: none;
					border: none;
					font-size: 24px;
					cursor: pointer;
					color: #666;
				}
				.super-modal-body pre {
					background: #f0f0f1;
					padding: 10px;
					overflow-x: auto;
					font-size: 12px;
				}
				.super-modal-body .detail-row {
					display: flex;
					border-bottom: 1px solid #eee;
					padding: 8px 0;
				}
				.super-modal-body .detail-label {
					font-weight: 600;
					width: 150px;
					flex-shrink: 0;
				}
				.view-details-btn {
					cursor: pointer;
					color: #2271b1;
					text-decoration: underline;
				}
			</style>

			<script>
				jQuery(document).ready(function($) {
					// Modal handling
					$('.view-details-btn').on('click', function() {
						var logId = $(this).data('log-id');
						var $modal = $('#super-log-details-modal');
						var $body = $modal.find('.super-modal-body');

						$body.html('<p>Loading...</p>');
						$modal.show();

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'super_get_log_details',
								log_id: logId,
								nonce: '<?php echo esc_js( wp_create_nonce( 'super_automation_logs' ) ); ?>'
							},
							success: function(response) {
								if (response.success) {
									$body.html(response.data.html);
								} else {
									$body.html('<p class="error">Error loading details</p>');
								}
							},
							error: function() {
								$body.html('<p class="error">Error loading details</p>');
							}
						});
					});

					$('.super-modal-close, .super-modal-overlay').on('click', function() {
						$('#super-log-details-modal').hide();
					});

					// ESC key to close modal
					$(document).on('keydown', function(e) {
						if (e.key === 'Escape') {
							$('#super-log-details-modal').hide();
						}
					});
				});
			</script>
			<?php
		}

		/**
		 * AJAX handler for log details
		 *
		 * @since 6.5.0
		 */
		public static function ajax_get_log_details() {
			check_ajax_referer( 'super_automation_logs', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ) );
			}

			$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

			if ( ! $log_id ) {
				wp_send_json_error( array( 'message' => 'Invalid log ID' ) );
			}

			global $wpdb;
			$table = $wpdb->prefix . 'superforms_automation_logs';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$log = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$log_id
			), ARRAY_A );

			if ( ! $log ) {
				wp_send_json_error( array( 'message' => 'Log not found' ) );
			}

			// Build HTML output
			ob_start();
			?>
			<div class="log-details">
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'ID', 'super-forms' ); ?></span>
					<span class="detail-value"><?php echo esc_html( $log['id'] ); ?></span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Trigger ID', 'super-forms' ); ?></span>
					<span class="detail-value"><?php echo esc_html( $log['automation_id'] ); ?></span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Event', 'super-forms' ); ?></span>
					<span class="detail-value"><?php echo esc_html( $log['event_id'] ); ?></span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Status', 'super-forms' ); ?></span>
					<span class="detail-value">
						<span class="status-badge status-<?php echo esc_attr( $log['status'] ); ?>">
							<?php echo esc_html( $log['status'] ); ?>
						</span>
					</span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Form ID', 'super-forms' ); ?></span>
					<span class="detail-value">
						<?php if ( $log['form_id'] ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_create_form&id=' . $log['form_id'] ) ); ?>">
								#<?php echo esc_html( $log['form_id'] ); ?>
							</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Entry ID', 'super-forms' ); ?></span>
					<span class="detail-value">
						<?php if ( $log['entry_id'] ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_contact_entry&id=' . $log['entry_id'] ) ); ?>">
								#<?php echo esc_html( $log['entry_id'] ); ?>
							</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Execution Time', 'super-forms' ); ?></span>
					<span class="detail-value">
						<?php echo $log['execution_time_ms'] ? esc_html( $log['execution_time_ms'] . 'ms' ) : '—'; ?>
					</span>
				</div>
				<div class="detail-row">
					<span class="detail-label"><?php esc_html_e( 'Executed At', 'super-forms' ); ?></span>
					<span class="detail-value"><?php echo esc_html( $log['executed_at'] ); ?></span>
				</div>
				<?php if ( $log['error_message'] ) : ?>
					<div class="detail-row">
						<span class="detail-label"><?php esc_html_e( 'Error', 'super-forms' ); ?></span>
						<span class="detail-value" style="color: #d63638;"><?php echo esc_html( $log['error_message'] ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( $log['context_data'] ) : ?>
					<h4><?php esc_html_e( 'Context Data', 'super-forms' ); ?></h4>
					<pre><?php echo esc_html( wp_json_encode( json_decode( $log['context_data'] ), JSON_PRETTY_PRINT ) ); ?></pre>
				<?php endif; ?>
				<?php if ( $log['result_data'] ) : ?>
					<h4><?php esc_html_e( 'Result Data', 'super-forms' ); ?></h4>
					<pre><?php echo esc_html( wp_json_encode( json_decode( $log['result_data'] ), JSON_PRETTY_PRINT ) ); ?></pre>
				<?php endif; ?>
			</div>
			<?php
			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );
		}

		/**
		 * AJAX handler for exporting logs
		 *
		 * @since 6.5.0
		 */
		public static function ajax_export_logs() {
			check_ajax_referer( 'super_automation_logs', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Unauthorized' );
			}

			self::export_csv();
		}

		/**
		 * Export logs as CSV
		 *
		 * @since 6.5.0
		 */
		private static function export_csv() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Unauthorized' );
			}

			$args = array(
				'limit' => 10000, // Reasonable limit for CSV export
			);

			if ( isset( $_GET['status'] ) && $_GET['status'] ) {
				$args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
			}
			if ( isset( $_GET['date_from'] ) && $_GET['date_from'] ) {
				$args['date_from'] = sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) . ' 00:00:00';
			}
			if ( isset( $_GET['date_to'] ) && $_GET['date_to'] ) {
				$args['date_to'] = sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) . ' 23:59:59';
			}
			if ( isset( $_GET['form_id'] ) && $_GET['form_id'] ) {
				$args['form_id'] = absint( $_GET['form_id'] );
			}

			$logger = SUPER_Automation_Logger::instance();
			$logs = $logger->get_logs( $args );

			// Set headers for CSV download
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=trigger-logs-' . gmdate( 'Y-m-d' ) . '.csv' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$output = fopen( 'php://output', 'w' );

			// CSV headers
			fputcsv( $output, array(
				'ID',
				'Trigger ID',
				'Event',
				'Status',
				'Form ID',
				'Entry ID',
				'Execution Time (ms)',
				'Error Message',
				'Executed At',
			) );

			// Data rows
			foreach ( $logs as $log ) {
				fputcsv( $output, array(
					$log['id'],
					$log['automation_id'],
					$log['event_id'],
					$log['status'],
					$log['form_id'],
					$log['entry_id'],
					$log['execution_time_ms'],
					$log['error_message'],
					$log['executed_at'],
				) );
			}

			fclose( $output );
			exit;
		}
	}

	/**
	 * SUPER_Automation_Logs_List_Table Class
	 *
	 * Extends WP_List_Table for displaying trigger logs
	 */
	class SUPER_Automation_Logs_List_Table extends WP_List_Table {

		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct( array(
				'singular' => 'log',
				'plural'   => 'logs',
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
				'cb'             => '<input type="checkbox" />',
				'executed_at'    => __( 'Date/Time', 'super-forms' ),
				'event_id'       => __( 'Event', 'super-forms' ),
				'status'         => __( 'Status', 'super-forms' ),
				'execution_time' => __( 'Duration', 'super-forms' ),
				'form_id'        => __( 'Form', 'super-forms' ),
				'entry_id'       => __( 'Entry', 'super-forms' ),
				'details'        => __( 'Details', 'super-forms' ),
			);
		}

		/**
		 * Get sortable columns
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			return array(
				'executed_at'    => array( 'executed_at', true ),
				'status'         => array( 'status', false ),
				'execution_time' => array( 'execution_time_ms', false ),
			);
		}

		/**
		 * Prepare items for display
		 */
		public function prepare_items() {
			$per_page = 50;
			$current_page = $this->get_pagenum();

			// Build query args from filters
			$args = array(
				'limit'  => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
			);

			if ( isset( $_GET['status'] ) && $_GET['status'] ) {
				$args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
			}
			if ( isset( $_GET['date_from'] ) && $_GET['date_from'] ) {
				$args['date_from'] = sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) . ' 00:00:00';
			}
			if ( isset( $_GET['date_to'] ) && $_GET['date_to'] ) {
				$args['date_to'] = sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) . ' 23:59:59';
			}
			if ( isset( $_GET['form_id'] ) && $_GET['form_id'] ) {
				$args['form_id'] = absint( $_GET['form_id'] );
			}
			if ( isset( $_GET['entry_id'] ) && $_GET['entry_id'] ) {
				$args['entry_id'] = absint( $_GET['entry_id'] );
			}

			// Handle sorting
			if ( isset( $_GET['orderby'] ) ) {
				$args['orderby'] = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			}
			if ( isset( $_GET['order'] ) ) {
				$args['order'] = sanitize_text_field( wp_unslash( $_GET['order'] ) );
			}

			$logger = SUPER_Automation_Logger::instance();
			$this->items = $logger->get_logs( $args );

			// Get total count for pagination
			$total_items = $logger->get_logs_count( $args );

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
			return sprintf( '<input type="checkbox" name="log_ids[]" value="%s" />', esc_attr( $item['id'] ) );
		}

		/**
		 * Status column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_status( $item ) {
			return sprintf(
				'<span class="status-badge status-%s">%s</span>',
				esc_attr( $item['status'] ),
				esc_html( $item['status'] )
			);
		}

		/**
		 * Execution time column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_execution_time( $item ) {
			if ( ! $item['execution_time_ms'] ) {
				return '—';
			}
			return esc_html( $item['execution_time_ms'] . 'ms' );
		}

		/**
		 * Form ID column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_form_id( $item ) {
			if ( ! $item['form_id'] ) {
				return '—';
			}
			return sprintf(
				'<a href="%s">#%d</a>',
				esc_url( admin_url( 'admin.php?page=super_create_form&id=' . $item['form_id'] ) ),
				$item['form_id']
			);
		}

		/**
		 * Entry ID column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_entry_id( $item ) {
			if ( ! $item['entry_id'] ) {
				return '—';
			}
			return sprintf(
				'<a href="%s">#%d</a>',
				esc_url( admin_url( 'admin.php?page=super_contact_entry&id=' . $item['entry_id'] ) ),
				$item['entry_id']
			);
		}

		/**
		 * Details column
		 *
		 * @param array $item Row data
		 * @return string
		 */
		public function column_details( $item ) {
			return sprintf(
				'<span class="view-details-btn" data-log-id="%d">%s</span>',
				esc_attr( $item['id'] ),
				esc_html__( 'View', 'super-forms' )
			);
		}

		/**
		 * Message when no items found
		 */
		public function no_items() {
			esc_html_e( 'No trigger logs found.', 'super-forms' );
		}
	}

	// Initialize
	add_action( 'init', array( 'SUPER_Automation_Logs_Page', 'init' ) );

endif;
