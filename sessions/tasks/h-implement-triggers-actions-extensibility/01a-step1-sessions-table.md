---
name: 01a-step1-sessions-table
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-11-23
completed: 2025-11-23
parent: 01a-implement-built-in-actions-spam-detection
---

# Step 1: Sessions Database Table and DAL

## Problem/Goal

Create the `wp_superforms_sessions` table and a Data Access Layer (DAL) class for session management. This is the foundation for progressive form auto-save and the pre-submission firewall.

## Why This Step First

- Sessions table is required before spam/duplicate detection can work properly
- Sessions must exist BEFORE entry creation for the abort flow to work
- All other steps depend on this infrastructure

## Success Criteria

- [x] `wp_superforms_sessions` table created via `dbDelta()` in `class-install.php`
- [x] `SUPER_Session_DAL` class created with CRUD operations
- [x] Session lifecycle methods implemented
- [x] Unit tests for DAL operations

## Database Schema

```sql
CREATE TABLE {$wpdb->prefix}superforms_sessions (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  session_key VARCHAR(32) NOT NULL UNIQUE,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED,
  user_ip VARCHAR(45),
  status VARCHAR(20) DEFAULT 'draft',
  form_data LONGTEXT,
  metadata LONGTEXT,
  started_at DATETIME NOT NULL,
  last_saved_at DATETIME,
  completed_at DATETIME,
  expires_at DATETIME,
  PRIMARY KEY (id),
  KEY session_key (session_key),
  KEY form_id_status (form_id, status),
  KEY expires_at (expires_at),
  KEY user_lookup (user_id, form_id, status)
) ENGINE=InnoDB $charset_collate;
```

**Session Status Values:**
- `draft` - Session created, form being filled
- `submitting` - Form submission in progress
- `completed` - Form successfully submitted
- `aborted` - Submission blocked (spam/duplicate)
- `abandoned` - No activity for 30+ minutes
- `expired` - Session expired (24 hours)

## Implementation

### File 1: Database Schema

**File:** `/src/includes/class-install.php`
**Location:** After API keys table creation (~line 269)

```php
// Progressive sessions table for form auto-save
// @since 6.5.0
$table_name = $wpdb->prefix . 'superforms_sessions';

$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    session_key VARCHAR(32) NOT NULL,
    form_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED,
    user_ip VARCHAR(45),
    status VARCHAR(20) DEFAULT 'draft',
    form_data LONGTEXT,
    metadata LONGTEXT,
    started_at DATETIME NOT NULL,
    last_saved_at DATETIME,
    completed_at DATETIME,
    expires_at DATETIME,
    PRIMARY KEY (id),
    UNIQUE KEY session_key (session_key),
    KEY form_id_status (form_id, status),
    KEY expires_at (expires_at),
    KEY user_lookup (user_id, form_id, status)
) ENGINE=InnoDB $charset_collate;";

dbDelta( $sql );
```

### File 2: Session DAL Class

**File:** `/src/includes/class-session-dal.php` (NEW)

```php
<?php
/**
 * Session Data Access Layer
 *
 * Handles all database operations for form sessions.
 * Sessions track progressive form fills and enable auto-save recovery.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Session_DAL {

    /**
     * Create a new session
     *
     * @param array $data Session data
     * @return int|WP_Error Session ID or error
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        // Generate unique session key
        $session_key = isset($data['session_key'])
            ? $data['session_key']
            : wp_generate_password(32, false);

        $insert_data = [
            'session_key' => $session_key,
            'form_id' => absint($data['form_id']),
            'user_id' => isset($data['user_id']) ? absint($data['user_id']) : null,
            'user_ip' => isset($data['user_ip']) ? sanitize_text_field($data['user_ip']) : null,
            'status' => isset($data['status']) ? sanitize_key($data['status']) : 'draft',
            'form_data' => isset($data['form_data']) ? wp_json_encode($data['form_data']) : '{}',
            'metadata' => isset($data['metadata']) ? wp_json_encode($data['metadata']) : '{}',
            'started_at' => current_time('mysql'),
            'last_saved_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        ];

        $result = $wpdb->insert($table, $insert_data);

        if ($result === false) {
            return new WP_Error('db_insert_failed', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Get session by ID
     *
     * @param int $id Session ID
     * @return array|null Session data or null
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        $session = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        if ($session) {
            $session['form_data'] = json_decode($session['form_data'], true);
            $session['metadata'] = json_decode($session['metadata'], true);
        }

        return $session;
    }

    /**
     * Get session by session key
     *
     * @param string $session_key Unique session key
     * @return array|null Session data or null
     */
    public static function get_by_key($session_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        $session = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE session_key = %s", $session_key),
            ARRAY_A
        );

        if ($session) {
            $session['form_data'] = json_decode($session['form_data'], true);
            $session['metadata'] = json_decode($session['metadata'], true);
        }

        return $session;
    }

    /**
     * Update session
     *
     * @param int $id Session ID
     * @param array $data Data to update
     * @return bool|WP_Error Success or error
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        $update_data = [];

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_key($data['status']);
        }
        if (isset($data['form_data'])) {
            $update_data['form_data'] = wp_json_encode($data['form_data']);
        }
        if (isset($data['metadata'])) {
            $update_data['metadata'] = wp_json_encode($data['metadata']);
        }
        if (isset($data['completed_at'])) {
            $update_data['completed_at'] = $data['completed_at'];
        }

        // Always update last_saved_at and reset expiry
        $update_data['last_saved_at'] = current_time('mysql');
        $update_data['expires_at'] = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new WP_Error('db_update_failed', $wpdb->last_error);
        }

        return true;
    }

    /**
     * Update session by session key
     *
     * @param string $session_key Session key
     * @param array $data Data to update
     * @return bool|WP_Error Success or error
     */
    public static function update_by_key($session_key, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        $update_data = [];

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_key($data['status']);
        }
        if (isset($data['form_data'])) {
            $update_data['form_data'] = wp_json_encode($data['form_data']);
        }
        if (isset($data['metadata'])) {
            $update_data['metadata'] = wp_json_encode($data['metadata']);
        }
        if (isset($data['completed_at'])) {
            $update_data['completed_at'] = $data['completed_at'];
        }

        // Always update last_saved_at and reset expiry
        $update_data['last_saved_at'] = current_time('mysql');
        $update_data['expires_at'] = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $result = $wpdb->update($table, $update_data, ['session_key' => $session_key]);

        if ($result === false) {
            return new WP_Error('db_update_failed', $wpdb->last_error);
        }

        return true;
    }

    /**
     * Delete session
     *
     * @param int $id Session ID
     * @return bool Success
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        return $wpdb->delete($table, ['id' => $id]) !== false;
    }

    /**
     * Find existing recoverable session for user/form
     *
     * @param int $form_id Form ID
     * @param int|null $user_id User ID (null for guests)
     * @param string|null $user_ip IP address (for guest sessions)
     * @return array|null Most recent recoverable session or null
     */
    public static function find_recoverable($form_id, $user_id = null, $user_ip = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        // Build query based on user/guest
        if ($user_id) {
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table
                WHERE form_id = %d
                AND user_id = %d
                AND status IN ('draft', 'abandoned')
                AND expires_at > NOW()
                ORDER BY last_saved_at DESC
                LIMIT 1",
                $form_id,
                $user_id
            ), ARRAY_A);
        } else {
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table
                WHERE form_id = %d
                AND user_ip = %s
                AND user_id IS NULL
                AND status IN ('draft', 'abandoned')
                AND expires_at > NOW()
                ORDER BY last_saved_at DESC
                LIMIT 1",
                $form_id,
                $user_ip
            ), ARRAY_A);
        }

        if ($session) {
            $session['form_data'] = json_decode($session['form_data'], true);
            $session['metadata'] = json_decode($session['metadata'], true);
        }

        return $session;
    }

    /**
     * Mark session as completed
     *
     * @param string $session_key Session key
     * @param int|null $entry_id Created entry ID (optional)
     * @return bool Success
     */
    public static function mark_completed($session_key, $entry_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        $update_data = [
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
            'last_saved_at' => current_time('mysql'),
        ];

        // Store entry_id in metadata if provided
        if ($entry_id) {
            $session = self::get_by_key($session_key);
            if ($session) {
                $metadata = $session['metadata'] ?: [];
                $metadata['entry_id'] = $entry_id;
                $update_data['metadata'] = wp_json_encode($metadata);
            }
        }

        return $wpdb->update($table, $update_data, ['session_key' => $session_key]) !== false;
    }

    /**
     * Mark session as aborted
     *
     * @param string $session_key Session key
     * @param string $reason Abort reason
     * @return bool Success
     */
    public static function mark_aborted($session_key, $reason = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        $session = self::get_by_key($session_key);
        $metadata = $session ? ($session['metadata'] ?: []) : [];
        $metadata['abort_reason'] = $reason;
        $metadata['aborted_at'] = current_time('mysql');

        return $wpdb->update($table, [
            'status' => 'aborted',
            'metadata' => wp_json_encode($metadata),
            'last_saved_at' => current_time('mysql'),
        ], ['session_key' => $session_key]) !== false;
    }

    /**
     * Cleanup expired sessions
     *
     * @param int $limit Max sessions to cleanup per run
     * @return int Number of sessions deleted
     */
    public static function cleanup_expired($limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        // Get expired session IDs
        $expired_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $table WHERE expires_at < NOW() LIMIT %d",
            $limit
        ));

        if (empty($expired_ids)) {
            return 0;
        }

        // Delete expired sessions
        $placeholders = implode(',', array_fill(0, count($expired_ids), '%d'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE id IN ($placeholders)",
            ...$expired_ids
        ));

        return count($expired_ids);
    }

    /**
     * Mark abandoned sessions (no activity for 30+ minutes)
     *
     * @return int Number of sessions marked abandoned
     */
    public static function mark_abandoned() {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        $threshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));

        return $wpdb->query($wpdb->prepare(
            "UPDATE $table
            SET status = 'abandoned'
            WHERE status = 'draft'
            AND last_saved_at < %s",
            $threshold
        ));
    }

    /**
     * Get session statistics for a form
     *
     * @param int $form_id Form ID
     * @return array Statistics
     */
    public static function get_form_stats($form_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_sessions';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'aborted' THEN 1 ELSE 0 END) as aborted,
                SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned
            FROM $table
            WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
    }
}
```

### File 3: Include DAL in Plugin

**File:** `/src/super-forms.php`
**Location:** After other class includes (~line 200+)

```php
// Session management
require_once SUPER_PLUGIN_DIR . 'includes/class-session-dal.php';
```

## Testing

**File:** `/tests/triggers/test-session-dal.php` (NEW)

```php
<?php
class Test_Session_DAL extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        // Ensure table exists
        SUPER_Install::create_tables();
    }

    public function test_create_session() {
        $session_id = SUPER_Session_DAL::create([
            'form_id' => 123,
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ]);

        $this->assertIsInt($session_id);
        $this->assertGreaterThan(0, $session_id);
    }

    public function test_get_session() {
        $session_id = SUPER_Session_DAL::create(['form_id' => 123]);
        $session = SUPER_Session_DAL::get($session_id);

        $this->assertIsArray($session);
        $this->assertEquals(123, $session['form_id']);
        $this->assertEquals('draft', $session['status']);
    }

    public function test_update_session() {
        $session_id = SUPER_Session_DAL::create(['form_id' => 123]);

        $result = SUPER_Session_DAL::update($session_id, [
            'status' => 'submitting',
            'form_data' => ['email' => 'test@example.com'],
        ]);

        $this->assertTrue($result);

        $session = SUPER_Session_DAL::get($session_id);
        $this->assertEquals('submitting', $session['status']);
        $this->assertEquals('test@example.com', $session['form_data']['email']);
    }

    public function test_mark_completed() {
        $session_id = SUPER_Session_DAL::create(['form_id' => 123]);
        $session = SUPER_Session_DAL::get($session_id);

        $result = SUPER_Session_DAL::mark_completed($session['session_key'], 456);

        $this->assertTrue($result);

        $updated = SUPER_Session_DAL::get($session_id);
        $this->assertEquals('completed', $updated['status']);
        $this->assertNotNull($updated['completed_at']);
        $this->assertEquals(456, $updated['metadata']['entry_id']);
    }

    public function test_mark_aborted() {
        $session_id = SUPER_Session_DAL::create(['form_id' => 123]);
        $session = SUPER_Session_DAL::get($session_id);

        $result = SUPER_Session_DAL::mark_aborted($session['session_key'], 'spam_detected');

        $this->assertTrue($result);

        $updated = SUPER_Session_DAL::get($session_id);
        $this->assertEquals('aborted', $updated['status']);
        $this->assertEquals('spam_detected', $updated['metadata']['abort_reason']);
    }

    public function test_find_recoverable_for_user() {
        // Create a recoverable session
        $session_id = SUPER_Session_DAL::create([
            'form_id' => 123,
            'user_id' => 1,
            'form_data' => ['name' => 'John'],
        ]);

        $found = SUPER_Session_DAL::find_recoverable(123, 1);

        $this->assertIsArray($found);
        $this->assertEquals($session_id, $found['id']);
        $this->assertEquals('John', $found['form_data']['name']);
    }

    public function test_find_recoverable_for_guest() {
        // Create a guest session
        $session_id = SUPER_Session_DAL::create([
            'form_id' => 123,
            'user_ip' => '192.168.1.1',
            'form_data' => ['email' => 'guest@example.com'],
        ]);

        $found = SUPER_Session_DAL::find_recoverable(123, null, '192.168.1.1');

        $this->assertIsArray($found);
        $this->assertEquals($session_id, $found['id']);
    }

    public function test_no_recoverable_for_completed() {
        // Create a completed session
        $session_id = SUPER_Session_DAL::create([
            'form_id' => 123,
            'user_id' => 1,
        ]);
        $session = SUPER_Session_DAL::get($session_id);
        SUPER_Session_DAL::mark_completed($session['session_key']);

        // Should not find completed session
        $found = SUPER_Session_DAL::find_recoverable(123, 1);
        $this->assertNull($found);
    }
}
```

## Dependencies

- None (this is the foundation)

## Notes

- Session key is a 32-character random string (URL-safe)
- Sessions expire 24 hours after last activity
- Abandoned sessions are those with 30+ minutes of inactivity
- Form data stored as JSON for flexibility
- Metadata can store analytics (time_spent, fields_completed, etc.)

## Work Log

### 2025-11-23

#### Completed
- Database table `wp_superforms_sessions` created in `class-install.php` (lines 277-299)
  - Columns: id, session_key (unique), form_id, user_id, user_ip, status, form_data, metadata, started_at, last_saved_at, completed_at, expires_at
  - Indexes: session_key (unique), form_id_status, expires_at, user_lookup
- `SUPER_Session_DAL` class created (`/src/includes/class-session-dal.php`, ~530 lines)
  - CRUD: create(), get(), get_by_key(), update(), update_by_key(), delete(), delete_by_key()
  - Lifecycle: mark_completed(), mark_aborted(), mark_abandoned()
  - Recovery: find_recoverable() for user and guest sessions
  - Cleanup: cleanup_expired(), cleanup_completed()
  - Statistics: get_form_stats(), get_global_stats(), get_active_count()
- Plugin bootstrap updated (`super-forms.php:247`) to include session DAL
- Unit tests created (`/tests/triggers/test-session-dal.php`, ~570 lines, 28 test methods)
- All 317 tests passed (28 new + 289 existing), 1175 assertions
