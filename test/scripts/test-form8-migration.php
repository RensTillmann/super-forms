<?php
// Test migration for form 8 with email reminders

require_once('/var/www/html/wp-config.php');

echo "=== Testing Form 8 Migration ===\n";

$form_id = 8;

// Check initial state
$existing_emails = get_post_meta($form_id, '_emails', true);
echo "Initial _emails field: " . (empty($existing_emails) ? 'EMPTY (good)' : 'EXISTS') . "\n";

// Verify settings before migration
$form_settings = get_post_meta($form_id, '_super_form_settings', true);
$has_reminder = isset($form_settings['email_reminder_1']) && $form_settings['email_reminder_1'] === 'true';
$has_send = isset($form_settings['send']) && $form_settings['send'] === 'yes';
$has_confirm = isset($form_settings['confirm']) && $form_settings['confirm'] === 'yes';

echo "Before migration:\n";
echo "  Email reminder 1: " . ($has_reminder ? 'YES' : 'NO') . "\n";
echo "  Admin email: " . ($has_send ? 'YES' : 'NO') . "\n";
echo "  Confirmation email: " . ($has_confirm ? 'YES' : 'NO') . "\n";

$form_version = get_post_meta($form_id, '_super_version', true);
echo "  Form version: $form_version\n\n";

// Trigger migration by loading form settings
echo "Triggering migration...\n";
$settings = SUPER_Common::get_form_settings($form_id);
echo "Migration triggered successfully\n\n";

// Check results
$migrated_emails = get_post_meta($form_id, '_emails', true);

if (!empty($migrated_emails)) {
    echo "SUCCESS! _emails field created with " . count($migrated_emails) . " email(s)\n\n";
    
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
            echo "  Type: EMAIL REMINDER ✅\n";
            echo "  Reminder enabled: " . $email['data']['reminder']['enabled'] . "\n";
            if (isset($email['data']['reminder']['time_method'])) {
                echo "  Time method: " . $email['data']['reminder']['time_method'] . "\n";
            }
            if (isset($email['data']['reminder']['time_fixed'])) {
                echo "  Fixed time: " . $email['data']['reminder']['time_fixed'] . "\n";
            }
        } else {
            echo "  Type: Regular email\n";
        }
        echo "\n";
    }
    
    // Check for specific types
    $admin_emails = array_filter($migrated_emails, function($email) {
        return isset($email['name']) && $email['name'] === 'Admin E-mail';
    });
    $confirmation_emails = array_filter($migrated_emails, function($email) {
        return isset($email['name']) && $email['name'] === 'Confirmation E-mail';
    });
    $reminder_emails = array_filter($migrated_emails, function($email) {
        return isset($email['name']) && strpos($email['name'], 'Email Reminder') !== false;
    });
    
    echo "Summary:\n";
    echo "  Admin emails: " . count($admin_emails) . "\n";
    echo "  Confirmation emails: " . count($confirmation_emails) . "\n";
    echo "  Email reminders: " . count($reminder_emails) . "\n";
    
} else {
    echo "FAILED: _emails field was not created\n";
}

echo "\n=== Migration Test Complete ===\n";
?>