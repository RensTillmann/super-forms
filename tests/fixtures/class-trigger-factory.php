<?php
/**
 * Test Trigger Factory
 *
 * Creates test triggers with various action types for PHPUnit testing
 *
 * @package Super_Forms
 * @subpackage Tests/Fixtures
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SUPER_Test_Trigger_Factory Class
 *
 * Factory for creating test triggers with all action types
 */
class SUPER_Test_Trigger_Factory {

	/**
	 * Created trigger IDs for cleanup
	 *
	 * @var array
	 */
	private static $created_triggers = array();

	/**
	 * Create a set of triggers for a form
	 *
	 * @param int   $form_id Form ID to attach triggers to.
	 * @param array $options Which triggers to create.
	 * @return array Created trigger IDs keyed by type
	 */
	public static function create_trigger_set( $form_id, $options = array() ) {
		$defaults = array(
			'on_submit_log'          => true,
			'on_submit_webhook'      => false,
			'on_high_budget'         => false,
			'on_spam_detected'       => false,
			'on_entry_created'       => false,
			'on_payment_success'     => false,
			'on_subscription_created' => false,
		);
		$options = wp_parse_args( $options, $defaults );

		$triggers = array();

		if ( $options['on_submit_log'] ) {
			$triggers['on_submit_log'] = self::create_submit_log_trigger( $form_id );
		}

		if ( $options['on_submit_webhook'] ) {
			$triggers['on_submit_webhook'] = self::create_submit_webhook_trigger( $form_id );
		}

		if ( $options['on_high_budget'] ) {
			$triggers['on_high_budget'] = self::create_conditional_trigger( $form_id );
		}

		if ( $options['on_spam_detected'] ) {
			$triggers['on_spam_detected'] = self::create_spam_trigger( $form_id );
		}

		if ( $options['on_entry_created'] ) {
			$triggers['on_entry_created'] = self::create_entry_trigger( $form_id );
		}

		if ( $options['on_payment_success'] ) {
			$triggers['on_payment_success'] = self::create_payment_trigger( $form_id );
		}

		if ( $options['on_subscription_created'] ) {
			$triggers['on_subscription_created'] = self::create_subscription_trigger( $form_id );
		}

		return $triggers;
	}

	/**
	 * Create a simple log trigger on form.submitted
	 *
	 * @param int    $form_id Form ID.
	 * @param string $message Log message (supports tags).
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_submit_log_trigger( $form_id, $message = 'Form {form_id} submitted by {email}' ) {
		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'Log on Submit',
				'event_id'     => 'form.submitted',
			),
			array(
				array(
					'action_type'   => 'log_message',
					'action_config' => array(
						'message'   => $message,
						'log_level' => 'info',
					),
				),
			)
		);
	}

	/**
	 * Create a webhook trigger on form.submitted
	 *
	 * @param int    $form_id     Form ID.
	 * @param string $webhook_url Webhook URL.
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_submit_webhook_trigger( $form_id, $webhook_url = 'https://httpbin.org/post' ) {
		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'Webhook on Submit',
				'event_id'     => 'form.submitted',
			),
			array(
				array(
					'action_type'   => 'webhook',
					'action_config' => array(
						'url'         => $webhook_url,
						'method'      => 'POST',
						'payload'     => '{"form_id": "{form_id}", "email": "{email}"}',
						'content_type' => 'application/json',
					),
				),
			)
		);
	}

	/**
	 * Create a conditional trigger (budget > $25K)
	 *
	 * @param int   $form_id   Form ID.
	 * @param float $threshold Budget threshold (default 25000).
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_conditional_trigger( $form_id, $threshold = 25000 ) {
		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'High Budget Alert',
				'event_id'     => 'form.submitted',
				'conditions'   => array(
					'operator' => 'AND',
					'rules'    => array(
						array(
							'field'    => 'budget',
							'operator' => '>',
							'value'    => (string) $threshold,
						),
					),
				),
			),
			array(
				array(
					'action_type'   => 'log_message',
					'action_config' => array(
						'message'   => 'High-budget lead: {email} with budget {budget}',
						'log_level' => 'warning',
					),
				),
			)
		);
	}

	/**
	 * Create a spam detection trigger
	 *
	 * @param int $form_id Form ID.
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_spam_trigger( $form_id ) {
		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'Spam Handler',
				'event_id'     => 'form.spam_detected',
			),
			array(
				array(
					'action_type'   => 'log_message',
					'action_config' => array(
						'message'   => 'Spam detected from IP {ip}',
						'log_level' => 'warning',
					),
				),
			)
		);
	}

	/**
	 * Create an entry created trigger
	 *
	 * @param int $form_id Form ID.
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_entry_trigger( $form_id ) {
		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'Entry Created Handler',
				'event_id'     => 'entry.created',
			),
			array(
				array(
					'action_type'   => 'log_message',
					'action_config' => array(
						'message'   => 'Entry {entry_id} created for form {form_id}',
						'log_level' => 'info',
					),
				),
			)
		);
	}

	/**
	 * Create a payment success trigger
	 *
	 * @param int    $form_id  Form ID.
	 * @param string $gateway  Payment gateway (stripe/paypal).
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_payment_trigger( $form_id, $gateway = 'stripe' ) {
		$event_id = 'payment.' . $gateway . '.checkout_completed';

		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => ucfirst( $gateway ) . ' Payment Success',
				'event_id'     => $event_id,
			),
			array(
				array(
					'action_type'   => 'log_message',
					'action_config' => array(
						'message'   => 'Payment received: {amount} {currency} from {email}',
						'log_level' => 'info',
					),
				),
				array(
					'action_type'   => 'update_entry_status',
					'action_config' => array(
						'status' => 'paid',
					),
				),
			)
		);
	}

	/**
	 * Create a subscription trigger
	 *
	 * @param int    $form_id Form ID.
	 * @param string $gateway Payment gateway (stripe/paypal).
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_subscription_trigger( $form_id, $gateway = 'stripe' ) {
		$event_id = 'subscription.' . $gateway . '.created';

		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => ucfirst( $gateway ) . ' Subscription Created',
				'event_id'     => $event_id,
			),
			array(
				array(
					'action_type'   => 'log_message',
					'action_config' => array(
						'message'   => 'Subscription {subscription_id} created for {email}',
						'log_level' => 'info',
					),
				),
			)
		);
	}

	/**
	 * Create a trigger with send_email action
	 *
	 * @param int   $form_id    Form ID.
	 * @param array $email_config Email configuration.
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_email_trigger( $form_id, $email_config = array() ) {
		$defaults = array(
			'to'      => '{email}',
			'subject' => 'Thank you for your submission',
			'body'    => "Dear {name},\n\nThank you for contacting us.\n\nBest regards",
		);
		$config = wp_parse_args( $email_config, $defaults );

		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'Send Email on Submit',
				'event_id'     => 'form.submitted',
			),
			array(
				array(
					'action_type'   => 'send_email',
					'action_config' => $config,
				),
			)
		);
	}

	/**
	 * Create a trigger with HTTP request action
	 *
	 * @param int   $form_id       Form ID.
	 * @param array $request_config HTTP request configuration.
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_http_request_trigger( $form_id, $request_config = array() ) {
		$defaults = array(
			'url'           => 'https://httpbin.org/post',
			'method'        => 'POST',
			'body_format'   => 'json',
			'body'          => '{"email": "{email}", "name": "{name}"}',
			'auth_type'     => 'none',
		);
		$config = wp_parse_args( $request_config, $defaults );

		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'HTTP Request on Submit',
				'event_id'     => 'form.submitted',
			),
			array(
				array(
					'action_type'   => 'http_request',
					'action_config' => $config,
				),
			)
		);
	}

	/**
	 * Create a trigger with multiple actions
	 *
	 * @param int   $form_id Form ID.
	 * @param array $action_types Array of action type strings.
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_multi_action_trigger( $form_id, $action_types = array( 'log_message', 'webhook' ) ) {
		$actions = array();

		foreach ( $action_types as $index => $action_type ) {
			$actions[] = array(
				'action_type'     => $action_type,
				'action_config'   => self::get_default_action_config( $action_type ),
				'execution_order' => $index + 1,
			);
		}

		return self::create_trigger(
			$form_id,
			array(
				'trigger_name' => 'Multi-Action Trigger',
				'event_id'     => 'form.submitted',
			),
			$actions
		);
	}

	/**
	 * Create a global trigger (fires for all forms)
	 *
	 * @param string $event_id Event to listen for.
	 * @param array  $actions  Actions to execute.
	 * @return int|WP_Error Trigger ID
	 */
	public static function create_global_trigger( $event_id = 'form.submitted', $actions = array() ) {
		if ( empty( $actions ) ) {
			$actions = array(
				array(
					'action_type'   => 'log_message',
					'action_config' => array(
						'message'   => 'Global trigger fired for form {form_id}',
						'log_level' => 'debug',
					),
				),
			);
		}

		return self::create_trigger(
			null, // Global - no specific form
			array(
				'trigger_name' => 'Global Trigger',
				'scope'        => 'global',
				'scope_id'     => null,
				'event_id'     => $event_id,
			),
			$actions
		);
	}

	/**
	 * Clean up all created triggers
	 */
	public static function cleanup() {
		if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) {
			return;
		}

		foreach ( self::$created_triggers as $trigger_id ) {
			SUPER_Trigger_DAL::delete_trigger( $trigger_id );
		}
		self::$created_triggers = array();
	}

	/**
	 * Get all created trigger IDs
	 *
	 * @return array Trigger IDs
	 */
	public static function get_created_triggers() {
		return self::$created_triggers;
	}

	// =========================================================================
	// Private Helper Methods
	// =========================================================================

	/**
	 * Create a trigger with actions
	 *
	 * @param int|null $form_id      Form ID (null for global triggers).
	 * @param array    $trigger_data Trigger configuration.
	 * @param array    $actions      Actions to attach.
	 * @return int|WP_Error Trigger ID
	 */
	private static function create_trigger( $form_id, $trigger_data, $actions ) {
		if ( ! class_exists( 'SUPER_Trigger_DAL' ) ) {
			return new WP_Error( 'missing_class', 'SUPER_Trigger_DAL class not found' );
		}

		$defaults = array(
			'trigger_name'    => 'Test Trigger ' . uniqid(),
			'scope'           => $form_id ? 'form' : 'global',
			'scope_id'        => $form_id,
			'event_id'        => 'form.submitted',
			'enabled'         => 1,
			'execution_order' => 10,
			'conditions'      => null,
		);

		$data = wp_parse_args( $trigger_data, $defaults );

		// Convert conditions to JSON if array
		if ( is_array( $data['conditions'] ) ) {
			$data['conditions'] = wp_json_encode( $data['conditions'] );
		}

		$trigger_id = SUPER_Trigger_DAL::create_trigger( $data );

		if ( is_wp_error( $trigger_id ) ) {
			return $trigger_id;
		}

		self::$created_triggers[] = $trigger_id;

		// Add actions
		foreach ( $actions as $index => $action ) {
			$action_defaults = array(
				'execution_order' => $index + 1,
				'enabled'         => 1,
			);
			$action_data = wp_parse_args( $action, $action_defaults );

			// Convert action_config to JSON if array
			if ( is_array( $action_data['action_config'] ) ) {
				$action_data['action_config'] = wp_json_encode( $action_data['action_config'] );
			}

			SUPER_Trigger_DAL::create_action( $trigger_id, $action_data );
		}

		return $trigger_id;
	}

	/**
	 * Get default configuration for an action type
	 *
	 * @param string $action_type Action type.
	 * @return array Default configuration
	 */
	private static function get_default_action_config( $action_type ) {
		$configs = array(
			'log_message' => array(
				'message'   => 'Action {action_type} executed for form {form_id}',
				'log_level' => 'debug',
			),
			'webhook' => array(
				'url'          => 'https://httpbin.org/post',
				'method'       => 'POST',
				'payload'      => '{"form_id": "{form_id}"}',
				'content_type' => 'application/json',
			),
			'send_email' => array(
				'to'      => 'test@example.com',
				'subject' => 'Test Email',
				'body'    => 'Test email body',
			),
			'http_request' => array(
				'url'         => 'https://httpbin.org/post',
				'method'      => 'POST',
				'body_format' => 'json',
				'body'        => '{}',
			),
			'update_entry_status' => array(
				'status' => 'processed',
			),
			'update_entry_field' => array(
				'field_name'  => 'processed',
				'field_value' => 'yes',
			),
			'set_variable' => array(
				'variable_name'  => 'test_var',
				'variable_value' => 'test_value',
			),
			'run_hook' => array(
				'hook_name' => 'super_test_hook',
				'hook_type' => 'action',
			),
		);

		return isset( $configs[ $action_type ] ) ? $configs[ $action_type ] : array();
	}
}
