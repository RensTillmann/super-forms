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
		// Check if Action Scheduler is available via Scheduler class
		if ( ! SUPER_Trigger_Scheduler::is_available() ) {
			return [
				'success' => false,
				'error'   => __( 'Action Scheduler is required for delayed execution', 'super-forms' ),
			];
		}

		$delay_amount     = absint( $config['delay_amount'] ?? 1 );
		$delay_unit       = $config['delay_unit'] ?? 'minutes';
		$actions_to_delay = $config['actions_to_delay'] ?? [];
		$unique           = ! empty( $config['unique_execution'] );

		if ( empty( $actions_to_delay ) ) {
			return [
				'success' => false,
				'error'   => __( 'No actions specified to delay', 'super-forms' ),
			];
		}

		// Calculate delay in seconds
		$delay_seconds  = $this->convert_to_seconds( $delay_amount, $delay_unit );
		$scheduled_time = time() + $delay_seconds;

		// Prepare hook arguments - this will be passed as a single argument to the handler
		$hook_args = [
			'trigger_id' => $context['trigger_id'] ?? 0,
			'context'    => $context,
			'actions'    => $actions_to_delay,
		];

		// Use the Scheduler's hook constant for consistency
		$hook = SUPER_Trigger_Scheduler::HOOK_EXECUTE_DELAYED;

		if ( $unique ) {
			// Check if already scheduled with same arguments
			$existing = as_get_scheduled_actions(
				[
					'hook'   => $hook,
					'args'   => array( $hook_args ), // Wrapped for proper comparison
					'status' => 'pending',
					'group'  => SUPER_Trigger_Scheduler::GROUP,
				],
				'ids'
			);

			if ( ! empty( $existing ) ) {
				return [
					'success'            => false,
					'error'              => __( 'Identical delayed action already scheduled', 'super-forms' ),
					'existing_action_id' => $existing[0],
				];
			}
		}

		// Schedule the action - wrap $hook_args in array so it's passed as single argument
		$action_id = as_schedule_single_action(
			$scheduled_time,
			$hook,
			array( $hook_args ), // Important: wrap in array for proper arg passing
			SUPER_Trigger_Scheduler::GROUP
		);

		return [
			'success'        => true,
			'action_id'      => $action_id,
			'scheduled_time' => gmdate( 'Y-m-d H:i:s', $scheduled_time ),
			'delay_amount'   => $delay_amount,
			'delay_unit'     => $delay_unit,
			'actions_count'  => count( $actions_to_delay ),
			'unique'         => $unique,
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
		return SUPER_Trigger_Scheduler::is_available();
	}

	/**
	 * This action must run synchronously because it schedules other actions.
	 * The scheduled actions themselves run asynchronously.
	 *
	 * @return bool
	 */
	public function supports_async() {
		return false;
	}

	/**
	 * Get the preferred execution mode for this action.
	 *
	 * @return string
	 */
	public function get_execution_mode() {
		return 'sync'; // Must run sync to schedule the delayed actions
	}

	public function get_required_capabilities() {
		return ['edit_posts'];
	}
}
