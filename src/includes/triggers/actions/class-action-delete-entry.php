<?php
/**
 * Delete Entry Action
 *
 * Deletes a contact entry (move to trash or permanent delete)
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_Delete_Entry extends SUPER_Trigger_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'delete_entry';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('Delete Entry', 'super-forms');
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
        return __('Delete or trash a contact entry', 'super-forms');
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
                'description' => __('Entry ID to delete (when using custom source). Supports {tags}.', 'super-forms'),
                'show_if' => ['entry_id_source' => 'custom']
            ],
            [
                'name' => 'delete_type',
                'label' => __('Delete Type', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'trash',
                'options' => [
                    'trash' => __('Move to Trash', 'super-forms'),
                    'permanent' => __('Permanent Delete', 'super-forms')
                ],
                'description' => __('How to delete the entry', 'super-forms')
            ],
            [
                'name' => 'delete_attachments',
                'label' => __('Delete Attachments', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => false,
                'description' => __('Also delete uploaded files associated with this entry', 'super-forms')
            ],
            [
                'name' => 'confirm_deletion',
                'label' => __('Require Confirmation', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Safety check: require explicit confirmation', 'super-forms')
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
        // Safety check
        if (!empty($config['confirm_deletion']) && empty($context['deletion_confirmed'])) {
            return new WP_Error(
                'deletion_not_confirmed',
                __('Deletion requires explicit confirmation', 'super-forms')
            );
        }

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

        // Get entry title for logging
        $entry_title = $entry->post_title;
        $delete_type = $config['delete_type'] ?? 'trash';

        // Delete attachments if requested
        if (!empty($config['delete_attachments'])) {
            $this->delete_entry_attachments($entry_id);
        }

        // Delete entry data using Data Access Layer
        if (class_exists('SUPER_Data_Access')) {
            SUPER_Data_Access::delete_entry_data($entry_id);
        }

        // Delete or trash the entry post
        if ($delete_type === 'permanent') {
            $result = wp_delete_post($entry_id, true);
        } else {
            $result = wp_trash_post($entry_id);
        }

        if (!$result) {
            return new WP_Error(
                'delete_failed',
                sprintf(__('Failed to delete entry #%d', 'super-forms'), $entry_id)
            );
        }

        // Fire action hook
        do_action('super_trigger_entry_deleted', $entry_id, $delete_type, $context);

        return [
            'entry_id' => $entry_id,
            'entry_title' => $entry_title,
            'delete_type' => $delete_type,
            'message' => sprintf(
                __('Entry #%d (%s) has been %s', 'super-forms'),
                $entry_id,
                $entry_title,
                $delete_type === 'permanent' ? 'permanently deleted' : 'moved to trash'
            )
        ];
    }

    /**
     * Delete uploaded files associated with entry
     *
     * @param int $entry_id
     * @return void
     */
    protected function delete_entry_attachments($entry_id) {
        // Get entry data to find file uploads
        if (!class_exists('SUPER_Data_Access')) {
            return;
        }

        $entry_data = SUPER_Data_Access::get_entry_data($entry_id);
        if (is_wp_error($entry_data) || empty($entry_data)) {
            return;
        }

        $upload_dir = wp_upload_dir();

        foreach ($entry_data as $field) {
            if (!isset($field['type']) || $field['type'] !== 'files') {
                continue;
            }

            $file_value = $field['value'] ?? '';
            if (empty($file_value)) {
                continue;
            }

            // Handle multiple files
            $files = is_array($file_value) ? $file_value : explode(',', $file_value);

            foreach ($files as $file) {
                // Convert URL to path if needed
                if (strpos($file, 'http') === 0) {
                    $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file);
                } else {
                    $file_path = $file;
                }

                // Delete file if it exists
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
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
        return isset($context['entry_id']) || !empty($context['config']['entry_id']);
    }

    /**
     * Get required capabilities
     *
     * @return array
     */
    public function get_required_capabilities() {
        return ['manage_options', 'delete_posts'];
    }
}
