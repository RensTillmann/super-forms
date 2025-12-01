<?php
/**
 * Base class for trigger actions
 *
 * @package Super_Forms
 * @subpackage Triggers
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

abstract class SUPER_Action_Base {

    /**
     * Get action ID
     *
     * @return string
     */
    abstract public function get_id();

    /**
     * Get action label
     *
     * @return string
     */
    abstract public function get_label();

    /**
     * Execute the action
     *
     * @param array $context Event context data
     * @param array $config Action configuration
     * @return array|WP_Error Result data or error
     */
    abstract public function execute($context, $config);

    /**
     * Get action category
     *
     * @return string
     */
    public function get_category() {
        return 'general';
    }

    /**
     * Get action description
     *
     * @return string
     */
    public function get_description() {
        return '';
    }

    /**
     * Get settings schema for UI
     *
     * @return array
     */
    public function get_settings_schema() {
        return [];
    }

    /**
     * Validate action configuration
     *
     * @param array $config
     * @return bool|WP_Error
     */
    public function validate_config($config) {
        $schema = $this->get_settings_schema();

        foreach ($schema as $field) {
            $name = $field['name'] ?? '';
            $required = $field['required'] ?? false;

            if ($required && empty($config[$name])) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf(
                        __('Missing required field: %s', 'super-forms'),
                        $field['label'] ?? $name
                    )
                );
            }

            // Validate field type
            if (isset($config[$name]) && isset($field['type'])) {
                $valid = $this->validate_field_type($config[$name], $field['type']);
                if (is_wp_error($valid)) {
                    return $valid;
                }
            }
        }

        return true;
    }

    /**
     * Validate field type
     *
     * @param mixed $value
     * @param string $type
     * @return bool|WP_Error
     */
    protected function validate_field_type($value, $type) {
        switch ($type) {
            case 'email':
                if (!is_email($value)) {
                    return new WP_Error('invalid_email', __('Invalid email address', 'super-forms'));
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return new WP_Error('invalid_url', __('Invalid URL', 'super-forms'));
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    return new WP_Error('invalid_number', __('Invalid number', 'super-forms'));
                }
                break;

            case 'checkbox':
            case 'toggle':
                if (!is_bool($value) && !in_array($value, ['0', '1', 'true', 'false'])) {
                    return new WP_Error('invalid_boolean', __('Invalid boolean value', 'super-forms'));
                }
                break;
        }

        return true;
    }

    /**
     * Sanitize value based on output context
     *
     * @param mixed $value Value to sanitize
     * @param string $sanitize_type Type of sanitization (html, email, url, text, sql, attribute, none)
     * @param bool $allow_html Whether to allow safe HTML
     * @return mixed Sanitized value
     */
    private function sanitize_value($value, $sanitize_type, $allow_html = false) {
        // Convert arrays/objects to JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        switch ($sanitize_type) {
            case 'html':
                // For HTML output (safest default)
                if ($allow_html) {
                    return wp_kses_post($value); // Allow safe HTML
                }
                return esc_html($value); // Escape all HTML

            case 'email':
                return sanitize_email($value);

            case 'url':
                return esc_url($value);

            case 'text':
                return sanitize_text_field($value);

            case 'sql':
                // Use with EXTREME caution
                global $wpdb;
                return $wpdb->_real_escape($value);

            case 'attribute':
                return esc_attr($value);

            case 'none':
                // No sanitization - use only for trusted data
                return $value;

            default:
                return esc_html($value); // Safe default
        }
    }

    /**
     * Validate required variables exist
     *
     * @param array $required_vars List of required variable names
     * @param array $context Context data
     * @return array Validation result with 'valid' boolean and 'missing' array
     */
    protected function validate_context($required_vars, $context) {
        $missing = array_diff($required_vars, array_keys($context));

        if (!empty($missing)) {
            SUPER_Automation_Logger::warning(
                "Missing required variables: " . implode(', ', $missing)
            );
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }

    /**
     * Replace variables with context-aware sanitization
     *
     * @param string $string String with {variables}
     * @param array $context Context data
     * @param array $options Sanitization options
     * @return string
     */
    protected function replace_variables($string, $context, $options = []) {
        $defaults = [
            'sanitize' => 'html',  // Default: HTML-safe
            'allow_html' => false,
            'missing_behavior' => 'empty',
            'missing_variables' => []
        ];

        $options = array_merge($defaults, $options);

        return preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            function($matches) use ($context, &$options) {
                $variable = $matches[1];

                if (!isset($context[$variable])) {
                    $options['missing_variables'][] = $variable;

                    switch ($options['missing_behavior']) {
                        case 'empty': return '';
                        case 'keep': return $matches[0];
                        case 'error':
                            throw new Exception("Required variable {$variable} not found");
                        default: return '';
                    }
                }

                $value = $context[$variable];

                // Apply sanitization based on output context
                return $this->sanitize_value(
                    $value,
                    $options['sanitize'],
                    $options['allow_html']
                );
            },
            $string
        );
    }

    /**
     * Replace tags in string with context values (backward compatibility)
     *
     * @param string $string String with {tags}
     * @param array $context Context data
     * @return string
     * @deprecated Use replace_variables() with sanitization options
     */
    protected function replace_tags($string, $context) {
        return $this->replace_variables($string, $context, ['sanitize' => 'html']);
    }

    /**
     * Log action execution
     *
     * @param array $context
     * @param array $config
     * @param array $result
     */
    protected function log_execution($context, $config, $result) {
        global $wpdb;

        $table = $wpdb->prefix . 'superforms_automation_logs';

        $log_data = [
            'automation_id' => $context['automation_id'] ?? 0,
            'entry_id' => $context['entry_id'] ?? null,
            'form_id' => $context['form_id'] ?? null,
            'event_id' => $context['event_id'] ?? '',
            'action_id' => $this->get_id(),
            'status' => is_wp_error($result) ? 'error' : 'success',
            'error_message' => is_wp_error($result) ? $result->get_error_message() : null,
            'context_data' => json_encode([
                'config' => $config,
                'result' => is_wp_error($result) ? null : $result
            ]),
            'user_id' => get_current_user_id(),
            'executed_at' => current_time('mysql')
        ];

        $wpdb->insert($table, $log_data);
    }

    /**
     * Check if action can run in current context
     *
     * @param array $context
     * @return bool
     */
    public function can_run($context) {
        // Override in child classes for specific checks
        return true;
    }

    /**
     * Get required capabilities to configure this action
     *
     * @return array
     */
    public function get_required_capabilities() {
        return ['manage_options'];
    }

    /**
     * Sanitize configuration values
     *
     * @param array $config
     * @return array
     */
    public function sanitize_config($config) {
        $schema = $this->get_settings_schema();
        $sanitized = [];

        foreach ($schema as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'text';

            if (!isset($config[$name])) {
                continue;
            }

            $value = $config[$name];

            switch ($type) {
                case 'email':
                    $sanitized[$name] = sanitize_email($value);
                    break;

                case 'url':
                    $sanitized[$name] = esc_url_raw($value);
                    break;

                case 'textarea':
                case 'wysiwyg':
                    $sanitized[$name] = wp_kses_post($value);
                    break;

                case 'number':
                    $sanitized[$name] = intval($value);
                    break;

                case 'checkbox':
                case 'toggle':
                    $sanitized[$name] = (bool) $value;
                    break;

                case 'select':
                case 'radio':
                    // Validate against allowed options
                    $options = $field['options'] ?? [];
                    if (in_array($value, array_keys($options))) {
                        $sanitized[$name] = $value;
                    }
                    break;

                default:
                    $sanitized[$name] = sanitize_text_field($value);
                    break;
            }
        }

        return $sanitized;
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    public function get_default_config() {
        $defaults = [];
        $schema = $this->get_settings_schema();

        foreach ($schema as $field) {
            if (isset($field['name']) && isset($field['default'])) {
                $defaults[$field['name']] = $field['default'];
            }
        }

        return $defaults;
    }

    /**
     * Format result for UI display
     *
     * @param array|WP_Error $result
     * @return array
     */
    public function format_result($result) {
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'error' => $result->get_error_message(),
                'error_code' => $result->get_error_code()
            ];
        }

        return array_merge(['success' => true], $result);
    }

    // -------------------------------------------------------------------------
    // Phase 2: Async Execution Support
    // -------------------------------------------------------------------------

    /**
     * Check if this action supports asynchronous execution
     *
     * Actions that affect the form submission flow (like abort_submission,
     * redirect_user) should return false. Actions that make external requests
     * or perform slow operations should return true.
     *
     * @return bool
     * @since 6.5.0
     */
    public function supports_async() {
        return true; // Default: most actions can run async
    }

    /**
     * Get the preferred execution mode for this action
     *
     * Values:
     * - 'sync': Execute immediately (blocking)
     * - 'async': Queue for background execution
     * - 'auto': Let the executor decide based on context
     *
     * @return string
     * @since 6.5.0
     */
    public function get_execution_mode() {
        return 'auto'; // Let the executor decide by default
    }

    /**
     * Get retry configuration for failed executions
     *
     * @return array {
     *     @type int  $max_retries     Maximum retry attempts (default: 3)
     *     @type int  $initial_delay   Initial delay in seconds before first retry (default: 120)
     *     @type bool $exponential     Use exponential backoff (default: true)
     *     @type int  $max_delay       Maximum delay in seconds (default: 1800)
     * }
     * @since 6.5.0
     */
    public function get_retry_config() {
        return [
            'max_retries'   => 3,
            'initial_delay' => 120,    // 2 minutes
            'exponential'   => true,   // Exponential backoff
            'max_delay'     => 1800,   // 30 minutes max
        ];
    }

    /**
     * Check if this action should be retried on failure
     *
     * Override in child classes to disable retry for specific actions
     * or implement custom retry logic.
     *
     * @param array|WP_Error $result The failed result
     * @param int            $attempt Current attempt number
     * @return bool
     * @since 6.5.0
     */
    public function should_retry( $result, $attempt ) {
        // Don't retry if action doesn't support async
        if ( ! $this->supports_async() ) {
            return false;
        }

        // Get max retries from config
        $config = $this->get_retry_config();
        if ( $attempt >= $config['max_retries'] ) {
            return false;
        }

        // Don't retry certain error types
        if ( is_wp_error( $result ) ) {
            $no_retry_codes = [
                'permission_denied',
                'invalid_config',
                'missing_required_field',
                'action_disabled',
            ];

            if ( in_array( $result->get_error_code(), $no_retry_codes, true ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if action is rate limited
     *
     * Override for actions that call external APIs with rate limits.
     *
     * @return bool|int False if not rate limited, or seconds until next allowed execution
     * @since 6.5.0
     */
    public function is_rate_limited() {
        return false; // No rate limiting by default
    }

    /**
     * Get rate limit configuration
     *
     * Override for actions that need rate limiting.
     *
     * @return array {
     *     @type int $limit  Maximum executions allowed in the window
     *     @type int $window Time window in seconds
     * }
     * @since 6.5.0
     */
    public function get_rate_limit_config() {
        return [
            'limit'  => 0,  // 0 = no rate limiting
            'window' => 60, // 1 minute window
        ];
    }

    /**
     * Called before action is queued for async execution
     *
     * Use this to prepare any data that needs to be serialized
     * or to perform validation before queuing.
     *
     * @param array $context Event context
     * @param array $config  Action configuration
     * @return array|WP_Error Modified context or error to prevent queuing
     * @since 6.5.0
     */
    public function prepare_for_queue( $context, $config ) {
        return $context; // Return context unchanged by default
    }

    /**
     * Get action metadata for registration
     *
     * Returns all metadata about this action in a single call.
     * Used by the registry for action registration.
     *
     * @return array
     * @since 6.5.0
     */
    public function get_metadata() {
        return [
            'id'                => $this->get_id(),
            'label'             => $this->get_label(),
            'description'       => $this->get_description(),
            'category'          => $this->get_category(),
            'settings_schema'   => $this->get_settings_schema(),
            'supports_async'    => $this->supports_async(),
            'execution_mode'    => $this->get_execution_mode(),
            'retry_config'      => $this->get_retry_config(),
            'rate_limit_config' => $this->get_rate_limit_config(),
            'capabilities'      => $this->get_required_capabilities(),
        ];
    }
}