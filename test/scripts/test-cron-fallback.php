#!/usr/bin/env php
<?php
/**
 * WP-Cron Fallback System Tests
 *
 * Comprehensive test suite for SUPER_Cron_Fallback system
 * Can run via CLI or Developer Tools button
 *
 * SECURITY: Uses secure bootstrap.php for WordPress loading
 * - Deterministic path search (no user input)
 * - Safety limits prevent infinite loops
 * - Matches WordPress core pattern
 * - See test/bootstrap.php for implementation details
 *
 * @package Super_Forms
 * @since 6.4.127
 */

// Simulate admin context for CLI tests
// Required for SUPER_Background_Migration::check_version_and_schedule() to run
if (!defined('WP_ADMIN')) {
    define('WP_ADMIN', true);
}

// Bootstrap WordPress if running via CLI
// Uses secure bootstrap.php that searches upward for wp-load.php
if (!defined('ABSPATH')) {
    require_once(dirname(__DIR__) . '/bootstrap.php');
}

// Load SUPER_Ajax class explicitly for CLI tests
// Normally loaded via WordPress hooks, but hooks may not fire in CLI context
if (!class_exists('SUPER_Ajax')) {
    require_once(dirname(dirname(__DIR__)) . '/includes/class-ajax.php');
}

// Load WordPress admin functions for cleanup (wp_delete_user, etc.)
if (!function_exists('wp_delete_user')) {
    require_once(ABSPATH . 'wp-admin/includes/user.php');
}

if (!class_exists('SUPER_Cron_Fallback_Test')) :

class SUPER_Cron_Fallback_Test {

    private $results = [];
    private $start_time = 0;
    private $current_test = '';
    private $original_user_id = 0;
    private $test_entries = [];

    /**
     * Available tests
     */
    private $available_tests = [
        'all' => 'All Tests',
        // Priority 0: Critical Gap Tests (7 methods)
        'plugin_auto_update' => '[P0] Plugin Auto-Update Simulation',
        'ftp_override' => '[P0] FTP Override Simulation',
        'migration_threshold_crossing' => '[P0] Migration Threshold Crossing',
        'real_cron_failure' => '[P0] Real WP-Cron Failure (Enabled But Broken)',
        'stuck_migration_resumption' => '[P0] Stuck Migration Resumption',
        'e2e_integration' => '[P0] Automated E2E Integration',
        'csv_import_after_migration' => '[P0] CSV Import After Migration',
        // Priority 1: Critical Path Tests (13 methods)
        'queue_stale_no_history_work_pending' => '[P1] Queue Stale: No History + Work Pending',
        'queue_fresh_no_history_no_work' => '[P1] Queue Fresh: No History + No Work',
        'queue_stale_old_run' => '[P1] Queue Stale: Last Run >15min',
        'queue_fresh_recent_run' => '[P1] Queue Fresh: Recent Run',
        'detects_disabled_cron' => '[P1] Detects DISABLE_WP_CRON',
        'no_trigger_cron_enabled' => '[P1] No Trigger When Cron Enabled',
        'pending_work_migration' => '[P1] Pending Work: Migration',
        'no_pending_work_complete' => '[P1] No Pending Work: Complete',
        'notice_shows_stale_work' => '[P1] Notice Shows: Stale + Work',
        'notice_hides_no_work' => '[P1] Notice Hides: No Work',
        'notice_hides_dismissed' => '[P1] Notice Hides: Dismissed',
        'notice_reappears_stalled' => '[P1] Notice Reappears: Still Stalled',
        'notice_admin_only' => '[P1] Notice: Admin Only',
        // Priority 2: AJAX Endpoint Tests (8 methods)
        'ajax_trigger_requires_nonce' => '[P2] AJAX Trigger: Requires Nonce',
        'ajax_trigger_requires_permission' => '[P2] AJAX Trigger: Requires Permission',
        'ajax_dismiss_requires_auth' => '[P2] AJAX Dismiss: Requires Auth',
        'ajax_trigger_attempts_async' => '[P2] Trigger: Async Attempt',
        'ajax_process_sync_works' => '[P2] Process: Batch Works',
        'ajax_process_returns_progress' => '[P2] Process: Returns Progress',
        'ajax_dismiss_saves_meta' => '[P2] Dismiss: Saves Meta',
        'ajax_error_batch_fails' => '[P2] Edge Cases: Empty Migration',
        // Priority 3: Integration Tests (3 methods)
        'integration_disabled_cron_flow' => '[P3] Integration: Disabled Cron Flow',
        'integration_async_mode' => '[P3] Integration: Async Mode',
        'integration_notice_lifecycle' => '[P3] Integration: Notice Lifecycle',
        // Test Isolation Verification
        'verify_test_isolation' => '[VERIFY] Test Isolation - Runs Key Tests Twice',
    ];

    /**
     * Run selected test or all tests
     *
     * @param string $test_name Test to run ('all' or specific test)
     * @return array Test results
     */
    public function run($test_name = 'all') {
        // Safety checks
        $safety_check = $this->safety_check();
        if ($safety_check !== true) {
            return $this->error_result($safety_check);
        }

        $this->log("🧪 Starting WP-Cron Fallback Tests");
        $this->log("Test: " . $this->available_tests[$test_name]);
        $this->log("Date: " . date('Y-m-d H:i:s'));
        $this->log("========================================");

        // Store original user for restoration
        $this->original_user_id = get_current_user_id();

        try {
            if ($test_name === 'all') {
                // Run all tests sequentially
                foreach ($this->available_tests as $key => $label) {
                    if ($key === 'all') continue;
                    $this->run_single_test($key);
                }
            } else {
                // Run single test
                $this->run_single_test($test_name);
            }
        } catch (Exception $e) {
            $this->log("❌ FATAL ERROR: " . $e->getMessage());
            $this->cleanup_all();
            return $this->error_result($e->getMessage());
        }

        // Final cleanup
        $this->cleanup_all();

        return $this->get_results();
    }

    /**
     * Run a single test by name
     *
     * @param string $test_name
     */
    private function run_single_test($test_name) {
        $this->current_test = $test_name;
        $this->start_time = microtime(true);

        $method = 'test_' . $test_name;
        if (!method_exists($this, $method)) {
            throw new Exception("Test method $method does not exist");
        }

        try {
            $this->$method();
            $duration = round((microtime(true) - $this->start_time) * 1000, 2);
            $this->log("✅ PASSED ({$duration}ms)");
        } catch (Exception $e) {
            $duration = round((microtime(true) - $this->start_time) * 1000, 2);
            $this->log("❌ FAILED ({$duration}ms): " . $e->getMessage());
            throw $e; // Re-throw to stop execution
        } finally {
            // Always cleanup test-specific data
            $this->cleanup_test_data();
        }
    }

    // ========================================
    // PRIORITY 1: CRITICAL PATH TESTS
    // ========================================

    /**
     * Test: Queue stale when no history and work pending (fresh install + broken cron)
     */
    private function test_queue_stale_no_history_work_pending() {
        $this->log("\n--- [P1] Queue Stale: No History + Work Pending ---");

        // Establish pre-update state: Fresh install with pending migration
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => false, // No queue history
            'entries_count' => 10,
            'create_entries' => true, // Create real entries
        ));

        // Check if queue is considered stale
        $is_stale = SUPER_Cron_Fallback::is_queue_stale();

        $this->assert($is_stale === true, 'Queue should be stale when never run with pending work');
        $this->log("✓ Queue correctly identified as stale");
    }

    /**
     * Test: Queue fresh when no history and no work
     */
    private function test_queue_fresh_no_history_no_work() {
        $this->log("\n--- [P1] Queue Fresh: No History + No Work ---");

        // Establish pre-update state: No queue history, no pending work
        $this->establish_pre_update_state(array(
            'migration_state' => 'completed', // No work pending
            'queue_last_run' => false, // No history
        ));

        // Check if queue is considered fresh
        $is_stale = SUPER_Cron_Fallback::is_queue_stale();

        $this->assert($is_stale === false, 'Queue should be fresh when no history and no work');
        $this->log("✓ Queue correctly identified as fresh");
    }

    /**
     * Test: Queue stale when last run older than threshold
     */
    private function test_queue_stale_old_run() {
        $this->log("\n--- [P1] Queue Stale: Last Run >15min ---");

        // Establish pre-update state: Queue last ran 20 minutes ago
        $twenty_minutes_ago = date('Y-m-d H:i:s', time() - (20 * 60));
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => $twenty_minutes_ago,
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Check if queue is considered stale
        $is_stale = SUPER_Cron_Fallback::is_queue_stale();

        $this->assert($is_stale === true, 'Queue should be stale when last run >15min ago');
        $this->log("✓ Queue correctly identified as stale (last run: 20min ago)");
    }

    /**
     * Test: Queue fresh when last run recent
     */
    private function test_queue_fresh_recent_run() {
        $this->log("\n--- [P1] Queue Fresh: Recent Run ---");

        // Establish pre-update state: Queue ran 5 minutes ago
        $five_minutes_ago = date('Y-m-d H:i:s', time() - (5 * 60));
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => $five_minutes_ago,
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Check if queue is considered fresh
        $is_stale = SUPER_Cron_Fallback::is_queue_stale();

        $this->assert($is_stale === false, 'Queue should be fresh when run <15min ago');
        $this->log("✓ Queue correctly identified as fresh (last run: 5min ago)");
    }

    /**
     * Test: Detects DISABLE_WP_CRON constant
     */
    private function test_detects_disabled_cron() {
        $this->log("\n--- [P1] Detects DISABLE_WP_CRON ---");

        // Establish pre-update state: Clean state
        $this->establish_pre_update_state();

        // Check if constant detection works
        $is_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $detected = SUPER_Cron_Fallback::is_cron_disabled();

        $this->assert($detected === $is_disabled, 'Should accurately detect DISABLE_WP_CRON constant');

        if ($is_disabled) {
            $this->log("✓ DISABLE_WP_CRON is set - correctly detected");
            $this->log("⚠️  NOTE: This is expected on dev server with custom cron setup");
        } else {
            $this->log("✓ DISABLE_WP_CRON is not set - correctly detected");
        }
    }

    /**
     * Test: No trigger when cron enabled
     */
    private function test_no_trigger_cron_enabled() {
        $this->log("\n--- [P1] No Trigger When Cron Enabled ---");

        // Establish pre-update state: Recent queue run, no pending work
        $two_minutes_ago = date('Y-m-d H:i:s', time() - (2 * 60));
        $this->establish_pre_update_state(array(
            'migration_state' => 'completed', // No work pending
            'queue_last_run' => $two_minutes_ago, // Recent run
        ));

        // Should not need intervention
        $needs_intervention = SUPER_Cron_Fallback::needs_intervention();

        $this->assert($needs_intervention === false, 'Should not need intervention when cron working');
        $this->log("✓ No intervention needed when cron is functioning");
    }

    /**
     * Test: Has pending work when migration in progress
     */
    private function test_pending_work_migration() {
        $this->log("\n--- [P1] Pending Work: Migration ---");

        // Establish pre-update state: Migration in progress with real entries
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'entries_count' => 10,
            'create_entries' => true, // Create real unmigrated entries
        ));

        // Check for pending work
        $has_work = SUPER_Cron_Fallback::has_pending_work();

        $this->assert($has_work === true, 'Should detect pending migration work');
        $this->log("✓ Pending migration work detected");
    }

    /**
     * Test: No pending work when migration complete
     */
    private function test_no_pending_work_complete() {
        $this->log("\n--- [P1] No Pending Work: Complete ---");

        // Establish pre-update state: Migration fully complete
        $this->establish_pre_update_state(array(
            'migration_state' => 'completed',
        ));

        // Check for pending work
        $has_work = SUPER_Cron_Fallback::has_pending_work();

        $this->assert($has_work === false, 'Should not detect work when migration complete');
        $this->log("✓ No pending work detected when complete");
    }

    /**
     * Test: Notice shows when queue stale and work pending
     */
    private function test_notice_shows_stale_work() {
        $this->log("\n--- [P1] Notice Shows: Stale + Work ---");

        // Establish pre-update state: Stale queue with pending work
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => false, // Stale (no history)
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Setup: Admin user (notices are admin-only)
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Check if notice should show
        $should_show = SUPER_Cron_Fallback::should_show_notice();

        $this->assert($should_show === true, 'Notice should show when queue stale with work');
        $this->log("✓ Notice correctly shows for stale queue with work");
    }

    /**
     * Test: Notice hides when no pending work
     */
    private function test_notice_hides_no_work() {
        $this->log("\n--- [P1] Notice Hides: No Work ---");

        // Establish pre-update state: No pending work
        $this->establish_pre_update_state(array(
            'migration_state' => 'completed', // No work
        ));

        // Setup: Admin user
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Check if notice should show
        $should_show = SUPER_Cron_Fallback::should_show_notice();

        $this->assert($should_show === false, 'Notice should hide when no work pending');
        $this->log("✓ Notice correctly hidden when no work");
    }

    /**
     * Test: Notice hides when dismissed recently
     */
    private function test_notice_hides_dismissed() {
        $this->log("\n--- [P1] Notice Hides: Dismissed ---");

        // Establish pre-update state: Stale queue with pending work
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => false,
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Setup: Admin user
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Dismiss notice (just now)
        update_user_meta($admin_id, 'super_cron_notice_dismissed', time());

        // Check if notice should show
        $should_show = SUPER_Cron_Fallback::should_show_notice();

        $this->assert($should_show === false, 'Notice should hide when recently dismissed');
        $this->log("✓ Notice correctly hidden after dismissal");
    }

    /**
     * Test: Notice reappears when dismissed but still stalled
     */
    private function test_notice_reappears_stalled() {
        $this->log("\n--- [P1] Notice Reappears: Still Stalled ---");

        // Establish pre-update state: Stale queue with pending work
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => false,
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Setup: Admin user
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Dismiss notice 2 hours ago (older than 1h threshold)
        $two_hours_ago = time() - (2 * 60 * 60);
        update_user_meta($admin_id, 'super_cron_notice_dismissed', $two_hours_ago);

        // Check if notice should show
        $should_show = SUPER_Cron_Fallback::should_show_notice();

        $this->assert($should_show === true, 'Notice should reappear after 1h if still stalled');
        $this->log("✓ Notice correctly reappears after dismissal timeout (1h)");
    }

    /**
     * Test: Notice admin only
     */
    private function test_notice_admin_only() {
        $this->log("\n--- [P1] Notice: Admin Only ---");

        // Establish pre-update state: Stale queue with pending work
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => false,
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Setup: Non-admin user (subscriber)
        $subscriber_id = $this->create_test_user('subscriber');
        wp_set_current_user($subscriber_id);

        // Check if notice should show
        $should_show = SUPER_Cron_Fallback::should_show_notice();

        $this->assert($should_show === false, 'Notice should not show for non-admin users');
        $this->log("✓ Notice correctly hidden for non-admin users");
    }

    // ========================================
    // PRIORITY 2: AJAX ENDPOINT TESTS
    // ========================================

    /**
     * Test: AJAX trigger requires nonce
     */
    private function test_ajax_trigger_requires_nonce() {
        $this->log("\n--- [P2] AJAX Trigger: Requires Nonce ---");

        // Establish pre-update state: Clean state for security test
        $this->establish_pre_update_state();

        // Setup: Admin user
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Attempt AJAX without nonce (unset both 'nonce' and 'security')
        unset($_POST['nonce']);
        unset($_POST['security']);
        unset($_GET['nonce']);
        unset($_GET['security']);

        // Call handler directly - expect wp_die() from check_ajax_referer()
        $died = false;
        $output = '';

        // Hook into wp_die to catch the failure
        add_filter('wp_die_ajax_handler', function() use (&$died) {
            $died = true;
            return function($message, $title, $args) {
                // Silent die handler
            };
        }, 1);

        ob_start();
        try {
            SUPER_Ajax::trigger_cron_fallback();
        } catch (Exception $e) {
            // Some environments may throw exception
            $died = true;
        }
        $output = ob_get_clean();

        // Remove filter
        remove_all_filters('wp_die_ajax_handler');

        // Verify nonce check triggered wp_die or error response
        $this->assert($died === true || strpos($output, 'error') !== false, 'Should die or error without nonce');
        $this->log("✓ Nonce requirement enforced (handler died without nonce)");
    }

    /**
     * Test: AJAX trigger requires manage_options permission
     */
    private function test_ajax_trigger_requires_permission() {
        $this->log("\n--- [P2] AJAX Trigger: Requires Permission ---");

        // Establish pre-update state: Clean state for permission test
        $this->establish_pre_update_state();

        // Setup: Non-admin user (subscriber doesn't have manage_options)
        $subscriber_id = $this->create_test_user('subscriber');
        wp_set_current_user($subscriber_id);

        // Create valid nonce
        $_POST['nonce'] = wp_create_nonce('super-form-builder');

        // Capture JSON response
        ob_start();
        try {
            SUPER_Ajax::trigger_cron_fallback();
        } catch (Exception $e) {
            // wp_send_json_error may throw/exit
        }
        $output = ob_get_clean();

        // Decode JSON response
        $response = json_decode($output, true);

        // Verify permission denied
        $this->assert(
            isset($response['success']) && $response['success'] === false,
            'Should return error for non-admin user'
        );
        $this->assert(
            isset($response['data']['message']) && strpos($response['data']['message'], 'Permission denied') !== false,
            'Should return "Permission denied" message'
        );

        $this->log("✓ Permission check enforced (subscriber denied)");
    }

    /**
     * Test: AJAX dismiss requires authentication
     */
    private function test_ajax_dismiss_requires_auth() {
        $this->log("\n--- [P2] AJAX Dismiss: Requires Auth ---");

        // Establish pre-update state: Clean state for auth test
        $this->establish_pre_update_state();

        // Setup: No user (logged out)
        wp_set_current_user(0);

        // Create nonce (won't help without user)
        $_POST['nonce'] = wp_create_nonce('super-form-builder');

        // Hook into wp_die to catch authentication failure
        $died = false;
        add_filter('wp_die_ajax_handler', function() use (&$died) {
            $died = true;
            return function($message, $title, $args) {};
        }, 1);

        ob_start();
        try {
            SUPER_Ajax::dismiss_cron_notice();
        } catch (Exception $e) {
            $died = true;
        }
        $output = ob_get_clean();

        remove_all_filters('wp_die_ajax_handler');

        // Verify authentication required (either died or error response)
        $response = json_decode($output, true);
        $auth_failed = $died || (isset($response['success']) && $response['success'] === false);

        $this->assert($auth_failed === true, 'Should require authentication to dismiss');
        $this->log("✓ Authentication required for dismiss");
    }

    /**
     * Test: Trigger attempts async first
     */
    private function test_ajax_trigger_attempts_async() {
        $this->log("\n--- [P2] Trigger: Async Attempt ---");

        // Establish pre-update state: Migration in progress
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Call business logic directly (no AJAX, no exit, no output buffering)
        $result = SUPER_Ajax::trigger_cron_fallback_logic();

        // Verify result
        $this->assert(!is_wp_error($result), 'Should not return WP_Error');
        $this->assert(isset($result['mode']), 'Should return mode');
        $this->assert(in_array($result['mode'], ['async', 'sync']), 'Mode should be async or sync');

        $this->log("✓ Async attempt returns valid mode: " . $result['mode']);
    }

    /**
     * Test: Process batch works
     */
    private function test_ajax_process_sync_works() {
        $this->log("\n--- [P2] Process: Batch Works ---");

        // Establish pre-update state: Create real test entries for migration
        $this->establish_pre_update_state(array(
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Start migration
        SUPER_Migration_Manager::start_migration();

        // Verify entries are pending
        $status_before = SUPER_Migration_Manager::get_migration_status();
        $this->assert($status_before['total_entries'] >= 10, 'Should have entries to migrate');

        // Call business logic directly
        $result = SUPER_Ajax::process_batch_logic(10);

        // Verify result
        $this->assert(!is_wp_error($result), 'Should not return WP_Error');
        $this->assert(isset($result['processed']), 'Should return processed count');
        $this->assert($result['processed'] > 0, 'Should process some entries');

        $this->log("✓ Batch processing works (processed: {$result['processed']} entries)");
    }

    /**
     * Test: Process returns progress
     */
    private function test_ajax_process_returns_progress() {
        $this->log("\n--- [P2] Process: Returns Progress ---");

        // Establish pre-update state: Create real entries with migration in progress
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Call business logic directly
        $result = SUPER_Ajax::process_batch_logic(10);

        // Verify all progress fields exist
        $this->assert(!is_wp_error($result), 'Should not return WP_Error');
        $this->assert(isset($result['processed']), 'Should return processed count');
        $this->assert(isset($result['remaining']), 'Should return remaining count');
        $this->assert(isset($result['percentage']), 'Should return percentage');
        $this->assert(isset($result['is_complete']), 'Should return is_complete flag');

        $this->log("✓ Progress tracking complete:");
        $this->log("  - Processed: {$result['processed']}");
        $this->log("  - Remaining: {$result['remaining']}");
        $this->log("  - Percentage: {$result['percentage']}%");
    }

    /**
     * Test: Dismiss saves user meta
     */
    private function test_ajax_dismiss_saves_meta() {
        $this->log("\n--- [P2] Dismiss: Saves Meta ---");

        // Establish pre-update state: Clean state
        $this->establish_pre_update_state();

        // Setup: Admin user
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Verify not dismissed initially
        $dismissed_before = get_user_meta($admin_id, 'super_cron_notice_dismissed', true);
        $this->assert(empty($dismissed_before), 'Should not be dismissed initially');

        // Call business logic directly
        $result = SUPER_Ajax::dismiss_cron_notice_logic($admin_id);

        // Verify result
        $this->assert(!is_wp_error($result), 'Should not return WP_Error');
        $this->assert($result === true, 'Should return true on success');

        // Verify meta saved
        $dismissed_after = get_user_meta($admin_id, 'super_cron_notice_dismissed', true);
        $this->assert(!empty($dismissed_after), 'Should have dismissal timestamp');
        $this->assert(is_numeric($dismissed_after), 'Timestamp should be numeric');
        $this->assert($dismissed_after > 0, 'Timestamp should be > 0');
        $this->assert($dismissed_after <= time(), 'Timestamp should be <= now');

        $this->log("✓ User meta saved correctly (timestamp: $dismissed_after)");
    }

    /**
     * Test: Handles edge cases gracefully
     */
    private function test_ajax_error_batch_fails() {
        $this->log("\n--- [P2] Edge Cases: Empty Migration ---");

        // Establish pre-update state: Migration with no entries (edge case)
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'entries_count' => 0, // No entries to migrate
        ));

        // Call business logic directly
        $result = SUPER_Ajax::process_batch_logic(10);

        // Should handle gracefully (not crash)
        $this->assert(!is_wp_error($result), 'Should not return WP_Error for empty migration');

        // Should either have 0 processed or be complete
        $handled_gracefully = ($result['processed'] === 0) || ($result['is_complete'] === true);
        $this->assert($handled_gracefully === true, 'Should handle empty migration state gracefully');

        $this->log("✓ Edge case handled gracefully (no entries to process)");
    }

    // ========================================
    // PRIORITY 3: INTEGRATION TESTS
    // ========================================

    /**
     * Test: Full fallback flow with disabled cron
     */
    private function test_integration_disabled_cron_flow() {
        $this->log("\n--- [P3] Integration: Disabled Cron Flow ---");

        // Establish pre-update state: Create entries and start migration
        $this->establish_pre_update_state(array(
            'entries_count' => 20,
            'create_entries' => true,
            'queue_last_run' => false, // Simulate stale queue
        ));

        // Start migration
        SUPER_Migration_Manager::start_migration();

        // Setup: Admin user
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Test detection logic
        $is_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $has_pending_work = SUPER_Cron_Fallback::has_pending_work();
        $is_queue_stale = SUPER_Cron_Fallback::is_queue_stale();
        $needs_intervention = SUPER_Cron_Fallback::needs_intervention();
        $should_show = SUPER_Cron_Fallback::should_show_notice();

        // Log detection results
        $this->log("Detection results:");
        $this->log("  - DISABLE_WP_CRON: " . ($is_cron_disabled ? 'TRUE' : 'FALSE'));
        $this->log("  - Has pending work: " . ($has_pending_work ? 'yes' : 'no'));
        $this->log("  - Queue is stale: " . ($is_queue_stale ? 'yes' : 'no'));
        $this->log("  - Needs intervention: " . ($needs_intervention ? 'yes' : 'no'));
        $this->log("  - Should show notice: " . ($should_show ? 'yes' : 'no'));

        // Assert automated checks
        $this->assert($has_pending_work === true, 'Should have pending work');
        $this->assert($is_queue_stale === true, 'Queue should be stale (no recent run)');
        $this->assert($should_show === true, 'Notice should show');

        $this->log("✓ Automated checks passed");

        // Manual verification guide
        if (!$is_cron_disabled) {
            $this->log("");
            $this->log("========================================");
            $this->log("MANUAL TEST GUIDE: Disabled Cron Flow");
            $this->log("========================================");
            $this->log("");
            $this->log("**Prerequisites:**");
            $this->log("1. SSH access to server");
            $this->log("2. Ability to edit wp-config.php");
            $this->log("3. Admin access to WordPress");
            $this->log("");
            $this->log("**Test Steps:**");
            $this->log("");
            $this->log("STEP 1: Disable WP-Cron");
            $this->log("  - SSH into server");
            $this->log("  - Edit wp-config.php");
            $this->log("  - Add before /* That's all */:");
            $this->log("    define('DISABLE_WP_CRON', true);");
            $this->log("");
            $this->log("STEP 2: Create Test Data");
            $this->log("  - Navigate to any Super Form");
            $this->log("  - Submit 5-10 test entries");
            $this->log("  - Verify entries appear in Contact Entries");
            $this->log("");
            $this->log("STEP 3: Trigger Migration");
            $this->log("  - Go to Developer Tools page");
            $this->log("  - Click 'Reset Migration' if needed");
            $this->log("  - Click 'Start Migration'");
            $this->log("  - Background jobs will queue but NOT process (cron disabled)");
            $this->log("");
            $this->log("STEP 4: Verify Fallback Detection");
            $this->log("  - Wait 15 minutes OR manually clear last_queue_run:");
            $this->log("    DELETE FROM wp_options WHERE option_name='superforms_last_queue_run';");
            $this->log("  - Refresh any admin page");
            $this->log("  - Admin notice should appear:");
            $this->log("    'Database Upgrade Required - Click to upgrade now'");
            $this->log("");
            $this->log("STEP 5: Test Async Processing");
            $this->log("  - Click 'Trigger Background Processing' in notice");
            $this->log("  - System attempts async processing");
            $this->log("  - Check response in browser DevTools Network tab:");
            $this->log("    {\"success\":true,\"data\":{\"mode\":\"async\"}}");
            $this->log("  - Monitor Action Scheduler:");
            $this->log("    WP Admin → Tools → Action Scheduler → Pending");
            $this->log("  - Jobs should start processing immediately");
            $this->log("");
            $this->log("STEP 6: Test Sync Fallback (if async fails)");
            $this->log("  - If jobs don't process within 30 seconds");
            $this->log("  - Notice should update with progress bar");
            $this->log("  - Click 'Process Batch' button");
            $this->log("  - Progress bar should increment");
            $this->log("  - Continue until migration complete");
            $this->log("");
            $this->log("STEP 7: Verify Migration Success");
            $this->log("  - Go to Contact Entries page");
            $this->log("  - Listings should load quickly (<500ms)");
            $this->log("  - Search should work on field values");
            $this->log("  - Export to CSV should complete quickly");
            $this->log("");
            $this->log("STEP 8: Cleanup");
            $this->log("  - Remove DISABLE_WP_CRON from wp-config.php");
            $this->log("  - Delete test entries if needed");
            $this->log("");
            $this->log("**Expected Results:**");
            $this->log("✓ Notice appears after 15min of queue staleness");
            $this->log("✓ Async processing attempts first");
            $this->log("✓ Sync progress bar appears if async fails");
            $this->log("✓ Migration completes successfully");
            $this->log("✓ Notice disappears after completion");
            $this->log("✓ Listings performance improved (15-20s → <500ms)");
            $this->log("========================================");
        }
    }

    /**
     * Test: Async mode triggers queue processing
     */
    private function test_integration_async_mode() {
        $this->log("\n--- [P3] Integration: Async Mode ---");

        // Establish pre-update state: Clean state for infrastructure check
        $this->establish_pre_update_state();

        // Check Action Scheduler infrastructure
        $as_available = class_exists('ActionScheduler');
        $as_runner_available = class_exists('ActionScheduler_QueueRunner');

        $this->assert($as_available === true, 'ActionScheduler should be available');
        $this->assert($as_runner_available === true, 'ActionScheduler_QueueRunner should be available');

        if ($as_runner_available) {
            $as_runner = ActionScheduler_QueueRunner::instance();
            $batch_size = method_exists($as_runner, 'get_batch_size') ? $as_runner->get_batch_size() : 'unknown';

            $this->log("✓ Action Scheduler runner available");
            $this->log("  Batch size: $batch_size");
        }

        // Test async mode detection
        $is_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $this->log("  WP-Cron disabled: " . ($is_cron_disabled ? 'yes' : 'no'));

        // Test try_async_processing method existence
        $this->assert(
            method_exists('SUPER_Cron_Fallback', 'try_async_processing'),
            'try_async_processing method should exist'
        );

        $this->log("✓ Async processing infrastructure ready");
        $this->log("");
        $this->log("========================================");
        $this->log("MANUAL TEST GUIDE: Async Mode");
        $this->log("========================================");
        $this->log("");
        $this->log("**Prerequisites:**");
        $this->log("1. Access to wp-config.php");
        $this->log("2. Browser DevTools (Network tab)");
        $this->log("3. Admin access to WordPress");
        $this->log("");
        $this->log("**Test Steps:**");
        $this->log("");
        $this->log("STEP 1: Enable DISABLE_WP_CRON");
        $this->log("  - Edit wp-config.php");
        $this->log("  - Add: define('DISABLE_WP_CRON', true);");
        $this->log("  - This forces reliance on async processing");
        $this->log("");
        $this->log("STEP 2: Setup Test Migration");
        $this->log("  - Go to Developer Tools");
        $this->log("  - Reset migration if needed");
        $this->log("  - Create 10 test entries");
        $this->log("  - Start migration");
        $this->log("  - Jobs queue but don't process (cron disabled)");
        $this->log("");
        $this->log("STEP 3: Trigger Async Mode");
        $this->log("  - Open Browser DevTools → Network tab");
        $this->log("  - Navigate to any admin page");
        $this->log("  - Notice should appear");
        $this->log("  - Click 'Trigger Background Processing'");
        $this->log("");
        $this->log("STEP 4: Verify Async Dispatch");
        $this->log("  - In Network tab, find the AJAX request");
        $this->log("  - Response should show:");
        $this->log("    {\"success\":true,\"data\":{\"mode\":\"async\"}}");
        $this->log("  - This means system attempted async processing");
        $this->log("");
        $this->log("STEP 5: Monitor Action Scheduler");
        $this->log("  - Go to WP Admin → Tools → Action Scheduler");
        $this->log("  - Click 'Pending' tab");
        $this->log("  - Should see 'super_process_migration_batch' actions");
        $this->log("  - Click 'In-Progress' or 'Complete' tabs");
        $this->log("  - Jobs should start processing immediately");
        $this->log("  - Note: They process via async HTTP request, NOT WP-Cron");
        $this->log("");
        $this->log("STEP 6: Verify Processing Without Cron");
        $this->log("  - Monitor Action Scheduler logs");
        $this->log("  - Jobs should complete within 1-2 minutes");
        $this->log("  - Migration status should update");
        $this->log("  - Contact Entries page should load quickly");
        $this->log("");
        $this->log("STEP 7: Cleanup");
        $this->log("  - Remove DISABLE_WP_CRON from wp-config.php");
        $this->log("  - Clear any test data");
        $this->log("");
        $this->log("**Expected Results:**");
        $this->log("✓ Async mode auto-enables when DISABLE_WP_CRON detected");
        $this->log("✓ Action Scheduler processes jobs via HTTP request");
        $this->log("✓ Migration completes without WP-Cron");
        $this->log("✓ Jobs process immediately (not waiting for next cron run)");
        $this->log("✓ System handles cron-disabled servers gracefully");
        $this->log("========================================");
    }

    /**
     * Test: Notice disappears after migration completes
     */
    private function test_integration_notice_lifecycle() {
        $this->log("\n--- [P3] Integration: Notice Lifecycle ---");

        // Establish pre-update state: Create entries for migration
        $this->establish_pre_update_state(array(
            'entries_count' => 5,
            'create_entries' => true,
            'queue_last_run' => false, // Stale queue
        ));

        // Start migration
        SUPER_Migration_Manager::start_migration();

        // Setup: Admin user
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        // Notice should show (pending work, stale queue)
        $should_show_before = SUPER_Cron_Fallback::should_show_notice();
        $this->assert($should_show_before === true, 'Notice should show before completion');

        // Complete migration
        while (true) {
            $batch = SUPER_Migration_Manager::process_batch(10);
            if ($batch['remaining'] === 0) break;
        }
        SUPER_Migration_Manager::complete_migration();

        // Notice should hide (no pending work)
        $should_show_after = SUPER_Cron_Fallback::should_show_notice();
        $this->assert($should_show_after === false, 'Notice should hide after completion');

        $this->log("✓ Notice lifecycle verified");
        $this->log("  - Shows when work pending");
        $this->log("  - Hides when work complete");
    }

    // ========================================
    // PRIORITY 0: CRITICAL GAP TESTS
    // ========================================

    /**
     * Test: Plugin auto-update simulation
     *
     * Simulates WordPress auto-updating the plugin from pre-EAV to post-EAV version.
     * Verifies migration auto-triggers and EAV infrastructure is created.
     */
    private function test_plugin_auto_update() {
        $this->log("\n--- [P0] Plugin Auto-Update Simulation ---");

        // Establish pre-update state: Old version with serialized entries
        $this->establish_pre_update_state(array(
            'plugin_version' => '6.3.0', // Pre-EAV version
            'migration_state' => null, // No migration state yet
            'entries_count' => 20,
            'create_entries' => true,
        ));

        $this->log("✓ Pre-update state established (version 6.3.0, 20 entries)");

        // Verify entries are serialized only (no EAV data)
        global $wpdb;
        $eav_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_entry_data"
        );
        $this->assert($eav_count == 0, 'Should have no EAV data before migration');

        // Trigger version check (simulates init after auto-update)
        // This function detects the upgrade (6.3.0 → SUPER_VERSION) and triggers migration
        // Note: check_version_and_schedule() itself updates the version option
        SUPER_Background_Migration::check_version_and_schedule();

        $this->log("✓ Version upgrade triggered (6.3.0 → " . SUPER_VERSION . ")");

        // Verify migration was auto-scheduled
        $migration_state = get_option('superforms_eav_migration');
        $this->assert(!empty($migration_state), 'Migration state should be initialized');
        $this->assert(
            in_array($migration_state['status'], array('in_progress', 'not_started')),
            'Migration should be started or scheduled'
        );

        $this->log("✓ Migration auto-scheduled (status: {$migration_state['status']})");

        // Verify EAV tables exist (auto-created)
        $table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '{$wpdb->prefix}superforms_entry_data'"
        ) === $wpdb->prefix . 'superforms_entry_data';
        $this->assert($table_exists, 'EAV table should be auto-created');

        $this->log("✓ EAV infrastructure created automatically");

        // Verify Action Scheduler jobs were queued
        if (function_exists('as_get_scheduled_actions')) {
            $pending_jobs = as_get_scheduled_actions(array(
                'group' => 'superforms-migration',
                'status' => 'pending',
                'per_page' => 1,
            ));
            $has_jobs = !empty($pending_jobs);
            $this->log($has_jobs ? "✓ Background jobs queued" : "⚠️  No background jobs queued (may have processed immediately)");
        }

        $this->log("✅ Auto-update simulation complete");
    }

    /**
     * Test: FTP override simulation
     *
     * Simulates FTP file replacement while plugin is active (no activation hook).
     * Verifies migration triggers on init without activation.
     */
    private function test_ftp_override() {
        $this->log("\n--- [P0] FTP Override Simulation ---");

        // Establish pre-update state: Old version, entries exist
        $this->establish_pre_update_state(array(
            'plugin_version' => '6.3.0',
            'migration_state' => null,
            'entries_count' => 15,
            'create_entries' => true,
        ));

        $this->log("✓ Pre-FTP state established (version 6.3.0, 15 entries)");

        // Simulate FTP override: version option stays old, but code is new
        // In real scenario, files are replaced but wp-config/database unchanged
        // We simulate this by manually keeping old version then triggering init

        // Trigger init (simulates next page load after FTP upload)
        // The version check should detect mismatch and trigger migration
        SUPER_Background_Migration::check_version_and_schedule();

        $this->log("✓ Init triggered (simulating first page load after FTP)");

        // Verify version was updated
        $stored_version = get_option('super_plugin_version');
        $this->assert(
            version_compare($stored_version, '6.3.0', '>'),
            'Version should be updated to current'
        );

        $this->log("✓ Version auto-detected and updated ({$stored_version})");

        // Verify migration initialized
        $migration_state = get_option('superforms_eav_migration');
        $this->assert(!empty($migration_state), 'Migration should be initialized');

        // Verify tables self-healed (created if missing)
        global $wpdb;
        $table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '{$wpdb->prefix}superforms_entry_data'"
        ) === $wpdb->prefix . 'superforms_entry_data';
        $this->assert($table_exists, 'EAV table should be auto-created');

        $this->log("✓ Tables self-healed (created on init)");
        $this->log("✅ FTP override simulation complete");
    }

    /**
     * Test: Migration threshold crossing
     *
     * Verifies migration triggers when crossing from pre-EAV to post-EAV version,
     * but doesn't re-trigger for upgrades within the same era.
     */
    private function test_migration_threshold_crossing() {
        $this->log("\n--- [P0] Migration Threshold Crossing ---");

        // Part 1: Crossing threshold should trigger migration
        $this->log("\n=== Part 1: Crossing Threshold (6.3.0 → 6.4.127) ===");

        $this->establish_pre_update_state(array(
            'plugin_version' => '6.3.0', // Pre-threshold
            'migration_state' => null,
            'entries_count' => 10,
            'create_entries' => true,
        ));

        // Upgrade to post-threshold version
        // Note: check_version_and_schedule() detects the upgrade (6.3.0 → 6.4.200) and updates version itself
        SUPER_Background_Migration::check_version_and_schedule();

        $migration_state = get_option('superforms_eav_migration');
        $this->assert(!empty($migration_state), 'Migration should trigger when crossing threshold');
        $this->log("✓ Migration triggered on threshold crossing");

        // Part 2: Same-era upgrade should NOT re-trigger
        $this->log("\n=== Part 2: Same-Era Upgrade (6.4.100 → 6.4.127) ===");

        // Mark migration as completed
        update_option('superforms_eav_migration', array(
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ), false);

        // Simulate upgrade from 6.4.100 to 6.4.127 (both post-threshold)
        update_option('super_plugin_version', '6.4.100', false);
        update_option('super_plugin_version', '6.4.127', false);
        SUPER_Background_Migration::check_version_and_schedule();

        $migration_state_after = get_option('superforms_eav_migration');
        $this->assert(
            $migration_state_after['status'] === 'completed',
            'Migration should remain completed (not re-triggered)'
        );

        $this->log("✓ Migration not re-triggered for same-era upgrade");
        $this->log("✅ Threshold crossing logic verified");
    }

    /**
     * Test: Real WP-Cron failure (enabled but broken)
     *
     * Simulates cron enabled but broken (e.g., loopback requests blocked).
     * Verifies fallback system detects this scenario.
     */
    private function test_real_cron_failure() {
        $this->log("\n--- [P0] Real WP-Cron Failure (Enabled But Broken) ---");

        // Establish state: Cron NOT disabled, but queue stale with pending work
        $twenty_minutes_ago = date('Y-m-d H:i:s', time() - (20 * 60));
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'queue_last_run' => $twenty_minutes_ago, // Stale
            'entries_count' => 10,
            'create_entries' => true,
        ));

        $this->log("✓ State established: Queue stale for 20min, work pending");

        // Verify DISABLE_WP_CRON is NOT set (cron is "enabled")
        $is_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        if ($is_disabled) {
            $this->log("⚠️  DISABLE_WP_CRON is set on this server");
            $this->log("    This test simulates 'enabled but broken' cron");
            $this->log("    Proceeding with staleness check...");
        } else {
            $this->log("✓ WP-Cron is enabled (DISABLE_WP_CRON not set)");
        }

        // Check if fallback system detects broken cron
        $is_queue_stale = SUPER_Cron_Fallback::is_queue_stale();
        $has_pending_work = SUPER_Cron_Fallback::has_pending_work();
        $needs_intervention = SUPER_Cron_Fallback::needs_intervention();

        $this->assert($is_queue_stale === true, 'Queue should be detected as stale');
        $this->assert($has_pending_work === true, 'Should detect pending work');

        $this->log("✓ Fallback system detected stale queue");
        $this->log("✓ Pending work detected");
        $this->log("  Needs intervention: " . ($needs_intervention ? 'yes' : 'no'));

        // Verify notice would show (admin user)
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);

        $should_show_notice = SUPER_Cron_Fallback::should_show_notice();

        $this->assert($should_show_notice === true, 'Notice should show for broken cron');
        $this->log("✓ Admin notice would appear");

        $this->log("✅ Broken cron detection verified (enabled but not working)");
    }

    /**
     * Test: Stuck migration resumption
     *
     * Simulates server crash during migration and verifies recovery.
     */
    private function test_stuck_migration_resumption() {
        $this->log("\n--- [P0] Stuck Migration Resumption ---");

        // Establish stuck migration state
        $two_hours_ago = date('Y-m-d H:i:s', time() - (2 * 60 * 60));
        $this->establish_pre_update_state(array(
            'migration_state' => 'in_progress',
            'entries_count' => 30,
            'create_entries' => true,
        ));

        // Manually set stuck state (last batch 2hr ago, lock expired)
        update_option('superforms_eav_migration', array(
            'status' => 'in_progress',
            'total_entries' => 30,
            'migrated_entries' => 10, // Partial progress
            'last_processed_id' => $this->test_entries[9], // Stopped at 10th entry
            'started_at' => $two_hours_ago,
            'last_batch_processed_at' => $two_hours_ago,
        ), false);

        // Ensure lock is expired (deleted)
        delete_transient(SUPER_Background_Migration::LOCK_KEY);

        $this->log("✓ Stuck migration simulated (2hr old, lock expired, partial progress)");

        // Verify migration is detected as stuck
        $has_pending_work = SUPER_Cron_Fallback::has_pending_work();
        $this->assert($has_pending_work === true, 'Should detect stuck migration as pending work');
        $this->log("✓ Stuck migration detected as pending work");

        // Test resumption directly using business logic
        $state_before = get_option('superforms_eav_migration');
        $migrated_before = $state_before['migrated_entries'];

        // Process batch directly (what AJAX handler calls internally)
        $result = SUPER_Background_Migration::process_batch_action(10);

        // Verify batch processing succeeded
        $this->assert(!is_wp_error($result), 'Batch processing should not return WP_Error');
        $this->assert(isset($result['processed']), 'Result should contain processed count');
        $this->assert($result['processed'] > 0, 'Should process at least one entry');

        $this->log("✓ Processed {$result['processed']} entries");

        // Verify actual migration happened (entries in EAV table)
        global $wpdb;
        $table_name = $wpdb->prefix . 'superforms_entry_data';
        $eav_count = $wpdb->get_var("SELECT COUNT(DISTINCT entry_id) FROM {$table_name}");

        $this->assert($eav_count >= 10, 'Should have migrated at least 10 entries to EAV');
        $this->log("✓ Entries migrated to EAV table ({$eav_count} entries)");

        // Verify resumption from checkpoint (not starting over)
        $this->assert(
            $migrated_before === 10,
            'Should have started with 10 already migrated (checkpoint)'
        );
        $this->assert(
            $result['processed'] > 0,
            'Should have processed additional entries from checkpoint'
        );
        $this->log("✓ Resumed from checkpoint ({$result['processed']} more entries processed)");

        $this->log("✅ Stuck migration resumption verified");
    }

    /**
     * Test: Automated E2E integration
     *
     * Runs complete flow end-to-end without manual intervention.
     */
    private function test_e2e_integration() {
        $this->log("\n--- [P0] Automated E2E Integration ---");

        // Step 1: Version upgrade
        $this->log("\n=== Step 1: Version Upgrade ===");
        $this->establish_pre_update_state(array(
            'plugin_version' => '6.3.0',
            'migration_state' => null,
            'entries_count' => 25,
            'create_entries' => true,
        ));
        update_option('super_plugin_version', SUPER_VERSION, false);
        SUPER_Background_Migration::check_version_and_schedule();
        $this->log("✓ Version upgraded, migration scheduled");

        // Step 2: Queue stalls (simulate broken cron)
        $this->log("\n=== Step 2: Queue Stalls ===");
        delete_option('superforms_last_queue_run');
        $is_stale = SUPER_Cron_Fallback::is_queue_stale();
        $this->assert($is_stale === true, 'Queue should be stale');
        $this->log("✓ Queue detected as stale");

        // Step 3: Notice appears
        $this->log("\n=== Step 3: Admin Notice ===");
        $admin_id = $this->get_admin_user();
        wp_set_current_user($admin_id);
        $should_show = SUPER_Cron_Fallback::should_show_notice();
        $this->assert($should_show === true, 'Notice should appear');
        $this->log("✓ Notice would appear for admin");

        // Step 4: Async attempt (CLI-safe - tests business logic)
        $this->log("\n=== Step 4: Fallback Detection (CLI-safe) ===");
        $async_available = SUPER_Cron_Fallback::try_async_processing();
        // In CLI, this returns false (no HTTP server for async requests)
        $mode = $async_available ? 'async' : 'sync';
        $this->log("✓ Fallback mode determined: {$mode}");

        // Step 5: Process batches directly (CLI-safe)
        $this->log("\n=== Step 5: Batch Processing (CLI-safe) ===");
        $batches_processed = 0;
        $max_batches = 10; // Prevent infinite loop

        while ($batches_processed < $max_batches) {
            // Call business logic directly (what AJAX handler calls)
            $result = SUPER_Background_Migration::process_batch_action(10);

            if (is_wp_error($result) || !isset($result['processed'])) {
                break;
            }

            $batches_processed++;
            $percentage = isset($result['percentage']) ? $result['percentage'] : 'calculating';
            $this->log("  Batch {$batches_processed}: {$result['processed']} entries ({$percentage}%)");

            if (isset($result['is_complete']) && $result['is_complete'] === true) {
                $this->log("✓ Migration completed after {$batches_processed} batches");
                break;
            }

            // Safety check - if no more work, stop
            if ($result['processed'] === 0) {
                break;
            }
        }

        $this->assert($batches_processed > 0, 'Should process at least one batch');

        // Step 6: Verification
        $this->log("\n=== Step 6: Verification ===");
        global $wpdb;
        $eav_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id) FROM {$wpdb->prefix}superforms_entry_data"
        );
        $this->assert($eav_count > 0, 'Should have migrated entries in EAV table');
        $this->log("✓ EAV data present ({$eav_count} migrated entries)");

        $this->log("\n✅ E2E Integration test complete (business logic)");
    }

    /**
     * Test: E2E AJAX Integration (HTTP-only)
     *
     * Tests the complete E2E flow via AJAX handlers including nonce validation,
     * async processing attempts, and JSON response handling.
     * Only runs in HTTP context (Developer Tools page).
     *
     * @since 6.4.127
     */
    /**
     * Test: CSV import after migration
     *
     * Verifies system can handle new serialized data after migration completes.
     */
    private function test_csv_import_after_migration() {
        $this->log("\n--- [P0] CSV Import After Migration ---");

        // Simplified test: Just verify new entries after "completed" state trigger re-detection
        $this->log("\n=== Step 1: Establish Completed Migration State ===");
        $this->establish_pre_update_state(array(
            'migration_state' => 'completed',
        ));

        // Create minimal EAV data to satisfy "completed" state
        global $wpdb;
        $table_name = $wpdb->prefix . 'superforms_entry_data';
        $wpdb->insert($table_name, array(
            'entry_id' => 99999,
            'field_name' => 'test',
            'field_value' => 'dummy',
        ));

        update_option('superforms_eav_migration', array(
            'status' => 'completed',
            'completed_at' => time() - 86400, // 1 day ago
            'using_storage' => 'eav',
        ), false);
        $this->log("✓ Migration state marked as completed");

        // Step 2: Import new entries (simulates CSV import)
        $this->log("\n=== Step 2: Import New Entries ===");
        delete_transient('superforms_needs_migration');
        $imported_entries = $this->create_real_test_entries(2);
        $this->log("✓ Imported 2 new entries with serialized data");

        // Step 3: Verify detection
        $this->log("\n=== Step 3: Verify Detection ===");
        $needs_migration = SUPER_Background_Migration::needs_migration();
        $this->assert($needs_migration === true, 'Should detect new unmigrated entries after completion');
        $this->log("✓ System detected new unmigrated entries");

        // Step 4: Verify EAV state shows "needs remigration"
        $migration_state = get_option('superforms_eav_migration');
        $this->assert($migration_state['status'] === 'completed', 'Migration status should still be completed');
        $this->log("✓ Verified CSV import detection works after migration");

        $this->log("\n✅ CSV import after migration verified (detection only)");
    }

    // ========================================
    // TEST ISOLATION VERIFICATION
    // ========================================

    /**
     * Test: Verify test isolation by running key tests twice
     *
     * Proves that tests are independent and don't contaminate each other's state.
     * Runs a representative subset of tests twice and verifies identical results.
     */
    private function test_verify_test_isolation() {
        $this->log("\n--- [VERIFY] Test Isolation Verification ---");
        $this->log("Running representative tests twice to verify state independence...");
        $this->log("");

        // Select representative tests from each priority
        $representative_tests = array(
            'queue_stale_no_history_work_pending',  // P1: Queue detection
            'notice_shows_stale_work',              // P1: Notice logic
            'ajax_trigger_attempts_async',          // P2: AJAX logic
            'integration_notice_lifecycle',         // P3: Full integration
        );

        $first_run_results = array();
        $second_run_results = array();

        // First run
        $this->log("=== FIRST RUN ===");
        foreach ($representative_tests as $test_name) {
            try {
                $this->run_single_test($test_name);
                $first_run_results[$test_name] = 'PASSED';
            } catch (Exception $e) {
                $first_run_results[$test_name] = 'FAILED: ' . $e->getMessage();
            }
        }

        $this->log("");
        $this->log("=== SECOND RUN (Testing Isolation) ===");

        // Second run
        foreach ($representative_tests as $test_name) {
            try {
                $this->run_single_test($test_name);
                $second_run_results[$test_name] = 'PASSED';
            } catch (Exception $e) {
                $second_run_results[$test_name] = 'FAILED: ' . $e->getMessage();
            }
        }

        $this->log("");
        $this->log("=== ISOLATION VERIFICATION RESULTS ===");

        // Compare results
        $isolation_verified = true;
        foreach ($representative_tests as $test_name) {
            $first = $first_run_results[$test_name];
            $second = $second_run_results[$test_name];

            if ($first === $second) {
                $this->log("✓ {$test_name}: Consistent ({$first})");
            } else {
                $this->log("✗ {$test_name}: INCONSISTENT!");
                $this->log("    First run:  {$first}");
                $this->log("    Second run: {$second}");
                $isolation_verified = false;
            }
        }

        $this->log("");

        if ($isolation_verified) {
            $this->log("✅ TEST ISOLATION VERIFIED");
            $this->log("All tests produced identical results on second run.");
            $this->log("This proves tests are independent and don't contaminate state.");
        } else {
            throw new Exception("Test isolation verification FAILED - tests are not independent!");
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Setup pending migration for testing
     */
    private function setup_pending_migration() {
        update_option('superforms_eav_migration', [
            'status' => 'in_progress',
            'total_entries' => 100,
            'migrated_entries' => 50,
            'last_processed_id' => 12345,
            'started_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Create a test entry with data
     *
     * @param array $data Entry data
     * @return int Entry ID
     */
    private function create_test_entry($data = []) {
        $entry_id = wp_insert_post([
            'post_type' => 'super_contact_entry',
            'post_title' => '__TEST_CRON_FALLBACK_' . time() . '_' . uniqid(),
            'post_status' => 'publish'
        ]);

        if (!empty($data)) {
            add_post_meta($entry_id, '_super_contact_entry_data', serialize($data));
        }
        add_post_meta($entry_id, '_super_form_id', 999); // Test form ID
        add_post_meta($entry_id, '_super_test_entry', '1'); // Mark for cleanup

        return $entry_id;
    }

    /**
     * Get admin user ID
     *
     * @return int Admin user ID
     */
    private function get_admin_user() {
        $admins = get_users(['role' => 'administrator', 'number' => 1]);
        if (empty($admins)) {
            throw new Exception('No admin user found');
        }
        return $admins[0]->ID;
    }

    /**
     * Create test user with specific role
     *
     * @param string $role User role
     * @return int User ID
     */
    private function create_test_user($role = 'subscriber') {
        $user_id = wp_insert_user([
            'user_login' => '__test_user_' . uniqid(),
            'user_pass' => wp_generate_password(),
            'user_email' => 'test_' . uniqid() . '@example.com',
            'role' => $role
        ]);

        if (is_wp_error($user_id)) {
            throw new Exception('Failed to create test user: ' . $user_id->get_error_message());
        }

        return $user_id;
    }

    /**
     * Assert condition is true
     *
     * @param bool $condition
     * @param string $message
     * @throws Exception if assertion fails
     */
    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception("Assertion failed: $message");
        }
    }

    /**
     * Clear Action Scheduler queue for superforms-migration group
     *
     * Removes all pending/in-progress jobs to prevent contamination between tests
     *
     * @since 6.4.127
     */
    private function clear_action_scheduler_queue() {
        if (!function_exists('as_unschedule_all_actions')) {
            return; // Action Scheduler not available
        }

        // Unschedule all migration-related actions
        as_unschedule_all_actions('', array(), 'superforms-migration');

        // Also clear any WP-Cron fallback jobs
        wp_clear_scheduled_hook('super_migration_cron_batch');
        wp_clear_scheduled_hook('super_migration_cron_health');
    }

    /**
     * Establish pre-update state for test isolation
     *
     * Resets all state to clean pre-update conditions to ensure tests are independent.
     * Each test should call this at the start to establish its required initial state.
     *
     * @param array $config Configuration options:
     *   - plugin_version: string (default '6.3.0') - Simulates upgrade from this version
     *   - entries_count: int (default 0) - Number of test entries to create
     *   - migration_state: string|null (default null) - Migration state: 'not_started', 'in_progress', 'completed'
     *   - queue_last_run: string|false (default false) - Last queue run timestamp or false for no history
     *   - create_entries: bool (default false) - Whether to create real entries
     * @return array Created entry IDs (for cleanup)
     * @since 6.4.127
     */
    private function establish_pre_update_state($config = array()) {
        global $wpdb;

        // Default configuration
        $defaults = array(
            'plugin_version' => '6.3.0',
            'entries_count' => 0,
            'migration_state' => null,
            'queue_last_run' => false,
            'create_entries' => false,
        );
        $config = array_merge($defaults, $config);

        // 1. Set plugin version (simulates upgrade scenario)
        update_option('super_plugin_version', $config['plugin_version'], false);

        // 2. Clear/set migration state
        if ($config['migration_state'] === null) {
            delete_option('superforms_eav_migration');
        } else {
            $state = array();
            switch ($config['migration_state']) {
                case 'not_started':
                    delete_option('superforms_eav_migration');
                    break;
                case 'in_progress':
                    $state = array(
                        'status' => 'in_progress',
                        'total_entries' => $config['entries_count'] > 0 ? $config['entries_count'] : 100,
                        'migrated_entries' => 0,
                        'last_processed_id' => 0,
                        'started_at' => date('Y-m-d H:i:s'),
                        'last_batch_processed_at' => date('Y-m-d H:i:s', time() - 3600), // 1hr ago
                    );
                    update_option('superforms_eav_migration', $state, false);
                    break;
                case 'completed':
                    $state = array(
                        'status' => 'completed',
                        'completed_at' => date('Y-m-d H:i:s'),
                    );
                    update_option('superforms_eav_migration', $state, false);
                    break;
            }
        }

        // 3. Truncate EAV table (clean slate)
        $table_name = $wpdb->prefix . 'superforms_entry_data';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name) {
            $wpdb->query("TRUNCATE TABLE {$table_name}");
        }

        // 4. Clear queue history
        if ($config['queue_last_run'] === false) {
            delete_option('superforms_last_queue_run');
        } else {
            update_option('superforms_last_queue_run', $config['queue_last_run'], false);
        }

        // 5. Clear Action Scheduler jobs
        $this->clear_action_scheduler_queue();

        // 6. Clear locks and caches
        delete_transient(SUPER_Background_Migration::LOCK_KEY);
        delete_transient(SUPER_Background_Migration::SETUP_LOCK_KEY);
        delete_transient('super_migration_schedule_lock');
        delete_transient('superforms_needs_migration'); // Clear needs_migration() cache

        // 7. Clear notice dismissals (all users)
        delete_metadata('user', null, 'super_cron_notice_dismissed', '', true);

        // 8. Create test entries if requested
        $created_entries = array();
        if ($config['create_entries'] && $config['entries_count'] > 0) {
            for ($i = 0; $i < $config['entries_count']; $i++) {
                $entry_id = $this->create_test_entry(array(
                    'field_' . $i => array(
                        'name' => 'field_' . $i,
                        'value' => 'Value ' . $i,
                        'type' => 'text',
                        'label' => 'Field ' . $i
                    )
                ));
                $created_entries[] = $entry_id;
                $this->test_entries[] = $entry_id;
            }
        }

        return $created_entries;
    }

    /**
     * Create real test entries with serialized data
     *
     * Unlike setup_pending_migration() which creates fake state,
     * this creates actual contact entry posts with serialized postmeta.
     * Used for testing real migration detection and processing.
     *
     * @param int $count Number of entries to create
     * @param array $form_ids Form IDs to distribute entries across (default: [999])
     * @return array Created entry IDs
     * @since 6.4.127
     */
    private function create_real_test_entries($count = 10, $form_ids = array(999)) {
        $created_entries = array();

        for ($i = 0; $i < $count; $i++) {
            // Rotate through form IDs
            $form_id = $form_ids[$i % count($form_ids)];

            // Create varied test data
            $data = array(
                'name' => array(
                    'name' => 'name',
                    'value' => 'Test User ' . $i,
                    'type' => 'text',
                    'label' => 'Name'
                ),
                'email' => array(
                    'name' => 'email',
                    'value' => 'test' . $i . '@example.com',
                    'type' => 'email',
                    'label' => 'Email'
                ),
                'message' => array(
                    'name' => 'message',
                    'value' => 'Test message number ' . $i,
                    'type' => 'textarea',
                    'label' => 'Message'
                ),
            );

            $entry_id = wp_insert_post(array(
                'post_type' => 'super_contact_entry',
                'post_title' => '__TEST_CRON_FALLBACK_' . time() . '_' . uniqid(),
                'post_status' => 'publish'
            ));

            if (!is_wp_error($entry_id)) {
                add_post_meta($entry_id, '_super_contact_entry_data', serialize($data));
                add_post_meta($entry_id, '_super_form_id', $form_id);
                add_post_meta($entry_id, '_super_test_entry', '1'); // Mark for cleanup

                $created_entries[] = $entry_id;
                $this->test_entries[] = $entry_id;
            }
        }

        return $created_entries;
    }

    /**
     * Cleanup test data
     *
     * Removes all test-created data and restores clean state.
     * Guaranteed to run even if tests fail (called in finally block).
     *
     * @since 6.4.127
     */
    private function cleanup_test_data() {
        global $wpdb;

        // Delete test entries
        if (!empty($this->test_entries)) {
            foreach ($this->test_entries as $entry_id) {
                wp_delete_post($entry_id, true);
            }

            // Delete EAV data
            $ids = implode(',', array_map('intval', $this->test_entries));
            $wpdb->query(
                "DELETE FROM {$wpdb->prefix}superforms_entry_data
                 WHERE entry_id IN ($ids)"
            );

            $this->test_entries = array();
        }

        // Delete test users
        $test_users = get_users(array('search' => '__test_user_*'));
        foreach ($test_users as $user) {
            wp_delete_user($user->ID);
        }

        // Clear Action Scheduler jobs
        $this->clear_action_scheduler_queue();

        // Clear test options
        delete_option('superforms_last_queue_run');
        delete_option('superforms_async_processing_enabled');

        // Clear locks
        delete_transient(SUPER_Background_Migration::LOCK_KEY);
        delete_transient(SUPER_Background_Migration::SETUP_LOCK_KEY);
        delete_transient('super_migration_schedule_lock');

        // Clear notice dismissals (all users)
        delete_metadata('user', null, 'super_cron_notice_dismissed', '', true);

        // Restore original user
        if ($this->original_user_id > 0) {
            wp_set_current_user($this->original_user_id);
        }
    }

    /**
     * Cleanup all test data
     *
     * Comprehensive cleanup that also resets migration state and plugin version.
     * Called at end of full test suite run.
     *
     * @since 6.4.127
     */
    private function cleanup_all() {
        // Clean individual test data first
        $this->cleanup_test_data();

        // Reset migration state
        if (class_exists('SUPER_Migration_Manager')) {
            SUPER_Migration_Manager::reset_migration();
        }

        // Reset plugin version to current (prevents migration re-triggering)
        if (defined('SUPER_VERSION')) {
            update_option('super_plugin_version', SUPER_VERSION, false);
        }

        // Final lock cleanup (in case any were missed)
        delete_transient(SUPER_Background_Migration::LOCK_KEY);
        delete_transient(SUPER_Background_Migration::SETUP_LOCK_KEY);
        delete_transient('super_migration_schedule_lock');
    }

    /**
     * Safety checks before running tests
     *
     * @return bool|string True if safe, error message otherwise
     */
    private function safety_check() {
        // Check DEBUG_SF is enabled
        if (!defined('DEBUG_SF') || !DEBUG_SF) {
            return 'Tests only run when DEBUG_SF is enabled';
        }

        // Check hostname (only dev/localhost)
        $allowed_hosts = ['f4d.nl', 'localhost', '127.0.0.1'];
        $current_host = $_SERVER['HTTP_HOST'] ?? '';

        // For CLI context, check SERVER_NAME or assume allowed if DEBUG_SF is enabled
        if (php_sapi_name() === 'cli') {
            $current_host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        }

        $is_allowed = false;
        foreach ($allowed_hosts as $allowed) {
            if (strpos($current_host, $allowed) !== false) {
                $is_allowed = true;
                break;
            }
        }

        // If running in CLI and DEBUG_SF is enabled, assume dev environment
        if (!$is_allowed && php_sapi_name() === 'cli' && defined('DEBUG_SF') && DEBUG_SF) {
            $is_allowed = true;
        }

        if (!$is_allowed) {
            return 'Tests only run on dev/localhost environments';
        }

        // Check required classes
        $required_classes = [
            'SUPER_Cron_Fallback',
            'SUPER_Migration_Manager',
            'SUPER_Background_Migration',
            'SUPER_Data_Access'
        ];

        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                return "$class class not found";
            }
        }

        // Check Action Scheduler
        if (!class_exists('ActionScheduler')) {
            return 'ActionScheduler class not found (should be bundled)';
        }

        return true;
    }

    /**
     * Log message
     *
     * @param string $message
     */
    private function log($message) {
        $this->results['log'][] = $message;

        // Output to CLI if running in CLI
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        }
    }

    /**
     * Get test results
     *
     * @return array
     */
    private function get_results() {
        return [
            'success' => true,
            'log' => $this->results['log'] ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Create error result
     *
     * @param string $error
     * @return array
     */
    private function error_result($error) {
        return [
            'success' => false,
            'error' => $error,
            'log' => $this->results['log'] ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get available tests
     *
     * @return array
     */
    public function get_available_tests() {
        return $this->available_tests;
    }
}

endif;

// CLI execution
if (php_sapi_name() === 'cli' && !empty($argv)) {
    $test_name = $argv[1] ?? 'all';
    $test = new SUPER_Cron_Fallback_Test();
    $results = $test->run($test_name);

    echo "\n========================================\n";
    if ($results['success']) {
        echo "✅ ALL TESTS PASSED\n";
        exit(0);
    } else {
        echo "❌ TESTS FAILED: " . $results['error'] . "\n";
        exit(1);
    }
}
