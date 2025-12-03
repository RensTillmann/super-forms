# Phase 23: Production-Critical Refinements for Visual Workflow System

**Priority**: 🔴 **CRITICAL** - Must be implemented before production release

**Dependencies**: Phase 22 (Visual Workflow Integration)

**Timeline**: 6-7 days

---

## Overview

This phase addresses **7 production-critical issues** identified during architectural review. These refinements ensure security, reliability, and edge-case handling for the multi-trigger visual workflow system.

**Core Refinements (1-4): Architecture & Security**
1. InnoDB Transaction Safety
2. Variable Security (XSS/Injection Prevention)
3. Unmatched Config Edge Case
4. Global Context Variables

**Housekeeping Refinements (5-7): Maintenance & Production Reality**
5. REST API Security (Permission Callbacks)
6. Log Rotation & Cleanup (Prevent Database Bloat)
7. Headless User Context (Webhooks/Cron/API)

---

## Refinement #1: InnoDB Transaction Safety

### Problem

**Current Code:**
```php
$wpdb->query('START TRANSACTION');
// ... operations
$wpdb->query('COMMIT'); // or ROLLBACK
```

**The Risk:**
- MySQL transactions only work with **InnoDB** storage engine
- Many budget hosts default to **MyISAM** (especially on older installations)
- MyISAM **silently ignores** `START TRANSACTION`, `COMMIT`, `ROLLBACK`
- Result: **Data corruption** - trigger created but events not mapped

**Impact:**
- Orphaned triggers with no event listeners
- Silent failures that corrupt database state
- No user indication that something went wrong

### Solution

#### Step 1: Enforce InnoDB in Table Creation

**File**: `/src/includes/class-install.php`

```php
class SUPER_Install {

  public static function create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Determine storage engine (InnoDB preferred)
    $engine = self::get_storage_engine();

    $tables = [
      "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}superforms_automations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        workflow_graph LONGTEXT NOT NULL,
        enabled TINYINT(1) DEFAULT 1,
        scope VARCHAR(50) DEFAULT 'global',
        scope_id BIGINT UNSIGNED,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_enabled (enabled)
      ) ENGINE={$engine} {$charset_collate};",

      "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}superforms_automation_events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        trigger_id BIGINT UNSIGNED NOT NULL,
        event_id VARCHAR(100) NOT NULL,
        UNIQUE KEY unique_trigger_event (trigger_id, event_id),
        INDEX idx_event_id (event_id),
        FOREIGN KEY (trigger_id)
          REFERENCES {$wpdb->prefix}superforms_automations(id)
          ON DELETE CASCADE
      ) ENGINE={$engine} {$charset_collate};",

      "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}superforms_automation_executions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        trigger_id BIGINT UNSIGNED NOT NULL,
        event_id VARCHAR(100) NOT NULL,
        context LONGTEXT,
        status ENUM('running', 'completed', 'failed') DEFAULT 'running',
        error TEXT,
        started_at DATETIME NOT NULL,
        completed_at DATETIME,
        INDEX idx_trigger_id (trigger_id),
        INDEX idx_status (status),
        FOREIGN KEY (trigger_id)
          REFERENCES {$wpdb->prefix}superforms_automations(id)
          ON DELETE CASCADE
      ) ENGINE={$engine} {$charset_collate};"
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    foreach ($tables as $sql) {
      dbDelta($sql);
    }

    // Verify InnoDB was used
    self::verify_table_engine();
  }

  /**
   * Determine storage engine (InnoDB preferred)
   */
  private static function get_storage_engine() {
    global $wpdb;

    // Check if InnoDB is available
    $engines = $wpdb->get_results("SHOW ENGINES", ARRAY_A);

    $innodb_available = false;
    foreach ($engines as $engine) {
      if (strtolower($engine['Engine']) === 'innodb' &&
          in_array(strtolower($engine['Support']), ['yes', 'default'])) {
        $innodb_available = true;
        break;
      }
    }

    if ($innodb_available) {
      return 'InnoDB';
    }

    // Fallback to MyISAM with critical warning
    SUPER_Automation_Logger::error(
      'InnoDB storage engine not available. Falling back to MyISAM. ' .
      'TRANSACTIONS WILL NOT WORK. Please enable InnoDB in MySQL configuration.'
    );

    // Store warning for admin notice
    add_option('super_innodb_warning', true);

    return 'MyISAM';
  }

  /**
   * Verify tables are using InnoDB
   */
  private static function verify_table_engine() {
    global $wpdb;

    $tables = [
      $wpdb->prefix . 'superforms_triggers',
      $wpdb->prefix . 'superforms_trigger_events',
      $wpdb->prefix . 'superforms_trigger_executions'
    ];

    foreach ($tables as $table) {
      $engine = $wpdb->get_var("
        SELECT ENGINE
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = '{$table}'
      ");

      if (strtolower($engine) !== 'innodb') {
        SUPER_Automation_Logger::error(
          "Table {$table} is using {$engine} instead of InnoDB. " .
          "Transactions will not work properly."
        );

        add_option('super_innodb_warning', true);
      }
    }
  }
}
```

#### Step 2: Safe Transaction Wrapper

**File**: `/src/includes/class-automation-dal.php`

```php
class SUPER_Automation_DAL {

  /**
   * Execute callback within transaction (if supported)
   */
  public static function transaction($callback) {
    global $wpdb;

    // Check if InnoDB is in use
    $using_innodb = self::is_using_innodb();

    if ($using_innodb) {
      // Use transaction
      $wpdb->query('START TRANSACTION');

      try {
        $result = call_user_func($callback);
        $wpdb->query('COMMIT');
        return $result;

      } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        throw $e;
      }

    } else {
      // No transaction support - just execute with warning
      SUPER_Automation_Logger::warning(
        'MyISAM detected - executing without transaction support'
      );

      return call_user_func($callback);
    }
  }

  /**
   * Check if tables are using InnoDB (cached)
   */
  private static function is_using_innodb() {
    static $cache = null;

    if ($cache !== null) {
      return $cache;
    }

    global $wpdb;

    $engine = $wpdb->get_var("
      SELECT ENGINE
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = '{$wpdb->prefix}superforms_automations'
    ");

    $cache = (strtolower($engine) === 'innodb');
    return $cache;
  }
}
```

#### Step 3: Update REST Controller

**File**: `/src/includes/class-automation-rest-controller.php`

```php
public function create_trigger($request) {
  $params = $request->get_json_params();

  try {
    $trigger_id = SUPER_Automation_DAL::transaction(function() use ($params) {
      global $wpdb;

      // 1. Create trigger
      $result = $wpdb->insert(
        $wpdb->prefix . 'superforms_triggers',
        [
          'name' => $params['name'],
          'workflow_graph' => $params['workflow_graph'],
          'enabled' => $params['enabled'] ?? 1,
          'created_at' => current_time('mysql'),
          'updated_at' => current_time('mysql')
        ]
      );

      if (!$result) {
        throw new Exception('Failed to create trigger: ' . $wpdb->last_error);
      }

      $trigger_id = $wpdb->insert_id;

      // 2. Create event mappings
      foreach ($params['event_ids'] as $event_id) {
        $result = $wpdb->insert(
          $wpdb->prefix . 'superforms_trigger_events',
          [
            'trigger_id' => $trigger_id,
            'event_id' => $event_id
          ]
        );

        if (!$result) {
          throw new Exception('Failed to create event mapping: ' . $wpdb->last_error);
        }
      }

      return $trigger_id;
    });

    // Success - invalidate cache
    SUPER_Trigger_Event_Registry::invalidate_cache();

    return rest_ensure_response(['id' => $trigger_id]);

  } catch (Exception $e) {
    return new WP_Error('create_failed', $e->getMessage(), ['status' => 500]);
  }
}
```

**Testing Checklist:**
- [ ] Verify tables created with InnoDB on fresh install
- [ ] Test transaction rollback on error
- [ ] Verify admin warning shows if MyISAM detected
- [ ] Test on budget host with MyISAM default

---

## Refinement #2: Variable Security (XSS & Injection)

### Problem

**Current Code:**
```php
// Direct string replacement - NO SANITIZATION!
return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($matches) use ($context) {
  return $context[$matches[1]] ?? '';  // DANGEROUS!
}, $string);
```

**The Risk:**
- **Stored XSS**: User submits `<script>alert('XSS')</script>` in form field
- Variable `{name}` replaced in email body with malicious script
- Email sent with active XSS payload
- **SQL Injection**: Variable used in custom query action
- **Command Injection**: Variable used in execute command action

**Attack Vectors:**
1. Form submission → malicious input → email action → XSS in recipient's inbox
2. Webhook payload → malicious data → create post action → XSS on website
3. User input → transform context → SQL query action → database compromise

### Solution

#### Step 1: Context-Aware Sanitization Base Class

**File**: `/src/includes/automations/class-automation-action-base.php`

```php
abstract class SUPER_Automation_Action_Base {

  /**
   * Replace variables with context-aware sanitization
   */
  protected function replace_variables($string, $context, $options = []) {
    $defaults = [
      'sanitize' => 'html',  // Default: HTML-safe
      'allow_html' => false,
      'missing_behavior' => 'empty',
      'missing_variables' => []
    ];

    $options = array_merge($defaults, $options);

    return preg_replace_callback(
      '/\{([a-zA-Z0-9_]+)\}/',
      function($matches) use ($context, &$options) {
        $variable = $matches[1];

        if (!isset($context[$variable])) {
          $options['missing_variables'][] = $variable;

          switch ($options['missing_behavior']) {
            case 'empty': return '';
            case 'keep': return $matches[0];
            case 'error':
              throw new Exception("Required variable {$variable} not found");
            default: return '';
          }
        }

        $value = $context[$variable];

        // Apply sanitization based on output context
        return $this->sanitize_value(
          $value,
          $options['sanitize'],
          $options['allow_html']
        );
      },
      $string
    );
  }

  /**
   * Sanitize value based on output context
   */
  private function sanitize_value($value, $sanitize_type, $allow_html = false) {
    // Convert arrays/objects to JSON
    if (is_array($value) || is_object($value)) {
      $value = json_encode($value);
    }

    switch ($sanitize_type) {
      case 'html':
        // For HTML output (safest default)
        if ($allow_html) {
          return wp_kses_post($value); // Allow safe HTML
        }
        return esc_html($value); // Escape all HTML

      case 'email':
        return sanitize_email($value);

      case 'url':
        return esc_url($value);

      case 'text':
        return sanitize_text_field($value);

      case 'sql':
        // Use with EXTREME caution
        global $wpdb;
        return $wpdb->_real_escape($value);

      case 'attribute':
        return esc_attr($value);

      case 'none':
        // No sanitization - use only for trusted data
        return $value;

      default:
        return esc_html($value); // Safe default
    }
  }

  /**
   * Validate required variables exist
   */
  protected function validate_context($required_vars, $context) {
    $missing = array_diff($required_vars, array_keys($context));

    if (!empty($missing)) {
      SUPER_Automation_Logger::warning(
        "Missing required variables: " . implode(', ', $missing)
      );

      return [
        'valid' => false,
        'missing' => $missing
      ];
    }

    return ['valid' => true];
  }
}
```

#### Step 2: Action-Specific Sanitization

**Send Email Action:**

```php
class SUPER_Automation_Action_Send_Email extends SUPER_Automation_Action_Base {

  public function execute($config, $context) {
    // Validate critical variables
    $validation = $this->validate_context(['to'], $context);
    if (!$validation['valid']) {
      return ['success' => false, 'error' => 'Missing recipient'];
    }

    // Email-safe sanitization
    $to = $this->replace_variables($config['to'], $context, [
      'sanitize' => 'email',
      'missing_behavior' => 'error'
    ]);

    $from_email = $this->replace_variables($config['from_email'], $context, [
      'sanitize' => 'email'
    ]);

    $from_name = $this->replace_variables($config['from_name'], $context, [
      'sanitize' => 'text'
    ]);

    $subject = $this->replace_variables($config['subject'], $context, [
      'sanitize' => 'text'  // No HTML in subject
    ]);

    $body = $this->replace_variables($config['body'], $context, [
      'sanitize' => 'html',
      'allow_html' => true  // Allow safe HTML in body
    ]);

    // Send with sanitized data
    return wp_mail($to, $subject, $body, [
      'From: ' . $from_name . ' <' . $from_email . '>'
    ]);
  }
}
```

**HTTP Request Action:**

```php
class SUPER_Automation_Action_HTTP_Request extends SUPER_Automation_Action_Base {

  public function execute($config, $context) {
    // URL sanitization
    $url = $this->replace_variables($config['url'], $context, [
      'sanitize' => 'url'
    ]);

    // JSON body - sanitize after decoding
    $body_template = $config['body'];

    $body_replaced = $this->replace_variables($body_template, $context, [
      'sanitize' => 'none'  // Don't sanitize yet
    ]);

    $body_data = json_decode($body_replaced, true);

    if (is_array($body_data)) {
      array_walk_recursive($body_data, function(&$value) {
        if (is_string($value)) {
          $value = sanitize_text_field($value);
        }
      });

      $body = json_encode($body_data);
    } else {
      $body = sanitize_text_field($body_replaced);
    }

    return wp_remote_post($url, [
      'body' => $body,
      'headers' => ['Content-Type' => 'application/json']
    ]);
  }
}
```

**Create Post Action:**

```php
class SUPER_Automation_Action_Create_Post extends SUPER_Automation_Action_Base {

  public function execute($config, $context) {
    $title = $this->replace_variables($config['post_title'], $context, [
      'sanitize' => 'text'
    ]);

    $content = $this->replace_variables($config['post_content'], $context, [
      'sanitize' => 'html',
      'allow_html' => true  // WordPress post content
    ]);

    $post_id = wp_insert_post([
      'post_title' => $title,
      'post_content' => $content,
      'post_status' => $config['post_status'] ?? 'draft',
      'post_type' => $config['post_type'] ?? 'post'
    ]);

    return [
      'success' => !is_wp_error($post_id),
      'data' => ['post_id' => $post_id]
    ];
  }
}
```

**Testing Checklist:**
- [ ] Test XSS in form field → email action (should be escaped)
- [ ] Test HTML in email body (safe tags allowed, scripts removed)
- [ ] Test malicious URL in webhook action (should be sanitized)
- [ ] Test script tag in post content (should be stripped)

---

## Refinement #3: Unmatched Config Edge Case

### Problem

**Current Code:**
```php
private static function match_by_config($nodes, $event_id, $context) {
  // ... try to match ...

  // DANGEROUS FALLBACK!
  return $nodes[0];  // Returns first node even if config doesn't match
}
```

**The Risk:**
- Workflow has "Webhook A" (listens to `/webhook/stripe`)
- Request comes to `/webhook/paypal`
- No matching webhook node found
- **Current behavior**: Executes Webhook A anyway!
- **Result**: Wrong workflow path executed with wrong data

**Real-World Impact:**
- Stripe payment processed as PayPal payment
- Email sent to wrong customer
- Data saved to wrong database table

### Solution

#### Return Null Instead of Fallback

**File**: `/src/includes/class-workflow-executor.php`

```php
class SUPER_Workflow_Executor {

  /**
   * Match specific trigger node based on configuration
   * Returns NULL if no match found (don't execute wrong node!)
   */
  private static function match_by_config($nodes, $event_id, $context) {
    switch ($event_id) {
      case 'form.submitted':
        $form_id = $context['form_id'] ?? null;
        foreach ($nodes as $node) {
          $node_form_id = $node['config']['form_id'] ?? null;

          // Match specific form OR "all forms"
          if ($node_form_id === null || $node_form_id == $form_id) {
            return $node;
          }
        }
        break;

      case 'webhook.received':
        $webhook_url = $context['webhook_url'] ?? null;

        foreach ($nodes as $node) {
          $node_url = $node['config']['url'] ?? null;

          if ($node_url === $webhook_url) {
            return $node;
          }
        }

        // NO FALLBACK - return null if no match
        SUPER_Automation_Logger::info(
          "Webhook {$webhook_url} has no matching node. " .
          "Available: " . implode(', ', array_column($nodes, 'config'))
        );
        return null;

      case 'schedule':
        $schedule_id = $context['schedule_id'] ?? null;
        foreach ($nodes as $node) {
          if (($node['config']['schedule_id'] ?? null) === $schedule_id) {
            return $node;
          }
        }
        return null;

      case 'payment.stripe.checkout_completed':
        $form_id = $context['form_id'] ?? null;
        foreach ($nodes as $node) {
          $node_form_id = $node['config']['form_id'] ?? null;
          if ($node_form_id === null || $node_form_id == $form_id) {
            return $node;
          }
        }
        return null;

      default:
        // Events without config matching - return first
        return $nodes[0];
    }

    return null;
  }

  /**
   * Execute workflow - handle null start node gracefully
   */
  public static function execute($trigger_id, $event_id, $context) {
    global $wpdb;

    $trigger = $wpdb->get_row($wpdb->prepare("
      SELECT * FROM {$wpdb->prefix}superforms_automations WHERE id = %d
    ", $trigger_id), ARRAY_A);

    $graph = json_decode($trigger['workflow_graph'], true);

    $start_node = self::find_trigger_node_by_event(
      $graph['nodes'],
      $event_id,
      $context
    );

    if ($start_node === null) {
      // No matching node - log and exit WITHOUT executing
      SUPER_Automation_Logger::info(
        "Trigger {$trigger_id}: Event {$event_id} fired but no matching " .
        "node configuration. Workflow not executed."
      );
      return;
    }

    // Proceed with execution
    $adjacency = self::build_adjacency($graph['connections']);
    self::execute_node_chain($start_node['id'], $context, $graph['nodes'], $adjacency);
  }
}
```

**Testing Checklist:**
- [ ] Test webhook with non-matching URL (should not execute)
- [ ] Test form submit for different form ID (should not execute)
- [ ] Test schedule with wrong ID (should not execute)
- [ ] Verify logs show "no matching node" message

---

## Refinement #4: Global Context Variables

### Problem

**Current State:**
- Static analysis checks if variables exist in trigger context
- Warnings show for `{site_name}`, `{current_date}`, etc.
- These are **global** variables available everywhere
- False positives confuse users

**Example:**
```
User creates workflow:
[Webhook] → [Send Email]

Email body: "Welcome to {site_name}! Today is {current_date}"

Static analysis warns:
❌ Variable {site_name} not available in webhook trigger
❌ Variable {current_date} not available in webhook trigger

But these ARE available! (as global variables)
```

### Solution

#### Step 1: Define Global Context

**File**: `/src/includes/class-automation-context.php` (NEW)

```php
class SUPER_Automation_Context {

  /**
   * Get global context variables available in ALL triggers
   */
  public static function get_global_context() {
    global $wpdb;

    $user = wp_get_current_user();

    return [
      // Site information
      'site_url' => get_site_url(),
      'site_name' => get_bloginfo('name'),
      'site_description' => get_bloginfo('description'),
      'admin_email' => get_option('admin_email'),

      // Date/time
      'current_date' => current_time('Y-m-d'),
      'current_time' => current_time('H:i:s'),
      'current_datetime' => current_time('Y-m-d H:i:s'),
      'current_timestamp' => current_time('timestamp'),

      // Current user
      'current_user_id' => $user->ID,
      'current_user_email' => $user->user_email,
      'current_user_name' => $user->display_name,
      'current_user_login' => $user->user_login,

      // WordPress
      'wp_version' => get_bloginfo('version'),
      'wp_language' => get_bloginfo('language'),

      // Database
      'db_prefix' => $wpdb->prefix
    ];
  }

  /**
   * Get list of global variable names
   */
  public static function get_global_variable_names() {
    return array_keys(self::get_global_context());
  }

  /**
   * Merge global context with event context
   */
  public static function merge_context($event_context) {
    return array_merge(
      self::get_global_context(),
      $event_context
    );
  }
}
```

#### Step 2: Update Frontend Validation

**File**: `/src/react/admin/components/automations/VisualBuilder.tsx`

```typescript
// Global variables always available
const GLOBAL_VARIABLES = [
  'site_url',
  'site_name',
  'site_description',
  'admin_email',
  'current_date',
  'current_time',
  'current_datetime',
  'current_timestamp',
  'current_user_id',
  'current_user_email',
  'current_user_name',
  'current_user_login',
  'wp_version',
  'wp_language',
  'db_prefix'
];

const analyzeWorkflowContext = (nodes, connections) => {
  // ... existing code ...

  requiredVars.forEach(variable => {
    const unavailableIn = [];

    reachableTriggers.forEach(triggerType => {
      const triggerContext = analysis.triggerContexts.get(triggerType);

      // Check trigger context OR global context
      if (!triggerContext?.has(variable) && !GLOBAL_VARIABLES.includes(variable)) {
        unavailableIn.push(triggerType);
      }
    });

    // Only warn if not in global context
    if (unavailableIn.length > 0) {
      analysis.warnings.push({
        severity: 'warning',
        message: `Variable {${variable}} not available`,
        missingIn: unavailableIn
      });
    }
  });
};
```

#### Step 3: UI Helper Component

**File**: `/src/react/admin/components/automations/GlobalVariableHelper.tsx`

```typescript
export const GlobalVariableHelper = () => {
  const [showHelp, setShowHelp] = useState(false);

  return (
    <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
      <button
        onClick={() => setShowHelp(!showHelp)}
        className="flex items-center gap-2 text-sm font-medium text-blue-700"
      >
        <Info className="w-4 h-4" />
        Global Variables Available
      </button>

      {showHelp && (
        <div className="mt-2 text-xs text-blue-600 space-y-1">
          <p className="font-medium">Available in all triggers:</p>
          <div className="grid grid-cols-2 gap-1">
            {GLOBAL_VARIABLES.map(variable => (
              <code key={variable} className="bg-white px-2 py-1 rounded">
                {`{${variable}}`}
              </code>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};
```

#### Step 4: Backend Auto-Merge

**File**: `/src/includes/class-workflow-executor.php`

```php
public static function execute($trigger_id, $event_id, $context) {
  // ... existing code ...

  // ALWAYS merge global context before execution
  $context = SUPER_Automation_Context::merge_context($context);

  SUPER_Automation_Logger::debug('Execution context:', $context);

  // Continue with execution (now has global vars)
  self::execute_node_chain($start_node['id'], $context, $graph['nodes'], $adjacency);
}
```

**Testing Checklist:**
- [ ] Test `{site_name}` in email (should work)
- [ ] Test `{current_date}` in webhook action (should work)
- [ ] Verify no warnings for global variables in UI
- [ ] Test global helper shows all variables

---

## Refinement #5: REST API Security (The Hidden Door)

### Problem

**Current Code:**
```php
// In SUPER_Automation_REST_Controller::register_routes()
register_rest_route('super-forms/v1', '/automations', [
  'methods' => 'POST',
  'callback' => [$this, 'create_trigger'],
  // ⚠️ MISSING: permission_callback
]);
```

**The Risk:**
- Without `permission_callback`, **anyone on the internet** can send POST requests
- Attackers could create malicious workflows that:
  - Send spam emails using your SMTP server
  - Execute arbitrary HTTP requests to external services
  - Modify entry data or delete entries
  - Exfiltrate form submission data via webhooks
- WordPress REST API defaults to **public access** if no permission callback specified

**Impact:**
- **Security breach**: Unauthorized workflow creation
- **Resource abuse**: Server used for spam/phishing campaigns
- **Data theft**: Customer data exposed via malicious webhooks
- **Reputation damage**: Email server blacklisted for spam

### Solution

#### Step 1: Add Permission Callbacks to All Routes

**File**: `/src/includes/class-automation-rest-controller.php`

```php
class SUPER_Automation_REST_Controller extends WP_REST_Controller {

  public function register_routes() {
    // List all triggers
    register_rest_route('super-forms/v1', '/automations', [
      'methods' => 'GET',
      'callback' => [$this, 'get_triggers'],
      'permission_callback' => function() {
        // Admins can view all triggers
        return current_user_can('manage_options');
      }
    ]);

    // Create trigger
    register_rest_route('super-forms/v1', '/automations', [
      'methods' => 'POST',
      'callback' => [$this, 'create_trigger'],
      'permission_callback' => function() {
        // 🔒 CRITICAL: Only admins can create automations
        return current_user_can('manage_options');
      },
      'args' => $this->get_trigger_schema()
    ]);

    // Update trigger
    register_rest_route('super-forms/v1', '/automations/(?P<id>\d+)', [
      'methods' => 'PUT',
      'callback' => [$this, 'update_trigger'],
      'permission_callback' => function() {
        return current_user_can('manage_options');
      },
      'args' => [
        'id' => [
          'validate_callback' => function($param) {
            return is_numeric($param);
          }
        ]
      ]
    ]);

    // Delete trigger
    register_rest_route('super-forms/v1', '/automations/(?P<id>\d+)', [
      'methods' => 'DELETE',
      'callback' => [$this, 'delete_trigger'],
      'permission_callback' => function() {
        return current_user_can('manage_options');
      }
    ]);

    // Test trigger
    register_rest_route('super-forms/v1', '/automations/(?P<id>\d+)/test', [
      'methods' => 'POST',
      'callback' => [$this, 'test_trigger'],
      'permission_callback' => function() {
        return current_user_can('manage_options');
      }
    ]);

    // Get events (read-only, can be more permissive)
    register_rest_route('super-forms/v1', '/events', [
      'methods' => 'GET',
      'callback' => [$this, 'get_events'],
      'permission_callback' => function() {
        // Form editors can see available events
        return current_user_can('edit_posts');
      }
    ]);

    // Get action types (read-only, can be more permissive)
    register_rest_route('super-forms/v1', '/action-types', [
      'methods' => 'GET',
      'callback' => [$this, 'get_action_types'],
      'permission_callback' => function() {
        // Form editors can see available actions
        return current_user_can('edit_posts');
      }
    ]);
  }
}
```

#### Step 2: Add Security Logging

**File**: `/src/includes/class-automation-rest-controller.php`

```php
class SUPER_Automation_REST_Controller extends WP_REST_Controller {

  public function create_trigger($request) {
    // Log who is creating triggers (audit trail)
    $user = wp_get_current_user();
    SUPER_Automation_Logger::info('Trigger created via REST API', [
      'user_id' => $user->ID,
      'user_email' => $user->user_email,
      'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
      'trigger_name' => $request['name'],
      'event_ids' => $request['event_ids']
    ]);

    // ... rest of create logic
  }
}
```

**Testing Checklist:**
- [ ] Test unauthenticated POST to `/wp-json/super-forms/v1/automations` (should return 401/403)
- [ ] Test authenticated admin POST (should succeed)
- [ ] Test editor role POST (should fail)
- [ ] Verify security logs capture user details
- [ ] Test webhook endpoints have NO permission callback (public by design, signature verified)

---

## Refinement #6: The "Infinite Log" Problem

### Problem

**Current Code:**
```php
// Execution logs saved to wp_superforms_trigger_logs table
CREATE TABLE wp_superforms_trigger_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  trigger_id BIGINT,
  started_at DATETIME,
  completed_at DATETIME,
  status VARCHAR(20),
  context LONGTEXT,
  result LONGTEXT
);
```

**The Reality:**
- Busy contact form: **100 submissions/day**
- 1 trigger = **3,000 log rows/month**
- 10 triggers = **36,000 rows/month**
- 1 year = **432,000 rows** per site
- 100 client sites = **43 million rows** across ecosystem

**Impact:**
- Database bloat (5-50 MB per month depending on context data)
- Slow queries (no automatic cleanup)
- Hosting limits reached (shared hosts limit table sizes)
- Backup files become massive

### Solution

#### Step 1: Add Configurable Log Retention

**File**: `/src/includes/class-automation-logger.php`

```php
class SUPER_Automation_Logger {

  /**
   * Get log retention period in days (configurable via filter)
   */
  public static function get_retention_period() {
    // Default: 30 days
    $default = 30;

    // Allow customization via filter
    $days = apply_filters('super_automation_log_retention_days', $default);

    // Minimum 7 days (for debugging recent issues)
    // Maximum 365 days (prevents infinite growth)
    return max(7, min(365, (int)$days));
  }
}
```

#### Step 2: Schedule Daily Cleanup

**File**: `/src/includes/class-install.php`

```php
class SUPER_Install {

  public static function activate() {
    // ... existing activation code

    // Schedule daily log cleanup
    self::schedule_log_cleanup();
  }

  public static function schedule_log_cleanup() {
    // Only schedule if not already scheduled
    if (!wp_next_scheduled('super_automation_log_cleanup')) {
      // Run daily at 3 AM server time (low traffic period)
      wp_schedule_event(
        strtotime('tomorrow 03:00:00'),
        'daily',
        'super_automation_log_cleanup'
      );
    }
  }

  public static function deactivate() {
    // Clean up scheduled event on plugin deactivation
    $timestamp = wp_next_scheduled('super_automation_log_cleanup');
    if ($timestamp) {
      wp_unschedule_event($timestamp, 'super_automation_log_cleanup');
    }
  }
}
```

#### Step 3: Implement Cleanup Logic

**File**: `/src/includes/class-automation-logger.php`

```php
class SUPER_Automation_Logger {

  /**
   * Delete logs older than retention period
   * Called daily via WP Cron hook
   */
  public static function cleanup_old_logs() {
    global $wpdb;

    $retention_days = self::get_retention_period();

    // Delete old execution logs
    $deleted = $wpdb->query($wpdb->prepare("
      DELETE FROM {$wpdb->prefix}superforms_trigger_logs
      WHERE started_at < DATE_SUB(NOW(), INTERVAL %d DAY)
    ", $retention_days));

    // Delete old compliance audit logs (separate retention - 90 days minimum for GDPR)
    $compliance_retention = max(90, $retention_days);
    $wpdb->query($wpdb->prepare("
      DELETE FROM {$wpdb->prefix}superforms_compliance_audit
      WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
    ", $compliance_retention));

    // Log cleanup stats (meta-logging)
    if ($deleted > 0) {
      self::info('Automatic log cleanup completed', [
        'rows_deleted' => $deleted,
        'retention_days' => $retention_days,
        'next_cleanup' => wp_next_scheduled('super_automation_log_cleanup')
      ]);
    }

    // Optimize table after large deletions (improves performance)
    if ($deleted > 10000) {
      $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}superforms_trigger_logs");
    }
  }
}

// Register the cleanup hook
add_action('super_automation_log_cleanup', ['SUPER_Automation_Logger', 'cleanup_old_logs']);
```

#### Step 4: Admin Notice for Log Size

**File**: `/src/includes/class-automation-logger.php`

```php
class SUPER_Automation_Logger {

  /**
   * Show admin notice if log table is getting large
   */
  public static function maybe_show_size_warning() {
    global $wpdb;

    // Check table size
    $table_name = $wpdb->prefix . 'superforms_trigger_logs';
    $size = $wpdb->get_var("
      SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
      FROM information_schema.TABLES
      WHERE table_schema = DATABASE()
        AND table_name = '{$table_name}'
    ");

    // Warn if table > 100 MB
    if ($size > 100) {
      add_action('admin_notices', function() use ($size) {
        $retention_days = SUPER_Automation_Logger::get_retention_period();
        ?>
        <div class="notice notice-warning">
          <p>
            <strong>Super Forms:</strong> Trigger execution logs are using
            <?php echo esc_html($size); ?> MB of database space.
            Current retention: <?php echo esc_html($retention_days); ?> days.
            <a href="<?php echo admin_url('admin.php?page=super_settings&tab=triggers'); ?>">
              Reduce retention period
            </a> or
            <a href="#" onclick="if(confirm('Delete logs older than 7 days?')) { /* AJAX call */ }">
              clear old logs now
            </a>.
          </p>
        </div>
        <?php
      });
    }
  }
}

// Check on admin pages
add_action('admin_init', ['SUPER_Automation_Logger', 'maybe_show_size_warning']);
```

**Testing Checklist:**
- [ ] Create 1000 test logs with old timestamps
- [ ] Run `do_action('super_automation_log_cleanup')` manually
- [ ] Verify old logs deleted, recent logs preserved
- [ ] Test filter `super_automation_log_retention_days` works
- [ ] Verify WP Cron scheduled on activation
- [ ] Test large table warning appears at 100 MB threshold

---

## Refinement #7: User Context in "Headless" Triggers

### Problem

**Current Code:**
```php
// In SUPER_Automation_Context::get_global_context()
$user = wp_get_current_user();

return [
  'current_user_id' => $user->ID,
  'current_user_email' => $user->user_email,
  'current_user_name' => $user->display_name,
  // ...
];
```

**The Edge Cases:**
1. **Webhooks** (from Stripe/PayPal/Zapier): No logged-in user, `$user->ID = 0`
2. **Scheduled Triggers** (WP-Cron): No user session, `$user->display_name = ''`
3. **API Requests** (external systems): May not have WordPress authentication

**The Issues:**
- `$user->display_name` is **empty string** for guest users
- Some plugins throw **PHP notices** when accessing user properties without checking `exists()`
- Variables like `{current_user_name}` become blank in emails (looks broken)
- Conditional logic checking `{is_logged_in}` doesn't exist

**Impact:**
- Emails with "Hello, !" instead of "Hello, System!"
- Confusion about who triggered the action
- Missing audit trail context
- Harder to debug webhook/scheduled executions

### Solution

#### Step 1: Safe User Context Handling

**File**: `/src/includes/class-automation-context.php`

```php
class SUPER_Automation_Context {

  /**
   * Get global context variables available in all triggers
   * Handles both logged-in users and "headless" executions (webhooks, cron)
   */
  public static function get_global_context() {
    // Safely get current user (may be guest/system)
    $user = wp_get_current_user();
    $is_logged_in = $user->exists();

    return [
      // Site Information
      'site_url' => get_site_url(),
      'site_name' => get_bloginfo('name'),
      'site_description' => get_bloginfo('description'),
      'admin_email' => get_option('admin_email'),
      'home_url' => home_url(),

      // Date/Time
      'current_date' => current_time('Y-m-d'),
      'current_time' => current_time('H:i:s'),
      'current_datetime' => current_time('Y-m-d H:i:s'),
      'current_timestamp' => current_time('timestamp'),

      // Safe User Context (handles guest/system state)
      'current_user_id' => $is_logged_in ? $user->ID : 0,
      'current_user_email' => $is_logged_in ? $user->user_email : '',
      'current_user_name' => $is_logged_in ? $user->display_name : 'System/Guest',
      'current_user_login' => $is_logged_in ? $user->user_login : '',
      'current_user_role' => $is_logged_in ? implode(', ', $user->roles) : 'none',
      'is_logged_in' => $is_logged_in, // 🔑 Useful for conditional logic!

      // Request Context (for debugging)
      'request_type' => self::detect_request_type(),
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
      'ip_address' => self::get_client_ip()
    ];
  }

  /**
   * Detect how this trigger was invoked
   * @return string webhook|cron|admin|frontend|api
   */
  private static function detect_request_type() {
    // Webhook (from payment provider)
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/super-forms/v1/webhooks/') !== false) {
      return 'webhook';
    }

    // WP-Cron background job
    if (defined('DOING_CRON') && DOING_CRON) {
      return 'cron';
    }

    // WordPress admin
    if (is_admin() && !wp_doing_ajax()) {
      return 'admin';
    }

    // AJAX request
    if (wp_doing_ajax()) {
      return 'ajax';
    }

    // REST API
    if (defined('REST_REQUEST') && REST_REQUEST) {
      return 'api';
    }

    // Frontend form submission
    return 'frontend';
  }

  /**
   * Get real client IP (handles proxies/load balancers)
   */
  private static function get_client_ip() {
    // Check proxy headers first
    $headers = [
      'HTTP_CF_CONNECTING_IP', // Cloudflare
      'HTTP_X_FORWARDED_FOR',  // Standard proxy header
      'HTTP_X_REAL_IP',        // Nginx proxy
      'REMOTE_ADDR'            // Direct connection
    ];

    foreach ($headers as $header) {
      if (!empty($_SERVER[$header])) {
        // Handle comma-separated IPs (X-Forwarded-For can have multiple)
        $ips = explode(',', $_SERVER[$header]);
        $ip = trim($ips[0]);

        // Validate IP format
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
          return $ip;
        }
      }
    }

    return 'unknown';
  }
}
```

#### Step 2: Update UI to Show Headless Variables

**File**: `/src/react/admin/components/automations/VariableHelper.tsx`

```typescript
// Variable categories in help panel
const GLOBAL_VARIABLES = [
  // ... existing site/date vars ...

  // User Context Section
  {
    category: 'User Context',
    description: 'Safe for webhooks, cron, and logged-in users',
    variables: [
      {
        name: 'current_user_name',
        example: 'John Doe (or "System/Guest" if headless)',
        description: 'Current user display name, safe for webhooks/cron'
      },
      {
        name: 'is_logged_in',
        example: 'true/false',
        description: 'Whether a WordPress user triggered this (useful in conditionals)'
      },
      {
        name: 'request_type',
        example: 'webhook|cron|admin|frontend',
        description: 'How this trigger was invoked'
      }
    ]
  }
];
```

#### Step 3: Test Email Templates for Headless Context

**Example Email Template:**
```html
Subject: New Form Submission
From: {site_name} <noreply@{site_url}>

Hello Administrator,

A new form submission was received.

Submitted by: {current_user_name}
Submission type: {request_type}
Date: {current_date} at {current_time}

<!-- Conditional: Show different message for webhooks vs logged-in users -->
{{#if is_logged_in}}
  User email: {current_user_email}
  User role: {current_user_role}
{{else}}
  Source: External webhook or scheduled automation
{{/if}}

Form Data:
{field_name}: {field_value}
...
```

**Testing Checklist:**
- [ ] Test webhook trigger (Stripe webhook) - verify `current_user_name = "System/Guest"`
- [ ] Test scheduled trigger (WP-Cron) - verify `request_type = "cron"`
- [ ] Test logged-in form submission - verify `is_logged_in = true`
- [ ] Test conditional email template with `{{#if is_logged_in}}`
- [ ] Verify no PHP notices in debug log for guest users
- [ ] Test IP detection works through Cloudflare proxy

---

## Implementation Timeline

### Day 1: InnoDB Transaction Safety
- Create `class-install.php` with engine detection
- Create `SUPER_Automation_DAL::transaction()` wrapper
- Update REST controller to use transaction wrapper
- Add admin notice for MyISAM warning
- Test on fresh install + budget host

### Day 2: Variable Security
- Update `class-trigger-action-base.php` with sanitization
- Implement `sanitize_value()` method
- Update all 20 action classes with proper sanitization
- Add security tests (XSS, injection)

### Day 3: Edge Cases
- Update `match_by_config()` to return null
- Update executor to handle null gracefully
- Add logging for unmatched configs
- Test all event types with mismatched configs

### Day 4: Global Context
- Create `class-trigger-context.php`
- Update frontend validation with global vars
- Create UI helper component
- Update executor to auto-merge global context
- Integration testing

### Day 5: REST API Security
- Add `permission_callback` to all REST routes
- Implement security logging in REST controller
- Test unauthenticated access (should fail)
- Test different user roles (admin vs editor)
- Verify webhook endpoints remain public (signature verified)

### Day 6: Log Rotation & Cleanup
- Implement `get_retention_period()` with filter
- Create `schedule_log_cleanup()` in class-install.php
- Implement `cleanup_old_logs()` with OPTIMIZE TABLE
- Add admin notice for large log tables (>100 MB)
- Test manual cleanup via `do_action('super_automation_log_cleanup')`
- Verify WP Cron scheduling on activation

### Day 7: Headless User Context
- Update `class-trigger-context.php` with safe user handling
- Implement `detect_request_type()` method
- Implement `get_client_ip()` with proxy support
- Add `is_logged_in`, `request_type` to global context
- Update VariableHelper UI with headless variables
- Test webhook execution (verify "System/Guest" context)
- Test scheduled trigger (verify "cron" request_type)
- Final integration testing

---

## Success Criteria

✅ **InnoDB Enforcement:**
- Tables created with InnoDB on fresh install
- Admin warning shown if MyISAM detected
- Transactions roll back on error
- No orphaned triggers created

✅ **Security:**
- XSS attempts blocked (email, posts, webhooks)
- Context-appropriate sanitization applied
- No code injection possible via variables
- WordPress security functions used correctly

✅ **Edge Cases:**
- Mismatched webhook URLs don't execute wrong node
- Mismatched form IDs don't execute wrong workflow
- Logs show clear "no match" messages
- No silent failures or wrong executions

✅ **Global Variables:**
- All 15+ global variables available in every trigger
- No false warnings in UI validation
- UI helper shows available globals
- Backend auto-merges global context

✅ **REST API Security:**
- All REST endpoints have `permission_callback`
- Unauthenticated requests return 401/403
- Only admins can create/modify triggers
- Security audit logs capture user/IP details
- Webhook endpoints remain public (signature verified)

✅ **Log Rotation:**
- Logs automatically deleted after retention period (default 30 days)
- WP Cron scheduled on plugin activation
- Admin notice shown when logs exceed 100 MB
- Filter `super_automation_log_retention_days` allows customization
- Compliance logs retained minimum 90 days (GDPR)

✅ **Headless User Context:**
- Webhooks show "System/Guest" instead of blank user
- Scheduled triggers show "cron" request type
- No PHP notices when accessing user data in headless mode
- `{is_logged_in}` variable works in conditionals
- IP detection works through proxies/load balancers

---

## Files Created/Modified

### New Files:
- `/src/includes/class-automation-context.php`
- `/src/react/admin/components/automations/GlobalVariableHelper.tsx`

### Modified Files:
- `/src/includes/class-install.php`
- `/src/includes/class-automation-dal.php`
- `/src/includes/class-automation-rest-controller.php`
- `/src/includes/automations/class-automation-action-base.php`
- `/src/includes/class-workflow-executor.php`
- `/src/includes/automations/actions/*.php` (all 20 action classes)
- `/src/react/admin/components/automations/VisualBuilder.tsx`

---

## Risk Mitigation

**Risk**: Breaking existing triggers during security update

**Mitigation**:
- All sanitization is opt-in via `replace_variables()` options
- Existing action classes gradually updated
- Backwards compatibility maintained during transition
- Security logging shows any issues

**Risk**: Performance impact of global context merge

**Mitigation**:
- Global context cached per execution
- Merge happens once at execution start
- Negligible overhead (<1ms)

**Risk**: MyISAM hosts break workflows

**Mitigation**:
- Graceful degradation (no transaction, but still works)
- Clear admin warning
- Documentation for upgrading to InnoDB
- Support resources for budget hosts

---

## Documentation Updates

**Update**: `docs/CLAUDE.php.md`
- Add "Security: Variable Sanitization" section
- Document `replace_variables()` options
- Examples of context-aware sanitization

**Update**: `docs/CLAUDE.development.md`
- Add "InnoDB Requirement" section
- Document transaction wrapper usage
- Troubleshooting for MyISAM hosts

**Update**: `README.md`
- Add security highlights
- Document global variables
- Link to security best practices

---

## Testing Plan

### Unit Tests
- InnoDB detection logic
- Transaction rollback behavior
- Sanitization functions for each context
- Global context merging

### Integration Tests
- Full workflow with XSS attempt
- Webhook with mismatched URL
- Form with malicious input
- Global variables in all actions

### Manual Testing
- Fresh install on InnoDB host
- Fresh install on MyISAM host
- Security audit with penetration testing
- Edge case scenarios

---

## Conclusion

These four refinements transform the visual workflow system from **proof-of-concept** to **production-ready enterprise software**.

**Impact:**
- 🔒 **Security**: XSS/injection attacks blocked
- 💾 **Reliability**: Transaction safety prevents corruption
- 🎯 **Accuracy**: Wrong nodes never execute
- 🌍 **Usability**: Global variables reduce confusion

**Priority**: This phase MUST be completed before any production deployment.
