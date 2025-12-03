<?php
/**
 * Quick migration validation script
 * Run via: wp eval-file test/validate-migration.php
 */

global $wpdb;

echo "=== SUPER FORMS MIGRATION VALIDATION ===\n\n";

// 1. Check form counts
$old_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'super_form'");
$new_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}superforms_forms");

echo "--- FORM COUNTS ---\n";
echo "Old System (wp_posts): {$old_count} forms\n";
echo "New System (custom table): {$new_count} forms\n";
echo "Counts Match: " . ($old_count === $new_count ? 'YES' : 'NO') . "\n\n";

// 2. Check migration status
if (class_exists('SUPER_Form_Background_Migration')) {
    $status = SUPER_Form_Background_Migration::get_migration_status();
    echo "--- MIGRATION STATUS ---\n";
    echo "Status: {$status['status']}\n";
    echo "Is Complete: " . ($status['is_complete'] ? 'YES' : 'NO') . "\n";
    echo "Total Forms: {$status['total_forms']}\n";
    echo "Migrated: {$status['migrated']}\n";
    echo "Remaining: {$status['remaining']}\n";
    echo "Failed: {$status['failed_count']}\n";
    echo "Progress: {$status['progress_percent']}%\n\n";
} else {
    echo "--- MIGRATION STATUS ---\n";
    echo "ERROR: Migration class not found\n\n";
}

// 3. Check DAL
$dal_exists = class_exists('SUPER_Form_DAL');
echo "--- DAL CHECK ---\n";
echo "DAL Class Exists: " . ($dal_exists ? 'YES' : 'NO') . "\n";

if ($dal_exists) {
    // Test CRUD operations
    echo "\n--- DAL ROUND-TRIP TEST ---\n";

    $test_data = array(
        'name' => 'Test Migration Validation Form',
        'status' => 'draft',
        'elements' => array('field1' => array('type' => 'text')),
        'settings' => array('test' => true),
        'translations' => array()
    );

    $form_id = SUPER_Form_DAL::create($test_data);

    if (is_wp_error($form_id)) {
        echo "CREATE: FAILED - " . $form_id->get_error_message() . "\n";
    } else {
        echo "CREATE: SUCCESS (ID: {$form_id})\n";

        $form = SUPER_Form_DAL::get($form_id);
        if ($form && $form->name === 'Test Migration Validation Form') {
            echo "READ: SUCCESS\n";

            $updated = SUPER_Form_DAL::update($form_id, array('name' => 'Updated Test Form'));
            echo "UPDATE: " . (!is_wp_error($updated) && $updated ? 'SUCCESS' : 'FAILED') . "\n";

            $deleted = SUPER_Form_DAL::delete($form_id);
            echo "DELETE: " . (!is_wp_error($deleted) && $deleted ? 'SUCCESS' : 'FAILED') . "\n";
        } else {
            echo "READ: FAILED\n";
        }
    }
}

// 4. Check REST API
echo "\n--- REST API CHECK ---\n";
$routes = rest_get_server()->get_routes();
$forms_routes = array();
foreach ($routes as $route => $handlers) {
    if (strpos($route, '/super-forms/v1/forms') === 0) {
        $forms_routes[] = $route;
    }
}
echo "REST Routes Found: " . count($forms_routes) . "\n";
foreach ($forms_routes as $route) {
    echo "  - {$route}\n";
}

echo "\n=== VALIDATION COMPLETE ===\n";
