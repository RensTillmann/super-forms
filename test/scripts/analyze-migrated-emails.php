<?php
// Analyze the migrated emails for form 8

require_once('/var/www/html/wp-config.php');

$form_id = 8;

echo "=== Analyzing Migrated Emails for Form $form_id ===\n\n";

// Get the migrated emails
$emails = get_post_meta($form_id, '_emails', true);

if (empty($emails)) {
    echo "ERROR: No _emails field found!\n";
    exit(1);
}

echo "Total emails found: " . count($emails) . "\n\n";

foreach ($emails as $index => $email) {
    echo "Email #" . ($index + 1) . ":\n";
    echo "  Enabled: " . (isset($email['enabled']) ? $email['enabled'] : 'NOT SET') . "\n";
    echo "  Name: " . (isset($email['name']) ? $email['name'] : 'NOT SET') . "\n";
    
    if (isset($email['data'])) {
        echo "  To: " . (isset($email['data']['to']) ? $email['data']['to'] : 'NOT SET') . "\n";
        echo "  Subject: " . (isset($email['data']['subject']) ? $email['data']['subject'] : 'NOT SET') . "\n";
        
        // Check if it has reminder data
        if (isset($email['data']['reminder'])) {
            echo "  TYPE: EMAIL REMINDER ✅\n";
            echo "  Reminder enabled: " . $email['data']['reminder']['enabled'] . "\n";
            echo "  Base date: " . (isset($email['data']['reminder']['base_date']) ? $email['data']['reminder']['base_date'] : 'NOT SET') . "\n";
            echo "  Date offset: " . (isset($email['data']['reminder']['date_offset']) ? $email['data']['reminder']['date_offset'] : 'NOT SET') . "\n";
            echo "  Time method: " . (isset($email['data']['reminder']['time_method']) ? $email['data']['reminder']['time_method'] : 'NOT SET') . "\n";
            echo "  Fixed time: " . (isset($email['data']['reminder']['time_fixed']) ? $email['data']['reminder']['time_fixed'] : 'NOT SET') . "\n";
        } else {
            echo "  TYPE: Regular email\n";
        }
    }
    echo "\n";
}

// Check which email should have the schedule enabled
$reminderEmails = array_filter($emails, function($email) {
    return isset($email['data']['reminder']);
});

echo "Email reminders found: " . count($reminderEmails) . "\n";

if (count($reminderEmails) > 0) {
    echo "\nPROBLEM: The UI should show 'Enable scheduled execution' as CHECKED for email reminder!\n";
    echo "The migration created the reminder data but the UI is not reading it correctly.\n";
} else {
    echo "\nPROBLEM: No email reminders found in migrated data!\n";
}

echo "\n=== Analysis Complete ===\n";
?>