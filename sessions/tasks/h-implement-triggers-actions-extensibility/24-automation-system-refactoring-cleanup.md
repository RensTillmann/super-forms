# Phase 24: Automation System Refactoring & Cleanup

**Priority**: 🔴 **HIGH** - Technical debt that can cause runtime failures
**Dependencies**: None (can be done in parallel with Phase 23)
**Timeline**: 3-4 days

---

## Terminology Reference

See [Phase 26: Terminology Standardization](26-terminology-standardization.md) for the definitive naming convention:
- **Automation** = The saved entity (container)
- **Trigger** = Event node that starts execution
- **Action** = Task node that does something
- **Condition** = Logic node for branching
- **Control** = Flow node (delay, schedule, stop)

---

## Overview

This phase addresses **6 confirmed issues** discovered during comprehensive codebase analysis. These issues represent "refactoring hangover" - code artifacts from the schema evolution that was never fully cleaned up.

**Issue Summary:**

| # | Issue | Severity | File(s) | Impact |
|---|-------|----------|---------|--------|
| 1 | Schema Mismatch in Manager | High | `class-automation-manager.php` | Methods fail with old schema fields |
| 2 | Wrong Column Name (status vs entry_status) | High | `class-workflow-executor.php` | Silent update failures |
| 3 | Delay Execution Vaporware | High | `class-workflow-executor.php` | Feature doesn't work |
| 4 | Manager Validation Rejects Valid Data | High | `class-automation-manager.php` | Can't create automations via Manager |
| 5 | Performance Scalability (Fetch All) | Medium | `class-automation-manager.php` | O(n) per event at scale |
| 6 | False Positive Resolved | N/A | N/A | Table name typo was false alarm |

**Note**: File names above reflect the NEW naming convention from Phase 26.

---

## Issue #1: Schema Mismatch in Manager Class

### Problem

The `SUPER_Automation_Manager` class references columns that **don't exist** in the database schema:

**Target Database Schema (after Phase 26 rename):**
```sql
CREATE TABLE wp_superforms_automations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'visual',
  workflow_graph LONGTEXT,
  event_types_index VARCHAR(500),
  form_ids_index VARCHAR(500),
  enabled TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
  -- NO scope, scope_id, event_id, conditions, or execution_order columns!
);
```

**Manager Returns Non-Existent Fields (class-automation-manager.php):**
```php
return array(
    'id'              => $automation_id,
    'name'            => $automation_data['name'],
    'scope'           => $automation_data['scope'],           // ❌ DOESN'T EXIST
    'scope_id'        => $automation_data['scope_id'] ?? null, // ❌ DOESN'T EXIST
    'event_id'        => $automation_data['event_id'],         // ❌ DOESN'T EXIST
    'conditions'      => $automation_data['conditions'],       // ❌ DOESN'T EXIST
    'enabled'         => $automation_data['enabled'] ?? 1,
    'execution_order' => $automation_data['execution_order'] ?? 10, // ❌ DOESN'T EXIST
    'actions'         => $created_actions,
);
```

### Impact

- `SUPER_Automation_Manager::create_automation_with_actions()` will fail or return garbage data
- `SUPER_Automation_Manager::duplicate_automation()` will fail
- `SUPER_Automation_Manager::sanitize_automation_data()` sanitizes non-existent fields

### Why REST API Still Works

The REST Controller **bypasses Manager** and uses DAL directly:

```php
// class-automation-rest-controller.php (CORRECT)
$automation_data = array(
    'name'           => $params['name'] ?? '',
    'type'           => $params['type'] ?? 'visual',
    'workflow_graph' => $params['workflow_graph'] ?? '',
    'enabled'        => $params['enabled'] ?? 1,
);
$automation_id = SUPER_Automation_DAL::create_automation( $automation_data );
```

### Solution

**Option A: Deprecate Manager Class (Recommended)**
Since REST Controller works correctly, the Manager is effectively dead code.

```php
/**
 * @deprecated 7.1.0 Use SUPER_Automation_DAL directly or REST API
 */
class SUPER_Automation_Manager {
    // Mark all methods as deprecated
}
```

**Option B: Update Manager to Match New Schema**

```php
public static function create_automation_with_actions( $automation_data, $actions_data = array() ) {
    // Validate permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'permission_denied', __( 'Permission denied', 'super-forms' ) );
    }

    // Validate NEW schema fields
    if ( empty( $automation_data['name'] ) ) {
        return new WP_Error( 'missing_name', __( 'Automation name required', 'super-forms' ) );
    }

    // Sanitize NEW schema fields only
    $sanitized = array(
        'name'           => sanitize_text_field( $automation_data['name'] ),
        'type'           => sanitize_text_field( $automation_data['type'] ?? 'visual' ),
        'workflow_graph' => $automation_data['workflow_graph'] ?? '',
        'enabled'        => absint( $automation_data['enabled'] ?? 1 ),
    );

    // Create automation via DAL
    $automation_id = SUPER_Automation_DAL::create_automation( $sanitized );
    if ( is_wp_error( $automation_id ) ) {
        return $automation_id;
    }

    // Return NEW schema structure
    return array(
        'id'             => $automation_id,
        'name'           => $sanitized['name'],
        'type'           => $sanitized['type'],
        'workflow_graph' => $sanitized['workflow_graph'],
        'enabled'        => $sanitized['enabled'],
    );
}
```

### Files to Modify

- `/src/includes/class-automation-manager.php` - Update or deprecate

### Testing

```php
// Test Manager creates automation correctly
$result = SUPER_Automation_Manager::create_automation_with_actions([
    'name' => 'Test Automation',
    'type' => 'visual',
    'workflow_graph' => json_encode(['nodes' => [], 'connections' => []]),
], []);

// Should return valid automation with NEW schema fields
assert( isset( $result['id'] ) );
assert( isset( $result['type'] ) );
assert( ! isset( $result['scope'] ) );  // OLD field should NOT exist
assert( ! isset( $result['event_id'] ) );  // OLD field should NOT exist
```

---

## Issue #2: Wrong Column Name (status vs entry_status)

### Problem

**Location:** `class-workflow-executor.php`

```php
private static function action_update_entry_status( $config, $event_data, $context ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'superforms_entries';

    $updated = $wpdb->update(
        $table_name,
        array( 'status' => $new_status ),  // ❌ WRONG COLUMN NAME
        array( 'id' => $entry_id ),
        array( '%s' ),
        array( '%d' )
    );
}
```

**Actual Schema:**
```sql
CREATE TABLE superforms_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    entry_status VARCHAR(50) DEFAULT NULL,  -- ✅ CORRECT: 'entry_status'
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

### Impact

- `update_entry_status` action **silently fails** (no rows updated)
- `$wpdb->update()` returns `0` (no error, just no match)
- User sees "success" but status never changes

### Solution

```php
private static function action_update_entry_status( $config, $event_data, $context ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'superforms_entries';

    $updated = $wpdb->update(
        $table_name,
        array( 'entry_status' => $new_status ),  // ✅ FIXED
        array( 'id' => $entry_id ),
        array( '%s' ),
        array( '%d' )
    );

    // Add proper error detection
    if ( $updated === false ) {
        error_log( 'Failed to update entry status: ' . $wpdb->last_error );
        return array( 'status' => 'error', 'message' => 'Database error' );
    }

    if ( $updated === 0 ) {
        error_log( "Entry {$entry_id} not found or status unchanged" );
        return array( 'status' => 'warning', 'message' => 'Entry not found' );
    }

    return array( 'status' => 'success', 'updated' => true );
}
```

### Files to Modify

- `/src/includes/class-workflow-executor.php`

### Testing

```php
// Create test entry
global $wpdb;
$wpdb->insert(
    $wpdb->prefix . 'superforms_entries',
    ['form_id' => 1, 'entry_status' => 'pending', 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')]
);
$entry_id = $wpdb->insert_id;

// Execute action
$result = SUPER_Workflow_Executor::action_update_entry_status(
    ['status' => 'completed'],
    ['entry_id' => $entry_id],
    []
);

// Verify status changed
$status = $wpdb->get_var($wpdb->prepare(
    "SELECT entry_status FROM {$wpdb->prefix}superforms_entries WHERE id = %d",
    $entry_id
));
assert( $status === 'completed' );
```

---

## Issue #3: Delay Execution Vaporware

### Problem

**Location:** `class-workflow-executor.php`

The `delay_execution` action exists in the UI but **does nothing**:

```php
private static function action_delay_execution( $config, $event_data ) {
    error_log( 'ACTION: delay_execution' );

    $duration = $config['duration'] ?? 1;
    $unit = $config['unit'] ?? 'hours';

    // Convert to seconds
    $seconds = self::convert_duration_to_seconds( $duration, $unit );

    error_log( "Delaying execution for {$duration} {$unit} ({$seconds} seconds)" );

    // Schedule continuation using Action Scheduler
    // Note: This is a simplified version. In production, we'd need to:
    // 1. Store workflow state         ❌ NOT IMPLEMENTED
    // 2. Schedule continuation        ❌ NOT IMPLEMENTED
    // 3. Resume from this point later ❌ NOT IMPLEMENTED

    return array(
        'status' => 'delayed',  // ← Just returns, nothing actually scheduled!
        'duration' => $duration,
        'unit' => $unit,
        'seconds' => $seconds,
    );
}
```

### Impact

- Users configure delays in visual builder
- Delays are **completely ignored**
- Subsequent actions execute immediately
- No error shown - appears to work but doesn't

### Solution

Implement proper workflow state persistence and Action Scheduler integration:

```php
private static function action_delay_execution( $config, $event_data, $context ) {
    $duration = $config['duration'] ?? 1;
    $unit = $config['unit'] ?? 'hours';
    $seconds = self::convert_duration_to_seconds( $duration, $unit );

    // Get workflow state info
    $automation_id = $context['automation_id'] ?? 0;
    $current_node_id = $context['current_node_id'] ?? '';
    $workflow_graph = $context['workflow_graph'] ?? [];

    // Find downstream nodes to resume from
    $downstream_nodes = self::get_downstream_node_ids( $current_node_id, $workflow_graph['connections'] ?? [] );

    if ( empty( $downstream_nodes ) ) {
        // Nothing to schedule after delay
        return array( 'status' => 'success', 'message' => 'Delay completed (no downstream actions)' );
    }

    // Serialize workflow state
    $workflow_state = array(
        'automation_id'     => $automation_id,
        'resume_from_nodes' => $downstream_nodes,
        'event_data'        => $event_data,
        'context'           => $context,
        'executed_nodes'    => $context['executed_nodes'] ?? [],
        'node_outputs'      => $context['node_outputs'] ?? [],
    );

    // Store state in database
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'superforms_automation_states',
        array(
            'automation_id'  => $automation_id,
            'state_data'     => wp_json_encode( $workflow_state ),
            'resume_at'      => date( 'Y-m-d H:i:s', time() + $seconds ),
            'status'         => 'pending',
            'created_at'     => current_time( 'mysql' ),
        )
    );
    $state_id = $wpdb->insert_id;

    // Schedule resumption via Action Scheduler
    if ( function_exists( 'as_schedule_single_action' ) ) {
        as_schedule_single_action(
            time() + $seconds,
            'super_automation_resume',
            array( 'state_id' => $state_id ),
            'superforms_automations'
        );
    }

    // Return HALT status to stop current execution chain
    return array(
        'status'   => 'halted',  // Special status to stop traversal
        'halt'     => true,      // Flag for executor to stop
        'state_id' => $state_id,
        'resume_at' => date( 'Y-m-d H:i:s', time() + $seconds ),
        'message'  => "Workflow paused. Will resume in {$duration} {$unit}",
    );
}
```

### Required New Table

```sql
CREATE TABLE wp_superforms_automation_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    state_data LONGTEXT NOT NULL,
    resume_at DATETIME NOT NULL,
    status ENUM('pending', 'resumed', 'failed', 'expired') DEFAULT 'pending',
    error_message TEXT,
    created_at DATETIME NOT NULL,
    resumed_at DATETIME,
    INDEX idx_resume_at (resume_at),
    INDEX idx_status (status),
    INDEX idx_automation_id (automation_id)
) ENGINE=InnoDB;
```

### Required Resume Hook

```php
// In class-workflow-executor.php or new file
add_action( 'super_automation_resume', 'super_resume_delayed_automation' );

function super_resume_delayed_automation( $args ) {
    $state_id = $args['state_id'] ?? 0;

    global $wpdb;
    $state = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}superforms_automation_states WHERE id = %d AND status = 'pending'",
        $state_id
    ), ARRAY_A );

    if ( ! $state ) {
        error_log( "Automation state {$state_id} not found or already processed" );
        return;
    }

    // Decode state
    $workflow_state = json_decode( $state['state_data'], true );

    // Mark as resumed
    $wpdb->update(
        $wpdb->prefix . 'superforms_automation_states',
        array( 'status' => 'resumed', 'resumed_at' => current_time( 'mysql' ) ),
        array( 'id' => $state_id )
    );

    // Resume workflow execution from saved nodes
    foreach ( $workflow_state['resume_from_nodes'] as $node_id ) {
        SUPER_Workflow_Executor::resume_from_node(
            $node_id,
            $workflow_state['event_data'],
            $workflow_state['context']
        );
    }
}
```

### Files to Modify/Create

- `/src/includes/class-workflow-executor.php` - Implement delay properly
- `/src/includes/class-install.php` - Add `automation_states` table
- `/src/includes/class-automation-resume.php` (NEW) - Resume handler

### Testing

```php
// Test delay schedules Action Scheduler job
$workflow = [
    'nodes' => [
        ['id' => 'n1', 'type' => 'form.submitted', 'config' => []],
        ['id' => 'n2', 'type' => 'delay_execution', 'config' => ['duration' => 1, 'unit' => 'minutes']],
        ['id' => 'n3', 'type' => 'send_email', 'config' => ['to' => 'test@example.com']],
    ],
    'connections' => [
        ['from' => 'n1', 'to' => 'n2'],
        ['from' => 'n2', 'to' => 'n3'],
    ],
];

SUPER_Workflow_Executor::execute( $workflow, ['form_id' => 1], 1 );

// Verify Action Scheduler job was created
$scheduled = as_get_scheduled_actions([
    'hook' => 'super_automation_resume',
    'status' => 'pending',
]);
assert( count( $scheduled ) === 1 );

// Verify email NOT sent yet
// (would need to check mail mock)

// Fast-forward time and run AS
do_action( 'action_scheduler_run_queue' );

// Verify email now sent
```

---

## Issue #4: Manager Validation Rejects Valid Data

### Problem

**Location:** `class-automation-manager.php`

The validation method checks for `event_id` which doesn't exist at automation level:

```php
public static function validate_automation_data( $data ) {
    // Requires event_id
    if ( empty( $data['event_id'] ) ) {
        return new WP_Error(
            'missing_event_id',
            __( 'Event ID is required', 'super-forms' )  // ❌ OLD SCHEMA
        );
    }

    // Validates scope (OLD SCHEMA)
    // ...

    // Validates event exists
    $registry = SUPER_Automation_Registry::get_instance();
    $event = $registry->get_event( $data['event_id'] );  // ❌ event_id doesn't exist
    if ( null === $event ) {
        return new WP_Error( 'invalid_event_id', ... );
    }
}
```

### Impact

- `SUPER_Automation_Manager::create_automation_with_actions()` always fails validation
- Returns: `WP_Error('missing_event_id', 'Event ID is required')`
- Any code using Manager API is broken

### Solution

Update validation to match new schema:

```php
public static function validate_automation_data( $data ) {
    // Check NEW schema required fields
    if ( empty( $data['name'] ) ) {
        return new WP_Error(
            'missing_name',
            __( 'Automation name is required', 'super-forms' )
        );
    }

    // Validate type (NEW schema)
    $valid_types = array( 'visual', 'code' );
    $type = $data['type'] ?? 'visual';

    if ( ! in_array( $type, $valid_types, true ) ) {
        return new WP_Error(
            'invalid_type',
            sprintf( __( 'Invalid type "%s"', 'super-forms' ), $type )
        );
    }

    // Optionally validate workflow_graph structure
    if ( ! empty( $data['workflow_graph'] ) ) {
        $graph = is_string( $data['workflow_graph'] )
            ? json_decode( $data['workflow_graph'], true )
            : $data['workflow_graph'];

        if ( $graph === null ) {
            return new WP_Error(
                'invalid_workflow_graph',
                __( 'Workflow graph must be valid JSON', 'super-forms' )
            );
        }

        // Visual workflows should have nodes and connections
        if ( $type === 'visual' ) {
            if ( ! isset( $graph['nodes'] ) || ! isset( $graph['connections'] ) ) {
                return new WP_Error(
                    'invalid_workflow_structure',
                    __( 'Visual workflow must have nodes and connections arrays', 'super-forms' )
                );
            }
        }
    }

    return true;
}
```

### Files to Modify

- `/src/includes/class-automation-manager.php` - Update `validate_automation_data()`

---

## Issue #5: Performance Scalability (Fetch All Pattern)

### Problem

**Location:** `class-automation-manager.php`

```php
public static function resolve_automations_for_event( $event_id, $context ) {
    // Load ALL enabled automations
    $all_automations = SUPER_Automation_DAL::get_all_automations( true );  // ❌ O(n) per event

    // Then filter in PHP
    foreach ( $all_automations as $automation ) {
        // ... check each automation's workflow_graph JSON
    }
}
```

### Impact (At Scale)

| Automations | JSON Decodes per Event | Time (estimated) |
|----------|----------------------|------------------|
| 50       | 50                   | ~5ms             |
| 500      | 500                  | ~50ms            |
| 5000     | 5000                 | ~500ms           |

Every form submission triggers this O(n) operation.

### Current Architecture Rationale

This is **by design** (documented as "Node-Level Scope Architecture"):
- Scope is stored inside workflow_graph JSON
- Can't filter by scope at database level
- Enables multi-event workflows (one automation, multiple event nodes)

### Future Solution (Phase 25?)

**Option A: Junction Table for Event Types**

```sql
CREATE TABLE wp_superforms_automation_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    form_id BIGINT UNSIGNED,  -- NULL = all forms
    UNIQUE KEY automation_event_form (automation_id, event_type, form_id),
    INDEX idx_event_form (event_type, form_id)
) ENGINE=InnoDB;
```

Query becomes:
```sql
SELECT t.* FROM superforms_automations t
JOIN superforms_automation_events te ON t.id = te.automation_id
WHERE te.event_type = 'form.submitted'
  AND (te.form_id IS NULL OR te.form_id = 123)
  AND t.enabled = 1
```

**Option B: Denormalized event_types Column**

```sql
ALTER TABLE superforms_automations
ADD COLUMN event_types TEXT;  -- JSON array: ["form.submitted", "entry.updated"]
```

Query becomes:
```sql
SELECT * FROM superforms_automations
WHERE enabled = 1
  AND JSON_CONTAINS(event_types, '"form.submitted"')
```

### Recommendation

For now, document this as **known limitation**. Add performance monitoring:

```php
public static function resolve_automations_for_event( $event_id, $context ) {
    $start_time = microtime( true );

    $all_automations = SUPER_Automation_DAL::get_all_automations( true );

    $elapsed = ( microtime( true ) - $start_time ) * 1000;

    // Log if slow
    if ( $elapsed > 100 ) {
        error_log( sprintf(
            'PERFORMANCE WARNING: resolve_automations_for_event took %.2fms for %d automations',
            $elapsed,
            count( $all_automations )
        ) );
    }

    // ... rest of method
}
```

### Files to Modify

- `/src/includes/class-automation-manager.php` - Add performance logging
- Document in README as known limitation

---

## Issue #6: False Positive - Table Name Typo

### Verification

The developer's analysis mentioned a typo between `super_forms_entries` and `superforms_entries`.

**Verified:** Both locations use `superforms_entries` consistently:
- `class-install.php:307`: `$wpdb->prefix . 'superforms_entries'`
- `class-workflow-executor.php:344`: `$wpdb->prefix . 'superforms_entries'`

**Status:** No issue exists. This was a false positive.

---

## Implementation Plan

### Day 1: Critical Fixes

1. **Fix Column Name Bug** (Issue #2)
   - Change `status` to `entry_status` in `class-workflow-executor.php`
   - Add error detection for `$wpdb->update()` result
   - Test with actual entry update

2. **Fix Manager Validation** (Issue #4)
   - Update `validate_automation_data()` to validate NEW schema fields
   - Remove OLD schema field validation
   - Test Manager can create automations

### Day 2: Manager Cleanup

3. **Update Manager Schema Handling** (Issue #1)
   - Option A: Deprecate Manager class entirely
   - Option B: Update to return NEW schema fields
   - Update `sanitize_automation_data()` to match
   - Update `duplicate_automation()` to match

### Day 3-4: Delay Implementation

4. **Implement Delay Properly** (Issue #3)
   - Add `superforms_automation_states` table to `class-install.php`
   - Implement workflow state serialization
   - Implement Action Scheduler integration
   - Implement resume hook
   - Add `halt` status handling in executor
   - Test end-to-end delay workflow

### Day 5 (Optional): Performance

5. **Add Performance Monitoring** (Issue #5)
   - Add timing to `resolve_automations_for_event()`
   - Log warning if > 100ms
   - Document as known limitation
   - Consider junction table for future phase

---

## Success Criteria

✅ **Issue #1 (Schema Mismatch):**
- Manager returns only NEW schema fields
- No references to `scope`, `scope_id`, `event_id` at automation level
- Or: Manager is deprecated with clear documentation

✅ **Issue #2 (Column Name):**
- `update_entry_status` action uses `entry_status` column
- Action correctly updates entry status
- Proper error handling for missing entries

✅ **Issue #3 (Delay Execution):**
- Delay node actually pauses workflow
- Workflow state persisted to database
- Action Scheduler job created for resumption
- Workflow resumes correctly after delay
- Email/actions after delay execute at correct time

✅ **Issue #4 (Validation):**
- Manager validates NEW schema fields
- Manager accepts valid NEW schema data
- Manager rejects invalid data with clear errors

✅ **Issue #5 (Performance):**
- Performance logging in place
- Warning logged if resolve takes > 100ms
- Documented as known limitation
- Future optimization path documented

---

## Testing Plan

### Unit Tests

```php
// test-automation-manager-schema.php
class Test_Automation_Manager_Schema extends WP_UnitTestCase {

    public function test_create_automation_with_new_schema() {
        $result = SUPER_Automation_Manager::create_automation_with_actions([
            'name' => 'Test',
            'type' => 'visual',
            'workflow_graph' => '{"nodes":[],"connections":[]}',
        ], []);

        $this->assertNotWPError( $result );
        $this->assertArrayHasKey( 'id', $result );
        $this->assertArrayHasKey( 'type', $result );
        $this->assertArrayNotHasKey( 'scope', $result );  // OLD field
        $this->assertArrayNotHasKey( 'event_id', $result );  // OLD field
    }

    public function test_update_entry_status_uses_correct_column() {
        // Create entry
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'superforms_entries',
            [
                'form_id' => 1,
                'entry_status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]
        );
        $entry_id = $wpdb->insert_id;

        // Execute action
        $result = SUPER_Workflow_Executor::execute(
            [
                'nodes' => [
                    ['id' => 'n1', 'type' => 'form.submitted', 'config' => []],
                    ['id' => 'n2', 'type' => 'update_entry_status', 'config' => ['status' => 'completed']],
                ],
                'connections' => [['from' => 'n1', 'to' => 'n2']],
            ],
            ['entry_id' => $entry_id, 'form_id' => 1],
            1
        );

        // Verify status updated
        $status = $wpdb->get_var( $wpdb->prepare(
            "SELECT entry_status FROM {$wpdb->prefix}superforms_entries WHERE id = %d",
            $entry_id
        ) );

        $this->assertEquals( 'completed', $status );
    }

    public function test_delay_execution_creates_scheduled_action() {
        // Execute workflow with delay
        $result = SUPER_Workflow_Executor::execute(
            [
                'nodes' => [
                    ['id' => 'n1', 'type' => 'form.submitted', 'config' => []],
                    ['id' => 'n2', 'type' => 'delay_execution', 'config' => ['duration' => 5, 'unit' => 'minutes']],
                    ['id' => 'n3', 'type' => 'log_message', 'config' => ['message' => 'After delay']],
                ],
                'connections' => [
                    ['from' => 'n1', 'to' => 'n2'],
                    ['from' => 'n2', 'to' => 'n3'],
                ],
            ],
            ['form_id' => 1],
            1
        );

        // Verify Action Scheduler job created
        $scheduled = as_get_scheduled_actions([
            'hook' => 'super_automation_resume',
            'status' => ActionScheduler_Store::STATUS_PENDING,
        ]);

        $this->assertGreaterThan( 0, count( $scheduled ) );
    }
}
```

### Integration Tests

1. Create automation via Manager API → verify stored correctly
2. Execute workflow with delay → verify resumes after time
3. Execute update_entry_status → verify entry actually updated

---

## Files Summary

### To Modify

| File | Changes |
|------|---------|
| `/src/includes/class-automation-manager.php` | Update schema handling, fix validation |
| `/src/includes/class-workflow-executor.php` | Fix column name, implement delay |
| `/src/includes/class-install.php` | Add `automation_states` table |

### To Create

| File | Purpose |
|------|---------|
| `/src/includes/class-automation-resume.php` | Delayed workflow resumption handler |
| `/tests/automations/test-automation-manager-schema.php` | Unit tests for schema fixes |

---

## Control Flow Nodes: Complete Technical Specification

This section provides detailed implementation specifications for all control flow nodes.

### Stop Execution Node

**Purpose**: Immediately halt workflow execution. No downstream nodes execute.

**Implementation** (straightforward):

```php
private static function action_stop_execution( $config, $event_data, $context ) {
    $reason = $config['reason'] ?? 'Manual stop';

    // Log the stop
    SUPER_Automation_DAL::log_execution( array(
        'automation_id'  => $context['automation_id'],
        'event_id'    => $context['event_type'],
        'status'      => 'stopped',
        'result_data' => array( 'reason' => $reason ),
    ) );

    // Return halt signal - executor stops traversal
    return array(
        'status' => 'halted',
        'halt'   => true,        // ← Key flag for executor
        'reason' => $reason,
    );
}
```

**Executor Handling**:

```php
// In execute_node()
$result = call_user_func( $handler, $config, $event_data, $context );

// Check for halt signal
if ( isset( $result['halt'] ) && $result['halt'] === true ) {
    // Don't traverse to downstream nodes - stop here
    return $result;
}

// Otherwise continue to connected nodes...
foreach ( $downstream_nodes as $node_id ) {
    self::execute_node( $node_id, $event_data, $context, $graph );
}
```

**Config Schema**:
```json
{
  "reason": "Optional reason for stopping"
}
```

**No state persistence required** - execution simply ends.

---

### Schedule Node

**Purpose**: Execute downstream actions at a specific date/time (not relative delay).

**Implementation**:

```php
private static function action_schedule_execution( $config, $event_data, $context ) {
    $mode = $config['mode'] ?? 'absolute';  // 'absolute' or 'field'

    // Determine target timestamp
    if ( $mode === 'field' ) {
        // Get datetime from form field
        $field_name = $config['fieldName'] ?? '';
        $datetime_value = $event_data['fields'][ $field_name ] ?? '';

        if ( empty( $datetime_value ) ) {
            return array(
                'status'  => 'error',
                'message' => "Field '{$field_name}' is empty or missing",
            );
        }

        $timestamp = strtotime( $datetime_value );
    } else {
        // Absolute datetime from config
        $datetime = $config['datetime'] ?? '';
        $timezone = $config['timezone'] ?? wp_timezone_string();

        try {
            $tz = new DateTimeZone( $timezone );
            $dt = new DateTime( $datetime, $tz );
            $timestamp = $dt->getTimestamp();
        } catch ( Exception $e ) {
            return array(
                'status'  => 'error',
                'message' => 'Invalid datetime: ' . $e->getMessage(),
            );
        }
    }

    // Validate timestamp is in future
    if ( $timestamp <= time() ) {
        // Execute immediately if scheduled time has passed
        return array(
            'status'  => 'immediate',
            'message' => 'Scheduled time already passed, executing immediately',
        );
    }

    // Use shared delay infrastructure
    return self::schedule_workflow_resumption(
        $timestamp,
        $context,
        $event_data,
        'scheduled'
    );
}
```

**Config Schema**:
```json
{
  "mode": "absolute",
  "datetime": "2024-12-25T10:00:00",
  "timezone": "America/New_York"
}
```

Or field-based:
```json
{
  "mode": "field",
  "fieldName": "appointment_date"
}
```

---

### Shared Delay/Schedule Infrastructure

Both Delay and Schedule nodes use the same underlying mechanism:

```php
/**
 * Schedule workflow resumption at a specific timestamp
 *
 * @param int    $timestamp   Unix timestamp to resume at
 * @param array  $context     Current execution context
 * @param array  $event_data  Original event data
 * @param string $type        'delayed' or 'scheduled'
 * @return array Result with halt flag
 */
private static function schedule_workflow_resumption( $timestamp, $context, $event_data, $type = 'delayed' ) {
    global $wpdb;

    $automation_id      = $context['automation_id'] ?? 0;
    $current_node_id = $context['current_node_id'] ?? '';
    $workflow_graph  = $context['workflow_graph'] ?? array();

    // Find downstream nodes to resume from
    $downstream_nodes = self::get_downstream_node_ids(
        $current_node_id,
        $workflow_graph['connections'] ?? array()
    );

    if ( empty( $downstream_nodes ) ) {
        return array(
            'status'  => 'success',
            'message' => 'No downstream actions to schedule',
        );
    }

    // Serialize complete workflow state
    $state_data = array(
        'automation_id'        => $automation_id,
        'resume_node_id'    => $downstream_nodes[0],  // Primary resume point
        'resume_from_nodes' => $downstream_nodes,     // All downstream nodes
        'event_data'        => $event_data,
        'variables'         => $context['variables'] ?? array(),
        'node_outputs'      => $context['node_outputs'] ?? array(),
        'executed_nodes'    => $context['executed_nodes'] ?? array(),
    );

    // Store state in database
    $wpdb->insert(
        $wpdb->prefix . 'superforms_automation_states',
        array(
            'automation_id'     => $automation_id,
            'entry_id'       => $event_data['entry_id'] ?? null,
            'resume_node_id' => $downstream_nodes[0],
            'state_data'     => wp_json_encode( $state_data ),
            'resume_at'      => date( 'Y-m-d H:i:s', $timestamp ),
            'status'         => 'pending',
            'created_at'     => current_time( 'mysql' ),
        ),
        array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
    );
    $state_id = $wpdb->insert_id;

    // Schedule via Action Scheduler
    if ( function_exists( 'as_schedule_single_action' ) ) {
        as_schedule_single_action(
            $timestamp,
            'super_automation_resume',
            array( 'state_id' => $state_id ),
            'superforms_automations'
        );
    }

    return array(
        'status'    => 'halted',
        'halt'      => true,
        'type'      => $type,
        'state_id'  => $state_id,
        'resume_at' => date( 'Y-m-d H:i:s', $timestamp ),
        'message'   => sprintf( 'Workflow %s until %s', $type, date( 'Y-m-d H:i:s', $timestamp ) ),
    );
}
```

---

### Workflow States Table (Complete Schema)

```sql
CREATE TABLE wp_superforms_automation_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    entry_id BIGINT UNSIGNED,                          -- Link to form entry (if applicable)
    resume_node_id VARCHAR(100) NOT NULL,              -- Primary node to resume from
    state_data LONGTEXT NOT NULL,                      -- JSON: full execution state
    resume_at DATETIME NOT NULL,                       -- When to execute
    status ENUM('pending', 'resumed', 'failed', 'cancelled', 'expired') DEFAULT 'pending',
    attempts INT UNSIGNED DEFAULT 0,                   -- Retry count
    last_error TEXT,                                   -- Error message if failed
    created_at DATETIME NOT NULL,
    resumed_at DATETIME,                               -- When actually resumed

    -- Indexes for efficient querying
    INDEX idx_resume_pending (status, resume_at),      -- Action Scheduler polling
    INDEX idx_automation (automation_id),              -- Filter by automation
    INDEX idx_entry (entry_id),                        -- Filter by entry
    INDEX idx_status (status)                          -- Status filtering
) ENGINE=InnoDB;
```

**State Data Structure**:

```php
$state_data = array(
    // Automation context
    'automation_id'        => 42,
    'resume_node_id'    => 'node-send-reminder',
    'resume_from_nodes' => array( 'node-send-reminder', 'node-update-status' ),

    // Original event data (form submission, etc.)
    'event_data' => array(
        'form_id'   => 123,
        'entry_id'  => 456,
        'fields'    => array(
            'email'      => 'user@example.com',
            'first_name' => 'John',
            'amount'     => '99.00',
        ),
        'user_id'   => 1,
        'timestamp' => '2024-11-30 10:00:00',
    ),

    // Accumulated variables from previous nodes
    'variables' => array(
        'confirmation_sent' => true,
        'payment_status'    => 'completed',
        'custom_var'        => 'some value',
    ),

    // Outputs from executed nodes
    'node_outputs' => array(
        'node-send-email' => array(
            'status'     => 'sent',
            'message_id' => 'abc123',
        ),
        'node-payment' => array(
            'status'         => 'success',
            'transaction_id' => 'txn_xyz',
            'amount'         => 99.00,
        ),
    ),

    // Track which nodes already executed
    'executed_nodes' => array(
        'node-event-1',
        'node-send-email',
        'node-payment',
        'node-delay',
    ),
);
```

---

### Resume From Node Implementation

The executor needs a method to resume from a saved state:

```php
/**
 * Resume workflow execution from a saved state
 *
 * @param int $state_id Workflow state ID from database
 * @return array Execution result
 */
public static function resume_from_state( $state_id ) {
    global $wpdb;

    // Load state with row locking
    $state = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}superforms_automation_states
         WHERE id = %d AND status = 'pending'
         FOR UPDATE",
        $state_id
    ), ARRAY_A );

    if ( ! $state ) {
        error_log( "Automation state {$state_id} not found or already processed" );
        return array( 'status' => 'skipped', 'reason' => 'State not found or already processed' );
    }

    // Mark as in-progress to prevent duplicate execution
    $wpdb->update(
        $wpdb->prefix . 'superforms_automation_states',
        array( 'status' => 'resumed', 'resumed_at' => current_time( 'mysql' ) ),
        array( 'id' => $state_id )
    );

    // Decode state
    $state_data = json_decode( $state['state_data'], true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        $wpdb->update(
            $wpdb->prefix . 'superforms_automation_states',
            array( 'status' => 'failed', 'last_error' => 'Invalid JSON in state_data' ),
            array( 'id' => $state_id )
        );
        return array( 'status' => 'error', 'message' => 'Invalid state data' );
    }

    // Load automation to get workflow graph
    $automation = SUPER_Automation_DAL::get_automation( $state_data['automation_id'] );

    if ( is_wp_error( $automation ) ) {
        $wpdb->update(
            $wpdb->prefix . 'superforms_automation_states',
            array( 'status' => 'failed', 'last_error' => 'Automation not found' ),
            array( 'id' => $state_id )
        );
        return array( 'status' => 'error', 'message' => 'Automation not found' );
    }

    $workflow_graph = $automation['workflow_graph'];

    // Restore execution context
    $context = array(
        'automation_id'     => $state_data['automation_id'],
        'workflow_graph' => $workflow_graph,
        'variables'      => $state_data['variables'] ?? array(),
        'node_outputs'   => $state_data['node_outputs'] ?? array(),
        'executed_nodes' => $state_data['executed_nodes'] ?? array(),
        'is_resumed'     => true,
        'resumed_from'   => $state_id,
    );

    // Execute from each resume node
    $results = array();
    foreach ( $state_data['resume_from_nodes'] as $node_id ) {
        $node = self::find_node_by_id( $workflow_graph['nodes'], $node_id );

        if ( $node ) {
            $results[ $node_id ] = self::execute_node(
                $node,
                $state_data['event_data'],
                $context,
                $workflow_graph
            );
        }
    }

    return array(
        'status'  => 'completed',
        'results' => $results,
    );
}

/**
 * Find node by ID in nodes array
 */
private static function find_node_by_id( $nodes, $node_id ) {
    foreach ( $nodes as $node ) {
        if ( $node['id'] === $node_id ) {
            return $node;
        }
    }
    return null;
}

/**
 * Get downstream node IDs from connections
 */
private static function get_downstream_node_ids( $node_id, $connections ) {
    $downstream = array();
    foreach ( $connections as $conn ) {
        if ( $conn['from'] === $node_id ) {
            $downstream[] = $conn['to'];
        }
    }
    return $downstream;
}
```

---

### Action Scheduler Hook Registration

```php
// In class-workflow-executor.php or plugin bootstrap

add_action( 'super_automation_resume', array( 'SUPER_Workflow_Executor', 'handle_automation_resume' ) );

/**
 * Handle scheduled workflow resumption
 *
 * @param array $args Action Scheduler args containing state_id
 */
public static function handle_automation_resume( $args ) {
    $state_id = $args['state_id'] ?? 0;

    if ( empty( $state_id ) ) {
        error_log( 'super_automation_resume called without state_id' );
        return;
    }

    $result = self::resume_from_state( $state_id );

    // Log result
    if ( class_exists( 'SUPER_Automation_Logger' ) ) {
        SUPER_Automation_Logger::info(
            sprintf( 'Workflow resumed from state %d', $state_id ),
            $result
        );
    }
}
```

---

### Executor Halt Flag Handling

Update the main executor to respect halt flags:

```php
private static function execute_node( $node, $event_data, &$context, $graph ) {
    $node_id   = $node['id'];
    $node_type = $node['type'];
    $config    = $node['config'] ?? array();

    // Track current node in context
    $context['current_node_id'] = $node_id;

    // Get handler for this node type
    $handler = self::get_node_handler( $node_type );

    if ( ! $handler ) {
        return array( 'status' => 'error', 'message' => "Unknown node type: {$node_type}" );
    }

    // Execute node
    $start_time = microtime( true );
    $result = call_user_func( $handler, $config, $event_data, $context );
    $elapsed = ( microtime( true ) - $start_time ) * 1000;

    // Store output for downstream nodes
    $context['node_outputs'][ $node_id ] = $result;
    $context['executed_nodes'][] = $node_id;

    // Log execution
    SUPER_Automation_DAL::log_execution( array(
        'automation_id'        => $context['automation_id'],
        'event_id'          => $context['event_type'] ?? $node_type,
        'entry_id'          => $event_data['entry_id'] ?? null,
        'form_id'           => $event_data['form_id'] ?? null,
        'status'            => $result['status'] ?? 'unknown',
        'execution_time_ms' => round( $elapsed ),
        'result_data'       => $result,
    ) );

    // ═══════════════════════════════════════════════════════════
    // HALT CHECK - Stop/Delay/Schedule nodes return halt=true
    // ═══════════════════════════════════════════════════════════
    if ( isset( $result['halt'] ) && $result['halt'] === true ) {
        // Do NOT continue to downstream nodes
        // Workflow either stopped or scheduled for later
        return $result;
    }

    // Continue to downstream nodes
    $downstream = self::get_downstream_node_ids( $node_id, $graph['connections'] ?? array() );

    foreach ( $downstream as $next_node_id ) {
        $next_node = self::find_node_by_id( $graph['nodes'], $next_node_id );

        if ( $next_node ) {
            $downstream_result = self::execute_node( $next_node, $event_data, $context, $graph );

            // Propagate halt up the chain
            if ( isset( $downstream_result['halt'] ) && $downstream_result['halt'] === true ) {
                return $downstream_result;
            }
        }
    }

    return $result;
}
```

---

## Performance Optimization: Indexed Lookup Columns

### The Problem (Restated)

Every form submission currently:
1. Fetches ALL automations from database
2. Decodes JSON for each automation
3. Loops through all nodes to find matching events

At 500 automations: 500 JSON decodes per submission = slow.

### Solution: Denormalized Index Columns

Add indexable columns that are auto-populated when saving automations:

```sql
ALTER TABLE wp_superforms_automations
ADD COLUMN event_types_index VARCHAR(500) DEFAULT NULL,  -- JSON array: ["form.submitted"]
ADD COLUMN form_ids_index VARCHAR(500) DEFAULT NULL,     -- JSON array: [123, "all"]
ADD INDEX idx_event_types (event_types_index(100)),
ADD INDEX idx_form_ids (form_ids_index(100));
```

### Auto-Population on Save

Update `SUPER_Automation_DAL::create_automation()` and `update_automation()`:

```php
/**
 * Extract index columns from workflow graph
 *
 * @param mixed $workflow_graph JSON string or array
 * @return array Index column values
 */
private static function extract_index_columns( $workflow_graph ) {
    $graph = is_string( $workflow_graph )
        ? json_decode( $workflow_graph, true )
        : $workflow_graph;

    if ( ! is_array( $graph ) || empty( $graph['nodes'] ) ) {
        return array(
            'event_types_index' => '[]',
            'form_ids_index'    => '[]',
        );
    }

    $event_types = array();
    $form_ids    = array();

    // List of event node types
    $event_node_types = array(
        'form.submitted',
        'form.before_entry_created',
        'entry.updated',
        'entry.deleted',
        'payment.completed',
        'payment.failed',
        'user.registered',
        // Add more as needed
    );

    foreach ( $graph['nodes'] as $node ) {
        $type = $node['type'] ?? '';

        // Check if this is an event node
        if ( in_array( $type, $event_node_types, true ) ) {
            $event_types[] = $type;

            // Extract form scope
            $scope   = $node['config']['scope'] ?? 'current';
            $form_id = $node['config']['formId'] ?? null;

            if ( $scope === 'all' ) {
                $form_ids[] = 'all';
            } elseif ( $form_id ) {
                $form_ids[] = (int) $form_id;
            }
        }
    }

    return array(
        'event_types_index' => wp_json_encode( array_unique( $event_types ) ),
        'form_ids_index'    => wp_json_encode( array_unique( $form_ids ) ),
    );
}
```

### Optimized Query Method

```php
/**
 * Get automations that match a specific event and form
 *
 * @param string $event_type Event type (e.g., 'form.submitted')
 * @param int    $form_id    Form ID
 * @return array Matching automations
 */
public static function get_automations_for_event( $event_type, $form_id ) {
    global $wpdb;

    // Check MySQL version for JSON_CONTAINS support
    $version = $wpdb->db_version();
    $has_json = version_compare( $version, '5.7', '>=' );

    if ( $has_json ) {
        // Fast: Use JSON_CONTAINS (MySQL 5.7+ / MariaDB 10.2+)
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}superforms_automations
             WHERE enabled = 1
             AND JSON_CONTAINS(event_types_index, %s)
             AND (
                 JSON_CONTAINS(form_ids_index, '\"all\"')
                 OR JSON_CONTAINS(form_ids_index, %s)
             )",
            '"' . esc_sql( $event_type ) . '"',
            (string) $form_id
        );
    } else {
        // Fallback: LIKE queries (slower but compatible)
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}superforms_automations
             WHERE enabled = 1
             AND event_types_index LIKE %s
             AND (
                 form_ids_index LIKE '%s'
                 OR form_ids_index LIKE %s
             )",
            '%"' . esc_sql( $event_type ) . '"%',
            '%"all"%',
            '%' . (int) $form_id . '%'
        );
    }

    $results = $wpdb->get_results( $query, ARRAY_A );

    // Decode workflow_graph for each
    foreach ( $results as &$automation ) {
        if ( ! empty( $automation['workflow_graph'] ) ) {
            $automation['workflow_graph'] = json_decode( $automation['workflow_graph'], true );
        }
    }

    return $results;
}
```

### Performance Comparison

| Automations | Before (Fetch All) | After (Indexed) |
|----------|-------------------|-----------------|
| 50       | ~5ms              | ~1ms            |
| 500      | ~50ms             | ~2ms            |
| 5000     | ~500ms            | ~5ms            |

---

## Important Clarification: No Legacy Migration Required

**This is a new automation system.** There are no existing automations to migrate.

The "old schema" references in the Manager class are artifacts from development iteration, not from a previous production system. The cleanup involves:

1. Removing dead code that references non-existent columns
2. Fixing the Manager class to match the actual (new) schema
3. No data migration scripts needed

**All automations will be created fresh** through the visual workflow builder UI.

---

## Related Documentation

- [Phase 23: Production Critical Refinements](23-production-critical-refinements.md) - Security/reliability fixes
- [Phase 22: Visual Workflow Builder](22-integrate-ai-automation-visual-builder.md) - UI that depends on executor
- [Phase 25: Documentation Alignment](25-documentation-alignment-corrections.md) - Architecture consistency
- [README.md](README.md) - Main task overview
