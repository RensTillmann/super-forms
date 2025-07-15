<?php
/**
 * Import a form with debug logging
 */

$form_file = isset($GLOBALS['FORM_FILE_TO_IMPORT']) ? $GLOBALS['FORM_FILE_TO_IMPORT'] : '/scripts/../exports/original/form_142.json';

error_log('=== Starting Super Forms import with debug logging ===');
error_log('Importing form file: ' . $form_file);

if (!file_exists($form_file)) {
    error_log('ERROR: Form file not found: ' . $form_file);
    echo json_encode(['success' => false, 'error' => "Form file not found: $form_file"]);
    return;
}

error_log('Form file exists, reading JSON data...');
$form_data = json_decode(file_get_contents($form_file), true);

if (!$form_data) {
    error_log('ERROR: Invalid JSON in file: ' . $form_file);
    echo json_encode(['success' => false, 'error' => "Invalid JSON in file: $form_file"]);
    return;
}

error_log('JSON data loaded successfully. Form title: ' . ($form_data['title'] ?? 'Unknown'));
error_log('Form has elements: ' . (empty($form_data['elements']) ? 'NO' : 'YES'));

// Include the actual import function
include('/scripts/import-single-form.php');

// Call the import function
$result = import_super_form_direct($form_file);

error_log('Import completed. Success: ' . ($result['success'] ? 'YES' : 'NO'));
if ($result['success']) {
    error_log('New post ID: ' . $result['post_id']);
} else {
    error_log('Import error: ' . $result['error']);
}

echo json_encode($result);