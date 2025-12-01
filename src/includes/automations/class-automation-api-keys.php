<?php
/**
 * Trigger API Keys Manager
 *
 * Handles creation, validation, and management of API keys
 * for external access to the Super Forms REST API.
 *
 * @package    SUPER_Forms
 * @subpackage Triggers
 * @since      6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SUPER_Automation_API_Keys' ) ) :

	/**
	 * SUPER_Automation_API_Keys Class
	 *
	 * Manages API keys for REST API authentication including:
	 * - Key generation and hashing
	 * - Key validation
	 * - Permission management
	 * - Usage tracking
	 *
	 * @since 6.5.0
	 */
	class SUPER_Automation_API_Keys {

		/**
		 * Singleton instance
		 *
		 * @var SUPER_Automation_API_Keys|null
		 */
		private static $instance = null;

		/**
		 * Table name (without prefix)
		 *
		 * @var string
		 */
		const TABLE_NAME = 'superforms_api_keys';

		/**
		 * API key prefix for identification
		 *
		 * @var string
		 */
		const KEY_PREFIX = 'sf_';

		/**
		 * Available permissions
		 *
		 * @var array
		 */
		const PERMISSIONS = array(
			'triggers'    => 'Manage Triggers',
			'execute'     => 'Execute Triggers',
			'logs'        => 'View Logs',
			'credentials' => 'Manage Credentials',
			'forms'       => 'Read Forms',
			'entries'     => 'Read Entries',
		);

		/**
		 * Get singleton instance
		 *
		 * @return SUPER_Automation_API_Keys
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
			// Register AJAX handlers for admin
			add_action( 'wp_ajax_super_create_api_key', array( $this, 'ajax_create_key' ) );
			add_action( 'wp_ajax_super_revoke_api_key', array( $this, 'ajax_revoke_key' ) );
			add_action( 'wp_ajax_super_list_api_keys', array( $this, 'ajax_list_keys' ) );
		}

		/**
		 * Create a new API key
		 *
		 * @param string   $name        Key name/description
		 * @param array    $permissions Array of permission strings
		 * @param int|null $user_id     User ID (default: current user)
		 * @param int|null $rate_limit  Custom rate limit (requests/minute)
		 * @param int|null $expires     Expiration timestamp
		 * @return array|WP_Error Array with 'key' (raw) and 'id', or WP_Error
		 */
		public function create( $name, $permissions = array(), $user_id = null, $rate_limit = null, $expires = null ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			if ( ! $user_id ) {
				return new WP_Error( 'no_user', __( 'User ID is required', 'super-forms' ) );
			}

			// Validate permissions
			$valid_permissions = array();
			foreach ( $permissions as $perm ) {
				if ( array_key_exists( $perm, self::PERMISSIONS ) ) {
					$valid_permissions[] = $perm;
				}
			}

			if ( empty( $valid_permissions ) ) {
				return new WP_Error( 'no_permissions', __( 'At least one permission is required', 'super-forms' ) );
			}

			// Generate secure random key
			$raw_key = self::KEY_PREFIX . bin2hex( random_bytes( 24 ) ); // 48 hex chars + prefix
			$key_hash = hash( 'sha256', $raw_key );
			$key_prefix = substr( $raw_key, 0, 12 ); // First 12 chars for display

			$table = $wpdb->prefix . self::TABLE_NAME;
			$now = current_time( 'mysql' );

			$data = array(
				'key_name'       => sanitize_text_field( $name ),
				'api_key_hash'   => $key_hash,
				'api_key_prefix' => $key_prefix,
				'permissions'    => wp_json_encode( $valid_permissions ),
				'user_id'        => $user_id,
				'status'         => 'active',
				'rate_limit'     => $rate_limit ?: 60,
				'expires_at'     => $expires ? gmdate( 'Y-m-d H:i:s', $expires ) : null,
				'created_at'     => $now,
			);

			$result = $wpdb->insert( $table, $data, array(
				'%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s',
			) );

			if ( false === $result ) {
				return new WP_Error( 'db_error', __( 'Failed to create API key', 'super-forms' ) );
			}

			$key_id = $wpdb->insert_id;

			// Log key creation
			if ( class_exists( 'SUPER_Automation_Compliance' ) ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'api_key_created',
					array(
						'key_id'      => $key_id,
						'key_name'    => $name,
						'key_prefix'  => $key_prefix,
						'permissions' => $valid_permissions,
						'user_id'     => $user_id,
					)
				);
			}

			return array(
				'id'          => $key_id,
				'key'         => $raw_key, // Only returned once at creation
				'prefix'      => $key_prefix,
				'name'        => $name,
				'permissions' => $valid_permissions,
			);
		}

		/**
		 * Validate an API key
		 *
		 * @param string $key Raw API key
		 * @return array|false Key data or false if invalid
		 */
		public function validate_key( $key ) {
			global $wpdb;

			if ( empty( $key ) ) {
				return false;
			}

			$key_hash = hash( 'sha256', $key );
			$table = $wpdb->prefix . self::TABLE_NAME;

			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $table WHERE api_key_hash = %s AND status = 'active'",
				$key_hash
			), ARRAY_A );

			if ( ! $row ) {
				// Log failed attempt
				if ( class_exists( 'SUPER_Automation_Security' ) ) {
					SUPER_Automation_Security::instance()->log_security_event(
						'api_key_invalid',
						array( 'key_prefix' => substr( $key, 0, 12 ) )
					);
				}
				return false;
			}

			// Check expiration
			if ( $row['expires_at'] && strtotime( $row['expires_at'] ) < time() ) {
				// Auto-expire the key
				$this->update_status( $row['id'], 'expired' );
				return false;
			}

			// Update last used
			$this->update_usage( $row['id'] );

			return array(
				'id'          => (int) $row['id'],
				'user_id'     => (int) $row['user_id'],
				'permissions' => json_decode( $row['permissions'], true ),
				'rate_limit'  => (int) $row['rate_limit'],
				'key_name'    => $row['key_name'],
			);
		}

		/**
		 * Update API key usage statistics
		 *
		 * @param int $key_id Key ID
		 */
		private function update_usage( $key_id ) {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;
			$ip = class_exists( 'SUPER_Automation_Security' ) ?
				SUPER_Automation_Security::instance()->get_client_ip() : '0.0.0.0';

			$wpdb->query( $wpdb->prepare(
				"UPDATE $table SET
					usage_count = usage_count + 1,
					last_used_at = %s,
					last_used_ip = %s
				WHERE id = %d",
				current_time( 'mysql' ),
				$ip,
				$key_id
			) );
		}

		/**
		 * Update API key status
		 *
		 * @param int    $key_id Key ID
		 * @param string $status New status
		 * @return bool
		 */
		public function update_status( $key_id, $status ) {
			global $wpdb;

			$valid_statuses = array( 'active', 'revoked', 'expired' );
			if ( ! in_array( $status, $valid_statuses, true ) ) {
				return false;
			}

			$table = $wpdb->prefix . self::TABLE_NAME;

			$result = $wpdb->update(
				$table,
				array( 'status' => $status ),
				array( 'id' => $key_id ),
				array( '%s' ),
				array( '%d' )
			);

			// Log status change
			if ( class_exists( 'SUPER_Automation_Compliance' ) ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'api_key_status_changed',
					array(
						'key_id'     => $key_id,
						'new_status' => $status,
					)
				);
			}

			return false !== $result;
		}

		/**
		 * Revoke an API key
		 *
		 * @param int $key_id Key ID
		 * @return bool
		 */
		public function revoke( $key_id ) {
			return $this->update_status( $key_id, 'revoked' );
		}

		/**
		 * Delete an API key permanently
		 *
		 * @param int $key_id Key ID
		 * @return bool
		 */
		public function delete( $key_id ) {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;

			// Get key info for logging
			$key = $this->get( $key_id );

			$result = $wpdb->delete( $table, array( 'id' => $key_id ), array( '%d' ) );

			if ( $result && class_exists( 'SUPER_Automation_Compliance' ) ) {
				SUPER_Automation_Compliance::instance()->log_compliance_action(
					'api_key_deleted',
					array(
						'key_id'     => $key_id,
						'key_prefix' => $key ? $key['api_key_prefix'] : 'unknown',
					)
				);
			}

			return false !== $result;
		}

		/**
		 * Get an API key by ID
		 *
		 * @param int $key_id Key ID
		 * @return array|null
		 */
		public function get( $key_id ) {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;

			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$key_id
			), ARRAY_A );

			if ( $row ) {
				$row['permissions'] = json_decode( $row['permissions'], true );
			}

			return $row;
		}

		/**
		 * Get all API keys for a user
		 *
		 * @param int|null $user_id User ID (default: current user)
		 * @param bool     $include_revoked Include revoked keys
		 * @return array
		 */
		public function get_user_keys( $user_id = null, $include_revoked = false ) {
			global $wpdb;

			$user_id = $user_id ?: get_current_user_id();
			$table = $wpdb->prefix . self::TABLE_NAME;

			$status_clause = $include_revoked ? '' : "AND status = 'active'";

			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, key_name, api_key_prefix, permissions, status,
				        rate_limit, usage_count, last_used_at, expires_at, created_at
				 FROM $table
				 WHERE user_id = %d $status_clause
				 ORDER BY created_at DESC",
				$user_id
			), ARRAY_A );

			foreach ( $results as &$row ) {
				$row['permissions'] = json_decode( $row['permissions'], true );
			}

			return $results ?: array();
		}

		/**
		 * Get all API keys (admin only)
		 *
		 * @param array $args Query arguments
		 * @return array
		 */
		public function get_all( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'status'  => null,
				'user_id' => null,
				'limit'   => 50,
				'offset'  => 0,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			);

			$args = wp_parse_args( $args, $defaults );
			$table = $wpdb->prefix . self::TABLE_NAME;

			$where = array( '1=1' );
			$values = array();

			if ( $args['status'] ) {
				$where[] = 'status = %s';
				$values[] = $args['status'];
			}

			if ( $args['user_id'] ) {
				$where[] = 'user_id = %d';
				$values[] = $args['user_id'];
			}

			$where_clause = implode( ' AND ', $where );
			$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] ) ?: 'created_at DESC';

			$sql = "SELECT k.*, u.display_name as user_name
			        FROM $table k
			        LEFT JOIN {$wpdb->users} u ON k.user_id = u.ID
			        WHERE $where_clause
			        ORDER BY $orderby
			        LIMIT %d OFFSET %d";

			$values[] = $args['limit'];
			$values[] = $args['offset'];

			$results = $wpdb->get_results(
				$wpdb->prepare( $sql, $values ),
				ARRAY_A
			);

			foreach ( $results as &$row ) {
				$row['permissions'] = json_decode( $row['permissions'], true );
			}

			return $results ?: array();
		}

		/**
		 * Check if a key has a specific permission
		 *
		 * @param array  $key_data  Key data from validate_key()
		 * @param string $permission Permission to check
		 * @return bool
		 */
		public function has_permission( $key_data, $permission ) {
			if ( ! $key_data || ! isset( $key_data['permissions'] ) ) {
				return false;
			}

			return in_array( $permission, $key_data['permissions'], true );
		}

		/**
		 * Update API key permissions
		 *
		 * @param int   $key_id      Key ID
		 * @param array $permissions New permissions
		 * @return bool
		 */
		public function update_permissions( $key_id, $permissions ) {
			global $wpdb;

			$valid_permissions = array();
			foreach ( $permissions as $perm ) {
				if ( array_key_exists( $perm, self::PERMISSIONS ) ) {
					$valid_permissions[] = $perm;
				}
			}

			$table = $wpdb->prefix . self::TABLE_NAME;

			return false !== $wpdb->update(
				$table,
				array( 'permissions' => wp_json_encode( $valid_permissions ) ),
				array( 'id' => $key_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		/**
		 * Update API key rate limit
		 *
		 * @param int $key_id     Key ID
		 * @param int $rate_limit New rate limit
		 * @return bool
		 */
		public function update_rate_limit( $key_id, $rate_limit ) {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;

			return false !== $wpdb->update(
				$table,
				array( 'rate_limit' => (int) $rate_limit ),
				array( 'id' => $key_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		/**
		 * AJAX handler: Create API key
		 */
		public function ajax_create_key() {
			check_ajax_referer( 'super_forms_ajax', 'nonce' );

			if ( ! SUPER_Automation_Permissions::can_manage_api_keys() ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'super-forms' ) ) );
			}

			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$permissions = isset( $_POST['permissions'] ) ? array_map( 'sanitize_key', (array) $_POST['permissions'] ) : array();
			$rate_limit = isset( $_POST['rate_limit'] ) ? (int) $_POST['rate_limit'] : null;

			if ( empty( $name ) ) {
				wp_send_json_error( array( 'message' => __( 'Key name is required', 'super-forms' ) ) );
			}

			$result = $this->create( $name, $permissions, null, $rate_limit );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( $result );
		}

		/**
		 * AJAX handler: Revoke API key
		 */
		public function ajax_revoke_key() {
			check_ajax_referer( 'super_forms_ajax', 'nonce' );

			if ( ! SUPER_Automation_Permissions::can_manage_api_keys() ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'super-forms' ) ) );
			}

			$key_id = isset( $_POST['key_id'] ) ? (int) $_POST['key_id'] : 0;

			if ( ! $key_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid key ID', 'super-forms' ) ) );
			}

			$result = $this->revoke( $key_id );

			if ( ! $result ) {
				wp_send_json_error( array( 'message' => __( 'Failed to revoke key', 'super-forms' ) ) );
			}

			wp_send_json_success( array( 'message' => __( 'API key revoked', 'super-forms' ) ) );
		}

		/**
		 * AJAX handler: List API keys
		 */
		public function ajax_list_keys() {
			check_ajax_referer( 'super_forms_ajax', 'nonce' );

			if ( ! SUPER_Automation_Permissions::can_manage_api_keys() ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'super-forms' ) ) );
			}

			// If admin, get all keys; otherwise just user's keys
			if ( current_user_can( 'manage_options' ) ) {
				$keys = $this->get_all( array( 'limit' => 100 ) );
			} else {
				$keys = $this->get_user_keys( null, true );
			}

			wp_send_json_success( array( 'keys' => $keys ) );
		}

		/**
		 * Get available permissions
		 *
		 * @return array
		 */
		public static function get_available_permissions() {
			return self::PERMISSIONS;
		}

		/**
		 * Clean up expired API keys
		 *
		 * @return int Number of keys cleaned up
		 */
		public function cleanup_expired() {
			global $wpdb;

			$table = $wpdb->prefix . self::TABLE_NAME;

			$expired = $wpdb->query( $wpdb->prepare(
				"UPDATE $table SET status = 'expired'
				 WHERE expires_at IS NOT NULL
				 AND expires_at < %s
				 AND status = 'active'",
				current_time( 'mysql' )
			) );

			return $expired;
		}
	}

endif;
