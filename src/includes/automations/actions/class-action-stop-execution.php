<?php
/**
 * Stop Execution Action
 *
 * Stops the execution of subsequent actions in the trigger
 * Useful for conditional workflows where certain conditions should halt processing
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Stop_Execution extends SUPER_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'stop_execution';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __('Stop Execution', 'super-forms');
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Stop executing subsequent actions in this trigger. Useful for conditional workflows.', 'super-forms');
	}

	/**
	 * Get action category
	 *
	 * @return string
	 */
	public function get_category() {
		return 'flow_control';
	}

	/**
	 * Get settings schema for UI
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [
			[
				'name' => 'reason',
				'label' => __('Stop Reason', 'super-forms'),
				'type' => 'text',
				'required' => false,
				'default' => 'Execution stopped by action',
				'description' => __('Optional reason why execution was stopped (for logging purposes)', 'super-forms')
			],
			[
				'name' => 'log_stop',
				'label' => __('Log Stop Event', 'super-forms'),
				'type' => 'checkbox',
				'required' => false,
				'default' => true,
				'description' => __('Log when execution is stopped', 'super-forms')
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
		$reason = $config['reason'] ?? 'Execution stopped by action';
		$reason = $this->replace_tags($reason, $context);

		$log_stop = $config['log_stop'] ?? true;

		// Log if requested
		if ($log_stop && defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log(sprintf(
				'[Super Forms Trigger] [STOP] %s | Trigger: %s | Event: %s',
				$reason,
				$context['automation_id'] ?? 'unknown',
				$context['event_id'] ?? 'unknown'
			));
		}

		// Return success with stop_execution flag
		// The Executor checks for this flag and stops processing remaining actions
		return [
			'success' => true,
			'stop_execution' => true,
			'reason' => $reason,
			'stopped_at' => current_time('mysql')
		];
	}

	/**
	 * Check if action can run
	 *
	 * Stop execution can always run
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
		// Any user with form access can use stop execution
		return ['edit_posts'];
	}

	/**
	 * This action cannot run asynchronously
	 *
	 * It must execute synchronously to control the flow of subsequent actions.
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
