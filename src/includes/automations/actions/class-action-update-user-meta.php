<?php
/**
 * Update User Meta Action
 *
 * Updates WordPress user meta fields
 * Useful for storing form data in user profiles
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Update_User_Meta extends SUPER_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'update_user_meta';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __('Update User Meta', 'super-forms');
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Update custom fields (meta data) for a WordPress user. Useful for profile updates.', 'super-forms');
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'wordpress_integration';
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name' => 'user_id',
				'label' => __('User ID', 'super-forms'),
				'type' => 'text',
				'required' => false,
				'placeholder' => '{user_id} or {form_data.user_id} or leave empty for current user',
				'description' => __('ID of user to update. Supports {tags}. Leave empty for current logged-in user.', 'super-forms')
			],
			[
				'name' => 'meta_key',
				'label' => __('Meta Key', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => 'phone_number',
				'description' => __('Meta field name to update', 'super-forms')
			],
			[
				'name' => 'meta_value',
				'label' => __('Meta Value', 'super-forms'),
				'type' => 'textarea',
				'required' => true,
				'placeholder' => '{form_data.phone}',
				'description' => __('Value to set. Supports {tags}.', 'super-forms')
			],
			[
				'name' => 'value_type',
				'label' => __('Value Type', 'super-forms'),
				'type' => 'select',
				'required' => false,
				'default' => 'string',
				'options' => [
					'string' => 'String',
					'number' => 'Number',
					'boolean' => 'Boolean',
					'json' => 'JSON (parse and store as array)',
					'serialize' => 'Serialized (PHP serialize)'
				],
				'description' => __('How to store the value', 'super-forms')
			],
			[
				'name' => 'update_mode',
				'label' => __('Update Mode', 'super-forms'),
				'type' => 'select',
				'required' => false,
				'default' => 'replace',
				'options' => [
					'replace' => 'Replace (overwrite existing value)',
					'append' => 'Append (add to end)',
					'prepend' => 'Prepend (add to start)',
					'add_if_not_exists' => 'Only add if doesn\'t exist'
				],
				'description' => __('How to update the meta value', 'super-forms')
			],
			[
				'name' => 'require_permission',
				'label' => __('Require Edit User Permission', 'super-forms'),
				'type' => 'checkbox',
				'required' => false,
				'default' => true,
				'description' => __('Require current user to have edit_users capability', 'super-forms')
			]
		];
	}

	/**
	 * Execute the action
	 *
	 * @param array $context Event context data
	 * @param array $config Action configuration
	 * @return array Result data
	 */
	public function execute($context, $config) {
		// Get user ID
		$user_id = $config['user_id'] ?? '';
		if (empty($user_id)) {
			// Default to current user
			$user_id = get_current_user_id();
		} else {
			// Replace tags and convert to int
			$user_id = $this->replace_tags($user_id, $context);
			$user_id = absint($user_id);
		}

		if (empty($user_id)) {
			return [
				'success' => false,
				'error' => 'Valid user ID is required or user must be logged in'
			];
		}

		// Verify user exists
		$user = get_userdata($user_id);
		if (!$user) {
			return [
				'success' => false,
				'error' => 'User not found: ' . $user_id
			];
		}

		// Check permissions if required
		$require_permission = $config['require_permission'] ?? true;
		if ($require_permission && !current_user_can('edit_users')) {
			return [
				'success' => false,
				'error' => 'Permission denied: edit_users capability required'
			];
		}

		// Get meta key
		$meta_key = $config['meta_key'] ?? '';
		if (empty($meta_key)) {
			return [
				'success' => false,
				'error' => 'Meta key is required'
			];
		}

		// Sanitize meta key
		$meta_key = sanitize_key($meta_key);

		// Get and process value
		$meta_value = $config['meta_value'] ?? '';
		$meta_value = $this->replace_tags($meta_value, $context);

		// Convert value based on type
		$value_type = $config['value_type'] ?? 'string';
		$converted_value = $this->convert_value($meta_value, $value_type);

		if (is_wp_error($converted_value)) {
			return [
				'success' => false,
				'error' => $converted_value->get_error_message()
			];
		}

		// Get update mode
		$update_mode = $config['update_mode'] ?? 'replace';

		// Handle different update modes
		$final_value = $this->get_final_value($user_id, $meta_key, $converted_value, $update_mode);

		// Update user meta
		$updated = update_user_meta($user_id, $meta_key, $final_value);

		return [
			'success' => true,
			'user_id' => $user_id,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'meta_key' => $meta_key,
			'meta_value' => $final_value,
			'value_type' => $value_type,
			'update_mode' => $update_mode,
			'was_updated' => $updated !== false,
			'updated_at' => current_time('mysql')
		];
	}

	/**
	 * Convert value to specified type
	 *
	 * @param mixed $value Raw value
	 * @param string $type Target type
	 * @return mixed|WP_Error
	 */
	private function convert_value($value, $type) {
		switch ($type) {
			case 'number':
				if (!is_numeric($value)) {
					return new WP_Error('invalid_number', 'Value must be numeric');
				}
				return floatval($value);

			case 'boolean':
				return filter_var($value, FILTER_VALIDATE_BOOLEAN);

			case 'json':
				$decoded = json_decode($value, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					return new WP_Error('invalid_json', 'Invalid JSON: ' . json_last_error_msg());
				}
				return $decoded;

			case 'serialize':
				return maybe_unserialize($value);

			case 'string':
			default:
				return (string)$value;
		}
	}

	/**
	 * Get final value based on update mode
	 *
	 * @param int $user_id User ID
	 * @param string $meta_key Meta key
	 * @param mixed $new_value New value
	 * @param string $mode Update mode
	 * @return mixed Final value
	 */
	private function get_final_value($user_id, $meta_key, $new_value, $mode) {
		switch ($mode) {
			case 'append':
				$existing = get_user_meta($user_id, $meta_key, true);
				return $existing . $new_value;

			case 'prepend':
				$existing = get_user_meta($user_id, $meta_key, true);
				return $new_value . $existing;

			case 'add_if_not_exists':
				$existing = get_user_meta($user_id, $meta_key, true);
				return $existing !== '' ? $existing : $new_value;

			case 'replace':
			default:
				return $new_value;
		}
	}

	/**
	 * Check if action can run
	 *
	 * @param array $context
	 * @return bool
	 */
	public function can_run($context) {
		return true;
	}

	/**
	 * Get required capabilities
	 *
	 * @return array
	 */
	public function get_required_capabilities() {
		// Will be checked during execution based on config
		return ['edit_posts'];
	}
}
