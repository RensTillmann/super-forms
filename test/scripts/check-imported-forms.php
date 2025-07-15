#!/usr/bin/env php
<?php
/**
 * Check which Super Forms have been imported
 */

// Bootstrap WordPress
require_once('/var/www/html/wp-config.php');
require_once('/var/www/html/wp-load.php');

echo "=== CHECKING IMPORTED SUPER FORMS ===\n\n";

// Get all super_form posts
$args = array(
    'post_type' => 'super_form',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'ID',
    'order' => 'ASC'
);

$forms = get_posts($args);

echo "Total forms found: " . count($forms) . "\n\n";

if (empty($forms)) {
    echo "No Super Forms found in the database.\n";
    echo "The forms may not have been imported yet.\n";
    exit(0);
}

echo "ID\tStatus\t\tTitle\n";
echo str_repeat("-", 60) . "\n";

foreach ($forms as $form) {
    echo $form->ID . "\t" . $form->post_status . "\t\t" . substr($form->post_title, 0, 40) . "\n";
}

// Check for specific critical forms
$critical_forms = [8, 125, 69852, 71952];
echo "\n=== CRITICAL FORMS STATUS ===\n";

foreach ($critical_forms as $form_id) {
    $form = get_post($form_id);
    if ($form && $form->post_type === 'super_form') {
        echo "Form $form_id: FOUND (Status: {$form->post_status})\n";
    } else {
        echo "Form $form_id: NOT FOUND\n";
    }
}

// Check if form 8 has valid settings
echo "\n=== FORM 8 SETTINGS CHECK ===\n";
$form8 = get_post(8);
if ($form8) {
    $settings = get_post_meta(8, '_super_form_settings', true);
    if ($settings) {
        echo "Form 8 has settings: YES\n";
        echo "Settings type: " . (is_array($settings) ? 'array' : 'serialized') . "\n";
        if (is_array($settings)) {
            echo "Total settings: " . count($settings) . "\n";
        }
    } else {
        echo "Form 8 has settings: NO\n";
    }
    
    $elements = get_post_meta(8, '_super_elements', true);
    if ($elements) {
        echo "Form 8 has elements: YES\n";
    } else {
        echo "Form 8 has elements: NO\n";
    }
}

echo "\nDone.\n";
?>