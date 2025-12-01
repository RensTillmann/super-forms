<?php
/**
 * Trigger OAuth Manager
 *
 * Handles OAuth 2.0 authentication flows for third-party service integrations.
 * Supports authorization code flow with PKCE for enhanced security.
 *
 * @package    SUPER_Forms
 * @subpackage Triggers
 * @since      6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_OAuth' ) ) :

	/**
	 * SUPER_Automation_OAuth Class
	 *
	 * Manages OAuth 2.0 authorization flows including:
	 * - Authorization code flow
	 * - Token refresh
	 * - PKCE support
	 * - Provider registration
	 *
	 * @since 6.5.0
	 */
	class SUPER_Automation_OAuth {

		/**
		 * Singleton instance
		 *
		 * @var SUPER_Automation_OAuth|null
		 */
		private static $instance = null;

		/**
		 * Registered OAuth providers
		 *
		 * @var array
		 */
		private $providers = array();

		/**
		 * State transient prefix
		 *
		 * @var string
		 */
		const STATE_PREFIX = 'super_oauth_state_';

		/**
		 * State expiration in seconds (10 minutes)
		 *
		 * @var int
		 */
		const STATE_EXPIRY = 600;

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Automation_OAuth
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
			add_action( 'init', array( $this, 'handle_oauth_callback' ) );
			add_action( 'admin_init', array( $this, 'register_default_providers' ) );

			// Allow add-ons to register providers
			add_action( 'super_automation_oauth_register_providers', array( $this, 'do_provider_registration' ) );
		}

		/**
		 * Register default OAuth providers
		 */
		public function register_default_providers() {
			// Google
			$this->register_provider( 'google', array(
				'name'          => 'Google',
				'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
				'token_url'     => 'https://oauth2.googleapis.com/token',
				'scopes'        => array(
					'email',
					'profile',
					'https://www.googleapis.com/auth/spreadsheets',
					'https://www.googleapis.com/auth/drive.file',
				),
				'supports_pkce' => true,
			) );

			// Microsoft
			$this->register_provider( 'microsoft', array(
				'name'          => 'Microsoft',
				'authorize_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
				'token_url'     => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
				'scopes'        => array(
					'openid',
					'email',
					'profile',
					'offline_access',
					'User.Read',
				),
				'supports_pkce' => true,
			) );

			// Salesforce
			$this->register_provider( 'salesforce', array(
				'name'          => 'Salesforce',
				'authorize_url' => 'https://login.salesforce.com/services/oauth2/authorize',
				'token_url'     => 'https://login.salesforce.com/services/oauth2/token',
				'scopes'        => array( 'api', 'refresh_token' ),
				'supports_pkce' => true,
			) );

			// HubSpot
			$this->register_provider( 'hubspot', array(
				'name'          => 'HubSpot',
				'authorize_url' => 'https://app.hubspot.com/oauth/authorize',
				'token_url'     => 'https://api.hubapi.com/oauth/v1/token',
				'scopes'        => array(
					'crm.objects.contacts.read',
					'crm.objects.contacts.write',
				),
				'supports_pkce' => false,
			) );

			// Slack
			$this->register_provider( 'slack', array(
				'name'          => 'Slack',
				'authorize_url' => 'https://slack.com/oauth/v2/authorize',
				'token_url'     => 'https://slack.com/api/oauth.v2.access',
				'scopes'        => array(
					'chat:write',
					'channels:read',
				),
				'supports_pkce' => false,
			) );

			// Allow add-ons to register custom providers
			do_action( 'super_automation_oauth_register_providers', $this );
		}

		/**
		 * Action hook for provider registration by add-ons
		 *
		 * @param SUPER_Automation_OAuth $oauth OAuth instance
		 */
		public function do_provider_registration( $oauth ) {
			// This allows add-ons to call $oauth->register_provider()
		}

		/**
		 * Register an OAuth provider
		 *
		 * @param string $provider_id Unique provider identifier
		 * @param array  $config      Provider configuration
		 * @return bool
		 */
		public function register_provider( $provider_id, $config ) {
			$defaults = array(
				'name'          => $provider_id,
				'authorize_url' => '',
				'token_url'     => '',
				'scopes'        => array(),
				'client_id'     => '',
				'client_secret' => '',
				'supports_pkce' => false,
				'extra_params'  => array(),
			);

			$this->providers[ $provider_id ] = wp_parse_args( $config, $defaults );

			return true;
		}

		/**
		 * Get a registered provider
		 *
		 * @param string $provider_id Provider identifier
		 * @return array|null Provider config or null if not found
		 */
		public function get_provider( $provider_id ) {
			return $this->providers[ $provider_id ] ?? null;
		}

		/**
		 * Get all registered providers
		 *
		 * @return array
		 */
		public function get_providers() {
			return $this->providers;
		}

		/**
		 * Initiate OAuth authorization flow
		 *
		 * @param string     $provider_id Provider identifier
		 * @param array|null $scopes      Optional override scopes
		 * @param array      $state_data  Additional state data to preserve
		 * @return string|WP_Error Authorization URL or error
		 */
		public function initiate( $provider_id, $scopes = null, $state_data = array() ) {
			$provider = $this->get_provider( $provider_id );
			if ( ! $provider ) {
				return new WP_Error( 'invalid_provider', __( 'OAuth provider not found', 'super-forms' ) );
			}

			// Get client credentials from settings or credentials storage
			$client_id = $this->get_client_id( $provider_id );
			$client_secret = $this->get_client_secret( $provider_id );

			if ( ! $client_id ) {
				return new WP_Error( 'no_client_id', __( 'OAuth client ID not configured', 'super-forms' ) );
			}

			// Generate state token for CSRF protection
			$state_key = wp_generate_password( 32, false );

			// Generate PKCE challenge if supported
			$pkce = null;
			if ( $provider['supports_pkce'] ) {
				$pkce = $this->generate_pkce();
			}

			// Store state data
			$state = array(
				'provider'   => $provider_id,
				'user_id'    => get_current_user_id(),
				'return_url' => $state_data['return_url'] ?? admin_url( 'admin.php?page=super-triggers&tab=integrations' ),
				'pkce'       => $pkce,
				'extra'      => $state_data,
			);

			set_transient( self::STATE_PREFIX . $state_key, $state, self::STATE_EXPIRY );

			// Build authorization URL
			$params = array(
				'client_id'     => $client_id,
				'redirect_uri'  => $this->get_redirect_uri(),
				'response_type' => 'code',
				'scope'         => implode( ' ', $scopes ?: $provider['scopes'] ),
				'state'         => $state_key,
				'access_type'   => 'offline', // Request refresh token
				'prompt'        => 'consent', // Force consent to get refresh token
			);

			// Add PKCE parameters
			if ( $pkce ) {
				$params['code_challenge'] = $pkce['challenge'];
				$params['code_challenge_method'] = 'S256';
			}

			// Add any provider-specific extra parameters
			$params = array_merge( $params, $provider['extra_params'] );

			$auth_url = $provider['authorize_url'] . '?' . http_build_query( $params );

			return $auth_url;
		}

		/**
		 * Handle OAuth callback
		 */
		public function handle_oauth_callback() {
			// Check for OAuth callback parameters
			if ( ! isset( $_GET['super_oauth_callback'] ) || $_GET['super_oauth_callback'] !== '1' ) {
				return;
			}

			// Verify state
			if ( ! isset( $_GET['state'] ) ) {
				wp_die( __( 'Invalid OAuth callback: missing state', 'super-forms' ) );
			}

			$state_key = sanitize_text_field( $_GET['state'] );
			$state = get_transient( self::STATE_PREFIX . $state_key );

			if ( ! $state ) {
				wp_die( __( 'OAuth state expired or invalid. Please try again.', 'super-forms' ) );
			}

			// Delete transient immediately
			delete_transient( self::STATE_PREFIX . $state_key );

			// Check for errors
			if ( isset( $_GET['error'] ) ) {
				$error = sanitize_text_field( $_GET['error'] );
				$error_description = isset( $_GET['error_description'] ) ?
					sanitize_text_field( $_GET['error_description'] ) : $error;

				wp_redirect( add_query_arg( array(
					'oauth_error' => urlencode( $error_description ),
					'provider'    => $state['provider'],
				), $state['return_url'] ) );
				exit;
			}

			// Exchange code for tokens
			if ( ! isset( $_GET['code'] ) ) {
				wp_die( __( 'Invalid OAuth callback: missing authorization code', 'super-forms' ) );
			}

			$code = sanitize_text_field( $_GET['code'] );
			$result = $this->exchange_code( $state['provider'], $code, $state['pkce'] );

			if ( is_wp_error( $result ) ) {
				wp_redirect( add_query_arg( array(
					'oauth_error' => urlencode( $result->get_error_message() ),
					'provider'    => $state['provider'],
				), $state['return_url'] ) );
				exit;
			}

			// Store tokens
			$credentials = SUPER_Automation_Credentials::instance();

			$credentials->store(
				$state['provider'],
				'access_token',
				$result['access_token'],
				$state['user_id'],
				null,
				isset( $result['expires_in'] ) ? time() + $result['expires_in'] : null
			);

			if ( isset( $result['refresh_token'] ) ) {
				$credentials->store(
					$state['provider'],
					'refresh_token',
					$result['refresh_token'],
					$state['user_id']
				);
			}

			// Store token type if provided
			if ( isset( $result['token_type'] ) ) {
				$credentials->store(
					$state['provider'],
					'token_type',
					$result['token_type'],
					$state['user_id']
				);
			}

			// Log successful connection
			if ( class_exists( 'SUPER_Automation_Compliance' ) ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'oauth_connected',
					array(
						'provider' => $state['provider'],
						'user_id'  => $state['user_id'],
					)
				);
			}

			// Redirect back with success
			wp_redirect( add_query_arg( array(
				'oauth_success' => '1',
				'provider'      => $state['provider'],
			), $state['return_url'] ) );
			exit;
		}

		/**
		 * Exchange authorization code for tokens
		 *
		 * @param string     $provider_id Provider identifier
		 * @param string     $code        Authorization code
		 * @param array|null $pkce        PKCE data (verifier)
		 * @return array|WP_Error Token response or error
		 */
		public function exchange_code( $provider_id, $code, $pkce = null ) {
			$provider = $this->get_provider( $provider_id );
			if ( ! $provider ) {
				return new WP_Error( 'invalid_provider', __( 'OAuth provider not found', 'super-forms' ) );
			}

			$client_id = $this->get_client_id( $provider_id );
			$client_secret = $this->get_client_secret( $provider_id );

			$body = array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'code'          => $code,
				'redirect_uri'  => $this->get_redirect_uri(),
				'grant_type'    => 'authorization_code',
			);

			// Add PKCE verifier if used
			if ( $pkce && isset( $pkce['verifier'] ) ) {
				$body['code_verifier'] = $pkce['verifier'];
			}

			$response = wp_remote_post( $provider['token_url'], array(
				'body'    => $body,
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $status >= 400 || isset( $body['error'] ) ) {
				$error_msg = $body['error_description'] ?? $body['error'] ?? __( 'Token exchange failed', 'super-forms' );
				return new WP_Error( 'token_error', $error_msg );
			}

			if ( ! isset( $body['access_token'] ) ) {
				return new WP_Error( 'invalid_response', __( 'Invalid token response', 'super-forms' ) );
			}

			return $body;
		}

		/**
		 * Refresh an access token
		 *
		 * @param string   $provider_id Provider identifier
		 * @param int|null $user_id     User ID (default: current user)
		 * @return string|WP_Error New access token or error
		 */
		public function refresh_token( $provider_id, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			$provider = $this->get_provider( $provider_id );
			if ( ! $provider ) {
				return new WP_Error( 'invalid_provider', __( 'OAuth provider not found', 'super-forms' ) );
			}

			$credentials = SUPER_Automation_Credentials::instance();
			$refresh_token = $credentials->get( $provider_id, 'refresh_token', $user_id );

			if ( ! $refresh_token || is_wp_error( $refresh_token ) ) {
				return new WP_Error( 'no_refresh_token', __( 'No refresh token available', 'super-forms' ) );
			}

			$client_id = $this->get_client_id( $provider_id );
			$client_secret = $this->get_client_secret( $provider_id );

			$response = wp_remote_post( $provider['token_url'], array(
				'body' => array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $status >= 400 || isset( $body['error'] ) ) {
				$error_msg = $body['error_description'] ?? $body['error'] ?? __( 'Token refresh failed', 'super-forms' );

				// If refresh token is invalid, clear stored credentials
				if ( strpos( strtolower( $error_msg ), 'invalid' ) !== false ) {
					$credentials->delete( $provider_id, null, $user_id );
				}

				return new WP_Error( 'refresh_error', $error_msg );
			}

			if ( ! isset( $body['access_token'] ) ) {
				return new WP_Error( 'invalid_response', __( 'Invalid refresh response', 'super-forms' ) );
			}

			// Store new access token
			$credentials->store(
				$provider_id,
				'access_token',
				$body['access_token'],
				$user_id,
				null,
				isset( $body['expires_in'] ) ? time() + $body['expires_in'] : null
			);

			// Some providers return a new refresh token
			if ( isset( $body['refresh_token'] ) ) {
				$credentials->store(
					$provider_id,
					'refresh_token',
					$body['refresh_token'],
					$user_id
				);
			}

			return $body['access_token'];
		}

		/**
		 * Get a valid access token, refreshing if needed
		 *
		 * @param string   $provider_id Provider identifier
		 * @param int|null $user_id     User ID (default: current user)
		 * @return string|WP_Error Access token or error
		 */
		public function get_access_token( $provider_id, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			$credentials = SUPER_Automation_Credentials::instance();
			$access_token = $credentials->get( $provider_id, 'access_token', $user_id );

			// If token exists and is valid (not expired via has() check), return it
			if ( $access_token && ! is_wp_error( $access_token ) ) {
				return $access_token;
			}

			// Try to refresh
			return $this->refresh_token( $provider_id, $user_id );
		}

		/**
		 * Disconnect an OAuth provider
		 *
		 * @param string   $provider_id Provider identifier
		 * @param int|null $user_id     User ID (default: current user)
		 * @return bool
		 */
		public function disconnect( $provider_id, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			$credentials = SUPER_Automation_Credentials::instance();
			$result = $credentials->delete( $provider_id, null, $user_id );

			// Log disconnection
			if ( class_exists( 'SUPER_Automation_Compliance' ) ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'oauth_disconnected',
					array(
						'provider' => $provider_id,
						'user_id'  => $user_id,
					)
				);
			}

			return $result > 0;
		}

		/**
		 * Check if a provider is connected
		 *
		 * @param string   $provider_id Provider identifier
		 * @param int|null $user_id     User ID (default: current user)
		 * @return bool
		 */
		public function is_connected( $provider_id, $user_id = null ) {
			$user_id = $user_id ?: get_current_user_id();

			$credentials = SUPER_Automation_Credentials::instance();

			// Check for access token or refresh token
			return $credentials->has( $provider_id, 'access_token', $user_id ) ||
			       $credentials->has( $provider_id, 'refresh_token', $user_id );
		}

		/**
		 * Get OAuth redirect URI
		 *
		 * @return string
		 */
		public function get_redirect_uri() {
			return add_query_arg( 'super_oauth_callback', '1', admin_url( 'admin.php' ) );
		}

		/**
		 * Get client ID for a provider
		 *
		 * @param string $provider_id Provider identifier
		 * @return string|null
		 */
		private function get_client_id( $provider_id ) {
			// Check provider config first
			$provider = $this->get_provider( $provider_id );
			if ( $provider && ! empty( $provider['client_id'] ) ) {
				return $provider['client_id'];
			}

			// Check settings
			$settings = SUPER_Settings::get_settings();
			$key = 'oauth_' . $provider_id . '_client_id';

			return $settings[ $key ] ?? null;
		}

		/**
		 * Get client secret for a provider
		 *
		 * @param string $provider_id Provider identifier
		 * @return string|null
		 */
		private function get_client_secret( $provider_id ) {
			// Check provider config first
			$provider = $this->get_provider( $provider_id );
			if ( $provider && ! empty( $provider['client_secret'] ) ) {
				return $provider['client_secret'];
			}

			// Check settings
			$settings = SUPER_Settings::get_settings();
			$key = 'oauth_' . $provider_id . '_client_secret';

			return $settings[ $key ] ?? null;
		}

		/**
		 * Generate PKCE code verifier and challenge
		 *
		 * @return array Array with 'verifier' and 'challenge'
		 */
		private function generate_pkce() {
			// Generate random verifier (43-128 characters)
			$verifier = rtrim( strtr( base64_encode( random_bytes( 64 ) ), '+/', '-_' ), '=' );

			// Create SHA256 challenge
			$challenge = rtrim( strtr(
				base64_encode( hash( 'sha256', $verifier, true ) ),
				'+/',
				'-_'
			), '=' );

			return array(
				'verifier'  => $verifier,
				'challenge' => $challenge,
			);
		}

		/**
		 * Set OAuth credentials for a provider programmatically
		 *
		 * @param string $provider_id   Provider identifier
		 * @param string $client_id     OAuth client ID
		 * @param string $client_secret OAuth client secret
		 */
		public function set_provider_credentials( $provider_id, $client_id, $client_secret ) {
			if ( isset( $this->providers[ $provider_id ] ) ) {
				$this->providers[ $provider_id ]['client_id'] = $client_id;
				$this->providers[ $provider_id ]['client_secret'] = $client_secret;
			}
		}
	}

endif;
