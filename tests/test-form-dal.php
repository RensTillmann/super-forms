<?php
/**
 * Test SUPER_Form_DAL class
 *
 * @package Super_Forms\Tests
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test Form DAL operations
 */
class Test_Form_DAL extends TestCase {

	/**
	 * Test form IDs created during tests
	 */
	private $test_form_ids = array();

	/**
	 * Setup test environment
	 */
	public function set_up() {
		parent::set_up();

		// Ensure DAL class is loaded
		require_once dirname( __DIR__ ) . '/src/includes/class-form-dal.php';
	}

	/**
	 * Cleanup after tests
	 */
	public function tear_down() {
		// Clean up test forms
		foreach ( $this->test_form_ids as $form_id ) {
			SUPER_Form_DAL::delete( $form_id );
		}
		$this->test_form_ids = array();

		parent::tear_down();
	}

	/**
	 * Test create() method
	 */
	public function test_create() {
		$form_data = array(
			'name' => 'Test Form DAL Create',
			'status' => 'publish',
			'elements' => array( 'test' => 'element' ),
			'settings' => array( 'test' => 'setting' ),
			'translations' => array( 'en' => array( 'test' => 'translation' ) ),
		);

		$form_id = SUPER_Form_DAL::create( $form_data );
		$this->test_form_ids[] = $form_id;

		$this->assertIsInt( $form_id );
		$this->assertGreaterThan( 0, $form_id );
	}

	/**
	 * Test create() with missing required fields
	 */
	public function test_create_missing_fields() {
		$result = SUPER_Form_DAL::create( array() );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test get() method
	 */
	public function test_get() {
		// Create test form
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Test Form DAL Get',
			'status' => 'publish',
			'elements' => array( 'field1' => 'value1' ),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		// Get form
		$form = SUPER_Form_DAL::get( $form_id );

		$this->assertIsObject( $form );
		$this->assertEquals( $form_id, $form->id );
		$this->assertEquals( 'Test Form DAL Get', $form->name );
		$this->assertEquals( 'publish', $form->status );
		$this->assertIsArray( $form->elements );
		$this->assertEquals( 'value1', $form->elements['field1'] );
	}

	/**
	 * Test get() with invalid ID
	 */
	public function test_get_invalid_id() {
		$form = SUPER_Form_DAL::get( 999999999 );

		$this->assertNull( $form );
	}

	/**
	 * Test update() method
	 */
	public function test_update() {
		// Create test form
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Test Form Before Update',
			'status' => 'draft',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		// Update form
		$result = SUPER_Form_DAL::update( $form_id, array(
			'name' => 'Test Form After Update',
			'status' => 'publish',
		) );

		$this->assertTrue( $result );

		// Verify update
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertEquals( 'Test Form After Update', $form->name );
		$this->assertEquals( 'publish', $form->status );
	}

	/**
	 * Test delete() method
	 */
	public function test_delete() {
		// Create test form
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Test Form Delete',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );

		// Delete form
		$result = SUPER_Form_DAL::delete( $form_id );

		$this->assertTrue( $result );

		// Verify deletion
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertNull( $form );
	}

	/**
	 * Test query() method
	 */
	public function test_query() {
		// Create multiple test forms
		$form_ids = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$form_id = SUPER_Form_DAL::create( array(
				'name' => 'Test Form Query ' . $i,
				'status' => 'publish',
				'elements' => array(),
				'settings' => array(),
				'translations' => array(),
			) );
			$this->test_form_ids[] = $form_id;
			$form_ids[] = $form_id;
		}

		// Query forms
		$forms = SUPER_Form_DAL::query( array(
			'status' => 'publish',
			'number' => 10,
		) );

		$this->assertIsArray( $forms );
		$this->assertGreaterThanOrEqual( 3, count( $forms ) );

		// Verify our test forms are in results
		$found_ids = array_map( function( $form ) { return $form->id; }, $forms );
		foreach ( $form_ids as $test_id ) {
			$this->assertContains( $test_id, $found_ids );
		}
	}

	/**
	 * Test query() with unlimited results (number = -1)
	 */
	public function test_query_unlimited() {
		$forms = SUPER_Form_DAL::query( array(
			'number' => -1,
		) );

		$this->assertIsArray( $forms );
		// Should return all forms, not 0
		$this->assertGreaterThan( 0, count( $forms ) );
	}

	/**
	 * Test duplicate() method
	 */
	public function test_duplicate() {
		// Create original form
		$original_id = SUPER_Form_DAL::create( array(
			'name' => 'Original Form',
			'status' => 'publish',
			'elements' => array( 'field1' => 'value1' ),
			'settings' => array( 'setting1' => 'value1' ),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $original_id;

		// Duplicate form
		$duplicate_id = SUPER_Form_DAL::duplicate( $original_id );
		$this->test_form_ids[] = $duplicate_id;

		$this->assertIsInt( $duplicate_id );
		$this->assertNotEquals( $original_id, $duplicate_id );

		// Verify duplicate
		$duplicate = SUPER_Form_DAL::get( $duplicate_id );
		$this->assertEquals( 'Original Form (Copy)', $duplicate->name );
		$this->assertEquals( 'draft', $duplicate->status );
		$this->assertEquals( array( 'field1' => 'value1' ), $duplicate->elements );
	}

	/**
	 * Test search() method
	 */
	public function test_search() {
		// Create test form with unique name
		$unique_name = 'Unique Search Test Form ' . time();
		$form_id = SUPER_Form_DAL::create( array(
			'name' => $unique_name,
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		// Search for form
		$results = SUPER_Form_DAL::search( 'Unique Search Test' );

		$this->assertIsArray( $results );
		$this->assertGreaterThan( 0, count( $results ) );

		// Verify our form is in results
		$found = false;
		foreach ( $results as $form ) {
			if ( $form->id === $form_id ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Search did not find the test form' );
	}

	/**
	 * Test archive() method
	 */
	public function test_archive() {
		// Create test form
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Test Form Archive',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		// Archive form
		$result = SUPER_Form_DAL::archive( $form_id );

		$this->assertTrue( $result );

		// Verify archived
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertEquals( 'archived', $form->status );
	}

	/**
	 * Test restore() method
	 */
	public function test_restore() {
		// Create and archive test form
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Test Form Restore',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;
		SUPER_Form_DAL::archive( $form_id );

		// Restore form
		$result = SUPER_Form_DAL::restore( $form_id );

		$this->assertTrue( $result );

		// Verify restored
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertEquals( 'publish', $form->status );
	}

	/**
	 * Test JSON encoding/decoding of form data
	 */
	public function test_json_encoding() {
		$complex_data = array(
			'nested' => array(
				'deep' => array(
					'value' => 'test',
				),
			),
			'special_chars' => 'Test with "quotes" and \'apostrophes\'',
		);

		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'JSON Test Form',
			'status' => 'publish',
			'elements' => $complex_data,
			'settings' => $complex_data,
			'translations' => array( 'en' => $complex_data ),
		) );
		$this->test_form_ids[] = $form_id;

		$form = SUPER_Form_DAL::get( $form_id );

		$this->assertEquals( $complex_data, $form->elements );
		$this->assertEquals( $complex_data, $form->settings );
		$this->assertEquals( array( 'en' => $complex_data ), $form->translations );
	}

	/**
	 * Test timestamps (created_at, updated_at)
	 */
	public function test_timestamps() {
		$before = current_time( 'mysql' );

		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Timestamp Test Form',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		$after = current_time( 'mysql' );

		$form = SUPER_Form_DAL::get( $form_id );

		$this->assertNotEmpty( $form->created_at );
		$this->assertNotEmpty( $form->updated_at );
		$this->assertGreaterThanOrEqual( $before, $form->created_at );
		$this->assertLessThanOrEqual( $after, $form->created_at );
	}
}
