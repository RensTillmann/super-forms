<?php
/**
 * Webhook Action
 *
 * Sends HTTP POST/GET/PUT request to external URL
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_Webhook extends SUPER_Trigger_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'webhook';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('Send Webhook', 'super-forms');
    }

    /**
     * Get action category
     *
     * @return string
     */
    public function get_category() {
        return 'integration';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function get_description() {
        return __('Send HTTP request to external URL with form data', 'super-forms');
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
        return [
            [
                'name' => 'url',
                'label' => __('Webhook URL', 'super-forms'),
                'type' => 'url',
                'required' => true,
                'default' => '',
                'placeholder' => 'https://example.com/webhook',
                'description' => __('The URL to send the request to. Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'method',
                'label' => __('HTTP Method', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'POST',
                'options' => [
                    'POST' => 'POST',
                    'GET' => 'GET',
                    'PUT' => 'PUT',
                    'DELETE' => 'DELETE'
                ],
                'description' => __('HTTP method to use', 'super-forms')
            ],
            [
                'name' => 'body_format',
                'label' => __('Body Format', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'json',
                'options' => [
                    'json' => 'JSON',
                    'form_data' => 'Form Data',
                    'custom' => 'Custom'
                ],
                'description' => __('How to format the request body', 'super-forms')
            ],
            [
                'name' => 'custom_body',
                'label' => __('Custom Body', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'description' => __('Custom request body. Supports {tags}.', 'super-forms'),
                'show_if' => ['body_format' => 'custom']
            ],
            [
                'name' => 'headers',
                'label' => __('Custom Headers', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'placeholder' => "Authorization: Bearer {api_token}\nX-Custom-Header: value",
                'description' => __('One header per line (Name: Value). Supports {tags}.', 'super-forms')
            ],
            [
                'name' => 'timeout',
                'label' => __('Timeout (seconds)', 'super-forms'),
                'type' => 'number',
                'required' => false,
                'default' => 30,
                'description' => __('Request timeout in seconds', 'super-forms')
            ],
            [
                'name' => 'include_form_data',
                'label' => __('Include Form Data', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Include all form field data in request', 'super-forms')
            ],
            [
                'name' => 'include_metadata',
                'label' => __('Include Metadata', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Include entry_id, form_id, timestamp, etc.', 'super-forms')
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
        // Replace tags in URL
        $url = $this->replace_tags($config['url'], $context);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error(
                'invalid_url',
                sprintf(__('Invalid webhook URL: %s', 'super-forms'), $url)
            );
        }

        // Build request body
        $body = $this->build_request_body($context, $config);

        // Build headers
        $headers = $this->build_headers($context, $config);

        // Get method and timeout
        $method = strtoupper($config['method'] ?? 'POST');
        $timeout = absint($config['timeout'] ?? 30);

        // Build request args
        $args = [
            'method' => $method,
            'timeout' => $timeout,
            'headers' => $headers,
            'sslverify' => apply_filters('super_webhook_ssl_verify', true)
        ];

        // Add body for POST/PUT
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = $body;
        }

        // Allow filtering
        $args = apply_filters('super_trigger_webhook_args', $args, $url, $context, $config);

        // Send request
        $response = wp_remote_request($url, $args);

        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }

        // Get response details
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Consider 2xx and 3xx as success
        $is_success = $status_code >= 200 && $status_code < 400;

        if (!$is_success) {
            return new WP_Error(
                'webhook_error',
                sprintf(
                    __('Webhook returned status %d: %s', 'super-forms'),
                    $status_code,
                    $response_body
                )
            );
        }

        return [
            'url' => $url,
            'method' => $method,
            'status_code' => $status_code,
            'response_body' => $response_body,
            'message' => sprintf(__('Webhook sent successfully (%d)', 'super-forms'), $status_code)
        ];
    }

    /**
     * Build request body
     *
     * @param array $context
     * @param array $config
     * @return string|array
     */
    protected function build_request_body($context, $config) {
        $format = $config['body_format'] ?? 'json';

        if ($format === 'custom') {
            return $this->replace_tags($config['custom_body'], $context);
        }

        // Build data array
        $data = [];

        if (!empty($config['include_form_data'])) {
            $data['form_data'] = $context['form_data'] ?? [];
        }

        if (!empty($config['include_metadata'])) {
            $data['metadata'] = [
                'entry_id' => $context['entry_id'] ?? null,
                'form_id' => $context['form_id'] ?? null,
                'event_id' => $context['event_id'] ?? null,
                'timestamp' => current_time('mysql'),
                'user_id' => $context['user_id'] ?? get_current_user_id()
            ];
        }

        if ($format === 'json') {
            return json_encode($data);
        }

        // Form data format
        return $data;
    }

    /**
     * Build request headers
     *
     * @param array $context
     * @param array $config
     * @return array
     */
    protected function build_headers($context, $config) {
        $headers = [];

        // Set content type based on format
        $format = $config['body_format'] ?? 'json';
        if ($format === 'json') {
            $headers['Content-Type'] = 'application/json';
        } elseif ($format === 'form_data') {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        // Parse custom headers
        if (!empty($config['headers'])) {
            $custom_headers = $this->replace_tags($config['headers'], $context);
            $header_lines = explode("\n", $custom_headers);

            foreach ($header_lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                if (strpos($line, ':') !== false) {
                    list($name, $value) = explode(':', $line, 2);
                    $headers[trim($name)] = trim($value);
                }
            }
        }

        return $headers;
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
     * Webhooks support and prefer asynchronous execution
     *
     * External HTTP requests can be slow and shouldn't block form submission.
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
     * Get retry configuration for webhooks
     *
     * External services may be temporarily unavailable.
     *
     * @return array
     * @since 6.5.0
     */
    public function get_retry_config() {
        return [
            'max_retries'   => 5,      // More retries for external services
            'initial_delay' => 60,     // 1 minute
            'exponential'   => true,
            'max_delay'     => 3600,   // 1 hour max
        ];
    }
}
