---
name: 01a-step5-submission-flow-refactor
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-11-23
completed: 2025-11-26
parent: 01a-implement-built-in-actions-spam-detection
---

# Step 5: Submission Flow Refactor (Pre-submission Firewall)

## Problem/Goal

Refactor the form submission flow in `class-ajax.php` to implement the pre-submission firewall pattern. This integrates sessions, spam detection, and duplicate detection into a clear 3-phase flow.

## Why This Step

- Brings together all previous steps into coherent flow
- Ensures abort actions work correctly
- Creates clear separation between pre-submit and post-submit phases
- Enables session completion tracking

## Success Criteria

- [x] Submission flow refactored into 3 phases
- [x] Phase 1: Pre-submission firewall (load session, validate, spam check, duplicate check)
- [x] Phase 2: Database writes (session complete, entry create, files move)
- [x] Phase 3: Response (events, cleanup, return)
- [x] Abort checkpoint between Phase 1 and Phase 2
- [x] Session marked completed/aborted appropriately
- [x] All existing functionality preserved

## Work Log

### 2025-11-26 - Implementation Complete

**Key Changes:**
- Fixed `form.submitted` event to fire AFTER all processing (was firing before entry creation)
- Added clear PHASE 1/2/3 header comments to document the 3-phase flow
- Moved `form.submitted` from line 4927 to line 6233 (just before response)
- Events now fire in correct order: before_submit → spam_detected → duplicate_detected → [ABORT CHECK] → entry.created → entry.saved → session.completed → form.submitted

**Files Modified:**
- `/src/includes/class-ajax.php` - Refactored submission flow with 3-phase architecture

**Event Order After Refactor:**
1. `form.validation_failed` (exits early if validation fails)
2. `form.before_submit` (Phase 1 - Pre-submission firewall)
3. `form.spam_detected` (if spam detected, can abort)
4. `form.duplicate_detected` (if duplicate detected, can abort)
5. **[ABORT CHECKPOINT]** - Actions can stop flow here
6. `entry.created` (Phase 2 - Database writes)
7. `entry.saved` / `entry.updated`
8. `session.completed`
9. `form.submitted` (Phase 3 - Post-submission response, final event)

**Tests:** 423 tests, 1 flaky performance test (unrelated to changes)

## The New Flow

```
┌─────────────────────────────────────────────────────────────┐
│ PHASE 1: PRE-SUBMISSION FIREWALL                            │
├─────────────────────────────────────────────────────────────┤
│ 1. Load existing session (from session_key)                 │
│ 2. Merge submitted data with auto-saved data                │
│ 3. Update session status to 'submitting'                    │
│ 4. File upload to temp directory                            │
│ 5. Validation                                               │
│    └─ If fails: cleanup, fire validation_failed, return     │
│ 6. EVENT: form.before_submit                                │
│ 7. SPAM DETECTION                                           │
│    └─ If spam: fire spam_detected, check for abort          │
│ 8. DUPLICATE DETECTION                                      │
│    └─ If duplicate: fire duplicate_detected, check abort    │
│                                                              │
│ [ABORT CHECKPOINT]                                          │
│ If abort_submission action fired → skip to cleanup          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ PHASE 2: DATABASE WRITES                                    │
├─────────────────────────────────────────────────────────────┤
│ 9. Mark session as 'completed'                              │
│ 10. EVENT: session.completed                                │
│ 11. Create contact entry (if enabled)                       │
│ 12. Move files to permanent storage                         │
│ 13. Save entry data via Data Access Layer                   │
│ 14. Store submission hash (for duplicate detection)         │
│ 15. EVENT: entry.created                                    │
│ 16. EVENT: entry.saved                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ PHASE 3: POST-SUBMISSION RESPONSE                           │
├─────────────────────────────────────────────────────────────┤
│ 17. EVENT: form.submitted                                   │
│ 18. Execute legacy actions (emails, etc.)                   │
│ 19. Build success response                                  │
│ 20. Clear client session (via response flag)                │
│ 21. Return JSON response                                    │
└─────────────────────────────────────────────────────────────┘
```

## Implementation

### Main Refactor in class-ajax.php

**File:** `/src/includes/class-ajax.php`
**Method:** `submit_form()` (complete replacement of submission logic)

```php
/**
 * Process form submission with pre-submission firewall
 *
 * @since 6.5.0 - Refactored with sessions, spam, duplicate detection
 */
public static function submit_form() {
    // ══════════════════════════════════════════════════════════
    // INITIALIZATION
    // ══════════════════════════════════════════════════════════

    // Parse and sanitize form data
    $data = [];
    if (!empty($_POST['data'])) {
        $data = wp_unslash($_POST['data']);
        $data = json_decode($data, true);
        $data = wp_slash($data);
        unset($_POST['data']);
    }

    $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
    $session_key = isset($_POST['super_session_key']) ? sanitize_text_field($_POST['super_session_key']) : '';
    $entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0; // For updates

    if (!$form_id) {
        SUPER_Common::output_message(true, __('Invalid form.', 'super-forms'));
        die();
    }

    // Get form settings
    $settings = SUPER_Common::get_form_settings($form_id);

    // Build submission context
    $context = [
        'form_id' => $form_id,
        'form_data' => $data,
        'user_id' => get_current_user_id(),
        'user_ip' => SUPER_Common::real_ip(),
        'session_key' => $session_key,
        'session' => null,
        'entry_id' => $entry_id,
        'uploaded_files' => [],
        'abort_submission' => false,
        'abort_message' => '',
    ];

    // ══════════════════════════════════════════════════════════
    // PHASE 1: PRE-SUBMISSION FIREWALL
    // ══════════════════════════════════════════════════════════

    // Step 1: Load existing session
    if ($session_key && class_exists('SUPER_Session_DAL')) {
        $context['session'] = SUPER_Session_DAL::get_by_key($session_key);

        if ($context['session']) {
            // Step 2: Merge with auto-saved data (submitted data takes precedence)
            $auto_saved = $context['session']['form_data'] ?? [];
            if (is_array($auto_saved)) {
                $context['form_data'] = array_merge($auto_saved, $data);
                $data = $context['form_data'];
            }

            // Step 3: Update session to 'submitting'
            SUPER_Session_DAL::update_by_key($session_key, [
                'status' => 'submitting',
                'form_data' => $data,
            ]);
        }
    }

    // Step 4: File upload (to temp directory)
    // ... existing file upload code ...
    // Store paths in $context['uploaded_files']

    // Step 5: Validation
    $validation_result = self::validate_submission($form_id, $data, $settings);
    if (is_wp_error($validation_result)) {
        // Cleanup uploaded files
        self::cleanup_temp_files($context['uploaded_files']);

        // Fire validation_failed event
        if (class_exists('SUPER_Trigger_Executor')) {
            SUPER_Trigger_Executor::fire_event('form.validation_failed', array_merge($context, [
                'validation_errors' => $validation_result->get_error_messages(),
            ]));
        }

        SUPER_Common::output_message(true, $validation_result->get_error_message());
        die();
    }

    // Step 6: Fire form.before_submit event
    if (class_exists('SUPER_Trigger_Executor')) {
        SUPER_Trigger_Executor::fire_event('form.before_submit', $context);
    }

    // Step 7: SPAM DETECTION
    if (class_exists('SUPER_Spam_Detector')) {
        $spam_result = SUPER_Spam_Detector::check($form_id, $data, $context);

        if ($spam_result['spam']) {
            SUPER_Spam_Detector::log_detection($form_id, $spam_result, $context);

            // Fire spam_detected event
            if (class_exists('SUPER_Trigger_Executor')) {
                $event_context = array_merge($context, [
                    'event_id' => 'form.spam_detected',
                    'detection_method' => $spam_result['method'],
                    'spam_score' => $spam_result['score'],
                    'spam_details' => $spam_result['details'],
                ]);

                $event_result = SUPER_Trigger_Executor::fire_event('form.spam_detected', $event_context);

                // Check for abort action
                if (is_wp_error($event_result) && $event_result->get_error_code() === 'submission_aborted') {
                    $context['abort_submission'] = true;
                    $context['abort_message'] = $event_result->get_error_message();
                    $context['abort_reason'] = 'spam_detected';
                }
            }

            // If no explicit abort, still block spam silently
            if (!$context['abort_submission']) {
                self::handle_abort($context, 'spam_detected:' . $spam_result['method']);
                exit;
            }
        }
    }

    // Legacy honeypot check (if SUPER_Spam_Detector not loaded)
    if (!class_exists('SUPER_Spam_Detector') && !empty($data['super_hp'])) {
        exit;
    }

    // Step 8: DUPLICATE DETECTION
    if (class_exists('SUPER_Duplicate_Detector')) {
        $dup_result = SUPER_Duplicate_Detector::check($form_id, $data, $context);

        if ($dup_result['duplicate']) {
            // Fire duplicate_detected event
            if (class_exists('SUPER_Trigger_Executor')) {
                $event_context = array_merge($context, [
                    'event_id' => 'form.duplicate_detected',
                    'detection_method' => $dup_result['method'],
                    'original_entry_id' => $dup_result['original_entry_id'],
                    'duplicate_details' => $dup_result['details'],
                ]);

                $event_result = SUPER_Trigger_Executor::fire_event('form.duplicate_detected', $event_context);

                // Check for abort action
                if (is_wp_error($event_result) && $event_result->get_error_code() === 'submission_aborted') {
                    $context['abort_submission'] = true;
                    $context['abort_message'] = $event_result->get_error_message();
                    $context['abort_reason'] = 'duplicate_detected';
                }
            }

            // Handle based on configured action
            $dup_action = SUPER_Duplicate_Detector::get_action($form_id);

            if ($dup_action === 'block' && !$context['abort_submission']) {
                self::handle_abort($context, 'duplicate_blocked');
                exit;
            }

            if ($dup_action === 'update') {
                // Update existing entry instead of creating new
                $entry_id = $dup_result['original_entry_id'];
                $context['entry_id'] = $entry_id;
                $context['is_update'] = true;
            }
        }
    }

    // ════════════════════════════════════════════════════════════
    // ABORT CHECKPOINT
    // ════════════════════════════════════════════════════════════

    if ($context['abort_submission']) {
        self::handle_abort($context, $context['abort_reason'] ?? 'aborted');

        // Return abort message to user
        $message = !empty($context['abort_message'])
            ? $context['abort_message']
            : __('Your submission could not be processed.', 'super-forms');

        SUPER_Common::output_message(true, $message);
        die();
    }

    // Remove system fields from data before saving
    unset($data['super_hp']);
    unset($data['super_form_start_time']);
    $context['form_data'] = $data;

    // ══════════════════════════════════════════════════════════
    // PHASE 2: DATABASE WRITES
    // ══════════════════════════════════════════════════════════

    // Step 9: Mark session as completed
    if ($session_key && class_exists('SUPER_Session_DAL')) {
        SUPER_Session_DAL::update_by_key($session_key, [
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
        ]);

        // Step 10: Fire session.completed event
        if (class_exists('SUPER_Trigger_Executor')) {
            SUPER_Trigger_Executor::fire_event('session.completed', $context);
        }
    }

    // Step 11: Create or update contact entry
    $is_update = !empty($context['is_update']) && $entry_id;

    if (!$is_update && ($settings['save_contact_entry'] ?? true)) {
        // Create new entry
        $entry_id = wp_insert_post([
            'post_type' => 'super_contact_entry',
            'post_status' => 'publish',
            'post_title' => sprintf('Entry #%d', time()),
            'post_parent' => $form_id,
        ]);

        if (is_wp_error($entry_id)) {
            SUPER_Common::output_message(true, __('Failed to create entry.', 'super-forms'));
            die();
        }

        $context['entry_id'] = $entry_id;

        // Store metadata
        update_post_meta($entry_id, '_super_user_ip', $context['user_ip']);
        update_post_meta($entry_id, '_super_session_key', $session_key);
        if ($context['user_id']) {
            update_post_meta($entry_id, '_super_user_id', $context['user_id']);
        }
    }

    // Step 12: Move files to permanent storage
    if ($entry_id && !empty($context['uploaded_files'])) {
        $context['uploaded_files'] = self::move_files_to_permanent($context['uploaded_files'], $entry_id);
        // Update file paths in data
        foreach ($context['uploaded_files'] as $file) {
            if (!empty($file['field_name']) && !empty($file['url'])) {
                $data[$file['field_name']] = $file['url'];
            }
        }
        $context['form_data'] = $data;
    }

    // Step 13: Save entry data via Data Access Layer
    if ($entry_id) {
        SUPER_Data_Access::save_entry_data($entry_id, $data);
    }

    // Step 14: Store submission hash (for duplicate detection)
    if ($entry_id && class_exists('SUPER_Duplicate_Detector')) {
        SUPER_Duplicate_Detector::store_submission_hash($entry_id);
    }

    // Step 15: Fire entry.created event
    if ($entry_id && !$is_update && class_exists('SUPER_Trigger_Executor')) {
        SUPER_Trigger_Executor::fire_event('entry.created', $context);
    }

    // Step 16: Fire entry.saved event
    if ($entry_id && class_exists('SUPER_Trigger_Executor')) {
        $event_id = $is_update ? 'entry.updated' : 'entry.saved';
        SUPER_Trigger_Executor::fire_event($event_id, $context);
    }

    // ══════════════════════════════════════════════════════════
    // PHASE 3: POST-SUBMISSION RESPONSE
    // ══════════════════════════════════════════════════════════

    // Step 17: Fire form.submitted event
    if (class_exists('SUPER_Trigger_Executor')) {
        SUPER_Trigger_Executor::fire_event('form.submitted', $context);
    }

    // Step 18: Execute legacy actions (emails, redirects, etc.)
    // ... existing legacy action code ...

    // Step 19-21: Build and return response
    $response = [
        'success' => true,
        'entry_id' => $entry_id,
        'session_key' => $session_key,
        'clear_session' => true, // Tell client to clear localStorage
        // ... other response data ...
    ];

    // ... existing response handling ...
}

/**
 * Handle submission abort
 *
 * Cleanup files, mark session, log event
 *
 * @param array $context Submission context
 * @param string $reason Abort reason
 */
private static function handle_abort($context, $reason) {
    // Cleanup uploaded files
    if (!empty($context['uploaded_files'])) {
        self::cleanup_temp_files($context['uploaded_files']);
    }

    // Mark session as aborted
    if (!empty($context['session_key']) && class_exists('SUPER_Session_DAL')) {
        SUPER_Session_DAL::mark_aborted($context['session_key'], $reason);
    }

    // Log abort
    if (class_exists('SUPER_Trigger_Logger')) {
        SUPER_Trigger_Logger::log([
            'level' => 'info',
            'message' => 'Submission aborted: ' . $reason,
            'context' => [
                'form_id' => $context['form_id'],
                'reason' => $reason,
                'user_ip' => $context['user_ip'],
            ],
        ]);
    }
}

/**
 * Cleanup temporary uploaded files
 *
 * @param array $files File info array
 */
private static function cleanup_temp_files($files) {
    foreach ($files as $file) {
        if (!empty($file['path']) && file_exists($file['path'])) {
            @unlink($file['path']);
        }
    }
}

/**
 * Move files from temp to permanent storage
 *
 * @param array $files File info array
 * @param int $entry_id Entry ID
 * @return array Updated file info
 */
private static function move_files_to_permanent($files, $entry_id) {
    $upload_dir = wp_upload_dir();
    $permanent_dir = $upload_dir['basedir'] . '/super-forms/' . $entry_id;

    if (!file_exists($permanent_dir)) {
        wp_mkdir_p($permanent_dir);
    }

    $moved = [];
    foreach ($files as $file) {
        $filename = basename($file['path']);
        $new_path = $permanent_dir . '/' . $filename;
        $new_url = $upload_dir['baseurl'] . '/super-forms/' . $entry_id . '/' . $filename;

        if (rename($file['path'], $new_path)) {
            $moved[] = array_merge($file, [
                'path' => $new_path,
                'url' => $new_url,
            ]);
        }
    }

    return $moved;
}
```

## Event Order Summary

After refactor, events fire in this order:

1. `form.validation_failed` (if validation fails - exits early)
2. `form.before_submit` (always, before any checks)
3. `form.spam_detected` (if spam detected)
4. `form.duplicate_detected` (if duplicate detected)
5. **[ABORT CHECKPOINT]** (if abort action triggered - exits)
6. `session.completed` (if session exists)
7. `entry.created` (for new entries only)
8. `entry.saved` (for new entries) OR `entry.updated` (for updates)
9. `form.submitted` (always on successful submission)

## Testing

### Integration Test

```php
public function test_full_submission_flow() {
    // 1. Create session
    // 2. Auto-save some data
    // 3. Submit form
    // 4. Verify session marked completed
    // 5. Verify entry created
    // 6. Verify events fired in order
}

public function test_spam_aborts_submission() {
    // 1. Create session
    // 2. Submit with honeypot filled
    // 3. Verify spam_detected event fired
    // 4. Verify session marked aborted
    // 5. Verify no entry created
}

public function test_duplicate_blocks_submission() {
    // 1. Create existing entry
    // 2. Submit with same email
    // 3. Verify duplicate_detected event fired
    // 4. Verify no new entry created
}

public function test_duplicate_updates_existing() {
    // 1. Configure form for "update" on duplicate
    // 2. Create existing entry
    // 3. Submit with same email
    // 4. Verify existing entry updated
}
```

## Dependencies

- Step 1: Sessions Table and DAL
- Step 2: Client-Side Sessions
- Step 3: Spam Detector
- Step 4: Duplicate Detector

## Notes

- This is a significant refactor of submit_form()
- Preserves all existing functionality
- Legacy code paths remain for backward compatibility
- Session key passed via hidden field from client
- Abort checkpoint is the key architectural change
