<?php
/**
 * Increment Counter Action
 *
 * Increments a numeric counter stored in WordPress options/meta
 * Useful for tracking submission counts, limits, etc.
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class SUPER_Action_Increment_Counter extends SUPER_Action_Base {

	public function get_id() {
		return 'increment_counter';
	}

	public function get_label() {
		return __('Increment Counter', 'super-forms');
	}

	public function get_description() {
		return __('Increment a numeric counter. Useful for tracking submission counts or quotas.', 'super-forms');
	}

	public function get_category() {
		return 'data_management';
	}

	public function get_settings_schema() {
		return [
			[
				'name' => 'counter_name',
				'label' => __('Counter Name', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => 'submission_count',
				'description' => __('Unique name for this counter', 'super-forms')
			],
			[
				'name' => 'increment_by',
				'label' => __('Increment By', 'super-forms'),
				'type' => 'number',
				'default' => 1,
				'description' => __('Amount to increment (can be negative for decrement)', 'super-forms')
			],
			[
				'name' => 'storage_type',
				'label' => __('Storage Type', 'super-forms'),
				'type' => 'select',
				'default' => 'option',
				'options' => [
					'option' => 'WordPress Option (global)',
					'entry_meta' => 'Entry Meta',
					'user_meta' => 'User Meta',
					'form_meta' => 'Form Meta'
				]
			],
			[
				'name' => 'initial_value',
				'label' => __('Initial Value', 'super-forms'),
				'type' => 'number',
				'default' => 0,
				'description' => __('Starting value if counter doesn\'t exist', 'super-forms')
			]
		];
	}

	public function execute($context, $config) {
		$counter_name = sanitize_key($config['counter_name'] ?? '');
		if (empty($counter_name)) {
			return ['success' => false, 'error' => 'Counter name required'];
		}

		$increment_by = floatval($config['increment_by'] ?? 1);
		$storage_type = $config['storage_type'] ?? 'option';
		$initial_value = floatval($config['initial_value'] ?? 0);

		$key = '_super_counter_' . $counter_name;

		// Get current value
		$current_value = $this->get_counter_value($key, $storage_type, $context, $initial_value);

		// Increment
		$new_value = $current_value + $increment_by;

		// Store new value
		$this->set_counter_value($key, $new_value, $storage_type, $context);

		return [
			'success' => true,
			'counter_name' => $counter_name,
			'previous_value' => $current_value,
			'new_value' => $new_value,
			'increment_by' => $increment_by,
			'storage_type' => $storage_type
		];
	}

	private function get_counter_value($key, $storage_type, $context, $default) {
		switch ($storage_type) {
			case 'entry_meta':
				return floatval(get_post_meta($context['entry_id'] ?? 0, $key, true)) ?: $default;
			case 'user_meta':
				$user_id = $context['user_id'] ?? get_current_user_id();
				return floatval(get_user_meta($user_id, $key, true)) ?: $default;
			case 'form_meta':
				return floatval(get_post_meta($context['form_id'] ?? 0, $key, true)) ?: $default;
			default:
				return floatval(get_option($key, $default));
		}
	}

	private function set_counter_value($key, $value, $storage_type, $context) {
		switch ($storage_type) {
			case 'entry_meta':
				update_post_meta($context['entry_id'] ?? 0, $key, $value);
				break;
			case 'user_meta':
				$user_id = $context['user_id'] ?? get_current_user_id();
				update_user_meta($user_id, $key, $value);
				break;
			case 'form_meta':
				update_post_meta($context['form_id'] ?? 0, $key, $value);
				break;
			default:
				update_option($key, $value);
		}
	}

	public function can_run($context) {
		return true;
	}

	public function get_required_capabilities() {
		return ['edit_posts'];
	}
}
