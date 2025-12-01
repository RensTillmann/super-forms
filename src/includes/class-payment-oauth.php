<?php
/**
 * Payment OAuth Manager
 *
 * Handles OAuth flows for payment gateways (Stripe Connect, PayPal).
 * Supports both platform OAuth (Quick Connect) and manual API key configuration.
 *
 * @package    SUPER_Forms
 * @subpackage Payments
 * @since      6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SUPER_Payment_OAuth' ) ) :

	/**
	 * SUPER_Payment_OAuth Class
	 *
	 * Manages payment gateway OAuth connections:
	 * - Stripe Connect (Standard/Express)
	 * - PayPal OAuth
	 * - Manual API key fallback
	 *
	 * @since 6.5.0
	 */
	class SUPER_Payment_OAuth {

		/**
		 * Singleton instance
		 *
		 * @var SUPER_Payment_OAuth|null
		 */
		private static $instance = null;

		/**
		 * Platform server URL for token exchange
		 * This server holds the client_secret and handles secure token exchange
		 *
		 * @var string
		 */
		const PLATFORM_SERVER = 'https://super-forms.com/oauth';

		/**
		 * Stripe Connect OAuth URLs
		 *
		 * @var array
		 */
		private $stripe_urls = array(
			'authorize' => 'https://connect.stripe.com/oauth/authorize',
			'token'     => 'https://connect.stripe.com/oauth/token',
			'deauth'    => 'https://connect.stripe.com/oauth/deauthorize',
		);

		/**
		 * PayPal OAuth URLs (sandbox and live)
		 *
		 * @var array
		 */
		private $paypal_urls = array(
			'sandbox' => array(
				'authorize' => 'https://www.sandbox.paypal.com/connect',
				'token'     => 'https://api-m.sandbox.paypal.com/v1/oauth2/token',
			),
			'live' => array(
				'authorize' => 'https://www.paypal.com/connect',
				'token'     => 'https://api-m.paypal.com/v1/oauth2/token',
			),
		);

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Payment_OAuth
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			// Handle OAuth callbacks
			add_action( 'init', array( $this, 'handle_oauth_callback' ) );

			// Register REST routes for token exchange
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

			// Admin AJAX handlers
			add_action( 'wp_ajax_super_payment_connect', array( $this, 'ajax_initiate_connect' ) );
			add_action( 'wp_ajax_super_payment_disconnect', array( $this, 'ajax_disconnect' ) );
			add_action( 'wp_ajax_super_payment_status', array( $this, 'ajax_connection_status' ) );
			add_action( 'wp_ajax_super_payment_save_manual', array( $this, 'ajax_save_manual_keys' ) );
		}

		/**
		 * Register REST routes for webhook handling
		 */
		public function register_rest_routes() {
			// Callback endpoint for platform OAuth redirect
			register_rest_route( 'super-forms/v1', '/payment-oauth/callback', array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_oauth_callback' ),
				'permission_callback' => '__return_true', // Public - validated via state token
			) );
		}

		/**
		 * Initiate Stripe Connect OAuth flow
		 *
		 * @param array $args Connection arguments.
		 * @return string|WP_Error Authorization URL or error
		 */
		public function initiate_stripe_connect( $args = array() ) {
			$defaults = array(
				'mode'       => 'live', // 'test' or 'live'
				'return_url' => admin_url( 'admin.php?page=super_settings&tab=stripe' ),
			);
			$args = wp_parse_args( $args, $defaults );

			// Check if platform OAuth is available
			if ( ! $this->is_platform_oauth_available( 'stripe' ) ) {
				return new WP_Error(
					'platform_unavailable',
					__( 'Quick Connect is not available. Please use manual API key configuration.', 'super-forms' )
				);
			}

			// Generate state token for CSRF protection
			$state = $this->generate_state_token( array(
				'gateway'    => 'stripe',
				'mode'       => $args['mode'],
				'return_url' => $args['return_url'],
				'user_id'    => get_current_user_id(),
				'site_url'   => site_url(),
			) );

			// Build authorization URL
			// Note: client_id is the Super Forms platform client ID
			$params = array(
				'client_id'     => $this->get_platform_client_id( 'stripe' ),
				'redirect_uri'  => self::PLATFORM_SERVER . '/stripe/callback',
				'response_type' => 'code',
				'scope'         => 'read_write',
				'state'         => $state,
			);

			// Add suggested capabilities for Connect accounts
			$params['stripe_user[email]'] = wp_get_current_user()->user_email;
			$params['stripe_user[url]'] = site_url();

			$auth_url = $this->stripe_urls['authorize'] . '?' . http_build_query( $params );

			return $auth_url;
		}

		/**
		 * Initiate PayPal OAuth flow
		 *
		 * @param array $args Connection arguments.
		 * @return string|WP_Error Authorization URL or error
		 */
		public function initiate_paypal_connect( $args = array() ) {
			$defaults = array(
				'mode'       => 'live', // 'sandbox' or 'live'
				'return_url' => admin_url( 'admin.php?page=super_settings&tab=paypal' ),
			);
			$args = wp_parse_args( $args, $defaults );

			// Check if platform OAuth is available
			if ( ! $this->is_platform_oauth_available( 'paypal' ) ) {
				return new WP_Error(
					'platform_unavailable',
					__( 'Quick Connect is not available. Please use manual API key configuration.', 'super-forms' )
				);
			}

			// Generate state token
			$state = $this->generate_state_token( array(
				'gateway'    => 'paypal',
				'mode'       => $args['mode'],
				'return_url' => $args['return_url'],
				'user_id'    => get_current_user_id(),
				'site_url'   => site_url(),
			) );

			$urls = $args['mode'] === 'sandbox' ? $this->paypal_urls['sandbox'] : $this->paypal_urls['live'];

			// Build authorization URL
			$params = array(
				'client_id'     => $this->get_platform_client_id( 'paypal' ),
				'redirect_uri'  => self::PLATFORM_SERVER . '/paypal/callback',
				'response_type' => 'code',
				'scope'         => 'openid email https://uri.paypal.com/services/paypalattributes',
				'state'         => $state,
			);

			$auth_url = $urls['authorize'] . '?' . http_build_query( $params );

			return $auth_url;
		}

		/**
		 * Handle OAuth callback from platform server
		 */
		public function handle_oauth_callback() {
			// Check for payment OAuth callback
			if ( ! isset( $_GET['super_payment_oauth'] ) || $_GET['super_payment_oauth'] !== '1' ) {
				return;
			}

			// Verify state token
			if ( ! isset( $_GET['state'] ) ) {
				wp_die( __( 'Invalid payment OAuth callback: missing state', 'super-forms' ) );
			}

			$state = $this->validate_state_token( sanitize_text_field( $_GET['state'] ) );
			if ( ! $state ) {
				wp_die( __( 'Payment OAuth state expired or invalid. Please try again.', 'super-forms' ) );
			}

			$gateway = $state['gateway'];
			$return_url = $state['return_url'];

			// Check for errors
			if ( isset( $_GET['error'] ) ) {
				$error = sanitize_text_field( $_GET['error'] );
				wp_redirect( add_query_arg( array(
					'payment_oauth_error' => urlencode( $error ),
					'gateway'             => $gateway,
				), $return_url ) );
				exit;
			}

			// Process encrypted tokens from platform server
			if ( isset( $_GET['tokens'] ) ) {
				$encrypted_tokens = sanitize_text_field( $_GET['tokens'] );
				$result = $this->process_platform_tokens( $gateway, $encrypted_tokens, $state );

				if ( is_wp_error( $result ) ) {
					wp_redirect( add_query_arg( array(
						'payment_oauth_error' => urlencode( $result->get_error_message() ),
						'gateway'             => $gateway,
					), $return_url ) );
					exit;
				}

				// Success
				wp_redirect( add_query_arg( array(
					'payment_oauth_success' => '1',
					'gateway'               => $gateway,
				), $return_url ) );
				exit;
			}

			wp_die( __( 'Invalid payment OAuth callback', 'super-forms' ) );
		}

		/**
		 * REST callback for OAuth redirect from platform server
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return WP_REST_Response|WP_Error
		 */
		public function rest_oauth_callback( $request ) {
			$state_token = $request->get_param( 'state' );
			$tokens = $request->get_param( 'tokens' );
			$error = $request->get_param( 'error' );

			if ( ! $state_token ) {
				return new WP_Error( 'invalid_callback', 'Missing state parameter', array( 'status' => 400 ) );
			}

			$state = $this->validate_state_token( $state_token );
			if ( ! $state ) {
				return new WP_Error( 'invalid_state', 'Invalid or expired state', array( 'status' => 400 ) );
			}

			$return_url = $state['return_url'];

			// Handle error
			if ( $error ) {
				wp_redirect( add_query_arg( array(
					'payment_oauth_error' => urlencode( $error ),
					'gateway'             => $state['gateway'],
				), $return_url ) );
				exit;
			}

			// Process tokens
			if ( $tokens ) {
				$result = $this->process_platform_tokens( $state['gateway'], $tokens, $state );

				if ( is_wp_error( $result ) ) {
					wp_redirect( add_query_arg( array(
						'payment_oauth_error' => urlencode( $result->get_error_message() ),
						'gateway'             => $state['gateway'],
					), $return_url ) );
					exit;
				}

				wp_redirect( add_query_arg( array(
					'payment_oauth_success' => '1',
					'gateway'               => $state['gateway'],
				), $return_url ) );
				exit;
			}

			return new WP_Error( 'invalid_callback', 'Missing tokens', array( 'status' => 400 ) );
		}

		/**
		 * Process encrypted tokens from platform server
		 *
		 * @param string $gateway   Gateway identifier (stripe/paypal).
		 * @param string $encrypted Encrypted token data.
		 * @param array  $state     State data.
		 * @return bool|WP_Error
		 */
		private function process_platform_tokens( $gateway, $encrypted, $state ) {
			// Decrypt tokens using site-specific key
			$tokens = $this->decrypt_platform_tokens( $encrypted );

			if ( ! $tokens || ! is_array( $tokens ) ) {
				return new WP_Error( 'decrypt_failed', __( 'Failed to process connection tokens', 'super-forms' ) );
			}

			$user_id = $state['user_id'];
			$mode = $state['mode'];

			// Store tokens using credentials system
			if ( class_exists( 'SUPER_Automation_Credentials' ) ) {
				$credentials = SUPER_Automation_Credentials::instance();

				if ( $gateway === 'stripe' ) {
					// Store Stripe credentials
					$credentials->store( 'stripe', $mode . '_access_token', $tokens['access_token'], $user_id );

					if ( isset( $tokens['refresh_token'] ) ) {
						$credentials->store( 'stripe', $mode . '_refresh_token', $tokens['refresh_token'], $user_id );
					}

					if ( isset( $tokens['stripe_user_id'] ) ) {
						$credentials->store( 'stripe', $mode . '_user_id', $tokens['stripe_user_id'], $user_id );
					}

					// Store connection metadata
					$credentials->store( 'stripe', $mode . '_connected_at', time(), $user_id );
					$credentials->store( 'stripe', $mode . '_connection_type', 'platform_oauth', $user_id );

				} elseif ( $gateway === 'paypal' ) {
					// Store PayPal credentials
					$credentials->store( 'paypal', $mode . '_access_token', $tokens['access_token'], $user_id );

					if ( isset( $tokens['refresh_token'] ) ) {
						$credentials->store( 'paypal', $mode . '_refresh_token', $tokens['refresh_token'], $user_id );
					}

					if ( isset( $tokens['merchant_id'] ) ) {
						$credentials->store( 'paypal', $mode . '_merchant_id', $tokens['merchant_id'], $user_id );
					}

					$credentials->store( 'paypal', $mode . '_connected_at', time(), $user_id );
					$credentials->store( 'paypal', $mode . '_connection_type', 'platform_oauth', $user_id );
				}
			}

			// Log successful connection
			if ( class_exists( 'SUPER_Automation_Logger' ) ) {
				SUPER_Automation_Logger::info( 'Payment gateway connected via OAuth', array(
					'gateway' => $gateway,
					'mode'    => $mode,
					'user_id' => $user_id,
				) );
			}

			// Fire action for auto-webhook configuration
			do_action( 'super_payment_oauth_connected', $gateway, $mode, $tokens, $user_id );

			return true;
		}

		/**
		 * Save manual API keys
		 *
		 * @param string $gateway Gateway identifier.
		 * @param array  $keys    API keys.
		 * @param string $mode    Mode (test/live or sandbox/live).
		 * @return bool|WP_Error
		 */
		public function save_manual_keys( $gateway, $keys, $mode = 'live' ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error( 'unauthorized', __( 'Permission denied', 'super-forms' ) );
			}

			$user_id = get_current_user_id();

			if ( class_exists( 'SUPER_Automation_Credentials' ) ) {
				$credentials = SUPER_Automation_Credentials::instance();

				if ( $gateway === 'stripe' ) {
					// Validate Stripe keys format
					if ( isset( $keys['secret_key'] ) ) {
						$prefix = $mode === 'test' ? 'sk_test_' : 'sk_live_';
						if ( strpos( $keys['secret_key'], $prefix ) !== 0 && strpos( $keys['secret_key'], 'rk_' ) !== 0 ) {
							return new WP_Error( 'invalid_key', __( 'Invalid Stripe secret key format', 'super-forms' ) );
						}
						$credentials->store( 'stripe', $mode . '_secret_key', $keys['secret_key'], $user_id );
					}

					if ( isset( $keys['publishable_key'] ) ) {
						$credentials->store( 'stripe', $mode . '_publishable_key', $keys['publishable_key'], $user_id );
					}

					if ( isset( $keys['webhook_secret'] ) ) {
						$credentials->store( 'stripe', $mode . '_webhook_secret', $keys['webhook_secret'], $user_id );
					}

					$credentials->store( 'stripe', $mode . '_connection_type', 'manual', $user_id );
					$credentials->store( 'stripe', $mode . '_connected_at', time(), $user_id );

				} elseif ( $gateway === 'paypal' ) {
					if ( isset( $keys['client_id'] ) ) {
						$credentials->store( 'paypal', $mode . '_client_id', $keys['client_id'], $user_id );
					}

					if ( isset( $keys['client_secret'] ) ) {
						$credentials->store( 'paypal', $mode . '_client_secret', $keys['client_secret'], $user_id );
					}

					if ( isset( $keys['webhook_id'] ) ) {
						$credentials->store( 'paypal', $mode . '_webhook_id', $keys['webhook_id'], $user_id );
					}

					$credentials->store( 'paypal', $mode . '_connection_type', 'manual', $user_id );
					$credentials->store( 'paypal', $mode . '_connected_at', time(), $user_id );
				}
			}

			// Log manual connection
			if ( class_exists( 'SUPER_Automation_Logger' ) ) {
				SUPER_Automation_Logger::info( 'Payment gateway configured manually', array(
					'gateway' => $gateway,
					'mode'    => $mode,
					'user_id' => $user_id,
				) );
			}

			do_action( 'super_payment_manual_connected', $gateway, $mode, $user_id );

			return true;
		}

		/**
		 * Disconnect a payment gateway
		 *
		 * @param string $gateway Gateway identifier.
		 * @param string $mode    Mode (test/live).
		 * @return bool
		 */
		public function disconnect( $gateway, $mode = 'live' ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$user_id = get_current_user_id();

			if ( class_exists( 'SUPER_Automation_Credentials' ) ) {
				$credentials = SUPER_Automation_Credentials::instance();

				// Delete all credentials for this gateway/mode
				$keys_to_delete = array(
					$mode . '_access_token',
					$mode . '_refresh_token',
					$mode . '_secret_key',
					$mode . '_publishable_key',
					$mode . '_webhook_secret',
					$mode . '_client_id',
					$mode . '_client_secret',
					$mode . '_webhook_id',
					$mode . '_user_id',
					$mode . '_merchant_id',
					$mode . '_connected_at',
					$mode . '_connection_type',
				);

				foreach ( $keys_to_delete as $key ) {
					$credentials->delete( $gateway, $key, $user_id );
				}
			}

			// Log disconnection
			if ( class_exists( 'SUPER_Automation_Logger' ) ) {
				SUPER_Automation_Logger::info( 'Payment gateway disconnected', array(
					'gateway' => $gateway,
					'mode'    => $mode,
					'user_id' => $user_id,
				) );
			}

			do_action( 'super_payment_disconnected', $gateway, $mode, $user_id );

			return true;
		}

		/**
		 * Check if a gateway is connected
		 *
		 * @param string $gateway Gateway identifier.
		 * @param string $mode    Mode (test/live).
		 * @return bool
		 */
		public function is_connected( $gateway, $mode = 'live' ) {
			$user_id = get_current_user_id();

			if ( ! class_exists( 'SUPER_Automation_Credentials' ) ) {
				return false;
			}

			$credentials = SUPER_Automation_Credentials::instance();

			// Check for OAuth connection
			if ( $credentials->has( $gateway, $mode . '_access_token', $user_id ) ) {
				return true;
			}

			// Check for manual connection
			if ( $gateway === 'stripe' ) {
				return $credentials->has( $gateway, $mode . '_secret_key', $user_id );
			} elseif ( $gateway === 'paypal' ) {
				return $credentials->has( $gateway, $mode . '_client_id', $user_id ) &&
				       $credentials->has( $gateway, $mode . '_client_secret', $user_id );
			}

			return false;
		}

		/**
		 * Get connection status for a gateway
		 *
		 * @param string $gateway Gateway identifier.
		 * @param string $mode    Mode (test/live).
		 * @return array
		 */
		public function get_connection_status( $gateway, $mode = 'live' ) {
			$user_id = get_current_user_id();

			$status = array(
				'connected'       => false,
				'connection_type' => null,
				'connected_at'    => null,
				'account_id'      => null,
			);

			if ( ! $this->is_connected( $gateway, $mode ) ) {
				return $status;
			}

			$status['connected'] = true;

			if ( class_exists( 'SUPER_Automation_Credentials' ) ) {
				$credentials = SUPER_Automation_Credentials::instance();

				$status['connection_type'] = $credentials->get( $gateway, $mode . '_connection_type', $user_id );
				$status['connected_at'] = $credentials->get( $gateway, $mode . '_connected_at', $user_id );

				if ( $gateway === 'stripe' ) {
					$status['account_id'] = $credentials->get( $gateway, $mode . '_user_id', $user_id );
				} elseif ( $gateway === 'paypal' ) {
					$status['account_id'] = $credentials->get( $gateway, $mode . '_merchant_id', $user_id );
				}
			}

			return $status;
		}

		/**
		 * Get API credentials for a gateway
		 *
		 * @param string $gateway Gateway identifier.
		 * @param string $mode    Mode (test/live).
		 * @return array|null
		 */
		public function get_api_credentials( $gateway, $mode = 'live' ) {
			$user_id = get_current_user_id();

			if ( ! class_exists( 'SUPER_Automation_Credentials' ) ) {
				return null;
			}

			$credentials = SUPER_Automation_Credentials::instance();

			if ( $gateway === 'stripe' ) {
				// Try OAuth token first
				$access_token = $credentials->get( 'stripe', $mode . '_access_token', $user_id );
				if ( $access_token ) {
					return array(
						'secret_key' => $access_token,
						'type'       => 'oauth',
					);
				}

				// Fall back to manual key
				$secret_key = $credentials->get( 'stripe', $mode . '_secret_key', $user_id );
				if ( $secret_key ) {
					return array(
						'secret_key'       => $secret_key,
						'publishable_key'  => $credentials->get( 'stripe', $mode . '_publishable_key', $user_id ),
						'webhook_secret'   => $credentials->get( 'stripe', $mode . '_webhook_secret', $user_id ),
						'type'             => 'manual',
					);
				}

			} elseif ( $gateway === 'paypal' ) {
				// Try OAuth token first
				$access_token = $credentials->get( 'paypal', $mode . '_access_token', $user_id );
				if ( $access_token ) {
					return array(
						'access_token' => $access_token,
						'merchant_id'  => $credentials->get( 'paypal', $mode . '_merchant_id', $user_id ),
						'type'         => 'oauth',
					);
				}

				// Fall back to manual credentials
				$client_id = $credentials->get( 'paypal', $mode . '_client_id', $user_id );
				$client_secret = $credentials->get( 'paypal', $mode . '_client_secret', $user_id );
				if ( $client_id && $client_secret ) {
					return array(
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
						'webhook_id'    => $credentials->get( 'paypal', $mode . '_webhook_id', $user_id ),
						'type'          => 'manual',
					);
				}
			}

			return null;
		}

		// =========================================================================
		// AJAX Handlers
		// =========================================================================

		/**
		 * AJAX: Initiate OAuth connection
		 */
		public function ajax_initiate_connect() {
			check_ajax_referer( 'super_payment_oauth', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'super-forms' ) ) );
			}

			$gateway = isset( $_POST['gateway'] ) ? sanitize_text_field( $_POST['gateway'] ) : '';
			$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'live';

			if ( ! in_array( $gateway, array( 'stripe', 'paypal' ), true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gateway', 'super-forms' ) ) );
			}

			$args = array(
				'mode'       => $mode,
				'return_url' => isset( $_POST['return_url'] ) ? esc_url_raw( $_POST['return_url'] ) : '',
			);

			if ( $gateway === 'stripe' ) {
				$url = $this->initiate_stripe_connect( $args );
			} else {
				$url = $this->initiate_paypal_connect( $args );
			}

			if ( is_wp_error( $url ) ) {
				wp_send_json_error( array( 'message' => $url->get_error_message() ) );
			}

			wp_send_json_success( array( 'redirect_url' => $url ) );
		}

		/**
		 * AJAX: Disconnect gateway
		 */
		public function ajax_disconnect() {
			check_ajax_referer( 'super_payment_oauth', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'super-forms' ) ) );
			}

			$gateway = isset( $_POST['gateway'] ) ? sanitize_text_field( $_POST['gateway'] ) : '';
			$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'live';

			if ( ! in_array( $gateway, array( 'stripe', 'paypal' ), true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gateway', 'super-forms' ) ) );
			}

			$this->disconnect( $gateway, $mode );

			wp_send_json_success( array( 'message' => __( 'Disconnected successfully', 'super-forms' ) ) );
		}

		/**
		 * AJAX: Get connection status
		 */
		public function ajax_connection_status() {
			check_ajax_referer( 'super_payment_oauth', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'super-forms' ) ) );
			}

			$gateway = isset( $_POST['gateway'] ) ? sanitize_text_field( $_POST['gateway'] ) : '';
			$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'live';

			if ( ! in_array( $gateway, array( 'stripe', 'paypal' ), true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gateway', 'super-forms' ) ) );
			}

			$status = $this->get_connection_status( $gateway, $mode );

			wp_send_json_success( $status );
		}

		/**
		 * AJAX: Save manual API keys
		 */
		public function ajax_save_manual_keys() {
			check_ajax_referer( 'super_payment_oauth', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'super-forms' ) ) );
			}

			$gateway = isset( $_POST['gateway'] ) ? sanitize_text_field( $_POST['gateway'] ) : '';
			$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'live';
			$keys = isset( $_POST['keys'] ) ? array_map( 'sanitize_text_field', $_POST['keys'] ) : array();

			if ( ! in_array( $gateway, array( 'stripe', 'paypal' ), true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gateway', 'super-forms' ) ) );
			}

			$result = $this->save_manual_keys( $gateway, $keys, $mode );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'API keys saved successfully', 'super-forms' ) ) );
		}

		// =========================================================================
		// Helper Methods
		// =========================================================================

		/**
		 * Generate state token for CSRF protection
		 *
		 * @param array $data State data.
		 * @return string
		 */
		private function generate_state_token( $data ) {
			$token = wp_generate_password( 32, false );

			set_transient( 'super_payment_oauth_' . $token, $data, 600 ); // 10 minutes

			return $token;
		}

		/**
		 * Validate and retrieve state token data
		 *
		 * @param string $token State token.
		 * @return array|false
		 */
		private function validate_state_token( $token ) {
			$data = get_transient( 'super_payment_oauth_' . $token );

			if ( $data ) {
				delete_transient( 'super_payment_oauth_' . $token );
			}

			return $data;
		}

		/**
		 * Check if platform OAuth is available
		 *
		 * @param string $gateway Gateway identifier.
		 * @return bool
		 */
		public function is_platform_oauth_available( $gateway ) {
			// Check if platform client ID is configured
			$client_id = $this->get_platform_client_id( $gateway );

			if ( ! $client_id ) {
				return false;
			}

			// Check if platform server is reachable (cached check)
			$cache_key = 'super_platform_oauth_available_' . $gateway;
			$available = get_transient( $cache_key );

			if ( $available === false ) {
				// Check platform server health endpoint
				$response = wp_remote_get( self::PLATFORM_SERVER . '/health', array(
					'timeout' => 5,
				) );

				$available = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;

				// Cache result for 1 hour
				set_transient( $cache_key, $available ? '1' : '0', HOUR_IN_SECONDS );
			}

			return $available === '1' || $available === true;
		}

		/**
		 * Get platform client ID for a gateway
		 *
		 * @param string $gateway Gateway identifier.
		 * @return string|null
		 */
		private function get_platform_client_id( $gateway ) {
			// These would be set by Super Forms platform configuration
			// For now, return null to indicate platform OAuth is not yet available
			$client_ids = array(
				'stripe' => defined( 'SUPER_STRIPE_PLATFORM_CLIENT_ID' ) ? SUPER_STRIPE_PLATFORM_CLIENT_ID : null,
				'paypal' => defined( 'SUPER_PAYPAL_PLATFORM_CLIENT_ID' ) ? SUPER_PAYPAL_PLATFORM_CLIENT_ID : null,
			);

			return $client_ids[ $gateway ] ?? null;
		}

		/**
		 * Decrypt tokens received from platform server
		 *
		 * @param string $encrypted Encrypted token data.
		 * @return array|false
		 */
		private function decrypt_platform_tokens( $encrypted ) {
			// Tokens are encrypted with a site-specific key derived from AUTH_KEY
			$key = hash( 'sha256', AUTH_KEY . site_url(), true );

			// Base64 decode
			$data = base64_decode( $encrypted );
			if ( ! $data ) {
				return false;
			}

			// Extract IV and ciphertext
			$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
			$iv = substr( $data, 0, $iv_length );
			$ciphertext = substr( $data, $iv_length );

			// Decrypt
			$decrypted = openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
			if ( ! $decrypted ) {
				return false;
			}

			return json_decode( $decrypted, true );
		}
	}

endif;
