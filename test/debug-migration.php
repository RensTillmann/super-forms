<?php
require_once('/var/www/html/wp-config.php');

$form_id = 8;
$s = SUPER_Common::get_form_settings($form_id);
$existing_emails = get_post_meta($form_id, '_emails', true);
$has_reminder_settings = ! empty($s['email_reminder_amount']) && intval($s['email_reminder_amount']) > 0;

echo "existing_emails empty: " . (empty($existing_emails) ? "true" : "false") . "\n";
echo "email_reminder_amount: " . (isset($s['email_reminder_amount']) ? $s['email_reminder_amount'] : "not set") . "\n";
echo "has_reminder_settings: " . ($has_reminder_settings ? "true" : "false") . "\n";
echo "Migration condition: " . ((empty($existing_emails) && $has_reminder_settings) ? "true" : "false") . "\n";
?>