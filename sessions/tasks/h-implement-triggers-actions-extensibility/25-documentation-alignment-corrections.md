# Phase 25: Documentation Alignment & Architecture Corrections

**Priority**: HIGH - Documentation drift causing confusion
**Dependencies**: Should be done before implementing Phase 20-24
**Timeline**: 1 day (documentation only)

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

Comprehensive analysis of Phases 20-24 reveals significant **documentation drift** from the actual implemented architecture. This phase documents all misalignments and provides corrections to ensure all subtasks are consistent with the **Node-Level Scope Architecture** that was actually implemented.

---

## Actual Implemented Architecture (Source of Truth)

### Database Schema (Target - after Phase 26 rename)

```sql
-- AUTOMATIONS TABLE (NO scope columns!)
CREATE TABLE wp_superforms_automations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'visual',            -- 'visual' or 'code'
    workflow_graph LONGTEXT,                      -- JSON: {nodes, connections, groups, viewport}
    event_types_index VARCHAR(500) DEFAULT NULL,  -- For fast lookup: ["form.submitted"]
    form_ids_index VARCHAR(500) DEFAULT NULL,     -- For fast lookup: [123, "all"]
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

-- ACTIONS TABLE (for 'code' type only, not visual!)
CREATE TABLE wp_superforms_automation_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_config TEXT,
    execution_order INT DEFAULT 10,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

### Node-Level Scope Architecture (Documented in DAL:6)

```
Scope is configured in event nodes within workflow_graph JSON, NOT at trigger level.

Example workflow_graph:
{
  "nodes": [
    {
      "id": "event-1",
      "type": "form.submitted",
      "config": {
        "scope": "specific",      // ← Scope is HERE, in node config
        "formId": 123             // ← Form filter is HERE
      }
    },
    {
      "id": "action-1",
      "type": "send_email",
      "config": { ... }
    }
  ],
  "connections": [
    {"from": "event-1", "to": "action-1"}
  ]
}
```

### Two Workflow Modes

| Mode | Storage | Use Case |
|------|---------|----------|
| `visual` | `workflow_graph` JSON (nodes/connections) | Drag-and-drop canvas UI |
| `code` | `trigger_actions` table (1:N actions) | Simple list-based triggers |

**Important**: The `trigger_actions` table is ONLY for `workflow_type='code'`. Visual workflows store everything in `workflow_graph` JSON.

---

## Phase 20 Misalignments

### Issue 20.1: Trigger-Level Scope UI (WRONG)

**Location**: Lines 88-91

**Current (WRONG)**:
```
┌─────────────────────────────────────────────────────────────────────────┐
│ Scope: ○ This form ○ All forms ○ Specific                              │
└─────────────────────────────────────────────────────────────────────────┘
```

**Correct**: Scope is configured per EVENT NODE, not at trigger level. The Settings panel should NOT have a scope selector.

**Correction**: Remove scope from trigger Settings panel. Scope is configured in each event node's PropertiesPanel:

```jsx
// In EventNodePropertiesPanel.jsx
<SelectField
  label="Scope"
  value={node.config.scope || 'current'}
  options={[
    { value: 'current', label: 'Current form only' },
    { value: 'all', label: 'All forms' },
    { value: 'specific', label: 'Specific form...' }
  ]}
/>
{node.config.scope === 'specific' && (
  <FormSelector value={node.config.formId} />
)}
```

---

### Issue 20.2: Trigger-Level Event Selector (WRONG)

**Location**: Lines 44-56

**Current (WRONG)**:
```
│ Event: [form.submitted ▼]                                               │
```

**Correct**: Events are NODES in the canvas, not a dropdown on the trigger. A trigger can have MULTIPLE event nodes (multi-trigger workflows).

**Correction**: Remove event dropdown from trigger creation. Events are added as nodes:

```jsx
// Correct: Add event by dragging from palette
<NodePalette>
  <Category name="Events">
    <DraggableNode type="form.submitted" label="Form Submitted" />
    <DraggableNode type="entry.updated" label="Entry Updated" />
    <DraggableNode type="payment.completed" label="Payment Completed" />
  </Category>
</NodePalette>
```

---

### Issue 20.3: Table Reference Ambiguity

**Location**: Line 98

**Current**:
```
Storage: wp_superforms_triggers + wp_superforms_trigger_actions tables
```

**Clarification**: The `trigger_actions` table is ONLY used for `workflow_type='code'`. Visual workflows use `workflow_graph` JSON. Add clarification:

```
Storage:
- Visual mode: wp_superforms_triggers.workflow_graph (JSON nodes/connections)
- Code mode: wp_superforms_triggers + wp_superforms_trigger_actions (1:N)
```

---

## Phase 21 Misalignments

### Issue 21.1: Using Non-Existent DAL Methods

**Location**: Lines 207-243

**Current (BROKEN)**:
```php
$existing = SUPER_Trigger_DAL::get_by_form_and_event( $form_id, 'form.before_entry_created' );
```

**Problem**: `get_by_form_and_event()` doesn't exist! With node-level scope, we can't filter triggers by form at the database level.

**Correction**: Use `get_all_triggers()` and filter by parsing workflow_graph:

```php
// Find triggers that listen to this form
$all_triggers = SUPER_Trigger_DAL::get_all_triggers( true );

foreach ( $all_triggers as $trigger ) {
    $graph = $trigger['workflow_graph'];
    if ( ! is_array( $graph ) || empty( $graph['nodes'] ) ) {
        continue;
    }

    // Find event nodes that match this form
    foreach ( $graph['nodes'] as $node ) {
        if ( $node['type'] !== 'form.before_entry_created' ) {
            continue;
        }

        $scope = $node['config']['scope'] ?? 'current';
        $form_id_filter = $node['config']['formId'] ?? null;

        // Check if this node listens to our form
        if ( $scope === 'all' ) {
            // Matches
        } elseif ( $scope === 'specific' && $form_id_filter == $form_id ) {
            // Matches
        } elseif ( $scope === 'current' && $form_id_filter == $form_id ) {
            // Matches
        }
    }
}
```

---

### Issue 21.2: Using Old Schema Fields

**Location**: Lines 225-232

**Current (WRONG)**:
```php
$trigger_data = array(
    'trigger_name' => $name,
    'event_id' => 'form.before_entry_created',  // ❌ DOESN'T EXIST
    'scope' => 'form',                           // ❌ DOESN'T EXIST
    'scope_id' => $form_id,                      // ❌ DOESN'T EXIST
);
```

**Correction**: Use actual schema with node-level scope:

```php
$trigger_data = array(
    'trigger_name'   => $name,
    'workflow_type'  => 'visual',
    'workflow_graph' => wp_json_encode( array(
        'nodes' => array(
            array(
                'id'     => 'event-1',
                'type'   => 'form.before_entry_created',
                'config' => array(
                    'scope'  => 'specific',
                    'formId' => $form_id,
                ),
                'position' => array( 'x' => 100, 'y' => 100 ),
            ),
            // ... action nodes
        ),
        'connections' => array( /* ... */ ),
    ) ),
    'enabled' => 1,
);

$trigger_id = SUPER_Trigger_DAL::create_trigger( $trigger_data );
```

---

## Phase 22 Alignment Status

Phase 22 is **mostly correct** - it accurately documents node-level scope architecture.

### Minor Clarification Needed

**Location**: Lines 403-410

**Current**:
```
Mode selection at trigger creation (Visual vs List)
Once created, trigger stays in chosen mode forever
Separate code paths: Visual uses `workflow_graph`, List uses `trigger_actions` table
```

**Status**: CORRECT but needs clarity that "List mode" maps to `workflow_type='code'` in database.

---

## Phase 23 Misalignments

### Issue 23.1: Wrong Schema in Code Examples

**Location**: Lines 67-89

**Current (WRONG)**:
```sql
CREATE TABLE wp_superforms_triggers (
    id BIGINT(20) UNSIGNED,
    trigger_name VARCHAR(255) NOT NULL,
    scope VARCHAR(50) NOT NULL,              -- ❌ DOESN'T EXIST
    scope_id BIGINT(20) UNSIGNED,            -- ❌ DOESN'T EXIST
    event_id VARCHAR(100) NOT NULL,          -- ❌ DOESN'T EXIST
    conditions LONGTEXT,                      -- ❌ DOESN'T EXIST
    workflow_type VARCHAR(50) DEFAULT 'visual',
    workflow_graph LONGTEXT,
    enabled TINYINT(1) DEFAULT 1,
    execution_order INT DEFAULT 10,          -- ❌ DOESN'T EXIST
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

**Correction**: Replace with actual schema (see "Actual Implemented Architecture" above).

---

### Issue 23.2: Permission Callback Already Exists

**Location**: Lines 960-1035

**Current**: Shows permission callback implementation as TODO.

**Reality**: `SUPER_Trigger_REST_Controller::check_permission()` already exists (lines 238-272) with:
- API key authentication
- WordPress cookie fallback
- SUPER_Trigger_Permissions integration
- Capability checks

**Correction**: Mark this as ALREADY IMPLEMENTED or verify actual behavior matches spec.

---

## Phase 24 Status

Phase 24 is **accurate** - it correctly identifies the schema mismatch issues.

### Additional Note for Delay Implementation

The delay implementation in Phase 24 needs clarification on how workflow state is serialized:

**Workflow State Must Include**:
```php
$workflow_state = array(
    'trigger_id'        => $trigger_id,
    'workflow_graph'    => $workflow_graph,      // Full graph for reference
    'resume_from_nodes' => $downstream_nodes,    // Where to continue
    'event_data'        => $event_data,          // Original trigger data
    'context'           => array(
        'executed_nodes' => $executed,           // Which nodes already ran
        'node_outputs'   => $outputs,            // Previous action results
        'variables'      => $vars,               // Accumulated variables
    ),
);
```

---

## Delay vs Schedule Nodes

### Delay Node

**Purpose**: Pause workflow execution for a duration, then continue.

**Behavior**:
1. Calculate delay seconds from config
2. Find downstream nodes (what comes after delay)
3. Serialize workflow state to `wp_superforms_workflow_states`
4. Schedule Action Scheduler job
5. Return `{ halt: true }` to stop current execution
6. When AS job runs, resume from downstream nodes

**Config Schema**:
```json
{
  "duration": 5,
  "unit": "minutes"  // seconds, minutes, hours, days
}
```

### Schedule Node

**Purpose**: Execute downstream actions at a specific date/time.

**Behavior**:
1. Parse schedule config (absolute time or field value)
2. Calculate Unix timestamp
3. Same workflow state serialization as Delay
4. Schedule Action Scheduler job for that timestamp
5. Return `{ halt: true }`

**Config Schema**:
```json
{
  "mode": "absolute",  // or "field"
  "datetime": "2024-12-25T10:00:00",
  "fieldName": "appointment_date",  // if mode=field
  "timezone": "America/New_York"
}
```

### Shared Infrastructure

Both nodes use the same infrastructure:

```
┌─────────────────────────────────────────────────────────────────┐
│                    WORKFLOW EXECUTION FLOW                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  [Event] → [Action] → [Delay/Schedule] → [HALT]                 │
│                              │                                   │
│                              ▼                                   │
│                    ┌──────────────────┐                         │
│                    │ workflow_states  │                         │
│                    │ table            │                         │
│                    └────────┬─────────┘                         │
│                              │                                   │
│                              ▼                                   │
│                    ┌──────────────────┐                         │
│                    │ Action Scheduler │                         │
│                    │ (as_schedule_    │                         │
│                    │  single_action)  │                         │
│                    └────────┬─────────┘                         │
│                              │                                   │
│                              │  (after delay/at scheduled time) │
│                              ▼                                   │
│                    ┌──────────────────┐                         │
│                    │ super_workflow_  │                         │
│                    │ resume hook      │                         │
│                    └────────┬─────────┘                         │
│                              │                                   │
│                              ▼                                   │
│  [Continue from saved node] → [Email] → [Update Status] → [End] │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Duplicate Code Analysis

### 1. Manager vs DAL Duplication

**Problem**: `SUPER_Trigger_Manager` duplicates `SUPER_Trigger_DAL` functionality with wrong schema.

**Solution Options**:

| Option | Pros | Cons |
|--------|------|------|
| A. Deprecate Manager | Clean, single source of truth | Breaking change |
| B. Update Manager to wrap DAL | Backwards compatible | Extra layer |
| C. Remove Manager, update callers | Clean, no duplication | More refactoring |

**Recommendation**: Option A (Deprecate Manager)
- REST Controller already uses DAL directly
- Manager has wrong schema anyway
- Add `@deprecated` notices, remove in next major version

---

### 2. Phase 20 vs Phase 22 Overlap

**Problem**: Both phases describe building React trigger UI.

**Analysis**:
- Phase 20: "Triggers V2 UI" - settings panel, basic trigger management
- Phase 22: "AI Automation Visual Builder" - full canvas, drag-and-drop workflow

**Recommendation**: MERGE these phases
- Phase 22 is the comprehensive solution
- Phase 20's scope selector and event dropdown are WRONG for node-level architecture
- Combine into single "Triggers Visual Workflow Builder" phase

**Merged Scope**:
1. React canvas with react-flow
2. Node palette (events, actions, conditions, utilities)
3. PropertiesPanel for node configuration (scope lives here!)
4. TriggerList sidebar
5. Save/load via REST API

---

### 3. trigger_actions Table Confusion

**Problem**: Unclear when `trigger_actions` table is used.

**Clarification**:
```
workflow_type='visual':
  - Actions stored in workflow_graph.nodes[]
  - trigger_actions table NOT used
  - Full graph with connections, positions, etc.

workflow_type='code':
  - Actions stored in trigger_actions table (1:N)
  - workflow_graph is NULL or empty
  - Simple ordered list execution
```

**Recommendation**: Document this clearly in all phases. Consider if 'code' mode is even needed (maybe deprecate for simplicity?).

---

## Corrections Summary

### Files to Update

| File | Changes |
|------|---------|
| `20-implement-triggers-v2-ui.md` | Remove scope/event at trigger level, document node-level scope |
| `21-implement-form-settings-migration.md` | Fix DAL method calls, use actual schema |
| `23-production-critical-refinements.md` | Fix schema examples, note what's already implemented |

### Architecture Decisions to Document

1. **Scope lives in event nodes**, not trigger level
2. **Multiple events per trigger** are supported (multi-trigger workflows)
3. **trigger_actions table** only for `workflow_type='code'`
4. **Delay/Schedule nodes** require `workflow_states` table + Action Scheduler
5. **Manager class** should be deprecated in favor of DAL

---

## Critical Clarification: No Legacy Trigger Migration

**This is a completely new trigger system.** There are no existing triggers to migrate.

### What This Means

1. **No old triggers exist** - The triggers table is new, there's no data to migrate
2. **"Old schema" is dead code** - References to `scope`, `event_id`, `scope_id` columns in the Manager class are artifacts from development iteration, not from a previous production system
3. **Phase 21 is NOT about trigger migration** - It's about converting form builder settings (emails, redirects, etc.) INTO new triggers
4. **No migration scripts needed** - Just delete/fix the dead code

### Phase 21 Clarification

Phase 21 "Form Settings Migration" means:
- Converting legacy form builder email settings → New `send_email` action nodes
- Converting legacy redirect settings → New `redirect` action nodes
- Converting legacy webhooks → New `http_request` action nodes

It does NOT mean:
- ~~Migrating old triggers to new format~~
- ~~Converting old scope columns to JSON~~
- ~~Running data migration scripts~~

---

## Performance Optimization: Indexed Lookup Columns

### The Problem

Because scope is stored in `workflow_graph` JSON, the database cannot filter by form/event:

```php
// Current: O(n) - fetches ALL triggers for every form submission
$all_triggers = SUPER_Trigger_DAL::get_all_triggers( true );

foreach ( $all_triggers as $trigger ) {
    // Decode JSON for EVERY trigger
    $graph = json_decode( $trigger['workflow_graph'], true );

    // Loop through nodes to find matches
    foreach ( $graph['nodes'] as $node ) {
        // Check if this trigger applies...
    }
}
```

### The Solution: Denormalized Index Columns

Add indexed columns that are auto-populated when saving triggers:

```sql
ALTER TABLE wp_superforms_triggers
ADD COLUMN event_types_index VARCHAR(500) DEFAULT NULL,  -- ["form.submitted"]
ADD COLUMN form_ids_index VARCHAR(500) DEFAULT NULL,     -- [123, "all"]
ADD INDEX idx_event_types (event_types_index(100)),
ADD INDEX idx_form_ids (form_ids_index(100));
```

### Auto-Population on Save

When saving a trigger, extract event types and form IDs from the workflow graph:

```php
private static function extract_index_columns( $workflow_graph ) {
    $graph = is_string( $workflow_graph )
        ? json_decode( $workflow_graph, true )
        : $workflow_graph;

    $event_types = array();
    $form_ids = array();

    foreach ( $graph['nodes'] ?? array() as $node ) {
        if ( self::is_event_node( $node['type'] ) ) {
            $event_types[] = $node['type'];

            $scope = $node['config']['scope'] ?? 'current';
            if ( $scope === 'all' ) {
                $form_ids[] = 'all';
            } elseif ( isset( $node['config']['formId'] ) ) {
                $form_ids[] = (int) $node['config']['formId'];
            }
        }
    }

    return array(
        'event_types_index' => wp_json_encode( array_unique( $event_types ) ),
        'form_ids_index'    => wp_json_encode( array_unique( $form_ids ) ),
    );
}
```

### Optimized Query

```php
public static function get_triggers_for_event( $event_type, $form_id ) {
    global $wpdb;

    // MySQL 5.7+ with JSON_CONTAINS
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}superforms_triggers
         WHERE enabled = 1
         AND JSON_CONTAINS(event_types_index, %s)
         AND (
             JSON_CONTAINS(form_ids_index, '\"all\"')
             OR JSON_CONTAINS(form_ids_index, %s)
         )",
        '"' . esc_sql( $event_type ) . '"',
        (string) $form_id
    ), ARRAY_A );
}
```

### Performance Impact

| Triggers | Before (Fetch All) | After (Indexed) |
|----------|-------------------|-----------------|
| 50       | ~5ms              | ~1ms            |
| 500      | ~50ms             | ~2ms            |
| 5000     | ~500ms            | ~5ms            |

---

## Implementation Order (Revised)

Given the alignment issues, here's the recommended implementation order:

1. **Phase 25 (This doc)**: Apply documentation corrections
2. **Phase 24**: Fix Manager schema + column name bug (quick wins)
3. **Phase 22**: Build visual workflow UI (with correct node-level scope)
4. **Phase 21**: Convert form settings to triggers (NOT migration of old triggers!)
5. **Phase 23**: Production refinements (verify what's already done)
6. **Phase 24 cont'd**: Implement delay/schedule with workflow_states

---

## Success Criteria

- [ ] All subtasks reference correct schema (no scope/event_id at trigger level)
- [ ] Phase 20 merged into Phase 22 or corrected
- [ ] Phase 21 clarified as "settings conversion" not "trigger migration"
- [ ] Phase 23 schema examples match reality
- [ ] Delay/Schedule node architecture clearly documented
- [ ] Manager class marked for deprecation
- [ ] No conflicting UI designs (scope always at node level)
- [ ] Performance optimization documented with index columns
