<?php
/**
 * Test Send Email Action
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers/Actions
 * @since 6.5.0
 */

require_once dirname(__FILE__) . '/../class-action-test-case.php';

class Test_Action_Send_Email extends SUPER_Action_Test_Case {

	/**
	 * Get the action instance to test
	 *
	 * @return SUPER_Trigger_Action_Base
	 */
	protected function get_action_instance() {
		return new SUPER_Action_Send_Email();
	}

	/**
	 * Test action basic properties
	 */
	public function test_action_properties() {
		$this->assertEquals('send_email', $this->action->get_id());
		$this->assertEquals('Send Email', $this->action->get_label());
		$this->assertEquals('notification', $this->action->get_category());
		$this->assertNotEmpty($this->action->get_description());
	}

	/**
	 * Test settings schema has required email fields
	 */
	public function test_settings_schema_has_email_fields() {
		$schema = $this->action->get_settings_schema();

		$field_names = array_column($schema, 'name');

		$this->assertContains('to', $field_names, 'Schema should have "to" field');
		$this->assertContains('subject', $field_names, 'Schema should have "subject" field');
		$this->assertContains('body', $field_names, 'Schema should have "body" field');
	}

	/**
	 * Test basic email sending
	 */
	public function test_send_basic_email() {
		// Reset wp_mail
		reset_phpmailer_instance();

		$config = [
			'to' => 'feeling4design@gmail.com',
			'subject' => 'Test Subject',
			'body' => 'Test email body',
			'from' => 'sender@example.com',
			'from_name' => 'Test Sender'
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		$this->assertTrue($result['success'], 'Email send should succeed');
		$this->assertArrayHasKey('to', $result);
		$this->assertEquals('feeling4design@gmail.com', $result['to']);
	}

	/**
	 * Test email with tag replacement
	 */
	public function test_send_email_with_tags() {
		reset_phpmailer_instance();

		$config = [
			'to' => '{form_data.email}',
			'subject' => 'Hello {form_data.name}',
			'body' => 'Form #{form_id} submitted',
			'from' => 'noreply@example.com'
		];

		$context = $this->get_test_context();

		$result = $this->action->execute($context, $config);

		$this->assertTrue($result['success']);
		$this->assertEquals('feeling4design@gmail.com', $result['to'], 'Should replace {form_data.email}');
		$this->assertStringContainsString('Hello Test User', $result['subject'], 'Should replace {form_data.name}');
	}

	/**
	 * Test email validation
	 */
	public function test_invalid_email_address() {
		$config = [
			'to' => 'invalid-email',
			'subject' => 'Test',
			'body' => 'Test'
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		// Action returns WP_Error for invalid email
		$this->assertTrue(is_wp_error($result), 'Should return WP_Error with invalid email');
		$this->assertEquals('invalid_email', $result->get_error_code());
	}

	/**
	 * Test multiple recipients
	 */
	public function test_multiple_recipients() {
		reset_phpmailer_instance();

		$config = [
			'to' => 'user1@example.com, user2@example.com',
			'subject' => 'Test Multiple',
			'body' => 'Test message'
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		$this->assertTrue($result['success']);
	}

	/**
	 * Test email with attachments (if supported)
	 */
	public function test_email_with_attachments() {
		reset_phpmailer_instance();

		$schema = $this->action->get_settings_schema();
		$has_attachments = in_array('attachments', array_column($schema, 'name'));

		if (!$has_attachments) {
			$this->markTestSkipped('Action does not support attachments');
		}

		$config = [
			'to' => 'feeling4design@gmail.com',
			'subject' => 'Test with Attachment',
			'body' => 'See attachment',
			'attachments' => ['/tmp/test.txt']
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		$this->assertIsArray($result);
	}

	/**
	 * Test HTML vs plain text emails
	 */
	public function test_html_email() {
		reset_phpmailer_instance();

		$schema = $this->action->get_settings_schema();
		$has_html_field = in_array('html', array_column($schema, 'name'));

		if (!$has_html_field) {
			$this->markTestSkipped('Action does not have HTML field');
		}

		$config = [
			'to' => 'feeling4design@gmail.com',
			'subject' => 'HTML Email',
			'body' => '<h1>Hello</h1><p>This is HTML</p>',
			'html' => true
		];

		$result = $this->action->execute($this->get_test_context(), $config);

		$this->assertTrue($result['success']);
	}

	/**
	 * Test required capabilities
	 */
	public function test_required_capabilities() {
		$caps = $this->action->get_required_capabilities();

		$this->assertIsArray($caps);
		$this->assertNotEmpty($caps, 'Send email should require capabilities');
	}

	/**
	 * Test can_run checks
	 */
	public function test_can_run() {
		// Should run with valid context
		$this->assertTrue($this->action->can_run($this->get_test_context()));
	}
}
