<?php
/**
 * Session AJAX Handler Tests
 *
 * Tests for session management functionality added in Step 2.
 * Tests the underlying logic rather than AJAX wrappers since wp_send_json exits.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

class Test_Session_Ajax extends WP_UnitTestCase {

	/**
	 * Store captured events
	 */
	private $captured_events = array();

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure the sessions table exists
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $table_exists !== $table ) {
			SUPER_Install::install();
		} else {
			// Check if client_token column exists (added in later version)
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
					WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'client_token'",
					DB_NAME,
					$table
				)
			);
			if ( ! $column_exists ) {
				// Add client_token column if missing
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN client_token VARCHAR(36) AFTER user_id" ); // phpcs:ignore

				// Check if index exists before adding
				$index_exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
						WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'client_token_lookup'",
						DB_NAME,
						$table
					)
				);
				if ( ! $index_exists ) {
					$wpdb->query( "ALTER TABLE {$table} ADD INDEX client_token_lookup (client_token, form_id, status)" ); // phpcs:ignore
				}
			}
		}

		// Clear captured events
		$this->captured_events = array();

		// Hook into trigger executor to capture events
		add_action( 'super_trigger_event', array( $this, 'capture_event' ), 10, 2 );
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Clear all test sessions
		$wpdb->query( "TRUNCATE TABLE {$table}" );

		// Remove event capture hook
		remove_action( 'super_trigger_event', array( $this, 'capture_event' ), 10 );

		parent::tearDown();
	}

	/**
	 * Capture fired events for testing
	 */
	public function capture_event( $event_name, $context ) {
		$this->captured_events[] = array(
			'event'   => $event_name,
			'context' => $context,
		);
	}

	// =========================================================================
	// SESSION CREATION LOGIC TESTS
	// =========================================================================

	/**
	 * Test session creation logic (what create_session AJAX does)
	 */
	public function test_create_session_logic() {
		$form_id = 123;
		$user_id = get_current_user_id() ?: null;
		$user_ip = '127.0.0.1';

		// Create session (simulating AJAX handler logic)
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'  => $form_id,
			'user_id'  => $user_id,
			'user_ip'  => $user_ip,
			'metadata' => array(
				'first_field'     => 'email',
				'page_url'        => 'https://example.com/contact',
				'start_timestamp' => time(),
			),
		) );

		$this->assertIsInt( $session_id );
		$this->assertGreaterThan( 0, $session_id );

		$session = SUPER_Session_DAL::get( $session_id );
		$this->assertNotNull( $session );
		$this->assertEquals( 123, $session['form_id'] );
		$this->assertEquals( 'draft', $session['status'] );
		$this->assertEquals( 'email', $session['metadata']['first_field'] );
	}

	/**
	 * Test session resume with existing key (what create_session does when session exists)
	 */
	public function test_create_session_resumes_existing() {
		// Create an existing session
		$existing_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'status'  => 'draft',
		) );
		$existing = SUPER_Session_DAL::get( $existing_id );

		// Simulate checking for existing session (what AJAX handler does)
		$check = SUPER_Session_DAL::get_by_key( $existing['session_key'] );

		$this->assertNotNull( $check );
		$this->assertEquals( 'draft', $check['status'] );
		$this->assertEquals( $existing['session_key'], $check['session_key'] );

		// In AJAX handler, if session exists in draft status, it's resumed
		// No new session is created
		$this->assertEquals( $existing_id, $check['id'] );
	}

	/**
	 * Test session creation stores metadata correctly
	 */
	public function test_create_session_stores_metadata() {
		$metadata = array(
			'first_field'     => 'first_name',
			'page_url'        => 'https://example.com/signup',
			'user_agent'      => 'Mozilla/5.0 Test Browser',
			'start_timestamp' => time(),
		);

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'  => 456,
			'metadata' => $metadata,
		) );

		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( 'first_name', $session['metadata']['first_field'] );
		$this->assertEquals( 'https://example.com/signup', $session['metadata']['page_url'] );
		$this->assertEquals( 'Mozilla/5.0 Test Browser', $session['metadata']['user_agent'] );
		$this->assertNotEmpty( $session['metadata']['start_timestamp'] );
	}

	/**
	 * Test session creation stores client_token and fingerprint
	 *
	 * client_token (UUID in localStorage) is used for session identification.
	 * fingerprint (browser characteristics hash) is stored in metadata for spam detection.
	 */
	public function test_create_session_stores_client_token_and_fingerprint() {
		$client_token = 'client-uuid-1234-5678';
		$fingerprint = 'fp_abc123xyz';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'      => 456,
			'client_token' => $client_token,
			'metadata'     => array(
				'fingerprint' => $fingerprint,
			),
		) );

		$session = SUPER_Session_DAL::get( $session_id );

		// client_token stored in column for efficient lookups
		$this->assertEquals( $client_token, $session['client_token'] );

		// fingerprint stored in metadata for spam detection heuristics
		$this->assertEquals( $fingerprint, $session['metadata']['fingerprint'] );
	}

	/**
	 * Test find_by_client_token method
	 */
	public function test_find_by_client_token() {
		$client_token = 'find-test-uuid-1234';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'      => 555,
			'client_token' => $client_token,
			'form_data'    => array( 'field' => 'test value' ),
		) );

		// Should find session by client_token and form_id
		$found = SUPER_Session_DAL::find_by_client_token( $client_token, 555 );

		$this->assertNotNull( $found );
		$this->assertEquals( 'test value', $found['form_data']['field'] );
		$this->assertEquals( $client_token, $found['client_token'] );
	}

	/**
	 * Test find_by_client_token returns null for wrong form
	 */
	public function test_find_by_client_token_wrong_form() {
		$client_token = 'form-specific-uuid';

		SUPER_Session_DAL::create( array(
			'form_id'      => 111,
			'client_token' => $client_token,
		) );

		// Should not find session for different form
		$found = SUPER_Session_DAL::find_by_client_token( $client_token, 222 );

		$this->assertNull( $found );
	}

	/**
	 * Test find_by_client_token ignores completed sessions
	 */
	public function test_find_by_client_token_ignores_completed() {
		$client_token = 'completed-session-uuid';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'      => 333,
			'client_token' => $client_token,
		) );
		$session = SUPER_Session_DAL::get( $session_id );
		SUPER_Session_DAL::mark_completed( $session['session_key'] );

		// Should not find completed session
		$found = SUPER_Session_DAL::find_by_client_token( $client_token, 333 );

		$this->assertNull( $found );
	}

	// =========================================================================
	// AUTO SAVE LOGIC TESTS
	// =========================================================================

	/**
	 * Test auto-save logic (what auto_save_session AJAX does)
	 */
	public function test_auto_save_session_logic() {
		// Create initial session
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'  => 123,
			'metadata' => array( 'start_timestamp' => time() - 60 ),
		) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Simulate auto-save data
		$form_data = array(
			'email' => 'test@example.com',
			'name'  => 'John Doe',
		);

		// Update metadata (what AJAX handler does)
		$metadata = $session['metadata'] ?: array();
		$metadata['last_field'] = 'email';
		$metadata['fields_count'] = count( array_filter( $form_data, function( $v ) {
			return ! empty( $v );
		} ) );
		$metadata['last_save_timestamp'] = time();

		if ( isset( $metadata['start_timestamp'] ) ) {
			$metadata['time_spent_seconds'] = time() - $metadata['start_timestamp'];
		}

		// Update session
		$result = SUPER_Session_DAL::update_by_key( $session['session_key'], array(
			'form_data' => $form_data,
			'metadata'  => $metadata,
		) );

		$this->assertTrue( $result );

		// Verify update
		$updated = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'test@example.com', $updated['form_data']['email'] );
		$this->assertEquals( 'John Doe', $updated['form_data']['name'] );
		$this->assertEquals( 'email', $updated['metadata']['last_field'] );
		$this->assertEquals( 2, $updated['metadata']['fields_count'] );
		$this->assertGreaterThanOrEqual( 60, $updated['metadata']['time_spent_seconds'] );
	}

	/**
	 * Test auto-save with invalid session key returns error
	 */
	public function test_auto_save_invalid_session_key() {
		$session = SUPER_Session_DAL::get_by_key( 'invalid_key_12345' );

		$this->assertNull( $session );
	}

	/**
	 * Test auto-save updates last_saved_at timestamp
	 */
	public function test_auto_save_updates_timestamp() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );
		$original_saved = $session['last_saved_at'];

		sleep( 1 );

		SUPER_Session_DAL::update_by_key( $session['session_key'], array(
			'form_data' => array( 'email' => 'updated@example.com' ),
		) );

		$updated = SUPER_Session_DAL::get( $session_id );

		$this->assertGreaterThan(
			strtotime( $original_saved ),
			strtotime( $updated['last_saved_at'] ),
			'last_saved_at should be updated'
		);
	}

	// =========================================================================
	// SESSION RECOVERY LOGIC TESTS
	// =========================================================================

	/**
	 * Test recovery check by stored key (what check_session_recovery does)
	 */
	public function test_check_recovery_by_stored_key() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'   => 123,
			'form_data' => array( 'email' => 'saved@example.com' ),
		) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Simulate checking stored key (what AJAX handler does)
		$stored_key = $session['session_key'];
		$found = SUPER_Session_DAL::get_by_key( $stored_key );

		$this->assertNotNull( $found );
		$this->assertEquals( 123, (int) $found['form_id'] );
		$this->assertContains( $found['status'], array( 'draft', 'abandoned' ) );
		$this->assertEquals( 'saved@example.com', $found['form_data']['email'] );
	}

	/**
	 * Test recovery check finds session by user
	 */
	public function test_check_recovery_by_user() {
		$user_id = $this->factory->user->create();

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'   => 456,
			'user_id'   => $user_id,
			'form_data' => array( 'name' => 'User Session' ),
		) );

		// Find recoverable session
		$found = SUPER_Session_DAL::find_recoverable( 456, $user_id );

		$this->assertNotNull( $found );
		$this->assertEquals( 'User Session', $found['form_data']['name'] );
	}

	/**
	 * Test recovery check finds session by client_token for guests
	 *
	 * client_token is a UUID stored in localStorage - unique per browser profile.
	 * This ensures we don't accidentally recover someone else's data on shared computers.
	 */
	public function test_check_recovery_by_client_token() {
		// UUID v4 format: 36 chars like a1b2c3d4-e5f6-7890-1234-567890abcdef
		$client_token = 'a1b2c3d4-e5f6-7890-1234-567890abcdef';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'      => 789,
			'client_token' => $client_token,
			'user_ip'      => '192.168.1.100',
			'form_data'    => array( 'email' => 'guest@example.com' ),
		) );

		// Debug: Check if session was created and has client_token
		$this->assertIsInt( $session_id, 'Session creation should return an int ID' );
		$created_session = SUPER_Session_DAL::get( $session_id );
		$this->assertNotNull( $created_session, 'Created session should be retrievable' );
		$this->assertEquals( $client_token, $created_session['client_token'], 'client_token should be stored in session' );

		// Find recoverable session by client_token
		$found = SUPER_Session_DAL::find_recoverable( 789, null, $client_token );

		$this->assertNotNull( $found, 'find_recoverable should find session by client_token' );
		$this->assertEquals( 'guest@example.com', $found['form_data']['email'] );
		$this->assertEquals( $client_token, $found['client_token'] );
	}

	/**
	 * Test that different client_tokens don't see each other's sessions
	 *
	 * This is the critical test - ensuring that on a shared computer,
	 * different browser profiles (with different client_tokens) won't
	 * accidentally recover each other's form data.
	 */
	public function test_client_token_isolation() {
		$token_alice = 'alice-uuid-1234';
		$token_bob = 'bob-uuid-5678';

		// Alice creates a session
		SUPER_Session_DAL::create( array(
			'form_id'      => 123,
			'client_token' => $token_alice,
			'form_data'    => array( 'name' => 'Alice Secret Data' ),
		) );

		// Bob creates a session on the same form
		SUPER_Session_DAL::create( array(
			'form_id'      => 123,
			'client_token' => $token_bob,
			'form_data'    => array( 'name' => 'Bob Secret Data' ),
		) );

		// Alice should only see her session
		$alice_session = SUPER_Session_DAL::find_recoverable( 123, null, $token_alice );
		$this->assertEquals( 'Alice Secret Data', $alice_session['form_data']['name'] );

		// Bob should only see his session
		$bob_session = SUPER_Session_DAL::find_recoverable( 123, null, $token_bob );
		$this->assertEquals( 'Bob Secret Data', $bob_session['form_data']['name'] );
	}

	/**
	 * Test recovery check returns null when no session exists
	 */
	public function test_check_recovery_no_session() {
		// With no user_id and no client_token, should return null
		$found = SUPER_Session_DAL::find_recoverable( 999, null, null );
		$this->assertNull( $found );

		// With non-matching client_token, should return null
		$found = SUPER_Session_DAL::find_recoverable( 999, null, 'nonexistent-token' );
		$this->assertNull( $found );
	}

	/**
	 * Test recovery check ignores completed sessions
	 */
	public function test_check_recovery_ignores_completed() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'user_id' => 1,
		) );
		$session = SUPER_Session_DAL::get( $session_id );
		SUPER_Session_DAL::mark_completed( $session['session_key'] );

		$found = SUPER_Session_DAL::find_recoverable( 123, 1 );

		$this->assertNull( $found );
	}

	/**
	 * Test recovery check ignores aborted sessions
	 */
	public function test_check_recovery_ignores_aborted() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'user_id' => 1,
		) );
		$session = SUPER_Session_DAL::get( $session_id );
		SUPER_Session_DAL::mark_aborted( $session['session_key'], 'spam' );

		$found = SUPER_Session_DAL::find_recoverable( 123, 1 );

		$this->assertNull( $found );
	}

	// =========================================================================
	// RESUME SESSION LOGIC TESTS
	// =========================================================================

	/**
	 * Test resume session logic (what resume_session AJAX does)
	 */
	public function test_resume_session_logic() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'   => 123,
			'form_data' => array( 'email' => 'resume@example.com', 'phone' => '555-1234' ),
		) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Simulate resume (what AJAX handler does)
		$metadata = $session['metadata'] ?: array();
		$metadata['resumed_at'] = current_time( 'mysql' );
		$metadata['resume_count'] = ( $metadata['resume_count'] ?? 0 ) + 1;

		SUPER_Session_DAL::update_by_key( $session['session_key'], array(
			'status'   => 'draft',
			'metadata' => $metadata,
		) );

		$updated = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( 'draft', $updated['status'] );
		$this->assertEquals( 1, $updated['metadata']['resume_count'] );
		$this->assertNotEmpty( $updated['metadata']['resumed_at'] );
		$this->assertEquals( 'resume@example.com', $updated['form_data']['email'] );
	}

	/**
	 * Test resume increments resume count
	 */
	public function test_resume_increments_count() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'  => 123,
			'metadata' => array( 'resume_count' => 2 ),
		) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Increment resume count
		$metadata = $session['metadata'] ?: array();
		$metadata['resume_count'] = ( $metadata['resume_count'] ?? 0 ) + 1;

		SUPER_Session_DAL::update_by_key( $session['session_key'], array(
			'metadata' => $metadata,
		) );

		$updated = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( 3, $updated['metadata']['resume_count'] );
	}

	// =========================================================================
	// DISMISS SESSION LOGIC TESTS
	// =========================================================================

	/**
	 * Test dismiss session logic (what dismiss_session AJAX does)
	 */
	public function test_dismiss_session_logic() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Simulate dismiss (what AJAX handler does)
		$result = SUPER_Session_DAL::delete( $session['id'] );

		$this->assertTrue( $result );

		// Verify deletion
		$deleted = SUPER_Session_DAL::get( $session_id );
		$this->assertNull( $deleted );
	}

	/**
	 * Test dismiss handles nonexistent session gracefully
	 */
	public function test_dismiss_nonexistent_session() {
		// Trying to get a nonexistent session
		$session = SUPER_Session_DAL::get_by_key( 'nonexistent_key' );

		$this->assertNull( $session );

		// Deleting nonexistent ID should not throw error
		// Note: wpdb->delete() returns 0 for no rows affected, and the DAL returns !== false
		// So it returns true (no error) even when no rows deleted - this is expected behavior
		$result = SUPER_Session_DAL::delete( 999999 );

		// Returns true because no database error occurred (0 !== false is true)
		$this->assertTrue( $result );
	}

	// =========================================================================
	// SESSION COMPLETION ON SUBMISSION TESTS
	// =========================================================================

	/**
	 * Test session marked completed on form submission
	 * This tests the integration point in submit_form()
	 */
	public function test_session_completed_on_submission() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'   => 123,
			'form_data' => array( 'email' => 'submit@example.com' ),
		) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Simulate what submit_form() does after successful submission
		$entry_id = 456;
		SUPER_Session_DAL::mark_completed( $session['session_key'], $entry_id );

		// Verify session was marked completed
		$updated = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( 'completed', $updated['status'] );
		$this->assertNotNull( $updated['completed_at'] );
		$this->assertEquals( 456, $updated['metadata']['entry_id'] );
	}

	/**
	 * Test session completion links to entry
	 */
	public function test_session_completion_links_entry() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$entry_id = 789;
		SUPER_Session_DAL::mark_completed( $session['session_key'], $entry_id );

		$updated = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( $entry_id, $updated['metadata']['entry_id'] );
	}

	/**
	 * Test session completion without entry_id
	 */
	public function test_session_completion_without_entry() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		SUPER_Session_DAL::mark_completed( $session['session_key'] );

		$updated = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( 'completed', $updated['status'] );
		$this->assertNotNull( $updated['completed_at'] );
	}

	// =========================================================================
	// EVENT FIRING TESTS
	// =========================================================================

	/**
	 * Test session.started event context structure
	 */
	public function test_session_started_event_context() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not available' );
		}

		$form_id = 123;
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => $form_id ) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Fire the event (what AJAX handler does)
		SUPER_Trigger_Executor::fire_event( 'session.started', array(
			'form_id'     => $form_id,
			'session_id'  => $session_id,
			'session_key' => $session['session_key'],
			'user_id'     => get_current_user_id(),
			'user_ip'     => '127.0.0.1',
		) );

		// Check event was captured
		$started_events = array_filter( $this->captured_events, function( $e ) {
			return $e['event'] === 'session.started';
		} );

		$this->assertNotEmpty( $started_events, 'session.started event should be fired' );

		$event = array_values( $started_events )[0];
		$this->assertEquals( $form_id, $event['context']['form_id'] );
		$this->assertEquals( $session_id, $event['context']['session_id'] );
		$this->assertEquals( $session['session_key'], $event['context']['session_key'] );
	}

	/**
	 * Test session.auto_saved event context structure
	 */
	public function test_session_auto_saved_event_context() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not available' );
		}

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'  => 123,
			'metadata' => array( 'start_timestamp' => time() - 30 ),
		) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Fire the event (what AJAX handler does after save)
		SUPER_Trigger_Executor::fire_event( 'session.auto_saved', array(
			'form_id'      => 123,
			'session_id'   => $session_id,
			'session_key'  => $session['session_key'],
			'fields_count' => 3,
			'time_spent'   => 30,
		) );

		$saved_events = array_filter( $this->captured_events, function( $e ) {
			return $e['event'] === 'session.auto_saved';
		} );

		$this->assertNotEmpty( $saved_events, 'session.auto_saved event should be fired' );

		$event = array_values( $saved_events )[0];
		$this->assertEquals( 123, $event['context']['form_id'] );
		$this->assertEquals( 3, $event['context']['fields_count'] );
		$this->assertEquals( 30, $event['context']['time_spent'] );
	}

	/**
	 * Test session.resumed event context structure
	 */
	public function test_session_resumed_event_context() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not available' );
		}

		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 456 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Fire the event (what AJAX handler does)
		SUPER_Trigger_Executor::fire_event( 'session.resumed', array(
			'form_id'     => 456,
			'session_id'  => $session_id,
			'session_key' => $session['session_key'],
			'user_id'     => get_current_user_id(),
		) );

		$resumed_events = array_filter( $this->captured_events, function( $e ) {
			return $e['event'] === 'session.resumed';
		} );

		$this->assertNotEmpty( $resumed_events, 'session.resumed event should be fired' );

		$event = array_values( $resumed_events )[0];
		$this->assertEquals( 456, $event['context']['form_id'] );
		$this->assertEquals( $session['session_key'], $event['context']['session_key'] );
	}

	/**
	 * Test session.completed event context structure
	 */
	public function test_session_completed_event_context() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not available' );
		}

		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 789 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Fire the event (what submit_form does)
		SUPER_Trigger_Executor::fire_event( 'session.completed', array(
			'session_id'  => $session_id,
			'session_key' => $session['session_key'],
			'form_id'     => 789,
			'entry_id'    => 1234,
			'timestamp'   => current_time( 'mysql' ),
			'user_id'     => get_current_user_id(),
			'user_ip'     => '127.0.0.1',
		) );

		$completed_events = array_filter( $this->captured_events, function( $e ) {
			return $e['event'] === 'session.completed';
		} );

		$this->assertNotEmpty( $completed_events, 'session.completed event should be fired' );

		$event = array_values( $completed_events )[0];
		$this->assertEquals( 789, $event['context']['form_id'] );
		$this->assertEquals( 1234, $event['context']['entry_id'] );
		$this->assertEquals( $session['session_key'], $event['context']['session_key'] );
	}

	// =========================================================================
	// SECURITY TESTS
	// =========================================================================

	/**
	 * Test session key is properly validated
	 */
	public function test_session_key_validation() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		// Valid key should work
		$valid = SUPER_Session_DAL::get_by_key( $session['session_key'] );
		$this->assertNotNull( $valid );

		// Tampered key should not work
		$tampered = SUPER_Session_DAL::get_by_key( $session['session_key'] . 'tampered' );
		$this->assertNull( $tampered );

		// SQL injection attempt should not work
		$injection = SUPER_Session_DAL::get_by_key( "' OR '1'='1" );
		$this->assertNull( $injection );
	}

	/**
	 * Test form_id validation
	 */
	public function test_form_id_validation() {
		// Creating session without form_id should fail
		$result = SUPER_Session_DAL::create( array(
			'user_id' => 1,
		) );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test session isolation by form_id
	 */
	public function test_session_isolation_by_form() {
		$user_id = 1;

		// Create sessions for different forms
		SUPER_Session_DAL::create( array(
			'form_id'   => 111,
			'user_id'   => $user_id,
			'form_data' => array( 'field' => 'form111' ),
		) );

		SUPER_Session_DAL::create( array(
			'form_id'   => 222,
			'user_id'   => $user_id,
			'form_data' => array( 'field' => 'form222' ),
		) );

		// Recovery should only find session for specific form
		$found111 = SUPER_Session_DAL::find_recoverable( 111, $user_id );
		$found222 = SUPER_Session_DAL::find_recoverable( 222, $user_id );

		$this->assertEquals( 'form111', $found111['form_data']['field'] );
		$this->assertEquals( 'form222', $found222['form_data']['field'] );
	}
}
