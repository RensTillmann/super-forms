#!/usr/bin/env php
<?php
/**
 * Debug Form 8 Loading Issue
 */

// Check if we have WordPress
if (!function_exists('get_post')) {
    die("Run this with WP-CLI: wp eval-file /scripts/debug-form8-loading.php\n");
}

echo "=== DEBUGGING FORM 8 LOADING ISSUE ===\n\n";

// Get form 8
$form = get_post(8);
if (!$form) {
    echo "Form 8 not found!\n";
    exit(1);
}

echo "Form Title: " . $form->post_title . "\n";
echo "Form Status: " . $form->post_status . "\n";
echo "Form Type: " . $form->post_type . "\n\n";

// Check settings
echo "Checking form settings...\n";
$settings = get_post_meta(8, '_super_form_settings', true);

if ($settings === false) {
    echo "No settings found!\n";
} else {
    echo "Settings type: " . gettype($settings) . "\n";
    
    if (is_string($settings)) {
        echo "Settings is still serialized - this might be the issue!\n";
        echo "Serialized length: " . strlen($settings) . "\n";
        
        // Try to unserialize
        $unserialized = @unserialize($settings);
        if ($unserialized === false) {
            echo "ERROR: Cannot unserialize settings - data is corrupted!\n";
            
            // Check for common corruption patterns
            if (strpos($settings, 'n | = 0') !== false) {
                echo "Found corrupted JavaScript pattern in settings!\n";
            }
        } else {
            echo "Settings can be unserialized.\n";
            echo "Total settings keys: " . count($unserialized) . "\n";
        }
    } elseif (is_array($settings)) {
        echo "Settings already unserialized.\n";
        echo "Total settings keys: " . count($settings) . "\n";
    }
}

// Check elements
echo "\nChecking form elements...\n";
$elements = get_post_meta(8, '_super_elements', true);

if ($elements === false) {
    echo "No elements found!\n";
} else {
    echo "Elements type: " . gettype($elements) . "\n";
    
    if (is_string($elements)) {
        echo "Elements is still serialized.\n";
        echo "Serialized length: " . strlen($elements) . "\n";
        
        // Try to unserialize
        $unserialized = @unserialize($elements);
        if ($unserialized === false) {
            echo "ERROR: Cannot unserialize elements - data is corrupted!\n";
        } else {
            echo "Elements can be unserialized.\n";
            if (is_array($unserialized)) {
                echo "Total elements: " . count($unserialized) . "\n";
            }
        }
    } elseif (is_array($elements)) {
        echo "Elements already unserialized.\n";
        echo "Total elements: " . count($elements) . "\n";
    }
}

// Check for wp_localize_script data
echo "\nChecking for potential wp_localize_script issues...\n";

// Simulate what happens when the form builder loads
if (is_array($settings)) {
    $json_settings = json_encode($settings);
    if ($json_settings === false) {
        echo "ERROR: Cannot JSON encode settings - " . json_last_error_msg() . "\n";
        
        // Find problematic values
        foreach ($settings as $key => $value) {
            $test = @json_encode(array($key => $value));
            if ($test === false) {
                echo "  Problematic key: $key\n";
                if (is_string($value)) {
                    echo "  Value contains invalid UTF-8 or other encoding issue\n";
                }
            }
        }
    } else {
        echo "Settings can be JSON encoded successfully.\n";
        echo "JSON length: " . strlen($json_settings) . "\n";
        
        // Check for the specific JavaScript error pattern
        if (strpos($json_settings, 'n | = 0') !== false) {
            echo "WARNING: Found corrupted JavaScript pattern in JSON!\n";
        }
    }
}

// Memory check
echo "\nMemory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "Peak memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. The issue appears to be server-side data corruption\n";
echo "2. Form 8 may have corrupted serialized data\n";
echo "3. Try re-importing form 8 with fresh data\n";
echo "4. Or manually fix the corrupted data\n";

echo "\nDone.\n";