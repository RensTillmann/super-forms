<?php
/**
 * Forms List Page
 *
 * Displays all forms with search, filter, and management actions
 *
 * @package Super_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Load DAL
if ( ! class_exists( 'SUPER_Form_DAL' ) ) {
	require_once dirname( dirname( __DIR__ ) ) . '/class-form-dal.php';
}

// Handle bulk actions
if ( isset( $_POST['action'] ) && isset( $_POST['form_ids'] ) && check_admin_referer( 'bulk-forms' ) ) {
	$action = sanitize_text_field( $_POST['action'] );
	$form_ids = array_map( 'absint', $_POST['form_ids'] );

	switch ( $action ) {
		case 'delete':
			foreach ( $form_ids as $form_id ) {
				SUPER_Form_DAL::delete( $form_id );
			}
			echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( '%d forms deleted.', 'super-forms' ), count( $form_ids ) ) . '</p></div>';
			break;
		case 'archive':
			foreach ( $form_ids as $form_id ) {
				SUPER_Form_DAL::archive( $form_id );
			}
			echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( '%d forms archived.', 'super-forms' ), count( $form_ids ) ) . '</p></div>';
			break;
		case 'restore':
			foreach ( $form_ids as $form_id ) {
				SUPER_Form_DAL::restore( $form_id );
			}
			echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( '%d forms restored.', 'super-forms' ), count( $form_ids ) ) . '</p></div>';
			break;
	}
}

// Handle single actions
if ( isset( $_GET['action'] ) && isset( $_GET['form_id'] ) && check_admin_referer( 'form-action' ) ) {
	$action = sanitize_text_field( $_GET['action'] );
	$form_id = absint( $_GET['form_id'] );

	switch ( $action ) {
		case 'delete':
			SUPER_Form_DAL::delete( $form_id );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form deleted.', 'super-forms' ) . '</p></div>';
			break;
		case 'duplicate':
			$new_id = SUPER_Form_DAL::duplicate( $form_id );
			if ( ! is_wp_error( $new_id ) ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Form duplicated.', 'super-forms' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=super_create_form&id=' . $new_id ) ) . '">' . esc_html__( 'Edit duplicate', 'super-forms' ) . '</a></p></div>';
			}
			break;
		case 'archive':
			SUPER_Form_DAL::archive( $form_id );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form archived.', 'super-forms' ) . '</p></div>';
			break;
		case 'restore':
			SUPER_Form_DAL::restore( $form_id );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Form restored.', 'super-forms' ) . '</p></div>';
			break;
	}
}

// Get filter parameters
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// Query forms
$query_args = array(
	'number' => -1,
	'orderby' => 'created_at',
	'order' => 'DESC',
);

if ( $status_filter ) {
	$query_args['status'] = $status_filter;
}

if ( $search_query ) {
	$forms = SUPER_Form_DAL::search( $search_query );
	if ( $status_filter ) {
		$forms = array_filter( $forms, function( $form ) use ( $status_filter ) {
			return $form->status === $status_filter;
		} );
	}
} else {
	$forms = SUPER_Form_DAL::query( $query_args );
}

// Get entry counts for all forms
global $wpdb;
$entry_counts = array();
if ( ! empty( $forms ) ) {
	$form_ids = array_map( function( $form ) { return $form->id; }, $forms );
	$form_ids_placeholder = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
	$counts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT form_id, COUNT(*) as count FROM {$wpdb->prefix}superforms_entries WHERE form_id IN ($form_ids_placeholder) GROUP BY form_id",
			...$form_ids
		)
	);
	foreach ( $counts as $row ) {
		$entry_counts[ $row->form_id ] = $row->count;
	}
}

// Count by status
$status_counts = array(
	'all' => count( SUPER_Form_DAL::query( array( 'number' => -1 ) ) ),
	'publish' => count( SUPER_Form_DAL::query( array( 'status' => 'publish', 'number' => -1 ) ) ),
	'draft' => count( SUPER_Form_DAL::query( array( 'status' => 'draft', 'number' => -1 ) ) ),
	'archived' => count( SUPER_Form_DAL::query( array( 'status' => 'archived', 'number' => -1 ) ) ),
);

?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Forms', 'super-forms' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_create_form' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'super-forms' ); ?></a>

	<?php if ( $search_query ) : ?>
		<span class="subtitle"><?php printf( esc_html__( 'Search results for: %s', 'super-forms' ), '<strong>' . esc_html( $search_query ) . '</strong>' ); ?></span>
	<?php endif; ?>

	<hr class="wp-header-end">

	<!-- Status filters -->
	<ul class="subsubsub">
		<li class="all">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_forms_list' ) ); ?>" <?php echo empty( $status_filter ) ? 'class="current"' : ''; ?>>
				<?php esc_html_e( 'All', 'super-forms' ); ?> <span class="count">(<?php echo absint( $status_counts['all'] ); ?>)</span>
			</a> |
		</li>
		<li class="publish">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_forms_list&status=publish' ) ); ?>" <?php echo $status_filter === 'publish' ? 'class="current"' : ''; ?>>
				<?php esc_html_e( 'Published', 'super-forms' ); ?> <span class="count">(<?php echo absint( $status_counts['publish'] ); ?>)</span>
			</a> |
		</li>
		<li class="draft">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_forms_list&status=draft' ) ); ?>" <?php echo $status_filter === 'draft' ? 'class="current"' : ''; ?>>
				<?php esc_html_e( 'Draft', 'super-forms' ); ?> <span class="count">(<?php echo absint( $status_counts['draft'] ); ?>)</span>
			</a> |
		</li>
		<li class="archived">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_forms_list&status=archived' ) ); ?>" <?php echo $status_filter === 'archived' ? 'class="current"' : ''; ?>>
				<?php esc_html_e( 'Archived', 'super-forms' ); ?> <span class="count">(<?php echo absint( $status_counts['archived'] ); ?>)</span>
			</a>
		</li>
	</ul>

	<!-- Search form -->
	<form method="get" class="search-form" style="float: right; margin-top: -40px;">
		<input type="hidden" name="page" value="super_forms_list">
		<?php if ( $status_filter ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
		<?php endif; ?>
		<p class="search-box">
			<label class="screen-reader-text" for="form-search-input"><?php esc_html_e( 'Search Forms:', 'super-forms' ); ?></label>
			<input type="search" id="form-search-input" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search forms...', 'super-forms' ); ?>">
			<input type="submit" class="button" value="<?php esc_attr_e( 'Search Forms', 'super-forms' ); ?>">
		</p>
	</form>

	<!-- Forms table -->
	<form method="post">
		<?php wp_nonce_field( 'bulk-forms' ); ?>

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'super-forms' ); ?></label>
				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk Actions', 'super-forms' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'super-forms' ); ?></option>
					<option value="archive"><?php esc_html_e( 'Archive', 'super-forms' ); ?></option>
					<?php if ( $status_filter === 'archived' ) : ?>
						<option value="restore"><?php esc_html_e( 'Restore', 'super-forms' ); ?></option>
					<?php endif; ?>
				</select>
				<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'super-forms' ); ?>">
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<input type="checkbox" id="cb-select-all-1">
					</td>
					<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Title', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Shortcode', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Entries', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'super-forms' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $forms ) ) : ?>
					<tr>
						<td colspan="6" style="text-align: center; padding: 40px;">
							<?php if ( $search_query ) : ?>
								<?php esc_html_e( 'No forms found matching your search.', 'super-forms' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'No forms found.', 'super-forms' ); ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_create_form' ) ); ?>" class="button button-primary" style="margin-left: 10px;"><?php esc_html_e( 'Create your first form', 'super-forms' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $forms as $form ) : ?>
						<?php
						$edit_url = admin_url( 'admin.php?page=super_create_form&id=' . $form->id );
						$delete_url = wp_nonce_url( admin_url( 'admin.php?page=super_forms_list&action=delete&form_id=' . $form->id ), 'form-action' );
						$duplicate_url = wp_nonce_url( admin_url( 'admin.php?page=super_forms_list&action=duplicate&form_id=' . $form->id ), 'form-action' );
						$archive_url = wp_nonce_url( admin_url( 'admin.php?page=super_forms_list&action=archive&form_id=' . $form->id ), 'form-action' );
						$restore_url = wp_nonce_url( admin_url( 'admin.php?page=super_forms_list&action=restore&form_id=' . $form->id ), 'form-action' );
						$entries_count = isset( $entry_counts[ $form->id ] ) ? $entry_counts[ $form->id ] : 0;
						?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="form_ids[]" value="<?php echo absint( $form->id ); ?>">
							</th>
							<td class="column-primary" data-colname="<?php esc_attr_e( 'Title', 'super-forms' ); ?>">
								<strong>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="row-title">
										<?php echo esc_html( $form->name ); ?>
									</a>
								</strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'super-forms' ); ?></a> |
									</span>
									<span class="duplicate">
										<a href="<?php echo esc_url( $duplicate_url ); ?>"><?php esc_html_e( 'Duplicate', 'super-forms' ); ?></a> |
									</span>
									<?php if ( $form->status === 'archived' ) : ?>
										<span class="restore">
											<a href="<?php echo esc_url( $restore_url ); ?>"><?php esc_html_e( 'Restore', 'super-forms' ); ?></a> |
										</span>
									<?php else : ?>
										<span class="archive">
											<a href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Archive', 'super-forms' ); ?></a> |
										</span>
									<?php endif; ?>
									<span class="trash">
										<a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this form?', 'super-forms' ); ?>');"><?php esc_html_e( 'Delete', 'super-forms' ); ?></a>
									</span>
									<?php if ( $entries_count > 0 ) : ?>
										| <span class="view-entries">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_contact_entries&form_id=' . $form->id ) ); ?>"><?php esc_html_e( 'View Entries', 'super-forms' ); ?></a>
										</span>
									<?php endif; ?>
								</div>
								<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'super-forms' ); ?></span></button>
							</td>
							<td data-colname="<?php esc_attr_e( 'Shortcode', 'super-forms' ); ?>">
								<code>[super_form id="<?php echo absint( $form->id ); ?>"]</code>
							</td>
							<td data-colname="<?php esc_attr_e( 'Entries', 'super-forms' ); ?>">
								<?php if ( $entries_count > 0 ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=super_contact_entries&form_id=' . $form->id ) ); ?>">
										<?php echo absint( $entries_count ); ?>
									</a>
								<?php else : ?>
									<span class="dashicons dashicons-minus" style="color: #ddd;"></span>
								<?php endif; ?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Status', 'super-forms' ); ?>">
								<?php
								$status_labels = array(
									'publish' => __( 'Published', 'super-forms' ),
									'draft' => __( 'Draft', 'super-forms' ),
									'archived' => __( 'Archived', 'super-forms' ),
								);
								echo '<span class="status-' . esc_attr( $form->status ) . '">' . esc_html( isset( $status_labels[ $form->status ] ) ? $status_labels[ $form->status ] : $form->status ) . '</span>';
								?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Date', 'super-forms' ); ?>">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $form->created_at ) ) ); ?>
								<br>
								<small><?php printf( esc_html__( 'Modified: %s', 'super-forms' ), date_i18n( get_option( 'date_format' ), strtotime( $form->updated_at ) ) ); ?></small>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column">
						<input type="checkbox" id="cb-select-all-2">
					</td>
					<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Title', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Shortcode', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Entries', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'super-forms' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'super-forms' ); ?></th>
				</tr>
			</tfoot>
		</table>
	</form>
</div>

<style>
	.status-publish { color: #46b450; }
	.status-draft { color: #f56e28; }
	.status-archived { color: #999; }
</style>

<script>
jQuery(document).ready(function($) {
	// Select all checkboxes
	$('#cb-select-all-1, #cb-select-all-2').on('click', function() {
		$('input[name="form_ids[]"]').prop('checked', this.checked);
	});

	// Sync header/footer select all
	$('input[name="form_ids[]"]').on('click', function() {
		var allChecked = $('input[name="form_ids[]"]:checked').length === $('input[name="form_ids[]"]').length;
		$('#cb-select-all-1, #cb-select-all-2').prop('checked', allChecked);
	});
});
</script>
