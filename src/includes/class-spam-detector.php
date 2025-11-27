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
        'honeypot_enabled'        => true,   // Always recommended
        'time_check_enabled'      => true,   // Reliable, low false positive
        'min_submission_time'     => 3,      // seconds
        'ip_blacklist_enabled'    => false,  // Requires manual config
        'ip_blacklist'            => '',
        'keyword_filter_enabled'  => false,  // DISABLED by default (high false positive risk)
        'spam_keywords'           => "viagra\ncialis\ncasino\npoker\nlottery",
        'keyword_threshold'       => 0.3,    // 30% - percentage-based detection
        'keyword_min_matches'     => 2,      // Absolute count - minimum unique keywords
        'akismet_enabled'         => false,  // Requires Akismet plugin
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
            $result = self::check_submission_time($context, $form_data, $settings['min_submission_time']);
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
            $result = self::check_keywords($form_data, $settings['spam_keywords'], $settings['keyword_threshold'], $settings['keyword_min_matches']);
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
            '_debug_settings' => $settings,  // Temporary debug info
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

        $merged = wp_parse_args($spam_settings, self::$defaults);

        // Debug: Log settings in test environment
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log('[SPAM DEBUG] Form ID: ' . $form_id);
            error_log('[SPAM DEBUG] Spam settings from form: ' . print_r($spam_settings, true));
            error_log('[SPAM DEBUG] Merged settings: ' . print_r($merged, true));
        }

        return $merged;
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
     * @param array $form_data Form data (fallback for legacy)
     * @param int $min_seconds Minimum allowed seconds
     * @return array Result
     */
    private static function check_submission_time($context, $form_data, $min_seconds) {
        // Try to get start time from session (primary)
        $start_time = null;

        // From session metadata (primary source)
        if (!empty($context['session']) && !empty($context['session']['metadata']['start_timestamp'])) {
            $start_time = $context['session']['metadata']['start_timestamp'];
        }
        // From context form_data (if passed explicitly)
        elseif (!empty($context['form_data']['super_form_start_time'])) {
            $start_time = intval($context['form_data']['super_form_start_time']);
        }
        // From form data (legacy/fallback)
        elseif (!empty($form_data['super_form_start_time'])) {
            $start_time = intval($form_data['super_form_start_time']);
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
     * @param string $blacklist Newline-separated IPs/CIDRs/wildcards
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
     * Supports TWO detection modes (OR logic):
     * 1. Percentage threshold: If X% of keywords are found
     * 2. Absolute count: If N unique keywords are found
     *
     * @param array $form_data Form data
     * @param string $keywords Newline-separated keywords
     * @param float $threshold Percentage threshold (0.0-1.0, 0 = disabled)
     * @param int $min_matches Minimum unique keywords (0 = disabled)
     * @return array Result
     */
    private static function check_keywords($form_data, $keywords, $threshold, $min_matches) {
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

        // Get unique matches
        $unique_matches = array_unique($matches);
        $unique_count = count($unique_matches);

        // Calculate percentage
        $percentage = $unique_count / max(count($keyword_list), 1);

        // Check if spam via either method (OR logic)
        $spam_by_percentage = ($threshold > 0 && $percentage >= $threshold);
        $spam_by_count = ($min_matches > 0 && $unique_count >= $min_matches);

        if ($spam_by_percentage || $spam_by_count) {
            $detection_reason = [];
            if ($spam_by_percentage) {
                $detection_reason[] = sprintf('%.0f%% threshold met', $percentage * 100);
            }
            if ($spam_by_count) {
                $detection_reason[] = sprintf('%d unique keywords', $unique_count);
            }

            return [
                'spam' => true,
                'method' => 'keywords',
                'score' => min($percentage * 2, 1.0),
                'details' => sprintf(
                    'Found %d spam keyword(s) (%s): %s',
                    $unique_count,
                    implode(' and ', $detection_reason),
                    implode(', ', array_slice($unique_matches, 0, 5))
                ),
                'matched_keywords' => $unique_matches,
                'total_matches' => $total_matches,
                'percentage' => $percentage,
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
            $logger = SUPER_Trigger_Logger::instance();
            $logger->warning(
                sprintf('Spam detected: %s', $result['method']),
                [
                    'form_id' => $form_id,
                    'method' => $result['method'],
                    'score' => $result['score'],
                    'details' => $result['details'],
                    'user_ip' => $context['user_ip'] ?? '',
                ]
            );
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
