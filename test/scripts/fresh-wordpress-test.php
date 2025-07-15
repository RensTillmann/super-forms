<?php
/**
 * Fresh WordPress Installation Email Migration Test
 * 
 * This script simulates a fresh WordPress installation and tests
 * the email migration functionality step by step.
 */

if (!defined('WP_CLI') && !defined('ABSPATH')) {
    die('This script must be run through WP-CLI or WordPress');
}

function fresh_wordpress_email_test() {
    echo "=== FRESH WORDPRESS EMAIL MIGRATION TEST ===\n";
    
    // Step 1: Clean up existing test data
    echo "Step 1: Cleaning up existing test data...\n";
    cleanup_existing_forms();
    
    // Step 2: Import form 71883 using our corrected import script
    echo "Step 2: Importing Loan Pre-Qualification form...\n";
    $import_result = import_loan_form();
    
    if (!$import_result['success']) {
        echo "Import failed: {$import_result['error']}\n";
        return $import_result;
    }
    
    $form_id = $import_result['post_id'];
    echo "Form imported as Post ID: {$form_id}\n";
    
    // Step 3: Check the version that was set
    $version = get_post_meta($form_id, '_super_version', true);
    echo "Form version: {$version}\n";
    
    // Step 4: Trigger settings retrieval to initiate migration
    echo "Step 3: Triggering email migration by loading form settings...\n";
    
    // This should trigger the migration logic in get_form_settings()
    if (class_exists('SUPER_Common')) {
        echo "Loading form settings to trigger migration...\n";
        $settings = SUPER_Common::get_form_settings($form_id);
        echo "Settings loaded - migration should have triggered\n";
        
        // Step 5: Check if email triggers were created
        echo "Step 4: Checking for migrated email triggers...\n";
        $triggers = get_post_meta($form_id, '_super_form_triggers', true);
        
        if (!empty($triggers)) {
            echo "Found " . count($triggers) . " triggers after migration\n";
            
            // Look for email triggers
            $email_triggers = array();
            foreach ($triggers as $trigger) {
                if (isset($trigger['actions'])) {
                    foreach ($trigger['actions'] as $action) {
                        if (isset($action['action']) && $action['action'] === 'send_email') {
                            $email_triggers[] = array(
                                'name' => $trigger['name'],
                                'from_email' => isset($action['data']['from_email']) ? $action['data']['from_email'] : 'not set',
                                'to' => isset($action['data']['to']) ? $action['data']['to'] : 'not set',
                                'subject' => isset($action['data']['subject']) ? $action['data']['subject'] : 'not set'
                            );
                        }
                    }
                }
            }
            
            echo "Email triggers found: " . count($email_triggers) . "\n";
            foreach ($email_triggers as $i => $trigger) {
                echo "  Trigger " . ($i + 1) . ": {$trigger['name']}\n";
                echo "    From: {$trigger['from_email']}\n";
                echo "    To: {$trigger['to']}\n";
                echo "    Subject: {$trigger['subject']}\n";
            }
            
            // Step 6: Check specific email settings we expect
            echo "Step 5: Verifying expected email settings...\n";
            $expected_admin_recipients = 'wisconsinhardmoney@gmail.com, info.wisconsinhardmoney@gmail.com, michelle.wisconsinhardmoney@gmail.com';
            $expected_admin_subject = 'Loan Pre-Qualification';
            $expected_confirm_subject = 'Thank you!';
            
            $admin_email_found = false;
            $confirm_email_found = false;
            
            foreach ($email_triggers as $trigger) {
                // Check for admin email
                if (strpos($trigger['to'], 'wisconsinhardmoney@gmail.com') !== false) {
                    $admin_email_found = true;
                    echo "✅ Admin email trigger found with correct recipients\n";
                    if ($trigger['subject'] === $expected_admin_subject) {
                        echo "✅ Admin email subject matches: {$trigger['subject']}\n";
                    } else {
                        echo "❌ Admin email subject mismatch. Expected: '{$expected_admin_subject}', Got: '{$trigger['subject']}'\n";
                    }
                }
                
                // Check for confirmation email  
                if ($trigger['to'] === '{email}' || strpos($trigger['name'], 'confirmation') !== false) {
                    $confirm_email_found = true;
                    echo "✅ Confirmation email trigger found\n";
                    if ($trigger['subject'] === $expected_confirm_subject) {
                        echo "✅ Confirmation email subject matches: {$trigger['subject']}\n";
                    } else {
                        echo "❌ Confirmation email subject mismatch. Expected: '{$expected_confirm_subject}', Got: '{$trigger['subject']}'\n";
                    }
                }
            }
            
            if (!$admin_email_found) {
                echo "❌ Admin email trigger not found\n";
            }
            if (!$confirm_email_found) {
                echo "❌ Confirmation email trigger not found\n";
            }
            
            $success = $admin_email_found && $confirm_email_found;
            
        } else {
            echo "❌ No triggers found after migration\n";
            $success = false;
        }
        
        // Step 7: Check emails settings directly
        echo "Step 6: Checking direct email settings...\n";
        $emails = SUPER_Common::get_form_emails_settings($form_id);
        if (!empty($emails)) {
            echo "Found " . count($emails) . " email configurations\n";
            foreach ($emails as $i => $email) {
                if (isset($email['data'])) {
                    echo "  Email " . ($i + 1) . ":\n";
                    echo "    To: " . (isset($email['data']['to']) ? $email['data']['to'] : 'not set') . "\n";
                    echo "    Subject: " . (isset($email['data']['subject']) ? $email['data']['subject'] : 'not set') . "\n";
                }
            }
        } else {
            echo "No email configurations found\n";
        }
        
    } else {
        echo "❌ SUPER_Common class not available\n";
        $success = false;
    }
    
    echo "\n=== TEST RESULTS ===\n";
    echo "Form ID: {$form_id}\n";
    echo "Version: {$version}\n";
    echo "Migration Success: " . ($success ? "✅ YES" : "❌ NO") . "\n";
    echo "Form Builder URL: wp-admin/admin.php?page=super_create_form&id={$form_id}\n";
    
    return array(
        'success' => $success,
        'form_id' => $form_id,
        'version' => $version,
        'email_triggers' => isset($email_triggers) ? $email_triggers : array()
    );
}

function cleanup_existing_forms() {
    // Delete any existing imported forms
    $existing_forms = get_posts(array(
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
    
    foreach ($existing_forms as $form) {
        wp_delete_post($form->ID, true);
        echo "Deleted existing form: {$form->ID}\n";
    }
}

function import_loan_form() {
    // Import the Loan Pre-Qualification form using our corrected import script
    include_once('/scripts/import-single-form.php');
    
    $form_file = '/scripts/../exports/original/form_71883.json';
    
    if (!file_exists($form_file)) {
        return array('success' => false, 'error' => 'Form file not found: ' . $form_file);
    }
    
    try {
        $result = import_super_form_direct($form_file);
        return $result;
    } catch (Exception $e) {
        return array('success' => false, 'error' => $e->getMessage());
    }
}

// Execute the test
$test_result = fresh_wordpress_email_test();
echo "\nFinal Result: " . json_encode($test_result, JSON_PRETTY_PRINT) . "\n";
?>