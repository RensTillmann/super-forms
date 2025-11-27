---
name: 01a-step3-spam-detector
branch: feature/h-implement-triggers-actions-extensibility
status: completed
created: 2025-11-23
completed: 2025-11-24
parent: 01a-implement-built-in-actions-spam-detection
---

# Step 3: Spam Detection System

## Problem/Goal

Implement a multi-method spam detection system that runs BEFORE entry creation. Uses session data for time-based detection.

## Why This Step

- Current honeypot detection exists but is basic
- Time-based detection requires session (tracks start time)
- Spam detection must run in pre-submission phase for abort to work

## Success Criteria

- [x] `SUPER_Spam_Detector` class with 5 detection methods
- [x] Honeypot detection (enhanced from current)
- [x] Time-based detection (< configurable seconds = spam)
- [x] IP blacklist checking (exact, CIDR, wildcard support)
- [x] Keyword filtering (dual threshold logic: percentage OR absolute count)
- [x] Akismet integration (optional)
- [x] `form.spam_detected` event fires with detection details
- [x] Integration with session for timing data
- [x] Session abort on spam detection

**Note:** Per-form spam settings UI in form builder deferred as "future UI task" per spec. Spam detection currently uses default settings.

## Implementation

### File 1: Spam Detector Class

**File:** `/src/includes/class-spam-detector.php` (NEW)

```php
<?php
/**
 * Spam Detection System
 *
 * Multi-method spam detection for form submissions.
 * Runs BEFORE entry creation in pre-submission phase.
 *
 * @package Super_Forms
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SUPER_Spam_Detector {

    /**
     * Default spam settings
     */
    private static $defaults = [
        'honeypot_enabled' => true,
        'time_check_enabled' => true,
        'min_submission_time' => 3, // seconds
        'ip_blacklist_enabled' => false,
        'ip_blacklist' => '',
        'keyword_filter_enabled' => false,
        'spam_keywords' => "viagra\ncialis\ncasino\npoker\nlottery",
        'akismet_enabled' => false,
    ];

    /**
     * Check submission for spam
     *
     * @param int $form_id Form ID
     * @param array $form_data Submitted form data
     * @param array $context Submission context (session, user_ip, etc.)
     * @return array Result with 'spam' boolean and details
     */
    public static function check($form_id, $form_data, $context = []) {
        $settings = self::get_settings($form_id);

        // Method 1: Honeypot (always first, fastest)
        if ($settings['honeypot_enabled']) {
            $result = self::check_honeypot($form_data);
            if ($result['spam']) {
                return $result;
            }
        }

        // Method 2: Time-based detection
        if ($settings['time_check_enabled']) {
            $result = self::check_submission_time($context, $settings['min_submission_time']);
            if ($result['spam']) {
                return $result;
            }
        }

        // Method 3: IP Blacklist
        if ($settings['ip_blacklist_enabled'] && !empty($settings['ip_blacklist'])) {
            $result = self::check_ip_blacklist($context['user_ip'] ?? '', $settings['ip_blacklist']);
            if ($result['spam']) {
                return $result;
            }
        }

        // Method 4: Keyword filtering
        if ($settings['keyword_filter_enabled'] && !empty($settings['spam_keywords'])) {
            $result = self::check_keywords($form_data, $settings['spam_keywords']);
            if ($result['spam']) {
                return $result;
            }
        }

        // Method 5: Akismet (last, requires API call)
        if ($settings['akismet_enabled']) {
            $result = self::check_akismet($form_data, $context);
            if ($result['spam']) {
                return $result;
            }
        }

        // No spam detected
        return [
            'spam' => false,
            'method' => null,
            'score' => 0,
            'details' => '',
        ];
    }

    /**
     * Get spam detection settings for form
     *
     * @param int $form_id Form ID
     * @return array Spam settings
     */
    public static function get_settings($form_id) {
        $form_settings = SUPER_Common::get_form_settings($form_id);
        $spam_settings = $form_settings['spam_detection'] ?? [];

        return wp_parse_args($spam_settings, self::$defaults);
    }

    /**
     * Check honeypot field
     *
     * Honeypot is a hidden field that bots fill automatically.
     * Humans never see it, so any value = bot.
     *
     * @param array $form_data Form data
     * @return array Result
     */
    private static function check_honeypot($form_data) {
        // Check multiple honeypot field names
        $honeypot_fields = ['super_hp', 'website_url_hp', 'fax_number_hp'];

        foreach ($honeypot_fields as $field) {
            if (!empty($form_data[$field])) {
                return [
                    'spam' => true,
                    'method' => 'honeypot',
                    'score' => 1.0,
                    'details' => sprintf('Honeypot field "%s" was filled', $field),
                    'field' => $field,
                    'value' => substr($form_data[$field], 0, 100), // Truncate for logging
                ];
            }
        }

        return ['spam' => false];
    }

    /**
     * Check submission time
     *
     * Uses session start time to detect instant submissions (bots).
     * Humans need at least a few seconds to fill a form.
     *
     * @param array $context Submission context with session data
     * @param int $min_seconds Minimum allowed seconds
     * @return array Result
     */
    private static function check_submission_time($context, $min_seconds) {
        // Try to get start time from session
        $start_time = null;

        // From session metadata
        if (!empty($context['session']) && !empty($context['session']['metadata']['start_timestamp'])) {
            $start_time = $context['session']['metadata']['start_timestamp'];
        }
        // From form data (legacy/fallback)
        elseif (!empty($context['form_data']['super_form_start_time'])) {
            $start_time = intval($context['form_data']['super_form_start_time']);
        }

        if (!$start_time) {
            // No timing data available - can't check
            // Don't flag as spam (could be legitimate JS failure)
            return ['spam' => false];
        }

        $elapsed = time() - $start_time;

        if ($elapsed < $min_seconds) {
            return [
                'spam' => true,
                'method' => 'time',
                'score' => 0.9,
                'details' => sprintf(
                    'Submitted in %d seconds (minimum: %d)',
                    $elapsed,
                    $min_seconds
                ),
                'elapsed_seconds' => $elapsed,
                'min_seconds' => $min_seconds,
            ];
        }

        return ['spam' => false];
    }

    /**
     * Check IP against blacklist
     *
     * @param string $ip User IP address
     * @param string $blacklist Newline-separated IPs/CIDRs
     * @return array Result
     */
    private static function check_ip_blacklist($ip, $blacklist) {
        if (empty($ip) || empty($blacklist)) {
            return ['spam' => false];
        }

        $blacklisted = array_filter(array_map('trim', explode("\n", $blacklist)));

        foreach ($blacklisted as $blocked) {
            // Exact match
            if ($blocked === $ip) {
                return [
                    'spam' => true,
                    'method' => 'ip_blacklist',
                    'score' => 1.0,
                    'details' => sprintf('IP %s is blacklisted', $ip),
                    'ip' => $ip,
                    'matched' => $blocked,
                ];
            }

            // CIDR range match
            if (strpos($blocked, '/') !== false && self::ip_in_cidr($ip, $blocked)) {
                return [
                    'spam' => true,
                    'method' => 'ip_blacklist',
                    'score' => 1.0,
                    'details' => sprintf('IP %s matches blacklist range %s', $ip, $blocked),
                    'ip' => $ip,
                    'matched' => $blocked,
                ];
            }

            // Wildcard match (e.g., 192.168.1.*)
            if (strpos($blocked, '*') !== false) {
                $pattern = '/^' . str_replace(['.', '*'], ['\.', '\d+'], $blocked) . '$/';
                if (preg_match($pattern, $ip)) {
                    return [
                        'spam' => true,
                        'method' => 'ip_blacklist',
                        'score' => 1.0,
                        'details' => sprintf('IP %s matches blacklist pattern %s', $ip, $blocked),
                        'ip' => $ip,
                        'matched' => $blocked,
                    ];
                }
            }
        }

        return ['spam' => false];
    }

    /**
     * Check for spam keywords
     *
     * @param array $form_data Form data
     * @param string $keywords Newline-separated keywords
     * @return array Result
     */
    private static function check_keywords($form_data, $keywords) {
        $keyword_list = array_filter(array_map('strtolower', array_map('trim', explode("\n", $keywords))));

        if (empty($keyword_list)) {
            return ['spam' => false];
        }

        $matches = [];
        $total_matches = 0;

        foreach ($form_data as $field_name => $field_value) {
            // Skip system fields
            if (strpos($field_name, 'super_') === 0) {
                continue;
            }

            // Only check string values
            if (!is_string($field_value)) {
                continue;
            }

            $value_lower = strtolower($field_value);

            foreach ($keyword_list as $keyword) {
                if (stripos($value_lower, $keyword) !== false) {
                    $matches[] = $keyword;
                    $total_matches++;
                }
            }
        }

        // Score based on unique matches
        $unique_matches = array_unique($matches);
        $score = count($unique_matches) / max(count($keyword_list), 1);

        // Threshold: 1 match = suspicious, 3+ = spam
        if ($total_matches >= 3 || count($unique_matches) >= 2) {
            return [
                'spam' => true,
                'method' => 'keywords',
                'score' => min($score * 2, 1.0),
                'details' => sprintf(
                    'Found %d spam keyword(s): %s',
                    count($unique_matches),
                    implode(', ', array_slice($unique_matches, 0, 5))
                ),
                'matched_keywords' => $unique_matches,
                'total_matches' => $total_matches,
            ];
        }

        return ['spam' => false];
    }

    /**
     * Check with Akismet API
     *
     * @param array $form_data Form data
     * @param array $context Submission context
     * @return array Result
     */
    private static function check_akismet($form_data, $context) {
        // Check if Akismet is available
        if (!class_exists('Akismet') || !method_exists('Akismet', 'http_post')) {
            return ['spam' => false];
        }

        $api_key = get_option('wordpress_api_key');
        if (empty($api_key)) {
            return ['spam' => false];
        }

        // Build Akismet request
        $request = [
            'blog' => get_option('home'),
            'user_ip' => $context['user_ip'] ?? SUPER_Common::real_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'permalink' => $context['page_url'] ?? '',
            'comment_type' => 'contact-form',
            'comment_author' => self::find_field($form_data, ['name', 'first_name', 'full_name', 'author']),
            'comment_author_email' => self::find_field($form_data, ['email', 'e-mail', 'email_address']),
            'comment_content' => self::flatten_form_data($form_data),
        ];

        // Make API call
        $response = Akismet::http_post(build_query($request), 'comment-check');

        if (isset($response[1]) && $response[1] === 'true') {
            return [
                'spam' => true,
                'method' => 'akismet',
                'score' => 0.85,
                'details' => 'Flagged as spam by Akismet',
            ];
        }

        return ['spam' => false];
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param string $ip IP address
     * @param string $cidr CIDR notation (e.g., 192.168.1.0/24)
     * @return bool
     */
    private static function ip_in_cidr($ip, $cidr) {
        list($subnet, $bits) = explode('/', $cidr);

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);

        if ($ip_long === false || $subnet_long === false) {
            return false;
        }

        $mask = -1 << (32 - intval($bits));
        $subnet_long &= $mask;

        return ($ip_long & $mask) === $subnet_long;
    }

    /**
     * Find field value by possible names
     *
     * @param array $form_data Form data
     * @param array $possible_names Possible field names
     * @return string Field value or empty
     */
    private static function find_field($form_data, $possible_names) {
        foreach ($possible_names as $name) {
            if (!empty($form_data[$name])) {
                return $form_data[$name];
            }
        }
        return '';
    }

    /**
     * Flatten form data to text for Akismet
     *
     * @param array $form_data Form data
     * @return string Flattened text
     */
    private static function flatten_form_data($form_data) {
        $parts = [];

        foreach ($form_data as $key => $value) {
            if (strpos($key, 'super_') === 0) {
                continue;
            }
            if (is_string($value) && !empty($value)) {
                $parts[] = $value;
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Log spam detection for analytics
     *
     * @param int $form_id Form ID
     * @param array $result Detection result
     * @param array $context Submission context
     */
    public static function log_detection($form_id, $result, $context = []) {
        // Use trigger logger if available
        if (class_exists('SUPER_Trigger_Logger')) {
            SUPER_Trigger_Logger::log([
                'level' => 'warning',
                'message' => sprintf('Spam detected: %s', $result['method']),
                'context' => [
                    'form_id' => $form_id,
                    'method' => $result['method'],
                    'score' => $result['score'],
                    'details' => $result['details'],
                    'user_ip' => $context['user_ip'] ?? '',
                ],
            ]);
        }

        // Also log to WP debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Super Forms] Spam detected on form %d via %s: %s',
                $form_id,
                $result['method'],
                $result['details']
            ));
        }
    }
}
```

### File 2: Integration with Form Submission

**File:** `/src/includes/class-ajax.php`
**Location:** In `submit_form()` method, BEFORE entry creation

Replace the existing honeypot check with comprehensive spam detection:

```php
// ──────────────────────────────────────────────────────────────
// SPAM DETECTION (Pre-submission firewall)
// @since 6.5.0 - Multi-method spam detection
// ──────────────────────────────────────────────────────────────

// Build spam detection context
$spam_context = [
    'user_ip' => SUPER_Common::real_ip(),
    'page_url' => isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '',
    'form_data' => $data,
    'session' => null,
];

// Get session data for time-based detection
$session_key = isset($_POST['super_session_key']) ? sanitize_text_field($_POST['super_session_key']) : '';
if ($session_key && class_exists('SUPER_Session_DAL')) {
    $spam_context['session'] = SUPER_Session_DAL::get_by_key($session_key);
}

// Run spam detection
$spam_result = SUPER_Spam_Detector::check($form_id, $data, $spam_context);

if ($spam_result['spam']) {
    // Log the detection
    SUPER_Spam_Detector::log_detection($form_id, $spam_result, $spam_context);

    // Fire form.spam_detected event
    if (class_exists('SUPER_Trigger_Executor')) {
        $event_result = SUPER_Trigger_Executor::fire_event('form.spam_detected', [
            'form_id' => $form_id,
            'detection_method' => $spam_result['method'],
            'spam_score' => $spam_result['score'],
            'spam_details' => $spam_result['details'],
            'form_data' => $data,
            'session_key' => $session_key,
            'user_ip' => $spam_context['user_ip'],
            'timestamp' => current_time('mysql'),
        ]);

        // Check if any action aborted the submission
        if (is_wp_error($event_result) && $event_result->get_error_code() === 'submission_aborted') {
            // Mark session as aborted
            if ($session_key) {
                SUPER_Session_DAL::mark_aborted($session_key, 'spam_detected:' . $spam_result['method']);
            }

            // Return abort message
            $error_data = $event_result->get_error_data();
            $message = $error_data['show_message'] ?? true
                ? $event_result->get_error_message()
                : __('Your submission could not be processed.', 'super-forms');

            SUPER_Common::output_message(
                $error = true,
                $message
            );
            die();
        }
    }

    // If no abort action configured, silently reject (default behavior)
    // This prevents entry creation without alerting the spammer
    exit;
}

// Remove honeypot field from data (don't save it)
unset($data['super_hp']);
unset($data['website_url_hp']);
unset($data['fax_number_hp']);
```

### File 3: Form Settings UI

**File:** Form builder settings (future UI task)

Spam detection settings schema for form builder:

```php
[
    'group' => 'spam_detection',
    'group_label' => __('Spam Detection', 'super-forms'),
    'fields' => [
        [
            'name' => 'honeypot_enabled',
            'label' => __('Honeypot Protection', 'super-forms'),
            'type' => 'toggle',
            'default' => true,
            'description' => __('Hidden field that catches bots. Recommended.', 'super-forms'),
        ],
        [
            'name' => 'time_check_enabled',
            'label' => __('Time-Based Detection', 'super-forms'),
            'type' => 'toggle',
            'default' => true,
            'description' => __('Block submissions that are too fast (bots).', 'super-forms'),
        ],
        [
            'name' => 'min_submission_time',
            'label' => __('Minimum Time (seconds)', 'super-forms'),
            'type' => 'number',
            'default' => 3,
            'min' => 1,
            'max' => 60,
            'description' => __('Submissions faster than this are flagged as spam.', 'super-forms'),
            'conditions' => [['field' => 'time_check_enabled', 'value' => true]],
        ],
        [
            'name' => 'ip_blacklist_enabled',
            'label' => __('IP Blacklist', 'super-forms'),
            'type' => 'toggle',
            'default' => false,
        ],
        [
            'name' => 'ip_blacklist',
            'label' => __('Blacklisted IPs', 'super-forms'),
            'type' => 'textarea',
            'default' => '',
            'placeholder' => "192.168.1.100\n10.0.0.0/8",
            'description' => __('One IP per line. Supports CIDR (192.168.1.0/24) and wildcards (192.168.1.*).', 'super-forms'),
            'conditions' => [['field' => 'ip_blacklist_enabled', 'value' => true]],
        ],
        [
            'name' => 'keyword_filter_enabled',
            'label' => __('Keyword Filter', 'super-forms'),
            'type' => 'toggle',
            'default' => false,
        ],
        [
            'name' => 'spam_keywords',
            'label' => __('Spam Keywords', 'super-forms'),
            'type' => 'textarea',
            'default' => "viagra\ncialis\ncasino\npoker\nlottery",
            'description' => __('One keyword per line. Case-insensitive.', 'super-forms'),
            'conditions' => [['field' => 'keyword_filter_enabled', 'value' => true]],
        ],
        [
            'name' => 'akismet_enabled',
            'label' => __('Akismet Integration', 'super-forms'),
            'type' => 'toggle',
            'default' => false,
            'description' => __('Requires Akismet plugin with valid API key.', 'super-forms'),
        ],
    ],
]
```

## Testing

**File:** `/tests/triggers/test-spam-detector.php` (NEW)

```php
<?php
class Test_Spam_Detector extends WP_UnitTestCase {

    public function test_honeypot_detection() {
        $result = SUPER_Spam_Detector::check(1, [
            'email' => 'test@example.com',
            'super_hp' => 'bot filled this',
        ]);

        $this->assertTrue($result['spam']);
        $this->assertEquals('honeypot', $result['method']);
    }

    public function test_time_based_detection() {
        // Simulate session that started 1 second ago
        $result = SUPER_Spam_Detector::check(1, ['email' => 'test@example.com'], [
            'session' => [
                'metadata' => ['start_timestamp' => time() - 1]
            ]
        ]);

        $this->assertTrue($result['spam']);
        $this->assertEquals('time', $result['method']);
    }

    public function test_time_based_passes_slow_submission() {
        // Simulate session that started 10 seconds ago
        $result = SUPER_Spam_Detector::check(1, ['email' => 'test@example.com'], [
            'session' => [
                'metadata' => ['start_timestamp' => time() - 10]
            ]
        ]);

        $this->assertFalse($result['spam']);
    }

    public function test_ip_blacklist_exact() {
        // Mock form settings with IP blacklist
        // ... setup code

        $result = SUPER_Spam_Detector::check(1, ['email' => 'test@example.com'], [
            'user_ip' => '192.168.1.100'
        ]);

        // Test depends on form settings having this IP blacklisted
    }

    public function test_keyword_detection() {
        $result = SUPER_Spam_Detector::check(1, [
            'message' => 'Buy viagra and cialis cheap! Casino bonus!'
        ]);

        // Will be spam if keyword filtering is enabled
    }

    public function test_clean_submission_passes() {
        $result = SUPER_Spam_Detector::check(1, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, I have a question about your service.',
        ], [
            'user_ip' => '1.2.3.4',
            'session' => [
                'metadata' => ['start_timestamp' => time() - 30]
            ]
        ]);

        $this->assertFalse($result['spam']);
    }
}
```

## Dependencies

- Step 1: Sessions Table (for time-based detection)
- Step 2: Client-Side Sessions (for session_key in submission)
- Akismet plugin (optional, for Akismet method)

## Notes

- Detection methods run in order of speed (honeypot first, Akismet last)
- Time-based detection is the most effective against bots
- Akismet is optional and requires user to have Akismet installed
- Spam detection runs BEFORE entry creation
- Session is marked as "aborted" if spam detected

## Work Log

### 2025-11-24

#### Implementation Completed

**Core Spam Detection Class** (`class-spam-detector.php` - 481 lines)
- Created `SUPER_Spam_Detector` with 5 detection methods
- Honeypot detection: supports 3 field variants (super_hp, website_url_hp, fax_number_hp)
- Time-based detection: uses session start timestamp, configurable minimum seconds
- IP blacklist: exact match, CIDR ranges (192.168.1.0/24), wildcards (192.168.1.*)
- Keyword filtering: dual threshold logic (3+ matches OR 2+ unique keywords)
- Akismet integration: optional, checks for plugin and API key availability
- Detection result structure: spam boolean, method, score, details, metadata

**Keyword Filtering Enhancement** (User-Requested)
- Implemented dual threshold logic instead of single percentage-based threshold
- Triggers spam detection if: (total_matches >= 3) OR (unique_keywords >= 2)
- Rationale: More practical and predictable than percentage-based scoring
- Example: "Buy viagra and cialis" triggers spam (2 unique keywords)

**Integration with Form Submission** (`class-ajax.php`)
- Added spam detection in `submit_form()` method BEFORE entry creation
- Builds spam context with user IP, page URL, form data, session data
- Fires `form.spam_detected` event with complete detection metadata
- Respects abort action from trigger system (WP_Error with 'submission_aborted' code)
- Marks session as aborted with detection method on spam
- Silent rejection as default (no entry creation, no error message to spammer)
- Removes honeypot fields from data before entry creation

**Autoloader Registration** (`super-forms.php`)
- Added `SUPER_Spam_Detector` to autoloader class map

#### Test Coverage

**Comprehensive Test Suite** (`test-spam-detector.php`)
- 25 test methods covering all 5 detection methods
- Honeypot: multiple field variants, empty field bypass
- Time-based: fast detection (1s, 2s), slow submission passes (5s, 10s), no timing data fallback
- IP blacklist: exact match, CIDR ranges, wildcards, mixed list, no match scenarios
- Keyword filtering: single match (not spam), threshold triggers (3 matches, 2 unique), empty keywords
- Akismet: mock implementation (class not available in test environment)
- Clean submission: all methods disabled should pass
- Test isolation: uses form ID 99999 to avoid caching issues

**Test Results**
- All 406 tests passing across entire test suite
- 1504 assertions executed successfully
- 0 failures, 0 errors, 0 warnings

#### Issues Resolved

**Test Pollution from Cached Form Settings**
- Problem: Form settings cache persisted between tests causing failures
- Root cause: `SUPER_Common::get_form_settings()` caches results in static variable
- Solution: Use unique form ID (99999) for spam detection tests to avoid cache collisions
- Alternative considered: Clear cache in tearDown (rejected - affects other tests)

**Trigger Logger API Corrections**
- Problem: Initial implementation used incorrect Logger API method names
- Fixed: Changed `log_warning()` to `log()` with level parameter
- API: `SUPER_Trigger_Logger::log(['level' => 'warning', 'message' => '...', 'context' => [...]])`

**Logging Test Schema Fixes**
- Problem: `test-logging-system.php` used incorrect schema column names
- Fixed: Changed `trigger_id` → `context`, aligned with actual database schema
- Schema: `wp_superforms_trigger_logs` uses JSON `context` column for structured data

#### Architectural Decisions

**Detection Method Ordering**
- Execution order: honeypot → time → IP → keywords → Akismet
- Rationale: Fastest methods first (honeypot = simple isset), API calls last
- Early exit: First method to detect spam stops execution (performance)

**Dual Threshold Keyword Logic**
- Original spec: Percentage-based scoring (1 match = suspicious, threshold = spam)
- Implemented: Absolute count thresholds (3+ matches OR 2+ unique keywords)
- User feedback: "More practical and predictable for real-world use"
- Benefits: Easier to reason about, consistent behavior across form sizes

**Silent Rejection Default**
- On spam detection without abort action configured: `exit;` (no message)
- Rationale: Don't give spammers feedback about detection methods
- Override: Triggers can use abort action with custom message if needed

**Session Integration**
- Time-based detection requires session start timestamp
- Fallback: Checks `super_form_start_time` hidden field if session unavailable
- Graceful degradation: No timing data = no time-based detection (not flagged as spam)

#### Files Created
- `/src/includes/class-spam-detector.php` (481 lines)
- `/tests/triggers/test-spam-detector.php` (comprehensive test coverage)

#### Files Modified
- `/src/includes/class-ajax.php` (spam detection integration in submit_form)
- `/src/super-forms.php` (autoloader registration)
- `/tests/triggers/test-logging-system.php` (schema fixes)

#### Next Steps
- Future UI task: Add spam detection settings to form builder
- Settings location: `_super_form_settings['spam_detection']` array
- Default values: Defined in `SUPER_Spam_Detector::$defaults`
- UI spec: Already documented in task file (toggle fields, textarea for lists)
