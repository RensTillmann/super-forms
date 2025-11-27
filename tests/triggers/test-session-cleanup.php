<?php
/**
 * Test Session Cleanup
 *
 * Tests for the session cleanup background jobs.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

class Test_Session_Cleanup extends WP_UnitTestCase {

	/**
	 * Form ID for testing
	 */
	private $form_id;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test form
		$this->form_id = wp_insert_post(
			array(
				'post_type'   => 'super_form',
				'post_status' => 'publish',
				'post_title'  => 'Test Form for Session Cleanup',
			)
		);
	}

	/**
	 * Clean up test environment
	 */
	public function tearDown(): void {
		// Clean up test form
		if ( $this->form_id ) {
			wp_delete_post( $this->form_id, true );
		}

		// Clean up test sessions
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
			$wpdb->query( "DELETE FROM $table WHERE session_key LIKE 'test_%'" );
		}

		parent::tearDown();
	}

	/**
	 * Test that class exists
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'SUPER_Session_Cleanup' ) );
	}

	/**
	 * Test get_stats returns array
	 */
	public function test_get_stats_returns_array() {
		$stats = SUPER_Session_Cleanup::get_stats();
		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'total', $stats );
		$this->assertArrayHasKey( 'active', $stats );
		$this->assertArrayHasKey( 'abandoned', $stats );
		$this->assertArrayHasKey( 'completed', $stats );
	}

	/**
	 * Test cleanup deletes expired sessions
	 */
	public function test_cleanup_deletes_expired_sessions() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Skip if table doesn't exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->markTestSkipped( 'Sessions table does not exist' );
		}

		// Create session with past expires_at
		$wpdb->insert(
			$table,
			array(
				'session_key'   => 'test_expired_' . uniqid(),
				'form_id'       => $this->form_id,
				'status'        => 'draft',
				'expires_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
				'started_at'    => current_time( 'mysql' ),
				'last_saved_at' => current_time( 'mysql' ),
			)
		);
		$session_id = $wpdb->insert_id;

		// Run cleanup
		SUPER_Session_Cleanup::run_cleanup();

		// Verify deleted
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$session_id
			)
		);

		$this->assertNull( $session );
	}

	/**
	 * Test cleanup does not delete non-expired sessions
	 */
	public function test_cleanup_does_not_delete_active_sessions() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Skip if table doesn't exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->markTestSkipped( 'Sessions table does not exist' );
		}

		$key = 'test_active_' . uniqid();

		// Create session with future expires_at
		$wpdb->insert(
			$table,
			array(
				'session_key'   => $key,
				'form_id'       => $this->form_id,
				'status'        => 'draft',
				'expires_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '+23 hours' ) ),
				'started_at'    => current_time( 'mysql' ),
				'last_saved_at' => current_time( 'mysql' ),
			)
		);

		// Run cleanup
		SUPER_Session_Cleanup::run_cleanup();

		// Verify NOT deleted
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE session_key = %s",
				$key
			)
		);

		$this->assertNotNull( $session );

		// Clean up
		$wpdb->delete( $table, array( 'session_key' => $key ) );
	}

	/**
	 * Test abandoned check marks inactive sessions
	 */
	public function test_abandoned_check_marks_inactive_sessions() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Skip if table doesn't exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->markTestSkipped( 'Sessions table does not exist' );
		}

		$key = 'test_abandoned_' . uniqid();

		// Create session with old last_saved_at
		$wpdb->insert(
			$table,
			array(
				'session_key'   => $key,
				'form_id'       => $this->form_id,
				'status'        => 'draft',
				'last_saved_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-45 minutes' ) ),
				'expires_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '+23 hours' ) ),
				'started_at'    => current_time( 'mysql' ),
			)
		);

		// Run abandoned check
		SUPER_Session_Cleanup::run_abandoned_check();

		// Verify marked abandoned
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE session_key = %s",
				$key
			)
		);

		$this->assertNotNull( $session );
		$this->assertEquals( 'abandoned', $session->status );

		// Clean up
		$wpdb->delete( $table, array( 'session_key' => $key ) );
	}

	/**
	 * Test abandoned check does not mark recent sessions
	 */
	public function test_abandoned_check_does_not_mark_recent_sessions() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Skip if table doesn't exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->markTestSkipped( 'Sessions table does not exist' );
		}

		$key = 'test_recent_' . uniqid();

		// Create session with recent last_saved_at
		$wpdb->insert(
			$table,
			array(
				'session_key'   => $key,
				'form_id'       => $this->form_id,
				'status'        => 'draft',
				'last_saved_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) ),
				'expires_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '+23 hours' ) ),
				'started_at'    => current_time( 'mysql' ),
			)
		);

		// Run abandoned check
		SUPER_Session_Cleanup::run_abandoned_check();

		// Verify NOT marked abandoned
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE session_key = %s",
				$key
			)
		);

		$this->assertNotNull( $session );
		$this->assertEquals( 'draft', $session->status );

		// Clean up
		$wpdb->delete( $table, array( 'session_key' => $key ) );
	}

	/**
	 * Test manual cleanup runs both checks
	 */
	public function test_manual_cleanup_returns_stats() {
		$stats = SUPER_Session_Cleanup::manual_cleanup();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'total', $stats );
	}

	/**
	 * Test cleanup does not delete completed sessions
	 */
	public function test_cleanup_does_not_delete_completed_sessions() {
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_sessions';

		// Skip if table doesn't exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->markTestSkipped( 'Sessions table does not exist' );
		}

		$key = 'test_completed_' . uniqid();

		// Create completed session with past expires_at
		$wpdb->insert(
			$table,
			array(
				'session_key'   => $key,
				'form_id'       => $this->form_id,
				'status'        => 'completed', // Completed sessions should not be deleted
				'expires_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
				'started_at'    => current_time( 'mysql' ),
				'last_saved_at' => current_time( 'mysql' ),
				'completed_at'  => current_time( 'mysql' ),
			)
		);

		// Run cleanup
		SUPER_Session_Cleanup::run_cleanup();

		// Verify NOT deleted
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE session_key = %s",
				$key
			)
		);

		$this->assertNotNull( $session );

		// Clean up
		$wpdb->delete( $table, array( 'session_key' => $key ) );
	}
}
