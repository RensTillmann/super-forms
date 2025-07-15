#!/usr/bin/env php
<?php
/**
 * Test WordPress admin page loading for Super Forms
 */

// Force PHP error display
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== WordPress Admin Page Test ===\n";

// Change to WordPress directory and load WordPress
chdir('/var/www/html');

// Simulate admin page request
$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=super_create_form&id=5';
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['SCRIPT_NAME'] = '/wp-admin/admin.php';
$_GET['page'] = 'super_create_form';
$_GET['id'] = '5';

// Set WordPress admin context
define('WP_ADMIN', true);
define('DOING_AJAX', false);

echo "1. Loading WordPress with admin context...\n";

try {
    // Load WordPress
    require_once('/var/www/html/wp-load.php');
    
    echo "✅ WordPress loaded successfully\n";
    
    // Check if we're in admin
    echo "2. Checking admin context...\n";
    if (is_admin()) {
        echo "✅ Admin context confirmed\n";
    } else {
        echo "❌ Not in admin context\n";
    }
    
    // Check if Super Forms is active
    echo "3. Checking plugin activation...\n";
    if (is_plugin_active('super-forms/super-forms.php')) {
        echo "✅ Super Forms is active\n";
    } else {
        echo "❌ Super Forms is not active\n";
        
        // Check if plugin exists in active plugins list
        $active_plugins = get_option('active_plugins', array());
        echo "Active plugins: " . implode(', ', $active_plugins) . "\n";
    }
    
    // Check current user capabilities
    echo "4. Checking user permissions...\n";
    if (function_exists('wp_get_current_user')) {
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID > 0) {
            echo "✅ User logged in: " . $current_user->user_login . "\n";
            echo "User capabilities: " . implode(', ', array_keys($current_user->caps)) . "\n";
        } else {
            echo "❌ No user logged in\n";
        }
    }
    
    // Check if Super Forms admin pages are registered
    echo "5. Checking Super Forms admin pages...\n";
    global $admin_page_hooks, $submenu, $menu;
    
    $super_forms_found = false;
    foreach ($menu as $menu_item) {
        if (strpos($menu_item[2], 'super') !== false) {
            echo "✅ Found Super Forms menu: " . $menu_item[0] . " -> " . $menu_item[2] . "\n";
            $super_forms_found = true;
        }
    }
    
    if (!$super_forms_found) {
        echo "❌ No Super Forms admin menus found\n";
    }
    
    // Try to access the specific admin page
    echo "6. Testing Super Forms admin page access...\n";
    
    if (class_exists('SUPER_Forms')) {
        echo "✅ SUPER_Forms class available\n";
        
        // Check if the form exists
        $form_id = 5;
        $post = get_post($form_id);
        if ($post && $post->post_type === 'super_form') {
            echo "✅ Form $form_id exists: " . $post->post_title . "\n";
        } else {
            echo "❌ Form $form_id not found or wrong post type\n";
        }
        
    } else {
        echo "❌ SUPER_Forms class not available\n";
    }
    
    // Test WordPress hooks and actions
    echo "7. Testing WordPress action hooks...\n";
    $hook_test_result = "";
    
    function test_admin_init() {
        global $hook_test_result;
        $hook_test_result .= "admin_init fired; ";
    }
    
    function test_admin_menu() {
        global $hook_test_result;
        $hook_test_result .= "admin_menu fired; ";
    }
    
    add_action('admin_init', 'test_admin_init');
    add_action('admin_menu', 'test_admin_menu');
    
    // Trigger admin hooks manually
    if (function_exists('do_action')) {
        do_action('admin_init');
        do_action('admin_menu');
        echo "Hook test results: $hook_test_result\n";
    }
    
} catch (Error $e) {
    echo "❌ Fatal Error during WordPress loading:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Exception $e) {
    echo "❌ Exception during WordPress loading:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
}

echo "\n=== Admin Page Test Complete ===\n";