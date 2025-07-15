#!/usr/bin/env php
<?php
/**
 * Clean duplicate Super Forms after import
 */

if (!function_exists('get_posts')) {
    die("Run with WP-CLI: wp eval-file /scripts/clean-duplicate-forms.php\n");
}

echo "=== CLEANING DUPLICATE SUPER FORMS ===\n\n";

// Get all super forms
$args = array(
    'post_type' => 'super_form',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'ID',
    'order' => 'ASC'
);

$forms = get_posts($args);
$total_forms = count($forms);
echo "Total forms found: $total_forms\n\n";

// Group forms by title
$forms_by_title = array();
foreach ($forms as $form) {
    $title = $form->post_title;
    if (!isset($forms_by_title[$title])) {
        $forms_by_title[$title] = array();
    }
    $forms_by_title[$title][] = $form;
}

// Find and remove duplicates
$duplicates_removed = 0;
$unique_forms = 0;

echo "Checking for duplicates...\n";
foreach ($forms_by_title as $title => $title_forms) {
    $count = count($title_forms);
    $unique_forms++;
    
    if ($count > 1) {
        echo "- \"$title\": $count copies found\n";
        
        // Keep the first one, delete the rest
        for ($i = 1; $i < $count; $i++) {
            wp_delete_post($title_forms[$i]->ID, true);
            $duplicates_removed++;
        }
    }
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "Unique forms: $unique_forms\n";
echo "Duplicates removed: $duplicates_removed\n";
echo "Forms remaining: " . ($total_forms - $duplicates_removed) . "\n";

// List some of the remaining forms
echo "\n=== SAMPLE OF REMAINING FORMS ===\n";
$remaining = get_posts(array(
    'post_type' => 'super_form',
    'posts_per_page' => 10,
    'orderby' => 'ID',
    'order' => 'ASC'
));

foreach ($remaining as $form) {
    echo "ID: {$form->ID} - {$form->post_title}\n";
}

echo "\nDone!\n";