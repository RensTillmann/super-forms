---
name: 04-storage-strategy-pattern
status: completed
created: 2025-10-31
completed: 2025-11-05
---

# Implement Phase-Based Storage Strategy Pattern

## Problem/Goal

**COMPLETED:** Migration state management already implemented in Subtask 03 (Data Access Layer) and Subtask 07 (Migration Manager). The `superforms_eav_migration` option tracks state and controls phase-based storage routing (serialized → dual-write → EAV).

## Success Criteria

- [ ] Migration state tracking in `wp_options` implemented
- [ ] Migration status transitions work correctly (not_started → in_progress → completed → rolled_back)
- [ ] Storage method toggle verified (serialized ↔ EAV)
- [ ] Dual-write during migration verified (writes to BOTH)
- [ ] Single-write after migration verified (writes to EAV only)
- [ ] Rollback sets correct storage method
- [ ] Multi-site: per-site migration state tracked correctly
- [ ] Unit tests pass for all state transitions

## Migration State Structure

**Storage location**: `wp_options` table, key `superforms_eav_migration`

```php
array(
    'status' => 'completed', // not_started|in_progress|completed|rolled_back
    'started_at' => '2025-10-31 14:00:00',
    'completed_at' => '2025-10-31 14:23:45',
    'total_entries' => 8456,
    'migrated_entries' => 8456,
    'failed_entries' => array(), // entry_id => error_message
    'rollback_count' => 0,
    'using_storage' => 'eav', // 'eav' or 'serialized'
)
```

## Implementation

**File**: `/src/includes/class-migration-state.php`

```php
<?php
/**
 * Migration State Management
 *
 * @package Super Forms
 * @since   6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SUPER_Migration_State')) :

class SUPER_Migration_State {

    const OPTION_KEY = 'superforms_eav_migration';

    /**
     * Initialize migration state
     *
     * @return bool True on success
     */
    public static function initialize() {
        $state = array(
            'status' => 'not_started',
            'started_at' => null,
            'completed_at' => null,
            'total_entries' => 0,
            'migrated_entries' => 0,
            'failed_entries' => array(),
            'rollback_count' => 0,
            'using_storage' => 'serialized',
        );

        return update_option(self::OPTION_KEY, $state);
    }

    /**
     * Start migration
     *
     * @param int $total_entries Total entries to migrate
     * @return bool True on success
     */
    public static function start_migration($total_entries) {
        $state = self::get_state();

        if ($state['status'] === 'in_progress') {
            return new WP_Error('migration_already_running', __('Migration already in progress', 'super-forms'));
        }

        $state['status'] = 'in_progress';
        $state['started_at'] = current_time('mysql');
        $state['total_entries'] = $total_entries;
        $state['migrated_entries'] = 0;
        $state['failed_entries'] = array();

        return update_option(self::OPTION_KEY, $state);
    }

    /**
     * Complete migration
     *
     * @return bool True on success
     */
    public static function complete_migration() {
        $state = self::get_state();

        $state['status'] = 'completed';
        $state['completed_at'] = current_time('mysql');
        $state['using_storage'] = 'eav';

        return update_option(self::OPTION_KEY, $state);
    }

    /**
     * Rollback migration
     *
     * @return bool True on success
     */
    public static function rollback() {
        $state = self::get_state();

        $state['status'] = 'rolled_back';
        $state['using_storage'] = 'serialized';
        $state['rollback_count']++;

        return update_option(self::OPTION_KEY, $state);
    }

    /**
     * Update migration progress
     *
     * @param int $migrated_count Number of entries migrated
     * @return bool
     */
    public static function update_progress($migrated_count) {
        $state = self::get_state();
        $state['migrated_entries'] = $migrated_count;
        return update_option(self::OPTION_KEY, $state);
    }

    /**
     * Record failed entry
     *
     * @param int    $entry_id Entry ID
     * @param string $error    Error message
     * @return bool
     */
    public static function record_failed_entry($entry_id, $error) {
        $state = self::get_state();
        $state['failed_entries'][$entry_id] = $error;
        return update_option(self::OPTION_KEY, $state);
    }

    /**
     * Get current migration state
     *
     * @return array Migration state
     */
    public static function get_state() {
        $state = get_option(self::OPTION_KEY);

        if (empty($state)) {
            self::initialize();
            $state = get_option(self::OPTION_KEY);
        }

        return $state;
    }

    /**
     * Check if migration is complete
     *
     * @return bool
     */
    public static function is_complete() {
        $state = self::get_state();
        return ($state['status'] === 'completed');
    }

    /**
     * Check if using EAV storage
     *
     * @return bool
     */
    public static function is_using_eav() {
        $state = self::get_state();
        return ($state['using_storage'] === 'eav');
    }

    /**
     * Get migration progress percentage
     *
     * @return float Percentage (0-100)
     */
    public static function get_progress_percentage() {
        $state = self::get_state();

        if ($state['total_entries'] == 0) {
            return 0;
        }

        return ($state['migrated_entries'] / $state['total_entries']) * 100;
    }
}

endif;
```

## Unit Tests

**File**: `test/phpunit/tests/test-migration-state.php`

```php
<?php
class Test_Migration_State extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        delete_option('superforms_eav_migration');
    }

    public function test_initialize() {
        SUPER_Migration_State::initialize();
        $state = SUPER_Migration_State::get_state();

        $this->assertEquals('not_started', $state['status']);
        $this->assertEquals('serialized', $state['using_storage']);
    }

    public function test_start_migration() {
        SUPER_Migration_State::initialize();
        SUPER_Migration_State::start_migration(1000);

        $state = SUPER_Migration_State::get_state();

        $this->assertEquals('in_progress', $state['status']);
        $this->assertEquals(1000, $state['total_entries']);
    }

    public function test_complete_migration() {
        SUPER_Migration_State::initialize();
        SUPER_Migration_State::start_migration(1000);
        SUPER_Migration_State::complete_migration();

        $state = SUPER_Migration_State::get_state();

        $this->assertEquals('completed', $state['status']);
        $this->assertEquals('eav', $state['using_storage']);
    }

    public function test_rollback() {
        SUPER_Migration_State::initialize();
        SUPER_Migration_State::start_migration(1000);
        SUPER_Migration_State::complete_migration();
        SUPER_Migration_State::rollback();

        $state = SUPER_Migration_State::get_state();

        $this->assertEquals('rolled_back', $state['status']);
        $this->assertEquals('serialized', $state['using_storage']);
        $this->assertEquals(1, $state['rollback_count']);
    }

    public function test_update_progress() {
        SUPER_Migration_State::initialize();
        SUPER_Migration_State::start_migration(1000);
        SUPER_Migration_State::update_progress(250);

        $percentage = SUPER_Migration_State::get_progress_percentage();

        $this->assertEquals(25, $percentage);
    }
}
```

## Dependencies

- Subtask 03 (Data Access Layer) uses migration state to determine storage method

## Estimated Effort

**2-3 days**

## Related Research

- Main README: Technical Implementation Details (migration state structure)
- Main README: Migration Timeline (phase transitions)
