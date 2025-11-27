<?php
/**
 * Test Trigger Registry
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Trigger_Registry extends WP_UnitTestCase {

    /**
     * Registry instance
     */
    private $registry;

    /**
     * Setup before each test
     */
    public function setUp(): void {
        parent::setUp();

        // Get registry instance and reset it (clears events/actions/initialized flag)
        $this->registry = SUPER_Trigger_Registry::get_instance();
        $this->registry->reset( false ); // Don't load builtins - tests will do that as needed
    }

    /**
     * Test registry singleton pattern
     */
    public function test_singleton_pattern() {
        $instance1 = SUPER_Trigger_Registry::get_instance();
        $instance2 = SUPER_Trigger_Registry::get_instance();

        $this->assertSame($instance1, $instance2, 'Registry should return same instance');
    }

    /**
     * Test registering an event
     */
    public function test_register_event() {
        $event_id = 'form.test_event';
        $config = [
            'label' => 'Test Event',
            'description' => 'A test event for unit testing',
            'category' => 'form_lifecycle',
            'available_context' => ['form_id', 'user_id'],
            'compatible_actions' => ['send_email', 'log_message']
        ];

        $result = $this->registry->register_event($event_id, $config);

        $this->assertTrue($result, 'Event registration should succeed');

        $events = $this->registry->get_events();
        $this->assertArrayHasKey($event_id, $events, 'Event should be registered');
        $this->assertEquals($config['label'], $events[$event_id]['label'], 'Event label should match');
    }

    /**
     * Test registering duplicate event fails
     */
    public function test_register_duplicate_event_fails() {
        $event_id = 'form.duplicate_test';
        $config = ['label' => 'Test Event'];

        $this->registry->register_event($event_id, $config);

        $this->expectException('Exception');
        $this->expectExceptionMessage('Event already registered');

        $this->registry->register_event($event_id, $config);
    }

    /**
     * Test registering an action
     */
    public function test_register_action() {
        $action_id = 'test_action';
        $action_class = 'SUPER_Action_Test';

        // Mock the action class
        if (!class_exists($action_class)) {
            eval('
                class SUPER_Action_Test extends SUPER_Trigger_Action_Base {
                    public function get_id() { return "test_action"; }
                    public function get_label() { return "Test Action"; }
                    public function execute($context, $config) { return ["success" => true]; }
                }
            ');
        }

        $result = $this->registry->register_action($action_id, $action_class);

        $this->assertTrue($result, 'Action registration should succeed');

        $actions = $this->registry->get_actions();
        $this->assertArrayHasKey($action_id, $actions, 'Action should be registered');
    }

    /**
     * Test getting events by category
     */
    public function test_get_events_by_category() {
        // Register events in different categories
        $this->registry->register_event('form.cat1_event', [
            'category' => 'form_lifecycle',
            'label' => 'Category 1 Event'
        ]);

        $this->registry->register_event('entry.cat2_event', [
            'category' => 'entry_management',
            'label' => 'Category 2 Event'
        ]);

        $this->registry->register_event('form.cat1_event2', [
            'category' => 'form_lifecycle',
            'label' => 'Another Category 1 Event'
        ]);

        $form_events = $this->registry->get_events_by_category('form_lifecycle');

        $this->assertCount(2, $form_events, 'Should have 2 form_lifecycle events');
        $this->assertArrayHasKey('form.cat1_event', $form_events);
        $this->assertArrayHasKey('form.cat1_event2', $form_events);
        $this->assertArrayNotHasKey('entry.cat2_event', $form_events);
    }

    /**
     * Test getting compatible actions for event
     */
    public function test_get_compatible_actions() {
        // Register event with compatible actions
        $this->registry->register_event('form.compat_test', [
            'label' => 'Compatibility Test',
            'compatible_actions' => ['send_email', 'log_message', 'webhook']
        ]);

        $compatible = $this->registry->get_compatible_actions('form.compat_test');

        $this->assertContains('send_email', $compatible, 'send_email should be compatible');
        $this->assertContains('log_message', $compatible, 'log_message should be compatible');
        $this->assertContains('webhook', $compatible, 'webhook should be compatible');
        $this->assertCount(3, $compatible, 'Should have exactly 3 compatible actions');
    }

    /**
     * Test event context validation
     */
    public function test_event_context_validation() {
        $this->registry->register_event('form.context_test', [
            'label' => 'Context Test',
            'available_context' => ['form_id', 'user_id', 'entry_id'],
            'required_context' => ['form_id']
        ]);

        $event = $this->registry->get_event('form.context_test');

        // Test valid context
        $valid_context = ['form_id' => 123, 'user_id' => 456];
        $this->assertTrue(
            $this->registry->validate_event_context('form.context_test', $valid_context),
            'Context with required fields should be valid'
        );

        // Test invalid context (missing required field)
        $invalid_context = ['user_id' => 456];
        $this->assertFalse(
            $this->registry->validate_event_context('form.context_test', $invalid_context),
            'Context without required fields should be invalid'
        );
    }

    /**
     * Test unregistering an event
     */
    public function test_unregister_event() {
        $event_id = 'form.to_remove';

        $this->registry->register_event($event_id, ['label' => 'To Remove']);
        $this->assertArrayHasKey($event_id, $this->registry->get_events());

        $result = $this->registry->unregister_event($event_id);

        $this->assertTrue($result, 'Unregister should succeed');
        $this->assertArrayNotHasKey($event_id, $this->registry->get_events());
    }

    /**
     * Test loading built-in events
     */
    public function test_load_builtin_events() {
        // Clear registry first
        $this->setUp();

        $this->registry->load_builtin_events();

        $events = $this->registry->get_events();

        // Check for key built-in events
        $this->assertArrayHasKey('form.loaded', $events, 'Should have form.loaded event');
        $this->assertArrayHasKey('form.submitted', $events, 'Should have form.submitted event');
        $this->assertArrayHasKey('entry.created', $events, 'Should have entry.created event');
        $this->assertArrayHasKey('session.started', $events, 'Should have session.started event');

        // Verify event structure
        $form_loaded = $events['form.loaded'];
        $this->assertArrayHasKey('label', $form_loaded);
        $this->assertArrayHasKey('category', $form_loaded);
        $this->assertArrayHasKey('description', $form_loaded);
        $this->assertArrayHasKey('available_context', $form_loaded);
    }

    /**
     * Test loading built-in actions
     */
    public function test_load_builtin_actions() {
        $this->registry->load_builtin_actions();

        $actions = $this->registry->get_actions();

        // Check for key built-in actions
        $this->assertArrayHasKey('send_email', $actions, 'Should have send_email action');
        $this->assertArrayHasKey('update_entry_status', $actions, 'Should have update_entry_status action');
        $this->assertArrayHasKey('webhook', $actions, 'Should have webhook action');
        $this->assertArrayHasKey('flow.abort_submission', $actions, 'Should have abort_submission action');
    }

    /**
     * Test third-party extension registration
     */
    public function test_extension_registration() {
        // Simulate third-party registration
        do_action('super_register_triggers', $this->registry);

        // Register custom event via filter
        add_filter('super_trigger_events', function($events) {
            $events['custom.third_party'] = [
                'label' => 'Third Party Event',
                'category' => 'custom',
                'addon' => 'my-addon'
            ];
            return $events;
        });

        $this->registry->refresh_events();

        $events = $this->registry->get_events();
        $this->assertArrayHasKey('custom.third_party', $events, 'Third-party event should be registered');
        $this->assertEquals('my-addon', $events['custom.third_party']['addon'], 'Should track addon source');
    }
}