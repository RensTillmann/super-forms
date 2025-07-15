#!/usr/bin/env php
<?php
/**
 * Clean import - only published forms, no duplicates
 */

if (!function_exists('get_posts')) {
    die("Run with WP-CLI: wp eval-file /scripts/clean-and-import-forms.php\n");
}

echo "=== CLEANING AND ORGANIZING SUPER FORMS ===\n\n";

// First, remove all backup forms
$backups = get_posts(array(
    'post_type' => 'super_form',
    'post_status' => array('backup', 'inherit', 'auto-draft', 'trash'),
    'posts_per_page' => -1
));

echo "Removing " . count($backups) . " backup/draft forms...\n";
foreach ($backups as $backup) {
    wp_delete_post($backup->ID, true);
}

// Get remaining forms
$forms = get_posts(array(
    'post_type' => 'super_form',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC'
));

echo "\nPublished forms: " . count($forms) . "\n";

// Remove duplicates by title (keep oldest)
$forms_by_title = array();
$duplicates = 0;

foreach ($forms as $form) {
    $title = trim($form->post_title);
    if (!isset($forms_by_title[$title])) {
        $forms_by_title[$title] = $form;
    } else {
        // Keep the one with lower ID (older)
        if ($form->ID < $forms_by_title[$title]->ID) {
            wp_delete_post($forms_by_title[$title]->ID, true);
            $forms_by_title[$title] = $form;
        } else {
            wp_delete_post($form->ID, true);
        }
        $duplicates++;
    }
}

echo "Duplicates removed: $duplicates\n";
echo "\n=== FINAL FORM COUNT ===\n";

$final_count = wp_count_posts('super_form');
echo "Published: " . $final_count->publish . "\n";
echo "Draft: " . $final_count->draft . "\n";
echo "Total unique forms: " . count($forms_by_title) . "\n";

// List some forms
echo "\n=== SAMPLE FORMS ===\n";
$i = 0;
foreach ($forms_by_title as $title => $form) {
    echo "ID: {$form->ID} - $title\n";
    if (++$i >= 20) break;
}

echo "\nDone!\n";