<?php
/**
 * Delay Execution Action
 *
 * Delays execution of subsequent actions using Action Scheduler
 * Requires Action Scheduler library
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class SUPER_Action_Delay_Execution extends SUPER_Trigger_Action_Base {

	public function get_id() {
		return 'delay_execution';
	}

	public function get_label() {
		return __('Delay Execution', 'super-forms');
	}

	public function get_description() {
		return __('Schedule actions to run after a specified delay. Requires Action Scheduler.', 'super-forms');
	}

	public function get_category() {
		return 'flow_control';
	}

	public function get_settings_schema() {
		return [
			[
				'name' => 'delay_amount',
				'label' => __('Delay Amount', 'super-forms'),
				'type' => 'number',
				'required' => true,
				'default' => 1,
				'min' => 1,
				'description' => __('How long to delay', 'super-forms')
			],
			[
				'name' => 'delay_unit',
				'label' => __('Delay Unit', 'super-forms'),
				'type' => 'select',
				'required' => true,
				'default' => 'minutes',
				'options' => [
					'minutes' => 'Minutes',
					'hours' => 'Hours',
					'days' => 'Days',
					'weeks' => 'Weeks'
				]
			],
			[
				'name' => 'actions_to_delay',
				'label' => __('Actions to Schedule', 'super-forms'),
				'type' => 'action_list',
				'required' => true,
				'description' => __('Actions to execute after the delay', 'super-forms')
			],
			[
				'name' => 'unique_execution',
				'label' => __('Unique Execution', 'super-forms'),
				'type' => 'checkbox',
				'default' => false,
				'description' => __('Prevent scheduling if identical delayed action already exists', 'super-forms')
			]
		];
	}

	public function execute($context, $config) {
		// Check if Action Scheduler is available
		if (!function_exists('as_schedule_single_action')) {
			return [
				'success' => false,
				'error' => 'Action Scheduler is required for delayed execution'
			];
		}

		$delay_amount = absint($config['delay_amount'] ?? 1);
		$delay_unit = $config['delay_unit'] ?? 'minutes';
		$actions_to_delay = $config['actions_to_delay'] ?? [];
		$unique = !empty($config['unique_execution']);

		if (empty($actions_to_delay)) {
			return [
				'success' => false,
				'error' => 'No actions specified to delay'
			];
		}

		// Calculate timestamp
		$delay_seconds = $this->convert_to_seconds($delay_amount, $delay_unit);
		$scheduled_time = time() + $delay_seconds;

		// Prepare hook arguments
		$hook_args = [
			'trigger_id' => $context['trigger_id'] ?? 0,
			'context' => $context,
			'actions' => $actions_to_delay
		];

		// Schedule the action
		$hook = 'super_execute_delayed_trigger_actions';

		if ($unique) {
			// Check if already scheduled
			$existing = as_get_scheduled_actions([
				'hook' => $hook,
				'args' => $hook_args,
				'status' => 'pending'
			], 'ids');

			if (!empty($existing)) {
				return [
					'success' => false,
					'error' => 'Identical delayed action already scheduled',
					'existing_action_id' => $existing[0]
				];
			}
		}

		// Schedule the action
		$action_id = as_schedule_single_action($scheduled_time, $hook, $hook_args, 'super-forms-triggers');

		return [
			'success' => true,
			'action_id' => $action_id,
			'scheduled_time' => date('Y-m-d H:i:s', $scheduled_time),
			'delay_amount' => $delay_amount,
			'delay_unit' => $delay_unit,
			'actions_count' => count($actions_to_delay),
			'unique' => $unique
		];
	}

	/**
	 * Convert delay to seconds
	 */
	private function convert_to_seconds($amount, $unit) {
		switch ($unit) {
			case 'minutes':
				return $amount * 60;
			case 'hours':
				return $amount * 3600;
			case 'days':
				return $amount * 86400;
			case 'weeks':
				return $amount * 604800;
			default:
				return $amount * 60;
		}
	}

	public function can_run($context) {
		return function_exists('as_schedule_single_action');
	}

	public function get_required_capabilities() {
		return ['edit_posts'];
	}
}
