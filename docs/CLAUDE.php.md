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
$data = unserialize(get_option('my_option')); // Security risk - PHP object injection!
mail($to, $subject, $message);
```

## Security Best Practices

### Deserialization Security

**NEVER use raw `unserialize()`** - always use `maybe_unserialize()`:

```php
// ❌ BAD - PHP object injection vulnerability (POP chain attack vector)
$data = @unserialize($postmeta_value);
$data = unserialize($option_value);

// ✅ GOOD - WordPress best practice (safe deserialization)
$data = maybe_unserialize($postmeta_value);
$data = maybe_unserialize($option_value);

// maybe_unserialize() benefits:
// - Safely handles already-unserialized data (won't double-unserialize)
// - Doesn't execute object constructors on untrusted data
// - WordPress standard for postmeta/options deserialization
// - Eliminates need for @ error suppression operator
```

**Why this matters:** WordPress postmeta and options often contain user-controlled data. Using raw `unserialize()` allows attackers to inject malicious serialized objects that execute code during unserialization. WordPress core and plugin review team specifically check for this pattern.

**Reference:** Security fix implemented in v6.4.126 for EAV migration system.

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

## Database Migration Patterns

### Data Structure Migration on Load

When plugin features evolve their data structures, migration logic should run automatically on data load rather than in separate migration routines. This ensures seamless backward compatibility without requiring manual intervention or one-time migration tasks.

**Example: Listings Extension Data Structure Migration**

The Listings extension underwent a data structure change in v6.4.x to support translation features. The migration runs automatically in `get_form_listings_settings()` whenever old format data is detected.

**Implementation Pattern:**

```php
public static function get_form_listings_settings($form_id) {
    // Load settings from meta key
    $s = maybe_unserialize(get_post_meta($form_id, '_listings', true));

    // Detect if migration is needed
    if (isset($s['lists']) && is_array($s['lists']) && !empty($s['lists'])) {
        $needs_migration = false;

        // Check 1: Old format used object with numeric keys
        $first_key = array_key_first($s['lists']);
        if (is_int($first_key) || (is_string($first_key) && ctype_digit($first_key))) {
            $needs_migration = true;
        }

        // Check 2: Old format had fields at wrong level (not grouped)
        if (!$needs_migration) {
            foreach ($s['lists'] as $list) {
                if (isset($list['retrieve']) || isset($list['form_ids'])) {
                    $needs_migration = true;
                    break;
                }
            }
        }

        if ($needs_migration) {
            $migrated_lists = array();

            foreach ($s['lists'] as $index => $list) {
                // 1. Generate unique IDs for lists that don't have them
                if (!isset($list['id']) || empty($list['id'])) {
                    $list['id'] = self::generate_random_code(array(
                        'len' => 5, 'char' => '4', 'upper' => 'true', 'lower' => 'true'
                    ), false);
                }

                // 2. Move fields from top level to proper groups
                if (!isset($list['display'])) {
                    $list['display'] = array();
                }
                if (isset($list['retrieve'])) {
                    $list['display']['retrieve'] = $list['retrieve'];
                    unset($list['retrieve']);
                }

                // 3. Convert nested objects to arrays
                if (isset($list['custom_columns']['columns'])) {
                    $first_col_key = array_key_first($list['custom_columns']['columns']);
                    if (is_string($first_col_key) && ctype_digit($first_col_key)) {
                        $list['custom_columns']['columns'] = array_values($list['custom_columns']['columns']);
                    }
                }

                $migrated_lists[] = $list;
            }

            $s['lists'] = $migrated_lists;

            // Save migrated data back to database
            update_post_meta($form_id, '_listings', $s);
        }
    }

    return $s;
}
```

**Key Principles:**

1. **Detection, not assumption** - Check multiple signals to determine if migration is needed
2. **Preserve user data** - Never delete or overwrite without verification
3. **Idempotent** - Running migration multiple times produces same result
4. **Logged for debugging** - Use `DEBUG_SF` constant for detailed migration logs
5. **Save immediately** - Persist migrated data so migration doesn't repeat on every load

**Migration Statistics Tracking:**

```php
$migration_stats = array(
    'ids_generated' => 0,
    'fields_relocated' => 0,
    'arrays_converted' => 0,
);

// Track what was changed
if (!isset($list['id'])) {
    $migration_stats['ids_generated']++;
}

// Log statistics
if (defined('DEBUG_SF') && DEBUG_SF) {
    error_log(sprintf(
        '[SF Listings Migration] Form %d: Migrated %d listings (IDs generated: %d, fields relocated: %d, arrays converted: %d)',
        $form_id,
        count($migrated_lists),
        $migration_stats['ids_generated'],
        $migration_stats['fields_relocated'],
        $migration_stats['arrays_converted']
    ));
}
```

**Backward Compatibility Helper Functions:**

When data structure changes, provide helper functions to resolve both old and new identifiers:

```php
/**
 * Resolve list_id parameter to array index
 * Handles backward compatibility between numeric indices (old) and ID strings (new)
 *
 * @param string|int $list_id_param The list_id from shortcode or POST data
 * @param array      $lists         The lists array from form settings
 * @return int                       Array index, or -1 if not found
 */
public static function resolve_list_id($list_id_param, $lists) {
    if (!is_array($lists) || empty($lists)) {
        return -1;
    }

    // Backward compatibility: numeric index (old format)
    if (is_numeric($list_id_param)) {
        $index = absint($list_id_param);
        return isset($lists[$index]) ? $index : -1;
    }

    // New format: find by ID string
    $list_id_param = sanitize_text_field($list_id_param);
    foreach ($lists as $k => $v) {
        if (isset($v['id']) && $v['id'] === $list_id_param) {
            return $k;
        }
    }

    return -1; // Not found
}
```

**When to Use This Pattern:**

- Data structure changes for existing features
- Field grouping changes in admin UI
- Identifier changes (numeric index to unique ID)
- Object-to-array conversions

**When NOT to Use This Pattern:**

- Database schema changes (use Action Scheduler background migration)
- Large-scale data transformations (>1000 records)
- One-time cleanup operations

**Reference:** Implemented in v6.4.127 for Listings extension backward compatibility (see `/home/rens/super-forms/sessions/tasks/h-fix-listings-backward-compatibility.md`).

**EAV Storage Compatibility:**

When implementing features that query entry data, ensure compatibility with both serialized (legacy) and EAV (new) storage formats:

```php
// ❌ BAD - INNER JOIN excludes EAV entries (no _super_contact_entry_data meta)
$query = "SELECT post.*
          FROM {$wpdb->posts} AS post
          INNER JOIN {$wpdb->postmeta} AS meta
              ON meta.post_id = post.ID
              AND meta.meta_key = '_super_contact_entry_data'
          WHERE post.post_type = 'super_contact_entry'";

// ✅ GOOD - LEFT JOIN includes both serialized and EAV entries
$query = "SELECT post.*
          FROM {$wpdb->posts} AS post
          LEFT JOIN {$wpdb->postmeta} AS meta
              ON meta.post_id = post.ID
              AND meta.meta_key = '_super_contact_entry_data'
          WHERE post.post_type = 'super_contact_entry'";
```

**Key Principle:** Use LEFT JOIN for entry queries to include entries that have been migrated to EAV storage. INNER JOIN will exclude entries that no longer have `_super_contact_entry_data` postmeta.

**Data Access Layer Pattern:**

When building features that export or process entry data, use the Data Access layer instead of direct postmeta queries:

```php
// ❌ BAD - Direct postmeta query doesn't support EAV
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT pm.meta_value
     FROM {$wpdb->postmeta} pm
     WHERE pm.post_id IN (%s)
     AND pm.meta_key = '_super_contact_entry_data'",
    implode(',', $entry_ids)
));
foreach ($results as $row) {
    $data = maybe_unserialize($row->meta_value);
    // Process data...
}

// ✅ GOOD - Data Access layer handles both formats
$bulk_data = SUPER_Data_Access::get_bulk_entry_data($entry_ids);
foreach ($bulk_data as $entry_id => $data) {
    // Data is already unserialized and normalized
    // Works with both serialized and EAV storage
}
```

**Benefits of Data Access Layer:**
- Automatic format detection (serialized vs EAV)
- Consistent data structure regardless of storage method
- Single point of maintenance for storage changes
- Performance optimizations (bulk queries, caching)

**Reference:** Implemented in v6.4.127 for Listings extension CSV export compatibility.

### Version Threshold Protection

**CRITICAL:** Any migration that uses `TRUNCATE TABLE` or other destructive operations MUST implement version threshold protection to prevent data loss during normal plugin updates.

**The Problem:**
Without version thresholds, migrations run on EVERY plugin version update. If a migration uses `TRUNCATE TABLE` to reset state before migrating data, users who already completed the migration will lose all their data on the next plugin update.

**The Solution - Version Threshold Pattern:**

```php
class SUPER_Background_Migration {
    /**
     * Version when migration was introduced
     * Migration only runs when upgrading FROM < this version TO >= this version
     *
     * @since 6.4.126
     */
    const MIGRATION_INTRODUCED_VERSION = '6.4.100';

    public static function check_version_and_schedule() {
        // Get version BEFORE this update
        $plugin_version_before_update = get_option('super_forms_version', '0.0.0');
        $current_version = SUPER_VERSION;

        // Only run migration if crossing the threshold
        if (version_compare($plugin_version_before_update, self::MIGRATION_INTRODUCED_VERSION, '>=')) {
            // User already migrated or installed after migration was introduced
            return; // Don't run migration
        }

        // User is upgrading from pre-migration version
        // Safe to run migration
        $this->setup_and_start_migration();
    }
}

// IMPORTANT: Update stored version on every plugin load
add_action('plugins_loaded', function() {
    $current_version = SUPER_VERSION;
    $stored_version = get_option('super_forms_version', '0.0.0');

    if (version_compare($current_version, $stored_version, '>')) {
        update_option('super_forms_version', $current_version);
    }
});
```

**Key Rules:**
1. The threshold version MUST be the FIRST version that includes the migration code
2. Store plugin version in options table on EVERY plugin load (before version check runs)
3. Compare STORED version (before update) against CURRENT version (after update)
4. Only trigger migration when crossing the threshold (upgrading from < threshold to >= threshold)

**Example Scenarios:**

```php
// Scenario 1: Fresh install at v6.4.110
// - stored_version: '0.0.0' (first install)
// - current_version: '6.4.110'
// - Migration: SKIP (already has EAV tables from install, no old data)

// Scenario 2: Upgrade from v6.3.0 to v6.4.110
// - stored_version: '6.3.0' (before update)
// - current_version: '6.4.110' (after update)
// - Crosses threshold (6.3.0 < 6.4.100 < 6.4.110)
// - Migration: RUN (needs to migrate from serialized to EAV)

// Scenario 3: Upgrade from v6.4.110 to v6.4.120
// - stored_version: '6.4.110' (before update)
// - current_version: '6.4.120' (after update)
// - Already past threshold (6.4.110 >= 6.4.100)
// - Migration: SKIP (already migrated, don't TRUNCATE!)
```

**Without This Pattern:**
- v6.4.110: User migrates 10,000 entries to EAV storage
- v6.4.120: Plugin updates, migration runs AGAIN
- TRUNCATE TABLE wipes all 10,000 migrated entries
- User loses all contact entry data with no recovery path

**Alternative Approaches Considered:**
- Option flag (`migration_completed`): Can get corrupted, not version-aware
- Migration state table: Adds complexity, can be manually reset
- Manual trigger only: Requires user action, poor UX

**Why Version Threshold is Best:**
- Deterministic (based on version numbers, not state)
- Self-documenting (version constant shows when migration was added)
- Works even if migration state gets corrupted
- Survives database resets and fresh installs

**Reference:** Implemented in v6.4.126 after discovering production data loss risk.

### Entry Editing Lock During Migration

**CRITICAL:** Entry editing must be blocked during migration to prevent data integrity issues and race conditions.

**The Problem:**
During migration (status = 'in_progress'), the system uses dual-write to maintain both serialized and EAV storage formats. If an admin edits an entry while migration is processing it, race conditions can occur:
- Admin loads entry #500 (reads from serialized storage)
- Migration processes entry #500 (writes to EAV storage)
- Admin saves changes (writes to both storages, potentially overwriting migration data)

**The Solution - AJAX Handler Protection:**

```php
public static function update_contact_entry() {
    $id = absint($_POST['id']);
    $new_data = $_POST['data'];

    // Check if migration is in progress
    $migration = get_option('superforms_eav_migration', array());
    if (!empty($migration) && isset($migration['status']) && is_string($migration['status']) && $migration['status'] === 'in_progress') {
        SUPER_Common::output_message(array(
            'error' => true,
            'msg' => esc_html__('Entry editing is temporarily disabled while database migration is in progress. Please wait for migration to complete.', 'super-forms')
        ));
        die();
    }

    // ... rest of entry update logic
}
```

**Key Implementation Details:**
1. **Check migration STATUS, not lock state** - Migration status persists for entire duration (hours), while lock is only held during batch processing (seconds)
2. **Type validation** - Use `is_string($migration['status'])` to prevent type coercion edge cases
3. **Input sanitization** - All user input must be sanitized with `sanitize_text_field()` for security
4. **Server-side blocking** - AJAX-level checks provide better UX than UI-level blocking (migration might complete while admin is editing)

**Protected AJAX Handlers:**
- `update_contact_entry()` - Admin back-end entry editing (class-ajax.php line 1296)
- `submit_form()` - Front-end entry editing when entry_id parameter present (class-ajax.php line 5027)

**Not Protected (Intentionally Safe):**
- New entry creation - Uses dual-write during migration, safe to create new entries
- Entry deletion - Deletes from both storage formats
- Entry viewing - Read-only operations don't modify data

**Security Improvements (v6.4.126):**
- Added `sanitize_text_field()` to entry title input
- Added `sanitize_text_field()` to entry status input
- Added `is_string()` type validation to migration status checks

**Reference:** Implemented in subtask 14 of EAV migration plan.

### Migration Cleanup After Completion

After successful migration, clean up old data to reduce database bloat:

```php
// In migration completion handler
public static function complete_migration() {
    global $wpdb;

    // Mark migration as complete
    self::update_state(array('status' => 'completed'));

    // Clean up old serialized postmeta (after migration confirmed successful)
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta}
         WHERE meta_key = %s
         AND post_id IN (
             SELECT ID FROM {$wpdb->posts}
             WHERE post_type = %s
         )",
        '_super_contact_entry_data',
        'super_contact_entry'
    ));

    if (defined('DEBUG_SF') && DEBUG_SF) {
        error_log('[SF Migration] Cleaned up ' . $deleted . ' serialized postmeta rows');
    }
}
```

**Cleanup Rules:**
1. Only delete after migration status is `completed`
2. Use prepared statements for DELETE queries
3. Verify record is truly migrated before deleting old data
4. Log cleanup operations for debugging

**Why Clean Up Matters:**
- 10,000 entries with serialized data: ~15MB postmeta
- Same data in EAV format: ~8MB
- Cleanup saves ~7MB + reduces query overhead

**Reference:** Implemented in v6.4.126 as part of migration completion flow.

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

## WordPress Admin Menu Registration

### Menu Index Position Guidelines

WordPress reserves menu indexes for different purposes. Using the wrong index can cause conflicts with core menus or other plugins.

**Index Ranges:**
- **1-25**: Reserved for WordPress core (Dashboard, Posts, Media, Pages, Comments, etc.)
- **50-80**: Plugin feature menus
- **81-95**: Admin/settings menus
- **96-99**: Developer/debug menus (conditionally shown)

**Example:**
```php
// ❌ BAD - Conflicts with WordPress core menus
add_submenu_page(
    'super_forms',
    'Developer Tools',
    'Developer Tools',
    'manage_options',
    'super_developer_tools',
    'callback',
    5  // TOO LOW - conflicts with core
);

// ✅ GOOD - Safe high index for developer tools
add_submenu_page(
    'super_forms',
    'Developer Tools',
    'Developer Tools',
    'manage_options',
    'super_developer_tools',
    'callback',
    99  // Safe for debug/dev menus
);

// ✅ GOOD - No index parameter (WordPress auto-positions)
add_submenu_page(
    'super_forms',
    'Settings',
    'Settings',
    'manage_options',
    'super_settings',
    'callback'
    // No index - WordPress handles positioning
);
```

**Conditional Menu Items:**
```php
// Developer Tools - only show when DEBUG mode enabled
if (defined('DEBUG_SF') && DEBUG_SF === true) {
    add_submenu_page(
        'super_forms',
        esc_html__('Developer Tools', 'super-forms'),
        esc_html__('Developer Tools', 'super-forms'),
        'manage_options',
        'super_developer_tools',
        'SUPER_Pages::developer_tools'
        // No index - placed after other menu items
    );
}
```

**Why This Matters:**
- Low indexes (< 50) conflict with WordPress core menu items
- Conditional menus can disappear if index conflicts with always-present menu
- Debug/developer menus should use high indexes (96-99) to avoid shifting other menus

**Best Practice:**
- Omit index parameter unless you need specific positioning
- Use 96-99 for debug/developer tools
- Never use indexes < 50

**Reference:** Fixed in v6.4.126 - moved Developer Tools menu from index 5 to 99.

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
