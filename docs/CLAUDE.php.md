# PHP & WordPress Plugin Development Guide

## WordPress Plugin Development Context

### Environment

- WordPress plugin requiring PHP 7.4+ and WordPress 5.8+
- Uses jQuery (but prefers vanilla JavaScript for frontend interactions and WordPress REST API)
- Includes various third-party integrations (PayPal, Mailchimp, WooCommerce, etc.)
- Action Scheduler library (v3.9.3) for background processing

### Core WordPress Development Principles

- **WordPress Standards First**: Always follow WordPress Coding Standards (WPCS)
- **Security by Design**: Implement sanitization, nonces, and capability checks
- **Performance Minded**: Optimize queries, use caching, conditional loading
- **Accessibility Required**: Ensure WCAG 2.1 AA compliance
- **Translation Ready**: All strings must be translatable with proper text domain

## Coding Standards

### Naming Conventions

- Prefix all functions, classes, objects with `super`, `SUPER`, or `sfui` to avoid conflicts
- Use WordPress naming conventions (underscores for functions, PascalCase for classes)
- Follow WordPress hook naming patterns

**Examples:**
```php
// ✅ GOOD - Proper prefixing
function super_forms_sanitize_data($data) { }
class SUPER_Background_Migration { }
do_action('super_forms_before_save', $entry_id);

// ❌ BAD - No prefix (conflicts with other plugins)
function sanitize_data($data) { }
class Migration { }
do_action('before_save', $entry_id);
```

### Indentation & Formatting

- Use **4 spaces** for indentation (PHP and JS)
- Use **consistent indentation** throughout files
- Follow WordPress Coding Standards for brace placement
- Add spaces around operators and after commas

**Examples:**
```php
// ✅ GOOD
if ( $condition ) {
    $result = $value + 10;
    do_something( $param1, $param2 );
}

// ❌ BAD
if($condition){
    $result=$value+10;
    do_something($param1,$param2);
}
```

### WordPress Functions Over Native PHP

Always prefer WordPress functions when available:

```php
// ✅ GOOD - Use WordPress functions
$url = home_url('/page/');
$path = wp_upload_dir()['path'];
$data = get_option('my_option', array());
wp_mail($to, $subject, $message);

// ❌ BAD - Native PHP when WordPress alternative exists
$url = $_SERVER['HTTP_HOST'] . '/page/';
$path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
$data = unserialize(get_option('my_option'));
mail($to, $subject, $message);
```

## Security Best Practices

### Input Sanitization

**ALWAYS sanitize user input** using WordPress functions:

```php
// Text input
$name = sanitize_text_field($_POST['name']);

// Email
$email = sanitize_email($_POST['email']);

// URL
$website = esc_url_raw($_POST['website']);

// Integer
$id = absint($_POST['id']);

// Array of integers
$ids = array_map('absint', $_POST['ids']);

// Rich text (allows specific HTML)
$content = wp_kses_post($_POST['content']);

// Filename
$filename = sanitize_file_name($_POST['filename']);

// SQL LIKE query
$search = $wpdb->esc_like($search_term);
```

### Output Escaping

**ALWAYS escape output** with appropriate WordPress functions:

```php
// HTML content
echo esc_html($user_input);

// Attributes
echo '<input type="text" value="' . esc_attr($value) . '">';

// URLs
echo '<a href="' . esc_url($url) . '">Link</a>';

// JavaScript
echo '<script>var data = ' . wp_json_encode($data) . ';</script>';

// SQL queries (use prepared statements)
$wpdb->prepare("SELECT * FROM table WHERE id = %d AND name = %s", $id, $name);
```

### Nonce Verification

**ALWAYS use nonces** for all form submissions and AJAX requests:

```php
// Create nonce in PHP
wp_nonce_field('super-form-builder', 'super_nonce');

// OR for AJAX
wp_localize_script('my-script', 'myData', array(
    'nonce' => wp_create_nonce('super-form-builder')
));

// Verify nonce in handler
if (!wp_verify_nonce($_POST['super_nonce'], 'super-form-builder')) {
    wp_die('Security check failed');
}

// OR for AJAX
check_ajax_referer('super-form-builder', 'security');
```

### Capability Checks

**ALWAYS validate capabilities** before allowing admin actions:

```php
// Check if user can manage options
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'super-forms'));
}

// Check custom capability
if (!current_user_can('super_forms_manage_entries')) {
    wp_send_json_error(array('message' => 'Insufficient permissions'));
}

// Check if user owns resource
$post = get_post($post_id);
if ($post->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
    wp_die('You cannot edit this entry.');
}
```

### SQL Injection Prevention

**ALWAYS use prepared statements** for database queries:

```php
global $wpdb;

// ✅ GOOD - Prepared statement
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}superforms_entry_data
     WHERE entry_id = %d AND field_name = %s",
    $entry_id,
    $field_name
));

// ❌ BAD - Direct interpolation (SQL injection risk!)
$results = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}superforms_entry_data
     WHERE entry_id = $entry_id AND field_name = '$field_name'"
);

// For LIKE queries, use wpdb::esc_like()
$search = '%' . $wpdb->esc_like($search_term) . '%';
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM table WHERE field_value LIKE %s",
    $search
));

// ✅ GOOD - DELETE with prepared statement (v6.4.126 security fix)
$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->prefix}actionscheduler_actions
     WHERE action_id < %d AND status = %s",
    $threshold_id,
    'complete'
));

// ❌ BAD - Direct integer interpolation (SQL injection risk!)
$wpdb->query(
    "DELETE FROM {$wpdb->prefix}actionscheduler_actions
     WHERE action_id < $threshold_id AND status = 'complete'"
);

// ✅ GOOD - TRUNCATE with validation (v6.4.126 security fix)
$allowed_tables = array('superforms_entry_data', 'actionscheduler_actions');
$table_name = 'superforms_entry_data'; // user input
if (in_array($table_name, $allowed_tables, true)) {
    $full_table = $wpdb->prefix . esc_sql($table_name);
    // Verify table exists
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $full_table
    ));
    if ($table_exists) {
        $wpdb->query("TRUNCATE TABLE $full_table");
    }
}
```

### File Upload Security

```php
// Validate file type
$allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
$file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

if (!in_array($file_ext, $allowed_types)) {
    wp_die('Invalid file type');
}

// Use WordPress upload handling
$upload = wp_handle_upload($_FILES['file'], array('test_form' => false));

if (isset($upload['error'])) {
    wp_die($upload['error']);
}

// Sanitize filename
$filename = sanitize_file_name($_FILES['file']['name']);
```

## Performance Considerations

### Database Query Optimization

**Minimize database queries:**

```php
// ✅ GOOD - Single query with IN clause
$entry_ids = array(1, 2, 3, 4, 5);
$placeholders = implode(',', array_fill(0, count($entry_ids), '%d'));
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM table WHERE entry_id IN ($placeholders)",
    ...$entry_ids
));

// ❌ BAD - N+1 query problem
foreach ($entry_ids as $entry_id) {
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM table WHERE entry_id = %d",
        $entry_id
    ));
}
```

**Use indexes:**
```php
// Ensure queries use indexed columns
// Check table indexes in class-install.php
KEY entry_id (entry_id),
KEY field_name (field_name),
KEY entry_field (entry_id, field_name)
```

### WordPress Transients for Caching

```php
// Check transient first
$data = get_transient('super_forms_cached_data');

if (false === $data) {
    // Expensive operation
    $data = perform_expensive_calculation();

    // Cache for 1 hour
    set_transient('super_forms_cached_data', $data, HOUR_IN_SECONDS);
}

return $data;

// Example: Cache expensive LEFT JOIN query (v6.4.126 optimization)
// Migration status polling happens every 2 seconds, causing performance issues
$orphaned_count = get_transient('super_orphaned_metadata_count');

if (false === $orphaned_count) {
    // Expensive LEFT JOIN query to find orphaned metadata
    $orphaned_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = '_super_contact_entry_data'
         AND p.ID IS NULL"
    );

    // Cache for 5 minutes to prevent excessive database load
    set_transient('super_orphaned_metadata_count', $orphaned_count, 5 * MINUTE_IN_SECONDS);
}

return (int) $orphaned_count;
```

### Conditional Asset Loading

```php
// Only load assets on specific pages
add_action('admin_enqueue_scripts', 'super_forms_admin_scripts');
function super_forms_admin_scripts($hook) {
    // Only load on Super Forms pages
    if (strpos($hook, 'super-forms') === false) {
        return;
    }

    wp_enqueue_script('super-forms-admin', /* ... */);
    wp_enqueue_style('super-forms-admin', /* ... */);
}
```

### Lazy Loading

```php
// Lazy load heavy classes
if (class_exists('SUPER_Heavy_Feature')) {
    SUPER_Heavy_Feature::init();
}

// OR autoload with spl_autoload_register
spl_autoload_register(function($class) {
    if (strpos($class, 'SUPER_') === 0) {
        $file = plugin_dir_path(__FILE__) . 'includes/class-' .
                strtolower(str_replace('SUPER_', '', $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
```

## WordPress Development Stack Requirements

### PHPCS (PHP CodeSniffer)

Run WordPress Coding Standards validation:

```bash
# Install PHPCS and WordPress standards
composer require --dev squizlabs/php_codesniffer
composer require --dev wp-coding-standards/wpcs

# Configure PHPCS
phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs

# Run PHPCS
phpcs --standard=WordPress src/includes/

# Auto-fix issues
phpcbf --standard=WordPress src/includes/
```

### Plugin Check

Use WordPress.org Plugin Check tool:

```bash
# Install Plugin Check plugin from wordpress.org
# OR use CLI tool
wp plugin install plugin-check --activate
wp plugin-check run /path/to/plugin
```

### Security Scanning

Regular WPScan vulnerability checks:

```bash
# Install WPScan
gem install wpscan

# Scan plugin
wpscan --url https://example.com --enumerate p
```

### Query Monitor

Monitor database queries and performance:

```bash
# Install Query Monitor plugin
wp plugin install query-monitor --activate

# Access via admin bar → Query Monitor
# Check:
# - Database queries count and time
# - Slow queries (>0.05s)
# - Duplicate queries
# - PHP errors and warnings
```

## WordPress-Specific File Change Protocol

When editing WordPress plugin files, ALWAYS:

1. **Follow WordPress naming conventions** (prefix with `super_forms_`)
2. **Use WordPress functions** instead of native PHP when available
3. **Implement proper error handling** with `WP_Error`
4. **Add inline documentation** with PHPDoc standards
5. **Test both frontend and admin functionality**

### PHPDoc Documentation

```php
/**
 * Migrate a single contact entry from serialized to EAV storage
 *
 * @since 6.4.111
 * @param int $entry_id The contact entry post ID to migrate
 * @return array {
 *     Migration result
 *
 *     @type bool   $success Whether migration succeeded
 *     @type string $error   Error message if failed
 *     @type int    $fields  Number of fields migrated
 * }
 */
public function migrate_entry($entry_id) {
    // Implementation
}
```

### Error Handling with WP_Error

```php
// Return WP_Error on failure
public function save_entry($data) {
    if (empty($data)) {
        return new WP_Error('empty_data', __('Entry data cannot be empty', 'super-forms'));
    }

    // Save logic
    $result = $wpdb->insert(/* ... */);

    if (false === $result) {
        return new WP_Error('db_error', $wpdb->last_error);
    }

    return array('success' => true, 'id' => $wpdb->insert_id);
}

// Check for errors
$result = $this->save_entry($data);
if (is_wp_error($result)) {
    error_log('[SF Error] ' . $result->get_error_message());
    return false;
}
```

## WordPress Hooks & Filters System

### Action Hooks

```php
// Add action hook
do_action('super_forms_before_save', $entry_id, $form_id);

// Let other plugins hook in
add_action('super_forms_before_save', 'my_custom_function', 10, 2);
function my_custom_function($entry_id, $form_id) {
    // Custom logic
}
```

### Filter Hooks

```php
// Apply filter hook
$batch_size = apply_filters('super_forms_migration_batch_size', $batch_size);

// Let other plugins modify
add_filter('super_forms_migration_batch_size', 'my_custom_batch_size');
function my_custom_batch_size($batch_size) {
    return 50; // Override to 50 entries per batch
}
```

### Hook Naming Conventions

- Actions: `super_forms_{action}_{context}`
- Filters: `super_forms_{value}_{context}`
- Examples:
  - `super_forms_before_save_entry`
  - `super_forms_after_delete_entry`
  - `super_forms_entry_data`
  - `super_forms_validation_rules`

## Translation & Internationalization

### Text Domain

All strings must use `super-forms` text domain:

```php
// Simple string
__('Hello World', 'super-forms');

// Echo string
_e('Hello World', 'super-forms');

// With sprintf
sprintf(__('Processing %d entries', 'super-forms'), $count);

// Pluralization
_n('1 entry', '%d entries', $count, 'super-forms');

// Context (for ambiguous strings)
_x('Post', 'verb', 'super-forms'); // vs "Post" (noun)

// Escape and translate
esc_html__('Hello World', 'super-forms');
esc_attr__('Hello World', 'super-forms');
```

### Generate Translation Files

```bash
# Install WP-CLI i18n command
wp package install wp-cli/i18n-command

# Generate POT file
wp i18n make-pot . languages/super-forms.pot

# Update PO files
wp i18n update-po languages/super-forms.pot languages/
```

## WordPress REST API

### Register Custom Endpoint

```php
add_action('rest_api_init', 'super_forms_register_api');
function super_forms_register_api() {
    register_rest_route('super-forms/v1', '/entries/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'super_forms_get_entry',
        'permission_callback' => 'super_forms_api_permissions',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            )
        )
    ));
}

function super_forms_api_permissions() {
    return current_user_can('manage_options');
}

function super_forms_get_entry($request) {
    $entry_id = $request['id'];
    $data = SUPER_Data_Access::get_entry_data($entry_id);

    if (!$data) {
        return new WP_Error('not_found', 'Entry not found', array('status' => 404));
    }

    return rest_ensure_response($data);
}
```

## WordPress Plugin Guidelines Reference

For comprehensive WordPress plugin development guidelines, security best practices, and automated validation processes, refer to `wp-plugin-guidelines.md` in this project.

**Key Guidelines to Remember:**
- Always sanitize user input with WordPress functions
- Use nonces for all form submissions and AJAX requests
- Escape all output with appropriate WordPress functions
- Check user capabilities before allowing admin actions
- Follow WordPress naming conventions for all code elements
- Use WordPress APIs instead of native PHP functions
- Implement proper error handling with WP_Error
- Test both frontend and admin functionality thoroughly

## Debugging

### WP_DEBUG Mode

Enable debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

### Debug Logging

```php
// Log to debug.log
if (WP_DEBUG_LOG) {
    error_log('[SF Debug] Entry ID: ' . $entry_id);
}

// Use WordPress debug functions
do_action('qm/debug', $variable); // Query Monitor integration

// Conditional logging (migration system)
if (defined('DEBUG_SF') && DEBUG_SF) {
    error_log('[SF Migration] Batch processed: ' . $batch_size . ' entries');
    error_log('[SF Migration Debug] Detailed entry-level debug info'); // Verbose debug logs
}

// Production-safe debug filter (v6.4.126)
// Enable via filter without code changes
if (apply_filters('super_forms_migration_debug', false)) {
    error_log('[SF Migration Debug] Lock acquired, processing batch...');
    error_log('[SF Migration Debug] Action Scheduler context: ' . $context);
}

// Enable debug logging in production:
// Add to theme's functions.php or custom plugin:
add_filter('super_forms_migration_debug', '__return_true');

// Or conditionally enable for specific users:
add_filter('super_forms_migration_debug', function() {
    return current_user_can('manage_options') && isset($_GET['debug_migration']);
});
```

### Common Debug Scenarios

```php
// Log database errors
if ($wpdb->last_error) {
    error_log('[SF DB Error] ' . $wpdb->last_error);
}

// Log API responses
$response = wp_remote_post($url, $args);
if (is_wp_error($response)) {
    error_log('[SF API Error] ' . $response->get_error_message());
}

// Log variable dumps
error_log('[SF Debug] Data: ' . print_r($data, true));
error_log('[SF Debug] JSON: ' . wp_json_encode($data));
```
