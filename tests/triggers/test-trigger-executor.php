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
	 * Created trigger IDs for cleanup
	 */
	private static $created_trigger_ids = array();

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
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

		// Clear any existing test triggers
		$this->cleanup_triggers();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		$this->cleanup_triggers();
		parent::tearDown();
	}

	/**
	 * Cleanup test triggers
	 */
	private function cleanup_triggers() {
		if ( class_exists( 'SUPER_Trigger_DAL' ) && ! empty( self::$created_trigger_ids ) ) {
			foreach ( self::$created_trigger_ids as $trigger_id ) {
				SUPER_Trigger_DAL::delete_trigger( $trigger_id );
			}
			self::$created_trigger_ids = array();
		}
	}

	/**
	 * Helper to create a trigger using DAL
	 */
	private function create_test_trigger( $data, $actions = array() ) {
		$defaults = array(
			'trigger_name' => 'Test Trigger ' . uniqid(),
			'scope' => 'form',
			'scope_id' => $this->form_id,
			'event_id' => 'form.submitted',
			'enabled' => 1,
			'execution_order' => 10,
		);

		$trigger_data = array_merge( $defaults, $data );
		$trigger_id = SUPER_Trigger_DAL::create_trigger( $trigger_data );

		if ( ! is_wp_error( $trigger_id ) ) {
			self::$created_trigger_ids[] = $trigger_id;

			// Add actions
			foreach ( $actions as $action ) {
				$action_defaults = array(
					'execution_order' => 1,
					'enabled' => 1,
				);
				$action_data = array_merge( $action_defaults, $action );
				SUPER_Trigger_DAL::create_action( $trigger_id, $action_data );
			}
		}

		return $trigger_id;
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
		// Create a trigger with log_message action
		$trigger_id = $this->create_test_trigger(
			[
				'scope' => 'form',
				'scope_id' => $this->form_id,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Test log', 'log_level' => 'debug']),
				]
			]
		);

		$this->assertNotWPError($trigger_id, 'Trigger should be created successfully');

		$context = [
			'form_id' => $this->form_id,
			'user_id' => $this->user_id
		];

		$results = SUPER_Trigger_Executor::fire_event('form.submitted', $context);

		$this->assertNotEmpty($results, 'Should have results from trigger execution');
	}

	/**
	 * Test trigger with conditions
	 */
	public function test_trigger_with_conditions() {
		// Create trigger with conditions
		$trigger_id = $this->create_test_trigger(
			[
				'scope' => 'form',
				'scope_id' => $this->form_id,
				'event_id' => 'form.submitted',
				'conditions' => json_encode([
					'operator' => 'AND',
					'rules' => [
						[
							'field' => '{email}',
							'operator' => 'contains',
							'value' => 'test.com'
						]
					]
				]),
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Condition met', 'log_level' => 'debug']),
				]
			]
		);

		$this->assertNotWPError($trigger_id);

		// Test with matching condition
		$matching_context = [
			'form_id' => $this->form_id,
			'data' => ['email' => ['value' => 'user@test.com']]
		];

		$results = SUPER_Trigger_Executor::fire_event('form.submitted', $matching_context);
		$this->assertNotEmpty($results, 'Should execute when conditions match');

		// Test with non-matching condition
		$non_matching_context = [
			'form_id' => $this->form_id,
			'data' => ['email' => ['value' => 'user@example.org']]
		];

		$results = SUPER_Trigger_Executor::fire_event('form.submitted', $non_matching_context);
		$this->assertEmpty($results, 'Should not execute when conditions do not match');
	}

	/**
	 * Test trigger execution_order ordering
	 */
	public function test_trigger_priority() {
		$execution_order = [];

		// Create trigger with lower priority (higher execution_order = later)
		$this->create_test_trigger(
			[
				'scope' => 'global',
				'scope_id' => null,
				'event_id' => 'form.submitted',
				'execution_order' => 20,
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Second', 'log_level' => 'debug']),
				]
			]
		);

		// Create trigger with higher priority (lower execution_order = first)
		$this->create_test_trigger(
			[
				'scope' => 'global',
				'scope_id' => null,
				'event_id' => 'form.submitted',
				'execution_order' => 5,
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'First', 'log_level' => 'debug']),
				]
			]
		);

		// Track action execution order
		add_action('super_trigger_action_executed', function($action_type, $result, $context, $config) use (&$execution_order) {
			if ($action_type === 'log_message' && isset($config['message'])) {
				$execution_order[] = $config['message'];
			}
		}, 10, 4);

		SUPER_Trigger_Executor::fire_event('form.submitted', ['form_id' => $this->form_id]);

		$this->assertEquals(['First', 'Second'], $execution_order, 'Triggers should execute in priority order');
	}

	/**
	 * Test scope filtering
	 */
	public function test_scope_filtering() {
		// Create global trigger
		$this->create_test_trigger(
			[
				'scope' => 'global',
				'scope_id' => null,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Global trigger', 'log_level' => 'debug']),
				]
			]
		);

		// Create form-specific trigger
		$this->create_test_trigger(
			[
				'scope' => 'form',
				'scope_id' => $this->form_id,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Form trigger', 'log_level' => 'debug']),
				]
			]
		);

		// Create trigger for different form
		$other_form_id = wp_insert_post([
			'post_type' => 'super_form',
			'post_status' => 'publish'
		]);

		$this->create_test_trigger(
			[
				'scope' => 'form',
				'scope_id' => $other_form_id,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Other form trigger', 'log_level' => 'debug']),
				]
			]
		);

		$messages = [];
		add_action('super_trigger_action_executed', function($action_type, $result, $context, $config) use (&$messages) {
			if ($action_type === 'log_message' && isset($config['message'])) {
				$messages[] = $config['message'];
			}
		}, 10, 4);

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
		$this->create_test_trigger(
			[
				'scope' => 'global',
				'scope_id' => null,
				'event_id' => 'form.submitted',
				'enabled' => 0, // Disabled
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Should not execute', 'log_level' => 'debug']),
				]
			]
		);

		$executed = false;
		add_action('super_trigger_action_executed', function() use (&$executed) {
			$executed = true;
		});

		SUPER_Trigger_Executor::fire_event('form.submitted', ['form_id' => $this->form_id]);

		$this->assertFalse($executed, 'Disabled trigger should not execute');
	}

	/**
	 * Test error handling in actions
	 */
	public function test_action_error_handling() {
		// Create trigger with webhook action that will fail
		$trigger_id = $this->create_test_trigger(
			[
				'scope' => 'global',
				'scope_id' => null,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'webhook',
					'action_config' => json_encode([
						'url' => 'http://invalid-url-that-will-fail.local/test',
						'method' => 'POST'
					]),
				]
			]
		);

		$results = SUPER_Trigger_Executor::fire_event('form.submitted', ['form_id' => $this->form_id]);

		$this->assertNotEmpty($results, 'Should return results even with errors');
	}

	/**
	 * Test successful execution logging
	 */
	public function test_successful_execution_logging() {
		global $wpdb;

		$trigger_id = $this->create_test_trigger(
			[
				'scope' => 'form',
				'scope_id' => $this->form_id,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Test logging', 'log_level' => 'debug']),
				]
			]
		);

		SUPER_Trigger_Executor::fire_event('form.submitted', [
			'form_id' => $this->form_id,
			'entry_id' => 999
		]);

		// Check execution was logged
		$log = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}superforms_trigger_logs WHERE trigger_id = %d ORDER BY id DESC LIMIT 1",
				$trigger_id
			)
		);

		$this->assertNotNull($log, 'Execution should be logged');
	}

	/**
	 * Test multiple actions execute in order
	 */
	public function test_multiple_actions_order() {
		$action_order = [];

		$trigger_id = $this->create_test_trigger(
			[
				'scope' => 'global',
				'scope_id' => null,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'First action', 'log_level' => 'debug']),
					'execution_order' => 1,
				],
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Second action', 'log_level' => 'debug']),
					'execution_order' => 2,
				],
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Third action', 'log_level' => 'debug']),
					'execution_order' => 3,
				]
			]
		);

		add_action('super_trigger_action_executed', function($action_type, $result, $context, $config) use (&$action_order) {
			if ($action_type === 'log_message' && isset($config['message'])) {
				$action_order[] = $config['message'];
			}
		}, 10, 4);

		SUPER_Trigger_Executor::fire_event('form.submitted', ['form_id' => $this->form_id]);

		$this->assertEquals(
			['First action', 'Second action', 'Third action'],
			$action_order,
			'Actions should execute in order'
		);
	}

	/**
	 * Test execution mode detection
	 */
	public function test_execution_mode_detection() {
		// Test sync-only action
		$sync_mode = SUPER_Trigger_Executor::determine_execution_mode('abort_submission', []);
		$this->assertEquals('sync', $sync_mode, 'abort_submission should always be sync');

		// Test async-preferred action
		$async_mode = SUPER_Trigger_Executor::determine_execution_mode('webhook', []);
		$this->assertEquals('async', $async_mode, 'webhook should prefer async');

		// Test default mode
		$default_mode = SUPER_Trigger_Executor::determine_execution_mode('log_message', []);
		$this->assertEquals('sync', $default_mode, 'log_message should default to sync');
	}

	/**
	 * Test context is passed to actions
	 */
	public function test_context_passed_to_actions() {
		$received_context = null;

		$trigger_id = $this->create_test_trigger(
			[
				'scope' => 'global',
				'scope_id' => null,
				'event_id' => 'form.submitted',
			],
			[
				[
					'action_type' => 'log_message',
					'action_config' => json_encode(['message' => 'Context test', 'log_level' => 'debug']),
				]
			]
		);

		add_action('super_trigger_action_executed', function($action_type, $result, $context, $config) use (&$received_context) {
			$received_context = $context;
		}, 10, 4);

		$test_context = [
			'form_id' => $this->form_id,
			'entry_id' => 123,
			'user_id' => $this->user_id,
			'custom_data' => 'test value'
		];

		SUPER_Trigger_Executor::fire_event('form.submitted', $test_context);

		$this->assertNotNull($received_context, 'Context should be passed to actions');
		$this->assertEquals($this->form_id, $received_context['form_id']);
		$this->assertEquals(123, $received_context['entry_id']);
		$this->assertEquals('test value', $received_context['custom_data']);
	}
}
