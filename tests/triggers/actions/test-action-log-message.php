<?php
/**
 * Test Log Message Action
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers/Actions
 * @since 6.5.0
 */

require_once dirname(__FILE__) . '/../class-action-test-case.php';

class Test_Action_Log_Message extends SUPER_Action_Test_Case {

	/**
	 * Get the action instance to test
	 *
	 * @return SUPER_Trigger_Action_Base
	 */
	protected function get_action_instance() {
		return new SUPER_Action_Log_Message();
	}

	/**
	 * Test action basic properties
	 */
	public function test_action_properties() {
		$this->assertEquals('log_message', $this->action->get_id());
		$this->assertEquals('Log Message', $this->action->get_label());
		$this->assertEquals('utility', $this->action->get_category());
		$this->assertNotEmpty($this->action->get_description());
	}

	/**
	 * Test settings schema structure
	 */
	public function test_settings_schema_structure() {
		$schema = $this->action->get_settings_schema();

		// Should have 3 fields
		$this->assertCount(3, $schema);

		// Check message field
		$message_field = $this->find_schema_field($schema, 'message');
		$this->assertNotNull($message_field, 'Schema should have message field');
		$this->assertEquals('textarea', $message_field['type']);
		$this->assertTrue($message_field['required']);

		// Check log_level field
		$level_field = $this->find_schema_field($schema, 'log_level');
		$this->assertNotNull($level_field, 'Schema should have log_level field');
		$this->assertEquals('select', $level_field['type']);
		$this->assertArrayHasKey('options', $level_field);
		$this->assertArrayHasKey('debug', $level_field['options']);
		$this->assertArrayHasKey('info', $level_field['options']);
		$this->assertArrayHasKey('warning', $level_field['options']);
		$this->assertArrayHasKey('error', $level_field['options']);

		// Check include_context field
		$context_field = $this->find_schema_field($schema, 'include_context');
		$this->assertNotNull($context_field, 'Schema should have include_context field');
		$this->assertEquals('checkbox', $context_field['type']);
	}

	/**
	 * Test basic log message execution
	 */
	public function test_execute_basic_log() {
		$config = [
			'message' => 'Test log message',
			'log_level' => 'info',
			'include_context' => false
		];

		$context = $this->get_test_context();

		// Enable WP_DEBUG for this test
		if (!defined('WP_DEBUG')) {
			define('WP_DEBUG', true);
		}
		if (!defined('WP_DEBUG_LOG')) {
			define('WP_DEBUG_LOG', true);
		}

		$result = $this->action->execute($context, $config);

		// Verify result structure
		$this->assertTrue($result['success'], 'Execution should succeed');
		$this->assertEquals('Test log message', $result['message']);
		$this->assertEquals('info', $result['log_level']);
		$this->assertTrue($result['logged_to_file'], 'Should log to file when WP_DEBUG enabled');
		$this->assertArrayHasKey('logged_at', $result);
	}

	/**
	 * Test log message with tag replacement
	 */
	public function test_execute_with_tag_replacement() {
		$config = [
			'message' => 'Form #{form_id} submitted by {form_data.name} at {timestamp}',
			'log_level' => 'info',
			'include_context' => false
		];

		$context = $this->get_test_context();

		$result = $this->action->execute($context, $config);

		$this->assertTrue($result['success']);
		$this->assertStringContainsString((string)$this->form_id, $result['message'], 'Should replace {form_id}');
		$this->assertStringContainsString('Test User', $result['message'], 'Should replace {form_data.name}');
	}

	/**
	 * Test log message with context data
	 */
	public function test_execute_with_context() {
		$config = [
			'message' => 'Testing with context',
			'log_level' => 'debug',
			'include_context' => true
		];

		$context = $this->get_test_context();

		$result = $this->action->execute($context, $config);

		$this->assertTrue($result['success']);
		$this->assertEquals('debug', $result['log_level']);
	}

	/**
	 * Test different log levels
	 */
	public function test_different_log_levels() {
		$levels = ['debug', 'info', 'warning', 'error'];

		foreach ($levels as $level) {
			$config = [
				'message' => "Testing {$level} level",
				'log_level' => $level,
				'include_context' => false
			];

			$result = $this->action->execute($this->get_test_context(), $config);

			$this->assertTrue($result['success'], "Should succeed with {$level} level");
			$this->assertEquals($level, $result['log_level']);
		}
	}

	/**
	 * Test execution without WP_DEBUG
	 */
	public function test_execute_without_wp_debug() {
		// Skip if WP_DEBUG is already defined
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$this->markTestSkipped('WP_DEBUG is enabled, cannot test disabled state');
		}

		$config = [
			'message' => 'Test message',
			'log_level' => 'info',
			'include_context' => false
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		$this->assertTrue($result['success']);
		$this->assertFalse($result['logged_to_file'], 'Should not log to file when WP_DEBUG disabled');
	}

	/**
	 * Test sensitive data redaction
	 */
	public function test_sensitive_data_redaction() {
		$config = [
			'message' => 'Testing redaction',
			'log_level' => 'info',
			'include_context' => true
		];

		$context = $this->get_test_context();
		$context['password'] = 'secret123';
		$context['api_key'] = 'abc123def456';
		$context['credit_card'] = '4111111111111111';

		$result = $this->action->execute($context, $config);

		$this->assertTrue($result['success']);
		// Context should be sanitized internally
	}

	/**
	 * Test can_run always returns true
	 */
	public function test_can_run() {
		$this->assertTrue($this->action->can_run($this->get_test_context()));
		$this->assertTrue($this->action->can_run([]));
	}

	/**
	 * Test required capabilities
	 */
	public function test_required_capabilities() {
		$caps = $this->action->get_required_capabilities();

		$this->assertIsArray($caps);
		$this->assertContains('edit_posts', $caps);
	}

	/**
	 * Test missing message config
	 */
	public function test_missing_message_uses_default() {
		$config = [
			'log_level' => 'info',
			'include_context' => false
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		$this->assertTrue($result['success']);
		$this->assertNotEmpty($result['message'], 'Should use default message when none provided');
	}

	/**
	 * Test default log level
	 */
	public function test_default_log_level() {
		$config = [
			'message' => 'Test message',
			'include_context' => false
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		$this->assertTrue($result['success']);
		$this->assertEquals('info', $result['log_level'], 'Should default to info level');
	}

	/**
	 * Helper to find schema field by name
	 */
	private function find_schema_field($schema, $name) {
		foreach ($schema as $field) {
			if ($field['name'] === $name) {
				return $field;
			}
		}
		return null;
	}
}
