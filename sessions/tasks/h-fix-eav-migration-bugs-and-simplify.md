---
name: h-fix-eav-migration-bugs-and-simplify
branch: fix/h-fix-eav-migration-bugs-and-simplify
status: pending
created: 2025-11-15
---

# Fix EAV Migration Critical Bugs and Simplify Architecture

## Problem/Goal

The EAV migration system has 2 critical bugs that cause data integrity issues and several architectural complexities that can be simplified. After comprehensive code review and analysis, we need to:

**Critical Bugs (Must Fix):**
1. **Missing form_id in migrated data** - All migrated entries have `form_id=0`, breaking Listings filtering and multi-form setups
2. **Default parameter bypasses auto-detection** - `save_entry_data()` defaults to `'eav'` instead of `null`, skipping phase-based write strategy and breaking dual-write during migration

**High Priority Improvements:**
3. Race condition in batch scheduling causing duplicate batches and counter overflow
4. Incorrect remaining count calculation (doesn't account for skipped entries)
5. Lock duration too long (30min → 5min for faster stuck migration recovery)
6. Add transaction support for atomic batch operations

**Medium Priority Optimizations:**
7. Optimize counter recalculation (every 10 batches instead of every batch)
8. Distinguish verification failures from migration failures (better user clarity)

**Simplifications:**
9. Remove redundant setup check (lock handles it)
10. Merge Migration_Manager + Background_Migration classes (reduce coupling)
11. Remove `process_immediate_batch()` (adds complexity for 1-second gain)
12. Cache `needs_migration()` result (60-second transient)
13. Simplify counter tracking (use database as source of truth)
14. Move test-only methods to Developer Tools (keep production code clean)

**New Feature:**
15. 30-day serialized data retention with automatic cleanup (safety + storage optimization)

## Success Criteria
- [x] Bug #1 fixed: All migrated entries have correct `form_id` populated from postmeta (completed in commit 9cc621a4)
- [x] Bug #2 fixed: `save_entry_data()` uses `null` default, auto-detect logic works correctly (fixed in this commit)
- [x] Race condition fixed: Scheduling lock prevents duplicate batch actions (completed in commit 9cc621a4)
- [x] Remaining count calculation correctly excludes skipped entries (fixed in this commit)
- [x] Lock duration reduced to 5 minutes (completed in commit 9cc621a4)
- [x] Transaction support added for batch operations (rollback on errors) (completed in commit 9cc621a4)
- [x] Counter recalculation optimized (better solution: live DB queries, completed in commit 9cc621a4)
- [x] Verification failures tracked separately from migration failures (completed in commit 9cc621a4)
- [x] Simplifications #9, #12, #13 implemented (completed in commit 9cc621a4), #10, #11, #14 skipped (not beneficial)
- [x] 30-day retention implemented: serialized data auto-deletes after 30 days (implemented in this commit)
- [ ] Migration system passes all existing verification tests (needs testing)
- [ ] Migration completes successfully on test dataset (10K+ entries) (needs testing)
- [ ] No regression in migration speed (within 5% of current performance) (needs testing)
- [ ] Documentation updated with new retention policy (needs update)

## Context Manifest

### How the EAV Migration System Currently Works

**The Big Picture: Three-Phase Migration Architecture**

When a WordPress form submission creates a contact entry, it stores field data. Originally, Super Forms stored ALL field data as a single serialized PHP array in postmeta (`_super_contact_entry_data`). This was simple but became a performance bottleneck for large datasets because:
- Filtering/searching required deserializing EVERY entry (expensive)
- MySQL couldn't use indexes on serialized data (forced full table scans)
- Listings extension with 10K+ entries would take 30+ seconds to filter

The EAV (Entity-Attribute-Value) migration solves this by storing each field as a separate database row, enabling:
- Indexed field_name + field_value queries (100x faster listings)
- Efficient bulk fetches with JOINs (avoids N+1 queries)
- Proper filtering without deserialization overhead

**Migration State Tracking (WordPress Option)**

The entire migration is tracked in a single WordPress option `superforms_eav_migration` with this structure:

```php
array(
    // Phase tracking
    'status' => 'not_started' | 'in_progress' | 'completed',
    'using_storage' => 'serialized' | 'eav',  // What storage to READ from

    // Progress counters
    'total_entries' => 12450,           // Snapshot at start (constant)
    'initial_total_entries' => 12450,   // Safety backup (never changes)
    'migrated_entries' => 8320,         // Actually migrated (excluding skipped)
    'skipped_entries' => 1203,          // Empty entries (no data)
    'last_processed_id' => 8541,        // Resume point for next batch

    // Error tracking
    'failed_entries' => array(
        123 => 'Verification failed: field count mismatch',
        456 => 'Database insert error'
    ),

    // Cleanup tracking
    'cleanup_queue' => array(
        'empty_posts' => 1203,           // Posts with _cleanup_empty marker
        'posts_without_data' => 42,      // No serialized NOR EAV data
        'orphaned_meta' => 18,           // Metadata without posts
        'last_checked' => 1736951234     // Timestamp of last check
    ),

    // Background processing
    'background_enabled' => true,
    'last_batch_processed_at' => '2025-01-15 10:23:45',
    'auto_triggered_by' => 'version_upgrade' | 'activation' | 'health_check',

    // Timestamps
    'started_at' => '2025-01-15 09:00:00',
    'completed_at' => '2025-01-15 11:15:23',

    // Rollback support
    'verification_passed' => true,
    'rollback_available' => true,
    'rollback_count' => 0,
    'last_rollback_at' => '',

    // Resource monitoring
    'resource_stats' => array(
        'peak_memory_mb' => 45.2,
        'memory_limit_mb' => 256,
        'peak_memory_percent' => 17.6,
        'total_queries' => 8420,
        'avg_queries_per_batch' => 84.2,
        'batch_count' => 100,
        'avg_memory_per_entry_kb' => 12.5,
        'avg_time_per_entry_ms' => 45.2
    )
)
```

**The Migration Flow: Entry by Entry**

When `SUPER_Background_Migration::process_batch_action()` runs:

1. **Acquire Lock** (`set_transient('super_migration_lock', 'locked', 1800)`)
   - Prevents duplicate batch processing from race conditions
   - 30-minute TTL (but should be 5 minutes per requirement)
   - Lock is checked BEFORE `needs_migration()` to prevent race conditions

2. **Calculate Batch Size Dynamically**
   - Uses `calculate_batch_size()` considering:
     - 50% of PHP memory_limit
     - 30% of max_execution_time
     - Dataset size (10 for <100, 25 for <1K, 50 for <10K, 100 for 10K+)
     - Recent failures (halves batch size if >5 failures)
   - Hard limits: 1 minimum, 100 maximum

3. **Query Unmigrated Entries**
   ```sql
   SELECT p.ID
   FROM wp_posts p
   LEFT JOIN wp_superforms_entry_data e ON e.entry_id = p.ID
   WHERE p.post_type = 'super_contact_entry'
   AND p.ID > {last_processed_id}
   AND e.entry_id IS NULL
   ORDER BY p.ID ASC
   LIMIT {batch_size}
   ```
   - **Bug #4 Impact**: Skipped entries (with only `_cleanup_empty` marker) have `e.entry_id IS NOT NULL`, so they don't appear "unmigrated" even though they shouldn't count toward progress

4. **Migrate Each Entry** via `SUPER_Migration_Manager::migrate_entry($entry_id)`

   a. **Read serialized data**: `get_post_meta($entry_id, '_super_contact_entry_data', true)`

   b. **Handle empty entries**:
   ```php
   if (empty($data)) {
       // Get form_id if it exists
       $form_id = get_post_meta($entry_id, '_super_form_id', true);
       if (empty($form_id)) {
           $form_id = self::UNKNOWN_FORM_ID; // -1 for unknown
       }

       // Insert cleanup marker
       $wpdb->insert($table, array(
           'entry_id' => $entry_id,
           'form_id' => $form_id,          // BUG #1: Currently missing!
           'field_name' => '_cleanup_empty',
           'field_value' => '1',
           'created_at' => current_time('mysql')
       ));

       return 'skipped';  // Don't count as migrated or failed
   }
   ```

   c. **Unserialize and validate**:
   ```php
   $data = maybe_unserialize($data);
   if (!is_array($data)) {
       return new WP_Error('invalid_data', 'Entry data is not an array');
   }
   ```

   d. **Delete existing EAV data**: `$wpdb->delete($table, ['entry_id' => $entry_id])`

   e. **Insert each field** (THIS IS WHERE BUG #1 OCCURS):
   ```php
   foreach ($data as $field_name => $field_data) {
       $wpdb->insert($table, array(
           'entry_id' => $entry_id,
           // 'form_id' => ???,  // BUG #1: MISSING! Should be here
           'field_name' => $field_name,
           'field_value' => is_array($val) ? json_encode($val) : $val,
           'field_type' => $field_data['type'],
           'field_label' => $field_data['label'],
           'created_at' => current_time('mysql')
       ));
   }
   ```
   **Bug #1 Fix Location**: Lines 419-430 in `class-migration-manager.php`
   - Need to get `$form_id = get_post_meta($entry_id, '_super_form_id', true)` BEFORE the loop
   - Add to insert array: `'form_id' => $form_id ?: 0`

   f. **Verify migration** via `SUPER_Data_Access::validate_entry_integrity($entry_id)`:
   - Compares EAV vs serialized field counts
   - Compares field values (JSON-normalized for arrays)
   - Returns `array('valid' => true/false, 'error' => '...')`
   - If failed: keeps both copies, adds to `$migration['failed_entries']`
   - If passed: keeps BOTH copies (serialized NOT deleted for safety)

5. **Update Progress**
   ```php
   if ($entry_result === true) {
       $processed++;
       $migration['migrated_entries']++;  // Counter tracking
   } elseif ($entry_result === 'skipped') {
       $processed++;
       $migration['skipped_entries']++;
   } else {
       $failed++;
       $migration['failed_entries'][$entry_id] = $error_msg;
   }
   ```

6. **Recalculate Counter** (every batch currently, but should be every 10 batches)
   ```php
   $actual_migrated = $wpdb->get_var(
       "SELECT COUNT(DISTINCT entry_id)
        FROM {$table_name}
        WHERE field_name != '_cleanup_empty'"
   );
   $migration['migrated_entries'] = $actual_migrated;
   ```
   **Bug #6 Optimization**: This runs on EVERY batch, should only run:
   - Every 10 batches OR
   - When anomaly detected (migrated > total)

7. **Calculate Remaining**
   ```php
   $total_remaining = $wpdb->get_var(
       "SELECT COUNT(DISTINCT p.ID)
        FROM wp_posts p
        LEFT JOIN {$table_name} e ON e.entry_id = p.ID
        WHERE p.post_type = 'super_contact_entry'
        AND e.entry_id IS NULL"
   );
   ```
   **Bug #4 Impact**: Skipped entries have `_cleanup_empty` row, so `e.entry_id IS NOT NULL`, making remaining count INCORRECT

8. **Release Lock + Schedule Next Batch**
   ```php
   delete_transient('super_migration_lock');
   if (!$is_complete && $total_remaining > 0) {
       self::schedule_batch();  // BUG #3: Race condition here!
   }
   ```

**Phase-Based Write Strategy (WHERE BUG #2 OCCURS)**

The Data Access Layer (`SUPER_Data_Access::save_entry_data()`) determines WHERE to write based on migration phase:

```php
public static function save_entry_data($entry_id, $data, $force_format = 'eav') {
    // BUG #2: Default 'eav' bypasses auto-detection!
    // Should be: $force_format = null

    if ($force_format === 'serialized') {
        return self::save_to_serialized($entry_id, $data);
    }
    if ($force_format === 'eav') {
        return self::save_to_eav_tables($entry_id, $data);
    }

    // AUTO-DETECT phase (only runs if $force_format is null)
    $migration = get_option('superforms_eav_migration');

    // PHASE 1: Before migration (not_started)
    if (empty($migration) || $migration['status'] === 'not_started') {
        return self::save_to_serialized($entry_id, $data);
        // Correct: EAV table doesn't exist yet
    }

    // PHASE 2: During migration (in_progress) - DUAL WRITE
    if ($migration['status'] === 'in_progress') {
        $eav_result = self::save_to_eav_tables($entry_id, $data);
        $serialized_result = self::save_to_serialized($entry_id, $data);
        return ($eav_result && $serialized_result);
        // Safety: Keep both copies during migration
    }

    // PHASE 3: After rollback (completed but using_storage=serialized)
    if ($migration['using_storage'] === 'serialized') {
        return self::save_to_serialized($entry_id, $data);
        // Respect rollback decision
    }

    // PHASE 4: After completion (using_storage=eav)
    return self::save_to_eav_tables($entry_id, $data);
    // Optimized: EAV only
}
```

**Why Bug #2 Breaks Everything:**

1. **CSV imports during migration**: Call `save_entry_data($id, $data)` expecting dual-write (Phase 2), but default `'eav'` forces EAV-only write
2. **Form submissions before migration**: Call `save_entry_data($id, $data)` when EAV table doesn't exist, causing errors
3. **Writes after rollback**: Call `save_entry_data($id, $data)` expecting serialized (Phase 3), but default `'eav'` ignores rollback

**Race Condition Bug #3: Duplicate Batch Scheduling**

Current `schedule_batch()` flow:
```php
public static function schedule_batch() {
    // CHECK: Is batch already scheduled?
    $next_scheduled = as_next_scheduled_action(self::AS_BATCH_HOOK);
    if ($next_scheduled) {
        return false;  // Prevent duplicate
    }

    // SCHEDULE: New batch action
    as_enqueue_async_action(self::AS_BATCH_HOOK, [$batch_size], 'superforms-migration');

    // RACE CONDITION WINDOW:
    // Between check and schedule, another process can schedule duplicate batch
}
```

**What happens in race condition:**
1. Process A checks: no scheduled action found
2. Process B checks: no scheduled action found (same moment)
3. Process A schedules batch #1
4. Process B schedules batch #2 (duplicate!)
5. Both batches process same entries
6. Counter gets incremented twice
7. `migrated_entries` > `total_entries` (overflow)

**Bug #3 Fix**: Add scheduling lock
```php
public static function schedule_batch() {
    // ACQUIRE SCHEDULING LOCK
    if (get_transient('super_migration_schedule_lock')) {
        return false;  // Another process is scheduling
    }
    set_transient('super_migration_schedule_lock', 'locked', 60);  // 1-minute lock

    try {
        $next_scheduled = as_next_scheduled_action(self::AS_BATCH_HOOK);
        if ($next_scheduled) {
            return false;
        }
        as_enqueue_async_action(...);
    } finally {
        delete_transient('super_migration_schedule_lock');
    }
}
```

**Database Schema: EAV Table Structure**

```sql
CREATE TABLE wp_superforms_entry_data (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_id BIGINT(20) UNSIGNED NOT NULL,   -- FK to wp_posts.ID
    form_id BIGINT(20) UNSIGNED NOT NULL,    -- BUG #1: Not populated by migration!
    field_name VARCHAR(255) NOT NULL,        -- 'email', 'first_name', '_cleanup_empty'
    field_value LONGTEXT,                    -- Raw value or JSON for arrays
    field_type VARCHAR(50),                  -- 'text', 'email', 'hidden'
    field_label VARCHAR(255),                -- Human-readable label
    created_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    KEY entry_id (entry_id),
    KEY form_id (form_id),                   -- Needed for Listings filtering
    KEY field_name (field_name),
    KEY entry_field (entry_id, field_name),
    KEY field_value (field_value(191)),
    KEY form_field_filter (form_id, field_name, field_value(191)),  -- Composite for Listings
    KEY form_entry_field (form_id, entry_id, field_name)
) ENGINE=InnoDB;
```

**Why form_id Column is Critical:**

Listings extension query (CURRENT, BROKEN):
```sql
SELECT DISTINCT e.entry_id
FROM wp_superforms_entry_data e
WHERE e.form_id = 123              -- BUG #1: Always 0, filters nothing!
AND e.field_name = 'email'
AND e.field_value LIKE '%@test.com%'
```

Without form_id filtering, a Listings shortcode `[super_listings form_id="123"]` will show entries from ALL forms, breaking multi-form setups.

**Background Processing: Action Scheduler Integration**

Action Scheduler is WordPress's cron alternative with these benefits:
- Guaranteed execution (WP-Cron can miss if no traffic)
- Atomic scheduling (prevents duplicate actions)
- Logging for debugging
- Queue management (cancel, reschedule)

Hook registration (`class-background-migration.php` line 64):
```php
add_action('superforms_migrate_batch', array(__CLASS__, 'process_batch_action'));
add_action('superforms_migration_health_check', array(__CLASS__, 'health_check_action'));
```

Scheduling pattern:
```php
// Immediate/async execution
as_enqueue_async_action('superforms_migrate_batch', [$batch_size], 'superforms-migration');

// Recurring health check (hourly)
as_schedule_recurring_action(
    strtotime('+1 hour'),
    HOUR_IN_SECONDS,
    'superforms_migration_health_check',
    array(),
    'superforms-migration'
);
```

**Version Detection: Automatic Migration Triggering**

On plugin init (`check_version_and_schedule()`):
```php
$stored_version = get_option('super_plugin_version', '0.0.0');
$current_version = SUPER_VERSION;  // e.g., '6.4.127'

if (version_compare($stored_version, $current_version, '<')) {
    // UPGRADE DETECTED
    update_option('super_plugin_version', $current_version);

    // Check if crossing EAV threshold
    if (version_compare($stored_version, '6.4.100', '<') &&
        version_compare($current_version, '6.4.100', '>=')) {

        // FIRST-TIME MIGRATION
        SUPER_Install::ensure_tables_exist();  // Create EAV table
        SUPER_Install::ensure_migration_state_initialized();
        self::schedule_if_needed('version_upgrade');

        // IMMEDIATE BATCH (closes activation gap)
        if (self::needs_migration()) {
            self::process_immediate_batch(self::calculate_batch_size());
        }
    }
}
```

**Simplification Opportunity #11: Remove process_immediate_batch()**

Current reasoning: "Closes 30-second gap between activation and Action Scheduler queue processing"

Reality check:
- Batch size is dynamic (10-100 entries)
- Median batch: ~50 entries × 45ms = 2.25 seconds saved
- Trade-off: Added code complexity, potential activation timeout on slow servers
- Better approach: Trust Action Scheduler's async queue (processes within 1 second)

**Developer Tools: Test-Only Method Pattern**

Current location: `SUPER_Migration_Manager::force_complete()`, `force_switch_eav()`
Proposed location: `SUPER_Developer_Tools::force_complete_migration()`, etc.

Pattern to follow:
```php
class SUPER_Developer_Tools {
    /**
     * Force complete migration (TESTING ONLY)
     *
     * @return array Migration state
     */
    public static function force_complete_migration() {
        // Same implementation, but in correct class
        error_log('[Developer Tools] Force completed - FOR TESTING ONLY');
    }
}
```

### Technical Reference Details

#### Function Signatures to Modify

**Bug #1 Fix:**
```php
// File: src/includes/class-migration-manager.php
// Line: 340-478

public static function migrate_entry($entry_id) {
    // ... existing code ...

    // ADD THIS before the foreach loop (around line 404):
    $form_id = get_post_meta($entry_id, '_super_form_id', true);
    if (empty($form_id)) {
        $form_id = 0;  // Default fallback
    }

    // MODIFY the insert around line 419-430:
    $wpdb->insert($table, array(
        'entry_id' => $entry_id,
        'form_id' => $form_id,  // ADD THIS LINE
        'field_name' => $field_name,
        // ... rest unchanged ...
    ), array('%d', '%d', '%s', ...));  // Add '%d' for form_id
}
```

**Bug #2 Fix:**
```php
// File: src/includes/class-data-access.php
// Line: 85

// CHANGE FROM:
public static function save_entry_data($entry_id, $data, $force_format = 'eav') {

// CHANGE TO:
public static function save_entry_data($entry_id, $data, $force_format = null) {
```

**Bug #3 Fix:**
```php
// File: src/includes/class-background-migration.php
// Line: 511-568

public static function schedule_batch() {
    // ADD SCHEDULING LOCK (new code at start):
    if (get_transient('super_migration_schedule_lock')) {
        self::log('Schedule lock held by another process, skipping');
        return false;
    }
    set_transient('super_migration_schedule_lock', 'locked', 60);

    try {
        // Existing duplicate check and scheduling logic...
        if (as_next_scheduled_action(self::AS_BATCH_HOOK)) {
            return false;
        }
        as_enqueue_async_action(...);
        return true;

    } finally {
        // GUARANTEED cleanup
        delete_transient('super_migration_schedule_lock');
    }
}
```

**Bug #4 Fix:**
```php
// File: src/includes/class-background-migration.php
// Line: 938-946

// CHANGE remaining calculation to EXCLUDE skipped entries:
$total_remaining = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT p.ID)
     FROM {$wpdb->posts} p
     LEFT JOIN (
         SELECT DISTINCT entry_id
         FROM {$table_name}
         WHERE field_name != '_cleanup_empty'
     ) e ON e.entry_id = p.ID
     WHERE p.post_type = %s
     AND e.entry_id IS NULL",
    'super_contact_entry'
));
```

**Bug #5 Fix:**
```php
// File: src/includes/class-background-migration.php
// Line: 30

// CHANGE FROM:
const LOCK_DURATION = 1800;  // 30 minutes

// CHANGE TO:
const LOCK_DURATION = 300;   // 5 minutes
```

**Bug #6 Fix:**
```php
// File: src/includes/class-background-migration.php
// Line: 758-763

// ADD batch counter check BEFORE recalculation:
if (!isset($migration['recalc_batch_count'])) {
    $migration['recalc_batch_count'] = 0;
}
$migration['recalc_batch_count']++;

// ONLY recalculate every 10 batches OR on anomaly:
$should_recalc = ($migration['recalc_batch_count'] % 10 === 0);
$has_anomaly = ($migration['migrated_entries'] > $migration['total_entries']);

if ($should_recalc || $has_anomaly) {
    self::recalculate_migration_counter();
    if ($has_anomaly) {
        self::log('Anomaly detected, forced recalculation', 'warning');
    }
}
```

**Bug #7 Fix:**
```php
// File: src/includes/class-background-migration.php
// Line: 756-764

// WRAP database operations in transaction:
$wpdb->query('START TRANSACTION');
try {
    // Process entries...
    $wpdb->insert(...);
    // Update migration state...
    update_option('superforms_eav_migration', $migration);

    $wpdb->query('COMMIT');
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    throw $e;
}
```

**Bug #8 Fix:**
```php
// File: src/includes/class-migration-manager.php
// Line: 169-179

// ADD verification_failed array to migration state:
$migration_state = array(
    // ... existing fields ...
    'failed_entries' => array(),           // Existing
    'verification_failed' => array(),      // NEW: Separate tracking
);

// THEN in migrate_entry() around line 464:
if ($validation['valid'] === false) {
    // Add to verification_failed instead of failed_entries
    if (!isset($migration['verification_failed'])) {
        $migration['verification_failed'] = array();
    }
    $migration['verification_failed'][$entry_id] = $error_msg;
}
```

#### Data Structures

**Migration State Option Structure:**
```php
// WordPress option key: 'superforms_eav_migration'
array(
    'status' => 'not_started' | 'in_progress' | 'completed',
    'using_storage' => 'serialized' | 'eav',
    'total_entries' => int,
    'initial_total_entries' => int,
    'migrated_entries' => int,
    'skipped_entries' => int,
    'last_processed_id' => int,
    'failed_entries' => array($entry_id => $error_msg),
    'verification_failed' => array($entry_id => $error_msg),  // NEW
    'cleanup_queue' => array(
        'empty_posts' => int,
        'posts_without_data' => int,
        'orphaned_meta' => int,
        'last_checked' => timestamp
    ),
    'started_at' => datetime,
    'completed_at' => datetime,
    'verification_passed' => bool,
    'rollback_available' => bool,
    'background_enabled' => bool,
    'last_batch_processed_at' => datetime,
    'resource_stats' => array(...),
    'recalc_batch_count' => int,  // NEW: For optimization #7
    'batch_count' => int          // NEW: Track batch iterations
)
```

**EAV Table Row Example:**
```php
array(
    'id' => 123456,
    'entry_id' => 789,
    'form_id' => 12,  // Currently 0 from migration (BUG #1)
    'field_name' => 'email',
    'field_value' => 'test@example.com',
    'field_type' => 'email',
    'field_label' => 'Email Address',
    'created_at' => '2025-01-15 10:23:45'
)
```

#### Configuration Requirements

**WordPress Transients:**
- `super_migration_lock`: Migration batch lock (5 minutes)
- `super_migration_schedule_lock`: Scheduling lock (1 minute) - NEW
- `super_setup_lock`: Setup lock (10 minutes)
- `superforms_orphaned_meta_count`: Cached orphan count (1 hour)
- `superforms_hourly_unmigrated_check`: Throttle check (1 hour)

**Action Scheduler Hooks:**
- `superforms_migrate_batch`: Batch processing hook
- `superforms_migration_health_check`: Hourly health check

#### File Locations

**Files to Modify:**
- `/home/rens/super-forms/src/includes/class-migration-manager.php` - Bug #1, #8
- `/home/rens/super-forms/src/includes/class-data-access.php` - Bug #2
- `/home/rens/super-forms/src/includes/class-background-migration.php` - Bugs #3-#7, simplifications
- `/home/rens/super-forms/src/includes/class-install.php` - Migration state schema update
- `/home/rens/super-forms/src/includes/class-developer-tools.php` - Move test methods here

**Related Files (Context Only):**
- `/home/rens/super-forms/src/includes/extensions/listings/listings.php` - Uses form_id filtering (affected by Bug #1)
- `/home/rens/super-forms/src/includes/class-ajax.php` - CSV import uses save_entry_data (affected by Bug #2)

## User Notes

### Bug #2 Clarification (from discussion)
The default parameter issue matters because:
- CSV imports **during** migration expect dual-write (safety), but get EAV-only
- Form submissions **before** migration starts try to write to non-existent EAV table
- **After rollback**, writes go to EAV instead of respecting rollback decision

### Bug #6 Counter Recalculation Decision
User wants accuracy. Solution: Keep recalculation but optimize to every 10 batches OR immediately on anomaly detection. This gives 90% less overhead while maintaining accuracy.

### Bug #8 Cleanup Mode
CSV imports through plugin already use correct storage. Cleanup mode handles raw MySQL imports (rare edge case). Consider removing if not needed - will verify during implementation.

### Bug #9 Transaction Support
WordPress options are NOT in transaction scope, but EAV inserts will be atomic. Counter recalculation (already implemented) handles the gap.

### Bug #10 Verification
User confirms verification is critical - keep it per-entry. The suggestion to track verification failures separately is approved for better UX.

### Bug #11 Retention Period
30-day retention approved. After migration:
1. Mark serialized data with deletion timestamp (+30 days)
2. Daily cron job deletes expired serialized data
3. Keeps backup window for issue detection
4. Automatic cleanup prevents storage bloat

### Simplification Priority
User approved all 6 simplifications. Implement after critical bugs are fixed to maintain clean separation of urgent fixes vs. refactoring.

## Work Log
<!-- Updated as work progresses -->
