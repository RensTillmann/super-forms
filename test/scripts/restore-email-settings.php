<?php
// Restore legacy email settings to trigger migration for form 71883

require_once('/var/www/html/wp-config.php');

$form_id = 71883;

// Get current form settings
$current_settings = get_post_meta($form_id, '_super_form_settings', true);
if (!$current_settings) {
    $current_settings = array();
}

// Add legacy email settings back temporarily
$legacy_email_settings = array(
    'send' => 'yes',
    'header_to' => 'wisconsinhardmoney@gmail.com, info.wisconsinhardmoney@gmail.com, michelle.wisconsinhardmoney@gmail.com',
    'header_subject' => 'Loan Pre-Qualification',
    'header_from_type' => 'default',
    'header_reply_enabled' => '',
    'email_body' => 'The following information has been sent by the submitter:

<table cellpadding="5">{loop_fields}</table>

Best regards, {option_blogname}',
    'email_loop' => '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>',
    'email_exclude_empty' => '',
    'email_rtl' => '',
    'header_cc' => '',
    'header_bcc' => '',
    'header_additional' => '',
    'admin_attachments' => '',
    'confirm' => 'yes',
    'confirm_to' => '{email}',
    'confirm_subject' => 'Thank you!',
    'confirm_from_type' => 'default',
    'confirm_header_reply_enabled' => '',
    'confirm_body' => 'Thank you for contacting us!

<table cellpadding="5">{loop_fields}</table>

We will be in contact with you for the next steps within 24 hours.

Best Regards, {option_blogname}',
    'confirm_email_loop' => '<tr><th valign="top" align="right">{loop_label}</th><td>{loop_value}</td></tr>',
    'confirm_exclude_empty' => '',
    'confirm_rtl' => '',
    'confirm_header_cc' => '',
    'confirm_header_bcc' => '',
    'confirm_header_additional' => '',
    'confirm_attachments' => '',
);

// Merge legacy settings with current settings
$updated_settings = array_merge($current_settings, $legacy_email_settings);

// Update the form settings
update_post_meta($form_id, '_super_form_settings', $updated_settings);

echo "Legacy email settings restored for form $form_id\n";
echo "Settings restored:\n";
foreach ($legacy_email_settings as $key => $value) {
    echo "- $key: $value\n";
}
?>