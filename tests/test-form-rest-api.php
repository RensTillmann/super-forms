<?php
/**
 * Test SUPER_Form_REST_Controller class
 *
 * Tests REST API endpoints for form operations
 *
 * @package Super_Forms\Tests
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test Form REST API
 */
class Test_Form_REST_API extends TestCase {

	/**
	 * Test form IDs
	 */
	private $test_form_ids = array();

	/**
	 * REST API namespace
	 */
	private $namespace = 'super-forms/v1';

	/**
	 * Admin user ID
	 */
	private $admin_user_id;

	/**
	 * Setup test environment
	 */
	public function set_up() {
		parent::set_up();

		// Create admin user
		$this->admin_user_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		// Load required classes
		require_once dirname( __DIR__ ) . '/src/includes/class-form-dal.php';
		require_once dirname( __DIR__ ) . '/src/includes/class-form-operations.php';
		require_once dirname( __DIR__ ) . '/src/includes/class-form-rest-controller.php';

		// Register REST routes
		add_action( 'rest_api_init', array( 'SUPER_Form_REST_Controller', 'register_routes' ) );
		do_action( 'rest_api_init' );
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
	 * Test GET /forms endpoint
	 */
	public function test_get_forms() {
		// Create test forms
		for ( $i = 1; $i <= 3; $i++ ) {
			$form_id = SUPER_Form_DAL::create( array(
				'name' => 'REST Test Form ' . $i,
				'status' => 'publish',
				'elements' => array(),
				'settings' => array(),
				'translations' => array(),
			) );
			$this->test_form_ids[] = $form_id;
		}

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/forms' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertGreaterThanOrEqual( 3, count( $data ) );
	}

	/**
	 * Test GET /forms/{id} endpoint
	 */
	public function test_get_form() {
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'REST Get Test Form',
			'status' => 'publish',
			'elements' => array( 'test' => 'element' ),
			'settings' => array( 'test' => 'setting' ),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/forms/' . $form_id );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( $form_id, $data['id'] );
		$this->assertEquals( 'REST Get Test Form', $data['name'] );
	}

	/**
	 * Test POST /forms endpoint (create)
	 */
	public function test_create_form() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/forms' );
		$request->set_body_params( array(
			'name' => 'REST Created Form',
			'status' => 'publish',
			'elements' => array( 'field1' => 'value1' ),
			'settings' => array(),
			'translations' => array(),
		) );

		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'REST Created Form', $data['name'] );

		$this->test_form_ids[] = $data['id'];
	}

	/**
	 * Test PUT /forms/{id} endpoint (update)
	 */
	public function test_update_form() {
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Before Update',
			'status' => 'draft',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'PUT', '/' . $this->namespace . '/forms/' . $form_id );
		$request->set_body_params( array(
			'name' => 'After Update',
			'status' => 'publish',
		) );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'After Update', $data['name'] );
		$this->assertEquals( 'publish', $data['status'] );
	}

	/**
	 * Test DELETE /forms/{id} endpoint
	 */
	public function test_delete_form() {
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Form to Delete',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/forms/' . $form_id );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify deletion
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertNull( $form );
	}

	/**
	 * Test POST /forms/{id}/operations endpoint
	 */
	public function test_apply_operations() {
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Operations Test Form',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/forms/' . $form_id . '/operations' );
		$request->set_body_params( array(
			'operations' => array(
				array(
					'op' => 'add',
					'path' => '/elements/field1',
					'value' => array( 'type' => 'text' ),
				),
			),
		) );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'field1', $data['elements'] );
	}

	/**
	 * Test POST /forms/{id}/duplicate endpoint
	 */
	public function test_duplicate_form() {
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Original Form',
			'status' => 'publish',
			'elements' => array( 'field1' => 'value1' ),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/forms/' . $form_id . '/duplicate' );
		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertNotEquals( $form_id, $data['id'] );
		$this->assertEquals( 'Original Form (Copy)', $data['name'] );

		$this->test_form_ids[] = $data['id'];
	}

	/**
	 * Test GET /forms/search endpoint
	 */
	public function test_search_forms() {
		$unique_name = 'Unique Search Form ' . time();
		$form_id = SUPER_Form_DAL::create( array(
			'name' => $unique_name,
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/forms/search' );
		$request->set_query_params( array( 'query' => 'Unique Search Form' ) );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertGreaterThan( 0, count( $data ) );

		// Verify our form is in results
		$found = false;
		foreach ( $data as $form ) {
			if ( $form['id'] === $form_id ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found );
	}

	/**
	 * Test POST /forms/{id}/archive endpoint
	 */
	public function test_archive_form() {
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Form to Archive',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/forms/' . $form_id . '/archive' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify archived
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertEquals( 'archived', $form->status );
	}

	/**
	 * Test POST /forms/{id}/restore endpoint
	 */
	public function test_restore_form() {
		$form_id = SUPER_Form_DAL::create( array(
			'name' => 'Form to Restore',
			'status' => 'publish',
			'elements' => array(),
			'settings' => array(),
			'translations' => array(),
		) );
		$this->test_form_ids[] = $form_id;
		SUPER_Form_DAL::archive( $form_id );

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/forms/' . $form_id . '/restore' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify restored
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertEquals( 'publish', $form->status );
	}

	/**
	 * Test POST /forms/bulk endpoint
	 */
	public function test_bulk_operations() {
		// Create test forms
		$form_ids = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$form_id = SUPER_Form_DAL::create( array(
				'name' => 'Bulk Test Form ' . $i,
				'status' => 'publish',
				'elements' => array(),
				'settings' => array(),
				'translations' => array(),
			) );
			$this->test_form_ids[] = $form_id;
			$form_ids[] = $form_id;
		}

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/forms/bulk' );
		$request->set_body_params( array(
			'operation' => 'archive',
			'form_ids' => $form_ids,
		) );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 3, $data['success_count'] );
		$this->assertEquals( 0, $data['failed_count'] );

		// Verify all forms archived
		foreach ( $form_ids as $form_id ) {
			$form = SUPER_Form_DAL::get( $form_id );
			$this->assertEquals( 'archived', $form->status );
		}
	}

	/**
	 * Test permission check (non-admin user)
	 */
	public function test_permission_check() {
		$subscriber_id = $this->factory->user->create( array(
			'role' => 'subscriber',
		) );
		wp_set_current_user( $subscriber_id );

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/forms' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test 404 for non-existent form
	 */
	public function test_not_found() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/forms/999999999' );
		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test validation errors
	 */
	public function test_validation_errors() {
		wp_set_current_user( $this->admin_user_id );

		// Try to create form without required fields
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/forms' );
		$request->set_body_params( array() );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
	}
}
