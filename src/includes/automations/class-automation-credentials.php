<?php
/**
 * Trigger Credentials Manager
 *
 * Provides secure encrypted storage for API credentials, OAuth tokens,
 * and other sensitive data used by trigger actions.
 *
 * @package    SUPER_Forms
 * @subpackage Triggers
 * @since      6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_Credentials' ) ) :

	/**
	 * SUPER_Automation_Credentials Class
	 *
	 * Handles encrypted storage and retrieval of API credentials.
	 * Uses AES-256-CBC encryption with WordPress AUTH_KEY as the key source.
	 *
	 * @since 6.5.0
	 */
	class SUPER_Automation_Credentials {

		/**
		 * Singleton instance
		 *
		 * @var SUPER_Automation_Credentials|null
		 */
		private static $instance = null;

		/**
		 * Encryption key derived from WordPress salts
		 *
		 * @var string
		 */
		private $encryption_key;

		/**
		 * Encryption method
		 *
		 * @var string
		 */
		const ENCRYPTION_METHOD = 'AES-256-CBC';

		/**
		 * Table name (without prefix)
		 *
		 * @var string
		 */
		const TABLE_NAME = 'superforms_api_credentials';

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Automation_Credentials
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
			$this->encryption_key = $this->get_encryption_key();
		}

		/**
		 * Get or generate encryption key
		 *
		 * Uses WordPress AUTH_KEY if available, otherwise generates
		 * and stores a unique key in the database.
		 *
		 * @return string 32-character encryption key
		 */
		private function get_encryption_key() {
			// Use AUTH_KEY if defined (preferred - uses wp-config.php salt)
			if ( defined( 'AUTH_KEY' ) && AUTH_KEY && strlen( AUTH_KEY ) >= 32 ) {
				return substr( hash( 'sha256', AUTH_KEY . 'super_forms_triggers_credential_encryption' ), 0, 32 );
			}

			// Fallback: generate and store a unique key
			$key = get_option( 'super_forms_encryption_key' );
			if ( ! $key ) {
				$key = wp_generate_password( 64, true, true );
				update_option( 'super_forms_encryption_key', $key );
			}

			return substr( hash( 'sha256', $key ), 0, 32 );
		}

		/**
		 * Store a credential
		 *
		 * @param string   $service  Service identifier (e.g., 'stripe', 'mailchimp', 'google')
		 * @param string   $key      Credential key (e.g., 'access_token', 'api_key', 'refresh_token')
		 * @param string   $value    Credential value to encrypt and store
		 * @param int|null $user_id  User ID (default: current user)
		 * @param int|null $form_id  Optional form ID for form-specific credentials
		 * @param int|null $expires  Optional expiration timestamp
		 * @return bool|WP_Error True on success, WP_Error on failure
		 */
		public function store( $service, $key, $value, $user_id = null, $form_id = null, $expires = null ) {
			global $wpdb;

			if ( empty( $service ) || empty( $key ) ) {
				return new WP_Error( 'invalid_params', __( 'Service and key are required', 'super-forms' ) );
			}

			$user_id = $user_id ?: get_current_user_id();
			if ( ! $user_id ) {
				return new WP_Error( 'no_user', __( 'User ID is required', 'super-forms' ) );
			}

			// Encrypt the value
			$encrypted = $this->encrypt( $value );
			if ( is_wp_error( $encrypted ) ) {
				return $encrypted;
			}

			$table = $wpdb->prefix . self::TABLE_NAME;
			$now = current_time( 'mysql' );

			// Check if credential already exists
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table WHERE service = %s AND credential_key = %s AND user_id = %d",
				$service,
				$key,
				$user_id
			) );

			$data = array(
				'service'          => sanitize_key( $service ),
				'credential_key'   => sanitize_key( $key ),
				'credential_value' => $encrypted,
				'user_id'          => $user_id,
				'form_id'          => $form_id,
				'expires_at'       => $expires ? gmdate( 'Y-m-d H:i:s', $expires ) : null,
				'updated_at'       => $now,
			);

			$formats = array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' );

			if ( $existing ) {
				// Update existing
				$result = $wpdb->update(
					$table,
					$data,
					array( 'id' => $existing->id ),
					$formats,
					array( '%d' )
				);
			} else {
				// Insert new
				$data['created_at'] = $now;
				$formats[] = '%s';
				$result = $wpdb->insert( $table, $data, $formats );
			}

			if ( false === $result ) {
				return new WP_Error( 'db_error', __( 'Failed to store credential', 'super-forms' ) );
			}

			// Clear cache
			$this->clear_cache( $service, $key, $user_id );

			// Log credential storage for compliance
			if ( class_exists( 'SUPER_Automation_Compliance' ) ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'credential_stored',
					array(
						'service' => $service,
						'key'     => $key,
						'user_id' => $user_id,
						'form_id' => $form_id,
					)
				);
			}

			return true;
		}

		/**
		 * Retrieve a credential
		 *
		 * @param string   $service  Service identifier
		 * @param string   $key      Credential key
		 * @param int|null $user_id  User ID (default: current user)
		 * @return string|null|WP_Error Decrypted value, null if not found, or WP_Error
		 */
		public function get( $service, $key, $user_id = null ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			if ( ! $user_id ) {
				return new WP_Error( 'no_user', __( 'User ID is required', 'super-forms' ) );
			}

			// Check cache first
			$cache_key = $this->get_cache_key( $service, $key, $user_id );
			$cached = wp_cache_get( $cache_key, 'super_forms_credentials' );
			if ( false !== $cached ) {
				return $cached;
			}

			$table = $wpdb->prefix . self::TABLE_NAME;

			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT credential_value, expires_at FROM $table
				 WHERE service = %s AND credential_key = %s AND user_id = %d",
				$service,
				$key,
				$user_id
			) );

			if ( ! $row ) {
				return null;
			}

			// Check expiration
			if ( $row->expires_at && strtotime( $row->expires_at ) < time() ) {
				// Credential expired - delete it
				$this->delete( $service, $key, $user_id );
				return null;
			}

			// Decrypt the value
			$decrypted = $this->decrypt( $row->credential_value );
			if ( is_wp_error( $decrypted ) ) {
				return $decrypted;
			}

			// Cache for 5 minutes
			wp_cache_set( $cache_key, $decrypted, 'super_forms_credentials', 300 );

			// Log credential access for compliance
			if ( class_exists( 'SUPER_Automation_Compliance' ) ) {
				SUPER_Automation_Compliance::instance()->log_credential_access(
					$service . '.' . $key,
					$service
				);
			}

			return $decrypted;
		}

		/**
		 * Delete a credential
		 *
		 * @param string      $service  Service identifier
		 * @param string|null $key      Credential key (null to delete all for service)
		 * @param int|null    $user_id  User ID (default: current user)
		 * @return int|false Number of rows deleted or false on error
		 */
		public function delete( $service, $key = null, $user_id = null ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			$table = $wpdb->prefix . self::TABLE_NAME;

			$where = array(
				'service' => $service,
				'user_id' => $user_id,
			);
			$formats = array( '%s', '%d' );

			if ( $key ) {
				$where['credential_key'] = $key;
				$formats[] = '%s';
			}

			$result = $wpdb->delete( $table, $where, $formats );

			// Clear cache
			$this->clear_cache( $service, $key, $user_id );

			// Log deletion for compliance
			if ( class_exists( 'SUPER_Automation_Compliance' ) && $result ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'credential_deleted',
					array(
						'service' => $service,
						'key'     => $key,
						'user_id' => $user_id,
						'count'   => $result,
					)
				);
			}

			return $result;
		}

		/**
		 * Get all credentials for a service
		 *
		 * @param string   $service  Service identifier
		 * @param int|null $user_id  User ID (default: current user)
		 * @return array Array of credential keys (not values for security)
		 */
		public function get_service_credentials( $service, $user_id = null ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			$table = $wpdb->prefix . self::TABLE_NAME;

			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT credential_key, expires_at, created_at, updated_at
				 FROM $table
				 WHERE service = %s AND user_id = %d",
				$service,
				$user_id
			), ARRAY_A );

			return $results ?: array();
		}

		/**
		 * Check if a credential exists and is not expired
		 *
		 * @param string   $service  Service identifier
		 * @param string   $key      Credential key
		 * @param int|null $user_id  User ID (default: current user)
		 * @return bool
		 */
		public function has( $service, $key, $user_id = null ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			$table = $wpdb->prefix . self::TABLE_NAME;

			$expires = $wpdb->get_var( $wpdb->prepare(
				"SELECT expires_at FROM $table
				 WHERE service = %s AND credential_key = %s AND user_id = %d",
				$service,
				$key,
				$user_id
			) );

			if ( null === $expires ) {
				return false; // Not found
			}

			// Check if expired
			if ( $expires && strtotime( $expires ) < time() ) {
				return false;
			}

			return true;
		}

		/**
		 * Update expiration time for a credential
		 *
		 * @param string   $service  Service identifier
		 * @param string   $key      Credential key
		 * @param int      $expires  New expiration timestamp
		 * @param int|null $user_id  User ID (default: current user)
		 * @return bool
		 */
		public function update_expiry( $service, $key, $expires, $user_id = null ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			$table = $wpdb->prefix . self::TABLE_NAME;

			$result = $wpdb->update(
				$table,
				array(
					'expires_at' => gmdate( 'Y-m-d H:i:s', $expires ),
					'updated_at' => current_time( 'mysql' ),
				),
				array(
					'service'        => $service,
					'credential_key' => $key,
					'user_id'        => $user_id,
				),
				array( '%s', '%s' ),
				array( '%s', '%s', '%d' )
			);

			// Clear cache
			$this->clear_cache( $service, $key, $user_id );

			return false !== $result;
		}

		/**
		 * Delete all expired credentials
		 *
		 * @return int Number of rows deleted
		 */
		public function cleanup_expired() {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;

			$deleted = $wpdb->query( $wpdb->prepare(
				"DELETE FROM $table WHERE expires_at IS NOT NULL AND expires_at < %s",
				current_time( 'mysql' )
			) );

			if ( $deleted > 0 && class_exists( 'SUPER_Automation_Logger' ) ) {
				SUPER_Automation_Logger::instance()->log(
					'INFO',
					sprintf( 'Cleaned up %d expired credentials', $deleted ),
					array( 'component' => 'credentials' )
				);
			}

			return $deleted;
		}

		/**
		 * Encrypt a value using AES-256-CBC
		 *
		 * @param string $value Value to encrypt
		 * @return string|WP_Error Base64-encoded encrypted value
		 */
		public function encrypt( $value ) {
			if ( ! function_exists( 'openssl_encrypt' ) ) {
				return new WP_Error( 'no_openssl', __( 'OpenSSL extension is required for encryption', 'super-forms' ) );
			}

			// Generate random IV
			$iv_length = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
			$iv = openssl_random_pseudo_bytes( $iv_length );

			// Encrypt
			$encrypted = openssl_encrypt(
				$value,
				self::ENCRYPTION_METHOD,
				$this->encryption_key,
				OPENSSL_RAW_DATA,
				$iv
			);

			if ( false === $encrypted ) {
				return new WP_Error( 'encryption_failed', __( 'Failed to encrypt value', 'super-forms' ) );
			}

			// Combine IV + encrypted data and base64 encode
			// Format: base64(IV + encrypted_data)
			return base64_encode( $iv . $encrypted );
		}

		/**
		 * Decrypt a value encrypted with AES-256-CBC
		 *
		 * @param string $encrypted_value Base64-encoded encrypted value
		 * @return string|WP_Error Decrypted value
		 */
		public function decrypt( $encrypted_value ) {
			if ( ! function_exists( 'openssl_decrypt' ) ) {
				return new WP_Error( 'no_openssl', __( 'OpenSSL extension is required for decryption', 'super-forms' ) );
			}

			// Decode from base64
			$data = base64_decode( $encrypted_value );
			if ( false === $data ) {
				return new WP_Error( 'invalid_data', __( 'Invalid encrypted data', 'super-forms' ) );
			}

			// Extract IV and encrypted data
			$iv_length = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
			$iv = substr( $data, 0, $iv_length );
			$encrypted = substr( $data, $iv_length );

			// Decrypt
			$decrypted = openssl_decrypt(
				$encrypted,
				self::ENCRYPTION_METHOD,
				$this->encryption_key,
				OPENSSL_RAW_DATA,
				$iv
			);

			if ( false === $decrypted ) {
				return new WP_Error( 'decryption_failed', __( 'Failed to decrypt value', 'super-forms' ) );
			}

			return $decrypted;
		}

		/**
		 * Get cache key for a credential
		 *
		 * @param string $service Service identifier
		 * @param string $key     Credential key
		 * @param int    $user_id User ID
		 * @return string
		 */
		private function get_cache_key( $service, $key, $user_id ) {
			return 'cred_' . md5( $service . '_' . $key . '_' . $user_id );
		}

		/**
		 * Clear cache for a credential
		 *
		 * @param string      $service Service identifier
		 * @param string|null $key     Credential key (null to clear all for service)
		 * @param int         $user_id User ID
		 */
		private function clear_cache( $service, $key, $user_id ) {
			if ( $key ) {
				wp_cache_delete( $this->get_cache_key( $service, $key, $user_id ), 'super_forms_credentials' );
			} else {
				// Clear all cached credentials for this service/user
				// Note: wp_cache doesn't support wildcard deletion, so we rely on cache expiry
				wp_cache_flush();
			}
		}

		/**
		 * Get all services with stored credentials for a user
		 *
		 * @param int|null $user_id User ID (default: current user)
		 * @return array List of service names
		 */
		public function get_connected_services( $user_id = null ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			$table = $wpdb->prefix . self::TABLE_NAME;

			$services = $wpdb->get_col( $wpdb->prepare(
				"SELECT DISTINCT service FROM $table WHERE user_id = %d",
				$user_id
			) );

			return $services ?: array();
		}

		/**
		 * Delete all credentials for a user (GDPR compliance)
		 *
		 * @param int $user_id User ID
		 * @return int Number of rows deleted
		 */
		public function delete_user_credentials( $user_id ) {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;

			$deleted = $wpdb->delete(
				$table,
				array( 'user_id' => $user_id ),
				array( '%d' )
			);

			// Log for compliance
			if ( class_exists( 'SUPER_Automation_Compliance' ) && $deleted ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'user_credentials_deleted',
					array(
						'user_id' => $user_id,
						'count'   => $deleted,
						'reason'  => 'user_request_or_gdpr',
					)
				);
			}

			return $deleted;
		}

		/**
		 * Export credentials metadata for a user (GDPR compliance)
		 * Note: Does not export actual credential values for security
		 *
		 * @param int $user_id User ID
		 * @return array Credential metadata
		 */
		public function export_user_credentials( $user_id ) {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;

			$credentials = $wpdb->get_results( $wpdb->prepare(
				"SELECT service, credential_key, form_id, expires_at, created_at, updated_at
				 FROM $table
				 WHERE user_id = %d",
				$user_id
			), ARRAY_A );

			return $credentials ?: array();
		}
	}

endif;
