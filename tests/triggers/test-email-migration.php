<?php
/**
 * Tests for Email Trigger Migration
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 */

class Test_Email_Migration extends WP_UnitTestCase {

	/**
	 * Test form ID
	 *
	 * @var int
	 */
	protected static $form_id;

	/**
	 * Set up test fixtures
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Create test form with legacy email settings
		self::$form_id = wp_insert_post( array(
			'post_type'   => 'super_form',
			'post_title'  => 'Test Form for Email Migration',
			'post_status' => 'publish',
		) );

		// Add legacy form settings with admin and confirmation emails
		update_post_meta( self::$form_id, '_super_form_settings', array(
			'send'                => 'yes',
			'header_to'           => '{option_admin_email}',
			'header_from_type'    => 'default',
			'header_from'         => 'test@example.com',
			'header_from_name'    => 'Test Form',
			'header_subject'      => 'New submission',
			'email_body'          => '<p>Hello Admin,</p><p>{loop_fields}</p><p>Best regards</p>',
			'email_loop'          => '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>',
			'header_cc'           => 'cc@example.com',
			'header_bcc'          => 'bcc@example.com',
			'confirm'             => 'yes',
			'confirm_to'          => '{email}',
			'confirm_from_type'   => 'custom',
			'confirm_from'        => 'noreply@example.com',
			'confirm_from_name'   => 'My Website',
			'confirm_subject'     => 'Thank you for your submission',
			'confirm_body'        => '<p>Dear {name},</p><p>Thank you!</p>',
			'confirm_email_loop'  => '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>',
		) );
	}

	/**
	 * Tear down test fixtures
	 */
	public static function tearDownAfterClass(): void {
		wp_delete_post( self::$form_id, true );
		parent::tearDownAfterClass();
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		$this->cleanup_test_migration_data( self::$form_id );
		parent::tearDown();
	}

	/**
	 * Helper to clean up migration data for a specific form
	 *
	 * @param int $form_id Form ID to clean up
	 */
	protected function cleanup_test_migration_data( $form_id ) {
		global $wpdb;

		// Clean up triggers created during test
		$wpdb->query( "DELETE FROM {$wpdb->prefix}superforms_trigger_actions WHERE trigger_id IN (SELECT id FROM {$wpdb->prefix}superforms_triggers WHERE scope_id = " . absint( $form_id ) . ")" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}superforms_triggers WHERE scope_id = " . absint( $form_id ) );

		// Clean up meta
		delete_post_meta( $form_id, SUPER_Email_Trigger_Migration::EMAIL_TRIGGER_MAP );

		// Reset migration state
		delete_option( SUPER_Email_Trigger_Migration::MIGRATION_OPTION );
	}

	/**
	 * Test migration state initialization
	 */
	public function test_get_state_returns_default() {
		delete_option( SUPER_Email_Trigger_Migration::MIGRATION_OPTION );

		$state = SUPER_Email_Trigger_Migration::get_state();

		$this->assertIsArray( $state );
		$this->assertEquals( 'not_started', $state['status'] );
		$this->assertEquals( 0, $state['forms_migrated'] );
	}

	/**
	 * Test migrating a single form
	 */
	public function test_migrate_form() {
		$result = SUPER_Email_Trigger_Migration::migrate_form( self::$form_id );

		$this->assertIsArray( $result );
		$this->assertEquals( self::$form_id, $result['form_id'] );
		$this->assertEquals( 2, $result['emails_migrated'] ); // Admin + Confirmation
		$this->assertArrayHasKey( 'admin', $result['trigger_map'] );
		$this->assertArrayHasKey( 'confirmation', $result['trigger_map'] );

		// Verify triggers were created
		$admin_trigger_id = $result['trigger_map']['admin'];
		$trigger = SUPER_Trigger_DAL::get_trigger( $admin_trigger_id );

		$this->assertEquals( 'Admin Email', $trigger['trigger_name'] );
		$this->assertEquals( 'form.submitted', $trigger['event_id'] );
		$this->assertEquals( 'form', $trigger['scope'] );
		$this->assertEquals( self::$form_id, $trigger['scope_id'] );

		// Verify action was created
		$actions = SUPER_Trigger_DAL::get_actions( $admin_trigger_id );
		$this->assertCount( 1, $actions );
		$this->assertEquals( 'send_email', $actions[0]['action_type'] );

		// Verify action config
		$config = $actions[0]['action_config'];
		$this->assertEquals( '{option_admin_email}', $config['to'] );
		$this->assertEquals( 'New submission', $config['subject'] );
		$this->assertEquals( 'legacy_html', $config['body_type'] );
		$this->assertEquals( 'cc@example.com', $config['cc'] );
		$this->assertEquals( 'bcc@example.com', $config['bcc'] );
	}

	/**
	 * Test confirmation email migration
	 */
	public function test_migrate_confirmation_email() {
		$result = SUPER_Email_Trigger_Migration::migrate_form( self::$form_id );

		$confirm_trigger_id = $result['trigger_map']['confirmation'];
		$trigger = SUPER_Trigger_DAL::get_trigger( $confirm_trigger_id );

		$this->assertEquals( 'Confirmation Email', $trigger['trigger_name'] );

		$actions = SUPER_Trigger_DAL::get_actions( $confirm_trigger_id );
		$config = $actions[0]['action_config'];

		$this->assertEquals( '{email}', $config['to'] );
		$this->assertEquals( 'Thank you for your submission', $config['subject'] );
		$this->assertEquals( 'noreply@example.com', $config['from'] );
		$this->assertEquals( 'My Website', $config['from_name'] );
	}

	/**
	 * Test email reminder migration
	 */
	public function test_migrate_email_reminder() {
		// Add reminder settings to form
		$settings = get_post_meta( self::$form_id, '_super_form_settings', true );
		$settings['email_reminder_1'] = 'yes';
		$settings['email_reminder_1_to'] = '{email}';
		$settings['email_reminder_1_subject'] = 'Reminder: Follow up';
		$settings['email_reminder_1_body'] = '<p>This is a reminder.</p>';
		$settings['email_reminder_1_base_date'] = 'appointment_date';
		$settings['email_reminder_1_date_offset'] = '1';
		$settings['email_reminder_1_time_method'] = 'fixed';
		$settings['email_reminder_1_time_fixed'] = '09:00';
		update_post_meta( self::$form_id, '_super_form_settings', $settings );

		// Re-delete trigger map to force fresh migration
		delete_post_meta( self::$form_id, SUPER_Email_Trigger_Migration::EMAIL_TRIGGER_MAP );

		$result = SUPER_Email_Trigger_Migration::migrate_form( self::$form_id );

		$this->assertEquals( 3, $result['emails_migrated'] ); // Admin + Confirmation + Reminder
		$this->assertArrayHasKey( 'reminder_1', $result['trigger_map'] );

		$reminder_trigger_id = $result['trigger_map']['reminder_1'];
		$trigger = SUPER_Trigger_DAL::get_trigger( $reminder_trigger_id );

		$this->assertEquals( 'Email Reminder #1', $trigger['trigger_name'] );
		$this->assertEquals( 'entry.created', $trigger['event_id'] );

		// Should have 2 actions: delay + send_email
		$actions = SUPER_Trigger_DAL::get_actions( $reminder_trigger_id, false );
		$this->assertCount( 2, $actions );

		// Find delay action
		$delay_action = null;
		$email_action = null;
		foreach ( $actions as $action ) {
			if ( $action['action_type'] === 'delay_execution' ) {
				$delay_action = $action;
			} elseif ( $action['action_type'] === 'send_email' ) {
				$email_action = $action;
			}
		}

		$this->assertNotNull( $delay_action );
		$this->assertNotNull( $email_action );

		// Verify delay config
		$delay_config = $delay_action['action_config'];
		$this->assertEquals( 'relative_to_field', $delay_config['delay_type'] );
		$this->assertEquals( 'appointment_date', $delay_config['delay_field'] );
		$this->assertEquals( 1, $delay_config['delay_days'] );
	}

	/**
	 * Test count forms needing migration
	 */
	public function test_count_forms_needing_migration() {
		// Clear any existing trigger map
		delete_post_meta( self::$form_id, SUPER_Email_Trigger_Migration::EMAIL_TRIGGER_MAP );

		$count = SUPER_Email_Trigger_Migration::count_forms_needing_migration();

		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test get forms needing migration
	 */
	public function test_get_forms_needing_migration() {
		delete_post_meta( self::$form_id, SUPER_Email_Trigger_Migration::EMAIL_TRIGGER_MAP );

		$forms = SUPER_Email_Trigger_Migration::get_forms_needing_migration( 0, 100 );

		$this->assertContains( self::$form_id, $forms );
	}

	/**
	 * Test form is excluded after migration
	 */
	public function test_form_excluded_after_migration() {
		// First migrate the form
		SUPER_Email_Trigger_Migration::migrate_form( self::$form_id );

		// Now it should not appear in forms needing migration
		$forms = SUPER_Email_Trigger_Migration::get_forms_needing_migration( 0, 100 );

		$this->assertNotContains( self::$form_id, $forms );
	}

	/**
	 * Test migration state tracking
	 */
	public function test_update_state() {
		SUPER_Email_Trigger_Migration::update_state( array(
			'status'     => 'in_progress',
			'started_at' => '2025-01-01 00:00:00',
		) );

		$state = SUPER_Email_Trigger_Migration::get_state();

		$this->assertEquals( 'in_progress', $state['status'] );
		$this->assertEquals( '2025-01-01 00:00:00', $state['started_at'] );
	}

	/**
	 * Test is_complete check
	 */
	public function test_is_complete() {
		$this->assertFalse( SUPER_Email_Trigger_Migration::is_complete() );

		SUPER_Email_Trigger_Migration::update_state( array(
			'status' => 'completed',
		) );

		$this->assertTrue( SUPER_Email_Trigger_Migration::is_complete() );
	}

	/**
	 * Test get trigger for email
	 */
	public function test_get_trigger_for_email() {
		$result = SUPER_Email_Trigger_Migration::migrate_form( self::$form_id );

		$admin_trigger = SUPER_Email_Trigger_Migration::get_trigger_for_email( self::$form_id, 'admin' );
		$confirm_trigger = SUPER_Email_Trigger_Migration::get_trigger_for_email( self::$form_id, 'confirmation' );

		$this->assertEquals( $result['trigger_map']['admin'], $admin_trigger );
		$this->assertEquals( $result['trigger_map']['confirmation'], $confirm_trigger );

		// Non-existent type
		$nonexistent = SUPER_Email_Trigger_Migration::get_trigger_for_email( self::$form_id, 'nonexistent' );
		$this->assertNull( $nonexistent );
	}

	/**
	 * Test disabled email is migrated as inactive trigger
	 *
	 * Disabled emails should still be migrated so users can re-enable them later.
	 * The trigger's enabled flag should be set to 0.
	 */
	public function test_disabled_email_migrated_as_inactive() {
		// Create form with admin email disabled but confirmation enabled
		$form_id = wp_insert_post( array(
			'post_type'   => 'super_form',
			'post_title'  => 'Test Form Disabled Email',
			'post_status' => 'publish',
		) );

		update_post_meta( $form_id, '_super_form_settings', array(
			'send'            => 'no',  // Disabled
			'header_to'       => 'admin@example.com',
			'header_subject'  => 'Admin notification',
			'email_body'      => 'New submission received',
			'confirm'         => 'yes', // Enabled
			'confirm_to'      => '{email}',
			'confirm_subject' => 'Thanks',
			'confirm_body'    => 'Thank you',
		) );

		$result = SUPER_Email_Trigger_Migration::migrate_form( $form_id );

		// Both emails should be migrated
		$this->assertEquals( 2, $result['emails_migrated'] );
		$this->assertArrayHasKey( 'admin', $result['trigger_map'] );
		$this->assertArrayHasKey( 'confirmation', $result['trigger_map'] );

		// Verify admin trigger is disabled
		$admin_trigger = SUPER_Trigger_DAL::get_trigger( $result['trigger_map']['admin'] );
		$this->assertEquals( 0, $admin_trigger['enabled'], 'Disabled email should create inactive trigger' );

		// Verify confirmation trigger is enabled
		$confirm_trigger = SUPER_Trigger_DAL::get_trigger( $result['trigger_map']['confirmation'] );
		$this->assertEquals( 1, $confirm_trigger['enabled'], 'Enabled email should create active trigger' );

		// Cleanup
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}superforms_trigger_actions WHERE trigger_id IN (SELECT id FROM {$wpdb->prefix}superforms_triggers WHERE scope_id = $form_id)" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}superforms_triggers WHERE scope_id = $form_id" );
		wp_delete_post( $form_id, true );
	}

	/**
	 * Test legacy body is built correctly
	 */
	public function test_legacy_body_built_correctly() {
		$result = SUPER_Email_Trigger_Migration::migrate_form( self::$form_id );

		$admin_trigger_id = $result['trigger_map']['admin'];
		$actions = SUPER_Trigger_DAL::get_actions( $admin_trigger_id );
		$config = $actions[0]['action_config'];

		// Body should contain the original body with loop_fields
		$this->assertStringContainsString( 'Hello Admin', $config['body'] );
		$this->assertStringContainsString( '{loop_fields}', $config['body'] );
		$this->assertStringContainsString( 'Best regards', $config['body'] );

		// Loop settings should be preserved
		$this->assertEquals( '<table cellpadding="5">', $config['loop_open'] );
		$this->assertStringContainsString( '{loop_label}', $config['loop'] );
		$this->assertStringContainsString( '{loop_value}', $config['loop'] );
		$this->assertEquals( '</table>', $config['loop_close'] );
	}

}
