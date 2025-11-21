<?php
/**
 * End-to-End Test for Trigger/Action System
 *
 * Tests the complete flow: Event → Trigger → Action → Log
 *
 * Usage: wp eval-file test-trigger-system.php
 */

echo "\n";
echo "=======================================================\n";
echo "  SUPER FORMS TRIGGER SYSTEM - END-TO-END TEST\n";
echo "=======================================================\n\n";

// Step 1: Verify database tables exist
echo "[1/6] Checking database tables...\n";
global $wpdb;

$tables_to_check = [
	'superforms_triggers',
	'superforms_trigger_actions',
	'superforms_trigger_logs'
];

$tables_exist = true;
foreach ($tables_to_check as $table) {
	$table_name = $wpdb->prefix . $table;
	$exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
	
	if ($exists) {
		echo "  ✓ Table exists: {$table_name}\n";
	} else {
		echo "  ✗ Table MISSING: {$table_name}\n";
		$tables_exist = false;
	}
}

if (!$tables_exist) {
	echo "\n❌ ERROR: Required tables are missing. Please run Super Forms installation/activation.\n";
	exit(1);
}

echo "  ✓ All required tables exist\n\n";

// Step 2: Verify foundation classes are loaded
echo "[2/6] Verifying foundation classes...\n";

$required_classes = [
	'SUPER_Trigger_Registry',
	'SUPER_Trigger_DAL',
	'SUPER_Trigger_Manager',
	'SUPER_Trigger_Executor',
	'SUPER_Trigger_Conditions',
	'SUPER_Trigger_Action_Base'
];

$classes_loaded = true;
foreach ($required_classes as $class) {
	if (class_exists($class)) {
		echo "  ✓ Class loaded: {$class}\n";
	} else {
		echo "  ✗ Class MISSING: {$class}\n";
		$classes_loaded = false;
	}
}

if (!$classes_loaded) {
	echo "\n❌ ERROR: Required classes are not loaded. Please check plugin initialization.\n";
	exit(1);
}

echo "  ✓ All foundation classes loaded\n\n";

// Step 3: Verify Log Message action is registered
echo "[3/6] Checking action registration...\n";

$registry = SUPER_Trigger_Registry::get_instance();

// Manually trigger initialization if not already done
// (wp eval-file may execute before 'init' hook)
if (method_exists($registry, 'initialize')) {
	// Check if already initialized by seeing if we have any actions registered
	$reflection = new ReflectionClass($registry);
	$actions_prop = $reflection->getProperty('actions');
	$actions_prop->setAccessible(true);
	$actions = $actions_prop->getValue($registry);

	if (empty($actions)) {
		$registry->initialize();
	}
}

$log_action = $registry->get_action_instance('log_message');

if ($log_action) {
	echo "  ✓ Log Message action registered and instantiable\n";
	echo "    - ID: " . $log_action->get_id() . "\n";
	echo "    - Label: " . $log_action->get_label() . "\n";
	echo "    - Category: " . $log_action->get_category() . "\n";
} else {
	echo "  ✗ Log Message action NOT found\n";
	exit(1);
}

echo "\n";

// Step 4: Create test trigger
echo "[4/6] Creating test trigger...\n";

$trigger_data = [
	'trigger_name' => 'Test Trigger - Log Message',
	'event_id' => 'form.submitted',
	'scope' => 'form',
	'scope_id' => 999, // Test form ID
	'conditions' => '',
	'enabled' => 1,
	'execution_order' => 10
];

$trigger_id = SUPER_Trigger_DAL::create_trigger($trigger_data);

if (is_wp_error($trigger_id)) {
	echo "  ✗ Failed to create trigger: " . $trigger_id->get_error_message() . "\n";
	exit(1);
}

echo "  ✓ Trigger created with ID: {$trigger_id}\n";

// Step 5: Create test action for the trigger
echo "[5/6] Creating test action...\n";

$action_data = [
	'action_type' => 'log_message',
	'action_config' => [
		'message' => 'TEST: Form #{form_id} submitted at {timestamp}',
		'log_level' => 'info',
		'include_context' => false
	],
	'execution_order' => 10,
	'enabled' => 1
];

$action_id = SUPER_Trigger_DAL::create_action($trigger_id, $action_data);

if (is_wp_error($action_id)) {
	echo "  ✗ Failed to create action: " . $action_id->get_error_message() . "\n";
	// Cleanup
	SUPER_Trigger_DAL::delete_trigger($trigger_id);
	exit(1);
}

echo "  ✓ Action created with ID: {$action_id}\n\n";

// Step 6: Fire test event
echo "[6/6] Firing test event...\n";

$test_context = [
	'form_id' => 999,
	'entry_id' => 12345,
	'timestamp' => current_time('mysql'),
	'user_id' => get_current_user_id(),
	'form_data' => [
		'name' => 'Test User',
		'email' => 'test@example.com'
	]
];

echo "  → Firing 'form.submitted' event...\n";

$results = SUPER_Trigger_Executor::fire_event('form.submitted', $test_context);

if (empty($results)) {
	echo "  ⚠ No triggers executed (this may be expected if scope doesn't match)\n";
} else {
	echo "  ✓ Event fired and " . count($results) . " trigger(s) executed\n";
	
	foreach ($results as $tid => $result) {
		echo "\n  Trigger #{$tid} Results:\n";
		echo "    - Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
		echo "    - Actions executed: " . ($result['actions_count'] ?? 0) . "\n";
		echo "    - Execution time: " . ($result['execution_time'] ?? 0) . "ms\n";
		
		if (!empty($result['actions_results'])) {
			foreach ($result['actions_results'] as $aid => $action_result) {
				echo "\n    Action #{$aid}:\n";
				if (is_array($action_result)) {
					foreach ($action_result as $key => $value) {
						if (!is_array($value) && !is_object($value)) {
							echo "      - {$key}: {$value}\n";
						}
					}
				}
			}
		}
	}
}

echo "\n";

// Verification: Check database logs
echo "Verifying database logs...\n";

$logs = SUPER_Trigger_DAL::get_execution_logs(['trigger_id' => $trigger_id], 10);

if (!empty($logs)) {
	echo "  ✓ Found " . count($logs) . " log entries\n";
	echo "\n  Latest log entry:\n";
	$latest = $logs[0];
	echo "    - Event ID: " . $latest['event_id'] . "\n";
	echo "    - Status: " . $latest['status'] . "\n";
	echo "    - Executed at: " . $latest['executed_at'] . "\n";
} else {
	echo "  ⚠ No log entries found\n";
}

echo "\n";

// Cleanup
echo "Cleaning up test data...\n";
$deleted = SUPER_Trigger_DAL::delete_trigger($trigger_id);

if ($deleted && !is_wp_error($deleted)) {
	echo "  ✓ Test trigger deleted (ID: {$trigger_id})\n";
} else {
	echo "  ⚠ Failed to delete test trigger\n";
}

echo "\n";
echo "=======================================================\n";
echo "  TEST COMPLETE\n";
echo "=======================================================\n";
echo "\n";
echo "Next steps:\n";
echo "  1. Check wp-content/debug.log for log message\n";
echo "  2. Query trigger_logs table for detailed execution data\n";
echo "  3. Implement additional actions (send_email, webhook, etc.)\n";
echo "\n";
