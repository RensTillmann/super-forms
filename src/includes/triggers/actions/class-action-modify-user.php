<?php
/**
 * Modify User Action
 *
 * Modifies WordPress user data (email, role, display name, etc.)
 * Different from update_user_meta which updates custom fields
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Modify_User extends SUPER_Trigger_Action_Base {

	public function get_id() {
		return 'modify_user';
	}

	public function get_label() {
		return __('Modify User', 'super-forms');
	}

	public function get_description() {
		return __('Modify WordPress user data like email, role, or display name.', 'super-forms');
	}

	public function get_category() {
		return 'user_management';
	}

	public function get_settings_schema() {
		return [
			[
				'name' => 'user_id',
				'label' => __('User ID', 'super-forms'),
				'type' => 'text',
				'placeholder' => '{user_id} or leave empty for current user',
				'description' => __('User to modify. Empty = current user', 'super-forms')
			],
			[
				'name' => 'field_to_modify',
				'label' => __('Field to Modify', 'super-forms'),
				'type' => 'select',
				'required' => true,
				'options' => [
					'user_email' => 'Email Address',
					'display_name' => 'Display Name',
					'first_name' => 'First Name',
					'last_name' => 'Last Name',
					'role' => 'User Role',
					'user_pass' => 'Password',
					'description' => 'Biographical Info'
				]
			],
			[
				'name' => 'field_value',
				'label' => __('New Value', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => '{form_data.email}',
				'description' => __('New value. Supports {tags}.', 'super-forms')
			]
		];
	}

	public function execute($context, $config) {
		$user_id = $config['user_id'] ?? '';
		$user_id = empty($user_id) ? get_current_user_id() : absint($this->replace_tags($user_id, $context));

		if (!$user_id) {
			return ['success' => false, 'error' => 'User ID required or must be logged in'];
		}

		if (!current_user_can('edit_users') && $user_id !== get_current_user_id()) {
			return ['success' => false, 'error' => 'Permission denied'];
		}

		$field = $config['field_to_modify'] ?? '';
		$value = $this->replace_tags($config['field_value'] ?? '', $context);

		$user_data = ['ID' => $user_id];

		switch ($field) {
			case 'user_email':
				if (!is_email($value)) {
					return ['success' => false, 'error' => 'Invalid email'];
				}
				$user_data['user_email'] = $value;
				break;
			case 'display_name':
			case 'first_name':
			case 'last_name':
			case 'description':
				$user_data[$field] = sanitize_text_field($value);
				break;
			case 'user_pass':
				$user_data['user_pass'] = $value;
				break;
			case 'role':
				$user = get_userdata($user_id);
				$user->set_role($value);
				return ['success' => true, 'user_id' => $user_id, 'role' => $value];
			default:
				return ['success' => false, 'error' => 'Invalid field'];
		}

		$result = wp_update_user($user_data);

		if (is_wp_error($result)) {
			return ['success' => false, 'error' => $result->get_error_message()];
		}

		return [
			'success' => true,
			'user_id' => $user_id,
			'field_modified' => $field,
			'new_value' => $value
		];
	}

	public function can_run($context) {
		return true;
	}

	public function get_required_capabilities() {
		return ['edit_posts'];
	}
}
