<?php
/**
 * Base Test Case for Trigger Actions
 *
 * Provides common utilities for testing trigger actions
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

abstract class SUPER_Action_Test_Case extends WP_UnitTestCase {

	/**
	 * Test form ID
	 *
	 * @var int
	 */
	protected $form_id;

	/**
	 * Test entry ID
	 *
	 * @var int
	 */
	protected $entry_id;

	/**
	 * Test user ID
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Action instance being tested
	 *
	 * @var SUPER_Trigger_Action_Base
	 */
	protected $action;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test form
		$this->form_id = wp_insert_post([
			'post_type' => 'super_form',
			'post_status' => 'publish',
			'post_title' => 'Test Form for Actions'
		]);

		// Create test entry
		$this->entry_id = wp_insert_post([
			'post_type' => 'super_contact_entry',
			'post_status' => 'publish',
			'post_title' => 'Test Entry'
		]);

		update_post_meta($this->entry_id, '_super_form_id', $this->form_id);
		update_post_meta($this->entry_id, '_super_contact_entry_data', [
			'name' => 'Test User',
			'email' => 'test@example.com',
			'message' => 'This is a test message'
		]);

		// Create test user
		$this->user_id = $this->factory->user->create([
			'role' => 'subscriber',
			'user_email' => 'subscriber@example.com'
		]);

		// Initialize action instance
		$this->action = $this->get_action_instance();

		// Ensure we're logged in for permission tests
		wp_set_current_user($this->user_id);
	}

	/**
	 * Get the action instance to test
	 *
	 * Must be implemented by child classes
	 *
	 * @return SUPER_Trigger_Action_Base
	 */
	abstract protected function get_action_instance();

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		// Clean up test data
		wp_delete_post($this->form_id, true);
		wp_delete_post($this->entry_id, true);
		wp_delete_user($this->user_id);

		parent::tearDown();
	}

	/**
	 * Get standard test context
	 *
	 * @return array
	 */
	protected function get_test_context() {
		return [
			'form_id' => $this->form_id,
			'entry_id' => $this->entry_id,
			'user_id' => $this->user_id,
			'form_data' => [
				'name' => 'Test User',
				'email' => 'feeling4design@gmail.com',
				'message' => 'This is a test message',
				'phone' => '1234567890',
				'company' => 'Test Company'
			],
			'timestamp' => current_time('mysql'),
			'ip_address' => '127.0.0.1',
			'user_agent' => 'PHPUnit Test'
		];
	}

	/**
	 * Test that action has required methods
	 */
	public function test_action_has_required_methods() {
		$this->assertTrue(method_exists($this->action, 'get_id'), 'Action must have get_id() method');
		$this->assertTrue(method_exists($this->action, 'get_label'), 'Action must have get_label() method');
		$this->assertTrue(method_exists($this->action, 'get_description'), 'Action must have get_description() method');
		$this->assertTrue(method_exists($this->action, 'get_category'), 'Action must have get_category() method');
		$this->assertTrue(method_exists($this->action, 'execute'), 'Action must have execute() method');
	}

	/**
	 * Test that action returns valid metadata
	 */
	public function test_action_metadata() {
		$this->assertNotEmpty($this->action->get_id(), 'Action ID must not be empty');
		$this->assertNotEmpty($this->action->get_label(), 'Action label must not be empty');
		$this->assertNotEmpty($this->action->get_category(), 'Action category must not be empty');

		$this->assertIsString($this->action->get_id());
		$this->assertIsString($this->action->get_label());
		$this->assertIsString($this->action->get_category());
	}

	/**
	 * Test that settings schema is valid
	 */
	public function test_settings_schema() {
		$schema = $this->action->get_settings_schema();

		$this->assertIsArray($schema, 'Settings schema must be an array');

		// Validate each field in schema
		foreach ($schema as $field) {
			$this->assertArrayHasKey('name', $field, 'Schema field must have name');
			$this->assertArrayHasKey('type', $field, 'Schema field must have type');
			$this->assertNotEmpty($field['name'], 'Schema field name must not be empty');
			$this->assertNotEmpty($field['type'], 'Schema field type must not be empty');
		}
	}

	/**
	 * Test that execute returns valid result
	 *
	 * @param array $config Action configuration
	 * @param array $context Event context
	 */
	protected function assert_execute_returns_valid_result($config, $context = null) {
		if ($context === null) {
			$context = $this->get_test_context();
		}

		$result = $this->action->execute($context, $config);

		$this->assertIsArray($result, 'Execute must return an array');
		$this->assertArrayHasKey('success', $result, 'Result must have success key');
		$this->assertIsBool($result['success'], 'Success must be boolean');
	}

	/**
	 * Test that action validates config correctly
	 */
	protected function assert_config_validation_works($valid_config, $invalid_config) {
		// Valid config should pass
		$valid_result = $this->action->validate_config($valid_config);
		if (is_wp_error($valid_result)) {
			$this->fail('Valid config should pass validation: ' . $valid_result->get_error_message());
		}

		// Invalid config should fail
		$invalid_result = $this->action->validate_config($invalid_config);
		$this->assertTrue(is_wp_error($invalid_result), 'Invalid config should fail validation');
	}

	/**
	 * Test tag replacement in action
	 */
	protected function assert_tag_replacement_works($input, $expected_output, $context = null) {
		if ($context === null) {
			$context = $this->get_test_context();
		}

		$reflection = new ReflectionClass($this->action);
		$method = $reflection->getMethod('replace_tags');
		$method->setAccessible(true);

		$result = $method->invokeArgs($this->action, [$input, $context]);
		$this->assertEquals($expected_output, $result, 'Tag replacement should work correctly');
	}

	/**
	 * Helper: Create mock email for testing
	 *
	 * @return string Email address
	 */
	protected function get_test_email() {
		return 'test-' . uniqid() . '@example.com';
	}

	/**
	 * Helper: Create mock URL for testing
	 *
	 * @return string URL
	 */
	protected function get_test_url() {
		return 'https://example.com/test-' . uniqid();
	}

	/**
	 * Helper: Capture WordPress errors
	 *
	 * @return array Captured errors
	 */
	protected function capture_errors() {
		$errors = [];

		set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
			$errors[] = [
				'level' => $errno,
				'message' => $errstr,
				'file' => $errfile,
				'line' => $errline
			];
		});

		return $errors;
	}

	/**
	 * Helper: Stop capturing errors
	 */
	protected function stop_capturing_errors() {
		restore_error_handler();
	}
}
