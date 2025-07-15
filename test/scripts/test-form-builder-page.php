#!/usr/bin/env php
<?php
/**
 * Test the specific form builder page that's showing blank
 */

// Force PHP error display
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== Form Builder Page Test ===\n";
echo "Testing what happens when loading Super Forms builder page...\n\n";

// Simulate being logged in as admin
chdir('/var/www/html');

// Set up environment to match browser request
$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=super_create_form&id=5';
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['SCRIPT_NAME'] = '/wp-admin/admin.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['page'] = 'super_create_form';
$_GET['id'] = '5';

define('WP_ADMIN', true);

echo "1. Loading WordPress and simulating admin user...\n";

try {
    // Load WordPress
    require_once('/var/www/html/wp-load.php');
    
    // Simulate logged in admin user
    wp_set_current_user(1); // Admin user ID is usually 1
    
    $current_user = wp_get_current_user();
    echo "✅ Simulated user: " . $current_user->user_login . " (ID: " . $current_user->ID . ")\n";
    
    echo "\n2. Testing form builder page hook...\n";
    
    // Check if Super Forms registers the admin page
    global $admin_page_hooks, $plugin_page_hookname;
    
    // Manually trigger admin_menu action to register pages
    do_action('admin_menu');
    
    echo "Admin page hooks registered:\n";
    if (!empty($admin_page_hooks)) {
        foreach ($admin_page_hooks as $key => $value) {
            if (strpos($key, 'super') !== false || strpos($value, 'super') !== false) {
                echo "  - $key => $value\n";
            }
        }
    }
    
    echo "\n3. Testing page parameter handling...\n";
    $page = $_GET['page'] ?? '';
    $form_id = $_GET['id'] ?? '';
    
    echo "Page parameter: '$page'\n";
    echo "Form ID parameter: '$form_id'\n";
    
    // Check if this is a valid Super Forms admin page
    if ($page === 'super_create_form') {
        echo "✅ Valid Super Forms admin page requested\n";
        
        // Check form ID
        if (is_numeric($form_id)) {
            $post = get_post((int)$form_id);
            if ($post && $post->post_type === 'super_form') {
                echo "✅ Valid form ID: $form_id ('" . $post->post_title . "')\n";
            } else {
                echo "❌ Invalid form ID or wrong post type\n";
                exit(1);
            }
        } else {
            echo "❌ Form ID is not numeric: '$form_id'\n";
            exit(1);
        }
    } else {
        echo "❌ Not a Super Forms admin page\n";
        exit(1);
    }
    
    echo "\n4. Testing Super Forms admin page handler...\n";
    
    // Look for the admin page handler function
    if (class_exists('SUPER_Forms')) {
        $super_forms = SUPER_Forms();
        echo "✅ SUPER_Forms instance available\n";
        
        // Check if admin page methods exist
        $methods_to_check = [
            'create_form',
            'admin_page',
            'forms_page',
            'create_form_page'
        ];
        
        foreach ($methods_to_check as $method) {
            if (method_exists($super_forms, $method)) {
                echo "✅ Method '$method' exists\n";
            } else {
                echo "❌ Method '$method' does not exist\n";
            }
        }
        
        // Try to call the admin page handler directly
        echo "\n5. Attempting to call admin page handler...\n";
        
        try {
            // Start output buffering to catch any output
            ob_start();
            
            // Try different possible method names for the form builder page
            if (method_exists($super_forms, 'create_form')) {
                echo "Calling create_form method...\n";
                $super_forms->create_form();
            } elseif (method_exists($super_forms, 'forms_page')) {
                echo "Calling forms_page method...\n";
                $super_forms->forms_page();
            } else {
                echo "❌ No suitable admin page method found\n";
                
                // List all available methods
                $methods = get_class_methods($super_forms);
                echo "Available methods:\n";
                foreach ($methods as $method) {
                    if (strpos($method, 'admin') !== false || strpos($method, 'page') !== false || strpos($method, 'form') !== false) {
                        echo "  - $method\n";
                    }
                }
            }
            
            $output = ob_get_clean();
            
            if (!empty($output)) {
                echo "📄 Admin page output:\n";
                echo "--- START OUTPUT ---\n";
                echo $output;
                echo "\n--- END OUTPUT ---\n";
            } else {
                echo "❌ No output from admin page handler\n";
            }
            
        } catch (Error $e) {
            ob_end_clean();
            echo "❌ Fatal Error in admin page handler:\n";
            echo "  Message: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . "\n";
            echo "  Line: " . $e->getLine() . "\n";
            echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
        } catch (Exception $e) {
            ob_end_clean();
            echo "❌ Exception in admin page handler:\n";
            echo "  Message: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . "\n";
            echo "  Line: " . $e->getLine() . "\n";
        }
        
    } else {
        echo "❌ SUPER_Forms class not available\n";
    }
    
} catch (Error $e) {
    echo "❌ Fatal Error:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Form Builder Page Test Complete ===\n";