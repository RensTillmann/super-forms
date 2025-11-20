---
name: 01-implement-foundation-registry
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
parent: h-implement-triggers-actions-extensibility
---

# Implement Foundation and Registry System

## Problem/Goal
Establish the core foundation for the extensible triggers/actions system, including database tables, data access layer, base classes, and the registry pattern for add-on extensibility.

## Success Criteria
- [ ] Database tables created automatically on plugin update/install
- [ ] Data Access Layer (DAL) class implemented for CRUD operations
- [ ] Base action class with common functionality
- [ ] Registry system for events and actions registration
- [ ] Backward compatibility maintained with existing trigger data
- [ ] Unit tests for all new classes
- [ ] PHP 7.4+ compatibility maintained

## Implementation Steps

### Step 1: Database Schema & Automatic Table Creation

**File:** `/src/includes/class-install.php`
**Location:** Line ~84 in `create_tables()` method

Add three new tables after existing EAV table creation (line ~116):

1. **super_triggers** - Stores trigger configurations
   - Fields: id, name, event, scope, form_id, form_ids, enabled, execution_order, timestamps
   - Indexes: event, form_id, enabled_event, scope_form

2. **super_trigger_actions** - Stores actions for each trigger
   - Fields: id, trigger_id, action_type, execution_order, enabled, conditions_data, settings_data, i18n_data, timestamps
   - Foreign key: trigger_id references super_triggers(id)
   - Indexes: trigger_id, action_type

3. **super_trigger_execution_log** - Stores execution history
   - Fields: id, trigger_name, action_name, form_id, entry_id, event, executed_at, execution_time_ms, status, error_message, result_data, user_id, scheduled_action_id
   - Indexes: entry_id, form_id, event, status, user_id, executed_at, scheduled_action_id

**Important:** Also update `check_version()` method to trigger table creation on plugin update.

### Step 2: Data Access Layer (DAL) Implementation

**File:** `/src/includes/class-trigger-dal.php` (new file)

Create `SUPER_Trigger_DAL` class with methods:

**Core CRUD Methods:**
- `create_trigger($data)` - Insert new trigger
- `get_trigger($id)` - Retrieve single trigger
- `update_trigger($id, $data)` - Update trigger
- `delete_trigger($id)` - Delete trigger and cascade actions
- `get_triggers_by_event($event, $form_id = null)` - Get active triggers for event

**Action Management:**
- `create_action($trigger_id, $data)` - Add action to trigger
- `update_action($action_id, $data)` - Update action
- `delete_action($action_id)` - Remove action
- `reorder_actions($trigger_id, $order_array)` - Update execution order

**Query Methods:**
- `get_form_triggers($form_id)` - All triggers for a form
- `get_global_triggers()` - All global triggers
- `search_triggers($args)` - Advanced search with pagination

### Step 3: Base Action Class

**File:** `/src/includes/class-trigger-action-base.php` (new file)

Create abstract `SUPER_Trigger_Action_Base` class:

```php
abstract class SUPER_Trigger_Action_Base {
    abstract public function get_id();
    abstract public function get_label();
    abstract public function get_group();
    abstract public function get_settings_schema();
    abstract public function execute($data, $config, $context);

    // Common methods:
    public function supports_scheduling() { return false; }
    public function supports_conditions() { return true; }
    public function validate_config($config) { return true; }
    public function sanitize_config($config) { return $config; }
    public function replace_tags($string, $data, $context) { /* implementation */ }
    public function log_result($success, $message, $data = array()) { /* implementation */ }
}
```

### Step 4: Registry System

**File:** `/src/includes/class-trigger-registry.php` (new file)

Create singleton `SUPER_Trigger_Registry` class:

**Event Registration:**
```php
public function register_event($id, $label, $category = 'General') {
    // Store in $this->events array
    // Allow filtering via 'super_trigger_events' filter
}
```

**Action Registration:**
```php
public function register_action($action_instance) {
    // Validate instance of SUPER_Trigger_Action_Base
    // Store in $this->actions array
    // Allow filtering via 'super_trigger_actions' filter
}
```

**Initialization Hook:**
```php
// In main plugin file, add:
add_action('init', function() {
    $registry = SUPER_Trigger_Registry::instance();
    do_action('super_trigger_register', $registry);
});
```

### Step 5: Backward Compatibility

**File:** `/src/includes/class-trigger-migration.php` (new file)

Create migration class to handle existing trigger data:

1. Read existing triggers from form settings
2. Convert to new database structure
3. Maintain compatibility with existing action hooks
4. Provide fallback for old trigger format

### Step 6: Core Events Registration

Register built-in events in registry:

**Form Events:**
- submission.created
- submission.validated
- submission.saved
- submission.failed

**Entry Events:**
- entry.status_changed
- entry.deleted
- entry.exported

**User Events:**
- user.registered
- user.logged_in
- user.profile_updated

## Context Manifest
<!-- To be added by context-gathering agent -->

## User Notes
- This is the foundation phase - must be rock solid before other phases
- All database operations should use WordPress $wpdb for compatibility
- Follow WordPress coding standards throughout
- Ensure Action Scheduler v3.9.3 compatibility
- Consider multisite compatibility from the start

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Subtask created with detailed implementation steps