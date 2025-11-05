---
name: 11-migration-progress-page
status: pending
priority: medium
created: 2025-10-31
completed: 2025-11-01
reopened: 2025-11-05
---

# Migration Progress Page - Merge into Developer Tools

## Problem/Goal

**REOPENED 2025-11-05:** Standalone Migration page exists but needs merging into Developer Tools. With automatic background migration, separate menu item is unnecessary.

**Work Required:**
1. Move migration monitoring section from `/src/includes/admin/views/page-migration.php` into Developer Tools page
2. Remove standalone "Migration" menu item from `class-menu.php`
3. Keep monitoring UI (status, progress bar, entry counts)
4. Allow manual trigger for testing/debugging
5. **NEW:** Add failed entries section with:
   - List of failed entry IDs
   - Validation failure reasons
   - Diff viewer showing serialized vs EAV data mismatches
   - Link to view/edit affected entry
   - Retry button for individual failed entries

## Success Criteria

### Migration Monitoring (Merge into Developer Tools)
- [ ] Migration section moved from standalone page into Developer Tools
- [ ] Standalone "Migration" menu item removed
- [ ] Real-time progress bar with AJAX updates
- [ ] Migration status display (status badge, storage mode, entry counts)
- [ ] Manual trigger button for testing/debugging (Developer Tools only)
- [ ] Activity log with timestamps

### Failed Entries Section (NEW)
- [ ] Failed entries table showing entry IDs and failure counts
- [ ] Validation failure reasons displayed for each entry
- [ ] Diff viewer showing field-by-field comparison:
  - Serialized data (expected)
  - EAV data (actual)
  - Highlighted mismatches
- [ ] Link to view/edit affected entry in WordPress admin
- [ ] "Retry Migration" button for individual failed entries
- [ ] "Retry All Failed" bulk action button

### Technical Implementation
- [ ] Uses `failed_entries` array from migration state option
- [ ] Calls `SUPER_Data_Access::validate_entry_integrity($entry_id)` for diff data
- [ ] Retry triggers `SUPER_Migration_Manager::migrate_entry($entry_id)` directly
- [ ] Integration with Migration Manager verified
- [ ] Tests pass - **NOTE: Requires Subtask 01 (Test Suite Foundation)**

## Implementation Details

### Failed Entries UI Mockup

```html
<!-- Failed Entries Section -->
<div class="failed-entries-section">
  <h3>Failed Entries (5 entries failed validation)</h3>

  <table class="wp-list-table widefat">
    <thead>
      <tr>
        <th>Entry ID</th>
        <th>Failed At</th>
        <th>Reason</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>#12345</td>
        <td>2025-11-05 10:23:15</td>
        <td>Field count mismatch: 15 serialized vs 14 EAV</td>
        <td>
          <button class="button view-diff" data-entry-id="12345">View Diff</button>
          <button class="button retry-entry" data-entry-id="12345">Retry</button>
          <a href="post.php?post=12345&action=edit" class="button">Edit Entry</a>
        </td>
      </tr>
    </tbody>
  </table>

  <button class="button button-primary retry-all-failed">Retry All Failed Entries</button>
</div>

<!-- Diff Viewer Modal (shown when "View Diff" clicked) -->
<div class="diff-viewer-modal">
  <h3>Entry #12345 - Data Comparison</h3>

  <table class="diff-table">
    <thead>
      <tr>
        <th>Field Name</th>
        <th>Serialized (Expected)</th>
        <th>EAV (Actual)</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr class="match">
        <td>email</td>
        <td>john@example.com</td>
        <td>john@example.com</td>
        <td><span class="dashicons dashicons-yes-alt"></span> Match</td>
      </tr>
      <tr class="mismatch">
        <td>phone</td>
        <td>+1-555-1234</td>
        <td>+15551234</td>
        <td><span class="dashicons dashicons-warning"></span> Mismatch</td>
      </tr>
      <tr class="missing">
        <td>address</td>
        <td>123 Main St</td>
        <td><em>Missing in EAV</em></td>
        <td><span class="dashicons dashicons-dismiss"></span> Missing</td>
      </tr>
    </tbody>
  </table>
</div>
```

### Data Source

Failed entries tracked in migration state:
```php
$migration = get_option('superforms_eav_migration');
$failed_entries = $migration['failed_entries']; // array(entry_id => error_message)

// Get detailed diff for specific entry
$validation = SUPER_Data_Access::validate_entry_integrity($entry_id);
// Returns: {valid: bool, error: string, mismatches: array}
```

### Retry Implementation

```php
// AJAX handler for retry
public static function retry_failed_entry() {
    $entry_id = intval($_POST['entry_id']);

    // Re-attempt migration
    $result = SUPER_Migration_Manager::migrate_entry($entry_id);

    if (!is_wp_error($result)) {
        // Remove from failed list
        $migration = get_option('superforms_eav_migration');
        unset($migration['failed_entries'][$entry_id]);
        update_option('superforms_eav_migration', $migration);

        wp_send_json_success(['message' => 'Entry migrated successfully']);
    } else {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
}
```

## Estimated Effort

**5-6 days** (updated from 4-5 days to include failed entries UI)

## Dependencies

- Previous subtasks complete

## Related Research

- Main README: Phase 4 details
- Research task: h-research-eav-migration-complete.md

## Work Log

- [2025-11-01] **Subtask Completed**: Migration admin page already fully implemented
  - Location: `/src/includes/admin/views/page-migration.php` (814 lines)
  - Menu registration: `class-menu.php` line 62-69 (Super Forms → Migration)
  - Page callback: `SUPER_Pages::migration()` in `class-pages.php` line 2476-2478

  **UI Components:**
  - Migration status card with:
    - Current status badge (Not Started/In Progress/Completed)
    - Storage method display (Serialized/EAV)
    - Total entries count
    - Migrated entries count
    - Failed entries count
    - Progress bar with percentage
  - Migration controls:
    - "Start Migration" button (with backup confirmation)
    - "Migrating..." status during process
    - "Rollback to Serialized Storage" button (post-migration)
  - Migration activity log with timestamps
  - Data integrity validation section (from Subtask 14)

  **JavaScript Functionality:**
  - `updateStatus()` - Polls migration status via AJAX
  - `processBatch()` - Processes entries in batches with 500ms delay
  - `addLog()` - Adds timestamped entries to activity log
  - Start button handler (line 579-609):
    - Confirms with user before starting
    - Calls `super_migration_start` AJAX action
    - Begins batch processing loop
  - Rollback button handler (line 611-641):
    - Confirms with user before rollback
    - Calls `super_migration_rollback` AJAX action
    - Reloads page on success
  - Real-time progress updates:
    - Updates status badge
    - Updates entry counts
    - Updates progress bar
    - Auto-refreshes after completion

  **Error Handling:**
  - AJAX error detection and logging
  - User-friendly error messages
  - Failed entry count tracking
  - Resume capability on errors

  **Integration:**
  - Connected to SUPER_Migration_Manager via AJAX endpoints
  - Uses nonce for security: `wp_create_nonce('super-form-builder')`
  - AJAX actions: super_migration_start, super_migration_process_batch, super_migration_get_status, super_migration_rollback

  **Styling:**
  - Embedded CSS (lines 196-478)
  - WordPress admin theme compatible
  - Color-coded status badges
  - Responsive progress bars
  - Activity log with syntax highlighting

  - PHP syntax validated successfully
  - Ready for end-to-end migration testing
