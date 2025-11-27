<?php
/**
 * Listings Query Tests
 *
 * Tests for listings extension query components with Entry DAL integration.
 * Verifies sorting, filtering, and storage mode handling for custom table storage.
 *
 * Architecture:
 * - Entry records: SUPER_Entry_DAL::create() → wp_superforms_entries (fixed schema)
 * - Entry data: SUPER_Data_Access::save_entry_data() → wp_superforms_entry_data (EAV schema)
 *
 * @package Super_Forms
 * @since 6.5.0
 */

class Test_Listings_Queries extends WP_UnitTestCase {

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

		global $wpdb;

		// Ensure the entries tables exist
		$entries_table = $wpdb->prefix . 'superforms_entries';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $entries_table ) );
		if ( $table_exists !== $entries_table ) {
			SUPER_Install::install();
		}

		// Clean up all test data before each test
		$meta_table = $wpdb->prefix . 'superforms_entry_meta';
		$data_table = $wpdb->prefix . 'superforms_entry_data';

		$wpdb->query( "DELETE FROM {$data_table}" );
		$wpdb->query( "DELETE FROM {$meta_table}" );
		$wpdb->query( "DELETE FROM {$entries_table}" );

		// Also clean up any post type entries
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'super_contact_entry'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_super_contact_entry_%'" );

		// Reset migration state cache
		if ( method_exists( 'SUPER_Entry_DAL', 'clear_cache' ) ) {
			SUPER_Entry_DAL::clear_cache();
		}

		// Create a test form
		if ( ! self::$test_form_id ) {
			self::$test_form_id = wp_insert_post( array(
				'post_type'   => 'super_form',
				'post_title'  => 'Test Form for Listings',
				'post_status' => 'publish',
			) );
		}
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'superforms_entries';
		$meta_table = $wpdb->prefix . 'superforms_entry_meta';
		$data_table = $wpdb->prefix . 'superforms_entry_data';

		$wpdb->suppress_errors( true );
		$wpdb->query( "DELETE FROM {$data_table}" );
		$wpdb->query( "DELETE FROM {$meta_table}" );
		$wpdb->query( "DELETE FROM {$entries_table}" );
		$wpdb->suppress_errors( false );

		parent::tearDown();
	}

	/**
	 * Helper: Create entry with field data
	 *
	 * Creates both the entry record and its field data in EAV format.
	 *
	 * @param array $entry_args  Entry record arguments for SUPER_Entry_DAL::create()
	 * @param array $field_data  Field data array for SUPER_Data_Access::save_entry_data()
	 * @return int Entry ID
	 */
	private function create_entry_with_data( array $entry_args, array $field_data = array() ) {
		// Ensure form_id is set
		if ( empty( $entry_args['form_id'] ) ) {
			$entry_args['form_id'] = self::$test_form_id;
		}

		// Create entry record
		$entry_id = SUPER_Entry_DAL::create( $entry_args );
		$this->assertIsInt( $entry_id, 'Entry creation failed' );

		// Store field data in EAV format
		if ( ! empty( $field_data ) ) {
			$result = SUPER_Data_Access::save_entry_data( $entry_id, $field_data, 'eav' );
			$this->assertTrue( $result === true || ! is_wp_error( $result ), 'Field data storage failed' );
		}

		return $entry_id;
	}

	// =========================================================================
	// QUERY COMPONENTS TESTS
	// =========================================================================

	/**
	 * Test that get_query_components returns correct structure
	 */
	public function test_query_components_structure() {
		$components = SUPER_Entry_DAL::get_query_components();

		$this->assertIsArray( $components );
		$this->assertArrayHasKey( 'storage_mode', $components );
		$this->assertArrayHasKey( 'entries_table', $components );
		$this->assertArrayHasKey( 'entry_meta_table', $components );
	}

	/**
	 * Test query components for custom_table storage mode
	 */
	public function test_query_components_custom_table_mode() {
		// Create an entry to trigger custom_table mode detection
		$entry_id = $this->create_entry_with_data(
			array( 'title' => 'Test Entry', 'entry_status' => 'pending' ),
			array( 'email' => array( 'name' => 'email', 'value' => 'test@example.com' ) )
		);

		// Clear cache to force fresh detection
		SUPER_Entry_DAL::clear_cache();

		$components = SUPER_Entry_DAL::get_query_components();

		// When entries exist in custom table, should use custom_table or both mode
		$this->assertContains( $components['storage_mode'], array( 'custom_table', 'both' ) );

		// Table names should be prefixed
		global $wpdb;
		$this->assertEquals( $wpdb->prefix . 'superforms_entries', $components['entries_table'] );
		$this->assertEquals( $wpdb->prefix . 'superforms_entry_meta', $components['entry_meta_table'] );
	}

	// =========================================================================
	// SORTING TESTS
	// =========================================================================

	/**
	 * Test that entries can be sorted by date using correct column alias
	 */
	public function test_entry_sorting_by_date() {
		global $wpdb;

		// Create entries with different timestamps
		$entry1_id = $this->create_entry_with_data(
			array( 'title' => 'Entry 1', 'entry_status' => 'pending' ),
			array( 'name' => array( 'name' => 'name', 'value' => 'Alice' ) )
		);

		sleep( 1 ); // Ensure different timestamps

		$entry2_id = $this->create_entry_with_data(
			array( 'title' => 'Entry 2', 'entry_status' => 'pending' ),
			array( 'name' => array( 'name' => 'name', 'value' => 'Bob' ) )
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];

			// Test sorting by created_at DESC (most recent first)
			$results = $wpdb->get_results( "
				SELECT entry.id, entry.title
				FROM {$entries_table} AS entry
				ORDER BY entry.created_at DESC
			" );

			$this->assertEquals( 2, count( $results ) );
			$this->assertEquals( $entry2_id, $results[0]->id, 'Most recent entry should be first' );
			$this->assertEquals( $entry1_id, $results[1]->id );

			// Test sorting by created_at ASC (oldest first)
			$results_asc = $wpdb->get_results( "
				SELECT entry.id, entry.title
				FROM {$entries_table} AS entry
				ORDER BY entry.created_at ASC
			" );

			$this->assertEquals( $entry1_id, $results_asc[0]->id, 'Oldest entry should be first' );
		}
	}

	/**
	 * Test sorting by custom field using EAV join (core listings pattern)
	 */
	public function test_custom_field_sorting_with_eav_join() {
		global $wpdb;

		// Create entries with different custom field values
		$this->create_entry_with_data(
			array( 'title' => 'Entry Z', 'entry_status' => 'pending' ),
			array( 'company' => array( 'name' => 'company', 'value' => 'Zebra Corp' ) )
		);

		$this->create_entry_with_data(
			array( 'title' => 'Entry A', 'entry_status' => 'pending' ),
			array( 'company' => array( 'name' => 'company', 'value' => 'Alpha Inc' ) )
		);

		$this->create_entry_with_data(
			array( 'title' => 'Entry M', 'entry_status' => 'pending' ),
			array( 'company' => array( 'name' => 'company', 'value' => 'Mango LLC' ) )
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];
			$data_table = $wpdb->prefix . 'superforms_entry_data';

			// This is the exact pattern used by listings.php for custom column sorting
			$results = $wpdb->get_results( "
				SELECT entry.id, entry.title, eav_sort.field_value AS company
				FROM {$entries_table} AS entry
				LEFT JOIN {$data_table} AS eav_sort
					ON eav_sort.entry_id = entry.id
					AND eav_sort.field_name = 'company'
				ORDER BY eav_sort.field_value ASC
			" );

			$this->assertEquals( 3, count( $results ) );
			$this->assertEquals( 'Alpha Inc', $results[0]->company, 'Alpha Inc should be first (ASC)' );
			$this->assertEquals( 'Mango LLC', $results[1]->company );
			$this->assertEquals( 'Zebra Corp', $results[2]->company, 'Zebra Corp should be last (ASC)' );

			// Test DESC order
			$results_desc = $wpdb->get_results( "
				SELECT entry.id, entry.title, eav_sort.field_value AS company
				FROM {$entries_table} AS entry
				LEFT JOIN {$data_table} AS eav_sort
					ON eav_sort.entry_id = entry.id
					AND eav_sort.field_name = 'company'
				ORDER BY eav_sort.field_value DESC
			" );

			$this->assertEquals( 'Zebra Corp', $results_desc[0]->company, 'Zebra Corp should be first (DESC)' );
		}
	}

	// =========================================================================
	// FILTERING TESTS
	// =========================================================================

	/**
	 * Test filtering entries by form_id using correct column
	 */
	public function test_filter_by_form_id() {
		global $wpdb;

		// Create another form
		$other_form_id = wp_insert_post( array(
			'post_type'   => 'super_form',
			'post_title'  => 'Other Test Form',
			'post_status' => 'publish',
		) );

		// Create entries for different forms
		$this->create_entry_with_data(
			array( 'form_id' => self::$test_form_id, 'title' => 'Entry Form 1' ),
			array()
		);

		$this->create_entry_with_data(
			array( 'form_id' => self::$test_form_id, 'title' => 'Entry Form 1 - 2' ),
			array()
		);

		$this->create_entry_with_data(
			array( 'form_id' => $other_form_id, 'title' => 'Entry Form 2' ),
			array()
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];

			// Filter by form_id (this is what listings.php does)
			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT entry.id, entry.title
				FROM {$entries_table} AS entry
				WHERE entry.form_id = %d
			", self::$test_form_id ) );

			$this->assertEquals( 2, count( $results ), 'Should find 2 entries for test form' );
		}

		wp_delete_post( $other_form_id, true );
	}

	/**
	 * Test filtering entries by custom field value using EAV join (LIKE pattern)
	 */
	public function test_filter_by_custom_field_eav_like() {
		global $wpdb;

		// Create entries with different email domains
		$this->create_entry_with_data(
			array( 'title' => 'Gmail User 1', 'entry_status' => 'pending' ),
			array( 'email' => array( 'name' => 'email', 'value' => 'user@gmail.com' ) )
		);

		$this->create_entry_with_data(
			array( 'title' => 'Yahoo User', 'entry_status' => 'pending' ),
			array( 'email' => array( 'name' => 'email', 'value' => 'user@yahoo.com' ) )
		);

		$this->create_entry_with_data(
			array( 'title' => 'Gmail User 2', 'entry_status' => 'pending' ),
			array( 'email' => array( 'name' => 'email', 'value' => 'other@gmail.com' ) )
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];
			$data_table = $wpdb->prefix . 'superforms_entry_data';

			// This is the exact pattern used by listings.php for custom column filtering
			$results = $wpdb->get_results( "
				SELECT entry.id, entry.title, eav_filter.field_value AS email
				FROM {$entries_table} AS entry
				LEFT JOIN {$data_table} AS eav_filter
					ON eav_filter.entry_id = entry.id
					AND eav_filter.field_name = 'email'
				WHERE eav_filter.field_value LIKE '%gmail.com%'
			" );

			$this->assertEquals( 2, count( $results ), 'Should find 2 Gmail users' );
		}
	}

	/**
	 * Test filtering entries by entry_status column (custom table mode)
	 */
	public function test_filter_by_entry_status_column() {
		global $wpdb;

		// Create entries with different statuses
		$this->create_entry_with_data(
			array( 'title' => 'Pending Entry 1', 'entry_status' => 'pending' ),
			array()
		);

		$this->create_entry_with_data(
			array( 'title' => 'Approved Entry', 'entry_status' => 'approved' ),
			array()
		);

		$this->create_entry_with_data(
			array( 'title' => 'Pending Entry 2', 'entry_status' => 'pending' ),
			array()
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];

			// In custom table mode, entry_status is a direct column (not meta)
			// This tests the fix at line 2581-2585 in listings.php
			$results = $wpdb->get_results( "
				SELECT entry.id, entry.title, entry.entry_status
				FROM {$entries_table} AS entry
				WHERE entry.entry_status = 'pending'
			" );

			$this->assertEquals( 2, count( $results ), 'Should find 2 pending entries' );
		}
	}

	/**
	 * Test filtering entries by date range
	 */
	public function test_filter_by_date_range() {
		global $wpdb;

		// Create an older entry
		$entry1_id = $this->create_entry_with_data(
			array( 'title' => 'Old Entry', 'entry_status' => 'pending' ),
			array()
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];

			// Update entry date to 30 days ago
			$old_date = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
			$wpdb->update( $entries_table, array( 'created_at' => $old_date ), array( 'id' => $entry1_id ) );

			// Create a recent entry
			$this->create_entry_with_data(
				array( 'title' => 'Recent Entry', 'entry_status' => 'pending' ),
				array()
			);

			// Query entries from last 7 days (listings date range pattern)
			$week_ago = gmdate( 'Y-m-d', strtotime( '-7 days' ) );

			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT entry.id, entry.title
				FROM {$entries_table} AS entry
				WHERE DATE(entry.created_at) >= CAST(%s AS DATE)
			", $week_ago ) );

			$this->assertEquals( 1, count( $results ), 'Should find only recent entry' );
			$this->assertEquals( 'Recent Entry', $results[0]->title );
		}
	}

	/**
	 * Test filtering entries by user_id column
	 */
	public function test_filter_by_user_id_column() {
		global $wpdb;

		// Create a test user
		$user_id = wp_create_user( 'testlistingsuser', 'password', 'testlistings@example.com' );

		// Create entries with different user IDs
		$this->create_entry_with_data(
			array( 'title' => 'User Entry', 'entry_status' => 'pending', 'user_id' => $user_id ),
			array()
		);

		$this->create_entry_with_data(
			array( 'title' => 'Guest Entry', 'entry_status' => 'pending', 'user_id' => 0 ),
			array()
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];

			// This tests the user_id filter (was post_author, now uses $col_user_id)
			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT entry.id, entry.title
				FROM {$entries_table} AS entry
				WHERE entry.user_id = %d
			", $user_id ) );

			$this->assertEquals( 1, count( $results ), 'Should find 1 user entry' );
			$this->assertEquals( 'User Entry', $results[0]->title );
		}

		wp_delete_user( $user_id );
	}

	// =========================================================================
	// COMBINED FILTER AND SORT TESTS (Real Listings Patterns)
	// =========================================================================

	/**
	 * Test combined filtering and sorting by custom fields
	 * This replicates the exact query pattern from listings.php
	 */
	public function test_combined_filter_and_sort_custom_fields() {
		global $wpdb;

		// Create entries with various attributes
		$this->create_entry_with_data(
			array( 'title' => 'Entry A Pending', 'entry_status' => 'pending' ),
			array( 'priority' => array( 'name' => 'priority', 'value' => '3' ) )
		);

		$this->create_entry_with_data(
			array( 'title' => 'Entry B Approved', 'entry_status' => 'approved' ),
			array( 'priority' => array( 'name' => 'priority', 'value' => '1' ) )
		);

		$this->create_entry_with_data(
			array( 'title' => 'Entry C Pending', 'entry_status' => 'pending' ),
			array( 'priority' => array( 'name' => 'priority', 'value' => '2' ) )
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];
			$data_table = $wpdb->prefix . 'superforms_entry_data';

			// Query pending entries sorted by priority (exact listings.php pattern)
			$results = $wpdb->get_results( "
				SELECT entry.id, entry.title, eav_sort.field_value AS priority
				FROM {$entries_table} AS entry
				LEFT JOIN {$data_table} AS eav_sort
					ON eav_sort.entry_id = entry.id
					AND eav_sort.field_name = 'priority'
				WHERE entry.entry_status = 'pending'
				ORDER BY eav_sort.field_value ASC
			" );

			$this->assertEquals( 2, count( $results ), 'Should find 2 pending entries' );
			$this->assertEquals( '2', $results[0]->priority, 'Priority 2 should be first' );
			$this->assertEquals( '3', $results[1]->priority, 'Priority 3 should be second' );
		}
	}

	/**
	 * Test filtering by one custom field while sorting by another
	 */
	public function test_filter_one_field_sort_another() {
		global $wpdb;

		// Create entries with department and hire_date
		$this->create_entry_with_data(
			array( 'title' => 'Sales Bob', 'entry_status' => 'pending' ),
			array(
				'department' => array( 'name' => 'department', 'value' => 'Sales' ),
				'hire_date'  => array( 'name' => 'hire_date', 'value' => '2023-06-15' ),
			)
		);

		$this->create_entry_with_data(
			array( 'title' => 'Sales Alice', 'entry_status' => 'pending' ),
			array(
				'department' => array( 'name' => 'department', 'value' => 'Sales' ),
				'hire_date'  => array( 'name' => 'hire_date', 'value' => '2023-01-10' ),
			)
		);

		$this->create_entry_with_data(
			array( 'title' => 'Engineering Charlie', 'entry_status' => 'pending' ),
			array(
				'department' => array( 'name' => 'department', 'value' => 'Engineering' ),
				'hire_date'  => array( 'name' => 'hire_date', 'value' => '2023-03-20' ),
			)
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];
			$data_table = $wpdb->prefix . 'superforms_entry_data';

			// Filter by department=Sales, sort by hire_date
			// This requires TWO separate EAV joins (filter + sort)
			$results = $wpdb->get_results( "
				SELECT entry.id, entry.title,
					eav_filter.field_value AS department,
					eav_sort.field_value AS hire_date
				FROM {$entries_table} AS entry
				LEFT JOIN {$data_table} AS eav_filter
					ON eav_filter.entry_id = entry.id
					AND eav_filter.field_name = 'department'
				LEFT JOIN {$data_table} AS eav_sort
					ON eav_sort.entry_id = entry.id
					AND eav_sort.field_name = 'hire_date'
				WHERE eav_filter.field_value = 'Sales'
				ORDER BY eav_sort.field_value ASC
			" );

			$this->assertEquals( 2, count( $results ), 'Should find 2 Sales entries' );
			$this->assertEquals( '2023-01-10', $results[0]->hire_date, 'Alice (Jan) should be first' );
			$this->assertEquals( '2023-06-15', $results[1]->hire_date, 'Bob (Jun) should be second' );
		}
	}

	// =========================================================================
	// COLUMN ALIAS VERIFICATION TESTS
	// =========================================================================

	/**
	 * Test that the 'entry' table alias works correctly in all query parts
	 */
	public function test_entry_alias_in_full_query() {
		global $wpdb;

		$user_id = wp_create_user( 'aliasuser', 'password', 'alias@example.com' );

		$entry_id = $this->create_entry_with_data(
			array(
				'title'        => 'Alias Test Entry',
				'entry_status' => 'pending',
				'user_id'      => $user_id,
			),
			array(
				'field1' => array( 'name' => 'field1', 'value' => 'value1' ),
				'field2' => array( 'name' => 'field2', 'value' => 'value2' ),
			)
		);

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];
			$data_table = $wpdb->prefix . 'superforms_entry_data';

			// Full query with entry alias (matches listings.php after refactor)
			$result = $wpdb->get_row( $wpdb->prepare( "
				SELECT
					entry.id AS entry_id,
					entry.form_id AS form_id,
					entry.user_id AS author_id,
					entry.created_at AS date,
					entry.title AS title,
					entry.entry_status AS status,
					entry.wp_status,
					eav1.field_value AS field1_value,
					eav2.field_value AS field2_value
				FROM {$entries_table} AS entry
				LEFT JOIN {$data_table} AS eav1
					ON eav1.entry_id = entry.id
					AND eav1.field_name = 'field1'
				LEFT JOIN {$data_table} AS eav2
					ON eav2.entry_id = entry.id
					AND eav2.field_name = 'field2'
				WHERE entry.id = %d
					AND entry.wp_status != 'trash'
					AND entry.form_id = %d
					AND entry.user_id = %d
			", $entry_id, self::$test_form_id, $user_id ) );

			$this->assertNotNull( $result, 'Query with entry alias should return result' );
			$this->assertEquals( $entry_id, $result->entry_id );
			$this->assertEquals( self::$test_form_id, $result->form_id );
			$this->assertEquals( $user_id, $result->author_id );
			$this->assertEquals( 'Alias Test Entry', $result->title );
			$this->assertEquals( 'pending', $result->status );
			$this->assertEquals( 'publish', $result->wp_status );
			$this->assertEquals( 'value1', $result->field1_value );
			$this->assertEquals( 'value2', $result->field2_value );
		}

		wp_delete_user( $user_id );
	}

	/**
	 * Test COUNT query pattern (used for pagination)
	 */
	public function test_count_query_pattern() {
		global $wpdb;

		// Create multiple entries
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->create_entry_with_data(
				array( 'title' => "Entry {$i}", 'entry_status' => 'pending' ),
				array( 'name' => array( 'name' => 'name', 'value' => "Person {$i}" ) )
			);
		}

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];

			// COUNT query pattern from listings.php
			$count = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(entry.id)
				FROM {$entries_table} AS entry
				WHERE entry.wp_status != 'trash'
					AND entry.form_id = %d
			", self::$test_form_id ) );

			$this->assertEquals( 5, intval( $count ), 'Should count 5 entries' );
		}
	}

	/**
	 * Test pagination with LIMIT/OFFSET
	 */
	public function test_pagination_limit_offset() {
		global $wpdb;

		// Create 10 entries
		for ( $i = 1; $i <= 10; $i++ ) {
			$this->create_entry_with_data(
				array( 'title' => sprintf( 'Entry %02d', $i ), 'entry_status' => 'pending' ),
				array()
			);
		}

		$components = SUPER_Entry_DAL::get_query_components();

		if ( $components['storage_mode'] !== 'post_type' ) {
			$entries_table = $components['entries_table'];

			// Page 1: entries 1-5
			$page1 = $wpdb->get_results( "
				SELECT entry.id, entry.title
				FROM {$entries_table} AS entry
				WHERE entry.wp_status != 'trash'
				ORDER BY entry.created_at ASC
				LIMIT 5 OFFSET 0
			" );

			$this->assertEquals( 5, count( $page1 ) );
			$this->assertEquals( 'Entry 01', $page1[0]->title );

			// Page 2: entries 6-10
			$page2 = $wpdb->get_results( "
				SELECT entry.id, entry.title
				FROM {$entries_table} AS entry
				WHERE entry.wp_status != 'trash'
				ORDER BY entry.created_at ASC
				LIMIT 5 OFFSET 5
			" );

			$this->assertEquals( 5, count( $page2 ) );
			$this->assertEquals( 'Entry 06', $page2[0]->title );
		}
	}
}
