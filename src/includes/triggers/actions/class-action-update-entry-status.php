<?php
/**
 * Update Entry Status Action
 *
 * Changes the status of a contact entry post
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_Update_Entry_Status extends SUPER_Trigger_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'update_entry_status';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('Update Entry Status', 'super-forms');
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
        return __('Change the status of a contact entry', 'super-forms');
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
                'name' => 'new_status',
                'label' => __('New Status', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'publish',
                'options' => [
                    'publish' => __('Approved', 'super-forms'),
                    'pending' => __('Pending', 'super-forms'),
                    'draft' => __('Draft', 'super-forms'),
                    'trash' => __('Trash', 'super-forms')
                ],
                'description' => __('The status to set on the entry', 'super-forms')
            ],
            [
                'name' => 'update_modified_date',
                'label' => __('Update Modified Date', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Update the modified date when changing status', 'super-forms')
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

        // Verify entry exists via Entry DAL
        // @since 6.5.0 - Use Entry DAL for dual storage mode support
        $entry = SUPER_Entry_DAL::get($entry_id);

        if (is_wp_error($entry)) {
            return new WP_Error(
                'invalid_entry',
                sprintf(__('Entry #%d not found or is not a contact entry', 'super-forms'), $entry_id)
            );
        }

        // Get new status
        $new_status = $config['new_status'];
        $old_status = $entry->wp_status;

        // Update entry status via Entry DAL
        // @since 6.5.0 - Use Entry DAL for dual storage mode support
        $result = SUPER_Entry_DAL::update($entry_id, array('wp_status' => $new_status));

        if (is_wp_error($result)) {
            return $result;
        }

        // Fire action hook for other integrations
        do_action('super_trigger_entry_status_updated', $entry_id, $old_status, $new_status, $context);

        return [
            'entry_id' => $entry_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'message' => sprintf(
                __('Entry #%d status changed from %s to %s', 'super-forms'),
                $entry_id,
                $old_status,
                $new_status
            )
        ];
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
        // Needs entry_id in context OR custom entry_id configured
        return isset($context['entry_id']) || !empty($context['config']['entry_id']);
    }
}
