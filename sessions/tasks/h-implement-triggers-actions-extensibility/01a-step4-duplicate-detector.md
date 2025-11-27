---
name: 01a-step4-duplicate-detector
branch: feature/h-implement-triggers-actions-extensibility
status: complete
created: 2025-11-23
completed: 2025-11-26
parent: 01a-implement-built-in-actions-spam-detection
---

# Step 4: Duplicate Detection System

## Problem/Goal

Implement duplicate submission detection that runs BEFORE entry creation. Prevents accidental double-submissions and intentional spam floods.

## Why This Step

- Duplicates waste storage and trigger duplicate emails
- Detection must run pre-submission for abort to work
- Configurable per-form (some forms allow duplicates)

## Success Criteria

- [x] `SUPER_Duplicate_Detector` class with 4 detection methods
- [x] Email + time window detection
- [x] IP + time window detection
- [x] Field hash matching (exact duplicates)
- [x] Custom field combination (configurable)
- [x] Per-form duplicate settings
- [x] `form.duplicate_detected` event fires with detection details
- [x] Can optionally update existing entry instead of creating new

## Work Log

### 2025-11-26 - Implementation Complete

**Files Created:**
- `/src/includes/class-duplicate-detector.php` - Core class with 4 detection methods
- `/tests/triggers/test-duplicate-detector.php` - 17 unit tests

**Files Modified:**
- `/src/super-forms.php` - Added require_once for SUPER_Duplicate_Detector
- `/src/includes/class-ajax.php` - Integrated duplicate detection into submission flow

**Implementation Details:**
- 4 detection methods: email+time, IP+time, hash matching, custom field combinations
- Handles both flat and structured form data formats
- Uses EAV table queries for email field lookups, postmeta for IP lookups
- Hash stored after entry creation via global variable pattern
- Fires `form.duplicate_detected` event with full detection context
- 3 action modes: block, update existing, allow duplicate
- Logger integration uses singleton pattern: `SUPER_Trigger_Logger::instance()->info()`

**Tests:** All 423 tests pass (3 skipped)

## Implementation

### File 1: Duplicate Detector Class

**File:** `/src/includes/class-duplicate-detector.php` (NEW)

```php
<?php
/**
 * Duplicate Detection System
 *
 * Detects duplicate form submissions using multiple methods.
 * Runs BEFORE entry creation in pre-submission phase.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Duplicate_Detector {

    /**
     * Default duplicate detection settings
     */
    private static $defaults = [
        'duplicate_detection_enabled' => false,
        'email_time_enabled' => true,
        'email_field' => 'email',
        'email_time_window' => 10, // minutes
        'ip_time_enabled' => true,
        'ip_time_window' => 5, // minutes
        'hash_enabled' => true,
        'hash_fields' => [], // Empty = all fields
        'custom_fields_enabled' => false,
        'custom_unique_fields' => [], // Field names that must be unique together
        'action_on_duplicate' => 'block', // block, update, allow
    ];

    /**
     * Check submission for duplicates
     *
     * @param int $form_id Form ID
     * @param array $form_data Submitted form data
     * @param array $context Submission context
     * @return array Result with 'duplicate' boolean and details
     */
    public static function check($form_id, $form_data, $context = []) {
        $settings = self::get_settings($form_id);

        // Skip if duplicate detection is disabled
        if (empty($settings['duplicate_detection_enabled'])) {
            return ['duplicate' => false];
        }

        // Method 1: Email + Time Window
        if ($settings['email_time_enabled']) {
            $result = self::check_email_time($form_id, $form_data, $settings);
            if ($result['duplicate']) {
                return $result;
            }
        }

        // Method 2: IP + Time Window
        if ($settings['ip_time_enabled']) {
            $result = self::check_ip_time($form_id, $context, $settings);
            if ($result['duplicate']) {
                return $result;
            }
        }

        // Method 3: Field Hash Matching
        if ($settings['hash_enabled']) {
            $result = self::check_hash($form_id, $form_data, $settings);
            if ($result['duplicate']) {
                return $result;
            }
        }

        // Method 4: Custom Field Combination
        if ($settings['custom_fields_enabled'] && !empty($settings['custom_unique_fields'])) {
            $result = self::check_custom_fields($form_id, $form_data, $settings);
            if ($result['duplicate']) {
                return $result;
            }
        }

        return [
            'duplicate' => false,
            'method' => null,
            'original_entry_id' => null,
        ];
    }

    /**
     * Get duplicate detection settings for form
     *
     * @param int $form_id Form ID
     * @return array Settings
     */
    public static function get_settings($form_id) {
        $form_settings = SUPER_Common::get_form_settings($form_id);
        $dup_settings = $form_settings['duplicate_detection'] ?? [];

        return wp_parse_args($dup_settings, self::$defaults);
    }

    /**
     * Check for duplicate by email + time window
     *
     * @param int $form_id Form ID
     * @param array $form_data Form data
     * @param array $settings Detection settings
     * @return array Result
     */
    private static function check_email_time($form_id, $form_data, $settings) {
        $email_field = $settings['email_field'];
        $email = $form_data[$email_field] ?? '';

        if (empty($email) || !is_email($email)) {
            return ['duplicate' => false];
        }

        $time_window = intval($settings['email_time_window']);
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$time_window} minutes"));

        global $wpdb;

        // Check in EAV table
        $entry_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ed.entry_id
            FROM {$wpdb->prefix}superforms_entry_data ed
            JOIN {$wpdb->posts} p ON ed.entry_id = p.ID
            WHERE ed.form_id = %d
            AND ed.field_name = %s
            AND ed.field_value = %s
            AND p.post_date >= %s
            AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
            LIMIT 1",
            $form_id,
            $email_field,
            $email,
            $cutoff
        ));

        if ($entry_id) {
            return [
                'duplicate' => true,
                'method' => 'email_time',
                'original_entry_id' => $entry_id,
                'details' => sprintf(
                    'Email "%s" submitted within %d minutes (entry #%d)',
                    $email,
                    $time_window,
                    $entry_id
                ),
                'email' => $email,
                'time_window' => $time_window,
            ];
        }

        return ['duplicate' => false];
    }

    /**
     * Check for duplicate by IP + time window
     *
     * @param int $form_id Form ID
     * @param array $context Submission context
     * @param array $settings Detection settings
     * @return array Result
     */
    private static function check_ip_time($form_id, $context, $settings) {
        $user_ip = $context['user_ip'] ?? SUPER_Common::real_ip();

        if (empty($user_ip)) {
            return ['duplicate' => false];
        }

        $time_window = intval($settings['ip_time_window']);
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$time_window} minutes"));

        global $wpdb;

        // Check entries by IP
        $entry_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'super_contact_entry'
            AND p.post_parent = %d
            AND p.post_date >= %s
            AND p.post_status = 'publish'
            AND pm.meta_key = '_super_user_ip'
            AND pm.meta_value = %s
            ORDER BY p.post_date DESC
            LIMIT 1",
            $form_id,
            $cutoff,
            $user_ip
        ));

        if ($entry_id) {
            return [
                'duplicate' => true,
                'method' => 'ip_time',
                'original_entry_id' => $entry_id,
                'details' => sprintf(
                    'IP %s submitted within %d minutes (entry #%d)',
                    $user_ip,
                    $time_window,
                    $entry_id
                ),
                'ip' => $user_ip,
                'time_window' => $time_window,
            ];
        }

        return ['duplicate' => false];
    }

    /**
     * Check for exact duplicate by field hash
     *
     * @param int $form_id Form ID
     * @param array $form_data Form data
     * @param array $settings Detection settings
     * @return array Result
     */
    private static function check_hash($form_id, $form_data, $settings) {
        // Generate hash of submission
        $hash_fields = $settings['hash_fields'];

        if (empty($hash_fields)) {
            // Use all non-system fields
            $hash_data = array_filter($form_data, function($key) {
                return strpos($key, 'super_') !== 0 && strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);
        } else {
            // Use specified fields only
            $hash_data = [];
            foreach ($hash_fields as $field) {
                if (isset($form_data[$field])) {
                    $hash_data[$field] = $form_data[$field];
                }
            }
        }

        if (empty($hash_data)) {
            return ['duplicate' => false];
        }

        // Sort for consistent hashing
        ksort($hash_data);
        $submission_hash = md5(json_encode($hash_data));

        global $wpdb;

        // Check for matching hash in recent entries (last 24 hours)
        $cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $entry_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'super_contact_entry'
            AND p.post_parent = %d
            AND p.post_date >= %s
            AND p.post_status = 'publish'
            AND pm.meta_key = '_super_submission_hash'
            AND pm.meta_value = %s
            ORDER BY p.post_date DESC
            LIMIT 1",
            $form_id,
            $cutoff,
            $submission_hash
        ));

        if ($entry_id) {
            return [
                'duplicate' => true,
                'method' => 'hash',
                'original_entry_id' => $entry_id,
                'details' => sprintf(
                    'Exact duplicate of entry #%d',
                    $entry_id
                ),
                'hash' => $submission_hash,
            ];
        }

        // Store hash for future duplicate checks
        // This will be stored after entry creation
        $GLOBALS['super_pending_submission_hash'] = $submission_hash;

        return ['duplicate' => false];
    }

    /**
     * Check for duplicate by custom field combination
     *
     * @param int $form_id Form ID
     * @param array $form_data Form data
     * @param array $settings Detection settings
     * @return array Result
     */
    private static function check_custom_fields($form_id, $form_data, $settings) {
        $unique_fields = $settings['custom_unique_fields'];

        if (empty($unique_fields)) {
            return ['duplicate' => false];
        }

        // Build conditions for each unique field
        $field_values = [];
        foreach ($unique_fields as $field) {
            if (!isset($form_data[$field]) || $form_data[$field] === '') {
                // Skip if any required field is empty
                return ['duplicate' => false];
            }
            $field_values[$field] = $form_data[$field];
        }

        global $wpdb;

        // Build query to find matching entries
        $entry_ids = null;

        foreach ($field_values as $field => $value) {
            $matching_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT entry_id
                FROM {$wpdb->prefix}superforms_entry_data
                WHERE form_id = %d
                AND field_name = %s
                AND field_value = %s",
                $form_id,
                $field,
                $value
            ));

            if ($entry_ids === null) {
                $entry_ids = $matching_ids;
            } else {
                // Intersect with previous results
                $entry_ids = array_intersect($entry_ids, $matching_ids);
            }

            if (empty($entry_ids)) {
                break;
            }
        }

        if (!empty($entry_ids)) {
            $entry_id = reset($entry_ids);

            return [
                'duplicate' => true,
                'method' => 'custom_fields',
                'original_entry_id' => $entry_id,
                'details' => sprintf(
                    'Duplicate by fields: %s (entry #%d)',
                    implode(', ', array_keys($field_values)),
                    $entry_id
                ),
                'matched_fields' => $field_values,
            ];
        }

        return ['duplicate' => false];
    }

    /**
     * Store submission hash after entry creation
     *
     * Called after successful entry creation to enable hash-based detection.
     *
     * @param int $entry_id Entry ID
     */
    public static function store_submission_hash($entry_id) {
        if (!empty($GLOBALS['super_pending_submission_hash'])) {
            update_post_meta($entry_id, '_super_submission_hash', $GLOBALS['super_pending_submission_hash']);
            unset($GLOBALS['super_pending_submission_hash']);
        }
    }

    /**
     * Get the action to take on duplicate detection
     *
     * @param int $form_id Form ID
     * @return string Action: 'block', 'update', or 'allow'
     */
    public static function get_action($form_id) {
        $settings = self::get_settings($form_id);
        return $settings['action_on_duplicate'] ?? 'block';
    }
}
```

### File 2: Integration with Form Submission

**File:** `/src/includes/class-ajax.php`
**Location:** After spam detection, BEFORE entry creation

```php
// ──────────────────────────────────────────────────────────────
// DUPLICATE DETECTION (Pre-submission firewall)
// @since 6.5.0 - Multi-method duplicate detection
// ──────────────────────────────────────────────────────────────

$duplicate_context = [
    'user_ip' => SUPER_Common::real_ip(),
    'user_id' => get_current_user_id(),
    'session_key' => $session_key,
];

$duplicate_result = SUPER_Duplicate_Detector::check($form_id, $data, $duplicate_context);

if ($duplicate_result['duplicate']) {
    $duplicate_action = SUPER_Duplicate_Detector::get_action($form_id);

    // Fire form.duplicate_detected event
    if (class_exists('SUPER_Trigger_Executor')) {
        $event_result = SUPER_Trigger_Executor::fire_event('form.duplicate_detected', [
            'form_id' => $form_id,
            'detection_method' => $duplicate_result['method'],
            'original_entry_id' => $duplicate_result['original_entry_id'],
            'duplicate_details' => $duplicate_result['details'],
            'form_data' => $data,
            'session_key' => $session_key,
            'user_ip' => $duplicate_context['user_ip'],
            'timestamp' => current_time('mysql'),
            'configured_action' => $duplicate_action,
        ]);

        // Check if any action aborted the submission
        if (is_wp_error($event_result) && $event_result->get_error_code() === 'submission_aborted') {
            // Mark session as aborted
            if ($session_key && class_exists('SUPER_Session_DAL')) {
                SUPER_Session_DAL::mark_aborted($session_key, 'duplicate_detected:' . $duplicate_result['method']);
            }

            // Return abort message
            $error_data = $event_result->get_error_data();
            $message = $error_data['show_message'] ?? true
                ? $event_result->get_error_message()
                : __('Your submission could not be processed.', 'super-forms');

            SUPER_Common::output_message($error = true, $message);
            die();
        }
    }

    // Handle based on configured action
    switch ($duplicate_action) {
        case 'block':
            // Silent rejection (don't inform user it's a duplicate)
            if ($session_key && class_exists('SUPER_Session_DAL')) {
                SUPER_Session_DAL::mark_aborted($session_key, 'duplicate_blocked');
            }
            SUPER_Common::output_message(
                $error = true,
                __('Your submission could not be processed at this time.', 'super-forms')
            );
            die();

        case 'update':
            // Update existing entry instead of creating new
            $entry_id = $duplicate_result['original_entry_id'];
            // Set flag for later processing
            $GLOBALS['super_update_existing_entry'] = $entry_id;
            break;

        case 'allow':
            // Allow duplicate to proceed
            break;
    }
}
```

### File 3: Store Hash After Entry Creation

**File:** `/src/includes/class-ajax.php`
**Location:** After entry creation

```php
// After entry is created:
if ($entry_id && class_exists('SUPER_Duplicate_Detector')) {
    SUPER_Duplicate_Detector::store_submission_hash($entry_id);
}
```

### File 4: Form Settings Schema

```php
[
    'group' => 'duplicate_detection',
    'group_label' => __('Duplicate Detection', 'super-forms'),
    'fields' => [
        [
            'name' => 'duplicate_detection_enabled',
            'label' => __('Enable Duplicate Detection', 'super-forms'),
            'type' => 'toggle',
            'default' => false,
            'description' => __('Prevent duplicate form submissions.', 'super-forms'),
        ],
        [
            'name' => 'email_time_enabled',
            'label' => __('Email + Time Window', 'super-forms'),
            'type' => 'toggle',
            'default' => true,
            'description' => __('Block if same email submitted recently.', 'super-forms'),
            'conditions' => [['field' => 'duplicate_detection_enabled', 'value' => true]],
        ],
        [
            'name' => 'email_field',
            'label' => __('Email Field Name', 'super-forms'),
            'type' => 'text',
            'default' => 'email',
            'conditions' => [
                ['field' => 'duplicate_detection_enabled', 'value' => true],
                ['field' => 'email_time_enabled', 'value' => true],
            ],
        ],
        [
            'name' => 'email_time_window',
            'label' => __('Time Window (minutes)', 'super-forms'),
            'type' => 'number',
            'default' => 10,
            'min' => 1,
            'max' => 1440,
            'conditions' => [
                ['field' => 'duplicate_detection_enabled', 'value' => true],
                ['field' => 'email_time_enabled', 'value' => true],
            ],
        ],
        [
            'name' => 'ip_time_enabled',
            'label' => __('IP + Time Window', 'super-forms'),
            'type' => 'toggle',
            'default' => true,
            'conditions' => [['field' => 'duplicate_detection_enabled', 'value' => true]],
        ],
        [
            'name' => 'ip_time_window',
            'label' => __('IP Time Window (minutes)', 'super-forms'),
            'type' => 'number',
            'default' => 5,
            'min' => 1,
            'max' => 60,
            'conditions' => [
                ['field' => 'duplicate_detection_enabled', 'value' => true],
                ['field' => 'ip_time_enabled', 'value' => true],
            ],
        ],
        [
            'name' => 'hash_enabled',
            'label' => __('Exact Duplicate (Hash)', 'super-forms'),
            'type' => 'toggle',
            'default' => true,
            'description' => __('Block submissions with identical field values.', 'super-forms'),
            'conditions' => [['field' => 'duplicate_detection_enabled', 'value' => true]],
        ],
        [
            'name' => 'action_on_duplicate',
            'label' => __('Action on Duplicate', 'super-forms'),
            'type' => 'select',
            'default' => 'block',
            'options' => [
                'block' => __('Block submission', 'super-forms'),
                'update' => __('Update existing entry', 'super-forms'),
                'allow' => __('Allow duplicate', 'super-forms'),
            ],
            'conditions' => [['field' => 'duplicate_detection_enabled', 'value' => true]],
        ],
    ],
]
```

## Testing

**File:** `/tests/triggers/test-duplicate-detector.php` (NEW)

```php
<?php
class Test_Duplicate_Detector extends WP_UnitTestCase {

    private $form_id;
    private $entry_id;

    public function setUp(): void {
        parent::setUp();

        // Create test form
        $this->form_id = wp_insert_post([
            'post_type' => 'super_form',
            'post_status' => 'publish',
            'post_title' => 'Test Form',
        ]);

        // Enable duplicate detection
        update_post_meta($this->form_id, '_super_form_settings', [
            'duplicate_detection' => [
                'duplicate_detection_enabled' => true,
                'email_time_enabled' => true,
                'email_time_window' => 10,
            ]
        ]);

        // Create existing entry
        $this->entry_id = wp_insert_post([
            'post_type' => 'super_contact_entry',
            'post_status' => 'publish',
            'post_parent' => $this->form_id,
        ]);

        // Add entry data
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'superforms_entry_data', [
            'entry_id' => $this->entry_id,
            'form_id' => $this->form_id,
            'field_name' => 'email',
            'field_value' => 'existing@example.com',
            'created_at' => current_time('mysql'),
        ]);
    }

    public function test_email_time_duplicate_detected() {
        $result = SUPER_Duplicate_Detector::check($this->form_id, [
            'email' => 'existing@example.com',
            'name' => 'John',
        ]);

        $this->assertTrue($result['duplicate']);
        $this->assertEquals('email_time', $result['method']);
        $this->assertEquals($this->entry_id, $result['original_entry_id']);
    }

    public function test_new_email_passes() {
        $result = SUPER_Duplicate_Detector::check($this->form_id, [
            'email' => 'new@example.com',
            'name' => 'Jane',
        ]);

        $this->assertFalse($result['duplicate']);
    }

    public function test_disabled_detection_allows_all() {
        // Disable detection
        update_post_meta($this->form_id, '_super_form_settings', [
            'duplicate_detection' => [
                'duplicate_detection_enabled' => false,
            ]
        ]);

        $result = SUPER_Duplicate_Detector::check($this->form_id, [
            'email' => 'existing@example.com',
        ]);

        $this->assertFalse($result['duplicate']);
    }
}
```

## Dependencies

- Step 1: Sessions Table (for abort flow)
- Step 3: Spam Detector (runs before duplicate detection)
- EAV table must exist for field queries

## Notes

- Duplicate detection runs AFTER spam detection (spam blocked first)
- Hash is stored after entry creation (not before)
- "Update existing" option useful for profile update forms
- Time windows are configurable per-form
- IP-based detection may give false positives on shared IPs (offices, schools)
