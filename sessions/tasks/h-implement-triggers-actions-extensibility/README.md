---
name: h-implement-triggers-actions-extensibility
branch: feature/h-implement-triggers-actions-extensibility
status: in-progress
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

**Architecture Decision: Dedicated Admin Page**

Triggers are managed through a dedicated "Super Forms > Triggers" admin page (NOT within form builder):
- Centralized management of all triggers across all forms
- Advanced filtering by scope (form/global/user/role), event type, status
- Bulk operations (enable/disable/delete multiple triggers)
- Execution logs and debugging view
- Form builder integration: Simple "Triggers (3)" link to dedicated page

**Benefits:**
- Global triggers visible in one place (not hidden in individual forms)
- Better performance (direct table queries vs iterating form meta)
- Scope flexibility (form-specific, global, user-based, role-based)
- Future-proof for non-form triggers (WordPress events, WooCommerce, etc.)

## Scope System Architecture

Triggers can be scoped to different contexts, determining where and when they execute:

### Core Scopes (Phase 1)

1. **`form`** - Trigger bound to specific form
   - `scope_id = form_id`
   - Example: "Send confirmation email when Contact Form #123 is submitted"
   - Permissions: Form editors can manage (future)
   - Most common use case (90% of triggers)

2. **`global`** - Trigger applies to ALL forms
   - `scope_id = NULL`
   - Example: "Log all form submissions to external analytics"
   - Permissions: Requires `manage_options`
   - Power user feature

3. **`user`** - Trigger for specific user's submissions
   - `scope_id = user_id`
   - Example: "When user #5 submits ANY form, alert their manager"
   - Permissions: User can manage their own triggers (future)
   - Personal automation

4. **`role`** - Trigger for submissions by users with specific role
   - `scope_id = NULL` (role stored in conditions)
   - Example: "When 'Employee' role submits, require manager approval"
   - Permissions: Requires `manage_options`
   - Team-based automation

### Future Scopes (Multisite)

5. **`site`** - Trigger for specific site in network
   - `scope_id = blog_id`
   - Multisite only

6. **`network`** - Network-wide trigger
   - `scope_id = NULL`
   - Super admin only

### How Scopes Work with Events

Scope determines **which submissions** trigger evaluates:
- `form` scope: Only submissions from that specific form
- `global` scope: All form submissions across all forms
- `user` scope: Only submissions by that specific user (any form)
- `role` scope: Only submissions by users with that role (any form)

Additional filtering done via **conditions** (e.g., "payment gateway is Stripe", "total > 100").

## Subtasks

This task is divided into 8 implementation phases, each documented as a separate subtask:

1. **[Foundation and Registry System](01-implement-foundation-registry.md)** - Backend foundation: Database schema, DAL, Manager, Registry, Conditions Engine, REST API (NO UI)
2. **[Action Scheduler Integration](02-implement-action-scheduler.md)** - Async execution via Action Scheduler
3. **[Execution and Logging](03-implement-execution-logging.md)** - Advanced logging, debugging, compliance
4. **[API and Security](04-implement-api-security.md)** - OAuth, encrypted credentials, rate limiting
5. **[HTTP Request Action](05-implement-http-request.md)** - Postman-like HTTP request builder
6. **[Payment and Subscription Events](06-implement-payment-subscription.md)** - Payment gateway integrations
7. **[Example Add-ons](07-implement-example-addons.md)** - Three complete example add-ons
8. **[Real-time Interactions](08-implement-realtime-interactions.md)** - Client-side triggers, validation, duplicate detection

**Note:** Admin UI implementation deferred to separate phase after Phase 1. UI will consume REST API built in Phase 1.

## Success Criteria

### Phase 1: Foundation and Registry System (Backend Only)
- [ ] Database tables created with scope support
- [ ] Data Access Layer (DAL) with scope queries
- [ ] Manager class with business logic and validation
- [ ] Registry system for events and actions
- [ ] Complex condition engine (AND/OR/NOT grouping, {tag} replacement)
- [ ] Base action class with common functionality
- [ ] Executor class (synchronous execution)
- [ ] REST API v1 endpoints (full CRUD)
- [ ] Unit tests (80%+ coverage for critical paths)
- [ ] NO backward compatibility needed (unreleased feature)
- [ ] NO admin UI (deferred to separate phase)
- [ ] See [Phase 1 subtask](01-implement-foundation-registry.md) for details

### Phase 2: Action Scheduler Integration (Async Execution)
- [ ] Executor enhanced with async execution via Action Scheduler
- [ ] Scheduled/delayed action support
- [ ] Queue management integration
- [ ] Failed action retry mechanism
- [ ] Note: Phase 1 builds synchronous executor, Phase 2 adds async layer
- [ ] See [Phase 2 subtask](02-implement-action-scheduler.md) for details

### Phase 3: Execution and Logging
- [ ] Robust execution engine with error handling
- [ ] Comprehensive logging system
- [ ] Debug mode for development
- [ ] Performance metrics tracking
- [ ] See [Phase 3 subtask](03-implement-execution-logging.md) for details

### Phase 4: API and Security
- [ ] Secure API credentials storage
- [ ] REST API endpoints
- [ ] OAuth 2.0 support
- [ ] Rate limiting implemented
- [ ] See [Phase 4 subtask](04-implement-api-security.md) for details

### Phase 5: HTTP Request Action
- [ ] Support all HTTP methods
- [ ] Multiple authentication methods
- [ ] Flexible body formats
- [ ] Response parsing and mapping
- [ ] See [Phase 5 subtask](05-implement-http-request.md) for details

### Phase 6: Payment Events
- [ ] Payment gateway events integrated
- [ ] Subscription lifecycle tracked
- [ ] Refund handling implemented
- [ ] See [Phase 6 subtask](06-implement-payment-subscription.md) for details

### Phase 7: Example Add-ons
- [ ] Slack integration example
- [ ] Google Sheets example
- [ ] CRM Connector example
- [ ] Developer documentation
- [ ] See [Phase 7 subtask](07-implement-example-addons.md) for details

### Phase 8: Real-time Interactions
- [ ] Client-side event system (keyup, change, blur, focus)
- [ ] Debouncing and caching mechanisms
- [ ] Email validation and duplicate detection
- [ ] Dynamic field population from APIs
- [ ] Network failure handling and retry logic
- [ ] See [Phase 8 subtask](08-implement-realtime-interactions.md) for details

## Implementation Order

The phases should be implemented sequentially:
1. **Phase 1 (Foundation)** - Backend infrastructure (database, DAL, manager, registry, conditions, executor, REST API) - NO UI
2. **Phase 1.5 (Admin UI)** - Dedicated "Triggers" admin page - DEFERRED until after Phase 1
3. **Phase 2 (Action Scheduler)** - Async execution layer
4. **Phase 3 (Execution/Logging)** - Advanced logging and debugging
5. **Phase 4 (API/Security)** - OAuth and encrypted credentials
6. **Phase 5 (HTTP Request)** - Most versatile action type
7. **Phase 6 (Payment Events)** - Integration with payment add-ons
8. **Phase 7 (Examples)** - Demonstrate the system to add-on developers
9. **Phase 8 (Real-time)** - Client-side interactions

**Note:** Phase 1 builds complete backend without UI. UI can be implemented as separate phase using REST API.

## Context Manifest

### How the Current Triggers/Actions System Works

The existing triggers/actions system (in `/src/includes/class-triggers.php`) is a **basic WP-Cron-based email scheduler** that works but has architectural limitations preventing extensibility.

**Current Architecture Overview:**

When a form is submitted, the system processes triggers via hardcoded logic:

1. **Event Detection**: Form submission triggers are hardcoded in form processing code (not hook-based)
2. **Action Execution**: Only one action type exists: `send_email()` method in `SUPER_Triggers` class
3. **Scheduling**: Uses custom WP-Cron (`super_scheduled_trigger_actions`) + custom post type (`sf_scheduled_action`)
4. **Storage**: Scheduled actions stored as WordPress posts with serialized data in `post_content`
5. **Execution**: Cron job queries posts by `_super_scheduled_trigger_action_timestamp` meta key

**Current Data Flow (Email Sending Example):**

```
Form Submission (Frontend)
  ↓
SUPER_Ajax::submit_form() processes submission
  ↓
(Hardcoded) Check for triggers in form settings
  ↓
SUPER_Triggers::send_email($triggerEventParameters)
  ↓
If schedule enabled:
  - Create sf_scheduled_action post
  - Store serialized action data in post_content
  - Store timestamp in _super_scheduled_trigger_action_timestamp meta
  - Store parameters in _super_scheduled_trigger_action_data meta
  ↓
WP-Cron (super_scheduled_trigger_actions) runs every minute
  ↓
SUPER_Triggers::execute_scheduled_trigger_actions()
  - Queries posts by timestamp < current_time
  - Unserializes post_content to get action options
  - Calls hardcoded method (e.g., send_email)
  - Deletes post after sending
```

**Hardcoded Events and Actions:**

The system currently recognizes these events (implicitly, not via registry):
- `sf.after.submission` - After form submission completes
- Payment events (via payment add-ons, not unified)
- Post creation events (via Front-End Posting add-on)

Available actions (all hardcoded methods):
- `send_email()` - Send scheduled emails with loop fields
- `update_contact_entry_status()` - Change entry status
- `update_created_post_status()` - Change post status (can schedule future posts)
- `update_registered_user_login_status()` - Modify user login status (incomplete)
- `update_registered_user_role()` - Change user role (incomplete)

**Critical Limitations (Why Rebuild is Needed):**

1. **No Registry Pattern**: Events/actions exist as hardcoded strings and method names - impossible for add-ons to register new ones
2. **No Action Scheduler Integration**: Uses unreliable WP-Cron instead of bundled Action Scheduler library
3. **No Extensibility Hooks**: Zero `apply_filters()` or `do_action()` hooks for add-ons to tap into
4. **Fragile Scheduling**: Stores execution logic in serialized post_content (PHP object injection risk, debugging nightmare)
5. **No Error Handling**: Failed actions just disappear - no retry, no logging, no debugging info
6. **Single Condition Logic**: Only one condition per trigger (no AND/OR/NOT combinations)
7. **No Execution Logging**: Once action runs, no audit trail exists

**Form Settings Storage:**

Triggers are stored in `_super_form_settings` post meta (NOT separate meta key like listings):

```php
$settings = get_post_meta($form_id, '_super_form_settings', true);
$triggers = $settings['triggers']; // Array of trigger configurations

// Example structure (current):
$settings['triggers'] = array(
  array(
    'event' => 'sf.after.submission',
    'name' => 'Send confirmation email',
    'conditions' => array( /* single condition */ ),
    'actions' => array(
      array(
        'action' => 'send_email', // maps to SUPER_Triggers::send_email()
        'data' => array( /* email options */ )
      )
    )
  )
);
```

**Admin UI Integration:**

The form builder has a "Triggers" tab (line 126 in `page-create-form.php`):

```php
$tabs = array(
  'builder' => 'Builder',
  'emails' => 'Emails',
  'settings' => 'Settings',
  'triggers' => 'Triggers', // ← Existing tab
);
$tabs = apply_filters('super_create_form_tabs', $tabs); // ← Hook for extensions
```

Extensions add tabs via:
```php
add_filter('super_create_form_tabs', array($this, 'add_tab'), 10, 1);
add_action('super_create_form_stripe_tab', array($this, 'add_tab_content'));
```

The triggers UI uses custom JavaScript (see `/src/assets/js/backend/create-form.js` lines 1253, 1351, 1696) to handle trigger configuration. The UI system (`SUPER_UI` class) provides:
- Repeater fields (`data-r` attribute) for dynamic trigger/action rows
- Field grouping (`data-g` attribute) for organized settings
- Filter/toggle logic for conditional UI display
- i18n support for translating trigger configurations

**Existing Hook Points (Minimal):**

The current system has only 3 filter hooks:
- `super_before_sending_email_body_filter` - Modify email body before sending
- `super_before_sending_email_attachments_filter` - Modify attachments array
- `super_before_email_loop_data_filter` - Process individual fields in email loop

**No hooks exist for:**
- Registering new events
- Registering new actions
- Extending conditional logic
- Adding custom UI elements
- Hooking into execution lifecycle

### Payment Add-on Event System (Current State)

Payment add-ons have **their own event handling** - NOT integrated with triggers system.

**Stripe Webhook Flow:**

```php
// /src/includes/extensions/stripe/stripe.php
public static function handle_webhooks($wp) {
  // Listens for query var 'sfstripewebhook=true'
  $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

  switch($event->type) {
    case 'checkout.session.completed':
      // Process payment success
      SUPER_Common::cleanupFormSubmissionInfo($session->metadata->sfsi_id, $event->type);
      break;
    case 'customer.subscription.created':
    case 'customer.subscription.updated':
      self::afterSubscriptionUpdated($event);
      break;
    case 'customer.subscription.deleted':
      self::afterSubscriptionDeleted($event);
      break;
    // ... more events
  }
}
```

**Required Stripe Events** (hardcoded array):
```php
public static $required_events = array(
  'checkout.session.async_payment_failed',
  'checkout.session.async_payment_succeeded',
  'checkout.session.completed',
  'customer.subscription.created',
  'customer.subscription.updated',
  'customer.subscription.deleted',
  'invoice.paid',
  'invoice.payment_failed',
  'payment_intent.succeeded',
  'payment_intent.payment_failed',
);
```

**Subscription Status Actions:**

The Stripe extension updates entry/post status based on subscription state:

```php
// Entry status mapping
$s['subscription']['entry_status'] = array(
  array('status' => 'active', 'value' => 'active'),
  array('status' => 'canceled', 'value' => 'canceled'),
  // ...
);

// Post status mapping
$s['subscription']['post_status'] = array(
  array('status' => 'active', 'value' => 'publish'),
  array('status' => 'canceled', 'value' => 'draft'),
  // ...
);
```

**PayPal Integration:**

PayPal add-on (`/src/add-ons/super-forms-paypal/`) uses similar webhook pattern:
- Custom post type: `super_paypal_txn` for transactions
- Custom post type: `super_paypal_sub` for subscriptions
- IPN (Instant Payment Notification) webhook handler
- No integration with triggers system

**WooCommerce Integration:**

WooCommerce add-on (`/src/add-ons/super-forms-woocommerce/`) hooks into WooCommerce events:
```php
add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_contact_entry_link_to_order'));
add_filter('super_after_contact_entry_data_filter', array($this, 'add_entry_order_link'));
```

Creates WooCommerce orders after form submission but no unified event system.

### Action Scheduler Integration Points

**Already Bundled and Active:**

Action Scheduler v3.9.3 is loaded early in plugin bootstrap:

```php
// /src/super-forms.php line 169-173
if (file_exists(SUPER_PLUGIN_DIR . '/includes/lib/action-scheduler/action-scheduler.php')) {
  require_once SUPER_PLUGIN_DIR . '/includes/lib/action-scheduler/action-scheduler.php';
}
```

**Currently Used For:**

1. **EAV Migration** (`SUPER_Background_Migration`):
   ```php
   // Hook registration
   add_action(self::AS_BATCH_HOOK, array(__CLASS__, 'process_batch_action'));
   add_action(self::AS_HEALTH_CHECK_HOOK, array(__CLASS__, 'health_check_action'));

   // Scheduling
   as_schedule_single_action(time(), self::AS_BATCH_HOOK, $args, 'superforms');
   ```

2. **Cleanup Jobs**:
   ```php
   as_schedule_recurring_action(
     time(),
     300, // Every 5 minutes
     'super_cleanup_old_serialized_data',
     array(),
     'superforms'
   );
   ```

**Action Scheduler Functions Available:**

```php
// Single execution
as_schedule_single_action($timestamp, $hook, $args, $group);

// Recurring execution
as_schedule_recurring_action($timestamp, $interval_seconds, $hook, $args, $group);

// Cancel actions
as_unschedule_action($hook, $args, $group);
as_unschedule_all_actions($hook);

// Query actions
as_get_scheduled_actions($args);
as_next_scheduled_action($hook, $args, $group);
```

**Action Scheduler Tables** (created automatically):
- `wp_actionscheduler_actions` - Queue of scheduled actions
- `wp_actionscheduler_logs` - Execution history and errors
- `wp_actionscheduler_groups` - Action grouping
- `wp_actionscheduler_claims` - Lock mechanism for concurrent processing

**WP-Cron Fallback System:**

The plugin has a fallback for when WP-Cron fails (`SUPER_Cron_Fallback` class):
- Detects stale migration jobs (15 minute threshold)
- Provides 4 processing modes: sync, async, monitor, trigger
- Shows admin notice with "Database Upgrade Required" message
- Enables Action Scheduler async mode when `DISABLE_WP_CRON` detected
- Uses AJAX endpoints for manual trigger and progress polling

This fallback system should be **reused for trigger actions** - if Action Scheduler queue stalls, same detection/remediation applies.

### Data Access Layer Patterns

**CRITICAL**: All contact entry data operations must use `SUPER_Data_Access` abstraction layer.

**Why This Matters:**

The plugin is in the middle of migrating from serialized meta storage to EAV tables. During this transition:
- Some entries stored in `_super_contact_entry_data` meta (serialized)
- Some entries stored in `wp_superforms_entry_data` table (EAV)
- The Data Access Layer abstracts this complexity

**Required Usage Pattern:**

```php
// ✅ CORRECT - Always use Data Access Layer
$entry_data = SUPER_Data_Access::get_entry_data($entry_id);
SUPER_Data_Access::save_entry_data($entry_id, $data);
SUPER_Data_Access::update_entry_field($entry_id, 'field_name', 'new_value');
SUPER_Data_Access::delete_entry_data($entry_id);

// ❌ WRONG - Never access storage directly
$data = get_post_meta($entry_id, '_super_contact_entry_data', true); // Breaks during migration!
update_post_meta($entry_id, '_super_contact_entry_data', $data); // Doesn't write to EAV!
```

**Data Access Layer Methods:**

```php
class SUPER_Data_Access {
  // Get entry data (auto-detects EAV or serialized)
  public static function get_entry_data($entry_id);

  // Save entry data (dual-write during migration, EAV-only after)
  public static function save_entry_data($entry_id, $data, $force_format = null);

  // Update single field (more efficient than full save)
  public static function update_entry_field($entry_id, $field_name, $field_value);

  // Delete entry data (from both storage methods)
  public static function delete_entry_data($entry_id);

  // Internal methods (don't call directly)
  private static function get_from_eav_tables($entry_id);
  private static function get_from_serialized($entry_id);
  private static function save_to_eav_tables($entry_id, $data);
  private static function save_to_serialized($entry_id, $data);
}
```

**Migration State Detection:**

```php
$migration = get_option('superforms_eav_migration');
// States: 'not_started', 'in_progress', 'completed'
// Storage: 'serialized' or 'eav'

// Auto-routing based on state:
// - not_started: serialized only
// - in_progress: dual-write (both serialized + EAV)
// - completed (eav): EAV only
// - completed (serialized): rolled back to serialized
```

**Entry Data Structure:**

```php
// Structure returned by get_entry_data():
array(
  'field_name' => array(
    'name' => 'field_name',
    'value' => 'field value',
    'label' => 'Field Label',
    'type' => 'text',
    'exclude' => 0, // 0=include, 1=attachment only, 2=exclude all, 3=exclude admin
  ),
  // ... more fields
)
```

**Trigger Actions Must Use Data Access Layer:**

When implementing actions that read/write entry data:

```php
// Example: Update entry field action
public static function update_entry_field_action($params) {
  $entry_id = $params['entry_id'];
  $field_name = $params['field_name'];
  $new_value = $params['value'];

  // ✅ Use Data Access Layer
  SUPER_Data_Access::update_entry_field($entry_id, $field_name, $new_value);

  // ❌ DON'T do this
  // $data = get_post_meta($entry_id, '_super_contact_entry_data', true);
  // $data[$field_name]['value'] = $new_value;
  // update_post_meta($entry_id, '_super_contact_entry_data', $data);
}
```

### Database Schema Patterns

**Existing EAV Table** (for contact entries):

```sql
CREATE TABLE wp_superforms_entry_data (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  entry_id BIGINT(20) UNSIGNED NOT NULL,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  field_name VARCHAR(255) NOT NULL,
  field_value LONGTEXT,
  field_type VARCHAR(50),
  field_label VARCHAR(255),
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY entry_id (entry_id),
  KEY form_id (form_id),
  KEY field_name (field_name),
  KEY entry_field (entry_id, field_name),
  KEY field_value (field_value(191)),
  KEY form_field_filter (form_id, field_name, field_value(191)),
  KEY form_entry_field (form_id, entry_id, field_name)
) ENGINE=InnoDB;
```

**Table Creation Pattern** (from `SUPER_Install`):

```php
private static function create_tables() {
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $table_name = $wpdb->prefix . 'superforms_entry_data';
  $sql = "CREATE TABLE $table_name (...) ENGINE=InnoDB $charset_collate;";

  dbDelta($sql); // WordPress function that handles CREATE/ALTER intelligently

  // Run schema upgrades for existing installations
  self::upgrade_database_schema();
}
```

**Schema Upgrade Pattern** (safe for existing installs):

```php
private static function upgrade_database_schema() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'superforms_entry_data';

  // Check if table exists
  $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
  if ($table_exists !== $table_name) return;

  // Check if column exists before adding
  $column_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'form_id'",
    DB_NAME, $table_name
  ));

  if (!$column_exists) {
    $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN form_id BIGINT(20)...");
  }
}
```

**Installation Hooks:**

```php
// Plugin activation
register_activation_hook(__FILE__, array('SUPER_Install', 'install'));

// Plugin deactivation
register_deactivation_hook(__FILE__, array('SUPER_Install', 'deactivate'));
```

**Required Tables for New System:**

Based on the subtask requirements, we'll need:

1. `wp_superforms_triggers` - Trigger configuration registry
2. `wp_superforms_trigger_logs` - Execution history and debugging
3. `wp_superforms_api_credentials` - Encrypted API key storage
4. `wp_superforms_http_logs` - HTTP request/response logging

These should follow the same patterns as the entry_data table.

### Form Builder Integration Patterns

**Tab Registration System:**

Extensions add tabs to the form builder via filter + action pattern:

```php
// 1. Add tab to tabs array
add_filter('super_create_form_tabs', array($this, 'add_tab'), 10, 1);
public static function add_tab($tabs) {
  $tabs['stripe'] = esc_html__('Stripe', 'super-forms');
  return $tabs;
}

// 2. Provide tab content
add_action('super_create_form_stripe_tab', array($this, 'add_tab_content'));
public static function add_tab_content($atts) {
  // $atts contains: form_id, settings, woocommerce, listings, pdf, stripe, etc.
  $s = $atts['stripe']; // Settings for this tab

  // Render UI using SUPER_UI helper or custom HTML
  echo '<div class="super-stripe-settings">...</div>';
}
```

**Settings Storage Pattern:**

Most extensions store settings in separate meta keys (NOT in `_super_form_settings`):

```php
// Listings uses separate meta key
$listings = get_post_meta($form_id, '_listings', true);

// WooCommerce uses separate meta key
$woocommerce = get_post_meta($form_id, '_woocommerce', true);

// Stripe uses separate meta key
$stripe = get_post_meta($form_id, '_stripe', true);

// PDF uses separate meta key
$pdf = get_post_meta($form_id, '_pdf', true);

// BUT: Triggers currently stored in main settings
$settings = get_post_meta($form_id, '_super_form_settings', true);
$triggers = $settings['triggers'];
```

**Decision Point**: Should new trigger system use separate `_triggers` meta key or remain in `_super_form_settings`?

**Recommendation**: Use separate `_triggers` meta key because:
1. Consistent with other extensions (listings, stripe, pdf, woocommerce)
2. Allows for complex nested structures without bloating main settings
3. Easier to version and migrate independently
4. Better for i18n translation mapping (see Listings migration context)

**UI Rendering Helper** (`SUPER_UI` class):

```php
// Loop over settings nodes and render fields
SUPER_UI::loop_over_tab_setting_nodes($s, $nodes, $prefix);

// Key attributes for field organization:
// - data-g="group_name" - Group fields together
// - data-r="repeater_name" - Repeater field for dynamic rows
// - data-f="filter_condition" - Conditional display logic

// Example structure:
$nodes = array(
  array(
    'group' => true,
    'group_name' => 'email_settings',
    'nodes' => array(
      array('name' => 'to', 'type' => 'text', 'label' => 'To'),
      array('name' => 'subject', 'type' => 'text', 'label' => 'Subject'),
    )
  ),
  array(
    'type' => 'repeater',
    'name' => 'actions',
    'nodes' => array(
      array('name' => 'action_type', 'type' => 'dropdown'),
      array('name' => 'action_data', 'type' => 'textarea'),
    )
  )
);
```

**JavaScript Integration:**

Form builder JavaScript (`/src/assets/js/backend/create-form.js`) handles:

```javascript
// Get settings for a specific tab
SUPER.get_tab_settings({}, 'triggers', undefined, undefined, returnData);

// Special handling for triggers/stripe/listings/woocommerce/pdf tabs
if (slug === 'triggers' || slug === 'stripe') {
  // These tabs have special serialization logic
}

// i18n translation support
if (SUPER.ui.i18n.translating && slug === 'triggers') {
  // Load translated version of settings
}

// Save form data
params.form_data.triggers = JSON.parse(params.form_data.triggers);
```

**Filter System for Conditional UI:**

```html
<!-- Show field only when specific condition met -->
<div class="sfui-setting" data-f='{"field":"payment_enabled","value":"true"}'>
  <!-- Field only visible when payment_enabled = true -->
</div>

<!-- Multiple conditions (JSON array) -->
<div data-f='[{"field":"type","value":"stripe"},{"field":"mode","value":"test"}]'>
  <!-- AND logic: show when type=stripe AND mode=test -->
</div>
```

JavaScript evaluates `data-f` attributes to show/hide fields dynamically.

### Admin Settings and Global Configuration

**Global Settings Access:**

```php
// Get global plugin settings
$global_settings = SUPER_Settings::get_settings();

// Specific setting access
$setting_value = !empty($global_settings['setting_key']) ? $global_settings['setting_key'] : 'default';

// Example: Stripe global webhook secret
$endpoint_secret = $global_settings['stripe_' . $mode . '_webhook_secret'];
```

**Settings Structure:**

Settings are organized in tabs (similar to form builder):

```php
// /src/includes/class-settings.php
public static function get_settings() {
  $settings = get_option('super_settings');
  if (!$settings) {
    $settings = self::get_defaults();
  }
  return $settings;
}

public static function get_defaults() {
  return array(
    'smtp_enabled' => '',
    'smtp_host' => '',
    'file_upload_remove_from_media' => 'yes',
    // ... hundreds of settings
  );
}
```

**Adding Global Settings:**

Extensions can add settings to global settings page:

```php
add_filter('super_settings_after_custom_js_filter', array($this, 'add_settings'), 10, 2);

public static function add_settings($html, $settings) {
  $html .= '<div class="super-settings-stripe">';
  $html .= '<h3>Stripe Settings</h3>';
  // Render settings fields
  $html .= '</div>';
  return $html;
}
```

**Secrets Management:**

The plugin has a "Secrets" tab for sensitive data storage:

```php
// Form-level secrets
$localSecrets = get_post_meta($form_id, '_super_form_secrets', true);

// Global secrets (shared across all forms)
$globalSecrets = get_option('super_global_secrets');

// Structure:
array(
  'stripe_secret_key' => 'sk_test_...',
  'api_token' => 'token_...',
  // Stored as plain text in database - NOT encrypted currently
)
```

**SECURITY CONCERN**: Current secrets are stored as plain text in the database. The new system should implement proper encryption for API credentials.

### Hook and Filter Registration Patterns

**WordPress Hook Conventions:**

```php
// Action hooks - do something at specific point
do_action('super_before_save_form', $form_id, $settings);
add_action('super_before_save_form', 'my_callback', 10, 2);

// Filter hooks - modify data
$settings = apply_filters('super_form_settings_filter', $settings, $form_id);
add_filter('super_form_settings_filter', 'my_callback', 10, 2);
```

**Naming Pattern in Super Forms:**

```php
// Core plugin hooks - prefix with 'super'
do_action('super_loaded');
do_action('super_after_contact_entry_saved', $entry_id, $form_id);
apply_filters('super_before_sending_email_body_filter', $body, $context);

// Extension-specific hooks - prefix with extension name
do_action('super_stripe_loaded');
do_action('super_woocommerce_order_created', $order_id);
```

**Registration Pattern for Extensibility:**

For the new trigger/action system to be extensible, we need:

```php
// ✅ Proposed registry pattern
class SUPER_Trigger_Registry {
  private static $events = array();
  private static $actions = array();

  // Allow add-ons to register events
  public static function register_event($event_name, $args) {
    self::$events[$event_name] = $args;
    do_action('super_trigger_event_registered', $event_name, $args);
  }

  // Allow add-ons to register actions
  public static function register_action($action_name, $callback, $args) {
    self::$actions[$action_name] = array(
      'callback' => $callback,
      'args' => $args,
    );
    do_action('super_trigger_action_registered', $action_name, $args);
  }

  // Fire event and execute matching triggers
  public static function fire_event($event_name, $context) {
    do_action('super_before_trigger_event', $event_name, $context);

    // Find triggers for this event
    // Execute matching actions

    do_action('super_after_trigger_event', $event_name, $context);
  }
}

// Usage by add-ons:
add_action('plugins_loaded', 'my_addon_register_triggers');
function my_addon_register_triggers() {
  // Register custom event
  SUPER_Trigger_Registry::register_event('my_addon.custom_event', array(
    'label' => 'Custom Event Occurred',
    'description' => 'Fires when something happens',
    'context_fields' => array('field1', 'field2'),
  ));

  // Register custom action
  SUPER_Trigger_Registry::register_action('my_addon.custom_action',
    array('My_Addon', 'execute_action'),
    array(
      'label' => 'Do Custom Action',
      'fields' => array(/* UI fields */),
    )
  );
}
```

**Action Priority and Ordering:**

WordPress hooks support priority (default 10):

```php
add_action('super_trigger_event', 'low_priority_callback', 5);
add_action('super_trigger_event', 'default_priority_callback', 10);
add_action('super_trigger_event', 'high_priority_callback', 20);
```

The new trigger system should support action ordering within a trigger (not just hook priority).

### Error Handling and WP_Error Pattern

**WordPress Error Convention:**

```php
// Return WP_Error on failure
function my_function($entry_id) {
  if (!$entry_id) {
    return new WP_Error(
      'invalid_entry_id',
      __('Invalid entry ID provided', 'super-forms'),
      array('entry_id' => $entry_id) // Optional data
    );
  }

  // Check for errors
  if (is_wp_error($result)) {
    error_log('Error: ' . $result->get_error_message());
    $error_data = $result->get_error_data();
  }

  return $result;
}
```

**Data Access Layer Example:**

```php
// From SUPER_Data_Access class
public static function get_entry_data($entry_id) {
  if (empty($entry_id) || !is_numeric($entry_id)) {
    return new WP_Error('invalid_entry_id', __('Invalid entry ID', 'super-forms'));
  }

  $post = get_post($entry_id);
  if (!$post || $post->post_type !== 'super_contact_entry') {
    return new WP_Error('entry_not_found', __('Entry not found', 'super-forms'));
  }

  return $data;
}
```

**Trigger Actions Should Use WP_Error:**

```php
public static function execute_action($action_name, $context) {
  if (!isset(self::$actions[$action_name])) {
    return new WP_Error(
      'action_not_found',
      sprintf(__('Action "%s" not registered', 'super-forms'), $action_name),
      array('action' => $action_name, 'registered_actions' => array_keys(self::$actions))
    );
  }

  $callback = self::$actions[$action_name]['callback'];
  $result = call_user_func($callback, $context);

  if (is_wp_error($result)) {
    // Log error
    self::log_action_error($action_name, $result);
    return $result;
  }

  return $result;
}
```

### Critical Integration Points

**Form Submission Hook Points:**

The form submission flow is where triggers must be integrated:

```php
// /src/includes/class-ajax.php - submit_form() method
// Current flow (simplified):
1. Validate form data
2. Save contact entry (wp_insert_post)
3. Save entry data (SUPER_Data_Access::save_entry_data)
4. Process payment (if enabled)
5. Send emails (if configured)
6. Execute triggers (hardcoded check)
7. Redirect or show message

// Where to fire events:
do_action('super_before_save_contact_entry', $data, $settings);
do_action('super_after_save_contact_entry', $entry_id, $data, $settings);
do_action('super_before_payment_processing', $entry_id, $payment_data);
do_action('super_after_payment_success', $entry_id, $payment_data);
do_action('super_after_payment_failed', $entry_id, $error);
```

**Payment Integration Points:**

```php
// Stripe webhook events → trigger events
add_action('super_stripe_checkout_completed', function($session_data) {
  SUPER_Trigger_Registry::fire_event('payment.stripe.completed', array(
    'entry_id' => $session_data['entry_id'],
    'amount' => $session_data['amount'],
    'currency' => $session_data['currency'],
    'session' => $session_data,
  ));
});

// Similar for subscription events
add_action('super_stripe_subscription_created', function($sub_data) {
  SUPER_Trigger_Registry::fire_event('subscription.stripe.created', $sub_data);
});
```

**Email Reminder Pattern (Migrate to Action Scheduler):**

The Email Reminders add-on uses WP-Cron - should migrate to Action Scheduler pattern:

```php
// Current (WP-Cron):
if (!wp_next_scheduled('super_cron_reminders')) {
  wp_schedule_event(time(), 'every_minute', 'super_cron_reminders');
}

// New (Action Scheduler):
as_schedule_recurring_action(
  time(),
  60, // Every minute
  'super_process_scheduled_triggers',
  array(),
  'superforms_triggers'
);
```

**Background Processing Pattern:**

From the migration system:

```php
// Schedule batch processing
public static function schedule_batch() {
  $timestamp = time() + 5; // Start in 5 seconds

  as_schedule_single_action(
    $timestamp,
    self::AS_BATCH_HOOK, // 'superforms_migrate_batch'
    array('batch_size' => 50),
    'superforms' // Group name
  );
}

// Process batch
public static function process_batch_action() {
  // Acquire lock to prevent concurrent execution
  $lock = get_transient(self::LOCK_KEY);
  if ($lock) return; // Another batch is running

  set_transient(self::LOCK_KEY, time(), self::LOCK_DURATION);

  try {
    // Do work
    self::process_entries(50);

    // Schedule next batch if more work remains
    if (self::has_more_work()) {
      self::schedule_batch();
    }
  } finally {
    delete_transient(self::LOCK_KEY);
  }
}
```

**Retry and Error Handling:**

Action Scheduler has built-in retry:

```php
// Failed actions are automatically retried
// Configure retry strategy via Action Scheduler settings

// Check if action failed
$action_id = as_next_scheduled_action('my_hook');
if ($action_id) {
  $logs = as_get_action_logs($action_id);
  foreach ($logs as $log) {
    if ($log->get_message_type() === 'error') {
      // Handle error
    }
  }
}
```

### Technical Reference Details

**Required Function Signatures:**

```php
// Event registration
SUPER_Trigger_Registry::register_event(
  string $event_id,           // 'payment.stripe.succeeded'
  array $args = array(
    'label' => string,        // 'Stripe Payment Succeeded'
    'description' => string,  // 'Fires when payment completes'
    'context' => array(),     // Available data fields
    'category' => string,     // 'payment', 'form', 'user', etc.
  )
): void

// Action registration
SUPER_Trigger_Registry::register_action(
  string $action_id,          // 'http.request'
  callable $callback,         // Array($class, $method) or function
  array $args = array(
    'label' => string,        // 'HTTP Request'
    'description' => string,  // 'Make HTTP API call'
    'fields' => array(),      // UI field definitions
    'category' => string,     // 'integration', 'notification', etc.
    'async' => bool,          // Execute via Action Scheduler?
  )
): void

// Fire event
SUPER_Trigger_Registry::fire_event(
  string $event_id,           // 'form.submitted'
  array $context = array()    // Event data
): array // Returns array of execution results

// Execute action
SUPER_Trigger_Executor::execute_action(
  string $action_id,          // 'send.email'
  array $action_config,       // Configuration from trigger
  array $context              // Event context data
): bool|WP_Error
```

**Database Table Schemas:**

```sql
-- Trigger configuration (replaces post type storage)
CREATE TABLE wp_superforms_triggers (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  event_id VARCHAR(100) NOT NULL,
  trigger_name VARCHAR(255) NOT NULL,
  enabled TINYINT(1) DEFAULT 1,
  conditions TEXT,              -- JSON encoded conditions
  actions TEXT,                 -- JSON encoded actions
  priority INT DEFAULT 10,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY form_id (form_id),
  KEY event_id (event_id),
  KEY enabled (enabled)
) ENGINE=InnoDB;

-- Execution logging
CREATE TABLE wp_superforms_trigger_logs (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  trigger_id BIGINT(20) UNSIGNED NOT NULL,
  entry_id BIGINT(20) UNSIGNED,
  event_id VARCHAR(100) NOT NULL,
  action_id VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL,  -- 'success', 'failed', 'pending'
  error_message TEXT,
  execution_time FLOAT,         -- In seconds
  context_data LONGTEXT,        -- JSON
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY trigger_id (trigger_id),
  KEY entry_id (entry_id),
  KEY status (status),
  KEY created_at (created_at)
) ENGINE=InnoDB;

-- API credentials storage
CREATE TABLE wp_superforms_api_credentials (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  credential_name VARCHAR(100) NOT NULL,
  credential_type VARCHAR(50) NOT NULL, -- 'api_key', 'oauth2', 'basic_auth'
  credential_data TEXT,                 -- Encrypted JSON
  form_id BIGINT(20) UNSIGNED,          -- NULL = global
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY credential_name (credential_name),
  KEY form_id (form_id)
) ENGINE=InnoDB;
```

**File Structure:**

```
/src/includes/
  class-trigger-registry.php       - Event/action registration
  class-trigger-executor.php       - Execution engine
  class-trigger-logger.php         - Logging and debugging
  class-trigger-conditions.php     - Conditional logic engine
  class-api-credentials.php        - Encrypted credential storage

  /triggers/
    class-base-action.php          - Abstract base class
    /actions/
      class-action-http-request.php
      class-action-send-email.php
      class-action-webhook.php
    /events/
      class-event-form-submitted.php
      class-event-payment-completed.php
```

**Action Scheduler Usage:**

```php
// Schedule action execution
as_schedule_single_action(
  $timestamp,                          // When to execute
  'superforms_execute_trigger_action', // Hook name
  array(
    'trigger_id' => $trigger_id,
    'action_id' => $action_id,
    'context' => $context,
  ),
  'superforms_triggers'                // Group for organization
);

// Register hook handler
add_action('superforms_execute_trigger_action', array('SUPER_Trigger_Executor', 'execute_scheduled_action'), 10, 1);

// Query scheduled actions
$pending = as_get_scheduled_actions(array(
  'hook' => 'superforms_execute_trigger_action',
  'status' => 'pending',
  'group' => 'superforms_triggers',
));
```

## User Notes

### Architecture Considerations
- **No Backward Compatibility Required**: System unreleased, clean slate implementation
- **Dedicated Admin Page**: Triggers managed through "Super Forms > Triggers" menu (NOT form builder tabs)
- **Form Builder Link**: Add "Triggers (3)" link in forms list table pointing to dedicated page
- **Database Storage**: Triggers in custom tables (NOT post meta) for performance and flexibility
- **Scope System**: Support form/global/user/role scopes from day one
- **Action Scheduler Already Bundled**: v3.9.3 at `/src/includes/lib/action-scheduler/`
- **Existing Payment Add-ons**: PayPal, Stripe, WooCommerce integrations already exist
- **Data Layer Architecture**: Use `SUPER_Data_Access` layer for all entry data operations, NOT direct `update_post_meta`
- **Background Processing**: Use Action Scheduler for ALL background tasks (no WP-Cron)
- **REST API First**: Build REST API in Phase 1, UI consumes it in Phase 1.5
- **Permissions**: Start simple (`manage_options` only), enhance later

### Development Guidelines
- Follow WordPress coding standards
- Use WordPress APIs where possible (wpdb, options, etc.)
- **Entry Data Storage**: Always use `SUPER_Data_Access::update_entry_data()` for storing contact entry data
- **Scheduled Tasks**: All background/scheduled tasks must use Action Scheduler, not WP-Cron
- Ensure PHP 7.4+ compatibility
- Write unit tests for all new functionality
- Document all hooks and filters for developers

### Impact on Existing Features

The new triggers/actions system will affect several existing features:

1. **Form Submissions**
   - Current: Direct email sending and basic actions
   - Enhanced: Event-driven architecture with retry capability
   - Migration: Existing forms continue working, new features opt-in

2. **Email Notifications**
   - Current: Synchronous sending during form submission
   - Enhanced: Queued via Action Scheduler for reliability
   - Benefit: Better performance, automatic retries, failure tracking

3. **Payment Processing**
   - Current: Individual add-on handling
   - Enhanced: Unified event system for all payment gateways
   - Integration: Existing payment add-ons emit standardized events

4. **Data Storage**
   - Current: EAV tables via Data Access Layer
   - Enhanced: Trigger execution logs in separate tables
   - Compatibility: Full compatibility with existing EAV migration system

5. **Background Jobs**
   - Current: Mix of WP-Cron and Action Scheduler
   - Enhanced: Consolidate all background jobs to Action Scheduler
   - Benefit: Consistent queue management and monitoring

### Epic Use Cases

#### Epic 1: Enterprise Integration Suite
**User Stories:**
- As an enterprise user, I need to sync form submissions to Salesforce CRM
- As a marketing manager, I need to segment leads into MailChimp lists based on form responses
- As a support team, I need tickets created in Zendesk from support forms

**Edge Cases:**
- API rate limiting and retry strategies
- Credential rotation and OAuth token refresh
- Bulk operations for historical data sync
- Field mapping with data type conversions
- Handling API downtime gracefully

#### Epic 2: Advanced Conditional Logic
**User Stories:**
- As a form admin, I need complex conditions (AND/OR/NOT logic)
- As a business owner, I need different actions based on calculated values
- As a developer, I need custom PHP conditions for complex rules

**Edge Cases:**
- Circular condition dependencies
- Performance with 50+ conditions per form
- Condition evaluation order and precedence
- Dynamic conditions based on user roles/capabilities
- Time-based conditions (business hours, dates)

#### Epic 3: Real-time Form Interactions
**User Stories:**
- As a user, I want instant field validation via external APIs
- As a form designer, I need to show/hide fields based on external data
- As an admin, I need real-time duplicate detection during typing

**Edge Cases:**
- Debouncing rapid keystrokes
- Network latency and timeout handling
- Caching API responses for performance
- Fallback behavior when services unavailable
- Security for client-side API calls

#### Epic 4: Payment Lifecycle Management
**User Stories:**
- As a business owner, I need actions triggered on successful payments
- As an accountant, I need invoice generation on subscription renewals
- As a customer service rep, I need alerts for failed payments

**Edge Cases:**
- Partial refunds and proration
- Currency conversion handling
- Payment method changes mid-subscription
- Dunning process for failed payments
- Regulatory compliance (PCI, GDPR)

#### Epic 5: Developer Ecosystem
**User Stories:**
- As a developer, I need to create custom actions for clients
- As an agency, I need to package reusable trigger/action combinations
- As a plugin author, I need to integrate my plugin with Super Forms

**Edge Cases:**
- Version compatibility between add-ons
- Namespace conflicts between custom actions
- Performance impact of multiple add-ons
- Action execution priority and ordering
- Debugging tools for custom actions

#### Epic 6: Audit and Compliance
**User Stories:**
- As a compliance officer, I need audit logs of all trigger executions
- As an admin, I need to track data flow through integrations
- As a security auditor, I need to verify API credential usage

**Edge Cases:**
- Log retention policies and rotation
- GDPR data deletion requests
- Encrypted storage of sensitive logs
- Performance with millions of log entries
- Log export for external analysis

### Testing Requirements

**Unit Tests (80%+ coverage):**
- `SUPER_Trigger_DAL` - Database CRUD operations with scope queries
- `SUPER_Trigger_Manager` - Business logic, validation, permissions
- `SUPER_Trigger_Registry` - Event/action registration and retrieval
- `SUPER_Trigger_Conditions` - Complex condition evaluation (AND/OR/NOT, tag replacement)
- `SUPER_Trigger_Executor` - Synchronous action execution
- `SUPER_Trigger_Action_Base` - Abstract base class implementation
- `SUPER_Trigger_REST_Controller` - REST API endpoints (CRUD, validation)

**Integration Tests:**
- Test with high-volume forms (1000+ submissions/day)
- Test with multiple active triggers per form
- Test scope isolation (form triggers don't fire for other forms)
- Test global triggers fire for all forms
- Test Action Scheduler async execution (Phase 2)
- Test payment event reliability
- Test third-party add-on registration via hooks

**Performance Tests:**
- Trigger lookup performance with 100+ triggers
- Condition evaluation with deeply nested groups
- REST API response time (<200ms for list queries)

**Critical Path Tests:**
- **Data Layer Testing**: Verify all entry operations use `SUPER_Data_Access` methods
- **Background Job Testing**: Confirm all scheduled tasks use Action Scheduler
- **Scope Security**: Users cannot access triggers outside their permission scope
- **Tag Replacement**: All {tag} patterns replaced correctly in conditions/actions
- **Error Handling**: WP_Error returned consistently, no PHP fatal errors

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Task created and broken into 8 subtasks
- [2025-11-20] All subtask files created with detailed implementation plans
- [2025-11-20] Architecture refined: Scope system, dedicated admin page, backend-first approach
- [2025-11-20] README updated with scope architecture, no backward compatibility, REST API focus
- [2025-11-21] **Phase 1 COMPLETE**: All 7 foundation classes + 19 actions implemented (~5,788 lines)
- [2025-11-21] End-to-end tests passing on dev server, test infrastructure ready
- [2025-11-21] **Status Review**: Backend production-ready, next priority = Testing + Admin UI
- [2025-11-21] **Event Tests Enhanced**: Added 8 comprehensive tests - 23 total covering performance (<2ms), edge cases, validation