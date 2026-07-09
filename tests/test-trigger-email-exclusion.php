<?php
/**
 * Test per-email field exclusion in the trigger email path.
 *
 * Regression coverage for the bug where SUPER_Triggers::retrieve_email_loop_html()
 * hardcoded the filter `type` to 'admin' for every email it assembled, which made the
 * per-email exclude options unreachable for confirmation emails:
 *   - exclude = 1 (exclude from confirmation) leaked the field/attachment into the
 *     confirmation email anyway.
 *   - exclude = 3 (exclude from admin) dropped the field/attachment from ALL emails.
 *
 * The role is now threaded through the _emails entry data as `_email_type` and passed
 * to the `super_before_email_loop_data_filter` consumers (here the Signature add-on).
 *
 * @package Super_Forms\Tests
 */

require_once 'class-test-helpers.php';

class Test_Trigger_Email_Exclusion extends SUPER_Test_Helpers {

	/**
	 * A data URI value that the Signature add-on recognizes as a drawn signature.
	 */
	const SIGNATURE_VALUE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

	public function set_up() {
		parent::set_up();
		// Load the Signature add-on so its `super_before_email_loop_data_filter` consumer
		// (the sole registered consumer) is active. The file self-registers exactly once.
		require_once dirname( __DIR__ ) . '/src/add-ons/super-forms-signature/super-forms-signature.php';
	}

	/**
	 * Assemble the email loop for a single signature field.
	 *
	 * @param int         $exclude    Field exclude setting (0|1|2|3).
	 * @param string|null $email_type Role to thread as `_email_type`, or null to omit it.
	 * @return array retrieve_email_loop_html() result.
	 */
	private function run_loop( $exclude, $email_type = null ) {
		$data = array(
			'signature' => array(
				'name'    => 'signature',
				'label'   => 'Signature:',
				'type'    => 'signature',
				'value'   => self::SIGNATURE_VALUE,
				'exclude' => $exclude,
			),
		);
		$settings = array();
		$options  = array(
			'loop'          => '{loop_label} {loop_value}',
			'exclude_empty' => 'false',
			'exclude'       => array(
				'enabled'        => 'false',
				'exclude_fields' => array(),
			),
		);
		if ( null !== $email_type ) {
			$options['_email_type'] = $email_type;
		}
		return SUPER_Triggers::retrieve_email_loop_html( $data, $settings, $options );
	}

	public function test_exclude_from_confirmation_keeps_admin_drops_confirm() {
		// exclude = 1 -> keep in admin email, drop from confirmation email.
		$confirm = $this->run_loop( 1, 'confirm' );
		$this->assertCount( 0, $confirm['string_attachments'], 'Confirmation email must not embed the signature when exclude=1.' );
		$this->assertStringNotContainsString( 'cid:', $confirm['email_loop'], 'Confirmation loop must not reference the signature image when exclude=1.' );

		$admin = $this->run_loop( 1, 'admin' );
		$this->assertCount( 1, $admin['string_attachments'], 'Admin email must keep the signature when exclude=1.' );
		$this->assertSame( 'signature.png', $admin['string_attachments'][0]['filename'] );
		$this->assertStringContainsString( 'cid:', $admin['email_loop'], 'Admin loop must reference the signature image when exclude=1.' );
	}

	public function test_exclude_from_admin_keeps_confirm_drops_admin() {
		// exclude = 3 -> drop from admin email, keep in confirmation email (not all emails).
		$admin = $this->run_loop( 3, 'admin' );
		$this->assertCount( 0, $admin['string_attachments'], 'Admin email must not embed the signature when exclude=3.' );
		$this->assertStringNotContainsString( 'cid:', $admin['email_loop'], 'Admin loop must not reference the signature image when exclude=3.' );

		$confirm = $this->run_loop( 3, 'confirm' );
		$this->assertCount( 1, $confirm['string_attachments'], 'Confirmation email must keep the signature when exclude=3.' );
		$this->assertSame( 'signature.png', $confirm['string_attachments'][0]['filename'] );
		$this->assertStringContainsString( 'cid:', $confirm['email_loop'], 'Confirmation loop must reference the signature image when exclude=3.' );
	}

	public function test_no_exclusion_delivers_to_both_roles() {
		// exclude = 0 -> both emails embed the signature.
		$admin   = $this->run_loop( 0, 'admin' );
		$confirm = $this->run_loop( 0, 'confirm' );
		$this->assertCount( 1, $admin['string_attachments'], 'Admin email must embed the signature when not excluded.' );
		$this->assertCount( 1, $confirm['string_attachments'], 'Confirmation email must embed the signature when not excluded.' );
	}

	public function test_missing_email_type_defaults_to_admin_role() {
		// No `_email_type` on the entry -> preserve prior behavior (treat as admin email).
		$default = $this->run_loop( 1, null );
		$this->assertCount( 1, $default['string_attachments'], 'Entries without _email_type must behave as the admin role.' );

		$excluded = $this->run_loop( 3, null );
		$this->assertCount( 0, $excluded['string_attachments'], 'Entries without _email_type must honor admin exclusion (exclude=3).' );
	}
}
