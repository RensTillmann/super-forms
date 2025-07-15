#!/usr/bin/env php
<?php
/**
 * Step-by-step debugging to isolate exactly where the fatal error occurs
 * in the SUPER_Pages::create_form() method
 */

// Force PHP error display
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== Step-by-Step Super Forms Debug ===\n";
echo "Isolating the exact location of the fatal error...\n\n";

// Change to WordPress directory
chdir('/var/www/html');

// Simulate admin page request
$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=super_create_form&id=5';
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['SCRIPT_NAME'] = '/wp-admin/admin.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['page'] = 'super_create_form';
$_GET['id'] = '5';

// Set WordPress admin context
define('WP_ADMIN', true);

echo "1. Loading WordPress with admin context...\n";

try {
    // Load WordPress
    require_once('/var/www/html/wp-load.php');
    echo "✅ WordPress loaded successfully\n";

    // Set current user to admin
    wp_set_current_user(1);
    echo "✅ Set current user to admin\n";

    echo "\n2. Checking Super Forms plugin status...\n";
    
    if (!class_exists('SUPER_Forms')) {
        echo "❌ SUPER_Forms class not found\n";
        exit(1);
    }
    echo "✅ SUPER_Forms class exists\n";
    
    $super_forms = SUPER_Forms();
    echo "✅ SUPER_Forms instance created\n";
    
    // Check if SUPER_Pages class exists
    if (!class_exists('SUPER_Pages')) {
        echo "❌ SUPER_Pages class not found\n";
        exit(1);
    }
    echo "✅ SUPER_Pages class exists\n";
    
    // Check if create_form method exists
    if (!method_exists('SUPER_Pages', 'create_form')) {
        echo "❌ SUPER_Pages::create_form method not found\n";
        exit(1);
    }
    echo "✅ SUPER_Pages::create_form method exists\n";
    
    echo "\n3. Testing step-by-step method calls...\n";
    
    // Step 3.1: Try to get an instance of SUPER_Pages
    echo "Step 3.1: Getting SUPER_Pages instance...\n";
    try {
        if (method_exists('SUPER_Pages', '__construct')) {
            $pages_instance = new SUPER_Pages();
            echo "✅ SUPER_Pages instance created successfully\n";
        } else {
            echo "⚠️ SUPER_Pages has no constructor, using static method\n";
            $pages_instance = null;
        }
    } catch (Error $e) {
        echo "❌ Fatal Error creating SUPER_Pages instance:\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . "\n";
        echo "  Line: " . $e->getLine() . "\n";
        echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
    
    // Step 3.2: Check method signature
    echo "\nStep 3.2: Analyzing create_form method...\n";
    $reflection = new ReflectionMethod('SUPER_Pages', 'create_form');
    echo "✅ Method is " . ($reflection->isStatic() ? "static" : "instance") . "\n";
    echo "✅ Method parameters: " . $reflection->getNumberOfParameters() . "\n";
    echo "✅ Method file: " . $reflection->getFileName() . "\n";
    echo "✅ Method line: " . $reflection->getStartLine() . "\n";
    
    // Step 3.3: Read the actual method code to see what it does
    echo "\nStep 3.3: Reading method source code...\n";
    $file_content = file_get_contents($reflection->getFileName());
    $lines = explode("\n", $file_content);
    $start_line = $reflection->getStartLine() - 1;
    $end_line = $reflection->getEndLine() - 1;
    
    echo "Method source (first 10 lines):\n";
    for ($i = $start_line; $i < min($start_line + 10, $end_line + 1, count($lines)); $i++) {
        echo "  " . ($i + 1) . ": " . $lines[$i] . "\n";
    }
    
    // Step 3.4: Test calling the method with error isolation
    echo "\nStep 3.4: Attempting to call create_form method...\n";
    
    // Capture all output and errors
    ob_start();
    $error_before = error_get_last();
    
    try {
        echo "Calling SUPER_Pages::create_form()...\n";
        
        // Call the method
        if ($reflection->isStatic()) {
            SUPER_Pages::create_form();
        } else {
            if ($pages_instance) {
                $pages_instance->create_form();
            } else {
                echo "❌ Cannot call instance method without instance\n";
                exit(1);
            }
        }
        
        echo "✅ Method completed without fatal error\n";
        
    } catch (Error $e) {
        echo "❌ Fatal Error in create_form method:\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . "\n";
        echo "  Line: " . $e->getLine() . "\n";
        echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
        
        // Try to identify the exact line where it fails
        echo "\n🔍 Error Analysis:\n";
        $error_file = $e->getFile();
        $error_line = $e->getLine();
        
        if (file_exists($error_file)) {
            $error_file_content = file_get_contents($error_file);
            $error_lines = explode("\n", $error_file_content);
            
            echo "Context around error (line $error_line in $error_file):\n";
            for ($i = max(0, $error_line - 3); $i < min(count($error_lines), $error_line + 3); $i++) {
                $marker = ($i + 1) == $error_line ? ">>> " : "    ";
                echo "$marker" . ($i + 1) . ": " . $error_lines[$i] . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Exception in create_form method:\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . "\n";
        echo "  Line: " . $e->getLine() . "\n";
    }
    
    $output = ob_get_clean();
    $error_after = error_get_last();
    
    // Check for PHP errors
    if ($error_after && $error_after !== $error_before) {
        echo "\n❌ PHP Error detected:\n";
        echo "  Type: " . $error_after['type'] . "\n";
        echo "  Message: " . $error_after['message'] . "\n";
        echo "  File: " . $error_after['file'] . "\n";
        echo "  Line: " . $error_after['line'] . "\n";
    }
    
    // Show captured output
    if (!empty($output)) {
        echo "\n📄 Method output:\n";
        echo "--- START OUTPUT ---\n";
        echo $output;
        echo "\n--- END OUTPUT ---\n";
    }
    
} catch (Error $e) {
    echo "❌ Fatal Error during WordPress/plugin loading:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Step-by-Step Debug Complete ===\n";