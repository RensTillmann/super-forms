<?php
/**
 * Test Helper Class
 *
 * Base class for Super Forms tests with helper methods for creating test data.
 *
 * @package Super_Forms\Tests
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * SUPER_Test_Helpers class
 *
 * Provides helper methods for creating test entries and test data.
 */
class SUPER_Test_Helpers extends TestCase {

	/**
	 * Create a test contact entry with data
	 *
	 * @param array $data Optional entry data. Uses defaults if not provided.
	 * @return int Entry ID
	 */
	protected function create_test_entry( $data = array() ) {
		$defaults = array(
			'name'  => array(
				'name'  => 'name',
				'value' => 'Test User',
				'label' => 'Name',
				'type'  => 'text',
			),
			'email' => array(
				'name'  => 'email',
				'value' => 'test@example.com',
				'label' => 'Email',
				'type'  => 'email',
			),
		);

		$entry_data = wp_parse_args( $data, $defaults );

		// Create the contact entry post
		$entry_id = wp_insert_post(
			array(
				'post_type'   => 'super_contact_entry',
				'post_title'  => 'Test Entry - ' . gmdate( 'Y-m-d H:i:s' ),
				'post_status' => 'publish',
			)
		);

		// Save entry data as serialized meta
		add_post_meta( $entry_id, '_super_contact_entry_data', serialize( $entry_data ) );

		return $entry_id;
	}

	/**
	 * Create multiple test entries
	 *
	 * @param int   $count Number of entries to create.
	 * @param array $base_data Base data for entries (will be modified per entry).
	 * @return array Array of entry IDs.
	 */
	protected function create_test_entries( $count, $base_data = array() ) {
		$entry_ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$data = $base_data;

			// Modify email to be unique
			if ( isset( $data['email'] ) ) {
				$data['email']['value'] = "user{$i}@example.com";
			} else {
				$data['email'] = array(
					'name'  => 'email',
					'value' => "user{$i}@example.com",
					'label' => 'Email',
					'type'  => 'email',
				);
			}

			// Add index field for tracking
			$data["entry_index_{$i}"] = array(
				'name'  => "entry_index_{$i}",
				'value' => $i,
				'label' => 'Entry Index',
				'type'  => 'hidden',
			);

			$entry_ids[] = $this->create_test_entry( $data );
		}

		return $entry_ids;
	}

	/**
	 * Create complex test data with various field types
	 *
	 * @return array Complex entry data array
	 */
	protected function create_complex_test_data() {
		return array(
			'name'           => array(
				'name'  => 'name',
				'value' => 'Test User',
				'label' => 'Name',
				'type'  => 'text',
			),
			'email'          => array(
				'name'  => 'email',
				'value' => 'complex@example.com',
				'label' => 'Email',
				'type'  => 'email',
			),
			'phone'          => array(
				'name'  => 'phone',
				'value' => '+1-555-123-4567',
				'label' => 'Phone',
				'type'  => 'text',
			),
			'message'        => array(
				'name'  => 'message',
				'value' => "Multi-line\ntext\nvalue",
				'label' => 'Message',
				'type'  => 'textarea',
			),
			'number_field'   => array(
				'name'  => 'number_field',
				'value' => '42',
				'label' => 'Number Field',
				'type'  => 'number',
			),
			'checkbox'       => array(
				'name'  => 'checkbox',
				'value' => 'option1,option2',
				'label' => 'Checkbox Field',
				'type'  => 'checkbox',
			),
			'file_upload'    => array(
				'name'  => 'file_upload',
				'value' => '/uploads/2025/10/test-file.pdf',
				'label' => 'File Upload',
				'type'  => 'file',
			),
		);
	}

	/**
	 * Create test data with repeater fields (nested arrays)
	 *
	 * @return array Entry data with repeater fields
	 */
	protected function create_repeater_test_data() {
		return array(
			'customer' => array(
				'name'  => 'customer',
				'value' => array(
					0 => array(
						'first_name' => array(
							'value' => 'John',
							'label' => 'First Name',
							'type'  => 'text',
						),
						'last_name'  => array(
							'value' => 'Doe',
							'label' => 'Last Name',
							'type'  => 'text',
						),
					),
					1 => array(
						'first_name' => array(
							'value' => 'Jane',
							'label' => 'First Name',
							'type'  => 'text',
						),
						'last_name'  => array(
							'value' => 'Smith',
							'label' => 'Last Name',
							'type'  => 'text',
						),
					),
				),
				'label' => 'Customer',
				'type'  => 'dynamic',
			),
		);
	}

	/**
	 * Clean up test entries after test
	 */
	protected function cleanup_test_entries() {
		global $wpdb;

		// Delete all test entries
		$wpdb->query(
			"DELETE FROM {$wpdb->posts}
			WHERE post_type = 'super_contact_entry'
			AND post_title LIKE 'Test Entry%'"
		);

		// Clean up orphaned meta
		$wpdb->query(
			"DELETE FROM {$wpdb->postmeta}
			WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
		);
	}

	/**
	 * Set up before each test
	 */
	public function set_up() {
		parent::set_up();

		// Reset migration state to prevent backwards compat hooks from interfering
		// Migration status can persist between test runs in wptests_options,
		// causing the intercept_get_entry_data filter to activate unexpectedly
		delete_option( 'superforms_eav_migration' );

		$this->cleanup_test_entries();
	}

	/**
	 * Tear down after each test
	 */
	public function tear_down() {
		// Reset migration state even if test errored before its own cleanup
		// This prevents backwards compat hooks from interfering with subsequent tests
		delete_option( 'superforms_eav_migration' );

		$this->cleanup_test_entries();
		parent::tear_down();
	}
}
