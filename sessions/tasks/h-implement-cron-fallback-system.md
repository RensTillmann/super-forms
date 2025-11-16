---
name: h-implement-cron-fallback-system
branch: feature/h-implement-cron-fallback-system
status: pending
created: 2025-11-16
---

# Implement WP-Cron Fallback System

## Problem/Goal

Background job processing in Super Forms depends on WordPress's WP-Cron system, which can fail in several scenarios:

1. **Disabled WP-Cron** - Users disable `DISABLE_WP_CRON` without setting up system cron
2. **Low-Traffic Sites** - WP-Cron only triggers on page loads; low traffic = delayed/missed jobs
3. **Server Issues** - Plugin conflicts, security restrictions, or server configuration problems

When WP-Cron fails, critical background jobs stall:
- EAV database migration (can affect 3K-26K+ entries)
- 30-day retention cleanup
- Email reminders from add-on
- Other scheduled maintenance tasks

**Goal:** Implement a robust fallback system that:
- Auto-detects WP-Cron failures
- Automatically enables Action Scheduler's async processing
- Provides simple, non-scary admin UI for manual intervention
- Shows minimal information to avoid overwhelming users
- Processes migrations/tasks reliably even when WP-Cron is broken

## Success Criteria

**Phase 1: Auto-Detection & Fallback**
- [ ] Auto-enable Action Scheduler async processing when `DISABLE_WP_CRON` is detected
- [ ] Health check runs on admin_init to detect stalled background jobs (regardless of DISABLE_WP_CRON setting)
- [ ] System tracks last successful queue run timestamp to determine if cron is broken

**Phase 2: Simple Admin Notice (Non-Scary UX)**
- [ ] Admin notice appears on ALL admin pages (not just Super Forms pages) when background jobs are stalled
- [ ] Notice text is minimal and non-technical: "Database Upgrade Required" with simple call-to-action
- [ ] Notice does NOT show entry counts, percentages, or technical details
- [ ] Single button: "Upgrade Now" or similar user-friendly label

**Phase 3: Smart Button Behavior**
- [ ] When clicked, button triggers background processing
- [ ] If async processing works: Notice dismisses automatically, migration continues in background
- [ ] If async fails: Shows inline progress bar on same page (no page reload)
- [ ] Progress bar updates via AJAX, shows completion when done

**Phase 4: Graceful Handling**
- [ ] System doesn't create duplicate notices if migration is already running
- [ ] Notice is dismissible but reappears if jobs remain stalled (with delay to avoid annoyance)
- [ ] Works for all background jobs (migration, cleanup, email reminders, etc.)

**Additional Success Measures:**
- [ ] No PHP errors or warnings in error logs
- [ ] Works on WordPress 6.4+ / PHP 7.4+ (our new minimum requirements)
- [ ] Tested with both DISABLE_WP_CRON scenarios (true/false)

## Context Manifest

### How Background Job Processing Currently Works

When Super Forms needs to perform background tasks (EAV migration, 30-day cleanup, email reminders), it relies on a two-tier system: Action Scheduler (primary) with WP-Cron fallback.

**The Current Flow (Happy Path):**

1. **Initialization** - When `SUPER_Background_Migration::init()` is called during plugin load (`class-background-migration.php` line 62), it registers Action Scheduler hooks:
   - `superforms_migrate_batch` - processes migration batches
   - `superforms_migration_health_check` - hourly health monitoring
   - WP-Cron fallback hooks (if Action Scheduler unavailable)

2. **Job Scheduling** - When migration needs to start (`schedule_batch()` at line 482):
   ```php
   // Try Action Scheduler first
   if (function_exists('as_enqueue_async_action')) {
       as_enqueue_async_action(
           self::AS_BATCH_HOOK,
           array($batch_size),
           'superforms-migration'
       );
   }
   // Fallback to WP-Cron
   else {
       wp_schedule_single_event(time(), 'super_migration_cron_batch', array($batch_size));
   }
   ```

3. **Action Scheduler Execution** - Two processing modes:
   - **WP-Cron Mode** (default): `ActionScheduler_QueueRunner::init()` schedules `action_scheduler_run_queue` hook to run every minute via WP-Cron (`ActionScheduler_QueueRunner.php` lines 70-88)
   - **Async Mode**: `ActionScheduler_AsyncRequest_QueueRunner` triggers queue processing via admin-ajax.php after admin page loads, bypassing WP-Cron entirely (`ActionScheduler_AsyncRequest_QueueRunner.php` lines 47-57)

4. **Queue Processing** - `ActionScheduler_QueueRunner::run()` (line 148):
   - Claims batch of actions from queue
   - Processes each action by firing registered hook
   - Continues until batch complete or resource limits hit
   - Uses `ActionScheduler_Abstract_QueueRunner::process_action()` for execution

**Where It Breaks:**

**Scenario 1: DISABLE_WP_CRON = true**
- WP-Cron never runs because WordPress doesn't trigger it on page loads
- Action Scheduler's default WP-Cron mode fails (queue never processes)
- Jobs sit in database forever unless async mode enabled
- **Current detection**: Check `defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true`

**Scenario 2: Low-Traffic Sites**
- WP-Cron depends on site visitors to trigger
- Few visitors = long delays between cron runs
- Migration can take days/weeks instead of minutes/hours
- No current detection mechanism

**Scenario 3: Server Issues**
- Plugin conflicts prevent hooks from firing
- Security restrictions block loopback requests (wp-cron.php calls)
- Server configuration prevents background processing
- **Current detection**: None - requires monitoring queue staleness

**Health Check System (Existing):**

The `health_check_action()` method (line 978) runs hourly and detects stuck migrations:
```php
// Check if no activity in 1 hour
$time_since_last = time() - strtotime($last_processed);
if ($time_since_last > 3600) {
    self::log("Migration appears stuck, attempting resume");
    self::release_lock();
    self::schedule_if_needed('health_check');
}
```

However, this health check ALSO depends on WP-Cron/Action Scheduler working, creating a chicken-and-egg problem.

**Migration State Tracking:**

Migration status stored in `superforms_eav_migration` option (`class-migration-manager.php` line 270):
```php
array(
    'status' => 'in_progress|completed|not_started',
    'using_storage' => 'serialized|eav',
    'started_at' => '2025-11-16 10:30:00',
    'last_batch_processed_at' => '2025-11-16 10:35:00',
    'last_processed_id' => 1234,
    'migrated_entries' => 150, // Calculated live from DB
    'total_entries' => 500,
    'failed_entries' => array(),
    'background_enabled' => true
)
```

### For Cron Fallback Implementation: What Needs to Connect

**Phase 1: Auto-Detection & Async Enablement**

We need to detect WP-Cron failures and automatically enable Action Scheduler's async processing mode. This happens in two places:

1. **On Admin Init** (`admin_init` hook) - Check environment and enable async if needed:
   ```php
   // Pseudo-code for detection logic
   function detect_and_enable_async() {
       // Check 1: DISABLE_WP_CRON constant
       $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true;

       // Check 2: Queue staleness (jobs pending >1 hour)
       $last_run = get_option('superforms_last_queue_run');
       $queue_stale = (time() - strtotime($last_run)) > 3600;

       // Check 3: Pending migration work exists
       $needs_migration = SUPER_Background_Migration::needs_migration();

       if (($cron_disabled || $queue_stale) && $needs_migration) {
           // Enable async processing automatically
           update_option('superforms_async_processing_enabled', true);
       }
   }
   ```

2. **Queue Run Tracking** - Track when Action Scheduler successfully runs:
   ```php
   // Hook into Action Scheduler execution
   add_action('action_scheduler_after_process_queue', function() {
       update_option('superforms_last_queue_run', current_time('mysql'));
   });
   ```

**Action Scheduler Async Mode Activation:**

Action Scheduler's async mode is controlled by `ActionScheduler_QueueRunner` and `ActionScheduler_AsyncRequest_QueueRunner`. The async request runner dispatches via `admin-ajax.php` when:
- An admin page loads (`is_admin()` check in `maybe_dispatch_async_request()` line 123)
- A lock prevents duplicate simultaneous runs (60-second lock via `ActionScheduler_OptionLock`)
- Pending actions exist in queue

To force async mode to trigger more reliably, we can:
1. Manually call `ActionScheduler_QueueRunner::instance()->async_request->maybe_dispatch()` on admin pages
2. Alternatively, directly run the queue: `ActionScheduler_QueueRunner::instance()->run('Manual Trigger')`

**Phase 2: Simple Admin Notice**

Admin notices shown via `all_admin_notices` hook in `super-forms.php` line 410. Existing pattern:

```php
public function show_admin_notices() {
    // Check screen context
    $screen = get_current_screen();

    // Display notice
    echo '<div class="notice notice-warning">';
    echo '<p><strong>Database Upgrade Required</strong></p>';
    echo '<p>Background processing is stalled. Click below to upgrade now.</p>';
    echo '<button class="button button-primary" id="super-upgrade-now">Upgrade Now</button>';
    echo '</div>';
}
```

Notice should appear on ALL admin pages (not just Super Forms pages) when:
- Migration status is `in_progress`
- Last batch processed >1 hour ago
- Jobs still pending

**Dismissal Pattern:**

Existing notices use user meta to track dismissal (`sessions/tasks/h-implement-eav-contact-entry-storage/10-admin-notices-4-phases.md` lines 73-92):
```php
// Check if dismissed
$dismissed = get_user_meta(get_current_user_id(), 'super_cron_notice_dismissed', true);

// Dismiss action
update_user_meta(get_current_user_id(), 'super_cron_notice_dismissed', time());
```

Notice should reappear if jobs remain stalled after dismissal (check timestamp + delay).

**Phase 3: Smart Button Behavior**

Button click should trigger AJAX request to new endpoint:

```php
// In class-ajax.php, add to $ajax_events array (line 30):
'trigger_background_processing' => false,

// Handler method:
public static function trigger_background_processing() {
    check_ajax_referer('super-form-builder', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    // Try async processing first
    $async_worked = try_async_processing();

    if ($async_worked) {
        wp_send_json_success(array('mode' => 'async'));
    } else {
        // Fall back to synchronous processing with progress bar
        wp_send_json_success(array('mode' => 'sync'));
    }
}
```

**Async Processing Attempt:**

```php
function try_async_processing() {
    // Check if Action Scheduler available
    if (!function_exists('ActionScheduler') || !class_exists('ActionScheduler_QueueRunner')) {
        return false;
    }

    // Trigger async dispatch
    ActionScheduler_QueueRunner::instance()->async_request->maybe_dispatch();

    // Give it 2 seconds to process
    sleep(2);

    // Check if jobs were processed
    $status = SUPER_Migration_Manager::get_migration_status();
    $recent_activity = (time() - strtotime($status['last_batch_processed_at'])) < 10;

    return $recent_activity;
}
```

**Synchronous Processing with Progress:**

If async fails, process batches via AJAX with progress updates:

```php
// AJAX endpoint: migration_process_batch_sync
public static function migration_process_batch_sync() {
    check_ajax_referer('super-form-builder', 'security');

    $batch_size = 10; // Small batches for responsiveness
    $result = SUPER_Background_Migration::process_batch_action($batch_size);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    // Get updated status for progress bar
    $status = SUPER_Migration_Manager::get_migration_status();

    wp_send_json_success(array(
        'processed' => $result['processed'],
        'remaining' => $result['remaining'],
        'is_complete' => $result['is_complete'],
        'percentage' => round(($status['migrated_entries'] / $status['total_entries']) * 100)
    ));
}
```

**Phase 4: Graceful Handling**

**Duplicate Notice Prevention:**

```php
// Check migration state before showing notice
$migration = get_option('superforms_eav_migration', array());

// Don't show if:
// 1. No migration needed
if (!SUPER_Background_Migration::needs_migration()) return;

// 2. Migration running recently (last 5 minutes)
$last_run = strtotime($migration['last_batch_processed_at']);
if ((time() - $last_run) < 300) return;

// 3. User dismissed recently (last 1 hour)
$dismissed = get_user_meta(get_current_user_id(), 'super_cron_notice_dismissed', true);
if ($dismissed && (time() - $dismissed) < 3600) return;
```

**Background Job Detection:**

Works for all background jobs, not just migration:
- Email reminders use Action Scheduler hooks
- 30-day cleanup uses `super_cleanup_old_serialized_data` hook
- All rely on same queue infrastructure

### Technical Reference Details

#### Action Scheduler Integration Points

**Class: ActionScheduler_QueueRunner** (`src/includes/lib/action-scheduler/classes/ActionScheduler_QueueRunner.php`)
- `::instance()` - Get singleton instance
- `->init()` - Initialize WP-Cron scheduling (line 70)
- `->run($context)` - Process queue manually (line 148)
- `->maybe_dispatch_async_request()` - Check and trigger async (line 123)
- `->async_request` - `ActionScheduler_AsyncRequest_QueueRunner` instance

**Class: ActionScheduler_AsyncRequest_QueueRunner** (`src/includes/lib/action-scheduler/classes/ActionScheduler_AsyncRequest_QueueRunner.php`)
- `->maybe_dispatch()` - Dispatch async request if allowed (line 62)
- `->allow()` - Check if async processing should run (line 76)
- `->handle()` - Process queue via AJAX (line 47)

**Functions** (Action Scheduler API):
- `as_enqueue_async_action($hook, $args, $group)` - Schedule immediate action
- `as_next_scheduled_action($hook, $args, $group)` - Check for existing schedule
- `as_unschedule_all_actions($hook, $args, $group)` - Cancel scheduled actions
- `as_get_scheduled_actions($args, $return_format)` - Query scheduled actions

#### AJAX Infrastructure

**AJAX Registration Pattern** (`class-ajax.php` line 30):
```php
$ajax_events = array(
    'trigger_cron_fallback' => false, // Admin-only endpoint
);

foreach ($ajax_events as $ajax_event => $nopriv) {
    add_action('wp_ajax_super_' . $ajax_event, array(__CLASS__, $ajax_event));
    if ($nopriv) {
        add_action('wp_ajax_nopriv_super_' . $ajax_event, array(__CLASS__, $ajax_event));
    }
}
```

**AJAX Handler Pattern** (`class-ajax.php` line 6549):
```php
public static function trigger_cron_fallback() {
    // Security check
    check_ajax_referer('super-form-builder', 'security');

    // Permission check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    // Process logic
    $result = process_fallback();

    // Return response
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}
```

**Nonce Generation** (for JavaScript):
Localized in `super-forms.php` via `wp_localize_script()`. Need to add nonce to existing localization:
```php
wp_localize_script('super-admin', 'superforms_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('super-form-builder'),
));
```

#### Admin Notice System

**Hook Point** (`super-forms.php` line 410):
```php
add_action('all_admin_notices', array($this, 'show_admin_notices'));
```

**Notice HTML Structure** (WordPress standards):
```php
<div class="notice notice-warning is-dismissible">
    <p><strong>Heading Text</strong></p>
    <p>Description text</p>
    <p>
        <button class="button button-primary">Primary Action</button>
        <button class="button">Secondary Action</button>
    </p>
</div>
```

**Notice Classes:**
- `notice` - Base class (required)
- `notice-error` - Red (errors)
- `notice-warning` - Yellow (warnings)
- `notice-success` - Green (success)
- `notice-info` - Blue (informational)
- `is-dismissible` - Shows X button for dismissal

#### WP-Cron Detection

**Check DISABLE_WP_CRON Constant:**
```php
$cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true;
```

**Track Queue Run Timestamps:**
```php
// Option to track last successful queue run
update_option('superforms_last_queue_run', current_time('mysql'));

// Check staleness
$last_run = get_option('superforms_last_queue_run');
if ($last_run) {
    $hours_since = (time() - strtotime($last_run)) / 3600;
    $is_stale = $hours_since > 1; // Stale if >1 hour
}
```

**Migration Lock Status:**
```php
// Check if migration currently locked (running)
$is_locked = SUPER_Background_Migration::is_locked();

// Lock transient key
const LOCK_KEY = 'super_migration_lock';

// Check lock directly
$locked = get_transient('super_migration_lock');
```

#### Background Migration State

**Option Key:** `superforms_eav_migration`

**State Structure:**
```php
array(
    'status' => 'not_started|in_progress|completed',
    'using_storage' => 'serialized|eav',
    'started_at' => '2025-11-16 10:00:00',
    'last_batch_processed_at' => '2025-11-16 10:30:00',
    'last_processed_id' => 1500,
    'total_entries' => 5000,
    'migrated_entries' => 1500, // Calculated live from DB
    'failed_entries' => array(),
    'background_enabled' => true,
    'auto_triggered_by' => 'version_upgrade|health_check|manual',
)
```

**Get Status Method:**
`SUPER_Migration_Manager::get_migration_status()` - Returns full status with live DB counts

**Check If Migration Needed:**
`SUPER_Background_Migration::needs_migration()` - Returns bool, uses transient cache (60s TTL)

#### File Locations

**Implementation Files:**
- `/src/includes/class-background-migration.php` - Background migration orchestration (lines 1-1296)
- `/src/includes/class-migration-manager.php` - Entry-by-entry migration logic (lines 1-300+)
- `/src/includes/class-ajax.php` - AJAX endpoint handlers (lines 1-300+)
- `/src/super-forms.php` - Main plugin file, admin notices hook (line 410)

**New Code Locations:**
- Cron detection class: `/src/includes/class-cron-fallback.php` (new file)
- Admin notice method: Add to `super-forms.php` `show_admin_notices()` method (line 1507)
- AJAX handlers: Add to `class-ajax.php` `$ajax_events` array (line 30)
- JavaScript: `/src/assets/js/backend/cron-fallback.js` (new file)

**Configuration:**
No config file needed - use WordPress options:
- `superforms_async_processing_enabled` - Boolean option
- `superforms_last_queue_run` - Timestamp option
- `super_cron_notice_dismissed_{user_id}` - User meta option

## User Notes
<!-- Any specific notes or requirements from the developer -->

## Work Log
<!-- Updated as work progresses -->
- [YYYY-MM-DD] Started task, initial research
