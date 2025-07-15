#!/usr/bin/env php
<?php
/**
 * Raw PHP test - bypass WordPress error handling completely
 * Test Super Forms components directly to find the actual error
 */

// Force PHP error display
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('html_errors', 0); // Plain text errors for CLI

echo "=== Raw Super Forms Component Test ===\n";
echo "Testing Super Forms plugin files directly...\n\n";

// Change to WordPress directory
chdir('/var/www/html');

// Minimal WordPress bootstrap to load just what we need
define('ABSPATH', '/var/www/html/');
define('WPINC', 'wp-includes');
define('WP_CONTENT_DIR', '/var/www/html/wp-content');
define('WP_PLUGIN_DIR', '/var/www/html/wp-content/plugins');

// Load WordPress config
if (file_exists('/var/www/html/wp-config.php')) {
    echo "✅ Loading wp-config.php...\n";
    require_once('/var/www/html/wp-config.php');
} else {
    echo "❌ wp-config.php not found\n";
    exit(1);
}

// Test 1: Check if Super Forms main file exists and is readable
echo "\n1. Testing Super Forms main file...\n";
$plugin_file = WP_PLUGIN_DIR . '/super-forms/super-forms.php';
if (!file_exists($plugin_file)) {
    echo "❌ Plugin file not found: $plugin_file\n";
    exit(1);
}

if (!is_readable($plugin_file)) {
    echo "❌ Plugin file not readable: $plugin_file\n";
    exit(1);
}

echo "✅ Plugin file exists and is readable\n";

// Test 2: Try to include the main plugin file
echo "\n2. Testing plugin file inclusion...\n";
try {
    // Capture any output/errors
    ob_start();
    $error_before = error_get_last();
    
    include_once($plugin_file);
    
    $output = ob_get_clean();
    $error_after = error_get_last();
    
    if ($output) {
        echo "📄 Plugin file output:\n$output\n";
    }
    
    if ($error_after && $error_after !== $error_before) {
        echo "❌ PHP Error during plugin inclusion:\n";
        echo "  Type: " . $error_after['type'] . "\n";
        echo "  Message: " . $error_after['message'] . "\n";
        echo "  File: " . $error_after['file'] . "\n";
        echo "  Line: " . $error_after['line'] . "\n";
    } else {
        echo "✅ Plugin file included without PHP errors\n";
    }
    
} catch (ParseError $e) {
    echo "❌ Parse Error in plugin file:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ Fatal Error in plugin file:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Exception in plugin file:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    exit(1);
}

// Test 3: Check if Super Forms classes are defined
echo "\n3. Testing Super Forms classes...\n";
$classes_to_check = [
    'SUPER_Forms',
    'SUPER_Install', 
    'SUPER_Settings',
    'SUPER_Common'
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "✅ Class $class is defined\n";
    } else {
        echo "❌ Class $class is NOT defined\n";
    }
}

// Test 4: Try to load WordPress core minimally
echo "\n4. Testing minimal WordPress bootstrap...\n";
try {
    // Load minimal WordPress
    require_once(ABSPATH . 'wp-includes/load.php');
    require_once(ABSPATH . 'wp-includes/default-constants.php');
    require_once(ABSPATH . 'wp-includes/plugin.php');
    
    echo "✅ WordPress core files loaded\n";
    
} catch (Error $e) {
    echo "❌ Error loading WordPress core:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
}

// Test 5: Check specific Super Forms include files
echo "\n5. Testing individual Super Forms include files...\n";
$include_files = [
    'includes/class-common.php',
    'includes/class-settings.php',
    'includes/class-install.php',
    'includes/class-shortcodes.php'
];

foreach ($include_files as $file) {
    $full_path = WP_PLUGIN_DIR . '/super-forms/' . $file;
    echo "Testing: $file\n";
    
    if (!file_exists($full_path)) {
        echo "  ❌ File not found\n";
        continue;
    }
    
    try {
        ob_start();
        $error_before = error_get_last();
        
        include_once($full_path);
        
        $output = ob_get_clean();
        $error_after = error_get_last();
        
        if ($error_after && $error_after !== $error_before) {
            echo "  ❌ PHP Error:\n";
            echo "    Message: " . $error_after['message'] . "\n";
            echo "    Line: " . $error_after['line'] . "\n";
        } else {
            echo "  ✅ Loaded successfully\n";
        }
        
    } catch (Error $e) {
        echo "  ❌ Fatal Error:\n";
        echo "    Message: " . $e->getMessage() . "\n";
        echo "    File: " . $e->getFile() . "\n";
        echo "    Line: " . $e->getLine() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "If no errors were shown above, the issue might be in WordPress admin page loading.\n";