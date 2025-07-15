<?php
// Trigger email reminders migration for form 7

require_once('/var/www/html/wp-config.php');

echo "=== Testing Email Reminders Migration ===\n";

$form_id = 7;

echo "Form ID: $form_id\n";

// Check if _emails field exists before migration
$existing_emails = get_post_meta($form_id, '_emails', true);
echo "Existing _emails field: " . (empty($existing_emails) ? 'NOT FOUND (good for testing)' : 'EXISTS') . "\n\n";

// Load form settings which should trigger migration
echo "Triggering migration by loading form settings...\n";
$settings = SUPER_Common::get_form_settings($form_id);

echo "Form settings loaded successfully\n\n";

// Check if _emails field was created
$migrated_emails = get_post_meta($form_id, '_emails', true);

if (!empty($migrated_emails)) {
    echo "SUCCESS: _emails field created with " . count($migrated_emails) . " email(s)\n\n";
    
    foreach ($migrated_emails as $index => $email) {
        $name = isset($email['name']) ? $email['name'] : 'Unknown';
        $enabled = isset($email['enabled']) ? $email['enabled'] : 'false';
        $to = isset($email['data']['to']) ? $email['data']['to'] : 'Not set';
        $subject = isset($email['data']['subject']) ? $email['data']['subject'] : 'Not set';
        
        echo "Email #" . ($index + 1) . ":\n";
        echo "  Name: $name\n";
        echo "  Enabled: $enabled\n";
        echo "  To: $to\n";
        echo "  Subject: $subject\n";
        
        // Check if it's an email reminder
        if (isset($email['data']['reminder'])) {
            echo "  Type: EMAIL REMINDER\n";
            echo "  Reminder enabled: " . $email['data']['reminder']['enabled'] . "\n";
            echo "  Time method: " . $email['data']['reminder']['time_method'] . "\n";
            echo "  Fixed time: " . $email['data']['reminder']['time_fixed'] . "\n";
        } else {
            echo "  Type: Regular email\n";
        }
        echo "\n";
    }
} else {
    echo "FAILED: _emails field was not created\n";
    
    // Check form settings for debugging
    $form_settings = get_post_meta($form_id, '_super_form_settings', true);
    $has_reminder = isset($form_settings['email_reminder_1']) && $form_settings['email_reminder_1'] === 'true';
    echo "Debug: email_reminder_1 in settings: " . ($has_reminder ? 'YES' : 'NO') . "\n";
}

echo "\n=== Migration Test Complete ===\n";
?>