<?php
/**
 * Entry DAL Tests
 *
 * Tests for SUPER_Entry_DAL class - Phase 17 custom table entry management.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

class Test_Entry_DAL extends WP_UnitTestCase {

	/**
	 * Test form ID
	 *
	 * @var int
	 */
	private static $test_form_id;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure the entries tables exist
		global $wpdb;
		$entries_table = $wpdb->prefix . 'superforms_entries';

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $entries_table ) );
		if ( $table_exists !== $entries_table ) {
			SUPER_Install::install();
		}

		// Clean up any leftover entries before each test
		$meta_table = $wpdb->prefix . 'superforms_entry_meta';
		$wpdb->query( "DELETE FROM {$meta_table}" );
		$wpdb->query( "DELETE FROM {$entries_table}" );

		// Also clean up any post type entries (for post_type storage mode)
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'super_contact_entry'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_super_contact_entry_%'" );

		// Reset migration state cache to ensure fresh state detection
		if ( method_exists( 'SUPER_Entry_DAL', 'clear_cache' ) ) {
			SUPER_Entry_DAL::clear_cache();
		}

		// Create a test form
		if ( ! self::$test_form_id ) {
			self::$test_form_id = wp_insert_post( array(
				'post_type'   => 'super_form',
				'post_title'  => 'Test Form for Entry DAL',
				'post_status' => 'publish',
			) );
		}
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		global $wpdb;

		// Clear all test entries (use DELETE to handle FK constraints and missing tables)
		$entries_table = $wpdb->prefix . 'superforms_entries';
		$meta_table = $wpdb->prefix . 'superforms_entry_meta';

		// Suppress errors for missing tables
		$wpdb->suppress_errors( true );
		$wpdb->query( "DELETE FROM {$meta_table}" );
		$wpdb->query( "DELETE FROM {$entries_table}" );
		$wpdb->suppress_errors( false );

		parent::tearDown();
	}

	// =========================================================================
	// CREATE TESTS
	// =========================================================================

	/**
	 * Test creating a basic entry
	 */
	public function test_create_entry() {
		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id' => self::$test_form_id,
			'title'   => 'Test Entry',
		) );

		$this->assertIsInt( $entry_id );
		$this->assertGreaterThan( 0, $entry_id );
	}

	/**
	 * Test creating entry with all fields
	 */
	public function test_create_entry_with_all_fields() {
		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id'      => self::$test_form_id,
			'user_id'      => 1,
			'title'        => 'Full Entry',
			'wp_status'    => 'publish',
			'entry_status' => 'pending_review',
			'ip_address'   => '192.168.1.100',
			'user_agent'   => 'Mozilla/5.0 Test Agent',
		) );

		$this->assertIsInt( $entry_id );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		$this->assertEquals( self::$test_form_id, $entry->form_id );
		$this->assertEquals( 1, $entry->user_id );
		$this->assertEquals( 'Full Entry', $entry->title );
		$this->assertEquals( 'publish', $entry->wp_status );
		$this->assertEquals( 'pending_review', $entry->entry_status );
		$this->assertEquals( '192.168.1.100', $entry->ip_address );
		$this->assertEquals( 'Mozilla/5.0 Test Agent', $entry->user_agent );
	}

	/**
	 * Test creating guest entry (no user_id)
	 */
	public function test_create_guest_entry() {
		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id'    => self::$test_form_id,
			'ip_address' => '10.0.0.1',
		) );

		$this->assertIsInt( $entry_id );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		$this->assertEquals( 0, $entry->user_id );
		$this->assertEquals( '10.0.0.1', $entry->ip_address );
	}

	/**
	 * Test create fails without form_id
	 */
	public function test_create_fails_without_form_id() {
		$result = SUPER_Entry_DAL::create( array(
			'title' => 'No Form Entry',
		) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_form_id', $result->get_error_code() );
	}

	/**
	 * Test timestamps are auto-generated
	 */
	public function test_timestamps_auto_generated() {
		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id' => self::$test_form_id,
		) );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		$this->assertNotEmpty( $entry->created_at );
		$this->assertNotEmpty( $entry->updated_at );
		$this->assertNotEmpty( $entry->created_at_gmt );
		$this->assertNotEmpty( $entry->updated_at_gmt );
	}

	/**
	 * Test default wp_status is publish
	 */
	public function test_default_status_is_publish() {
		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id' => self::$test_form_id,
		) );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		$this->assertEquals( 'publish', $entry->wp_status );
	}

	// =========================================================================
	// READ TESTS
	// =========================================================================

	/**
	 * Test get entry by ID
	 */
	public function test_get_entry() {
		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id' => self::$test_form_id,
			'title'   => 'Test Get Entry',
		) );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		$this->assertIsObject( $entry );
		$this->assertEquals( self::$test_form_id, $entry->form_id );
		$this->assertEquals( 'Test Get Entry', $entry->title );
	}

	/**
	 * Test get non-existent entry returns WP_Error
	 */
	public function test_get_nonexistent_entry() {
		$entry = SUPER_Entry_DAL::get( 99999 );

		$this->assertInstanceOf( WP_Error::class, $entry );
		$this->assertEquals( 'entry_not_found', $entry->get_error_code() );
	}

	/**
	 * Test query entries
	 */
	public function test_query_entries() {
		// Create test entries
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'Entry 1' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'Entry 2' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'Entry 3' ) );

		$entries = SUPER_Entry_DAL::query();

		$this->assertCount( 3, $entries );
	}

	/**
	 * Test query with form_id filter
	 */
	public function test_query_by_form_id() {
		// Create entries for different forms
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		// Create another form
		$other_form_id = wp_insert_post( array(
			'post_type'   => 'super_form',
			'post_title'  => 'Other Form',
			'post_status' => 'publish',
		) );
		SUPER_Entry_DAL::create( array( 'form_id' => $other_form_id ) );

		$entries = SUPER_Entry_DAL::query( array( 'form_id' => self::$test_form_id ) );

		$this->assertCount( 2, $entries );
	}

	/**
	 * Test query with status filter
	 */
	public function test_query_by_status() {
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'wp_status' => 'publish' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'wp_status' => 'publish' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'wp_status' => 'draft' ) );

		$published = SUPER_Entry_DAL::query( array( 'wp_status' => 'publish' ) );
		$drafts = SUPER_Entry_DAL::query( array( 'wp_status' => 'draft' ) );

		$this->assertCount( 2, $published );
		$this->assertCount( 1, $drafts );
	}

	/**
	 * Test query with pagination
	 */
	public function test_query_with_pagination() {
		// Create 10 entries
		for ( $i = 0; $i < 10; $i++ ) {
			SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => "Entry $i" ) );
		}

		$page1 = SUPER_Entry_DAL::query( array( 'limit' => 5, 'offset' => 0 ) );
		$page2 = SUPER_Entry_DAL::query( array( 'limit' => 5, 'offset' => 5 ) );

		$this->assertCount( 5, $page1 );
		$this->assertCount( 5, $page2 );
	}

	/**
	 * Test query with sorting
	 */
	public function test_query_with_sorting() {
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'A Entry' ) );
		sleep( 1 ); // Ensure different timestamps
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'Z Entry' ) );

		$asc = SUPER_Entry_DAL::query( array( 'orderby' => 'title', 'order' => 'ASC' ) );
		$desc = SUPER_Entry_DAL::query( array( 'orderby' => 'title', 'order' => 'DESC' ) );

		$this->assertEquals( 'A Entry', $asc[0]->title );
		$this->assertEquals( 'Z Entry', $desc[0]->title );
	}

	/**
	 * Test count entries
	 */
	public function test_count_entries() {
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		$count = SUPER_Entry_DAL::count();

		$this->assertEquals( 3, $count );
	}

	/**
	 * Test count with filters
	 */
	public function test_count_with_filters() {
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'wp_status' => 'publish' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'wp_status' => 'publish' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'wp_status' => 'trash' ) );

		$publish_count = SUPER_Entry_DAL::count( array( 'wp_status' => 'publish' ) );
		$trash_count = SUPER_Entry_DAL::count( array( 'wp_status' => 'trash' ) );

		$this->assertEquals( 2, $publish_count );
		$this->assertEquals( 1, $trash_count );
	}

	// =========================================================================
	// UPDATE TESTS
	// =========================================================================

	/**
	 * Test update entry
	 */
	public function test_update_entry() {
		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id' => self::$test_form_id,
			'title'   => 'Original Title',
		) );

		$result = SUPER_Entry_DAL::update( $entry_id, array(
			'title'     => 'Updated Title',
			'wp_status' => 'super_read',
		) );

		$this->assertTrue( $result );

		$entry = SUPER_Entry_DAL::get( $entry_id );
		$this->assertEquals( 'Updated Title', $entry->title );
		$this->assertEquals( 'super_read', $entry->wp_status );
	}

	/**
	 * Test update updates timestamp
	 */
	public function test_update_updates_timestamp() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );
		$entry = SUPER_Entry_DAL::get( $entry_id );
		$original_updated = $entry->updated_at;

		sleep( 1 );

		SUPER_Entry_DAL::update( $entry_id, array( 'title' => 'Changed' ) );

		$updated = SUPER_Entry_DAL::get( $entry_id );
		$this->assertGreaterThan(
			strtotime( $original_updated ),
			strtotime( $updated->updated_at )
		);
	}

	/**
	 * Test update non-existent entry
	 */
	public function test_update_nonexistent_entry() {
		$result = SUPER_Entry_DAL::update( 99999, array( 'title' => 'Test' ) );

		// Returns WP_Error when entry not found
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	// =========================================================================
	// DELETE TESTS
	// =========================================================================

	/**
	 * Test soft delete (trash)
	 */
	public function test_soft_delete() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		$result = SUPER_Entry_DAL::delete( $entry_id, false );

		$this->assertTrue( $result );

		$entry = SUPER_Entry_DAL::get( $entry_id );
		$this->assertEquals( 'trash', $entry->wp_status );
	}

	/**
	 * Test hard delete (permanent)
	 */
	public function test_hard_delete() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		$result = SUPER_Entry_DAL::delete( $entry_id, true );

		$this->assertTrue( $result );

		$entry = SUPER_Entry_DAL::get( $entry_id );
		$this->assertInstanceOf( WP_Error::class, $entry );
	}

	/**
	 * Test restore from trash
	 */
	public function test_restore_entry() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		// Trash it
		SUPER_Entry_DAL::delete( $entry_id, false );
		$trashed = SUPER_Entry_DAL::get( $entry_id );
		$this->assertEquals( 'trash', $trashed->wp_status );

		// Restore it
		$result = SUPER_Entry_DAL::restore( $entry_id );

		$this->assertTrue( $result );

		$restored = SUPER_Entry_DAL::get( $entry_id );
		$this->assertEquals( 'publish', $restored->wp_status );
	}

	/**
	 * Test delete cleans up meta
	 */
	public function test_hard_delete_cleans_meta() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		// Add meta
		SUPER_Entry_DAL::add_meta( $entry_id, 'test_key', 'test_value' );

		// Verify meta exists
		$meta = SUPER_Entry_DAL::get_meta( $entry_id, 'test_key' );
		$this->assertEquals( 'test_value', $meta );

		// Hard delete
		SUPER_Entry_DAL::delete( $entry_id, true );

		// Verify meta is cleaned up
		global $wpdb;
		$meta_table = $wpdb->prefix . 'superforms_entry_meta';
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$meta_table} WHERE entry_id = %d",
			$entry_id
		) );
		$this->assertEquals( 0, $count );
	}

	// =========================================================================
	// META TESTS
	// =========================================================================

	/**
	 * Test add and get meta
	 */
	public function test_add_and_get_meta() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		$meta_id = SUPER_Entry_DAL::add_meta( $entry_id, 'test_key', 'test_value' );

		$this->assertIsInt( $meta_id );
		$this->assertGreaterThan( 0, $meta_id );

		$value = SUPER_Entry_DAL::get_meta( $entry_id, 'test_key' );
		$this->assertEquals( 'test_value', $value );
	}

	/**
	 * Test update meta
	 */
	public function test_update_meta() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		SUPER_Entry_DAL::add_meta( $entry_id, 'test_key', 'original' );
		SUPER_Entry_DAL::update_meta( $entry_id, 'test_key', 'updated' );

		$value = SUPER_Entry_DAL::get_meta( $entry_id, 'test_key' );
		$this->assertEquals( 'updated', $value );
	}

	/**
	 * Test delete meta
	 */
	public function test_delete_meta() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		SUPER_Entry_DAL::add_meta( $entry_id, 'test_key', 'test_value' );
		$result = SUPER_Entry_DAL::delete_meta( $entry_id, 'test_key' );

		$this->assertTrue( $result );

		$value = SUPER_Entry_DAL::get_meta( $entry_id, 'test_key' );
		// get_meta returns empty string when key doesn't exist
		$this->assertEmpty( $value );
	}

	/**
	 * Test get all meta
	 */
	public function test_get_all_meta() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		// Use underscore-prefixed keys (standard pattern for entry meta)
		SUPER_Entry_DAL::add_meta( $entry_id, '_key1', 'value1' );
		SUPER_Entry_DAL::add_meta( $entry_id, '_key2', 'value2' );
		SUPER_Entry_DAL::add_meta( $entry_id, '_key3', 'value3' );

		$all_meta = SUPER_Entry_DAL::get_all_meta( $entry_id );

		$this->assertCount( 3, $all_meta );
		$this->assertEquals( 'value1', $all_meta['_key1'] );
		$this->assertEquals( 'value2', $all_meta['_key2'] );
		$this->assertEquals( 'value3', $all_meta['_key3'] );
	}

	/**
	 * Test delete all meta
	 */
	public function test_delete_all_meta() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		SUPER_Entry_DAL::add_meta( $entry_id, 'key1', 'value1' );
		SUPER_Entry_DAL::add_meta( $entry_id, 'key2', 'value2' );

		$result = SUPER_Entry_DAL::delete_all_meta( $entry_id );

		$this->assertTrue( $result );

		$all_meta = SUPER_Entry_DAL::get_all_meta( $entry_id );
		$this->assertEmpty( $all_meta );
	}

	/**
	 * Test meta with array value (serialization)
	 */
	public function test_meta_array_value() {
		$entry_id = SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		$array_value = array( 'key' => 'value', 'nested' => array( 'a', 'b', 'c' ) );
		SUPER_Entry_DAL::add_meta( $entry_id, 'array_key', $array_value );

		$retrieved = SUPER_Entry_DAL::get_meta( $entry_id, 'array_key' );

		$this->assertIsArray( $retrieved );
		$this->assertEquals( $array_value, $retrieved );
	}

	/**
	 * Test meta key mapping (old postmeta keys)
	 */
	public function test_meta_key_mapping() {
		// Test that old postmeta key names get mapped to new shorter keys
		$old_key = '_super_contact_entry_wc_order_id';
		$expected_new_key = '_wc_order_id';

		$new_key = SUPER_Entry_DAL::get_new_meta_key( $old_key );

		$this->assertEquals( $expected_new_key, $new_key );
	}

	// =========================================================================
	// STORAGE MODE TESTS
	// =========================================================================

	/**
	 * Test get storage mode
	 */
	public function test_get_storage_mode() {
		$mode = SUPER_Entry_DAL::get_storage_mode();

		// Should be one of the valid modes
		$this->assertContains( $mode, array( 'post_type', 'custom_table', 'both' ) );
	}

	// =========================================================================
	// QUERY HELPER TESTS
	// =========================================================================

	/**
	 * Test get entries by form
	 */
	public function test_get_by_form() {
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id ) );

		$entries = SUPER_Entry_DAL::get_by_form( self::$test_form_id );

		$this->assertCount( 2, $entries );
	}

	/**
	 * Test get entries by user
	 */
	public function test_get_by_user() {
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'user_id' => 1 ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'user_id' => 1 ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'user_id' => 2 ) );

		$user1_entries = SUPER_Entry_DAL::get_by_user( 1 );
		$user2_entries = SUPER_Entry_DAL::get_by_user( 2 );

		$this->assertCount( 2, $user1_entries );
		$this->assertCount( 1, $user2_entries );
	}

	// =========================================================================
	// EDGE CASE TESTS
	// =========================================================================

	/**
	 * Test entry with very long title
	 */
	public function test_long_title_truncation() {
		$long_title = str_repeat( 'A', 500 ); // Exceeds VARCHAR(255)

		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id' => self::$test_form_id,
			'title'   => $long_title,
		) );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		// Custom table VARCHAR(255) - title is truncated by sanitize_text_field in create()
		// Note: sanitize_text_field doesn't truncate, but DB column does
		$this->assertNotEmpty( $entry->title );
	}

	/**
	 * Test entry with special characters in title
	 */
	public function test_special_characters_in_title() {
		$special_title = "Test <script>alert('xss')</script> & 'quotes' \"double\"";

		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id' => self::$test_form_id,
			'title'   => $special_title,
		) );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		// Title should be stored (sanitization happens elsewhere)
		$this->assertNotEmpty( $entry->title );
	}

	/**
	 * Test query with search term
	 */
	public function test_query_with_search() {
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'Apple Pie Recipe' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'Banana Bread Recipe' ) );
		SUPER_Entry_DAL::create( array( 'form_id' => self::$test_form_id, 'title' => 'Cherry Cake' ) );

		$results = SUPER_Entry_DAL::query( array( 'search' => 'Recipe' ) );

		$this->assertCount( 2, $results );
	}

	/**
	 * Test IPv6 address storage
	 */
	public function test_ipv6_address() {
		$ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

		$entry_id = SUPER_Entry_DAL::create( array(
			'form_id'    => self::$test_form_id,
			'ip_address' => $ipv6,
		) );

		$entry = SUPER_Entry_DAL::get( $entry_id );

		$this->assertEquals( $ipv6, $entry->ip_address );
	}
}
