<?php
/**
 * HTTP Request Action (Postman-like)
 *
 * Comprehensive HTTP request action supporting multiple methods,
 * authentication types, body formats, and response mapping.
 *
 * @package Super_Forms
 * @subpackage Triggers/Actions
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Action_HTTP_Request extends SUPER_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    public function get_id() {
        return 'http_request';
    }

    /**
     * Get action label
     *
     * @return string
     */
    public function get_label() {
        return __('HTTP Request', 'super-forms');
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
        return __('Make HTTP API calls with full control over headers, authentication, body format, and response handling', 'super-forms');
    }

    /**
     * Get settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
        return [
            // Request name for identification
            [
                'name' => 'request_name',
                'label' => __('Request Name', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'e.g., Send to CRM API',
                'description' => __('Descriptive name for this request (for logging)', 'super-forms')
            ],

            // HTTP Method
            [
                'name' => 'method',
                'label' => __('HTTP Method', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'POST',
                'options' => [
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                    'HEAD' => 'HEAD',
                    'OPTIONS' => 'OPTIONS'
                ],
                'description' => __('HTTP method to use', 'super-forms')
            ],

            // URL
            [
                'name' => 'url',
                'label' => __('URL', 'super-forms'),
                'type' => 'url',
                'required' => true,
                'default' => '',
                'placeholder' => 'https://api.example.com/endpoint',
                'description' => __('The URL to send the request to. Supports {tags}.', 'super-forms')
            ],

            // Authentication Type
            [
                'name' => 'auth_type',
                'label' => __('Authentication', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'super-forms'),
                    'basic' => __('Basic Auth', 'super-forms'),
                    'bearer' => __('Bearer Token', 'super-forms'),
                    'api_key' => __('API Key', 'super-forms'),
                    'oauth2' => __('OAuth 2.0', 'super-forms'),
                    'custom' => __('Custom Header', 'super-forms')
                ],
                'description' => __('Authentication method', 'super-forms')
            ],

            // Basic Auth - Username
            [
                'name' => 'basic_username',
                'label' => __('Username', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'description' => __('Basic auth username. Supports {tags}.', 'super-forms'),
                'show_if' => ['auth_type' => 'basic']
            ],

            // Basic Auth - Password
            [
                'name' => 'basic_password',
                'label' => __('Password', 'super-forms'),
                'type' => 'password',
                'required' => false,
                'default' => '',
                'description' => __('Basic auth password. Supports {tags}.', 'super-forms'),
                'show_if' => ['auth_type' => 'basic']
            ],

            // Bearer Token
            [
                'name' => 'bearer_token',
                'label' => __('Bearer Token', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'Your token or {tag}',
                'description' => __('Bearer token. Supports {tags}.', 'super-forms'),
                'show_if' => ['auth_type' => 'bearer']
            ],

            // API Key - Location
            [
                'name' => 'api_key_location',
                'label' => __('API Key Location', 'super-forms'),
                'type' => 'select',
                'required' => false,
                'default' => 'header',
                'options' => [
                    'header' => __('Header', 'super-forms'),
                    'query' => __('Query String', 'super-forms')
                ],
                'description' => __('Where to send the API key', 'super-forms'),
                'show_if' => ['auth_type' => 'api_key']
            ],

            // API Key - Name
            [
                'name' => 'api_key_name',
                'label' => __('API Key Name', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => 'X-API-Key',
                'placeholder' => 'X-API-Key',
                'description' => __('Header or query parameter name', 'super-forms'),
                'show_if' => ['auth_type' => 'api_key']
            ],

            // API Key - Value
            [
                'name' => 'api_key_value',
                'label' => __('API Key Value', 'super-forms'),
                'type' => 'password',
                'required' => false,
                'default' => '',
                'description' => __('API key value. Supports {tags}.', 'super-forms'),
                'show_if' => ['auth_type' => 'api_key']
            ],

            // OAuth 2.0 - Provider
            [
                'name' => 'oauth_credential_id',
                'label' => __('OAuth Credential', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'google_sheets',
                'description' => __('Stored OAuth credential name', 'super-forms'),
                'show_if' => ['auth_type' => 'oauth2']
            ],

            // Custom Auth Header Name
            [
                'name' => 'custom_auth_header',
                'label' => __('Header Name', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'placeholder' => 'X-Custom-Auth',
                'description' => __('Custom authentication header name', 'super-forms'),
                'show_if' => ['auth_type' => 'custom']
            ],

            // Custom Auth Header Value
            [
                'name' => 'custom_auth_value',
                'label' => __('Header Value', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '',
                'description' => __('Custom authentication header value. Supports {tags}.', 'super-forms'),
                'show_if' => ['auth_type' => 'custom']
            ],

            // Custom Headers
            [
                'name' => 'headers',
                'label' => __('Custom Headers', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'placeholder' => "Content-Type: application/json\nX-Custom-Header: value",
                'description' => __('One header per line (Name: Value). Supports {tags}.', 'super-forms')
            ],

            // Body Type
            [
                'name' => 'body_type',
                'label' => __('Body Type', 'super-forms'),
                'type' => 'select',
                'required' => true,
                'default' => 'json',
                'options' => [
                    'none' => __('None', 'super-forms'),
                    'json' => __('JSON', 'super-forms'),
                    'form_data' => __('Form Data', 'super-forms'),
                    'xml' => __('XML', 'super-forms'),
                    'raw' => __('Raw', 'super-forms'),
                    'graphql' => __('GraphQL', 'super-forms'),
                    'auto' => __('Auto (all form data as JSON)', 'super-forms')
                ],
                'description' => __('Request body format', 'super-forms')
            ],

            // JSON Body
            [
                'name' => 'json_body',
                'label' => __('JSON Body', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => "{\n  \"name\": \"{name}\",\n  \"email\": \"{email}\"\n}",
                'description' => __('JSON body. Use {field_name} for values. Use {repeater:field_name} or {repeater:field_name|fields:a,b} for repeater/dynamic column data.', 'super-forms'),
                'show_if' => ['body_type' => 'json']
            ],

            // Form Data (key=value pairs)
            [
                'name' => 'form_data_body',
                'label' => __('Form Data', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'placeholder' => "name={name}\nemail={email}",
                'description' => __('One field per line (key=value). Supports {tags}.', 'super-forms'),
                'show_if' => ['body_type' => 'form_data']
            ],

            // XML Body
            [
                'name' => 'xml_body',
                'label' => __('XML Body', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'placeholder' => '<?xml version="1.0"?>\n<request>\n  <name>{name}</name>\n</request>',
                'description' => __('XML body. Supports {tags}.', 'super-forms'),
                'show_if' => ['body_type' => 'xml']
            ],

            // Raw Body
            [
                'name' => 'raw_body',
                'label' => __('Raw Body', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'description' => __('Raw body content. Supports {tags}.', 'super-forms'),
                'show_if' => ['body_type' => 'raw']
            ],

            // Raw Body Content Type
            [
                'name' => 'raw_content_type',
                'label' => __('Content Type', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => 'text/plain',
                'placeholder' => 'text/plain',
                'description' => __('Content-Type header for raw body', 'super-forms'),
                'show_if' => ['body_type' => 'raw']
            ],

            // GraphQL Query
            [
                'name' => 'graphql_query',
                'label' => __('GraphQL Query', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'placeholder' => 'mutation CreateUser($name: String!) {\n  createUser(name: $name) {\n    id\n  }\n}',
                'description' => __('GraphQL query or mutation', 'super-forms'),
                'show_if' => ['body_type' => 'graphql']
            ],

            // GraphQL Variables
            [
                'name' => 'graphql_variables',
                'label' => __('GraphQL Variables', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'placeholder' => '{"name": "{name}"}',
                'description' => __('JSON variables for GraphQL. Supports {tags}.', 'super-forms'),
                'show_if' => ['body_type' => 'graphql']
            ],

            // Response Format
            [
                'name' => 'response_format',
                'label' => __('Expected Response Format', 'super-forms'),
                'type' => 'select',
                'required' => false,
                'default' => 'json',
                'options' => [
                    'json' => 'JSON',
                    'xml' => 'XML',
                    'text' => 'Plain Text',
                    'auto' => 'Auto-detect'
                ],
                'description' => __('Expected response format for parsing', 'super-forms')
            ],

            // Response Mapping
            [
                'name' => 'response_mapping',
                'label' => __('Response Mapping', 'super-forms'),
                'type' => 'textarea',
                'required' => false,
                'default' => '',
                'placeholder' => "user_id=data.user.id\nall_ids=data.items[*].id|json\nusers=data.users|repeater",
                'description' => __('Map response to variables. Use [*] for arrays (items[*].id). Modifiers: |json, |repeater, |first, |last, |count, |join, |flatten, |unique, |sort, |reverse, |slice:0:5', 'super-forms')
            ],

            // Success Status Codes
            [
                'name' => 'success_status_codes',
                'label' => __('Success Status Codes', 'super-forms'),
                'type' => 'text',
                'required' => false,
                'default' => '200,201,204',
                'description' => __('Comma-separated HTTP status codes to consider success', 'super-forms')
            ],

            // Timeout
            [
                'name' => 'timeout',
                'label' => __('Timeout (seconds)', 'super-forms'),
                'type' => 'number',
                'required' => false,
                'default' => 30,
                'description' => __('Request timeout in seconds (max 300)', 'super-forms')
            ],

            // Follow Redirects
            [
                'name' => 'follow_redirects',
                'label' => __('Follow Redirects', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Automatically follow HTTP redirects', 'super-forms')
            ],

            // SSL Verification
            [
                'name' => 'ssl_verify',
                'label' => __('Verify SSL Certificate', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => true,
                'description' => __('Verify SSL certificate validity', 'super-forms')
            ],

            // Retry on Failure
            [
                'name' => 'retry_on_failure',
                'label' => __('Retry on Failure', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => false,
                'description' => __('Automatically retry failed requests', 'super-forms')
            ],

            // Retry Count
            [
                'name' => 'retry_count',
                'label' => __('Retry Count', 'super-forms'),
                'type' => 'number',
                'required' => false,
                'default' => 3,
                'description' => __('Number of retry attempts (1-5)', 'super-forms'),
                'show_if' => ['retry_on_failure' => true]
            ],

            // Retry Delay
            [
                'name' => 'retry_delay',
                'label' => __('Retry Delay (seconds)', 'super-forms'),
                'type' => 'number',
                'required' => false,
                'default' => 5,
                'description' => __('Seconds to wait between retries', 'super-forms'),
                'show_if' => ['retry_on_failure' => true]
            ],

            // Debug Mode
            [
                'name' => 'debug_mode',
                'label' => __('Debug Mode', 'super-forms'),
                'type' => 'toggle',
                'required' => false,
                'default' => false,
                'description' => __('Log full request/response details', 'super-forms')
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
        $retry_enabled = !empty($config['retry_on_failure']);
        $max_attempts = $retry_enabled ? min(absint($config['retry_count'] ?? 3), 5) : 1;
        $retry_delay = absint($config['retry_delay'] ?? 5);
        $debug_mode = !empty($config['debug_mode']);

        $attempt = 0;
        $last_error = null;

        while ($attempt < $max_attempts) {
            $attempt++;

            // Build the request
            $request = $this->build_request($context, $config);

            if (is_wp_error($request)) {
                return $request;
            }

            if ($debug_mode) {
                $this->debug_log('Request attempt ' . $attempt, $request);
            }

            // Send the request
            $response = $this->send_request($request);

            if ($debug_mode) {
                $this->debug_log('Response received', [
                    'is_error' => is_wp_error($response),
                    'status' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_code($response),
                    'body' => is_wp_error($response) ? null : substr(wp_remote_retrieve_body($response), 0, 1000)
                ]);
            }

            // Check if successful
            if ($this->is_success($response, $config)) {
                // Parse response and map values
                $mapped_data = $this->map_response($response, $config);

                // Store mapped data in context for subsequent actions
                if (!empty($mapped_data)) {
                    $this->store_mapped_data($mapped_data, $context);
                }

                $status_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);

                return [
                    'success' => true,
                    'url' => $request['url'],
                    'method' => $request['method'],
                    'status_code' => $status_code,
                    'response_body' => $response_body,
                    'mapped_data' => $mapped_data,
                    'attempts' => $attempt,
                    'message' => sprintf(
                        __('HTTP request successful (%d)', 'super-forms'),
                        $status_code
                    )
                ];
            }

            // Store error for reporting
            $last_error = is_wp_error($response)
                ? $response
                : new WP_Error(
                    'http_error',
                    sprintf(
                        __('HTTP request failed with status %d', 'super-forms'),
                        wp_remote_retrieve_response_code($response)
                    ),
                    [
                        'status_code' => wp_remote_retrieve_response_code($response),
                        'body' => wp_remote_retrieve_body($response)
                    ]
                );

            // Wait before retry (if not last attempt)
            if ($attempt < $max_attempts) {
                sleep($retry_delay);
            }
        }

        // All attempts failed
        return new WP_Error(
            'http_request_failed',
            sprintf(
                __('HTTP request failed after %d attempts: %s', 'super-forms'),
                $attempt,
                $last_error ? $last_error->get_error_message() : __('Unknown error', 'super-forms')
            ),
            [
                'attempts' => $attempt,
                'last_error' => $last_error ? $last_error->get_error_data() : null
            ]
        );
    }

    /**
     * Build the HTTP request
     *
     * @param array $context
     * @param array $config
     * @return array|WP_Error
     */
    protected function build_request($context, $config) {
        // Validate critical variables
        $validation = $this->validate_context(['url'], $context);
        if (!$validation['valid']) {
            return new WP_Error('missing_url', 'Missing required URL');
        }

        // URL sanitization
        $url = $this->replace_variables($config['url'] ?? '', $context, [
            'sanitize' => 'url',
            'missing_behavior' => 'error'
        ]);

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error(
                'invalid_url',
                sprintf(__('Invalid URL: %s', 'super-forms'), $url)
            );
        }

        // Add API key to query string if needed
        if (($config['auth_type'] ?? '') === 'api_key' && ($config['api_key_location'] ?? '') === 'query') {
            $key_name = $config['api_key_name'] ?? 'api_key';
            $key_value = $this->replace_tags($config['api_key_value'] ?? '', $context);
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . urlencode($key_name) . '=' . urlencode($key_value);
        }

        // Build headers
        $headers = $this->build_headers($context, $config);

        // Build body
        $body = $this->build_body($context, $config);

        // Get timeout (max 300 seconds)
        $timeout = min(absint($config['timeout'] ?? 30), 300);

        return [
            'url' => $url,
            'method' => strtoupper($config['method'] ?? 'POST'),
            'headers' => $headers,
            'body' => $body,
            'timeout' => $timeout,
            'redirection' => !empty($config['follow_redirects']) ? 5 : 0,
            'sslverify' => isset($config['ssl_verify']) ? (bool) $config['ssl_verify'] : true
        ];
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

        // Parse custom headers first (can be overridden by auth)
        if (!empty($config['headers'])) {
            $custom_headers = $this->replace_tags($config['headers'], $context);
            $header_lines = explode("\n", $custom_headers);

            foreach ($header_lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, ':') === false) {
                    continue;
                }
                list($name, $value) = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }

        // Add authentication headers
        $auth_type = $config['auth_type'] ?? 'none';

        switch ($auth_type) {
            case 'basic':
                $username = $this->replace_tags($config['basic_username'] ?? '', $context);
                $password = $this->replace_tags($config['basic_password'] ?? '', $context);
                $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
                break;

            case 'bearer':
                $token = $this->replace_tags($config['bearer_token'] ?? '', $context);
                $headers['Authorization'] = 'Bearer ' . $token;
                break;

            case 'api_key':
                if (($config['api_key_location'] ?? '') === 'header') {
                    $key_name = $config['api_key_name'] ?? 'X-API-Key';
                    $key_value = $this->replace_tags($config['api_key_value'] ?? '', $context);
                    $headers[$key_name] = $key_value;
                }
                break;

            case 'oauth2':
                $token = $this->get_oauth_token($config, $context);
                if ($token) {
                    $headers['Authorization'] = 'Bearer ' . $token;
                }
                break;

            case 'custom':
                $header_name = $config['custom_auth_header'] ?? '';
                $header_value = $this->replace_tags($config['custom_auth_value'] ?? '', $context);
                if (!empty($header_name)) {
                    $headers[$header_name] = $header_value;
                }
                break;
        }

        // Set Content-Type based on body type (if not already set)
        if (!isset($headers['Content-Type'])) {
            $body_type = $config['body_type'] ?? 'json';

            switch ($body_type) {
                case 'json':
                case 'auto':
                case 'graphql':
                    $headers['Content-Type'] = 'application/json';
                    break;

                case 'form_data':
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    break;

                case 'xml':
                    $headers['Content-Type'] = 'application/xml';
                    break;

                case 'raw':
                    $headers['Content-Type'] = $config['raw_content_type'] ?? 'text/plain';
                    break;
            }
        }

        return $headers;
    }

    /**
     * Build request body
     *
     * @param array $context
     * @param array $config
     * @return string|array|null
     */
    protected function build_body($context, $config) {
        $body_type = $config['body_type'] ?? 'json';
        $method = strtoupper($config['method'] ?? 'POST');

        // No body for GET, HEAD, OPTIONS
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS']) || $body_type === 'none') {
            return null;
        }

        switch ($body_type) {
            case 'json':
                $json = $config['json_body'] ?? '{}';
                // Process repeater tags before standard tag replacement
                $json = $this->process_repeater_tags($json, $context);
                // Now do standard tag replacement
                $json = $this->replace_tags($json, $context);

                // Validate JSON
                $decoded = json_decode($json);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Try to fix common issues (trailing commas, etc.)
                    $json = preg_replace(",s*([]}])/", "$1", $json);
                    $decoded = json_decode($json);
                }

                // Sanitize string values in JSON structure
                if (is_array($decoded)) {
                    array_walk_recursive($decoded, function(&$value) {
                        if (is_string($value)) {
                            $value = sanitize_text_field($value);
                        }
                    });
                    $json = json_encode($decoded);
                }

                // TODO: Implement proper JSON sanitization for nested structures in future version
                // Validate JSON
                $decoded = json_decode($json);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Try to fix common issues (trailing commas, etc.)
                    $json = preg_replace('/,\s*([\]}])/', '$1', $json);
                }
                return $json;

            case 'form_data':
                $form_data = [];
                $lines = explode("\n", $config['form_data_body'] ?? '');
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '=') === false) {
                        continue;
                    }
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = $this->replace_tags(trim($value), $context);
                    $form_data[$key] = $value;
                }
                return $form_data;

            case 'xml':
                return $this->replace_tags($config['xml_body'] ?? '', $context);

            case 'raw':
                return $this->replace_tags($config['raw_body'] ?? '', $context);

            case 'graphql':
                $query = $config['graphql_query'] ?? '';
                $variables = $this->replace_tags($config['graphql_variables'] ?? '{}', $context);
                $vars_decoded = json_decode($variables, true);
                return json_encode([
                    'query' => $query,
                    'variables' => is_array($vars_decoded) ? $vars_decoded : []
                ]);

            case 'auto':
                // Send all form data as JSON
                $data = [
                    'form_data' => $context['form_data'] ?? $context['data'] ?? [],
                    'metadata' => [
                        'entry_id' => $context['entry_id'] ?? null,
                        'form_id' => $context['form_id'] ?? null,
                        'event_id' => $context['event_id'] ?? null,
                        'timestamp' => current_time('c'),
                        'user_id' => $context['user_id'] ?? get_current_user_id()
                    ]
                ];
                return json_encode($data);

            default:
                return null;
        }
    }

    /**
     * Process repeater tags in JSON body
     *
     * Syntax: {repeater:field_name} or {repeater:field_name|fields:name,email}
     *
     * Converts Super Forms repeater format to standard JSON array:
     * SF: {0: {name: {value: 'John'}}, 1: {name: {value: 'Jane'}}}
     * API: [{"name": "John"}, {"name": "Jane"}]
     *
     * @param string $json JSON body string
     * @param array $context Event context with form data
     * @return string Processed JSON
     */
    protected function process_repeater_tags($json, $context) {
        // Match {repeater:field_name} or {repeater:field_name|fields:a,b,c}
        $pattern = '/\{repeater:([a-zA-Z0-9_]+)(?:\|fields:([a-zA-Z0-9_,]+))?\}/';

        return preg_replace_callback($pattern, function($matches) use ($context) {
            $field_name = $matches[1];
            $fields_filter = isset($matches[2]) ? array_map('trim', explode(',', $matches[2])) : null;

            // Get repeater data from context
            $repeater_data = $this->get_repeater_data($field_name, $context);

            if (empty($repeater_data)) {
                return '[]'; // Empty array if no data
            }

            // Convert SF repeater format to standard array
            $array = $this->convert_repeater_to_array($repeater_data, $fields_filter);

            return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $json);
    }

    /**
     * Get repeater data from context
     *
     * @param string $field_name
     * @param array $context
     * @return array|null
     */
    protected function get_repeater_data($field_name, $context) {
        // Check in form_data first (flat key-value)
        if (isset($context['form_data'][$field_name])) {
            $data = $context['form_data'][$field_name];
            // If it's already an array with numeric keys, it's repeater data
            if (is_array($data)) {
                return $data;
            }
        }

        // Check in data (Super Forms structure with value wrappers)
        if (isset($context['data'][$field_name])) {
            $data = $context['data'][$field_name];
            if (is_array($data) && isset($data['value']) && is_array($data['value'])) {
                return $data['value'];
            }
            if (is_array($data) && !isset($data['value'])) {
                return $data;
            }
        }

        // Check if it's a dynamic column/repeater field (numeric keys at top level)
        if (isset($context['data'])) {
            foreach ($context['data'] as $key => $value) {
                if (strpos($key, $field_name . '_') === 0 || $key === $field_name) {
                    if (is_array($value) && isset($value[0])) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Convert Super Forms repeater format to standard array
     *
     * SF format:  {0: {name: {value: "John"}, email: {value: "john@test.com"}}, ...}
     * API format: [{"name": "John", "email": "john@test.com"}, ...]
     *
     * Supports nested repeaters (repeaters within repeaters) with recursive conversion.
     *
     * @param array $repeater_data
     * @param array|null $fields_filter Optional list of fields to include
     * @param int $depth Current recursion depth (max 5)
     * @return array
     */
    protected function convert_repeater_to_array($repeater_data, $fields_filter = null, $depth = 0) {
        // Prevent infinite recursion
        if ($depth > 5) {
            return $repeater_data;
        }

        $result = [];

        foreach ($repeater_data as $index => $row) {
            if (!is_numeric($index) || !is_array($row)) {
                continue;
            }

            $item = [];

            foreach ($row as $field => $field_data) {
                // Apply fields filter (only at top level)
                if ($depth === 0 && $fields_filter !== null && !in_array($field, $fields_filter)) {
                    continue;
                }

                // Extract value from SF format
                if (is_array($field_data) && isset($field_data['value'])) {
                    $value = $field_data['value'];
                } else {
                    $value = $field_data;
                }

                // Check if value is a nested repeater (array with numeric keys containing arrays)
                if ($this->is_nested_repeater($value)) {
                    $value = $this->convert_repeater_to_array($value, null, $depth + 1);
                }
                // Try to parse JSON values (for nested objects stored as strings)
                elseif (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // Check if decoded JSON is a nested repeater
                        if ($this->is_nested_repeater($decoded)) {
                            $value = $this->convert_repeater_to_array($decoded, null, $depth + 1);
                        } else {
                            $value = $decoded;
                        }
                    }
                }

                $item[$field] = $value;
            }

            if (!empty($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Check if a value is a nested repeater structure
     *
     * A nested repeater has:
     * - Numeric keys (0, 1, 2, ...)
     * - Array values containing field data
     *
     * @param mixed $value
     * @return bool
     */
    protected function is_nested_repeater($value) {
        if (!is_array($value) || empty($value)) {
            return false;
        }

        // Check if all keys are numeric
        $keys = array_keys($value);
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                return false;
            }
        }

        // Check if first item is an array (row of fields)
        $first = reset($value);
        if (!is_array($first)) {
            return false;
        }

        // Check if first item contains SF field format (arrays with 'value' key)
        // or is itself an associative array (already extracted values)
        foreach ($first as $field_value) {
            if (is_array($field_value)) {
                // Could be SF format {value: ...} or nested repeater
                return true;
            }
        }

        // It's an array of simple associative arrays
        return !empty($first) && is_string(key($first));
    }

    /**
     * Send the HTTP request
     *
     * @param array $request
     * @return array|WP_Error
     */
    protected function send_request($request) {
        $args = [
            'method' => $request['method'],
            'timeout' => $request['timeout'],
            'redirection' => $request['redirection'],
            'headers' => $request['headers'],
            'sslverify' => $request['sslverify']
        ];

        // Add body for methods that support it
        if ($request['body'] !== null) {
            $args['body'] = $request['body'];
        }

        // Allow filtering
        $args = apply_filters('super_http_request_args', $args, $request);

        // Use appropriate WP function
        switch ($request['method']) {
            case 'GET':
                return wp_remote_get($request['url'], $args);

            case 'POST':
                return wp_remote_post($request['url'], $args);

            case 'HEAD':
                return wp_remote_head($request['url'], $args);

            default:
                return wp_remote_request($request['url'], $args);
        }
    }

    /**
     * Check if response is successful
     *
     * @param array|WP_Error $response
     * @param array $config
     * @return bool
     */
    protected function is_success($response, $config) {
        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $success_codes_str = $config['success_status_codes'] ?? '200,201,204';
        $success_codes = array_map('trim', explode(',', $success_codes_str));
        $success_codes = array_map('intval', $success_codes);

        return in_array($status_code, $success_codes, true);
    }

    /**
     * Map response data to variables
     *
     * Supports pipe modifiers for output transformation:
     * - |json    : Convert array to JSON string
     * - |repeater: Convert array to Super Forms repeater format
     * - |first   : Get first item from array
     * - |last    : Get last item from array
     * - |count   : Get array length
     * - |keys    : Get array keys
     * - |values  : Get array values (re-indexed)
     * - |join    : Join array with comma (or |join:separator)
     * - |flatten : Flatten nested array
     *
     * @param array $response
     * @param array $config
     * @return array
     */
    protected function map_response($response, $config) {
        $mapping_str = $config['response_mapping'] ?? '';

        if (empty($mapping_str)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $format = $config['response_format'] ?? 'auto';

        // Auto-detect format
        if ($format === 'auto') {
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            if (stripos($content_type, 'json') !== false) {
                $format = 'json';
            } elseif (stripos($content_type, 'xml') !== false) {
                $format = 'xml';
            } else {
                $format = 'text';
            }
        }

        $mapped = [];

        // Parse mapping lines
        $lines = explode("\n", $mapping_str);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '=') === false) {
                continue;
            }

            list($variable, $path_with_modifiers) = explode('=', $line, 2);
            $variable = trim($variable);
            $path_with_modifiers = trim($path_with_modifiers);

            // Parse path and modifiers (e.g., "data.items[*].id|json")
            $parts = explode('|', $path_with_modifiers);
            $path = array_shift($parts);
            $modifiers = $parts;

            $value = null;

            switch ($format) {
                case 'json':
                    $data = json_decode($body, true);
                    if (is_array($data)) {
                        $value = $this->get_value_by_path($data, $path);
                    }
                    break;

                case 'xml':
                    $xml = @simplexml_load_string($body);
                    if ($xml !== false) {
                        // Try XPath
                        $nodes = @$xml->xpath($path);
                        if (!empty($nodes)) {
                            $value = (string) $nodes[0];
                        }
                    }
                    break;

                case 'text':
                    // For text, use regex pattern
                    if (preg_match('/' . preg_quote($path, '/') . '/', $body, $matches)) {
                        $value = $matches[0];
                    }
                    break;
            }

            // Apply modifiers
            if ($value !== null && !empty($modifiers)) {
                $value = $this->apply_modifiers($value, $modifiers);
            }

            if ($value !== null) {
                $mapped[$variable] = $value;
            }
        }

        return $mapped;
    }

    /**
     * Apply pipe modifiers to a value
     *
     * @param mixed $value The value to transform
     * @param array $modifiers Array of modifier strings
     * @return mixed Transformed value
     */
    protected function apply_modifiers($value, $modifiers) {
        foreach ($modifiers as $modifier) {
            // Parse modifier and optional argument (e.g., "join:," or "join")
            $modifier_parts = explode(':', $modifier, 2);
            $modifier_name = strtolower(trim($modifier_parts[0]));
            $modifier_arg = isset($modifier_parts[1]) ? $modifier_parts[1] : null;

            switch ($modifier_name) {
                case 'json':
                    // Convert to JSON string
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;

                case 'repeater':
                    // Convert array to Super Forms repeater format
                    $value = $this->convert_to_repeater_format($value, $modifier_arg);
                    break;

                case 'first':
                    // Get first item
                    if (is_array($value) && !empty($value)) {
                        $value = reset($value);
                    }
                    break;

                case 'last':
                    // Get last item
                    if (is_array($value) && !empty($value)) {
                        $value = end($value);
                    }
                    break;

                case 'count':
                    // Get array length
                    $value = is_array($value) ? count($value) : (is_string($value) ? strlen($value) : 0);
                    break;

                case 'keys':
                    // Get array keys
                    if (is_array($value)) {
                        $value = array_keys($value);
                    }
                    break;

                case 'values':
                    // Get array values (re-indexed)
                    if (is_array($value)) {
                        $value = array_values($value);
                    }
                    break;

                case 'join':
                    // Join array with separator
                    if (is_array($value)) {
                        $separator = $modifier_arg !== null ? $modifier_arg : ',';
                        $value = implode($separator, array_map(function($v) {
                            return is_array($v) ? json_encode($v) : $v;
                        }, $value));
                    }
                    break;

                case 'flatten':
                    // Flatten nested array
                    if (is_array($value)) {
                        $value = $this->array_flatten($value);
                    }
                    break;

                case 'unique':
                    // Remove duplicates
                    if (is_array($value)) {
                        $value = array_values(array_unique($value, SORT_REGULAR));
                    }
                    break;

                case 'sort':
                    // Sort array
                    if (is_array($value)) {
                        if ($modifier_arg === 'desc') {
                            rsort($value);
                        } else {
                            sort($value);
                        }
                    }
                    break;

                case 'reverse':
                    // Reverse array
                    if (is_array($value)) {
                        $value = array_reverse($value);
                    }
                    break;

                case 'slice':
                    // Slice array: |slice:start:length
                    if (is_array($value) && $modifier_arg !== null) {
                        $slice_parts = explode(':', $modifier_arg);
                        $start = (int) ($slice_parts[0] ?? 0);
                        $length = isset($slice_parts[1]) ? (int) $slice_parts[1] : null;
                        $value = array_slice($value, $start, $length);
                    }
                    break;

                // =====================================================
                // FILE MODIFIERS
                // =====================================================

                case 'files':
                    // Split comma-separated file URLs into array
                    if (is_string($value)) {
                        $value = array_map('trim', explode(',', $value));
                        $value = array_filter($value); // Remove empty
                        $value = array_values($value); // Re-index
                    }
                    break;

                case 'file_base64':
                    // Convert file URL to base64 (with 5MB size limit)
                    if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                        $value = $this->url_to_base64($value, 5 * 1024 * 1024);
                    } elseif (is_array($value) && !empty($value)) {
                        // If array, convert first file
                        $first_url = reset($value);
                        if (is_string($first_url) && filter_var($first_url, FILTER_VALIDATE_URL)) {
                            $value = $this->url_to_base64($first_url, 5 * 1024 * 1024);
                        }
                    }
                    break;

                case 'file_meta':
                    // Extract file metadata from URL
                    if (is_string($value)) {
                        $value = $this->extract_file_meta($value);
                    } elseif (is_array($value)) {
                        // Convert array of URLs to array of metadata
                        $value = array_map([$this, 'extract_file_meta'], $value);
                    }
                    break;

                // =====================================================
                // SIGNATURE / BASE64 DATA URL MODIFIERS
                // =====================================================

                case 'base64_data':
                    // Extract just the base64 data from a data URL
                    // Input: "data:image/png;base64,iVBORw0KGgo..."
                    // Output: "iVBORw0KGgo..."
                    if (is_string($value) && strpos($value, 'base64,') !== false) {
                        $parts = explode('base64,', $value, 2);
                        $value = isset($parts[1]) ? $parts[1] : $value;
                    }
                    break;

                case 'base64_mime':
                    // Extract MIME type from data URL
                    // Input: "data:image/png;base64,iVBORw0KGgo..."
                    // Output: "image/png"
                    if (is_string($value) && preg_match('/^data:([^;,]+)/', $value, $matches)) {
                        $value = $matches[1];
                    } else {
                        $value = null;
                    }
                    break;

                case 'base64_ext':
                    // Extract file extension from data URL MIME type
                    // Input: "data:image/png;base64,..."
                    // Output: "png"
                    if (is_string($value) && preg_match('/^data:([^;,]+)/', $value, $matches)) {
                        $mime = $matches[1];
                        $value = $this->mime_to_extension($mime);
                    } else {
                        $value = null;
                    }
                    break;

                // =====================================================
                // WORDPRESS ATTACHMENT MODIFIERS
                // =====================================================

                case 'attachment_url':
                    // Get URL from WordPress attachment ID
                    if (is_numeric($value)) {
                        $url = wp_get_attachment_url((int) $value);
                        $value = $url ? $url : null;
                    }
                    break;

                case 'attachment_base64':
                    // Get base64 content from WordPress attachment (5MB limit)
                    if (is_numeric($value)) {
                        $file_path = get_attached_file((int) $value);
                        if ($file_path && file_exists($file_path)) {
                            $size = filesize($file_path);
                            if ($size <= 5 * 1024 * 1024) {
                                $content = file_get_contents($file_path);
                                $value = $content ? base64_encode($content) : null;
                            } else {
                                $value = null; // File too large
                            }
                        } else {
                            $value = null;
                        }
                    }
                    break;

                case 'attachment_meta':
                    // Get attachment metadata
                    if (is_numeric($value)) {
                        $attachment_id = (int) $value;
                        $value = [
                            'id' => $attachment_id,
                            'url' => wp_get_attachment_url($attachment_id),
                            'path' => get_attached_file($attachment_id),
                            'filename' => basename(get_attached_file($attachment_id) ?: ''),
                            'mime_type' => get_post_mime_type($attachment_id),
                            'metadata' => wp_get_attachment_metadata($attachment_id)
                        ];
                    }
                    break;
            }
        }

        return $value;
    }

    /**
     * Convert API array to Super Forms repeater format
     *
     * API format: [{"name": "John", "email": "john@example.com"}, ...]
     * SF format:  {0: {name: {value: "John"}, email: {value: "john@example.com"}}, ...}
     *
     * @param array $data Array data from API
     * @param string|null $fields Optional comma-separated list of fields to include
     * @return array Super Forms repeater format
     */
    protected function convert_to_repeater_format($data, $fields = null) {
        if (!is_array($data)) {
            return $data;
        }

        // Parse fields filter
        $field_filter = null;
        if ($fields !== null) {
            $field_filter = array_map('trim', explode(',', $fields));
        }

        $repeater = [];
        $index = 0;

        foreach ($data as $item) {
            if (!is_array($item)) {
                // Simple value - wrap it
                $repeater[$index] = [
                    'value' => ['value' => $item]
                ];
            } else {
                // Object/associative array
                $row = [];
                foreach ($item as $key => $value) {
                    // Apply field filter if specified
                    if ($field_filter !== null && !in_array($key, $field_filter)) {
                        continue;
                    }

                    // Wrap in SF field format
                    $row[$key] = [
                        'value' => is_array($value) ? json_encode($value) : $value
                    ];
                }
                $repeater[$index] = $row;
            }
            $index++;
        }

        return $repeater;
    }

    /**
     * Flatten a nested array
     *
     * @param array $array
     * @return array
     */
    protected function array_flatten($array) {
        $result = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert URL content to base64
     *
     * @param string $url URL to fetch
     * @param int $max_size Maximum file size in bytes (default 5MB)
     * @return string|null Base64 encoded content or null on failure
     */
    protected function url_to_base64($url, $max_size = 5242880) {
        // First, do a HEAD request to check content length
        $head_response = wp_remote_head($url, [
            'timeout' => 10,
            'sslverify' => false
        ]);

        if (is_wp_error($head_response)) {
            return null;
        }

        $content_length = wp_remote_retrieve_header($head_response, 'content-length');
        if ($content_length && (int) $content_length > $max_size) {
            return null; // File too large
        }

        // Fetch the file
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body) || strlen($body) > $max_size) {
            return null;
        }

        return base64_encode($body);
    }

    /**
     * Extract file metadata from URL
     *
     * @param string $url File URL
     * @return array File metadata
     */
    protected function extract_file_meta($url) {
        if (!is_string($url) || empty($url)) {
            return null;
        }

        $parsed = wp_parse_url($url);
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $filename = basename($path);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return [
            'url' => $url,
            'filename' => $filename,
            'extension' => strtolower($extension),
            'basename' => pathinfo($filename, PATHINFO_FILENAME)
        ];
    }

    /**
     * Convert MIME type to file extension
     *
     * @param string $mime MIME type
     * @return string|null File extension
     */
    protected function mime_to_extension($mime) {
        $mime_map = [
            // Images
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff',
            // Documents
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            // Text
            'text/plain' => 'txt',
            'text/html' => 'html',
            'text/css' => 'css',
            'text/javascript' => 'js',
            'application/json' => 'json',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            // Archives
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/gzip' => 'gz',
            'application/x-tar' => 'tar',
            // Audio
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            // Video
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogv',
        ];

        $mime = strtolower(trim($mime));
        return isset($mime_map[$mime]) ? $mime_map[$mime] : null;
    }

    /**
     * Get value from nested array by dot notation path
     *
     * Supports:
     * - Simple paths: data.user.id
     * - Specific indices: items[0].name
     * - Wildcard extraction: items[*].id -> returns array of all ids
     * - Nested wildcards: data.orders[*].items[*].sku
     *
     * @param array $data
     * @param string $path
     * @return mixed|null
     */
    protected function get_value_by_path($data, $path) {
        // Check if path contains wildcard
        if (strpos($path, '[*]') !== false) {
            return $this->get_values_by_wildcard_path($data, $path);
        }

        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            // Handle array index notation like items[0]
            if (preg_match('/^(.+?)\[(\d+)\]$/', $key, $matches)) {
                $array_key = $matches[1];
                $index = (int) $matches[2];

                if (!isset($current[$array_key]) || !is_array($current[$array_key])) {
                    return null;
                }
                $current = $current[$array_key][$index] ?? null;
            } else {
                if (!is_array($current) || !isset($current[$key])) {
                    return null;
                }
                $current = $current[$key];
            }
        }

        return $current;
    }

    /**
     * Get values from array using wildcard path notation
     *
     * Handles paths like:
     * - items[*].id -> all ids from items array
     * - data.orders[*].items[*].sku -> nested wildcard extraction
     *
     * @param array $data Source data
     * @param string $path Path with [*] wildcards
     * @return array Extracted values
     */
    protected function get_values_by_wildcard_path($data, $path) {
        $segments = $this->parse_path_segments($path);
        return $this->extract_wildcard_values($data, $segments);
    }

    /**
     * Parse path into segments, properly handling bracket notation
     *
     * @param string $path
     * @return array Array of segments with type info
     */
    protected function parse_path_segments($path) {
        $segments = [];
        $current = '';
        $in_bracket = false;

        for ($i = 0; $i < strlen($path); $i++) {
            $char = $path[$i];

            if ($char === '[') {
                if ($current !== '') {
                    $segments[] = ['type' => 'key', 'value' => $current];
                    $current = '';
                }
                $in_bracket = true;
                continue;
            }

            if ($char === ']') {
                if ($current === '*') {
                    $segments[] = ['type' => 'wildcard'];
                } elseif (is_numeric($current)) {
                    $segments[] = ['type' => 'index', 'value' => (int) $current];
                }
                $current = '';
                $in_bracket = false;
                continue;
            }

            if ($char === '.' && !$in_bracket) {
                if ($current !== '') {
                    $segments[] = ['type' => 'key', 'value' => $current];
                    $current = '';
                }
                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $segments[] = ['type' => 'key', 'value' => $current];
        }

        return $segments;
    }

    /**
     * Recursively extract values using parsed path segments
     *
     * @param mixed $data Current data context
     * @param array $segments Remaining path segments
     * @return mixed Extracted value(s)
     */
    protected function extract_wildcard_values($data, $segments) {
        if (empty($segments)) {
            return $data;
        }

        $segment = array_shift($segments);

        switch ($segment['type']) {
            case 'key':
                if (!is_array($data) || !isset($data[$segment['value']])) {
                    return null;
                }
                return $this->extract_wildcard_values($data[$segment['value']], $segments);

            case 'index':
                if (!is_array($data) || !isset($data[$segment['value']])) {
                    return null;
                }
                return $this->extract_wildcard_values($data[$segment['value']], $segments);

            case 'wildcard':
                if (!is_array($data)) {
                    return [];
                }

                $results = [];
                foreach ($data as $item) {
                    $value = $this->extract_wildcard_values($item, $segments);

                    // Flatten nested arrays from nested wildcards
                    if (is_array($value) && !empty($segments) && $this->has_remaining_wildcard($segments)) {
                        foreach ($value as $v) {
                            $results[] = $v;
                        }
                    } else {
                        $results[] = $value;
                    }
                }

                // Filter out null values
                return array_values(array_filter($results, function($v) {
                    return $v !== null;
                }));

            default:
                return null;
        }
    }

    /**
     * Check if remaining segments contain a wildcard
     *
     * @param array $segments
     * @return bool
     */
    protected function has_remaining_wildcard($segments) {
        foreach ($segments as $segment) {
            if ($segment['type'] === 'wildcard') {
                return true;
            }
        }
        return false;
    }

    /**
     * Store mapped data for subsequent actions
     *
     * @param array $mapped_data
     * @param array $context
     */
    protected function store_mapped_data($mapped_data, &$context) {
        // Store in context for subsequent actions in the same trigger
        if (!isset($context['mapped_data'])) {
            $context['mapped_data'] = [];
        }
        $context['mapped_data'] = array_merge($context['mapped_data'], $mapped_data);

        // Also fire action for external use
        do_action('super_http_request_response_mapped', $mapped_data, $context);
    }

    /**
     * Get OAuth token from stored credentials
     *
     * @param array $config
     * @param array $context
     * @return string|null
     */
    protected function get_oauth_token($config, $context) {
        $credential_id = $config['oauth_credential_id'] ?? '';

        if (empty($credential_id)) {
            return null;
        }

        // Try to get from SUPER_Automation_OAuth if available
        if (class_exists('SUPER_Automation_OAuth')) {
            $oauth = SUPER_Automation_OAuth::instance();
            $token = $oauth->get_valid_token($credential_id);
            if ($token) {
                return $token;
            }
        }

        // Fallback to SUPER_Automation_Credentials
        if (class_exists('SUPER_Automation_Credentials')) {
            $credentials = new SUPER_Automation_Credentials();
            $token = $credentials->get($credential_id, 'access_token', get_current_user_id());
            if ($token) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Debug log helper
     *
     * @param string $message
     * @param mixed $data
     */
    protected function debug_log($message, $data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Super Forms HTTP Request] %s: %s',
                $message,
                is_array($data) || is_object($data) ? print_r($data, true) : $data
            ));
        }
    }

    /**
     * HTTP requests support and prefer asynchronous execution
     *
     * @return bool
     */
    public function supports_async() {
        return true;
    }

    /**
     * Get execution mode
     *
     * @return string
     */
    public function get_execution_mode() {
        return 'async'; // Prefer async to not block form submission
    }

    /**
     * Get retry configuration
     *
     * External APIs may be temporarily unavailable.
     *
     * @return array
     */
    public function get_retry_config() {
        return [
            'max_retries'   => 5,
            'initial_delay' => 60,     // 1 minute
            'exponential'   => true,
            'max_delay'     => 3600,   // 1 hour max
        ];
    }
}
