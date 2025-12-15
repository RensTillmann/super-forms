<?php
/**
 * Generate Temporary Access Action
 *
 * Creates secure, time-limited access tokens for viewing/editing entries
 * or downloading files. Tokens are hashed for storage and validated on access.
 *
 * @package Super_Forms
 * @subpackage Automations/Actions
 * @since 6.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUPER_Action_Generate_Temp_Access extends SUPER_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'generate_temp_access';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Generate Temporary Access Link', 'super-forms' );
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'utility';
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Create a secure, time-limited access link for viewing entries, editing forms, or downloading files', 'super-forms' );
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name'        => 'resource_type',
				'label'       => __( 'Resource Type', 'super-forms' ),
				'type'        => 'select',
				'required'    => true,
				'default'     => 'entry',
				'options'     => [
					'entry' => __( 'Form Entry', 'super-forms' ),
					'file'  => __( 'Uploaded File', 'super-forms' ),
					'form'  => __( 'Form (Pre-filled)', 'super-forms' ),
				],
				'description' => __( 'What type of resource to grant access to', 'super-forms' ),
			],
			[
				'name'        => 'resource_id_source',
				'label'       => __( 'Resource ID Source', 'super-forms' ),
				'type'        => 'select',
				'required'    => true,
				'default'     => 'context',
				'options'     => [
					'context' => __( 'From Context (entry_id/form_id)', 'super-forms' ),
					'field'   => __( 'From Form Field', 'super-forms' ),
					'custom'  => __( 'Custom Value', 'super-forms' ),
				],
				'description' => __( 'Where to get the resource ID from', 'super-forms' ),
			],
			[
				'name'        => 'resource_id_field',
				'label'       => __( 'Field Name', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => 'file_upload',
				'description' => __( 'Form field containing the resource ID', 'super-forms' ),
				'show_if'     => [ 'resource_id_source' => 'field' ],
			],
			[
				'name'        => 'resource_id_custom',
				'label'       => __( 'Custom Resource ID', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => '{entry_id}',
				'description' => __( 'Custom value with {tags} support', 'super-forms' ),
				'show_if'     => [ 'resource_id_source' => 'custom' ],
			],
			[
				'name'        => 'access_type',
				'label'       => __( 'Access Type', 'super-forms' ),
				'type'        => 'select',
				'required'    => true,
				'default'     => 'view',
				'options'     => [
					'view'     => __( 'View Only', 'super-forms' ),
					'edit'     => __( 'Edit', 'super-forms' ),
					'download' => __( 'Download', 'super-forms' ),
				],
				'description' => __( 'What level of access to grant', 'super-forms' ),
			],
			[
				'name'        => 'expiry_duration',
				'label'       => __( 'Expires After', 'super-forms' ),
				'type'        => 'select',
				'required'    => true,
				'default'     => '24h',
				'options'     => [
					'1h'  => __( '1 Hour', 'super-forms' ),
					'6h'  => __( '6 Hours', 'super-forms' ),
					'24h' => __( '24 Hours', 'super-forms' ),
					'7d'  => __( '7 Days', 'super-forms' ),
					'30d' => __( '30 Days', 'super-forms' ),
					'90d' => __( '90 Days', 'super-forms' ),
				],
				'description' => __( 'How long the access link remains valid', 'super-forms' ),
			],
			[
				'name'        => 'max_uses',
				'label'       => __( 'Maximum Uses', 'super-forms' ),
				'type'        => 'number',
				'required'    => false,
				'default'     => '',
				'min'         => 1,
				'max'         => 1000,
				'placeholder' => __( 'Unlimited', 'super-forms' ),
				'description' => __( 'Limit how many times the link can be used (leave empty for unlimited)', 'super-forms' ),
			],
			[
				'name'        => 'custom_redirect',
				'label'       => __( 'Custom Redirect URL', 'super-forms' ),
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => 'https://example.com/view-entry',
				'description' => __( 'Optional: Custom URL where the token will redirect. Leave empty for default handler.', 'super-forms' ),
			],
		];
	}

	/**
	 * Execute the action
	 *
	 * @param array $context Event context data.
	 * @param array $config  Action configuration.
	 * @return array|WP_Error Result data or error.
	 */
	public function execute( $context, $config ) {
		global $wpdb;

		// Get resource ID based on source setting.
		$resource_id = $this->get_resource_id( $context, $config );

		if ( is_wp_error( $resource_id ) ) {
			return $resource_id;
		}

		if ( empty( $resource_id ) || ! is_numeric( $resource_id ) ) {
			return new WP_Error(
				'invalid_resource_id',
				__( 'Could not determine resource ID', 'super-forms' )
			);
		}

		$resource_type = sanitize_key( $config['resource_type'] ?? 'entry' );
		$access_type   = sanitize_key( $config['access_type'] ?? 'view' );

		// Validate resource exists.
		$resource_exists = $this->validate_resource_exists( $resource_type, $resource_id );
		if ( ! $resource_exists ) {
			return new WP_Error(
				'resource_not_found',
				sprintf(
					__( 'Resource not found: %s #%d', 'super-forms' ),
					$resource_type,
					$resource_id
				)
			);
		}

		// Generate cryptographically secure token.
		$token      = wp_generate_password( 32, false, false );
		$token_hash = hash( 'sha256', $token );

		// Calculate expiry.
		$expires_at = $this->calculate_expiry( $config['expiry_duration'] ?? '24h' );

		// Prepare max_uses (null for unlimited).
		$max_uses = ! empty( $config['max_uses'] ) ? absint( $config['max_uses'] ) : null;

		// Prepare metadata.
		$metadata = [
			'form_id'         => $context['form_id'] ?? null,
			'entry_id'        => $context['entry_id'] ?? null,
			'custom_redirect' => ! empty( $config['custom_redirect'] ) ? esc_url_raw( $config['custom_redirect'] ) : null,
		];

		// Insert token record.
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'superforms_temp_access',
			[
				'token_hash'    => $token_hash,
				'resource_type' => $resource_type,
				'resource_id'   => $resource_id,
				'access_type'   => $access_type,
				'created_by'    => $context['user_id'] ?? get_current_user_id(),
				'created_at'    => current_time( 'mysql' ),
				'expires_at'    => $expires_at,
				'max_uses'      => $max_uses,
				'use_count'     => 0,
				'metadata'      => wp_json_encode( $metadata ),
			],
			[ '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%s' ]
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'database_error',
				__( 'Failed to create access token', 'super-forms' )
			);
		}

		// Build access URL (validate to prevent open redirect).
		$default_url = home_url( '/' );
		$base_url    = $default_url;

		if ( ! empty( $config['custom_redirect'] ) ) {
			// wp_validate_redirect ensures URL is same-origin or in allowed hosts.
			$validated = wp_validate_redirect( esc_url_raw( $config['custom_redirect'] ), $default_url );
			$base_url  = $validated;
		}

		$access_url = add_query_arg(
			[
				'sf_access' => $token,
				'sf_type'   => $resource_type,
			],
			$base_url
		);

		// Calculate human-readable expiry.
		$expires_timestamp = strtotime( $expires_at );
		$expires_human     = human_time_diff( time(), $expires_timestamp );

		return [
			'success' => true,
			'data'    => [
				'access_url'     => $access_url,
				'token'          => $token,
				'expires_at'     => $expires_at,
				'expires_in'     => $expires_human,
				'max_uses'       => $max_uses,
				'resource_type'  => $resource_type,
				'resource_id'    => $resource_id,
				'access_type'    => $access_type,
				'message'        => sprintf(
					__( 'Access link generated (expires in %s)', 'super-forms' ),
					$expires_human
				),
			],
		];
	}

	/**
	 * Get resource ID based on configuration
	 *
	 * @param array $context Event context.
	 * @param array $config  Action config.
	 * @return int|WP_Error Resource ID or error.
	 */
	private function get_resource_id( $context, $config ) {
		$source        = $config['resource_id_source'] ?? 'context';
		$resource_type = $config['resource_type'] ?? 'entry';

		switch ( $source ) {
			case 'context':
				// Get from context based on resource type.
				if ( 'entry' === $resource_type && ! empty( $context['entry_id'] ) ) {
					return absint( $context['entry_id'] );
				}
				if ( 'form' === $resource_type && ! empty( $context['form_id'] ) ) {
					return absint( $context['form_id'] );
				}
				// For files, try entry_id first, then form_id.
				if ( 'file' === $resource_type ) {
					if ( ! empty( $context['entry_id'] ) ) {
						return absint( $context['entry_id'] );
					}
					if ( ! empty( $context['form_id'] ) ) {
						return absint( $context['form_id'] );
					}
				}
				return new WP_Error(
					'missing_context',
					__( 'Resource ID not found in context', 'super-forms' )
				);

			case 'field':
				$field_name = $config['resource_id_field'] ?? '';
				if ( empty( $field_name ) ) {
					return new WP_Error(
						'missing_field_name',
						__( 'Field name not specified', 'super-forms' )
					);
				}
				$form_data = $context['form_data'] ?? [];
				if ( isset( $form_data[ $field_name ] ) ) {
					return absint( $form_data[ $field_name ] );
				}
				return new WP_Error(
					'field_not_found',
					sprintf( __( 'Field "%s" not found in form data', 'super-forms' ), $field_name )
				);

			case 'custom':
				$custom_value = $config['resource_id_custom'] ?? '';
				if ( empty( $custom_value ) ) {
					return new WP_Error(
						'missing_custom_value',
						__( 'Custom resource ID not specified', 'super-forms' )
					);
				}
				// Replace tags.
				$resolved = $this->replace_variables( $custom_value, $context, [
					'sanitize'         => 'text',
					'missing_behavior' => 'empty',
				] );
				return absint( $resolved );

			default:
				return new WP_Error(
					'invalid_source',
					__( 'Invalid resource ID source', 'super-forms' )
				);
		}
	}

	/**
	 * Validate that the resource exists
	 *
	 * @param string $resource_type Resource type.
	 * @param int    $resource_id   Resource ID.
	 * @return bool Whether resource exists.
	 */
	private function validate_resource_exists( $resource_type, $resource_id ) {
		global $wpdb;

		switch ( $resource_type ) {
			case 'entry':
				// Check entries table first, fall back to posts.
				$entry_table = $wpdb->prefix . 'superforms_entries';
				$exists      = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$entry_table} WHERE id = %d",
						$resource_id
					)
				);
				if ( $exists ) {
					return true;
				}
				// Fall back to posts table (legacy entries).
				$post = get_post( $resource_id );
				return $post && 'super_contact_entry' === $post->post_type;

			case 'form':
				// Check forms table first, fall back to posts.
				$form_table = $wpdb->prefix . 'superforms_forms';
				$exists     = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$form_table} WHERE id = %d",
						$resource_id
					)
				);
				if ( $exists ) {
					return true;
				}
				// Fall back to posts table (legacy forms).
				$post = get_post( $resource_id );
				return $post && 'super_form' === $post->post_type;

			case 'file':
				// Check if it's a valid attachment.
				$post = get_post( $resource_id );
				return $post && 'attachment' === $post->post_type;

			default:
				return false;
		}
	}

	/**
	 * Calculate expiry datetime
	 *
	 * @param string $duration Duration string (e.g., '24h', '7d').
	 * @return string MySQL datetime string.
	 */
	private function calculate_expiry( $duration ) {
		$intervals = [
			'1h'  => '+1 hour',
			'6h'  => '+6 hours',
			'24h' => '+24 hours',
			'7d'  => '+7 days',
			'30d' => '+30 days',
			'90d' => '+90 days',
		];

		$interval = $intervals[ $duration ] ?? '+24 hours';
		return gmdate( 'Y-m-d H:i:s', strtotime( $interval ) );
	}

	/**
	 * Supports async execution
	 *
	 * @return bool
	 */
	public function supports_async() {
		return true;
	}

	/**
	 * Get execution mode - sync preferred for immediate URL response
	 *
	 * @return string
	 */
	public function get_execution_mode() {
		return 'sync';
	}

	/**
	 * Validate an access token (static utility for token consumption)
	 *
	 * @param string $token Raw token from URL.
	 * @return array|WP_Error Token data or error.
	 */
	public static function validate_token( $token ) {
		global $wpdb;

		if ( empty( $token ) || strlen( $token ) !== 32 ) {
			return new WP_Error( 'invalid_token', __( 'Invalid access token', 'super-forms' ) );
		}

		$token_hash = hash( 'sha256', $token );
		$table      = $wpdb->prefix . 'superforms_temp_access';

		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE token_hash = %s",
				$token_hash
			),
			ARRAY_A
		);

		if ( ! $record ) {
			return new WP_Error( 'token_not_found', __( 'Access token not found', 'super-forms' ) );
		}

		// Check if revoked.
		if ( ! empty( $record['revoked_at'] ) ) {
			return new WP_Error( 'token_revoked', __( 'Access token has been revoked', 'super-forms' ) );
		}

		// Check expiry.
		if ( strtotime( $record['expires_at'] ) < time() ) {
			return new WP_Error( 'token_expired', __( 'Access token has expired', 'super-forms' ) );
		}

		// Check max uses.
		if ( ! empty( $record['max_uses'] ) && $record['use_count'] >= $record['max_uses'] ) {
			return new WP_Error( 'token_exhausted', __( 'Access token usage limit reached', 'super-forms' ) );
		}

		// Update use count.
		$wpdb->update(
			$table,
			[
				'use_count'    => $record['use_count'] + 1,
				'last_used_at' => current_time( 'mysql' ),
			],
			[ 'id' => $record['id'] ],
			[ '%d', '%s' ],
			[ '%d' ]
		);

		// Decode metadata.
		$record['metadata'] = ! empty( $record['metadata'] )
			? json_decode( $record['metadata'], true )
			: [];

		return $record;
	}

	/**
	 * Revoke an access token
	 *
	 * @param int $token_id Token record ID.
	 * @return bool Success.
	 */
	public static function revoke_token( $token_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'superforms_temp_access',
			[ 'revoked_at' => current_time( 'mysql' ) ],
			[ 'id' => $token_id ],
			[ '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Clean up expired tokens (for scheduled job)
	 *
	 * @return int Number of deleted records.
	 */
	public static function cleanup_expired_tokens() {
		global $wpdb;

		$table   = $wpdb->prefix . 'superforms_temp_access';
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE expires_at < %s",
				current_time( 'mysql' )
			)
		);

		return $deleted;
	}
}
