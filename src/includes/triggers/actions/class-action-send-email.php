<?php
/**
 * Send Email Action
 *
 * Sends email with {tag} replacement and attachment support
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_Send_Email extends SUPER_Trigger_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'send_email';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('Send Email', 'super-forms');
    }

    /**
     * Get action category
     *
     * @return string
     */
    public function get_category() {
        return 'notification';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function get_description() {
        return __('Send an email notification with form data', 'super-forms');
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
        return [
            [
                'name' => 'to',
                'label' => __('To', 'super-forms'),
                'type' => 'text',
                'required' => true,
                'default' => '',
                'placeholder' => 'email@example.com or {form_data.email}',
                'description' => __('Email address(es). Separate multiple with commas. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'cc',
                'label' => __('CC', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'cc@example.com',
                'description' => __('Carbon copy recipients. Separate multiple with commas.', 'super-forms')
            ],
            [
                'name' => 'bcc',
                'label' => __('BCC', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'bcc@example.com',
                'description' => __('Blind carbon copy recipients. Separate multiple with commas.', 'super-forms')
            ],
            [
                'name' => 'from',
                'label' => __('From', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'noreply@example.com',
                'description' => __('From email address. Leave empty to use WordPress default.', 'super-forms')
            ],
            [
                'name' => 'from_name',
                'label' => __('From Name', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'My Website',
                'description' => __('From name. Leave empty to use WordPress default.', 'super-forms')
            ],
            [
                'name' => 'reply_to',
                'label' => __('Reply To', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => '{form_data.email}',
                'description' => __('Reply-to email address. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'subject',
                'label' => __('Subject', 'super-forms'),
                'type' => 'text',
                'required' => true,
                'default' => '',
                'placeholder' => 'New form submission',
                'description' => __('Email subject line. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'body',
                'label' => __('Body', 'super-forms'),
                'type' => 'wysiwyg',
                'required' => true,
                'default' => '',
                'description' => __('Email body content. Supports HTML and {tags}.', 'super-forms')
            ],
            [
                'name' => 'body_open',
                'label' => __('Body Opening', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'description' => __('Optional HTML before body content. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'body_close',
                'label' => __('Body Closing', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'description' => __('Optional HTML after body content. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'attachments',
                'label' => __('Attachments', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'description' => __('Comma-separated file paths or field names containing uploaded files. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'include_entry_data',
                'label' => __('Include Entry Data', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => false,
                'description' => __('Append all form fields to email body', 'super-forms')
            ],
            [
                'name' => 'exclude_empty_fields',
                'label' => __('Exclude Empty Fields', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('When including entry data, skip empty fields', 'super-forms')
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
        // Replace tags in all email fields
        $to = $this->replace_tags($config['to'], $context);
        $subject = $this->replace_tags($config['subject'], $context);
        $body = $this->replace_tags($config['body'], $context);

        // Validate email addresses
        $to_emails = array_map('trim', explode(',', $to));
        $valid_emails = array_filter($to_emails, 'is_email');

        if (empty($valid_emails)) {
            return new WP_Error(
                'invalid_email',
                __('No valid email addresses found in "To" field', 'super-forms')
            );
        }

        // Build headers
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // From header
        if (!empty($config['from'])) {
            $from = $this->replace_tags($config['from'], $context);
            $from_name = !empty($config['from_name'])
                ? $this->replace_tags($config['from_name'], $context)
                : '';

            if (is_email($from)) {
                $headers[] = $from_name
                    ? "From: {$from_name} <{$from}>"
                    : "From: {$from}";
            }
        }

        // Reply-To header
        if (!empty($config['reply_to'])) {
            $reply_to = $this->replace_tags($config['reply_to'], $context);
            if (is_email($reply_to)) {
                $headers[] = "Reply-To: {$reply_to}";
            }
        }

        // CC header
        if (!empty($config['cc'])) {
            $cc = $this->replace_tags($config['cc'], $context);
            $cc_emails = array_map('trim', explode(',', $cc));
            $valid_cc = array_filter($cc_emails, 'is_email');
            if (!empty($valid_cc)) {
                $headers[] = 'Cc: ' . implode(', ', $valid_cc);
            }
        }

        // BCC header
        if (!empty($config['bcc'])) {
            $bcc = $this->replace_tags($config['bcc'], $context);
            $bcc_emails = array_map('trim', explode(',', $bcc));
            $valid_bcc = array_filter($bcc_emails, 'is_email');
            if (!empty($valid_bcc)) {
                $headers[] = 'Bcc: ' . implode(', ', $valid_bcc);
            }
        }

        // Build full email body
        $full_body = '';

        if (!empty($config['body_open'])) {
            $full_body .= $this->replace_tags($config['body_open'], $context);
        }

        $full_body .= $body;

        // Include entry data if requested
        if (!empty($config['include_entry_data'])) {
            $full_body .= $this->format_entry_data($context, $config);
        }

        if (!empty($config['body_close'])) {
            $full_body .= $this->replace_tags($config['body_close'], $context);
        }

        // Handle attachments
        $attachments = [];
        if (!empty($config['attachments'])) {
            $attachments = $this->process_attachments($config['attachments'], $context);
        }

        // Allow filtering before sending
        $full_body = apply_filters('super_trigger_email_body', $full_body, $context, $config);
        $subject = apply_filters('super_trigger_email_subject', $subject, $context, $config);
        $headers = apply_filters('super_trigger_email_headers', $headers, $context, $config);
        $attachments = apply_filters('super_trigger_email_attachments', $attachments, $context, $config);

        // Send email
        $sent = wp_mail($valid_emails, $subject, $full_body, $headers, $attachments);

        if (!$sent) {
            return new WP_Error(
                'email_send_failed',
                __('Failed to send email', 'super-forms')
            );
        }

        return [
            'recipients' => $valid_emails,
            'subject' => $subject,
            'message' => __('Email sent successfully', 'super-forms')
        ];
    }

    /**
     * Format entry data for email body
     *
     * @param array $context
     * @param array $config
     * @return string
     */
    protected function format_entry_data($context, $config) {
        $html = '<br><br><hr><h3>' . __('Form Data', 'super-forms') . '</h3>';
        $html .= '<table style="width:100%; border-collapse:collapse;">';

        $entry_data = isset($context['form_data']) ? $context['form_data'] : [];
        $exclude_empty = !empty($config['exclude_empty_fields']);

        foreach ($entry_data as $key => $value) {
            // Skip empty fields if requested
            if ($exclude_empty && empty($value)) {
                continue;
            }

            // Skip system fields
            if (strpos($key, '_super_') === 0) {
                continue;
            }

            // Format value
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $label = ucwords(str_replace('_', ' ', $key));
            $html .= '<tr>';
            $html .= '<td style="padding:8px; border:1px solid #ddd; font-weight:bold; width:30%;">' . esc_html($label) . '</td>';
            $html .= '<td style="padding:8px; border:1px solid #ddd;">' . nl2br(esc_html($value)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Process attachments from config
     *
     * @param string $attachments_config
     * @param array $context
     * @return array
     */
    protected function process_attachments($attachments_config, $context) {
        $attachments = [];
        $attachment_items = array_map('trim', explode(',', $attachments_config));

        foreach ($attachment_items as $item) {
            // Replace tags
            $item = $this->replace_tags($item, $context);

            // Check if it's a file path
            if (file_exists($item)) {
                $attachments[] = $item;
                continue;
            }

            // Check if it's a field name with uploaded file
            if (isset($context['form_data'][$item])) {
                $file_value = $context['form_data'][$item];

                // Could be URL or path
                if (is_string($file_value)) {
                    // Convert URL to path if needed
                    if (strpos($file_value, 'http') === 0) {
                        $upload_dir = wp_upload_dir();
                        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_value);
                        if (file_exists($file_path)) {
                            $attachments[] = $file_path;
                        }
                    } elseif (file_exists($file_value)) {
                        $attachments[] = $file_value;
                    }
                }
            }
        }

        return $attachments;
    }

    /**
     * Check if action can run
     *
     * @param array $context
     * @return bool
     */
    public function can_run($context) {
        // Email action can always run
        return true;
    }
}
