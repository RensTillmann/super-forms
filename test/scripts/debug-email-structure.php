<?php
// Debug email structure - what UI expects vs what migration provides

require_once('/var/www/html/wp-config.php');

$form_id = 8;

echo "=== Email Structure Debug ===\n\n";

// Get the migrated emails
$emails = get_post_meta($form_id, '_emails', true);

echo "Current migrated structure:\n";
echo json_encode($emails, JSON_PRETTY_PRINT) . "\n\n";

// Check what the UI function actually returns
$ui_emails = SUPER_Common::get_form_emails_settings($form_id);

echo "UI function returns:\n";
echo json_encode($ui_emails, JSON_PRETTY_PRINT) . "\n\n";

// Check specifically the third email (Email Reminder #1)
if (isset($emails[2])) {
    $reminderEmail = $emails[2];
    echo "Email Reminder #1 structure:\n";
    echo json_encode($reminderEmail, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Expected UI structure for schedule:\n";
    echo "- schedule.enabled should be: true\n";
    echo "- schedule.schedules should be an array with items containing:\n";
    echo "  - date: base_date\n";
    echo "  - days: date_offset\n";
    echo "  - method: time_method\n";
    echo "  - time: time_fixed\n\n";
    
    if (isset($reminderEmail['data']['reminder'])) {
        $reminder = $reminderEmail['data']['reminder'];
        echo "Current reminder data:\n";
        foreach ($reminder as $key => $value) {
            echo "  $key: $value\n";
        }
        
        echo "\nPROBLEM: The migrated reminder data is stored under 'reminder' but UI expects it under 'schedule'\n";
        echo "SOLUTION: Need to map reminder data to schedule structure during migration\n";
    }
}

echo "\n=== Debug Complete ===\n";
?>