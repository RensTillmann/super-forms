<?php
// Test email reminders import with proper serialization handling

require_once('/var/www/html/wp-config.php');

$form_file = '/scripts/../exports/original/form_69852.json';
$form_data = json_decode(file_get_contents($form_file), true);

echo "=== Testing Email Reminders Import ===\n";
echo "Form: " . $form_data['title'] . " (ID: " . $form_data['id'] . ")\n\n";

// Check if settings is serialized
$settings_raw = $form_data['settings'];
echo "Settings type: " . (is_string($settings_raw) ? 'string' : 'array') . "\n";
echo "Settings starts with: " . substr($settings_raw, 0, 20) . "\n\n";

// Properly unserialize the settings
if (is_string($settings_raw)) {
    $settings = unserialize($settings_raw);
    if ($settings === false) {
        echo "ERROR: Failed to unserialize settings\n";
        exit(1);
    }
} else {
    $settings = $settings_raw;
}

echo "Settings successfully unserialized. Total keys: " . count($settings) . "\n\n";

// Check for email reminders
echo "=== Email Reminder Analysis ===\n";
for ($i = 1; $i <= 3; $i++) {
    $key = 'email_reminder_' . $i;
    if (isset($settings[$key])) {
        echo "Found {$key}: " . $settings[$key] . "\n";
        
        if ($settings[$key] === 'true' || $settings[$key] === true) {
            echo "  Email reminder {$i} is ENABLED\n";
            
            // Check all related settings
            $related_keys = array(
                $key . '_to',
                $key . '_from',
                $key . '_from_name', 
                $key . '_subject',
                $key . '_body',
                $key . '_header_cc',
                $key . '_header_bcc',
                $key . '_attachments'
            );
            
            foreach ($related_keys as $rkey) {
                if (isset($settings[$rkey]) && !empty($settings[$rkey])) {
                    $value = is_string($settings[$rkey]) ? $settings[$rkey] : json_encode($settings[$rkey]);
                    if (strlen($value) > 100) $value = substr($value, 0, 100) . '...';
                    echo "    {$rkey}: {$value}\n";
                }
            }
        }
    }
}

// Test if we can import correctly now
echo "\n=== Test Import ===\n";

// Delete existing form 6 if it exists
$existing = get_post(6);
if ($existing) {
    wp_delete_post(6, true);
    echo "Deleted existing form 6\n";
}

// Create the post correctly
$post_data = array(
    'post_title' => $form_data['title'],
    'post_type'  => 'super_form', 
    'post_status' => 'publish',
    'post_content' => '',
    'post_date' => $form_data['date'],
    'post_author' => 1
);

$post_id = wp_insert_post($post_data);
if (is_wp_error($post_id)) {
    echo "ERROR: Failed to create post\n";
    exit(1);
}

echo "Created post with ID: {$post_id}\n";

// Import the properly unserialized settings
update_post_meta($post_id, '_super_form_settings', $settings);
update_post_meta($post_id, '_super_version', '6.4.110');

echo "Settings imported successfully\n";

// Verify the import
$imported_settings = get_post_meta($post_id, '_super_form_settings', true);
$has_reminder = isset($imported_settings['email_reminder_1']) && $imported_settings['email_reminder_1'] === 'true';

echo "Email reminder 1 verification: " . ($has_reminder ? 'SUCCESS' : 'FAILED') . "\n";

if ($has_reminder) {
    echo "Email reminder 1 to: " . (isset($imported_settings['email_reminder_1_to']) ? $imported_settings['email_reminder_1_to'] : 'NOT SET') . "\n";
    echo "Email reminder 1 subject: " . (isset($imported_settings['email_reminder_1_subject']) ? $imported_settings['email_reminder_1_subject'] : 'NOT SET') . "\n";
}

echo "\nForm {$post_id} is ready for email reminders migration testing!\n";
?>