<?php
/**
 * Backwards Compatibility Tests for EAV Migration
 *
 * Tests that old serialized data remains accessible throughout
 * and after the migration process.
 *
 * @package Super Forms
 * @subpackage Tests
 */

class Test_Backwards_Compatibility extends SUPER_Test_Helpers {

	/**
	 * Test reading old serialized entry data before migration
	 *
	 * Ensures that entries created before migration can still be read
	 */
	public function test_read_old_serialized_data() {
		// Create entry with old serialized format
		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry( $test_data );

		// Ensure no migration state exists
		delete_option( 'superforms_eav_migration' );

		// Read data using Data Access Layer
		$retrieved = SUPER_Data_Access::get_entry_data( $entry_id );

		// Verify data matches
		$this->assertIsArray( $retrieved, 'Retrieved data should be an array' );
		$this->assertEquals( $test_data['email']['value'], $retrieved['email']['value'], 'Email should match' );
		$this->assertEquals( $test_data['name']['value'], $retrieved['name']['value'], 'Name should match' );

		// Cleanup
		$this->cleanup_test_entries();
	}

	/**
	 * Test that serialized data is preserved during migration
	 *
	 * Old serialized data should never be deleted, enabling rollback
	 */
	public function test_serialized_data_preserved_during_migration() {
		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry( $test_data );

		// Simulate migration in progress
		update_option(
			'superforms_eav_migration',
			array(
				'status'          => 'in_progress',
				'using_storage'   => 'serialized',
				'total_entries'   => 1,
				'migrated_entries' => 0,
			)
		);

		// Migrate the entry
		SUPER_Migration_Manager::process_batch();

		// Check serialized data still exists
		$serialized = get_post_meta( $entry_id, '_super_contact_entry_data', true );
		$this->assertNotEmpty( $serialized, 'Serialized data should still exist after migration' );
		$this->assertIsString( $serialized, 'Serialized data should be a string' );

		// Verify it can be unserialized
		$unserialized = maybe_unserialize( $serialized );
		$this->assertIsArray( $unserialized, 'Serialized data should unserialize to array' );
		$this->assertEquals( $test_data['email']['value'], $unserialized['email']['value'], 'Serialized email should match' );

		// Cleanup
		$this->cleanup_test_entries();
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test reading data after migration completes
	 *
	 * Data should still be accessible via Data Access Layer
	 */
	public function test_read_data_after_migration() {
		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry( $test_data );

		// Simulate completed migration using EAV
		update_option(
			'superforms_eav_migration',
			array(
				'status'           => 'completed',
				'using_storage'    => 'eav',
				'total_entries'    => 1,
				'migrated_entries' => 1,
			)
		);

		// Manually insert data into EAV tables (simulating migration)
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';

		foreach ( $test_data as $field_name => $field_data ) {
			$field_value = isset( $field_data['value'] ) ? $field_data['value'] : '';
			if ( is_array( $field_value ) ) {
				$field_value = wp_json_encode( $field_value );
			}

			$wpdb->insert(
				$table,
				array(
					'entry_id'    => $entry_id,
					'field_name'  => $field_name,
					'field_value' => $field_value,
					'field_type'  => isset( $field_data['type'] ) ? $field_data['type'] : '',
					'field_label' => isset( $field_data['label'] ) ? $field_data['label'] : '',
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		// Read data using Data Access Layer
		$retrieved = SUPER_Data_Access::get_entry_data( $entry_id );

		// Verify data matches
		$this->assertIsArray( $retrieved, 'Retrieved data should be an array' );
		$this->assertEquals( $test_data['email']['value'], $retrieved['email']['value'], 'Email should match after migration' );

		// Cleanup
		$this->cleanup_test_entries();
		$wpdb->delete( $table, array( 'entry_id' => $entry_id ), array( '%d' ) );
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test EAV fallback to serialized when EAV data is empty
	 *
	 * If EAV table is empty, should fallback to reading serialized
	 */
	public function test_eav_fallback_to_serialized() {
		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry( $test_data );

		// Simulate completed migration using EAV
		update_option(
			'superforms_eav_migration',
			array(
				'status'           => 'completed',
				'using_storage'    => 'eav',
				'total_entries'    => 1,
				'migrated_entries' => 1,
			)
		);

		// Intentionally leave EAV table empty to trigger fallback
		// Read data - should fallback to serialized
		$retrieved = SUPER_Data_Access::get_entry_data( $entry_id );

		// Verify data was retrieved from serialized fallback
		$this->assertIsArray( $retrieved, 'Retrieved data should be an array via fallback' );
		$this->assertEquals( $test_data['email']['value'], $retrieved['email']['value'], 'Email should match via fallback' );

		// Cleanup
		$this->cleanup_test_entries();
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test data integrity after migration remains consistent
	 */
	public function test_data_integrity_after_migration() {
		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry( $test_data );

		// Start migration
		SUPER_Migration_Manager::start_migration();

		// Process migration
		SUPER_Migration_Manager::process_batch();

		// Complete migration
		SUPER_Migration_Manager::complete_migration();

		// Read data
		$retrieved = SUPER_Data_Access::get_entry_data( $entry_id );

		// Verify all fields match
		$this->assertIsArray( $retrieved, 'Retrieved data should be an array' );
		$this->assertEquals( $test_data['email']['value'], $retrieved['email']['value'], 'Email should match' );
		$this->assertEquals( $test_data['name']['value'], $retrieved['name']['value'], 'Name should match' );
		$this->assertEquals( $test_data['phone']['value'], $retrieved['phone']['value'], 'Phone should match' );

		// Cleanup
		$this->cleanup_test_entries();
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';
		$wpdb->delete( $table, array( 'entry_id' => $entry_id ), array( '%d' ) );
		delete_option( 'superforms_eav_migration' );
	}
}
