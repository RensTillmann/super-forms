---
name: h-implement-developer-tools-page
branch: feature/developer-tools-page
status: pending
created: 2025-11-01
---

# Developer Tools Page for EAV Migration Testing

## Problem/Goal

The EAV migration system is functionally complete but untested with real data. We need a comprehensive developer tools page (visible only when `DEBUG_SF = true`) that allows developers to:

1. Generate synthetic test contact entries in old serialized format
2. Control migration process (start, pause, reset, rollback)
3. Run automated verification tests to ensure data integrity
4. Benchmark performance improvements (CSV export, listings filters, admin search)
5. Inspect database state and statistics
6. Clean up test data safely

This page will enable rapid testing cycles without requiring a staging environment, validate the 30-60x performance claims, and provide confidence for production deployment.

## Success Criteria

**Functional Completeness:**
- [ ] Page only appears when `DEBUG_SF = true` in wp-config.php
- [ ] All 7 sections implemented and functional (per ASCII design reference)
- [ ] Test data generator creates entries with all data complexity options
- [ ] Migration controls can start/pause/reset migration
- [ ] All 10 automated verification tests execute and report results
- [ ] Performance benchmarks measure and display serialized vs EAV improvements
- [ ] Database inspector shows accurate statistics
- [ ] Cleanup functions safely delete only test entries

**Data Safety:**
- [ ] Test entries tagged with `_super_test_entry = true` meta
- [ ] Destructive actions require confirmation dialogs
- [ ] "Delete Everything" requires typing confirmation phrase
- [ ] No accidental deletion of real (non-test) entries possible

**Testing Validation:**
- [ ] Full test cycle completes successfully (generate → migrate → verify → benchmark → cleanup)
- [ ] Verification tests correctly identify data integrity issues (if any)
- [ ] Performance benchmarks show measurable improvements (10-100x for export, 30-60x for listings)
- [ ] Rollback functionality preserves data integrity

**User Experience:**
- [ ] All AJAX operations show loading/progress indicators
- [ ] Success/error messages display for all actions
- [ ] Page responsive on mobile devices
- [ ] Real-time updates for migration progress

**Security:**
- [ ] All AJAX handlers verify nonces
- [ ] Quick SQL executor uses query whitelist only
- [ ] Requires `manage_options` capability
- [ ] Audit logging for all developer actions

**Code Quality:**
- [ ] WordPress coding standards followed
- [ ] Proper escaping/sanitization throughout
- [ ] No PHP errors or warnings
- [ ] JavaScript console clean (no errors)

## Visual Design

The page follows this 7-section layout:

```
┌──────────────────────────────────────────────────────────────────────┐
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ QUICK ACTIONS                                              │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  [🎯 Full Test Cycle]  [🔄 Reset Everything]  [📊 Docs]  │    │
│  │                                                            │    │
│  │  Full Test Cycle: Generate 1000 entries → Migrate →       │    │
│  │  Verify → Benchmark → Report results                      │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ 1️⃣  TEST DATA GENERATOR                                    │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  Generate synthetic contact entries (old serialized format)│    │
│  │                                                            │    │
│  │  Entry Count:                                              │    │
│  │  ○ 10      ○ 100      ○ 1,000      ○ 10,000    Custom: [__]│    │
│  │                                                            │    │
│  │  Data Complexity:                                          │    │
│  │  ☑ Basic text (name, email, phone)                        │    │
│  │  ☑ Special characters (UTF-8: é, ñ, 中文, emoji: 🚀)       │    │
│  │  ☑ Long text fields (>10KB lorem ipsum)                   │    │
│  │  ☑ Numeric values (integers, decimals)                    │    │
│  │  ☑ Empty/null values                                      │    │
│  │  ☑ Checkbox arrays (multi-select)                         │    │
│  │  ☑ File upload URLs                                       │    │
│  │                                                            │    │
│  │  Assign to Form:                                           │    │
│  │  [Select Form ▼]  (Creates generic form if none selected) │    │
│  │                                                            │    │
│  │  Entry Dates:                                              │    │
│  │  ○ All today                                               │    │
│  │  ○ Random past 30 days                                     │    │
│  │  ○ Random past year                                        │    │
│  │                                                            │    │
│  │  [Generate Test Entries]  [Delete All Test Entries]       │    │
│  │                                                            │    │
│  │  Status: Ready to generate                                 │    │
│  │  ┌────────────────────────────────────────────────────┐   │    │
│  │  │ [Log area - results appear here]                   │   │    │
│  │  │                                                     │   │    │
│  │  └────────────────────────────────────────────────────┘   │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ 2️⃣  MIGRATION CONTROLS                                     │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  Current Status: ● Not Started                             │    │
│  │  Progress: [████████░░░░░░░░░░░░░░] 0 / 0 (0%)           │    │
│  │                                                            │    │
│  │  Actions:                                                  │    │
│  │  [▶️ Start Migration]  [⏸️ Pause]  [⏹️ Stop]               │    │
│  │                                                            │    │
│  │  Advanced:                                                 │    │
│  │  [🔄 Reset to Not Started]                                │    │
│  │  [⚡ Force Complete (skip actual migration)]              │    │
│  │  [🔙 Rollback to Serialized]                              │    │
│  │  [⏩ Force Switch to EAV]                                  │    │
│  │                                                            │    │
│  │  Settings:                                                 │    │
│  │  Batch Size: [10 ▼] 5/10/25/50/100                        │    │
│  │  Delay: [500ms ▼] 0/100/250/500/1000                      │    │
│  │                                                            │    │
│  │  [Open Migration Admin Page →]                            │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ 3️⃣  AUTOMATED VERIFICATION                                 │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  Run automated tests to verify data integrity              │    │
│  │                                                            │    │
│  │  [▶️ Run All Tests]  [Run Selected Tests]                 │    │
│  │                                                            │    │
│  │  Tests:                                    Status   Time   │    │
│  │  ☑ Data Integrity (EAV ↔ Serialized)      ⏸️ Idle   --    │    │
│  │  ☑ Field Count Match                       ⏸️ Idle   --    │    │
│  │  ☑ Field Values Match                      ⏸️ Idle   --    │    │
│  │  ☑ CSV Export Byte-Comparison              ⏸️ Idle   --    │    │
│  │  ☑ CSV Import Roundtrip                    ⏸️ Idle   --    │    │
│  │  ☑ Listings Query Accuracy                 ⏸️ Idle   --    │    │
│  │  ☑ Search Query Accuracy                   ⏸️ Idle   --    │    │
│  │  ☑ Bulk Fetch Consistency                  ⏸️ Idle   --    │    │
│  │  ☑ Empty Entry Handling                    ⏸️ Idle   --    │    │
│  │  ☑ Special Characters Preservation         ⏸️ Idle   --    │    │
│  │                                                            │    │
│  │  Summary: 0/10 passed, 0 failed, 10 not run               │    │
│  │                                                            │    │
│  │  ┌────────────────────────────────────────────────────┐   │    │
│  │  │ [Detailed results appear here]                     │   │    │
│  │  │                                                     │   │    │
│  │  │ Example:                                            │   │    │
│  │  │ ✓ Data Integrity - 1000/1000 entries match         │   │    │
│  │  │ ✓ CSV Export - Files identical (MD5 match)         │   │    │
│  │  │ ✗ Search Accuracy - 3 mismatches found             │   │    │
│  │  │   → Entry #123: Expected "John", got "Jon"         │   │    │
│  │  │                                                     │   │    │
│  │  └────────────────────────────────────────────────────┘   │    │
│  │                                                            │    │
│  │  [Download Test Report (JSON)]  [Export to CSV]           │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ 4️⃣  PERFORMANCE BENCHMARKS                                 │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  Measure real-world performance improvements               │    │
│  │                                                            │    │
│  │  [▶️ Run All Benchmarks]  [Run Selected]                  │    │
│  │                                                            │    │
│  │  Entry Count for Tests: [100 ▼] 10/100/1000/10000         │    │
│  │                                                            │    │
│  │  ┌─────────────────────────────────────────────────────┐  │    │
│  │  │ CSV Export (1000 entries)                           │  │    │
│  │  │                                                      │  │    │
│  │  │ Serialized:  ████████████████████  3,245ms          │  │    │
│  │  │ EAV:         █  28ms                                 │  │    │
│  │  │                                                      │  │    │
│  │  │ Improvement: 115.9x faster 🔥                       │  │    │
│  │  └─────────────────────────────────────────────────────┘  │    │
│  │                                                            │    │
│  │  ┌─────────────────────────────────────────────────────┐  │    │
│  │  │ Listings Filter (field="email" value="@test.com")   │  │    │
│  │  │                                                      │  │    │
│  │  │ Serialized:  █████████████  1,856ms                 │  │    │
│  │  │ EAV:         █  42ms                                 │  │    │
│  │  │                                                      │  │    │
│  │  │ Improvement: 44.2x faster ⚡                         │  │    │
│  │  └─────────────────────────────────────────────────────┘  │    │
│  │                                                            │    │
│  │  ┌─────────────────────────────────────────────────────┐  │    │
│  │  │ Admin Search (keyword="john")                        │  │    │
│  │  │                                                      │  │    │
│  │  │ Serialized:  ████████  978ms                         │  │    │
│  │  │ EAV:         █  31ms                                 │  │    │
│  │  │                                                      │  │    │
│  │  │ Improvement: 31.5x faster ⚡                         │  │    │
│  │  └─────────────────────────────────────────────────────┘  │    │
│  │                                                            │    │
│  │  [Download Benchmark Report]  [Compare with Previous]     │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ 5️⃣  DATABASE INSPECTOR                                     │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  View database state and statistics                        │    │
│  │                                                            │    │
│  │  wp_postmeta (Serialized):                                │    │
│  │  └─ _super_contact_entry_data: 1,234 rows                 │    │
│  │                                                            │    │
│  │  wp_superforms_entry_data (EAV):                          │    │
│  │  └─ Total rows: 45,678                                    │    │
│  │  └─ Unique entries: 1,234                                 │    │
│  │  └─ Unique field names: 37                                │    │
│  │  └─ Avg fields per entry: 37                              │    │
│  │  └─ Table size: 2.4 MB                                    │    │
│  │                                                            │    │
│  │  Index Status:                                             │    │
│  │  ✓ PRIMARY (id)                                           │    │
│  │  ✓ idx_entry_id                                           │    │
│  │  ✓ idx_field_name                                         │    │
│  │  ✓ idx_entry_field (composite)                            │    │
│  │  ✓ idx_field_value (text, 100 chars)                      │    │
│  │                                                            │    │
│  │  [Run ANALYZE TABLE]  [Check Index Usage]                 │    │
│  │  [View Sample Entry Data]  [Compare Storage Sizes]        │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ 6️⃣  CLEANUP & RESET                                        │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  ⚠️ DANGER ZONE - These actions affect database           │    │
│  │                                                            │    │
│  │  Selective Cleanup:                                        │    │
│  │  [Delete Test Entries Only] (0 found)                     │    │
│  │  [Delete All EAV Data] (keeps serialized)                 │    │
│  │  [Delete All Serialized Data] (keeps EAV)                 │    │
│  │                                                            │    │
│  │  Full Reset:                                               │    │
│  │  [Reset Migration Status to "Not Started"]                │    │
│  │  [Delete Everything & Reset] (all entries + migration)    │    │
│  │                                                            │    │
│  │  Database Maintenance:                                     │    │
│  │  [Optimize EAV Tables]                                     │    │
│  │  [Rebuild Indexes]                                         │    │
│  │  [Vacuum Orphaned Data]                                    │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ 7️⃣  DEVELOPER UTILITIES                                    │    │
│  ├────────────────────────────────────────────────────────────┤    │
│  │                                                            │    │
│  │  [Export Migration Status (JSON)]                         │    │
│  │  [View Migration Logs]                                     │    │
│  │  [Generate Test Report]                                    │    │
│  │  [View PHP Error Log]                                      │    │
│  │  [Enable Query Debugging]                                  │    │
│  │                                                            │    │
│  │  Quick SQL:                                                │    │
│  │  ┌──────────────────────────────────────────────────┐     │    │
│  │  │ SELECT COUNT(*) FROM wp_superforms_entry_data    │     │    │
│  │  │ WHERE field_name = 'email'                       │     │    │
│  │  └──────────────────────────────────────────────────┘     │    │
│  │  [Execute Query]  [Load Common Queries ▼]                 │    │
│  │                                                            │    │
│  │  Results: [Query results appear here]                     │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

## Implementation Requirements

### File Structure

**New Files:**
- `/src/includes/admin/views/page-developer-tools.php` - UI page
- `/src/includes/class-developer-tools.php` - Backend logic class
- `/src/assets/js/backend/developer-tools.js` - AJAX interactions
- `/src/assets/css/backend/developer-tools.css` - Styling

**Modified Files:**
- `/src/super-forms.php` - Add menu registration (only when DEBUG_SF = true)

### Key Features

**1. Test Data Generator**
- Generate 10/100/1,000/10,000 synthetic entries
- Configurable data patterns (UTF-8, long text, empty values, etc.)
- Tag entries with `_super_test_entry = true` meta
- Assign to specific form or create generic form
- Configurable entry dates (today, past 30 days, past year)
- Display generation progress and results

**2. Migration Controls**
- Display current migration status and progress
- Start/Pause/Stop migration
- Advanced controls: Reset, Force Complete, Rollback, Force EAV switch
- Configurable batch size and delay
- Link to Migration Admin Page
- Real-time progress updates via AJAX

**3. Automated Verification**
- 10 automated tests for data integrity
- Compare serialized vs EAV data
- CSV export byte-comparison (MD5)
- CSV import roundtrip testing
- Listings and search query accuracy
- Display pass/fail status with timing
- Detailed error reporting with specific mismatches
- Export test reports (JSON/CSV)

**4. Performance Benchmarks**
- Measure CSV export (serialized vs EAV)
- Measure listings filter (SUBSTRING_INDEX vs indexed JOIN)
- Measure admin search (serialized vs EAV)
- Configurable entry count for tests
- Visual progress bars showing relative performance
- Calculate improvement multiplier (e.g., "115.9x faster")
- Download benchmark reports
- Compare with previous runs

**5. Database Inspector**
- Show wp_postmeta row count for serialized data
- Show wp_superforms_entry_data statistics (rows, entries, fields, size)
- Display index status (PRIMARY, idx_entry_id, etc.)
- Run ANALYZE TABLE
- Check index usage stats
- View sample entry data
- Compare storage sizes

**6. Cleanup & Reset**
- Delete test entries only (tagged with `_super_test_entry`)
- Delete all EAV data (keep serialized)
- Delete all serialized data (keep EAV)
- Reset migration status
- Delete everything and reset
- Optimize EAV tables
- Rebuild indexes
- Vacuum orphaned data
- Require confirmation for all destructive actions

**7. Developer Utilities**
- Export migration status as JSON
- View migration logs
- Generate comprehensive test report
- View PHP error log
- Enable query debugging (SAVEQUERIES)
- Quick SQL executor with common query templates
- Display query results

### Technical Specifications

**Access Control:**
```php
// Only show page when DEBUG_SF is true
if ( ! defined( 'DEBUG_SF' ) || DEBUG_SF !== true ) {
    return; // Don't register menu
}

// Require admin capability
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized' );
}
```

**AJAX Handlers:**
- `super_dev_generate_entries` - Generate test data
- `super_dev_delete_test_entries` - Delete tagged test entries
- `super_dev_run_verification` - Run verification tests
- `super_dev_run_benchmarks` - Run performance benchmarks
- `super_dev_reset_migration` - Reset migration status
- `super_dev_rollback` - Rollback to serialized
- `super_dev_execute_sql` - Execute SQL query (with whitelist)

**Security:**
- Nonce verification for all AJAX requests
- SQL query whitelist for Quick SQL executor
- Confirmation dialogs for destructive actions
- Audit logging for all developer actions

**UI/UX:**
- WordPress admin styling (postbox, button classes)
- Responsive layout (mobile-friendly)
- Collapsible sections
- Real-time updates via AJAX polling
- Progress indicators
- Success/error notifications
- Tooltips for complex features

### Testing Workflow

**Recommended developer cycle:**
1. Enable `DEBUG_SF = true` in wp-config.php
2. Navigate to Super Forms > Developer Tools
3. Generate 1,000 test entries with all data patterns
4. Start migration and monitor progress
5. Run automated verification tests
6. Run performance benchmarks
7. Review results and validate claims
8. Test rollback functionality
9. Verify data integrity after rollback
10. Cleanup test entries
11. Reset everything

**Expected results:**
- All 10 verification tests pass
- CSV export 10-100x faster with EAV
- Listings filter 30-60x faster with EAV
- Admin search significantly faster with EAV
- Rollback preserves all data integrity

## Context Manifest

### How the WordPress Admin Menu System Works in Super Forms

The admin menu registration follows WordPress's standard pattern and is handled through a dedicated menu class. When WordPress loads the admin area, it fires the `admin_menu` action hook. Super Forms hooks into this via `add_action('admin_menu', 'SUPER_Menu::register_menu')` in `/src/super-forms.php` at line 376. This registration happens during the plugin's initialization phase, specifically when `$this->is_request('admin')` returns true, meaning we're in the WordPress admin dashboard.

The menu registration itself is centralized in `/src/includes/class-menu.php` in the `SUPER_Menu::register_menu()` static method (lines 28-131). This method uses WordPress's `add_menu_page()` to create the top-level "Super Forms" menu, then uses multiple `add_submenu_page()` calls to register child menu items like "Your Forms", "Create Form", "Settings", "Migration", "Contact Entries", "Documentation", "Licenses", and "Demos". Each submenu page specifies a callback function that renders the page content - these callbacks live in the `SUPER_Pages` class.

For our Developer Tools page, we need to add a conditional submenu item that ONLY appears when `DEBUG_SF` constant is defined and set to `true` in wp-config.php. The pattern already exists in the codebase - we'll add a conditional check before the `add_submenu_page()` call. The menu item should appear after the Migration page but before Contact Entries. The callback will be `SUPER_Pages::developer_tools`, which we'll implement as a static method that includes our view file.

**Critical Menu Registration Pattern:**
```php
// In SUPER_Menu::register_menu() after line 69 (after Migration menu)
if (defined('DEBUG_SF') && DEBUG_SF === true) {
    add_submenu_page(
        'super_forms',                           // Parent slug
        esc_html__('Developer Tools', 'super-forms'),  // Page title
        esc_html__('Developer Tools', 'super-forms'),  // Menu title
        'manage_options',                        // Capability required
        'super_developer_tools',                 // Menu slug
        'SUPER_Pages::developer_tools'           // Callback function
    );
}
```

### How Admin Page Views Are Structured

Admin pages in Super Forms follow a clean separation pattern: the page callback in `SUPER_Pages` class simply includes a view file from `/src/includes/admin/views/`. For example, the Migration page (lines 2476-2478 in class-pages.php) shows this pattern:

```php
public static function migration() {
    include_once SUPER_PLUGIN_DIR . '/includes/admin/views/page-migration.php';
}
```

The view file (`page-migration.php`) is a complete HTML/PHP template that contains ALL the markup, inline styles, and inline JavaScript for the page. This is the WordPress admin pattern - self-contained page templates. The migration page demonstrates this perfectly with 815 lines containing the full page structure, CSS in `<style>` tags, and JavaScript in `<script>` tags at the bottom.

The page starts with a security check to ensure direct access is prevented (`if (!defined('ABSPATH')) exit;`), then loads any necessary data (like migration status via `SUPER_Migration_Manager::get_migration_status()`), creates a nonce for AJAX security (`wp_create_nonce('super-form-builder')`), and then outputs the complete HTML structure using WordPress admin styling classes.

**Page Structure Pattern:**
1. Security check and data loading at the top
2. WordPress admin wrapper: `<div class="wrap super-[page-name]">`
3. Page title: `<h1>` tag
4. Content sections wrapped in cards/postboxes
5. Inline `<style>` for page-specific CSS
6. Inline `<script>` for jQuery-based interactivity
7. Hidden input for nonce: `<input type="hidden" id="super-nonce" value="<?php echo esc_attr($nonce); ?>" />`

For our Developer Tools page, we'll create `/src/includes/admin/views/page-developer-tools.php` following this exact pattern. The page will be self-contained with all CSS and JavaScript inline, using WordPress's standard admin UI classes for consistency.

### How AJAX Handlers Work in Super Forms

AJAX in Super Forms uses a systematic registration and handling pattern. All AJAX handlers are centralized in `/src/includes/class-ajax.php`, which is a massive file (6000+ lines) containing all backend AJAX operations. The class initializes AJAX hooks through the `init()` method (line 28), which loops through a large array of AJAX action names and registers them using WordPress's AJAX hook system.

The registration pattern (lines 123-127) shows the dual-hook approach:
```php
add_action('wp_ajax_super_' . $ajax_event, array(__CLASS__, $ajax_event));
if ($ajax_allow_anonymous) {
    add_action('wp_ajax_nopriv_super_' . $ajax_event, array(__CLASS__, $ajax_event));
}
```

This means when JavaScript calls `ajaxurl` with `action: 'super_migration_start'`, WordPress routes it to `SUPER_Ajax::migration_start()`. The method naming convention is critical - the AJAX action name MUST match the static method name in the class.

**Existing Migration AJAX Handlers Pattern (lines 6286-6399):**

Each AJAX handler follows a strict security and response pattern:

1. **Permission Check**: First thing is always capability verification
```php
if (!current_user_can('manage_options')) {
    wp_send_json_error(array('message' => esc_html__('You do not have permission...', 'super-forms')));
}
```

2. **Process Request**: Call the appropriate manager/helper class method
```php
$result = SUPER_Migration_Manager::start_migration();
```

3. **Error Handling**: Check for WP_Error and send appropriate response
```php
if (is_wp_error($result)) {
    wp_send_json_error(array('message' => $result->get_error_message()));
}
```

4. **Success Response**: Send data back to JavaScript
```php
wp_send_json_success($result);
```

The migration handlers demonstrate this pattern perfectly:
- `migration_start()` - Starts the migration process
- `migration_process_batch()` - Processes batch of entries
- `migration_get_status()` - Returns current status
- `migration_rollback()` - Rolls back to serialized
- `get_entry_ids()` - Returns all entry IDs for validation
- `validate_entries()` - Validates entry data integrity

For our Developer Tools page, we'll need to create new AJAX handlers following this exact pattern for operations like:
- `dev_generate_entries` - Generate test contact entries
- `dev_delete_test_entries` - Delete entries tagged as test
- `dev_run_verification` - Run verification tests
- `dev_run_benchmarks` - Run performance benchmarks
- `dev_reset_migration` - Reset migration status
- `dev_execute_sql` - Execute whitelisted SQL queries
- `dev_get_db_stats` - Get database statistics

### How Contact Entries Are Created - The Complete Data Flow

Contact entry creation happens in the massive `submit_form()` method in `/src/includes/class-ajax.php` (starting around line 4559). This is THE entry point for all form submissions. The flow is:

1. **Data Collection**: Form fields are validated and collected into a structured array where each field name is a key containing an associative array with `value`, `type`, `label`, and metadata.

2. **Conditional Save Check** (lines 4779-4815): The system checks if contact entry saving should happen based on the `save_contact_entry` setting and any conditional logic. The setting defaults to 'yes' but can be conditionally disabled.

3. **Post Creation** (lines 4818-4830): If saving is enabled, a WordPress post is created:
```php
$post = array(
    'post_status' => 'super_unread',
    'post_type'   => 'super_contact_entry',
    'post_parent' => $form_id,  // Links entry to form
);
$contact_entry_id = wp_insert_post($post);
```

4. **Title Generation** (lines 4841-4867): The entry title is generated from a configurable template that can include field values via the {tags} system. For example, `{first_name} {last_name} - Entry` would become "John Doe - Entry". If no custom title is set, it defaults to "Contact entry 123" (using the entry ID).

5. **Data Serialization** (happens later in the method): The entire field data array is serialized using PHP's `serialize()` function and stored as post meta under the key `_super_contact_entry_data`. This is the OLD storage method that the EAV migration is replacing.

**Entry Data Structure:**
```php
$data = array(
    'first_name' => array(
        'name'  => 'first_name',
        'value' => 'John',
        'type'  => 'text',
        'label' => 'First Name'
    ),
    'email' => array(
        'name'  => 'email',
        'value' => 'john@example.com',
        'type'  => 'email',
        'label' => 'Email Address'
    ),
    // ... more fields
);
```

For generating test entries in the Developer Tools page, we'll need to:
1. Create a WordPress post with `post_type = 'super_contact_entry'`
2. Set `post_parent` to the selected form ID (or 0 for generic)
3. Generate synthetic field data matching this structure
4. Store it using `SUPER_Data_Access::save_entry_data()` which handles storage based on migration state
5. Add a meta flag: `add_post_meta($entry_id, '_super_test_entry', true)` to mark it as test data
6. Set the post_date to achieve date distribution (today, past 30 days, past year)

### How the Data Access Layer Works - The Migration Abstraction

The Data Access Layer (`/src/includes/class-data-access.php`) is the BRILLIANT abstraction that makes the EAV migration transparent to the rest of the codebase. This 597-line class provides a unified API for reading and writing contact entry data, automatically detecting whether to use serialized storage or EAV tables based on the migration state.

**The Core Intelligence - Storage Detection:**

Every data access operation checks the migration status first (lines 47-53):
```php
$migration = get_option('superforms_eav_migration');
$use_eav = false;
if (!empty($migration) && $migration['status'] === 'completed') {
    $use_eav = ($migration['using_storage'] === 'eav');
}
```

This means the system can be in one of several states:
- **Not Started**: Uses serialized storage only
- **In Progress**: Uses serialized storage for reads, DUAL-WRITES to both
- **Completed (EAV)**: Uses EAV tables exclusively (with serialized fallback for safety)
- **Rolled Back**: Uses serialized storage only

**Critical Methods Available:**

1. **`get_entry_data($entry_id)`** (lines 30-68): Returns entry data array in consistent format regardless of storage. Uses EAV if migration completed, falls back to serialized if EAV data missing. Returns WP_Error on failure.

2. **`save_entry_data($entry_id, $data)`** (lines 84-115): Implements the phase-based write strategy:
   - Before migration: Serialized only
   - During migration: BOTH (dual-write for safety)
   - After migration (EAV): EAV only
   - After rollback: Serialized only

3. **`get_bulk_entry_data($entry_ids)`** (lines 349-428): THE PERFORMANCE WINNER. Fetches multiple entries in a single query to avoid N+1 problem. For EAV mode, uses one query with IN clause to get all data at once. Returns array indexed by entry_id.

4. **`validate_entry_integrity($entry_id)`** (lines 453-557): Compares data between EAV and serialized storage to ensure migration was successful. Returns detailed validation result with:
   - Field count comparison
   - Field value comparison (normalized for arrays/objects)
   - Missing field detection
   - Extra field detection
   - Specific mismatch details

5. **`bulk_validate_integrity($entry_ids)`** (lines 566-593): Validates multiple entries, returns summary with total/valid/invalid counts and detailed error array.

**For Developer Tools Implementation:**

We'll use these Data Access methods extensively:
- `get_entry_data()` for reading individual entries
- `save_entry_data()` for creating test entries (handles dual-write automatically)
- `get_bulk_entry_data()` for performance benchmarks (to test the bulk optimization)
- `validate_entry_integrity()` for automated verification tests
- `bulk_validate_integrity()` for full dataset validation

The beauty is we don't need to worry about migration state - the Data Access Layer handles it all.

### How the Migration Manager Works - State Machine and Batch Processing

The Migration Manager (`/src/includes/class-migration-manager.php`) orchestrates the background migration process with a state machine approach. It's 343 lines of carefully designed batch processing logic.

**Migration State Machine:**

The migration goes through these states (stored in `superforms_eav_migration` option):
1. **not_started**: Initial state, nothing migrated yet
2. **in_progress**: Actively processing entries in batches
3. **completed**: All entries migrated successfully

The state object contains (lines 55-66):
```php
array(
    'status'               => 'in_progress',
    'using_storage'        => 'serialized',  // 'serialized' or 'eav'
    'total_entries'        => 1234,
    'migrated_entries'     => 450,
    'failed_entries'       => array(),       // entry_id => error_message
    'started_at'           => '2025-11-01 10:30:00',
    'completed_at'         => '',
    'last_processed_id'    => 450,           // Resume point
    'verification_passed'  => false,
    'rollback_available'   => true,
)
```

**Available Methods:**

1. **`start_migration()`** (lines 32-71):
   - Counts total entries with `SELECT COUNT(*) WHERE post_type = 'super_contact_entry'`
   - Initializes state to 'in_progress'
   - Sets `using_storage` to 'serialized' (still reading from serialized during migration)
   - Returns state array or WP_Error

2. **`process_batch($batch_size = null)`** (lines 80-151):
   - Default batch size is 10 (const BATCH_SIZE)
   - Fetches next batch: `WHERE ID > last_processed_id ORDER BY ID ASC LIMIT batch_size`
   - Calls `migrate_entry()` for each entry
   - Updates progress tracking
   - Returns detailed batch result with processed/failed counts, progress percentage
   - Automatically calls `complete_migration()` when no entries remain

3. **`migrate_entry($entry_id)`** (lines 160-232):
   - Reads serialized data: `get_post_meta($entry_id, '_super_contact_entry_data', true)`
   - Unserializes the data array
   - Deletes any existing EAV data for the entry
   - Inserts each field into EAV table (handles array values by JSON encoding)
   - Returns true on success or WP_Error on failure

4. **`complete_migration()`** (lines 240-270):
   - Sets status to 'completed'
   - **CRITICAL**: Sets `using_storage` to 'eav' (this is the switch!)
   - Sets completed timestamp
   - Returns completion summary

5. **`rollback_migration()`** (lines 278-306):
   - Can only rollback completed migrations
   - Switches `using_storage` back to 'serialized'
   - Increments rollback counter
   - Does NOT delete EAV data (allows re-switching)

6. **`reset_migration()`** (lines 324-339):
   - Resets state to 'not_started'
   - Clears all progress tracking
   - Used for testing or restarting

**For Developer Tools:**

We'll expose these migration controls:
- Start/Pause/Resume via the AJAX batch processing pattern
- Reset via `reset_migration()`
- Rollback via `rollback_migration()`
- Force complete (call `complete_migration()` directly - DANGEROUS)
- Force EAV switch (manually set `using_storage` to 'eav')
- Configurable batch size and delay between batches

### WordPress Admin UI Patterns - Styling and JavaScript

The Migration page demonstrates the complete WordPress admin UI pattern. Here's how it works:

**HTML Structure:**
```php
<div class="wrap super-migration-page">  // WordPress admin wrapper
    <h1>Page Title</h1>  // Standard WP admin page title

    // Notice boxes using custom classes
    <div class="sfui-notice sfui-blue">
        <h3>Section Title</h3>
        <p>Content...</p>
        <ul>...</ul>
    </div>

    // Content cards/sections
    <div class="super-migration-status-card">
        <h2>Section Heading</h2>
        <table class="widefat">  // WordPress table style
            <tbody>
                <tr>
                    <th>Label:</th>
                    <td>Value</td>
                </tr>
            </tbody>
        </table>

        // Buttons using WordPress classes
        <button class="button button-primary button-hero">Action</button>
        <button class="button button-secondary">Secondary</button>
    </div>
</div>
```

**WordPress Button Classes:**
- `button` - Base button style
- `button-primary` - Blue primary action button
- `button-secondary` - Gray secondary button
- `button-hero` - Larger size for prominent actions

**Custom Style Patterns** (lines 196-478):
The migration page uses inline `<style>` tags with custom classes:
- `.sfui-notice` - Notice boxes with color variants (.sfui-blue, .sfui-yellow, .sfui-green)
- `.sfui-badge` - Status badges with color variants
- Progress bars with `.super-migration-progress-bar` and animated `.super-migration-progress-fill`
- Log areas with `.super-migration-log` and individual `.super-migration-log-entry` items

**JavaScript Pattern** (lines 481-813):
Uses jQuery (WordPress includes it by default) with:
- AJAX calls using `ajaxurl` (WordPress global variable)
- Nonce passed via hidden input: `$('#super-migration-nonce').val()`
- Batch processing with recursive function calls: `processBatch()` calls itself
- Real-time UI updates using jQuery selectors
- Error handling with try/catch and fallback messages

**AJAX Call Pattern:**
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'super_migration_start',  // Maps to AJAX handler
        security: migrationNonce          // Nonce for verification
    },
    success: function(response) {
        if (response.success) {
            // Handle success: response.data contains result
        } else {
            // Handle error: response.data.message contains error
        }
    },
    error: function() {
        // Handle AJAX failure
    }
});
```

For the Developer Tools page, we'll follow this exact pattern with:
- Inline styles using similar class naming conventions (`.super-devtools-*`)
- jQuery-based AJAX interactions
- Real-time progress updates
- Confirmation dialogs for destructive actions
- Log areas for operation feedback

### Database Schema and Statistics Queries

The EAV table structure (from `/src/includes/class-install.php` lines 76-91):

```sql
CREATE TABLE wp_superforms_entry_data (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_id BIGINT(20) UNSIGNED NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    field_value LONGTEXT,
    field_type VARCHAR(50),
    field_label VARCHAR(255),
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY entry_id (entry_id),           -- Fast single entry lookups
    KEY field_name (field_name),       -- Fast field filtering
    KEY entry_field (entry_id, field_name),  -- Composite for specific field queries
    KEY field_value (field_value(191))  -- Prefix index for searches
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Database Statistics Queries for Developer Tools:**

1. **Serialized Storage Stats:**
```sql
SELECT COUNT(*) FROM wp_postmeta
WHERE meta_key = '_super_contact_entry_data'
```

2. **EAV Table Stats:**
```sql
-- Total rows
SELECT COUNT(*) FROM wp_superforms_entry_data

-- Unique entries
SELECT COUNT(DISTINCT entry_id) FROM wp_superforms_entry_data

-- Unique field names
SELECT COUNT(DISTINCT field_name) FROM wp_superforms_entry_data

-- Average fields per entry
SELECT AVG(field_count) FROM (
    SELECT entry_id, COUNT(*) as field_count
    FROM wp_superforms_entry_data
    GROUP BY entry_id
) as subquery

-- Table size
SELECT
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
AND table_name = 'wp_superforms_entry_data'
```

3. **Index Usage Stats:**
```sql
SHOW INDEX FROM wp_superforms_entry_data
```

4. **Sample Entry Data:**
```sql
SELECT entry_id, field_name, LEFT(field_value, 50) as value_preview
FROM wp_superforms_entry_data
WHERE entry_id = [entry_id]
LIMIT 20
```

### Test Entry Generation Strategy

For generating realistic test entries with various data patterns, we need to create entries that match real-world complexity:

**Field Data Patterns:**

1. **Basic Text Fields:**
```php
'first_name' => array(
    'name'  => 'first_name',
    'value' => 'John',
    'type'  => 'text',
    'label' => 'First Name'
)
```

2. **Special Characters (UTF-8):**
```php
'name_international' => array(
    'name'  => 'name_international',
    'value' => 'José Müller 中文名 🚀',  // Accents, umlauts, Chinese, emoji
    'type'  => 'text',
    'label' => 'International Name'
)
```

3. **Long Text Fields:**
```php
'description' => array(
    'name'  => 'description',
    'value' => str_repeat('Lorem ipsum dolor sit amet... ', 100),  // >10KB
    'type'  => 'textarea',
    'label' => 'Description'
)
```

4. **Numeric Values:**
```php
'age' => array(
    'name'  => 'age',
    'value' => '25',  // Stored as string but numeric
    'type'  => 'number',
    'label' => 'Age'
),
'price' => array(
    'name'  => 'price',
    'value' => '199.99',  // Decimal
    'type'  => 'number',
    'label' => 'Price'
)
```

5. **Empty/Null Values:**
```php
'optional_field' => array(
    'name'  => 'optional_field',
    'value' => '',  // Empty string
    'type'  => 'text',
    'label' => 'Optional Field'
)
```

6. **Checkbox Arrays:**
```php
'interests' => array(
    'name'  => 'interests',
    'value' => array('sports', 'music', 'reading'),  // Will be JSON encoded in EAV
    'type'  => 'checkbox',
    'label' => 'Interests'
)
```

7. **File Upload URLs:**
```php
'attachment' => array(
    'name'  => 'attachment',
    'value' => 'http://example.com/uploads/document.pdf',
    'type'  => 'file',
    'label' => 'Attachment'
)
```

**Entry Creation Process:**
```php
// 1. Create post
$post_id = wp_insert_post(array(
    'post_type'   => 'super_contact_entry',
    'post_status' => 'super_unread',
    'post_parent' => $form_id,
    'post_title'  => 'Test Entry ' . $counter,
    'post_date'   => $generated_date  // For date distribution
));

// 2. Generate field data based on selected patterns
$data = array();
if ($include_basic_text) {
    $data['first_name'] = array(/* ... */);
    $data['email'] = array(/* ... */);
}
if ($include_special_chars) {
    $data['name_utf8'] = array(/* ... */);
}
// ... build data array

// 3. Save using Data Access Layer (handles migration state)
SUPER_Data_Access::save_entry_data($post_id, $data);

// 4. Mark as test entry
add_post_meta($post_id, '_super_test_entry', true);

// 5. If form_id specified, link it
if ($form_id > 0) {
    wp_update_post(array(
        'ID' => $post_id,
        'post_parent' => $form_id
    ));
}
```

**Date Distribution:**
- **All Today**: `'post_date' => current_time('mysql')`
- **Random Past 30 Days**: `'post_date' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'))`
- **Random Past Year**: `'post_date' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 365) . ' days'))`

### Performance Benchmarking Strategy

To validate the "30-60x faster" performance claims, we need to benchmark three critical operations:

**1. CSV Export Benchmark:**

Old serialized method (from class-ajax.php export logic):
- Fetches all entries: `get_posts(['post_type' => 'super_contact_entry'])`
- For each entry: `$data = get_post_meta($id, '_super_contact_entry_data', true)`
- Unserializes each: `$data = unserialize($serialized)`
- Extracts field values one-by-one
- Timing: Start timer, run export, end timer

New EAV method:
- Fetches entry IDs once
- Uses `SUPER_Data_Access::get_bulk_entry_data($entry_ids)` - SINGLE QUERY
- Extracts field values from returned array
- Timing: Start timer, run export, end timer

**Expected Result**: 10-100x faster for large datasets because bulk query eliminates N+1 problem.

**2. Listings Filter Benchmark:**

Old serialized method (from listings extension):
- Uses `SUBSTRING_INDEX()` parsing in SQL:
```sql
WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, 's:5:"email";s:$len:"', -1), '";', 1) = 'test@example.com'
```
- Scans EVERY entry's serialized blob
- No index usage possible
- Timing: Measure query execution time

New EAV method:
- Direct indexed JOIN:
```sql
INNER JOIN wp_superforms_entry_data eav ON eav.entry_id = post.ID
WHERE eav.field_name = 'email' AND eav.field_value = 'test@example.com'
```
- Uses `field_name` and `field_value` indexes
- Timing: Measure query execution time

**Expected Result**: 30-60x faster due to indexed lookups vs full table scans.

**3. Admin Search Benchmark:**

Old method:
- Uses LIKE on serialized meta_value: `WHERE meta_value LIKE '%search_term%'`
- Full table scan of wp_postmeta
- Timing: Measure query time

New method:
- Uses indexed field_value search:
```sql
WHERE field_value LIKE 'search_term%'  -- Uses prefix index
```
- Index-assisted search
- Timing: Measure query time

**Expected Result**: 30-50x faster for indexed field searches.

**Benchmark Implementation:**
```php
// Timing wrapper
function benchmark_operation($callback, $iterations = 1) {
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    $end = microtime(true);
    return ($end - $start) * 1000; // Return milliseconds
}

// Example usage
$serialized_time = benchmark_operation(function() use ($entry_ids) {
    // Old method: N+1 queries
    foreach ($entry_ids as $id) {
        $data = get_post_meta($id, '_super_contact_entry_data', true);
        $unserialized = unserialize($data);
    }
});

$eav_time = benchmark_operation(function() use ($entry_ids) {
    // New method: single bulk query
    $all_data = SUPER_Data_Access::get_bulk_entry_data($entry_ids);
});

$improvement = $serialized_time / $eav_time;  // e.g., 115.9x faster
```

### Security Considerations

**Access Control:**
```php
// Page visibility check (in SUPER_Menu::register_menu())
if (!defined('DEBUG_SF') || DEBUG_SF !== true) {
    return; // Don't register menu if DEBUG_SF not enabled
}

// Capability check (in page view)
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'super-forms'));
}
```

**AJAX Security:**
```php
// Every AJAX handler must verify:
1. Capability: if (!current_user_can('manage_options'))
2. Nonce: check_ajax_referer('super-form-builder', 'security')
```

**SQL Injection Prevention:**

For the Quick SQL executor, we need a WHITELIST approach:
```php
$allowed_queries = array(
    'count_entries' => "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_entry_data",
    'count_serialized' => "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_super_contact_entry_data'",
    'show_indexes' => "SHOW INDEX FROM {$wpdb->prefix}superforms_entry_data",
    'table_stats' => "SELECT COUNT(*) as rows, COUNT(DISTINCT entry_id) as entries FROM {$wpdb->prefix}superforms_entry_data",
);

// In AJAX handler
$query_key = sanitize_text_field($_POST['query_key']);
if (!isset($allowed_queries[$query_key])) {
    wp_send_json_error(array('message' => 'Query not allowed'));
}
$result = $wpdb->get_results($allowed_queries[$query_key]);
```

**Never** allow freeform SQL input - only predefined query templates.

**Destructive Action Protection:**
```javascript
// Confirmation dialogs
$('.super-devtools-delete-all').on('click', function() {
    var confirmText = prompt('Type "DELETE EVERYTHING" to confirm:');
    if (confirmText !== 'DELETE EVERYTHING') {
        alert('Confirmation text did not match. Operation cancelled.');
        return false;
    }
    // Proceed with deletion...
});
```

**Test Entry Safety:**

All test entries MUST be tagged:
```php
add_post_meta($entry_id, '_super_test_entry', true);
```

Deletion queries MUST verify this flag:
```php
// Get only test entries
$test_entries = $wpdb->get_col("
    SELECT post_id FROM {$wpdb->postmeta}
    WHERE meta_key = '_super_test_entry'
    AND meta_value = '1'
");

// Delete only these entries
foreach ($test_entries as $entry_id) {
    wp_delete_post($entry_id, true);  // true = force delete, skip trash
    SUPER_Data_Access::delete_entry_data($entry_id);
}
```

**Audit Logging:**

Log all destructive operations:
```php
error_log(sprintf(
    '[Super Forms Dev Tools] User %s performed action: %s at %s',
    wp_get_current_user()->user_login,
    $action_name,
    current_time('mysql')
));
```

### File Structure and Implementation Checklist

**Files to Create:**
1. `/src/includes/admin/views/page-developer-tools.php` - Main UI page (800+ lines like migration page)
2. `/src/includes/class-developer-tools.php` - Backend logic class for test data generation, benchmarks, etc.
3. `/src/assets/js/backend/developer-tools.js` - Optional separate JS file (or inline in view)
4. `/src/assets/css/backend/developer-tools.css` - Optional separate CSS (or inline in view)

**Files to Modify:**
1. `/src/includes/class-menu.php` - Add conditional menu registration (after line 69)
2. `/src/includes/class-pages.php` - Add `developer_tools()` callback method
3. `/src/includes/class-ajax.php` - Add all new AJAX handlers

**Implementation Priority:**

Phase 1 - Menu and Page Structure:
- Register menu (conditional on DEBUG_SF)
- Create page view with basic layout
- Add nonce and security checks

Phase 2 - Test Data Generator:
- Implement data generation logic in SUPER_Developer_Tools class
- Create AJAX handler for generation
- Wire up UI controls and progress tracking

Phase 3 - Migration Controls:
- Wire existing Migration Manager methods to UI
- Add advanced controls (force complete, force EAV switch)
- Implement batch size/delay configuration

Phase 4 - Verification Tests:
- Implement each of the 10 automated tests
- Create batch processing for validation
- Add detailed error reporting

Phase 5 - Performance Benchmarks:
- Implement timing wrappers
- Create benchmark comparison logic
- Add visual progress bars and multiplier calculations

Phase 6 - Database Inspector:
- Implement statistics queries
- Add index status checking
- Create sample data viewer

Phase 7 - Cleanup & Utilities:
- Implement safe deletion with test entry verification
- Add SQL query whitelist and executor
- Implement migration log viewer

### Critical Implementation Notes

**WordPress Hooks to Respect:**
- Don't bypass WordPress post creation - use `wp_insert_post()`
- Don't directly manipulate wp_posts - use post functions
- Use `$wpdb->prepare()` for all dynamic SQL
- Escape all output with appropriate functions

**Migration State Awareness:**
- Always check migration state before operations
- Use Data Access Layer for ALL entry data operations
- Never assume storage format - let the abstraction handle it

**User Experience:**
- Show loading indicators for all AJAX operations
- Display clear success/error messages
- Provide real-time progress for long operations
- Use confirmation dialogs for destructive actions

**Testing Strategy:**
- Test with DEBUG_SF = false (page should not appear)
- Test with no entries (empty state handling)
- Test with large datasets (10,000+ entries)
- Test all migration states (not_started, in_progress, completed, rolled_back)
- Test error scenarios (permission denied, database errors)

**Performance Considerations:**
- Use batch processing for operations on large datasets
- Implement client-side timeouts and retry logic
- Add server-side time limits for benchmarks
- Provide progress feedback every N operations

## User Notes
<!-- Any specific notes or requirements from the developer -->

## Work Log

### 2025-11-01

#### Completed
- Corrected documentation in subtask 19 (CSV export/import) - removed incorrect Zapier/Mailchimp CSV references, clarified that these integrations use webhooks/APIs
- Created comprehensive ASCII design for Developer Tools page with 7 sections (Quick Actions, Test Data Generator, Migration Controls, Automated Verification, Performance Benchmarks, Database Inspector, Cleanup & Reset, Developer Utilities)
- Created complete task file with detailed specifications for all features
- Defined 32 success criteria across 6 categories (functional, data safety, testing, UX, security, code quality)
- Generated comprehensive implementation context via context-gathering agent (830+ lines covering WordPress admin patterns, AJAX handlers, database schema, security, and implementation phases)
- Added task to database-architecture index under high priority
- Committed task file to repository

#### Decisions
- Developer Tools page will only be visible when `DEBUG_SF = true` in wp-config.php for security
- Test entries will be tagged with `_super_test_entry = true` meta to prevent accidental deletion of real data
- All destructive operations require confirmation dialogs ("Delete Everything" requires typing confirmation phrase)
- Quick SQL executor will use whitelist approach for security (no freeform SQL input)
- Implementation structured as FILE task type (single deliverable, estimated 1-1.5 days)

#### Discovered
- Clarified that Zapier and Mailchimp integrations use webhooks/APIs, not CSV files
- Identified three CSV export methods: XML via WordPress Tools, CSV via Settings (date range), CSV via Contact Entries bulk action
- Developer Tools page will enable rapid testing cycles without requiring staging environment
- Performance benchmarks will validate the claimed 30-60x improvements for listings and 10-100x for CSV exports

#### Next Steps
- Task status: pending (ready for implementation when needed)
- Branch: feature/developer-tools-page (will be created when task starts)
- Files to create: page-developer-tools.php, class-developer-tools.php, developer-tools.js, developer-tools.css
- Files to modify: class-menu.php (conditional menu registration), class-pages.php (callback), class-ajax.php (AJAX handlers)
