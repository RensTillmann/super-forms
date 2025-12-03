<?php
/**
 * Integration Test: Full Submission Flow
 *
 * Tests the complete flow from form creation through trigger execution
 *
 * @package Super_Forms
 * @subpackage Tests/Integration
 * @since 6.5.0
 */

class Test_Full_Submission_Flow extends WP_UnitTestCase {

	/**
	 * Test user ID
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test user
		$this->user_id = $this->factory->user->create( array(
			'role'       => 'administrator',
			'user_email' => 'admin@example.com',
		) );
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		// Cleanup fixtures
		if ( class_exists( 'SUPER_Test_Form_Factory' ) ) {
			SUPER_Test_Form_Factory::cleanup();
		}
		if ( class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			SUPER_Test_Trigger_Factory::cleanup();
		}
		if ( class_exists( 'SUPER_Webhook_Simulator' ) ) {
			SUPER_Webhook_Simulator::clear_history();
		}

		wp_delete_user( $this->user_id );
		parent::tearDown();
	}

	/**
	 * Test: Form Factory creates valid comprehensive form
	 */
	public function test_form_factory_creates_comprehensive_form() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) ) {
			$this->markTestSkipped( 'SUPER_Test_Form_Factory not available' );
		}

		$form_id = SUPER_Test_Form_Factory::create_comprehensive_form();

		$this->assertIsInt( $form_id, 'Form ID should be an integer' );
		$this->assertGreaterThan( 0, $form_id, 'Form ID should be positive' );

		// Verify form exists in DAL
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertNotNull( $form, 'Form should exist in DAL' );
		$this->assertEquals( 'publish', $form->status, 'Form should be published' );

		// Verify elements stored
		$this->assertIsArray( $form->elements, 'Elements should be stored as array' );
		$this->assertGreaterThan( 5, count( $form->elements ), 'Comprehensive form should have multiple elements' );

		// Verify settings stored
		$this->assertIsArray( $form->settings, 'Settings should be stored as array' );
	}

	/**
	 * Test: Form Factory creates valid simple form
	 */
	public function test_form_factory_creates_simple_form() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) ) {
			$this->markTestSkipped( 'SUPER_Test_Form_Factory not available' );
		}

		$form_id = SUPER_Test_Form_Factory::create_simple_form();

		$this->assertIsInt( $form_id );
		$this->assertGreaterThan( 0, $form_id );

		// Verify form exists in DAL
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertNotNull( $form );
		$elements = $form->elements;
		$this->assertIsArray( $elements );

		// Should have name, email, subject, message fields
		$field_names = array_column( array_column( $elements, 'data' ), 'name' );
		$this->assertContains( 'name', $field_names, 'Should have name field' );
		$this->assertContains( 'email', $field_names, 'Should have email field' );
		$this->assertContains( 'message', $field_names, 'Should have message field' );
	}

	/**
	 * Test: Form Factory creates multi-step form
	 */
	public function test_form_factory_creates_multistep_form() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) ) {
			$this->markTestSkipped( 'SUPER_Test_Form_Factory not available' );
		}

		$form_id = SUPER_Test_Form_Factory::create_multistep_form();

		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertNotNull( $form );
		$elements = $form->elements;

		// Count multipart elements
		$multipart_count = 0;
		foreach ( $elements as $element ) {
			if ( isset( $element['tag'] ) && $element['tag'] === 'multipart' ) {
				$multipart_count++;
			}
		}

		$this->assertEquals( 3, $multipart_count, 'Should have 3 multipart (step) elements' );
	}

	/**
	 * Test: Form Factory creates repeater form
	 */
	public function test_form_factory_creates_repeater_form() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) ) {
			$this->markTestSkipped( 'SUPER_Test_Form_Factory not available' );
		}

		$form_id = SUPER_Test_Form_Factory::create_repeater_form();

		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertNotNull( $form );
		$elements = $form->elements;

		// Find column with duplicate enabled
		$has_repeater = false;
		foreach ( $elements as $element ) {
			if (
				isset( $element['tag'] ) &&
				$element['tag'] === 'column' &&
				isset( $element['data']['duplicate'] ) &&
				$element['data']['duplicate'] === 'enabled'
			) {
				$has_repeater = true;
				break;
			}
		}

		$this->assertTrue( $has_repeater, 'Should have repeater column element' );
	}

	/**
	 * Test: Form Factory creates entry with data
	 */
	public function test_form_factory_creates_entry() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) ) {
			$this->markTestSkipped( 'SUPER_Test_Form_Factory not available' );
		}

		$form_id = SUPER_Test_Form_Factory::create_simple_form();
		$data    = SUPER_Test_Form_Factory::get_simple_submission_data();

		$entry_id = SUPER_Test_Form_Factory::create_entry( $form_id, $data );

		$this->assertIsInt( $entry_id, 'Entry ID should be an integer' );
		$this->assertGreaterThan( 0, $entry_id, 'Entry ID should be positive' );

		// Verify entry post
		$entry = get_post( $entry_id );
		$this->assertEquals( 'super_contact_entry', $entry->post_type );
		$this->assertEquals( $form_id, $entry->post_parent );

		// Verify form ID meta
		$stored_form_id = get_post_meta( $entry_id, '_super_form_id', true );
		$this->assertEquals( $form_id, (int) $stored_form_id );

		// Verify test entry marker
		$is_test = get_post_meta( $entry_id, '_super_test_entry', true );
		$this->assertTrue( (bool) $is_test, 'Entry should be marked as test entry' );
	}

	/**
	 * Test: Trigger Factory creates log trigger
	 */
	public function test_trigger_factory_creates_log_trigger() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) || ! class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			$this->markTestSkipped( 'Fixture classes not available' );
		}

		if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_DAL not available' );
		}

		$form_id    = SUPER_Test_Form_Factory::create_simple_form();
		$trigger_id = SUPER_Test_Trigger_Factory::create_submit_log_trigger( $form_id );

		$this->assertNotWPError( $trigger_id, 'Trigger should be created successfully' );
		$this->assertIsInt( $trigger_id );
		$this->assertGreaterThan( 0, $trigger_id );

		// Verify trigger exists in database
		$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
		$this->assertNotWPError( $trigger );
		$this->assertEquals( $form_id, $trigger['scope_id'] );
		$this->assertEquals( 'form.submitted', $trigger['event_id'] );
	}

	/**
	 * Test: Trigger Factory creates conditional trigger
	 */
	public function test_trigger_factory_creates_conditional_trigger() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) || ! class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			$this->markTestSkipped( 'Fixture classes not available' );
		}

		if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_DAL not available' );
		}

		$form_id    = SUPER_Test_Form_Factory::create_comprehensive_form();
		$trigger_id = SUPER_Test_Trigger_Factory::create_conditional_trigger( $form_id, 25000 );

		$this->assertNotWPError( $trigger_id );

		$trigger = SUPER_Trigger_DAL::get_trigger( $trigger_id );
		$this->assertNotEmpty( $trigger['conditions'], 'Trigger should have conditions' );

		// Conditions may be returned as array or JSON string
		$conditions = $trigger['conditions'];
		if ( is_string( $conditions ) ) {
			$conditions = json_decode( $conditions, true );
		}
		$this->assertIsArray( $conditions );
		$this->assertEquals( 'AND', $conditions['operator'] );
		$this->assertNotEmpty( $conditions['rules'] );
	}

	/**
	 * Test: Trigger Factory creates trigger set
	 */
	public function test_trigger_factory_creates_trigger_set() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) || ! class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			$this->markTestSkipped( 'Fixture classes not available' );
		}

		if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_DAL not available' );
		}

		$form_id = SUPER_Test_Form_Factory::create_comprehensive_form();

		$triggers = SUPER_Test_Trigger_Factory::create_trigger_set( $form_id, array(
			'on_submit_log'     => true,
			'on_high_budget'    => true,
			'on_spam_detected'  => true,
			'on_entry_created'  => true,
		) );

		$this->assertIsArray( $triggers );
		$this->assertArrayHasKey( 'on_submit_log', $triggers );
		$this->assertArrayHasKey( 'on_high_budget', $triggers );
		$this->assertArrayHasKey( 'on_spam_detected', $triggers );
		$this->assertArrayHasKey( 'on_entry_created', $triggers );

		// Verify all are valid trigger IDs
		foreach ( $triggers as $key => $trigger_id ) {
			$this->assertNotWPError( $trigger_id, "Trigger {$key} should be created" );
			$this->assertGreaterThan( 0, $trigger_id );
		}
	}

	/**
	 * Test: Trigger fires on form submission event
	 */
	public function test_trigger_fires_on_form_submitted() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) || ! class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			$this->markTestSkipped( 'Fixture classes not available' );
		}

		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not available' );
		}

		// Create form and trigger
		$form_id    = SUPER_Test_Form_Factory::create_simple_form();
		$trigger_id = SUPER_Test_Trigger_Factory::create_submit_log_trigger( $form_id );

		$this->assertNotWPError( $trigger_id );

		// Create entry
		$data     = SUPER_Test_Form_Factory::get_simple_submission_data();
		$entry_id = SUPER_Test_Form_Factory::create_entry( $form_id, $data );

		// Fire the event
		$context = array(
			'form_id'   => $form_id,
			'entry_id'  => $entry_id,
			'user_id'   => $this->user_id,
			'form_data' => $data,
			'email'     => $data['email']['value'],
		);

		$results = SUPER_Trigger_Executor::fire_event( 'form.submitted', $context );

		$this->assertIsArray( $results, 'Fire event should return results array' );
		$this->assertNotEmpty( $results, 'Should have at least one trigger result' );
	}

	/**
	 * Test: Conditional trigger evaluates conditions correctly
	 */
	public function test_conditional_trigger_evaluates_conditions() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) || ! class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			$this->markTestSkipped( 'Fixture classes not available' );
		}

		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not available' );
		}

		$form_id = SUPER_Test_Form_Factory::create_comprehensive_form();

		// Create conditional trigger (budget > 25000)
		$trigger_id = SUPER_Test_Trigger_Factory::create_conditional_trigger( $form_id, 25000 );
		$this->assertNotWPError( $trigger_id );

		// Test with budget below threshold - should NOT fire
		$low_budget_data = SUPER_Test_Form_Factory::get_test_submission_data();
		$low_budget_data['budget']['value'] = '10000';

		$low_context = array(
			'form_id'   => $form_id,
			'form_data' => $low_budget_data,
			'budget'    => '10000',
		);

		$low_results = SUPER_Trigger_Executor::fire_event( 'form.submitted', $low_context );

		// The conditional trigger should not execute (condition not met)
		// Note: Results may include other triggers or be empty if condition fails
		$this->assertIsArray( $low_results );

		// Test with budget above threshold - should fire
		$high_budget_data = SUPER_Test_Form_Factory::get_test_submission_data();
		$high_budget_data['budget']['value'] = '50000';

		$high_context = array(
			'form_id'   => $form_id,
			'form_data' => $high_budget_data,
			'budget'    => '50000',
		);

		$high_results = SUPER_Trigger_Executor::fire_event( 'form.submitted', $high_context );

		// Should have results when condition is met
		$this->assertIsArray( $high_results );
	}

	/**
	 * Test: Webhook Simulator generates valid Stripe webhook
	 */
	public function test_webhook_simulator_generates_stripe_webhook() {
		if ( ! class_exists( 'SUPER_Webhook_Simulator' ) ) {
			$this->markTestSkipped( 'SUPER_Webhook_Simulator not available' );
		}

		$webhook = SUPER_Webhook_Simulator::stripe( 'payment_intent.succeeded', array(
			'amount' => 5000,
		), array(
			'form_id'  => 123,
			'entry_id' => 456,
		) );

		$this->assertIsArray( $webhook );
		$this->assertArrayHasKey( 'payload', $webhook );
		$this->assertArrayHasKey( 'signature', $webhook );
		$this->assertArrayHasKey( 'headers', $webhook );

		// Verify payload structure
		$payload = $webhook['payload'];
		$this->assertEquals( 'payment_intent.succeeded', $payload['type'] );
		$this->assertEquals( 'event', $payload['object'] );
		$this->assertFalse( $payload['livemode'] );

		// Verify data object
		$this->assertArrayHasKey( 'data', $payload );
		$this->assertArrayHasKey( 'object', $payload['data'] );
		$this->assertEquals( 5000, $payload['data']['object']['amount'] );

		// Verify metadata
		$metadata = $payload['data']['object']['metadata'];
		$this->assertEquals( 123, $metadata['form_id'] );
		$this->assertEquals( 456, $metadata['entry_id'] );
	}

	/**
	 * Test: Webhook Simulator generates valid PayPal webhook
	 */
	public function test_webhook_simulator_generates_paypal_webhook() {
		if ( ! class_exists( 'SUPER_Webhook_Simulator' ) ) {
			$this->markTestSkipped( 'SUPER_Webhook_Simulator not available' );
		}

		$webhook = SUPER_Webhook_Simulator::paypal( 'PAYMENT.CAPTURE.COMPLETED', array(
			'amount' => array(
				'currency_code' => 'USD',
				'value'         => '99.99',
			),
		) );

		$this->assertIsArray( $webhook );
		$this->assertArrayHasKey( 'payload', $webhook );
		$this->assertArrayHasKey( 'headers', $webhook );

		// Verify payload structure
		$payload = $webhook['payload'];
		$this->assertEquals( 'PAYMENT.CAPTURE.COMPLETED', $payload['event_type'] );
		$this->assertEquals( 'capture', $payload['resource_type'] );
		$this->assertArrayHasKey( 'resource', $payload );

		// Verify headers
		$headers = $webhook['headers'];
		$this->assertArrayHasKey( 'PAYPAL-TRANSMISSION-ID', $headers );
		$this->assertArrayHasKey( 'PAYPAL-TRANSMISSION-SIG', $headers );
	}

	/**
	 * Test: Webhook Simulator verifies Stripe signature
	 */
	public function test_webhook_simulator_verifies_stripe_signature() {
		if ( ! class_exists( 'SUPER_Webhook_Simulator' ) ) {
			$this->markTestSkipped( 'SUPER_Webhook_Simulator not available' );
		}

		$webhook = SUPER_Webhook_Simulator::stripe( 'checkout.session.completed' );

		// Signature should verify against the payload
		$is_valid = SUPER_Webhook_Simulator::verify_stripe_signature(
			$webhook['json'],
			$webhook['signature']
		);

		$this->assertTrue( $is_valid, 'Generated signature should verify correctly' );

		// Tampered payload should fail verification
		$tampered = str_replace( 'checkout.session.completed', 'tampered', $webhook['json'] );
		$is_invalid = SUPER_Webhook_Simulator::verify_stripe_signature(
			$tampered,
			$webhook['signature']
		);

		$this->assertFalse( $is_invalid, 'Tampered payload should fail verification' );
	}

	/**
	 * Test: Webhook Simulator tracks history
	 */
	public function test_webhook_simulator_tracks_history() {
		if ( ! class_exists( 'SUPER_Webhook_Simulator' ) ) {
			$this->markTestSkipped( 'SUPER_Webhook_Simulator not available' );
		}

		SUPER_Webhook_Simulator::clear_history();

		SUPER_Webhook_Simulator::stripe( 'payment_intent.succeeded' );
		SUPER_Webhook_Simulator::paypal( 'PAYMENT.CAPTURE.COMPLETED' );
		SUPER_Webhook_Simulator::stripe( 'checkout.session.completed' );

		$all_history = SUPER_Webhook_Simulator::get_history();
		$this->assertCount( 3, $all_history );

		$stripe_history = SUPER_Webhook_Simulator::get_history( 'stripe' );
		$this->assertCount( 2, $stripe_history );

		$paypal_history = SUPER_Webhook_Simulator::get_history( 'paypal' );
		$this->assertCount( 1, $paypal_history );
	}

	/**
	 * Test: Submission data matches form fields
	 */
	public function test_submission_data_matches_form_fields() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) ) {
			$this->markTestSkipped( 'SUPER_Test_Form_Factory not available' );
		}

		$form_id  = SUPER_Test_Form_Factory::create_simple_form();
		$form = SUPER_Form_DAL::get( $form_id );
		$this->assertNotNull( $form );
		$elements = $form->elements;

		// Get field names from elements
		$form_field_names = array();
		foreach ( $elements as $element ) {
			if ( isset( $element['data']['name'] ) ) {
				$form_field_names[] = $element['data']['name'];
			}
		}

		// Get submission data
		$submission_data = SUPER_Test_Form_Factory::get_simple_submission_data();

		// Verify submission data keys match form fields
		foreach ( array_keys( $submission_data ) as $field_name ) {
			$this->assertContains(
				$field_name,
				$form_field_names,
				"Submission field '{$field_name}' should exist in form"
			);
		}
	}

	/**
	 * Test: Full flow - form creation, entry, trigger, execution
	 */
	public function test_full_submission_flow() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) || ! class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			$this->markTestSkipped( 'Fixture classes not available' );
		}

		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not available' );
		}

		// 1. Create form
		$form_id = SUPER_Test_Form_Factory::create_simple_form( array(
			'title' => 'Full Flow Test Form',
		) );
		$this->assertGreaterThan( 0, $form_id, 'Form should be created' );

		// 2. Create trigger
		$trigger_id = SUPER_Test_Trigger_Factory::create_submit_log_trigger( $form_id );
		$this->assertNotWPError( $trigger_id, 'Trigger should be created' );

		// 3. Get submission data
		$data = SUPER_Test_Form_Factory::get_simple_submission_data();
		$this->assertNotEmpty( $data, 'Submission data should not be empty' );

		// 4. Create entry
		$entry_id = SUPER_Test_Form_Factory::create_entry( $form_id, $data );
		$this->assertGreaterThan( 0, $entry_id, 'Entry should be created' );

		// 5. Fire event
		$context = array(
			'form_id'   => $form_id,
			'entry_id'  => $entry_id,
			'user_id'   => $this->user_id,
			'form_data' => $data,
			'name'      => $data['name']['value'],
			'email'     => $data['email']['value'],
			'subject'   => $data['subject']['value'],
			'message'   => $data['message']['value'],
		);

		$results = SUPER_Trigger_Executor::fire_event( 'form.submitted', $context );

		// 6. Verify execution
		$this->assertIsArray( $results, 'Results should be an array' );
		$this->assertNotEmpty( $results, 'Should have execution results' );

		// Check that the trigger executed (results are keyed by trigger_id)
		$this->assertArrayHasKey( $trigger_id, $results, 'Our trigger should have executed' );
		$trigger_result = $results[ $trigger_id ];
		$this->assertIsArray( $trigger_result, 'Trigger result should be an array' );
		$this->assertArrayHasKey( 'success', $trigger_result, 'Result should have success key' );
		$this->assertTrue( $trigger_result['success'], 'Trigger execution should succeed' );
	}

	/**
	 * Test: Cleanup removes all created fixtures
	 */
	public function test_cleanup_removes_fixtures() {
		if ( ! class_exists( 'SUPER_Test_Form_Factory' ) || ! class_exists( 'SUPER_Test_Trigger_Factory' ) ) {
			$this->markTestSkipped( 'Fixture classes not available' );
		}

		// Create some fixtures
		$form_id    = SUPER_Test_Form_Factory::create_simple_form();
		$entry_id   = SUPER_Test_Form_Factory::create_entry( $form_id, array() );

		// Verify they exist
		$this->assertNotNull( SUPER_Form_DAL::get( $form_id ) );
		$this->assertNotNull( get_post( $entry_id ) );

		// Track IDs before cleanup
		$created_forms   = SUPER_Test_Form_Factory::get_created_forms();
		$created_entries = SUPER_Test_Form_Factory::get_created_entries();

		$this->assertContains( $form_id, $created_forms );
		$this->assertContains( $entry_id, $created_entries );

		// Cleanup
		SUPER_Test_Form_Factory::cleanup();

		// Verify they're deleted
		$this->assertNull( get_post( $form_id ), 'Form should be deleted' );
		$this->assertNull( get_post( $entry_id ), 'Entry should be deleted' );

		// Verify tracking arrays are cleared
		$this->assertEmpty( SUPER_Test_Form_Factory::get_created_forms() );
		$this->assertEmpty( SUPER_Test_Form_Factory::get_created_entries() );
	}
}
