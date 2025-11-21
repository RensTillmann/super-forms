<?php
/**
 * Update Post Meta Action
 *
 * Updates WordPress post meta fields
 * Useful for integrating form submissions with WordPress posts
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class SUPER_Action_Update_Post_Meta extends SUPER_Trigger_Action_Base {

	/**
	 * Get action ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'update_post_meta';
	}

	/**
	 * Get action label
	 *
	 * @return string
	 */
	public function get_label() {
		return __('Update Post Meta', 'super-forms');
	}

	/**
	 * Get action description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Update custom fields (meta data) for a WordPress post. Supports {tags} for dynamic values.', 'super-forms');
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
				'name' => 'post_id',
				'label' => __('Post ID', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => '123 or {post.ID} or {form_data.post_id}',
				'description' => __('ID of the post to update. Supports {tags}.', 'super-forms')
			],
			[
				'name' => 'meta_key',
				'label' => __('Meta Key', 'super-forms'),
				'type' => 'text',
				'required' => true,
				'placeholder' => 'my_custom_field',
				'description' => __('Meta field name to update', 'super-forms')
			],
			[
				'name' => 'meta_value',
				'label' => __('Meta Value', 'super-forms'),
				'type' => 'textarea',
				'required' => true,
				'placeholder' => '{form_data.field_name}',
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
					'append' => 'Append (add to end of existing value)',
					'prepend' => 'Prepend (add to start of existing value)',
					'add_if_not_exists' => 'Only add if meta key doesn\'t exist'
				],
				'description' => __('How to update the meta value', 'super-forms')
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
		// Get post ID
		$post_id = $config['post_id'] ?? '';
		$post_id = $this->replace_tags($post_id, $context);
		$post_id = absint($post_id);

		if (empty($post_id)) {
			return [
				'success' => false,
				'error' => 'Valid post ID is required'
			];
		}

		// Verify post exists
		$post = get_post($post_id);
		if (!$post) {
			return [
				'success' => false,
				'error' => 'Post not found: ' . $post_id
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
		$final_value = $this->get_final_value($post_id, $meta_key, $converted_value, $update_mode);

		// Update post meta
		$updated = update_post_meta($post_id, $meta_key, $final_value);

		return [
			'success' => true,
			'post_id' => $post_id,
			'post_title' => $post->post_title,
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
				// Value will be auto-serialized by update_post_meta if it's an array
				return maybe_unserialize($value);

			case 'string':
			default:
				return (string)$value;
		}
	}

	/**
	 * Get final value based on update mode
	 *
	 * @param int $post_id Post ID
	 * @param string $meta_key Meta key
	 * @param mixed $new_value New value
	 * @param string $mode Update mode
	 * @return mixed Final value
	 */
	private function get_final_value($post_id, $meta_key, $new_value, $mode) {
		switch ($mode) {
			case 'append':
				$existing = get_post_meta($post_id, $meta_key, true);
				return $existing . $new_value;

			case 'prepend':
				$existing = get_post_meta($post_id, $meta_key, true);
				return $new_value . $existing;

			case 'add_if_not_exists':
				$existing = get_post_meta($post_id, $meta_key, true);
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
		return ['edit_posts'];
	}
}
