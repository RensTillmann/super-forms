---
name: 02-implement-action-scheduler
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-11-20
completed: 2025-11-22
parent: h-implement-triggers-actions-extensibility
---

# Implement Action Scheduler Integration

## Problem/Goal
Replace unreliable WP-Cron implementation with Action Scheduler for robust background processing, scheduled actions, and proper queue management. Action Scheduler is already bundled (v3.9.3) but not utilized for triggers.

## Success Criteria
- [x] All scheduled trigger actions use Action Scheduler instead of WP-Cron
- [x] Existing scheduled actions migrated to Action Scheduler
- [ ] Queue monitoring and management UI integrated (deferred to Phase 4)
- [x] Failed action retry mechanism implemented
- [x] Performance optimized for high-volume forms
- [x] Zero data loss during migration
- [ ] Admin can view/manage scheduled actions (deferred to Phase 4)

## Implementation Steps

### Step 1: Action Scheduler Manager Class

**File:** `/src/includes/class-trigger-scheduler.php` (new file)

Create `SUPER_Trigger_Scheduler` class:

```php
class SUPER_Trigger_Scheduler {
    const GROUP = 'super_forms_triggers';
    const RETRY_LIMIT = 3;

    // Schedule an action
    public function schedule_action($timestamp, $hook, $args = array(), $group = self::GROUP) {
        return as_schedule_single_action($timestamp, $hook, $args, $group);
    }

    // Schedule recurring action
    public function schedule_recurring($timestamp, $interval, $hook, $args = array()) {
        return as_schedule_recurring_action($timestamp, $interval, $hook, $args, self::GROUP);
    }

    // Cancel scheduled actions
    public function cancel_action($hook, $args = array()) {
        as_unschedule_all_actions($hook, $args, self::GROUP);
    }

    // Get next scheduled occurrence
    public function get_next_scheduled($hook, $args = array()) {
        return as_next_scheduled_action($hook, $args, self::GROUP);
    }

    // Check if action is scheduled
    public function is_scheduled($hook, $args = array()) {
        return as_has_scheduled_action($hook, $args, self::GROUP);
    }

    // Get pending actions count
    public function get_pending_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'actionscheduler_actions';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE group_id = %s AND status = 'pending'",
            self::GROUP
        ));
    }
}
```

### Step 2: Migrate Existing WP-Cron Jobs

**File:** `/src/includes/class-trigger-migration.php` (update existing)

Add migration method:

```php
public function migrate_scheduled_actions() {
    global $wpdb;

    // Find existing scheduled trigger actions
    $query = "SELECT post_id, meta_value AS timestamp, post_content
              FROM $wpdb->postmeta AS r
              INNER JOIN $wpdb->posts ON ID = post_id
              WHERE meta_key = '_super_scheduled_trigger_action_timestamp'";

    $scheduled_actions = $wpdb->get_results($query);

    foreach ($scheduled_actions as $action) {
        // Parse existing data
        $trigger_data = maybe_unserialize($action->post_content);

        // Schedule with Action Scheduler
        as_schedule_single_action(
            $action->timestamp,
            'super_trigger_execute_action',
            array(
                'action_id' => $action->post_id,
                'trigger_data' => $trigger_data
            ),
            'super_forms_triggers'
        );

        // Mark old entry as migrated using Data Access Layer
        // Note: For contact entry data, always use SUPER_Data_Access
        // This example assumes the action has an associated entry_id
        if ($action->entry_id) {
            SUPER_Data_Access::update_entry_data($action->entry_id, array(
                '_migrated_to_action_scheduler' => true
            ));
        }
    }

    // Remove old WP-Cron hook
    wp_clear_scheduled_hook('super_execute_scheduled_trigger_actions');
}
```

### Step 3: Update Trigger Execution

**File:** `/src/includes/class-triggers.php` (update existing)

Replace WP-Cron methods with Action Scheduler:

```php
// Old WP-Cron method (remove):
// wp_schedule_single_event($timestamp, 'super_execute_scheduled_trigger_actions');

// New Action Scheduler method:
public static function schedule_trigger_action($trigger_id, $action_data, $delay = 0) {
    $scheduler = new SUPER_Trigger_Scheduler();

    $timestamp = time() + $delay;
    $args = array(
        'trigger_id' => $trigger_id,
        'action_data' => $action_data,
        'attempt' => 1
    );

    return $scheduler->schedule_action(
        $timestamp,
        'super_trigger_execute_action',
        $args
    );
}

// Hook for execution
add_action('super_trigger_execute_action', array('SUPER_Triggers', 'execute_scheduled_action'));

public static function execute_scheduled_action($args) {
    try {
        // Execute the action
        $result = self::execute_action($args['action_data']);

        if (!$result['success'] && $args['attempt'] < 3) {
            // Retry with exponential backoff
            $delay = pow(2, $args['attempt']) * 60; // 2min, 4min, 8min
            $args['attempt']++;

            $scheduler = new SUPER_Trigger_Scheduler();
            $scheduler->schedule_action(
                time() + $delay,
                'super_trigger_execute_action',
                $args
            );
        }
    } catch (Exception $e) {
        // Log error
        error_log('Super Forms Trigger Error: ' . $e->getMessage());
    }
}
```

### Step 4: Queue Management UI

**File:** `/src/includes/admin/class-trigger-queue-page.php` (new file)

Create admin page for queue management:

```php
class SUPER_Trigger_Queue_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
    }

    public function add_menu_page() {
        add_submenu_page(
            'super_forms',
            __('Trigger Queue', 'super-forms'),
            __('Trigger Queue', 'super-forms'),
            'manage_options',
            'super-trigger-queue',
            array($this, 'render_page')
        );
    }

    public function render_page() {
        // Display Action Scheduler admin UI filtered to our group
        ?>
        <div class="wrap">
            <h1><?php _e('Trigger Action Queue', 'super-forms'); ?></h1>

            <div class="super-queue-stats">
                <?php $this->display_queue_stats(); ?>
            </div>

            <div class="super-queue-actions">
                <?php
                // Use Action Scheduler's built-in list table
                ActionScheduler_AdminView::instance()->render_admin_ui();
                ?>
            </div>
        </div>
        <?php
    }

    private function display_queue_stats() {
        $scheduler = new SUPER_Trigger_Scheduler();
        $pending = $scheduler->get_pending_count();

        $stats = array(
            'pending' => as_get_scheduled_actions(array(
                'group' => 'super_forms_triggers',
                'status' => 'pending',
                'per_page' => -1
            ), 'count'),
            'running' => as_get_scheduled_actions(array(
                'group' => 'super_forms_triggers',
                'status' => 'in-progress',
                'per_page' => -1
            ), 'count'),
            'failed' => as_get_scheduled_actions(array(
                'group' => 'super_forms_triggers',
                'status' => 'failed',
                'per_page' => -1
            ), 'count')
        );

        // Display stats dashboard
    }
}
```

### Step 5: Recurring Actions Support

Add support for recurring triggers (e.g., daily reports, weekly summaries):

```php
// In SUPER_Trigger_Scheduler class
public function schedule_recurring_trigger($trigger_id, $interval, $start_time = null) {
    $start = $start_time ?: time();

    return $this->schedule_recurring(
        $start,
        $interval,
        'super_trigger_execute_recurring',
        array('trigger_id' => $trigger_id)
    );
}

// Interval options
public function get_interval_options() {
    return array(
        'hourly' => HOUR_IN_SECONDS,
        'twice_daily' => 12 * HOUR_IN_SECONDS,
        'daily' => DAY_IN_SECONDS,
        'weekly' => WEEK_IN_SECONDS,
        'monthly' => 30 * DAY_IN_SECONDS
    );
}
```

### Step 6: Performance Optimization

**Batch Processing:**
```php
public function schedule_batch($actions, $batch_size = 25) {
    $batches = array_chunk($actions, $batch_size);

    foreach ($batches as $index => $batch) {
        $delay = $index * 2; // Stagger batches by 2 seconds

        as_schedule_single_action(
            time() + $delay,
            'super_trigger_process_batch',
            array('batch' => $batch),
            self::GROUP
        );
    }
}
```

**Rate Limiting:**
```php
public function can_execute($action_type, $limit = 10, $window = 60) {
    $key = 'super_trigger_rate_' . $action_type;
    $count = get_transient($key) ?: 0;

    if ($count >= $limit) {
        return false;
    }

    set_transient($key, $count + 1, $window);
    return true;
}
```

## Context Manifest
<!-- To be added by context-gathering agent -->

## User Notes
- Action Scheduler is already bundled at `/src/includes/lib/action-scheduler/`
- Version 3.9.3 requires PHP 7.2+ minimum
- Use Action Scheduler's built-in UI where possible
- Consider impact on existing scheduled actions during migration
- Test thoroughly with high-volume scenarios

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Subtask created with Action Scheduler integration details
- [2025-11-22] **Phase 2 Complete** - Full implementation delivered:

  **Files Created:**
  - `class-trigger-scheduler.php` (~500 lines) - Core wrapper for Action Scheduler integration
    - Singleton pattern with availability checking
    - Hook registration for scheduled action execution
    - Exponential backoff retry mechanism (2/4/8 min delays)
    - Rate limiting support for API-heavy actions
    - Delayed execution support for `delay_execution` action
    - Recurring action support

  **Files Modified:**
  - `class-trigger-executor.php` - Added execution mode support (sync/async/auto)
    - Smart action queuing with mode detection
    - Sync-only actions list (flow control actions)
    - Async-preferred actions list (external requests)
  - `class-trigger-action-base.php` - Added async support methods
    - `supports_async()`, `get_execution_mode()`, `get_retry_config()`
    - `should_retry()`, `is_rate_limited()`, `prepare_for_queue()`
  - `class-action-delay-execution.php` - Fixed argument passing bug (wrapped args in array)
  - `super-forms.php` - Include and initialization of scheduler

  **Sync-only actions** (overrode `supports_async() → false`):
  - abort_submission, stop_execution, redirect_user, set_variable, conditional_action

  **Async-preferred actions** (overrode `get_execution_mode() → 'async'`):
  - webhook (5 retries, 1hr max), send_email (5min initial delay)

  **Queue UI deferred:** The admin queue management UI was deferred to Phase 4 (Admin UI) since it makes more sense to build the complete trigger management interface together.