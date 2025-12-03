<?php
/**
 * Test SUPER_Form_Operations class
 *
 * Tests JSON Patch (RFC 6902) operations for form editing
 *
 * @package Super_Forms\Tests
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test Form Operations (JSON Patch)
 */
class Test_Form_Operations extends TestCase {

	/**
	 * Test form ID
	 */
	private $test_form_id;

	/**
	 * Setup test environment
	 */
	public function set_up() {
		parent::set_up();

		// Ensure classes are loaded
		require_once dirname( __DIR__ ) . '/src/includes/class-form-dal.php';
		require_once dirname( __DIR__ ) . '/src/includes/class-form-operations.php';

		// Create test form
		$this->test_form_id = SUPER_Form_DAL::create( array(
			'name' => 'Test Form Operations',
			'status' => 'publish',
			'elements' => array(
				'field1' => array(
					'type' => 'text',
					'name' => 'field1',
					'label' => 'Field 1',
				),
			),
			'settings' => array(
				'setting1' => 'value1',
			),
			'translations' => array(),
		) );
	}

	/**
	 * Cleanup after tests
	 */
	public function tear_down() {
		if ( $this->test_form_id ) {
			SUPER_Form_DAL::delete( $this->test_form_id );
		}

		parent::tear_down();
	}

	/**
	 * Test add operation
	 */
	public function test_add_operation() {
		$operations = array(
			array(
				'op' => 'add',
				'path' => '/elements/field2',
				'value' => array(
					'type' => 'email',
					'name' => 'field2',
					'label' => 'Email',
				),
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify field was added
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertArrayHasKey( 'field2', $form->elements );
		$this->assertEquals( 'email', $form->elements['field2']['type'] );
	}

	/**
	 * Test remove operation
	 */
	public function test_remove_operation() {
		$operations = array(
			array(
				'op' => 'remove',
				'path' => '/elements/field1',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify field was removed
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertArrayNotHasKey( 'field1', $form->elements );
	}

	/**
	 * Test replace operation
	 */
	public function test_replace_operation() {
		$operations = array(
			array(
				'op' => 'replace',
				'path' => '/elements/field1/label',
				'value' => 'Updated Label',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify label was replaced
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertEquals( 'Updated Label', $form->elements['field1']['label'] );
	}

	/**
	 * Test copy operation
	 */
	public function test_copy_operation() {
		$operations = array(
			array(
				'op' => 'copy',
				'from' => '/elements/field1',
				'path' => '/elements/field1_copy',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify field was copied
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertArrayHasKey( 'field1_copy', $form->elements );
		$this->assertEquals( $form->elements['field1'], $form->elements['field1_copy'] );
	}

	/**
	 * Test move operation
	 */
	public function test_move_operation() {
		$operations = array(
			array(
				'op' => 'move',
				'from' => '/settings/setting1',
				'path' => '/settings/setting1_moved',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify setting was moved
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertArrayNotHasKey( 'setting1', $form->settings );
		$this->assertArrayHasKey( 'setting1_moved', $form->settings );
		$this->assertEquals( 'value1', $form->settings['setting1_moved'] );
	}

	/**
	 * Test test operation (condition)
	 */
	public function test_test_operation() {
		// Should succeed - value matches
		$operations = array(
			array(
				'op' => 'test',
				'path' => '/settings/setting1',
				'value' => 'value1',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );
		$this->assertFalse( is_wp_error( $result ) );

		// Should fail - value doesn't match
		$operations = array(
			array(
				'op' => 'test',
				'path' => '/settings/setting1',
				'value' => 'wrong_value',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test multiple operations in sequence
	 */
	public function test_multiple_operations() {
		$operations = array(
			// Add new field
			array(
				'op' => 'add',
				'path' => '/elements/email',
				'value' => array( 'type' => 'email', 'name' => 'email' ),
			),
			// Update existing field
			array(
				'op' => 'replace',
				'path' => '/elements/field1/label',
				'value' => 'New Label',
			),
			// Add new setting
			array(
				'op' => 'add',
				'path' => '/settings/new_setting',
				'value' => 'new_value',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify all operations applied
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertArrayHasKey( 'email', $form->elements );
		$this->assertEquals( 'New Label', $form->elements['field1']['label'] );
		$this->assertEquals( 'new_value', $form->settings['new_setting'] );
	}

	/**
	 * Test invalid operation
	 */
	public function test_invalid_operation() {
		$operations = array(
			array(
				'op' => 'invalid_op',
				'path' => '/elements/field1',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test missing required fields
	 */
	public function test_missing_required_fields() {
		// Missing 'path'
		$operations = array(
			array(
				'op' => 'add',
				'value' => 'test',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test invalid path format
	 */
	public function test_invalid_path_format() {
		// Path must start with /
		$operations = array(
			array(
				'op' => 'add',
				'path' => 'invalid/path',
				'value' => 'test',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test operation on non-existent form
	 */
	public function test_operation_on_nonexistent_form() {
		$operations = array(
			array(
				'op' => 'add',
				'path' => '/elements/field2',
				'value' => 'test',
			),
		);

		$result = SUPER_Form_Operations::apply( 999999999, $operations );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test nested path operations
	 */
	public function test_nested_path_operations() {
		$operations = array(
			array(
				'op' => 'replace',
				'path' => '/elements/field1/type',
				'value' => 'textarea',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify nested value changed
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertEquals( 'textarea', $form->elements['field1']['type'] );
	}

	/**
	 * Test array index operations
	 */
	public function test_array_index_operations() {
		// First, add an array setting
		SUPER_Form_DAL::update( $this->test_form_id, array(
			'settings' => array(
				'array_setting' => array( 'item1', 'item2', 'item3' ),
			),
		) );

		// Replace array item by index
		$operations = array(
			array(
				'op' => 'replace',
				'path' => '/settings/array_setting/1',
				'value' => 'updated_item2',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertFalse( is_wp_error( $result ) );

		// Verify array item changed
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertEquals( 'updated_item2', $form->settings['array_setting'][1] );
	}

	/**
	 * Test operation atomicity (all or nothing)
	 */
	public function test_operation_atomicity() {
		$original_form = SUPER_Form_DAL::get( $this->test_form_id );

		// Operations with one invalid operation in the middle
		$operations = array(
			array(
				'op' => 'add',
				'path' => '/elements/new_field',
				'value' => 'test',
			),
			array(
				'op' => 'invalid_operation', // This will fail
				'path' => '/elements/field1',
			),
			array(
				'op' => 'replace',
				'path' => '/name',
				'value' => 'Should not apply',
			),
		);

		$result = SUPER_Form_Operations::apply( $this->test_form_id, $operations );

		$this->assertInstanceOf( 'WP_Error', $result );

		// Verify no changes were applied (atomicity)
		$form = SUPER_Form_DAL::get( $this->test_form_id );
		$this->assertEquals( $original_form->elements, $form->elements );
		$this->assertEquals( $original_form->name, $form->name );
	}
}
