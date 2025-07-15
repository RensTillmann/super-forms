<?php
// Check email data for form 8
require_once('/var/www/html/wp-config.php');

$form_id = 8;

// Get current emails meta
$emails = get_post_meta($form_id, '_emails', true);
echo "=== _emails meta ===\n";
if (empty($emails)) {
    echo "EMPTY\n";
} else {
    echo json_encode($emails, JSON_PRETTY_PRINT) . "\n";
}

// Get form settings
$settings = get_post_meta($form_id, '_super_form_settings', true);
if ($settings) {
    $s = json_decode($settings, true);
    echo "\n=== Email reminder settings ===\n";
    echo "email_reminder_amount: " . (isset($s['email_reminder_amount']) ? $s['email_reminder_amount'] : 'not set') . "\n";
    echo "email_reminder_base_date: " . (isset($s['email_reminder_base_date']) ? $s['email_reminder_base_date'] : 'not set') . "\n";
    echo "email_reminder_date_offset: " . (isset($s['email_reminder_date_offset']) ? $s['email_reminder_date_offset'] : 'not set') . "\n";
    echo "email_reminder_time_method: " . (isset($s['email_reminder_time_method']) ? $s['email_reminder_time_method'] : 'not set') . "\n";
    echo "email_reminder_time_fixed: " . (isset($s['email_reminder_time_fixed']) ? $s['email_reminder_time_fixed'] : 'not set') . "\n";
    
    echo "\n=== Admin email settings ===\n";
    echo "send: " . (isset($s['send']) ? $s['send'] : 'not set') . "\n";
    echo "send_to_email: " . (isset($s['send_to_email']) ? $s['send_to_email'] : 'not set') . "\n";
    echo "send_from_email: " . (isset($s['send_from_email']) ? $s['send_from_email'] : 'not set') . "\n";
    
    echo "\n=== Confirm email settings ===\n";
    echo "confirm: " . (isset($s['confirm']) ? $s['confirm'] : 'not set') . "\n";
    echo "confirm_to_email: " . (isset($s['confirm_to_email']) ? $s['confirm_to_email'] : 'not set') . "\n";
    echo "confirm_from_email: " . (isset($s['confirm_from_email']) ? $s['confirm_from_email'] : 'not set') . "\n";
}
?>