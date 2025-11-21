# Phase 1 Architecture Blueprint - Complete Implementation Strategy

## Ultra-Deep Analysis Summary

This document provides the complete architectural blueprint for Phase 1 implementation based on ultra-deep analysis of the codebase, confirmed architectural decisions, and comprehensive event mapping.

---

## 1. Executive Summary

### Architectural Decisions (LOCKED IN)

| Decision | Choice | Rationale |
|----------|--------|-----------|
| **Migration** | No backward compatibility needed | Unreleased feature, clean slate |
| **Event Mapping** | Map all 18 events before coding | Prevent merge conflicts, ensure completeness |
| **Conditions Engine** | Simple (AND/OR/NOT only) | Can't test complex features without UI |
| **Data Storage** | Dual storage (entry data + logs) | User-facing + admin debugging |
| **Database Schema** | Normalized (separate actions table) | Better for async execution (Phase 2) |
| **Scope System** | Full system (form/global/user/role/site/network) | Hard to change DB schema later |
| **REST API Namespace** | `/super-forms/v1/` | First REST API in plugin, future-proof |
| **Permissions** | Simple (`manage_options`) with abstraction | Easy to enhance in Phase 1.5 |

### Implementation Statistics

- **18 Events** mapped across 3 categories (form: 5, entry: 5, file: 3, payment stubs: 5)
- **3 Database Tables** with 24 indexed columns
- **7 Core Classes** (DAL, Manager, Registry, Conditions, Executor, Action Base, REST Controller)
- **8 Integration Points** in class-ajax.php for event firing
- **80%+ Test Coverage** target for critical paths

---

## 2. Database Architecture

### Schema Design Philosophy
- **Normalized for flexibility**: Separate actions table enables per-action status tracking
- **Scope-first**: Every trigger has scope (form/global/user/role/site/network)
- **Future-proof**: Unused columns now > ALTER TABLE in production later
- **Performance-focused**: Strategic indexes for common queries

### Table 1: `wp_superforms_triggers`

```sql
CREATE TABLE {$wpdb->prefix}superforms_triggers (
  -- Primary Key
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Core Fields
  trigger_name VARCHAR(255) NOT NULL,
  event_id VARCHAR(100) NOT NULL,              -- 'form.submitted', 'payment.completed'

  -- Scope System (FULL IMPLEMENTATION)
  scope VARCHAR(50) NOT NULL DEFAULT 'form',   -- 'form', 'global', 'user', 'role', 'site', 'network'
  scope_id BIGINT(20),                         -- form_id, user_id, blog_id (NULL for global/role/network)

  -- Configuration
  conditions TEXT,                             -- JSON: simplified AND/OR/NOT structure
  enabled TINYINT(1) DEFAULT 1,
  execution_order INT DEFAULT 10,

  -- Metadata
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  -- Indexes
  PRIMARY KEY (id),
  KEY scope_lookup (scope, scope_id, enabled),         -- Quick scope-based queries
  KEY event_lookup (event_id, enabled),                 -- Event matching
  KEY form_triggers (scope, scope_id) USING BTREE      -- Form-specific triggers

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Notes**:
- `scope` + `scope_id` combination enables flexible trigger targeting
- `execution_order` allows priority sorting (lower = earlier)
- `conditions` stores simplified JSON (no XOR/NAND/NOR in Phase 1)
- Composite indexes optimize the most common query patterns

### Table 2: `wp_superforms_trigger_actions`

```sql
CREATE TABLE {$wpdb->prefix}superforms_trigger_actions (
  -- Primary Key
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Relationships
  trigger_id BIGINT(20) UNSIGNED NOT NULL,

  -- Action Definition
  action_type VARCHAR(100) NOT NULL,           -- 'http.request', 'email.send', 'webhook.call'
  action_config TEXT,                          -- JSON: action-specific configuration
  execution_order INT DEFAULT 10,
  enabled TINYINT(1) DEFAULT 1,

  -- Metadata
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  -- Indexes
  PRIMARY KEY (id),
  KEY trigger_id (trigger_id),
  KEY action_type (action_type),
  KEY trigger_order (trigger_id, execution_order),     -- Ordered action execution

  -- Foreign Key Constraint
  FOREIGN KEY (trigger_id)
    REFERENCES {$wpdb->prefix}superforms_triggers(id)
    ON DELETE CASCADE                                   -- Delete actions when trigger deleted

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Notes**:
- Normalized design: 1 trigger → N actions
- Cascade delete: Removing trigger removes all its actions
- Per-action `enabled` flag for selective execution
- Phase 2 will add async execution tracking here

### Table 3: `wp_superforms_trigger_logs`

```sql
CREATE TABLE {$wpdb->prefix}superforms_trigger_logs (
  -- Primary Key
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Relationships
  trigger_id BIGINT(20) UNSIGNED NOT NULL,
  action_id BIGINT(20) UNSIGNED,               -- NULL = trigger-level log
  entry_id BIGINT(20) UNSIGNED,
  form_id BIGINT(20) UNSIGNED,

  -- Execution Details
  event_id VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL,                 -- 'success', 'failed', 'pending'
  error_message TEXT,
  execution_time_ms INT,                       -- Performance tracking

  -- Data
  context_data LONGTEXT,                       -- JSON: event context
  result_data LONGTEXT,                        -- JSON: action result

  -- Audit Trail
  user_id BIGINT(20) UNSIGNED,
  scheduled_action_id BIGINT(20) UNSIGNED,     -- Action Scheduler ID (Phase 2)
  executed_at DATETIME NOT NULL,

  -- Indexes
  PRIMARY KEY (id),
  KEY trigger_id (trigger_id),
  KEY entry_id (entry_id),
  KEY form_id (form_id),
  KEY status (status),
  KEY executed_at (executed_at),
  KEY form_status (form_id, status)            -- Admin dashboard queries

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Notes**:
- Dual-purpose: Trigger-level logs AND action-level logs
- Performance tracking: `execution_time_ms` for optimization
- Comprehensive audit: Who, what, when, why for compliance
- JSON storage: Flexible for varying event/action data

---

## 3. Class Architecture

### Overview Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                  WordPress / Super Forms                     │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        │ Events (form submission, etc.)
                        ▼
        ┌───────────────────────────────┐
        │   SUPER_Trigger_Registry      │ ◄── Add-ons register events/actions
        │   - register_event()          │
        │   - register_action()         │
        │   - fire_event()              │
        └──────────┬────────────────────┘
                   │
                   │ fire_event()
                   ▼
        ┌───────────────────────────────┐
        │   SUPER_Trigger_Manager       │
        │   - resolve_triggers()        │
        │   - validate_data()           │
        │   - check_permissions()       │
        └──────────┬────────────────────┘
                   │
                   │ get_triggers_for_event()
                   ▼
        ┌───────────────────────────────┐
        │   SUPER_Trigger_DAL           │
        │   - get_triggers_by_scope()   │
        │   - get_actions()             │
        │   - log_execution()           │
        └──────────┬────────────────────┘
                   │
                   │ Database queries
                   ▼
        ┌───────────────────────────────┐
        │   MySQL Tables                │
        │   - triggers                  │
        │   - trigger_actions           │
        │   - trigger_logs              │
        └───────────────────────────────┘

Execution Flow:
────────────────
Registry → Executor → Conditions Engine → Action Instances → Logger
           │          │                   │
           ├─────────►│ evaluate()        │
           │          └──────────►        │
           │                      ├─execute()
           └──────────────────────┴─log_result()
```

### Class Responsibilities

#### 1. **SUPER_Trigger_Registry** (Singleton)
- **Purpose**: Central registration point for events and actions
- **Pattern**: Singleton with static methods
- **File**: `/src/includes/class-trigger-registry.php`

```php
class SUPER_Trigger_Registry {
    private static $instance = null;
    private static $events = array();
    private static $actions = array();

    // Singleton
    public static function instance() { ... }

    // Event Management
    public static function register_event($event_id, $args) { ... }
    public static function get_event($event_id) { ... }
    public static function get_all_events() { ... }

    // Action Management
    public static function register_action(SUPER_Trigger_Action_Base $action_instance) { ... }
    public static function get_action($action_type) { ... }
    public static function get_all_actions() { ... }

    // Event Firing
    public static function fire_event($event_id, $context) {
        // Fire WordPress actions for extensions
        do_action('super_trigger_event_' . $event_id, $context);
        do_action('super_trigger_event', $event_id, $context);

        // Execute matching triggers
        if (class_exists('SUPER_Trigger_Executor')) {
            return SUPER_Trigger_Executor::execute_event($event_id, $context);
        }
        return array();
    }
}
```

**Key Design Decisions**:
- Singleton pattern matches `SUPER_Trigger_Registry::instance()` convention
- Static methods for easy access: `SUPER_Trigger_Registry::fire_event()`
- Fires WordPress `do_action()` hooks so extensions can listen
- Event/action arrays filterable via `apply_filters()`

#### 2. **SUPER_Trigger_DAL** (Static)
- **Purpose**: Database abstraction layer with scope-aware queries
- **Pattern**: Static methods (matches `SUPER_Data_Access`)
- **File**: `/src/includes/class-trigger-dal.php`

```php
class SUPER_Trigger_DAL {

    // ─────────────────────────────────────────────────────────
    // TRIGGER CRUD
    // ─────────────────────────────────────────────────────────

    /**
     * Create new trigger
     * @return int|WP_Error Trigger ID or error
     */
    public static function create_trigger($data) {
        global $wpdb;

        // Validate required fields
        if (empty($data['trigger_name']) || empty($data['event_id'])) {
            return new WP_Error('invalid_data', __('Trigger name and event ID required', 'super-forms'));
        }

        // Set defaults
        $data = wp_parse_args($data, array(
            'scope' => 'form',
            'scope_id' => null,
            'conditions' => '',
            'enabled' => 1,
            'execution_order' => 10
        ));

        $result = $wpdb->insert(
            $wpdb->prefix . 'superforms_triggers',
            array(
                'trigger_name' => sanitize_text_field($data['trigger_name']),
                'event_id' => sanitize_text_field($data['event_id']),
                'scope' => sanitize_text_field($data['scope']),
                'scope_id' => !empty($data['scope_id']) ? absint($data['scope_id']) : null,
                'conditions' => is_array($data['conditions']) ? wp_json_encode($data['conditions']) : $data['conditions'],
                'enabled' => absint($data['enabled']),
                'execution_order' => absint($data['execution_order']),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s')
        );

        if (false === $result) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    // ─────────────────────────────────────────────────────────
    // SCOPE-AWARE QUERIES (KEY FUNCTIONALITY)
    // ─────────────────────────────────────────────────────────

    /**
     * Get all triggers for a specific scope
     * Examples:
     *   get_triggers_by_scope('form', 123)  → Form #123 triggers
     *   get_triggers_by_scope('global')     → All global triggers
     *   get_triggers_by_scope('user', 5)    → User #5 triggers
     */
    public static function get_triggers_by_scope($scope, $scope_id = null, $enabled_only = true) {
        global $wpdb;

        $where_clauses = array('scope = %s');
        $where_values = array($scope);

        if ($scope_id !== null) {
            $where_clauses[] = 'scope_id = %d';
            $where_values[] = absint($scope_id);
        } else {
            $where_clauses[] = 'scope_id IS NULL';
        }

        if ($enabled_only) {
            $where_clauses[] = 'enabled = 1';
        }

        $where = implode(' AND ', $where_clauses);

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}superforms_triggers
             WHERE {$where}
             ORDER BY execution_order ASC, id ASC",
            $where_values
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get all active triggers that apply to a given context
     * This is the CORE query used during event firing
     *
     * Logic:
     * 1. Get form-specific triggers (scope='form', scope_id=form_id)
     * 2. Get global triggers (scope='global')
     * 3. Get user-specific triggers (scope='user', scope_id=user_id)
     * 4. Get role-based triggers (scope='role', conditions check user role)
     * 5. Merge and sort by execution_order
     */
    public static function get_active_triggers_for_context($event_id, $context) {
        global $wpdb;

        $all_triggers = array();

        // 1. Form-specific triggers
        if (!empty($context['form_id'])) {
            $form_triggers = self::get_triggers_by_event($event_id, 'form', $context['form_id']);
            $all_triggers = array_merge($all_triggers, $form_triggers);
        }

        // 2. Global triggers
        $global_triggers = self::get_triggers_by_event($event_id, 'global');
        $all_triggers = array_merge($all_triggers, $global_triggers);

        // 3. User-specific triggers
        if (!empty($context['user_id'])) {
            $user_triggers = self::get_triggers_by_event($event_id, 'user', $context['user_id']);
            $all_triggers = array_merge($all_triggers, $user_triggers);
        }

        // 4. Role-based triggers (Phase 1.5 - deferred)
        // TODO: Implement role-based trigger resolution

        // Sort by execution_order
        usort($all_triggers, function($a, $b) {
            return absint($a['execution_order']) - absint($b['execution_order']);
        });

        return $all_triggers;
    }

    // ─────────────────────────────────────────────────────────
    // ACTION CRUD
    // ─────────────────────────────────────────────────────────

    public static function create_action($trigger_id, $data) { ... }
    public static function get_actions($trigger_id, $enabled_only = true) { ... }
    public static function update_action($action_id, $data) { ... }
    public static function delete_action($action_id) { ... }

    // ─────────────────────────────────────────────────────────
    // LOGGING
    // ─────────────────────────────────────────────────────────

    /**
     * Log trigger/action execution
     * Supports both trigger-level and action-level logging
     */
    public static function log_execution($log_data) {
        global $wpdb;

        $defaults = array(
            'action_id' => null,
            'entry_id' => null,
            'form_id' => null,
            'status' => 'success',
            'error_message' => '',
            'execution_time_ms' => 0,
            'context_data' => '',
            'result_data' => '',
            'user_id' => get_current_user_id(),
            'scheduled_action_id' => null
        );

        $log_data = wp_parse_args($log_data, $defaults);

        // Dual storage: Log to database + entry meta
        $log_id = $wpdb->insert(
            $wpdb->prefix . 'superforms_trigger_logs',
            array(
                'trigger_id' => absint($log_data['trigger_id']),
                'action_id' => !empty($log_data['action_id']) ? absint($log_data['action_id']) : null,
                'entry_id' => !empty($log_data['entry_id']) ? absint($log_data['entry_id']) : null,
                'form_id' => !empty($log_data['form_id']) ? absint($log_data['form_id']) : null,
                'event_id' => sanitize_text_field($log_data['event_id']),
                'status' => sanitize_text_field($log_data['status']),
                'error_message' => sanitize_textarea_field($log_data['error_message']),
                'execution_time_ms' => absint($log_data['execution_time_ms']),
                'context_data' => is_array($log_data['context_data']) ? wp_json_encode($log_data['context_data']) : $log_data['context_data'],
                'result_data' => is_array($log_data['result_data']) ? wp_json_encode($log_data['result_data']) : $log_data['result_data'],
                'user_id' => absint($log_data['user_id']),
                'scheduled_action_id' => !empty($log_data['scheduled_action_id']) ? absint($log_data['scheduled_action_id']) : null,
                'executed_at' => current_time('mysql')
            )
        );

        // Also store in entry data via Data Access Layer
        if (!empty($log_data['entry_id'])) {
            SUPER_Data_Access::update_entry_data(
                $log_data['entry_id'],
                array(
                    '_super_last_trigger_execution' => array(
                        'log_id' => $wpdb->insert_id,
                        'trigger_id' => $log_data['trigger_id'],
                        'status' => $log_data['status'],
                        'timestamp' => current_time('mysql')
                    )
                )
            );
        }

        return $wpdb->insert_id;
    }
}
```

**Key Design Decisions**:
- **Scope-aware queries**: Core differentiator from simple systems
- **WP_Error returns**: Consistent with WordPress conventions
- **Dual storage**: Logs table + entry meta for different use cases
- **Prepared statements**: SQL injection prevention
- **Data Access Layer integration**: Uses `SUPER_Data_Access` for entry data

---

## 4. Simplified Conditions Engine

### Architecture Decision: Keep It Simple

**Phase 1 Operators**:
- ✅ AND, OR, NOT (logical grouping)
- ✅ `=`, `!=`, `>`, `<`, `>=`, `<=` (comparisons)
- ✅ `contains`, `starts_with`, `ends_with` (string matching)
- ✅ `empty`, `not_empty` (existence checks)
- ✅ `in` (list membership)

**Deferred to Phase 1.5**:
- ❌ XOR, NAND, NOR (advanced logic)
- ❌ `regex` (pattern matching)
- ❌ `custom` (PHP evaluation)
- ❌ `between` (range checks)
- ❌ Type casting (string, int, float, date)

### Condition Structure (JSON)

```json
{
  "operator": "AND",
  "groups": [
    {
      "operator": "OR",
      "conditions": [
        {
          "field": "{email}",
          "operator": "contains",
          "value": "@gmail.com"
        },
        {
          "field": "{email}",
          "operator": "contains",
          "value": "@yahoo.com"
        }
      ]
    }
  ],
  "conditions": [
    {
      "field": "{country}",
      "operator": "=",
      "value": "US"
    }
  ]
}
```

**Evaluation Logic**: `(email contains @gmail.com OR email contains @yahoo.com) AND country = US`

### Implementation: `SUPER_Trigger_Conditions`

```php
class SUPER_Trigger_Conditions {

    /**
     * Evaluate condition structure
     * @param array|string $conditions Condition structure or JSON string
     * @param array $context Event context data
     * @return bool
     */
    public static function evaluate($conditions, $context) {
        if (empty($conditions)) {
            return true; // No conditions = always true
        }

        if (is_string($conditions)) {
            $conditions = json_decode($conditions, true);
        }

        // Handle group
        if (isset($conditions['operator'])) {
            return self::evaluate_group($conditions, $context);
        }

        // Handle single condition
        return self::evaluate_single($conditions, $context);
    }

    /**
     * Evaluate condition group with AND/OR/NOT logic
     */
    private static function evaluate_group($group, $context) {
        $operator = strtoupper($group['operator'] ?? 'AND');
        $results = array();

        // Evaluate sub-groups
        if (!empty($group['groups'])) {
            foreach ($group['groups'] as $subgroup) {
                $results[] = self::evaluate_group($subgroup, $context);
            }
        }

        // Evaluate individual conditions
        if (!empty($group['conditions'])) {
            foreach ($group['conditions'] as $condition) {
                $results[] = self::evaluate_single($condition, $context);
            }
        }

        // Apply operator
        switch ($operator) {
            case 'AND':
                return !in_array(false, $results, true);

            case 'OR':
                return in_array(true, $results, true);

            case 'NOT':
                // NOT inverts AND result
                return !(!in_array(false, $results, true));

            default:
                return false;
        }
    }

    /**
     * Evaluate single condition
     */
    private static function evaluate_single($condition, $context) {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';

        // Replace {tags} in field name
        $field_value = self::get_field_value($field, $context);

        // Evaluate based on operator
        switch ($operator) {
            case '=':
            case '==':
                return $field_value == $value;

            case '!=':
            case '<>':
                return $field_value != $value;

            case '>':
                return floatval($field_value) > floatval($value);

            case '<':
                return floatval($field_value) < floatval($value);

            case '>=':
                return floatval($field_value) >= floatval($value);

            case '<=':
                return floatval($field_value) <= floatval($value);

            case 'contains':
                return stripos($field_value, $value) !== false;

            case 'starts_with':
                return strpos($field_value, $value) === 0;

            case 'ends_with':
                return substr($field_value, -strlen($value)) === $value;

            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                return in_array($field_value, array_map('trim', $values));

            case 'empty':
                return empty($field_value);

            case 'not_empty':
                return !empty($field_value);

            default:
                // Allow extensions to add operators
                return apply_filters(
                    'super_evaluate_condition_operator',
                    false,
                    $operator,
                    $field_value,
                    $value,
                    $context
                );
        }
    }

    /**
     * Get field value from context with {tag} support
     */
    private static function get_field_value($field, $context) {
        // Remove curly braces
        $field = str_replace(array('{', '}'), '', $field);

        // Check direct context
        if (isset($context[$field])) {
            return $context[$field];
        }

        // Check entry_data
        if (!empty($context['entry_data'][$field]['value'])) {
            return $context['entry_data'][$field]['value'];
        }

        // Check form_data
        if (!empty($context['data'][$field]['value'])) {
            return $context['data'][$field]['value'];
        }

        return '';
    }
}
```

**Key Design Decisions**:
- Simple, recursive evaluation
- No type casting in Phase 1 (string comparison only)
- Filterable for extensions to add operators
- {tag} support for dynamic field references

---

## 5. Implementation Checklist

### Step-by-Step Implementation Order

#### ✅ Step 1: Event Mapping (COMPLETED)
- [x] Map all 18 event firing points
- [x] Document exact line numbers and context
- [x] Create EVENT_FIRING_MAP.md

#### 🔄 Step 2: Architecture Documentation (IN PROGRESS)
- [x] Create ARCHITECTURE_BLUEPRINT.md
- [ ] Review with user for final approval

#### ⏳ Step 3: Database Tables
**File**: `/src/includes/class-install.php`
- [ ] Add table creation in `create_tables()` method
- [ ] Add version check in `check_version()` method
- [ ] Test installation on fresh site
- [ ] Test upgrade on existing site

#### ⏳ Step 4: Data Access Layer
**File**: `/src/includes/class-trigger-dal.php`
- [ ] Implement trigger CRUD methods
- [ ] Implement scope-aware queries
- [ ] Implement action CRUD methods
- [ ] Implement logging methods
- [ ] Add comprehensive error handling (WP_Error)

#### ⏳ Step 5: Base Action Class
**File**: `/src/includes/class-trigger-action-base.php`
- [ ] Create abstract class
- [ ] Define required methods
- [ ] Implement common helper methods
- [ ] Add tag replacement logic

#### ⏳ Step 6: Registry System
**File**: `/src/includes/class-trigger-registry.php`
- [ ] Implement singleton pattern
- [ ] Add event registration
- [ ] Add action registration
- [ ] Implement fire_event() method
- [ ] Add WordPress action hooks

#### ⏳ Step 7: Conditions Engine
**File**: `/src/includes/class-trigger-conditions.php`
- [ ] Implement simplified evaluation logic
- [ ] Add AND/OR/NOT support
- [ ] Add basic operators
- [ ] Implement {tag} replacement
- [ ] Add extensibility filters

#### ⏳ Step 8: Manager Class
**File**: `/src/includes/class-trigger-manager.php`
- [ ] Implement validation logic
- [ ] Add permission checking
- [ ] Create trigger resolution logic
- [ ] Add sanitization methods

#### ⏳ Step 9: Executor Class
**File**: `/src/includes/class-trigger-executor.php`
- [ ] Implement synchronous execution
- [ ] Add condition evaluation integration
- [ ] Implement action execution loop
- [ ] Add comprehensive logging
- [ ] Add error handling

#### ⏳ Step 10: REST API Controller
**File**: `/src/includes/class-trigger-rest-controller.php`
- [ ] Extend WP_REST_Controller
- [ ] Implement CRUD endpoints
- [ ] Add permission checks
- [ ] Add validation
- [ ] Register routes on rest_api_init

#### ⏳ Step 11: Event Registration
**File**: `/src/includes/class-triggers-init.php`
- [ ] Create initialization class
- [ ] Register all 18 Phase 1 events
- [ ] Add to plugin bootstrap

#### ⏳ Step 12: Event Firing Integration
**File**: `/src/includes/class-ajax.php`
- [ ] Add fire_event() calls at all mapped locations
- [ ] Test each event individually
- [ ] Verify context data completeness

#### ⏳ Step 13: Unit Testing
**Directory**: `/tests/`
- [ ] Test DAL CRUD operations
- [ ] Test scope-aware queries
- [ ] Test conditions engine
- [ ] Test executor logic
- [ ] Test REST API endpoints
- [ ] Achieve 80%+ coverage

---

## 6. Testing Strategy

### Unit Test Coverage Targets

| Component | Target | Critical Paths |
|-----------|--------|----------------|
| DAL | 90% | CRUD, scope queries, error handling |
| Conditions | 95% | All operators, nesting, edge cases |
| Manager | 85% | Validation, permissions, scope resolution |
| Executor | 90% | Event firing, action execution, logging |
| Registry | 80% | Registration, retrieval, fire_event() |
| REST API | 85% | All endpoints, auth, validation |

### Integration Testing

1. **Event Firing Test**:
   - Submit form and verify all events fire in correct order
   - Check event context data completeness
   - Verify dual storage (logs table + entry meta)

2. **Scope Isolation Test**:
   - Create triggers for different scopes
   - Verify form triggers don't fire for other forms
   - Verify global triggers fire for all forms

3. **Condition Evaluation Test**:
   - Test nested AND/OR/NOT logic
   - Test all operators with various data types
   - Test {tag} replacement in conditions

4. **REST API Test**:
   - CRUD operations via REST API
   - Permission checks
   - Invalid data handling

### Performance Benchmarks

- Trigger lookup: < 50ms for 100+ triggers
- Condition evaluation: < 10ms for nested groups
- Event firing overhead: < 100ms total
- REST API response: < 200ms for list queries

---

## 7. Risk Analysis & Mitigation

### Risk Matrix

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| DB schema changes needed | HIGH | LOW | Full scope system from start |
| Complex conditions too slow | MEDIUM | MEDIUM | Keep Phase 1 simple, optimize later |
| Entry data storage conflicts | HIGH | LOW | Use SUPER_Data_Access exclusively |
| Event firing breaks existing code | MEDIUM | LOW | Fire after WordPress actions, test thoroughly |
| REST API security issues | HIGH | MEDIUM | Use WP capabilities, validate all inputs |

### Mitigation Strategies

1. **Database Schema**: Implement full scope system now (form/global/user/role/site/network)
2. **Performance**: Simple conditions only (AND/OR/NOT), defer complex operators
3. **Data Layer**: Always use `SUPER_Data_Access::update_entry_data()`, never direct postmeta
4. **Backward Compatibility**: Keep old `SUPER_Common::triggerEvent()` functional
5. **Security**: Require `manage_options` for all REST endpoints in Phase 1

---

## 8. Next Steps

### Immediate Actions (This Session)

1. ✅ Complete architecture blueprint (this document)
2. Get user approval on architecture decisions
3. Begin Step 3: Create database tables
4. Begin Step 4: Implement DAL

### This Week

- Complete all 7 core classes
- Write unit tests as you go (TDD approach)
- Test event firing integration
- Document all classes with PHPDoc

### Phase 1 Completion

- All 18 events firing correctly
- REST API fully functional
- 80%+ test coverage
- Ready for Phase 1.5 (Admin UI)

---

## 9. Success Metrics

### Technical Metrics

- [ ] All 3 database tables created successfully
- [ ] All 7 core classes implemented and tested
- [ ] All 18 events fire with correct context data
- [ ] 80%+ unit test coverage achieved
- [ ] REST API passes all CRUD tests
- [ ] Zero PHP warnings/errors in debug mode

### Performance Metrics

- [ ] Event firing overhead < 100ms
- [ ] Trigger lookup < 50ms for 100 triggers
- [ ] Condition evaluation < 10ms for nested groups
- [ ] REST API response time < 200ms

### Code Quality Metrics

- [ ] WordPress coding standards compliance
- [ ] No SQL injection vulnerabilities
- [ ] Proper WP_Error usage throughout
- [ ] Comprehensive PHPDoc comments
- [ ] No use of deprecated functions

---

## 10. Appendix

### A. File Structure

```
/src/includes/
  class-install.php                        [MODIFY] Add table creation
  class-ajax.php                           [MODIFY] Add event firing
  class-trigger-dal.php                    [NEW] Data Access Layer
  class-trigger-manager.php                [NEW] Business Logic
  class-trigger-registry.php               [NEW] Event/Action Registry
  class-trigger-conditions.php             [NEW] Conditions Engine
  class-trigger-executor.php               [NEW] Synchronous Executor
  class-trigger-action-base.php            [NEW] Abstract Action Class
  class-trigger-rest-controller.php        [NEW] REST API
  class-triggers-init.php                  [NEW] Initialization

/tests/
  test-trigger-dal.php                     [NEW] DAL tests
  test-trigger-manager.php                 [NEW] Manager tests
  test-trigger-conditions.php              [NEW] Conditions tests
  test-trigger-executor.php                [NEW] Executor tests
  test-trigger-registry.php                [NEW] Registry tests
  test-trigger-rest-api.php                [NEW] REST API tests
```

### B. Coding Standards Checklist

- [ ] Use tabs for indentation
- [ ] Use `snake_case` for function/variable names
- [ ] Use `UPPER_CASE` for constants
- [ ] Add PHPDoc for all classes/methods
- [ ] Sanitize all user inputs
- [ ] Escape all outputs
- [ ] Use prepared statements for SQL
- [ ] Return WP_Error on failure
- [ ] Follow WordPress naming conventions
- [ ] Use `wp_json_encode()` not `json_encode()`

### C. WordPress Integration Points

**Hooks We Fire**:
- `do_action('super_trigger_event_' . $event_id, $context)`
- `do_action('super_trigger_event', $event_id, $context)`
- `do_action('super_trigger_action_executed', $action_id, $result, $context)`

**Hooks We Listen To**:
- `init` (register events/actions)
- `rest_api_init` (register REST routes)
- `plugins_loaded` (initialize system)

**Filters We Provide**:
- `apply_filters('super_trigger_events', $events)`
- `apply_filters('super_trigger_actions', $actions)`
- `apply_filters('super_evaluate_condition_operator', $result, $operator, ...)`

---

**Document Version**: 1.0
**Last Updated**: 2025-11-21
**Status**: Ready for Implementation
