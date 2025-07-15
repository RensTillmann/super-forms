<?php
// Fix form 7 import with proper email reminders

require_once('/var/www/html/wp-config.php');

echo "=== Fixing Form 7 Import ===\n";

$form_file = '/scripts/../exports/original/form_69852.json';
$form_data = json_decode(file_get_contents($form_file), true);

// Delete existing form 7
wp_delete_post(7, true);
echo "Deleted existing form 7\n";

// Properly unserialize the settings
$settings_raw = $form_data['settings'];
$settings = unserialize($settings_raw);

echo "Settings unserialized. Total keys: " . count($settings) . "\n";

// Verify email reminders in unserialized data
$reminder1_exists = isset($settings['email_reminder_1']) && $settings['email_reminder_1'] === 'true';
echo "Email reminder 1 in unserialized data: " . ($reminder1_exists ? 'YES' : 'NO') . "\n";

if ($reminder1_exists) {
    echo "Email reminder 1 details:\n";
    echo "  To: " . (isset($settings['email_reminder_1_to']) ? $settings['email_reminder_1_to'] : 'NOT SET') . "\n";
    echo "  Subject: " . (isset($settings['email_reminder_1_subject']) ? $settings['email_reminder_1_subject'] : 'NOT SET') . "\n";
}

// Create new post
$post_data = array(
    'post_title' => $form_data['title'],
    'post_type'  => 'super_form',
    'post_status' => 'publish',
    'post_content' => '',
    'post_date' => $form_data['date'],
    'post_author' => 1
);

$post_id = wp_insert_post($post_data);
echo "Created new post with ID: $post_id\n";

// Set form version to trigger migration
update_post_meta($post_id, '_super_version', '6.3.0'); // Use older version to trigger migration

// Save the properly unserialized settings
update_post_meta($post_id, '_super_form_settings', $settings);
echo "Saved form settings\n";

// Verify the settings were saved correctly
$saved_settings = get_post_meta($post_id, '_super_form_settings', true);
$reminder1_saved = isset($saved_settings['email_reminder_1']) && $saved_settings['email_reminder_1'] === 'true';
echo "Email reminder 1 after save: " . ($reminder1_saved ? 'YES' : 'NO') . "\n";

if ($reminder1_saved) {
    echo "Email reminder 1 saved details:\n";
    echo "  To: " . (isset($saved_settings['email_reminder_1_to']) ? $saved_settings['email_reminder_1_to'] : 'NOT SET') . "\n";
    echo "  Subject: " . (isset($saved_settings['email_reminder_1_subject']) ? $saved_settings['email_reminder_1_subject'] : 'NOT SET') . "\n";
}

// Check basic email settings too
$has_send = isset($saved_settings['send']) && $saved_settings['send'] === 'yes';
$has_confirm = isset($saved_settings['confirm']) && $saved_settings['confirm'] === 'yes';
echo "Admin email (send): " . ($has_send ? 'YES' : 'NO') . "\n";
echo "Confirmation email (confirm): " . ($has_confirm ? 'YES' : 'NO') . "\n";

echo "\nForm $post_id is ready for migration testing!\n";
echo "Version set to 6.3.0 to ensure migration triggers\n";

echo "\n=== Import Fix Complete ===\n";
?>