<?php
// Import form 71952 for email migration testing

require_once('/var/www/html/wp-config.php');

$form_file = '/scripts/../exports/original/form_71952.json';

if (!file_exists($form_file)) {
    echo "Form file not found: $form_file\n";
    exit(1);
}

$form_data = json_decode(file_get_contents($form_file), true);
if (!$form_data) {
    echo "Failed to parse form data\n";
    exit(1);
}

echo "Importing form: " . $form_data['title'] . " (ID: " . $form_data['id'] . ")\n";

// Create WordPress post (remove ID to let WordPress auto-assign)
$post_data = array(
    'post_title' => $form_data['title'],
    'post_type'  => 'super_form',
    'post_status' => 'publish',
    'post_content' => '',
    'post_date' => $form_data['date'],
    'post_author' => 1
);

// Try to insert the post
$post_id = wp_insert_post($post_data);

if (is_wp_error($post_id)) {
    echo "Failed to create post: " . $post_id->get_error_message() . "\n";
    exit(1);
}

echo "Created post with ID: $post_id\n";

// Add form settings (PHP serialized - unserialize first to avoid double-serialization)
if (!empty($form_data['settings'])) {
    $settings = $form_data['settings'];
    if (is_string($settings)) {
        // Try to unserialize if it's a PHP serialized string from XML export
        $unserialized = maybe_unserialize($settings);
        if ($unserialized !== $settings) {
            // It was serialized, use the unserialized version
            $settings = $unserialized;
        } else {
            // It's a JSON string, decode it
            $json_decoded = json_decode($settings, true);
            if ($json_decoded !== null) {
                $settings = $json_decoded;
            }
        }
    }
    update_post_meta($post_id, '_super_form_settings', $settings);
    echo "Added form settings\n";
}

// Add form elements (PHP serialized - unserialize first to avoid double-serialization)
if (!empty($form_data['elements'])) {
    $elements = $form_data['elements'];
    if (is_string($elements)) {
        // Try to unserialize if it's a PHP serialized string from XML export
        $unserialized = maybe_unserialize($elements);
        if ($unserialized !== $elements) {
            // It was serialized, use the unserialized version
            $elements = $unserialized;
        } else {
            // It's a JSON string, decode it
            $json_decoded = json_decode($elements, true);
            if ($json_decoded !== null) {
                $elements = $json_decoded;
            }
        }
    }
    update_post_meta($post_id, '_super_elements', $elements);
    echo "Added form elements\n";
}

// Set the Super Forms version to current version to ensure migration logic runs
update_post_meta($post_id, '_super_version', '6.4.110');

echo "Form import completed successfully!\n";
echo "Form ID: $post_id\n";
echo "Title: " . $form_data['title'] . "\n";

// Check if email settings exist
$settings_check = get_post_meta($post_id, '_super_form_settings', true);
$has_send = isset($settings_check['send']) && $settings_check['send'] === 'yes';
$has_confirm = isset($settings_check['confirm']) && $settings_check['confirm'] === 'yes';

echo "Email settings check:\n";
echo "- Admin email (send): " . ($has_send ? 'YES' : 'NO') . "\n";
echo "- Confirmation email (confirm): " . ($has_confirm ? 'YES' : 'NO') . "\n";

if ($has_send) {
    echo "- Admin email subject: " . (isset($settings_check['header_subject']) ? $settings_check['header_subject'] : 'Not set') . "\n";
}
if ($has_confirm) {
    echo "- Confirmation email subject: " . (isset($settings_check['confirm_subject']) ? $settings_check['confirm_subject'] : 'Not set') . "\n";
}
?>