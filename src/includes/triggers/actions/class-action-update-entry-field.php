<?php
/**
 * Update Entry Field Action
 *
 * Updates a specific field in contact entry data using Data Access Layer
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_Update_Entry_Field extends SUPER_Trigger_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'update_entry_field';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('Update Entry Field', 'super-forms');
    }

    /**
     * Get action category
     *
     * @return string
     */
    public function get_category() {
        return 'entry_management';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function get_description() {
        return __('Update a specific field value in contact entry data', 'super-forms');
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
        return [
            [
                'name' => 'entry_id_source',
                'label' => __('Entry ID Source', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'context',
                'options' => [
                    'context' => __('From Event Context', 'super-forms'),
                    'custom' => __('Custom Entry ID', 'super-forms')
                ],
                'description' => __('Where to get the entry ID from', 'super-forms')
            ],
            [
                'name' => 'entry_id',
                'label' => __('Entry ID', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => '123 or {custom_entry_id}',
                'description' => __('Entry ID to update (when using custom source). Supports {tags}.', 'super-forms'),
                'show_if' => ['entry_id_source' => 'custom']
            ],
            [
                'name' => 'field_name',
                'label' => __('Field Name', 'super-forms'),
                'type' => 'text',
                'required' => true,
                'default' => '',
                'placeholder' => 'email or status',
                'description' => __('The field name to update (without brackets). Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'field_value',
                'label' => __('New Value', 'super-forms'),
                'type' => 'textarea',
                'required' => true,
                'default' => '',
                'placeholder' => 'New value or {form_data.field_name}',
                'description' => __('The new value to set. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'value_type',
                'label' => __('Value Type', 'super-forms'),
                'type' => 'select',
                'required' => false,
                'default' => 'string',
                'options' => [
                    'string' => __('Text/String', 'super-forms'),
                    'number' => __('Number', 'super-forms'),
                    'json' => __('JSON', 'super-forms'),
                    'serialize' => __('Serialized', 'super-forms')
                ],
                'description' => __('How to interpret the value', 'super-forms')
            ],
            [
                'name' => 'create_if_missing',
                'label' => __('Create If Missing', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Create the field if it doesn\'t exist in entry data', 'super-forms')
            ]
        ];
    }

    /**
     * Execute the action
     *
     * @param array $context Event context data
     * @param array $config Action configuration
     * @return array|WP_Error Result data or error
     */
    public function execute($context, $config) {
        // Get entry ID
        $entry_id = $this->get_entry_id($context, $config);

        if (!$entry_id) {
            return new WP_Error(
                'missing_entry_id',
                __('No entry ID found in context or configuration', 'super-forms')
            );
        }

        // Verify entry exists
        $entry = get_post($entry_id);
        if (!$entry || $entry->post_type !== 'super_contact_entry') {
            return new WP_Error(
                'invalid_entry',
                sprintf(__('Entry #%d not found or is not a contact entry', 'super-forms'), $entry_id)
            );
        }

        // Get field name and value with tag replacement
        $field_name = $this->replace_tags($config['field_name'], $context);
        $field_value = $this->replace_tags($config['field_value'], $context);

        // Process value based on type
        $field_value = $this->process_value($field_value, $config['value_type'] ?? 'string');

        // CRITICAL: Use Data Access Layer for all entry data operations
        // This ensures compatibility with EAV migration system
        $entry_data = SUPER_Data_Access::get_entry_data($entry_id);

        if (is_wp_error($entry_data)) {
            return $entry_data;
        }

        // Check if field exists
        $field_exists = isset($entry_data[$field_name]);

        if (!$field_exists && empty($config['create_if_missing'])) {
            return new WP_Error(
                'field_not_found',
                sprintf(__('Field "%s" not found in entry #%d', 'super-forms'), $field_name, $entry_id)
            );
        }

        // Store old value for logging
        $old_value = $field_exists ? $entry_data[$field_name]['value'] : null;

        // Update entry data using Data Access Layer
        // This automatically handles EAV vs serialized storage
        $update_result = SUPER_Data_Access::update_entry_field($entry_id, $field_name, $field_value);

        if (is_wp_error($update_result)) {
            return $update_result;
        }

        // Fire action hook for other integrations
        do_action('super_trigger_entry_field_updated', $entry_id, $field_name, $old_value, $field_value, $context);

        return [
            'entry_id' => $entry_id,
            'field_name' => $field_name,
            'old_value' => $old_value,
            'new_value' => $field_value,
            'field_existed' => $field_exists,
            'message' => sprintf(
                __('Updated field "%s" in entry #%d', 'super-forms'),
                $field_name,
                $entry_id
            )
        ];
    }

    /**
     * Process value based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function process_value($value, $type) {
        switch ($type) {
            case 'number':
                return is_numeric($value) ? floatval($value) : 0;

            case 'json':
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;

            case 'serialize':
                return maybe_unserialize($value);

            case 'string':
            default:
                return strval($value);
        }
    }

    /**
     * Get entry ID from context or config
     *
     * @param array $context
     * @param array $config
     * @return int|null
     */
    protected function get_entry_id($context, $config) {
        $source = $config['entry_id_source'] ?? 'context';

        if ($source === 'context') {
            return isset($context['entry_id']) ? absint($context['entry_id']) : null;
        }

        if ($source === 'custom' && !empty($config['entry_id'])) {
            $entry_id = $this->replace_tags($config['entry_id'], $context);
            return absint($entry_id);
        }

        return null;
    }

    /**
     * Check if action can run
     *
     * @param array $context
     * @return bool
     */
    public function can_run($context) {
        // Check if SUPER_Data_Access class exists
        if (!class_exists('SUPER_Data_Access')) {
            return false;
        }

        // Needs entry_id in context OR custom entry_id configured
        return isset($context['entry_id']) || !empty($context['config']['entry_id']);
    }
}
