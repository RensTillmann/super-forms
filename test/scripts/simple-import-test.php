<?php
/**
 * Simple Import Test - Manual execution
 * Run this through WordPress admin or wp-cli
 */

// Step 1: Clean up existing forms
echo "Cleaning up existing forms...\n";

$existing_forms = get_posts(array(
    'post_type' => 'super_form',
    'meta_query' => array(
        array(
            'key' => '_super_form_id',
            'value' => '71883',
            'compare' => '='
        )
    ),
    'numberposts' => -1
));

foreach ($existing_forms as $form) {
    wp_delete_post($form->ID, true);
    echo "Deleted form: {$form->ID}\n";
}

// Step 2: Import using our fixed script
echo "Importing form 71883...\n";

// Include the import function
require_once(__DIR__ . '/import-single-form.php');

$form_file = __DIR__ . '/../exports/original/form_71883.json';
$result = import_super_form_direct($form_file);

if ($result['success']) {
    $form_id = $result['post_id'];
    echo "Success! Form imported as ID: {$form_id}\n";
    
    // Check version
    $version = get_post_meta($form_id, '_super_version', true);
    echo "Version set to: {$version}\n";
    
    // Check original settings exist
    $settings = get_post_meta($form_id, '_super_form_settings', true);
    if (!empty($settings)) {
        echo "Legacy settings found - migration should trigger on first load\n";
        
        // Show admin email recipients from legacy settings
        if (isset($settings['header_to'])) {
            echo "Legacy admin recipients: {$settings['header_to']}\n";
        }
        if (isset($settings['header_subject'])) {
            echo "Legacy admin subject: {$settings['header_subject']}\n";
        }
        if (isset($settings['confirm_subject'])) {
            echo "Legacy confirm subject: {$settings['confirm_subject']}\n";
        }
    }
    
    echo "\nNow visit: http://localhost:8080/wp-admin/admin.php?page=super_create_form&id={$form_id}\n";
    echo "Click the 'Emails' tab and check if the migration worked!\n";
    
} else {
    echo "Import failed: {$result['error']}\n";
}
?>