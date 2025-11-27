<?php
/**
 * Performance Tests for Trigger System
 *
 * Tests trigger lookup, condition evaluation, and execution performance.
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Performance extends WP_UnitTestCase {

	/**
	 * Test database logger instance
	 */
	private static $created_trigger_ids = array();

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
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up test triggers
		if ( class_exists( 'SUPER_Trigger_DAL' ) && ! empty( self::$created_trigger_ids ) ) {
			foreach ( self::$created_trigger_ids as $trigger_id ) {
				SUPER_Trigger_DAL::delete_trigger( $trigger_id );
			}
			self::$created_trigger_ids = array();
		}

		// Clear test context
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::clear_test_context( 'pass' );
		}
	}

	/**
	 * Test: Trigger lookup performance with 100 triggers
	 *
	 * Creates 100 triggers and measures lookup time.
	 * Target: < 20ms for trigger resolution
	 */
	public function test_trigger_lookup_performance_100_triggers() {
		if ( ! class_exists( 'SUPER_Trigger_DAL' ) || ! class_exists( 'SUPER_Trigger_Manager' ) ) {
			$this->markTestSkipped( 'Trigger classes not loaded' );
		}

		// Create 100 triggers (50 for form 1, 50 for form 2)
		for ( $i = 0; $i < 100; $i++ ) {
			$form_id = ( $i < 50 ) ? 1 : 2;

			$trigger_id = SUPER_Trigger_DAL::create_trigger( array(
				'trigger_name' => "Performance Test Trigger {$i}",
				'scope' => 'form',
				'scope_id' => $form_id,
				'event_id' => 'form.submitted',
				'enabled' => 1,
				'execution_order' => $i,
			) );

			if ( $trigger_id && ! is_wp_error( $trigger_id ) ) {
				self::$created_trigger_ids[] = $trigger_id;
			}
		}

		$this->assertCount( 100, self::$created_trigger_ids, 'Should create 100 triggers' );

		// Measure lookup time for form 1 (should find ~50 triggers)
		$iterations = 10;
		$timings = array();

		for ( $i = 0; $i < $iterations; $i++ ) {
			$start = microtime( true );

			$triggers = SUPER_Trigger_Manager::resolve_triggers_for_event(
				'form.submitted',
				array( 'form_id' => 1 )
			);

			$end = microtime( true );
			$timings[] = ( $end - $start ) * 1000; // Convert to ms
		}

		$avg_time = array_sum( $timings ) / count( $timings );
		$max_time = max( $timings );

		// Log performance metrics
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::log_performance(
				'trigger_lookup_100',
				array(
					'total_triggers' => 100,
					'triggers_found' => count( $triggers ),
					'iterations' => $iterations,
					'avg_time_ms' => $avg_time,
					'max_time_ms' => $max_time,
				)
			);
		}

		// Assert performance requirements
		$this->assertLessThan(
			20,
			$avg_time,
			sprintf( 'Average trigger lookup with 100 triggers should be <20ms (actual: %.3fms)', $avg_time )
		);

		$this->assertLessThan(
			50,
			$max_time,
			sprintf( 'Max trigger lookup should be <50ms (actual: %.3fms)', $max_time )
		);
	}

	/**
	 * Test: Condition evaluation performance
	 *
	 * Tests complex condition evaluation with nested groups.
	 * Target: < 10ms for nested condition evaluation
	 */
	public function test_condition_evaluation_performance() {
		if ( ! class_exists( 'SUPER_Trigger_Conditions' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Conditions not loaded' );
		}

		// Create complex nested condition structure
		$conditions = array(
			'operator' => 'AND',
			'rules' => array(
				array(
					'field' => '{email}',
					'operator' => 'contains',
					'value' => '@gmail.com',
				),
				array(
					'operator' => 'OR',
					'rules' => array(
						array(
							'field' => '{country}',
							'operator' => '==',
							'value' => 'US',
						),
						array(
							'field' => '{country}',
							'operator' => '==',
							'value' => 'CA',
						),
						array(
							'operator' => 'AND',
							'rules' => array(
								array(
									'field' => '{total}',
									'operator' => '>',
									'value' => '100',
								),
								array(
									'field' => '{membership}',
									'operator' => '==',
									'value' => 'premium',
								),
							),
						),
					),
				),
			),
		);

		$context = array(
			'form_id' => 1,
			'entry_id' => 999,
			'data' => array(
				'email' => array( 'value' => 'test@gmail.com' ),
				'country' => array( 'value' => 'US' ),
				'total' => array( 'value' => '150' ),
				'membership' => array( 'value' => 'premium' ),
			),
		);

		// Measure evaluation time
		$iterations = 100;
		$timings = array();

		for ( $i = 0; $i < $iterations; $i++ ) {
			$start = microtime( true );

			$result = SUPER_Trigger_Conditions::evaluate( $conditions, $context );

			$end = microtime( true );
			$timings[] = ( $end - $start ) * 1000; // Convert to ms
		}

		$avg_time = array_sum( $timings ) / count( $timings );
		$max_time = max( $timings );

		// Log performance metrics
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::log_performance(
				'condition_evaluation_nested',
				array(
					'nesting_depth' => 3,
					'total_rules' => 6,
					'iterations' => $iterations,
					'avg_time_ms' => $avg_time,
					'max_time_ms' => $max_time,
					'result' => $result,
				)
			);
		}

		// Assert performance requirements
		$this->assertLessThan(
			10,
			$avg_time,
			sprintf( 'Average condition evaluation should be <10ms (actual: %.3fms)', $avg_time )
		);

		$this->assertLessThan(
			25,
			$max_time,
			sprintf( 'Max condition evaluation should be <25ms (actual: %.3fms)', $max_time )
		);

		// Also verify the result is correct
		$this->assertTrue( $result, 'Condition should evaluate to true' );
	}

	/**
	 * Test: Tag replacement performance
	 *
	 * Tests tag replacement with many fields.
	 * Target: < 5ms per replacement
	 */
	public function test_tag_replacement_performance() {
		if ( ! class_exists( 'SUPER_Trigger_Conditions' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Conditions not loaded' );
		}

		// Create context with many fields
		$context = array(
			'form_id' => 123,
			'entry_id' => 456,
			'timestamp' => current_time( 'mysql' ),
			'data' => array(),
		);

		// Add 50 fields to context
		for ( $i = 0; $i < 50; $i++ ) {
			$context['data'][ "field_{$i}" ] = array(
				'value' => "Value for field {$i}",
				'label' => "Field {$i}",
			);
		}

		// String with many tags to replace
		$template = 'Form {form_id} Entry {entry_id} - ';
		for ( $i = 0; $i < 20; $i++ ) {
			$template .= "{field_{$i}} | ";
		}

		// Measure replacement time
		$iterations = 100;
		$timings = array();

		for ( $i = 0; $i < $iterations; $i++ ) {
			$start = microtime( true );

			$result = SUPER_Trigger_Conditions::replace_tags( $template, $context );

			$end = microtime( true );
			$timings[] = ( $end - $start ) * 1000; // Convert to ms
		}

		$avg_time = array_sum( $timings ) / count( $timings );
		$max_time = max( $timings );

		// Log performance metrics
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::log_performance(
				'tag_replacement',
				array(
					'context_fields' => 50,
					'tags_to_replace' => 22,
					'iterations' => $iterations,
					'avg_time_ms' => $avg_time,
					'max_time_ms' => $max_time,
				)
			);
		}

		// Assert performance requirements
		$this->assertLessThan(
			5,
			$avg_time,
			sprintf( 'Average tag replacement should be <5ms (actual: %.3fms)', $avg_time )
		);

		$this->assertLessThan(
			15,
			$max_time,
			sprintf( 'Max tag replacement should be <15ms (actual: %.3fms)', $max_time )
		);

		// Verify replacement worked
		$this->assertStringContainsString( 'Form 123', $result );
		$this->assertStringContainsString( 'Entry 456', $result );
		$this->assertStringContainsString( 'Value for field 0', $result );
	}

	/**
	 * Test: Full trigger execution cycle performance
	 *
	 * Tests complete flow: event → trigger lookup → condition → action
	 * Target: < 100ms for full cycle
	 */
	public function test_full_execution_cycle_performance() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) || ! class_exists( 'SUPER_Trigger_DAL' ) ) {
			$this->markTestSkipped( 'Trigger classes not loaded' );
		}

		// Create a test trigger with log action
		$trigger_id = SUPER_Trigger_DAL::create_trigger( array(
			'trigger_name' => 'Performance Test Full Cycle',
			'scope' => 'form',
			'scope_id' => 999,
			'event_id' => 'form.submitted',
			'conditions' => json_encode( array(
				'operator' => 'AND',
				'rules' => array(
					array(
						'field' => '{form_id}',
						'operator' => '==',
						'value' => '999',
					),
				),
			) ),
			'enabled' => 1,
		) );

		if ( $trigger_id && ! is_wp_error( $trigger_id ) ) {
			self::$created_trigger_ids[] = $trigger_id;

			// Add action
			SUPER_Trigger_DAL::create_action( $trigger_id, array(
				'action_type' => 'log_message',
				'action_config' => json_encode( array(
					'message' => 'Performance test: Form {form_id}',
					'log_level' => 'debug',
				) ),
				'execution_order' => 1,
				'enabled' => 1,
			) );
		}

		$context = array(
			'form_id' => 999,
			'entry_id' => 888,
			'timestamp' => current_time( 'mysql' ),
			'data' => array(
				'email' => array( 'value' => 'test@example.com' ),
			),
		);

		// Measure full cycle time
		$iterations = 10;
		$timings = array();

		for ( $i = 0; $i < $iterations; $i++ ) {
			$start = microtime( true );

			$results = SUPER_Trigger_Executor::fire_event( 'form.submitted', $context );

			$end = microtime( true );
			$timings[] = ( $end - $start ) * 1000; // Convert to ms
		}

		$avg_time = array_sum( $timings ) / count( $timings );
		$max_time = max( $timings );

		// Log performance metrics
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::log_performance(
				'full_execution_cycle',
				array(
					'trigger_id' => $trigger_id,
					'iterations' => $iterations,
					'avg_time_ms' => $avg_time,
					'max_time_ms' => $max_time,
					'triggers_executed' => count( $results ),
				)
			);
		}

		// Assert performance requirements
		$this->assertLessThan(
			100,
			$avg_time,
			sprintf( 'Average full cycle should be <100ms (actual: %.3fms)', $avg_time )
		);

		$this->assertLessThan(
			200,
			$max_time,
			sprintf( 'Max full cycle should be <200ms (actual: %.3fms)', $max_time )
		);
	}

	/**
	 * Test: Memory usage during trigger processing
	 *
	 * Ensures memory doesn't grow excessively during processing.
	 */
	public function test_memory_usage_stability() {
		if ( ! class_exists( 'SUPER_Trigger_Executor' ) ) {
			$this->markTestSkipped( 'SUPER_Trigger_Executor not loaded' );
		}

		$initial_memory = memory_get_usage( true );
		$peak_memory = 0;

		// Fire 100 events
		for ( $i = 0; $i < 100; $i++ ) {
			SUPER_Trigger_Executor::fire_event(
				'form.submitted',
				array(
					'form_id' => 1,
					'entry_id' => $i,
					'data' => array(
						'email' => array( 'value' => "test{$i}@example.com" ),
					),
				)
			);

			$current_memory = memory_get_usage( true );
			if ( $current_memory > $peak_memory ) {
				$peak_memory = $current_memory;
			}
		}

		$final_memory = memory_get_usage( true );
		$memory_growth = $final_memory - $initial_memory;
		$memory_growth_mb = $memory_growth / 1024 / 1024;

		// Log memory metrics
		if ( class_exists( 'SUPER_Test_DB_Logger' ) ) {
			SUPER_Test_DB_Logger::log_performance(
				'memory_usage',
				array(
					'events_fired' => 100,
					'initial_memory_mb' => $initial_memory / 1024 / 1024,
					'final_memory_mb' => $final_memory / 1024 / 1024,
					'peak_memory_mb' => $peak_memory / 1024 / 1024,
					'growth_mb' => $memory_growth_mb,
				)
			);
		}

		// Memory growth should be less than 10MB for 100 events
		$this->assertLessThan(
			10,
			$memory_growth_mb,
			sprintf( 'Memory growth for 100 events should be <10MB (actual: %.2fMB)', $memory_growth_mb )
		);
	}
}
