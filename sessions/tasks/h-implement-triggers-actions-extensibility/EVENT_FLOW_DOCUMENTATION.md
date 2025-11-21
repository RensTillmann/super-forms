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

### File Upload Events
- `file.uploaded` - File successfully attached to media library

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

## Scenario 3: Spam Detection (Honeypot Triggered)

**Flow:** User submits form → honeypot field filled → REJECTED immediately

```
         ⚠️  HONEYPOT CHECK HAPPENS FIRST
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. form.spam_detected                                       │
│    Location: class-ajax.php:3233                            │
│    Context: { form_id, detection_method='honeypot',        │
│              honeypot_value, user_id, user_ip }             │
└─────────────────────────────────────────────────────────────┘
                          ↓
                    ❌ EXIT (silent)

⚠️  NO OTHER EVENTS FIRE
    - form.before_submit does NOT fire
    - No entry created
    - Submission stops immediately
```

**Note:** Honeypot check occurs in `submit_form_checks()` at line 3231, BEFORE the main `submit_form()` method is called. This is intentional for security - spam submissions exit as early as possible.

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
    'detection_method' => string,  // 'honeypot'
    'honeypot_value' => string,    // What was filled
    'timestamp' => string,
    'user_id' => int,
    'user_ip' => string
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

## Next Steps for Implementation

### Phase 1.5: Admin UI
- Build dedicated "Triggers" admin page
- Trigger creation/editing interface
- Action configuration UI
- Execution logs viewer

### Phase 2: Built-in Actions
Implement action classes:
- `send_email` - Email notifications
- `webhook` - HTTP POST to external URL
- `update_entry_status` - Change entry status
- `update_entry_field` - Modify entry data
- `log_message` - Debug logging

### Phase 3: Testing Infrastructure
- Unit tests for event firing
- Integration tests for trigger execution
- Developer tools page enhancements

---

## File Locations Reference

**Event Firing Locations** (all in `/src/includes/class-ajax.php`):
- Line 3233: `form.spam_detected`
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
- `/src/includes/class-trigger-manager.php` - Business logic
- `/src/includes/class-trigger-dal.php` - Database access
- `/src/includes/class-trigger-conditions.php` - Condition evaluation

---

## Version History

- **v1.0.0** (2025-01-21) - Initial event firing implementation
  - 10 events implemented across form submission flow
  - Event context standardization
  - WordPress action hook integration
