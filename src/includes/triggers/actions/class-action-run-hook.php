<?php
/**
 * Run Hook Action
 *
 * Executes a WordPress action or filter hook
 * Allows custom code integration without modifying core
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Run_Hook extends SUPER_Trigger_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'run_hook';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __('Run Hook', 'super-forms');
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Execute a WordPress action hook for custom integrations. Developers can hook into this to run custom code.', 'super-forms');
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'developer';
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name' => 'hook_name',
				'label' => __('Hook Name', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => 'my_custom_hook',
				'description' => __('Name of the WordPress action hook to fire', 'super-forms')
			],
			[
				'name' => 'pass_context',
				'label' => __('Pass Event Context', 'super-forms'),
				'type' => 'checkbox',
				'required' => false,
				'default' => true,
				'description' => __('Pass the full event context as the first parameter', 'super-forms')
			],
			[
				'name' => 'pass_config',
				'label' => __('Pass Action Config', 'super-forms'),
				'type' => 'checkbox',
				'required' => false,
				'default' => false,
				'description' => __('Pass the action configuration as the second parameter', 'super-forms')
			],
			[
				'name' => 'custom_params',
				'label' => __('Custom Parameters', 'super-forms'),
				'type' => 'textarea',
				'required' => false,
				'placeholder' => 'param1\nparam2\n{form_data.field}',
				'description' => __('Additional parameters (one per line). Supports {tags}.', 'super-forms')
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
		// Get hook name
		$hook_name = $config['hook_name'] ?? '';
		if (empty($hook_name)) {
			return [
				'success' => false,
				'error' => 'Hook name is required'
			];
		}

		// Sanitize hook name
		$hook_name = sanitize_key($hook_name);

		// Build parameters
		$params = [];

		// Add context if requested
		if ($config['pass_context'] ?? true) {
			$params[] = $context;
		}

		// Add config if requested
		if ($config['pass_config'] ?? false) {
			$params[] = $config;
		}

		// Add custom parameters
		if (!empty($config['custom_params'])) {
			$custom_params = explode("\n", $config['custom_params']);
			foreach ($custom_params as $param) {
				$param = trim($param);
				if (!empty($param)) {
					$param = $this->replace_tags($param, $context);
					$params[] = $param;
				}
			}
		}

		// Count hooks before execution
		$hooks_before = 0;
		if (isset($GLOBALS['wp_filter'][$hook_name])) {
			$hooks_before = count($GLOBALS['wp_filter'][$hook_name]->callbacks);
		}

		// Execute the hook
		do_action_ref_array($hook_name, $params);

		// Check if any hooks were actually attached
		$has_listeners = $hooks_before > 0;

		return [
			'success' => true,
			'hook_name' => $hook_name,
			'param_count' => count($params),
			'has_listeners' => $has_listeners,
			'listener_count' => $hooks_before,
			'executed_at' => current_time('mysql')
		];
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
		// Running hooks requires elevated permissions
		return ['manage_options'];
	}
}
