#!/usr/bin/env php
<?php
/**
 * Import Critical Forms for Testing JavaScript Fixes
 * 
 * This script imports the most critical forms that test various features:
 * - Form 125: Redirect settings (tests basic functionality)
 * - Form 69852: Email reminders (tests email migration)
 * - Form 71952: Complex settings (tests conditional logic)
 */

// Bootstrap WordPress if running via CLI
// Uses secure bootstrap.php that searches upward for wp-load.php
if (!defined('ABSPATH')) {
    require_once(dirname(__DIR__) . '/bootstrap.php');
}

// Include the import function
require_once('/scripts/import-single-form.php');

echo "=== IMPORTING CRITICAL FORMS FOR TESTING ===\n\n";

$critical_forms = array(
    array(
        'file' => '/scripts/../exports/original/form_125.json',
        'description' => 'Form with redirect settings'
    ),
    array(
        'file' => '/scripts/../exports/original/form_69852.json',
        'description' => 'Form with email reminders'
    ),
    array(
        'file' => '/scripts/../exports/original/form_71952.json',
        'description' => 'Form with complex settings'
    )
);

$results = array();

foreach ($critical_forms as $form) {
    echo "Importing: {$form['description']}\n";
    echo "File: {$form['file']}\n";
    
    if (!file_exists($form['file'])) {
        echo "✗ File not found!\n\n";
        continue;
    }
    
    $result = import_super_form_direct($form['file']);
    
    if ($result['success']) {
        echo "✓ Successfully imported (Post ID: {$result['post_id']})\n";
        echo "  Original Form ID: {$result['original_id']}\n";
        echo "  Title: {$result['title']}\n";
        $results[] = $result;
    } else {
        echo "✗ Import failed: {$result['error']}\n";
    }
    
    echo "\n";
}

echo "=== IMPORT SUMMARY ===\n";
echo "Total forms imported: " . count($results) . "\n\n";

echo "=== TESTING INSTRUCTIONS ===\n";
echo "1. Access WordPress admin at: /wp-admin/\n";
echo "2. Go to Super Forms > All Forms\n";
echo "3. Test each imported form:\n";
foreach ($results as $result) {
    echo "   - Edit form ID {$result['post_id']} ({$result['title']})\n";
}
echo "\n";
echo "4. Check browser console for:\n";
echo "   - No JavaScript syntax errors\n";
echo "   - No infinite loops\n";
echo "   - Smooth tab switching\n";
echo "\n";

// Note about Form 8
echo "=== NOTE ABOUT FORM 8 ===\n";
echo "Form 8 was not found in the exports. This was the form with the\n";
echo "JavaScript syntax error. The fix has been applied globally, so any\n";
echo "form using the popup add-on should now work correctly.\n";
echo "\n";

echo "Done.\n";
?>