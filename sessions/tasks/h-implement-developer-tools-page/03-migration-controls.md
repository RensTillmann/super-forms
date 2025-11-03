---
name: 03-migration-controls
parent: h-implement-developer-tools-page
status: pending
created: 2025-11-02
---

# Phase 3: Migration Controls

## Goal

Provide advanced migration control beyond what the Migration admin page offers. This includes starting/pausing migration, configurable batch sizes, and advanced operations like force complete, reset, and rollback.

## Success Criteria

- [ ] Display current migration status and progress in real-time
- [ ] Start/Pause/Stop migration buttons work correctly
- [ ] Progress bar updates during migration
- [ ] Reset to "Not Started" function works
- [ ] Force Complete (skip actual migration) function works
- [ ] Rollback to Serialized function works
- [ ] Force Switch to EAV function works
- [ ] Batch size configuration (5/10/25/50/100) persists
- [ ] Delay configuration (0/100/250/500/1000ms) persists
- [ ] Link to Migration Admin Page opens correctly
- [ ] Real-time status polling works without page reload

## Implementation Requirements

### Files to Modify

1. **`/src/includes/class-ajax.php`** - Add/expose AJAX handlers:
   - `migration_reset` - Reset migration to "not_started"
   - `migration_force_complete` - Mark as complete without migration
   - `migration_force_eav` - Force switch to EAV storage
   - (Others already exist: `migration_start`, `migration_process_batch`, `migration_get_status`, `migration_rollback`)

2. **`/src/includes/class-migration-manager.php`** - Add helper methods if needed:
   - `force_complete()` - Skip migration, mark complete
   - `force_switch_eav()` - Manually switch storage to EAV

3. **`/src/includes/admin/views/page-developer-tools.php`** - Add Migration Controls UI section

## Technical Specifications

### Migration State Machine

The migration system uses these states (from SUPER_Migration_Manager):

```php
array(
    'status'               => 'in_progress',        // 'not_started', 'in_progress', 'completed'
    'using_storage'        => 'serialized',         // 'serialized' or 'eav'
    'total_entries'        => 1234,
    'migrated_entries'     => 450,
    'failed_entries'       => array(),              // entry_id => error_message
    'started_at'           => '2025-11-01 10:30:00',
    'completed_at'         => '',
    'last_processed_id'    => 450,                  // Resume point
    'verification_passed'  => false,
    'rollback_available'   => true,
)
```

### Existing Migration Methods (Already Implemented)

From `/src/includes/class-migration-manager.php`:

**1. `start_migration()`** (lines 32-71)
- Counts total entries
- Initializes state to 'in_progress'
- Sets using_storage to 'serialized'
- Returns state array or WP_Error

**2. `process_batch($batch_size)`** (lines 80-151)
- Default batch size: 10
- Fetches next batch using last_processed_id
- Calls migrate_entry() for each
- Updates progress tracking
- Auto-calls complete_migration() when done

**3. `get_migration_status()`** (static)
- Returns current migration state from options table
- Returns empty array if not started

**4. `complete_migration()`** (lines 240-270)
- Sets status to 'completed'
- **CRITICAL**: Sets using_storage to 'eav' (THE SWITCH!)
- Sets completed timestamp

**5. `rollback_migration()`** (lines 278-306)
- Can only rollback completed migrations
- Switches using_storage back to 'serialized'
- Does NOT delete EAV data
- Increments rollback counter

**6. `reset_migration()`** (lines 324-339)
- Resets state to 'not_started'
- Clears all progress tracking
- Used for testing/restarting

### New Migration Methods to Add

**Force Complete (Skip Migration)**
```php
public static function force_complete() {
    $migration = get_option('superforms_eav_migration', array());

    if (empty($migration)) {
        return new WP_Error('not_started', __('Migration not started', 'super-forms'));
    }

    // Count total entries
    global $wpdb;
    $total = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'super_contact_entry'
        AND post_status IN ('publish', 'super_read', 'super_unread')
    ");

    $migration['status'] = 'completed';
    $migration['using_storage'] = 'eav';
    $migration['total_entries'] = $total;
    $migration['migrated_entries'] = $total;  // Pretend all migrated
    $migration['completed_at'] = current_time('mysql');

    update_option('superforms_eav_migration', $migration);

    return $migration;
}
```

**Force Switch to EAV**
```php
public static function force_switch_eav() {
    $migration = get_option('superforms_eav_migration', array());

    if (empty($migration)) {
        return new WP_Error('not_started', __('Migration not started', 'super-forms'));
    }

    $migration['using_storage'] = 'eav';
    update_option('superforms_eav_migration', $migration);

    return $migration;
}
```

### AJAX Handlers to Add

**Migration Reset**
```php
public static function migration_reset() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission', 'super-forms')));
    }

    // Reset migration
    $result = SUPER_Migration_Manager::reset_migration();

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}
```

**Force Complete**
```php
public static function migration_force_complete() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission', 'super-forms')));
    }

    $result = SUPER_Migration_Manager::force_complete();

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}
```

**Force EAV Switch**
```php
public static function migration_force_eav() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission', 'super-forms')));
    }

    $result = SUPER_Migration_Manager::force_switch_eav();

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}
```

### UI Section HTML

```php
<!-- Migration Controls Section -->
<div class="super-devtools-section">
    <h2><?php echo esc_html__('2. Migration Controls', 'super-forms'); ?></h2>

    <?php
    $migration_status = SUPER_Migration_Manager::get_migration_status();
    $status = !empty($migration_status) ? $migration_status['status'] : 'not_started';
    $using_storage = !empty($migration_status) ? $migration_status['using_storage'] : 'serialized';
    $total = !empty($migration_status) ? $migration_status['total_entries'] : 0;
    $migrated = !empty($migration_status) ? $migration_status['migrated_entries'] : 0;
    $progress = $total > 0 ? round(($migrated / $total) * 100, 2) : 0;
    ?>

    <!-- Current Status -->
    <table class="widefat" style="max-width: 600px;">
        <tbody>
            <tr>
                <th style="width: 200px;"><?php echo esc_html__('Current Status:', 'super-forms'); ?></th>
                <td>
                    <span class="migration-status-badge" data-status="<?php echo esc_attr($status); ?>">
                        <?php
                        if ($status === 'not_started') {
                            echo '<span class="sfui-badge sfui-grey">● Not Started</span>';
                        } elseif ($status === 'in_progress') {
                            echo '<span class="sfui-badge sfui-blue">● In Progress</span>';
                        } elseif ($status === 'completed') {
                            echo '<span class="sfui-badge sfui-green">● Completed</span>';
                        }
                        ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__('Using Storage:', 'super-forms'); ?></th>
                <td class="migration-using-storage">
                    <?php echo $using_storage === 'eav' ? 'EAV Tables' : 'Serialized'; ?>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__('Progress:', 'super-forms'); ?></th>
                <td>
                    <div class="super-migration-progress-bar">
                        <div class="super-migration-progress-fill migration-progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    <span class="migration-progress-text"><?php echo number_format($migrated); ?> / <?php echo number_format($total); ?> (<?php echo $progress; ?>%)</span>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Actions -->
    <h3 style="margin-top: 20px;"><?php echo esc_html__('Actions:', 'super-forms'); ?></h3>
    <p>
        <button id="migration-start-btn" class="button button-primary">
            <?php echo esc_html__('▶️ Start Migration', 'super-forms'); ?>
        </button>
        <button id="migration-pause-btn" class="button button-secondary" style="display: none;">
            <?php echo esc_html__('⏸️ Pause', 'super-forms'); ?>
        </button>
    </p>

    <!-- Advanced Controls -->
    <h3><?php echo esc_html__('Advanced:', 'super-forms'); ?></h3>
    <p>
        <button id="migration-reset-btn" class="button button-secondary">
            <?php echo esc_html__('🔄 Reset to Not Started', 'super-forms'); ?>
        </button>
        <button id="migration-force-complete-btn" class="button button-secondary">
            <?php echo esc_html__('⚡ Force Complete (skip migration)', 'super-forms'); ?>
        </button>
        <button id="migration-rollback-btn" class="button button-secondary">
            <?php echo esc_html__('🔙 Rollback to Serialized', 'super-forms'); ?>
        </button>
        <button id="migration-force-eav-btn" class="button button-secondary">
            <?php echo esc_html__('⏩ Force Switch to EAV', 'super-forms'); ?>
        </button>
    </p>

    <!-- Settings -->
    <h3><?php echo esc_html__('Settings:', 'super-forms'); ?></h3>
    <p>
        <label>
            <?php echo esc_html__('Batch Size:', 'super-forms'); ?>
            <select id="migration-batch-size">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </label>
        &nbsp;&nbsp;
        <label>
            <?php echo esc_html__('Delay:', 'super-forms'); ?>
            <select id="migration-delay">
                <option value="0">0ms</option>
                <option value="100">100ms</option>
                <option value="250">250ms</option>
                <option value="500" selected>500ms</option>
                <option value="1000">1000ms</option>
            </select>
        </label>
    </p>

    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=super_migration')); ?>" class="button button-secondary">
            <?php echo esc_html__('Open Migration Admin Page →', 'super-forms'); ?>
        </a>
    </p>

    <!-- Log Area -->
    <div class="migration-log"></div>
</div>
```

### JavaScript Implementation

```javascript
var migrationActive = false;
var migrationPaused = false;

// Start migration
$('#migration-start-btn').on('click', function() {
    if (confirm('Start migration process?')) {
        startMigration();
    }
});

// Pause migration
$('#migration-pause-btn').on('click', function() {
    migrationPaused = true;
    $('#migration-pause-btn').hide();
    $('#migration-start-btn').text('▶️ Resume Migration').show();
    appendMigrationLog('Migration paused by user');
});

// Reset migration
$('#migration-reset-btn').on('click', function() {
    if (confirm('Reset migration to "Not Started"? This will clear all progress.')) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_reset',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    appendMigrationLog('✓ Migration reset to "Not Started"');
                    updateMigrationStatus();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});

// Force complete
$('#migration-force-complete-btn').on('click', function() {
    if (confirm('Force mark migration as complete WITHOUT actually migrating data?\n\nWARNING: This is for testing only!')) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_force_complete',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    appendMigrationLog('✓ Migration marked as complete (forced)');
                    updateMigrationStatus();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});

// Rollback
$('#migration-rollback-btn').on('click', function() {
    if (confirm('Rollback to serialized storage? Migration can be re-run later.')) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_rollback',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    appendMigrationLog('✓ Rolled back to serialized storage');
                    updateMigrationStatus();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});

// Force EAV switch
$('#migration-force-eav-btn').on('click', function() {
    if (confirm('Force switch to EAV storage WITHOUT migrating?\n\nWARNING: Only use if EAV data already exists!')) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_migration_force_eav',
                security: devtoolsNonce
            },
            success: function(response) {
                if (response.success) {
                    appendMigrationLog('✓ Forced switch to EAV storage');
                    updateMigrationStatus();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});

// Start migration process
function startMigration() {
    migrationActive = true;
    migrationPaused = false;
    $('#migration-start-btn').hide();
    $('#migration-pause-btn').show();

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_migration_start',
            security: devtoolsNonce
        },
        success: function(response) {
            if (response.success) {
                appendMigrationLog('✓ Migration started: ' + response.data.total_entries + ' entries to migrate');
                processMigrationBatch();
            } else {
                alert('Error: ' + response.data.message);
                migrationActive = false;
                $('#migration-start-btn').show();
                $('#migration-pause-btn').hide();
            }
        }
    });
}

// Process migration batch
function processMigrationBatch() {
    if (!migrationActive || migrationPaused) {
        return;
    }

    var batchSize = parseInt($('#migration-batch-size').val());
    var delay = parseInt($('#migration-delay').val());

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_migration_process_batch',
            security: devtoolsNonce,
            batch_size: batchSize
        },
        success: function(response) {
            if (response.success) {
                var data = response.data;

                // Update progress
                updateMigrationProgress(data.migrated_entries, data.total_entries);

                // Log batch result
                appendMigrationLog('Processed batch: ' + data.batch_processed + ' entries (' + data.migrated_entries + ' / ' + data.total_entries + ')');

                // Check if complete
                if (data.status === 'completed') {
                    appendMigrationLog('✓ Migration complete!');
                    migrationActive = false;
                    $('#migration-start-btn').text('▶️ Start Migration').show();
                    $('#migration-pause-btn').hide();
                    updateMigrationStatus();
                } else {
                    // Continue with next batch
                    setTimeout(processMigrationBatch, delay);
                }
            } else {
                appendMigrationLog('✗ Error: ' + response.data.message);
                migrationActive = false;
                $('#migration-start-btn').show();
                $('#migration-pause-btn').hide();
            }
        },
        error: function() {
            appendMigrationLog('✗ AJAX error occurred');
            migrationActive = false;
            $('#migration-start-btn').show();
            $('#migration-pause-btn').hide();
        }
    });
}

// Update migration progress UI
function updateMigrationProgress(current, total) {
    var percent = (current / total) * 100;
    $('.migration-progress-fill').css('width', percent + '%');
    $('.migration-progress-text').text(current.toLocaleString() + ' / ' + total.toLocaleString() + ' (' + Math.round(percent) + '%)');
}

// Update migration status display
function updateMigrationStatus() {
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_migration_get_status',
            security: devtoolsNonce
        },
        success: function(response) {
            if (response.success && response.data) {
                var status = response.data;

                // Update status badge
                var statusText = status.status === 'not_started' ? 'Not Started' :
                                status.status === 'in_progress' ? 'In Progress' : 'Completed';
                var badgeClass = status.status === 'not_started' ? 'sfui-grey' :
                                status.status === 'in_progress' ? 'sfui-blue' : 'sfui-green';
                $('.migration-status-badge').html('<span class="sfui-badge ' + badgeClass + '">● ' + statusText + '</span>');

                // Update storage
                $('.migration-using-storage').text(status.using_storage === 'eav' ? 'EAV Tables' : 'Serialized');

                // Update progress
                updateMigrationProgress(status.migrated_entries, status.total_entries);
            }
        }
    });
}

// Append to migration log
function appendMigrationLog(message) {
    var timestamp = new Date().toLocaleTimeString();
    $('.migration-log').prepend('<div>[' + timestamp + '] ' + message + '</div>');
}

// Poll status every 5 seconds when not actively migrating
setInterval(function() {
    if (!migrationActive) {
        updateMigrationStatus();
    }
}, 5000);
```

## Testing Requirements

1. **Start Migration Test**
   - Click Start Migration
   - ✓ Status changes to "In Progress"
   - ✓ Progress bar updates
   - ✓ Batch processing continues until complete

2. **Pause/Resume Test**
   - Start migration
   - Click Pause during processing
   - ✓ Migration stops
   - Click Resume
   - ✓ Migration continues from last_processed_id

3. **Reset Test**
   - Start migration, let some entries migrate
   - Click Reset
   - ✓ Status returns to "Not Started"
   - ✓ Progress resets to 0

4. **Force Complete Test**
   - Click Force Complete
   - ✓ Status changes to "Completed"
   - ✓ using_storage changes to "eav"
   - ✓ No actual migration occurred

5. **Rollback Test**
   - Complete migration (real or forced)
   - Click Rollback
   - ✓ using_storage changes to "serialized"
   - ✓ Status remains "Completed"
   - ✓ Entries still read from serialized

6. **Force EAV Test**
   - Have EAV data present
   - Click Force Switch to EAV
   - ✓ using_storage changes to "eav"
   - ✓ Entries now read from EAV tables

7. **Batch Size Test**
   - Change batch size to 5
   - Start migration
   - ✓ Each batch processes 5 entries
   - Change to 100
   - ✓ Each batch processes 100 entries

8. **Delay Test**
   - Set delay to 0ms
   - ✓ Batches process immediately
   - Set delay to 1000ms
   - ✓ 1 second pause between batches

9. **Link Test**
   - Click "Open Migration Admin Page"
   - ✓ Opens correct page in same/new tab

10. **Status Polling Test**
    - Leave page open
    - Make migration changes in another tab
    - ✓ Status updates within 5 seconds

## Estimated Time

**1.5-2 hours** for implementation and testing

## Dependencies

- Phase 1 must be complete (page foundation)
- SUPER_Migration_Manager class (already exists, needs 2 new methods)
- Existing migration AJAX handlers (already implemented)

## Security Notes

- All destructive operations require confirmation dialogs
- Only accessible when DEBUG_SF = true
- Requires manage_options capability
- Force operations log warnings to error_log
- Advanced controls clearly labeled as dangerous

## Notes

- This phase reuses existing Migration Manager logic where possible
- Real-time status polling prevents page reload requirements
- Batch size and delay configurable for different server capabilities
- Advanced controls (force complete, force EAV) are testing tools only
- Migration state persists in wp_options table
- Log area shows timestamped activity for debugging
