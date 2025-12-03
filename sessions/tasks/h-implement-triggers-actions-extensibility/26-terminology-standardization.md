# Phase 26: Terminology Standardization - "Automations" Naming Convention

**Priority**: HIGH - Foundational for all UI/UX work
**Dependencies**: Should be done before Phase 20/22 UI implementation
**Timeline**: 2-3 days

---

## Overview

Standardize all terminology across the codebase, documentation, database, and UI to use the **Automations** naming convention. This ensures consistency and clarity for both developers and end users.

---

## The Definitive Naming Convention

### UI Context

| Element | Name |
|---------|------|
| **Tab Name** | Automations |
| **Entity (singular)** | Automation |
| **Entity (plural)** | Automations |
| **Create Button** | + Create Automation |
| **Save Button** | Save Automation |
| **Dropdown** | Select an Automation |

### Node Categories (Building Blocks)

| Category | UI Label | What It Does | Examples |
|----------|----------|--------------|----------|
| **Trigger** | "When..." | The event that starts the automation | Form Submitted, User Registered, Payment Completed, Entry Updated |
| **Action** | "Then..." | External task performed by the system | Send Email, Create Entry, HTTP Request, Add Subscriber |
| **Condition** | "If..." | Gateway that splits the path (Yes/No) | Field Comparison, A/B Test |
| **Control** | "Flow..." | Utilities that manipulate time or process | Delay, Schedule, Stop Execution, Set Variable |

### The Sentence Test

Users should be able to read their screen like a sentence:

> "I am building an **Automation**.
> It starts with a **Trigger** (Form Submitted).
> Then, it flows into a **Control** (Delay 1 hour).
> Then, it hits a **Condition** (Is budget > 1000?).
> Finally, it runs an **Action** (Send Email)."

---

## Database Schema Changes

### Table Renames

| Current Name | New Name |
|--------------|----------|
| `wp_superforms_triggers` | `wp_superforms_automations` |
| `wp_superforms_trigger_actions` | `wp_superforms_automation_actions` |
| `wp_superforms_trigger_logs` | `wp_superforms_automation_logs` |
| `wp_superforms_workflow_states` | `wp_superforms_automation_states` |

### Column Renames

**automations table:**

| Current | New |
|---------|-----|
| `trigger_name` | `name` |
| `workflow_type` | `type` (values: 'visual', 'code') |
| `workflow_graph` | `workflow_graph` (keep - describes the graph inside) |

**automation_actions table:**

| Current | New |
|---------|-----|
| `trigger_id` | `automation_id` |

**automation_logs table:**

| Current | New |
|---------|-----|
| `trigger_id` | `automation_id` |

**automation_states table:**

| Current | New |
|---------|-----|
| `trigger_id` | `automation_id` |

### New Schema

```sql
-- Main automations table
CREATE TABLE wp_superforms_automations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'visual',           -- 'visual' or 'code'
    workflow_graph LONGTEXT,                      -- JSON: {nodes, connections}
    event_types_index VARCHAR(500) DEFAULT NULL,  -- For fast lookup: ["form.submitted"]
    form_ids_index VARCHAR(500) DEFAULT NULL,     -- For fast lookup: [123, "all"]
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_enabled (enabled),
    INDEX idx_event_types (event_types_index(100)),
    INDEX idx_form_ids (form_ids_index(100))
) ENGINE=InnoDB;

-- Actions table (for 'code' type only)
CREATE TABLE wp_superforms_automation_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_config TEXT,
    execution_order INT DEFAULT 10,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_automation (automation_id)
) ENGINE=InnoDB;

-- Execution logs
CREATE TABLE wp_superforms_automation_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    entry_id BIGINT UNSIGNED,
    form_id BIGINT UNSIGNED,
    status VARCHAR(50) NOT NULL,
    execution_time_ms INT UNSIGNED,
    result_data LONGTEXT,
    created_at DATETIME NOT NULL,
    INDEX idx_automation (automation_id),
    INDEX idx_entry (entry_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Workflow states (for delay/schedule resumption)
CREATE TABLE wp_superforms_automation_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automation_id BIGINT UNSIGNED NOT NULL,
    entry_id BIGINT UNSIGNED,
    resume_node_id VARCHAR(100) NOT NULL,
    state_data LONGTEXT NOT NULL,
    resume_at DATETIME NOT NULL,
    status ENUM('pending', 'resumed', 'failed', 'cancelled', 'expired') DEFAULT 'pending',
    attempts INT UNSIGNED DEFAULT 0,
    last_error TEXT,
    created_at DATETIME NOT NULL,
    resumed_at DATETIME,
    INDEX idx_resume_pending (status, resume_at),
    INDEX idx_automation (automation_id),
    INDEX idx_entry (entry_id)
) ENGINE=InnoDB;
```

---

## JSON Graph Structure

### Node Structure with Explicit Category

```json
{
  "nodes": [
    {
      "id": "node-1",
      "category": "trigger",
      "type": "form.submitted",
      "config": {
        "scope": "specific",
        "formId": 123
      },
      "position": { "x": 100, "y": 100 }
    },
    {
      "id": "node-2",
      "category": "control",
      "type": "delay",
      "config": {
        "duration": 2,
        "unit": "hours"
      },
      "position": { "x": 300, "y": 100 }
    },
    {
      "id": "node-3",
      "category": "condition",
      "type": "field_comparison",
      "config": {
        "operator": "AND",
        "rules": [
          { "field": "budget", "operator": ">=", "value": "10000" }
        ]
      },
      "position": { "x": 500, "y": 100 }
    },
    {
      "id": "node-4",
      "category": "action",
      "type": "send_email",
      "config": {
        "to": "{email}",
        "subject": "Welcome!"
      },
      "position": { "x": 700, "y": 50 }
    }
  ],
  "connections": [
    { "from": "node-1", "to": "node-2" },
    { "from": "node-2", "to": "node-3" },
    { "from": "node-3", "to": "node-4", "sourceHandle": "true" }
  ]
}
```

### Node Type Registry by Category

```php
// Trigger nodes (events that start automations)
$trigger_types = array(
    'form.submitted',
    'form.before_entry_created',
    'entry.updated',
    'entry.deleted',
    'payment.completed',
    'payment.failed',
    'user.registered',
    'scheduled.time',
);

// Action nodes (tasks that get executed)
$action_types = array(
    'send_email',
    'create_entry',
    'update_entry_status',
    'delete_entry',
    'http_request',
    'run_hook',
    'mailpoet.add_subscriber',
    'mailster.add_subscriber',
    'fluentcrm.add_contact',
);

// Condition nodes (branching logic)
$condition_types = array(
    'field_comparison',
    'ab_test',
    'user_role_check',
    'entry_exists',
);

// Control nodes (flow utilities)
$control_types = array(
    'delay',
    'schedule',
    'stop_execution',
    'set_variable',
    'loop',
);
```

---

## Class Renames

### PHP Classes

| Current | New |
|---------|-----|
| `SUPER_Trigger_DAL` | `SUPER_Automation_DAL` |
| `SUPER_Trigger_Manager` | `SUPER_Automation_Manager` (deprecated) |
| `SUPER_Trigger_REST_Controller` | `SUPER_Automation_REST_Controller` |
| `SUPER_Trigger_Registry` | `SUPER_Automation_Registry` |
| `SUPER_Trigger_Executor` | `SUPER_Automation_Executor` |
| `SUPER_Trigger_Logger` | `SUPER_Automation_Logger` |
| `SUPER_Visual_Workflow_Executor` | `SUPER_Workflow_Executor` |
| `SUPER_Trigger_Settings_Migration` | `SUPER_Settings_To_Automation_Converter` |

### File Renames

| Current | New |
|---------|-----|
| `class-trigger-dal.php` | `class-automation-dal.php` |
| `class-trigger-manager.php` | `class-automation-manager.php` |
| `class-trigger-rest-controller.php` | `class-automation-rest-controller.php` |
| `class-trigger-registry.php` | `class-automation-registry.php` |
| `class-trigger-executor.php` | `class-automation-executor.php` |
| `class-visual-workflow-executor.php` | `class-workflow-executor.php` |

---

## REST API Endpoints

### Current → New

| Current | New |
|---------|-----|
| `GET /super-forms/v1/triggers` | `GET /super-forms/v1/automations` |
| `POST /super-forms/v1/triggers` | `POST /super-forms/v1/automations` |
| `GET /super-forms/v1/triggers/{id}` | `GET /super-forms/v1/automations/{id}` |
| `PUT /super-forms/v1/triggers/{id}` | `PUT /super-forms/v1/automations/{id}` |
| `DELETE /super-forms/v1/triggers/{id}` | `DELETE /super-forms/v1/automations/{id}` |
| `POST /super-forms/v1/triggers/{id}/execute` | `POST /super-forms/v1/automations/{id}/execute` |

---

## Action Scheduler Hooks

| Current | New |
|---------|-----|
| `super_workflow_resume` | `super_automation_resume` |
| `super_trigger_execute` | `super_automation_execute` |

---

## UI Components (React)

### Component Renames

| Current | New |
|---------|-----|
| `TriggerList` | `AutomationList` |
| `TriggerEditor` | `AutomationEditor` |
| `TriggerCanvas` | `WorkflowCanvas` |
| `TriggerPropertiesPanel` | `NodePropertiesPanel` |

### Node Palette Structure

```jsx
<NodePalette>
  <Category name="Triggers" icon={Zap}>
    <DraggableNode category="trigger" type="form.submitted" label="Form Submitted" />
    <DraggableNode category="trigger" type="entry.updated" label="Entry Updated" />
    <DraggableNode category="trigger" type="payment.completed" label="Payment Completed" />
  </Category>

  <Category name="Actions" icon={Play}>
    <DraggableNode category="action" type="send_email" label="Send Email" />
    <DraggableNode category="action" type="create_entry" label="Create Entry" />
    <DraggableNode category="action" type="http_request" label="HTTP Request" />
  </Category>

  <Category name="Conditions" icon={GitBranch}>
    <DraggableNode category="condition" type="field_comparison" label="If / Else" />
    <DraggableNode category="condition" type="ab_test" label="A/B Test" />
  </Category>

  <Category name="Control" icon={Clock}>
    <DraggableNode category="control" type="delay" label="Delay" />
    <DraggableNode category="control" type="schedule" label="Schedule" />
    <DraggableNode category="control" type="stop_execution" label="Stop" />
  </Category>
</NodePalette>
```

---

## Migration Strategy

### Phase 26.1: Documentation Update (Day 1)

1. Update all task files (README, Phase 20-25) with new terminology
2. Update CLAUDE.md files with terminology reference
3. Create terminology glossary for developers

### Phase 26.2: Database Migration (Day 2)

```php
class SUPER_Terminology_Migration {

    public static function migrate() {
        global $wpdb;

        // 1. Rename tables
        $wpdb->query( "RENAME TABLE {$wpdb->prefix}superforms_triggers TO {$wpdb->prefix}superforms_automations" );
        $wpdb->query( "RENAME TABLE {$wpdb->prefix}superforms_trigger_actions TO {$wpdb->prefix}superforms_automation_actions" );
        $wpdb->query( "RENAME TABLE {$wpdb->prefix}superforms_trigger_logs TO {$wpdb->prefix}superforms_automation_logs" );

        // 2. Rename columns in automations table
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automations CHANGE trigger_name name VARCHAR(255) NOT NULL" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automations CHANGE workflow_type type VARCHAR(50) DEFAULT 'visual'" );

        // 3. Rename columns in related tables
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automation_actions CHANGE trigger_id automation_id BIGINT UNSIGNED NOT NULL" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automation_logs CHANGE trigger_id automation_id BIGINT UNSIGNED NOT NULL" );

        // 4. Add index columns if not exist
        self::add_index_columns();

        // 5. Update version
        update_option( 'super_forms_terminology_version', '1.0' );
    }

    private static function add_index_columns() {
        global $wpdb;

        // Check if columns exist
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}superforms_automations" );

        if ( ! in_array( 'event_types_index', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automations ADD COLUMN event_types_index VARCHAR(500) DEFAULT NULL" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automations ADD INDEX idx_event_types (event_types_index(100))" );
        }

        if ( ! in_array( 'form_ids_index', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automations ADD COLUMN form_ids_index VARCHAR(500) DEFAULT NULL" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}superforms_automations ADD INDEX idx_form_ids (form_ids_index(100))" );
        }
    }
}
```

### Phase 26.3: Code Refactoring (Day 2-3)

1. Create new class files with updated names
2. Update all internal references
3. Update REST API routes
4. Update Action Scheduler hooks

---

## Success Criteria

- [x] All documentation uses "Automation" for container, "Trigger" for event nodes
- [x] Database tables renamed to `superforms_automations`
- [x] PHP classes renamed to `SUPER_Automation_*`
- [x] REST API endpoints use `/automations`
- [x] Node categories clearly defined: trigger, action, condition, control
- [x] UI components use consistent terminology
- [x] No references to old "trigger" terminology for container

---

## Glossary

| Term | Definition |
|------|------------|
| **Automation** | The saved entity containing a workflow. What users create, name, and enable/disable. |
| **Workflow** | The graph of nodes and connections inside an automation. |
| **Trigger** | A node type that starts the automation (the "when"). Events like form submission. |
| **Action** | A node type that performs a task (the "what"). Like sending email. |
| **Condition** | A node type that branches the flow (the "if"). Evaluates to true/false. |
| **Control** | A node type that manipulates flow (delay, schedule, stop). |
| **Node** | A single element in the workflow graph. Has a category and type. |
| **Connection** | A link between two nodes defining execution flow. |

---

---

## Context Manifest

### Discovered During Implementation
[Date: 2025-11-30 / Phase 26 completion]

During Phase 26 implementation, we discovered several important architectural patterns and migration requirements that weren't fully documented in the original context. The terminology standardization work involved:

**Database Migration Complexity:** The migration from `triggers` to `automations` terminology was more extensive than initially documented. We renamed 4 main database tables (`wp_superforms_triggers` → `wp_superforms_automations`, etc.) and updated 15+ foreign key columns across these tables. This required careful SQL migration scripts to preserve existing data while updating the schema.

**PHP Class File System Impact:** 19 PHP class files were renamed and moved from `/src/includes/triggers/` to `/src/includes/automations/` directory. This wasn't just a simple find-replace - it required updating class inheritance, autoloading patterns, and internal references. The `SUPER_Trigger_*` classes became `SUPER_Automation_*` classes with consistent naming patterns.

**Legacy Function Preservation:** During the class renames, we discovered that several legacy functions (like `execute_scheduled_trigger_actions()` in `SUPER_Automations` class) still use old terminology in their internal implementation. This was intentionally preserved to maintain backward compatibility with scheduled actions and hooks that may be stored in the database or used by external integrations.

**REST API Endpoint Consistency:** All REST API endpoints were successfully updated from `/v1/triggers` to `/v1/automations` with proper route registration updates. The controller class (`SUPER_Automation_REST_Controller`) maintains the same interface patterns, ensuring API client compatibility.

**React UI Component Integration:** While the React admin UI components were updated, we discovered that the component architecture is actually more unified than initially understood. The admin UI uses a single bundle approach (`/src/react/admin/`) rather than separate apps, which simplified the terminology updates across components.

**Documentation Update Cascade:** The terminology changes created a cascade effect across all documentation files. We discovered that CLAUDE.md, javascript.md, and other documentation files needed updates to reflect the new "Automations" terminology while preserving "Trigger" as a node category within automations.

During implementation, we discovered that the terminology standardization was more than just renaming - it was establishing a new mental model where "Automation" is the container/workflow entity and "Trigger" is a specific type of node within that workflow. This distinction wasn't as clearly documented in the original planning and became crucial during the React UI component updates.

#### Updated Technical Details
- Database migration scripts must handle both table renames and foreign key column updates
- PHP class autoloading requires careful attention during namespace changes
- REST API route updates need proper WordPress hook registration changes
- React component terminology should distinguish between container (Automation) and node types (Trigger, Action, etc.)
- Legacy function names may be preserved for backward compatibility with scheduled tasks
- File system organization changed from `/triggers/` to `/automations/` directory structure

### Post-Implementation Audit Discoveries (2025-12-02)

During a post-completion audit, we discovered **critical runtime errors** that had been silently failing since the terminology migration:

**1. Duplicate Event Firing**
The new automation system (`SUPER_Automation_Executor::fire_event()`) was already integrated at `class-ajax.php:6216`. However, the legacy `triggerEvent()` method was still being called at line 6229 as a duplicate. This meant every form submission was attempting to fire events twice - once successfully through the new system, and once through a broken legacy path.

**2. Undefined Function Stubs (Fatal Error Risk)**
Three functions were being called throughout the codebase but **never actually defined anywhere**:
- `get_form_triggers()` - Called in 3 locations
- `save_form_triggers()` - Called in 5 locations
- `add_emails_as_trigger()` - Called in 1 location

These would cause PHP fatal errors if execution reached these code paths. The functions existed as *conceptual stubs* in comments but were never implemented.

**3. Broken Email Migration (Silent Failure)**
The most critical discovery: The `SUPER_Email_Trigger_Migration` class was renamed to `SUPER_Email_Automation_Migration` during Phase 26, but **all 15 callers across 4 files** still checked for `class_exists('SUPER_Email_Trigger_Migration')`. This condition always evaluated to `false`, causing:
- Email migration to never start
- Background migration status checks to fail
- Migration UI to show incorrect state
- Legacy email settings to never convert to automations

This was a **silent failure** - no errors were logged, the system simply skipped email migration entirely.

**4. Form Duplication Broken**
Form duplication logic in `super-forms.php` attempted to copy "triggers" using the undefined `get_form_triggers()` and `save_form_triggers()` functions. This would cause fatal errors when users tried to duplicate forms.

#### Resolution Pattern
The correct approach for terminology migrations:
1. **Rename files and classes first**
2. **Update ALL callers using automated find-replace** (regex: `SUPER_Email_Trigger_Migration` → `SUPER_Email_Automation_Migration`)
3. **Remove function stubs that were never implemented** rather than attempting to implement them
4. **Check for duplicate integration points** - the new system was already integrated, making legacy calls redundant

#### Files That Required Post-Migration Fixes
- `class-ajax.php` - Removed duplicate event firing, removed 2 broken function calls, updated 4 class references
- `class-common.php` - Removed entire 80-line `triggerEvent()` method, replaced 2 undefined function calls with empty arrays, updated 4 class references
- `class-pages.php` - Replaced undefined function call with empty array
- `super-forms.php` - Removed 4 lines calling undefined functions in form duplication
- `class-migration-manager.php` - Updated 3 class name references
- `class-background-migration.php` - Updated 4 class name references

---

## Work Log

### 2025-12-02: Legacy Code Cleanup (Post-completion audit)

During a follow-up audit, we discovered and fixed several legacy "trigger" references that were still causing issues:

**Broken Function Stubs Removed:**
- `get_form_triggers()` - Called but never defined (replaced calls with `array()`)
- `save_form_triggers()` - Called but never defined (removed all calls)
- `add_emails_as_trigger()` - Called but never defined (removed)

**Legacy Method Removed:**
- Removed entire `triggerEvent()` method from `class-common.php` (~80 lines)
- This method referenced missing `class-triggers.php` file
- Discovered the new `SUPER_Automation_Executor::fire_event()` was already being called, making the legacy call a duplicate

**Critical Bug: Broken Email Migration System**
- The class was renamed from `SUPER_Email_Trigger_Migration` to `SUPER_Email_Automation_Migration` but ALL callers still referenced the old name
- This meant `class_exists('SUPER_Email_Trigger_Migration')` always returned false
- **The entire email migration system was silently failing** - no emails were being migrated to automations
- Updated all references to `SUPER_Email_Automation_Migration` in:
  - `class-ajax.php` (4 occurrences)
  - `class-common.php` (4 occurrences)
  - `class-migration-manager.php` (3 occurrences)
  - `class-background-migration.php` (4 occurrences)

**Files Modified:**
- `/src/includes/class-ajax.php` - Removed broken function calls, fixed class references
- `/src/includes/class-common.php` - Removed broken methods/calls, fixed class references
- `/src/includes/class-pages.php` - Replaced broken `get_form_triggers()` with empty array
- `/src/super-forms.php` - Removed broken function calls from form duplication logic
- `/src/includes/class-migration-manager.php` - Fixed class name reference
- `/src/includes/class-background-migration.php` - Fixed class name references (4 occurrences)

---

## Related Documentation

- [Phase 24: Trigger System Cleanup](24-trigger-system-refactoring-cleanup.md) - Technical debt fixes
- [Phase 25: Documentation Alignment](25-documentation-alignment-corrections.md) - Architecture consistency
- [Phase 22: Visual Workflow Builder](22-integrate-ai-automation-visual-builder.md) - UI implementation
