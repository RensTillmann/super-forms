<?php
/**
 * Abort Submission Action
 *
 * Stops form submission flow (prevents entry creation)
 * Critical for spam/duplicate detection that happens BEFORE entry is saved
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_Abort_Submission extends SUPER_Trigger_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'flow.abort_submission';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('Abort Submission', 'super-forms');
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
     * Get action description
     *
     * @return string
     */
    public function get_description() {
        return __('Stop form submission and prevent entry creation (for spam/duplicate detection)', 'super-forms');
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
        return [
            [
                'name' => 'abort_reason',
                'label' => __('Abort Reason', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'spam_detected',
                'options' => [
                    'spam_detected' => __('Spam Detected', 'super-forms'),
                    'duplicate_detected' => __('Duplicate Detected', 'super-forms'),
                    'validation_failed' => __('Validation Failed', 'super-forms'),
                    'rate_limit_exceeded' => __('Rate Limit Exceeded', 'super-forms'),
                    'custom' => __('Custom Reason', 'super-forms')
                ],
                'description' => __('Why the submission is being aborted', 'super-forms')
            ],
            [
                'name' => 'custom_reason',
                'label' => __('Custom Reason', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'Blocked: {reason}',
                'description' => __('Custom abort reason. Supports {tags}.', 'super-forms'),
                'show_if' => ['abort_reason' => 'custom']
            ],
            [
                'name' => 'show_error_message',
                'label' => __('Show Error Message', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Display error message to user', 'super-forms')
            ],
            [
                'name' => 'error_message',
                'label' => __('Error Message', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => 'Your submission could not be processed.',
                'description' => __('Message shown to user. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'cleanup_files',
                'label' => __('Cleanup Uploaded Files', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Delete uploaded files when aborting', 'super-forms')
            ],
            [
                'name' => 'log_abort',
                'label' => __('Log Abort Event', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Log this abort to trigger logs', 'super-forms')
            ],
            [
                'name' => 'send_notification',
                'label' => __('Send Admin Notification', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => false,
                'description' => __('Email admin when submission is aborted', 'super-forms')
            ],
            [
                'name' => 'notification_email',
                'label' => __('Notification Email', 'super-forms'),
                'type' => 'email',
                'required' => false,
                'default' => '',
                'placeholder' => 'admin@example.com',
                'description' => __('Admin email for notifications', 'super-forms'),
                'show_if' => ['send_notification' => true]
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
        // Get abort reason
        $abort_reason = $config['abort_reason'] ?? 'spam_detected';
        if ($abort_reason === 'custom' && !empty($config['custom_reason'])) {
            $abort_reason = $this->replace_tags($config['custom_reason'], $context);
        }

        // Cleanup uploaded files if requested
        if (!empty($config['cleanup_files'])) {
            $this->cleanup_uploaded_files($context);
        }

        // Log abort if requested
        if (!empty($config['log_abort'])) {
            $this->log_abort_event($context, $config, $abort_reason);
        }

        // Send admin notification if requested
        if (!empty($config['send_notification'])) {
            $this->send_admin_notification($context, $config, $abort_reason);
        }

        // Fire action hook
        do_action('super_trigger_submission_aborted', $abort_reason, $context, $config);

        // Build error message for user
        $error_message = '';
        if (!empty($config['show_error_message'])) {
            $error_message = !empty($config['error_message'])
                ? $this->replace_tags($config['error_message'], $context)
                : __('Your submission could not be processed.', 'super-forms');
        }

        // Return special abort signal
        // The executor will recognize this and halt further processing
        return new WP_Error(
            'submission_aborted',
            $error_message,
            [
                'abort_reason' => $abort_reason,
                'show_message' => !empty($config['show_error_message']),
                'is_abort_action' => true
            ]
        );
    }

    /**
     * Cleanup uploaded files
     *
     * @param array $context
     * @return void
     */
    protected function cleanup_uploaded_files($context) {
        if (empty($context['form_data'])) {
            return;
        }

        $upload_dir = wp_upload_dir();

        foreach ($context['form_data'] as $key => $value) {
            // Look for file fields
            if (!is_string($value)) {
                continue;
            }

            // Check if it's a file URL or path
            $is_file = false;
            if (strpos($value, 'http') === 0) {
                $is_file = true;
                $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $value);
            } elseif (file_exists($value)) {
                $is_file = true;
                $file_path = $value;
            }

            // Delete file if it exists
            if ($is_file && file_exists($file_path)) {
                @unlink($file_path);
            }
        }
    }

    /**
     * Log abort event to database
     *
     * @param array $context
     * @param array $config
     * @param string $reason
     * @return void
     */
    protected function log_abort_event($context, $config, $reason) {
        global $wpdb;

        $table = $wpdb->prefix . 'superforms_trigger_logs';

        $log_data = [
            'trigger_id' => $context['trigger_id'] ?? 0,
            'entry_id' => null, // No entry created
            'form_id' => $context['form_id'] ?? null,
            'event_id' => $context['event_id'] ?? '',
            'status' => 'aborted',
            'error_message' => $reason,
            'context_data' => json_encode([
                'abort_reason' => $reason,
                'form_data' => $context['form_data'] ?? [],
                'user_ip' => SUPER_Common::real_ip()
            ]),
            'user_id' => get_current_user_id(),
            'executed_at' => current_time('mysql')
        ];

        $wpdb->insert($table, $log_data);
    }

    /**
     * Send admin notification
     *
     * @param array $context
     * @param array $config
     * @param string $reason
     * @return void
     */
    protected function send_admin_notification($context, $config, $reason) {
        $to = !empty($config['notification_email'])
            ? $config['notification_email']
            : get_option('admin_email');

        if (!is_email($to)) {
            return;
        }

        $form_id = $context['form_id'] ?? 0;
        $form_title = get_the_title($form_id);

        $subject = sprintf(
            __('[%s] Submission Aborted: %s', 'super-forms'),
            get_bloginfo('name'),
            $form_title
        );

        $message = sprintf(
            __("A form submission was aborted.\n\nForm: %s (#%d)\nReason: %s\nTime: %s\nIP: %s\n\nForm Data:\n%s", 'super-forms'),
            $form_title,
            $form_id,
            $reason,
            current_time('mysql'),
            SUPER_Common::real_ip(),
            json_encode($context['form_data'] ?? [], JSON_PRETTY_PRINT)
        );

        wp_mail($to, $subject, $message);
    }

    /**
     * Check if action can run
     *
     * @param array $context
     * @return bool
     */
    public function can_run($context) {
        // Can only abort BEFORE entry is created
        // If entry_id exists, submission is already saved
        return empty($context['entry_id']);
    }
}
