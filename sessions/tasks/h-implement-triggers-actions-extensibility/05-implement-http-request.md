---
name: 05-implement-http-request
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-11-20
completed: 2025-11-23
parent: h-implement-triggers-actions-extensibility
---

# Implement HTTP Request Action (Postman-like)

## Problem/Goal
Build a powerful, flexible HTTP request action that allows users to make API calls to any endpoint with full control over headers, authentication, body format, and response handling. This is the Swiss Army knife of integrations.

## Success Criteria
- [ ] Support all HTTP methods (GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS)
- [ ] Multiple authentication methods (Basic, Bearer, API Key, OAuth 2.0)
- [ ] Flexible body formats (JSON, XML, Form Data, Raw)
- [ ] Custom headers with tag replacement
- [ ] Response parsing and field mapping
- [ ] Error handling with retry logic
- [ ] Request/response logging for debugging
- [ ] Import/export request templates

## Implementation Steps

### Step 1: HTTP Request Action Class

**File:** `/src/includes/actions/class-http-request-action.php` (new file)

Create the comprehensive HTTP request action:

```php
class SUPER_HTTP_Request_Action extends SUPER_Trigger_Action_Base {

    public function get_id() {
        return 'http_request';
    }

    public function get_label() {
        return __('HTTP Request', 'super-forms');
    }

    public function get_group() {
        return __('Integrations', 'super-forms');
    }

    public function get_icon() {
        return 'dashicons-admin-links';
    }

    public function supports_scheduling() {
        return true;
    }

    public function get_settings_schema() {
        return array(
            // Request Configuration
            'request_name' => array(
                'type' => 'text',
                'label' => __('Request Name', 'super-forms'),
                'description' => __('Descriptive name for this request', 'super-forms'),
                'placeholder' => 'e.g., Send to CRM API'
            ),

            'method' => array(
                'type' => 'select',
                'label' => __('Method', 'super-forms'),
                'options' => array(
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                    'HEAD' => 'HEAD',
                    'OPTIONS' => 'OPTIONS'
                ),
                'default' => 'POST'
            ),

            'url' => array(
                'type' => 'text',
                'label' => __('URL', 'super-forms'),
                'placeholder' => 'https://api.example.com/endpoint',
                'description' => __('Supports {tags} for dynamic values', 'super-forms'),
                'required' => true
            ),

            // Authentication Tab
            'auth_type' => array(
                'type' => 'select',
                'label' => __('Authentication', 'super-forms'),
                'options' => array(
                    'none' => __('None', 'super-forms'),
                    'basic' => __('Basic Auth', 'super-forms'),
                    'bearer' => __('Bearer Token', 'super-forms'),
                    'api_key' => __('API Key', 'super-forms'),
                    'oauth2' => __('OAuth 2.0', 'super-forms'),
                    'custom' => __('Custom Header', 'super-forms')
                ),
                'default' => 'none'
            ),

            // Basic Auth
            'basic_username' => array(
                'type' => 'text',
                'label' => __('Username', 'super-forms'),
                'condition' => 'auth_type:basic'
            ),
            'basic_password' => array(
                'type' => 'password',
                'label' => __('Password', 'super-forms'),
                'condition' => 'auth_type:basic'
            ),

            // Bearer Token
            'bearer_token' => array(
                'type' => 'text',
                'label' => __('Bearer Token', 'super-forms'),
                'placeholder' => 'Your token here or {tag}',
                'condition' => 'auth_type:bearer'
            ),

            // API Key
            'api_key_location' => array(
                'type' => 'select',
                'label' => __('API Key Location', 'super-forms'),
                'options' => array(
                    'header' => __('Header', 'super-forms'),
                    'query' => __('Query String', 'super-forms')
                ),
                'condition' => 'auth_type:api_key'
            ),
            'api_key_name' => array(
                'type' => 'text',
                'label' => __('Key Name', 'super-forms'),
                'placeholder' => 'X-API-Key',
                'condition' => 'auth_type:api_key'
            ),
            'api_key_value' => array(
                'type' => 'text',
                'label' => __('Key Value', 'super-forms'),
                'condition' => 'auth_type:api_key'
            ),

            // OAuth 2.0
            'oauth_provider' => array(
                'type' => 'select',
                'label' => __('OAuth Provider', 'super-forms'),
                'options' => $this->get_oauth_providers(),
                'condition' => 'auth_type:oauth2'
            ),

            // Headers
            'headers' => array(
                'type' => 'repeater',
                'label' => __('Headers', 'super-forms'),
                'fields' => array(
                    'name' => array(
                        'type' => 'text',
                        'label' => __('Header Name', 'super-forms'),
                        'placeholder' => 'Content-Type'
                    ),
                    'value' => array(
                        'type' => 'text',
                        'label' => __('Header Value', 'super-forms'),
                        'placeholder' => 'application/json'
                    ),
                    'enabled' => array(
                        'type' => 'checkbox',
                        'label' => __('Enabled', 'super-forms'),
                        'default' => true
                    )
                )
            ),

            // Body
            'body_type' => array(
                'type' => 'select',
                'label' => __('Body Type', 'super-forms'),
                'options' => array(
                    'none' => __('None', 'super-forms'),
                    'json' => __('JSON', 'super-forms'),
                    'form' => __('Form Data', 'super-forms'),
                    'xml' => __('XML', 'super-forms'),
                    'raw' => __('Raw', 'super-forms'),
                    'graphql' => __('GraphQL', 'super-forms')
                ),
                'default' => 'json'
            ),

            // JSON Body
            'json_body' => array(
                'type' => 'code',
                'label' => __('JSON Body', 'super-forms'),
                'language' => 'json',
                'condition' => 'body_type:json',
                'description' => __('Use {field_name} to insert form values', 'super-forms'),
                'default' => '{\n  "name": "{name}",\n  "email": "{email}"\n}'
            ),

            // Form Data
            'form_data' => array(
                'type' => 'repeater',
                'label' => __('Form Fields', 'super-forms'),
                'condition' => 'body_type:form',
                'fields' => array(
                    'key' => array(
                        'type' => 'text',
                        'label' => __('Field Name', 'super-forms')
                    ),
                    'value' => array(
                        'type' => 'text',
                        'label' => __('Field Value', 'super-forms'),
                        'placeholder' => '{field_name}'
                    )
                )
            ),

            // XML Body
            'xml_body' => array(
                'type' => 'code',
                'label' => __('XML Body', 'super-forms'),
                'language' => 'xml',
                'condition' => 'body_type:xml',
                'description' => __('Use {field_name} to insert form values', 'super-forms')
            ),

            // GraphQL
            'graphql_query' => array(
                'type' => 'code',
                'label' => __('GraphQL Query', 'super-forms'),
                'language' => 'graphql',
                'condition' => 'body_type:graphql'
            ),
            'graphql_variables' => array(
                'type' => 'code',
                'label' => __('Variables', 'super-forms'),
                'language' => 'json',
                'condition' => 'body_type:graphql'
            ),

            // Response Handling
            'response_format' => array(
                'type' => 'select',
                'label' => __('Expected Response Format', 'super-forms'),
                'options' => array(
                    'json' => 'JSON',
                    'xml' => 'XML',
                    'html' => 'HTML',
                    'text' => 'Plain Text'
                ),
                'default' => 'json'
            ),

            'response_mapping' => array(
                'type' => 'repeater',
                'label' => __('Map Response to Fields', 'super-forms'),
                'description' => __('Extract values from response and save them', 'super-forms'),
                'fields' => array(
                    'response_path' => array(
                        'type' => 'text',
                        'label' => __('Response Path', 'super-forms'),
                        'placeholder' => 'data.user.id',
                        'description' => __('JSON path or XPath', 'super-forms')
                    ),
                    'save_as' => array(
                        'type' => 'text',
                        'label' => __('Save As', 'super-forms'),
                        'placeholder' => 'user_id',
                        'description' => __('Variable name to use in subsequent actions', 'super-forms')
                    )
                )
            ),

            // Error Handling
            'retry_on_failure' => array(
                'type' => 'checkbox',
                'label' => __('Retry on Failure', 'super-forms'),
                'default' => false
            ),
            'retry_count' => array(
                'type' => 'number',
                'label' => __('Retry Count', 'super-forms'),
                'default' => 3,
                'min' => 1,
                'max' => 5,
                'condition' => 'retry_on_failure:true'
            ),
            'retry_delay' => array(
                'type' => 'number',
                'label' => __('Retry Delay (seconds)', 'super-forms'),
                'default' => 5,
                'min' => 1,
                'max' => 60,
                'condition' => 'retry_on_failure:true'
            ),

            // Success Conditions
            'success_status_codes' => array(
                'type' => 'text',
                'label' => __('Success Status Codes', 'super-forms'),
                'default' => '200,201,204',
                'description' => __('Comma-separated HTTP status codes', 'super-forms')
            ),

            // Debugging
            'debug_mode' => array(
                'type' => 'checkbox',
                'label' => __('Debug Mode', 'super-forms'),
                'description' => __('Log full request/response details', 'super-forms'),
                'default' => false
            ),

            // Advanced Options
            'timeout' => array(
                'type' => 'number',
                'label' => __('Timeout (seconds)', 'super-forms'),
                'default' => 30,
                'min' => 1,
                'max' => 300
            ),
            'follow_redirects' => array(
                'type' => 'checkbox',
                'label' => __('Follow Redirects', 'super-forms'),
                'default' => true
            ),
            'ssl_verify' => array(
                'type' => 'checkbox',
                'label' => __('Verify SSL Certificate', 'super-forms'),
                'default' => true
            )
        );
    }

    public function execute($data, $config, $context) {
        $request = $this->build_request($data, $config, $context);
        $attempt = 0;
        $max_attempts = $config['retry_on_failure'] ? $config['retry_count'] : 1;

        while ($attempt < $max_attempts) {
            $attempt++;

            if ($config['debug_mode']) {
                $this->debug_log('Request attempt ' . $attempt, $request);
            }

            $response = $this->send_request($request, $config);

            if ($config['debug_mode']) {
                $this->debug_log('Response received', $response);
            }

            if ($this->is_success($response, $config)) {
                // Process response mapping
                $mapped_data = $this->map_response($response, $config);

                // Store mapped data in context for subsequent actions
                if (!empty($mapped_data)) {
                    $this->store_mapped_data($mapped_data, $context);
                }

                return $this->log_result(true, __('HTTP request successful', 'super-forms'), array(
                    'status_code' => wp_remote_retrieve_response_code($response),
                    'mapped_data' => $mapped_data
                ));
            }

            // If not last attempt, wait before retry
            if ($attempt < $max_attempts) {
                sleep($config['retry_delay']);
            }
        }

        // All attempts failed
        return $this->log_result(false, __('HTTP request failed after retries', 'super-forms'), array(
            'last_error' => $this->get_error_message($response),
            'attempts' => $attempt
        ));
    }

    private function build_request($data, $config, $context) {
        // Build URL with tag replacement
        $url = $this->replace_tags($config['url'], $data, $context);

        // Add query parameters for API key if needed
        if ($config['auth_type'] === 'api_key' && $config['api_key_location'] === 'query') {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . $config['api_key_name'] . '=' . urlencode($config['api_key_value']);
        }

        // Build headers
        $headers = $this->build_headers($data, $config, $context);

        // Build body
        $body = $this->build_body($data, $config, $context);

        return array(
            'url' => $url,
            'method' => $config['method'],
            'headers' => $headers,
            'body' => $body,
            'timeout' => $config['timeout'],
            'redirection' => $config['follow_redirects'] ? 5 : 0,
            'sslverify' => $config['ssl_verify']
        );
    }

    private function build_headers($data, $config, $context) {
        $headers = array();

        // Add custom headers
        if (!empty($config['headers'])) {
            foreach ($config['headers'] as $header) {
                if ($header['enabled']) {
                    $name = $this->replace_tags($header['name'], $data, $context);
                    $value = $this->replace_tags($header['value'], $data, $context);
                    $headers[$name] = $value;
                }
            }
        }

        // Add authentication headers
        switch ($config['auth_type']) {
            case 'basic':
                $headers['Authorization'] = 'Basic ' . base64_encode(
                    $config['basic_username'] . ':' . $config['basic_password']
                );
                break;

            case 'bearer':
                $token = $this->replace_tags($config['bearer_token'], $data, $context);
                $headers['Authorization'] = 'Bearer ' . $token;
                break;

            case 'api_key':
                if ($config['api_key_location'] === 'header') {
                    $headers[$config['api_key_name']] = $config['api_key_value'];
                }
                break;

            case 'oauth2':
                $token = $this->get_oauth_token($config['oauth_provider']);
                if ($token) {
                    $headers['Authorization'] = 'Bearer ' . $token;
                }
                break;
        }

        // Add content type based on body type
        if (!isset($headers['Content-Type'])) {
            switch ($config['body_type']) {
                case 'json':
                case 'graphql':
                    $headers['Content-Type'] = 'application/json';
                    break;
                case 'xml':
                    $headers['Content-Type'] = 'application/xml';
                    break;
                case 'form':
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    break;
            }
        }

        return $headers;
    }

    private function build_body($data, $config, $context) {
        switch ($config['body_type']) {
            case 'json':
                $json = $this->replace_tags($config['json_body'], $data, $context);
                return $json;

            case 'form':
                $form_data = array();
                foreach ($config['form_data'] as $field) {
                    $key = $this->replace_tags($field['key'], $data, $context);
                    $value = $this->replace_tags($field['value'], $data, $context);
                    $form_data[$key] = $value;
                }
                return http_build_query($form_data);

            case 'xml':
                return $this->replace_tags($config['xml_body'], $data, $context);

            case 'graphql':
                $query = $this->replace_tags($config['graphql_query'], $data, $context);
                $variables = $this->replace_tags($config['graphql_variables'], $data, $context);
                return json_encode(array(
                    'query' => $query,
                    'variables' => json_decode($variables, true)
                ));

            case 'raw':
                return $this->replace_tags($config['raw_body'], $data, $context);

            default:
                return null;
        }
    }

    private function send_request($request, $config) {
        switch ($request['method']) {
            case 'GET':
                return wp_remote_get($request['url'], $request);
            case 'POST':
                return wp_remote_post($request['url'], $request);
            case 'HEAD':
                return wp_remote_head($request['url'], $request);
            default:
                return wp_remote_request($request['url'], $request);
        }
    }

    private function is_success($response, $config) {
        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $success_codes = array_map('trim', explode(',', $config['success_status_codes']));

        return in_array($status_code, $success_codes);
    }

    private function map_response($response, $config) {
        if (empty($config['response_mapping'])) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $mapped = array();

        // Parse response based on format
        switch ($config['response_format']) {
            case 'json':
                $data = json_decode($body, true);
                if (!$data) return $mapped;

                foreach ($config['response_mapping'] as $mapping) {
                    $value = $this->get_json_path($data, $mapping['response_path']);
                    if ($value !== null) {
                        $mapped[$mapping['save_as']] = $value;
                    }
                }
                break;

            case 'xml':
                $xml = simplexml_load_string($body);
                if (!$xml) return $mapped;

                foreach ($config['response_mapping'] as $mapping) {
                    $nodes = $xml->xpath($mapping['response_path']);
                    if (!empty($nodes)) {
                        $mapped[$mapping['save_as']] = (string) $nodes[0];
                    }
                }
                break;
        }

        return $mapped;
    }

    private function get_json_path($data, $path) {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    private function debug_log($message, $data) {
        if (defined('SUPER_TRIGGERS_DEBUG') && SUPER_TRIGGERS_DEBUG) {
            error_log('[HTTP Request Debug] ' . $message . ': ' . print_r($data, true));
        }
    }
}
```

### Step 2: Request Templates System

**File:** `/src/includes/class-http-request-templates.php` (new file)

Create template management for common API integrations:

```php
class SUPER_HTTP_Request_Templates {
    private static $templates = array();

    public static function init() {
        self::register_default_templates();
        add_filter('super_http_request_templates', array(__CLASS__, 'get_templates'));
    }

    private static function register_default_templates() {
        // Slack Webhook
        self::$templates['slack_webhook'] = array(
            'name' => 'Slack Webhook',
            'description' => 'Send message to Slack channel',
            'config' => array(
                'method' => 'POST',
                'url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
                'headers' => array(
                    array('name' => 'Content-Type', 'value' => 'application/json', 'enabled' => true)
                ),
                'body_type' => 'json',
                'json_body' => '{"text": "New form submission from {name}", "blocks": [{"type": "section", "text": {"type": "mrkdwn", "text": "*Email:* {email}\n*Message:* {message}"}}]}'
            )
        );

        // Mailchimp Subscribe
        self::$templates['mailchimp'] = array(
            'name' => 'Mailchimp Subscribe',
            'description' => 'Add subscriber to Mailchimp list',
            'config' => array(
                'method' => 'POST',
                'url' => 'https://us1.api.mailchimp.com/3.0/lists/{list_id}/members',
                'auth_type' => 'api_key',
                'api_key_location' => 'header',
                'api_key_name' => 'Authorization',
                'api_key_value' => 'apikey YOUR_API_KEY',
                'body_type' => 'json',
                'json_body' => '{"email_address": "{email}", "status": "subscribed", "merge_fields": {"FNAME": "{first_name}", "LNAME": "{last_name}"}}'
            )
        );

        // Zapier Webhook
        self::$templates['zapier'] = array(
            'name' => 'Zapier Webhook',
            'description' => 'Trigger Zapier automation',
            'config' => array(
                'method' => 'POST',
                'url' => 'https://hooks.zapier.com/hooks/catch/YOUR_HOOK_ID/',
                'body_type' => 'json',
                'json_body' => '{"form_id": "{form_id}", "entry_id": "{entry_id}", "data": {form_data}}'
            )
        );

        // Custom API with Bearer Token
        self::$templates['api_bearer'] = array(
            'name' => 'API with Bearer Token',
            'description' => 'Generic API call with Bearer authentication',
            'config' => array(
                'method' => 'POST',
                'url' => 'https://api.example.com/endpoint',
                'auth_type' => 'bearer',
                'bearer_token' => 'YOUR_TOKEN_HERE',
                'body_type' => 'json',
                'json_body' => '{}'
            )
        );
    }

    public static function get_templates() {
        return apply_filters('super_http_request_templates_list', self::$templates);
    }

    public static function import_template($template_id) {
        if (isset(self::$templates[$template_id])) {
            return self::$templates[$template_id]['config'];
        }
        return null;
    }
}
```

### Step 3: Request Builder UI

**File:** `/src/assets/js/admin/http-request-builder.js` (new file)

Create interactive request builder:

```javascript
(function($) {
    'use strict';

    window.SuperHTTPRequestBuilder = {
        init: function() {
            this.bindEvents();
            this.initCodeEditors();
            this.loadTemplates();
        },

        bindEvents: function() {
            // Template selector
            $(document).on('change', '.super-http-template-select', this.loadTemplate);

            // Test request button
            $(document).on('click', '.super-test-request', this.testRequest);

            // Import from cURL
            $(document).on('click', '.super-import-curl', this.showCurlImport);

            // Export as cURL
            $(document).on('click', '.super-export-curl', this.exportAsCurl);

            // Body type change
            $(document).on('change', '[name="body_type"]', this.toggleBodyFields);

            // Auth type change
            $(document).on('change', '[name="auth_type"]', this.toggleAuthFields);
        },

        testRequest: function(e) {
            e.preventDefault();

            var $button = $(this);
            var config = SuperHTTPRequestBuilder.getConfiguration();

            $button.prop('disabled', true).text('Testing...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_test_http_request',
                    config: config,
                    nonce: super_forms_ajax.nonce
                },
                success: function(response) {
                    SuperHTTPRequestBuilder.showTestResults(response);
                },
                error: function(xhr) {
                    alert('Test failed: ' + xhr.responseText);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Request');
                }
            });
        },

        showTestResults: function(response) {
            var modal = `
                <div class="super-modal">
                    <div class="super-modal-content">
                        <h2>Request Test Results</h2>
                        <div class="test-results">
                            <h3>Request</h3>
                            <pre>${JSON.stringify(response.request, null, 2)}</pre>

                            <h3>Response</h3>
                            <div class="response-status">
                                Status: <span class="status-${response.success ? 'success' : 'error'}">
                                    ${response.status_code} ${response.status_text}
                                </span>
                            </div>

                            <h4>Headers</h4>
                            <pre>${JSON.stringify(response.headers, null, 2)}</pre>

                            <h4>Body</h4>
                            <pre>${response.body}</pre>

                            ${response.mapped_data ? `
                                <h4>Mapped Data</h4>
                                <pre>${JSON.stringify(response.mapped_data, null, 2)}</pre>
                            ` : ''}
                        </div>
                        <button class="button close-modal">Close</button>
                    </div>
                </div>
            `;

            $('body').append(modal);
        },

        getConfiguration: function() {
            var config = {};

            // Collect all form fields
            $('.super-http-request-settings').find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();

                if ($field.attr('type') === 'checkbox') {
                    value = $field.is(':checked');
                }

                config[name] = value;
            });

            // Collect repeater fields
            config.headers = this.collectRepeaterData('.headers-repeater');
            config.form_data = this.collectRepeaterData('.form-data-repeater');
            config.response_mapping = this.collectRepeaterData('.response-mapping-repeater');

            return config;
        },

        collectRepeaterData: function(selector) {
            var data = [];
            $(selector).find('.repeater-item').each(function() {
                var item = {};
                $(this).find('input, select').each(function() {
                    var name = $(this).data('field');
                    item[name] = $(this).val();
                });
                data.push(item);
            });
            return data;
        },

        exportAsCurl: function() {
            var config = SuperHTTPRequestBuilder.getConfiguration();
            var curl = SuperHTTPRequestBuilder.buildCurlCommand(config);

            var $modal = $(`
                <div class="super-modal">
                    <div class="super-modal-content">
                        <h2>Export as cURL</h2>
                        <textarea class="curl-export" readonly>${curl}</textarea>
                        <button class="button copy-curl">Copy to Clipboard</button>
                        <button class="button close-modal">Close</button>
                    </div>
                </div>
            `);

            $('body').append($modal);
        },

        buildCurlCommand: function(config) {
            var curl = `curl -X ${config.method} "${config.url}"`;

            // Add headers
            if (config.headers && config.headers.length) {
                config.headers.forEach(function(header) {
                    if (header.enabled) {
                        curl += ` \\\n  -H "${header.name}: ${header.value}"`;
                    }
                });
            }

            // Add authentication
            if (config.auth_type === 'basic') {
                curl += ` \\\n  -u "${config.basic_username}:${config.basic_password}"`;
            } else if (config.auth_type === 'bearer') {
                curl += ` \\\n  -H "Authorization: Bearer ${config.bearer_token}"`;
            }

            // Add body
            if (config.body_type !== 'none' && config[config.body_type + '_body']) {
                curl += ` \\\n  -d '${config[config.body_type + '_body']}'`;
            }

            return curl;
        }
    };

    $(document).ready(function() {
        SuperHTTPRequestBuilder.init();
    });

})(jQuery);
```

### Step 4: Testing Framework

Create comprehensive testing capabilities:

```php
class SUPER_HTTP_Request_Tester {

    public static function test_request($config) {
        // Create mock data for testing
        $test_data = array(
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
            'form_id' => '12345',
            'entry_id' => '67890'
        );

        $test_context = array(
            'form_id' => 12345,
            'user_id' => get_current_user_id(),
            'trigger_id' => 'test'
        );

        // Execute the request
        $action = new SUPER_HTTP_Request_Action();
        $request = $action->build_request($test_data, $config, $test_context);

        // Send the request
        $response = $action->send_request($request, $config);

        // Format response for display
        return array(
            'request' => $request,
            'response' => array(
                'success' => !is_wp_error($response),
                'status_code' => wp_remote_retrieve_response_code($response),
                'status_text' => wp_remote_retrieve_response_message($response),
                'headers' => wp_remote_retrieve_headers($response)->getAll(),
                'body' => wp_remote_retrieve_body($response),
                'mapped_data' => $action->map_response($response, $config)
            )
        );
    }

    // AJAX handler
    public static function ajax_test_request() {
        check_ajax_referer('super_forms_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $config = $_POST['config'];
        $result = self::test_request($config);

        wp_send_json_success($result);
    }
}

add_action('wp_ajax_super_test_http_request', array('SUPER_HTTP_Request_Tester', 'ajax_test_request'));
```

## Context Manifest
<!-- To be added by context-gathering agent -->

## User Notes
- This is the most powerful and flexible action in the system
- Must handle various authentication methods securely
- Response mapping is crucial for chaining actions
- Debug mode should provide comprehensive logging
- Consider rate limiting for external API calls
- Template system should be extensible by other plugins
- Import/export functionality enables sharing configurations

## Work Log

### 2025-11-20
- Subtask created with comprehensive HTTP Request action implementation plan

### 2025-11-23
- **Phase 5 COMPLETE** - Full implementation committed

**Files Created:**
- `/src/includes/triggers/actions/class-action-http-request.php` (~1800 lines)
- `/src/includes/triggers/class-http-request-templates.php` (~600 lines)
- `/tests/triggers/actions/test-action-http-request.php`
- `/tests/triggers/test-http-request-templates.php`

**Core Features Implemented:**
- All HTTP methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS
- Authentication: None, Basic, Bearer, API Key, OAuth 2.0, Custom Header
- Body types: None, JSON, Form Data, XML, Raw, GraphQL, Auto
- Response parsing with JSON/XML path mapping to context variables
- Tag replacement in URL, headers, body, and auth parameters
- Retry mechanism with configurable attempts and delays
- Debug mode for development/troubleshooting
- 15 pre-built templates: Slack, Discord, Teams, Zapier, Make, n8n, Mailchimp, SendGrid, HubSpot, Salesforce, Airtable, Notion, Google Sheets, Telegram, Generic REST API

**Dynamic Data Enhancements (continued session):**

1. **Response Mapping Wildcards**
   - `items[*].id` extracts all IDs from array
   - `orders[*].items[*].sku` supports nested wildcards
   - Helper methods: `get_values_by_wildcard_path()`, `parse_path_segments()`, `extract_wildcard_values()`

2. **Pipe Modifiers (20+ modifiers)**
   - Basic: `|json`, `|first`, `|last`, `|count`, `|keys`, `|values`
   - Array: `|join`, `|flatten`, `|unique`, `|sort`, `|reverse`, `|slice:start:length`
   - File: `|files`, `|file_base64`, `|file_meta`
   - Signature/Base64: `|base64_data`, `|base64_mime`, `|base64_ext`
   - Attachment: `|attachment_url`, `|attachment_base64`, `|attachment_meta`

3. **Repeater-to-API Body Serialization**
   - `{repeater:field_name}` converts SF repeater format to JSON array
   - `{repeater:field_name|fields:a,b,c}` for field filtering
   - Nested repeater support (5-level depth limit)
   - Helper methods: `process_repeater_tags()`, `convert_repeater_to_array()`, `is_nested_repeater()`

4. **File/URL Helpers**
   - `url_to_base64()` - Fetch URL and encode (5MB limit, HEAD check first)
   - `extract_file_meta()` - Parse URL for filename/extension/basename
   - `mime_to_extension()` - Map 30+ MIME types to file extensions

**Test Results:**
- PHPUnit: 251 tests, 870 assertions, 0 failures

**Success Criteria Status:**
- [x] Support all HTTP methods (GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS)
- [x] Multiple authentication methods (Basic, Bearer, API Key, OAuth 2.0)
- [x] Flexible body formats (JSON, XML, Form Data, Raw)
- [x] Custom headers with tag replacement
- [x] Response parsing and field mapping
- [x] Error handling with retry logic
- [x] Request/response logging for debugging
- [x] Import/export request templates (via SUPER_HTTP_Request_Templates class)