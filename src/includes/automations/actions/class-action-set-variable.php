<?php
/**
 * Set Variable Action
 *
 * Sets a variable for the rest of this run: every action after it reads the
 * value as {variable_name}, and the executor also carries it as the node's
 * variable_value output. The value does not outlive the run.
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Set_Variable extends SUPER_Action_Base {

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

		// The variable lives in this run's context: the executor carries it into
		// every action after this one, where {name} reads it. There is no other
		// place to keep it — nothing read the entry, user or global stores back,
		// so a stored variable looked set and read empty.
		return [
			'success' => true,
			'variable_name' => $variable_name,
			'variable_value' => $converted_value,
			'value_type' => $value_type,
			'set_at' => current_time('mysql'),
			'variables' => [ $variable_name => $converted_value ]
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

	/**
	 * This action cannot run asynchronously
	 *
	 * Variables must be set synchronously to be available to subsequent actions.
	 *
	 * @return bool
	 * @since 6.5.0
	 */
	public function supports_async() {
		return false;
	}

	/**
	 * Get execution mode
	 *
	 * @return string
	 * @since 6.5.0
	 */
	public function get_execution_mode() {
		return 'sync';
	}
}
