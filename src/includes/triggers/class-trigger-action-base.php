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

abstract class SUPER_Trigger_Action_Base {

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
     * Replace tags in string with context values
     *
     * @param string $string String with {tags}
     * @param array $context Context data
     * @return string
     */
    protected function replace_tags($string, $context) {
        // Handle simple tags like {user_name}
        $string = preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            function($matches) use ($context) {
                $key = $matches[1];
                return isset($context[$key]) ? $context[$key] : $matches[0];
            },
            $string
        );

        // Handle nested tags like {form_data.email}
        $string = preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\}/',
            function($matches) use ($context) {
                $parent = $matches[1];
                $child = $matches[2];

                if (isset($context[$parent]) && is_array($context[$parent])) {
                    return isset($context[$parent][$child]) ? $context[$parent][$child] : $matches[0];
                }

                return $matches[0];
            },
            $string
        );

        return $string;
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

        $table = $wpdb->prefix . 'superforms_trigger_logs';

        $log_data = [
            'trigger_id' => $context['trigger_id'] ?? 0,
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
}