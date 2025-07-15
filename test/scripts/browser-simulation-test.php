<?php
/**
 * Test that simulates actual browser admin page request
 * This should reveal what happens in real browser context
 */

// Create a temporary PHP file that WordPress can execute directly
$test_content = '<?php
// Simulate actual browser admin request
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/wp-admin/admin.php?page=super_create_form&id=5";
$_SERVER["HTTP_HOST"] = "localhost:8080";
$_SERVER["SCRIPT_NAME"] = "/wp-admin/admin.php";
$_SERVER["QUERY_STRING"] = "page=super_create_form&id=5";
$_GET["page"] = "super_create_form";
$_GET["id"] = "5";

// Force admin context
define("WP_ADMIN", true);

// Enable error reporting
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

echo "<h1>Browser Simulation Test</h1>";
echo "<p>Testing form builder page in simulated browser context...</p>";

try {
    // Load WordPress
    require_once("/var/www/html/wp-load.php");
    
    echo "<h2>1. WordPress Environment</h2>";
    echo "<p>✅ WordPress loaded successfully</p>";
    echo "<p>is_admin(): " . (is_admin() ? "true" : "false") . "</p>";
    echo "<p>Current user ID: " . get_current_user_id() . "</p>";
    
    // Force login as admin
    wp_set_current_user(1);
    echo "<p>✅ Set current user to admin (ID: 1)</p>";
    
    echo "<h2>2. Super Forms Plugin Status</h2>";
    
    if (class_exists("SUPER_Forms")) {
        echo "<p>✅ SUPER_Forms class exists</p>";
        
        $sf = SUPER_Forms();
        echo "<p>is_request(admin): " . ($sf->is_request("admin") ? "true" : "false") . "</p>";
        
        if (class_exists("SUPER_Pages")) {
            echo "<p>✅ SUPER_Pages class loaded</p>";
            
            if (method_exists("SUPER_Pages", "create_form")) {
                echo "<p>✅ create_form method exists</p>";
                
                echo "<h2>3. Testing Form Builder Page</h2>";
                echo "<p>Attempting to call SUPER_Pages::create_form()...</p>";
                
                // Capture output and errors
                ob_start();
                $error_before = error_get_last();
                
                try {
                    SUPER_Pages::create_form();
                } catch (Error $e) {
                    echo "<div style=\"background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;\">";
                    echo "<h3>❌ Fatal Error in create_form():</h3>";
                    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
                    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
                    echo "<p><strong>Stack Trace:</strong></p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<div style=\"background: #fff3e0; border: 1px solid #ff9800; padding: 10px; margin: 10px 0;\">";
                    echo "<h3>⚠️ Exception in create_form():</h3>";
                    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
                    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
                    echo "</div>";
                }
                
                $output = ob_get_clean();
                $error_after = error_get_last();
                
                if ($error_after && $error_after !== $error_before) {
                    echo "<div style=\"background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;\">";
                    echo "<h3>❌ PHP Error detected:</h3>";
                    echo "<p><strong>Type:</strong> " . $error_after["type"] . "</p>";
                    echo "<p><strong>Message:</strong> " . htmlspecialchars($error_after["message"]) . "</p>";
                    echo "<p><strong>File:</strong> " . htmlspecialchars($error_after["file"]) . "</p>";
                    echo "<p><strong>Line:</strong> " . htmlspecialchars($error_after["line"]) . "</p>";
                    echo "</div>";
                }
                
                if (!empty($output)) {
                    echo "<h3>📄 Form Builder Output:</h3>";
                    echo "<div style=\"background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin: 10px 0;\">";
                    echo $output;
                    echo "</div>";
                } else {
                    echo "<p>❌ No output from create_form() method</p>";
                }
                
            } else {
                echo "<p>❌ create_form method does not exist</p>";
            }
        } else {
            echo "<p>❌ SUPER_Pages class not loaded</p>";
        }
    } else {
        echo "<p>❌ SUPER_Forms class not found</p>";
    }
    
} catch (Error $e) {
    echo "<div style=\"background: #ffebee; border: 1px solid #f44336; padding: 10px;\">";
    echo "<h3>❌ Fatal Error during WordPress load:</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    echo "</div>";
}

echo "<h2>4. Environment Information</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>WordPress Version: " . (defined("WP_VERSION") ? WP_VERSION : "Unknown") . "</p>";
echo "<p>Memory Usage: " . memory_get_usage(true) / 1024 / 1024 . " MB</p>";
echo "<p>Peak Memory: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB</p>";

echo "<hr>";
echo "<p><strong>Test completed at:</strong> " . date("Y-m-d H:i:s") . "</p>";
?>';

// Write the test file to the WordPress directory
file_put_contents('/projects/super-forms/test/browser-test.php', $test_content);

echo "Created browser simulation test file: test/browser-test.php\n";
echo "You can now access it via: http://localhost:8080/browser-test.php\n";
echo "This will show you exactly what happens in real browser context!\n";