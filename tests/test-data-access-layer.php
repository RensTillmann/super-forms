<?php
/**
 * Test Data Access Layer
 * Tests CRUD operations and phase-based storage strategy
 */

require_once 'class-test-helpers.php';

class Test_Data_Access_Layer extends SUPER_Test_Helpers {

	public function test_get_entry_data_serialized() {
		$entry_id = $this->create_test_entry(array(
			'name' => array('value' => 'Test Name'),
		));

		$data = SUPER_Data_Access::get_entry_data($entry_id);

		$this->assertIsArray($data);
		$this->assertEquals('Test Name', $data['name']['value']);

		$this->cleanup_test_entries();
	}

	public function test_save_entry_data() {
		$entry_id = wp_insert_post(array(
			'post_type' => 'super_contact_entry',
			'post_status' => 'publish',
		));

		$data = array(
			'email' => array('value' => 'new@example.com'),
		);

		SUPER_Data_Access::save_entry_data($entry_id, $data);

		$retrieved = SUPER_Data_Access::get_entry_data($entry_id);
		$this->assertEquals('new@example.com', $retrieved['email']['value']);

		$this->cleanup_test_entries();
	}

	public function test_update_single_field() {
		$entry_id = $this->create_test_entry(array(
			'status' => array('value' => 'pending'),
		));

		SUPER_Data_Access::update_entry_field($entry_id, 'status', 'approved');

		$data = SUPER_Data_Access::get_entry_data($entry_id);
		$this->assertEquals('approved', $data['status']['value']);

		$this->cleanup_test_entries();
	}

	public function test_delete_entry_data() {
		$entry_id = $this->create_test_entry();

		SUPER_Data_Access::delete_entry_data($entry_id);

		$data = SUPER_Data_Access::get_entry_data($entry_id);
		$this->assertEmpty($data);

		$this->cleanup_test_entries();
	}

	public function test_invalid_entry_id_returns_error() {
		$result = SUPER_Data_Access::get_entry_data('invalid');
		$this->assertWPError($result);
	}

	public function test_nonexistent_entry_returns_error() {
		$result = SUPER_Data_Access::get_entry_data(999999);
		$this->assertWPError($result);
	}

	public function test_save_with_invalid_data_returns_error() {
		$entry_id = wp_insert_post(array(
			'post_type' => 'super_contact_entry',
			'post_status' => 'publish',
		));

		$result = SUPER_Data_Access::save_entry_data($entry_id, 'not_an_array');
		$this->assertWPError($result);

		$this->cleanup_test_entries();
	}

	public function test_repeater_field_handling() {
		$repeater_data = array(
			'customer' => array(
				'value' => array(
					array('value' => 'John'),
					array('value' => 'Doe'),
				),
			),
		);

		$entry_id = $this->create_test_entry($repeater_data);
		$retrieved = SUPER_Data_Access::get_entry_data($entry_id);

		$this->assertIsArray($retrieved['customer']['value']);
		$this->assertCount(2, $retrieved['customer']['value']);

		$this->cleanup_test_entries();
	}

	public function test_complex_entry_data() {
		$complex_data = $this->create_complex_test_data();
		$entry_id = $this->create_test_entry($complex_data);

		$retrieved = SUPER_Data_Access::get_entry_data($entry_id);

		$this->assertArrayHasKey('name', $retrieved);
		$this->assertArrayHasKey('email', $retrieved);
		$this->assertArrayHasKey('message', $retrieved);

		$this->cleanup_test_entries();
	}
}
