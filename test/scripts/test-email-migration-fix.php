<?php
/**
 * Test the email migration fix by re-importing form 71883
 * 
 * This script will:
 * 1. Delete the existing imported form 209
 * 2. Re-import form 71883 with the version fix
 * 3. Check if email settings are properly migrated
 */

if (!defined('WP_CLI') && !defined('ABSPATH')) {
    die('This script must be run through WP-CLI or WordPress');
}

function test_email_migration_fix() {
    echo "=== TESTING EMAIL MIGRATION FIX ===\n";
    
    // Step 1: Delete existing form 209 (Loan Pre-Qualification)
    echo "Step 1: Deleting existing form 209...\n";
    $existing_posts = get_posts(array(
        'post_type' => 'super_form',
        'meta_query' => array(
            array(
                'key' => '_super_form_id',
                'value' => '71883',
                'compare' => '='
            )
        ),
        'numberposts' => -1
    ));
    
    foreach ($existing_posts as $post) {
        wp_delete_post($post->ID, true); // Force delete
        echo "Deleted post ID: {$post->ID}\n";
    }
    
    // Step 2: Re-import form 71883 with version fix
    echo "Step 2: Re-importing form 71883 with version fix...\n";
    
    // Include the import function
    include_once('/scripts/import-single-form.php');
    
    $form_file = '/scripts/../exports/original/form_71883.json';
    $result = import_super_form_direct($form_file);
    
    if ($result['success']) {
        $new_post_id = $result['post_id'];
        echo "Successfully imported form as Post ID: {$new_post_id}\n";
        
        // Step 3: Check the version that was set
        $version = get_post_meta($new_post_id, '_super_version', true);
        echo "Form version set to: {$version}\n";
        
        // Step 4: Check if form settings exist and trigger migration
        echo "Step 4: Triggering settings retrieval to test migration...\n";
        
        // This should trigger the migration logic in get_form_settings()
        $form_settings = get_post_meta($new_post_id, '_super_form_settings', true);
        
        if (!empty($form_settings)) {
            echo "Found legacy form settings, migration should trigger...\n";
            
            // Force load the settings which should trigger migration
            if (class_exists('SUPER_Common')) {
                $migrated_settings = SUPER_Common::get_form_settings($new_post_id);
                echo "Settings retrieval completed - migration should have run\n";
                
                // Check if email triggers were created
                $triggers = get_post_meta($new_post_id, '_super_form_triggers', true);
                if (!empty($triggers)) {
                    echo "Triggers found: " . count($triggers) . " triggers created\n";
                    
                    // Look for email triggers
                    $email_triggers = 0;
                    foreach ($triggers as $trigger) {
                        if (isset($trigger['actions'])) {
                            foreach ($trigger['actions'] as $action) {
                                if (isset($action['action']) && $action['action'] === 'send_email') {
                                    $email_triggers++;
                                    echo "Found email trigger: {$trigger['name']}\n";
                                    if (isset($action['data']['from_email'])) {
                                        echo "  From email: {$action['data']['from_email']}\n";
                                    }
                                    if (isset($action['data']['subject'])) {
                                        echo "  Subject: {$action['data']['subject']}\n";
                                    }
                                }
                            }
                        }
                    }
                    echo "Total email triggers found: {$email_triggers}\n";
                } else {
                    echo "No triggers found - migration may not have run\n";
                }
            }
        } else {
            echo "No legacy form settings found\n";
        }
        
        echo "=== TEST COMPLETED ===\n";
        echo "New form ID: {$new_post_id}\n";
        echo "You can now test the form builder at: wp-admin/admin.php?page=super_create_form&id={$new_post_id}\n";
        
        return array(
            'success' => true,
            'new_post_id' => $new_post_id,
            'version' => $version
        );
        
    } else {
        echo "Import failed: {$result['error']}\n";
        return array('success' => false, 'error' => $result['error']);
    }
}

// Execute the test
$test_result = test_email_migration_fix();
echo json_encode($test_result, JSON_PRETTY_PRINT);
?>