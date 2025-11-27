<?php
/**
 * Sandbox Test Runner
 *
 * CLI script for automated sandbox testing of the trigger system.
 * Designed to be called via SSH after syncing files.
 *
 * Usage:
 *   php tests/sandbox-runner.php              # Run tests (creates sandbox if needed)
 *   php tests/sandbox-runner.php --cleanup    # Run tests then cleanup
 *   php tests/sandbox-runner.php --keep       # Run tests, keep sandbox for inspection
 *   php tests/sandbox-runner.php --status     # Show sandbox status only
 *   php tests/sandbox-runner.php --reset      # Cleanup and recreate sandbox
 *
 * Exit Codes:
 *   0 = All tests passed
 *   1 = Some tests failed
 *   2 = Setup error (WordPress not loaded, etc.)
 *
 * @package Super_Forms
 * @subpackage Tests
 * @since 6.5.0
 */

// Ensure CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Parse command line arguments
$options = getopt('', ['cleanup', 'keep', 'status', 'reset', 'verbose', 'help', 'json']);

if (isset($options['help'])) {
    echo <<<HELP
Sandbox Test Runner - Super Forms Trigger System Testing

Usage: php tests/sandbox-runner.php [options]

Options:
  --status    Show current sandbox status only
  --reset     Cleanup existing sandbox and create fresh one
  --cleanup   Run tests then cleanup sandbox
  --keep      Run tests and keep sandbox (default)
  --verbose   Show detailed output
  --json      Output results as JSON
  --help      Show this help message

Examples:
  php tests/sandbox-runner.php                # Run tests
  php tests/sandbox-runner.php --status       # Check sandbox state
  php tests/sandbox-runner.php --reset        # Fresh sandbox + tests
  php tests/sandbox-runner.php --cleanup      # Run tests then remove sandbox

Exit Codes:
  0 = All tests passed
  1 = Some tests failed
  2 = Setup error

HELP;
    exit(0);
}

$verbose = isset($options['verbose']);
$json_output = isset($options['json']);

// Helper function for output
function output($message, $is_error = false) {
    global $json_output;
    if (!$json_output) {
        if ($is_error) {
            fwrite(STDERR, $message . PHP_EOL);
        } else {
            echo $message . PHP_EOL;
        }
    }
}

function output_verbose($message) {
    global $verbose, $json_output;
    if ($verbose && !$json_output) {
        echo "  [DEBUG] " . $message . PHP_EOL;
    }
}

// Find WordPress root
$wp_load_paths = [
    dirname(__DIR__, 4) . '/wp-load.php',           // Standard plugin location
    dirname(__DIR__, 5) . '/wp-load.php',           // One level deeper
    '/var/www/html/wp-load.php',                    // Common server path
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',    // Document root
];

// Also check relative to plugin
$plugin_dir = dirname(__DIR__);
$current = $plugin_dir;
for ($i = 0; $i < 6; $i++) {
    $current = dirname($current);
    $wp_load_paths[] = $current . '/wp-load.php';
}

$wp_load = null;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        $wp_load = $path;
        break;
    }
}

if (!$wp_load) {
    output("ERROR: Could not find wp-load.php", true);
    output("Searched paths:", true);
    foreach (array_unique($wp_load_paths) as $path) {
        output("  - $path", true);
    }
    exit(2);
}

output_verbose("Loading WordPress from: $wp_load");

// Load WordPress
define('WP_USE_THEMES', false);
require_once $wp_load;

// Load Sandbox Manager explicitly (not auto-loaded by Super Forms)
$plugin_dir = dirname(__DIR__);
$sandbox_manager_path = $plugin_dir . '/includes/class-sandbox-manager.php';
if (file_exists($sandbox_manager_path)) {
    require_once $sandbox_manager_path;
    output_verbose("Loaded Sandbox Manager from: $sandbox_manager_path");
} else {
    // Try alternative path (running from tests/ directory within plugin)
    $alt_path = dirname($plugin_dir) . '/includes/class-sandbox-manager.php';
    if (file_exists($alt_path)) {
        require_once $alt_path;
        output_verbose("Loaded Sandbox Manager from: $alt_path");
    }
}

// Verify required classes exist
$required_classes = [
    'SUPER_Sandbox_Manager',
    'SUPER_Trigger_Executor',
    'SUPER_Trigger_DAL',
];

foreach ($required_classes as $class) {
    if (!class_exists($class)) {
        output("ERROR: Required class '$class' not found.", true);
        output("Make sure Super Forms is activated and trigger system is loaded.", true);
        output("Plugin dir: $plugin_dir", true);
        exit(2);
    }
}

// Load test fixtures if available
$fixtures_dir = dirname(__FILE__) . '/fixtures';
$fixture_files = [
    'class-form-factory.php',
    'class-trigger-factory.php',
    'class-webhook-simulator.php',
];

foreach ($fixture_files as $file) {
    $path = $fixtures_dir . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
        output_verbose("Loaded fixture: $file");
    }
}

output("");
output("===========================================");
output(" Super Forms Sandbox Test Runner");
output("===========================================");
output("");

// Set current user to admin for permissions
$admin_user = get_users(['role' => 'administrator', 'number' => 1]);
if (!empty($admin_user)) {
    wp_set_current_user($admin_user[0]->ID);
    output_verbose("Running as user: " . $admin_user[0]->user_login);
}

// Collect results for JSON output
$json_results = [
    'success' => false,
    'status' => null,
    'tests' => null,
    'errors' => [],
];

// Handle --status flag
if (isset($options['status'])) {
    $status = SUPER_Sandbox_Manager::get_sandbox_status();

    if (!$status['exists']) {
        output("Sandbox Status: NOT CREATED");
        output("");
        output("Run without --status to create sandbox and run tests.");
    } else {
        output("Sandbox Status: ACTIVE");
        output("Created: " . $status['created_at']);
        output("");
        output("Forms:");
        foreach ($status['forms'] as $type => $form) {
            output("  - [$type] {$form['form_title']} (ID: {$form['form_id']}, Triggers: {$form['trigger_count']})");
        }
        output("");
        output("Statistics:");
        output("  Total Triggers: " . $status['trigger_count']);
        output("  Total Entries:  " . $status['entry_count']);
        output("  Total Logs:     " . $status['log_count']);
    }

    if ($json_output) {
        $json_results['success'] = true;
        $json_results['status'] = $status;
        echo json_encode($json_results, JSON_PRETTY_PRINT) . PHP_EOL;
    }

    exit(0);
}

// Handle --reset flag
if (isset($options['reset'])) {
    output("Resetting sandbox...");
    $cleanup = SUPER_Sandbox_Manager::cleanup_sandbox();
    output_verbose("Cleanup: " . json_encode($cleanup));
}

// Check if sandbox exists, create if not
$status = SUPER_Sandbox_Manager::get_sandbox_status();

if (!$status['exists']) {
    output("Creating sandbox environment...");
    output("");

    // Create with multiple form types for comprehensive testing
    $form_types = ['simple', 'comprehensive', 'repeater'];

    if (class_exists('SUPER_Test_Form_Factory')) {
        output_verbose("Using test fixtures for form creation");
    } else {
        output_verbose("Test fixtures not available, using fallback form creation");
    }

    $sandbox = SUPER_Sandbox_Manager::create_sandbox([
        'form_types' => $form_types,
    ]);

    if (is_wp_error($sandbox)) {
        output("ERROR: Failed to create sandbox: " . $sandbox->get_error_message(), true);
        $json_results['errors'][] = $sandbox->get_error_message();
        if ($json_output) {
            echo json_encode($json_results, JSON_PRETTY_PRINT) . PHP_EOL;
        }
        exit(2);
    }

    output("Sandbox created successfully!");
    output("Forms: " . count($sandbox['forms']));
    output("Triggers: " . count($sandbox['trigger_ids']));
    output("");

    // Refresh status
    $status = SUPER_Sandbox_Manager::get_sandbox_status();
}

output("Running test suite...");
output("");

// Run tests
$results = SUPER_Sandbox_Manager::run_test_suite();

// Display results
output("===========================================");
output(" TEST RESULTS");
output("===========================================");
output("");

$all_passed = true;

foreach ($results['tests'] as $form_type => $test) {
    $status_icon = $test['passed'] ? '[PASS]' : '[FAIL]';
    $status_color = $test['passed'] ? '' : ''; // Could add ANSI colors here

    output("$status_icon $form_type (Form ID: {$test['form_id']})");
    output("       Entry Created:     " . ($test['entry_created'] ? 'Yes' : 'No'));
    output("       Triggers Found:    " . $test['triggers_found']);
    output("       Triggers Executed: " . $test['triggers_executed']);
    output("       Logs Created:      " . $test['logs_created']);

    if (!empty($test['errors'])) {
        foreach ($test['errors'] as $error) {
            output("       ERROR: $error");
        }
    }

    output("");

    if (!$test['passed']) {
        $all_passed = false;
    }
}

output("===========================================");
output(" SUMMARY");
output("===========================================");
output("");
output("Total Forms:  " . $results['total_forms']);
output("Passed:       " . $results['total_passed']);
output("Failed:       " . $results['total_failed']);
output("");

if ($all_passed) {
    output("Result: ALL TESTS PASSED");
} else {
    output("Result: SOME TESTS FAILED");
}

output("");

// Show recent logs for debugging
if ($verbose) {
    $logs = SUPER_Sandbox_Manager::get_sandbox_logs(10);
    if (!empty($logs)) {
        output("Recent Trigger Logs:");
        output("-------------------------------------------");
        foreach ($logs as $log) {
            $status = $log['status'] ?? 'unknown';
            $event = $log['event_id'] ?? 'N/A';
            $time = $log['created_at'] ?? 'N/A';
            output("  [$status] $event at $time");
            if (!empty($log['error_message'])) {
                output("    Error: " . $log['error_message']);
            }
        }
        output("");
    }
}

// Handle cleanup
if (isset($options['cleanup'])) {
    output("Cleaning up sandbox...");
    $cleanup_stats = SUPER_Sandbox_Manager::cleanup_sandbox();
    output("Deleted: {$cleanup_stats['forms_deleted']} forms, {$cleanup_stats['triggers_deleted']} triggers, {$cleanup_stats['entries_deleted']} entries, {$cleanup_stats['logs_deleted']} logs");
    output("");
} else {
    output("Sandbox preserved for inspection.");
    output("Use --cleanup flag to remove sandbox data.");
    output("");
}

// JSON output
if ($json_output) {
    $json_results['success'] = $all_passed;
    $json_results['tests'] = $results;
    $json_results['status'] = SUPER_Sandbox_Manager::get_sandbox_status();
    echo json_encode($json_results, JSON_PRETTY_PRINT) . PHP_EOL;
}

// Exit with appropriate code
exit($all_passed ? 0 : 1);
