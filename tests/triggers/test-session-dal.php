<?php
/**
 * Session DAL Tests
 *
 * Tests for SUPER_Session_DAL class - progressive session management.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

class Test_Session_DAL extends WP_UnitTestCase {

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure the sessions table exists
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Check if table exists, create if not
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
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Clear all test sessions
		$wpdb->query( "TRUNCATE TABLE {$table}" );

		parent::tearDown();
	}

	// =========================================================================
	// CREATE TESTS
	// =========================================================================

	/**
	 * Test creating a basic session
	 */
	public function test_create_session() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'user_id' => 1,
			'user_ip' => '127.0.0.1',
		) );

		$this->assertIsInt( $session_id );
		$this->assertGreaterThan( 0, $session_id );
	}

	/**
	 * Test creating session with all fields
	 */
	public function test_create_session_with_all_fields() {
		$form_data = array( 'email' => 'test@example.com', 'name' => 'John' );
		$metadata = array( 'first_field' => 'email', 'time_started' => time() );

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'   => 456,
			'user_id'   => 2,
			'user_ip'   => '192.168.1.1',
			'status'    => 'draft',
			'form_data' => $form_data,
			'metadata'  => $metadata,
		) );

		$this->assertIsInt( $session_id );

		// Verify data was stored correctly
		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( 456, $session['form_id'] );
		$this->assertEquals( 2, $session['user_id'] );
		$this->assertEquals( '192.168.1.1', $session['user_ip'] );
		$this->assertEquals( 'draft', $session['status'] );
		$this->assertEquals( $form_data, $session['form_data'] );
		$this->assertEquals( $metadata, $session['metadata'] );
	}

	/**
	 * Test creating guest session (no user_id)
	 */
	public function test_create_guest_session() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'user_ip' => '10.0.0.1',
		) );

		$this->assertIsInt( $session_id );

		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertNull( $session['user_id'] );
		$this->assertEquals( '10.0.0.1', $session['user_ip'] );
	}

	/**
	 * Test create fails without form_id
	 */
	public function test_create_fails_without_form_id() {
		$result = SUPER_Session_DAL::create( array(
			'user_id' => 1,
		) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_form_id', $result->get_error_code() );
	}

	/**
	 * Test session key is auto-generated
	 */
	public function test_session_key_auto_generated() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
		) );

		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertNotEmpty( $session['session_key'] );
		$this->assertEquals( 32, strlen( $session['session_key'] ) );
	}

	/**
	 * Test custom session key
	 */
	public function test_custom_session_key() {
		$custom_key = 'custom_test_key_12345678901234';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'     => 123,
			'session_key' => $custom_key,
		) );

		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( $custom_key, $session['session_key'] );
	}

	/**
	 * Test invalid status defaults to draft
	 */
	public function test_invalid_status_defaults_to_draft() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'status'  => 'invalid_status',
		) );

		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertEquals( 'draft', $session['status'] );
	}

	// =========================================================================
	// READ TESTS
	// =========================================================================

	/**
	 * Test get session by ID
	 */
	public function test_get_session() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertIsArray( $session );
		$this->assertEquals( 123, $session['form_id'] );
		$this->assertEquals( 'draft', $session['status'] );
	}

	/**
	 * Test get session by key
	 */
	public function test_get_session_by_key() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$fetched = SUPER_Session_DAL::get_by_key( $session['session_key'] );

		$this->assertIsArray( $fetched );
		$this->assertEquals( $session_id, $fetched['id'] );
	}

	/**
	 * Test get non-existent session returns null
	 */
	public function test_get_nonexistent_session() {
		$session = SUPER_Session_DAL::get( 99999 );

		$this->assertNull( $session );
	}

	/**
	 * Test JSON fields are decoded
	 */
	public function test_json_fields_decoded() {
		$form_data = array( 'email' => 'test@example.com' );

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'   => 123,
			'form_data' => $form_data,
		) );

		$session = SUPER_Session_DAL::get( $session_id );

		$this->assertIsArray( $session['form_data'] );
		$this->assertIsArray( $session['metadata'] );
		$this->assertEquals( $form_data, $session['form_data'] );
	}

	// =========================================================================
	// UPDATE TESTS
	// =========================================================================

	/**
	 * Test update session
	 */
	public function test_update_session() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );

		$result = SUPER_Session_DAL::update( $session_id, array(
			'status'    => 'submitting',
			'form_data' => array( 'email' => 'test@example.com' ),
		) );

		$this->assertTrue( $result );

		$session = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'submitting', $session['status'] );
		$this->assertEquals( 'test@example.com', $session['form_data']['email'] );
	}

	/**
	 * Test update session by key
	 */
	public function test_update_session_by_key() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$result = SUPER_Session_DAL::update_by_key( $session['session_key'], array(
			'status' => 'completed',
		) );

		$this->assertTrue( $result );

		$updated = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'completed', $updated['status'] );
	}

	/**
	 * Test update resets expiry
	 */
	public function test_update_resets_expiry() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$original_expiry = $session['expires_at'];

		// Wait a second and update
		sleep( 1 );

		SUPER_Session_DAL::update( $session_id, array(
			'form_data' => array( 'name' => 'Updated' ),
		) );

		$updated = SUPER_Session_DAL::get( $session_id );

		// Expiry should be >= original (reset to +24 hours from now)
		$this->assertGreaterThanOrEqual(
			strtotime( $original_expiry ),
			strtotime( $updated['expires_at'] )
		);
	}

	/**
	 * Test update with invalid status is ignored
	 */
	public function test_update_invalid_status_ignored() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );

		SUPER_Session_DAL::update( $session_id, array(
			'status' => 'invalid_status_value',
		) );

		$session = SUPER_Session_DAL::get( $session_id );

		// Status should remain unchanged (draft)
		$this->assertEquals( 'draft', $session['status'] );
	}

	// =========================================================================
	// DELETE TESTS
	// =========================================================================

	/**
	 * Test delete session
	 */
	public function test_delete_session() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );

		$result = SUPER_Session_DAL::delete( $session_id );

		$this->assertTrue( $result );

		$session = SUPER_Session_DAL::get( $session_id );
		$this->assertNull( $session );
	}

	/**
	 * Test delete session by key
	 */
	public function test_delete_session_by_key() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$result = SUPER_Session_DAL::delete_by_key( $session['session_key'] );

		$this->assertTrue( $result );

		$deleted = SUPER_Session_DAL::get( $session_id );
		$this->assertNull( $deleted );
	}

	// =========================================================================
	// LIFECYCLE TESTS
	// =========================================================================

	/**
	 * Test mark completed
	 */
	public function test_mark_completed() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$result = SUPER_Session_DAL::mark_completed( $session['session_key'], 456 );

		$this->assertTrue( $result );

		$updated = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'completed', $updated['status'] );
		$this->assertNotNull( $updated['completed_at'] );
		$this->assertEquals( 456, $updated['metadata']['entry_id'] );
	}

	/**
	 * Test mark completed without entry_id
	 */
	public function test_mark_completed_without_entry() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$result = SUPER_Session_DAL::mark_completed( $session['session_key'] );

		$this->assertTrue( $result );

		$updated = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'completed', $updated['status'] );
		$this->assertNotNull( $updated['completed_at'] );
	}

	/**
	 * Test mark aborted
	 */
	public function test_mark_aborted() {
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );

		$result = SUPER_Session_DAL::mark_aborted( $session['session_key'], 'spam_detected' );

		$this->assertTrue( $result );

		$updated = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'aborted', $updated['status'] );
		$this->assertEquals( 'spam_detected', $updated['metadata']['abort_reason'] );
		$this->assertNotEmpty( $updated['metadata']['aborted_at'] );
	}

	/**
	 * Test mark abandoned
	 */
	public function test_mark_abandoned() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Create a session
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );

		// Manually set last_saved_at to 31 minutes ago
		$old_time = gmdate( 'Y-m-d H:i:s', strtotime( '-31 minutes' ) );
		$wpdb->update( $table, array( 'last_saved_at' => $old_time ), array( 'id' => $session_id ) );

		// Mark abandoned sessions
		$count = SUPER_Session_DAL::mark_abandoned();

		$this->assertEquals( 1, $count );

		$session = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'abandoned', $session['status'] );
	}

	/**
	 * Test mark abandoned doesn't affect recent sessions
	 */
	public function test_mark_abandoned_ignores_recent() {
		// Create a recent session
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );

		// Mark abandoned sessions
		$count = SUPER_Session_DAL::mark_abandoned();

		$this->assertEquals( 0, $count );

		$session = SUPER_Session_DAL::get( $session_id );
		$this->assertEquals( 'draft', $session['status'] );
	}

	// =========================================================================
	// RECOVERY TESTS
	// =========================================================================

	/**
	 * Test find recoverable for logged-in user
	 */
	public function test_find_recoverable_for_user() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'   => 123,
			'user_id'   => 1,
			'form_data' => array( 'name' => 'John' ),
		) );

		$found = SUPER_Session_DAL::find_recoverable( 123, 1 );

		$this->assertIsArray( $found );
		$this->assertEquals( $session_id, $found['id'] );
		$this->assertEquals( 'John', $found['form_data']['name'] );
	}

	/**
	 * Test find recoverable for guest using client_token
	 *
	 * Guest sessions are now identified by client_token (localStorage UUID)
	 * instead of IP address. This ensures we don't recover someone else's
	 * form data on shared computers.
	 */
	public function test_find_recoverable_for_guest() {
		$client_token = 'guest-uuid-test-1234';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id'      => 123,
			'client_token' => $client_token,
			'user_ip'      => '192.168.1.1',
			'form_data'    => array( 'email' => 'guest@example.com' ),
		) );

		$found = SUPER_Session_DAL::find_recoverable( 123, null, $client_token );

		$this->assertIsArray( $found );
		$this->assertEquals( $session_id, $found['id'] );
		$this->assertEquals( $client_token, $found['client_token'] );
	}

	/**
	 * Test find recoverable returns null without client_token for anonymous users
	 *
	 * Anonymous users MUST provide a client_token to recover sessions.
	 * We no longer match by IP address as that could recover someone else's data.
	 */
	public function test_find_recoverable_requires_client_token_for_anonymous() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id'      => 456,
			'client_token' => 'some-uuid',
			'user_ip'      => '192.168.1.100',
		) );

		// Without client_token, anonymous user cannot recover
		$found = SUPER_Session_DAL::find_recoverable( 456, null, null );

		$this->assertNull( $found );
	}

	/**
	 * Test find recoverable returns most recent
	 */
	public function test_find_recoverable_returns_most_recent() {
		// Create older session
		$old_id = SUPER_Session_DAL::create( array(
			'form_id'   => 123,
			'user_id'   => 1,
			'form_data' => array( 'name' => 'Old' ),
		) );

		// Wait and create newer session
		sleep( 1 );

		$new_id = SUPER_Session_DAL::create( array(
			'form_id'   => 123,
			'user_id'   => 1,
			'form_data' => array( 'name' => 'New' ),
		) );

		$found = SUPER_Session_DAL::find_recoverable( 123, 1 );

		$this->assertEquals( $new_id, $found['id'] );
		$this->assertEquals( 'New', $found['form_data']['name'] );
	}

	/**
	 * Test find recoverable ignores completed sessions
	 */
	public function test_find_recoverable_ignores_completed() {
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
	 * Test find recoverable ignores aborted sessions
	 */
	public function test_find_recoverable_ignores_aborted() {
		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'user_id' => 1,
		) );
		$session = SUPER_Session_DAL::get( $session_id );
		SUPER_Session_DAL::mark_aborted( $session['session_key'], 'spam' );

		$found = SUPER_Session_DAL::find_recoverable( 123, 1 );

		$this->assertNull( $found );
	}

	/**
	 * Test find recoverable includes abandoned sessions
	 */
	public function test_find_recoverable_includes_abandoned() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'user_id' => 1,
		) );

		// Mark as abandoned (manually, as mark_abandoned requires old time)
		$wpdb->update( $table, array( 'status' => 'abandoned' ), array( 'id' => $session_id ) );

		$found = SUPER_Session_DAL::find_recoverable( 123, 1 );

		$this->assertIsArray( $found );
		$this->assertEquals( $session_id, $found['id'] );
	}

	/**
	 * Test find recoverable ignores expired sessions
	 */
	public function test_find_recoverable_ignores_expired() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		$session_id = SUPER_Session_DAL::create( array(
			'form_id' => 123,
			'user_id' => 1,
		) );

		// Set expires_at to past
		$wpdb->update(
			$table,
			array( 'expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ) ),
			array( 'id' => $session_id )
		);

		$found = SUPER_Session_DAL::find_recoverable( 123, 1 );

		$this->assertNull( $found );
	}

	// =========================================================================
	// CLEANUP TESTS
	// =========================================================================

	/**
	 * Test cleanup expired sessions
	 */
	public function test_cleanup_expired() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Create an expired session
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$wpdb->update(
			$table,
			array( 'expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ) ),
			array( 'id' => $session_id )
		);

		// Create a non-expired session
		$active_id = SUPER_Session_DAL::create( array( 'form_id' => 456 ) );

		$deleted = SUPER_Session_DAL::cleanup_expired();

		$this->assertEquals( 1, $deleted );

		// Expired should be deleted
		$this->assertNull( SUPER_Session_DAL::get( $session_id ) );

		// Active should remain
		$this->assertNotNull( SUPER_Session_DAL::get( $active_id ) );
	}

	/**
	 * Test cleanup completed sessions
	 */
	public function test_cleanup_completed() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Create an old completed session
		$session_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $session_id );
		SUPER_Session_DAL::mark_completed( $session['session_key'] );

		// Set completed_at to 8 days ago
		$wpdb->update(
			$table,
			array( 'completed_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-8 days' ) ) ),
			array( 'id' => $session_id )
		);

		// Create a recent completed session
		$recent_id = SUPER_Session_DAL::create( array( 'form_id' => 456 ) );
		$recent = SUPER_Session_DAL::get( $recent_id );
		SUPER_Session_DAL::mark_completed( $recent['session_key'] );

		$deleted = SUPER_Session_DAL::cleanup_completed( 7 );

		$this->assertEquals( 1, $deleted );

		// Old should be deleted
		$this->assertNull( SUPER_Session_DAL::get( $session_id ) );

		// Recent should remain
		$this->assertNotNull( SUPER_Session_DAL::get( $recent_id ) );
	}

	// =========================================================================
	// STATISTICS TESTS
	// =========================================================================

	/**
	 * Test get form statistics
	 */
	public function test_get_form_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Create sessions with various statuses
		SUPER_Session_DAL::create( array( 'form_id' => 123, 'status' => 'draft' ) );
		SUPER_Session_DAL::create( array( 'form_id' => 123, 'status' => 'draft' ) );

		$completed_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $completed_id );
		SUPER_Session_DAL::mark_completed( $session['session_key'] );

		$aborted_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $aborted_id );
		SUPER_Session_DAL::mark_aborted( $session['session_key'], 'spam' );

		// Create session for different form (should not be counted)
		SUPER_Session_DAL::create( array( 'form_id' => 999 ) );

		$stats = SUPER_Session_DAL::get_form_stats( 123 );

		$this->assertEquals( 4, $stats['total'] );
		$this->assertEquals( 2, $stats['draft'] );
		$this->assertEquals( 1, $stats['completed'] );
		$this->assertEquals( 1, $stats['aborted'] );
	}

	/**
	 * Test get global statistics
	 */
	public function test_get_global_stats() {
		SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		SUPER_Session_DAL::create( array( 'form_id' => 456 ) );
		SUPER_Session_DAL::create( array( 'form_id' => 789 ) );

		$stats = SUPER_Session_DAL::get_global_stats();

		$this->assertEquals( 3, $stats['total'] );
		$this->assertEquals( 3, $stats['draft'] );
	}

	/**
	 * Test get active session count
	 */
	public function test_get_active_count() {
		// Create active sessions
		SUPER_Session_DAL::create( array( 'form_id' => 123, 'status' => 'draft' ) );
		SUPER_Session_DAL::create( array( 'form_id' => 123, 'status' => 'draft' ) );

		// Create completed session
		$completed_id = SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		$session = SUPER_Session_DAL::get( $completed_id );
		SUPER_Session_DAL::mark_completed( $session['session_key'] );

		$count = SUPER_Session_DAL::get_active_count( 123 );

		$this->assertEquals( 2, $count );
	}

	/**
	 * Test get active count global
	 */
	public function test_get_active_count_global() {
		SUPER_Session_DAL::create( array( 'form_id' => 123 ) );
		SUPER_Session_DAL::create( array( 'form_id' => 456 ) );

		$count = SUPER_Session_DAL::get_active_count();

		$this->assertEquals( 2, $count );
	}
}
