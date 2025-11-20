---
name: 08-implement-realtime-interactions
parent: h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
---

# Subtask 08: Implement Real-time Form Interactions

## Overview

Implement client-side event triggers for real-time form interactions including field validation, dynamic content loading, duplicate detection, and API-based lookups. This addresses Epic 3: Real-time Form Interactions.

## Background

Current limitations:
- No real-time validation via external APIs
- No dynamic field updates based on user input
- No duplicate detection during typing
- No client-side trigger events (keypress, blur, change)
- No debouncing for rapid user inputs

## Requirements

### Client-Side Event System

Support the following JavaScript events:
- `field_keyup` - Triggered on keyup with debouncing
- `field_change` - Triggered when field value changes
- `field_blur` - Triggered when field loses focus
- `field_focus` - Triggered when field gains focus
- `button_click` - Custom button triggers
- `form_step_change` - Multi-step form navigation
- `calculation_complete` - After calculations update

### Real-time Actions

1. **API Validation**
   - Validate email addresses (deliverability)
   - Check username availability
   - Verify phone numbers
   - Validate addresses via geocoding

2. **Duplicate Detection**
   - Check for existing submissions
   - Prevent duplicate registrations
   - Real-time conflict resolution

3. **Dynamic Content**
   - Load options based on selection
   - Update field visibility/values
   - Populate from external APIs
   - Auto-complete suggestions

4. **Performance Requirements**
   - Debounce rapid inputs (configurable delay)
   - Cache API responses
   - Handle network failures gracefully
   - Provide loading indicators

## Implementation Steps

### Step 1: Client-Side Event Registration

Create JavaScript event system for real-time triggers:

```javascript
// super-forms-realtime.js
(function($) {
    'use strict';

    class SuperFormsRealtime {
        constructor() {
            this.triggers = {};
            this.cache = new Map();
            this.debounceTimers = {};
            this.init();
        }

        init() {
            // Register global event handlers
            $(document).on('keyup', '.super-field input, .super-field textarea',
                this.handleKeyUp.bind(this));
            $(document).on('change', '.super-field select, .super-field input[type="checkbox"], .super-field input[type="radio"]',
                this.handleChange.bind(this));
            $(document).on('blur', '.super-field input, .super-field textarea',
                this.handleBlur.bind(this));
            $(document).on('focus', '.super-field input, .super-field textarea',
                this.handleFocus.bind(this));
            $(document).on('click', '.super-button[data-trigger="true"]',
                this.handleButtonClick.bind(this));

            // Listen for trigger registration from backend
            $(document).on('super:register_trigger', this.registerTrigger.bind(this));
        }

        registerTrigger(event, data) {
            const { fieldName, eventType, actionId, config } = data;

            if (!this.triggers[fieldName]) {
                this.triggers[fieldName] = {};
            }

            if (!this.triggers[fieldName][eventType]) {
                this.triggers[fieldName][eventType] = [];
            }

            this.triggers[fieldName][eventType].push({
                actionId,
                config: {
                    debounce: config.debounce || 300,
                    cache: config.cache !== false,
                    showLoader: config.showLoader !== false,
                    errorHandling: config.errorHandling || 'inline',
                    ...config
                }
            });
        }

        handleKeyUp(event) {
            const $field = $(event.target);
            const fieldName = $field.closest('.super-field').data('field-name');
            const triggers = this.getTriggers(fieldName, 'field_keyup');

            if (triggers.length === 0) return;

            // Clear existing debounce timer
            if (this.debounceTimers[fieldName]) {
                clearTimeout(this.debounceTimers[fieldName]);
            }

            // Get debounce delay from first trigger config
            const debounceDelay = triggers[0].config.debounce;

            // Set new debounce timer
            this.debounceTimers[fieldName] = setTimeout(() => {
                this.executeTriggers($field, triggers, {
                    value: $field.val(),
                    event: 'field_keyup'
                });
            }, debounceDelay);
        }

        handleChange(event) {
            const $field = $(event.target);
            const fieldName = $field.closest('.super-field').data('field-name');
            const triggers = this.getTriggers(fieldName, 'field_change');

            if (triggers.length === 0) return;

            this.executeTriggers($field, triggers, {
                value: $field.val(),
                event: 'field_change'
            });
        }

        handleBlur(event) {
            const $field = $(event.target);
            const fieldName = $field.closest('.super-field').data('field-name');
            const triggers = this.getTriggers(fieldName, 'field_blur');

            if (triggers.length === 0) return;

            this.executeTriggers($field, triggers, {
                value: $field.val(),
                event: 'field_blur'
            });
        }

        handleFocus(event) {
            const $field = $(event.target);
            const fieldName = $field.closest('.super-field').data('field-name');
            const triggers = this.getTriggers(fieldName, 'field_focus');

            if (triggers.length === 0) return;

            this.executeTriggers($field, triggers, {
                value: $field.val(),
                event: 'field_focus'
            });
        }

        handleButtonClick(event) {
            event.preventDefault();
            const $button = $(event.currentTarget);
            const buttonId = $button.data('button-id');
            const triggers = this.getTriggers(buttonId, 'button_click');

            if (triggers.length === 0) return;

            this.executeTriggers($button, triggers, {
                buttonId,
                event: 'button_click'
            });
        }

        getTriggers(fieldName, eventType) {
            return (this.triggers[fieldName] && this.triggers[fieldName][eventType]) || [];
        }

        async executeTriggers($element, triggers, context) {
            const $form = $element.closest('form');
            const formData = this.collectFormData($form);

            for (const trigger of triggers) {
                try {
                    await this.executeAction($element, trigger, {
                        ...context,
                        formData
                    });
                } catch (error) {
                    this.handleError($element, error, trigger.config);
                }
            }
        }

        async executeAction($element, trigger, context) {
            const { actionId, config } = trigger;

            // Check cache if enabled
            if (config.cache) {
                const cacheKey = this.getCacheKey(actionId, context);
                if (this.cache.has(cacheKey)) {
                    const cached = this.cache.get(cacheKey);
                    if (Date.now() - cached.timestamp < config.cacheTimeout || 60000) {
                        this.processResponse($element, cached.data, config);
                        return;
                    }
                }
            }

            // Show loader if configured
            if (config.showLoader) {
                this.showLoader($element);
            }

            try {
                const response = await $.ajax({
                    url: super_forms_realtime.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'super_realtime_trigger',
                        nonce: super_forms_realtime.nonce,
                        action_id: actionId,
                        context: JSON.stringify(context)
                    }
                });

                // Cache successful response
                if (config.cache && response.success) {
                    const cacheKey = this.getCacheKey(actionId, context);
                    this.cache.set(cacheKey, {
                        timestamp: Date.now(),
                        data: response
                    });
                }

                this.processResponse($element, response, config);

            } finally {
                if (config.showLoader) {
                    this.hideLoader($element);
                }
            }
        }

        processResponse($element, response, config) {
            if (!response.success) {
                this.handleError($element, response.error || 'Unknown error', config);
                return;
            }

            // Process different response types
            const { action, data } = response;

            switch (action) {
                case 'validate':
                    this.handleValidation($element, data);
                    break;

                case 'populate':
                    this.handlePopulate($element, data);
                    break;

                case 'show_hide':
                    this.handleVisibility(data);
                    break;

                case 'update_options':
                    this.handleUpdateOptions($element, data);
                    break;

                case 'show_message':
                    this.handleMessage($element, data);
                    break;

                case 'custom':
                    // Allow custom handlers
                    $(document).trigger('super:realtime_response', {
                        element: $element,
                        data: data
                    });
                    break;
            }
        }

        handleValidation($element, data) {
            const $wrapper = $element.closest('.super-field');

            // Remove existing validation
            $wrapper.removeClass('super-error super-valid');
            $wrapper.find('.super-error-msg').remove();

            if (data.valid) {
                $wrapper.addClass('super-valid');
                if (data.message) {
                    $wrapper.append(`<div class="super-success-msg">${data.message}</div>`);
                }
            } else {
                $wrapper.addClass('super-error');
                if (data.message) {
                    $wrapper.append(`<div class="super-error-msg">${data.message}</div>`);
                }
            }
        }

        handlePopulate($element, data) {
            const $form = $element.closest('form');

            Object.keys(data.fields).forEach(fieldName => {
                const value = data.fields[fieldName];
                const $field = $form.find(`[name="${fieldName}"]`);

                if ($field.length) {
                    $field.val(value).trigger('change');
                }
            });
        }

        handleVisibility(data) {
            data.show && data.show.forEach(fieldName => {
                $(`.super-field[data-field-name="${fieldName}"]`).show();
            });

            data.hide && data.hide.forEach(fieldName => {
                $(`.super-field[data-field-name="${fieldName}"]`).hide();
            });
        }

        handleUpdateOptions($element, data) {
            const $select = $element.is('select') ? $element : $element.find('select');

            if (!$select.length) return;

            // Store current value
            const currentValue = $select.val();

            // Clear and update options
            $select.empty();

            if (data.placeholder) {
                $select.append(`<option value="">${data.placeholder}</option>`);
            }

            data.options.forEach(option => {
                const selected = option.value === currentValue ? 'selected' : '';
                $select.append(`<option value="${option.value}" ${selected}>${option.label}</option>`);
            });

            $select.trigger('change');
        }

        handleMessage($element, data) {
            const $wrapper = $element.closest('.super-field');

            // Remove existing messages
            $wrapper.find('.super-realtime-message').remove();

            if (data.message) {
                const messageClass = data.type || 'info';
                $wrapper.append(`<div class="super-realtime-message super-message-${messageClass}">${data.message}</div>`);

                if (data.autoHide) {
                    setTimeout(() => {
                        $wrapper.find('.super-realtime-message').fadeOut(() => {
                            $(this).remove();
                        });
                    }, data.autoHide);
                }
            }
        }

        handleError($element, error, config) {
            const errorMessage = typeof error === 'string' ? error : error.message;

            switch (config.errorHandling) {
                case 'inline':
                    this.handleMessage($element, {
                        message: errorMessage,
                        type: 'error'
                    });
                    break;

                case 'toast':
                    this.showToast(errorMessage, 'error');
                    break;

                case 'console':
                    console.error('Super Forms Realtime Error:', error);
                    break;

                case 'silent':
                    // Do nothing
                    break;
            }
        }

        showLoader($element) {
            const $wrapper = $element.closest('.super-field');
            $wrapper.addClass('super-loading');
            $wrapper.append('<div class="super-loader"></div>');
        }

        hideLoader($element) {
            const $wrapper = $element.closest('.super-field');
            $wrapper.removeClass('super-loading');
            $wrapper.find('.super-loader').remove();
        }

        getCacheKey(actionId, context) {
            return `${actionId}_${JSON.stringify(context)}`;
        }

        collectFormData($form) {
            const data = {};
            $form.find('.super-field').each((i, field) => {
                const $field = $(field);
                const fieldName = $field.data('field-name');
                const $input = $field.find('input, select, textarea').first();

                if ($input.length && fieldName) {
                    data[fieldName] = $input.val();
                }
            });
            return data;
        }

        showToast(message, type = 'info') {
            // Implementation depends on toast library
            if (window.toastr) {
                toastr[type](message);
            } else {
                alert(message);
            }
        }
    }

    // Initialize on document ready
    $(document).ready(() => {
        window.SuperFormsRealtime = new SuperFormsRealtime();
    });

})(jQuery);
```

### Step 2: Server-Side AJAX Handler

Implement the server-side handler for real-time actions:

```php
class SUPER_Realtime_Handler {

    public function __construct() {
        add_action('wp_ajax_super_realtime_trigger', array($this, 'handle_realtime_trigger'));
        add_action('wp_ajax_nopriv_super_realtime_trigger', array($this, 'handle_realtime_trigger'));
    }

    public function handle_realtime_trigger() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'super_realtime_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $action_id = sanitize_text_field($_POST['action_id']);
        $context = json_decode(stripslashes($_POST['context']), true);

        // Get the action configuration
        $action = $this->get_action_config($action_id);

        if (!$action) {
            wp_send_json_error('Action not found');
        }

        // Check if user has permission
        if (!$this->check_permissions($action, $context)) {
            wp_send_json_error('Permission denied');
        }

        try {
            // Execute the action
            $result = $this->execute_action($action, $context);

            // Log the execution (using Data Access Layer)
            $this->log_execution($action_id, $context, $result);

            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function execute_action($action, $context) {
        $type = $action['type'];
        $config = $action['config'];

        switch ($type) {
            case 'email_validation':
                return $this->validate_email($context['value'], $config);

            case 'duplicate_check':
                return $this->check_duplicate($context['value'], $config);

            case 'api_lookup':
                return $this->api_lookup($context['value'], $config);

            case 'dynamic_populate':
                return $this->dynamic_populate($context, $config);

            case 'conditional_logic':
                return $this->evaluate_conditions($context, $config);

            case 'custom_action':
                return $this->execute_custom($action, $context);

            default:
                throw new Exception('Unknown action type: ' . $type);
        }
    }

    private function validate_email($email, $config) {
        // Basic validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array(
                'action' => 'validate',
                'data' => array(
                    'valid' => false,
                    'message' => __('Invalid email format', 'super-forms')
                )
            );
        }

        // Advanced validation (DNS, deliverability)
        if (!empty($config['check_dns'])) {
            $domain = substr(strrchr($email, "@"), 1);
            if (!checkdnsrr($domain, 'MX')) {
                return array(
                    'action' => 'validate',
                    'data' => array(
                        'valid' => false,
                        'message' => __('Email domain does not exist', 'super-forms')
                    )
                );
            }
        }

        // External API validation if configured
        if (!empty($config['api_endpoint'])) {
            $response = wp_remote_post($config['api_endpoint'], array(
                'body' => json_encode(array('email' => $email)),
                'headers' => array('Content-Type' => 'application/json')
            ));

            if (is_wp_error($response)) {
                throw new Exception('API validation failed');
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            return array(
                'action' => 'validate',
                'data' => array(
                    'valid' => $body['valid'] ?? true,
                    'message' => $body['message'] ?? ''
                )
            );
        }

        return array(
            'action' => 'validate',
            'data' => array(
                'valid' => true,
                'message' => __('Email is valid', 'super-forms')
            )
        );
    }

    private function check_duplicate($value, $config) {
        global $wpdb;

        $field_name = $config['field_name'];
        $form_id = $config['form_id'];

        // Use Data Access Layer to check for duplicates
        $existing = SUPER_Data_Access::find_entries_by_field(
            $form_id,
            $field_name,
            $value
        );

        if (!empty($existing)) {
            return array(
                'action' => 'validate',
                'data' => array(
                    'valid' => false,
                    'message' => sprintf(
                        __('This %s is already registered', 'super-forms'),
                        $field_name
                    )
                )
            );
        }

        return array(
            'action' => 'validate',
            'data' => array(
                'valid' => true,
                'message' => ''
            )
        );
    }

    private function api_lookup($value, $config) {
        $endpoint = $config['endpoint'];
        $method = $config['method'] ?? 'GET';
        $headers = $config['headers'] ?? array();

        // Replace placeholders in endpoint
        $endpoint = str_replace('{value}', urlencode($value), $endpoint);

        // Add authentication if configured
        if (!empty($config['auth_type'])) {
            $headers = $this->add_authentication($headers, $config);
        }

        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => $config['timeout'] ?? 10
        );

        if ($method === 'POST' || $method === 'PUT') {
            $args['body'] = json_encode($config['body'] ?? array('value' => $value));
        }

        $response = wp_remote_request($endpoint, $args);

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Map response to action
        return $this->map_api_response($body, $config['response_mapping'] ?? array());
    }

    private function dynamic_populate($context, $config) {
        $source = $config['source'];

        switch ($source) {
            case 'database':
                return $this->populate_from_database($context, $config);

            case 'api':
                return $this->populate_from_api($context, $config);

            case 'user_meta':
                return $this->populate_from_user_meta($context, $config);

            default:
                throw new Exception('Unknown populate source: ' . $source);
        }
    }

    private function populate_from_database($context, $config) {
        global $wpdb;

        $query = $config['query'];
        $params = $config['params'] ?? array();

        // Replace placeholders with context values
        foreach ($context['formData'] as $key => $value) {
            $query = str_replace('{' . $key . '}', '%s', $query);
            $params[] = $value;
        }

        $results = $wpdb->get_row($wpdb->prepare($query, $params), ARRAY_A);

        if (!$results) {
            return array(
                'action' => 'populate',
                'data' => array('fields' => array())
            );
        }

        // Map database fields to form fields
        $mapped = array();
        foreach ($config['field_mapping'] as $db_field => $form_field) {
            if (isset($results[$db_field])) {
                $mapped[$form_field] = $results[$db_field];
            }
        }

        return array(
            'action' => 'populate',
            'data' => array('fields' => $mapped)
        );
    }

    private function log_execution($action_id, $context, $result) {
        // Use Data Access Layer if entry_id exists
        if (!empty($context['entry_id'])) {
            SUPER_Data_Access::update_entry_data($context['entry_id'], array(
                '_realtime_actions' => array(
                    'action_id' => $action_id,
                    'timestamp' => current_time('mysql'),
                    'context' => $context,
                    'result' => $result
                )
            ));
        }

        // Also log to dedicated realtime table for analytics
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'super_realtime_logs',
            array(
                'action_id' => $action_id,
                'event_type' => $context['event'] ?? '',
                'field_value' => $context['value'] ?? '',
                'response' => json_encode($result),
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'executed_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }

    private function add_authentication(&$headers, $config) {
        switch ($config['auth_type']) {
            case 'bearer':
                $token = $this->get_secure_credential($config['token_key']);
                $headers['Authorization'] = 'Bearer ' . $token;
                break;

            case 'api_key':
                $key = $this->get_secure_credential($config['api_key']);
                $headers[$config['header_name'] ?? 'X-API-Key'] = $key;
                break;

            case 'oauth2':
                $token = $this->get_oauth_token($config);
                $headers['Authorization'] = 'Bearer ' . $token;
                break;
        }

        return $headers;
    }

    private function get_secure_credential($key) {
        // Retrieve from secure storage
        $credentials = get_option('super_triggers_credentials', array());

        if (isset($credentials[$key])) {
            // Decrypt if encrypted
            return SUPER_Trigger_Security::decrypt($credentials[$key]);
        }

        throw new Exception('Credential not found: ' . $key);
    }
}

// Initialize the handler
new SUPER_Realtime_Handler();
```

### Step 3: Form Builder Integration

Add real-time trigger configuration to the form builder:

```javascript
// Extension to form builder for real-time triggers
SUPER.realtime_builder = {

    init: function() {
        // Add realtime tab to element settings
        $(document).on('click', '.super-element-settings', function() {
            SUPER.realtime_builder.addRealtimeTab($(this));
        });
    },

    addRealtimeTab: function($element) {
        const $tabs = $element.find('.super-tabs');

        // Add tab if not exists
        if (!$tabs.find('[data-tab="realtime"]').length) {
            $tabs.append('<li data-tab="realtime">Real-time Actions</li>');

            // Add tab content
            const $content = $element.find('.super-tab-content');
            $content.append(this.getRealtimeContent());
        }
    },

    getRealtimeContent: function() {
        return `
            <div class="super-tab-realtime" style="display:none;">
                <h3>Real-time Triggers</h3>

                <div class="super-realtime-triggers">
                    <button class="super-btn super-add-trigger">
                        Add Real-time Trigger
                    </button>

                    <div class="super-triggers-list"></div>
                </div>

                <div class="super-trigger-template" style="display:none;">
                    <div class="super-trigger-item">
                        <select name="event_type" class="super-event-type">
                            <option value="">Select Event</option>
                            <option value="field_keyup">On Key Up</option>
                            <option value="field_change">On Change</option>
                            <option value="field_blur">On Blur</option>
                            <option value="field_focus">On Focus</option>
                        </select>

                        <select name="action_type" class="super-action-type">
                            <option value="">Select Action</option>
                            <option value="email_validation">Validate Email</option>
                            <option value="duplicate_check">Check Duplicate</option>
                            <option value="api_lookup">API Lookup</option>
                            <option value="dynamic_populate">Populate Fields</option>
                            <option value="conditional_logic">Conditional Logic</option>
                            <option value="custom_action">Custom Action</option>
                        </select>

                        <div class="super-action-config"></div>

                        <button class="super-btn super-remove-trigger">Remove</button>
                    </div>
                </div>
            </div>
        `;
    },

    getActionConfig: function(actionType) {
        const configs = {
            email_validation: `
                <label>
                    <input type="checkbox" name="check_dns" value="1">
                    Check DNS records
                </label>
                <label>
                    API Endpoint (optional):
                    <input type="text" name="api_endpoint" placeholder="https://api.example.com/validate">
                </label>
            `,

            duplicate_check: `
                <label>
                    Field to Check:
                    <select name="field_name">
                        <option value="email">Email</option>
                        <option value="username">Username</option>
                        <option value="phone">Phone</option>
                        <option value="custom">Custom Field</option>
                    </select>
                </label>
                <label>
                    Custom Message:
                    <input type="text" name="error_message" placeholder="This value already exists">
                </label>
            `,

            api_lookup: `
                <label>
                    API Endpoint:
                    <input type="text" name="endpoint" placeholder="https://api.example.com/lookup/{value}">
                </label>
                <label>
                    Method:
                    <select name="method">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                    </select>
                </label>
                <label>
                    Response Mapping:
                    <textarea name="response_mapping" placeholder='{"api_field": "form_field"}'></textarea>
                </label>
            `,

            dynamic_populate: `
                <label>
                    Data Source:
                    <select name="source">
                        <option value="database">Database</option>
                        <option value="api">API</option>
                        <option value="user_meta">User Meta</option>
                    </select>
                </label>
                <label>
                    Field Mapping:
                    <textarea name="field_mapping" placeholder='{"source_field": "target_field"}'></textarea>
                </label>
            `,

            conditional_logic: `
                <label>
                    Condition:
                    <input type="text" name="condition" placeholder="field_name == 'value'">
                </label>
                <label>
                    Action if True:
                    <select name="true_action">
                        <option value="show">Show Fields</option>
                        <option value="hide">Hide Fields</option>
                        <option value="enable">Enable Fields</option>
                        <option value="disable">Disable Fields</option>
                    </select>
                </label>
                <label>
                    Target Fields:
                    <input type="text" name="target_fields" placeholder="field1, field2">
                </label>
            `,

            custom_action: `
                <label>
                    Action ID:
                    <input type="text" name="custom_action_id" placeholder="my_custom_action">
                </label>
                <label>
                    Configuration (JSON):
                    <textarea name="custom_config" rows="5">{}</textarea>
                </label>
            `
        };

        return configs[actionType] || '';
    }
};
```

### Step 4: Performance Optimization

Implement caching and debouncing strategies:

```php
class SUPER_Realtime_Cache {

    private static $instance = null;
    private $cache_table;

    public function __construct() {
        global $wpdb;
        $this->cache_table = $wpdb->prefix . 'super_realtime_cache';
        $this->init();
    }

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init() {
        // Schedule cache cleanup via Action Scheduler
        add_action('init', function() {
            if (!as_next_scheduled_action('super_cleanup_realtime_cache')) {
                as_schedule_recurring_action(
                    time() + HOUR_IN_SECONDS,
                    HOUR_IN_SECONDS,
                    'super_cleanup_realtime_cache',
                    array(),
                    'super_forms_triggers'
                );
            }
        });

        add_action('super_cleanup_realtime_cache', array($this, 'cleanup_expired'));
    }

    public function get($key, $context = array()) {
        global $wpdb;

        $cache_key = $this->generate_key($key, $context);

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT value, expires_at FROM {$this->cache_table}
             WHERE cache_key = %s AND expires_at > NOW()",
            $cache_key
        ));

        if ($result) {
            return json_decode($result->value, true);
        }

        return null;
    }

    public function set($key, $value, $context = array(), $ttl = 3600) {
        global $wpdb;

        $cache_key = $this->generate_key($key, $context);

        $wpdb->replace(
            $this->cache_table,
            array(
                'cache_key' => $cache_key,
                'value' => json_encode($value),
                'created_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + $ttl)
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    public function delete($key, $context = array()) {
        global $wpdb;

        $cache_key = $this->generate_key($key, $context);

        $wpdb->delete(
            $this->cache_table,
            array('cache_key' => $cache_key),
            array('%s')
        );
    }

    public function cleanup_expired() {
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE FROM {$this->cache_table} WHERE expires_at < NOW()"
        );

        if ($deleted > 0) {
            error_log(sprintf('Cleaned up %d expired realtime cache entries', $deleted));
        }

        return $deleted;
    }

    private function generate_key($key, $context) {
        return md5($key . serialize($context));
    }

    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->cache_table} (
            cache_key varchar(32) NOT NULL,
            value longtext NOT NULL,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY (cache_key),
            KEY idx_expires (expires_at)
        ) $charset_collate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
```

### Step 5: Error Handling and Fallbacks

Implement robust error handling for network failures:

```javascript
// Add to super-forms-realtime.js
class RealtimeErrorHandler {

    constructor() {
        this.retryQueue = [];
        this.maxRetries = 3;
        this.retryDelay = 1000; // Start with 1 second
        this.online = navigator.onLine;

        this.init();
    }

    init() {
        // Monitor online/offline status
        window.addEventListener('online', () => {
            this.online = true;
            this.processRetryQueue();
        });

        window.addEventListener('offline', () => {
            this.online = false;
        });
    }

    handleError(request, error, retryCount = 0) {
        // Check if it's a network error
        if (!this.online || error.status === 0) {
            this.addToRetryQueue(request);
            this.showOfflineMessage();
            return;
        }

        // Check if we should retry
        if (retryCount < this.maxRetries && this.isRetryableError(error)) {
            setTimeout(() => {
                this.retryRequest(request, retryCount + 1);
            }, this.retryDelay * Math.pow(2, retryCount)); // Exponential backoff
            return;
        }

        // Log permanent failure
        this.logFailure(request, error);

        // Show user-friendly error
        this.showErrorMessage(request, error);
    }

    isRetryableError(error) {
        // Retry on timeout, 5xx errors, and rate limiting
        return error.status >= 500 ||
               error.status === 429 ||
               error.status === 408 ||
               error.statusText === 'timeout';
    }

    addToRetryQueue(request) {
        // Add to queue with timestamp
        this.retryQueue.push({
            request: request,
            timestamp: Date.now()
        });

        // Store in localStorage for persistence
        localStorage.setItem(
            'super_realtime_queue',
            JSON.stringify(this.retryQueue)
        );
    }

    processRetryQueue() {
        const queue = [...this.retryQueue];
        this.retryQueue = [];

        queue.forEach(item => {
            // Skip if too old (> 1 hour)
            if (Date.now() - item.timestamp > 3600000) {
                return;
            }

            // Retry the request
            this.retryRequest(item.request, 0);
        });

        // Clear localStorage
        localStorage.removeItem('super_realtime_queue');
    }

    showOfflineMessage() {
        const message = 'You appear to be offline. Your actions will be processed when connection is restored.';

        if (!$('.super-offline-notice').length) {
            $('body').append(`
                <div class="super-offline-notice">
                    <span>${message}</span>
                </div>
            `);
        }
    }

    showErrorMessage(request, error) {
        const userMessage = this.getUserFriendlyMessage(error);

        // Show inline error
        if (request.element) {
            $(request.element).closest('.super-field')
                .append(`<div class="super-error-msg">${userMessage}</div>`);
        }

        // Also show toast if available
        if (window.toastr) {
            toastr.error(userMessage);
        }
    }

    getUserFriendlyMessage(error) {
        const messages = {
            400: 'Invalid input. Please check your data and try again.',
            401: 'Authentication required. Please log in.',
            403: 'You don\'t have permission to perform this action.',
            404: 'The requested resource was not found.',
            429: 'Too many requests. Please wait a moment and try again.',
            500: 'Server error. Please try again later.',
            502: 'Service temporarily unavailable. Please try again.',
            503: 'Service is currently down for maintenance.',
            timeout: 'Request timed out. Please check your connection and try again.'
        };

        return messages[error.status] || messages[error.statusText] || 'An error occurred. Please try again.';
    }

    logFailure(request, error) {
        // Send failure log to server (fire and forget)
        $.ajax({
            url: super_forms_realtime.ajax_url,
            type: 'POST',
            data: {
                action: 'super_log_realtime_error',
                request: JSON.stringify(request),
                error: JSON.stringify({
                    status: error.status,
                    statusText: error.statusText,
                    message: error.responseText
                }),
                timestamp: new Date().toISOString()
            }
        });

        // Also log to console in debug mode
        if (super_forms_realtime.debug) {
            console.error('Realtime Action Failed:', {
                request: request,
                error: error
            });
        }
    }
}
```

## Testing Requirements

1. **Performance Testing**
   - Test with 50+ concurrent users
   - Verify debouncing works correctly
   - Ensure cache hit rates > 80%
   - API response times < 500ms

2. **Network Resilience**
   - Test offline mode handling
   - Verify retry mechanism
   - Test timeout scenarios
   - Validate error recovery

3. **Security Testing**
   - Test rate limiting
   - Verify nonce validation
   - Test permission checks
   - Validate input sanitization

4. **Browser Compatibility**
   - Test in Chrome, Firefox, Safari, Edge
   - Test on mobile devices
   - Verify touch event handling
   - Test with screen readers

## Context Manifest
<!-- To be added by context-gathering agent -->

## User Notes

- Consider WebSocket support for truly real-time updates (future enhancement)
- Implement request batching for multiple simultaneous triggers
- Add support for Progressive Web App offline capabilities
- Consider implementing a circuit breaker pattern for failing APIs
- Add telemetry for monitoring real-time action performance

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Subtask created for real-time form interactions (Epic 3)