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

class SUPER_Action_Send_Email extends SUPER_Action_Base {

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
        // Validate critical variables
        $validation = $this->validate_context(['to'], $context);
        if (!$validation['valid']) {
            return new WP_Error('missing_recipient', 'Missing required recipient email');
        }

        // Email-safe sanitization
        $to = $this->replace_variables($config['to'], $context, [
            'sanitize' => 'email',
            'missing_behavior' => 'error'
        ]);

        $subject = $this->replace_variables($config['subject'], $context, [
            'sanitize' => 'text'  // No HTML in subject
        ]);

        // Handle different body types
        $body_type = $config['body_type'] ?? 'html';
        $body = $this->render_body($config['body'], $body_type, $context, $config);

        // Validate email addresses
        $to_emails = array_map('trim', explode(',', $to));
        $valid_emails = array_filter($to_emails, 'is_email');

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
        $full_body = apply_filters('super_automation_email_body', $full_body, $context, $config);
        $subject = apply_filters('super_automation_email_subject', $subject, $context, $config);
        $headers = apply_filters('super_automation_email_headers', $headers, $context, $config);
        $attachments = apply_filters('super_automation_email_attachments', $attachments, $context, $config);

        // Send email
        $sent = wp_mail($valid_emails, $subject, $full_body, $headers, $attachments);

        if (!$sent) {
            return new WP_Error(
                'email_send_failed',
                __('Failed to send email', 'super-forms')
            );
        }

        return [
            'success' => true,
            'to' => implode(', ', $valid_emails),
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
     * @param string|array $attachments_config
     * @param array $context
     * @return array
     */
    protected function process_attachments($attachments_config, $context) {
        $attachments = [];

        // Handle array input (e.g., from tests or direct configuration)
        if (is_array($attachments_config)) {
            $attachment_items = $attachments_config;
        } else {
            $attachment_items = array_map('trim', explode(',', $attachments_config));
        }

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

    /**
     * Email supports and prefers asynchronous execution
     *
     * Email sending can be slow and shouldn't block form submission.
     *
     * @return bool
     * @since 6.5.0
     */
    public function supports_async() {
        return true;
    }

    /**
     * Get execution mode
     *
     * @return string
     * @since 6.5.0
     */
    public function get_execution_mode() {
        return 'async'; // Prefer async to not block form submission
    }

    /**
     * Get retry configuration for email
     *
     * @return array
     * @since 6.5.0
     */
    public function get_retry_config() {
        return [
            'max_retries'   => 3,
            'initial_delay' => 300,    // 5 minutes (allow SMTP recovery)
            'exponential'   => true,
            'max_delay'     => 3600,   // 1 hour max
        ];
    }

    /**
     * Render email body based on body_type
     *
     * Supports multiple body formats:
     * - html: Raw HTML with tag replacement
     * - email_v2: Email v2 builder JSON format
     * - legacy_html: Legacy format from old email settings
     *
     * @param mixed  $body      Body content (string or array)
     * @param string $body_type Body type identifier
     * @param array  $context   Event context
     * @return string Rendered HTML body
     * @since 6.5.0
     */
    protected function render_body($body, $body_type, $context, $config = []) {
        switch ($body_type) {
            case 'email_v2':
                return $this->render_email_v2($body, $context);

            case 'legacy_html':
                // Legacy format with {loop_fields} support
                return $this->render_legacy_html($body, $context, $config);

            case 'html':
            default:
                // Standard HTML with tag replacement
                return $this->replace_tags($body, $context);
        }
    }

    /**
     * Render legacy HTML email body
     *
     * Handles {loop_fields} tag replacement using the configured loop template.
     *
     * @param string $body    Email body content
     * @param array  $context Event context
     * @param array  $config  Action configuration (contains loop settings)
     * @return string Rendered HTML
     * @since 6.5.0
     */
    protected function render_legacy_html($body, $context, $config) {
        // First handle {loop_fields} tag if present
        if (strpos($body, '{loop_fields}') !== false) {
            $loop_html = $this->render_loop_fields($context, $config);
            $body = str_replace('{loop_fields}', $loop_html, $body);
        }

        // Apply RTL wrapping if enabled
        if (!empty($config['rtl'])) {
            $body = '<div dir="rtl" style="text-align:right;">' . $body . '</div>';
        }

        // Do standard tag replacement
        return $this->replace_tags($body, $context);
    }

    /**
     * Render {loop_fields} content for legacy emails
     *
     * @param array $context Event context with form_data
     * @param array $config  Action configuration with loop settings
     * @return string Loop HTML
     * @since 6.5.0
     */
    protected function render_loop_fields($context, $config) {
        $form_data = $context['form_data'] ?? [];

        if (empty($form_data)) {
            return '';
        }

        // Get loop configuration
        $loop_open  = $config['loop_open'] ?? '<table cellpadding="5">';
        $loop       = $config['loop'] ?? '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>';
        $loop_close = $config['loop_close'] ?? '</table>';
        $exclude_empty = !empty($config['exclude_empty']);

        $html = $loop_open;

        foreach ($form_data as $field_name => $field_data) {
            // Skip system fields
            if (strpos($field_name, '_super_') === 0) {
                continue;
            }

            // Get label and value
            $label = $field_name;
            $value = '';

            if (is_array($field_data)) {
                // Structured field data from entry
                $label = $field_data['label'] ?? ucwords(str_replace('_', ' ', $field_name));
                $value = $field_data['value'] ?? '';

                // Handle nested arrays (checkboxes, multi-select)
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
            } else {
                // Simple key-value
                $label = ucwords(str_replace('_', ' ', $field_name));
                $value = $field_data;
            }

            // Skip empty values if configured
            if ($exclude_empty && empty($value) && $value !== '0') {
                continue;
            }

            // Build row from loop template
            $row = str_replace(
                ['{loop_label}', '{loop_value}'],
                [esc_html($label), nl2br(esc_html($value))],
                $loop
            );

            $html .= $row;
        }

        $html .= $loop_close;

        return $html;
    }

    /**
     * Render Email v2 JSON template to HTML
     *
     * Email v2 stores email content as structured JSON with elements.
     * This method converts that JSON to final HTML for sending.
     *
     * @param mixed $body    Body content (JSON string or array)
     * @param array $context Event context
     * @return string Rendered HTML
     * @since 6.5.0
     */
    protected function render_email_v2($body, $context) {
        // Parse JSON if string
        if (is_string($body)) {
            // Check if it's actually HTML (backwards compat)
            if (strpos($body, '<') !== false && strpos($body, '{') !== 0) {
                return $this->replace_tags($body, $context);
            }

            $body = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Not valid JSON, treat as HTML
                return $this->replace_tags($body, $context);
            }
        }

        if (!is_array($body)) {
            return '';
        }

        // Check if it's the simple body format (just HTML content stored in body key)
        if (isset($body['body']) && is_string($body['body'])) {
            return $this->replace_tags($body['body'], $context);
        }

        // Build HTML from Email v2 structure
        $html = $this->build_email_v2_html($body, $context);

        // Apply tag replacement to final HTML
        return $this->replace_tags($html, $context);
    }

    /**
     * Build HTML from Email v2 element structure
     *
     * @param array $template Email v2 template data
     * @param array $context  Event context
     * @return string HTML output
     * @since 6.5.0
     */
    protected function build_email_v2_html($template, $context) {
        $html = '';

        // Get global settings
        $settings = $template['settings'] ?? [];
        $bgColor = $settings['backgroundColor'] ?? '#ffffff';
        $fontFamily = $settings['fontFamily'] ?? 'Arial, sans-serif';

        // Start wrapper
        $html .= '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '</head><body style="margin:0;padding:0;background-color:#f4f4f4;">';
        $html .= '<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;">';
        $html .= '<tr><td align="center" style="padding:20px;">';
        $html .= '<table width="600" cellpadding="0" cellspacing="0" style="background-color:' . esc_attr($bgColor) . ';font-family:' . esc_attr($fontFamily) . ';">';

        // Render elements
        if (!empty($template['elements']) && is_array($template['elements'])) {
            foreach ($template['elements'] as $element) {
                $html .= $this->render_email_v2_element($element, $context);
            }
        }

        // Close wrapper
        $html .= '</table></td></tr></table></body></html>';

        return $html;
    }

    /**
     * Render single Email v2 element
     *
     * @param array $element Element data
     * @param array $context Event context
     * @return string Element HTML
     * @since 6.5.0
     */
    protected function render_email_v2_element($element, $context) {
        $type = $element['type'] ?? '';
        $props = $element['props'] ?? [];
        $styles = $element['styles'] ?? [];

        // Build inline styles
        $styleAttr = $this->build_inline_styles($styles);

        switch ($type) {
            case 'text':
            case 'paragraph':
                $content = $props['content'] ?? '';
                return '<tr><td style="padding:10px 20px;' . $styleAttr . '">' . $content . '</td></tr>';

            case 'heading':
                $content = $props['content'] ?? '';
                $level = $props['level'] ?? 'h2';
                return '<tr><td style="padding:10px 20px;' . $styleAttr . '"><' . $level . '>' . $content . '</' . $level . '></td></tr>';

            case 'image':
                $src = $props['src'] ?? '';
                $alt = $props['alt'] ?? '';
                $width = $props['width'] ?? '100%';
                return '<tr><td style="padding:10px 20px;' . $styleAttr . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" width="' . esc_attr($width) . '" style="max-width:100%;height:auto;"></td></tr>';

            case 'button':
                $text = $props['text'] ?? 'Click Here';
                $url = $props['url'] ?? '#';
                $bgColor = $styles['backgroundColor'] ?? '#0073aa';
                $color = $styles['color'] ?? '#ffffff';
                return '<tr><td style="padding:10px 20px;" align="center"><a href="' . esc_url($url) . '" style="display:inline-block;padding:12px 24px;background-color:' . esc_attr($bgColor) . ';color:' . esc_attr($color) . ';text-decoration:none;border-radius:4px;">' . esc_html($text) . '</a></td></tr>';

            case 'divider':
                $color = $styles['borderColor'] ?? '#dddddd';
                return '<tr><td style="padding:10px 20px;"><hr style="border:none;border-top:1px solid ' . esc_attr($color) . ';"></td></tr>';

            case 'spacer':
                $height = $styles['height'] ?? '20px';
                return '<tr><td style="height:' . esc_attr($height) . ';"></td></tr>';

            case 'loop_fields':
                // Special element: render all form fields
                return '<tr><td style="padding:10px 20px;">' . $this->format_entry_data($context, ['exclude_empty_fields' => true]) . '</td></tr>';

            case 'row':
            case 'column':
                // Container elements - render children
                $childHtml = '';
                if (!empty($element['children']) && is_array($element['children'])) {
                    foreach ($element['children'] as $child) {
                        $childHtml .= $this->render_email_v2_element($child, $context);
                    }
                }
                return $childHtml;

            default:
                // Unknown element type - skip
                return '';
        }
    }

    /**
     * Build inline CSS from styles array
     *
     * @param array $styles Style properties
     * @return string Inline style string
     * @since 6.5.0
     */
    protected function build_inline_styles($styles) {
        if (empty($styles) || !is_array($styles)) {
            return '';
        }

        $css = [];
        $map = [
            'backgroundColor' => 'background-color',
            'color' => 'color',
            'fontSize' => 'font-size',
            'fontWeight' => 'font-weight',
            'textAlign' => 'text-align',
            'padding' => 'padding',
            'margin' => 'margin',
            'borderRadius' => 'border-radius',
        ];

        foreach ($styles as $key => $value) {
            $cssKey = $map[$key] ?? $key;
            $css[] = $cssKey . ':' . $value;
        }

        return implode(';', $css);
    }
}
