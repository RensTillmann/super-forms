<?php
/**
 * Unit Tests for SUPER_Trigger_Scheduler
 *
 * Tests Action Scheduler integration, exponential backoff, and scheduling.
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Trigger_Scheduler extends WP_UnitTestCase {

	/**
	 * Scheduler instance
	 *
	 * @var SUPER_Trigger_Scheduler
	 */
	private $scheduler;

	/**
	 * Track created action IDs for cleanup
	 *
	 * @var array
	 */
	private static $created_action_ids = array();

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Set test context for DB logging
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			$reflection = new ReflectionClass( $this );
			$method = $this->getName();
			SUPER_Test_DB_Logger::set_test_context( $reflection->getName(), $method );
		}

		// Get scheduler instance
		if ( class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->scheduler = SUPER_Trigger_Scheduler::get_instance();
		}
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		// Clean up scheduled actions
		if ( ! empty( self::$created_action_ids ) && function_exists( 'as_unschedule_action' ) ) {
			foreach ( self::$created_action_ids as $action_id ) {
				try {
					$store = ActionScheduler_Store::instance();
					$store->cancel_action( $action_id );
				} catch ( Exception $e ) {
					// Ignore cleanup errors
				}
			}
			self::$created_action_ids = array();
		}

		// Clear test context
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::clear_test_context( 'pass' );
		}

		parent::tearDown();
	}

	/**
	 * Test: Action Scheduler is available
	 */
	public function test_action_scheduler_is_available() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$is_available = SUPER_Trigger_Scheduler::is_available();

		$this->assertTrue( $is_available, 'Action Scheduler should be available' );
		$this->assertTrue( function_exists( 'as_schedule_single_action' ), 'as_schedule_single_action should exist' );
		$this->assertTrue( function_exists( 'as_get_scheduled_actions' ), 'as_get_scheduled_actions should exist' );
	}

	/**
	 * Test: Scheduler singleton pattern
	 */
	public function test_scheduler_singleton() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$instance1 = SUPER_Trigger_Scheduler::get_instance();
		$instance2 = SUPER_Trigger_Scheduler::get_instance();

		$this->assertSame( $instance1, $instance2, 'Scheduler should return same instance' );
	}

	/**
	 * Test: Exponential backoff calculation
	 *
	 * Formula: min(60 * 2^attempt, 1800)
	 * - Attempt 1: 120s (2 min)
	 * - Attempt 2: 240s (4 min)
	 * - Attempt 3: 480s (8 min)
	 * - Attempt 4: 960s (16 min)
	 * - Attempt 5+: 1800s (30 min cap)
	 */
	public function test_exponential_backoff_calculation() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		// Access private method via reflection
		$reflection = new ReflectionClass( $this->scheduler );
		$method = $reflection->getMethod( 'calculate_retry_delay' );
		$method->setAccessible( true );

		// Test attempt 1: 2^1 * 60 = 120 seconds (2 minutes)
		$delay1 = $method->invoke( $this->scheduler, 1 );
		$this->assertEquals( 120, $delay1, 'Attempt 1 should have 120s delay (2 min)' );

		// Test attempt 2: 2^2 * 60 = 240 seconds (4 minutes)
		$delay2 = $method->invoke( $this->scheduler, 2 );
		$this->assertEquals( 240, $delay2, 'Attempt 2 should have 240s delay (4 min)' );

		// Test attempt 3: 2^3 * 60 = 480 seconds (8 minutes)
		$delay3 = $method->invoke( $this->scheduler, 3 );
		$this->assertEquals( 480, $delay3, 'Attempt 3 should have 480s delay (8 min)' );

		// Test attempt 4: 2^4 * 60 = 960 seconds (16 minutes)
		$delay4 = $method->invoke( $this->scheduler, 4 );
		$this->assertEquals( 960, $delay4, 'Attempt 4 should have 960s delay (16 min)' );

		// Test attempt 5: 2^5 * 60 = 1920, capped to 1800 seconds (30 minutes)
		$delay5 = $method->invoke( $this->scheduler, 5 );
		$this->assertEquals( 1800, $delay5, 'Attempt 5 should be capped at 1800s (30 min)' );

		// Test attempt 10: Should still be capped at 1800
		$delay10 = $method->invoke( $this->scheduler, 10 );
		$this->assertEquals( 1800, $delay10, 'High attempts should be capped at 1800s' );
	}

	/**
	 * Test: Schedule trigger action creates scheduled action
	 */
	public function test_schedule_trigger_action_creates_action() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$trigger_id = 123;
		$action_id = 456;
		$context = array(
			'form_id' => 999,
			'entry_id' => 888,
			'form_data' => array( 'email' => 'test@example.com' ),
		);

		$scheduled_action_id = $this->scheduler->schedule_trigger_action(
			$trigger_id,
			$action_id,
			$context,
			60 // 1 minute delay
		);

		if ( $scheduled_action_id ) {
			self::$created_action_ids[] = $scheduled_action_id;
		}

		$this->assertIsInt( $scheduled_action_id, 'Should return action ID' );
		$this->assertGreaterThan( 0, $scheduled_action_id, 'Action ID should be positive' );

		// Verify it's scheduled
		$is_scheduled = as_has_scheduled_action(
			SUPER_Trigger_Scheduler::HOOK_EXECUTE_ACTION,
			null,
			SUPER_Trigger_Scheduler::GROUP
		);

		$this->assertTrue( $is_scheduled, 'Action should be scheduled' );
	}

	/**
	 * Test: Schedule recurring action
	 */
	public function test_schedule_recurring_action() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$args = array( 'trigger_id' => 789 );

		$scheduled_action_id = $this->scheduler->schedule_recurring(
			time() + 60,
			3600, // 1 hour interval
			SUPER_Trigger_Scheduler::HOOK_EXECUTE_RECURRING,
			$args
		);

		if ( $scheduled_action_id ) {
			self::$created_action_ids[] = $scheduled_action_id;
		}

		$this->assertIsInt( $scheduled_action_id, 'Should return action ID' );
		$this->assertGreaterThan( 0, $scheduled_action_id, 'Action ID should be positive' );
	}

	/**
	 * Test: Get queue stats returns expected format
	 */
	public function test_get_queue_stats_format() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$stats = $this->scheduler->get_queue_stats();

		$this->assertIsArray( $stats, 'Stats should be array' );
		$this->assertArrayHasKey( 'pending', $stats, 'Stats should have pending count' );
		$this->assertArrayHasKey( 'in_progress', $stats, 'Stats should have in_progress count' );
		$this->assertArrayHasKey( 'failed', $stats, 'Stats should have failed count' );
		$this->assertArrayHasKey( 'complete', $stats, 'Stats should have complete count' );

		// All counts should be non-negative integers
		$this->assertGreaterThanOrEqual( 0, $stats['pending'] );
		$this->assertGreaterThanOrEqual( 0, $stats['in_progress'] );
		$this->assertGreaterThanOrEqual( 0, $stats['failed'] );
		$this->assertGreaterThanOrEqual( 0, $stats['complete'] );
	}

	/**
	 * Test: Get pending count
	 */
	public function test_get_pending_count() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		// Get initial count
		$initial_count = $this->scheduler->get_pending_count();

		// Schedule a new action
		$context = array( 'form_id' => 1, 'entry_id' => 1 );
		$scheduled_id = $this->scheduler->schedule_trigger_action( 1, 1, $context, 3600 );

		if ( $scheduled_id ) {
			self::$created_action_ids[] = $scheduled_id;
		}

		// Count should increase
		$new_count = $this->scheduler->get_pending_count();

		$this->assertEquals( $initial_count + 1, $new_count, 'Pending count should increase by 1' );
	}

	/**
	 * Test: Constants are defined correctly
	 */
	public function test_constants_defined() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$this->assertEquals( 'super-forms-triggers', SUPER_Trigger_Scheduler::GROUP );
		$this->assertEquals( 3, SUPER_Trigger_Scheduler::DEFAULT_RETRY_LIMIT );
		$this->assertEquals( 'super_trigger_execute_scheduled_action', SUPER_Trigger_Scheduler::HOOK_EXECUTE_ACTION );
		$this->assertEquals( 'super_execute_delayed_trigger_actions', SUPER_Trigger_Scheduler::HOOK_EXECUTE_DELAYED );
		$this->assertEquals( 'super_trigger_retry_failed_action', SUPER_Trigger_Scheduler::HOOK_RETRY_ACTION );
		$this->assertEquals( 'super_trigger_execute_recurring', SUPER_Trigger_Scheduler::HOOK_EXECUTE_RECURRING );
	}

	/**
	 * Test: Hooks are registered
	 */
	public function test_hooks_registered() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		// Ensure scheduler is initialized
		SUPER_Trigger_Scheduler::get_instance();

		// Check that hooks are registered
		$this->assertGreaterThan(
			0,
			has_action( SUPER_Trigger_Scheduler::HOOK_EXECUTE_ACTION ),
			'HOOK_EXECUTE_ACTION should be registered'
		);

		$this->assertGreaterThan(
			0,
			has_action( SUPER_Trigger_Scheduler::HOOK_EXECUTE_DELAYED ),
			'HOOK_EXECUTE_DELAYED should be registered'
		);

		$this->assertGreaterThan(
			0,
			has_action( SUPER_Trigger_Scheduler::HOOK_RETRY_ACTION ),
			'HOOK_RETRY_ACTION should be registered'
		);

		$this->assertGreaterThan(
			0,
			has_action( SUPER_Trigger_Scheduler::HOOK_EXECUTE_RECURRING ),
			'HOOK_EXECUTE_RECURRING should be registered'
		);
	}

	/**
	 * Test: Should retry logic
	 */
	public function test_should_retry_logic() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		// Access private method via reflection
		$reflection = new ReflectionClass( $this->scheduler );
		$method = $reflection->getMethod( 'should_retry' );
		$method->setAccessible( true );

		// WP_Error should retry
		$wp_error = new WP_Error( 'test_error', 'Test error message' );
		$this->assertTrue( $method->invoke( $this->scheduler, $wp_error ), 'WP_Error should trigger retry' );

		// Array with success=false should retry
		$failed_result = array( 'success' => false );
		$this->assertTrue( $method->invoke( $this->scheduler, $failed_result ), 'Failed result should trigger retry' );

		// Array with success=true should NOT retry
		$success_result = array( 'success' => true );
		$this->assertFalse( $method->invoke( $this->scheduler, $success_result ), 'Successful result should NOT retry' );

		// NULL should retry (unexpected behavior)
		$this->assertTrue( $method->invoke( $this->scheduler, null ), 'NULL result should trigger retry' );
	}

	/**
	 * Test: Cancel trigger actions
	 */
	public function test_cancel_trigger_actions() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$trigger_id = 999;
		$context = array( 'form_id' => 1 );

		// Schedule multiple actions for the same trigger
		$id1 = $this->scheduler->schedule_trigger_action( $trigger_id, 1, $context, 3600 );
		$id2 = $this->scheduler->schedule_trigger_action( $trigger_id, 2, $context, 3600 );

		if ( $id1 ) {
			self::$created_action_ids[] = $id1;
		}
		if ( $id2 ) {
			self::$created_action_ids[] = $id2;
		}

		// Cancel all actions for this trigger
		$cancelled = $this->scheduler->cancel_trigger_actions( $trigger_id );

		// Should have cancelled 2 actions
		$this->assertGreaterThanOrEqual( 2, $cancelled, 'Should cancel at least 2 actions' );
	}

	/**
	 * Test: Performance - scheduling 100 actions
	 */
	public function test_scheduling_performance() {
		if ( ! class_exists( 'SUPER_Trigger_Scheduler' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Scheduler not loaded' );
		}

		$context = array(
			'form_id' => 1,
			'entry_id' => 1,
			'form_data' => array( 'email' => 'test@example.com' ),
		);

		$start = microtime( true );
		$scheduled_ids = array();

		for ( $i = 0; $i < 100; $i++ ) {
			$id = $this->scheduler->schedule_trigger_action(
				$i, // Different trigger IDs
				$i,
				$context,
				3600 + $i // Stagger by 1 second each
			);
			if ( $id ) {
				$scheduled_ids[] = $id;
			}
		}

		$elapsed = ( microtime( true ) - $start ) * 1000; // ms

		// Track for cleanup
		self::$created_action_ids = array_merge( self::$created_action_ids, $scheduled_ids );

		// Log performance
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::log_performance(
				'scheduler_bulk_schedule',
				array(
					'actions_scheduled' => count( $scheduled_ids ),
					'time_ms' => $elapsed,
					'avg_per_action_ms' => $elapsed / 100,
				)
			);
		}

		// All should be scheduled
		$this->assertCount( 100, $scheduled_ids, 'Should schedule 100 actions' );

		// Should complete in reasonable time (< 5 seconds)
		$this->assertLessThan(
			5000,
			$elapsed,
			sprintf( 'Scheduling 100 actions should take <5s (actual: %.2fms)', $elapsed )
		);
	}
}
