<?php
/**
 * Load Testing for SUPER_Form_DAL
 *
 * Tests performance with large datasets (1000+ forms)
 *
 * @package Super_Forms\Tests
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Load Test Form DAL Performance
 */
class Test_Load_Performance extends TestCase {

	/**
	 * Test form IDs for cleanup
	 */
	private $test_form_ids = array();

	/**
	 * Number of forms to create for load testing
	 */
	const LOAD_TEST_COUNT = 1000;

	/**
	 * Setup test environment
	 */
	public function set_up() {
		parent::set_up();

		// Ensure DAL class is loaded
		require_once dirname( __DIR__ ) . '/src/includes/class-form-dal.php';
	}

	/**
	 * Cleanup after tests
	 */
	public function tear_down() {
		// Clean up test forms
		foreach ( $this->test_form_ids as $form_id ) {
			SUPER_Form_DAL::delete( $form_id );
		}
		$this->test_form_ids = array();

		parent::tear_down();
	}

	/**
	 * Test bulk form creation performance
	 */
	public function test_bulk_create_performance() {
		$start_time = microtime( true );
		$created_count = 0;

		// Create forms in batches of 100
		for ( $i = 1; $i <= self::LOAD_TEST_COUNT; $i++ ) {
			$form_id = SUPER_Form_DAL::create( array(
				'name' => 'Load Test Form ' . $i,
				'status' => 'publish',
				'elements' => array(
					'field1' => array(
						'type' => 'text',
						'name' => 'field1',
						'label' => 'Field 1',
					),
				),
				'settings' => array(
					'test_setting' => 'value',
				),
				'translations' => array(),
			) );

			if ( ! is_wp_error( $form_id ) ) {
				$this->test_form_ids[] = $form_id;
				$created_count++;
			}

			// Report progress every 100 forms
			if ( $i % 100 === 0 ) {
				$elapsed = microtime( true ) - $start_time;
				$rate = $i / $elapsed;
				echo sprintf(
					"\n  Created %d/%d forms (%.2f forms/sec)",
					$i,
					self::LOAD_TEST_COUNT,
					$rate
				);
			}
		}

		$total_time = microtime( true ) - $start_time;
		$avg_time = $total_time / self::LOAD_TEST_COUNT;

		echo sprintf(
			"\n  Total: %d forms in %.2f seconds (%.4f sec/form, %.2f forms/sec)\n",
			$created_count,
			$total_time,
			$avg_time,
			self::LOAD_TEST_COUNT / $total_time
		);

		$this->assertEquals( self::LOAD_TEST_COUNT, $created_count );
		// Should create at least 10 forms per second
		$this->assertGreaterThan( 10, self::LOAD_TEST_COUNT / $total_time );
	}

	/**
	 * Test query performance with large dataset
	 *
	 * @depends test_bulk_create_performance
	 */
	public function test_query_performance_large_dataset() {
		$iterations = 10;
		$total_time = 0;

		for ( $i = 0; $i < $iterations; $i++ ) {
			$start_time = microtime( true );

			$forms = SUPER_Form_DAL::query( array(
				'status' => 'publish',
				'number' => -1,
				'orderby' => 'name',
				'order' => 'ASC',
			) );

			$elapsed = microtime( true ) - $start_time;
			$total_time += $elapsed;
		}

		$avg_time = $total_time / $iterations;

		echo sprintf(
			"\n  Query %d forms: %.4f sec average (%.4f sec total for %d iterations)\n",
			count( $forms ),
			$avg_time,
			$total_time,
			$iterations
		);

		$this->assertGreaterThanOrEqual( self::LOAD_TEST_COUNT, count( $forms ) );
		// Query should complete in under 1 second on average
		$this->assertLessThan( 1.0, $avg_time );
	}

	/**
	 * Test get performance with large dataset
	 *
	 * @depends test_bulk_create_performance
	 */
	public function test_get_performance_large_dataset() {
		// Test getting 100 random forms
		$sample_size = 100;
		$sample_ids = array_slice( $this->test_form_ids, 0, $sample_size );

		$start_time = microtime( true );

		foreach ( $sample_ids as $form_id ) {
			$form = SUPER_Form_DAL::get( $form_id );
			$this->assertNotNull( $form );
		}

		$total_time = microtime( true ) - $start_time;
		$avg_time = $total_time / $sample_size;

		echo sprintf(
			"\n  Get %d forms: %.4f sec total (%.4f sec/form, %.2f gets/sec)\n",
			$sample_size,
			$total_time,
			$avg_time,
			$sample_size / $total_time
		);

		// Should get at least 100 forms per second
		$this->assertGreaterThan( 100, $sample_size / $total_time );
	}

	/**
	 * Test update performance with large dataset
	 *
	 * @depends test_bulk_create_performance
	 */
	public function test_update_performance_large_dataset() {
		// Test updating 100 random forms
		$sample_size = 100;
		$sample_ids = array_slice( $this->test_form_ids, 0, $sample_size );

		$start_time = microtime( true );

		foreach ( $sample_ids as $form_id ) {
			$result = SUPER_Form_DAL::update( $form_id, array(
				'name' => 'Updated Form ' . $form_id,
			) );
			$this->assertTrue( $result );
		}

		$total_time = microtime( true ) - $start_time;
		$avg_time = $total_time / $sample_size;

		echo sprintf(
			"\n  Update %d forms: %.4f sec total (%.4f sec/form, %.2f updates/sec)\n",
			$sample_size,
			$total_time,
			$avg_time,
			$sample_size / $total_time
		);

		// Should update at least 50 forms per second
		$this->assertGreaterThan( 50, $sample_size / $total_time );
	}

	/**
	 * Test search performance with large dataset
	 *
	 * @depends test_bulk_create_performance
	 */
	public function test_search_performance_large_dataset() {
		$iterations = 10;
		$total_time = 0;

		for ( $i = 0; $i < $iterations; $i++ ) {
			$start_time = microtime( true );

			$results = SUPER_Form_DAL::search( 'Load Test' );

			$elapsed = microtime( true ) - $start_time;
			$total_time += $elapsed;
		}

		$avg_time = $total_time / $iterations;

		echo sprintf(
			"\n  Search %d forms: %.4f sec average (%.4f sec total for %d iterations)\n",
			count( $results ),
			$avg_time,
			$total_time,
			$iterations
		);

		$this->assertGreaterThanOrEqual( self::LOAD_TEST_COUNT, count( $results ) );
		// Search should complete in under 1 second on average
		$this->assertLessThan( 1.0, $avg_time );
	}

	/**
	 * Test delete performance with large dataset
	 *
	 * @depends test_bulk_create_performance
	 * @depends test_query_performance_large_dataset
	 * @depends test_get_performance_large_dataset
	 * @depends test_update_performance_large_dataset
	 * @depends test_search_performance_large_dataset
	 */
	public function test_delete_performance_large_dataset() {
		$count = count( $this->test_form_ids );
		$start_time = microtime( true );
		$deleted_count = 0;

		foreach ( $this->test_form_ids as $form_id ) {
			$result = SUPER_Form_DAL::delete( $form_id );
			if ( $result ) {
				$deleted_count++;
			}

			// Report progress every 100 forms
			if ( $deleted_count % 100 === 0 ) {
				$elapsed = microtime( true ) - $start_time;
				$rate = $deleted_count / $elapsed;
				echo sprintf(
					"\n  Deleted %d/%d forms (%.2f forms/sec)",
					$deleted_count,
					$count,
					$rate
				);
			}
		}

		$total_time = microtime( true ) - $start_time;
		$avg_time = $total_time / $count;

		echo sprintf(
			"\n  Total: %d forms deleted in %.2f seconds (%.4f sec/form, %.2f forms/sec)\n",
			$deleted_count,
			$total_time,
			$avg_time,
			$count / $total_time
		);

		// Clear array since we deleted them
		$this->test_form_ids = array();

		$this->assertEquals( $count, $deleted_count );
		// Should delete at least 10 forms per second
		$this->assertGreaterThan( 10, $count / $total_time );
	}

	/**
	 * Test memory usage with large dataset
	 */
	public function test_memory_usage_large_dataset() {
		$initial_memory = memory_get_usage();

		// Query all forms
		$forms = SUPER_Form_DAL::query( array(
			'status' => 'publish',
			'number' => -1,
		) );

		$peak_memory = memory_get_peak_usage();
		$memory_used = $peak_memory - $initial_memory;
		$memory_per_form = $memory_used / count( $forms );

		echo sprintf(
			"\n  Memory: %.2f MB total (%.2f KB/form for %d forms)\n",
			$memory_used / 1024 / 1024,
			$memory_per_form / 1024,
			count( $forms )
		);

		// Should use less than 50MB for 1000 forms
		$this->assertLessThan( 50 * 1024 * 1024, $memory_used );
	}
}
