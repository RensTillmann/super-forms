#!/usr/bin/env php
<?php
/**
 * Fix Form 8 by creating it with valid data
 */

if (!function_exists('update_post_meta')) {
    die("Run this with WP-CLI: wp eval-file /scripts/fix-form8.php\n");
}

echo "=== FIXING FORM 8 ===\n\n";

// Delete the corrupted form 8
$old_form = get_post(8);
if ($old_form) {
    wp_delete_post(8, true);
    echo "Deleted corrupted form 8\n";
}

// Create new form 8 with the popup add-on to test our JavaScript fix
$post_data = array(
    'import_id' => 8, // Force ID to be 8
    'post_title' => 'Test Form 8 - Popup Features',
    'post_type' => 'super_form',
    'post_status' => 'publish',
    'post_author' => 1
);

$post_id = wp_insert_post($post_data);

if (is_wp_error($post_id) || $post_id != 8) {
    // Try alternative approach
    global $wpdb;
    $wpdb->insert(
        $wpdb->posts,
        array(
            'ID' => 8,
            'post_title' => 'Test Form 8 - Popup Features',
            'post_type' => 'super_form',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1)
        )
    );
    $post_id = 8;
}

echo "Created new form with ID: $post_id\n";

// Basic form elements
$elements = array(
    array(
        'tag' => 'text',
        'group' => 'form_elements',
        'data' => array(
            'name' => 'first_name',
            'email' => 'First Name:',
            'placeholder' => 'Enter your first name',
            'validation' => 'empty',
            'icon' => 'user'
        )
    ),
    array(
        'tag' => 'text',
        'group' => 'form_elements',
        'data' => array(
            'name' => 'email',
            'email' => 'Email:',
            'placeholder' => 'your@email.com',
            'validation' => 'email',
            'icon' => 'envelope'
        )
    ),
    array(
        'tag' => 'textarea',
        'group' => 'form_elements',
        'data' => array(
            'name' => 'message',
            'email' => 'Message:',
            'placeholder' => 'Enter your message...',
            'validation' => 'empty'
        )
    )
);

// Settings with popup enabled to test the JavaScript fix
$settings = array(
    // Basic settings
    'theme_style' => 'default',
    'theme_field_size' => 'medium',
    'theme_icon_position' => 'outside',
    'theme_icon_align' => 'left',
    
    // Email settings
    'send' => 'yes',
    'header_to' => '{option_admin_email}',
    'header_from' => 'no-reply@{option_siteurl}',
    'header_from_name' => '{option_blogname}',
    'header_subject' => 'New form submission from {field_first_name}',
    'email_body' => '<table>{loop_fields}</table>',
    
    // Popup settings - this will test our css-plugin.js fix
    'popup_enabled' => 'true',
    'popup_page_load' => 'true',
    'popup_logged_in' => 'true',
    'popup_not_logged_in' => 'true',
    'popup_seconds' => '0',
    'popup_scrolled' => '0',
    'popup_width' => '600',
    'popup_background_color' => '#ffffff',
    'popup_overlay_color' => '#000000',
    'popup_overlay_opacity' => '0.7',
    'popup_close_btn' => 'true',
    'popup_close_btn_bg_color' => '#00bc65',
    'popup_close_btn_icon_color' => '#ffffff',
    
    // Animation settings that might use GSAP/css-plugin.js
    'popup_slide' => 'none',
    'popup_fade_duration' => '300',
    'popup_slide_duration' => '300',
    
    // Form settings
    'form_hide_after_submitting' => '',
    'form_clear_after_submitting' => '',
    'form_show_thanks_msg' => 'true',
    'form_thanks_title' => 'Thank you!',
    'form_thanks_description' => 'We will get back to you soon.',
    
    // Conditional logic test
    'conditional_logic_enabled' => 'true'
);

// Save the data
update_post_meta(8, '_super_elements', $elements);
update_post_meta(8, '_super_form_settings', $settings);
update_post_meta(8, '_super_version', '6.4.110'); // Current version

echo "✓ Form 8 has been fixed with valid data\n";
echo "✓ Popup features enabled to test css-plugin.js\n";
echo "✓ Basic elements added\n";
echo "✓ Email settings configured\n";

// Verify the fix
$test_settings = get_post_meta(8, '_super_form_settings', true);
$test_elements = get_post_meta(8, '_super_elements', true);

echo "\nVerification:\n";
echo "- Settings saved: " . (is_array($test_settings) ? 'Yes' : 'No') . "\n";
echo "- Elements saved: " . (is_array($test_elements) ? 'Yes' : 'No') . "\n";
echo "- Popup enabled: " . (isset($test_settings['popup_enabled']) ? $test_settings['popup_enabled'] : 'No') . "\n";

echo "\n=== READY FOR TESTING ===\n";
echo "1. Go to http://localhost:8080/wp-admin/\n";
echo "2. Edit Form 8\n";
echo "3. Check browser console for JavaScript errors\n";
echo "4. The form should load without hanging\n";

echo "\nDone!\n";