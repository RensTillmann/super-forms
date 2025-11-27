<?php
/**
 * Payment Events Tests
 *
 * Comprehensive tests for payment webhook handlers including:
 * - Event registration verification (16 events)
 * - Stripe signature verification (security-critical)
 * - Event mapping (gateway events -> Super Forms events)
 * - Context building from webhook payloads
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Payment_Events extends WP_UnitTestCase {

	/**
	 * REST controller instance
	 *
	 * @var SUPER_Trigger_REST_Controller
	 */
	protected $controller;

	/**
	 * Registry instance
	 *
	 * @var SUPER_Trigger_Registry
	 */
	protected $registry;

	/**
	 * Test webhook secret for Stripe
	 *
	 * @var string
	 */
	protected $test_webhook_secret = 'whsec_test_secret_for_unit_tests';

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Get registry instance
		$this->registry = SUPER_Trigger_Registry::get_instance();

		// Create controller instance
		if ( class_exists( 'SUPER_Trigger_REST_Controller' ) ) {
			$this->controller = new SUPER_Trigger_REST_Controller();
		}
	}

	/**
	 * Ensure registry has builtins loaded for event registration tests
	 */
	protected function ensure_builtins_loaded() {
		// Reset with builtins loaded for tests that need the registered events
		$this->registry->reset( true );
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	// =========================================================================
	// SECTION 1: Payment Event Registration Tests
	// =========================================================================

	/**
	 * Test Stripe payment events are registered
	 */
	public function test_stripe_payment_events_registered() {
		$this->ensure_builtins_loaded();

		$expected_events = array(
			'payment.stripe.checkout_completed',
			'payment.stripe.payment_succeeded',
			'payment.stripe.payment_failed',
		);

		foreach ( $expected_events as $event_id ) {
			$event = $this->registry->get_event( $event_id );
			$this->assertNotNull( $event, "Event $event_id should be registered" );
			$this->assertEquals( 'payment', $event['category'], "Event $event_id should have category 'payment'" );
			$this->assertEquals( 'stripe', $event['addon'], "Event $event_id should have addon 'stripe'" );
		}
	}

	/**
	 * Test Stripe subscription events are registered
	 */
	public function test_stripe_subscription_events_registered() {
		$this->ensure_builtins_loaded();

		$expected_events = array(
			'subscription.stripe.created',
			'subscription.stripe.updated',
			'subscription.stripe.cancelled',
			'subscription.stripe.invoice_paid',
			'subscription.stripe.invoice_failed',
		);

		foreach ( $expected_events as $event_id ) {
			$event = $this->registry->get_event( $event_id );
			$this->assertNotNull( $event, "Event $event_id should be registered" );
			$this->assertEquals( 'subscription', $event['category'], "Event $event_id should have category 'subscription'" );
			$this->assertEquals( 'stripe', $event['addon'], "Event $event_id should have addon 'stripe'" );
		}
	}

	/**
	 * Test PayPal payment events are registered
	 */
	public function test_paypal_payment_events_registered() {
		$this->ensure_builtins_loaded();

		$expected_events = array(
			'payment.paypal.capture_completed',
			'payment.paypal.capture_denied',
			'payment.paypal.capture_refunded',
		);

		foreach ( $expected_events as $event_id ) {
			$event = $this->registry->get_event( $event_id );
			$this->assertNotNull( $event, "Event $event_id should be registered" );
			$this->assertEquals( 'payment', $event['category'], "Event $event_id should have category 'payment'" );
			$this->assertEquals( 'paypal', $event['addon'], "Event $event_id should have addon 'paypal'" );
		}
	}

	/**
	 * Test PayPal subscription events are registered
	 */
	public function test_paypal_subscription_events_registered() {
		$this->ensure_builtins_loaded();

		$expected_events = array(
			'subscription.paypal.created',
			'subscription.paypal.activated',
			'subscription.paypal.cancelled',
			'subscription.paypal.suspended',
			'subscription.paypal.payment_failed',
		);

		foreach ( $expected_events as $event_id ) {
			$event = $this->registry->get_event( $event_id );
			$this->assertNotNull( $event, "Event $event_id should be registered" );
			$this->assertEquals( 'subscription', $event['category'], "Event $event_id should have category 'subscription'" );
			$this->assertEquals( 'paypal', $event['addon'], "Event $event_id should have addon 'paypal'" );
		}
	}

	/**
	 * Test all 16 payment events have required context fields
	 */
	public function test_payment_events_have_context_fields() {
		$this->ensure_builtins_loaded();

		$all_events = array(
			// Stripe (8)
			'payment.stripe.checkout_completed',
			'payment.stripe.payment_succeeded',
			'payment.stripe.payment_failed',
			'subscription.stripe.created',
			'subscription.stripe.updated',
			'subscription.stripe.cancelled',
			'subscription.stripe.invoice_paid',
			'subscription.stripe.invoice_failed',
			// PayPal (8)
			'payment.paypal.capture_completed',
			'payment.paypal.capture_denied',
			'payment.paypal.capture_refunded',
			'subscription.paypal.created',
			'subscription.paypal.activated',
			'subscription.paypal.cancelled',
			'subscription.paypal.suspended',
			'subscription.paypal.payment_failed',
		);

		$this->assertCount( 16, $all_events, 'Should have 16 payment events defined' );

		foreach ( $all_events as $event_id ) {
			$event = $this->registry->get_event( $event_id );
			$this->assertNotNull( $event, "Event $event_id should be registered" );
			$this->assertArrayHasKey( 'available_context', $event, "Event $event_id should have available_context" );
			$this->assertArrayHasKey( 'required_context', $event, "Event $event_id should have required_context" );
			$this->assertNotEmpty( $event['available_context'], "Event $event_id should have at least one context field" );
		}
	}

	/**
	 * Test payment events have phase 6 metadata
	 */
	public function test_payment_events_have_phase_6() {
		$this->ensure_builtins_loaded();

		$stripe_events = array(
			'payment.stripe.checkout_completed',
			'payment.stripe.payment_succeeded',
		);

		$paypal_events = array(
			'payment.paypal.capture_completed',
			'subscription.paypal.created',
		);

		foreach ( array_merge( $stripe_events, $paypal_events ) as $event_id ) {
			$event = $this->registry->get_event( $event_id );
			$this->assertNotNull( $event, "Event $event_id should be registered" );
			$this->assertEquals( 6, $event['phase'], "Event $event_id should be phase 6" );
		}
	}

	// =========================================================================
	// SECTION 2: Stripe Signature Verification Tests (SECURITY CRITICAL)
	// =========================================================================

	/**
	 * Test valid Stripe signature passes verification
	 */
	public function test_valid_stripe_signature_passes() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$payload = '{"id":"evt_test","type":"checkout.session.completed"}';
		$timestamp = time();
		$signature = $this->generate_stripe_signature( $payload, $timestamp, $this->test_webhook_secret );
		$header = "t={$timestamp},v1={$signature}";

		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, $header, $this->test_webhook_secret )
		);

		$this->assertTrue( $result, 'Valid signature should pass verification' );
	}

	/**
	 * Test invalid Stripe signature is rejected
	 */
	public function test_invalid_stripe_signature_rejected() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$payload = '{"id":"evt_test","type":"checkout.session.completed"}';
		$timestamp = time();
		$invalid_signature = 'invalid_signature_that_should_fail';
		$header = "t={$timestamp},v1={$invalid_signature}";

		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, $header, $this->test_webhook_secret )
		);

		$this->assertInstanceOf( WP_Error::class, $result, 'Invalid signature should return WP_Error' );
		$this->assertEquals( 'invalid_signature', $result->get_error_code(), 'Should have invalid_signature error code' );
	}

	/**
	 * Test expired timestamp is rejected (replay attack prevention)
	 */
	public function test_expired_timestamp_rejected() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$payload = '{"id":"evt_test","type":"checkout.session.completed"}';
		// Timestamp from 10 minutes ago (beyond 5-minute tolerance)
		$old_timestamp = time() - 600;
		$signature = $this->generate_stripe_signature( $payload, $old_timestamp, $this->test_webhook_secret );
		$header = "t={$old_timestamp},v1={$signature}";

		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, $header, $this->test_webhook_secret )
		);

		$this->assertInstanceOf( WP_Error::class, $result, 'Expired timestamp should return WP_Error' );
		$this->assertEquals( 'signature_expired', $result->get_error_code(), 'Should have signature_expired error code' );
	}

	/**
	 * Test malformed signature header is rejected
	 */
	public function test_malformed_signature_header_rejected() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$payload = '{"id":"evt_test","type":"checkout.session.completed"}';

		// Test missing timestamp
		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, 'v1=somesignature', $this->test_webhook_secret )
		);
		$this->assertInstanceOf( WP_Error::class, $result, 'Missing timestamp should return WP_Error' );
		$this->assertEquals( 'invalid_signature_format', $result->get_error_code() );

		// Test missing signature
		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, 't=12345', $this->test_webhook_secret )
		);
		$this->assertInstanceOf( WP_Error::class, $result, 'Missing signature should return WP_Error' );
		$this->assertEquals( 'invalid_signature_format', $result->get_error_code() );

		// Test completely invalid format
		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, 'garbage_header_value', $this->test_webhook_secret )
		);
		$this->assertInstanceOf( WP_Error::class, $result, 'Garbage header should return WP_Error' );
	}

	/**
	 * Test boundary timestamp tolerance (exactly at 5 minutes)
	 */
	public function test_boundary_timestamp_tolerance() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$payload = '{"id":"evt_test","type":"checkout.session.completed"}';

		// Timestamp at exactly 299 seconds (just under 5 minutes) - should PASS
		$almost_expired = time() - 299;
		$signature = $this->generate_stripe_signature( $payload, $almost_expired, $this->test_webhook_secret );
		$header = "t={$almost_expired},v1={$signature}";

		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, $header, $this->test_webhook_secret )
		);
		$this->assertTrue( $result, 'Timestamp at 299 seconds should still be valid' );

		// Timestamp at 301 seconds (just over 5 minutes) - should FAIL
		$just_expired = time() - 301;
		$signature = $this->generate_stripe_signature( $payload, $just_expired, $this->test_webhook_secret );
		$header = "t={$just_expired},v1={$signature}";

		$result = $this->invoke_private_method(
			$this->controller,
			'verify_stripe_signature',
			array( $payload, $header, $this->test_webhook_secret )
		);
		$this->assertInstanceOf( WP_Error::class, $result, 'Timestamp at 301 seconds should be expired' );
	}

	// =========================================================================
	// SECTION 3: Event Mapping Tests
	// =========================================================================

	/**
	 * Test Stripe checkout event mapping
	 */
	public function test_stripe_checkout_completed_maps_correctly() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$mapping = $this->invoke_private_method( $this->controller, 'get_stripe_event_mapping', array() );

		$this->assertArrayHasKey( 'checkout.session.completed', $mapping );
		$this->assertEquals( 'payment.stripe.checkout_completed', $mapping['checkout.session.completed'] );
	}

	/**
	 * Test Stripe payment intent events mapping
	 */
	public function test_stripe_payment_intent_events_map_correctly() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$mapping = $this->invoke_private_method( $this->controller, 'get_stripe_event_mapping', array() );

		$this->assertArrayHasKey( 'payment_intent.succeeded', $mapping );
		$this->assertEquals( 'payment.stripe.payment_succeeded', $mapping['payment_intent.succeeded'] );

		$this->assertArrayHasKey( 'payment_intent.payment_failed', $mapping );
		$this->assertEquals( 'payment.stripe.payment_failed', $mapping['payment_intent.payment_failed'] );
	}

	/**
	 * Test Stripe subscription events mapping
	 */
	public function test_stripe_subscription_events_map_correctly() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$mapping = $this->invoke_private_method( $this->controller, 'get_stripe_event_mapping', array() );

		$expected = array(
			'customer.subscription.created' => 'subscription.stripe.created',
			'customer.subscription.updated' => 'subscription.stripe.updated',
			'customer.subscription.deleted' => 'subscription.stripe.cancelled',
			'invoice.paid'                  => 'subscription.stripe.invoice_paid',
			'invoice.payment_failed'        => 'subscription.stripe.invoice_failed',
		);

		foreach ( $expected as $stripe_event => $super_event ) {
			$this->assertArrayHasKey( $stripe_event, $mapping, "Should map $stripe_event" );
			$this->assertEquals( $super_event, $mapping[ $stripe_event ], "Should map $stripe_event to $super_event" );
		}
	}

	/**
	 * Test PayPal capture events mapping
	 */
	public function test_paypal_capture_events_map_correctly() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$mapping = $this->invoke_private_method( $this->controller, 'get_paypal_event_mapping', array() );

		$expected = array(
			'PAYMENT.CAPTURE.COMPLETED' => 'payment.paypal.capture_completed',
			'PAYMENT.CAPTURE.DENIED'    => 'payment.paypal.capture_denied',
			'PAYMENT.CAPTURE.REFUNDED'  => 'payment.paypal.capture_refunded',
		);

		foreach ( $expected as $paypal_event => $super_event ) {
			$this->assertArrayHasKey( $paypal_event, $mapping, "Should map $paypal_event" );
			$this->assertEquals( $super_event, $mapping[ $paypal_event ], "Should map $paypal_event to $super_event" );
		}
	}

	/**
	 * Test PayPal subscription events mapping
	 */
	public function test_paypal_subscription_events_map_correctly() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$mapping = $this->invoke_private_method( $this->controller, 'get_paypal_event_mapping', array() );

		$expected = array(
			'BILLING.SUBSCRIPTION.CREATED'        => 'subscription.paypal.created',
			'BILLING.SUBSCRIPTION.ACTIVATED'      => 'subscription.paypal.activated',
			'BILLING.SUBSCRIPTION.CANCELLED'      => 'subscription.paypal.cancelled',
			'BILLING.SUBSCRIPTION.SUSPENDED'      => 'subscription.paypal.suspended',
			'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => 'subscription.paypal.payment_failed',
		);

		foreach ( $expected as $paypal_event => $super_event ) {
			$this->assertArrayHasKey( $paypal_event, $mapping, "Should map $paypal_event" );
			$this->assertEquals( $super_event, $mapping[ $paypal_event ], "Should map $paypal_event to $super_event" );
		}
	}

	/**
	 * Test unknown event type not in mapping
	 */
	public function test_unknown_event_type_not_mapped() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$stripe_mapping = $this->invoke_private_method( $this->controller, 'get_stripe_event_mapping', array() );
		$paypal_mapping = $this->invoke_private_method( $this->controller, 'get_paypal_event_mapping', array() );

		$this->assertArrayNotHasKey( 'unknown.event.type', $stripe_mapping );
		$this->assertArrayNotHasKey( 'UNKNOWN.EVENT.TYPE', $paypal_mapping );
		$this->assertArrayNotHasKey( 'completely_made_up', $stripe_mapping );
	}

	// =========================================================================
	// SECTION 4: Context Building Tests
	// =========================================================================

	/**
	 * Test Stripe checkout session context extraction
	 */
	public function test_stripe_checkout_session_context_extraction() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = $this->get_stripe_checkout_completed_payload();

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 'stripe', $context['gateway'] );
		$this->assertEquals( 'evt_test_123', $context['event_id'] );
		$this->assertEquals( 'checkout.session.completed', $context['event_type'] );
		$this->assertEquals( 'cs_test_session', $context['session_id'] );
		$this->assertEquals( 'pi_test_payment', $context['payment_intent'] );
		$this->assertEquals( 'cus_test_customer', $context['customer'] );
		$this->assertEquals( 'customer@example.com', $context['customer_email'] );
		$this->assertEquals( 2500, $context['amount_total'] );
		$this->assertEquals( 'usd', $context['currency'] );
		$this->assertEquals( 'paid', $context['payment_status'] );
	}

	/**
	 * Test Stripe metadata form_id extraction
	 */
	public function test_stripe_metadata_form_id_extraction() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = $this->get_stripe_checkout_completed_payload();

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 123, $context['form_id'], 'Should extract form_id from metadata' );
		$this->assertEquals( 456, $context['entry_id'], 'Should extract entry_id from metadata' );
		$this->assertIsArray( $context['metadata'], 'Should include full metadata array' );
	}

	/**
	 * Test Stripe amount conversion from cents to display format
	 */
	public function test_stripe_amount_conversion_cents_to_display() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = $this->get_stripe_checkout_completed_payload();

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 2500, $context['amount_total'], 'Amount should be in cents' );
		$this->assertEquals( '25.00', $context['amount_total_display'], 'Display amount should be formatted dollars' );
	}

	/**
	 * Test Stripe payment intent context extraction
	 */
	public function test_stripe_payment_intent_context_extraction() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'      => 'evt_pi_test',
			'type'    => 'payment_intent.succeeded',
			'livemode' => false,
			'created' => time(),
			'data'    => array(
				'object' => array(
					'id'       => 'pi_test_intent',
					'amount'   => 5000,
					'currency' => 'eur',
					'status'   => 'succeeded',
					'customer' => 'cus_pi_customer',
					'metadata' => array(
						'form_id'  => 789,
						'entry_id' => 101,
					),
				),
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 'pi_test_intent', $context['payment_intent_id'] );
		$this->assertEquals( 5000, $context['amount'] );
		$this->assertEquals( 'eur', $context['currency'] );
		$this->assertEquals( 'succeeded', $context['status'] );
		$this->assertEquals( '50.00', $context['amount_display'] );
	}

	/**
	 * Test Stripe subscription context extraction
	 */
	public function test_stripe_subscription_context_extraction() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'      => 'evt_sub_test',
			'type'    => 'customer.subscription.created',
			'livemode' => false,
			'created' => time(),
			'data'    => array(
				'object' => array(
					'id'                   => 'sub_test_subscription',
					'customer'             => 'cus_sub_customer',
					'status'               => 'active',
					'current_period_start' => 1700000000,
					'current_period_end'   => 1702678400,
					'cancel_at_period_end' => false,
					'items'                => array(
						'data' => array(
							array(
								'price' => array(
									'id'          => 'price_test',
									'unit_amount' => 1999,
									'recurring'   => array(
										'interval' => 'month',
									),
								),
							),
						),
					),
					'metadata' => array(
						'form_id'  => 222,
						'entry_id' => 333,
					),
				),
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 'sub_test_subscription', $context['subscription_id'] );
		$this->assertEquals( 'cus_sub_customer', $context['customer'] );
		$this->assertEquals( 'active', $context['status'] );
		$this->assertEquals( 'price_test', $context['plan_id'] );
		$this->assertEquals( 1999, $context['plan_amount'] );
		$this->assertEquals( 'month', $context['plan_interval'] );
		$this->assertEquals( '19.99', $context['plan_amount_display'] );
	}

	/**
	 * Test PayPal capture context extraction
	 */
	public function test_paypal_capture_context_extraction() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = $this->get_paypal_capture_completed_payload();

		$context = $this->invoke_private_method(
			$this->controller,
			'build_paypal_context',
			array( $event )
		);

		$this->assertEquals( 'paypal', $context['gateway'] );
		$this->assertEquals( 'WH-test-123', $context['event_id'] );
		$this->assertEquals( 'PAYMENT.CAPTURE.COMPLETED', $context['event_type'] );
		$this->assertEquals( 'capture_test_123', $context['capture_id'] );
		$this->assertEquals( 'COMPLETED', $context['status'] );
		$this->assertEquals( '49.99', $context['amount'] );
		$this->assertEquals( 'USD', $context['currency'] );
	}

	/**
	 * Test PayPal custom_id JSON parsing for form_id/entry_id
	 */
	public function test_paypal_custom_id_json_parsing() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = $this->get_paypal_capture_completed_payload();

		$context = $this->invoke_private_method(
			$this->controller,
			'build_paypal_context',
			array( $event )
		);

		$this->assertEquals( 555, $context['form_id'], 'Should parse form_id from custom_id JSON' );
		$this->assertEquals( 666, $context['entry_id'], 'Should parse entry_id from custom_id JSON' );
	}

	/**
	 * Test PayPal subscription context extraction
	 */
	public function test_paypal_subscription_context_extraction() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'            => 'WH-sub-test',
			'event_type'    => 'BILLING.SUBSCRIPTION.CREATED',
			'create_time'   => '2024-01-15T10:00:00Z',
			'resource_type' => 'subscription',
			'resource'      => array(
				'id'         => 'I-TESTSUB123',
				'status'     => 'ACTIVE',
				'plan_id'    => 'P-TESTPLAN',
				'start_time' => '2024-01-15T10:00:00Z',
				'subscriber' => array(
					'email_address' => 'subscriber@example.com',
					'name'          => array(
						'given_name' => 'John',
					),
				),
				'billing_info' => array(
					'last_payment' => array(
						'amount' => array(
							'value' => '29.99',
						),
						'time' => '2024-01-15T10:00:00Z',
					),
					'next_billing_time' => '2024-02-15T10:00:00Z',
				),
				'custom_id'  => '{"form_id":777,"entry_id":888}',
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_paypal_context',
			array( $event )
		);

		$this->assertEquals( 'I-TESTSUB123', $context['subscription_id'] );
		$this->assertEquals( 'ACTIVE', $context['status'] );
		$this->assertEquals( 'P-TESTPLAN', $context['plan_id'] );
		$this->assertEquals( 'subscriber@example.com', $context['subscriber_email'] );
		$this->assertEquals( 'John', $context['subscriber_name'] );
		$this->assertEquals( '29.99', $context['last_payment_amount'] );
		$this->assertEquals( 777, $context['form_id'] );
		$this->assertEquals( 888, $context['entry_id'] );
	}

	// =========================================================================
	// SECTION 5: Edge Cases
	// =========================================================================

	/**
	 * Test missing metadata uses zero IDs
	 */
	public function test_missing_metadata_uses_zero_ids() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'      => 'evt_no_meta',
			'type'    => 'checkout.session.completed',
			'livemode' => false,
			'created' => time(),
			'data'    => array(
				'object' => array(
					'id'             => 'cs_no_meta',
					'payment_intent' => 'pi_no_meta',
					'amount_total'   => 1000,
					'currency'       => 'usd',
					'payment_status' => 'paid',
					// No metadata!
				),
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 0, $context['form_id'], 'Missing form_id should default to 0' );
		$this->assertEquals( 0, $context['entry_id'], 'Missing entry_id should default to 0' );
	}

	/**
	 * Test malformed PayPal custom_id (not valid JSON)
	 */
	public function test_malformed_paypal_custom_id() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'            => 'WH-malformed',
			'event_type'    => 'PAYMENT.CAPTURE.COMPLETED',
			'create_time'   => '2024-01-15T10:00:00Z',
			'resource_type' => 'capture',
			'resource'      => array(
				'id'        => 'capture_malformed',
				'status'    => 'COMPLETED',
				'amount'    => array(
					'value'         => '10.00',
					'currency_code' => 'USD',
				),
				'custom_id' => 'not-valid-json', // Invalid JSON
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_paypal_context',
			array( $event )
		);

		// Should not have form_id/entry_id since JSON parsing failed
		$this->assertArrayNotHasKey( 'form_id', $context );
		$this->assertArrayNotHasKey( 'entry_id', $context );
		$this->assertEquals( 'not-valid-json', $context['custom_id'], 'Should still capture raw custom_id' );
	}

	/**
	 * Test zero amount handled correctly
	 */
	public function test_zero_amount_handled_correctly() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'      => 'evt_free',
			'type'    => 'checkout.session.completed',
			'livemode' => false,
			'created' => time(),
			'data'    => array(
				'object' => array(
					'id'             => 'cs_free',
					'amount_total'   => 0, // Free checkout
					'currency'       => 'usd',
					'payment_status' => 'no_payment_required',
				),
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 0, $context['amount_total'] );
		$this->assertEquals( '0.00', $context['amount_total_display'] );
	}

	/**
	 * Test large amount handled correctly
	 */
	public function test_large_amount_handled_correctly() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'      => 'evt_big',
			'type'    => 'checkout.session.completed',
			'livemode' => false,
			'created' => time(),
			'data'    => array(
				'object' => array(
					'id'             => 'cs_big',
					'amount_total'   => 99999999, // $999,999.99
					'currency'       => 'usd',
					'payment_status' => 'paid',
				),
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 99999999, $context['amount_total'] );
		$this->assertEquals( '999,999.99', $context['amount_total_display'] );
	}

	/**
	 * Test PayPal display amount formatting
	 */
	public function test_paypal_amount_display_formatting() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'            => 'WH-amount-test',
			'event_type'    => 'PAYMENT.CAPTURE.COMPLETED',
			'create_time'   => '2024-01-15T10:00:00Z',
			'resource_type' => 'capture',
			'resource'      => array(
				'id'     => 'capture_amount',
				'status' => 'COMPLETED',
				'amount' => array(
					'value'         => '123.45',
					'currency_code' => 'EUR',
				),
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_paypal_context',
			array( $event )
		);

		$this->assertEquals( '123.45', $context['amount'] );
		$this->assertEquals( '123.45', $context['amount_display'] );
	}

	/**
	 * Test Stripe payment error context extraction
	 */
	public function test_stripe_payment_error_context() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'REST controller not available' );
		}

		$event = array(
			'id'      => 'evt_failed',
			'type'    => 'payment_intent.payment_failed',
			'livemode' => false,
			'created' => time(),
			'data'    => array(
				'object' => array(
					'id'                 => 'pi_failed',
					'amount'             => 5000,
					'currency'           => 'usd',
					'status'             => 'requires_payment_method',
					'customer'           => 'cus_failed',
					'last_payment_error' => array(
						'code'    => 'card_declined',
						'message' => 'Your card was declined.',
					),
					'metadata' => array(
						'form_id' => 111,
					),
				),
			),
		);

		$context = $this->invoke_private_method(
			$this->controller,
			'build_stripe_context',
			array( $event )
		);

		$this->assertEquals( 'card_declined', $context['error_code'] );
		$this->assertEquals( 'Your card was declined.', $context['error_message'] );
	}

	// =========================================================================
	// HELPER METHODS
	// =========================================================================

	/**
	 * Generate valid Stripe signature for testing
	 *
	 * @param string $payload        Webhook payload
	 * @param int    $timestamp      Unix timestamp
	 * @param string $webhook_secret Webhook signing secret
	 * @return string HMAC-SHA256 signature
	 */
	protected function generate_stripe_signature( $payload, $timestamp, $webhook_secret ) {
		$signed_payload = $timestamp . '.' . $payload;
		return hash_hmac( 'sha256', $signed_payload, $webhook_secret );
	}

	/**
	 * Invoke private method using Reflection
	 *
	 * @param object $object     Object instance
	 * @param string $method     Method name
	 * @param array  $parameters Method parameters
	 * @return mixed Method return value
	 */
	protected function invoke_private_method( $object, $method, array $parameters = array() ) {
		$reflection = new ReflectionClass( get_class( $object ) );
		$method = $reflection->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}

	/**
	 * Get sample Stripe checkout.session.completed payload
	 *
	 * @return array
	 */
	protected function get_stripe_checkout_completed_payload() {
		return array(
			'id'      => 'evt_test_123',
			'type'    => 'checkout.session.completed',
			'livemode' => false,
			'created' => time(),
			'data'    => array(
				'object' => array(
					'id'               => 'cs_test_session',
					'payment_intent'   => 'pi_test_payment',
					'customer'         => 'cus_test_customer',
					'customer_email'   => 'customer@example.com',
					'amount_total'     => 2500, // $25.00 in cents
					'currency'         => 'usd',
					'payment_status'   => 'paid',
					'subscription'     => '',
					'metadata'         => array(
						'form_id'  => 123,
						'entry_id' => 456,
					),
				),
			),
		);
	}

	/**
	 * Get sample PayPal PAYMENT.CAPTURE.COMPLETED payload
	 *
	 * @return array
	 */
	protected function get_paypal_capture_completed_payload() {
		return array(
			'id'            => 'WH-test-123',
			'event_type'    => 'PAYMENT.CAPTURE.COMPLETED',
			'create_time'   => '2024-01-15T10:00:00Z',
			'resource_type' => 'capture',
			'resource'      => array(
				'id'         => 'capture_test_123',
				'status'     => 'COMPLETED',
				'amount'     => array(
					'value'         => '49.99',
					'currency_code' => 'USD',
				),
				'invoice_id' => 'INV-123',
				'custom_id'  => '{"form_id":555,"entry_id":666}',
			),
		);
	}
}
