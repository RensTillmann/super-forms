---
name: h-implement-automatic-background-migration
branch: feature/automatic-background-migration
status: pending
created: 2025-11-03
---

# Implement Automatic Background Migration System

## Problem/Goal

The EAV migration system currently requires manual initiation and browser tab to stay open during processing (Yoast-style foreground processing). This creates friction for users updating the plugin and risks incomplete migrations if browser is closed.

**Goal**: Build a completely frictionless, automatic background migration system that:
- Detects unmigrated entries on plugin activation/update
- Processes migrations completely in background (no browser required)
- Uses Action Scheduler (bundled as library) for reliability
- Falls back to enhanced WP-Cron if Action Scheduler unavailable
- Verifies each entry after migration before deleting serialized data
- Runs silently without user intervention (unless errors occur)
- Completes within 24-48 hours for typical datasets (10K-50K entries)

## Success Criteria

### Automatic Detection & Triggering
- [ ] System detects unmigrated entries on plugin activation
- [ ] System detects unmigrated entries on plugin update
- [ ] System runs daily health check via WP-Cron to ensure completion
- [ ] System resumes interrupted migrations automatically

### Background Processing Infrastructure
- [ ] Action Scheduler bundled as library using git subtree
- [ ] Uses Action Scheduler if available (preferred path)
- [ ] Falls back to enhanced WP-Cron implementation if Action Scheduler unavailable
- [ ] Processes batches completely in background (survives browser/tab closing)
- [ ] Survives server restarts and continues processing
- [ ] Throttled appropriately (50-100 entries/batch, 5-10s delay between batches)

### Data Integrity & Verification
- [ ] Per-entry verification: compares EAV data with serialized data before deletion
- [ ] Serialized data preserved during migration (deletion commented out for safety)
- [ ] Failed verifications logged with detailed error messages
- [ ] Failed entries kept in both formats (serialized + EAV) for manual review
- [ ] Serialized data serves as built-in backup (no separate backup table needed)
- [ ] Rollback function available if needed

### User Experience (Frictionless)
- [ ] Zero user interaction required for normal operation
- [ ] No admin notices during migration (silent background operation)
- [ ] Progress visible in Developer Tools page (if user navigates there)
- [ ] Optional admin dashboard widget shows migration status
- [ ] Optional email notification on completion
- [ ] Migration completes within 24-48 hours for typical datasets

### Safety & Reliability
- [ ] Automatic retry for failed batches (3 attempts via Action Scheduler)
- [ ] Lock mechanism prevents concurrent migrations
- [ ] Detailed logging to debug.log with `[Super Forms Migration]` prefix
- [ ] Link to Action Scheduler monitoring UI (when available)
- [ ] No data loss if migration is interrupted
- [ ] Graceful degradation on errors

### Testing & Validation
- [ ] Tested with 1,000 entry dataset
- [ ] Tested with 10,000 entry dataset
- [ ] Tested with 100,000+ entry dataset
- [ ] Tested plugin activation flow
- [ ] Tested plugin update flow
- [ ] Tested resume after server restart
- [ ] Tested on shared hosting environment (resource constraints)
- [ ] Tested with WP-Cron disabled (DISABLE_WP_CRON constant)

## Context Manifest

### How EAV Migration Currently Works

**Current System Architecture:**

The EAV migration system transforms contact entry data from serialized WordPress post_meta storage (`_super_contact_entry_data`) to a dedicated EAV table (`wp_superforms_entry_data`). The system uses a foreground AJAX-based approach where the browser must remain open during processing.

**Entry Point & Flow:**

When a user initiates migration via the Developer Tools page (`/src/includes/admin/views/page-developer-tools.php` lines 294-405), they click "Start Migration" which triggers JavaScript that makes repeated AJAX calls to `super_migration_process_batch`. Here's the complete flow:

1. **User clicks "Start Migration"** → JavaScript in page-developer-tools.php (line 2377)
2. **AJAX call to** `wp_ajax_super_migration_process_batch` → routes to `SUPER_Ajax::migration_process_batch()` in class-ajax.php (lines 6328-6342)
3. **Batch processing** → calls `SUPER_Migration_Manager::process_batch()` in class-migration-manager.php (lines 80-151)
4. **Per-entry migration** → `migrate_entry()` method (lines 160-232) reads serialized data and writes to EAV
5. **JavaScript polls** until complete, then switches storage mode

**Migration State Tracking:**

The system uses a single WordPress option `superforms_eav_migration` with this structure:

```php
array(
    'status' => 'not_started|in_progress|completed',
    'using_storage' => 'serialized|eav',  // Controls which storage to read from
    'total_entries' => 1000,
    'migrated_entries' => 450,
    'failed_entries' => array(123 => 'error message'),
    'started_at' => '2025-01-15 10:30:00',
    'completed_at' => '',
    'last_processed_id' => 4567,  // Resume point for interrupted migrations
    'verification_passed' => false,
    'rollback_available' => true
)
```

This option is initialized in `SUPER_Install::init_migration_state()` (class-install.php lines 99-117) during plugin activation.

**Data Access Layer (The Read/Write Router):**

`SUPER_Data_Access` (class-data-access.php) abstracts storage format throughout the codebase. Every read/write goes through these methods:

- **Reading:** `get_entry_data($entry_id)` (lines 30-68) checks migration status, reads from EAV if `status=completed && using_storage=eav`, otherwise reads from serialized
- **Writing:** `save_entry_data($entry_id, $data)` (lines 84-115) implements phase-based strategy:
  - Phase 1 (not_started): Write serialized only
  - Phase 2 (in_progress): DUAL-WRITE to both (safety during migration)
  - Phase 3 (completed + rolled back): Write serialized only
  - Phase 4 (completed + using_storage=eav): Write EAV only (optimized)

This dual-write during migration ensures new entries created while migrating are available in both formats.

**Batch Processing Logic:**

`process_batch($batch_size)` in class-migration-manager.php:
- Default batch size: 10 entries (line 24: `const BATCH_SIZE = 10`)
- Queries next batch: `SELECT ID FROM wp_posts WHERE post_type='super_contact_entry' AND ID > last_processed_id ORDER BY ID ASC LIMIT 10` (lines 98-106)
- Loops through entries calling `migrate_entry()` for each
- Updates progress after each batch (lines 134-136)
- Returns progress info: `{processed, failed, total_processed, remaining, progress, is_complete}`
- Completes when no more entries found (line 110)

**Entry Migration Process:**

`migrate_entry($entry_id)` (lines 160-232):
1. Read serialized data: `get_post_meta($entry_id, '_super_contact_entry_data', true)` (line 162)
2. Unserialize: `@unserialize($data)` with error handling (lines 170-178)
3. Delete existing EAV data: `$wpdb->delete($table, array('entry_id' => $entry_id))` (line 193)
4. Insert each field: Loop through data array, insert into EAV table with `$wpdb->insert()` (lines 196-229)
   - Field structure: `{entry_id, field_name, field_value, field_type, field_label, created_at}`
   - Repeater fields (arrays) stored as JSON: `wp_json_encode($field_value)` (line 207)
5. Return true on success or WP_Error on failure

**CRITICAL: No verification or deletion happens in current system.** Serialized data remains intact after migration as a backup/rollback mechanism.

**Database Table Schema:**

EAV table created in `SUPER_Install::create_tables()` (class-install.php lines 76-91):

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
    KEY entry_id (entry_id),                    -- Lookup by entry
    KEY field_name (field_name),                -- Lookup by field
    KEY entry_field (entry_id, field_name),     -- Combined lookup
    KEY field_value (field_value(191))          -- Search/filter (prefix index for LONGTEXT)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

The table is created via `dbDelta()` which handles CREATE if not exists and schema updates automatically.

**Integration Points Where EAV is Used:**

Once `using_storage=eav`, these queries switch to EAV tables:

1. **Contact Entry Search** (super-forms.php lines 1712-1724): Admin search uses `field_value LIKE` on EAV table instead of serialized LIKE
2. **Contact Entry Sorting** (super-forms.php lines 2445-2455): Custom field sorting uses EAV subquery instead of slow serialized extraction
3. **Listings Extension** (extensions/listings/listings.php lines 2449, 2660): Filters and sorting use indexed EAV JOINs instead of SUBSTRING_INDEX on serialized
4. **CSV Export** (via `get_bulk_entry_data()` in class-data-access.php lines 349-428): Single bulk query fetches all entry data vs N+1 serialized reads

### What Currently Triggers Migration Detection

**Plugin Activation:**
- `register_activation_hook(__FILE__, array('SUPER_Install', 'install'))` in super-forms.php line 317
- Calls `SUPER_Install::install()` → `create_tables()` → `init_migration_state()` (class-install.php lines 30-56)
- Initializes option to `status=not_started` but does NOT auto-start migration

**Plugin Update:**
- Hook: `add_action('init', array($this, 'update_plugin'))` in super-forms.php line 387
- `update_plugin()` method (lines 1675-1688) uses plugin-update-checker library
- On update, calls `SUPER_Install::install()` if settings missing (line 1678)
- Does NOT have specific migration detection logic

**Manual Initiation:**
- User navigates to Developer Tools page (only visible when `defined('DEBUG_SF') && DEBUG_SF === true`)
- Clicks "Start Migration" button (page-developer-tools.php line 346)
- JavaScript makes AJAX call to `super_migration_process_batch`

**CRITICAL GAP:** There is NO automatic detection of unmigrated entries on activation/update. The system waits for manual user action.

### Current Test Entry Generation System

**Entry Point:**
`SUPER_Developer_Tools::generate_test_entries($args)` in class-developer-tools.php (lines 29-89)

**What It Does:**
1. Generates synthetic entry data based on complexity options (lines 98-216):
   - basic_text: name, email, phone (lines 102-127)
   - special_chars: UTF-8 (José, 中文, emojis) (lines 130-137)
   - long_text: >10KB lorem ipsum (lines 140-147)
   - numeric: integers, decimals (lines 150-169)
   - empty: null/empty values (lines 172-185)
   - arrays: checkbox multi-select (lines 188-200)
   - files: file upload URLs (lines 203-214)

2. Creates WordPress post: `wp_insert_post()` with `post_type=super_contact_entry` (lines 52-59)

3. **CRITICAL:** Saves via Data Access Layer: `SUPER_Data_Access::save_entry_data($entry_id, $entry_data)` (line 66)
   - This respects migration state (dual-write during migration, EAV-only after)
   - Ensures test entries are migration-aware

4. Tags as test: `add_post_meta($entry_id, '_super_test_entry', true)` (line 73)

**Safe Deletion:**
`delete_test_entries()` (lines 252-294) only deletes entries with `_super_test_entry=1` meta, protecting production data.

### CSV Import System (Just Implemented)

**File:** page-developer-tools.php lines 176-289 (Import tab)

**Features:**
- Drag & drop or file select UI
- Pre-uploaded test files (3K-26K entries)
- Options: tag_as_test, auto_migrate
- Batch processing with progress tracking
- Import statistics display

**AJAX Handler:** Would be in class-ajax.php (check for `csv_import` or similar)

### Current Admin UI Structure

**Menu Registration:**
`SUPER_Menu::register_menu()` in class-menu.php (lines 28-140):
- Migration page: `super_migration` → `SUPER_Pages::migration()` (lines 62-69)
- Developer Tools: `super_developer_tools` → `SUPER_Pages::developer_tools()` (lines 71-79, only if DEBUG_SF=true)

**Pages Class:**
`SUPER_Pages` in class-pages.php:
- `migration()` method (line 2476): Includes `page-migration.php`
- `developer_tools()` method (line 2485): Includes `page-developer-tools.php`

**Developer Tools Page Structure:**
- Quick Actions (full test cycle, reset everything)
- Test Data Generator with tabs (Generate, Import)
- Migration Controls (start, pause, reset, force complete, rollback)
- Automated Verification (10 test suites)
- Performance Benchmarks
- Database Utilities

**Migration Status Display:**
Lines 298-341 show real-time migration progress with:
- Status badge (not_started, in_progress, completed)
- Storage mode (serialized vs EAV)
- Progress bar with percentage
- Entry counts

### AJAX Infrastructure

**Registration:**
`SUPER_Ajax` class in class-ajax.php (line 114 shows registration):
```php
'migration_process_batch' => false, // @since 6.0.0 - Process migration batch
```

**Handler:**
`migration_process_batch()` method (lines 6328-6342):
- Checks `current_user_can('manage_options')` permission
- Calls `SUPER_Migration_Manager::process_batch()`
- Returns JSON success/error with progress data

**Related Handlers:**
- `migration_get_status()` (lines 6349-6363): Returns current migration state
- `migration_rollback()` (lines 6370-6384): Switches back to serialized
- `migration_reset()` (lines 6391-6405): Resets to not_started
- `migration_force_complete()` (lines 6412-6420): Debug tool to skip migration

**JavaScript Integration:**
Page-developer-tools.php line 2377 shows AJAX call pattern:
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'super_migration_process_batch',
        nonce: nonce
    },
    success: function(response) { /* update UI */ }
});
```

### Data Verification System

**Location:** class-data-access.php lines 453-593

**Per-Entry Verification:**
`validate_entry_integrity($entry_id)` (lines 453-557):
1. Fetches from both storages: `get_from_eav_tables()` and `get_from_serialized()`
2. Compares field counts
3. Field-by-field value comparison (normalizes arrays to JSON for comparison)
4. Returns `{valid: true/false, error: 'description', mismatches: [...]}`

**Bulk Verification:**
`bulk_validate_integrity($entry_ids)` (lines 566-593):
- Loops through entries
- Aggregates results: `{total, valid, invalid, errors: {entry_id => validation}}`

**Used By:** Developer Tools verification tests (class-developer-tools.php lines 374-403)

### Performance & Optimization Patterns

**Bulk Fetch Pattern:**
`get_bulk_entry_data($entry_ids)` in class-data-access.php (lines 349-428):
- EAV: Single query with `WHERE entry_id IN (...)` (lines 373-406)
- Serialized: N+1 queries (pre-migration fallback)
- Used for CSV export, listings, admin tables

**Search Optimization:**
- Pre-migration: `meta_value LIKE '%keyword%'` on LONGTEXT (slow, full table scan)
- Post-migration: `field_value LIKE '%keyword%'` with prefix index (faster, index scan)
- See super-forms.php lines 1712-1724

**Sort Optimization:**
- Pre-migration: Cannot sort by custom fields efficiently (uses default WordPress sort)
- Post-migration: Subquery `(SELECT field_value FROM eav WHERE entry_id=ID AND field_name='email')`
- See super-forms.php lines 2445-2455

**Listings Filter Optimization:**
- Pre-migration: SUBSTRING_INDEX to extract field from serialized (extremely slow)
- Post-migration: Indexed JOIN on field_name and field_value (100x+ faster)
- See extensions/listings/listings.php lines 2449, 2660

### Plugin Activation & Hooks

**Activation Hook:**
- File: super-forms.php line 317
- Handler: `SUPER_Install::install()` in class-install.php (lines 30-56)
- Actions: Flush rewrite rules, save default settings, create tables, init migration state, update DB version

**Deactivation Hook:**
- File: super-forms.php line 320
- Handler: `SUPER_Install::deactivate()` in class-install.php (lines 136-144)
- Actions: Flush rewrite rules, clear scheduled cron hooks

**Update Detection:**
- Hook: `add_action('init', array($this, 'update_plugin'))` super-forms.php line 387
- Method: `update_plugin()` lines 1675-1688
- Uses plugin-update-checker library to check for updates
- No version comparison or migration triggering logic

**Plugin Update Complete Hook (Available but Unused):**
- Hook exists: `add_action('upgrader_process_complete', array($this, 'api_post_update'), 10, 2)` line 424
- Could be used to detect plugin updates and trigger migration

### WP-Cron Infrastructure

**Existing Cron Usage:**
Super Forms already uses WP-Cron for:
1. **Garbage collection:** `super_client_data_garbage_collection` (super-forms.php lines 529-530)
2. **Scheduled triggers:** `super_scheduled_trigger_actions` (lines 532-533)
3. **Email reminders:** `super_cron_reminders` (add-ons/super-forms-email-reminders line 144)

**Schedule Registration:**
```php
if (!wp_next_scheduled('super_client_data_garbage_collection')) {
    wp_schedule_event(time(), 'every_minute', 'super_client_data_garbage_collection');
}
```

**Custom Schedule:**
`minute_schedule()` filter in super-forms.php line 299 adds `every_minute` schedule (see full implementation)

**Hook Cleanup on Deactivation:**
Lines 140-142 in class-install.php clear all scheduled hooks:
```php
wp_clear_scheduled_hook('super_client_data_garbage_collection');
wp_clear_scheduled_hook('super_cron_reminders');
wp_clear_scheduled_hook('super_scheduled_trigger_actions');
```

### Technical Reference Details

#### Key File Paths

**Core Classes:**
- Migration Manager: `/src/includes/class-migration-manager.php`
- Data Access Layer: `/src/includes/class-data-access.php`
- Install/Activation: `/src/includes/class-install.php`
- AJAX Handlers: `/src/includes/class-ajax.php`
- Developer Tools: `/src/includes/class-developer-tools.php`

**Admin UI:**
- Menu: `/src/includes/class-menu.php`
- Pages: `/src/includes/class-pages.php`
- Developer Tools View: `/src/includes/admin/views/page-developer-tools.php`
- Migration View: `/src/includes/admin/views/page-migration.php`

**Main Plugin File:**
- Entry point: `/src/super-forms.php` (defines hooks, loads classes)

**Action Scheduler (NOT YET BUNDLED):**
- Will be added at: `/src/includes/lib/action-scheduler/`
- Must be loaded BEFORE `plugins_loaded` hook (priority 0)

#### Critical Function Signatures

**Migration Manager:**
```php
// Start migration - initializes state
public static function start_migration(): array|WP_Error

// Process batch - returns progress info
public static function process_batch($batch_size = null): array|WP_Error

// Migrate single entry - returns true or WP_Error
private static function migrate_entry($entry_id): bool|WP_Error

// Complete migration - switches to EAV storage
public static function complete_migration(): array

// Rollback - switches back to serialized
public static function rollback_migration(): array|WP_Error

// Get status - returns migration state array
public static function get_migration_status(): array|false

// Reset migration (testing only)
public static function reset_migration(): bool
```

**Data Access Layer:**
```php
// Read entry data (migration-aware)
public static function get_entry_data($entry_id): array|WP_Error

// Save entry data (phase-aware dual-write)
public static function save_entry_data($entry_id, $data): bool|WP_Error

// Bulk fetch (optimized for lists/export)
public static function get_bulk_entry_data($entry_ids): array

// Validate integrity (compare serialized vs EAV)
public static function validate_entry_integrity($entry_id): array

// Bulk validation
public static function bulk_validate_integrity($entry_ids): array

// Delete from both storages
public static function delete_entry_data($entry_id): bool
```

**Install Class:**
```php
// Plugin activation
public static function install(): void

// Create database tables
private static function create_tables(): void

// Initialize migration state
private static function init_migration_state(): void

// Plugin deactivation
public static function deactivate(): void
```

#### Database Schema

**EAV Table:** `wp_superforms_entry_data`
```sql
id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT
entry_id BIGINT(20) UNSIGNED NOT NULL
field_name VARCHAR(255) NOT NULL
field_value LONGTEXT
field_type VARCHAR(50)
field_label VARCHAR(255)
created_at DATETIME NOT NULL
```

**Indexes:**
- PRIMARY KEY (id)
- KEY entry_id (entry_id)
- KEY field_name (field_name)
- KEY entry_field (entry_id, field_name)
- KEY field_value (field_value(191))

**Post Meta Keys:**
- `_super_contact_entry_data` - Serialized entry data (pre-migration)
- `_super_test_entry` - Test entry flag (value: true/1)
- `_super_contact_entry_ip` - Entry IP address
- `_super_contact_entry_status` - Custom entry status

#### WordPress Option Keys

- `superforms_eav_migration` - Migration state tracking
- `super_settings` - Plugin global settings
- `superforms_db_version` - Database schema version (currently '1.0.0')

#### Constants & Config

**Debug Mode:**
```php
if (defined('DEBUG_SF') && DEBUG_SF === true) {
    // Developer Tools menu visible
}
```

**Plugin Constants:**
- `SUPER_PLUGIN_DIR` - Full path to plugin directory
- `SUPER_PLUGIN_FILE` - Plugin URL
- `SUPER_VERSION` - Current version
- `ABSPATH` - WordPress root path

### Implementation Architecture for Background Migration

**Where Action Scheduler Will Be Loaded:**

File: `/src/super-forms.php`
Location: In the `__construct()` method BEFORE `$this->includes()` (around line 164)

```php
public function __construct() {
    // Load Action Scheduler FIRST (before plugins_loaded)
    if (file_exists(SUPER_PLUGIN_DIR . '/includes/lib/action-scheduler/action-scheduler.php')) {
        require_once SUPER_PLUGIN_DIR . '/includes/lib/action-scheduler/action-scheduler.php';
    }

    $this->define_constants();
    $this->includes();
    $this->init_hooks();
    do_action('super_loaded');
}
```

**Where to Bundle Action Scheduler:**
- Target directory: `/src/includes/lib/action-scheduler/`
- Bundle method: git subtree or direct download from WooCommerce GitHub
- Version: 3.9.3 (latest stable as noted in task)

**Migration Trigger Points:**

1. **Plugin Activation:** Modify `SUPER_Install::install()` (class-install.php line 30)
   - After `init_migration_state()`, add detection and scheduling logic

2. **Plugin Update:** Hook into `upgrader_process_complete` (super-forms.php line 424)
   - Add method to detect plugin updates and schedule migration

3. **Daily Health Check:** Schedule recurring check via Action Scheduler
   - Detects incomplete migrations and resumes

**Migration Scheduling Pattern:**

```php
// Pseudo-code for how Action Scheduler will be used
public static function schedule_background_migration() {
    global $wpdb;

    // Count unmigrated entries
    $unmigrated = $wpdb->get_var("
        SELECT COUNT(*) FROM wp_posts p
        LEFT JOIN wp_superforms_entry_data e ON e.entry_id = p.ID
        WHERE p.post_type = 'super_contact_entry'
        AND e.entry_id IS NULL
    ");

    if ($unmigrated > 0) {
        // Schedule first batch (self-scheduling pattern)
        as_enqueue_async_action('superforms_migrate_batch', array(
            'batch_size' => 100,
            'offset' => 0
        ));

        // Schedule daily health check
        as_schedule_recurring_action(strtotime('+1 day'), DAY_IN_SECONDS, 'superforms_migration_health_check');
    }
}
```

**Verification Strategy:**

Will be added to `SUPER_Migration_Manager::migrate_entry()`:
1. Migrate entry to EAV
2. Verify: `SUPER_Data_Access::validate_entry_integrity($entry_id)`
3. If valid: Delete serialized data `delete_post_meta($entry_id, '_super_contact_entry_data')`
4. If invalid: Log error, keep both copies, add to failed_entries array

**Admin UI Integration:**

Developer Tools page already has migration progress display (page-developer-tools.php lines 294-405). Will add:
- Background migration status section
- Link to Action Scheduler admin UI (if available)
- Automatic progress updates via JavaScript polling
- Background processing indicator

**Fallback to WP-Cron:**

If Action Scheduler not available (function_exists check):
```php
if (!function_exists('as_enqueue_async_action')) {
    // Use enhanced WP-Cron with locking
    if (!wp_next_scheduled('superforms_migrate_batch')) {
        wp_schedule_event(time(), 'every_minute', 'superforms_migrate_batch');
    }
}
```

### Key Architectural Decisions to Implement

1. **Self-Scheduling Pattern:** Each batch schedules the next batch instead of scheduling all upfront
2. **Per-Entry Verification:** Verify each entry after migration (serialized data preserved for safety)
3. **Backup Strategy:** Serialized data serves as built-in backup (kept throughout migration)
4. **Lock Mechanism:** Use transients to prevent concurrent migrations
5. **Error Handling:** Failed entries kept in both formats, logged, and reported
6. **Progress Tracking:** Update migration state after each batch for resume capability
7. **Throttling:** 50-100 entries per batch, 5-10 second delay between batches
8. **Health Check:** Daily cron to detect and resume incomplete migrations

## Implementation Notes

### Architecture Overview

**Action Scheduler Integration:**
```php
// Bundle as library at: src/includes/lib/action-scheduler/
// Load in super-forms.php BEFORE 'plugins_loaded':
require_once SUPER_PLUGIN_DIR . '/src/includes/lib/action-scheduler/action-scheduler.php';
```

**Migration Flow:**
1. Plugin activation/update hook triggers detection
2. If unmigrated entries exist → schedule first batch
3. Action Scheduler processes batches in background
4. Each batch: migrate 50-100 entries, verify integrity, keep serialized data
5. Self-scheduling: each batch schedules next batch until complete
6. Daily health check ensures completion even if process interrupted

**Verification Strategy:**
- Per-entry: Migrate → Verify integrity → Log results
- Verification: Compare field count + field-by-field value comparison
- Failed verification: Keep both copies, log error, continue with next entry
- Serialized data: Preserved throughout migration as built-in backup (deletion commented out)

**Enhanced WP-Cron Fallback:**
- Self-scheduling pattern (don't schedule all batches upfront)
- Locking via transients to prevent concurrent runs
- Retry tracking in wp_options
- Good enough for sites without Action Scheduler

## User Notes
- Action Scheduler version: 3.9.3 (latest stable)
- Git subtree command for bundling provided in implementation
- Must load Action Scheduler before 'plugins_loaded' priority 0
- Action Scheduler creates 3 database tables on first use
- Multiple plugins can bundle Action Scheduler - newest version wins automatically

## Work Log
<!-- Updated as work progresses -->
