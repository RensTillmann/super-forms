<?php
/**
 * Import a single Super Form - simplified version for testing
 * Sets global variable $FORM_FILE_TO_IMPORT before including
 */

if (!defined('WP_CLI') && !defined('ABSPATH')) {
    die('This script must be run through WP-CLI or WordPress');
}

function import_super_form_direct($json_file) {
    // Read the JSON file
    if (!file_exists($json_file)) {
        return array('success' => false, 'error' => "Form file not found: $json_file");
    }
    
    $form_data = json_decode(file_get_contents($json_file), true);
    if (!$form_data) {
        return array('success' => false, 'error' => "Invalid JSON in file: $json_file");
    }
    
    // Create the post
    $post_data = array(
        'post_title' => $form_data['title'],
        'post_content' => $form_data['content'] ?: '',
        'post_status' => 'publish',
        'post_type' => 'super_form',
        'post_author' => 1, // Admin user
        'post_date' => $form_data['date']
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        return array('success' => false, 'error' => "Failed to create form post: " . $post_id->get_error_message());
    }
    
    // Add form settings (PHP serialized - unserialize first to avoid double-serialization)
    if (!empty($form_data['settings'])) {
        $settings = $form_data['settings'];
        if (is_string($settings)) {
            // Unserialize if it's a PHP serialized string from XML export
            $settings = maybe_unserialize($settings);
        }
        update_post_meta($post_id, '_super_form_settings', $settings);
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
    }
    
    // Handle form version for email migration logic
    if (isset($form_data['version']) && !empty($form_data['version'])) {
        // Version exists in export data - use the actual version
        update_post_meta($post_id, '_super_version', $form_data['version']);
        echo "Using export version: {$form_data['version']}\n";
    } else {
        // No version in export data - this is a legacy export that needs migration
        // Set to 6.3.999 to trigger migration logic (< 6.4)
        update_post_meta($post_id, '_super_version', '6.3.999');
        echo "Legacy export detected - setting version to 6.3.999 to trigger email migration\n";
    }
    
    return array(
        'success' => true,
        'post_id' => $post_id,
        'original_id' => $form_data['id'],
        'title' => $form_data['title'],
        'message' => 'Form imported successfully'
    );
}

// Check if form file is set
if (isset($GLOBALS['FORM_FILE_TO_IMPORT'])) {
    $result = import_super_form_direct($GLOBALS['FORM_FILE_TO_IMPORT']);
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo json_encode(array('success' => false, 'error' => 'No form file specified'), JSON_PRETTY_PRINT);
}