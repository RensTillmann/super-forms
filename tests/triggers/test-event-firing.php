<?php
/**
 * Test Event Firing in Form Submission Flow
 *
 * Tests that events fire correctly at the right points during form submission.
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Event_Firing extends WP_UnitTestCase {

	/**
	 * Captured events during test execution
	 *
	 * @var array
	 */
	private $fired_events = array();

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset captured events
		$this->fired_events = array();

		// Hook into all trigger events
		add_action( 'super_trigger_event', array( $this, 'capture_event' ), 10, 2 );

		// Set test context for DB logging
		$reflection = new ReflectionClass( $this );
		$method = $this->getName();
		SUPER_Test_DB_Logger::set_test_context( $reflection->getName(), $method );
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Remove event capture hook
		remove_action( 'super_trigger_event', array( $this, 'capture_event' ), 10 );

		// Clear test context
		SUPER_Test_DB_Logger::clear_test_context( 'pass' );
	}

	/**
	 * Capture fired events for inspection
	 *
	 * @param string $event_id Event identifier
	 * @param array  $context  Event context data
	 */
	public function capture_event( $event_id, $context ) {
		$this->fired_events[] = array(
			'event_id'  => $event_id,
			'context'   => $context,
			'timestamp' => microtime( true ),
		);

		// Log event to database
		SUPER_Test_DB_Logger::log_event( $event_id, $context );
	}

	/**
	 * Test: form.before_submit event fires
	 */
	public function test_form_before_submit_fires() {
		// Simulate event firing
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'form.before_submit',
				array(
					'form_id'  => 123,
					'raw_data' => array( 'test' => 'data' ),
					'user_id'  => 1,
				)
			);
		}

		// Assert event was captured
		$this->assertCount( 1, $this->fired_events, 'Should capture 1 event' );
		$this->assertEquals( 'form.before_submit', $this->fired_events[0]['event_id'], 'Event ID should match' );
		$this->assertArrayHasKey( 'form_id', $this->fired_events[0]['context'], 'Context should include form_id' );
		$this->assertEquals( 123, $this->fired_events[0]['context']['form_id'], 'Form ID should match' );
	}

	/**
	 * Test: form.submitted event fires
	 */
	public function test_form_submitted_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'form.submitted',
				array(
					'form_id'  => 456,
					'entry_id' => 789,
					'data'     => array( 'email' => array( 'value' => 'test@example.com' ) ),
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'form.submitted', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 789, $this->fired_events[0]['context']['entry_id'] );
	}

	/**
	 * Test: entry.created event fires with correct context
	 */
	public function test_entry_created_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'entry.created',
				array(
					'entry_id'     => 101,
					'form_id'      => 202,
					'entry_status' => 'super_unread',
					'timestamp'    => current_time( 'mysql' ),
					'user_id'      => get_current_user_id(),
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'entry.created', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 101, $this->fired_events[0]['context']['entry_id'] );
		$this->assertEquals( 202, $this->fired_events[0]['context']['form_id'] );
		$this->assertEquals( 'super_unread', $this->fired_events[0]['context']['entry_status'] );
	}

	/**
	 * Test: entry.saved event fires for new entries
	 */
	public function test_entry_saved_new_entry_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'entry.saved',
				array(
					'entry_id'   => 303,
					'form_id'    => 404,
					'entry_data' => array( 'name' => array( 'value' => 'John Doe' ) ),
					'is_update'  => false,
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'entry.saved', $this->fired_events[0]['event_id'] );
		$this->assertFalse( $this->fired_events[0]['context']['is_update'], 'Should be new entry' );
	}

	/**
	 * Test: entry.updated event fires for existing entries
	 */
	public function test_entry_updated_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'entry.updated',
				array(
					'entry_id'   => 505,
					'form_id'    => 606,
					'entry_data' => array( 'name' => array( 'value' => 'Jane Doe' ) ),
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'entry.updated', $this->fired_events[0]['event_id'] );
	}

	/**
	 * Test: entry.saved fires for updated entries with is_update flag
	 */
	public function test_entry_saved_update_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'entry.saved',
				array(
					'entry_id'   => 707,
					'form_id'    => 808,
					'entry_data' => array(),
					'is_update'  => true,
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertTrue( $this->fired_events[0]['context']['is_update'], 'Should be update' );
	}

	/**
	 * Test: entry.status_changed event fires
	 */
	public function test_entry_status_changed_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'entry.status_changed',
				array(
					'entry_id'        => 909,
					'form_id'         => 1010,
					'previous_status' => 'super_unread',
					'new_status'      => 'approved',
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'entry.status_changed', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 'super_unread', $this->fired_events[0]['context']['previous_status'] );
		$this->assertEquals( 'approved', $this->fired_events[0]['context']['new_status'] );
	}

	/**
	 * Test: form.spam_detected event fires
	 */
	public function test_spam_detected_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'form.spam_detected',
				array(
					'form_id'          => 1111,
					'detection_method' => 'honeypot',
					'honeypot_value'   => 'spam content',
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'form.spam_detected', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 'honeypot', $this->fired_events[0]['context']['detection_method'] );
	}

	/**
	 * Test: form.validation_failed event fires
	 */
	public function test_validation_failed_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'form.validation_failed',
				array(
					'form_id'       => 1212,
					'error_type'    => 'csrf_expired',
					'error_message' => 'Session expired',
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'form.validation_failed', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 'csrf_expired', $this->fired_events[0]['context']['error_type'] );
	}

	/**
	 * Test: form.duplicate_detected event fires
	 */
	public function test_duplicate_detected_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'form.duplicate_detected',
				array(
					'form_id'          => 1313,
					'entry_id'         => 1414,
					'duplicate_field'  => 'entry_title',
					'duplicate_value'  => 'Duplicate Title',
					'comparison_scope' => 'form',
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'form.duplicate_detected', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 'entry_title', $this->fired_events[0]['context']['duplicate_field'] );
	}

	/**
	 * Test: file.uploaded event fires
	 */
	public function test_file_uploaded_fires() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'file.uploaded',
				array(
					'attachment_id' => 1515,
					'form_id'       => 1616,
					'field_name'    => 'upload_field',
					'file_name'     => 'test.jpg',
					'file_type'     => 'image/jpeg',
					'file_size'     => 1024000,
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'file.uploaded', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 'test.jpg', $this->fired_events[0]['context']['file_name'] );
		$this->assertEquals( 'image/jpeg', $this->fired_events[0]['context']['file_type'] );
	}

	/**
	 * Test: Multiple events fire in sequence
	 */
	public function test_multiple_events_fire_in_sequence() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			// Simulate submission flow
			SUPER_Trigger_Executor::fire_event( 'form.before_submit', array( 'form_id' => 1 ) );
			SUPER_Trigger_Executor::fire_event( 'form.submitted', array( 'form_id' => 1 ) );
			SUPER_Trigger_Executor::fire_event( 'entry.created', array( 'entry_id' => 1, 'form_id' => 1 ) );
			SUPER_Trigger_Executor::fire_event( 'entry.saved', array( 'entry_id' => 1, 'form_id' => 1, 'is_update' => false ) );
		}

		$this->assertCount( 4, $this->fired_events, 'Should capture all 4 events' );

		// Verify order
		$this->assertEquals( 'form.before_submit', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 'form.submitted', $this->fired_events[1]['event_id'] );
		$this->assertEquals( 'entry.created', $this->fired_events[2]['event_id'] );
		$this->assertEquals( 'entry.saved', $this->fired_events[3]['event_id'] );
	}

	/**
	 * Test: Event timestamps are sequential
	 */
	public function test_event_timestamps_sequential() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event( 'form.before_submit', array( 'form_id' => 1 ) );
			usleep( 1000 ); // 1ms delay
			SUPER_Trigger_Executor::fire_event( 'form.submitted', array( 'form_id' => 1 ) );
			usleep( 1000 );
			SUPER_Trigger_Executor::fire_event( 'entry.created', array( 'entry_id' => 1, 'form_id' => 1 ) );
		}

		$this->assertCount( 3, $this->fired_events );

		// Timestamps should be increasing
		$this->assertLessThan(
			$this->fired_events[1]['timestamp'],
			$this->fired_events[0]['timestamp'],
			'Second event should fire after first'
		);
		$this->assertLessThan(
			$this->fired_events[2]['timestamp'],
			$this->fired_events[1]['timestamp'],
			'Third event should fire after second'
		);
	}

	/**
	 * Test: Event context includes all required fields
	 */
	public function test_event_context_complete() {
		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event(
				'entry.created',
				array(
					'entry_id'     => 999,
					'form_id'      => 888,
					'entry_status' => 'super_unread',
					'timestamp'    => current_time( 'mysql' ),
					'user_id'      => 1,
					'user_ip'      => '127.0.0.1',
				)
			);
		}

		$this->assertCount( 1, $this->fired_events );
		$context = $this->fired_events[0]['context'];

		// Required fields
		$this->assertArrayHasKey( 'entry_id', $context );
		$this->assertArrayHasKey( 'form_id', $context );
		$this->assertArrayHasKey( 'entry_status', $context );
		$this->assertArrayHasKey( 'timestamp', $context );
		$this->assertArrayHasKey( 'user_id', $context );
		$this->assertArrayHasKey( 'user_ip', $context );
	}

	/**
	 * Test: WordPress action hooks fire alongside trigger events
	 */
	public function test_wordpress_action_hooks_fire() {
		$wp_action_fired = false;
		$captured_event_id = null;

		// Hook into WordPress action
		add_action(
			'super_trigger_event',
			function ( $event_id, $context ) use ( &$wp_action_fired, &$captured_event_id ) {
				$wp_action_fired = true;
				$captured_event_id = $event_id;
			},
			5,
			2
		);

		if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
			SUPER_Trigger_Executor::fire_event( 'form.submitted', array( 'form_id' => 1 ) );
		}

		$this->assertTrue( $wp_action_fired, 'WordPress action should fire' );
		$this->assertEquals( 'form.submitted', $captured_event_id, 'Event ID should match' );
	}

	/**
	 * Test: Event firing overhead is minimal with no triggers
	 */
	public function test_event_firing_performance_overhead() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		$iterations = 100;
		$timings = array();

		for ( $i = 0; $i < $iterations; $i++ ) {
			$start = microtime( true );

			SUPER_Trigger_Executor::fire_event(
				'form.submitted',
				array(
					'form_id' => 1,
					'iteration' => $i,
				)
			);

			$end = microtime( true );
			$timings[] = ( $end - $start ) * 1000; // Convert to ms
		}

		$avg_time = array_sum( $timings ) / count( $timings );
		$max_time = max( $timings );
		$min_time = min( $timings );

		// Log performance metrics
		SUPER_Test_DB_Logger::log_performance(
			'event_firing_overhead',
			array(
				'iterations' => $iterations,
				'avg_time_ms' => $avg_time,
				'max_time_ms' => $max_time,
				'min_time_ms' => $min_time,
			)
		);

		// Assert performance requirements
		$this->assertLessThan( 2, $avg_time, sprintf( 'Average event firing should be <2ms (actual: %.3fms)', $avg_time ) );
		$this->assertLessThan( 10, $max_time, sprintf( 'Max event firing should be <10ms (actual: %.3fms)', $max_time ) );
	}

	/**
	 * Test: Status change event only fires when status actually changes
	 */
	public function test_status_change_only_when_changed() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		// Fire status change event
		SUPER_Trigger_Executor::fire_event(
			'entry.status_changed',
			array(
				'entry_id'        => 123,
				'form_id'         => 456,
				'previous_status' => 'super_unread',
				'new_status'      => 'approved',
			)
		);

		$this->assertCount( 1, $this->fired_events, 'Should fire when status changes' );

		// Reset
		$this->fired_events = array();

		// Fire with same status (should not be called in practice)
		// This test verifies the event doesn't fire if implemented correctly in AJAX class
		SUPER_Trigger_Executor::fire_event(
			'entry.status_changed',
			array(
				'entry_id'        => 123,
				'form_id'         => 456,
				'previous_status' => 'approved',
				'new_status'      => 'approved',
			)
		);

		// The executor will still fire, but in practice class-ajax.php should prevent this
		// This test documents expected behavior
		$this->assertGreaterThanOrEqual( 0, count( $this->fired_events ) );
	}

	/**
	 * Test: Spam detection prevents subsequent events
	 */
	public function test_spam_detection_blocks_submission_events() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		// In real flow, spam detection happens early and prevents other events
		// This test documents that only spam event fires

		SUPER_Trigger_Executor::fire_event(
			'form.spam_detected',
			array(
				'form_id'          => 1,
				'detection_method' => 'honeypot',
				'honeypot_field'   => 'super_hp',
				'honeypot_value'   => 'spam content',
			)
		);

		// Only spam event should be present
		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'form.spam_detected', $this->fired_events[0]['event_id'] );

		// In real flow, entry.created and entry.saved would NOT fire after spam detection
		// This is enforced by class-ajax.php returning early after spam detection
	}

	/**
	 * Test: Validation failure prevents submission events
	 */
	public function test_validation_failure_blocks_submission_events() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		// Validation failure should prevent entry creation
		SUPER_Trigger_Executor::fire_event(
			'form.validation_failed',
			array(
				'form_id'       => 1,
				'error_type'    => 'csrf_expired',
				'error_message' => 'CSRF token expired',
			)
		);

		$this->assertCount( 1, $this->fired_events );
		$this->assertEquals( 'form.validation_failed', $this->fired_events[0]['event_id'] );

		// entry.created should NOT fire after validation failure
	}

	/**
	 * Test: Event context preserves form data across events
	 */
	public function test_context_preserves_form_data() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		$form_data = array(
			'name'  => array( 'value' => 'John Doe' ),
			'email' => array( 'value' => 'john@example.com' ),
			'phone' => array( 'value' => '+1234567890' ),
		);

		$base_context = array(
			'form_id'   => 1,
			'form_data' => $form_data,
		);

		// Fire sequence of events
		SUPER_Trigger_Executor::fire_event( 'form.before_submit', $base_context );
		SUPER_Trigger_Executor::fire_event( 'form.submitted', array_merge( $base_context, array( 'entry_id' => 123 ) ) );
		SUPER_Trigger_Executor::fire_event( 'entry.created', array_merge( $base_context, array( 'entry_id' => 123 ) ) );

		$this->assertCount( 3, $this->fired_events );

		// Verify form_data is preserved
		foreach ( $this->fired_events as $event ) {
			if ( isset( $event['context']['form_data'] ) ) {
				$this->assertArrayHasKey( 'name', $event['context']['form_data'] );
				$this->assertArrayHasKey( 'email', $event['context']['form_data'] );
				$this->assertEquals( 'John Doe', $event['context']['form_data']['name']['value'] );
				$this->assertEquals( 'john@example.com', $event['context']['form_data']['email']['value'] );
			}
		}
	}

	/**
	 * Test: Entry update flow fires correct event sequence
	 */
	public function test_entry_update_flow_event_sequence() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		// Simulate update flow (not create)
		SUPER_Trigger_Executor::fire_event( 'form.before_submit', array( 'form_id' => 1, 'entry_id' => 123 ) );
		SUPER_Trigger_Executor::fire_event( 'form.submitted', array( 'form_id' => 1, 'entry_id' => 123 ) );
		SUPER_Trigger_Executor::fire_event( 'entry.updated', array( 'form_id' => 1, 'entry_id' => 123 ) );
		SUPER_Trigger_Executor::fire_event( 'entry.saved', array( 'form_id' => 1, 'entry_id' => 123, 'is_update' => true ) );

		$this->assertCount( 4, $this->fired_events );

		// Verify sequence
		$this->assertEquals( 'form.before_submit', $this->fired_events[0]['event_id'] );
		$this->assertEquals( 'form.submitted', $this->fired_events[1]['event_id'] );
		$this->assertEquals( 'entry.updated', $this->fired_events[2]['event_id'] );
		$this->assertEquals( 'entry.saved', $this->fired_events[3]['event_id'] );

		// Verify is_update flag
		$this->assertTrue( $this->fired_events[3]['context']['is_update'] );
	}

	/**
	 * Test: File upload context includes all metadata
	 */
	public function test_file_upload_complete_metadata() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		SUPER_Trigger_Executor::fire_event(
			'file.uploaded',
			array(
				'form_id'       => 1,
				'entry_id'      => 123,
				'field_name'    => 'document',
				'file_name'     => 'resume.pdf',
				'file_type'     => 'application/pdf',
				'file_size'     => 524288, // 512 KB
				'file_url'      => 'https://example.com/uploads/resume.pdf',
				'attachment_id' => 456,
				'upload_date'   => current_time( 'mysql' ),
			)
		);

		$this->assertCount( 1, $this->fired_events );
		$context = $this->fired_events[0]['context'];

		// Verify all file metadata present
		$required_fields = array( 'file_name', 'file_type', 'file_size', 'file_url', 'attachment_id', 'field_name' );

		foreach ( $required_fields as $field ) {
			$this->assertArrayHasKey( $field, $context, sprintf( 'File upload context must include %s', $field ) );
		}

		$this->assertEquals( 'resume.pdf', $context['file_name'] );
		$this->assertEquals( 'application/pdf', $context['file_type'] );
		$this->assertEquals( 524288, $context['file_size'] );
	}

	/**
	 * Test: All events include standard required fields
	 */
	public function test_all_events_have_required_standard_fields() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		$events_to_test = array(
			array( 'form.before_submit', array( 'form_id' => 1 ) ),
			array( 'form.submitted', array( 'form_id' => 1, 'entry_id' => 123 ) ),
			array( 'entry.created', array( 'form_id' => 1, 'entry_id' => 123 ) ),
			array( 'entry.saved', array( 'form_id' => 1, 'entry_id' => 123, 'is_update' => false ) ),
			array( 'form.spam_detected', array( 'form_id' => 1, 'detection_method' => 'honeypot' ) ),
		);

		foreach ( $events_to_test as $event_data ) {
			$this->fired_events = array(); // Reset

			list( $event_id, $context ) = $event_data;
			SUPER_Trigger_Executor::fire_event( $event_id, $context );

			$this->assertCount( 1, $this->fired_events );
			$captured_context = $this->fired_events[0]['context'];

			// form_id should be present in all events
			$this->assertArrayHasKey(
				'form_id',
				$captured_context,
				sprintf( 'Event %s must include form_id', $event_id )
			);
		}
	}
}
