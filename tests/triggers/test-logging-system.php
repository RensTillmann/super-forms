<?php
/**
 * Test Logging System (Phase 3)
 *
 * Tests for SUPER_Trigger_Logger, SUPER_Trigger_Performance,
 * SUPER_Trigger_Debugger, and SUPER_Trigger_Compliance.
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Logging_System extends WP_UnitTestCase {

    /**
     * Setup before each test
     */
    public function setUp(): void {
        parent::setUp();

        // Reset performance metrics
        if ( class_exists( 'SUPER_Trigger_Performance' ) ) {
            SUPER_Trigger_Performance::reset();
        }

        // Reset debugger data
        if ( class_exists( 'SUPER_Trigger_Debugger' ) ) {
            SUPER_Trigger_Debugger::reset();
        }
    }

    /**
     * Teardown after each test
     */
    public function tearDown(): void {
        parent::tearDown();

        // Clean up test logs
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->prefix}superforms_trigger_logs WHERE event_id LIKE 'test.%'" );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}superforms_compliance_audit WHERE action_type LIKE 'test_%'" );
    }

    // ============================================
    // SUPER_Trigger_Logger Tests
    // ============================================

    /**
     * Test Logger singleton pattern
     */
    public function test_logger_singleton() {
        $instance1 = SUPER_Trigger_Logger::instance();
        $instance2 = SUPER_Trigger_Logger::instance();

        $this->assertSame( $instance1, $instance2, 'Logger should return same instance' );
    }

    /**
     * Test Logger log_execution writes to database
     */
    public function test_logger_log_execution() {
        global $wpdb;
        $logger = SUPER_Trigger_Logger::instance();

        // Use the actual method signature:
        // log_execution( $trigger_name, $action_name, $status, $message, $data = array(), $execution_time = null )
        $logger->log_execution(
            'Test Trigger',
            'Test Action',
            'success',
            'Test log message',
            array(
                'trigger_id' => 999,
                'action_id'  => 888,
                'entry_id'   => 777,
                'form_id'    => 666,
                'event_id'   => 'test.log_execution',
            ),
            0.05  // 50ms in seconds
        );

        // Check database
        $log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}superforms_trigger_logs WHERE event_id = %s",
                'test.log_execution'
            ),
            ARRAY_A
        );

        $this->assertNotNull( $log, 'Log entry should exist in database' );
        $this->assertEquals( 999, $log['trigger_id'] );
        $this->assertEquals( 'success', $log['status'] );
    }

    /**
     * Test Logger get_logs retrieval
     */
    public function test_logger_get_logs() {
        global $wpdb;

        // Insert test logs
        $wpdb->insert(
            $wpdb->prefix . 'superforms_trigger_logs',
            array(
                'trigger_id'        => 1,
                'event_id'          => 'test.get_logs',
                'status'            => 'success',
                'execution_time_ms' => 100,
                'executed_at'       => current_time( 'mysql' ),
            )
        );

        $logger = SUPER_Trigger_Logger::instance();
        $logs = $logger->get_logs( array( 'event_id' => 'test.get_logs' ) );

        $this->assertNotEmpty( $logs, 'Should retrieve test log' );
        $this->assertEquals( 'test.get_logs', $logs[0]['event_id'] );
    }

    /**
     * Test Logger statistics
     */
    public function test_logger_statistics() {
        global $wpdb;

        // Insert test logs with different statuses
        $table = $wpdb->prefix . 'superforms_trigger_logs';
        $wpdb->insert( $table, array(
            'trigger_id'  => 1,
            'event_id'    => 'test.stats',
            'status'      => 'success',
            'executed_at' => current_time( 'mysql' ),
        ));
        $wpdb->insert( $table, array(
            'trigger_id'  => 1,
            'event_id'    => 'test.stats',
            'status'      => 'failed',
            'executed_at' => current_time( 'mysql' ),
        ));

        $logger = SUPER_Trigger_Logger::instance();
        $stats = $logger->get_statistics();

        $this->assertIsArray( $stats );
        $this->assertArrayHasKey( 'total', $stats );
    }

    // ============================================
    // SUPER_Trigger_Performance Tests
    // ============================================

    /**
     * Test Performance timer start/end
     */
    public function test_performance_timer() {
        SUPER_Trigger_Performance::start_timer( 'test_timer' );

        // Small delay
        usleep( 10000 ); // 10ms

        $metrics = SUPER_Trigger_Performance::end_timer( 'test_timer' );

        $this->assertIsArray( $metrics );
        $this->assertArrayHasKey( 'duration', $metrics );
        $this->assertArrayHasKey( 'duration_ms', $metrics );
        $this->assertArrayHasKey( 'memory_used', $metrics );
        $this->assertGreaterThan( 0, $metrics['duration_ms'] );
    }

    /**
     * Test Performance timer with metadata
     */
    public function test_performance_timer_with_meta() {
        SUPER_Trigger_Performance::start_timer( 'test_meta_timer' );

        $metrics = SUPER_Trigger_Performance::end_timer( 'test_meta_timer', array(
            'action_type' => 'send_email',
            'trigger_id'  => 123,
        ));

        $this->assertIsArray( $metrics['meta'] );
        $this->assertEquals( 'send_email', $metrics['meta']['action_type'] );
        $this->assertEquals( 123, $metrics['meta']['trigger_id'] );
    }

    /**
     * Test Performance get_elapsed without stopping timer
     */
    public function test_performance_get_elapsed() {
        SUPER_Trigger_Performance::start_timer( 'elapsed_timer' );

        usleep( 5000 ); // 5ms

        $elapsed = SUPER_Trigger_Performance::get_elapsed( 'elapsed_timer' );

        $this->assertIsFloat( $elapsed );
        $this->assertGreaterThan( 0, $elapsed );

        // Timer should still be running
        $this->assertTrue( SUPER_Trigger_Performance::has_running_timers() );

        // Clean up
        SUPER_Trigger_Performance::end_timer( 'elapsed_timer' );
    }

    /**
     * Test Performance time_callback
     */
    public function test_performance_time_callback() {
        $result = SUPER_Trigger_Performance::time_callback(
            'callback_timer',
            function( $a, $b ) {
                usleep( 5000 );
                return $a + $b;
            },
            array( 5, 3 )
        );

        $this->assertIsArray( $result );
        $this->assertEquals( 8, $result['result'] );
        $this->assertIsArray( $result['metrics'] );
        $this->assertGreaterThan( 0, $result['metrics']['duration_ms'] );
    }

    /**
     * Test Performance get_all_metrics
     */
    public function test_performance_get_all_metrics() {
        SUPER_Trigger_Performance::start_timer( 'metric1' );
        SUPER_Trigger_Performance::end_timer( 'metric1' );

        SUPER_Trigger_Performance::start_timer( 'metric2' );
        SUPER_Trigger_Performance::end_timer( 'metric2' );

        $metrics = SUPER_Trigger_Performance::get_all_metrics();

        $this->assertCount( 2, $metrics );
        $this->assertArrayHasKey( 'metric1', $metrics );
        $this->assertArrayHasKey( 'metric2', $metrics );
    }

    /**
     * Test Performance summary statistics
     */
    public function test_performance_summary() {
        SUPER_Trigger_Performance::start_timer( 'sum1' );
        usleep( 10000 );
        SUPER_Trigger_Performance::end_timer( 'sum1' );

        SUPER_Trigger_Performance::start_timer( 'sum2' );
        usleep( 20000 );
        SUPER_Trigger_Performance::end_timer( 'sum2' );

        $summary = SUPER_Trigger_Performance::get_summary();

        $this->assertEquals( 2, $summary['count'] );
        $this->assertGreaterThan( 0, $summary['total_time'] );
        $this->assertGreaterThan( 0, $summary['avg_time'] );
    }

    /**
     * Test Performance record_metric directly
     */
    public function test_performance_record_metric() {
        SUPER_Trigger_Performance::record_metric( 'direct_metric', 0.5, array(
            'source' => 'external',
        ));

        $metric = SUPER_Trigger_Performance::get_metric( 'direct_metric' );

        $this->assertNotNull( $metric );
        $this->assertEquals( 0.5, $metric['duration'] );
        $this->assertEquals( 500, $metric['duration_ms'] );
    }

    /**
     * Test Performance slow threshold detection
     */
    public function test_performance_slow_threshold() {
        $default = SUPER_Trigger_Performance::get_slow_threshold();
        $this->assertEquals( 1.0, $default ); // Default is 1 second

        SUPER_Trigger_Performance::set_slow_threshold( 0.5 );
        $this->assertEquals( 0.5, SUPER_Trigger_Performance::get_slow_threshold() );

        // Reset to default
        SUPER_Trigger_Performance::set_slow_threshold( 1.0 );
    }

    // ============================================
    // SUPER_Trigger_Debugger Tests
    // ============================================

    /**
     * Test Debugger debug mode detection
     */
    public function test_debugger_debug_mode() {
        // By default debug mode depends on WP_DEBUG and DEBUG_SF
        $is_debug = SUPER_Trigger_Debugger::is_debug_mode();

        // Just verify it returns a boolean
        $this->assertIsBool( $is_debug );
    }

    /**
     * Test Debugger log_event_fired
     */
    public function test_debugger_log_event_fired() {
        SUPER_Trigger_Debugger::log_event_fired( 'test.debug_event', array(
            'form_id' => 123,
        ));

        $data = SUPER_Trigger_Debugger::get_debug_data();

        $this->assertArrayHasKey( 'events', $data );
        $this->assertNotEmpty( $data['events'] );
        $this->assertEquals( 'test.debug_event', $data['events'][0]['event_id'] );
    }

    /**
     * Test Debugger log_trigger_evaluated
     */
    public function test_debugger_log_trigger_evaluated() {
        SUPER_Trigger_Debugger::log_trigger_evaluated( 1, 'Test Trigger', true, array() );

        $data = SUPER_Trigger_Debugger::get_debug_data();

        $this->assertArrayHasKey( 'triggers', $data );
        $this->assertNotEmpty( $data['triggers'] );
        $this->assertEquals( 1, $data['triggers'][0]['trigger_id'] );
        $this->assertTrue( $data['triggers'][0]['matched'] );
    }

    /**
     * Test Debugger log_action_executed
     */
    public function test_debugger_log_action_executed() {
        SUPER_Trigger_Debugger::log_action_executed( 5, 'send_email', true, null );

        $data = SUPER_Trigger_Debugger::get_debug_data();

        $this->assertArrayHasKey( 'actions', $data );
        $this->assertNotEmpty( $data['actions'] );
        $this->assertEquals( 5, $data['actions'][0]['action_id'] );
        $this->assertEquals( 'send_email', $data['actions'][0]['action_type'] );
        $this->assertTrue( $data['actions'][0]['success'] );
    }

    /**
     * Test Debugger log_condition_evaluated
     */
    public function test_debugger_log_condition_evaluated() {
        SUPER_Trigger_Debugger::log_condition_evaluated(
            'field_value',
            'email',
            'equals',
            'test@example.com',
            'test@example.com',
            true
        );

        $data = SUPER_Trigger_Debugger::get_debug_data();

        $this->assertArrayHasKey( 'conditions', $data );
        $this->assertNotEmpty( $data['conditions'] );
        $this->assertTrue( $data['conditions'][0]['passed'] );
    }

    /**
     * Test Debugger reset
     */
    public function test_debugger_reset() {
        SUPER_Trigger_Debugger::log_event_fired( 'test.reset', array() );

        $data_before = SUPER_Trigger_Debugger::get_debug_data();
        $this->assertNotEmpty( $data_before['events'] );

        SUPER_Trigger_Debugger::reset();

        $data_after = SUPER_Trigger_Debugger::get_debug_data();
        $this->assertEmpty( $data_after['events'] );
    }

    // ============================================
    // SUPER_Trigger_Compliance Tests
    // ============================================

    /**
     * Test Compliance log_compliance_action
     */
    public function test_compliance_log_action() {
        global $wpdb;

        // Get instance and call method
        // Signature: log_compliance_action( $action_type, $details = array(), $object_type = null, $object_id = null )
        $compliance = SUPER_Trigger_Compliance::instance();
        $compliance->log_compliance_action(
            'test_action_type',
            array( 'note' => 'Test compliance log' ),
            'trigger',
            123
        );

        $log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}superforms_compliance_audit WHERE action_type = %s",
                'test_action_type'
            ),
            ARRAY_A
        );

        $this->assertNotNull( $log, 'Compliance log should exist' );
        $this->assertEquals( 'trigger', $log['object_type'] );
        $this->assertEquals( 123, $log['object_id'] );
    }

    /**
     * Test Compliance delete_entry_logs
     */
    public function test_compliance_delete_entry_logs() {
        global $wpdb;

        // Insert log for an entry
        $wpdb->insert(
            $wpdb->prefix . 'superforms_trigger_logs',
            array(
                'trigger_id'  => 1,
                'entry_id'    => 99999,
                'event_id'    => 'test.delete_entry',
                'status'      => 'success',
                'executed_at' => current_time( 'mysql' ),
            )
        );

        $count_before = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_trigger_logs WHERE entry_id = 99999"
        );
        $this->assertEquals( 1, $count_before );

        // Delete logs for entry
        $compliance = SUPER_Trigger_Compliance::instance();
        $deleted = $compliance->delete_entry_logs( 99999 );

        $this->assertEquals( 1, $deleted );

        $count_after = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_trigger_logs WHERE entry_id = 99999"
        );
        $this->assertEquals( 0, $count_after );
    }

    /**
     * Test Compliance export_entry_logs
     */
    public function test_compliance_export_entry_logs() {
        global $wpdb;

        // Insert log for an entry
        $wpdb->insert(
            $wpdb->prefix . 'superforms_trigger_logs',
            array(
                'trigger_id'        => 1,
                'entry_id'          => 88888,
                'event_id'          => 'test.export_entry',
                'status'            => 'success',
                'execution_time_ms' => 100,
                'executed_at'       => current_time( 'mysql' ),
            )
        );

        // Export logs (returns JSON by default)
        $compliance = SUPER_Trigger_Compliance::instance();
        $export_json = $compliance->export_entry_logs( 88888 );

        // Decode JSON to array
        $export = json_decode( $export_json, true );

        $this->assertIsArray( $export );
        $this->assertNotEmpty( $export );
        $this->assertEquals( 'test.export_entry', $export[0]['event_id'] );
        $this->assertEquals( 'success', $export[0]['status'] );
    }

    /**
     * Test Compliance retention policy enforcement
     */
    public function test_compliance_retention_policy() {
        global $wpdb;

        // Insert old audit entry (older than retention period)
        $old_date = gmdate( 'Y-m-d H:i:s', strtotime( '-100 days' ) );
        $wpdb->insert(
            $wpdb->prefix . 'superforms_compliance_audit',
            array(
                'action_type'  => 'test_old_retention',
                'object_type'  => 'trigger',
                'object_id'    => 1,
                'performed_at' => $old_date,
            )
        );

        // Insert recent entry
        $wpdb->insert(
            $wpdb->prefix . 'superforms_compliance_audit',
            array(
                'action_type'  => 'test_recent_retention',
                'object_type'  => 'trigger',
                'object_id'    => 2,
                'performed_at' => current_time( 'mysql' ),
            )
        );

        // Enforce retention (default 90 days)
        $compliance = SUPER_Trigger_Compliance::instance();
        $compliance->enforce_retention_policy();

        // Old entry should be deleted
        $old_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_compliance_audit WHERE action_type = 'test_old_retention'"
        );
        $this->assertEquals( 0, $old_count, 'Old audit entries should be deleted' );

        // Recent entry should still exist
        $recent_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_compliance_audit WHERE action_type = 'test_recent_retention'"
        );
        $this->assertEquals( 1, $recent_count, 'Recent audit entries should be preserved' );
    }
}
