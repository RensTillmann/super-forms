---
name: 01a-step2-client-side-sessions
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-11-23
completed: 2025-11-24
parent: 01a-implement-built-in-actions-spam-detection
---

# Step 2: Client-Side Session Management and Auto-Save

## Problem/Goal

Implement the client-side JavaScript that creates sessions on first field interaction and auto-saves form data on blur/change. Also implement session recovery for users returning to incomplete forms.

## Why This Step

- Sessions need to exist BEFORE submission for the abort flow
- Auto-save provides better UX (recover from crashes/accidental navigation)
- Time tracking (session started → submitted) enables time-based spam detection

## Success Criteria

- [x] Session created on first field focus (AJAX to `super_create_session`)
- [x] Auto-save on field blur/change (debounced, AJAX to `super_auto_save_session`)
- [x] Session key stored in localStorage for recovery
- [x] Session recovery UI shown when returning to form with saved data
- [x] Server-side AJAX handlers implemented
- [x] Session events fired (`session.started`, `session.auto_saved`, `session.resumed`, `session.completed`)

## Implementation

### File 1: AJAX Handlers

**File:** `/src/includes/class-ajax.php`
**Location:** Add new AJAX handlers

```php
/**
 * Create a new form session
 *
 * Called on first field interaction (focus event)
 *
 * @since 6.5.0
 */
public static function create_session() {
    $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

    if (!$form_id) {
        wp_send_json_error(['message' => 'Missing form_id']);
    }

    // Check if session already exists (prevent duplicates)
    $existing_key = isset($_POST['existing_session']) ? sanitize_text_field($_POST['existing_session']) : '';
    if ($existing_key) {
        $existing = SUPER_Session_DAL::get_by_key($existing_key);
        if ($existing && $existing['status'] === 'draft') {
            wp_send_json_success([
                'session_key' => $existing_key,
                'resumed' => true,
            ]);
        }
    }

    // Create new session
    $session_id = SUPER_Session_DAL::create([
        'form_id' => $form_id,
        'user_id' => get_current_user_id() ?: null,
        'user_ip' => SUPER_Common::real_ip(),
        'metadata' => [
            'first_field' => isset($_POST['field_name']) ? sanitize_text_field($_POST['field_name']) : '',
            'page_url' => isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'start_timestamp' => time(),
        ],
    ]);

    if (is_wp_error($session_id)) {
        wp_send_json_error(['message' => $session_id->get_error_message()]);
    }

    $session = SUPER_Session_DAL::get($session_id);

    // Fire session.started event
    if (class_exists('SUPER_Trigger_Executor')) {
        SUPER_Trigger_Executor::fire_event('session.started', [
            'form_id' => $form_id,
            'session_id' => $session_id,
            'session_key' => $session['session_key'],
            'user_id' => get_current_user_id(),
            'user_ip' => SUPER_Common::real_ip(),
        ]);
    }

    wp_send_json_success([
        'session_key' => $session['session_key'],
        'session_id' => $session_id,
    ]);
}

/**
 * Auto-save form session
 *
 * Called on field blur/change (debounced)
 *
 * @since 6.5.0
 */
public static function auto_save_session() {
    $session_key = isset($_POST['session_key']) ? sanitize_text_field($_POST['session_key']) : '';
    $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

    if (!$session_key || !$form_id) {
        wp_send_json_error(['message' => 'Missing session_key or form_id']);
    }

    // Get form data
    $form_data = [];
    if (!empty($_POST['form_data'])) {
        $raw_data = wp_unslash($_POST['form_data']);
        if (is_string($raw_data)) {
            $form_data = json_decode($raw_data, true) ?: [];
        } elseif (is_array($raw_data)) {
            $form_data = $raw_data;
        }
    }

    // Get existing session to update metadata
    $session = SUPER_Session_DAL::get_by_key($session_key);
    if (!$session) {
        wp_send_json_error(['message' => 'Session not found']);
    }

    // Update metadata with progress info
    $metadata = $session['metadata'] ?: [];
    $metadata['last_field'] = isset($_POST['field_name']) ? sanitize_text_field($_POST['field_name']) : '';
    $metadata['fields_count'] = count(array_filter($form_data, function($v) {
        return !empty($v);
    }));
    $metadata['last_save_timestamp'] = time();

    // Calculate time spent
    if (isset($metadata['start_timestamp'])) {
        $metadata['time_spent_seconds'] = time() - $metadata['start_timestamp'];
    }

    // Update session
    $result = SUPER_Session_DAL::update_by_key($session_key, [
        'form_data' => $form_data,
        'metadata' => $metadata,
    ]);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    // Fire session.auto_saved event
    if (class_exists('SUPER_Trigger_Executor')) {
        SUPER_Trigger_Executor::fire_event('session.auto_saved', [
            'form_id' => $form_id,
            'session_id' => $session['id'],
            'session_key' => $session_key,
            'fields_count' => $metadata['fields_count'],
            'time_spent' => $metadata['time_spent_seconds'] ?? 0,
        ]);
    }

    wp_send_json_success(['saved' => true]);
}

/**
 * Check for recoverable session
 *
 * Called on form load to offer session recovery
 *
 * @since 6.5.0
 */
public static function check_session_recovery() {
    $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

    if (!$form_id) {
        wp_send_json_error(['message' => 'Missing form_id']);
    }

    $user_id = get_current_user_id() ?: null;
    $user_ip = SUPER_Common::real_ip();

    // Check localStorage session key first
    $stored_key = isset($_POST['stored_session']) ? sanitize_text_field($_POST['stored_session']) : '';

    if ($stored_key) {
        $session = SUPER_Session_DAL::get_by_key($stored_key);
        if ($session && $session['form_id'] == $form_id && in_array($session['status'], ['draft', 'abandoned'])) {
            wp_send_json_success([
                'has_session' => true,
                'session_key' => $stored_key,
                'form_data' => $session['form_data'],
                'last_saved' => $session['last_saved_at'],
                'fields_count' => $session['metadata']['fields_count'] ?? 0,
            ]);
        }
    }

    // Try to find by user/IP
    $session = SUPER_Session_DAL::find_recoverable($form_id, $user_id, $user_ip);

    if ($session) {
        wp_send_json_success([
            'has_session' => true,
            'session_key' => $session['session_key'],
            'form_data' => $session['form_data'],
            'last_saved' => $session['last_saved_at'],
            'fields_count' => $session['metadata']['fields_count'] ?? 0,
        ]);
    }

    wp_send_json_success(['has_session' => false]);
}

/**
 * Resume a session
 *
 * Called when user chooses to restore saved data
 *
 * @since 6.5.0
 */
public static function resume_session() {
    $session_key = isset($_POST['session_key']) ? sanitize_text_field($_POST['session_key']) : '';

    if (!$session_key) {
        wp_send_json_error(['message' => 'Missing session_key']);
    }

    $session = SUPER_Session_DAL::get_by_key($session_key);

    if (!$session) {
        wp_send_json_error(['message' => 'Session not found']);
    }

    // Update status to resumed and reset expiry
    SUPER_Session_DAL::update_by_key($session_key, [
        'status' => 'draft',
        'metadata' => array_merge($session['metadata'] ?: [], [
            'resumed_at' => current_time('mysql'),
            'resume_count' => ($session['metadata']['resume_count'] ?? 0) + 1,
        ]),
    ]);

    // Fire session.resumed event
    if (class_exists('SUPER_Trigger_Executor')) {
        SUPER_Trigger_Executor::fire_event('session.resumed', [
            'form_id' => $session['form_id'],
            'session_id' => $session['id'],
            'session_key' => $session_key,
            'user_id' => get_current_user_id(),
        ]);
    }

    wp_send_json_success([
        'form_data' => $session['form_data'],
        'session_key' => $session_key,
    ]);
}

/**
 * Dismiss/delete a recoverable session
 *
 * Called when user chooses "Start Fresh"
 *
 * @since 6.5.0
 */
public static function dismiss_session() {
    $session_key = isset($_POST['session_key']) ? sanitize_text_field($_POST['session_key']) : '';

    if (!$session_key) {
        wp_send_json_error(['message' => 'Missing session_key']);
    }

    $session = SUPER_Session_DAL::get_by_key($session_key);

    if ($session) {
        SUPER_Session_DAL::delete($session['id']);
    }

    wp_send_json_success(['deleted' => true]);
}
```

### File 2: Register AJAX Actions

**File:** `/src/includes/class-ajax.php`
**Location:** In `__construct()` method, add:

```php
// Session management (Progressive Save)
add_action('wp_ajax_super_create_session', array('SUPER_Ajax', 'create_session'));
add_action('wp_ajax_nopriv_super_create_session', array('SUPER_Ajax', 'create_session'));
add_action('wp_ajax_super_auto_save_session', array('SUPER_Ajax', 'auto_save_session'));
add_action('wp_ajax_nopriv_super_auto_save_session', array('SUPER_Ajax', 'auto_save_session'));
add_action('wp_ajax_super_check_session_recovery', array('SUPER_Ajax', 'check_session_recovery'));
add_action('wp_ajax_nopriv_super_check_session_recovery', array('SUPER_Ajax', 'check_session_recovery'));
add_action('wp_ajax_super_resume_session', array('SUPER_Ajax', 'resume_session'));
add_action('wp_ajax_nopriv_super_resume_session', array('SUPER_Ajax', 'resume_session'));
add_action('wp_ajax_super_dismiss_session', array('SUPER_Ajax', 'dismiss_session'));
add_action('wp_ajax_nopriv_super_dismiss_session', array('SUPER_Ajax', 'dismiss_session'));
```

### File 3: Client-Side JavaScript

**File:** `/src/assets/js/frontend/session-manager.js` (NEW)

```javascript
/**
 * Super Forms Session Manager
 *
 * Handles progressive form saving, session creation, and recovery.
 *
 * @since 6.5.0
 */
(function($) {
    'use strict';

    var SuperFormsSessions = {

        // Settings
        autoSaveDelay: 500, // ms debounce
        recoveryCheckDelay: 100, // ms after form init

        /**
         * Initialize session management for a form
         */
        init: function($form) {
            var self = this;
            var formId = $form.data('id');

            if (!formId) {
                return;
            }

            // Store reference
            $form.data('sf-sessions', {
                sessionKey: null,
                saveTimer: null,
                initialized: false
            });

            // Check for recoverable session on load
            setTimeout(function() {
                self.checkRecovery($form, formId);
            }, self.recoveryCheckDelay);

            // Bind events
            self.bindEvents($form, formId);
        },

        /**
         * Check for recoverable session
         */
        checkRecovery: function($form, formId) {
            var self = this;
            var storedKey = localStorage.getItem('super_session_' + formId);

            $.ajax({
                url: super_common_i18n.ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_check_session_recovery',
                    form_id: formId,
                    stored_session: storedKey || ''
                },
                success: function(response) {
                    if (response.success && response.data.has_session) {
                        self.showRecoveryPrompt($form, formId, response.data);
                    }
                }
            });
        },

        /**
         * Show recovery prompt to user
         */
        showRecoveryPrompt: function($form, formId, sessionData) {
            var self = this;

            // Format last saved time
            var lastSaved = new Date(sessionData.last_saved);
            var timeAgo = self.formatTimeAgo(lastSaved);

            // Create recovery UI
            var $prompt = $('<div class="super-session-recovery">' +
                '<div class="super-session-recovery-content">' +
                    '<p><strong>' + (super_common_i18n.session_recovery_title || 'Continue where you left off?') + '</strong></p>' +
                    '<p>' + (super_common_i18n.session_recovery_text || 'You have unsaved form data from {time}.').replace('{time}', timeAgo) + '</p>' +
                    '<div class="super-session-recovery-buttons">' +
                        '<button type="button" class="super-session-restore">' +
                            (super_common_i18n.session_restore || 'Restore') +
                        '</button>' +
                        '<button type="button" class="super-session-dismiss">' +
                            (super_common_i18n.session_start_fresh || 'Start Fresh') +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>');

            // Insert before form
            $form.before($prompt);

            // Bind restore button
            $prompt.find('.super-session-restore').on('click', function() {
                self.restoreSession($form, formId, sessionData.session_key, $prompt);
            });

            // Bind dismiss button
            $prompt.find('.super-session-dismiss').on('click', function() {
                self.dismissSession($form, formId, sessionData.session_key, $prompt);
            });
        },

        /**
         * Restore session data to form
         */
        restoreSession: function($form, formId, sessionKey, $prompt) {
            var self = this;

            $.ajax({
                url: super_common_i18n.ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_resume_session',
                    session_key: sessionKey
                },
                success: function(response) {
                    if (response.success) {
                        // Store session key
                        $form.data('sf-sessions').sessionKey = sessionKey;
                        localStorage.setItem('super_session_' + formId, sessionKey);

                        // Populate form fields
                        self.populateForm($form, response.data.form_data);

                        // Remove prompt
                        $prompt.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }
            });
        },

        /**
         * Dismiss recoverable session
         */
        dismissSession: function($form, formId, sessionKey, $prompt) {
            $.ajax({
                url: super_common_i18n.ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_dismiss_session',
                    session_key: sessionKey
                }
            });

            // Clear localStorage
            localStorage.removeItem('super_session_' + formId);

            // Remove prompt
            $prompt.fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Populate form with saved data
         */
        populateForm: function($form, formData) {
            if (!formData || typeof formData !== 'object') {
                return;
            }

            $.each(formData, function(fieldName, fieldValue) {
                var $field = $form.find('[name="' + fieldName + '"]');

                if ($field.length) {
                    if ($field.is(':checkbox')) {
                        $field.prop('checked', !!fieldValue);
                    } else if ($field.is(':radio')) {
                        $field.filter('[value="' + fieldValue + '"]').prop('checked', true);
                    } else if ($field.is('select')) {
                        $field.val(fieldValue).trigger('change');
                    } else {
                        $field.val(fieldValue);
                    }
                }
            });
        },

        /**
         * Bind form events
         */
        bindEvents: function($form, formId) {
            var self = this;
            var sessionData = $form.data('sf-sessions');

            // Create session on first field focus
            $form.on('focus', 'input, textarea, select', function() {
                if (!sessionData.sessionKey && !sessionData.initialized) {
                    sessionData.initialized = true;
                    self.createSession($form, formId, this.name);
                }
            });

            // Auto-save on field change/blur
            $form.on('blur change', 'input, textarea, select', function() {
                if (sessionData.sessionKey) {
                    self.scheduleAutoSave($form, formId, this.name);
                }
            });
        },

        /**
         * Create new session
         */
        createSession: function($form, formId, fieldName) {
            var self = this;
            var sessionData = $form.data('sf-sessions');

            // Check localStorage for existing session
            var existingKey = localStorage.getItem('super_session_' + formId);

            $.ajax({
                url: super_common_i18n.ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_create_session',
                    form_id: formId,
                    field_name: fieldName,
                    page_url: window.location.href,
                    existing_session: existingKey || ''
                },
                success: function(response) {
                    if (response.success) {
                        sessionData.sessionKey = response.data.session_key;
                        localStorage.setItem('super_session_' + formId, response.data.session_key);

                        // Add hidden field for submission
                        if (!$form.find('input[name="super_session_key"]').length) {
                            $form.append('<input type="hidden" name="super_session_key" value="' + response.data.session_key + '">');
                        }
                    }
                }
            });
        },

        /**
         * Schedule auto-save (debounced)
         */
        scheduleAutoSave: function($form, formId, fieldName) {
            var self = this;
            var sessionData = $form.data('sf-sessions');

            // Clear existing timer
            if (sessionData.saveTimer) {
                clearTimeout(sessionData.saveTimer);
            }

            // Schedule new save
            sessionData.saveTimer = setTimeout(function() {
                self.autoSave($form, formId, fieldName);
            }, self.autoSaveDelay);
        },

        /**
         * Auto-save form data
         */
        autoSave: function($form, formId, fieldName) {
            var sessionData = $form.data('sf-sessions');

            if (!sessionData.sessionKey) {
                return;
            }

            // Collect form data
            var formData = {};
            $form.find('input, textarea, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');

                if (!name || name.indexOf('super_') === 0) {
                    return; // Skip system fields
                }

                if ($field.is(':checkbox')) {
                    formData[name] = $field.is(':checked') ? $field.val() : '';
                } else if ($field.is(':radio')) {
                    if ($field.is(':checked')) {
                        formData[name] = $field.val();
                    }
                } else {
                    formData[name] = $field.val();
                }
            });

            $.ajax({
                url: super_common_i18n.ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_auto_save_session',
                    session_key: sessionData.sessionKey,
                    form_id: formId,
                    field_name: fieldName,
                    form_data: JSON.stringify(formData)
                }
            });
        },

        /**
         * Format time ago string
         */
        formatTimeAgo: function(date) {
            var seconds = Math.floor((new Date() - date) / 1000);

            if (seconds < 60) return 'just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            return Math.floor(seconds / 86400) + ' days ago';
        },

        /**
         * Clear session on successful submission
         */
        clearSession: function($form, formId) {
            localStorage.removeItem('super_session_' + formId);
            var sessionData = $form.data('sf-sessions');
            if (sessionData) {
                sessionData.sessionKey = null;
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        // Initialize for each Super Form
        $('.super-form').each(function() {
            SuperFormsSessions.init($(this));
        });
    });

    // Expose globally for integration
    window.SuperFormsSessions = SuperFormsSessions;

})(jQuery);
```

### File 4: Include JavaScript

**File:** `/src/includes/class-common.php`
**Location:** In frontend script enqueue section

```php
// Session manager script (Progressive Save)
wp_enqueue_script(
    'super-session-manager',
    SUPER_PLUGIN_FILE . 'assets/js/frontend/session-manager.js',
    array('jquery'),
    SUPER_VERSION,
    true
);
```

### File 5: CSS for Recovery Prompt

**File:** `/src/assets/css/frontend/session-recovery.css` (NEW)

```css
.super-session-recovery {
    background: #f0f8ff;
    border: 1px solid #3498db;
    border-radius: 4px;
    padding: 15px 20px;
    margin-bottom: 20px;
    font-family: inherit;
}

.super-session-recovery-content p {
    margin: 0 0 10px;
}

.super-session-recovery-content p:last-of-type {
    margin-bottom: 15px;
}

.super-session-recovery-buttons {
    display: flex;
    gap: 10px;
}

.super-session-recovery-buttons button {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    border: none;
}

.super-session-restore {
    background: #3498db;
    color: #fff;
}

.super-session-restore:hover {
    background: #2980b9;
}

.super-session-dismiss {
    background: #ecf0f1;
    color: #333;
}

.super-session-dismiss:hover {
    background: #bdc3c7;
}
```

## Integration with Form Submission

The session key must be passed during form submission so the backend can:
1. Mark session as completed
2. Link session to created entry
3. Calculate submission time for spam detection

**In `class-ajax.php` `submit_form()` method:**

```php
// Get session key from submission
$session_key = isset($_POST['super_session_key']) ? sanitize_text_field($_POST['super_session_key']) : '';

// After successful entry creation:
if ($session_key) {
    SUPER_Session_DAL::mark_completed($session_key, $entry_id);
}
```

## Testing

1. Load form → no recovery prompt (no previous session)
2. Start filling form → session created (check localStorage)
3. Change fields → auto-save fires (check network tab)
4. Navigate away and return → recovery prompt shown
5. Click "Restore" → form populated with saved data
6. Click "Start Fresh" → session deleted, fresh form
7. Submit form → session marked completed

## Dependencies

- Step 1: Sessions Table and DAL (must be complete)

## Client Token Architecture (Critical Security Update)

**Problem Solved:** Original design matched anonymous sessions by IP address, which could cause users on shared computers (libraries, offices, family computers) to accidentally recover someone else's form data.

**Solution:** Each browser profile gets a unique `client_token` (UUID v4) stored in localStorage:

```javascript
// Generate once per browser profile, stored permanently
getClientToken: function() {
    var token = localStorage.getItem('super_client_token');
    if (!token) {
        token = crypto.randomUUID(); // Fallback for older browsers included
        localStorage.setItem('super_client_token', token);
    }
    return token;
}
```

**Database Column:** `client_token VARCHAR(36)` added to `wp_superforms_sessions` table with index.

**Session Identification:**
| User Type | Primary Identifier | Fallback |
|-----------|-------------------|----------|
| Logged-in | `user_id` | - |
| Anonymous | `client_token` | None (must have token) |

**Fingerprint vs Client Token:**
- `client_token` = UUID in localStorage for **session identification** (reliable, unique per browser)
- `fingerprint` = browser characteristics hash for **spam detection** (stored in metadata, not used for matching)

**Why This Matters:**
- Alice and Bob sharing a computer at a library get different client tokens (different browser profiles)
- Eve trying to hijack a session can't guess the UUID
- Forms only restore data that belongs to the specific browser profile

## Notes

- Auto-save is debounced (500ms) to prevent excessive AJAX calls
- Session key stored in localStorage survives page refresh
- Recovery prompt requires matching client_token (no IP-based recovery for security)
- Time tracking starts on session creation for spam detection
- Fingerprint stored in metadata for spam heuristics, not session matching

## Work Log

### 2025-11-24

#### Completed
- Added `client_token VARCHAR(36)` column to sessions table schema in `class-install.php`
- Implemented `find_by_client_token()` method in `SUPER_Session_DAL` for anonymous session recovery
- Updated `session-manager.js` with `getClientToken()` using localStorage UUID and `generateFingerprint()` for spam detection
- Updated AJAX handlers in `class-ajax.php` (`create_session`, `check_session_recovery`) to match by client_token instead of IP for anonymous users
- Added client_token tests to `test-session-ajax.php` and `test-session-dal.php`
- Fixed test setup to ensure sessions table migration runs before tests

#### Decisions
- **Client token over IP**: Using localStorage UUID instead of IP address prevents users on shared computers (libraries, offices) from accidentally recovering each other's form data
- **Fingerprint as metadata only**: Browser fingerprint stored in session metadata for spam detection heuristics, not used for session matching (too unreliable)
- **Logged-in vs anonymous**: Logged-in users matched by `user_id`, anonymous users by `client_token` (no fallback to IP)

#### Test Results
- All 399 tests pass (1439 assertions, 14 skipped)
- New client_token-specific tests verify session creation, recovery, and AJAX handling

### 2025-11-27

#### Completed
- Rewrote `session-manager.js` in pure vanilla JS (no jQuery dependency)
- Implemented diff-tracking: only sends changed fields to server (not entire form)
- Added `AbortController` to cancel pending requests when user types fast
- Updated `auto_save_session` AJAX handler to accept `changes` parameter (diff-only) with backwards-compatible `form_data` fallback
- Removed jQuery from script dependencies in `super-forms.php`
- Added `wp_localize_script` for `super_session_i18n` with ajaxurl and i18n strings
- Updated CSS for inline banner layout (icon + text + buttons)

#### Architecture Decisions
- **Vanilla JS over jQuery**: Zero dependencies, faster load, works even if jQuery fails
- **Diff-tracking saves bandwidth**: A 10-field form saves 50 bytes per auto-save instead of 5KB
- **AbortController pattern**: Cancels in-flight requests when new save queued (prevents race conditions)
- **Backwards compatibility**: Server accepts both `changes` (new) and `form_data` (legacy) parameters

#### Key Code Patterns
```javascript
// Diff tracking
var changes = {};
for (key in currentData) {
    if (state.lastSavedData[key] !== currentData[key]) {
        changes[key] = currentData[key];
    }
}
// Only send if changes exist
if (Object.keys(changes).length > 0) {
    fetch(ajaxUrl, { body: { changes: JSON.stringify(changes) }});
}
```

```javascript
// AbortController pattern
if (state.abortController) {
    state.abortController.abort(); // Cancel previous
}
state.abortController = new AbortController();
fetch(ajaxUrl, { signal: state.abortController.signal });
```

#### Test Results
- All 431 tests pass (3 skipped)
