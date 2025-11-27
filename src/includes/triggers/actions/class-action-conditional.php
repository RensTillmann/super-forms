<?php
/**
 * Conditional Action
 *
 * Executes child actions only if conditions are met
 * Allows nested conditional logic within triggers
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class SUPER_Action_Conditional extends SUPER_Trigger_Action_Base {

	public function get_id() {
		return 'conditional_action';
	}

	public function get_label() {
		return __('Conditional Action', 'super-forms');
	}

	public function get_description() {
		return __('Execute child actions only if specified conditions are met. Supports AND/OR logic.', 'super-forms');
	}

	public function get_category() {
		return 'flow_control';
	}

	public function get_settings_schema() {
		return [
			[
				'name' => 'conditions',
				'label' => __('Conditions', 'super-forms'),
				'type' => 'condition_builder',
				'required' => true,
				'description' => __('Conditions that must be met to execute child actions', 'super-forms')
			],
			[
				'name' => 'child_actions',
				'label' => __('Actions to Execute', 'super-forms'),
				'type' => 'action_list',
				'required' => true,
				'description' => __('Actions to run if conditions are met', 'super-forms')
			],
			[
				'name' => 'else_actions',
				'label' => __('Else Actions', 'super-forms'),
				'type' => 'action_list',
				'required' => false,
				'description' => __('Actions to run if conditions are NOT met', 'super-forms')
			]
		];
	}

	public function execute($context, $config) {
		$conditions = $config['conditions'] ?? [];
		$child_actions = $config['child_actions'] ?? [];
		$else_actions = $config['else_actions'] ?? [];

		// Evaluate conditions using the Conditions engine
		$conditions_met = false;
		if (class_exists('SUPER_Trigger_Conditions')) {
			$conditions_met = SUPER_Trigger_Conditions::evaluate($conditions, $context);
		} else {
			// Fallback: simple condition check
			$conditions_met = $this->evaluate_simple_conditions($conditions, $context);
		}

		// Choose which actions to execute
		$actions_to_execute = $conditions_met ? $child_actions : $else_actions;

		if (empty($actions_to_execute)) {
			return [
				'success' => true,
				'conditions_met' => $conditions_met,
				'actions_executed' => 0,
				'message' => $conditions_met ? 'Conditions met but no child actions defined' : 'Conditions not met and no else actions defined'
			];
		}

		// Execute child actions
		$results = [];
		$executed_count = 0;

		foreach ($actions_to_execute as $action_config) {
			$action_type = $action_config['action_type'] ?? '';
			if (empty($action_type)) {
				continue;
			}

			// Get action instance from registry
			$registry = SUPER_Trigger_Registry::get_instance();
			$action = $registry->get_action_instance($action_type);

			if (!$action) {
				$results[] = [
					'action_type' => $action_type,
					'success' => false,
					'error' => 'Action not found'
				];
				continue;
			}

			// Execute action
			$action_result = $action->execute($context, $action_config);
			$results[] = array_merge(['action_type' => $action_type], $action_result);
			$executed_count++;

			// Check if we should stop execution
			if (!empty($action_result['stop_execution'])) {
				break;
			}
		}

		return [
			'success' => true,
			'conditions_met' => $conditions_met,
			'actions_executed' => $executed_count,
			'child_results' => $results
		];
	}

	/**
	 * Simple fallback condition evaluation
	 */
	private function evaluate_simple_conditions($conditions, $context) {
		if (empty($conditions)) {
			return true;
		}

		// Support simple {field} = value format
		if (isset($conditions['field']) && isset($conditions['value'])) {
			$field_value = $this->replace_tags('{' . $conditions['field'] . '}', $context);
			$expected_value = $conditions['value'];
			$operator = $conditions['operator'] ?? '=';

			switch ($operator) {
				case '=':
				case '==':
					return $field_value == $expected_value;
				case '!=':
					return $field_value != $expected_value;
				case '>':
					return $field_value > $expected_value;
				case '<':
					return $field_value < $expected_value;
				case 'contains':
					return stripos($field_value, $expected_value) !== false;
				default:
					return false;
			}
		}

		return true;
	}

	public function can_run($context) {
		return true;
	}

	public function get_required_capabilities() {
		return ['edit_posts'];
	}

	/**
	 * This action cannot run asynchronously
	 *
	 * Conditional logic must execute synchronously to control child action flow.
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
