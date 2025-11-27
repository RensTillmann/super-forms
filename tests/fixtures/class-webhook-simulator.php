<?php
/**
 * Webhook Simulator
 *
 * Generates mock payment webhook payloads for testing
 *
 * @package Super_Forms
 * @subpackage Tests/Fixtures
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SUPER_Webhook_Simulator Class
 *
 * Simulates payment gateway webhooks for testing
 */
class SUPER_Webhook_Simulator {

	/**
	 * Stripe webhook secret for testing
	 *
	 * @var string
	 */
	private static $stripe_webhook_secret = 'whsec_test_secret_123456';

	/**
	 * Simulated webhook history
	 *
	 * @var array
	 */
	private static $webhook_history = array();

	/**
	 * Simulate a Stripe webhook event
	 *
	 * @param string $event_type Stripe event type (e.g., 'payment_intent.succeeded').
	 * @param array  $data       Event data.
	 * @param array  $metadata   Optional metadata (form_id, entry_id).
	 * @return array Webhook payload with signature
	 */
	public static function stripe( $event_type, $data = array(), $metadata = array() ) {
		$timestamp = time();
		$event_id  = 'evt_test_' . uniqid();

		// Build the event object based on type
		$event_object = self::build_stripe_event_object( $event_type, $data, $metadata );

		// Build full event payload
		$payload = array(
			'id'               => $event_id,
			'object'           => 'event',
			'api_version'      => '2023-10-16',
			'created'          => $timestamp,
			'data'             => array(
				'object' => $event_object,
			),
			'livemode'         => false,
			'pending_webhooks' => 1,
			'request'          => array(
				'id'              => 'req_test_' . uniqid(),
				'idempotency_key' => 'idk_test_' . uniqid(),
			),
			'type'             => $event_type,
		);

		// Generate signature
		$payload_json = wp_json_encode( $payload );
		$signature    = self::generate_stripe_signature( $payload_json, $timestamp );

		$result = array(
			'payload'   => $payload,
			'json'      => $payload_json,
			'signature' => $signature,
			'headers'   => array(
				'Stripe-Signature' => $signature,
				'Content-Type'     => 'application/json',
			),
		);

		// Store in history
		self::$webhook_history[] = array(
			'provider'  => 'stripe',
			'type'      => $event_type,
			'timestamp' => $timestamp,
			'payload'   => $payload,
		);

		return $result;
	}

	/**
	 * Simulate a PayPal webhook event
	 *
	 * @param string $event_type PayPal event type (e.g., 'PAYMENT.CAPTURE.COMPLETED').
	 * @param array  $data       Event data.
	 * @param array  $metadata   Optional metadata.
	 * @return array Webhook payload
	 */
	public static function paypal( $event_type, $data = array(), $metadata = array() ) {
		$timestamp = gmdate( 'c' );
		$event_id  = 'WH-TEST-' . strtoupper( uniqid() );

		// Build the resource object based on type
		$resource = self::build_paypal_resource( $event_type, $data, $metadata );

		// Build full event payload
		$payload = array(
			'id'               => $event_id,
			'event_version'    => '1.0',
			'create_time'      => $timestamp,
			'resource_type'    => self::get_paypal_resource_type( $event_type ),
			'resource_version' => '2.0',
			'event_type'       => $event_type,
			'summary'          => self::get_paypal_event_summary( $event_type ),
			'resource'         => $resource,
			'links'            => array(
				array(
					'href'   => 'https://api.sandbox.paypal.com/v1/notifications/webhooks-events/' . $event_id,
					'rel'    => 'self',
					'method' => 'GET',
				),
				array(
					'href'   => 'https://api.sandbox.paypal.com/v1/notifications/webhooks-events/' . $event_id . '/resend',
					'rel'    => 'resend',
					'method' => 'POST',
				),
			),
		);

		$result = array(
			'payload' => $payload,
			'json'    => wp_json_encode( $payload ),
			'headers' => array(
				'Content-Type'                     => 'application/json',
				'PAYPAL-TRANSMISSION-ID'           => 'test-transmission-' . uniqid(),
				'PAYPAL-TRANSMISSION-TIME'         => $timestamp,
				'PAYPAL-TRANSMISSION-SIG'          => base64_encode( 'test-signature-' . $event_id ),
				'PAYPAL-CERT-URL'                  => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-test',
				'PAYPAL-AUTH-ALGO'                 => 'SHA256withRSA',
			),
		);

		// Store in history
		self::$webhook_history[] = array(
			'provider'  => 'paypal',
			'type'      => $event_type,
			'timestamp' => time(),
			'payload'   => $payload,
		);

		return $result;
	}

	/**
	 * Get webhook history
	 *
	 * @param string $provider Optional. Filter by provider (stripe/paypal).
	 * @return array Webhook history
	 */
	public static function get_history( $provider = null ) {
		if ( $provider ) {
			return array_filter(
				self::$webhook_history,
				function( $webhook ) use ( $provider ) {
					return $webhook['provider'] === $provider;
				}
			);
		}
		return self::$webhook_history;
	}

	/**
	 * Clear webhook history
	 */
	public static function clear_history() {
		self::$webhook_history = array();
	}

	/**
	 * Set Stripe webhook secret for testing
	 *
	 * @param string $secret Webhook secret.
	 */
	public static function set_stripe_secret( $secret ) {
		self::$stripe_webhook_secret = $secret;
	}

	/**
	 * Verify a Stripe webhook signature
	 *
	 * @param string $payload   JSON payload.
	 * @param string $signature Stripe-Signature header.
	 * @param int    $tolerance Tolerance in seconds (default 300).
	 * @return bool True if valid
	 */
	public static function verify_stripe_signature( $payload, $signature, $tolerance = 300 ) {
		// Parse signature header
		$sig_parts = array();
		foreach ( explode( ',', $signature ) as $part ) {
			list( $key, $value ) = explode( '=', $part, 2 );
			$sig_parts[ $key ] = $value;
		}

		if ( ! isset( $sig_parts['t'] ) || ! isset( $sig_parts['v1'] ) ) {
			return false;
		}

		$timestamp = (int) $sig_parts['t'];

		// Check timestamp tolerance
		if ( abs( time() - $timestamp ) > $tolerance ) {
			return false;
		}

		// Compute expected signature
		$signed_payload    = $timestamp . '.' . $payload;
		$expected_sig      = hash_hmac( 'sha256', $signed_payload, self::$stripe_webhook_secret );

		return hash_equals( $expected_sig, $sig_parts['v1'] );
	}

	// =========================================================================
	// Stripe Event Builders
	// =========================================================================

	/**
	 * Build Stripe event object based on event type
	 *
	 * @param string $event_type Event type.
	 * @param array  $data       Custom data.
	 * @param array  $metadata   Metadata.
	 * @return array Event object
	 */
	private static function build_stripe_event_object( $event_type, $data, $metadata ) {
		$defaults = array_merge(
			array(
				'form_id'  => 0,
				'entry_id' => 0,
			),
			$metadata
		);

		switch ( $event_type ) {
			case 'payment_intent.succeeded':
				return self::build_stripe_payment_intent( 'succeeded', $data, $defaults );

			case 'payment_intent.payment_failed':
				return self::build_stripe_payment_intent( 'requires_payment_method', $data, $defaults );

			case 'checkout.session.completed':
				return self::build_stripe_checkout_session( 'complete', $data, $defaults );

			case 'checkout.session.async_payment_succeeded':
				return self::build_stripe_checkout_session( 'complete', $data, $defaults );

			case 'checkout.session.async_payment_failed':
				return self::build_stripe_checkout_session( 'expired', $data, $defaults );

			case 'customer.subscription.created':
			case 'customer.subscription.updated':
			case 'customer.subscription.deleted':
				$status = $event_type === 'customer.subscription.deleted' ? 'canceled' : 'active';
				return self::build_stripe_subscription( $status, $data, $defaults );

			case 'invoice.paid':
				return self::build_stripe_invoice( 'paid', $data, $defaults );

			case 'invoice.payment_failed':
				return self::build_stripe_invoice( 'open', $data, $defaults );

			default:
				return array_merge(
					array( 'id' => 'obj_test_' . uniqid() ),
					$data
				);
		}
	}

	/**
	 * Build Stripe payment intent object
	 */
	private static function build_stripe_payment_intent( $status, $data, $metadata ) {
		$defaults = array(
			'id'                     => 'pi_test_' . uniqid(),
			'object'                 => 'payment_intent',
			'amount'                 => 2999,
			'currency'               => 'usd',
			'status'                 => $status,
			'customer'               => 'cus_test_' . uniqid(),
			'description'            => 'Test payment',
			'receipt_email'          => 'test@example.com',
			'metadata'               => $metadata,
			'created'                => time(),
			'livemode'               => false,
			'payment_method'         => 'pm_test_' . uniqid(),
			'payment_method_types'   => array( 'card' ),
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Build Stripe checkout session object
	 */
	private static function build_stripe_checkout_session( $status, $data, $metadata ) {
		$defaults = array(
			'id'                    => 'cs_test_' . uniqid(),
			'object'                => 'checkout.session',
			'mode'                  => 'payment',
			'status'                => $status,
			'payment_status'        => $status === 'complete' ? 'paid' : 'unpaid',
			'amount_total'          => 2999,
			'currency'              => 'usd',
			'customer'              => 'cus_test_' . uniqid(),
			'customer_email'        => 'test@example.com',
			'metadata'              => $metadata,
			'payment_intent'        => 'pi_test_' . uniqid(),
			'success_url'           => 'https://example.com/success',
			'cancel_url'            => 'https://example.com/cancel',
			'created'               => time(),
			'livemode'              => false,
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Build Stripe subscription object
	 */
	private static function build_stripe_subscription( $status, $data, $metadata ) {
		$now = time();
		$defaults = array(
			'id'                      => 'sub_test_' . uniqid(),
			'object'                  => 'subscription',
			'status'                  => $status,
			'customer'                => 'cus_test_' . uniqid(),
			'current_period_start'    => $now,
			'current_period_end'      => $now + ( 30 * 24 * 60 * 60 ),
			'metadata'                => $metadata,
			'items'                   => array(
				'data' => array(
					array(
						'id'    => 'si_test_' . uniqid(),
						'price' => array(
							'id'       => 'price_test_' . uniqid(),
							'unit_amount' => 2999,
							'currency' => 'usd',
							'recurring' => array(
								'interval' => 'month',
							),
						),
					),
				),
			),
			'cancel_at_period_end'    => false,
			'created'                 => $now,
			'livemode'                => false,
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Build Stripe invoice object
	 */
	private static function build_stripe_invoice( $status, $data, $metadata ) {
		$defaults = array(
			'id'                  => 'in_test_' . uniqid(),
			'object'              => 'invoice',
			'status'              => $status,
			'amount_due'          => 2999,
			'amount_paid'         => $status === 'paid' ? 2999 : 0,
			'currency'            => 'usd',
			'customer'            => 'cus_test_' . uniqid(),
			'customer_email'      => 'test@example.com',
			'subscription'        => 'sub_test_' . uniqid(),
			'metadata'            => $metadata,
			'period_start'        => time(),
			'period_end'          => time() + ( 30 * 24 * 60 * 60 ),
			'created'             => time(),
			'livemode'            => false,
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Generate Stripe webhook signature
	 *
	 * @param string $payload   JSON payload.
	 * @param int    $timestamp Unix timestamp.
	 * @return string Signature header value
	 */
	private static function generate_stripe_signature( $payload, $timestamp ) {
		$signed_payload = $timestamp . '.' . $payload;
		$signature      = hash_hmac( 'sha256', $signed_payload, self::$stripe_webhook_secret );
		return "t={$timestamp},v1={$signature}";
	}

	// =========================================================================
	// PayPal Event Builders
	// =========================================================================

	/**
	 * Build PayPal resource based on event type
	 */
	private static function build_paypal_resource( $event_type, $data, $metadata ) {
		$defaults = array_merge(
			array(
				'form_id'  => 0,
				'entry_id' => 0,
			),
			$metadata
		);

		switch ( $event_type ) {
			case 'PAYMENT.CAPTURE.COMPLETED':
			case 'PAYMENT.CAPTURE.DENIED':
			case 'PAYMENT.CAPTURE.REFUNDED':
				return self::build_paypal_capture( $event_type, $data, $defaults );

			case 'BILLING.SUBSCRIPTION.CREATED':
			case 'BILLING.SUBSCRIPTION.ACTIVATED':
			case 'BILLING.SUBSCRIPTION.CANCELLED':
			case 'BILLING.SUBSCRIPTION.SUSPENDED':
				return self::build_paypal_subscription( $event_type, $data, $defaults );

			case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
				return self::build_paypal_subscription_payment( $data, $defaults );

			default:
				return array_merge(
					array( 'id' => 'TEST-' . strtoupper( uniqid() ) ),
					$data
				);
		}
	}

	/**
	 * Build PayPal capture resource
	 */
	private static function build_paypal_capture( $event_type, $data, $metadata ) {
		$status_map = array(
			'PAYMENT.CAPTURE.COMPLETED' => 'COMPLETED',
			'PAYMENT.CAPTURE.DENIED'    => 'DECLINED',
			'PAYMENT.CAPTURE.REFUNDED'  => 'REFUNDED',
		);

		$defaults = array(
			'id'                => strtoupper( uniqid() ),
			'status'            => $status_map[ $event_type ] ?? 'COMPLETED',
			'amount'            => array(
				'currency_code' => 'USD',
				'value'         => '29.99',
			),
			'final_capture'     => true,
			'seller_protection' => array(
				'status' => 'ELIGIBLE',
			),
			'create_time'       => gmdate( 'c' ),
			'update_time'       => gmdate( 'c' ),
			'custom_id'         => wp_json_encode( $metadata ),
			'links'             => array(
				array(
					'href'   => 'https://api.sandbox.paypal.com/v2/payments/captures/' . uniqid(),
					'rel'    => 'self',
					'method' => 'GET',
				),
			),
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Build PayPal subscription resource
	 */
	private static function build_paypal_subscription( $event_type, $data, $metadata ) {
		$status_map = array(
			'BILLING.SUBSCRIPTION.CREATED'   => 'APPROVAL_PENDING',
			'BILLING.SUBSCRIPTION.ACTIVATED' => 'ACTIVE',
			'BILLING.SUBSCRIPTION.CANCELLED' => 'CANCELLED',
			'BILLING.SUBSCRIPTION.SUSPENDED' => 'SUSPENDED',
		);

		$defaults = array(
			'id'              => 'I-' . strtoupper( uniqid() ),
			'plan_id'         => 'P-' . strtoupper( uniqid() ),
			'status'          => $status_map[ $event_type ] ?? 'ACTIVE',
			'start_time'      => gmdate( 'c' ),
			'quantity'        => '1',
			'subscriber'      => array(
				'email_address' => 'test@example.com',
				'name'          => array(
					'given_name'  => 'Test',
					'surname'     => 'User',
				),
			),
			'billing_info'    => array(
				'outstanding_balance' => array(
					'currency_code' => 'USD',
					'value'         => '0.00',
				),
				'cycle_executions'    => array(
					array(
						'tenure_type'          => 'REGULAR',
						'sequence'             => 1,
						'cycles_completed'     => 1,
						'cycles_remaining'     => 0,
						'total_cycles'         => 0,
					),
				),
				'last_payment'        => array(
					'amount' => array(
						'currency_code' => 'USD',
						'value'         => '29.99',
					),
					'time'   => gmdate( 'c' ),
				),
				'next_billing_time'   => gmdate( 'c', strtotime( '+1 month' ) ),
			),
			'create_time'     => gmdate( 'c' ),
			'update_time'     => gmdate( 'c' ),
			'custom_id'       => wp_json_encode( $metadata ),
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Build PayPal subscription payment failed resource
	 */
	private static function build_paypal_subscription_payment( $data, $metadata ) {
		$defaults = array(
			'id'          => 'I-' . strtoupper( uniqid() ),
			'status'      => 'ACTIVE',
			'plan_id'     => 'P-' . strtoupper( uniqid() ),
			'billing_info' => array(
				'outstanding_balance' => array(
					'currency_code' => 'USD',
					'value'         => '29.99',
				),
				'failed_payments_count' => 1,
			),
			'custom_id'   => wp_json_encode( $metadata ),
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Get PayPal resource type from event type
	 */
	private static function get_paypal_resource_type( $event_type ) {
		if ( strpos( $event_type, 'PAYMENT.CAPTURE' ) === 0 ) {
			return 'capture';
		}
		if ( strpos( $event_type, 'BILLING.SUBSCRIPTION' ) === 0 ) {
			return 'subscription';
		}
		return 'unknown';
	}

	/**
	 * Get PayPal event summary
	 */
	private static function get_paypal_event_summary( $event_type ) {
		$summaries = array(
			'PAYMENT.CAPTURE.COMPLETED'          => 'A payment capture completed.',
			'PAYMENT.CAPTURE.DENIED'             => 'A payment capture was denied.',
			'PAYMENT.CAPTURE.REFUNDED'           => 'A payment capture was refunded.',
			'BILLING.SUBSCRIPTION.CREATED'       => 'A billing subscription was created.',
			'BILLING.SUBSCRIPTION.ACTIVATED'     => 'A billing subscription was activated.',
			'BILLING.SUBSCRIPTION.CANCELLED'     => 'A billing subscription was cancelled.',
			'BILLING.SUBSCRIPTION.SUSPENDED'     => 'A billing subscription was suspended.',
			'BILLING.SUBSCRIPTION.PAYMENT.FAILED' => 'A billing subscription payment failed.',
		);

		return $summaries[ $event_type ] ?? 'A webhook event occurred.';
	}
}
