# Event Firing Points Map - Phase 1 Implementation

## Overview
This document maps the exact insertion points for all 18 Phase 1 events in the Super Forms codebase.

## Current Event System
- **Method**: `SUPER_Common::triggerEvent($eventName, $atts)`
  Location: `/src/includes/class-common.php` line 631
- **Current Events**: Only `sf.after.files.uploaded` uses this system
- **Integration**: Existing triggers loaded from form meta and executed

## Phase 1 Events (18 total)

### 1. Form Lifecycle Events (5 events)

#### `form.before_submit`
**Location**: `/src/includes/class-ajax.php`
**Line**: 4659 (ALREADY EXISTS as WordPress action)
**Implementation**:
```php
// Line 4659 - EXISTING
do_action('super_before_submit_form', array('post' => $_POST));

// ADD after line 4659:
SUPER_Trigger_Registry::fire_event('form.before_submit', array(
    'form_id' => $form_id,
    'raw_data' => $_POST,
    'timestamp' => current_time('mysql'),
    'user_id' => get_current_user_id(),
    'user_ip' => SUPER_Common::real_ip()
));
```

#### `form.validation_failed`
**Location**: `/src/includes/class-ajax.php`
**Line**: Multiple locations where `SUPER_Common::output_message()` is called with errors
**Key Locations**:
- Line 3189: CSRF validation failure
- Line 3207: max_input_vars limit
- Line 3231: Honeypot spam detection (exit without message)
- Line 4951: Duplicate entry title validation

**Implementation**:
```php
// Example at line 3189 - CSRF failure
// BEFORE calling output_message():
SUPER_Trigger_Registry::fire_event('form.validation_failed', array(
    'form_id' => $form_id,
    'error_type' => 'csrf_expired',
    'error_message' => esc_html__('Unable to submit form, session expired!', 'super-forms'),
    'timestamp' => current_time('mysql'),
    'user_id' => get_current_user_id(),
    'user_ip' => SUPER_Common::real_ip()
));
SUPER_Common::output_message(array(...));
```

#### `form.spam_detected`
**Location**: `/src/includes/class-ajax.php`
**Line**: 3231-3233 (honeypot detection)
**Implementation**:
```php
// Line 3231 - honeypot spam detection
if (!empty($data['super_hp'])) {
    // ADD event firing:
    SUPER_Trigger_Registry::fire_event('form.spam_detected', array(
        'form_id' => absint($_POST['form_id']),
        'detection_method' => 'honeypot',
        'honeypot_value' => $data['super_hp'],
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'user_ip' => SUPER_Common::real_ip()
    ));
    exit;
}
```

**Note**: For Akismet spam detection, search for akismet integration in extensions.

#### `form.submitted`
**Location**: `/src/includes/class-ajax.php`
**Line**: 4678 (ALREADY EXISTS as WordPress action)
**Implementation**:
```php
// Line 4678 - EXISTING
do_action('super_before_processing_data', array('atts' => $sfsi));

// ADD after line 4678:
SUPER_Trigger_Registry::fire_event('form.submitted', array(
    'form_id' => $form_id,
    'entry_id' => $entry_id, // May be 0 for new submissions
    'sfsi_id' => $sfsi_id,
    'data' => $data,
    'settings' => $settings,
    'timestamp' => current_time('mysql'),
    'user_id' => get_current_user_id(),
    'user_ip' => SUPER_Common::real_ip()
));
```

#### `form.duplicate_detected`
**Location**: `/src/includes/class-ajax.php`
**Line**: 4949 (unique entry title check)
**Implementation**:
```php
// Line 4949 - Before wp_delete_post()
if ($total > 1) {
    // ADD event firing:
    SUPER_Trigger_Registry::fire_event('form.duplicate_detected', array(
        'form_id' => $form_id,
        'entry_id' => $contact_entry_id,
        'duplicate_field' => 'entry_title',
        'duplicate_value' => $contact_entry_title,
        'comparison_scope' => $settings['contact_entry_unique_title_compare'],
        'data' => $data,
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ));

    wp_delete_post($contact_entry_id, true);
    SUPER_Common::output_message(...);
}
```

---

### 2. Entry Events (5 events)

#### `entry.created`
**Location**: `/src/includes/class-ajax.php`
**Line**: 4866 (immediately after wp_insert_post)
**Implementation**:
```php
// Line 4866 - After entry creation
$contact_entry_id = wp_insert_post($post);
$sfsi['contact_entry_id'] = $contact_entry_id;
$sfsi['entry_id'] = $contact_entry_id;

// ADD event firing:
SUPER_Trigger_Registry::fire_event('entry.created', array(
    'entry_id' => $contact_entry_id,
    'form_id' => $form_id,
    'entry_status' => 'super_unread',
    'timestamp' => current_time('mysql'),
    'user_id' => get_current_user_id(),
    'user_ip' => SUPER_Common::real_ip()
));
```

#### `entry.saved`
**Location**: `/src/includes/class-ajax.php`
**Line**: 5107 (after SUPER_Data_Access::save_entry_data for new entries)
**Line**: 5048 (after save_entry_data for entry updates)
**Implementation**:
```php
// Line 5107 - New entry data saved
SUPER_Data_Access::save_entry_data($contact_entry_id, $final_entry_data);

// ADD event firing:
SUPER_Trigger_Registry::fire_event('entry.saved', array(
    'entry_id' => $contact_entry_id,
    'form_id' => $form_id,
    'entry_data' => $final_entry_data,
    'is_update' => false,
    'timestamp' => current_time('mysql'),
    'user_id' => get_current_user_id()
));

// Line 5048 - Entry update
$result = SUPER_Data_Access::save_entry_data($entry_id, $final_entry_data);

// ADD event firing:
SUPER_Trigger_Registry::fire_event('entry.saved', array(
    'entry_id' => $entry_id,
    'form_id' => $form_id,
    'entry_data' => $final_entry_data,
    'is_update' => true,
    'timestamp' => current_time('mysql'),
    'user_id' => get_current_user_id()
));
```

#### `entry.updated`
**Location**: `/src/includes/class-ajax.php`
**Line**: 5048 (for entry editing)
**Implementation**:
```php
// Line 5048 - When editing existing entry
if ($entry_id != 0) {
    // ... existing code ...
    $result = SUPER_Data_Access::save_entry_data($entry_id, $final_entry_data);

    // ADD event firing:
    SUPER_Trigger_Registry::fire_event('entry.updated', array(
        'entry_id' => $entry_id,
        'form_id' => $form_id,
        'entry_data' => $final_entry_data,
        'previous_data' => SUPER_Data_Access::get_entry_data($entry_id), // Get before update
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ));
}
```

#### `entry.status_changed`
**Location**: `/src/includes/class-ajax.php`
**Line**: 5080 (when entry status is updated)
**Implementation**:
```php
// Line 5080 - Status update
if ($update_entry_status !== false) {
    $previous_status = get_post_meta($entry_id, '_super_contact_entry_status', true);

    update_post_meta($entry_id, '_super_contact_entry_status', $update_entry_status);

    // ADD event firing:
    SUPER_Trigger_Registry::fire_event('entry.status_changed', array(
        'entry_id' => $entry_id,
        'form_id' => $form_id,
        'previous_status' => $previous_status,
        'new_status' => $update_entry_status,
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ));
}
```

#### `entry.deleted`
**Location**: Search for `wp_delete_post` calls with `super_contact_entry` post type
**File**: `/src/includes/class-ajax.php` - `delete_contact_entry()` method
**Implementation**:
```php
// In delete_contact_entry() method - BEFORE wp_delete_post()
public static function delete_contact_entry() {
    // ... existing validation ...
    $entry_id = absint($_POST['id']);
    $entry = get_post($entry_id);

    if($entry && $entry->post_type === 'super_contact_entry') {
        // ADD event firing BEFORE deletion:
        SUPER_Trigger_Registry::fire_event('entry.deleted', array(
            'entry_id' => $entry_id,
            'form_id' => $entry->post_parent,
            'entry_data' => SUPER_Data_Access::get_entry_data($entry_id),
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ));

        wp_delete_post($entry_id, true);
    }
}
```

---

### 3. File Upload Events (3 events)

#### `file.uploaded`
**Location**: `/src/includes/class-ajax.php`
**Line**: 4550 (after wp_insert_attachment) and similar locations
**Implementation**:
```php
// Line 4550 - After successful file upload
$attachment_id = wp_insert_attachment($attachment, $filename, 0);
add_post_meta($attachment_id, 'super-forms-form-upload-file', true);
$attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
wp_update_attachment_metadata($attachment_id, $attach_data);

// ADD event firing:
SUPER_Trigger_Registry::fire_event('file.uploaded', array(
    'attachment_id' => $attachment_id,
    'form_id' => $form_id,
    'field_name' => $fieldName,
    'file_name' => basename($filename),
    'file_type' => $uploaded_file['type'],
    'file_size' => filesize($filename),
    'file_url' => wp_get_attachment_url($attachment_id),
    'timestamp' => current_time('mysql'),
    'user_id' => get_current_user_id()
));
```

#### `file.upload_failed`
**Location**: `/src/includes/class-ajax.php`
**Line**: Search for file upload error handling in `upload_files()` method
**Implementation**:
```php
// When file upload fails (add in error handling sections)
if ($upload_error) {
    SUPER_Trigger_Registry::fire_event('file.upload_failed', array(
        'form_id' => $form_id,
        'field_name' => $fieldName,
        'error_message' => $error_message,
        'file_name' => $original_filename,
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ));
}
```

#### `file.deleted`
**Location**: `/src/includes/class-ajax.php`
**Line**: 5691+ (file deletion after form submission)
**Implementation**:
```php
// In file deletion loop (around line 5691)
foreach ($data as $k => $v) {
    if (isset($v['type']) && $v['type'] === 'files') {
        foreach ($v['files'] as $file) {
            if (!empty($file['attachment'])) {
                $attachment_id = absint($file['attachment']);

                // ADD event firing BEFORE deletion:
                SUPER_Trigger_Registry::fire_event('file.deleted', array(
                    'attachment_id' => $attachment_id,
                    'form_id' => $form_id,
                    'file_name' => basename(get_attached_file($attachment_id)),
                    'deletion_reason' => 'form_setting_enabled',
                    'timestamp' => current_time('mysql'),
                    'user_id' => get_current_user_id()
                ));

                wp_delete_attachment($attachment_id, true);
            }
        }
    }
}
```

---

### 4. Payment Events (5 stubs - Full implementation in Phase 6)

These events will be properly implemented in Phase 6 when payment integration is built. For Phase 1, we register the event definitions but don't fire them yet.

#### `payment.initiated`
**Phase 6 Location**: Payment add-ons (Stripe/PayPal/WooCommerce)
**Phase 1**: Register event definition only

#### `payment.completed`
**Phase 6 Location**: Payment webhooks/IPN handlers
**Phase 1**: Register event definition only

#### `payment.failed`
**Phase 6 Location**: Payment error handlers
**Phase 1**: Register event definition only

#### `payment.refunded`
**Phase 6 Location**: Refund webhooks
**Phase 1**: Register event definition only

#### `payment.disputed`
**Phase 6 Location**: Dispute/chargeback webhooks
**Phase 1**: Register event definition only

---

## Implementation Strategy

### Step 1: Create Event Registry & Fire Method
File: `/src/includes/class-trigger-registry.php`

```php
class SUPER_Trigger_Registry {
    private static $instance = null;
    private static $events = array();
    private static $actions = array();

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function register_event($event_id, $args = array()) {
        self::$events[$event_id] = wp_parse_args($args, array(
            'label' => '',
            'description' => '',
            'category' => 'general',
            'context_fields' => array()
        ));
    }

    public static function fire_event($event_id, $context = array()) {
        // Hook for extensions to listen
        do_action('super_trigger_event_' . $event_id, $context);
        do_action('super_trigger_event', $event_id, $context);

        // Find and execute matching triggers (via Executor)
        if (class_exists('SUPER_Trigger_Executor')) {
            return SUPER_Trigger_Executor::execute_event($event_id, $context);
        }

        return array();
    }
}
```

### Step 2: Register Core Events on Init
File: `/src/includes/class-triggers-init.php` (new file)

```php
class SUPER_Triggers_Init {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_core_events'), 5);
    }

    public static function register_core_events() {
        $registry = SUPER_Trigger_Registry::instance();

        // Form lifecycle events
        $registry->register_event('form.before_submit', array(
            'label' => __('Before Form Submission', 'super-forms'),
            'description' => __('Fires before form validation and processing starts', 'super-forms'),
            'category' => 'form',
            'context_fields' => array('form_id', 'raw_data', 'user_id', 'user_ip')
        ));

        // ... register all 18 events ...
    }
}
SUPER_Triggers_Init::init();
```

### Step 3: Insert Event Firing Calls
Modify `/src/includes/class-ajax.php` at the mapped locations above.

**Guidelines**:
- Fire events AFTER WordPress actions (don't replace them)
- Fire `entry.created` immediately after `wp_insert_post()`
- Fire `entry.saved` after `SUPER_Data_Access::save_entry_data()`
- Fire deletion events BEFORE the delete operation
- Include comprehensive context data in each event

### Step 4: Testing Event Firing
Create test script: `/tests/test-event-firing.php`

```php
// Hook into all trigger events
add_action('super_trigger_event', function($event_id, $context) {
    error_log("EVENT FIRED: {$event_id}");
    error_log("CONTEXT: " . json_encode($context));
}, 10, 2);

// Test form submission to verify all events fire in correct order
```

---

## Event Firing Order (Typical Form Submission)

1. `form.before_submit` - User clicks submit
2. `form.spam_detected` - If honeypot/Akismet triggered (then exit)
3. `form.validation_failed` - If validation errors (then exit)
4. `form.submitted` - Validation passed
5. `file.uploaded` - For each uploaded file
6. `entry.created` - Entry post created
7. `entry.saved` - Entry data saved to database
8. `payment.initiated` - If payment enabled (Phase 6)
9. `payment.completed` - If payment succeeds (Phase 6)

**Entry Update Flow**:
1. `form.before_submit`
2. `form.submitted`
3. `entry.updated` - Existing entry being edited
4. `entry.saved` - Data saved
5. `entry.status_changed` - If status changed

---

## Backward Compatibility

### Existing Event System
- `SUPER_Common::triggerEvent()` - Keep for backward compatibility
- Current event: `sf.after.files.uploaded`
- Old triggers in form meta still work

### Migration Path
1. **Phase 1**: New registry system runs in parallel with old system
2. **Phase 1.5**: Admin UI uses new system
3. **Phase 2+**: Deprecate old `triggerEvent()` method (keep functional)

---

## Context Data Standards

Every event must provide:
```php
$context = array(
    'event_id' => 'form.submitted',          // Event identifier
    'timestamp' => current_time('mysql'),     // When event occurred
    'form_id' => 123,                         // Form ID (if applicable)
    'entry_id' => 456,                        // Entry ID (if applicable)
    'user_id' => get_current_user_id(),      // User who triggered event (0 = guest)
    'user_ip' => SUPER_Common::real_ip(),    // IP address
    // Event-specific fields:
    'data' => array(),                        // Form data
    'settings' => array(),                    // Form settings
    // ... etc
);
```

---

## Next Steps

1. ✅ Complete event mapping (this document)
2. Create database tables
3. Build Data Access Layer
4. Implement Registry and Executor
5. Insert event firing calls at mapped locations
6. Test event firing with debug logging
7. Build Conditions Engine to filter events
8. Implement REST API for trigger management

---

## Notes

- All event firing uses `SUPER_Trigger_Registry::fire_event()` method
- Events provide WordPress `do_action()` hooks for extensions
- Context data is comprehensive for condition evaluation
- Dual storage: entry data via `SUPER_Data_Access` + trigger logs table
- Phase 1 = 18 events, Phase 6 = 20+ more, Phase 8 = 6+ more
