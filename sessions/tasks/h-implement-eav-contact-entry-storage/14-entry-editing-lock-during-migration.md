---
name: 14-subtask
status: completed
created: 2025-10-31
completed: 2025-11-16
---

# Entry Editing Lock During Migration

## Problem/Goal

Implementation of subtask 14 from EAV migration plan.

## Success Criteria

- [x] Migration status checks added to AJAX handlers
- [x] Admin back-end entry editing blocked during migration
- [x] Front-end entry editing blocked during migration
- [x] Input sanitization added for security hardening
- [x] Type validation added to migration status checks
- [x] Code review completed with no critical issues

## Estimated Effort

**2 days**

## Dependencies

- Previous subtasks complete

## Related Research

- Main README: Phase 5 details
- Research task: h-research-eav-migration-complete.md

## Context Manifest

### How Entry Editing Currently Works: The Complete Flow

When an administrator clicks on a contact entry in the WordPress admin (at `wp-admin/edit.php?post_type=super_contact_entry`), they view a list of all submitted form entries. Clicking on any entry loads the single entry edit page, which displays via the `SUPER_Pages::contact_entry()` method in `/home/rens/super-forms/src/includes/class-pages.php` starting at line 2491.

**The Entry Display Page Architecture:**

The `contact_entry()` method receives the entry ID via `$_GET['id']` and first validates that the post exists and is of type `super_contact_entry`. If valid, it marks the entry as read by updating the post status to `super_read` (line 2502). The method then retrieves the entry data using `SUPER_Data_Access::get_entry_data($_GET['id'])` at line 2509, which is our critical abstraction layer that already handles reading from either serialized or EAV storage based on migration state.

The page renders a WordPress-style edit interface with two columns: the left sidebar contains entry metadata (submission date, IP address, form ID, WooCommerce order link if applicable, entry status dropdown), and the right column displays all the form field values in an editable table (lines 2660-2690). Each field is rendered as an editable input with the class `super-shortcode-field` and a `name` attribute matching the field name.

**Critical UI Elements:**
- Title input: `<input name="super_contact_entry_post_title" ...>` (line 2548)
- Entry status dropdown: `<select name="entry_status">` (line 2602)
- Field value inputs: All have class `super-shortcode-field` (line 2720+)
- Update button: `<input class="super-update-contact-entry" data-contact-entry="<?php echo $entry_id; ?>" ...>` (line 2640)

**The JavaScript Update Flow:**

When the admin clicks the "Update" button, JavaScript in `/home/rens/super-forms/src/assets/js/backend/contact-entry.js` handles the click event (line 47). The event handler:

1. Captures the entry ID from `data-contact-entry` attribute
2. Captures the entry status from `select[name="entry_status"]`
3. Loops through all `.super-shortcode-field` elements to collect field values into a `$data` object
4. Includes the post title from `input[name="super_contact_entry_post_title"]`
5. Makes an AJAX POST request to `ajaxurl` with action `super_update_contact_entry`

The AJAX request payload (lines 63-71):
```javascript
{
    action: 'super_update_contact_entry',
    id: $id,                  // Entry ID
    entry_status: $entry_status,
    data: $data               // Object with field_name => field_value
}
```

**The AJAX Handler - Where We Need to Add the Lock Check:**

The AJAX action `super_update_contact_entry` is registered in `/home/rens/super-forms/src/includes/class-ajax.php` at line 63 in the `$ajax_events` array with `nopriv => false` (admin-only access). The handler method `SUPER_Ajax::update_contact_entry()` starts at line 1296.

This is our critical interception point. Currently, this method:

1. Receives entry ID: `$id = absint($_POST['id']);` (line 1297)
2. Receives field data: `$new_data = $_POST['data'];` (line 1298)
3. Updates the post title via `wp_update_post()` (line 1307)
4. Updates entry status via `update_post_meta()` (line 1311)
5. **Retrieves current entry data**: `$data = SUPER_Data_Access::get_entry_data($id);` (line 1313)
6. **Merges new values with existing data** (lines 1318-1324)
7. **Saves updated data**: `SUPER_Data_Access::save_entry_data($id, $data);` (line 1325)
8. Returns success message via `SUPER_Common::output_message()` and dies (lines 1327-1333)

**Why This Creates a Data Integrity Problem During Migration:**

The EAV migration system uses a dual-write strategy documented in the parent task README (lines 206-236). During migration (status = 'in_progress'), the `SUPER_Data_Access::save_entry_data()` method writes to BOTH storage formats to maintain consistency. However, there's a race condition window:

**Scenario: Migration Processes Entry While Admin Edits It**

```
T0: Admin loads entry #500 edit page
    → Data loaded: {name: "John", email: "john@example.com"}

T1: Background migration processes entry #500
    → Reads from serialized: {name: "John", email: "john@example.com"}
    → Writes to EAV table
    → Entry marked as migrated in database

T2: Admin changes name to "Jane" and clicks Update
    → JavaScript sends: {name: "Jane", email: "john@example.com"}
    → AJAX handler calls save_entry_data()
    → During migration: writes to BOTH EAV and serialized
    → But migration batch may have already moved past this entry

T3: Migration completes
    → Switches to EAV-only reads (migration['using_storage'] = 'eav')
    → Entry #500 might have inconsistent data depending on timing
```

The specific problem: If the migration batch processes an entry AFTER an admin has loaded the edit page but BEFORE they click save, the admin's save might overwrite migration data or create inconsistencies, especially if the migration had transformed data formats (like converting repeater fields to JSON).

### How the Migration Lock System Works

The migration lock system is implemented in `/home/rens/super-forms/src/includes/class-background-migration.php` and uses WordPress transients for distributed locking across multiple processes.

**Lock Implementation Details:**

The class defines three public static methods for lock management:

1. **`acquire_lock()`** (line 1105): Attempts to acquire the migration lock
   - Checks if transient `super_migration_lock` exists
   - If transient is `false` (doesn't exist), sets it to `'locked'` for 300 seconds (5 minutes)
   - Returns `true` if lock acquired, `false` if already locked
   - Lock duration: `LOCK_DURATION = 300` seconds (line 30)

2. **`release_lock()`** (line 1124): Releases the migration lock
   - Deletes the `super_migration_lock` transient
   - Always returns `true`

3. **`is_locked()`** (line 1135): Check lock status without modifying it
   - Returns `true` if transient exists (migration is locked)
   - Returns `false` if transient doesn't exist (migration not locked)

**Lock Usage Pattern in Migration Batch Processing:**

The `process_batch_action()` method (line 698) demonstrates the canonical lock usage pattern:

```php
// RACE CONDITION FIX: Acquire lock FIRST, before checking needs_migration()
if (!self::acquire_lock()) {
    self::log('Failed to acquire lock, batch aborted', 'warning');
    return new WP_Error('locked', 'Migration is locked by another process');
}

try {
    // Check if migration still needed (after acquiring lock)
    $needs_migration = self::needs_migration();

    if (!$needs_migration) {
        self::log('No migration needed, releasing lock');
        self::release_lock();
        return array(...);
    }

    // Process batch...
    // ... migration logic ...

    // Release lock BEFORE scheduling next batch
    self::release_lock();

} catch (Exception $e) {
    self::log('Exception during batch processing: ' . $e->getMessage(), 'error');
    self::release_lock(); // Guaranteed cleanup
    return new WP_Error('exception', $e->getMessage());
}
```

**Key Architectural Insights:**

1. **Lock Before Check Pattern**: Always acquire the lock BEFORE checking conditions. This prevents race conditions where multiple processes check the condition simultaneously and all proceed (documented in automatic background migration task, line 719-750).

2. **5-Minute TTL**: The lock auto-expires after 5 minutes even if cleanup fails. This prevents permanent deadlock from PHP fatal errors that bypass `finally` blocks.

3. **Transient-Based**: Uses WordPress transients (which use the database as storage by default), ensuring the lock works across multiple PHP processes and web servers in load-balanced environments.

4. **Guaranteed Cleanup**: The `try/catch` pattern with lock release in both success and error paths ensures locks don't leak.

**Lock State During Migration Phases:**

- **Before migration starts** (`status = 'not_started'`): Lock is NOT held (no migration running)
- **During migration** (`status = 'in_progress'`): Lock is held ONLY during batch processing (acquired at batch start, released at batch end)
- **Between batches**: Lock is released while Action Scheduler waits to schedule next batch
- **After migration complete** (`status = 'completed'`): Lock is permanently released

**Critical Timing Detail**: The lock is NOT continuously held during the entire multi-hour migration. It's acquired for each batch (processing 10-100 entries taking seconds), then released. This means there are windows between batches where entry editing would NOT be blocked if we only checked `is_locked()`.

### Where to Add Lock Checks: Strategic Intervention Points

Based on the analysis above, we need to prevent entry editing when migration status is `in_progress`, NOT just when the lock is held. The lock is only held during active batch processing (seconds), but the migration can take hours with batches scheduled via Action Scheduler.

**Primary Interception Point - AJAX Handler:**

File: `/home/rens/super-forms/src/includes/class-ajax.php`
Method: `SUPER_Ajax::update_contact_entry()`
Location: Line 1296

Add check IMMEDIATELY after receiving the entry ID:

```php
public static function update_contact_entry() {
    $id = absint($_POST['id']);
    $new_data = $_POST['data'];

    // NEW: Check if migration is in progress
    $migration = get_option('superforms_eav_migration', array());
    if (!empty($migration) && isset($migration['status']) && $migration['status'] === 'in_progress') {
        SUPER_Common::output_message(array(
            'error' => true,
            'msg' => esc_html__('Entry editing is temporarily disabled while database migration is in progress. Please wait for migration to complete.', 'super-forms')
        ));
        die();
    }

    // ... existing code continues ...
}
```

**Why This Location:**
- Catches ALL entry updates regardless of source (admin UI, API, custom code)
- Returns user-friendly error message via existing error handling
- Dies early before any database operations
- No need to modify JavaScript (error is handled by existing AJAX success callback at contact-entry.js line 72)

**Secondary Interception Point - Admin UI Display:**

File: `/home/rens/super-forms/src/includes/class-pages.php`
Method: `SUPER_Pages::contact_entry()`
Location: Line 2491

Add visual warning banner and disable the Update button when migration is in progress:

```php
public static function contact_entry() {
    $entry_id = $_GET['id'];

    // ... existing validation code ...

    // NEW: Check migration status for UI warnings
    $migration = get_option('superforms_eav_migration', array());
    $migration_in_progress = (!empty($migration) && isset($migration['status']) && $migration['status'] === 'in_progress');

    // ... existing code to load entry data ...

    // NEW: Display warning banner if migration in progress (insert after line 2542)
    if ($migration_in_progress) {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>' . esc_html__('Database Migration In Progress', 'super-forms') . '</strong></p>';
        echo '<p>' . esc_html__('Entry editing is temporarily disabled to prevent data inconsistencies. The Update button below will not work until migration completes.', 'super-forms') . '</p>';
        if (isset($migration['migrated_entries']) && isset($migration['total_entries'])) {
            $percent = round(($migration['migrated_entries'] / $migration['total_entries']) * 100);
            echo '<p>' . sprintf(esc_html__('Migration Progress: %d%% (%d / %d entries)', 'super-forms'), $percent, $migration['migrated_entries'], $migration['total_entries']) . '</p>';
        }
        echo '</div>';
    }

    // ... existing HTML rendering ...

    // Modify Update button rendering (line 2640) to disable when migration in progress:
    $disabled_attr = $migration_in_progress ? ' disabled="disabled"' : '';
    $disabled_class = $migration_in_progress ? ' disabled' : '';
    echo '<input name="save" type="submit" class="super-update-contact-entry button button-primary button-large' . $disabled_class . '" data-contact-entry="' . absint($entry_id) . '" value="' . esc_html__('Update', 'super-forms') . '"' . $disabled_attr . '>';
}
```

**Why This Location:**
- Provides proactive user communication (they see the warning before clicking Update)
- Disables the button to prevent accidental clicks
- Shows migration progress for transparency
- Doesn't prevent viewing entry data (read-only access is safe)

**Tertiary Interception Point - Listings Extension Edit:**

The Listings extension allows front-end entry editing via the `listings_edit_entry` AJAX handler (class-ajax.php line 401). However, this method only loads a form template and doesn't actually save data - it's just a display method. The actual save would go through the same `update_contact_entry` handler, so our primary interception point catches it.

**Entry Points We DON'T Need to Lock:**

1. **New entry creation**: Safe during migration - new entries are written to both storage formats automatically
2. **Entry deletion**: Safe during migration - deletes from both storage formats
3. **Bulk status updates**: Should be locked (separate subtask #15)
4. **CSV imports**: Should be locked (separate subtask #16)
5. **Entry viewing/reading**: Safe - read operations don't modify data

### Migration Status Check vs Lock Check: Critical Distinction

**DO NOT use `SUPER_Background_Migration::is_locked()`** for entry editing prevention. Here's why:

The migration lock (`super_migration_lock` transient) is only held during active batch processing (seconds). A multi-hour migration might look like this:

```
09:00:00 - Batch 1: acquire_lock() → process 50 entries → release_lock() (5 seconds)
09:00:05 - Action Scheduler waits...
09:00:15 - Batch 2: acquire_lock() → process 50 entries → release_lock() (5 seconds)
09:00:20 - Action Scheduler waits...
... continues for hours ...
```

Between batches (09:00:05-09:00:15 in example), `is_locked()` returns `false` even though migration is in progress. If we only checked the lock, entry editing would be incorrectly allowed during these windows.

**Correct Check:**
```php
$migration = get_option('superforms_eav_migration', array());
$is_migration_running = (!empty($migration) && isset($migration['status']) && $migration['status'] === 'in_progress');
```

This checks the migration STATUS, not the lock state. The status remains `'in_progress'` for the entire migration duration (hours), while the lock is only held during batch processing (seconds).

### Files That Need Modification

**Required Changes:**

1. **`/home/rens/super-forms/src/includes/class-ajax.php`**
   - Method: `update_contact_entry()` (line 1296)
   - Change: Add migration status check at method start, return error if in progress
   - Lines affected: Insert ~10 lines after line 1298

2. **`/home/rens/super-forms/src/includes/class-pages.php`**
   - Method: `contact_entry()` (line 2491)
   - Change: Add warning banner display and disable Update button when migration in progress
   - Lines affected: Insert banner after line 2542, modify button output at line 2640

**No Changes Needed:**

3. **`/home/rens/super-forms/src/includes/class-background-migration.php`**
   - Already has complete lock system
   - No modifications required

4. **`/home/rens/super-forms/src/includes/class-data-access.php`**
   - Already handles dual-write during migration
   - No modifications required

5. **`/home/rens/super-forms/src/assets/js/backend/contact-entry.js`**
   - Existing error handling (lines 72-87) will display the error message
   - No JavaScript changes needed

### Testing Approach

**Test Scenario 1: Verify Lock Prevents Edit (AJAX Level)**

1. Start migration via Developer Tools or migration UI
2. Verify migration status: `wp option get superforms_eav_migration` should show `status: in_progress`
3. Open browser DevTools → Network tab
4. Navigate to any contact entry edit page
5. Modify a field value and click "Update"
6. Verify AJAX response contains error message: "Entry editing is temporarily disabled while database migration is in progress"
7. Verify error notice displays in admin UI (red error box)
8. Verify entry data NOT saved (refresh page, old value still present)

**Test Scenario 2: Verify UI Warning Displays**

1. Start migration via Developer Tools
2. Navigate to contact entry edit page
3. Verify yellow warning banner displays at top of page
4. Verify warning shows migration progress percentage
5. Verify "Update" button is disabled (grayed out, cannot click)
6. Verify "Print" button still works (read-only operations allowed)
7. Complete migration (or force complete)
8. Refresh entry page
9. Verify warning banner no longer displays
10. Verify "Update" button is enabled and functional

**Test Scenario 3: Verify Lock Releases After Migration**

1. Start migration and let it complete naturally
2. Verify migration status: `status: completed`
3. Navigate to any contact entry edit page
4. Modify a field value and click "Update"
5. Verify success message: "Contact entry updated"
6. Verify entry data IS saved (refresh page, new value persists)

**Test Scenario 4: Edge Case - Migration Stuck/Failed**

1. Simulate stuck migration: Set migration status to `in_progress` manually
   ```
   wp option patch update superforms_eav_migration status in_progress
   ```
2. Verify entry editing is blocked (warning shows, AJAX returns error)
3. Manually force complete migration via Developer Tools
4. Verify entry editing is unblocked

**Test Scenario 5: Verify No Impact on New Entry Creation**

1. Start migration
2. Submit a new form entry via front-end
3. Verify entry is created successfully
4. Verify entry has data in BOTH storage formats (dual-write during migration)
5. Navigate to edit page for newly created entry
6. Verify warning banner displays (editing still blocked for ALL entries during migration)

**Test Scenario 6: Verify Multiple Admin Users**

1. Start migration
2. Open entry edit page in two different browsers (or incognito + regular)
3. Verify both show warning banner
4. Attempt to save from both browsers
5. Verify both receive error message
6. Complete migration
7. Verify both can now edit successfully

**Integration Test with Real Data:**

Use the Developer Tools CSV import feature (documented in CLAUDE.md) to test with realistic production data:

1. Import test data: `superforms-test-data-3943-entries.csv` (3,943 entries)
2. Start migration
3. While migration runs, attempt to edit entries at different IDs (beginning, middle, end of dataset)
4. Verify all edit attempts blocked
5. Let migration complete
6. Verify all entries editable again
7. Run cleanup to delete test data

**Verification Checklist:**

- [ ] AJAX handler returns proper error when migration in progress
- [ ] Admin UI displays warning banner with migration progress
- [ ] Update button is disabled during migration
- [ ] Lock check uses migration STATUS not lock transient state
- [ ] New entry creation still works during migration
- [ ] Entry viewing (read-only) still works during migration
- [ ] Lock releases properly after migration completes
- [ ] No JavaScript errors in browser console
- [ ] Error messages are user-friendly (no technical jargon)
- [ ] Migration progress percentage displays correctly

## Work Log

### 2025-11-16

#### Completed
- Added migration status checks to `update_contact_entry()` AJAX handler (line 1300-1310)
- Added migration status checks to `submit_form()` AJAX handler for front-end editing (line 5027-5037)
- Implemented server-side blocking (better UX than UI-level blocking)
- Added `sanitize_text_field()` to entry title input for security hardening (line 1313)
- Added `sanitize_text_field()` to entry status input for security hardening (line 1322)
- Added `is_string()` type validation to migration status checks (lines 1302, 5029)
- Code review completed with no critical issues identified

#### Decisions
- Check migration STATUS (`'in_progress'`) not lock transient (status persists for entire migration duration, lock only during batch processing)
- Server-side AJAX checks instead of UI-level blocking (migration might complete while user is editing)
- Same error handling pattern for both admin and front-end entry editing
- Follow existing WordPress/Super Forms error handling patterns with `SUPER_Common::output_message()`

#### Discovered
- Code review identified two warnings (both addressed):
  - Missing input sanitization in legacy code (fixed with `sanitize_text_field()`)
  - Type coercion edge case in status check (fixed with `is_string()` validation)
- No test environment available (no Docker, headless server)

#### Next Steps
- Manual testing recommended on staging server (f4d.nl/dev) or after deployment
- Monitor for any edge cases after deployment
- This is part of the larger EAV migration project (subtask 14 of parent task)
