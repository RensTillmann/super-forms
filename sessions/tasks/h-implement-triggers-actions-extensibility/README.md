---
name: h-implement-triggers-actions-extensibility
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
---

# Implement Extensible Triggers/Actions System

## Problem/Goal

The current triggers/actions system is functionally solid but architecturally closed - it works well for the 5 built-in actions but provides zero extensibility for add-ons. Events and actions are hardcoded arrays with no registration API, making it impossible for add-ons to integrate properly.

**Core Issues:**
- Add-ons cannot register new events or actions
- No hooks/filters for extending the triggers UI
- Custom WP-Cron scheduling instead of Action Scheduler (unreliable)
- No action result tracking or error handling
- Limited conditional logic (single condition only)
- No support for complex integrations (API requests, response parsing, field mapping)

**Important Note:** The triggers/actions system has NOT been released yet - no customers are using it. This means we can refactor completely without migration scripts or backward compatibility concerns.

**Vision:**
Build a modular add-on ecosystem where external plugins can easily extend Super Forms with:
- CRM integrations (Salesforce, HubSpot, Pipedrive)
- AI services (OpenAI, Claude, Gemini, Groq)
- Google services (Sheets, Docs, Drive, Calendar)
- Email marketing (MailChimp, SendGrid, Mailster)
- Custom HTTP requests (Postman-like functionality)
- Real-time field interactions (on keypress, button click)

## Success Criteria

### **Phase 1: Foundation, Registry System & Database Architecture**

**Implementation Order:** Follow these steps sequentially (each builds on previous):

**Step 1 - Database Schema:**
- [ ] Create file: `/src/includes/class-trigger-data-access.php`
- [ ] Design custom tables (NO migration needed - system not released):
  ```sql
  wp_super_triggers:
    id BIGINT AUTO_INCREMENT PRIMARY KEY
    name VARCHAR(255) NOT NULL
    event VARCHAR(100) NOT NULL -- 'sf.after.submission', 'wc.order.completed', etc.
    scope ENUM('form', 'global', 'specific') NOT NULL
    form_id BIGINT NULL -- for 'form' scope
    form_ids TEXT NULL -- JSON array for 'specific' scope
    enabled TINYINT(1) DEFAULT 1
    execution_order INT DEFAULT 0
    created_at DATETIME
    updated_at DATETIME
    INDEX idx_event (event)
    INDEX idx_form_id (form_id)
    INDEX idx_enabled_event (enabled, event)

  wp_super_trigger_actions:
    id BIGINT AUTO_INCREMENT PRIMARY KEY
    trigger_id BIGINT NOT NULL
    action_type VARCHAR(100) NOT NULL -- 'send_email', 'http_request', etc.
    execution_order INT DEFAULT 0
    enabled TINYINT(1) DEFAULT 1
    conditions_data TEXT NULL -- JSON conditions
    settings_data TEXT NOT NULL -- JSON action settings
    i18n_data TEXT NULL -- JSON translations
    created_at DATETIME
    updated_at DATETIME
    FOREIGN KEY (trigger_id) REFERENCES wp_super_triggers(id) ON DELETE CASCADE
    INDEX idx_trigger_id (trigger_id)
    INDEX idx_action_type (action_type)
  ```
- [ ] Add table creation to `/src/includes/class-install.php` in `create_tables()` method:
  ```php
  // Example location: after line 56 where other tables are created
  $charset_collate = $wpdb->get_charset_collate();
  $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}super_triggers (...) $charset_collate;";
  dbDelta($sql);
  ```
- [ ] Remove ALL postmeta trigger code from `/src/includes/class-common.php`:
  - Delete `get_form_triggers()` method (lines 237-256)
  - Delete `save_form_triggers()` method (lines 312-410)
  - These will be replaced by Data Access Layer

**Step 2 - Data Access Layer:**
- [ ] Implement `SUPER_Trigger_Data_Access` class with these EXACT methods:
  ```php
  class SUPER_Trigger_Data_Access {
      public static function get_triggers($form_id) {
          // Returns array of triggers for form (including global)
          // Query both 'form' scope with matching form_id
          // AND 'global' scope AND 'specific' scope containing form_id
      }

      public static function save_trigger($trigger_data) {
          // Insert or update trigger + actions (transaction)
          // Delete existing actions and re-insert (simpler than diff)
      }

      public static function delete_trigger($trigger_id) {
          // Delete trigger (actions cascade delete automatically)
      }

      public static function get_triggers_by_event($event_name) {
          // For reporting: "Show all forms using event X"
      }

      public static function get_all_triggers_using_action($action_type) {
          // For impact analysis: "Which forms use OpenAI action?"
      }
  }
  ```
- [ ] Update `/src/includes/class-common.php` to use Data Access Layer:
  ```php
  // Line ~237, replace get_form_triggers() internals:
  public static function get_form_triggers($form_id) {
      return SUPER_Trigger_Data_Access::get_triggers($form_id);
  }
  ```

**Step 3 - Base Action Class:**
- [ ] Create file: `/src/includes/triggers/class-trigger-action-base.php`
  ```php
  abstract class SUPER_Trigger_Action_Base {
      // REQUIRED methods that MUST be implemented:
      abstract public function get_id();        // Return: 'send_email'
      abstract public function get_label();     // Return: 'Send Email'
      abstract public function execute($data, $config, $context);
      abstract public function get_settings_schema(); // Return SFUI array

      // OPTIONAL methods with defaults:
      public function get_group() { return 'General'; }
      public function supports_scheduling() { return false; }
      public function validate_config($config) { return true; }
  }
  ```
- [ ] Pattern to follow: Similar to EAV migration's `SUPER_Background_Migration` base class structure

**Step 4 - Registry Pattern:**
- [ ] Create file: `/src/includes/class-trigger-registry.php`
  ```php
  class SUPER_Trigger_Registry {
      private static $instance = null;
      private $events = array();
      private $actions = array();

      public static function instance() {
          if (null === self::$instance) {
              self::$instance = new self();
          }
          return self::$instance;
      }

      public function register_action(SUPER_Trigger_Action_Base $action) {
          $this->actions[$action->get_id()] = $action;
      }

      public function register_event($id, $label, $group = 'Custom') {
          if (!isset($this->events[$group])) {
              $this->events[$group] = array();
          }
          $this->events[$group][$id] = $label;
      }

      // Called in class-pages.php to get dropdown options
      public function get_events_for_ui() {
          return apply_filters('super_trigger_events', $this->events);
      }

      public function get_actions_for_ui() {
          $ui_array = array();
          foreach ($this->actions as $action) {
              $group = $action->get_group();
              if (!isset($ui_array[$group])) {
                  $ui_array[$group] = array();
              }
              $ui_array[$group][$action->get_id()] = $action->get_label();
          }
          return apply_filters('super_trigger_actions', $ui_array);
      }
  }
  ```
- [ ] Initialize registry in main plugin file `/src/super-forms.php` (around line 200):
  ```php
  // After other includes
  require_once 'includes/class-trigger-registry.php';
  require_once 'includes/triggers/class-trigger-action-base.php';

  // In init() method:
  $registry = SUPER_Trigger_Registry::instance();

  // Register core events
  $registry->register_event('sf.after.submission', 'After Form Submission', 'Super Forms');
  $registry->register_event('sf.before.submission', 'Before Form Submission', 'Super Forms');

  // Allow add-ons to register
  do_action('super_trigger_register', $registry);
  ```

**Step 5 - Refactor Existing Actions:**
- [ ] Create these files in `/src/includes/triggers/actions/`:
  ```
  class-action-send-email.php
  class-action-update-entry-status.php
  class-action-update-post-status.php
  class-action-update-user-login.php
  class-action-update-user-role.php
  ```
- [ ] Example structure for `class-action-send-email.php`:
  ```php
  class SUPER_Action_Send_Email extends SUPER_Trigger_Action_Base {
      public function get_id() {
          return 'send_email';
      }

      public function get_label() {
          return __('Send Email', 'super-forms');
      }

      public function get_group() {
          return 'Communication';
      }

      public function execute($data, $config, $context) {
          // Move existing code from SUPER_Triggers::send_email()
          // Return standardized result:
          return array(
              'success' => true,
              'message' => 'Email sent successfully',
              'data' => array('recipients' => $to)
          );
      }

      public function get_settings_schema() {
          // Return SFUI nodes array (move from class-pages.php)
          return array(
              'to' => array(
                  'type' => 'text',
                  'label' => 'Recipients',
                  'description' => 'Comma-separated emails'
              ),
              // ... other email settings
          );
      }

      public function supports_scheduling() {
          return true; // Email can be scheduled
      }
  }
  ```
- [ ] Register core actions in `/src/super-forms.php`:
  ```php
  // After registry init:
  require_once 'includes/triggers/actions/class-action-send-email.php';
  $registry->register_action(new SUPER_Action_Send_Email());
  // ... register other 4 actions
  ```

**Step 6 - Update Backend UI:**
- [ ] Modify `/src/includes/class-pages.php` method `triggers_tab()` (line ~1447):
  ```php
  // Replace hardcoded events array (lines 1533-1568) with:
  $registry = SUPER_Trigger_Registry::instance();
  $events = $registry->get_events_for_ui();

  // Replace hardcoded actions array (lines 1570-1582) with:
  $actions = $registry->get_actions_for_ui();

  // For action settings, dynamically generate based on selected action:
  foreach ($registry->get_actions() as $action) {
      $settings = $action->get_settings_schema();
      // Convert to SFUI nodes with filter: 'action;' . $action->get_id()
  }
  ```
- [ ] Test that UI still works with refactored system

**Step 7 - Update Execution Flow:**
- [ ] Modify `/src/includes/class-common.php` method `triggerEvent()` (line ~467):
  ```php
  // Replace method_exists check with:
  $registry = SUPER_Trigger_Registry::instance();
  $action_obj = $registry->get_action($action_config['action']);
  if ($action_obj) {
      $result = $action_obj->execute($sfsi, $action_config, $context);
      // Log result (Phase 3 will add logging table)
  }
  ```

**Step 8 - Create Example Add-on:**
- [ ] Create `/examples/super-forms-example-addon/` directory with:
  ```php
  // super-forms-example-addon.php
  add_action('super_trigger_register', function($registry) {
      // Register custom event
      $registry->register_event(
          'my_addon.custom_event',
          'My Custom Event',
          'My Add-on'
      );

      // Register custom action
      require_once 'includes/class-my-custom-action.php';
      $registry->register_action(new My_Custom_Action());
  });
  ```
- [ ] Include documentation on how add-ons should structure their code

**Testing Checklist:**
- [ ] Form submission still triggers email action
- [ ] Scheduled emails still work (via existing WP-Cron - Phase 2 converts to Action Scheduler)
- [ ] UI shows dynamically registered events/actions
- [ ] Custom add-on successfully registers and executes
- [ ] No PHP errors or warnings
- [ ] Performance: Form submission time not increased by more than 100ms

### **Phase 2: Action Scheduler Integration**

**Why:** Current WP-Cron system unreliable (depends on site traffic). Action Scheduler already bundled (v3.9.3).

- [ ] Delete custom post type code from `/src/includes/class-triggers.php`:
  - Remove `execute_scheduled_trigger_actions()` method entirely
  - Remove WP-Cron hook registration
- [ ] Update scheduling in `triggerEvent()` method:
  ```php
  // Old way (DELETE THIS):
  wp_insert_post(array('post_type' => 'sf_scheduled_action'...));

  // New way (REPLACE WITH):
  as_schedule_single_action(
      $timestamp,
      'super_trigger_execute_action',
      array(
          'trigger_id' => $trigger['id'],
          'action_index' => $action_index,
          'form_id' => $form_id,
          'entry_id' => $entry_id,
          'data' => $sfsi
      ),
      'super-forms'  // Group name for management
  );
  ```
- [ ] Add handler in `/src/super-forms.php`:
  ```php
  add_action('super_trigger_execute_action', array('SUPER_Triggers', 'execute_scheduled_action'));
  ```
- [ ] Configure retry logic (in handler):
  ```php
  public static function execute_scheduled_action($args) {
      try {
          // Execute action
          $result = $action_obj->execute(...);
          if (!$result['success']) {
              throw new Exception($result['message']);
          }
      } catch (Exception $e) {
          // Action Scheduler will auto-retry 3 times
          throw $e; // Re-throw for AS to handle
      }
  }
  ```
- [ ] Test: Schedule email for +5 minutes, verify execution without page visits

### **Phase 3: Execution Logging & Error Tracking**

**Purpose:** Debug failed actions, monitor performance, provide audit trail.

- [ ] Create execution log table in `/src/includes/class-install.php`:
  ```sql
  wp_super_trigger_execution_log:
    id BIGINT AUTO_INCREMENT PRIMARY KEY
    trigger_name VARCHAR(255)
    action_name VARCHAR(100)
    form_id BIGINT
    entry_id BIGINT
    event VARCHAR(100)
    executed_at DATETIME
    execution_time_ms INT
    status ENUM('success', 'failed', 'skipped')
    result_data TEXT -- JSON
    error_message TEXT
    INDEX idx_form_event (form_id, event)
    INDEX idx_status_date (status, executed_at)
  ```
- [ ] Add logging to execution flow:
  ```php
  // In triggerEvent() after action execution:
  $start = microtime(true);
  $result = $action_obj->execute(...);
  $duration = (microtime(true) - $start) * 1000;

  $wpdb->insert($wpdb->prefix . 'super_trigger_execution_log', array(
      'trigger_name' => $trigger['name'],
      'action_name' => $action_config['action'],
      'form_id' => $form_id,
      'entry_id' => $entry_id,
      'event' => $trigger['event'],
      'executed_at' => current_time('mysql'),
      'execution_time_ms' => $duration,
      'status' => $result['success'] ? 'success' : 'failed',
      'result_data' => json_encode($result['data']),
      'error_message' => $result['error'] ?? null
  ));
  ```
- [ ] Add simple admin page to view logs (can enhance later):
  ```php
  // Add submenu under Super Forms
  add_submenu_page('super_forms', 'Trigger Logs', 'Trigger Logs',
                   'manage_options', 'super_trigger_logs',
                   array($this, 'render_logs_page'));
  ```
- [ ] Add cleanup cron (30-day retention):
  ```php
  // Daily cleanup
  $wpdb->query("DELETE FROM {$wpdb->prefix}super_trigger_execution_log
                WHERE executed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
  ```

### **Phase 4: HTTP Request Action (Enables 80% of Integrations)**

**This single action enables:** CRM sync, AI services, webhooks, API calls, etc.

- [ ] Create `/src/includes/triggers/actions/class-action-http-request.php`:
  ```php
  class SUPER_Action_HTTP_Request extends SUPER_Trigger_Action_Base {
      public function get_id() { return 'http_request'; }
      public function get_label() { return 'HTTP Request'; }
      public function get_group() { return 'Integrations'; }

      public function get_settings_schema() {
          return array(
              'url' => array('type' => 'text', 'label' => 'URL'),
              'method' => array('type' => 'select', 'options' =>
                  array('GET', 'POST', 'PUT', 'DELETE')),
              'headers' => array('type' => 'repeater', 'fields' => array(
                  'name' => array('type' => 'text'),
                  'value' => array('type' => 'text')
              )),
              'body' => array('type' => 'textarea'),
              'auth_type' => array('type' => 'select', 'options' =>
                  array('none', 'basic', 'bearer', 'api_key')),
              'auth_value' => array('type' => 'text')
          );
      }

      public function execute($data, $config, $context) {
          // Replace {tags} in all fields
          $url = SUPER_Common::email_tags($config['url'], $data, $context);
          $body = SUPER_Common::email_tags($config['body'], $data, $context);

          // Build headers
          $headers = array();
          if ($config['auth_type'] === 'bearer') {
              $headers['Authorization'] = 'Bearer ' . $config['auth_value'];
          }

          // Make request
          $response = wp_remote_request($url, array(
              'method' => $config['method'],
              'headers' => $headers,
              'body' => $body,
              'timeout' => 30
          ));

          if (is_wp_error($response)) {
              return array('success' => false, 'error' => $response->get_error_message());
          }

          return array(
              'success' => true,
              'data' => array(
                  'status' => wp_remote_retrieve_response_code($response),
                  'body' => wp_remote_retrieve_body($response)
              )
          );
      }
  }
  ```
- [ ] Document example configurations in `/docs/http-action-examples.md`:
  ```
  ## Mailchimp Subscribe
  URL: https://us1.api.mailchimp.com/3.0/lists/{list_id}/members
  Method: POST
  Headers: Authorization: Bearer {api_key}
  Body: {"email_address": "{email}", "status": "subscribed"}

  ## OpenAI Generate Text
  URL: https://api.openai.com/v1/chat/completions
  Method: POST
  Headers: Authorization: Bearer {api_key}
  Body: {"model": "gpt-3.5-turbo", "messages": [{"role": "user", "content": "{prompt}"}]}
  ```

### **Phase 5: Enhanced Conditional Logic (Optional - Can Defer)**

**Note:** Current single condition may be sufficient for v1. Consider deferring to v2.

- [ ] Extend conditions_data JSON structure to support groups
- [ ] Update UI with nested repeaters
- [ ] Update evaluation logic to handle AND/OR chains
- [ ] Maintain backward compatibility with old format

### **Phase 6: Example Add-ons (Critical for Adoption)**

Create 3 fully-functional example add-ons in `/examples/`:

**1. Simple Example - Custom Notification:**
- [ ] `/examples/super-forms-slack-notification/`
- [ ] Single action: Send Slack message
- [ ] Shows basic registration pattern
- [ ] ~50 lines of code total

**2. Medium Example - Google Sheets:**
- [ ] `/examples/super-forms-google-sheets/`
- [ ] OAuth flow example
- [ ] Append row action
- [ ] Shows token storage pattern
- [ ] ~200 lines of code

**3. Complex Example - CRM Integration:**
- [ ] `/examples/super-forms-crm-connector/`
- [ ] Multiple actions (create contact, update contact, add to list)
- [ ] Field mapping UI
- [ ] Error handling and retry
- [ ] ~500 lines of code

Each example MUST include:
- README.md with installation steps
- Inline code comments explaining patterns
- Common pitfalls section

## Context Manifest
<!-- Added by context-gathering agent -->

## User Notes

**Critical Requirements:**
- Each subtask/phase must be broken down in **high detail** with specific implementation steps
- Architecture must be **compatible and flexible** for many add-ons and use cases
- Include **multiple concrete examples** for each extensibility pattern
- Design patterns should support both simple add-ons (single action) and complex ones (multiple events/actions with UI)
- Code examples should demonstrate real-world scenarios (CRM, AI, Google services)

## Common Pitfalls to Avoid (IMPORTANT FOR JUNIOR DEVELOPERS)

### Database Pitfalls:
- **DON'T** use `$wpdb->query()` for INSERT/UPDATE - use `$wpdb->insert()` and `$wpdb->update()` (automatic escaping)
- **DON'T** forget to use `$wpdb->prepare()` for SELECT queries with variables
- **DON'T** store PHP objects directly - use `json_encode()` for arrays/objects in TEXT columns
- **DO** use transactions when updating trigger + actions together
- **DO** check if tables exist before querying (especially during plugin activation)

### Registry Pitfalls:
- **DON'T** instantiate action classes multiple times - registry should store single instance
- **DON'T** register actions before `init` hook - dependencies might not be loaded
- **DON'T** forget to check if action exists before calling `execute()`
- **DO** use `class_exists()` before requiring action class files

### Action Development Pitfalls:
- **DON'T** throw uncaught exceptions in `execute()` - return error array instead
- **DON'T** make external API calls without timeout (default to 30s)
- **DON'T** store sensitive data (API keys) in action settings - use WordPress options
- **DO** always return standardized format: `['success' => bool, 'message' => string, 'data' => array]`
- **DO** use `SUPER_Common::email_tags()` to replace ALL {field_tags} in settings

### UI Integration Pitfalls:
- **DON'T** hardcode action IDs in JavaScript - use data attributes
- **DON'T** forget to escape output in admin pages (use `esc_html()`, `esc_attr()`)
- **DO** use SFUI node system for settings (maintains consistency)
- **DO** test UI with multiple add-ons registered simultaneously

### Performance Pitfalls:
- **DON'T** load all triggers on every page - only on form submission
- **DON'T** make synchronous API calls during form submission - use Action Scheduler
- **DO** add indexes on frequently queried columns
- **DO** limit execution log queries (use LIMIT, date ranges)

## Security Checklist (MUST COMPLETE)

- [ ] **SQL Injection Prevention:**
  - All database queries use `$wpdb->prepare()` or safe methods (`insert()`, `update()`)
  - Never concatenate user input into SQL strings
  - Example: `$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id)`

- [ ] **XSS Prevention:**
  - All output escaped: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
  - JavaScript data: `wp_json_encode()` and `esc_js()`
  - Never trust data from database - escape on output

- [ ] **CSRF Protection:**
  - Admin pages use nonces: `wp_nonce_field('super_trigger_save')`
  - AJAX requests verify nonces: `check_ajax_referer('super_forms_ajax')`

- [ ] **Capability Checks:**
  - Admin pages: `current_user_can('manage_options')`
  - Form submission: Existing Super Forms permission system

- [ ] **Data Validation:**
  - Sanitize all input: `sanitize_text_field()`, `intval()`, `sanitize_email()`
  - Validate action settings in `validate_config()` method
  - Reject invalid JSON in settings_data and conditions_data

- [ ] **API Security:**
  - Store API keys encrypted in database (use WordPress salt)
  - Never log sensitive data (API keys, passwords)
  - Rate limiting for external API calls (prevent abuse)

## Architecture Decision Records (ADRs)

### ADR-001: Custom Tables vs Postmeta
**Decision:** Use custom tables from the start
**Rationale:**
- System not released, no migration needed
- Better querying capabilities (find triggers by event/action type)
- Cleaner data structure (no serialization)
- Better performance at scale

### ADR-002: Action Scheduler vs WP-Cron
**Decision:** Use Action Scheduler (already bundled)
**Rationale:**
- More reliable (doesn't depend on traffic)
- Built-in retry mechanism
- Better logging and monitoring
- Already included in plugin (v3.9.3)

### ADR-003: Registry Pattern for Extensibility
**Decision:** Singleton registry with filters/actions
**Rationale:**
- Standard WordPress pattern (familiar to developers)
- Easy for add-ons to extend
- Central management of events/actions
- Supports lazy loading

### ADR-004: Standardized Action Return Format
**Decision:** All actions return `['success' => bool, 'message' => string, 'data' => array]`
**Rationale:**
- Consistent error handling
- Enables action chaining (future)
- Simplifies logging
- Clear success/failure state

## Work Log
- [2025-11-20] Task created based on comprehensive triggers/actions system analysis
