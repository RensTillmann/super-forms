<?php
/**
 * Test Trigger Executor
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Trigger_Executor extends WP_UnitTestCase {

    /**
     * Test form ID
     */
    private $form_id;

    /**
     * Test user ID
     */
    private $user_id;

    /**
     * Setup before each test
     */
    public function setUp() {
        parent::setUp();

        // Create test form
        $this->form_id = wp_insert_post([
            'post_type' => 'super_form',
            'post_status' => 'publish',
            'post_title' => 'Test Form'
        ]);

        // Create test user
        $this->user_id = $this->factory->user->create([
            'role' => 'subscriber'
        ]);

        // Setup database tables
        $this->setup_trigger_tables();

        // Clear any existing triggers
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}superforms_triggers");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}superforms_trigger_logs");
    }

    /**
     * Setup trigger tables for testing
     */
    private function setup_trigger_tables() {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create triggers table
        $triggers_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}superforms_triggers (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            scope VARCHAR(50) NOT NULL DEFAULT 'form',
            scope_id BIGINT(20),
            form_id BIGINT(20),
            event_id VARCHAR(100) NOT NULL,
            conditions TEXT,
            actions TEXT NOT NULL,
            priority INT(11) DEFAULT 10,
            enabled TINYINT(1) DEFAULT 1,
            created_by BIGINT(20),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_scope (scope, scope_id),
            KEY idx_form_event (form_id, event_id),
            KEY idx_enabled_priority (enabled, priority)
        )";

        dbDelta($triggers_sql);

        // Create logs table
        $logs_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}superforms_trigger_logs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            trigger_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20),
            form_id BIGINT(20),
            event_id VARCHAR(100) NOT NULL,
            action_id VARCHAR(100),
            status VARCHAR(20),
            error_message TEXT,
            context_data TEXT,
            user_id BIGINT(20),
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_trigger (trigger_id),
            KEY idx_entry (entry_id),
            KEY idx_form_event (form_id, event_id),
            KEY idx_executed (executed_at)
        )";

        dbDelta($logs_sql);
    }

    /**
     * Test firing event with no triggers
     */
    public function test_fire_event_no_triggers() {
        $context = [
            'form_id' => $this->form_id,
            'user_id' => $this->user_id
        ];

        $results = SUPER_Trigger_Executor::fire_event('form.submitted', $context);

        $this->assertEmpty($results, 'Should return empty results when no triggers exist');
    }

    /**
     * Test firing event with matching trigger
     */
    public function test_fire_event_with_matching_trigger() {
        global $wpdb;

        // Create a trigger
        $trigger_id = $wpdb->insert(
            "{$wpdb->prefix}superforms_triggers",
            [
                'scope' => 'form',
                'scope_id' => $this->form_id,
                'form_id' => $this->form_id,
                'event_id' => 'form.submitted',
                'conditions' => json_encode([]),
                'actions' => json_encode([
                    [
                        'id' => 'log_message',
                        'config' => ['message' => 'Test log']
                    ]
                ]),
                'enabled' => 1,
                'created_by' => 1
            ]
        );

        // Mock the log_message action
        add_filter('super_trigger_action_log_message', function($result, $context, $config) {
            return ['success' => true, 'message' => $config['message']];
        }, 10, 3);

        $context = [
            'form_id' => $this->form_id,
            'user_id' => $this->user_id
        ];

        $results = SUPER_Trigger_Executor::fire_event('form.submitted', $context);

        $this->assertNotEmpty($results, 'Should have results from trigger execution');
        $this->assertEquals('success', $results[0]['status'], 'Trigger should execute successfully');
    }

    /**
     * Test trigger with conditions
     */
    public function test_trigger_with_conditions() {
        global $wpdb;

        // Create trigger with conditions
        $trigger_id = $wpdb->insert(
            "{$wpdb->prefix}superforms_triggers",
            [
                'scope' => 'form',
                'scope_id' => $this->form_id,
                'event_id' => 'form.submitted',
                'conditions' => json_encode([
                    'type' => 'AND',
                    'rules' => [
                        [
                            'field' => 'email',
                            'operator' => 'contains',
                            'value' => 'test.com'
                        ]
                    ]
                ]),
                'actions' => json_encode([
                    ['id' => 'log_message', 'config' => ['message' => 'Condition met']]
                ]),
                'enabled' => 1
            ]
        );

        // Test with matching condition
        $matching_context = [
            'form_id' => $this->form_id,
            'form_data' => ['email' => 'user@test.com']
        ];

        $results = SUPER_Trigger_Executor::fire_event('form.submitted', $matching_context);
        $this->assertNotEmpty($results, 'Should execute when conditions match');

        // Test with non-matching condition
        $non_matching_context = [
            'form_id' => $this->form_id,
            'form_data' => ['email' => 'user@example.org']
        ];

        $results = SUPER_Trigger_Executor::fire_event('form.submitted', $non_matching_context);
        $this->assertEmpty($results, 'Should not execute when conditions do not match');
    }

    /**
     * Test trigger priority ordering
     */
    public function test_trigger_priority() {
        global $wpdb;

        $execution_order = [];

        // Create triggers with different priorities
        $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'global',
            'event_id' => 'form.submitted',
            'priority' => 20, // Lower priority
            'actions' => json_encode([
                ['id' => 'log_message', 'config' => ['message' => 'Second']]
            ]),
            'enabled' => 1
        ]);

        $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'global',
            'event_id' => 'form.submitted',
            'priority' => 5, // Higher priority
            'actions' => json_encode([
                ['id' => 'log_message', 'config' => ['message' => 'First']]
            ]),
            'enabled' => 1
        ]);

        // Mock action to track order
        add_filter('super_trigger_action_log_message', function($result, $context, $config) use (&$execution_order) {
            $execution_order[] = $config['message'];
            return ['success' => true];
        }, 10, 3);

        SUPER_Trigger_Executor::fire_event('form.submitted', ['form_id' => $this->form_id]);

        $this->assertEquals(['First', 'Second'], $execution_order, 'Triggers should execute in priority order');
    }

    /**
     * Test scope filtering
     */
    public function test_scope_filtering() {
        global $wpdb;

        // Create global trigger
        $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'global',
            'event_id' => 'form.submitted',
            'actions' => json_encode([
                ['id' => 'log_message', 'config' => ['message' => 'Global trigger']]
            ]),
            'enabled' => 1
        ]);

        // Create form-specific trigger
        $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'form',
            'scope_id' => $this->form_id,
            'form_id' => $this->form_id,
            'event_id' => 'form.submitted',
            'actions' => json_encode([
                ['id' => 'log_message', 'config' => ['message' => 'Form trigger']]
            ]),
            'enabled' => 1
        ]);

        // Create trigger for different form
        $other_form_id = wp_insert_post([
            'post_type' => 'super_form',
            'post_status' => 'publish'
        ]);

        $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'form',
            'scope_id' => $other_form_id,
            'form_id' => $other_form_id,
            'event_id' => 'form.submitted',
            'actions' => json_encode([
                ['id' => 'log_message', 'config' => ['message' => 'Other form trigger']]
            ]),
            'enabled' => 1
        ]);

        $messages = [];
        add_filter('super_trigger_action_log_message', function($result, $context, $config) use (&$messages) {
            $messages[] = $config['message'];
            return ['success' => true];
        }, 10, 3);

        // Fire event for our test form
        SUPER_Trigger_Executor::fire_event('form.submitted', [
            'form_id' => $this->form_id,
            'user_id' => $this->user_id
        ]);

        $this->assertContains('Global trigger', $messages, 'Global trigger should execute');
        $this->assertContains('Form trigger', $messages, 'Form-specific trigger should execute');
        $this->assertNotContains('Other form trigger', $messages, 'Other form trigger should not execute');
    }

    /**
     * Test disabled triggers are skipped
     */
    public function test_disabled_triggers_skipped() {
        global $wpdb;

        $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'global',
            'event_id' => 'form.submitted',
            'actions' => json_encode([
                ['id' => 'log_message', 'config' => ['message' => 'Should not execute']]
            ]),
            'enabled' => 0 // Disabled
        ]);

        $executed = false;
        add_filter('super_trigger_action_log_message', function() use (&$executed) {
            $executed = true;
            return ['success' => true];
        });

        SUPER_Trigger_Executor::fire_event('form.submitted', ['form_id' => $this->form_id]);

        $this->assertFalse($executed, 'Disabled trigger should not execute');
    }

    /**
     * Test error handling in actions
     */
    public function test_action_error_handling() {
        global $wpdb;

        $trigger_id = $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'global',
            'event_id' => 'form.submitted',
            'actions' => json_encode([
                ['id' => 'failing_action', 'config' => []]
            ]),
            'enabled' => 1
        ]);

        // Mock failing action
        add_filter('super_trigger_action_failing_action', function() {
            return new WP_Error('action_failed', 'Test error message');
        });

        $results = SUPER_Trigger_Executor::fire_event('form.submitted', ['form_id' => $this->form_id]);

        $this->assertNotEmpty($results, 'Should return results even with errors');
        $this->assertEquals('error', $results[0]['status'], 'Should report error status');
        $this->assertStringContainsString('Test error message', $results[0]['error'], 'Should include error message');

        // Check error was logged
        $log = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}superforms_trigger_logs ORDER BY id DESC LIMIT 1"
        );

        $this->assertNotNull($log, 'Error should be logged');
        $this->assertEquals('error', $log->status, 'Log should show error status');
        $this->assertStringContainsString('Test error message', $log->error_message);
    }

    /**
     * Test abort submission action
     */
    public function test_abort_submission_action() {
        $context = [
            'form_id' => $this->form_id,
            'event_id' => 'form.spam_detected',
            'spam_method' => 'honeypot',
            'spam_score' => 1.0,
            'uploaded_files' => [
                ['path' => '/tmp/test.jpg', 'field_name' => 'upload']
            ]
        ];

        // Create mock abort action
        $action = new SUPER_Action_Abort_Submission();
        $config = [
            'cleanup_files' => true,
            'cleanup_session' => false,
            'user_message' => 'Your submission was detected as spam',
            'log_abort' => true
        ];

        $result = $action->execute($context, $config);

        $this->assertTrue($result['success'], 'Abort action should succeed');
        $this->assertTrue($result['abort'], 'Should set abort flag');
        $this->assertEquals('Your submission was detected as spam', $result['user_message']);
        $this->assertEquals(1, $result['files_cleaned'], 'Should report cleaned files');
    }

    /**
     * Test context variable replacement in actions
     */
    public function test_context_variable_replacement() {
        $context = [
            'form_id' => $this->form_id,
            'user_name' => 'John Doe',
            'email' => 'john@example.com',
            'form_data' => [
                'subject' => 'Test Subject'
            ]
        ];

        $config = [
            'message' => 'Hello {user_name}, your email is {email} and subject is {form_data.subject}'
        ];

        $replaced = SUPER_Trigger_Executor::replace_context_variables($config['message'], $context);

        $this->assertEquals(
            'Hello John Doe, your email is john@example.com and subject is Test Subject',
            $replaced,
            'Context variables should be replaced'
        );
    }

    /**
     * Test logging successful execution
     */
    public function test_successful_execution_logging() {
        global $wpdb;

        $trigger_id = $wpdb->insert("{$wpdb->prefix}superforms_triggers", [
            'scope' => 'global',
            'event_id' => 'form.submitted',
            'actions' => json_encode([
                ['id' => 'log_message', 'config' => ['message' => 'Success']]
            ]),
            'enabled' => 1
        ]);

        add_filter('super_trigger_action_log_message', function() {
            return ['success' => true, 'logged' => true];
        });

        SUPER_Trigger_Executor::fire_event('form.submitted', [
            'form_id' => $this->form_id,
            'entry_id' => 123
        ]);

        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}superforms_trigger_logs
            WHERE trigger_id = %d",
            $wpdb->insert_id
        ));

        $this->assertNotNull($log, 'Execution should be logged');
        $this->assertEquals('success', $log->status, 'Should log success status');
        $this->assertEquals(123, $log->entry_id, 'Should log entry ID');
        $this->assertEquals($this->form_id, $log->form_id, 'Should log form ID');
    }

    /**
     * Cleanup after each test
     */
    public function tearDown() {
        parent::tearDown();

        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}superforms_triggers");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}superforms_trigger_logs");
    }
}