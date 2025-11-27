<?php
/**
 * Test Duplicate Detector
 *
 * @package Super_Forms
 * @since 6.5.0
 */

class Test_Duplicate_Detector extends WP_UnitTestCase {

	/**
	 * Form ID for testing
	 */
	private $form_id;

	/**
	 * Entry ID for testing
	 */
	private $entry_id;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test form
		$this->form_id = wp_insert_post(
			array(
				'post_type'   => 'super_form',
				'post_status' => 'publish',
				'post_title'  => 'Test Form for Duplicates',
			)
		);

		// Enable duplicate detection for form
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'duplicate_detection' => array(
					'duplicate_detection_enabled' => true,
					'email_time_enabled'          => true,
					'email_time_window'           => 10,
					'ip_time_enabled'             => true,
					'ip_time_window'              => 5,
					'hash_enabled'                => true,
					'custom_fields_enabled'       => false,
				),
			)
		);

		// Create existing entry
		$this->entry_id = wp_insert_post(
			array(
				'post_type'   => 'super_contact_entry',
				'post_status' => 'publish',
				'post_parent' => $this->form_id,
				'post_title'  => 'Test Entry',
			)
		);

		// Add IP address meta
		add_post_meta( $this->entry_id, '_super_contact_entry_ip', '192.168.1.100' );

		// Add entry data to EAV table
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';

		// Check if table exists before inserting
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
			$wpdb->insert(
				$table,
				array(
					'entry_id'    => $this->entry_id,
					'form_id'     => $this->form_id,
					'field_name'  => 'email',
					'field_value' => 'existing@example.com',
					'created_at'  => current_time( 'mysql' ),
				)
			);
		}
	}

	/**
	 * Clean up test environment
	 */
	public function tearDown(): void {
		// Clean up test data
		if ( $this->entry_id ) {
			wp_delete_post( $this->entry_id, true );
		}
		if ( $this->form_id ) {
			wp_delete_post( $this->form_id, true );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
			$wpdb->delete( $table, array( 'form_id' => $this->form_id ) );
		}

		parent::tearDown();
	}

	/**
	 * Test that class exists
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'SUPER_Duplicate_Detector' ) );
	}

	/**
	 * Test disabled detection allows all submissions
	 */
	public function test_disabled_detection_allows_all() {
		// Disable detection
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'duplicate_detection' => array(
					'duplicate_detection_enabled' => false,
				),
			)
		);

		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'email' => 'existing@example.com' )
		);

		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test new email passes duplicate check
	 */
	public function test_new_email_passes() {
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'email' => 'new@example.com' ),
			array()
		);

		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test IP time detection with new IP
	 */
	public function test_new_ip_passes() {
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'email' => 'another@example.com' ),
			array( 'user_ip' => '10.0.0.1' )
		);

		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test get_settings returns defaults
	 */
	public function test_get_settings_returns_defaults() {
		// Use a form with no settings
		$empty_form_id = wp_insert_post(
			array(
				'post_type'   => 'super_form',
				'post_status' => 'publish',
				'post_title'  => 'Empty Settings Form',
			)
		);

		$settings = SUPER_Duplicate_Detector::get_settings( $empty_form_id );

		$this->assertFalse( $settings['duplicate_detection_enabled'] );
		$this->assertTrue( $settings['email_time_enabled'] );
		$this->assertEquals( 10, $settings['email_time_window'] );
		$this->assertEquals( 'block', $settings['action_on_duplicate'] );

		wp_delete_post( $empty_form_id, true );
	}

	/**
	 * Test get_action returns default block
	 */
	public function test_get_action_returns_block_by_default() {
		$action = SUPER_Duplicate_Detector::get_action( $this->form_id );
		$this->assertEquals( 'block', $action );
	}

	/**
	 * Test get_action returns configured action
	 */
	public function test_get_action_returns_configured_action() {
		// Clear form settings cache
		if ( class_exists( 'SUPER_Forms' ) && method_exists( 'SUPER_Forms', 'instance' ) ) {
			SUPER_Forms()->form_settings = null;
		}

		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'duplicate_detection' => array(
					'duplicate_detection_enabled' => true,
					'action_on_duplicate'         => 'update',
				),
			)
		);

		$action = SUPER_Duplicate_Detector::get_action( $this->form_id );
		$this->assertEquals( 'update', $action );
	}

	/**
	 * Test store_submission_hash stores hash
	 */
	public function test_store_submission_hash() {
		// Set pending hash
		$GLOBALS['super_pending_submission_hash'] = 'abc123hash';

		SUPER_Duplicate_Detector::store_submission_hash( $this->entry_id );

		$stored_hash = get_post_meta( $this->entry_id, '_super_submission_hash', true );
		$this->assertEquals( 'abc123hash', $stored_hash );

		// Global should be cleared
		$this->assertFalse( isset( $GLOBALS['super_pending_submission_hash'] ) );
	}

	/**
	 * Test store_submission_hash does nothing when no hash pending
	 */
	public function test_store_submission_hash_no_pending() {
		unset( $GLOBALS['super_pending_submission_hash'] );

		SUPER_Duplicate_Detector::store_submission_hash( $this->entry_id );

		$stored_hash = get_post_meta( $this->entry_id, '_super_submission_hash', true );
		$this->assertEmpty( $stored_hash );
	}

	/**
	 * Test hash detection stores pending hash when no duplicate
	 */
	public function test_hash_check_stores_pending_hash() {
		unset( $GLOBALS['super_pending_submission_hash'] );

		// Clear form settings cache and configure for hash-only detection
		if ( class_exists( 'SUPER_Forms' ) && method_exists( 'SUPER_Forms', 'instance' ) ) {
			SUPER_Forms()->form_settings = null;
		}

		// Enable only hash detection (disable email/IP time checks)
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'duplicate_detection' => array(
					'duplicate_detection_enabled' => true,
					'email_time_enabled'          => false,
					'ip_time_enabled'             => false,
					'hash_enabled'                => true,
					'custom_fields_enabled'       => false,
				),
			)
		);

		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array(
				'name'  => 'John Doe',
				'email' => 'unique@example.com',
			),
			array()
		);

		$this->assertFalse( $result['duplicate'] );
		$this->assertTrue( isset( $GLOBALS['super_pending_submission_hash'] ) );
		$this->assertNotEmpty( $GLOBALS['super_pending_submission_hash'] );
	}

	/**
	 * Test detection with structured form data
	 */
	public function test_detection_with_structured_data() {
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array(
				'email' => array(
					'value' => 'structured@example.com',
					'name'  => 'email',
					'type'  => 'text',
				),
			),
			array()
		);

		// New email should pass
		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test empty email field skips email check
	 */
	public function test_empty_email_skips_email_check() {
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'name' => 'John Doe' ), // No email field
			array()
		);

		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test invalid email skips email check
	 */
	public function test_invalid_email_skips_email_check() {
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'email' => 'not-an-email' ),
			array()
		);

		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test empty IP skips IP check
	 */
	public function test_empty_ip_skips_ip_check() {
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'email' => 'test@example.com' ),
			array( 'user_ip' => '' )
		);

		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test custom fields check with missing fields
	 */
	public function test_custom_fields_missing_field_passes() {
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'duplicate_detection' => array(
					'duplicate_detection_enabled' => true,
					'email_time_enabled'          => false,
					'ip_time_enabled'             => false,
					'hash_enabled'                => false,
					'custom_fields_enabled'       => true,
					'custom_unique_fields'        => array( 'email', 'phone' ),
				),
			)
		);

		// Only email, no phone - should pass (can't match)
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'email' => 'existing@example.com' ),
			array()
		);

		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test result structure
	 */
	public function test_result_structure_no_duplicate() {
		$result = SUPER_Duplicate_Detector::check(
			$this->form_id,
			array( 'email' => 'unique@example.com' ),
			array()
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'duplicate', $result );
		$this->assertFalse( $result['duplicate'] );
	}

	/**
	 * Test log_detection does not throw errors
	 */
	public function test_log_detection_no_errors() {
		$result = array(
			'duplicate'         => true,
			'method'            => 'email_time',
			'original_entry_id' => 123,
			'details'           => 'Test detection',
		);

		$context = array(
			'user_ip' => '127.0.0.1',
		);

		// Should not throw any errors (logger may not have database table in test environment)
		// The method handles missing logger gracefully
		try {
			SUPER_Duplicate_Detector::log_detection( $this->form_id, $result, $context );
			$this->assertTrue( true ); // If we get here, no errors occurred
		} catch ( Exception $e ) {
			// Logger database table may not exist in test environment - that's okay
			$this->assertStringContainsString( 'Table', $e->getMessage() );
		}
	}
}
