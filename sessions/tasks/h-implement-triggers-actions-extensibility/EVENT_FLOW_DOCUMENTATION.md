# Event Flow Documentation - Super Forms Triggers System

## Overview

This document maps the exact order of event firing for all form submission scenarios. Events are fired by `SUPER_Trigger_Executor::fire_event()` at strategic points in the submission flow.

## Event Categories

### Form Lifecycle Events
- `form.before_submit` - Before validation, after data collected
- `form.submitted` - After validation passes, before entry creation
- `form.spam_detected` - Spam detection triggered
- `form.validation_failed` - Validation errors occurred
- `form.duplicate_detected` - Duplicate entry detected

### Entry Events
- `entry.created` - Entry post created in database
- `entry.saved` - Entry data persisted (fires for new AND updated entries)
- `entry.updated` - Existing entry edited (only for updates)
- `entry.status_changed` - Entry status modified
- `entry.deleted` - Entry permanently deleted (admin or listings)

### File Events
- `file.uploaded` - File successfully attached to media library
- `file.upload_failed` - File upload error (size exceeded, wp_handle_upload error)
- `file.deleted` - File removed (submission delete option, vcard cleanup)

### Payment Events (Stripe)
- `payment.stripe.checkout_completed` - Stripe checkout session completed
- `payment.stripe.payment_succeeded` - Payment intent succeeded
- `payment.stripe.payment_failed` - Payment intent failed
- `subscription.stripe.created` - Subscription created
- `subscription.stripe.updated` - Subscription updated
- `subscription.stripe.cancelled` - Subscription cancelled
- `subscription.stripe.invoice_paid` - Invoice payment succeeded
- `subscription.stripe.invoice_failed` - Invoice payment failed

### Payment Events (PayPal)
- `payment.paypal.capture_completed` - Payment capture completed
- `payment.paypal.capture_denied` - Payment capture denied
- `payment.paypal.capture_refunded` - Payment refunded
- `subscription.paypal.created` - Subscription created
- `subscription.paypal.activated` - Subscription activated
- `subscription.paypal.cancelled` - Subscription cancelled
- `subscription.paypal.suspended` - Subscription suspended
- `subscription.paypal.payment_failed` - Subscription payment failed

---

## Scenario 1: Normal Form Submission (New Entry, No Files)

**Flow:** User submits form → validation passes → entry created → success

```
┌─────────────────────────────────────────────────────────────┐
│ 1. form.before_submit                                       │
│    Location: class-ajax.php:4680                            │
│    Context: { form_id, raw_data, user_id, user_ip }        │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. form.submitted                                           │
│    Location: class-ajax.php:4693                            │
│    Context: { form_id, entry_id=0, data, settings }        │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. entry.created                                            │
│    Location: class-ajax.php:4899                            │
│    Context: { entry_id, form_id, entry_status }            │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. entry.saved                                              │
│    Location: class-ajax.php:5170                            │
│    Context: { entry_id, form_id, entry_data, is_update=false }│
└─────────────────────────────────────────────────────────────┘
                          ↓
                    ✅ SUCCESS
```

---

## Scenario 2: Form Submission with File Uploads

**Flow:** User submits form with files → files uploaded → entry created → success

```
┌─────────────────────────────────────────────────────────────┐
│ 1. form.before_submit                                       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. form.submitted                                           │
└─────────────────────────────────────────────────────────────┘
                          ↓
         ┌────────────────────────────────┐
         │ FILE PROCESSING LOOP           │
         │ (for each uploaded file)       │
         └────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. file.uploaded (× N files)                                │
│    Location: class-ajax.php:4581                            │
│    Context: { attachment_id, form_id, field_name,          │
│              file_name, file_type, file_size, file_url }    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. entry.created                                            │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. entry.saved                                              │
└─────────────────────────────────────────────────────────────┘
                          ↓
                    ✅ SUCCESS
```

---

## Scenario 3: Spam Detection (Multi-Method)

**Flow:** User submits form → spam detection runs → REJECTED (silent or with message)

```
         ⚠️  SPAM DETECTION AFTER VALIDATION
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. form.spam_detected                                       │
│    Location: class-ajax.php:3307                            │
│    Context: { form_id, detection_method, spam_score,       │
│              spam_details, form_data, session_key,          │
│              user_ip, timestamp }                           │
└─────────────────────────────────────────────────────────────┘
                          ↓
      Trigger Actions Can Execute (e.g., Abort Submission)
                          ↓
                    ❌ EXIT (default: silent)

⚠️  NO ENTRY CREATED
    - form.before_submit does NOT fire
    - Spam detection runs BEFORE entry creation
    - Default behavior: silent rejection (exit without message)
    - Optional: Abort action can show custom message
```

**Detection Methods** (checked in order):
1. Honeypot (super_hp, website_url_hp, fax_number_hp)
2. Time-based (session start timestamp vs minimum seconds)
3. IP Blacklist (exact, CIDR, wildcards)
4. Keyword Filter (dual threshold)
5. Akismet (optional API call)

**Note:** Spam detection runs in `submit_form()` at line 3298, immediately AFTER validation passes but BEFORE entry creation. Uses `SUPER_Spam_Detector::check()` with session data for time-based detection.

---

## Scenario 4: Validation Failure (CSRF Expired)

**Flow:** User submits form → CSRF token invalid → REJECTED immediately

```
         ⚠️  CSRF CHECK HAPPENS FIRST
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. form.validation_failed                                   │
│    Location: class-ajax.php:3189                            │
│    Context: { form_id, error_type='csrf_expired',          │
│              error_message, user_id, user_ip }              │
└─────────────────────────────────────────────────────────────┘
                          ↓
                ❌ ERROR MESSAGE RETURNED

⚠️  NO OTHER EVENTS FIRE
    - form.before_submit does NOT fire
    - No entry created
    - User sees "session expired" error
```

**Note:** CSRF validation occurs in `submit_form_checks()` at line 3189, BEFORE the main `submit_form()` method is called.

---

## Scenario 5: Duplicate Entry Detected

**Flow:** User submits form → entry created → duplicate detected → entry deleted

```
┌─────────────────────────────────────────────────────────────┐
│ 1. form.before_submit                                       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. form.submitted                                           │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. entry.created                                            │
│    (Entry created, but will be deleted)                     │
└─────────────────────────────────────────────────────────────┘
                          ↓
         DUPLICATE TITLE CHECK RUNS
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. form.duplicate_detected                                  │
│    Location: class-ajax.php:5012                            │
│    Context: { form_id, entry_id, duplicate_field,          │
│              duplicate_value, comparison_scope, data }      │
└─────────────────────────────────────────────────────────────┘
                          ↓
              wp_delete_post() called
                          ↓
                ❌ ERROR MESSAGE RETURNED

⚠️  EVENTS THAT DO NOT FIRE:
    - entry.saved (entry deleted before save)
```

**Important:** The entry IS created (so `entry.created` fires), but it's immediately deleted when duplicate is detected. The `entry.saved` event does NOT fire because the duplicate check happens before `SUPER_Data_Access::save_entry_data()` is called.

---

## Scenario 6: Entry Update (Editing Existing Entry)

**Flow:** User edits entry → validation passes → entry updated → success

```
┌─────────────────────────────────────────────────────────────┐
│ 1. form.before_submit                                       │
│    Context: { entry_id > 0 (indicates update) }            │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. form.submitted                                           │
│    Context: { entry_id > 0 }                                │
└─────────────────────────────────────────────────────────────┘
                          ↓
      ⚠️  SKIP entry.created (entry already exists)
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. entry.updated                                            │
│    Location: class-ajax.php:5091                            │
│    Context: { entry_id, form_id, entry_data }              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. entry.saved                                              │
│    Location: class-ajax.php:5100                            │
│    Context: { entry_id, form_id, entry_data, is_update=true }│
└─────────────────────────────────────────────────────────────┘
                          ↓
        IF STATUS CHANGED (optional)
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. entry.status_changed (conditional)                       │
│    Location: class-ajax.php:5147                            │
│    Context: { entry_id, form_id, previous_status,          │
│              new_status }                                    │
│    Condition: Only fires if previous_status != new_status   │
└─────────────────────────────────────────────────────────────┘
                          ↓
                    ✅ SUCCESS
```

**Key Differences from New Entry:**
- `entry.created` does NOT fire (entry already exists)
- `entry.updated` fires (specific to updates)
- `entry.saved` fires with `is_update=true`
- `entry.status_changed` fires only if status actually changed

---

## Event Flow Decision Tree

```
                    FORM SUBMITTED
                          |
                          v
            ┌─────────────────────────┐
            │  Honeypot field filled? │
            └─────────────────────────┘
                    |         |
                   YES        NO
                    |         |
                    v         v
          spam_detected   ┌──────────┐
                ↓         │CSRF valid?│
               EXIT       └──────────┘
                                |    |
                              YES    NO
                                |    |
                                v    v
                         before_submit  validation_failed
                                |              ↓
                                v             EXIT
                           submitted
                                |
                    ┌───────────┴───────────┐
                    |                       |
              entry_id = 0           entry_id > 0
             (NEW ENTRY)             (UPDATE)
                    |                       |
                    v                       v
              Files uploaded?          updated
                    |                       |
              ┌─────┴─────┐                v
             YES          NO              saved
              |            |                |
              v            v                v
        file_uploaded  entry_created   status_changed?
              |            |                |
              └────────────┘          ┌─────┴─────┐
                    |                YES          NO
                    v                 |            |
              entry_created           v            v
                    |          status_changed    (end)
                    v                 |
              Duplicate check         v
                    |                (end)
              ┌─────┴─────┐
             YES          NO
              |            |
              v            v
        duplicate_detected  saved
              |            |
              v            v
          wp_delete_post  (end)
              |
              v
            EXIT
```

---

## Event Context Data Reference

### Common Fields (All Events)
```php
array(
    'timestamp' => current_time('mysql'),  // MySQL datetime
    'user_id' => get_current_user_id(),    // 0 for guests
    'user_ip' => SUPER_Common::real_ip()   // User IP address
)
```

### Form Events Context

**form.before_submit**
```php
array(
    'form_id' => int,
    'raw_data' => array,  // $_POST data
    'timestamp' => string,
    'user_id' => int,
    'user_ip' => string
)
```

**form.submitted**
```php
array(
    'form_id' => int,
    'entry_id' => int,       // 0 for new, >0 for update
    'sfsi_id' => string,     // Session ID
    'data' => array,         // Processed form data
    'settings' => array,     // Form settings
    'timestamp' => string,
    'user_id' => int,
    'user_ip' => string
)
```

**form.spam_detected**
```php
array(
    'form_id' => int,
    'detection_method' => string,  // 'honeypot', 'time', 'ip_blacklist', 'keywords', 'akismet'
    'spam_score' => float,         // 0.0 to 1.0 confidence score
    'spam_details' => string,      // Human-readable detection reason
    'form_data' => array,          // Complete submitted form data
    'session_key' => string,       // Session identifier (if session exists)
    'user_ip' => string,
    'timestamp' => string          // MySQL datetime
)
```

**form.validation_failed**
```php
array(
    'form_id' => int,
    'error_type' => string,      // 'csrf_expired', etc.
    'error_message' => string,
    'timestamp' => string,
    'user_id' => int,
    'user_ip' => string
)
```

**form.duplicate_detected**
```php
array(
    'form_id' => int,
    'entry_id' => int,              // ID of created (then deleted) entry
    'duplicate_field' => string,    // 'entry_title'
    'duplicate_value' => string,    // The duplicate value
    'comparison_scope' => string,   // 'form', 'global', etc.
    'data' => array,                // Form data
    'timestamp' => string,
    'user_id' => int
)
```

### Entry Events Context

**entry.created**
```php
array(
    'entry_id' => int,
    'form_id' => int,
    'entry_status' => string,  // 'super_unread'
    'timestamp' => string,
    'user_id' => int,
    'user_ip' => string
)
```

**entry.saved**
```php
array(
    'entry_id' => int,
    'form_id' => int,
    'entry_data' => array,    // All field data
    'is_update' => bool,      // false=new, true=update
    'timestamp' => string,
    'user_id' => int
)
```

**entry.updated**
```php
array(
    'entry_id' => int,
    'form_id' => int,
    'entry_data' => array,
    'timestamp' => string,
    'user_id' => int
)
```

**entry.status_changed**
```php
array(
    'entry_id' => int,
    'form_id' => int,
    'previous_status' => string,
    'new_status' => string,
    'timestamp' => string,
    'user_id' => int
)
```

**entry.deleted**
```php
array(
    'entry_id' => int,
    'form_id' => int,
    'deleted_by' => int,      // User ID who deleted
    'method' => string,       // 'trash', 'permanent', 'listings'
    'timestamp' => string,
    'user_id' => int
)
```

### File Events Context

**file.uploaded**
```php
array(
    'attachment_id' => int,
    'form_id' => int,
    'field_name' => string,
    'file_name' => string,
    'file_type' => string,    // MIME type
    'file_size' => int,       // bytes
    'file_url' => string,
    'timestamp' => string,
    'user_id' => int
)
```

**file.upload_failed**
```php
array(
    'form_id' => int,
    'file_name' => string,
    'error_message' => string,
    'error_code' => string,   // 'size_exceeded', 'wp_upload_error'
    'timestamp' => string,
    'user_id' => int
)
```

**file.deleted**
```php
array(
    'attachment_id' => int,   // May be null
    'form_id' => int,
    'file_url' => string,
    'deleted_by' => int,
    'reason' => string,       // 'submission_delete_option', 'vcard_delete_option'
    'timestamp' => string,
    'user_id' => int
)
```

---

## WordPress Action Hooks

Each event also fires corresponding WordPress actions that developers can hook into:

```php
// Specific event hook
do_action('super_trigger_event_form.submitted', $context);

// Generic event hook (all events)
do_action('super_trigger_event', $event_id, $context);
```

### Example Hook Usage

```php
// Listen to specific event
add_action('super_trigger_event_entry.created', function($context) {
    error_log('New entry created: ' . $context['entry_id']);
});

// Listen to all events
add_action('super_trigger_event', function($event_id, $context) {
    error_log("Event fired: {$event_id}");
}, 10, 2);
```

---

## Testing Event Firing

### Debug Logging Setup

Add to `wp-config.php` or theme's `functions.php`:

```php
// Enable WordPress debug logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Log all trigger events
add_action('super_trigger_event', function($event_id, $context) {
    error_log("🔥 EVENT: {$event_id}");
    error_log("📦 CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT));
}, 10, 2);
```

### Expected Log Output (Normal Submission)

```
🔥 EVENT: form.before_submit
📦 CONTEXT: {
    "form_id": 123,
    "raw_data": {...},
    "user_id": 1,
    "user_ip": "192.168.1.1"
}

🔥 EVENT: form.submitted
📦 CONTEXT: {
    "form_id": 123,
    "entry_id": 0,
    "data": {...}
}

🔥 EVENT: entry.created
📦 CONTEXT: {
    "entry_id": 456,
    "form_id": 123,
    "entry_status": "super_unread"
}

🔥 EVENT: entry.saved
📦 CONTEXT: {
    "entry_id": 456,
    "form_id": 123,
    "is_update": false
}
```

---

## Performance Considerations

### Event Firing Overhead

Each event firing involves:
1. `class_exists()` check (~0.001ms)
2. `SUPER_Trigger_Executor::fire_event()` call
3. Database queries to find matching triggers
4. Condition evaluation
5. Action execution

**Estimated overhead when NO triggers configured:** ~1-2ms per submission
**Overhead with 10 active triggers:** ~5-20ms per submission

### Optimization Recommendations

1. **Caching** - Trigger lookups should be cached
2. **Lazy Loading** - Only load action classes when needed
3. **Async Execution** - Heavy actions should use Action Scheduler
4. **Conditional Checks** - Skip event processing if no triggers exist

---

## Implementation Status

### Completed Phases

**Phase 1 - Foundation (Complete):**
- 7 core classes implemented (Registry, DAL, Manager, Executor, Conditions, Action Base, REST Controller)
- 19 built-in actions implemented
- REST API endpoints functional

**Phase 2 - Action Scheduler (Complete):**
- `SUPER_Trigger_Scheduler` class for async execution
- Retry mechanism with exponential backoff
- Queue management integration

**Phase 3 - Logging Infrastructure (Complete):**
- `SUPER_Trigger_Logger` - Centralized logging with levels
- `SUPER_Trigger_Debugger` - Real-time debug data collection
- `SUPER_Trigger_Performance` - Timing and memory tracking
- `SUPER_Trigger_Compliance` - GDPR and audit trails
- `SUPER_Trigger_Logs_Page` - Admin log viewer with filtering/export

### Remaining Phases

**Phase 1.5 - Admin UI (Pending):**
- Build dedicated "Triggers" admin page
- Trigger creation/editing interface
- Action configuration UI

**Phase 4+ - Advanced Features (Pending):**
- See task README for full phase list

---

## File Locations Reference

**Event Firing Locations** (all in `/src/includes/class-ajax.php`):
- Line 3307: `form.spam_detected` (uses SUPER_Spam_Detector::check() at line 3298)
- Line 3189: `form.validation_failed`
- Line 4680: `form.before_submit`
- Line 4693: `form.submitted`
- Line 4581: `file.uploaded`
- Line 4899: `entry.created`
- Line 5012: `form.duplicate_detected`
- Line 5091: `entry.updated`
- Line 5100: `entry.saved` (update path)
- Line 5147: `entry.status_changed`
- Line 5170: `entry.saved` (new entry path)

**Trigger System Classes**:
- `/src/includes/triggers/class-trigger-registry.php` - Event/action registration
- `/src/includes/class-trigger-executor.php` - Event firing and execution
- `/src/includes/class-spam-detector.php` - Multi-method spam detection (5 methods)
- `/src/includes/class-trigger-manager.php` - Business logic
- `/src/includes/class-trigger-dal.php` - Database access
- `/src/includes/class-trigger-conditions.php` - Condition evaluation
- `/src/includes/class-trigger-scheduler.php` - Action Scheduler integration (Phase 2)
- `/src/includes/class-trigger-logger.php` - Centralized logging (Phase 3)
- `/src/includes/class-trigger-debugger.php` - Debug data collection (Phase 3)
- `/src/includes/class-trigger-performance.php` - Performance tracking (Phase 3)
- `/src/includes/class-trigger-compliance.php` - GDPR/audit trails (Phase 3)

---

## Payment Events (Phase 6)

Payment events are fired from webhook endpoints when payment processors send notifications. These events enable triggers to respond to payment lifecycle changes.

### Stripe Payment Events

**Webhook Endpoint:** `POST /wp-json/super-forms/v1/webhooks/stripe`
**Signature Verification:** HMAC-SHA256 with webhook secret

| Event | Stripe Webhook | Description |
|-------|----------------|-------------|
| `payment.stripe.checkout_completed` | `checkout.session.completed` | Checkout session completed successfully |
| `payment.stripe.payment_succeeded` | `payment_intent.succeeded` | Payment intent succeeded |
| `payment.stripe.payment_failed` | `payment_intent.payment_failed` | Payment intent failed |
| `subscription.stripe.created` | `customer.subscription.created` | New subscription created |
| `subscription.stripe.updated` | `customer.subscription.updated` | Subscription plan or status changed |
| `subscription.stripe.cancelled` | `customer.subscription.deleted` | Subscription was cancelled |
| `subscription.stripe.invoice_paid` | `invoice.paid` | Subscription invoice payment succeeded |
| `subscription.stripe.invoice_failed` | `invoice.payment_failed` | Subscription invoice payment failed |

### PayPal Payment Events

**Webhook Endpoint:** `POST /wp-json/super-forms/v1/webhooks/paypal`
**Signature Verification:** PayPal API verification call

| Event | PayPal Webhook | Description |
|-------|----------------|-------------|
| `payment.paypal.capture_completed` | `PAYMENT.CAPTURE.COMPLETED` | PayPal payment capture completed |
| `payment.paypal.capture_denied` | `PAYMENT.CAPTURE.DENIED` | PayPal payment capture was denied |
| `payment.paypal.capture_refunded` | `PAYMENT.CAPTURE.REFUNDED` | PayPal payment was refunded |
| `subscription.paypal.created` | `BILLING.SUBSCRIPTION.CREATED` | PayPal billing subscription created |
| `subscription.paypal.activated` | `BILLING.SUBSCRIPTION.ACTIVATED` | PayPal subscription became active |
| `subscription.paypal.cancelled` | `BILLING.SUBSCRIPTION.CANCELLED` | PayPal subscription was cancelled |
| `subscription.paypal.suspended` | `BILLING.SUBSCRIPTION.SUSPENDED` | PayPal subscription was suspended |
| `subscription.paypal.payment_failed` | `BILLING.SUBSCRIPTION.PAYMENT.FAILED` | PayPal subscription payment failed |

### Payment Event Context

All payment events include the following context data (when available):

```php
array(
    'form_id' => int,        // Extracted from metadata/custom_id
    'entry_id' => int,       // Extracted from metadata/custom_id
    'event_type' => string,  // Original webhook event type
    'event_id' => string,    // Unique event identifier
    'mode' => string,        // 'test' or 'live'
    'timestamp' => string,   // MySQL datetime
)
```

**Note:** `form_id` and `entry_id` must be passed during checkout creation (in Stripe metadata or PayPal custom_id) for triggers to fire with form context.

---

## Version History

- **v1.2.0** (2025-11-23) - Payment events integration
  - 34 events total (added 16 payment events: 8 Stripe, 8 PayPal)
  - Webhook endpoints for Stripe and PayPal
  - Signature verification for both payment processors
  - `SUPER_Payment_OAuth` class for OAuth flows

- **v1.1.0** (2025-11-22) - Complete event coverage
  - 18 events implemented (session: 4, form: 6, entry: 5, file: 3)
  - Added: entry.deleted, file.upload_failed, file.deleted
  - Event context standardization
  - WordPress action hook integration

- **v1.0.0** (2025-11-21) - Initial event firing implementation
  - 10 events implemented across form submission flow
  - Event context standardization
  - WordPress action hook integration
