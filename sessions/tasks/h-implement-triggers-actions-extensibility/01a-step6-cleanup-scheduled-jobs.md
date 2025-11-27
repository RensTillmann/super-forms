---
name: 01a-step6-cleanup-scheduled-jobs
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-11-23
completed: 2025-11-26
parent: 01a-implement-built-in-actions-spam-detection
---

# Step 6: Session Cleanup and Scheduled Jobs

## Problem/Goal

Implement background jobs to clean up expired/abandoned sessions and fire lifecycle events. Uses Action Scheduler for reliable background processing.

## Why This Step

- Sessions accumulate without cleanup (storage bloat)
- Abandoned sessions should fire `session.abandoned` event
- Expired sessions should fire `session.expired` event
- Background processing keeps admin fast

## Success Criteria

- [x] Action Scheduler job for session cleanup
- [x] Abandoned session detection (30+ min inactive)
- [x] Expired session cleanup (24+ hours old)
- [x] `session.abandoned` event fires for abandoned sessions
- [x] `session.expired` event fires before deletion
- [x] Admin notice shows session statistics (optional)

## Work Log

### 2025-11-26 - Implementation Complete

**Files Created:**
- `/src/includes/class-session-cleanup.php` - Session cleanup class with Action Scheduler integration
- `/tests/triggers/test-session-cleanup.php` - 8 unit tests for cleanup functionality

**Files Modified:**
- `/src/super-forms.php` - Added include for class-session-cleanup.php
- `/src/includes/triggers/class-trigger-registry.php` - Registered session.abandoned and session.expired events

**Implementation Details:**
- `SUPER_Session_Cleanup` class with two scheduled jobs:
  - `super_session_cleanup` - Runs hourly, deletes expired sessions
  - `super_session_check_abandoned` - Runs every 5 minutes, marks inactive sessions
- Batch processing (100 sessions per run) prevents timeout
- Fires events BEFORE deletion for analytics/logging
- Session stats available via `SUPER_Session_Cleanup::get_stats()`
- Manual cleanup available via `SUPER_Session_Cleanup::manual_cleanup()`

**Session Lifecycle Events:**
- `session.abandoned` - Fires when session marked abandoned (30+ min inactive)
- `session.expired` - Fires before session deletion (past expires_at)

**Tests:** All 431 tests pass (3 skipped)

## Implementation

### File 1: Session Cleanup Class

**File:** `/src/includes/class-session-cleanup.php` (NEW)

```php
<?php
/**
 * Session Cleanup Handler
 *
 * Background jobs for cleaning up expired and abandoned sessions.
 * Uses Action Scheduler for reliable execution.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Session_Cleanup {

    /**
     * Hook name for cleanup job
     */
    const CLEANUP_HOOK = 'super_session_cleanup';

    /**
     * Hook name for abandoned session check
     */
    const ABANDONED_HOOK = 'super_session_check_abandoned';

    /**
     * Batch size for cleanup operations
     */
    const BATCH_SIZE = 100;

    /**
     * Initialize cleanup jobs
     */
    public static function init() {
        // Register hooks
        add_action(self::CLEANUP_HOOK, [__CLASS__, 'run_cleanup']);
        add_action(self::ABANDONED_HOOK, [__CLASS__, 'run_abandoned_check']);

        // Schedule recurring jobs on plugin activation
        add_action('super_activated', [__CLASS__, 'schedule_jobs']);

        // Ensure jobs are scheduled
        add_action('init', [__CLASS__, 'maybe_schedule_jobs']);
    }

    /**
     * Schedule cleanup jobs
     */
    public static function schedule_jobs() {
        // Clear existing schedules
        as_unschedule_all_actions(self::CLEANUP_HOOK);
        as_unschedule_all_actions(self::ABANDONED_HOOK);

        // Schedule cleanup every hour
        if (!as_next_scheduled_action(self::CLEANUP_HOOK)) {
            as_schedule_recurring_action(
                time(),
                HOUR_IN_SECONDS,
                self::CLEANUP_HOOK,
                [],
                'super-forms'
            );
        }

        // Schedule abandoned check every 5 minutes
        if (!as_next_scheduled_action(self::ABANDONED_HOOK)) {
            as_schedule_recurring_action(
                time(),
                5 * MINUTE_IN_SECONDS,
                self::ABANDONED_HOOK,
                [],
                'super-forms'
            );
        }
    }

    /**
     * Ensure jobs are scheduled (runs on init)
     */
    public static function maybe_schedule_jobs() {
        // Only check occasionally
        $last_check = get_transient('super_session_cleanup_check');
        if ($last_check) {
            return;
        }

        set_transient('super_session_cleanup_check', 1, HOUR_IN_SECONDS);

        // Schedule if not already scheduled
        if (!as_next_scheduled_action(self::CLEANUP_HOOK)) {
            as_schedule_recurring_action(
                time() + 60,
                HOUR_IN_SECONDS,
                self::CLEANUP_HOOK,
                [],
                'super-forms'
            );
        }

        if (!as_next_scheduled_action(self::ABANDONED_HOOK)) {
            as_schedule_recurring_action(
                time() + 30,
                5 * MINUTE_IN_SECONDS,
                self::ABANDONED_HOOK,
                [],
                'super-forms'
            );
        }
    }

    /**
     * Run cleanup job
     *
     * Deletes expired sessions and fires events.
     */
    public static function run_cleanup() {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        // Get expired sessions (batch)
        $expired = $wpdb->get_results($wpdb->prepare(
            "SELECT id, session_key, form_id, user_id, status, form_data, metadata
            FROM $table
            WHERE expires_at < NOW()
            AND status IN ('draft', 'abandoned')
            LIMIT %d",
            self::BATCH_SIZE
        ), ARRAY_A);

        if (empty($expired)) {
            return;
        }

        $deleted_count = 0;

        foreach ($expired as $session) {
            // Fire session.expired event before deletion
            if (class_exists('SUPER_Trigger_Executor')) {
                SUPER_Trigger_Executor::fire_event('session.expired', [
                    'form_id' => $session['form_id'],
                    'session_id' => $session['id'],
                    'session_key' => $session['session_key'],
                    'user_id' => $session['user_id'],
                    'previous_status' => $session['status'],
                    'form_data' => json_decode($session['form_data'], true),
                    'metadata' => json_decode($session['metadata'], true),
                ]);
            }

            // Delete session
            $wpdb->delete($table, ['id' => $session['id']]);
            $deleted_count++;
        }

        // Log cleanup
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[Super Forms] Session cleanup: deleted %d expired sessions', $deleted_count));
        }

        // If we hit the batch limit, schedule another run immediately
        if ($deleted_count >= self::BATCH_SIZE) {
            as_enqueue_async_action(self::CLEANUP_HOOK, [], 'super-forms');
        }
    }

    /**
     * Run abandoned session check
     *
     * Marks sessions as abandoned if no activity for 30+ minutes.
     */
    public static function run_abandoned_check() {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        // Find sessions with no activity for 30+ minutes
        $threshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));

        $abandoned = $wpdb->get_results($wpdb->prepare(
            "SELECT id, session_key, form_id, user_id, form_data, metadata
            FROM $table
            WHERE status = 'draft'
            AND last_saved_at < %s
            LIMIT %d",
            $threshold,
            self::BATCH_SIZE
        ), ARRAY_A);

        if (empty($abandoned)) {
            return;
        }

        $marked_count = 0;

        foreach ($abandoned as $session) {
            // Update status
            $wpdb->update($table, [
                'status' => 'abandoned',
            ], [
                'id' => $session['id']
            ]);

            // Fire session.abandoned event
            if (class_exists('SUPER_Trigger_Executor')) {
                SUPER_Trigger_Executor::fire_event('session.abandoned', [
                    'form_id' => $session['form_id'],
                    'session_id' => $session['id'],
                    'session_key' => $session['session_key'],
                    'user_id' => $session['user_id'],
                    'form_data' => json_decode($session['form_data'], true),
                    'metadata' => json_decode($session['metadata'], true),
                    'abandoned_after_minutes' => 30,
                ]);
            }

            $marked_count++;
        }

        // Log
        if (defined('WP_DEBUG') && WP_DEBUG && $marked_count > 0) {
            error_log(sprintf('[Super Forms] Session abandoned check: marked %d sessions', $marked_count));
        }
    }

    /**
     * Get session statistics
     *
     * @return array Statistics
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        return $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'aborted' THEN 1 ELSE 0 END) as aborted,
                SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired
            FROM $table",
            ARRAY_A
        );
    }

    /**
     * Manual cleanup trigger (for admin)
     *
     * @return array Results
     */
    public static function manual_cleanup() {
        self::run_abandoned_check();
        self::run_cleanup();

        return self::get_stats();
    }
}

// Initialize
SUPER_Session_Cleanup::init();
```

### File 2: Include in Plugin

**File:** `/src/super-forms.php`

```php
// Session cleanup (after session DAL)
require_once SUPER_PLUGIN_DIR . 'includes/class-session-cleanup.php';
```

### File 3: Admin Integration (Optional)

Add session stats to Developer Tools page:

```php
// In developer tools section
$session_stats = SUPER_Session_Cleanup::get_stats();
?>
<div class="super-section">
    <h3><?php esc_html_e('Session Statistics', 'super-forms'); ?></h3>
    <table class="widefat">
        <tr>
            <td><?php esc_html_e('Active Sessions', 'super-forms'); ?></td>
            <td><?php echo intval($session_stats['active']); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e('Abandoned Sessions', 'super-forms'); ?></td>
            <td><?php echo intval($session_stats['abandoned']); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e('Completed Sessions', 'super-forms'); ?></td>
            <td><?php echo intval($session_stats['completed']); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e('Aborted Sessions', 'super-forms'); ?></td>
            <td><?php echo intval($session_stats['aborted']); ?></td>
        </tr>
        <tr>
            <td><?php esc_html_e('Pending Cleanup', 'super-forms'); ?></td>
            <td><?php echo intval($session_stats['expired']); ?></td>
        </tr>
    </table>
    <p>
        <button type="button" class="button" id="super-cleanup-sessions">
            <?php esc_html_e('Run Cleanup Now', 'super-forms'); ?>
        </button>
    </p>
</div>
```

## Session Lifecycle Events Summary

After all steps implemented, session events fire at these points:

| Event | When | Context |
|-------|------|---------|
| `session.started` | First field focus | form_id, session_key, user_id/ip |
| `session.auto_saved` | Field blur/change | form_id, session_key, fields_count |
| `session.resumed` | User restores saved data | form_id, session_key, previous_data |
| `session.completed` | Form successfully submitted | form_id, session_key, entry_id |
| `session.abandoned` | 30 min without activity | form_id, session_key, form_data |
| `session.expired` | Session deleted after 24h | form_id, session_key, form_data |

## Testing

```php
public function test_cleanup_deletes_expired_sessions() {
    // Create session with past expires_at
    global $wpdb;
    $table = $wpdb->prefix . 'superforms_sessions';

    $wpdb->insert($table, [
        'session_key' => 'test_expired',
        'form_id' => 1,
        'status' => 'draft',
        'expires_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'started_at' => current_time('mysql'),
    ]);

    // Run cleanup
    SUPER_Session_Cleanup::run_cleanup();

    // Verify deleted
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE session_key = %s",
        'test_expired'
    ));

    $this->assertNull($session);
}

public function test_abandoned_check_marks_inactive_sessions() {
    // Create session with old last_saved_at
    global $wpdb;
    $table = $wpdb->prefix . 'superforms_sessions';

    $wpdb->insert($table, [
        'session_key' => 'test_abandoned',
        'form_id' => 1,
        'status' => 'draft',
        'last_saved_at' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+23 hours')),
        'started_at' => current_time('mysql'),
    ]);

    // Run abandoned check
    SUPER_Session_Cleanup::run_abandoned_check();

    // Verify marked abandoned
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE session_key = %s",
        'test_abandoned'
    ));

    $this->assertEquals('abandoned', $session->status);
}
```

## Dependencies

- Step 1: Sessions Table and DAL
- Action Scheduler library (bundled)

## Notes

- Uses Action Scheduler for reliable background processing
- Batch processing prevents timeout on large cleanups
- Events fire BEFORE deletion (for analytics/logging)
- Completed sessions are kept indefinitely (only expired draft/abandoned deleted)
- Manual cleanup available in Developer Tools
