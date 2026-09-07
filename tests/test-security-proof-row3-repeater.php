<?php
/**
 * A36/A37 public-boundary proof for matrix rows 3 and 12.
 *
 * Row 3 covers repeater submission integrity for duplicate rows, file receipts,
 * stored entry data, and closed-failure handling.
 *
 * Row 12 covers server-owned rebuilding of repeated selection presentation and
 * rejection of client-declared presentation variants.
 *
 * @package Super_Forms_Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';


class Test_Super_Forms_Proof_Row3_Repeater_Security extends Super_Forms_Upload_Security_Test_Case {

	private function repeater_elements() {
		return array(
			array(
				'tag' => 'column',
				'data' => array( 'duplicate' => 'enabled' ),
				'inner' => array(
					array(
						'tag' => 'text',
						'data' => array( 'name' => 'guest_name', 'validation' => 'none' ),
					),
					$this->file_element( 'guest_document' ),
				),
			),
		);
	}

	private function repeater_form( $extra_settings=array() ) {
		return $this->create_form( 'publish', $this->repeater_elements(), array_merge( array(
			'save_contact_entry' => 'yes',
			'send' => 'no',
			'confirm' => 'no',
			'form_thanks_title' => '',
			'form_thanks_description' => '',
			'form_show_thanks_msg' => '',
			'form_redirect_option' => '',
		), $extra_settings ) );
	}

	/**
	 * Issue one owned upload receipt per repeater row.
	 */
	private function issue_row_receipts( $form_id ) {
		$row1_owned = $this->create_owned_upload( $form_id, 'guest_document', false, 'row one passport bytes' );
		$row2_owned = $this->create_owned_upload( $form_id, 'guest_document', false, 'row two passport bytes' );
		return array(
			'row1' => array( 'owned' => $row1_owned, 'token' => $this->issue_receipt( $row1_owned['owned'] ) ),
			'row2' => array( 'owned' => $row2_owned, 'token' => $this->issue_receipt( $row2_owned['owned'] ) ),
		);
	}

	/**
	 * Build the repeater submission fixture used by the row 3 assertions.
	 */
	private function production_repeater_data( $row1_token, $row2_token ) {
		$row0_name = array(
			'name' => 'guest_name',
			'value' => 'Ada',
			'label' => 'Guest name',
			'exclude' => 'false',
			'replace_commas' => 'false',
			'exclude_entry' => 'false',
			'excludeconditional' => 'false',
			'type' => 'var',
		);
		$row0_document = array(
			'label' => 'Guest document',
			'type' => 'files',
			'exclude' => 'false',
			'exclude_entry' => 'false',
			'files' => array( array( 'upload_token' => $row1_token ) ),
		);
		$row1_name = array(
			'name' => 'guest_name_2',
			'value' => 'Grace',
			'label' => 'Guest name',
			'exclude' => 'false',
			'replace_commas' => 'false',
			'exclude_entry' => 'false',
			'excludeconditional' => 'false',
			'type' => 'var',
		);
		$row1_document = array(
			'field_name' => 'guest_document',
			'type' => 'files',
			'files' => array( array( 'upload_token' => $row2_token ) ),
		);
		$data = array(
			'_super_dynamic_data' => array(
				'guest_name' => array(
					array( 'guest_name' => $row0_name, 'guest_document' => $row0_document ),
					array( 'guest_name_2' => $row1_name, 'guest_document_2' => $row1_document ),
				),
			),
		);
		$data['guest_name'] = $data['_super_dynamic_data']['guest_name'][0]['guest_name'];
		$data['guest_document'] = $data['_super_dynamic_data']['guest_name'][0]['guest_document'];
		$data['guest_name_2'] = $data['_super_dynamic_data']['guest_name'][1]['guest_name_2'];
		$data['guest_document_2'] = $data['_super_dynamic_data']['guest_name'][1]['guest_document_2'];
		return $data;
	}

	/**
	 * Capture the hook payload emitted by the forked submit path.
	 */
	private function run_forked_submit_and_capture_hook() {
		$capture_path = tempnam( sys_get_temp_dir(), 'sf-proof-row3-hook-' );
		$this->assertNotFalse( $capture_path );
		$result = $this->run_dying_handler( static function() use ( $capture_path ) {
			add_action( 'super_before_sending_email_hook', static function( $atts ) use ( $capture_path ) {
				file_put_contents( $capture_path, wp_json_encode( $atts['data'] ), LOCK_EX );
			}, 10, 1 );
			call_user_func( array( 'SUPER_Ajax', 'submit_form' ) );
		} );
		$hook_json = file_exists( $capture_path ) ? file_get_contents( $capture_path ) : false;
		@unlink( $capture_path );
		return array(
			'result' => $result,
			'hook_data' => ( is_string( $hook_json ) && $hook_json !== '' ) ? json_decode( $hook_json, true ) : null,
		);
	}

	private function entry_ids_for( $form_id ) {
		return array_map( 'intval', get_posts( array(
			'post_type' => 'super_contact_entry',
			'post_parent' => $form_id,
			'post_status' => array( 'super_unread', 'super_read', 'publish' ),
			'fields' => 'ids',
			'posts_per_page' => -1,
		) ) );
	}

	public function test_repeater_text_and_file_rows_persist_integer_keyed_nested_data_with_alias_and_file_routes() {
		$this->configure_csrf( 'false' );
		$form_id = $this->repeater_form();
		$receipts = $this->issue_row_receipts( $form_id );
		$data = $this->production_repeater_data( $receipts['row1']['token'], $receipts['row2']['token'] );

		$this->set_submit_request( $form_id, $data );
		$capture = $this->run_forked_submit_and_capture_hook();
		$submit = $capture['result'];
		$this->assertSame( 0, $submit['status'], $submit['output'] );
		$decoded = json_decode( $submit['output'], true );
		$this->assertIsArray( $decoded, $submit['output'] );
		$this->assertFalse( $decoded['error'], isset( $decoded['msg'] ) ? $decoded['msg'] : $submit['output'] );

		$entry_ids = $this->entry_ids_for( $form_id );
		$this->assertCount( 1, $entry_ids );
		$entry_id = $entry_ids[0];

		// Assert against the persisted entry data.
		$entry_data = SUPER_Data_Access::get_entry_data( $entry_id );
		$this->assertIsArray( $entry_data );
		$this->assertArrayHasKey( '_super_dynamic_data', $entry_data );
		$this->assertArrayHasKey( 'guest_name', $entry_data['_super_dynamic_data'] );
		$rows = $entry_data['_super_dynamic_data']['guest_name'];

		$this->assertSame( array( 0, 1 ), array_keys( $rows ) );
		// Stored rows and mirrored routes must stay consistent.
		$this->assertSame( 'Ada', $entry_data['guest_name']['value'] );
		$this->assertSame( 'Grace', $entry_data['guest_name_2']['value'] );
		$this->assertSame( $entry_data['guest_name'], $rows[0]['guest_name'] );
		$this->assertSame( $entry_data['guest_name_2'], $rows[1]['guest_name_2'] );
		$this->assertArrayNotHasKey( 'guest_name_2', $rows[0] );
		$this->assertArrayNotHasKey( 'guest_name', $rows[1] );

		// File carriers must resolve to the owned files and strip internal proof data.
		$this->assertSame( $entry_data['guest_document'], $rows[0]['guest_document'] );
		$this->assertSame( $entry_data['guest_document_2'], $rows[1]['guest_document_2'] );
		$row0_files = array_values( $entry_data['guest_document']['files'] );
		$row1_files = array_values( $entry_data['guest_document_2']['files'] );
		$this->assertCount( 1, $row0_files );
		$this->assertCount( 1, $row1_files );
		$this->assertSame( basename( $receipts['row1']['owned']['file'] ), $row0_files[0]['value'] );
		$this->assertSame( basename( $receipts['row2']['owned']['file'] ), $row1_files[0]['value'] );
		$this->assertNotSame( $row0_files[0]['value'], $row1_files[0]['value'] );
		$this->assertSame( 'guest_document', $row0_files[0]['name'] );
		$this->assertSame( 'guest_document_2', $row1_files[0]['name'] );
		foreach( array( $row0_files[0], $row1_files[0] ) as $file ) {
			$this->assertArrayNotHasKey( 'upload_token', $file );
			$this->assertArrayNotHasKey( 'allowed_root', $file );
			$this->assertArrayNotHasKey( 'file', $file );
		}

		// A successful submission consumes both receipts and emits the persisted graph to the hook.
		$hook_data = $capture['hook_data'];
		$this->assertIsArray( $hook_data, 'super_before_sending_email_hook must fire with the resolved nested repeater graph.' );
		$this->assertSame( $entry_data['guest_name'], $hook_data['guest_name'] );
		$this->assertSame( $entry_data['guest_name_2'], $hook_data['guest_name_2'] );
		$this->assertSame( $entry_data['guest_document'], $hook_data['guest_document'] );
		$this->assertSame( $entry_data['guest_document_2'], $hook_data['guest_document_2'] );
		$this->assertSame( $entry_data['_super_dynamic_data'], $hook_data['_super_dynamic_data'] );
		$this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $receipts['row1']['token'], $form_id, 'guest_document' ) ) );
		$this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $receipts['row2']['token'], $form_id, 'guest_document' ) ) );
	}

	public function test_malformed_clone_alias_fails_closed_with_no_entry_and_receipts_intact() {
		$this->configure_csrf( 'false' );
		$form_id = $this->repeater_form();
		$receipts = $this->issue_row_receipts( $form_id );
		$data = $this->production_repeater_data( $receipts['row1']['token'], $receipts['row2']['token'] );

		// A mismatched repeater route must fail closed before persistence or receipt consumption.
		$data['guest_name_9'] = $data['guest_name_2'];
		unset( $data['guest_name_2'] );

		$this->set_submit_request( $form_id, $data );
		$submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
		$this->assertSame( 0, $submit['status'], $submit['output'] );
		$decoded = json_decode( $submit['output'], true );
		$this->assertIsArray( $decoded, $submit['output'] );
		$this->assertTrue( $decoded['error'] );
		$this->assertStringContainsString( 'invalid form data', strtolower( wp_strip_all_tags( $decoded['msg'] ) ) );
		$this->assertCount( 0, $this->entry_ids_for( $form_id ) );

		// Rejection leaves entry creation and both receipts untouched.
		$this->assertFileExists( $receipts['row1']['owned']['file'] );
		$this->assertFileExists( $receipts['row2']['owned']['file'] );
		$descriptor1 = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $receipts['row1']['token'], $form_id, 'guest_document' ) );
		$descriptor2 = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $receipts['row2']['token'], $form_id, 'guest_document' ) );
		$this->assertIsArray( $descriptor1, 'A malformed clone alias must fail closed before the first row receipt is claimed.' );
		$this->assertIsArray( $descriptor2, 'A malformed clone alias must fail closed before the second row receipt is claimed.' );
	}
}

class Test_Super_Forms_Proof_Row12_Dropdown_Security extends Super_Forms_Upload_Security_Test_Case {

	private function dropdown_element_data( $option_label='Member', $overrides=array() ) {
		return array_merge( array(
			'name' => 'choices',
			'label' => 'Membership',
			'dropdown_items' => array( array( 'value' => 'member', 'label' => $option_label ) ),
			'admin_email_value' => 'both',
			'confirm_email_value' => 'label',
			'contact_entry_value' => 'both',
		), $overrides );
	}

	/**
	 * Build the duplicate-name dropdown fixture used by the row 12 assertions.
	 */
	private function elements( $repeater_option_label='Member' ) {
		return array(
			array( 'tag' => 'dropdown', 'data' => $this->dropdown_element_data( 'Member' ) ),
			array(
				'tag' => 'column',
				'data' => array( 'duplicate' => 'enabled' ),
				'inner' => array(
					array( 'tag' => 'dropdown', 'data' => $this->dropdown_element_data( $repeater_option_label ) ),
				),
			),
		);
	}

	private function dropdown_form( $repeater_option_label='Member' ) {
		return $this->create_form( 'publish', $this->elements( $repeater_option_label ), array(
			'save_contact_entry' => 'yes',
			'send' => 'no',
			'confirm' => 'no',
			'form_thanks_title' => '',
			'form_thanks_description' => '',
			'form_show_thanks_msg' => '',
			'form_redirect_option' => '',
		) );
	}

	/**
	 * Build the client carrier fixture for the row 12 assertions.
	 */
	private function submission_data( $forged_label_row1='Forged label', $forged_label_row2='Forged row two label' ) {
		$row0 = array(
			'name' => 'choices',
			'value' => 'member',
			'selected_values' => array( 'member' ),
			'label' => $forged_label_row1,
			'type' => 'var',
		);
		$row1 = array(
			'name' => 'choices_2',
			'value' => 'member',
			'selected_values' => array( 'member' ),
			'label' => $forged_label_row2,
			'type' => 'var',
		);
		$data = array(
			'_super_dynamic_data' => array(
				'choices' => array(
					array( 'choices' => $row0 ),
					array( 'choices_2' => $row1 ),
				),
			),
		);
		$data['choices'] = $data['_super_dynamic_data']['choices'][0]['choices'];
		$data['choices_2'] = $data['_super_dynamic_data']['choices'][1]['choices_2'];
		return $data;
	}

	private function run_forked_submit_and_capture_hook() {
		$capture_path = tempnam( sys_get_temp_dir(), 'sf-proof-row12-hook-' );
		$this->assertNotFalse( $capture_path );
		$result = $this->run_dying_handler( static function() use ( $capture_path ) {
			add_action( 'super_before_sending_email_hook', static function( $atts ) use ( $capture_path ) {
				file_put_contents( $capture_path, wp_json_encode( $atts['data'] ), LOCK_EX );
			}, 10, 1 );
			call_user_func( array( 'SUPER_Ajax', 'submit_form' ) );
		} );
		$hook_json = file_exists( $capture_path ) ? file_get_contents( $capture_path ) : false;
		@unlink( $capture_path );
		return array(
			'result' => $result,
			'hook_data' => ( is_string( $hook_json ) && $hook_json !== '' ) ? json_decode( $hook_json, true ) : null,
		);
	}

	private function entry_ids_for( $form_id ) {
		return array_map( 'intval', get_posts( array(
			'post_type' => 'super_contact_entry',
			'post_parent' => $form_id,
			'post_status' => array( 'super_unread', 'super_read', 'publish' ),
			'fields' => 'ids',
			'posts_per_page' => -1,
		) ) );
	}

	public function test_agreeing_duplicate_dropdown_names_rebuild_canonical_presentation_with_configured_joiners() {
		$this->configure_csrf( 'false' );
		$form_id = $this->dropdown_form( 'Member' );
		$data = $this->submission_data();

		$this->set_submit_request( $form_id, $data );
		$capture = $this->run_forked_submit_and_capture_hook();
		$submit = $capture['result'];
		$this->assertSame( 0, $submit['status'], $submit['output'] );
		$decoded = json_decode( $submit['output'], true );
		$this->assertIsArray( $decoded, $submit['output'] );
		$this->assertFalse( $decoded['error'], isset( $decoded['msg'] ) ? $decoded['msg'] : $submit['output'] );

		$entry_ids = $this->entry_ids_for( $form_id );
		$this->assertCount( 1, $entry_ids );
		$entry_data = SUPER_Data_Access::get_entry_data( $entry_ids[0] );

		// Canonical presentation variants must be rebuilt from stored configuration.
		foreach( array(
			array( $entry_data['choices'], 'top-level route (row 1, collides with the standalone dropdown)' ),
			array( $entry_data['choices_2'], 'aliased repeater route (row 2)' ),
		) as $case ) {
			list( $carrier, $label ) = $case;
			$this->assertSame( 'member', $carrier['value'], $label );
			$this->assertSame( 'Membership', $carrier['label'], $label );
			$this->assertSame( 'Member', $carrier['option_label'], $label );
			$this->assertSame( 'Member (member)', $carrier['admin_value'], $label );
			$this->assertSame( 'Member', $carrier['confirm_value'], $label );
			$this->assertSame( 'Member (member)', $carrier['entry_value'], $label );
			$this->assertArrayNotHasKey( 'selected_values', $carrier, $label );
		}
		$this->assertSame( $entry_data['choices'], $entry_data['_super_dynamic_data']['choices'][0]['choices'] );
		$this->assertSame( $entry_data['choices_2'], $entry_data['_super_dynamic_data']['choices'][1]['choices_2'] );

		$hook_data = $capture['hook_data'];
		$this->assertIsArray( $hook_data );
		$this->assertSame( $entry_data['choices'], $hook_data['choices'] );
		$this->assertSame( $entry_data['choices_2'], $hook_data['choices_2'] );
	}

	public function test_conflicting_duplicate_dropdown_names_strip_presentation_at_the_public_boundary() {
		$this->configure_csrf( 'false' );
		// When stored presentation disagrees across matching routes, only the canonical value survives.
		$form_id = $this->dropdown_form( 'Conflicting' );
		$data = $this->submission_data();

		$this->set_submit_request( $form_id, $data );
		$capture = $this->run_forked_submit_and_capture_hook();
		$submit = $capture['result'];
		$this->assertSame( 0, $submit['status'], $submit['output'] );
		$decoded = json_decode( $submit['output'], true );
		$this->assertIsArray( $decoded, $submit['output'] );
		$this->assertFalse( $decoded['error'], isset( $decoded['msg'] ) ? $decoded['msg'] : $submit['output'] );

		$entry_ids = $this->entry_ids_for( $form_id );
		$this->assertCount( 1, $entry_ids );
		$entry_data = SUPER_Data_Access::get_entry_data( $entry_ids[0] );

		foreach( array(
			array( $entry_data['choices'], 'top-level route (row 1)' ),
			array( $entry_data['choices_2'], 'aliased repeater route (row 2)' ),
		) as $case ) {
			list( $carrier, $label ) = $case;
			$this->assertSame( 'member', $carrier['value'], $label );
			$this->assertArrayNotHasKey( 'label', $carrier, $label );
			$this->assertArrayNotHasKey( 'option_label', $carrier, $label );
			$this->assertArrayNotHasKey( 'admin_value', $carrier, $label );
			$this->assertArrayNotHasKey( 'confirm_value', $carrier, $label );
			$this->assertArrayNotHasKey( 'entry_value', $carrier, $label );
			$this->assertArrayNotHasKey( 'selected_values', $carrier, $label );
		}

		$hook_data = $capture['hook_data'];
		$this->assertIsArray( $hook_data );
		$this->assertSame( $entry_data['choices'], $hook_data['choices'] );
		$this->assertSame( $entry_data['choices_2'], $hook_data['choices_2'] );
	}

	public function test_forged_server_owned_presentation_keys_fail_closed_at_the_public_boundary() {
		$this->configure_csrf( 'false' );
		$form_id = $this->dropdown_form( 'Member' );

		// Client-declared presentation variants are rejected before entry creation.
		foreach( array( 'option_label', 'admin_value', 'confirm_value', 'entry_value' ) as $forged_key ) {
			foreach( array( 'choices', 'choices_2' ) as $target_route ) {
				$data = $this->submission_data();
				$data[ $target_route ][ $forged_key ] = 'forged-' . $forged_key;
				if( $target_route === 'choices' ) {
					$data['_super_dynamic_data']['choices'][0]['choices'][ $forged_key ] = 'forged-' . $forged_key;
				} else {
					$data['_super_dynamic_data']['choices'][1]['choices_2'][ $forged_key ] = 'forged-' . $forged_key;
				}

				$this->set_submit_request( $form_id, $data );
				$submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
				$this->assertSame( 0, $submit['status'], $submit['output'] );
				$decoded = json_decode( $submit['output'], true );
				$this->assertIsArray( $decoded, $submit['output'] );
				$this->assertTrue( $decoded['error'], "forging '$forged_key' on '$target_route' must be rejected, got: " . $submit['output'] );
				$this->assertStringContainsString( 'invalid form data', strtolower( wp_strip_all_tags( $decoded['msg'] ) ) );
			}
		}

		$this->assertCount( 0, $this->entry_ids_for( $form_id ), 'No forged presentation key may ever reach contact-entry storage.' );
	}
}

class Test_Super_Forms_Proof_Row12_Product_Attribute_Security extends Super_Forms_Upload_Security_Test_Case {

	private function form_settings() {
		return array(
			'save_contact_entry' => 'yes',
			'send' => 'no',
			'confirm' => 'no',
			'form_thanks_title' => '',
			'form_thanks_description' => '',
			'form_show_thanks_msg' => '',
			'form_redirect_option' => '',
		);
	}

	private function product_attribute_element( $tag ) {
		return array(
			'tag' => $tag,
			'data' => array(
				'name' => 'finish',
				'retrieve_method' => 'product_attribute',
				'retrieve_method_product_attribute' => 'color',
				'admin_email_value' => 'both',
				'confirm_email_value' => 'label',
				'contact_entry_value' => 'both',
			),
		);
	}
	private function run_contextless_product_attribute_submit( $form_id, $data ) {
		$this->set_submit_request( $form_id, $data );
		return $this->without_product_context( function() {
			return $this->run_dying_handler( function() {
				if( !class_exists('WooCommerce', false) ) {
					eval('class WooCommerce {}');
				}
				$GLOBALS['woocommerce'] = (object) array(
					'cart' => null,
				);
				SUPER_Ajax::submit_form();
			} );
		} );
	}

	private function without_product_context( $callback ) {
		global $post, $product;
		$had_post = isset($post);
		$had_product = isset($product);
		$saved_post = $had_post ? $post : null;
		$saved_product = $had_product ? $product : null;
		unset( $post, $product );
		try {
			return call_user_func( $callback );
		} finally {
			if( $had_post ) {
				$post = $saved_post;
			}else{
				unset( $post );
			}
			if( $had_product ) {
				$product = $saved_product;
			}else{
				unset( $product );
			}
		}
	}

	public function test_contextless_product_attribute_dropdown_accepts_the_frontend_selected_value_shape() {
		$this->configure_csrf( 'false' );
		$form_id = $this->create_form(
			'publish',
			array( $this->product_attribute_element( 'dropdown' ) ),
			$this->form_settings()
		);

		$submit = $this->run_contextless_product_attribute_submit(
			$form_id,
			array(
				'finish' => array(
					'name' => 'finish',
					'value' => 'blue',
					'selected_values' => array( 'blue' ),
					'type' => 'var',
				),
			)
		);

		$this->assertSame( 0, $submit['status'], $submit['output'] );
		$decoded = json_decode( $submit['output'], true );
		$this->assertIsArray( $decoded, $submit['output'] );
		$this->assertFalse( $decoded['error'], $submit['output'] );
		$entry_id = (int) $decoded['response_data']['contact_entry_id'];
		$this->assertGreaterThan( 0, $entry_id );
		$stored = SUPER_Data_Access::get_entry_data( $entry_id );
		$this->assertSame( 'blue', $stored['finish']['value'] );
		$this->assertSame( 'blue', $stored['finish']['option_label'] );
		$this->assertSame( 'blue (blue)', $stored['finish']['admin_value'] );
		$this->assertSame( 'blue', $stored['finish']['confirm_value'] );
		$this->assertSame( 'blue (blue)', $stored['finish']['entry_value'] );
	}

	public function test_contextless_product_attribute_radio_still_rejects_an_invalid_selected_value_shape() {
		$this->configure_csrf( 'false' );
		$form_id = $this->create_form(
			'publish',
			array( $this->product_attribute_element( 'radio' ) ),
			$this->form_settings()
		);

		$submit = $this->run_contextless_product_attribute_submit(
			$form_id,
			array(
				'finish' => array(
					'name' => 'finish',
					'value' => 'blue',
					'selected_values' => array( 'blue', 'red' ),
					'type' => 'var',
				),
			)
		);

		$this->assertSame( 0, $submit['status'], $submit['output'] );
		$decoded = json_decode( $submit['output'], true );
		$this->assertIsArray( $decoded, $submit['output'] );
		$this->assertTrue( $decoded['error'], $submit['output'] );
		$this->assertStringContainsString( 'invalid form data', strtolower( wp_strip_all_tags( $decoded['msg'] ) ) );
	}
}
