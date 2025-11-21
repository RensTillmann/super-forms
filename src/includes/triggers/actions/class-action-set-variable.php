<?php
/**
 * Set Variable Action
 *
 * Sets a variable in the trigger context that can be used by subsequent actions
 * Variables are stored in memory for the current trigger execution only
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Set_Variable extends SUPER_Trigger_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'set_variable';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __('Set Variable', 'super-forms');
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Set a variable that can be used by subsequent actions. Useful for passing data between actions.', 'super-forms');
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'data_management';
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name' => 'variable_name',
				'label' => __('Variable Name', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => 'my_variable',
				'description' => __('Name of the variable to set (alphanumeric and underscores only)', 'super-forms'),
				'validation' => 'alphanumeric_underscore'
			],
			[
				'name' => 'variable_value',
				'label' => __('Variable Value', 'super-forms'),
				'type' => 'textarea',
				'required' => true,
				'placeholder' => '{form_data.email}',
				'description' => __('Value to set. Supports {tag} replacement.', 'super-forms')
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
					'boolean' => 'Boolean (true/false)',
					'json' => 'JSON (parse as object/array)'
				],
				'description' => __('How to interpret the value', 'super-forms')
			],
			[
				'name' => 'scope',
				'label' => __('Variable Scope', 'super-forms'),
				'type' => 'select',
				'required' => false,
				'default' => 'trigger',
				'options' => [
					'trigger' => 'Trigger (current execution only)',
					'entry' => 'Entry (stored in entry meta)',
					'user' => 'User (stored in user meta)',
					'global' => 'Global (stored in options table)'
				],
				'description' => __('Where to store the variable', 'super-forms')
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
		// Validate variable name
		$variable_name = $config['variable_name'] ?? '';
		if (empty($variable_name)) {
			return [
				'success' => false,
				'error' => 'Variable name is required'
			];
		}

		// Sanitize variable name (alphanumeric and underscores only)
		$variable_name = preg_replace('/[^a-zA-Z0-9_]/', '', $variable_name);
		if (empty($variable_name)) {
			return [
				'success' => false,
				'error' => 'Variable name must contain alphanumeric characters or underscores'
			];
		}

		// Get and process value
		$variable_value = $config['variable_value'] ?? '';
		$variable_value = $this->replace_tags($variable_value, $context);

		// Convert value based on type
		$value_type = $config['value_type'] ?? 'string';
		$converted_value = $this->convert_value($variable_value, $value_type);

		if (is_wp_error($converted_value)) {
			return [
				'success' => false,
				'error' => $converted_value->get_error_message()
			];
		}

		// Get scope
		$scope = $config['scope'] ?? 'trigger';

		// Store variable based on scope
		$stored = $this->store_variable($variable_name, $converted_value, $scope, $context);

		if (is_wp_error($stored)) {
			return [
				'success' => false,
				'error' => $stored->get_error_message()
			];
		}

		return [
			'success' => true,
			'variable_name' => $variable_name,
			'variable_value' => $converted_value,
			'value_type' => $value_type,
			'scope' => $scope,
			'set_at' => current_time('mysql')
		];
	}

	/**
	 * Convert value to specified type
	 *
	 * @param mixed $value Raw value
	 * @param string $type Target type
	 * @return mixed|WP_Error Converted value or error
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
					return new WP_Error('invalid_json', 'Value is not valid JSON: ' . json_last_error_msg());
				}
				return $decoded;

			case 'string':
			default:
				return (string)$value;
		}
	}

	/**
	 * Store variable based on scope
	 *
	 * @param string $name Variable name
	 * @param mixed $value Variable value
	 * @param string $scope Storage scope
	 * @param array $context Event context
	 * @return bool|WP_Error Success or error
	 */
	private function store_variable($name, $value, $scope, $context) {
		switch ($scope) {
			case 'trigger':
				// Store in context for subsequent actions (handled by executor)
				// Return success - executor will add to context
				return true;

			case 'entry':
				if (empty($context['entry_id'])) {
					return new WP_Error('no_entry', 'Entry scope requires entry_id in context');
				}
				update_post_meta($context['entry_id'], '_super_var_' . $name, $value);
				return true;

			case 'user':
				$user_id = $context['user_id'] ?? get_current_user_id();
				if (empty($user_id)) {
					return new WP_Error('no_user', 'User scope requires user_id in context or logged-in user');
				}
				update_user_meta($user_id, '_super_var_' . $name, $value);
				return true;

			case 'global':
				update_option('_super_var_' . $name, $value);
				return true;

			default:
				return new WP_Error('invalid_scope', 'Invalid variable scope');
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
		return ['edit_posts'];
	}
}
