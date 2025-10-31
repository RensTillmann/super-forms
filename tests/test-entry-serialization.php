<?php
/**
 * Test Entry Data Serialization/Deserialization
 *
 * Critical Gap 1: Verify entry data survives serialize→unserialize cycle
 *
 * @package Super_Forms\Tests
 */

require_once 'class-test-helpers.php';

/**
 * Test_Entry_Serialization class
 */
class Test_Entry_Serialization extends SUPER_Test_Helpers {

	/**
	 * Test that entry data serialization preserves all fields
	 */
	public function test_entry_data_serialization_preserves_all_fields() {
		$original_data = array(
			'name'  => array(
				'value' => 'John Doe',
				'label' => 'Name',
				'type'  => 'text',
			),
			'email' => array(
				'value' => 'john@example.com',
				'label' => 'Email',
				'type'  => 'email',
			),
		);

		$serialized   = serialize( $original_data );
		$unserialized = unserialize( $serialized );

		$this->assertEquals( $original_data, $unserialized );
	}

	/**
	 * Test corrupt serialized data handling
	 */
	public function test_corrupt_serialized_data_handling() {
		$corrupt_data = 'a:2:{s:4:"name";s:'; // Incomplete serialized string
		$result       = @unserialize( $corrupt_data );

		$this->assertFalse( $result, 'Corrupt data should return false' );
	}

	/**
	 * Test serialization with special characters
	 */
	public function test_serialization_with_special_characters() {
		$data = array(
			'special' => array(
				'value' => 'Test "quotes" and \'apostrophes\' and <html>',
				'type'  => 'text',
			),
		);

		$serialized   = serialize( $data );
		$unserialized = unserialize( $serialized );

		$this->assertEquals( $data, $unserialized );
	}

	/**
	 * Test serialization with nested arrays (repeater fields)
	 */
	public function test_serialization_with_nested_arrays() {
		$data = $this->create_repeater_test_data();

		$serialized   = serialize( $data );
		$unserialized = unserialize( $serialized );

		$this->assertEquals( $data, $unserialized );
	}
}
