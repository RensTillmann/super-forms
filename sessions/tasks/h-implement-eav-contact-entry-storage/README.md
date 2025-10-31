---
name: h-implement-eav-contact-entry-storage
branch: feature/eav-contact-entry-storage
status: pending
created: 2025-10-31
---

# Implement EAV Storage for Contact Entry Data

## Problem/Goal

**Current State:**
Super Forms stores all contact entry field data as serialized PHP arrays in the `_super_contact_entry_data` post meta key. This creates severe performance bottlenecks:
- Listings Extension filters take 15-20 seconds using nested SUBSTRING_INDEX queries on 8,100+ entries
- Admin search uses LIKE on serialized data (cannot use indexes, 500-1,000ms per query)
- External integrations (Zapier, Mailchimp, Stripe, PayPal) query serialized data
- No ability to create database indexes on individual field values
- 13+ files directly manipulate serialized entry data with scattered, inconsistent access patterns

**Goal:**
Migrate to Entity-Attribute-Value (EAV) table structure for contact entry data storage while maintaining 100% backwards compatibility with existing integrations and ensuring zero data loss during migration.

**Research Foundation:**
This task is based on comprehensive 30-phase EAV migration research documented in `sessions/tasks/h-research-eav-migration-complete.md` (~15,000 lines analyzing every feature, dependency, and migration impact).

**Core Priorities:**
1. **Backwards Compatibility** - Zero breaking changes for external integrations (Zapier, Mailchimp, Stripe, PayPal, CSV exports)
2. **Migration Safety** - Convert existing production data without downtime or data loss
3. **Code Quality** - Refactor scattered data access into clean, testable abstractions
4. **Performance** - Achieve 30-60x improvement on listings filters (15-20 sec → <500ms)

## Success Criteria

### Backwards Compatibility
- [ ] All external integrations (Zapier, Mailchimp, Stripe, PayPal) continue working without code changes
- [ ] CSV/JSON export format remains byte-for-byte identical
- [ ] Third-party code using `get_post_meta($entry_id, '_super_contact_entry_data', true)` continues working
- [ ] Rollback mechanism switches storage method instantly (with documented limitations)

### Migration Safety
- [ ] Migration script converts 100% of existing entries with integrity verification
- [ ] Entry editing locked during migration (prevent data inconsistencies)
- [ ] Migration logs every conversion with success/failure tracking
- [ ] Test suite validates migration on 197 production forms from research
- [ ] Old serialized data preserved permanently (deleted only when entry deleted)
- [ ] Rollback available indefinitely with clear warning about post-migration data loss

### Code Quality
- [ ] All 13+ files with scattered data access refactored to use `SUPER_Data_Access` abstraction
- [ ] 500+ line conditional logic function broken into testable units
- [ ] WordPress Coding Standards (PHPCS) compliance achieved
- [ ] PHPDoc documentation for all new classes and methods
- [ ] Clean query builder pattern replaces SUBSTRING_INDEX horror

### Performance
- [ ] Listings Extension filtering: 15-20 seconds → <500ms (30-60x improvement verified)
- [ ] Admin search: LIKE on serialized → indexed EAV queries
- [ ] Database indexes on entry_id, field_name, and field_value
- [ ] Performance benchmarks documented before/after migration
- [ ] Optimized write strategy: Single write to EAV after migration (50% reduction)

### Background Processing & User Experience
- [ ] Action Scheduler library bundled in `/src/includes/libraries/action-scheduler/`
- [ ] Dedicated migration page (`wp-admin/admin.php?page=super-forms-eav-migration`)
- [ ] Real-time progress bar with AJAX updates every 2 seconds
- [ ] Persistent non-dismissible admin notice until migration complete
- [ ] Dashboard widget showing migration progress
- [ ] Admin menu badge notification (like plugin update count)
- [ ] Pause/Resume functionality for migration
- [ ] Automatic resume if interrupted (server restart, timeout)

### Admin Notifications
- [ ] Pre-migration warning notice with "Start Update" button (WooCommerce-style)
- [ ] Migration progress displayed on all admin pages
- [ ] Completion success notice with entry count
- [ ] Email notifications (pre-migration, completion, errors) with opt-out
- [ ] Non-technical language in all notices (avoid jargon)
- [ ] Rollback warning shows impact (entries edited/created after migration)

### Version Tracking & State Management
- [ ] `superforms_db_version` in `wp_options` (follows WordPress core pattern)
- [ ] `superforms_eav_migration` state array with detailed progress
- [ ] Migration version numbers (support future migrations)
- [ ] Multi-site: Per-site migration state in `wp_[site_id]_options`

### Feature Locking During Migration
- [ ] Bulk operations locked with explanatory notice
- [ ] CSV import locked with migration progress link
- [ ] Entry editing locked (front-end and admin)
- [ ] Form deletion prevented during migration
- [ ] Feature lock notices explain why and link to progress

### Error Handling & Recovery
- [ ] Failed batch automatic retry (3 attempts via Action Scheduler)
- [ ] Detailed error logging with entry IDs
- [ ] Admin error notice with "View Errors" link
- [ ] Email alert on critical errors
- [ ] Manual retry option for failed batches
- [ ] Graceful degradation (continue with other entries if some fail)

### Progress & Transparency
- [ ] Entries processed / total count
- [ ] Migration speed (entries/second)
- [ ] Estimated time remaining (dynamic calculation)
- [ ] Current batch information
- [ ] Detailed activity log (recent actions list)

### Testing
- [ ] PHPUnit test suite covers all 10 critical gaps identified in Phase 30
- [ ] Integration regression tests verify zero breaking changes
- [ ] Feature flag enables gradual rollout with monitoring
- [ ] Load testing validates performance with 10,000+ entries

### Documentation
- [ ] Migration guide for site owners
- [ ] Developer documentation for Data Access Layer API
- [ ] Rollback procedure documented with limitations
- [ ] Performance comparison metrics published

## Migration UX Mockups

### Phase 1: Initial Notice (After Plugin Update)
```
╔════════════════════════════════════════════════════════════╗
║ ⚠️ Super Forms Database Upgrade Required                   ║
║                                                            ║
║ A new version of Super Forms requires a database update.  ║
║                                                            ║
║ • Your site will remain accessible during the update      ║
║ • Estimated time: ~X minutes                              ║
║                                                            ║
║ [Start Database Update]                                   ║
╚════════════════════════════════════════════════════════════╝
```

**Notice Properties:**
- Type: Warning (yellow)
- Dismissible: No (critical update)
- Shown to: Admin users only
- Location: All admin pages (top)
- Conditions: `superforms_eav_migration['status'] !== 'completed'`

### Phase 2: Migration Progress
```
Super Forms → Database Migration

╔════════════════════════════════════════════════════════════╗
║ [████████████████░░░░░░] 75%                               ║
║                                                            ║
║ Migrating entries: 6,342 / 8,456                          ║
║ Estimated time remaining: 2 minutes                       ║
║                                                            ║
║ [Pause]                                                   ║
╚════════════════════════════════════════════════════════════╝
```

**Features:**
- Real-time AJAX updates (every 2 seconds)
- Progress bar animation
- Pause/Resume capability
- Dedicated page: `wp-admin/admin.php?page=super-forms-eav-migration`

### Phase 3: Completion Notice
```
╔════════════════════════════════════════════════════════════╗
║ ✅ Database update complete!                               ║
║                                                            ║
║ Successfully updated 8,456 entries.                       ║
║                                                            ║
║ [Dismiss]                                                 ║
╚════════════════════════════════════════════════════════════╝
```

**Notice Properties:**
- Type: Success (green)
- Dismissible: Yes
- Auto-dismiss: After 10 seconds

### Phase 4: Rollback Warning (Emergency Use)
```
╔════════════════════════════════════════════════════════════╗
║ ⚠️ Confirm Rollback                                        ║
║                                                            ║
║ Rolling back will revert all contact entries to their     ║
║ state at the time of migration (Nov 1, 2025).             ║
║                                                            ║
║ ⚠️ Changes made after migration will be lost:             ║
║ • 145 entries edited since migration                      ║
║ • 23 new entries created since migration                  ║
║                                                            ║
║ This action should only be used in emergencies.           ║
║                                                            ║
║ [Cancel] [Yes, Rollback Anyway]                           ║
╚════════════════════════════════════════════════════════════╝
```

## Technical Implementation Details

### Dual Storage Architecture

**Core Principle:** Write to both storage methods during transition, optimize after completion.

#### Phase-Based Write Strategy

```php
function save_entry_data($entry_id, $data) {
    $migration = get_option('superforms_eav_migration');

    // PHASE 1: Before migration starts
    if (empty($migration) || $migration['status'] === 'not_started') {
        // Write to serialized only (old method)
        update_post_meta($entry_id, '_super_contact_entry_data', serialize($data));
        return;
    }

    // PHASE 2: During migration
    if ($migration['status'] === 'in_progress') {
        // Write to BOTH (safety during transition)
        $this->save_to_eav_tables($entry_id, $data);
        update_post_meta($entry_id, '_super_contact_entry_data', serialize($data));
        return;
    }

    // PHASE 3: After migration (rolled back)
    if ($migration['using_storage'] === 'serialized') {
        // Write to serialized only
        update_post_meta($entry_id, '_super_contact_entry_data', serialize($data));
        return;
    }

    // PHASE 4: After migration complete (normal operation)
    // Write to EAV only - DON'T update serialized
    $this->save_to_eav_tables($entry_id, $data);
    // Old serialized data preserved but frozen at migration time
}
```

**Benefits:**
- ✅ 50% fewer writes after migration (2 operations → 1)
- ✅ Less database overhead (no unnecessary serialized updates)
- ✅ Old data preserved forever (rollback capability)
- ✅ Simpler after migration (single write path)

#### Read Strategy

```php
function get_entry_data($entry_id) {
    $migration = get_option('superforms_eav_migration');

    // Check if migration completed and not rolled back
    if (empty($migration) ||
        $migration['status'] !== 'completed' ||
        $migration['using_storage'] === 'serialized') {

        // Read from serialized (safe fallback)
        return unserialize(get_post_meta($entry_id, '_super_contact_entry_data', true));
    }

    // Read from EAV
    $eav_data = $this->get_from_eav_tables($entry_id);

    // Fallback to serialized if EAV empty (safety)
    if (empty($eav_data)) {
        return unserialize(get_post_meta($entry_id, '_super_contact_entry_data', true));
    }

    return $eav_data;
}
```

### Migration Timeline

```
T0: Plugin Updated (Code Deployed)
├── Save function NOW writes to BOTH storage methods
├── Read function STILL reads from serialized (migration not started)
└── New entries: Have BOTH EAV + serialized

T1: Admin Clicks "Start Database Update"
├── Migration begins processing old entries
├── Read function switches to EAV
└── New entries created during migration: Already have EAV (skipped)

T2: Migration Completes
├── ALL entries have BOTH storage methods
├── Read from EAV (primary)
├── Serialized data frozen at migration point (backup)
└── New entries: Write to EAV only
```

### Rollback Behavior

**Important Limitation:** Rolling back after post-migration changes results in data loss for those changes.

**Example:**
```
Nov 1: Migration completes (Entry #500: name='John')
Nov 5: User edits entry #500 (name='Jane') → Writes to EAV only
Nov 10: Admin triggers rollback → Reads from serialized
Result: Entry #500 shows name='John' (OLD data)
```

**Why this is acceptable:**
1. Rollback is EMERGENCY measure (not normal operation)
2. Alternative is writing to both forever (performance cost)
3. Most sites never rollback (migration tested thoroughly)
4. Clear warning shows impact before rollback

### Race Condition Prevention

**Entry Editing During Migration:**
- Solution: Lock all entry editing when `migration['status'] === 'in_progress'`
- Applies to: Admin editing, front-end editing, bulk operations, CSV import

**Concurrent Form Submissions:**
- Safe: New submissions write to BOTH storage methods
- Migration batches skip entries with existing EAV data
- No race conditions possible

**Migration Batch Logic:**
```php
function migrate_batch($batch_number, $batch_size) {
    global $wpdb;

    // Get entries WITHOUT EAV data yet
    $entries = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->prefix}superforms_entry_data e
            ON p.ID = e.entry_id
        WHERE p.post_type = 'super_contact_entry'
        AND p.post_status != 'trash'
        AND e.entry_id IS NULL  -- Critical: Only entries missing EAV
        ORDER BY p.ID ASC
        LIMIT %d OFFSET %d
    ", $batch_size, ($batch_number - 1) * $batch_size));

    foreach ($entries as $entry) {
        // Read serialized data
        $data = unserialize(get_post_meta($entry->ID, '_super_contact_entry_data', true));

        // Write to EAV (serialized stays untouched)
        $this->save_to_eav_tables($entry->ID, $data);

        // Verify with checksum
        $eav_data = $this->get_from_eav_tables($entry->ID);
        if (md5(serialize($data)) !== md5(serialize($eav_data))) {
            // Rollback this entry
            $wpdb->delete(
                $wpdb->prefix . 'superforms_entry_data',
                ['entry_id' => $entry->ID]
            );
        }
    }

    // Schedule next batch or finalize
}
```

### Database Schema

```sql
CREATE TABLE wp_superforms_entry_data (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_id BIGINT(20) UNSIGNED NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    field_value LONGTEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY entry_id (entry_id),
    KEY field_name (field_name),
    KEY entry_field (entry_id, field_name),
    KEY field_value (field_value(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Index Strategy:**
- `entry_id`: Fast lookups for single entry retrieval
- `field_name`: Fast filtering by field across all entries
- `entry_id, field_name`: Composite for "get field X from entry Y"
- `field_value(191)`: Prefix index for searches (MySQL LONGTEXT limit)

### Action Scheduler Integration

**Library Bundling:**
```php
// In super-forms.php (main plugin file)
if ( ! class_exists( 'ActionScheduler' ) ) {
    require_once plugin_dir_path( __FILE__ ) .
        'src/includes/libraries/action-scheduler/action-scheduler.php';
}
```

**Batch Scheduling:**
```php
// Schedule migration batches
as_schedule_single_action(
    time(),
    'superforms_migrate_batch',
    ['batch_number' => 1, 'batch_size' => 1000]
);

// Action callback
add_action('superforms_migrate_batch', 'superforms_process_migration_batch', 10, 2);

function superforms_process_migration_batch($batch_number, $batch_size) {
    // Migrate 1,000 entries
    // Schedule next batch if needed
    if ($more_entries) {
        as_schedule_single_action(
            time() + 5, // 5 second delay between batches
            'superforms_migrate_batch',
            ['batch_number' => $batch_number + 1, 'batch_size' => $batch_size]
        );
    }
}
```

**Benefits:**
- Survives PHP timeouts and server restarts
- Built-in retry logic for failed batches
- Progress tracking out of the box
- Battle-tested (used by WooCommerce, Yoast, etc.)

### Multi-Site Support

```php
// Per-site migration state
function get_migration_state() {
    if (is_multisite()) {
        // Auto-uses current blog's options table
        return get_option('superforms_eav_migration');
    } else {
        return get_option('superforms_eav_migration');
    }
}

// Network admin: View all sites' migration status
function get_network_migration_status() {
    if (!is_multisite()) {
        return null;
    }

    $sites = get_sites();
    $status = [];

    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);
        $status[$site->blog_id] = get_option('superforms_eav_migration');
        restore_current_blog();
    }

    return $status;
}
```

### Migration State Tracking

```php
'superforms_eav_migration' => [
    'status' => 'completed', // not_started|in_progress|completed|rolled_back
    'started_at' => '2025-10-31 14:00:00',
    'completed_at' => '2025-10-31 14:23:45',
    'total_entries' => 8456,
    'migrated_entries' => 8456,
    'failed_entries' => [], // [entry_id => error_message]
    'rollback_count' => 0,
    'using_storage' => 'eav', // 'eav' or 'serialized'
]
```

### Performance Optimizations

**Batch INSERT Optimization:**
```php
function save_to_eav_tables_batch($entries_data) {
    global $wpdb;

    $values = [];
    $placeholders = [];

    foreach ($entries_data as $entry_id => $data) {
        foreach ($data as $field_name => $field_value) {
            $values[] = $entry_id;
            $values[] = $field_name;
            $values[] = $field_value;
            $values[] = current_time('mysql');
            $placeholders[] = "(%d, %s, %s, %s)";
        }
    }

    // Single multi-value INSERT (15,000 individual INSERTs → 1 query)
    $query = "INSERT INTO {$wpdb->prefix}superforms_entry_data
              (entry_id, field_name, field_value, created_at)
              VALUES " . implode(', ', $placeholders);

    $wpdb->query($wpdb->prepare($query, $values));
}
```

**Result:** 15,000 queries → 1 query per batch (massive improvement)

## Proposed Subtasks

### Phase 1: Foundation & Safety
1. `01-test-suite-foundation.md` - Build PHPUnit tests for 10 critical gaps (Phase 30 requirement)
2. `02-backwards-compat-tests.md` - Test external integrations (Zapier, Mailchimp, Stripe, PayPal)

### Phase 2: Architecture & Refactoring
3. `03-data-access-layer.md` - Implement clean `SUPER_Data_Access` abstraction with error handling
4. `04-storage-strategy-pattern.md` - Phase-based read/write logic (serialized → dual → EAV only)
5. `05-refactor-entry-access.md` - Clean up 13+ files using direct meta access

### Phase 3: Database & Migration
6. `06-database-schema.md` - EAV tables with proper indexes
7. `07-migration-manager.md` - Batch processing with Action Scheduler
8. `08-migration-verification.md` - Checksum validation and error handling
9. `09-rollback-mechanism.md` - Storage method toggle with impact warnings

### Phase 4: User Experience & Admin UI
10. `10-admin-notices.md` - Pre-migration, progress, completion, rollback warnings
11. `11-migration-page.md` - Dedicated page with progress bar and AJAX updates
12. `12-dashboard-widget.md` - Real-time migration progress widget
13. `13-email-notifications.md` - Pre/post migration emails with opt-out

### Phase 5: Feature Locking & Safety
14. `14-entry-editing-lock.md` - Lock editing during migration
15. `15-bulk-operations-lock.md` - Lock bulk operations during migration
16. `16-csv-import-lock.md` - Lock CSV import during migration

### Phase 6: Query Optimization & Rewrites
17. `17-listings-filter-rewrite.md` - Replace SUBSTRING_INDEX with EAV JOINs (~40 lines)
18. `18-admin-search-rewrite.md` - Replace LIKE on serialized with indexed queries (~15 lines)
19. `19-export-compatibility.md` - Ensure CSV/JSON exports remain identical (field ordering)

### Phase 7: Multi-Site & Advanced Features
20. `20-multisite-support.md` - Per-site migration state and network admin dashboard
21. `21-wp-cli-commands.md` - CLI commands for migration control

### Phase 8: Code Quality & Standards
22. `22-phpcs-compliance.md` - WordPress Coding Standards validation
23. `23-phpdoc-documentation.md` - Document all new classes/methods
24. `24-conditional-logic-refactor.md` - Break 500+ line function into testable units

### Phase 9: Validation & Rollout
25. `25-performance-validation.md` - Verify 30-60x improvement (15-20 sec → <500ms)
26. `26-integration-regression-tests.md` - Verify zero breaking changes for external systems
27. `27-feature-flag-rollout.md` - Gradual migration with monitoring and telemetry

## Context Manifest

### How Contact Entry Data Storage Currently Works

**The Complete Data Flow - From Submission to Database:**

When a user submits a Super Forms form, the request arrives at `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-ajax.php` in the `submit_form()` method (line 4559). This AJAX endpoint is the single entry point for all form submissions. The method performs extensive validation, processes file uploads, and collects all field data into a massive PHP array structure. Each field's data is stored with its field name as the key and includes the value, type, label, and extensive metadata about the field configuration.

After data collection completes, the system checks if contact entry saving is enabled via `$settings['save_contact_entry']`. If enabled (which it is by default), Super Forms creates a new WordPress post of type `super_contact_entry` using `wp_insert_post()` (line 4767). The post title is generated from a configurable template that can include field values via the {tags} system. The actual field data array - containing ALL form field data for potentially dozens of fields - is then serialized using PHP's `serialize()` function and stored as a single meta value under the key `_super_contact_entry_data` using `add_post_meta()` at line 4997.

This is the root cause of all performance problems. The ENTIRE form submission - text inputs, dropdowns, checkboxes, file uploads, repeater fields (nested arrays), calculated values - EVERYTHING is converted into one long serialized string and jammed into a single row in the `wp_postmeta` table. For a complex form with 30 fields and repeater groups, this creates ONE meta row with a serialized array that could be 50KB or larger containing all field values, their labels, types, and metadata.

**Why This Architecture Was Chosen (Historical Context):**

WordPress's post meta system was designed for exactly this pattern - simple key-value storage. The original Super Forms developers (circa 2014) followed the standard WordPress pattern of serializing complex data structures for storage. This works perfectly fine for small sites with hundreds of entries. The performance problem only manifests at scale (8,000+ entries) when users started building complex listing pages, admin searches, and export systems that need to filter/sort by specific field values.

**The Tag System - The Abstraction That Saves Us:**

The {tags} system (`/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-common.php` - `email_tags()` function starting at line 5649) is how Super Forms accesses field values throughout the ENTIRE plugin. This is not just for emails - it's the universal data access mechanism used EVERYWHERE:

- **Emails**: Admin emails, confirmation emails, email reminders all use `{field_name}` tags to insert submitted values
- **PDF Generation**: Templates use tags to populate PDF content with entry data
- **Conditional Logic**: Triggers evaluate conditions like "if {email} == user@domain.com then..."
- **Redirect URLs**: Can contain tags like `/thank-you?name={first_name}`
- **Entry Titles**: Contact entry post titles are generated from tags
- **Calculator Fields**: Formulas use tags like `{price} * {quantity}`
- **Front-end Posting**: Post content/title/meta populated via tags
- **WooCommerce Integration**: Product data, order meta - all via tags
- **Webhooks**: Payload data populated with tags
- **Listings Display**: Every column value extracted via the underlying data access

The critical architectural insight: The `email_tags()` function doesn't care WHERE the data comes from. It receives a `$data` array parameter in a specific format and performs string replacements. The tag replacement engine works by:

1. Detecting `{field_name}` patterns in any template string
2. Looking up the field value from the `$data` array parameter
3. Replacing the tag with the value
4. Returning the transformed string

This abstraction is what makes the EAV migration possible. We only need to change HOW the `$data` array is populated, not the hundreds of places that use it. Every add-on, every extension, every feature that uses `email_tags()` will continue working identically after migration as long as the `$data` array maintains the same structure:

```php
$data['field_name'] = array(
    'name' => 'field_name',
    'value' => 'the actual field value',
    'label' => 'Field Label',
    'type' => 'text',
    // ... other metadata properties
);
```

**The Performance Bottleneck - Listings Extension SUBSTRING_INDEX Horror:**

The Listings Extension (`/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/listings/listings.php`) demonstrates the catastrophic performance issue starting around line 2437. When displaying a list of entries with custom columns, the system must filter and sort entries based on specific field values buried inside the serialized strings. Since MySQL cannot use indexes on serialized data, the query uses TRIPLE NESTED `SUBSTRING_INDEX()` functions to parse the serialized PHP format:

```sql
SUBSTRING_INDEX(
    SUBSTRING_INDEX(
        SUBSTRING_INDEX(
            meta.meta_value,
            's:4:"name";s:$fckLength:"$fck";s:5:"value";',
            -1
        ),
        '";s:',
        1
    ),
    ':"',
    -1
) AS filterValue_1
```

This parsing happens for EVERY entry in the database, for EVERY custom column filter. With 8,100 entries and 3 active filters, MySQL is parsing 24,300 serialized strings character-by-character (72,900+ string parsing operations). No indexes can help. The query must examine every single entry's full serialized data blob. Query time: 15-20 seconds.

With EAV migration, this becomes a simple indexed JOIN:
```sql
LEFT JOIN wp_superforms_entry_data AS field1
    ON field1.entry_id = post.ID
    AND field1.field_name = 'email'
WHERE field1.field_value LIKE '%search%'
```
Expected time: Under 500ms (30-60x improvement).

**Data Write Operations - Entry Creation and Updates:**

Entry creation happens at these critical locations:

1. **Primary Save** (`/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-ajax.php:4997`):
   - Creates the `super_contact_entry` post
   - Serializes entire data array: `$data = serialize($data_array)`
   - Saves via: `add_post_meta($entry_id, '_super_contact_entry_data', $data)`
   - This is called for EVERY form submission

2. **Entry Updates** (`/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-ajax.php:1285`):
   - Admin edits entry from backend
   - Retrieves: `$data = get_post_meta($id, '_super_contact_entry_data', true)`
   - Unserializes, modifies specific field values, re-serializes
   - Updates via: `update_post_meta($id, '_super_contact_entry_data', $data)`
   - Problem: Must deserialize entire blob to change one field

3. **Entry Duplication** (`/root/go/src/github.com/RensTillmann/super-forms/src/super-forms.php:3181-3182`):
   - Copies all post meta including serialized data blob directly
   - Used when admin wants to duplicate an entry
   - Simple read and write of the entire blob

**Data Read Operations - The 35+ Critical Update Points:**

From Phase 1 inventory, these are the 35+ locations that directly access `_super_contact_entry_data`:

**Core Files (21 locations):**
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-ajax.php`: Lines 1213, 1216, 1276, 1329, 5840 (5 reads)
- `/root/go/src/github.com/RensTillmann/super-forms/src/super-forms.php`: Lines 1067, 2158, 3181 (3 reads)
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-common.php`: Lines 5032, 5649 (2 reads - THE CRITICAL TAG SYSTEM)
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-pages.php`: Line 2499 (1 read)
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-shortcodes.php`: Lines 4292, 4294, 7657, 7794, 7799, 7827, 7867 (7 reads)

**Extensions (4 locations):**
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/listings/listings.php`: Line 2979 (display unserialization)
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/listings/form-blank-page-template.php`: Lines 107, 142 (2 reads)

**Add-ons (3 CRITICAL locations with direct access):**
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-front-end-posting/super-forms-front-end-posting.php`: Line 237
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-woocommerce/super-forms-woocommerce.php`: Line 1271
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-paypal/super-forms-paypal.php`: Line 2156

**IMPORTANT DISCOVERY**: 14 other add-ons (Mailchimp, Calculator, Email Reminders, etc.) access data through filters/hooks that pass the `$data` array as a parameter. They NEVER directly call `get_post_meta()` for entry data. This is why they require ZERO code changes.

**The Migration Challenge:**

Every single location that currently does:
```php
$data = get_post_meta($entry_id, '_super_contact_entry_data', true);
```

Must be replaced with:
```php
$data = SUPER_Data_Access::get_entry_data($entry_id);
```

The Data Access Layer will implement dual-read strategy:
1. Check if entry has been migrated to EAV
2. If yes: Query EAV table and construct `$data` array in identical format
3. If no: Fall back to `get_post_meta()` with serialized data
4. Return `$data` array in exact same format

The beauty of this approach: The tag system, conditional logic, calculators, webhooks, emails, PDFs, exports - EVERYTHING continues working without modification. We're just changing the plumbing underneath.

### For EAV Migration Implementation: Critical Connection Points

**Phase-Based Storage Strategy (Dual-Write During Transition):**

The migration uses a three-phase approach to ensure zero downtime and full rollback capability:

**Phase 1: Pre-Migration (Current State)**
- Write: Serialized only (`add_post_meta($entry_id, '_super_contact_entry_data', $serialized)`)
- Read: Serialized only (`get_post_meta($entry_id, '_super_contact_entry_data', true)`)

**Phase 2: During Migration**
- Write: BOTH storage methods (dual-write for safety)
  - New entries created during migration already have both
  - Migration script populates EAV for old entries
- Read: EAV first, fallback to serialized
  - Seamless transition as entries are migrated

**Phase 3: Post-Migration (Optimized)**
- Write: EAV only (50% fewer write operations)
- Read: EAV first, fallback to serialized (for rollback safety)
- Old serialized data preserved permanently (enables rollback, deleted only when entry deleted)

**Key Architectural Decision**: Write to both during transition, then optimize to EAV-only after migration completes. This gives us rollback capability while eliminating unnecessary dual-writes long-term.

**Rollback Consideration**: After migration completes, new entries and edits write ONLY to EAV. If admin rolls back weeks later, any entries created/edited after migration will show old values from the frozen serialized snapshot. This is acceptable because:
1. Rollback is emergency-only measure
2. Alternative (perpetual dual-write) costs 50% write performance forever
3. Rollback warning will show impact (entries edited/created after migration)
4. Most sites never rollback after testing confirms success

**Query Pattern Changes - Listings Extension Rewrite:**

Current approach (lines 2437-2857 of listings.php):
- Retrieves ALL entries with serialized data
- Uses SUBSTRING_INDEX to parse field values in SELECT
- Filters in HAVING clause AFTER all data retrieved
- Sorts by SUBSTRING_INDEX parsed values
- 15-20 second query time with 8,100 entries

New approach (will require ~40 line rewrite):
```sql
SELECT
    post.ID,
    post.post_title,
    post.post_date,
    field_email.field_value AS email,
    field_name.field_value AS name
FROM wp_posts AS post
LEFT JOIN wp_superforms_entry_data AS field_email
    ON field_email.entry_id = post.ID
    AND field_email.field_name = 'email'
LEFT JOIN wp_superforms_entry_data AS field_name
    ON field_name.entry_id = post.ID
    AND field_name.field_name = 'name'
WHERE post.post_type = 'super_contact_entry'
    AND field_email.field_value LIKE '%search%'
ORDER BY field_name.field_value ASC
LIMIT 50
```

Key improvements:
- WHERE clause filters (uses indexes)
- No HAVING clause (filtering before retrieval)
- No SUBSTRING_INDEX (direct column access)
- MySQL optimizer can use query plan with indexes

**Admin Search Rewrite:**

Current approach (`/root/go/src/github.com/RensTillmann/super-forms/src/super-forms.php:1574`):
```php
$where .= " AND $table_meta.meta_key = '_super_contact_entry_data'
            AND $table_meta.meta_value LIKE '%$search%'";
```

Problem: Searches ENTIRE serialized blob with LIKE operator. No indexes possible. Full table scan on 8,000 entries = 500-1,000ms per query.

New approach (using EAV):
```php
$where .= " AND EXISTS (
    SELECT 1 FROM {$wpdb->prefix}superforms_entry_data
    WHERE entry_id = {$wpdb->posts}.ID
    AND field_value LIKE '%$search%'
)";
```

With index on `field_value(191)`, query time drops to 50-100ms (10x improvement).

**CSV Export Compatibility (Critical for External Integrations):**

Current export (`/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-ajax.php:1329`):
```php
$data = unserialize($v->data);
foreach($columns as $column) {
    $csv_row[] = $data[$column]['value'];
}
```

After migration must maintain:
- Exact same column order
- Exact same field value formatting
- Exact same file field handling
- Byte-for-byte identical CSV output

Why this matters:
- Third-party systems (Zapier, Mailchimp) depend on consistent CSV format
- Automated imports may break if column order changes
- Users have existing CSV processing scripts

Solution: Data Access Layer returns fields in SAME order as original serialized array. Store field position/order in EAV table or maintain insertion order.

**Conditional Logic and Calculator Fields:**

Research Phase 16 revealed: Conditional logic and calculator fields are completely abstracted from data storage!

Conditional logic (`/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-common.php` - `conditional_compare_check()`):
- Receives field values via the `$data` array parameter
- Performs simple PHP comparisons (strpos, ==, !=, >, <)
- Does NOT call database queries
- Works on frontend (form submission) and backend (trigger evaluation)

Calculator (`/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-calculator/`):
- Uses math.js library (NOT eval - safe expression parser)
- Frontend-only (JavaScript in browser)
- Calls `email_tags()` to resolve {field_name} tags in formulas
- Never queries database directly

**Migration Impact**: ZERO code changes to conditional logic or calculator. They receive the `$data` array from Data Access Layer and work identically.

**Dynamic Groups and Repeater Fields (Complex Nested Arrays):**

Research revealed Super Forms supports repeater/dynamic groups where users can add multiple instances of a field group. These are stored as nested arrays in the serialized data:

```php
$data['customer'] = array(
    'name' => 'customer',
    'value' => array(
        0 => array(
            'first_name' => array('value' => 'John'),
            'last_name' => array('value' => 'Doe'),
        ),
        1 => array(
            'first_name' => array('value' => 'Jane'),
            'last_name' => array('value' => 'Smith'),
        ),
    ),
    'label' => 'Customer',
    'type' => 'dynamic',
);
```

Tags support repeater indexing: `{customer;1;first_name}` gets first repeater item.

**EAV Storage Strategy for Repeaters:**
- Field name: `customer[0][first_name]` (bracket notation)
- Field name: `customer[1][first_name]`
- OR use JSON storage for repeater values
- OR use separate repeater table with position column

**Critical**: Data Access Layer must reconstruct nested arrays identically when reading from EAV.

**External Integration Points (Backward Compatibility Requirements):**

Research Phase 4-5 found these integration types:

1. **Webhook Integrations** (Zapier, Integromat, custom webhooks):
   - Receive entry data as JSON in POST body
   - Use `email_tags()` to populate webhook payload
   - NO direct database access
   - Migration impact: ZERO (receive data via hooks)

2. **Email Service Integrations** (Mailchimp, MailPoet, Mailster):
   - Map form fields to list fields
   - Use `email_tags()` for field value extraction
   - NO direct database access
   - Migration impact: ZERO (receive data via hooks)

3. **Payment Gateway Integrations** (PayPal, Stripe):
   - Store transaction IDs in separate meta keys
   - PayPal: `_super_contact_entry_paypal_order_id`
   - Stripe: Uses Stripe extension with separate storage
   - Direct access: PayPal add-on line 2156 needs Data Access Layer update
   - Migration impact: LOW (single update point per integration)

4. **WooCommerce Integration**:
   - Creates orders from form data
   - Stores order ID in `_super_contact_entry_wc_order_id`
   - Direct access: WooCommerce add-on line 1271 needs update
   - Migration impact: MEDIUM (critical e-commerce functionality)

5. **Front-end Posting**:
   - Creates WordPress posts/pages/CPTs from entries
   - Direct access: Front-end posting add-on line 237 needs update
   - Migration impact: MEDIUM (creates WordPress content from entry data)

**Critical Guarantee**: Third-party code using `get_post_meta($entry_id, '_super_contact_entry_data', true)` will continue working during rollback period. After migration completes, users with custom code should be notified to update to Data Access Layer API (provide documentation and helper functions).

### Technical Reference: Database Schema and Performance Characteristics

**Proposed EAV Table Structure:**

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
    KEY entry_id (entry_id),
    KEY field_name (field_name),
    KEY entry_field (entry_id, field_name),
    KEY field_value (field_value(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Index Strategy Rationale:**

1. **entry_id**: Fast lookups when retrieving all fields for single entry (most common operation)
2. **field_name**: Fast filtering across all entries by specific field (listings filters)
3. **entry_id, field_name**: Composite for "get field X from entry Y" queries
4. **field_value(191)**: Prefix index for searches (MySQL LONGTEXT limit, 191 chars for utf8mb4)

**Storage Size Comparison:**

Current approach (serialized):
- One row per entry in wp_postmeta
- meta_value contains full serialized array (10KB-100KB per entry)
- 8,000 entries = 80MB-800MB in single column

EAV approach:
- Multiple rows per entry (one per field)
- Each row ~200 bytes average (field_name + field_value + metadata)
- Form with 20 fields = 20 rows = 4KB per entry
- 8,000 entries × 20 fields = 160,000 rows = 32MB total
- Significant storage reduction due to no serialization overhead

**Write Performance:**

Current (serialized):
- Single INSERT/UPDATE per entry
- Must serialize entire array (CPU intensive for large arrays)
- No way to update single field without rewriting entire blob

EAV:
- Multiple INSERTs per entry (one per field)
- Can use bulk INSERT for batch efficiency
- Can update single field with targeted UPDATE
- Trade-off: More rows, but more flexible

**Read Performance:**

Current (serialized):
- Single query retrieves entire entry
- Fast for "get all fields" operation
- Horrible for "filter by field value" (SUBSTRING_INDEX)
- Cannot use indexes for searches

EAV:
- Multiple rows per entry (requires JOIN or WHERE IN)
- Slightly slower for "get all fields" (mitigated by caching)
- 30-60x faster for filtered queries (indexed WHERE clauses)
- Enables complex filtering impossible with serialized data

**Migration Performance Estimates:**

Based on research findings:
- 8,100 entries from production testing
- Average 15-20 fields per entry
- Total EAV rows to create: ~150,000 rows

Migration approach using Action Scheduler:
- Batch size: 1,000 entries per batch
- Processing time per batch: 30-60 seconds
- Total batches: 9 batches
- Total migration time: 5-10 minutes
- Verify with checksum: MD5 hash comparison

**Background Processing via Action Scheduler:**

Why Action Scheduler (not WP-Cron):
- Survives PHP timeouts (stores queue in database)
- Survives server restarts (resumes automatically)
- Built-in retry logic for failed batches
- Progress tracking out of the box
- Battle-tested by WooCommerce, Yoast SEO

Implementation location:
- Bundle Action Scheduler library in `/root/go/src/github.com/RensTillmann/super-forms/src/includes/libraries/action-scheduler/`
- Initialize in main plugin file
- Schedule batches via `as_schedule_single_action()`

**Migration State Tracking (WordPress Options):**

```php
'superforms_eav_migration' => array(
    'status' => 'completed', // not_started|in_progress|completed|rolled_back
    'started_at' => '2025-10-31 14:00:00',
    'completed_at' => '2025-10-31 14:23:45',
    'total_entries' => 8456,
    'migrated_entries' => 8456,
    'failed_entries' => array(), // entry_id => error_message
    'using_storage' => 'eav', // 'eav' or 'serialized' (for rollback)
)
```

Stored in `wp_options` table as `superforms_eav_migration`.

For multisite: Stored in `wp_[site_id]_options` (per-site migration state).

**Entry Relationship Meta Keys (NOT Migrated):**

These remain in wp_postmeta (separate from entry data):
- `_super_contact_entry_wc_order_id`: WooCommerce order link
- `_super_contact_entry_paypal_order_id`: PayPal transaction link
- `_super_created_post`: Front-end posting created post link
- `_super_contact_entry_ip`: IP address tracking
- `_super_contact_entry_status`: Custom workflow status

Why separate: These are entry metadata, not field data. Keep in wp_postmeta for WordPress compatibility.

**WordPress Multisite Considerations:**

Research confirmed: Super Forms is multisite compatible.

Migration approach:
- Each site migrates independently
- Network admin can view migration status across all sites
- Migration scheduled per-site (not network-wide)
- EAV table prefix uses `$wpdb->prefix` (auto-handles multisite)

Network admin dashboard shows:
- Site 1: Completed (8,456 entries migrated)
- Site 2: In Progress (3,200 / 5,000 entries)
- Site 3: Not Started

### File Locations and Modification Plan

**Files Requiring Code Changes (13 files):**

1. **NEW FILE**: `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-data-access.php`
   - Create Data Access Layer abstraction
   - Methods: `get_entry_data()`, `save_entry_data()`, `update_entry_field()`, `delete_entry_data()`
   - Implement dual-read/write logic
   - Handle repeater fields and nested arrays

2. **src/includes/class-ajax.php** (PRIMARY WRITE/READ LOCATIONS):
   - Update lines 4997, 1732, 3182 (writes)
   - Update lines 1213, 1216, 1276, 1285, 1329, 5840 (reads)
   - Replace `get_post_meta()` with `SUPER_Data_Access::get_entry_data()`
   - Replace `add_post_meta()` / `update_post_meta()` with `SUPER_Data_Access::save_entry_data()`

3. **src/includes/class-common.php** (TAG SYSTEM):
   - No changes to `email_tags()` function itself
   - Update callers that populate `$data` parameter (if any direct calls exist)

4. **src/includes/extensions/listings/listings.php** (PERFORMANCE CRITICAL):
   - Rewrite lines 2437-2857 (query construction)
   - Replace SUBSTRING_INDEX with EAV table JOINs
   - Move filters from HAVING to WHERE clause
   - Update line 2979 (entry data retrieval for display)

5. **src/super-forms.php**:
   - Update lines 1067, 2158 (reads)
   - Update lines 3181-3182 (duplication)
   - Update lines 1574 (admin search WHERE clause)

6. **src/includes/class-shortcodes.php**:
   - Update lines 4292, 4294, 7657, 7794, 7799, 7827, 7867 (reads)

7. **src/includes/class-pages.php**:
   - Update line 2499 (backend entry display)

8. **src/includes/extensions/listings/form-blank-page-template.php**:
   - Update lines 107, 142 (reads)

9. **src/add-ons/super-forms-front-end-posting/super-forms-front-end-posting.php**:
   - Update line 237 (CRITICAL for post creation from entries)

10. **src/add-ons/super-forms-woocommerce/super-forms-woocommerce.php**:
    - Update line 1271 (CRITICAL for WooCommerce integration)

11. **src/add-ons/super-forms-paypal/super-forms-paypal.php**:
    - Update line 2156 (CRITICAL for PayPal IPN handling)

12. **NEW FILE**: `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-migration-manager.php`
    - Migration orchestration
    - Batch processing with Action Scheduler
    - Progress tracking and error handling
    - Checksum verification
    - Rollback functionality

13. **NEW FILE**: `/root/go/src/github.com/RensTillmann/super-forms/src/includes/admin/pages/migration.php`
    - Migration admin page UI
    - Progress bar with AJAX updates
    - Start/Pause/Resume controls
    - Error display
    - Rollback interface

**Files Requiring Testing (No Code Changes):**

14 add-ons that use indirect data access via hooks:
- CSV Attachment, Email Reminders, Email Templates
- Mailchimp, MailPoet, Mailster
- Password Protect, Popups
- Register & Login, Signature
- XML Attachment, Zapier
- Calculator (frontend only, no storage interaction)
- PDF Generator (client-side only)

**Database Migration Files:**

- **NEW FILE**: `/root/go/src/github.com/RensTillmann/super-forms/src/includes/migrations/001-create-eav-tables.php`
  - Creates wp_superforms_entry_data table
  - Adds indexes
  - Handles table prefix for multisite

- **NEW FILE**: `/root/go/src/github.com/RensTillmann/super-forms/src/includes/migrations/002-migrate-entry-data.php`
  - Batch migration logic
  - Read serialized data
  - Write to EAV table
  - Verify with checksums

**Admin Notice/UX Files:**

- **NEW FILE**: `/root/go/src/github.com/RensTillmann/super-forms/src/includes/admin/notices/migration-required.php`
  - Pre-migration warning notice
  - "Start Database Update" button

- **NEW FILE**: `/root/go/src/github.com/RensTillmann/super-forms/src/includes/admin/notices/migration-progress.php`
  - Progress notice shown on all admin pages during migration
  - Links to detailed migration page

### Migration Safety Requirements and Rollback Strategy

**Race Condition Prevention:**

During migration, prevent data inconsistencies:
1. Lock all entry editing when `migration['status'] === 'in_progress'`
2. Lock bulk operations (delete, export, status changes)
3. Lock CSV import
4. Allow form submissions (they write to BOTH storage methods)

Implementation:
```php
if ($migration['status'] === 'in_progress') {
    // Show notice: "Database upgrade in progress. Entry editing temporarily disabled."
    return new WP_Error('migration_in_progress', __('Please wait for database upgrade to complete.'));
}
```

**Verification Strategy:**

After migrating each entry:
```php
$original_data = unserialize(get_post_meta($entry_id, '_super_contact_entry_data', true));
$eav_data = SUPER_Data_Access::get_from_eav_tables($entry_id);

if (md5(serialize($original_data)) !== md5(serialize($eav_data))) {
    // Rollback this entry's EAV data
    // Log error with entry ID
    // Continue with other entries
}
```

**Rollback Mechanism:**

Toggle storage method via option:
```php
update_option('superforms_eav_migration', array(
    'status' => 'rolled_back',
    'using_storage' => 'serialized', // Switch back to old method
    'rolled_back_at' => current_time('mysql'),
));
```

Data Access Layer immediately starts reading from serialized meta.

**Rollback Limitations (Must Warn User):**

```
╔════════════════════════════════════════════════════════════╗
║ ⚠️  Confirm Rollback                                        ║
║                                                            ║
║ Rolling back will revert all contact entries to their     ║
║ state at the time of migration (Nov 1, 2025).             ║
║                                                            ║
║ ⚠️  Changes made after migration will be lost:             ║
║ • 145 entries edited since migration                      ║
║ • 23 new entries created since migration                  ║
║                                                            ║
║ This action should only be used in emergencies.           ║
║                                                            ║
║ [Cancel] [Yes, Rollback Anyway]                           ║
╚════════════════════════════════════════════════════════════╝
```

Calculate impact:
```php
// Count entries created after migration
$new_entries = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) FROM {$wpdb->posts}
    WHERE post_type = 'super_contact_entry'
    AND post_date > %s
", $migration['completed_at']));

// Count entries modified after migration
$edited_entries = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) FROM {$wpdb->posts}
    WHERE post_type = 'super_contact_entry'
    AND post_modified > %s
    AND post_date < %s
", $migration['completed_at'], $migration['completed_at']));
```

**Old Data Preservation:**

Never delete `_super_contact_entry_data` meta key after migration:
- Enables indefinite rollback capability
- Safety net if EAV data corruption occurs
- Debugging aid for migration issues
- Only deleted when entry itself is deleted

**Testing Requirements:**

Before production deployment:
1. Test on copy of production database (8,100 entry dataset from research)
2. Verify all 197 production forms from research migrate successfully
3. Test rollback on test site, verify data integrity
4. Test listings performance (15-20 sec → <500ms goal)
5. Test admin search performance (LIKE on serialized → indexed EAV)
6. Test CSV export byte-for-byte match
7. Test external integrations (Zapier, Mailchimp webhooks)
8. Test WooCommerce order creation
9. Test PayPal IPN handling
10. Test Front-end Posting post creation

**Performance Benchmarks:**

Document before/after metrics:
- Listings query time with 8,100 entries + 3 filters
- Admin search query time
- CSV export time for 1,000 entries
- Entry edit page load time
- Single entry data retrieval time

Target improvements:
- Listings: 15-20 sec → <500ms (30-60x)
- Admin search: 500-1,000ms → 50-100ms (10x)
- CSV export: Linear improvement (faster for large datasets)

### WordPress Standards and Code Quality Requirements

**WordPress Coding Standards Compliance:**

All new code must follow WPCS:
- Function naming: `super_forms_*` prefix
- Class naming: `SUPER_*` prefix
- Variable naming: snake_case
- Indentation: Tabs (not spaces)
- PHPDoc blocks for all public methods
- Sanitization: `sanitize_text_field()`, `esc_html()`, `esc_sql()`
- Nonce verification: `wp_verify_nonce()` for all AJAX requests
- Capability checks: `current_user_can('manage_options')` for admin actions

**Data Access Layer API Design:**

```php
class SUPER_Data_Access {
    /**
     * Get entry data from storage (EAV or serialized)
     *
     * @param int $entry_id WordPress post ID of contact entry
     * @return array Entry data in standard format
     */
    public static function get_entry_data($entry_id) { }

    /**
     * Save entry data to storage
     *
     * @param int $entry_id WordPress post ID
     * @param array $data Entry data array
     * @return bool True on success
     */
    public static function save_entry_data($entry_id, $data) { }

    /**
     * Update single field value
     *
     * @param int $entry_id WordPress post ID
     * @param string $field_name Field name to update
     * @param mixed $field_value New value
     * @return bool True on success
     */
    public static function update_entry_field($entry_id, $field_name, $field_value) { }

    /**
     * Delete all entry data
     *
     * @param int $entry_id WordPress post ID
     * @return bool True on success
     */
    public static function delete_entry_data($entry_id) { }
}
```

**Error Handling Strategy:**

Use `WP_Error` objects:
```php
if ($error_condition) {
    return new WP_Error(
        'super_forms_migration_error',
        __('Failed to migrate entry data', 'super-forms'),
        array('entry_id' => $entry_id)
    );
}
```

Log errors to custom log file:
```php
error_log('[Super Forms Migration] Entry ' . $entry_id . ' failed: ' . $error_message);
```

**Testing Strategy:**

1. Unit tests for Data Access Layer (PHPUnit)
2. Integration tests for tag system compatibility
3. Regression tests for external integrations
4. Load tests for query performance
5. Migration tests on production-size datasets

**Documentation Requirements:**

1. Developer documentation for Data Access Layer API
2. Migration guide for site owners
3. Rollback procedure documentation
4. Performance benchmark comparison
5. Update CHANGELOG.md with migration details

### Summary: Migration Readiness Assessment

**Confidence Level: HIGH**

Reasons for high confidence:
1. Tag system is perfectly abstracted (no changes needed)
2. Only 13 files require code changes (35 specific line updates)
3. 14 add-ons already use abstraction (zero changes)
4. Comprehensive 30-phase research completed (15,000 lines)
5. Clear rollback strategy with safety nets
6. Dual-write ensures zero downtime
7. Action Scheduler handles background processing reliably

**Biggest Risks (and Mitigations):**

1. **Risk**: Repeater field nested array reconstruction from EAV
   - **Mitigation**: Test with complex forms containing repeaters, verify array structure matches exactly

2. **Risk**: CSV export column order changes break automated imports
   - **Mitigation**: Store field order in EAV, reconstruct identical order, byte-for-byte comparison tests

3. **Risk**: Migration timeout on very large databases (>50,000 entries)
   - **Mitigation**: Action Scheduler batch processing, 1,000 entries per batch, automatic resume

4. **Risk**: Third-party code directly accessing serialized data breaks
   - **Mitigation**: Preserve serialized data permanently, provide migration guide, dual-read ensures compatibility

5. **Risk**: Listings query rewrite introduces bugs
   - **Mitigation**: ~40 lines to rewrite (small surface area), comprehensive testing on 8,100 entry dataset

**Implementation Order:**

1. Create Data Access Layer (class-data-access.php)
2. Create database migration files (create tables)
3. Update 3 critical add-ons (Front-end Posting, WooCommerce, PayPal)
4. Update core files (class-ajax.php, super-forms.php, etc.)
5. Rewrite Listings Extension queries
6. Create migration manager with Action Scheduler
7. Build admin UI (migration page, notices)
8. Test on production database copy
9. Deploy with feature flag for gradual rollout

## User Notes
<!-- Any specific notes or requirements from the developer -->

## Work Log
<!-- Updated as work progresses -->
- [YYYY-MM-DD] Started task, initial research
