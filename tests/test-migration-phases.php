<?php
/**
 * Migration Phase Tests
 *
 * Tests the 4 phases of migration storage strategy:
 * 1. Before migration (not_started) - Serialized only
 * 2. During migration (in_progress) - Dual-write to both
 * 3. After migration (completed, eav) - EAV only
 * 4. After rollback (completed, serialized) - Serialized only
 *
 * @package Super Forms
 * @subpackage Tests
 */

class Test_Migration_Phases extends SUPER_Test_Helpers {

	/**
	 * Test Phase 1: Before migration starts
	 *
	 * Should only write to serialized storage
	 */
	public function test_phase1_before_migration() {
		// No migration state
		delete_option( 'superforms_eav_migration' );

		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry();

		// Save data
		$result = SUPER_Data_Access::save_entry_data( $entry_id, $test_data );
		$this->assertTrue( $result, 'Save should succeed in phase 1' );

		// Verify only serialized exists
		$serialized = get_post_meta( $entry_id, '_super_contact_entry_data', true );
		$this->assertNotEmpty( $serialized, 'Serialized data should exist' );

		// Verify EAV table is empty
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE entry_id = %d", $entry_id ) );
		$this->assertEquals( 0, $count, 'EAV table should be empty in phase 1' );

		// Cleanup
		$this->cleanup_test_entries();
	}

	/**
	 * Test Phase 2: During migration
	 *
	 * Should write to BOTH serialized and EAV (dual-write)
	 */
	public function test_phase2_during_migration() {
		// Set migration in progress
		update_option(
			'superforms_eav_migration',
			array(
				'status'           => 'in_progress',
				'using_storage'    => 'serialized',
				'total_entries'    => 10,
				'migrated_entries' => 5,
			)
		);

		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry();

		// Save data
		$result = SUPER_Data_Access::save_entry_data( $entry_id, $test_data );
		$this->assertTrue( $result, 'Save should succeed in phase 2' );

		// Verify serialized exists
		$serialized = get_post_meta( $entry_id, '_super_contact_entry_data', true );
		$this->assertNotEmpty( $serialized, 'Serialized data should exist in phase 2' );

		// Verify EAV table has data
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE entry_id = %d", $entry_id ) );
		$this->assertGreaterThan( 0, $count, 'EAV table should have data in phase 2 (dual-write)' );

		// Cleanup
		$this->cleanup_test_entries();
		$wpdb->delete( $table, array( 'entry_id' => $entry_id ), array( '%d' ) );
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test Phase 3: After migration complete (using EAV)
	 *
	 * Should only write to EAV storage
	 */
	public function test_phase3_after_migration_eav() {
		// Set migration completed with EAV
		update_option(
			'superforms_eav_migration',
			array(
				'status'           => 'completed',
				'using_storage'    => 'eav',
				'total_entries'    => 10,
				'migrated_entries' => 10,
			)
		);

		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry();

		// Clear any existing serialized data
		delete_post_meta( $entry_id, '_super_contact_entry_data' );

		// Save data
		$result = SUPER_Data_Access::save_entry_data( $entry_id, $test_data );
		$this->assertTrue( $result, 'Save should succeed in phase 3' );

		// Verify serialized does NOT exist (optimization - single write)
		$serialized = get_post_meta( $entry_id, '_super_contact_entry_data', true );
		$this->assertEmpty( $serialized, 'Serialized data should NOT exist in phase 3 (EAV only)' );

		// Verify EAV table has data
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE entry_id = %d", $entry_id ) );
		$this->assertGreaterThan( 0, $count, 'EAV table should have data in phase 3' );

		// Cleanup
		$this->cleanup_test_entries();
		$wpdb->delete( $table, array( 'entry_id' => $entry_id ), array( '%d' ) );
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test Phase 4: After rollback (back to serialized)
	 *
	 * Should only write to serialized storage again
	 */
	public function test_phase4_after_rollback() {
		// Set migration completed but rolled back
		update_option(
			'superforms_eav_migration',
			array(
				'status'           => 'completed',
				'using_storage'    => 'serialized',
				'total_entries'    => 10,
				'migrated_entries' => 10,
				'rollback_count'   => 1,
			)
		);

		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry();

		// Save data
		$result = SUPER_Data_Access::save_entry_data( $entry_id, $test_data );
		$this->assertTrue( $result, 'Save should succeed in phase 4' );

		// Verify serialized exists
		$serialized = get_post_meta( $entry_id, '_super_contact_entry_data', true );
		$this->assertNotEmpty( $serialized, 'Serialized data should exist in phase 4' );

		// Verify EAV table is empty (back to serialized only)
		global $wpdb;
		$table = $wpdb->prefix . 'superforms_entry_data';
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE entry_id = %d", $entry_id ) );
		$this->assertEquals( 0, $count, 'EAV table should be empty in phase 4 (rolled back)' );

		// Cleanup
		$this->cleanup_test_entries();
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test rollback functionality
	 *
	 * Should switch storage back to serialized
	 */
	public function test_rollback_switches_storage() {
		// Set migration completed with EAV
		update_option(
			'superforms_eav_migration',
			array(
				'status'           => 'completed',
				'using_storage'    => 'eav',
				'total_entries'    => 10,
				'migrated_entries' => 10,
			)
		);

		// Perform rollback
		$result = SUPER_Migration_Manager::rollback_migration();

		// Verify rollback succeeded
		$this->assertIsArray( $result, 'Rollback should return array' );
		$this->assertTrue( $result['success'], 'Rollback should succeed' );

		// Verify storage switched
		$migration = get_option( 'superforms_eav_migration' );
		$this->assertEquals( 'serialized', $migration['using_storage'], 'Storage should be serialized after rollback' );
		$this->assertEquals( 1, $migration['rollback_count'], 'Rollback count should be 1' );
		$this->assertNotEmpty( $migration['last_rollback_at'], 'Last rollback timestamp should be set' );

		// Cleanup
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test rollback can only happen after migration completes
	 */
	public function test_rollback_requires_completed_migration() {
		// Set migration in progress
		update_option(
			'superforms_eav_migration',
			array(
				'status'           => 'in_progress',
				'using_storage'    => 'serialized',
				'total_entries'    => 10,
				'migrated_entries' => 5,
			)
		);

		// Attempt rollback
		$result = SUPER_Migration_Manager::rollback_migration();

		// Verify rollback failed
		$this->assertInstanceOf( 'WP_Error', $result, 'Rollback should fail during in_progress' );
		$this->assertEquals( 'migration_not_completed', $result->get_error_code(), 'Error code should be migration_not_completed' );

		// Cleanup
		delete_option( 'superforms_eav_migration' );
	}

	/**
	 * Test reading data works across all phases
	 */
	public function test_reading_works_across_all_phases() {
		$test_data = $this->create_complex_test_data();
		$entry_id  = $this->create_test_entry( $test_data );

		$phases = array(
			array(
				'name'   => 'Phase 1: Before migration',
				'option' => null,
			),
			array(
				'name'   => 'Phase 2: During migration',
				'option' => array(
					'status'           => 'in_progress',
					'using_storage'    => 'serialized',
					'total_entries'    => 1,
					'migrated_entries' => 0,
				),
			),
			array(
				'name'   => 'Phase 3: After migration (EAV)',
				'option' => array(
					'status'           => 'completed',
					'using_storage'    => 'eav',
					'total_entries'    => 1,
					'migrated_entries' => 1,
				),
			),
			array(
				'name'   => 'Phase 4: After rollback',
				'option' => array(
					'status'           => 'completed',
					'using_storage'    => 'serialized',
					'total_entries'    => 1,
					'migrated_entries' => 1,
					'rollback_count'   => 1,
				),
			),
		);

		foreach ( $phases as $phase ) {
			// Set phase
			if ( $phase['option'] === null ) {
				delete_option( 'superforms_eav_migration' );
			} else {
				update_option( 'superforms_eav_migration', $phase['option'] );
			}

			// Read data
			$retrieved = SUPER_Data_Access::get_entry_data( $entry_id );

			// Verify data readable
			$this->assertIsArray( $retrieved, $phase['name'] . ': Data should be readable' );
			$this->assertEquals( $test_data['email']['value'], $retrieved['email']['value'], $phase['name'] . ': Email should match' );
		}

		// Cleanup
		$this->cleanup_test_entries();
		delete_option( 'superforms_eav_migration' );
	}
}
