<?php
/**
 * Admin View: Create Form V2 (React-based Form Builder)
 *
 * @package SUPER_Forms/Admin/Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="super-create-form-v2">
	<!-- SFUI Admin React Mount Point -->
	<div id="sfui-admin-root" class="sfui-admin-container" data-testid="sfui-admin-root"></div>

	<script>
		// Pass data to React app - form data loaded via REST API
		window.sfuiData = {
			currentPage: '<?php echo esc_js( sanitize_text_field( $_GET['page'] ?? '' ) ); ?>',
			formId: <?php echo absint( $form_id ); ?>,
			forms: <?php echo wp_json_encode( $forms ); ?>,
			translations: <?php echo wp_json_encode( $translations ); ?>,
			settings: <?php echo wp_json_encode( $settings ); ?>,
			ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			restNonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
			restUrl: '<?php echo esc_url( rest_url( 'super-forms/v1' ) ); ?>',
			currentUserEmail: '<?php echo esc_js( wp_get_current_user()->user_email ); ?>',
			i18n: {
				save: '<?php echo esc_js( __( 'Save', 'super-forms' ) ); ?>',
				saving: '<?php echo esc_js( __( 'Saving...', 'super-forms' ) ); ?>',
				saved: '<?php echo esc_js( __( 'Saved!', 'super-forms' ) ); ?>',
				error: '<?php echo esc_js( __( 'Error saving', 'super-forms' ) ); ?>',
				preview: '<?php echo esc_js( __( 'Preview', 'super-forms' ) ); ?>',
				publish: '<?php echo esc_js( __( 'Publish', 'super-forms' ) ); ?>',
				addElement: '<?php echo esc_js( __( 'Add Element', 'super-forms' ) ); ?>',
				deleteElement: '<?php echo esc_js( __( 'Delete Element', 'super-forms' ) ); ?>',
				duplicateElement: '<?php echo esc_js( __( 'Duplicate Element', 'super-forms' ) ); ?>',
				elementSettings: '<?php echo esc_js( __( 'Element Settings', 'super-forms' ) ); ?>',
				formSettings: '<?php echo esc_js( __( 'Form Settings', 'super-forms' ) ); ?>',
				undo: '<?php echo esc_js( __( 'Undo', 'super-forms' ) ); ?>',
				redo: '<?php echo esc_js( __( 'Redo', 'super-forms' ) ); ?>'
			}
		};
	</script>
</div>
