<?php
/**
 * Sandbox Manager for Developer Tools
 *
 * Creates inspectable test forms, triggers, and entries in the live database
 * for visual validation of the trigger system.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SUPER_Sandbox_Manager class
 *
 * Manages sandbox test data for Developer Tools visual validation.
 */
class SUPER_Sandbox_Manager {

    /**
     * Meta key used to tag all sandbox data
     */
    const SANDBOX_META_KEY = '_super_sandbox_test';

    /**
     * Option key for sandbox state
     */
    const SANDBOX_OPTION = 'superforms_sandbox_state';

    /**
     * Form types available for sandbox testing
     */
    const FORM_TYPES = array(
        'simple'        => 'Simple Contact Form',
        'comprehensive' => 'Comprehensive Multi-Step Form',
        'repeater'      => 'Repeater/Dynamic Fields Form',
        'conditional'   => 'Conditional Logic Form',
        'registration'  => 'User Registration Form',
    );

    /**
     * Create a complete sandbox environment
     *
     * Creates multiple test forms with triggers for comprehensive testing.
     *
     * @param array $options Which form types to create (default: all)
     * @return array|WP_Error Sandbox state on success, WP_Error on failure
     */
    public static function create_sandbox($options = array()) {
        // Check if sandbox already exists
        $existing = self::get_sandbox_status();
        if (!empty($existing['forms']) && count($existing['forms']) > 0) {
            return new WP_Error('sandbox_exists', 'Sandbox already exists. Clean up first.');
        }

        $defaults = array(
            'form_types' => array('simple', 'comprehensive', 'repeater'), // Default form types
        );
        $options = wp_parse_args($options, $defaults);

        $sandbox_state = array(
            'created_at'  => current_time('mysql'),
            'forms'       => array(),
            'trigger_ids' => array(),
            'entry_ids'   => array(),
        );

        // Create forms using test fixtures if available
        $use_fixtures = class_exists('SUPER_Test_Form_Factory');

        foreach ($options['form_types'] as $form_type) {
            $form_result = self::create_form_by_type($form_type, $use_fixtures);

            if (is_wp_error($form_result)) {
                // Log but continue with other forms
                error_log('Sandbox: Failed to create ' . $form_type . ' form: ' . $form_result->get_error_message());
                continue;
            }

            $form_id = $form_result['form_id'];
            $sandbox_state['forms'][$form_type] = array(
                'form_id'     => $form_id,
                'form_title'  => $form_result['title'],
                'trigger_ids' => array(),
            );

            // Create triggers for this form
            $triggers = self::create_triggers_for_form($form_id, $form_type);
            $sandbox_state['forms'][$form_type]['trigger_ids'] = $triggers;
            $sandbox_state['trigger_ids'] = array_merge($sandbox_state['trigger_ids'], $triggers);
        }

        if (empty($sandbox_state['forms'])) {
            return new WP_Error('no_forms_created', 'Failed to create any sandbox forms.');
        }

        // Save sandbox state
        update_option(self::SANDBOX_OPTION, $sandbox_state);

        return $sandbox_state;
    }

    /**
     * Create a form by type
     *
     * @param string $form_type Form type identifier
     * @param bool   $use_fixtures Whether to use test fixtures
     * @return array|WP_Error Form details or error
     */
    private static function create_form_by_type($form_type, $use_fixtures) {
        $form_id = null;
        $title = self::FORM_TYPES[$form_type] ?? 'Sandbox Form';

        if ($use_fixtures && class_exists('SUPER_Test_Form_Factory')) {
            switch ($form_type) {
                case 'simple':
                    $form_id = SUPER_Test_Form_Factory::create_simple_form(array(
                        'title' => '[Sandbox] ' . $title,
                    ));
                    break;
                case 'comprehensive':
                    $form_id = SUPER_Test_Form_Factory::create_comprehensive_form(array(
                        'title' => '[Sandbox] ' . $title,
                    ));
                    break;
                case 'repeater':
                    $form_id = SUPER_Test_Form_Factory::create_repeater_form(array(
                        'title' => '[Sandbox] ' . $title,
                    ));
                    break;
                case 'multistep':
                    $form_id = SUPER_Test_Form_Factory::create_multistep_form(array(
                        'title' => '[Sandbox] ' . $title,
                    ));
                    break;
                case 'registration':
                    $form_id = SUPER_Test_Form_Factory::create_registration_form(array(
                        'title' => '[Sandbox] ' . $title,
                    ));
                    break;
                default:
                    $form_id = SUPER_Test_Form_Factory::create_simple_form(array(
                        'title' => '[Sandbox] ' . $title,
                    ));
            }
        } else {
            // Fallback: create simple form manually
            $form_id = self::create_simple_form_manually($title);
        }

        if (is_wp_error($form_id)) {
            return $form_id;
        }

        // Tag as sandbox
        update_post_meta($form_id, self::SANDBOX_META_KEY, '1');

        return array(
            'form_id' => $form_id,
            'title'   => '[Sandbox] ' . $title,
            'type'    => $form_type,
        );
    }

    /**
     * Create triggers appropriate for a form type
     *
     * @param int    $form_id   Form ID
     * @param string $form_type Form type identifier
     * @return array Trigger IDs
     */
    private static function create_triggers_for_form($form_id, $form_type) {
        $trigger_ids = array();

        // Use trigger factory if available
        if (class_exists('SUPER_Test_Trigger_Factory')) {
            $options = array(
                'on_submit_log'    => true,
                'on_entry_created' => true,
            );

            // Add conditional trigger for comprehensive forms
            if ($form_type === 'comprehensive') {
                $options['on_high_budget'] = true;
            }

            $triggers = SUPER_Test_Trigger_Factory::create_trigger_set($form_id, $options);
            foreach ($triggers as $trigger_id) {
                if (!is_wp_error($trigger_id)) {
                    $trigger_ids[] = $trigger_id;
                }
            }
        } else {
            // Fallback: create triggers manually
            $trigger_ids = self::create_triggers_manually($form_id, $form_type);
        }

        return $trigger_ids;
    }

    /**
     * Create a simple form manually (fallback when fixtures unavailable)
     *
     * @param string $title Form title
     * @return int|WP_Error Form ID
     */
    private static function create_simple_form_manually($title) {
        $elements = array(
            array(
                'tag'   => 'text',
                'group' => 'form_elements',
                'data'  => array(
                    'name'        => 'name',
                    'email'       => 'Name:',
                    'placeholder' => 'Your name',
                    'validation'  => 'empty',
                ),
            ),
            array(
                'tag'   => 'text',
                'group' => 'form_elements',
                'data'  => array(
                    'name'        => 'email',
                    'email'       => 'Email:',
                    'placeholder' => 'email@example.com',
                    'validation'  => 'email',
                    'type'        => 'email',
                ),
            ),
            array(
                'tag'   => 'textarea',
                'group' => 'form_elements',
                'data'  => array(
                    'name'        => 'message',
                    'email'       => 'Message:',
                    'placeholder' => 'Your message',
                    'validation'  => 'empty',
                ),
            ),
        );

        $form_id = wp_insert_post(array(
            'post_type'   => 'super_form',
            'post_status' => 'publish',
            'post_title'  => '[Sandbox] ' . $title,
        ));

        if (is_wp_error($form_id)) {
            return $form_id;
        }

        update_post_meta($form_id, '_super_elements', wp_json_encode($elements));
        update_post_meta($form_id, '_super_form_settings', array(
            'save_contact_entry' => 'yes',
            'send'               => '',
        ));

        return $form_id;
    }

    /**
     * Create triggers manually (fallback when fixtures unavailable)
     *
     * @param int    $form_id   Form ID
     * @param string $form_type Form type
     * @return array Trigger IDs
     */
    private static function create_triggers_manually($form_id, $form_type) {
        global $wpdb;
        $trigger_ids = array();

        // Trigger 1: Log on form submission
        $wpdb->insert(
            $wpdb->prefix . 'superforms_automations',
            array(
                'trigger_name'    => 'Sandbox: Log Submission (' . $form_type . ')',
                'event_id'        => 'form.submitted',
                'scope'           => 'form',
                'scope_id'        => $form_id,
                'conditions'      => wp_json_encode(array()),
                'enabled'         => 1,
                'execution_order' => 10,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s')
        );
        $trigger1_id = $wpdb->insert_id;
        if ($trigger1_id) {
            $trigger_ids[] = $trigger1_id;
            $wpdb->insert(
                $wpdb->prefix . 'superforms_automation_actions',
                array(
                    'trigger_id'   => $trigger1_id,
                    'action_type'  => 'log_message',
                    'action_order' => 1,
                    'config'       => wp_json_encode(array(
                        'message' => 'Sandbox submission! Form: {form_id}, Name: {name}, Email: {email}',
                        'level'   => 'info',
                    )),
                    'created_at'   => current_time('mysql'),
                ),
                array('%d', '%s', '%d', '%s', '%s')
            );
        }

        // Trigger 2: Log entry creation
        $wpdb->insert(
            $wpdb->prefix . 'superforms_automations',
            array(
                'trigger_name'    => 'Sandbox: Track Entry (' . $form_type . ')',
                'event_id'        => 'entry.created',
                'scope'           => 'form',
                'scope_id'        => $form_id,
                'conditions'      => wp_json_encode(array()),
                'enabled'         => 1,
                'execution_order' => 10,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s')
        );
        $trigger2_id = $wpdb->insert_id;
        if ($trigger2_id) {
            $trigger_ids[] = $trigger2_id;
            $wpdb->insert(
                $wpdb->prefix . 'superforms_automation_actions',
                array(
                    'trigger_id'   => $trigger2_id,
                    'action_type'  => 'log_message',
                    'action_order' => 1,
                    'config'       => wp_json_encode(array(
                        'message' => 'Entry created! ID: {entry_id}, Form: {form_id}',
                        'level'   => 'info',
                    )),
                    'created_at'   => current_time('mysql'),
                ),
                array('%d', '%s', '%d', '%s', '%s')
            );
        }

        return $trigger_ids;
    }

    /**
     * Get current sandbox status
     *
     * @return array Sandbox state with form details and statistics
     */
    public static function get_sandbox_status() {
        $state = get_option(self::SANDBOX_OPTION, array());

        if (empty($state)) {
            return array(
                'exists'        => false,
                'forms'         => array(),
                'trigger_ids'   => array(),
                'trigger_count' => 0,
                'entry_count'   => 0,
                'log_count'     => 0,
            );
        }

        global $wpdb;

        // Count entries with sandbox meta
        $entry_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
            self::SANDBOX_META_KEY
        ));

        // Count trigger logs
        $log_count = 0;
        $trigger_ids = !empty($state['trigger_ids']) ? $state['trigger_ids'] : array();
        if (!empty($trigger_ids)) {
            $placeholders = implode(',', array_fill(0, count($trigger_ids), '%d'));
            $log_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_automation_logs WHERE trigger_id IN ($placeholders)",
                ...$trigger_ids
            ));
        }

        // Enrich form data
        $forms = array();
        if (!empty($state['forms'])) {
            foreach ($state['forms'] as $type => $form_data) {
                $form_id = $form_data['form_id'];
                $form = get_post($form_id);
                $forms[$type] = array(
                    'form_id'      => $form_id,
                    'form_title'   => $form ? $form->post_title : 'Unknown',
                    'form_url'     => $form_id ? add_query_arg('super_sandbox', $type, home_url('/')) : '',
                    'edit_url'     => $form_id ? admin_url('admin.php?page=super_create_form&id=' . $form_id) : '',
                    'trigger_ids'  => $form_data['trigger_ids'] ?? array(),
                    'trigger_count' => count($form_data['trigger_ids'] ?? array()),
                );
            }
        }

        return array(
            'exists'        => !empty($state['forms']),
            'forms'         => $forms,
            'trigger_ids'   => $trigger_ids,
            'trigger_count' => count($trigger_ids),
            'entry_count'   => (int) $entry_count,
            'log_count'     => (int) $log_count,
            'created_at'    => $state['created_at'] ?? '',
        );
    }

    /**
     * Submit a test entry to a sandbox form
     *
     * @param string $form_type Form type (simple, comprehensive, etc.)
     * @param array  $custom_data Optional custom field data
     * @return array|WP_Error Entry details with execution results
     */
    public static function submit_test_entry($form_type = 'simple', $custom_data = array()) {
        $status = self::get_sandbox_status();
        if (!$status['exists']) {
            return new WP_Error('no_sandbox', 'No sandbox exists. Create one first.');
        }

        if (!isset($status['forms'][$form_type])) {
            // Fall back to first available form
            $form_type = array_key_first($status['forms']);
        }

        $form_id = $status['forms'][$form_type]['form_id'];

        // Get test data based on form type
        $entry_data = self::get_test_data_for_type($form_type, $custom_data);

        // Create the entry post
        $entry_id = wp_insert_post(array(
            'post_type'   => 'super_contact_entry',
            'post_title'  => 'Sandbox Entry (' . $form_type . ') - ' . current_time('mysql'),
            'post_status' => 'publish',
            'post_parent' => $form_id,
        ));

        if (is_wp_error($entry_id)) {
            return $entry_id;
        }

        // Tag as sandbox entry
        add_post_meta($entry_id, self::SANDBOX_META_KEY, $form_id);
        add_post_meta($entry_id, '_super_form_id', $form_id);

        // Save entry data using Data Access Layer
        if (class_exists('SUPER_Data_Access')) {
            SUPER_Data_Access::save_entry_data($entry_id, $entry_data);
        } else {
            update_post_meta($entry_id, '_super_contact_entry_data', $entry_data);
        }

        // Build context for trigger execution
        // Important: Include 'data' with field values for tag replacement
        $context = self::build_context($form_id, $entry_id, $entry_data);

        // Fire events and capture results
        $execution_results = array();
        if (class_exists('SUPER_Automation_Executor')) {
            // Enable debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Sandbox: Firing form.submitted for form_id={$form_id}, entry_id={$entry_id}");
                error_log("Sandbox: Context keys: " . implode(', ', array_keys($context)));
            }

            $execution_results['form.submitted'] = SUPER_Automation_Executor::fire_event('form.submitted', $context);
            $execution_results['entry.created'] = SUPER_Automation_Executor::fire_event('entry.created', $context);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Sandbox: form.submitted result: " . print_r($execution_results['form.submitted'], true));
                error_log("Sandbox: entry.created result: " . print_r($execution_results['entry.created'], true));
            }
        }

        // Update sandbox state with entry ID
        $state = get_option(self::SANDBOX_OPTION, array());
        if (!isset($state['entry_ids'])) {
            $state['entry_ids'] = array();
        }
        $state['entry_ids'][] = $entry_id;
        update_option(self::SANDBOX_OPTION, $state);

        // Count triggers found and executed
        $triggers_found = 0;
        $triggers_executed = 0;
        foreach ($execution_results as $event => $results) {
            $triggers_found += count($results);
            foreach ($results as $trigger_id => $result) {
                if (!empty($result['success'])) {
                    $triggers_executed++;
                }
            }
        }

        return array(
            'success'           => true,
            'entry_id'          => $entry_id,
            'form_id'           => $form_id,
            'form_type'         => $form_type,
            'entry_url'         => admin_url('admin.php?page=super_contact_entry&id=' . $entry_id),
            'entry_data'        => $entry_data,
            'triggers_found'    => $triggers_found,
            'triggers_executed' => $triggers_executed,
            'execution_results' => $execution_results,
        );
    }

    /**
     * Build context for trigger execution
     *
     * @param int   $form_id    Form ID
     * @param int   $entry_id   Entry ID
     * @param array $entry_data Entry field data
     * @return array Context for trigger execution
     */
    private static function build_context($form_id, $entry_id, $entry_data) {
        $context = array(
            'form_id'    => $form_id,
            'entry_id'   => $entry_id,
            'user_id'    => get_current_user_id(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Sandbox Test',
            'timestamp'  => current_time('mysql'),
        );

        // Add entry_data for reference
        $context['entry_data'] = $entry_data;

        // Add 'data' key with field values for tag replacement
        // The conditions engine and tag replacement look in $context['data']['field_name']['value']
        $context['data'] = $entry_data;

        // Also add flat field values directly to context for simple {field_name} replacement
        foreach ($entry_data as $field_name => $field_data) {
            if (is_array($field_data) && isset($field_data['value'])) {
                $context[$field_name] = $field_data['value'];
            } elseif (!is_array($field_data)) {
                $context[$field_name] = $field_data;
            }
        }

        return $context;
    }

    /**
     * Get test data appropriate for a form type
     *
     * @param string $form_type   Form type
     * @param array  $custom_data Custom overrides
     * @return array Test data in Super Forms field format
     */
    private static function get_test_data_for_type($form_type, $custom_data = array()) {
        // Try to use test fixtures if available
        if (class_exists('SUPER_Test_Form_Factory')) {
            switch ($form_type) {
                case 'comprehensive':
                    $data = SUPER_Test_Form_Factory::get_test_submission_data();
                    break;
                case 'repeater':
                    $data = SUPER_Test_Form_Factory::get_repeater_submission_data(3);
                    break;
                default:
                    $data = SUPER_Test_Form_Factory::get_simple_submission_data();
            }
        } else {
            // Fallback to basic test data
            $data = array(
                'name' => array(
                    'name'  => 'name',
                    'value' => 'Sandbox Test User',
                    'label' => 'Name',
                    'type'  => 'text',
                ),
                'email' => array(
                    'name'  => 'email',
                    'value' => 'sandbox@example.com',
                    'label' => 'Email',
                    'type'  => 'text',
                ),
                'message' => array(
                    'name'  => 'message',
                    'value' => 'Sandbox test submission at ' . current_time('mysql'),
                    'label' => 'Message',
                    'type'  => 'textarea',
                ),
            );
        }

        // Merge custom data
        foreach ($custom_data as $field_name => $value) {
            if (is_array($value)) {
                $data[$field_name] = $value;
            } else {
                $data[$field_name] = array(
                    'name'  => $field_name,
                    'value' => $value,
                    'label' => ucfirst($field_name),
                    'type'  => 'text',
                );
            }
        }

        return $data;
    }

    /**
     * Run automated test suite on sandbox
     *
     * Submits entries to all forms and verifies triggers execute correctly.
     *
     * @return array Test results
     */
    public static function run_test_suite() {
        $status = self::get_sandbox_status();
        if (!$status['exists']) {
            return array(
                'success' => false,
                'error'   => 'No sandbox exists. Create one first.',
                'tests'   => array(),
            );
        }

        $results = array(
            'success'      => true,
            'tests'        => array(),
            'total_forms'  => count($status['forms']),
            'total_passed' => 0,
            'total_failed' => 0,
        );

        foreach ($status['forms'] as $form_type => $form_data) {
            $test_result = array(
                'form_type'         => $form_type,
                'form_id'           => $form_data['form_id'],
                'passed'            => false,
                'entry_created'     => false,
                'triggers_found'    => 0,
                'triggers_executed' => 0,
                'logs_created'      => 0,
                'errors'            => array(),
            );

            // Submit test entry
            $submission = self::submit_test_entry($form_type);

            if (is_wp_error($submission)) {
                $test_result['errors'][] = $submission->get_error_message();
                $results['total_failed']++;
            } else {
                $test_result['entry_created'] = true;
                $test_result['entry_id'] = $submission['entry_id'];
                $test_result['triggers_found'] = $submission['triggers_found'];
                $test_result['triggers_executed'] = $submission['triggers_executed'];

                // Check logs were created
                $logs = self::get_logs_for_entry($submission['entry_id']);
                $test_result['logs_created'] = count($logs);

                // Determine pass/fail
                $expected_triggers = count($form_data['trigger_ids']);
                if ($test_result['triggers_executed'] >= $expected_triggers && $test_result['logs_created'] > 0) {
                    $test_result['passed'] = true;
                    $results['total_passed']++;
                } else {
                    $test_result['passed'] = false;
                    $results['total_failed']++;
                    if ($test_result['triggers_executed'] < $expected_triggers) {
                        $test_result['errors'][] = sprintf(
                            'Expected %d triggers, only %d executed',
                            $expected_triggers,
                            $test_result['triggers_executed']
                        );
                    }
                    if ($test_result['logs_created'] === 0) {
                        $test_result['errors'][] = 'No logs were created';
                    }
                }
            }

            $results['tests'][$form_type] = $test_result;
        }

        $results['success'] = ($results['total_failed'] === 0);

        return $results;
    }

    /**
     * Get trigger logs for a specific entry
     *
     * @param int $entry_id Entry ID
     * @return array Log entries
     */
    public static function get_logs_for_entry($entry_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}superforms_automation_logs WHERE entry_id = %d ORDER BY executed_at DESC",
            $entry_id
        ), ARRAY_A);
    }

    /**
     * Get all trigger logs for sandbox
     *
     * @param int $limit Number of logs to return
     * @return array Array of log entries
     */
    public static function get_sandbox_logs($limit = 50) {
        $status = self::get_sandbox_status();
        if (!$status['exists'] || empty($status['trigger_ids'])) {
            return array();
        }

        global $wpdb;
        $trigger_ids = $status['trigger_ids'];
        $placeholders = implode(',', array_fill(0, count($trigger_ids), '%d'));

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}superforms_automation_logs
             WHERE trigger_id IN ($placeholders)
             ORDER BY executed_at DESC
             LIMIT %d",
            ...array_merge($trigger_ids, array($limit))
        ), ARRAY_A);

        return $logs;
    }

    /**
     * Cleanup all sandbox data
     *
     * Removes forms, triggers, entries, and logs created by sandbox.
     *
     * @return array Cleanup statistics
     */
    public static function cleanup_sandbox() {
        $status = self::get_sandbox_status();
        $stats = array(
            'forms_deleted'    => 0,
            'triggers_deleted' => 0,
            'entries_deleted'  => 0,
            'logs_deleted'     => 0,
        );

        if (!$status['exists']) {
            return $stats;
        }

        global $wpdb;

        // Delete trigger logs
        $trigger_ids = $status['trigger_ids'];
        if (!empty($trigger_ids)) {
            $placeholders = implode(',', array_fill(0, count($trigger_ids), '%d'));
            $stats['logs_deleted'] = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}superforms_automation_logs WHERE trigger_id IN ($placeholders)",
                ...$trigger_ids
            ));
        }

        // Delete triggers
        foreach ($trigger_ids as $trigger_id) {
            $wpdb->delete(
                $wpdb->prefix . 'superforms_automation_actions',
                array('trigger_id' => $trigger_id),
                array('%d')
            );
            $deleted = $wpdb->delete(
                $wpdb->prefix . 'superforms_automations',
                array('id' => $trigger_id),
                array('%d')
            );
            if ($deleted) {
                $stats['triggers_deleted']++;
            }
        }

        // Delete entries with sandbox meta
        $entry_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
            self::SANDBOX_META_KEY
        ));

        foreach ($entry_ids as $entry_id) {
            $post = get_post($entry_id);
            if ($post && $post->post_type === 'super_contact_entry') {
                $wpdb->delete(
                    $wpdb->prefix . 'superforms_entry_data',
                    array('entry_id' => $entry_id),
                    array('%d')
                );
                if (wp_delete_post($entry_id, true)) {
                    $stats['entries_deleted']++;
                }
            }
        }

        // Delete forms
        foreach ($status['forms'] as $form_data) {
            $form_id = $form_data['form_id'];
            if ($form_id && wp_delete_post($form_id, true)) {
                $stats['forms_deleted']++;
            }
        }

        // Clear sandbox state
        delete_option(self::SANDBOX_OPTION);

        // Also cleanup test fixtures if used
        if (class_exists('SUPER_Test_Form_Factory')) {
            SUPER_Test_Form_Factory::cleanup();
        }
        if (class_exists('SUPER_Test_Trigger_Factory')) {
            SUPER_Test_Trigger_Factory::cleanup();
        }

        return $stats;
    }
}
