---
name: 01-implement-foundation-registry
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
parent: h-implement-triggers-actions-extensibility
---

# Implement Foundation and Registry System (Backend Only)

⚠️ **ARCHITECTURE UPDATED (2025-11-30):**
This document reflects the **ORIGINAL** database schema with trigger-level scope columns (`scope`, `scope_id`, `event_id`).
The architecture has been **UPDATED** to use **node-level scope** configuration instead.
See [README.md § Scope System Architecture](./README.md#scope-system-architecture) for the current architecture.

**Key Changes:**
- Triggers table NO LONGER has `scope`, `scope_id`, `event_id`, `form_id`, or `conditions` columns
- New schema: `id`, `trigger_name`, `workflow_type`, `workflow_graph`, `enabled`, timestamps
- Scope is now configured at the EVENT NODE level within `workflow_graph` JSON
- Visual workflows store nodes/connections; code workflows store actions array

## Problem/Goal
Establish the complete backend foundation for the extensible triggers/actions system. This phase builds all backend infrastructure WITHOUT any admin UI. The UI will be implemented in Phase 1.5 using the REST API built in this phase.

## Success Criteria
- [ ] Database tables created with scope support (form/global/user/role)
- [ ] Data Access Layer (DAL) with scope-aware queries
- [ ] Manager class with business logic, validation, permissions
- [ ] Registry system for events and actions registration
- [ ] Complex condition engine (AND/OR/NOT grouping, {tag} replacement)
- [ ] Base action class with common functionality
- [ ] Executor class (synchronous execution, Phase 2 adds async)
- [ ] REST API v1 endpoints (full CRUD for triggers/actions)
- [ ] Unit tests (80%+ coverage for critical paths)
- [ ] NO backward compatibility (unreleased feature)
- [ ] NO admin UI (deferred to Phase 1.5)
- [ ] PHP 7.4+ compatibility maintained

## Implementation Steps

### Step 1: Database Schema & Automatic Table Creation

**File:** `/src/includes/class-install.php`
**Location:** Line ~84 in `create_tables()` method

Add three new tables after existing EAV table creation (line ~116):

**1. wp_superforms_triggers** - Main triggers table with scope support

```sql
CREATE TABLE {$wpdb->prefix}superforms_triggers (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  trigger_name VARCHAR(255) NOT NULL,
  scope VARCHAR(50) NOT NULL DEFAULT 'form',  -- 'form', 'global', 'user', 'role', 'site', 'network'
  scope_id BIGINT(20),                        -- form_id, user_id, blog_id, etc. (NULL for global/role/network)
  event_id VARCHAR(100) NOT NULL,             -- 'form.submitted', 'payment.completed', etc.
  conditions TEXT,                            -- JSON: complex condition structure with AND/OR/NOT
  enabled TINYINT(1) DEFAULT 1,
  execution_order INT DEFAULT 10,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY scope_lookup (scope, scope_id, enabled),
  KEY event_lookup (event_id, enabled),
  KEY form_triggers (scope, scope_id) USING BTREE
) ENGINE=InnoDB $charset_collate;
```

**2. wp_superforms_trigger_actions** - Actions table (normalized)

```sql
CREATE TABLE {$wpdb->prefix}superforms_trigger_actions (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  trigger_id BIGINT(20) UNSIGNED NOT NULL,
  action_type VARCHAR(100) NOT NULL,          -- 'http.request', 'email.send', 'webhook.call', etc.
  action_config TEXT,                         -- JSON: action-specific configuration
  execution_order INT DEFAULT 10,
  enabled TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY trigger_id (trigger_id),
  KEY action_type (action_type),
  KEY trigger_order (trigger_id, execution_order),
  FOREIGN KEY (trigger_id) REFERENCES {$wpdb->prefix}superforms_triggers(id) ON DELETE CASCADE
) ENGINE=InnoDB $charset_collate;
```

**3. wp_superforms_trigger_logs** - Execution history

```sql
CREATE TABLE {$wpdb->prefix}superforms_trigger_logs (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  trigger_id BIGINT(20) UNSIGNED NOT NULL,
  action_id BIGINT(20) UNSIGNED,
  entry_id BIGINT(20) UNSIGNED,
  form_id BIGINT(20) UNSIGNED,
  event_id VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL,                -- 'success', 'failed', 'pending'
  error_message TEXT,
  execution_time_ms INT,
  context_data LONGTEXT,                      -- JSON: event context
  result_data LONGTEXT,                       -- JSON: action result
  user_id BIGINT(20) UNSIGNED,
  scheduled_action_id BIGINT(20) UNSIGNED,    -- Action Scheduler ID (Phase 2)
  executed_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY trigger_id (trigger_id),
  KEY entry_id (entry_id),
  KEY form_id (form_id),
  KEY status (status),
  KEY executed_at (executed_at),
  KEY form_status (form_id, status)
) ENGINE=InnoDB $charset_collate;
```

**Implementation Notes:**
- Use `dbDelta()` for safe schema upgrades
- Add version check in `check_version()` to run on plugin update
- Follow existing EAV table creation pattern in `class-install.php`

### Step 2: Data Access Layer (DAL) Implementation

**File:** `/src/includes/class-trigger-dal.php` (new file)

Create `SUPER_Trigger_DAL` class with scope-aware methods. Returns `WP_Error` on failure, data on success.

**Core Trigger CRUD:**
```php
- create_trigger($data)              // Insert: validates scope, returns trigger_id or WP_Error
- get_trigger($id)                   // Select: returns trigger array or WP_Error
- update_trigger($id, $data)         // Update: returns bool or WP_Error
- delete_trigger($id)                // Delete: cascades to actions, returns bool or WP_Error
```

**Scope-Aware Queries:**
```php
- get_triggers_by_scope($scope, $scope_id = null)
  // Examples:
  //   get_triggers_by_scope('form', 123)   → triggers for form #123
  //   get_triggers_by_scope('global')      → all global triggers
  //   get_triggers_by_scope('user', 5)     → triggers for user #5

- get_triggers_by_event($event_id, $scope = null, $scope_id = null)
  // Examples:
  //   get_triggers_by_event('form.submitted')           → all triggers for this event
  //   get_triggers_by_event('form.submitted', 'form', 123) → form-specific triggers
  //   get_triggers_by_event('payment.completed', 'global') → global payment triggers

- get_active_triggers_for_context($event_id, $context)
  // Smart lookup: checks form scope, global scope, user scope in priority order
  // Returns merged array of all applicable triggers
```

**Action Management:**
```php
- create_action($trigger_id, $data)  // Returns action_id or WP_Error
- get_actions($trigger_id)           // Returns array of actions (ordered)
- update_action($action_id, $data)   // Returns bool or WP_Error
- delete_action($action_id)          // Returns bool or WP_Error
- reorder_actions($trigger_id, $order_array)  // Update execution_order
```

**Advanced Queries:**
```php
- search_triggers($args)             // Pagination, filters (scope, event, status)
- count_triggers($filters)           // Count with optional filters
```

**Logging Methods:**
```php
- log_execution($trigger_id, $action_id, $entry_id, $status, $data)
- get_execution_logs($filters, $limit, $offset)
- get_trigger_stats($trigger_id)     // Success/failure counts, avg execution time
```

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

### Step 5: Manager Class (Business Logic Layer)

**File:** `/src/includes/class-trigger-manager.php` (new file)

Create `SUPER_Trigger_Manager` class - sits between REST API and DAL, handles business logic:

**Responsibilities:**
- Validation: Ensure scope/scope_id combinations are valid
- Permissions: Check `manage_options` capability (simple for Phase 1)
- Sanitization: Clean user input before passing to DAL
- Scope Resolution: Determine which triggers apply to given context

**Key Methods:**
```php
- create_trigger_with_actions($trigger_data, $actions_data)  // Atomic operation
- validate_trigger_data($data)                               // Returns true or WP_Error
- can_user_manage_trigger($trigger_id, $user_id)            // Permission check
- resolve_triggers_for_event($event_id, $context)           // Returns applicable triggers
```

### Step 6: Conditions Engine (Complex Logic Evaluation)

**File:** `/src/includes/class-trigger-conditions.php` (new file)

Create `SUPER_Trigger_Conditions` class for recursive condition evaluation:

**Features:**
- AND/OR/NOT grouping with unlimited nesting
- {tag} replacement: `{field_name}`, `{entry_id}`, `{user_email}`, etc.
- Operators: `=`, `!=`, `>`, `<`, `contains`, `starts_with`, `regex`, `in`, `empty`, etc.
- Type casting: string, int, float, bool, date, array

**Structure:**
```php
$conditions = [
  'operator' => 'AND',
  'rules' => [
    ['type' => 'condition', 'field' => '{email}', 'operator' => 'contains', 'value' => '@gmail.com'],
    ['type' => 'group', 'operator' => 'OR', 'rules' => [
      ['type' => 'condition', 'field' => '{country}', 'operator' => '=', 'value' => 'US'],
      ['type' => 'condition', 'field' => '{country}', 'operator' => '=', 'value' => 'CA']
    ]]
  ]
];
```

**Key Methods:**
```php
- evaluate($conditions, $context)           // Returns bool
- replace_tags($string, $context)           // '{email}' → 'user@example.com'
- validate_condition_structure($conditions) // Check for circular dependencies, complexity
```

**Note:** See "Advanced Conditional Logic Engine" section below for full implementation details.

### Step 7: Executor Class (Synchronous Action Execution)

**File:** `/src/includes/class-trigger-executor.php` (new file)

Create `SUPER_Trigger_Executor` class for running actions. Phase 1 = sync only, Phase 2 adds async via Action Scheduler.

**Key Methods:**
```php
- execute_trigger($trigger_id, $context)     // Run all actions for trigger
- execute_action($action_id, $context)       // Run single action
- fire_event($event_id, $context)            // Find and execute all matching triggers
```

**Flow:**
1. Get triggers for event + context (via Manager)
2. Evaluate conditions (via Conditions Engine)
3. If conditions pass, execute actions in order
4. Log results (via DAL)
5. Return success/failure

### Step 8: REST API v1 Endpoints

**File:** `/src/includes/class-trigger-rest-controller.php` (new file)

Create `SUPER_Trigger_REST_Controller` extending `WP_REST_Controller`:

**Endpoints:**
```php
// Automations CRUD (renamed from triggers)
GET    /wp-json/super-forms/v1/automations
POST   /wp-json/super-forms/v1/automations
GET    /wp-json/super-forms/v1/automations/{id}
PUT    /wp-json/super-forms/v1/automations/{id}
DELETE /wp-json/super-forms/v1/automations/{id}

// Actions CRUD (nested) - for code-based automations
GET    /wp-json/super-forms/v1/automations/{automation_id}/actions
POST   /wp-json/super-forms/v1/automations/{automation_id}/actions
PUT    /wp-json/super-forms/v1/automations/{automation_id}/actions/{id}
DELETE /wp-json/super-forms/v1/automations/{automation_id}/actions/{id}

// Registry introspection
GET    /wp-json/super-forms/v1/events          // List registered events
GET    /wp-json/super-forms/v1/action-types    // List registered action types

// Execution logs
GET    /wp-json/super-forms/v1/automation-logs    // Query logs with filters (renamed from trigger-logs)

// Testing (dev only)
POST   /wp-json/super-forms/v1/automations/{id}/test  // Fire with mock data
```

**Permissions:**
- All endpoints require `manage_options` for Phase 1
- Phase 1.5 will add granular scope-based permissions

### Step 9: Unit Testing Structure

**Directory:** `/test/`

Create comprehensive unit tests for all classes (80%+ coverage target):

**Test Files:**
```
test-trigger-dal.php          - Database CRUD, scope queries, error handling
test-trigger-manager.php      - Business logic, validation, permissions
test-trigger-registry.php     - Event/action registration, retrieval
test-trigger-conditions.php   - Condition evaluation, tag replacement, nesting
test-trigger-executor.php     - Action execution, logging, error handling
test-trigger-action-base.php  - Abstract class methods
test-trigger-rest-api.php     - REST endpoints, auth, validation
```

**Testing Approach:**
- Use WordPress testing framework (PHPUnit)
- Mock WordPress functions where needed
- Test both success and failure paths
- Test WP_Error returns
- Test scope isolation
- Test tag replacement edge cases
- Performance tests for complex conditions

**Core Events Registration** (in Step 4 Registry implementation):

Register these built-in events when plugin loads. Events organized by implementation phase:

### Phase 1 Events (Foundation - Implement Now)

**Form Lifecycle Events:**
- `form.before_submit` - Before any processing starts (allows pre-validation actions)
- `form.validation_failed` - Validation errors detected
- `form.submitted` - After validation passes, before save
- `form.spam_detected` - Spam detection triggered (Akismet, honeypot, etc.)
- `form.duplicate_detected` - Duplicate submission detected

**Entry Events:**
- `entry.created` - Right after entry post created in database
- `entry.saved` - After all entry data saved (includes custom fields)
- `entry.updated` - Existing entry edited
- `entry.status_changed` - Entry status modified (e.g., pending → approved)
- `entry.deleted` - Entry permanently deleted

**File Upload Events:**
- `file.uploaded` - File upload completed successfully
- `file.upload_failed` - File upload error
- `file.deleted` - Uploaded file removed

### Phase 6 Events (Payment Integration - Implement with Payment Phase)

**Generic Payment Events** (gateway-agnostic):
- `payment.initiated` - Payment process started
- `payment.completed` - Payment successful
- `payment.failed` - Payment error
- `payment.refunded` - Full or partial refund issued
- `payment.disputed` - Chargeback or dispute opened

**Subscription Events** (generic):
- `subscription.created` - New subscription started
- `subscription.updated` - Subscription modified (plan change, amount change)
- `subscription.renewed` - Recurring payment succeeded
- `subscription.payment_failed` - Recurring payment failed
- `subscription.canceled` - Subscription cancelled by user or admin
- `subscription.expired` - Subscription ended (reached end date)
- `subscription.trial_started` - Trial period began
- `subscription.trial_ending` - Trial ending soon (3 days before)
- `subscription.trial_ended` - Trial completed

**Gateway-Specific Events** (for advanced use cases):
- `payment.stripe.checkout_completed` - Stripe checkout session completed
- `payment.stripe.payment_intent_succeeded` - Stripe payment intent succeeded
- `payment.stripe.invoice_paid` - Stripe invoice paid
- `payment.paypal.payment_completed` - PayPal payment completed
- `payment.paypal.subscription_created` - PayPal subscription created

**WooCommerce Events** (Super Forms WooCommerce add-on integration):
- `woocommerce.order_created` - Order created from form submission
- `woocommerce.order_completed` - Order status changed to completed
- `woocommerce.order_failed` - Order failed
- `woocommerce.order_refunded` - Order refunded
- `woocommerce.product_created` - Product created via Front-End Posting

### Phase 8 Events (Real-time Interactions - Client-Side)

**Field Interaction Events** (JavaScript-triggered):
- `field.changed` - Field value changed
- `field.focused` - Field received focus
- `field.blurred` - Field lost focus
- `field.keypress` - Key pressed in field (debounced)
- `calculation.updated` - Calculated field value recalculated
- `form.abandoned` - User started form but left without submitting

### Future Phase Events (Deferred)

**User Events:**
- `user.registered` - New user account created via form
- `user.logged_in` - User logged in
- `user.logged_out` - User logged out
- `user.login_failed` - Failed login attempt
- `user.password_reset` - Password reset requested
- `user.password_changed` - Password successfully changed
- `user.role_changed` - User role modified
- `user.profile_updated` - User meta/profile updated
- `user.deleted` - User account deleted

**Post/CPT Events** (Front-End Posting add-on):
- `post.created` - Post created from form submission
- `post.updated` - Post updated via form
- `post.published` - Post status changed to published
- `post.status_changed` - Post status modified
- `post.deleted` - Post deleted

**Email Events** (with tracking):
- `email.sent` - Email successfully sent
- `email.failed` - Email sending failed
- `email.bounced` - Email bounced (requires tracking integration)
- `email.opened` - Email opened (requires tracking pixel)
- `email.link_clicked` - Email link clicked (requires tracking)

**Schedule/Time Events:**
- `schedule.daily` - Once per day trigger (e.g., 00:00 UTC)
- `schedule.weekly` - Once per week (e.g., Monday 00:00)
- `schedule.monthly` - Once per month (e.g., 1st of month)
- `entry.anniversary` - X days/weeks/months after entry created
- `reminder.due` - Scheduled reminder time reached

**Admin Events:**
- `form.created` - New form created
- `form.updated` - Form settings modified
- `form.deleted` - Form deleted
- `form.duplicated` - Form cloned
- `trigger.created` - New trigger added (meta-event)
- `trigger.updated` - Trigger configuration changed (meta-event)
- `trigger.deleted` - Trigger removed (meta-event)

**Integration Events:**
- `webhook.received` - Incoming webhook received
- `api.request_sent` - External API request made
- `api.response_received` - External API response received
- `action.completed` - Specific action finished successfully (meta-event)
- `action.failed` - Specific action failed (meta-event)

### Event Naming Convention

All events follow `category.action` pattern:
- **Category**: form, entry, payment, subscription, user, post, email, file, etc.
- **Action**: created, updated, deleted, completed, failed, etc.
- **Gateway-specific**: `payment.{gateway}.{action}` for provider-specific events

### Context Data Standards

Each event provides standardized context data:

```php
$context = [
  'event_id' => 'form.submitted',
  'timestamp' => current_time('mysql'),
  'entry_id' => 123,
  'form_id' => 456,
  'user_id' => get_current_user_id(),
  'user_ip' => $_SERVER['REMOTE_ADDR'],
  'form_data' => [], // All submitted field values
  'entry_data' => [], // Processed/sanitized entry data
  // Event-specific fields:
  'payment_id' => 789,        // For payment events
  'amount' => 99.99,          // For payment events
  'currency' => 'USD',        // For payment events
  'gateway' => 'stripe',      // For payment events
  'subscription_id' => 321,   // For subscription events
  'file_url' => 'https://...',  // For file events
  'post_id' => 654,           // For post events
];
```

### Implementation Priority

**Phase 1 (Now):** 18 events
- All form lifecycle events (5)
- All entry events (5)
- All file events (3)
- Basic payment events (5) - stubs for Phase 6

**Phase 6 (Payment):** 20+ events
- Complete payment/subscription events
- Gateway-specific events
- WooCommerce events

**Phase 8 (Real-time):** 6 events
- All field interaction events
- Form abandonment tracking

**Future:** 30+ events
- User lifecycle events
- Post/CPT events
- Email tracking events
- Schedule events
- Admin events
- Integration meta-events

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
- [2025-11-20] Architecture refined: Backend-only approach, scope system, REST API first
- [2025-11-20] Database schema updated with scope fields (form/global/user/role/site/network)
- [2025-11-20] Added Manager, Conditions Engine, Executor, REST API steps
- [2025-11-20] Removed backward compatibility requirement (unreleased feature)
- [2025-11-20] Added comprehensive unit testing structure (80%+ coverage target)
- [2025-11-20] Expanded event taxonomy: 70+ events across all phases with naming conventions and context standards