<?php
/**
 * Test actual form functionality - not just import
 */

if (!defined('WP_CLI') && !defined('ABSPATH')) {
    die('This script must be run through WP-CLI or WordPress');
}

function test_form_functionality($form_id) {
    $result = array(
        'form_id' => $form_id,
        'import_success' => false,
        'builder_page_loads' => false,
        'elements_render' => false,
        'settings_accessible' => false,
        'frontend_renders' => false,
        'errors' => array(),
        'warnings' => array(),
        'form_analysis' => array()
    );
    
    try {
        // 1. Check if form exists in database
        $post = get_post($form_id);
        if (!$post || $post->post_type !== 'super_form') {
            $result['errors'][] = 'Form not found in database';
            return $result;
        }
        
        $result['import_success'] = true;
        $result['form_analysis']['title'] = $post->post_title;
        $result['form_analysis']['date'] = $post->post_date;
        
        // 2. Check form settings
        $settings = get_post_meta($form_id, '_super_form_settings', true);
        if (empty($settings)) {
            $result['warnings'][] = 'No form settings found';
        } else {
            $result['settings_accessible'] = true;
            // Try to unserialize settings
            $unserialized = unserialize($settings);
            if ($unserialized === false && $settings !== 'b:0;') {
                $result['errors'][] = 'Form settings corrupted - cannot unserialize';
            } else {
                $result['form_analysis']['settings_count'] = is_array($unserialized) ? count($unserialized) : 0;
            }
        }
        
        // 3. Check form elements
        $elements = get_post_meta($form_id, '_super_elements', true);
        if (empty($elements)) {
            $result['warnings'][] = 'No form elements found';
        } else {
            $elements_array = json_decode($elements, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['errors'][] = 'Form elements JSON is invalid: ' . json_last_error_msg();
            } else {
                $result['elements_render'] = true;
                $result['form_analysis']['elements_count'] = count($elements_array);
                
                // Analyze element types
                $element_types = array();
                $complex_features = array();
                
                function analyze_elements($elements, &$element_types, &$complex_features) {
                    foreach ($elements as $element) {
                        if (isset($element['tag'])) {
                            $element_types[$element['tag']] = ($element_types[$element['tag']] ?? 0) + 1;
                            
                            // Check for complex features
                            if ($element['tag'] === 'calculator') {
                                $complex_features[] = 'calculator';
                            }
                            if ($element['tag'] === 'multipart') {
                                $complex_features[] = 'multipart';
                            }
                            if (isset($element['data']['conditional_action']) && $element['data']['conditional_action'] !== 'disabled') {
                                $complex_features[] = 'conditional_logic';
                            }
                        }
                        
                        // Check nested elements
                        if (isset($element['inner']) && is_array($element['inner'])) {
                            analyze_elements($element['inner'], $element_types, $complex_features);
                        }
                    }
                }
                
                analyze_elements($elements_array, $element_types, $complex_features);
                $result['form_analysis']['element_types'] = $element_types;
                $result['form_analysis']['complex_features'] = array_unique($complex_features);
            }
        }
        
        // 4. Test if Super Forms classes are available for builder page
        if (!class_exists('SUPER_Forms')) {
            $result['errors'][] = 'SUPER_Forms class not available';
        } else {
            $result['builder_page_loads'] = true;
        }
        
        // 5. Test shortcode rendering (basic test)
        if ($result['elements_render']) {
            $shortcode_output = do_shortcode("[super_form id=\"$form_id\"]");
            if (!empty($shortcode_output) && strpos($shortcode_output, 'super-form') !== false) {
                $result['frontend_renders'] = true;
            } else {
                $result['warnings'][] = 'Shortcode may not render correctly';
            }
        }
        
        // 6. Check for specific integrations in settings
        if ($result['settings_accessible'] && isset($unserialized)) {
            $integrations = array();
            foreach ($unserialized as $key => $value) {
                if (strpos($key, 'paypal') !== false && !empty($value)) {
                    $integrations[] = 'paypal';
                }
                if (strpos($key, 'stripe') !== false && !empty($value)) {
                    $integrations[] = 'stripe';
                }
                if (strpos($key, 'mailchimp') !== false && !empty($value)) {
                    $integrations[] = 'mailchimp';
                }
                if (strpos($key, 'woocommerce') !== false && !empty($value)) {
                    $integrations[] = 'woocommerce';
                }
            }
            $result['form_analysis']['integrations'] = $integrations;
        }
        
    } catch (Exception $e) {
        $result['errors'][] = 'Exception during testing: ' . $e->getMessage();
    } catch (Error $e) {
        $result['errors'][] = 'Fatal error during testing: ' . $e->getMessage();
    }
    
    return $result;
}

// Test the form if form ID is provided
if (isset($GLOBALS['FORM_ID_TO_TEST'])) {
    $result = test_form_functionality($GLOBALS['FORM_ID_TO_TEST']);
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo json_encode(array('error' => 'No form ID specified'), JSON_PRETTY_PRINT);
}