---
name: 01a-implement-built-in-actions-spam-detection
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
parent: h-implement-triggers-actions-extensibility
---

# Implement Built-in Actions & Spam/Duplicate Detection with Abort Flow

## Problem/Goal

Define and implement the 20 foundational actions users can configure when triggers fire. Implement spam/duplicate detection with a **pre-submission firewall** that can ABORT submissions. Implement progressive session management for auto-save and form recovery.

**Critical Architectural Decisions:**
- Progressive sessions created on first field interaction (not submission)
- Auto-save on field blur/change for recovery from crashes
- Spam/duplicate detection happens BEFORE entry creation (but after session exists)
- Custom table for sessions (better performance for frequent auto-saves)

## Success Criteria

**Core Actions (8):**
- [ ] Send Email action with {tag} support
- [ ] Update Entry Status action
- [ ] Update Entry Field action
- [ ] Delete Entry action (immediate or scheduled)
- [ ] Send Webhook action (POST/GET/PUT)
- [ ] Create WordPress Post action
- [ ] Log Message action
- [ ] **Abort Submission action** (prevents entry creation, keeps session)

**Flexibility Actions (12):**
- [ ] Update Post Meta (any post, any meta key)
- [ ] Update User Meta (current or specific user)
- [ ] Run WordPress Hook (do_action with args)
- [ ] Redirect User (with {tags} in URL)
- [ ] Modify User Account (add/remove role, login/logout)
- [ ] Increment Counter (limits, inventory, quotas)
- [ ] Set Variable (cookie/session/transient/option)
- [ ] Clear Cache (page/object/transient/all)
- [ ] Conditional Action (if-then-else logic)
- [ ] Stop Execution (halts further actions)
- [ ] Delay Execution (schedule for later via Action Scheduler)
- [ ] Execute PHP Code (DEFERRED - recommend Code Snippets plugin)

**Spam Detection (5 methods - implement from scratch):**
- [ ] Honeypot field detection (hidden field bots fill)
- [ ] Submission time tracking (< 3 seconds = bot)
- [ ] IP address blacklist checking
- [ ] Spam keyword filtering
- [ ] Akismet API integration (optional)

**Duplicate Detection (4 methods):**
- [ ] Email + time window (default 10 min)
- [ ] IP + time window (default 5 min)
- [ ] Field hash matching (exact duplicate)
- [ ] Custom field combination (configurable unique fields)

**Submission Flow with Abort Points:**
- [ ] Pre-submission event hooks (BEFORE any DB writes)
- [ ] Abort mechanism that cleans up files and prevents entry creation
- [ ] Session cleanup on abort
- [ ] Clear separation of pre-submit vs post-submit events

## Progressive Session Management Architecture

### Session Lifecycle

**Session Database Table (NEW):**
```sql
CREATE TABLE {$wpdb->prefix}superforms_sessions (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  session_key VARCHAR(32) NOT NULL UNIQUE,
  form_id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED,
  user_ip VARCHAR(45),
  status VARCHAR(20) DEFAULT 'draft', -- draft/abandoned/resumed/completed/expired
  form_data LONGTEXT,                 -- JSON: progressive save data
  metadata LONGTEXT,                  -- JSON: analytics (time_spent, fields_completed, etc.)
  started_at DATETIME NOT NULL,
  last_saved_at DATETIME,
  completed_at DATETIME,
  expires_at DATETIME,                -- 24 hours from last_saved_at
  PRIMARY KEY (id),
  KEY session_key (session_key),
  KEY form_id_status (form_id, status),
  KEY expires_at (expires_at)
) ENGINE=InnoDB $charset_collate;
```

**Progressive Save Flow:**
```javascript
// Client-side: Create session on FIRST field interaction, auto-save on blur/change
jQuery(document).on('focus', '.super-form input, .super-form textarea, .super-form select', function() {
    var $form = jQuery(this).closest('.super-form');
    var formId = $form.data('form-id');

    // Check if session already exists for this form
    if (!$form.data('session-key')) {
        // Create session on FIRST field focus (not submission)
        jQuery.ajax({
            url: super_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'super_create_session',
                form_id: formId,
                field_focused: this.name
            },
            success: function(response) {
                if (response.session_key) {
                    $form.data('session-key', response.session_key);
                    // Store in localStorage for recovery
                    localStorage.setItem('super_session_' + formId, response.session_key);
                }
            }
        });
    }
});

// Auto-save on field blur/change
jQuery(document).on('blur change', '.super-form input, .super-form textarea, .super-form select', function() {
    var $form = jQuery(this).closest('.super-form');
    var sessionKey = $form.data('session-key');

    if (sessionKey) {
        var formData = collectFormData($form);

        // Debounce auto-save (avoid too frequent saves)
        clearTimeout($form.data('save-timer'));
        var timer = setTimeout(function() {
            jQuery.ajax({
                url: super_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'super_auto_save_session',
                    session_key: sessionKey,
                    form_id: $form.data('form-id'),
                    field_name: this.name,
                    form_data: formData
                }
            });
        }.bind(this), 500); // 500ms debounce

        $form.data('save-timer', timer);
    }
});

// Server-side: Create session handler
public static function create_session() {
    global $wpdb;
    $table = $wpdb->prefix . 'superforms_sessions';

    $form_id = intval($_POST['form_id']);
    $session_key = wp_generate_password(32, false);

    // Create new session record
    $wpdb->insert($table, [
        'session_key' => $session_key,
        'form_id' => $form_id,
        'user_id' => get_current_user_id(),
        'user_ip' => $_SERVER['REMOTE_ADDR'],
        'status' => 'draft',
        'form_data' => '{}', // Empty initially
        'metadata' => json_encode([
            'first_field_focused' => sanitize_text_field($_POST['field_focused']),
            'time_started' => time()
        ]),
        'started_at' => current_time('mysql'),
        'last_saved_at' => current_time('mysql'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
    ]);

    // Fire event for analytics
    do_action('super_session_started', $session_key, $form_id);

    wp_send_json_success(['session_key' => $session_key]);
}

// Server-side: Auto-save handler
public static function auto_save_session() {
    global $wpdb;
    $table = $wpdb->prefix . 'superforms_sessions';

    $session_key = sanitize_text_field($_POST['session_key']);
    $form_data = $_POST['form_data'];

    // Update session with latest data
    $updated = $wpdb->update($table, [
        'form_data' => json_encode($form_data),
        'last_saved_at' => current_time('mysql'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')) // Reset expiry
    ], [
        'session_key' => $session_key
    ]);

    if ($updated !== false) {
        // Update metadata with progress
        $metadata = [
            'fields_completed' => count(array_filter($form_data)),
            'last_field_updated' => sanitize_text_field($_POST['field_name']),
            'time_spent' => time() - $session_start_time
        ];

        // Fire event for analytics/tracking
        do_action('super_session_auto_saved', $session_key, $form_data);

        wp_send_json_success(['saved' => true]);
    } else {
        wp_send_json_error(['message' => 'Failed to save session']);
    }
}
```

**Session Recovery:**
```php
// On form load, check for existing session
public static function check_existing_session($form_id, $user_id, $user_ip) {
    global $wpdb;
    $table = $wpdb->prefix . 'superforms_sessions';

    // Find most recent draft session
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table
        WHERE form_id = %d
        AND (user_id = %d OR user_ip = %s)
        AND status IN ('draft', 'abandoned')
        AND expires_at > NOW()
        ORDER BY last_saved_at DESC
        LIMIT 1",
        $form_id,
        $user_id,
        $user_ip
    ));

    if ($session) {
        // Offer to resume
        return [
            'has_session' => true,
            'session_key' => $session->session_key,
            'form_data' => json_decode($session->form_data, true),
            'last_saved' => $session->last_saved_at
        ];
    }

    return ['has_session' => false];
}
```

**Session Events:**
```php
// New events for progressive sessions
- session.started        // First field interaction
- session.auto_saved     // Field blur/change save
- session.resumed        // User returned to incomplete form
- session.abandoned      // 30 min without activity
- session.completed      // Form submitted successfully
- session.expired        // 24 hours passed
```

## Form Submission Flow Architecture

### Current Flow Problems

**What happens now:**
```
1. User submits form (client-side)
2. Files uploaded to server
3. Validation runs
4. Session created in database
5. Contact Entry created (if enabled)
6. Email sent
7. Success message shown
```

**Problems:**
- Spam detected at step 6 → already have DB records
- Files already uploaded → need cleanup
- Session exists → wasted storage
- No way to prevent entry creation

### New Flow with Abort Points

**Revised submission flow:**

```
┌─────────────────────────────────────────────────────────────┐
│ CLIENT-SIDE (JavaScript)                                    │
├─────────────────────────────────────────────────────────────┤
│ 1. User loads form (no session yet - performance)          │
│    └─ Honeypot field already rendered (display:none)       │
│                                                              │
│ 2. User focuses FIRST field                                 │
│    └─ Create SESSION in custom table (draft status)        │
│    └─ Set session_key in localStorage                      │
│    └─ EVENT: session.started                               │
│    └─ Start tracking interaction time                      │
│                                                              │
│ 3. User fills fields                                        │
│    └─ Auto-save on blur/change → SESSION TABLE             │
│    └─ EVENT: session.auto_saved (each save)                │
│    └─ Debounced saves (500ms) to prevent overload          │
│                                                              │
│ 4. User clicks Submit                                       │
│    └─ AJAX request to server with session_key              │
│    └─ Session already exists with partial data!            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ SERVER-SIDE PHASE 1: PRE-SUBMIT FIREWALL                   │
├─────────────────────────────────────────────────────────────┤
│ 5. Load Existing Session (from custom table)               │
│    Table: {$wpdb->prefix}superforms_sessions               │
│    └─ Merge submitted data with auto-saved data            │
│    └─ Update session status to 'submitting'                │
│                                                              │
│ 6. File Upload (happens first)                             │
│    Files: /wp-content/uploads/super-forms-temp/            │
│    └─ Store file paths in $uploaded_files array            │
│                                                              │
│ 7. Validation (WordPress nonce, required fields, etc)      │
│    └─ If fails: cleanup files, update session (error)      │
│                                                              │
│ 8. ✨ EVENT: form.before_submit                            │
│    Context: {form_data, session_id, uploaded_files}        │
│    Actions: Can inspect data, cannot modify entry (none)    │
│                                                              │
│ 9. ✨ SPAM DETECTION                                        │
│    Methods: Honeypot, Time (from session), IP, Keywords    │
│    └─ If spam: fire form.spam_detected event               │
│                                                              │
│ 10. ✨ EVENT: form.spam_detected (if spam)                 │
│     Context: {spam_method, spam_score, session_id}         │
│     Actions: Abort Submission, Log Message, Send Email     │
│     └─ If ABORT: cleanup files, mark session 'spam'        │
│     └─ If CONTINUE: mark as spam but proceed               │
│                                                              │
│ 11. ✨ DUPLICATE DETECTION                                  │
│     Methods: Email+Time, IP+Time, Hash, Custom Fields      │
│     └─ If duplicate: fire form.duplicate_detected event    │
│                                                              │
│ 12. ✨ EVENT: form.duplicate_detected (if duplicate)       │
│     Context: {duplicate_method, original_entry_id}         │
│     Actions: Abort Submission, Update Original, Log        │
│     └─ If ABORT: cleanup files, mark session 'duplicate'   │
│     └─ If CONTINUE: create duplicate entry                 │
│                                                              │
│ [ABORT CHECKPOINT]                                          │
│ If ABORT action fired: skip to step 20 (cleanup & return)  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ SERVER-SIDE PHASE 2: DATABASE WRITES                       │
├─────────────────────────────────────────────────────────────┤
│ 13. Update Session Status to 'completed'                   │
│     Table: {$wpdb->prefix}superforms_sessions              │
│     └─ Set completed_at timestamp                          │
│     └─ Store final form_data                               │
│                                                              │
│ 14. ✨ EVENT: session.completed                            │
│     Context: {session_id, form_data}                       │
│     Actions: Log, Webhook (for analytics)                  │
│                                                              │
│ 15. Create Contact Entry (if enabled in form settings)     │
│     Table: wp_posts (post_type: super_contact_entry)       │
│     Data: SUPER_Data_Access::save_entry_data()             │
│     └─ entry_id generated                                  │
│     └─ Link to session_id                                  │
│                                                              │
│ 16. Move Files to Permanent Storage                        │
│     From: /wp-content/uploads/super-forms-temp/            │
│     To:   /wp-content/uploads/super-forms/{entry_id}/      │
│     └─ Update file paths in entry data                     │
│                                                              │
│ 17. ✨ EVENT: entry.created                                │
│     Context: {entry_id, session_id, form_data, files}      │
│     Actions: All actions available (entry exists now)      │
│                                                              │
│ 18. ✨ EVENT: entry.saved                                  │
│     Context: {entry_id, full data saved}                   │
│     Actions: Send Email, Webhook, Create Post, etc         │
│                                                              │
│ 19. ✨ EVENT: form.submitted                               │
│     Context: {entry_id, session_id, everything}            │
│     Actions: Confirmation emails, CRM sync, etc            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ SERVER-SIDE PHASE 3: RESPONSE & CLEANUP                    │
├─────────────────────────────────────────────────────────────┤
│ 20. Return Response to Client                              │
│     Success: {status: 'success', entry_id, message}        │
│     Aborted: {status: 'aborted', message, reason}          │
│     Error:   {status: 'error', message, errors}            │
│                                                              │
│ 21. Cleanup on Abort (if triggered at steps 10 or 12)      │
│     - Delete temporary uploaded files                      │
│     - Update session status (spam/duplicate/aborted)       │
│     - Log abort event                                      │
│     - Return custom message to user                        │
└─────────────────────────────────────────────────────────────┘
```

### Event Classification by Phase

**Session Events** (During form filling):
- `session.started` - First field focused, session created
- `session.auto_saved` - Field blur/change triggers save
- `session.resumed` - User returns to incomplete form
- `session.abandoned` - 30 min without activity

**Pre-Submit Events** (BEFORE entry creation):
- `form.before_submit` - Inspection only, cannot abort yet
- `form.spam_detected` - **CAN ABORT** (prevents entry creation)
- `form.duplicate_detected` - **CAN ABORT** (prevents entry creation)
- `form.validation_failed` - Already aborted by validation

**Post-Submit Events** (AFTER entry creation):
- `session.completed` - Session marked as completed
- `entry.created` - Entry created, minimal data saved
- `entry.saved` - All data persisted
- `form.submitted` - Everything complete

**Special Behavior:**
- Pre-submit events can use **Abort Submission** action
- Post-submit events can only use **Update Entry Status** or **Delete Entry** actions
- Abort updates session status and cleans up files automatically
- Sessions exist independently from entries (created earlier)

## Abort Submission Action

**File:** `/src/includes/triggers/actions/class-action-abort-submission.php`

```php
class SUPER_Action_Abort_Submission extends SUPER_Trigger_Action_Base {

    public function get_id() {
        return 'flow.abort_submission';
    }

    public function get_label() {
        return __('Abort Submission', 'super-forms');
    }

    public function get_category() {
        return 'flow_control';
    }

    public function get_description() {
        return __('Prevents form submission from creating session/entry. Only works in pre-submit events (spam_detected, duplicate_detected).', 'super-forms');
    }

    public function get_settings_schema() {
        return [
            [
                'name' => 'cleanup_files',
                'label' => __('Cleanup Uploaded Files', 'super-forms'),
                'type' => 'checkbox',
                'default' => true,
                'description' => __('Delete files uploaded during this submission', 'super-forms')
            ],
            [
                'name' => 'cleanup_session',
                'label' => __('Cleanup Session', 'super-forms'),
                'type' => 'checkbox',
                'default' => true,
                'description' => __('Delete session if already created', 'super-forms')
            ],
            [
                'name' => 'user_message',
                'label' => __('Message to User', 'super-forms'),
                'type' => 'textarea',
                'default' => 'Your submission could not be processed at this time.',
                'description' => __('Message shown to user instead of success confirmation', 'super-forms')
            ],
            [
                'name' => 'log_abort',
                'label' => __('Log Abort Event', 'super-forms'),
                'type' => 'checkbox',
                'default' => true,
                'description' => __('Write abort event to logs for debugging', 'super-forms')
            ]
        ];
    }

    public function execute($context, $config) {
        // Only works in pre-submit phase
        if (!$this->is_pre_submit_phase($context)) {
            return new WP_Error(
                'abort_not_allowed',
                __('Abort Submission can only be used in pre-submit events (spam_detected, duplicate_detected)', 'super-forms'),
                ['event_id' => $context['event_id']]
            );
        }

        $user_message = $this->replace_tags($config['user_message'], $context);

        // Set abort flag in context
        $context['abort_submission'] = true;
        $context['abort_message'] = $user_message;
        $context['abort_cleanup'] = [
            'files' => $config['cleanup_files'] ?? true,
            'session' => $config['cleanup_session'] ?? true
        ];

        // Cleanup uploaded files
        if ($config['cleanup_files'] && !empty($context['uploaded_files'])) {
            $this->cleanup_files($context['uploaded_files']);
        }

        // Cleanup session if exists
        if ($config['cleanup_session'] && !empty($context['session_key'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'superforms_sessions';
            $wpdb->update($table, [
                'status' => 'aborted'
            ], [
                'session_key' => $context['session_key']
            ]);
        }

        // Log abort event
        if ($config['log_abort']) {
            $this->log_abort($context, $config);
        }

        return [
            'success' => true,
            'message' => __('Submission aborted', 'super-forms'),
            'abort' => true,
            'user_message' => $user_message,
            'files_cleaned' => count($context['uploaded_files'] ?? []),
            'session_cleaned' => !empty($context['session_id'])
        ];
    }

    /**
     * Check if current event is in pre-submit phase
     */
    private function is_pre_submit_phase($context) {
        $pre_submit_events = [
            'form.before_submit',
            'form.spam_detected',
            'form.duplicate_detected',
            'form.validation_failed'
        ];

        return in_array($context['event_id'], $pre_submit_events);
    }

    /**
     * Delete temporary uploaded files
     */
    private function cleanup_files($files) {
        foreach ($files as $file) {
            if (file_exists($file['path'])) {
                @unlink($file['path']);

                // Also remove from media library if attached
                if (!empty($file['attachment_id'])) {
                    wp_delete_attachment($file['attachment_id'], true);
                }
            }
        }
    }

    /**
     * Log abort event to database
     */
    private function log_abort($context, $config) {
        global $wpdb;
        $table = $wpdb->prefix . 'superforms_trigger_logs';

        $wpdb->insert($table, [
            'trigger_id' => 0, // No specific trigger, system abort
            'entry_id' => null,
            'form_id' => $context['form_id'],
            'event_id' => $context['event_id'],
            'status' => 'aborted',
            'error_message' => sprintf(
                'Submission aborted: %s (files: %d, session: %s)',
                $context['event_id'],
                count($context['uploaded_files'] ?? []),
                !empty($context['session_id']) ? 'cleaned' : 'none'
            ),
            'context_data' => json_encode([
                'spam_method' => $context['spam_method'] ?? null,
                'spam_score' => $context['spam_score'] ?? null,
                'duplicate_method' => $context['duplicate_method'] ?? null,
                'user_ip' => $context['user_ip'] ?? $_SERVER['REMOTE_ADDR'],
                'user_message' => $config['user_message']
            ]),
            'user_id' => get_current_user_id(),
            'executed_at' => current_time('mysql')
        ]);
    }
}
```

## Spam Detection Implementation

### Step 1: Client-Side Time Tracking

**File:** `/src/assets/js/frontend/form-submission.js` (add to existing)

```javascript
// Track when user starts interacting with form
jQuery(document).on('focus', '.super-form input, .super-form textarea, .super-form select', function() {
    var $form = jQuery(this).closest('.super-form');

    // Only set once per form load
    if (!$form.data('start-time')) {
        var startTime = Math.floor(Date.now() / 1000); // Unix timestamp
        $form.data('start-time', startTime);

        // Add hidden field with start time
        if ($form.find('input[name="super_form_start_time"]').length === 0) {
            $form.append('<input type="hidden" name="super_form_start_time" value="' + startTime + '">');
        }
    }
});

// Honeypot field (already rendered server-side, just ensure it stays hidden)
// Field name: super_hp
// CSS: .super-hp-field { position: absolute; left: -9999px; }
```

### Step 2: Server-Side Spam Detector

**File:** `/src/includes/class-spam-detector.php` (NEW FILE)

```php
<?php
class SUPER_Spam_Detector {

    /**
     * Check submission for spam using all enabled methods
     */
    public static function check_spam($form_id, $form_data, $context) {
        $settings = self::get_spam_settings($form_id);
        $spam_detected = false;
        $spam_method = '';
        $spam_score = 0;

        // Method 1: Honeypot (always enabled, lightweight)
        if (self::check_honeypot($form_data)) {
            return [
                'spam' => true,
                'method' => 'honeypot',
                'score' => 1.0,
                'details' => 'Bot filled honeypot field'
            ];
        }

        // Method 2: Time-based detection
        if ($settings['time_check_enabled']) {
            $min_seconds = intval($settings['min_time'] ?? 3);
            $result = self::check_submission_time($form_data, $min_seconds);
            if ($result['spam']) {
                return [
                    'spam' => true,
                    'method' => 'time',
                    'score' => $result['score'],
                    'details' => $result['details']
                ];
            }
        }

        // Method 3: IP Blacklist
        if ($settings['ip_blacklist_enabled']) {
            $result = self::check_ip_blacklist($context['user_ip'], $settings['ip_blacklist']);
            if ($result['spam']) {
                return [
                    'spam' => true,
                    'method' => 'ip_blacklist',
                    'score' => 1.0,
                    'details' => $result['details']
                ];
            }
        }

        // Method 4: Keyword filtering
        if ($settings['keyword_filter_enabled']) {
            $result = self::check_keywords($form_data, $settings['spam_keywords']);
            if ($result['spam']) {
                return [
                    'spam' => true,
                    'method' => 'keywords',
                    'score' => $result['score'],
                    'details' => $result['details']
                ];
            }
        }

        // Method 5: Akismet (optional, requires Akismet plugin)
        if ($settings['akismet_enabled'] && class_exists('Akismet')) {
            $result = self::check_akismet($form_data, $context);
            if ($result['spam']) {
                return [
                    'spam' => true,
                    'method' => 'akismet',
                    'score' => $result['score'],
                    'details' => $result['details']
                ];
            }
        }

        // No spam detected
        return ['spam' => false];
    }

    /**
     * Get spam detection settings for form
     */
    private static function get_spam_settings($form_id) {
        $form_settings = get_post_meta($form_id, '_super_form_settings', true);
        $spam_settings = $form_settings['spam_detection'] ?? [];

        return wp_parse_args($spam_settings, [
            'time_check_enabled' => true,
            'min_time' => 3, // seconds
            'ip_blacklist_enabled' => false,
            'ip_blacklist' => '', // One IP per line
            'keyword_filter_enabled' => false,
            'spam_keywords' => "viagra\ncialis\ncasino\npoker\nporn", // One per line
            'keyword_threshold' => 0.3, // 30% match = spam
            'akismet_enabled' => false
        ]);
    }

    /**
     * Check honeypot field (hidden field that bots fill)
     */
    private static function check_honeypot($form_data) {
        $honeypot_field = 'super_hp';
        $value = $form_data[$honeypot_field] ?? '';

        // Any value in honeypot = bot
        return !empty($value);
    }

    /**
     * Check if submission time is suspiciously fast
     */
    private static function check_submission_time($form_data, $min_seconds) {
        $start_time = intval($form_data['super_form_start_time'] ?? 0);

        if ($start_time === 0) {
            // No start time = suspicious, but don't auto-flag
            // Could be legitimate if JS failed
            return ['spam' => false];
        }

        $submit_time = time();
        $elapsed = $submit_time - $start_time;

        if ($elapsed < $min_seconds) {
            return [
                'spam' => true,
                'score' => 0.9,
                'details' => sprintf('Submitted in %d seconds (minimum: %d)', $elapsed, $min_seconds)
            ];
        }

        return ['spam' => false];
    }

    /**
     * Check IP against blacklist
     */
    private static function check_ip_blacklist($ip, $blacklist_string) {
        if (empty($blacklist_string)) {
            return ['spam' => false];
        }

        $ips = array_map('trim', explode("\n", $blacklist_string));
        $ips = array_filter($ips); // Remove empty lines

        if (in_array($ip, $ips)) {
            return [
                'spam' => true,
                'details' => sprintf('IP %s is blacklisted', $ip)
            ];
        }

        // Also check CIDR ranges if present
        foreach ($ips as $blacklisted) {
            if (strpos($blacklisted, '/') !== false) {
                if (self::ip_in_range($ip, $blacklisted)) {
                    return [
                        'spam' => true,
                        'details' => sprintf('IP %s matches blacklist range %s', $ip, $blacklisted)
                    ];
                }
            }
        }

        return ['spam' => false];
    }

    /**
     * Check for spam keywords in submission
     */
    private static function check_keywords($form_data, $keywords_string) {
        if (empty($keywords_string)) {
            return ['spam' => false];
        }

        $keywords = array_map('strtolower', array_map('trim', explode("\n", $keywords_string)));
        $keywords = array_filter($keywords);

        if (empty($keywords)) {
            return ['spam' => false];
        }

        $matches = 0;
        $matched_keywords = [];

        // Check all form fields for keywords
        foreach ($form_data as $field_name => $field_value) {
            // Skip system fields
            if (strpos($field_name, 'super_') === 0) continue;

            if (!is_string($field_value)) continue;

            $field_lower = strtolower($field_value);

            foreach ($keywords as $keyword) {
                if (stripos($field_lower, $keyword) !== false) {
                    $matches++;
                    $matched_keywords[] = $keyword;
                }
            }
        }

        $total_keywords = count($keywords);
        $score = $matches / max($total_keywords, 1);

        // Threshold: 30% keyword match = spam
        $threshold = 0.3;

        if ($score >= $threshold) {
            return [
                'spam' => true,
                'score' => $score,
                'details' => sprintf(
                    'Matched %d of %d spam keywords: %s',
                    $matches,
                    $total_keywords,
                    implode(', ', array_unique($matched_keywords))
                )
            ];
        }

        return ['spam' => false];
    }

    /**
     * Check with Akismet API
     */
    private static function check_akismet($form_data, $context) {
        if (!class_exists('Akismet')) {
            return ['spam' => false];
        }

        $api_key = get_option('wordpress_api_key');
        if (empty($api_key)) {
            return ['spam' => false];
        }

        // Prepare Akismet request
        $request = [
            'blog' => get_option('home'),
            'user_ip' => $context['user_ip'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'permalink' => $context['page_url'] ?? '',
            'comment_type' => 'contact-form',
            'comment_author' => $form_data['name'] ?? '',
            'comment_author_email' => $form_data['email'] ?? '',
            'comment_content' => self::flatten_form_data($form_data)
        ];

        $response = Akismet::http_post(
            build_query($request),
            'comment-check'
        );

        $is_spam = ($response[1] === 'true');

        if ($is_spam) {
            return [
                'spam' => true,
                'score' => 0.8,
                'details' => 'Flagged by Akismet'
            ];
        }

        return ['spam' => false];
    }

    /**
     * Flatten form data for Akismet
     */
    private static function flatten_form_data($form_data) {
        $text = '';
        foreach ($form_data as $key => $value) {
            if (strpos($key, 'super_') === 0) continue;
            if (is_string($value)) {
                $text .= $value . "\n";
            }
        }
        return $text;
    }

    /**
     * Check if IP is in CIDR range
     */
    private static function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) {
            return ($ip === $range);
        }

        list($subnet, $bits) = explode('/', $range);
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet_long &= $mask;

        return ($ip_long & $mask) === $subnet_long;
    }
}
```

### Step 3: Honeypot Field Rendering

**File:** `/src/includes/class-form-render.php` (modify existing)

```php
// Add honeypot field to every form (invisible to users, visible to bots)
public static function add_honeypot_field() {
    return '
    <div class="super-hp-field" style="position:absolute;left:-9999px;" aria-hidden="true">
        <label for="super_hp">Leave this field empty</label>
        <input type="text"
               id="super_hp"
               name="super_hp"
               value=""
               tabindex="-1"
               autocomplete="off">
    </div>';
}

// Call in form rendering:
echo self::add_honeypot_field();
```

### Step 4: Settings UI for Spam Detection

**File:** Form settings > Spam Detection tab (Phase 1.5 UI implementation)

```php
[
    'name' => 'spam_detection',
    'label' => __('Spam Detection', 'super-forms'),
    'fields' => [
        [
            'name' => 'time_check_enabled',
            'label' => __('Enable Time-Based Detection', 'super-forms'),
            'type' => 'checkbox',
            'default' => true,
            'description' => __('Flag submissions faster than minimum time as spam', 'super-forms')
        ],
        [
            'name' => 'min_time',
            'label' => __('Minimum Submission Time (seconds)', 'super-forms'),
            'type' => 'number',
            'default' => 3,
            'min' => 1,
            'conditions' => [['field' => 'time_check_enabled', 'value' => true]]
        ],
        [
            'name' => 'ip_blacklist_enabled',
            'label' => __('Enable IP Blacklist', 'super-forms'),
            'type' => 'checkbox',
            'default' => false
        ],
        [
            'name' => 'ip_blacklist',
            'label' => __('Blacklisted IPs', 'super-forms'),
            'type' => 'textarea',
            'description' => __('One IP per line. Supports CIDR ranges (e.g., 192.168.1.0/24)', 'super-forms'),
            'conditions' => [['field' => 'ip_blacklist_enabled', 'value' => true]]
        ],
        [
            'name' => 'keyword_filter_enabled',
            'label' => __('Enable Keyword Filter', 'super-forms'),
            'type' => 'checkbox',
            'default' => false
        ],
        [
            'name' => 'spam_keywords',
            'label' => __('Spam Keywords', 'super-forms'),
            'type' => 'textarea',
            'default' => "viagra\ncialis\ncasino\npoker",
            'description' => __('One keyword per line. Case-insensitive.', 'super-forms'),
            'conditions' => [['field' => 'keyword_filter_enabled', 'value' => true]]
        ],
        [
            'name' => 'akismet_enabled',
            'label' => __('Enable Akismet', 'super-forms'),
            'type' => 'checkbox',
            'default' => false,
            'description' => __('Requires Akismet plugin to be installed and configured', 'super-forms')
        ]
    ]
]
```

## Duplicate Detection Implementation

(Implementation same as in my previous draft - checking email+time, IP+time, hash, custom fields)

## Integration with Submission Flow

**File:** `/src/includes/class-ajax.php` (MODIFY existing `submit_form()` method)

```php
public static function submit_form() {
    // ... existing code for nonce verification, form_id extraction ...

    $form_id = intval($_POST['form_id']);
    $data = $_POST['data']; // Sanitized form data
    $form_settings = get_post_meta($form_id, '_super_form_settings', true);

    // Context for events
    $context = [
        'form_id' => $form_id,
        'user_id' => get_current_user_id(),
        'user_ip' => $_SERVER['REMOTE_ADDR'],
        'page_url' => $_POST['page_url'] ?? '',
        'form_data' => $data,
        'uploaded_files' => [], // Will be populated after upload
        'abort_submission' => false // Will be set by Abort action
    ];

    // === STEP 4: FILE UPLOAD ===
    if (!empty($_FILES)) {
        $upload_result = self::handle_file_uploads($_FILES, $form_id);
        if (is_wp_error($upload_result)) {
            return ['error' => $upload_result->get_error_message()];
        }
        $context['uploaded_files'] = $upload_result;
    }

    // === STEP 5: VALIDATION ===
    $validation = self::validate_submission($form_id, $data);
    if (is_wp_error($validation)) {
        // Cleanup uploaded files
        self::cleanup_files($context['uploaded_files']);

        // Fire validation_failed event
        $context['event_id'] = 'form.validation_failed';
        $context['validation_errors'] = $validation->get_error_messages();
        SUPER_Trigger_Executor::fire_event('form.validation_failed', $context);

        return ['error' => $validation->get_error_message()];
    }

    // === STEP 6: EVENT - form.before_submit ===
    $context['event_id'] = 'form.before_submit';
    SUPER_Trigger_Executor::fire_event('form.before_submit', $context);

    // === STEP 7: SPAM DETECTION ===
    $spam_result = SUPER_Spam_Detector::check_spam($form_id, $data, $context);

    if ($spam_result['spam']) {
        // Fire spam_detected event
        $context['event_id'] = 'form.spam_detected';
        $context['spam_method'] = $spam_result['method'];
        $context['spam_score'] = $spam_result['score'];
        $context['spam_details'] = $spam_result['details'];

        SUPER_Trigger_Executor::fire_event('form.spam_detected', $context);

        // === STEP 8: CHECK ABORT FLAG ===
        if ($context['abort_submission']) {
            return [
                'status' => 'aborted',
                'message' => $context['abort_message'],
                'reason' => 'spam_detected'
            ];
        }
    }

    // === STEP 9: DUPLICATE DETECTION ===
    $duplicate_result = SUPER_Duplicate_Detector::check_duplicate($form_id, $data, $form_settings);

    if ($duplicate_result['duplicate']) {
        // Fire duplicate_detected event
        $context['event_id'] = 'form.duplicate_detected';
        $context['duplicate_method'] = $duplicate_result['method'];
        $context['original_entry_id'] = $duplicate_result['original_entry_id'];

        SUPER_Trigger_Executor::fire_event('form.duplicate_detected', $context);

        // === STEP 10: CHECK ABORT FLAG ===
        if ($context['abort_submission']) {
            return [
                'status' => 'aborted',
                'message' => $context['abort_message'],
                'reason' => 'duplicate_detected'
            ];
        }
    }

    // [ABORT CHECKPOINT PASSED]
    // If we reach here, submission proceeds to database writes

    // === STEP 11: UPDATE EXISTING SESSION ===
    // Session was already created on first field focus
    global $wpdb;
    $table = $wpdb->prefix . 'superforms_sessions';

    $session_key = $_POST['session_key'] ?? null;

    if (!$session_key) {
        // Fallback: create session if somehow doesn't exist
        $session_key = wp_generate_password(32, false);
        $wpdb->insert($table, [
            'session_key' => $session_key,
            'form_id' => $form_id,
            'user_id' => $context['user_id'],
            'user_ip' => $context['user_ip'],
            'status' => 'completed',
            'form_data' => json_encode($data),
            'started_at' => current_time('mysql'),
            'completed_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ]);
        $session_id = $wpdb->insert_id;
    } else {
        // Update existing session to completed
        $wpdb->update($table, [
            'status' => 'completed',
            'form_data' => json_encode($data),
            'completed_at' => current_time('mysql')
        ], [
            'session_key' => $session_key
        ]);

        // Get session ID
        $session_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE session_key = %s",
            $session_key
        ));
    }

    $context['session_id'] = $session_id;
    $context['session_key'] = $session_key;

    // === STEP 12: EVENT - session.completed ===
    $context['event_id'] = 'session.completed';
    SUPER_Trigger_Executor::fire_event('session.completed', $context);

    // === STEP 13: CREATE CONTACT ENTRY (if enabled) ===
    $create_entry = $form_settings['save_contact_entry'] ?? true;

    if ($create_entry) {
        $entry_id = wp_insert_post([
            'post_type' => 'super_contact_entry',
            'post_status' => 'publish',
            'post_title' => sprintf('Entry %s', current_time('mysql')),
            'post_parent' => $form_id
        ]);

        if (is_wp_error($entry_id)) {
            // Cleanup session and files
            wp_delete_post($session_id, true);
            self::cleanup_files($context['uploaded_files']);
            return ['error' => $entry_id->get_error_message()];
        }

        $context['entry_id'] = $entry_id;

        // === STEP 14: MOVE FILES TO PERMANENT STORAGE ===
        if (!empty($context['uploaded_files'])) {
            $moved_files = self::move_files_to_permanent($context['uploaded_files'], $entry_id);
            $context['uploaded_files'] = $moved_files;

            // Update file paths in data
            foreach ($moved_files as $file) {
                $data[$file['field_name']] = $file['url'];
            }
        }

        // Save entry data using Data Access Layer
        SUPER_Data_Access::save_entry_data($entry_id, $data);

        // Store additional meta
        update_post_meta($entry_id, '_super_user_ip', $context['user_ip']);
        update_post_meta($entry_id, '_super_session_id', $session_id);

        // === STEP 15: EVENT - entry.created ===
        $context['event_id'] = 'entry.created';
        $context['entry_data'] = $data;
        SUPER_Trigger_Executor::fire_event('entry.created', $context);

        // === STEP 16: EVENT - entry.saved ===
        $context['event_id'] = 'entry.saved';
        SUPER_Trigger_Executor::fire_event('entry.saved', $context);
    }

    // === STEP 17: EVENT - form.submitted ===
    $context['event_id'] = 'form.submitted';
    SUPER_Trigger_Executor::fire_event('form.submitted', $context);

    // === STEP 18: RETURN SUCCESS ===
    return [
        'status' => 'success',
        'message' => __('Form submitted successfully', 'super-forms'),
        'entry_id' => $entry_id ?? null,
        'session_id' => $session_id
    ];
}

/**
 * Cleanup uploaded files
 */
private static function cleanup_files($files) {
    foreach ($files as $file) {
        if (file_exists($file['path'])) {
            @unlink($file['path']);
        }
        if (!empty($file['attachment_id'])) {
            wp_delete_attachment($file['attachment_id'], true);
        }
    }
}

/**
 * Move files from temp to permanent storage
 */
private static function move_files_to_permanent($files, $entry_id) {
    $moved = [];
    $upload_dir = wp_upload_dir();
    $permanent_dir = $upload_dir['basedir'] . '/super-forms/' . $entry_id;

    if (!file_exists($permanent_dir)) {
        wp_mkdir_p($permanent_dir);
    }

    foreach ($files as $file) {
        $filename = basename($file['path']);
        $new_path = $permanent_dir . '/' . $filename;
        $new_url = $upload_dir['baseurl'] . '/super-forms/' . $entry_id . '/' . $filename;

        if (rename($file['path'], $new_path)) {
            $moved[] = array_merge($file, [
                'path' => $new_path,
                'url' => $new_url
            ]);
        }
    }

    return $moved;
}
```

## Testing Requirements

**Unit Tests:**
- Test Abort Submission action with pre-submit events (success)
- Test Abort Submission action with post-submit events (error)
- Test file cleanup on abort
- Test session cleanup on abort
- Test each spam detection method independently
- Test spam detection with multiple methods enabled
- Test duplicate detection methods
- Test abort flag propagation through context

**Integration Tests:**
- Submit form with honeypot filled → aborted, files cleaned
- Submit form in < 3 seconds → aborted
- Submit form from blacklisted IP → aborted
- Submit form with spam keywords → aborted
- Submit duplicate (same email within 10 min) → aborted
- Submit valid form → session + entry created

## User Notes

**Critical Architectural Changes:**
- Spam/duplicate detection now happens BEFORE any DB writes
- Abort Submission action prevents session/entry creation
- Files uploaded to temp directory first, moved to permanent on success
- Pre-submit events can abort, post-submit events cannot
- Session always created (unless aborted), entry optional
- Clean separation of concerns: firewall → writes → confirmations

**Performance:**
- Spam detection runs in < 100ms (lightweight checks first)
- File cleanup async via Action Scheduler (Phase 2)
- Duplicate detection uses indexed queries (fast even with 100K entries)

## Work Log
- [2025-11-20] Subtask created with abort flow architecture
- [2025-11-20] Defined 3-phase submission flow (firewall → writes → response)
- [2025-11-20] Implemented Abort Submission action with cleanup
- [2025-11-20] Implemented 5 spam detection methods from scratch
- [2025-11-20] Added client-side time tracking and honeypot
- [2025-11-20] Documented event classification (pre-submit vs post-submit)
