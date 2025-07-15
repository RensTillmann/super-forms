<?php
// Debug migration trigger for form 7

require_once('/var/www/html/wp-config.php');

echo "=== Debugging Migration Trigger ===\n";

$form_id = 7;

// Get raw form settings
$form_settings = get_post_meta($form_id, '_super_form_settings', true);
echo "Form settings type: " . gettype($form_settings) . "\n";
echo "Form settings empty: " . (empty($form_settings) ? 'YES' : 'NO') . "\n";

if (!empty($form_settings)) {
    echo "Total settings keys: " . count($form_settings) . "\n";
    
    // Check for email reminder keys
    $reminder_keys = array();
    foreach ($form_settings as $key => $value) {
        if (strpos($key, 'email_reminder') !== false) {
            $reminder_keys[$key] = $value;
        }
    }
    
    echo "Email reminder keys found: " . count($reminder_keys) . "\n";
    if (!empty($reminder_keys)) {
        foreach ($reminder_keys as $key => $value) {
            echo "  $key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
        }
    }
    
    // Check version and migration condition
    $form_version = get_post_meta($form_id, '_super_version', true);
    echo "\nForm version: " . ($form_version ? $form_version : 'NOT SET') . "\n";
    
    // Check existing _emails field
    $existing_emails = get_post_meta($form_id, '_emails', true);
    echo "Existing _emails field: " . (empty($existing_emails) ? 'EMPTY' : 'EXISTS') . "\n";
    
    // Simulate migration condition check
    $needs_email_migration = empty($existing_emails) && ( ! empty( $form_settings['send'] ) || ! empty( $form_settings['confirm'] ) );
    echo "Needs basic email migration: " . ($needs_email_migration ? 'YES' : 'NO') . "\n";
    
    // Check if admin/confirmation emails exist
    echo "Admin email (send): " . (isset($form_settings['send']) ? $form_settings['send'] : 'NOT SET') . "\n";
    echo "Confirmation email (confirm): " . (isset($form_settings['confirm']) ? $form_settings['confirm'] : 'NOT SET') . "\n";
    
    // Check version comparison
    $current_form_version = $form_version ? $form_version : '0';
    $version_check = version_compare($current_form_version, '6.4', '<');
    echo "Version < 6.4: " . ($version_check ? 'YES' : 'NO') . "\n";
    
    // Final migration trigger condition
    $should_migrate = $version_check || $needs_email_migration;
    echo "\nShould trigger migration: " . ($should_migrate ? 'YES' : 'NO') . "\n";
    
} else {
    echo "Form settings are empty or not found!\n";
}

echo "\n=== Debug Complete ===\n";
?>