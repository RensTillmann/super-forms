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

## Data Access Layer Integration

### Critical: Always Use SUPER_Data_Access for Entry Data

All contact entry data operations MUST go through the Data Access Layer to ensure compatibility with the EAV migration system:

```php
// ✅ CORRECT - Use Data Access Layer for all entry data
SUPER_Data_Access::update_entry_data($entry_id, array(
    '_trigger_execution_id' => $execution_id,
    '_last_action_status' => 'completed',
    '_action_timestamp' => current_time('mysql'),
    '_action_metadata' => json_encode($metadata)
));

// ✅ CORRECT - Read entry data through Data Access Layer
$entry_data = SUPER_Data_Access::get_entry_data($entry_id);
$trigger_id = $entry_data['_trigger_execution_id'] ?? null;

// ✅ CORRECT - Delete specific entry data fields
SUPER_Data_Access::delete_entry_data($entry_id, array(
    '_trigger_execution_id',
    '_last_action_status'
));

// ❌ WRONG - Never use direct postmeta for entry data
update_post_meta($entry_id, '_trigger_execution_id', $execution_id);
$data = get_post_meta($entry_id, '_super_contact_entry_data', true);
delete_post_meta($entry_id, '_trigger_execution_id');
```

### Why This Matters

1. **EAV Migration Compatibility**: The Data Access Layer automatically routes to the correct storage (serialized postmeta or EAV tables) based on migration state
2. **Performance**: After migration, direct database queries are 30-60x faster than serialized data queries
3. **Future-Proofing**: The storage backend can change without breaking your code
4. **Consistency**: All entry data goes through the same validation and sanitization

### Data Access Layer Methods

```php
// Get all entry data
$data = SUPER_Data_Access::get_entry_data($entry_id);

// Update specific fields (merges with existing data)
SUPER_Data_Access::update_entry_data($entry_id, array(
    'field_name' => 'value',
    'another_field' => 'another_value'
));

// Find entries by field value (uses optimized queries)
$entries = SUPER_Data_Access::find_entries_by_field(
    $form_id,
    'email',
    'user@example.com'
);

// Check if entry exists
$exists = SUPER_Data_Access::entry_exists($entry_id);

// Delete specific fields
SUPER_Data_Access::delete_entry_data($entry_id, array('field1', 'field2'));
```

### Integration with Triggers System

When storing trigger/action execution data:

```php
class SUPER_Trigger_Execution {

    public function log_execution($entry_id, $trigger_id, $result) {
        // Store execution data using Data Access Layer
        SUPER_Data_Access::update_entry_data($entry_id, array(
            '_last_trigger_execution' => array(
                'trigger_id' => $trigger_id,
                'timestamp' => current_time('mysql'),
                'result' => $result,
                'status' => $result['success'] ? 'success' : 'failed'
            )
        ));

        // Also log to dedicated trigger logs table for querying
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'super_trigger_logs',
            array(
                'entry_id' => $entry_id,
                'trigger_id' => $trigger_id,
                'status' => $result['success'] ? 'success' : 'failed',
                'details' => json_encode($result),
                'executed_at' => current_time('mysql')
            )
        );
    }

    public function get_execution_history($entry_id) {
        // Get from Data Access Layer
        $entry_data = SUPER_Data_Access::get_entry_data($entry_id);
        return $entry_data['_trigger_execution_history'] ?? array();
    }
}
```

## Advanced Conditional Logic Engine (Epic 2)

### Complex Condition Support

The triggers system must support sophisticated conditional logic beyond simple field comparisons:

```php
class SUPER_Condition_Evaluator {

    /**
     * Evaluate complex condition groups with AND/OR/NOT logic
     */
    public function evaluate($conditions, $context) {
        if (empty($conditions)) {
            return true;
        }

        // Handle different condition structures
        if (isset($conditions['operator'])) {
            return $this->evaluate_group($conditions, $context);
        }

        // Legacy single condition support
        return $this->evaluate_single($conditions, $context);
    }

    /**
     * Evaluate a group of conditions with logical operators
     */
    private function evaluate_group($group, $context) {
        $operator = strtoupper($group['operator'] ?? 'AND');
        $results = array();

        // Process sub-groups
        if (!empty($group['groups'])) {
            foreach ($group['groups'] as $subgroup) {
                $results[] = $this->evaluate_group($subgroup, $context);
            }
        }

        // Process individual conditions
        if (!empty($group['conditions'])) {
            foreach ($group['conditions'] as $condition) {
                $results[] = $this->evaluate_single($condition, $context);
            }
        }

        // Apply operator
        switch ($operator) {
            case 'AND':
                return !in_array(false, $results, true);

            case 'OR':
                return in_array(true, $results, true);

            case 'NOT':
                // NOT inverts the result of AND operation on children
                return !(!in_array(false, $results, true));

            case 'XOR':
                // Exactly one condition must be true
                $true_count = array_count_values($results)[true] ?? 0;
                return $true_count === 1;

            case 'NAND':
                // NOT AND - at least one must be false
                return in_array(false, $results, true);

            case 'NOR':
                // NOT OR - all must be false
                return !in_array(true, $results, true);

            default:
                return false;
        }
    }

    /**
     * Evaluate a single condition
     */
    private function evaluate_single($condition, $context) {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';
        $type = $condition['type'] ?? 'string';

        // Get actual field value from context
        $field_value = $this->get_field_value($field, $context);

        // Type casting
        $field_value = $this->cast_value($field_value, $type);
        $value = $this->cast_value($value, $type);

        // Evaluate based on operator
        switch ($operator) {
            case '=':
            case '==':
                return $field_value == $value;

            case '!=':
            case '<>':
                return $field_value != $value;

            case '>':
                return $field_value > $value;

            case '>=':
                return $field_value >= $value;

            case '<':
                return $field_value < $value;

            case '<=':
                return $field_value <= $value;

            case 'contains':
                return stripos($field_value, $value) !== false;

            case 'not_contains':
                return stripos($field_value, $value) === false;

            case 'starts_with':
                return strpos($field_value, $value) === 0;

            case 'ends_with':
                return substr($field_value, -strlen($value)) === $value;

            case 'regex':
                return preg_match($value, $field_value) === 1;

            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                return in_array($field_value, $values);

            case 'not_in':
                $values = is_array($value) ? $value : explode(',', $value);
                return !in_array($field_value, $values);

            case 'between':
                list($min, $max) = is_array($value) ? $value : explode(',', $value);
                return $field_value >= $min && $field_value <= $max;

            case 'empty':
                return empty($field_value);

            case 'not_empty':
                return !empty($field_value);

            case 'changed':
                // Check if value changed from previous
                $previous = $context['previous_values'][$field] ?? null;
                return $field_value !== $previous;

            case 'custom':
                // Allow custom PHP evaluation (admin only)
                if (current_user_can('manage_options')) {
                    return $this->evaluate_custom($condition, $context);
                }
                return false;

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
     * Custom PHP condition evaluation
     */
    private function evaluate_custom($condition, $context) {
        $code = $condition['custom_code'] ?? '';

        if (empty($code)) {
            return false;
        }

        // Create safe evaluation context
        $eval_context = array(
            'value' => $this->get_field_value($condition['field'], $context),
            'form_data' => $context['form_data'] ?? array(),
            'user' => wp_get_current_user(),
            'post' => get_post(),
        );

        // Sandbox the evaluation
        try {
            $result = eval('return (' . $code . ');');
            return (bool) $result;
        } catch (Exception $e) {
            error_log('Custom condition error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get field value from context
     */
    private function get_field_value($field, $context) {
        // Handle special fields
        if (strpos($field, 'user.') === 0) {
            $user_field = substr($field, 5);
            $user = wp_get_current_user();
            return $user->$user_field ?? '';
        }

        if (strpos($field, 'post.') === 0) {
            $post_field = substr($field, 5);
            $post = get_post();
            return $post->$post_field ?? '';
        }

        if (strpos($field, 'meta.') === 0) {
            $meta_key = substr($field, 5);
            return get_post_meta(get_the_ID(), $meta_key, true);
        }

        // Calculate dynamic values
        if (strpos($field, 'calc.') === 0) {
            return $this->calculate_value(substr($field, 5), $context);
        }

        // Regular form field
        return $context['form_data'][$field] ?? '';
    }

    /**
     * Type casting for proper comparison
     */
    private function cast_value($value, $type) {
        switch ($type) {
            case 'int':
            case 'integer':
                return intval($value);

            case 'float':
            case 'decimal':
                return floatval($value);

            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'date':
                return strtotime($value);

            case 'json':
                return json_decode($value, true);

            case 'array':
                return is_array($value) ? $value : explode(',', $value);

            default:
                return strval($value);
        }
    }
}
```

### Condition Builder UI

Enhanced JavaScript for building complex conditions in the form builder:

```javascript
class ConditionBuilder {

    constructor(container) {
        this.container = container;
        this.conditions = {
            operator: 'AND',
            groups: [],
            conditions: []
        };
        this.init();
    }

    init() {
        this.render();
        this.bindEvents();
    }

    render() {
        const html = this.renderGroup(this.conditions);
        this.container.html(html);
    }

    renderGroup(group, level = 0) {
        return `
            <div class="condition-group" data-level="${level}">
                <div class="group-header">
                    <select class="group-operator">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                        <option value="NOT">NOT</option>
                        <option value="XOR">XOR</option>
                    </select>
                    <button class="add-condition">Add Condition</button>
                    <button class="add-group">Add Group</button>
                    ${level > 0 ? '<button class="remove-group">Remove Group</button>' : ''}
                </div>
                <div class="group-conditions">
                    ${group.conditions.map(c => this.renderCondition(c)).join('')}
                </div>
                <div class="group-subgroups">
                    ${group.groups.map(g => this.renderGroup(g, level + 1)).join('')}
                </div>
            </div>
        `;
    }

    renderCondition(condition) {
        return `
            <div class="condition-item">
                <select class="condition-field">
                    <option value="">Select Field</option>
                    ${this.getFieldOptions()}
                </select>
                <select class="condition-operator">
                    <option value="=">=</option>
                    <option value="!=">≠</option>
                    <option value=">">></option>
                    <option value="<"><</option>
                    <option value="contains">contains</option>
                    <option value="starts_with">starts with</option>
                    <option value="ends_with">ends with</option>
                    <option value="regex">matches regex</option>
                    <option value="in">in list</option>
                    <option value="between">between</option>
                    <option value="empty">is empty</option>
                    <option value="changed">has changed</option>
                </select>
                <input type="text" class="condition-value" placeholder="Value">
                <select class="condition-type">
                    <option value="string">String</option>
                    <option value="int">Number</option>
                    <option value="float">Decimal</option>
                    <option value="bool">Boolean</option>
                    <option value="date">Date</option>
                </select>
                <button class="remove-condition">×</button>
            </div>
        `;
    }
}
```

### Performance Optimization

For forms with many conditions, optimize evaluation:

```php
class SUPER_Condition_Cache {

    private $cache = array();
    private $hit_count = 0;
    private $miss_count = 0;

    /**
     * Cache condition results for performance
     */
    public function evaluate_with_cache($conditions, $context) {
        $cache_key = $this->generate_cache_key($conditions, $context);

        if (isset($this->cache[$cache_key])) {
            $this->hit_count++;
            return $this->cache[$cache_key];
        }

        $this->miss_count++;
        $evaluator = new SUPER_Condition_Evaluator();
        $result = $evaluator->evaluate($conditions, $context);

        // Only cache if conditions are deterministic
        if (!$this->has_dynamic_conditions($conditions)) {
            $this->cache[$cache_key] = $result;
        }

        return $result;
    }

    /**
     * Check if conditions contain dynamic elements
     */
    private function has_dynamic_conditions($conditions) {
        $dynamic_operators = array('changed', 'custom');

        if (isset($conditions['operator'])) {
            // Check groups recursively
            if (!empty($conditions['groups'])) {
                foreach ($conditions['groups'] as $group) {
                    if ($this->has_dynamic_conditions($group)) {
                        return true;
                    }
                }
            }
            // Check conditions
            if (!empty($conditions['conditions'])) {
                foreach ($conditions['conditions'] as $condition) {
                    if (in_array($condition['operator'] ?? '', $dynamic_operators)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Generate cache key for conditions
     */
    private function generate_cache_key($conditions, $context) {
        // Only include relevant context data in key
        $key_data = array(
            'conditions' => $conditions,
            'form_data' => $context['form_data'] ?? array(),
            'user_id' => get_current_user_id()
        );

        return md5(serialize($key_data));
    }

    /**
     * Get cache statistics
     */
    public function get_stats() {
        return array(
            'hits' => $this->hit_count,
            'misses' => $this->miss_count,
            'hit_rate' => $this->hit_count / max(1, $this->hit_count + $this->miss_count)
        );
    }
}
```

### Edge Cases and Validation

Handle circular dependencies and invalid conditions:

```php
class SUPER_Condition_Validator {

    /**
     * Validate conditions for circular dependencies
     */
    public function validate_conditions($conditions, $all_triggers = array()) {
        $errors = array();

        // Check for circular dependencies
        $dependency_graph = $this->build_dependency_graph($conditions, $all_triggers);
        if ($this->has_circular_dependency($dependency_graph)) {
            $errors[] = 'Circular dependency detected in conditions';
        }

        // Check for invalid field references
        $invalid_fields = $this->find_invalid_fields($conditions);
        if (!empty($invalid_fields)) {
            $errors[] = 'Invalid field references: ' . implode(', ', $invalid_fields);
        }

        // Check for excessive complexity
        $complexity = $this->calculate_complexity($conditions);
        if ($complexity > 100) {
            $errors[] = sprintf('Condition complexity too high: %d (max: 100)', $complexity);
        }

        return $errors;
    }

    /**
     * Calculate condition complexity score
     */
    private function calculate_complexity($conditions, $depth = 0) {
        $score = 0;

        if (isset($conditions['operator'])) {
            $score += 1; // Base score for group

            // Add depth penalty
            $score += $depth * 2;

            // Process subgroups
            if (!empty($conditions['groups'])) {
                foreach ($conditions['groups'] as $group) {
                    $score += $this->calculate_complexity($group, $depth + 1);
                }
            }

            // Process conditions
            if (!empty($conditions['conditions'])) {
                $score += count($conditions['conditions']);

                // Add complexity for special operators
                foreach ($conditions['conditions'] as $condition) {
                    if (in_array($condition['operator'] ?? '', array('regex', 'custom'))) {
                        $score += 5;
                    }
                }
            }
        }

        return $score;
    }
}
```

## User Notes
- This is the foundation phase - must be rock solid before other phases
- All database operations should use WordPress $wpdb for compatibility
- **CRITICAL**: All entry data operations MUST use SUPER_Data_Access methods
- Follow WordPress coding standards throughout
- Ensure Action Scheduler v3.9.3 compatibility
- Consider multisite compatibility from the start

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Subtask created with detailed implementation steps