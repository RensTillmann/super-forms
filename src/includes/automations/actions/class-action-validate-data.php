<?php
/**
 * Validate Data Action
 *
 * Run custom validation rules on form data beyond standard field validation.
 * Useful for cross-field validation, business logic checks, and conditional requirements.
 *
 * @package Super_Forms
 * @subpackage Automations/Actions
 * @since 6.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUPER_Action_Validate_Data extends SUPER_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'validate_data';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Validate Data', 'super-forms' );
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
		return __( 'Run custom validation rules on form data. Check required fields, formats, ranges, patterns, and uniqueness.', 'super-forms' );
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name'        => 'rules',
				'label'       => __( 'Validation Rules', 'super-forms' ),
				'type'        => 'repeater',
				'required'    => true,
				'fields'      => [
					[
						'name'        => 'field',
						'label'       => __( 'Field Name', 'super-forms' ),
						'type'        => 'text',
						'required'    => true,
						'placeholder' => 'email',
					],
					[
						'name'     => 'rule',
						'label'    => __( 'Rule', 'super-forms' ),
						'type'     => 'select',
						'required' => true,
						'default'  => 'required',
						'options'  => [
							'required'    => __( 'Required', 'super-forms' ),
							'email'       => __( 'Valid Email', 'super-forms' ),
							'url'         => __( 'Valid URL', 'super-forms' ),
							'phone'       => __( 'Valid Phone', 'super-forms' ),
							'numeric'     => __( 'Numeric', 'super-forms' ),
							'integer'     => __( 'Integer', 'super-forms' ),
							'min'         => __( 'Minimum Value', 'super-forms' ),
							'max'         => __( 'Maximum Value', 'super-forms' ),
							'min_length'  => __( 'Minimum Length', 'super-forms' ),
							'max_length'  => __( 'Maximum Length', 'super-forms' ),
							'regex'       => __( 'Regex Pattern', 'super-forms' ),
							'equals'      => __( 'Equals Value', 'super-forms' ),
							'not_equals'  => __( 'Not Equals Value', 'super-forms' ),
							'in_list'     => __( 'In List', 'super-forms' ),
							'not_in_list' => __( 'Not In List', 'super-forms' ),
							'unique'      => __( 'Unique in Database', 'super-forms' ),
							'date'        => __( 'Valid Date', 'super-forms' ),
							'date_after'  => __( 'Date After', 'super-forms' ),
							'date_before' => __( 'Date Before', 'super-forms' ),
						],
					],
					[
						'name'        => 'value',
						'label'       => __( 'Value/Pattern', 'super-forms' ),
						'type'        => 'text',
						'required'    => false,
						'placeholder' => __( 'Depends on rule', 'super-forms' ),
						'description' => __( 'Pattern for regex, number for min/max, comma-separated for lists', 'super-forms' ),
					],
					[
						'name'        => 'message',
						'label'       => __( 'Error Message', 'super-forms' ),
						'type'        => 'text',
						'required'    => false,
						'placeholder' => __( 'Custom error message', 'super-forms' ),
						'description' => __( 'Leave empty for default message', 'super-forms' ),
					],
				],
				'description' => __( 'Define validation rules to check', 'super-forms' ),
			],
			[
				'name'        => 'stop_on_first_error',
				'label'       => __( 'Stop on First Error', 'super-forms' ),
				'type'        => 'toggle',
				'required'    => false,
				'default'     => false,
				'description' => __( 'Stop validation after the first error is found', 'super-forms' ),
			],
			[
				'name'        => 'fail_action',
				'label'       => __( 'On Validation Failure', 'super-forms' ),
				'type'        => 'select',
				'required'    => false,
				'default'     => 'continue',
				'options'     => [
					'continue'       => __( 'Continue (return errors in result)', 'super-forms' ),
					'stop_workflow'  => __( 'Stop Workflow Execution', 'super-forms' ),
					'abort_submit'   => __( 'Abort Form Submission', 'super-forms' ),
				],
				'description' => __( 'What to do when validation fails', 'super-forms' ),
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
		$rules = $config['rules'] ?? [];

		if ( empty( $rules ) ) {
			return [
				'success' => true,
				'data'    => [
					'valid'   => true,
					'errors'  => [],
					'message' => __( 'No validation rules defined', 'super-forms' ),
				],
			];
		}

		$form_data          = $context['form_data'] ?? [];
		$stop_on_first      = ! empty( $config['stop_on_first_error'] );
		$fail_action        = $config['fail_action'] ?? 'continue';
		$errors             = [];
		$validated_fields   = [];

		foreach ( $rules as $rule ) {
			$field_name  = $rule['field'] ?? '';
			$rule_type   = $rule['rule'] ?? 'required';
			$rule_value  = $rule['value'] ?? '';
			$custom_msg  = $rule['message'] ?? '';

			if ( empty( $field_name ) ) {
				continue;
			}

			$field_value = $form_data[ $field_name ] ?? null;
			$is_valid    = $this->validate_rule( $field_value, $rule_type, $rule_value, $context );

			if ( ! $is_valid ) {
				$error = [
					'field'   => $field_name,
					'rule'    => $rule_type,
					'message' => ! empty( $custom_msg )
						? $custom_msg
						: $this->get_default_message( $rule_type, $field_name, $rule_value ),
				];
				$errors[] = $error;

				if ( $stop_on_first ) {
					break;
				}
			}

			$validated_fields[] = $field_name;
		}

		$is_valid = empty( $errors );

		// Handle failure action.
		if ( ! $is_valid ) {
			switch ( $fail_action ) {
				case 'stop_workflow':
					return [
						'success'        => true,
						'stop_execution' => true,
						'data'           => [
							'valid'            => false,
							'errors'           => $errors,
							'validated_fields' => $validated_fields,
							'message'          => __( 'Validation failed - workflow stopped', 'super-forms' ),
						],
					];

				case 'abort_submit':
					return new WP_Error(
						'validation_failed',
						$errors[0]['message'] ?? __( 'Validation failed', 'super-forms' ),
						[
							'valid'  => false,
							'errors' => $errors,
						]
					);
			}
		}

		return [
			'success' => true,
			'data'    => [
				'valid'            => $is_valid,
				'errors'           => $errors,
				'validated_fields' => $validated_fields,
				'rules_checked'    => count( $rules ),
				'message'          => $is_valid
					? __( 'All validation rules passed', 'super-forms' )
					: sprintf(
						__( 'Validation failed: %d error(s)', 'super-forms' ),
						count( $errors )
					),
			],
		];
	}

	/**
	 * Validate a single rule
	 *
	 * @param mixed  $value      Field value.
	 * @param string $rule_type  Rule type.
	 * @param string $rule_value Rule parameter.
	 * @param array  $context    Full context for unique checks.
	 * @return bool Is valid.
	 */
	private function validate_rule( $value, $rule_type, $rule_value, $context ) {
		// Handle empty values - only 'required' should fail.
		$is_empty = $this->is_empty_value( $value );

		switch ( $rule_type ) {
			case 'required':
				return ! $is_empty;

			case 'email':
				return $is_empty || is_email( $value );

			case 'url':
				return $is_empty || filter_var( $value, FILTER_VALIDATE_URL );

			case 'phone':
				if ( $is_empty ) {
					return true;
				}
				// Basic phone validation - digits, spaces, dashes, plus, parentheses.
				$cleaned = preg_replace( '/[\s\-\(\)\+]/', '', $value );
				return preg_match( '/^[0-9]{7,15}$/', $cleaned );

			case 'numeric':
				return $is_empty || is_numeric( $value );

			case 'integer':
				return $is_empty || ( is_numeric( $value ) && intval( $value ) == $value );

			case 'min':
				if ( $is_empty ) {
					return true;
				}
				$num = is_numeric( $value ) ? floatval( $value ) : strlen( $value );
				return $num >= floatval( $rule_value );

			case 'max':
				if ( $is_empty ) {
					return true;
				}
				$num = is_numeric( $value ) ? floatval( $value ) : strlen( $value );
				return $num <= floatval( $rule_value );

			case 'min_length':
				return $is_empty || strlen( (string) $value ) >= intval( $rule_value );

			case 'max_length':
				return $is_empty || strlen( (string) $value ) <= intval( $rule_value );

			case 'regex':
				if ( $is_empty || empty( $rule_value ) ) {
					return true;
				}
				// Add delimiters if not present.
				$pattern = $rule_value;
				if ( '/' !== substr( $pattern, 0, 1 ) ) {
					$pattern = '/' . $pattern . '/';
				}
				return (bool) @preg_match( $pattern, $value );

			case 'equals':
				return $is_empty || $value === $rule_value;

			case 'not_equals':
				return $is_empty || $value !== $rule_value;

			case 'in_list':
				if ( $is_empty ) {
					return true;
				}
				$list = array_map( 'trim', explode( ',', $rule_value ) );
				return in_array( $value, $list, true );

			case 'not_in_list':
				if ( $is_empty ) {
					return true;
				}
				$list = array_map( 'trim', explode( ',', $rule_value ) );
				return ! in_array( $value, $list, true );

			case 'unique':
				return $is_empty || $this->check_unique( $value, $rule_value, $context );

			case 'date':
				if ( $is_empty ) {
					return true;
				}
				$timestamp = strtotime( $value );
				return false !== $timestamp;

			case 'date_after':
				if ( $is_empty ) {
					return true;
				}
				$value_time = strtotime( $value );
				$compare    = strtotime( $rule_value );
				return $value_time && $compare && $value_time > $compare;

			case 'date_before':
				if ( $is_empty ) {
					return true;
				}
				$value_time = strtotime( $value );
				$compare    = strtotime( $rule_value );
				return $value_time && $compare && $value_time < $compare;

			default:
				return true;
		}
	}

	/**
	 * Check if a value is empty
	 *
	 * @param mixed $value Value to check.
	 * @return bool Is empty.
	 */
	private function is_empty_value( $value ) {
		if ( null === $value ) {
			return true;
		}
		if ( is_string( $value ) && '' === trim( $value ) ) {
			return true;
		}
		if ( is_array( $value ) && empty( $value ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if value is unique in database
	 *
	 * @param mixed  $value     Field value.
	 * @param string $config    Config string (table:column or field_name).
	 * @param array  $context   Context with form_id.
	 * @return bool Is unique.
	 */
	private function check_unique( $value, $config, $context ) {
		global $wpdb;

		// Default: check in entry_data for this form.
		$form_id = $context['form_id'] ?? 0;

		if ( empty( $form_id ) ) {
			return true; // Can't check without form_id.
		}

		// Parse config - can be "field_name" or "table:column".
		if ( strpos( $config, ':' ) !== false ) {
			// Custom table:column format (advanced).
			list( $table, $column ) = explode( ':', $config, 2 );
			$table  = sanitize_key( $table );
			$column = sanitize_key( $column );

			// Whitelist allowed tables AND columns to prevent SQL injection.
			$allowed = [
				'users'    => [ 'user_login', 'user_email', 'user_nicename', 'display_name' ],
				'posts'    => [ 'post_title', 'post_name', 'post_content' ],
				'postmeta' => [ 'meta_value' ],
				'usermeta' => [ 'meta_value' ],
			];

			if ( ! isset( $allowed[ $table ] ) || ! in_array( $column, $allowed[ $table ], true ) ) {
				return true; // Unknown table/column, skip check.
			}

			$full_table = $wpdb->prefix . $table;
			if ( 'users' === $table ) {
				$full_table = $wpdb->users;
			}

			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$full_table} WHERE {$column} = %s",
					$value
				)
			);

			return 0 === intval( $count );
		}

		// Default: check entry_data table for this form.
		$field_name = ! empty( $config ) ? sanitize_key( $config ) : null;
		$table      = $wpdb->prefix . 'superforms_entry_data';

		// Exclude current entry if editing.
		$entry_id = $context['entry_id'] ?? 0;

		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE form_id = %d AND field_value = %s",
			$form_id,
			$value
		);

		if ( $field_name ) {
			$query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE form_id = %d AND field_name = %s AND field_value = %s",
				$form_id,
				$field_name,
				$value
			);
		}

		if ( $entry_id ) {
			$query .= $wpdb->prepare( ' AND entry_id != %d', $entry_id );
		}

		$count = $wpdb->get_var( $query );

		return 0 === intval( $count );
	}

	/**
	 * Get default error message for a rule
	 *
	 * @param string $rule_type  Rule type.
	 * @param string $field_name Field name.
	 * @param string $rule_value Rule parameter.
	 * @return string Error message.
	 */
	private function get_default_message( $rule_type, $field_name, $rule_value ) {
		$field_label = ucfirst( str_replace( [ '_', '-' ], ' ', $field_name ) );

		$messages = [
			'required'    => sprintf( __( '%s is required', 'super-forms' ), $field_label ),
			'email'       => sprintf( __( '%s must be a valid email address', 'super-forms' ), $field_label ),
			'url'         => sprintf( __( '%s must be a valid URL', 'super-forms' ), $field_label ),
			'phone'       => sprintf( __( '%s must be a valid phone number', 'super-forms' ), $field_label ),
			'numeric'     => sprintf( __( '%s must be a number', 'super-forms' ), $field_label ),
			'integer'     => sprintf( __( '%s must be a whole number', 'super-forms' ), $field_label ),
			'min'         => sprintf( __( '%s must be at least %s', 'super-forms' ), $field_label, $rule_value ),
			'max'         => sprintf( __( '%s must be at most %s', 'super-forms' ), $field_label, $rule_value ),
			'min_length'  => sprintf( __( '%s must be at least %s characters', 'super-forms' ), $field_label, $rule_value ),
			'max_length'  => sprintf( __( '%s must be at most %s characters', 'super-forms' ), $field_label, $rule_value ),
			'regex'       => sprintf( __( '%s format is invalid', 'super-forms' ), $field_label ),
			'equals'      => sprintf( __( '%s must equal %s', 'super-forms' ), $field_label, $rule_value ),
			'not_equals'  => sprintf( __( '%s must not equal %s', 'super-forms' ), $field_label, $rule_value ),
			'in_list'     => sprintf( __( '%s must be one of: %s', 'super-forms' ), $field_label, $rule_value ),
			'not_in_list' => sprintf( __( '%s must not be one of: %s', 'super-forms' ), $field_label, $rule_value ),
			'unique'      => sprintf( __( '%s must be unique', 'super-forms' ), $field_label ),
			'date'        => sprintf( __( '%s must be a valid date', 'super-forms' ), $field_label ),
			'date_after'  => sprintf( __( '%s must be after %s', 'super-forms' ), $field_label, $rule_value ),
			'date_before' => sprintf( __( '%s must be before %s', 'super-forms' ), $field_label, $rule_value ),
		];

		return $messages[ $rule_type ] ?? sprintf( __( '%s is invalid', 'super-forms' ), $field_label );
	}

	/**
	 * Supports async execution
	 *
	 * @return bool
	 */
	public function supports_async() {
		return false; // Validation should run synchronously.
	}

	/**
	 * Get execution mode - always sync for validation
	 *
	 * @return string
	 */
	public function get_execution_mode() {
		return 'sync';
	}
}
