<?php
/**
 * Duplicate Detection System
 *
 * Detects duplicate form submissions using multiple methods.
 * Runs BEFORE entry creation in pre-submission phase.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUPER_Duplicate_Detector {

	/**
	 * Default duplicate detection settings
	 */
	private static $defaults = array(
		'duplicate_detection_enabled' => false,
		'email_time_enabled'          => true,
		'email_field'                 => 'email',
		'email_time_window'           => 10, // minutes
		'ip_time_enabled'             => true,
		'ip_time_window'              => 5, // minutes
		'hash_enabled'                => true,
		'hash_fields'                 => array(), // Empty = all fields
		'custom_fields_enabled'       => false,
		'custom_unique_fields'        => array(), // Field names that must be unique together
		'action_on_duplicate'         => 'block', // block, update, allow
	);

	/**
	 * Check submission for duplicates
	 *
	 * @param int   $form_id   Form ID
	 * @param array $form_data Submitted form data
	 * @param array $context   Submission context
	 * @return array Result with 'duplicate' boolean and details
	 */
	public static function check( $form_id, $form_data, $context = array() ) {
		$settings = self::get_settings( $form_id );

		// Skip if duplicate detection is disabled
		if ( empty( $settings['duplicate_detection_enabled'] ) ) {
			return array( 'duplicate' => false );
		}

		// Method 1: Email + Time Window
		if ( ! empty( $settings['email_time_enabled'] ) ) {
			$result = self::check_email_time( $form_id, $form_data, $settings );
			if ( $result['duplicate'] ) {
				return $result;
			}
		}

		// Method 2: IP + Time Window
		if ( ! empty( $settings['ip_time_enabled'] ) ) {
			$result = self::check_ip_time( $form_id, $context, $settings );
			if ( $result['duplicate'] ) {
				return $result;
			}
		}

		// Method 3: Field Hash Matching
		if ( ! empty( $settings['hash_enabled'] ) ) {
			$result = self::check_hash( $form_id, $form_data, $settings );
			if ( $result['duplicate'] ) {
				return $result;
			}
		}

		// Method 4: Custom Field Combination
		if ( ! empty( $settings['custom_fields_enabled'] ) && ! empty( $settings['custom_unique_fields'] ) ) {
			$result = self::check_custom_fields( $form_id, $form_data, $settings );
			if ( $result['duplicate'] ) {
				return $result;
			}
		}

		return array(
			'duplicate'         => false,
			'method'            => null,
			'original_entry_id' => null,
		);
	}

	/**
	 * Get duplicate detection settings for form
	 *
	 * @param int $form_id Form ID
	 * @return array Settings
	 */
	public static function get_settings( $form_id ) {
		$form_settings = SUPER_Common::get_form_settings( $form_id );
		$dup_settings  = isset( $form_settings['duplicate_detection'] ) ? $form_settings['duplicate_detection'] : array();

		return wp_parse_args( $dup_settings, self::$defaults );
	}

	/**
	 * Check for duplicate by email + time window
	 *
	 * @param int   $form_id   Form ID
	 * @param array $form_data Form data
	 * @param array $settings  Detection settings
	 * @return array Result
	 */
	private static function check_email_time( $form_id, $form_data, $settings ) {
		$email_field = $settings['email_field'];

		// Handle both flat and structured form data
		$email = '';
		if ( isset( $form_data[ $email_field ] ) ) {
			$email = is_array( $form_data[ $email_field ] ) && isset( $form_data[ $email_field ]['value'] )
				? $form_data[ $email_field ]['value']
				: $form_data[ $email_field ];
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			return array( 'duplicate' => false );
		}

		$time_window = intval( $settings['email_time_window'] );
		$cutoff      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$time_window} minutes" ) );

		global $wpdb;

		// Check in EAV table first
		$entry_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ed.entry_id
				FROM {$wpdb->prefix}superforms_entry_data ed
				JOIN {$wpdb->posts} p ON ed.entry_id = p.ID
				WHERE ed.form_id = %d
				AND ed.field_name = %s
				AND ed.field_value = %s
				AND p.post_date >= %s
				AND p.post_status = 'publish'
				ORDER BY p.post_date DESC
				LIMIT 1",
				$form_id,
				$email_field,
				$email,
				$cutoff
			)
		);

		if ( $entry_id ) {
			return array(
				'duplicate'         => true,
				'method'            => 'email_time',
				'original_entry_id' => intval( $entry_id ),
				'details'           => sprintf(
					'Email "%s" submitted within %d minutes (entry #%d)',
					$email,
					$time_window,
					$entry_id
				),
				'email'             => $email,
				'time_window'       => $time_window,
			);
		}

		return array( 'duplicate' => false );
	}

	/**
	 * Check for duplicate by IP + time window
	 *
	 * @param int   $form_id  Form ID
	 * @param array $context  Submission context
	 * @param array $settings Detection settings
	 * @return array Result
	 */
	private static function check_ip_time( $form_id, $context, $settings ) {
		$user_ip = isset( $context['user_ip'] ) ? $context['user_ip'] : SUPER_Common::real_ip();

		if ( empty( $user_ip ) ) {
			return array( 'duplicate' => false );
		}

		$time_window = intval( $settings['ip_time_window'] );
		$cutoff      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$time_window} minutes" ) );

		global $wpdb;

		// Check entries by IP in postmeta
		$entry_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'super_contact_entry'
				AND p.post_parent = %d
				AND p.post_date >= %s
				AND p.post_status = 'publish'
				AND pm.meta_key = '_super_contact_entry_ip'
				AND pm.meta_value = %s
				ORDER BY p.post_date DESC
				LIMIT 1",
				$form_id,
				$cutoff,
				$user_ip
			)
		);

		if ( $entry_id ) {
			return array(
				'duplicate'         => true,
				'method'            => 'ip_time',
				'original_entry_id' => intval( $entry_id ),
				'details'           => sprintf(
					'IP %s submitted within %d minutes (entry #%d)',
					$user_ip,
					$time_window,
					$entry_id
				),
				'ip'                => $user_ip,
				'time_window'       => $time_window,
			);
		}

		return array( 'duplicate' => false );
	}

	/**
	 * Check for exact duplicate by field hash
	 *
	 * @param int   $form_id   Form ID
	 * @param array $form_data Form data
	 * @param array $settings  Detection settings
	 * @return array Result
	 */
	private static function check_hash( $form_id, $form_data, $settings ) {
		// Generate hash of submission
		$hash_fields = isset( $settings['hash_fields'] ) ? $settings['hash_fields'] : array();

		if ( empty( $hash_fields ) ) {
			// Use all non-system fields
			$hash_data = array();
			foreach ( $form_data as $key => $value ) {
				if ( strpos( $key, 'super_' ) !== 0 && strpos( $key, '_' ) !== 0 ) {
					// Extract value from structured data
					$hash_data[ $key ] = is_array( $value ) && isset( $value['value'] ) ? $value['value'] : $value;
				}
			}
		} else {
			// Use specified fields only
			$hash_data = array();
			foreach ( $hash_fields as $field ) {
				if ( isset( $form_data[ $field ] ) ) {
					$hash_data[ $field ] = is_array( $form_data[ $field ] ) && isset( $form_data[ $field ]['value'] )
						? $form_data[ $field ]['value']
						: $form_data[ $field ];
				}
			}
		}

		if ( empty( $hash_data ) ) {
			return array( 'duplicate' => false );
		}

		// Sort for consistent hashing
		ksort( $hash_data );
		$submission_hash = md5( wp_json_encode( $hash_data ) );

		global $wpdb;

		// Check for matching hash in recent entries (last 24 hours)
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

		$entry_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'super_contact_entry'
				AND p.post_parent = %d
				AND p.post_date >= %s
				AND p.post_status = 'publish'
				AND pm.meta_key = '_super_submission_hash'
				AND pm.meta_value = %s
				ORDER BY p.post_date DESC
				LIMIT 1",
				$form_id,
				$cutoff,
				$submission_hash
			)
		);

		if ( $entry_id ) {
			return array(
				'duplicate'         => true,
				'method'            => 'hash',
				'original_entry_id' => intval( $entry_id ),
				'details'           => sprintf(
					'Exact duplicate of entry #%d',
					$entry_id
				),
				'hash'              => $submission_hash,
			);
		}

		// Store hash for future duplicate checks (will be stored after entry creation)
		$GLOBALS['super_pending_submission_hash'] = $submission_hash;

		return array( 'duplicate' => false );
	}

	/**
	 * Check for duplicate by custom field combination
	 *
	 * @param int   $form_id   Form ID
	 * @param array $form_data Form data
	 * @param array $settings  Detection settings
	 * @return array Result
	 */
	private static function check_custom_fields( $form_id, $form_data, $settings ) {
		$unique_fields = $settings['custom_unique_fields'];

		if ( empty( $unique_fields ) ) {
			return array( 'duplicate' => false );
		}

		// Build conditions for each unique field
		$field_values = array();
		foreach ( $unique_fields as $field ) {
			$value = isset( $form_data[ $field ] ) ? $form_data[ $field ] : null;

			// Handle structured data
			if ( is_array( $value ) && isset( $value['value'] ) ) {
				$value = $value['value'];
			}

			if ( $value === null || $value === '' ) {
				// Skip if any required field is empty
				return array( 'duplicate' => false );
			}
			$field_values[ $field ] = $value;
		}

		global $wpdb;

		// Build query to find matching entries
		$entry_ids = null;

		foreach ( $field_values as $field => $value ) {
			$matching_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT entry_id
					FROM {$wpdb->prefix}superforms_entry_data
					WHERE form_id = %d
					AND field_name = %s
					AND field_value = %s",
					$form_id,
					$field,
					$value
				)
			);

			if ( $entry_ids === null ) {
				$entry_ids = $matching_ids;
			} else {
				// Intersect with previous results
				$entry_ids = array_intersect( $entry_ids, $matching_ids );
			}

			if ( empty( $entry_ids ) ) {
				break;
			}
		}

		if ( ! empty( $entry_ids ) ) {
			$entry_id = reset( $entry_ids );

			return array(
				'duplicate'         => true,
				'method'            => 'custom_fields',
				'original_entry_id' => intval( $entry_id ),
				'details'           => sprintf(
					'Duplicate by fields: %s (entry #%d)',
					implode( ', ', array_keys( $field_values ) ),
					$entry_id
				),
				'matched_fields'    => $field_values,
			);
		}

		return array( 'duplicate' => false );
	}

	/**
	 * Store submission hash after entry creation
	 *
	 * Called after successful entry creation to enable hash-based detection.
	 *
	 * @param int $entry_id Entry ID
	 */
	public static function store_submission_hash( $entry_id ) {
		if ( ! empty( $GLOBALS['super_pending_submission_hash'] ) ) {
			update_post_meta( $entry_id, '_super_submission_hash', $GLOBALS['super_pending_submission_hash'] );
			unset( $GLOBALS['super_pending_submission_hash'] );
		}
	}

	/**
	 * Get the action to take on duplicate detection
	 *
	 * @param int $form_id Form ID
	 * @return string Action: 'block', 'update', or 'allow'
	 */
	public static function get_action( $form_id ) {
		$settings = self::get_settings( $form_id );
		return isset( $settings['action_on_duplicate'] ) ? $settings['action_on_duplicate'] : 'block';
	}

	/**
	 * Log duplicate detection for analytics
	 *
	 * @param int   $form_id Form ID
	 * @param array $result  Detection result
	 * @param array $context Submission context
	 */
	public static function log_detection( $form_id, $result, $context = array() ) {
		// Use trigger logger if available (singleton pattern)
		if ( class_exists( 'SUPER_Automation_Logger' ) ) {
			$logger = SUPER_Automation_Logger::instance();
			$logger->info(
				sprintf( 'Duplicate detected: %s', $result['method'] ),
				array(
					'trigger_name'      => 'DuplicateDetector',
					'action_name'       => 'Detection',
					'form_id'           => $form_id,
					'method'            => $result['method'],
					'original_entry_id' => isset( $result['original_entry_id'] ) ? $result['original_entry_id'] : null,
					'details'           => isset( $result['details'] ) ? $result['details'] : '',
					'user_ip'           => isset( $context['user_ip'] ) ? $context['user_ip'] : '',
				)
			);
		}

		// Also log to WP debug log if enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Super Forms] Duplicate detected on form %d via %s: %s',
					$form_id,
					isset( $result['method'] ) ? $result['method'] : 'unknown',
					isset( $result['details'] ) ? $result['details'] : ''
				)
			);
		}
	}
}
