<?php
/**
 * Import Super Form from JSON file
 * Usage: wp eval-file import-form.php form_id.json
 */

if (!defined('WP_CLI') && !defined('ABSPATH')) {
    die('This script must be run through WP-CLI or WordPress');
}

function import_super_form($json_file) {
    // Read the JSON file
    if (!file_exists($json_file)) {
        WP_CLI::error("Form file not found: $json_file");
        return false;
    }
    
    $form_data = json_decode(file_get_contents($json_file), true);
    if (!$form_data) {
        WP_CLI::error("Invalid JSON in file: $json_file");
        return false;
    }
    
    WP_CLI::log("Importing form: {$form_data['title']} (ID: {$form_data['id']})");
    
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
        WP_CLI::error("Failed to create form post: " . $post_id->get_error_message());
        return false;
    }
    
    // Add form settings (PHP serialized)
    if (!empty($form_data['settings'])) {
        update_post_meta($post_id, '_super_form_settings', $form_data['settings']);
        WP_CLI::log("✓ Settings imported");
    }
    
    // Add form elements (JSON)
    if (!empty($form_data['elements'])) {
        update_post_meta($post_id, '_super_elements', json_encode($form_data['elements']));
        WP_CLI::log("✓ Elements imported");
    }
    
    WP_CLI::success("Form imported successfully with ID: $post_id");
    
    // Validate the import
    $validation_result = validate_form_import($post_id, $form_data);
    
    return array(
        'post_id' => $post_id,
        'original_id' => $form_data['id'],
        'title' => $form_data['title'],
        'validation' => $validation_result
    );
}

function validate_form_import($post_id, $original_data) {
    $validation = array(
        'success' => true,
        'errors' => array(),
        'warnings' => array()
    );
    
    // Check if post exists
    $post = get_post($post_id);
    if (!$post) {
        $validation['success'] = false;
        $validation['errors'][] = 'Form post not found after import';
        return $validation;
    }
    
    // Check settings
    $settings = get_post_meta($post_id, '_super_form_settings', true);
    if (empty($settings) && !empty($original_data['settings'])) {
        $validation['success'] = false;
        $validation['errors'][] = 'Form settings not imported';
    }
    
    // Check elements
    $elements = get_post_meta($post_id, '_super_elements', true);
    if (empty($elements) && !empty($original_data['elements'])) {
        $validation['success'] = false;
        $validation['errors'][] = 'Form elements not imported';
    } else {
        // Validate elements structure
        $elements_array = json_decode($elements, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $validation['success'] = false;
            $validation['errors'][] = 'Form elements JSON is invalid';
        } else {
            $original_count = count($original_data['elements']);
            $imported_count = count($elements_array);
            if ($original_count !== $imported_count) {
                $validation['warnings'][] = "Element count mismatch: original=$original_count, imported=$imported_count";
            }
        }
    }
    
    // Check if form renders without errors
    try {
        $shortcode_output = do_shortcode("[super_form id=\"$post_id\"]");
        if (empty($shortcode_output) || strpos($shortcode_output, 'error') !== false) {
            $validation['warnings'][] = 'Form may not render correctly';
        }
    } catch (Exception $e) {
        $validation['warnings'][] = 'Form rendering test failed: ' . $e->getMessage();
    }
    
    return $validation;
}

// If running directly via WP-CLI
if (defined('WP_CLI') && WP_CLI) {
    $args = $GLOBALS['argv'] ?? array();
    if (count($args) > 1) {
        $json_file = $args[1];
        $result = import_super_form($json_file);
        if ($result) {
            WP_CLI::log(json_encode($result, JSON_PRETTY_PRINT));
        }
    } else {
        WP_CLI::error('Usage: wp eval-file import-form.php form_file.json');
    }
}