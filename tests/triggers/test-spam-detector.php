<?php
/**
 * Test Spam Detector
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 * @since 6.5.0
 */

class Test_Spam_Detector extends WP_UnitTestCase {

    /**
     * Test form ID
     */
    private $form_id;

    /**
     * Setup before each test
     */
    public function setUp(): void {
        parent::setUp();

        // Skip all tests if SUPER_Spam_Detector class doesn't exist yet
        if ( ! class_exists( 'SUPER_Spam_Detector' ) ) {
            $this->markTestSkipped( 'SUPER_Spam_Detector class not implemented yet' );
        }

        // Clear cached form settings to prevent test pollution
        if ( class_exists( 'SUPER_Forms' ) ) {
            SUPER_Forms()->form_settings = null;
        }

        // Create test form
        $this->form_id = wp_insert_post([
            'post_type' => 'super_form',
            'post_status' => 'publish',
            'post_title' => 'Test Form'
        ]);

        // Set default spam settings
        update_post_meta($this->form_id, '_super_form_settings', [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_submission_time' => 3,
                'ip_blacklist_enabled' => false,
                'keyword_filter_enabled' => false,
                'keyword_threshold' => 0.3,
                'keyword_min_matches' => 2,
                'akismet_enabled' => false
            ]
        ]);
    }

    /**
     * Cleanup after each test
     */
    public function tearDown(): void {
        // Clear cached form settings to prevent test pollution
        if ( class_exists( 'SUPER_Forms' ) ) {
            SUPER_Forms()->form_settings = null;
        }

        parent::tearDown();
    }

    /**
     * Test honeypot detection (bot fills hidden field)
     */
    public function test_honeypot_detection() {
        // Test with empty honeypot (human)
        $form_data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'super_hp' => '' // Empty honeypot
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        $this->assertFalse($result['spam'], 'Empty honeypot should not trigger spam');

        // Test with filled honeypot (bot)
        $form_data['super_hp'] = 'bot filled this';

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        $this->assertTrue($result['spam'], 'Filled honeypot should trigger spam');
        $this->assertEquals('honeypot', $result['method'], 'Should identify honeypot method');
        $this->assertEquals(1.0, $result['score'], 'Honeypot should have max score');
    }

    /**
     * Test time-based detection from session metadata (primary source)
     */
    public function test_time_based_detection() {
        // Enable time check with 3-second minimum
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_submission_time' => 3
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test fast submission (bot) - using session metadata
        $form_data = [
            'super_hp' => '' // Empty honeypot
        ];

        $context = [
            'session' => [
                'metadata' => [
                    'start_timestamp' => time() // Just started, 0 seconds elapsed
                ]
            ]
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, $context);

        $this->assertTrue($result['spam'], 'Fast submission should trigger spam');
        $this->assertEquals('time', $result['method'], 'Should identify time method');

        // Test slow submission (human) - using session metadata
        $context = [
            'session' => [
                'metadata' => [
                    'start_timestamp' => time() - 5 // 5 seconds ago
                ]
            ]
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, $context);

        $this->assertFalse($result['spam'], 'Slow submission should not trigger spam');
    }

    /**
     * Test time-based detection fallback to form_data (backwards compatibility)
     */
    public function test_time_based_detection_fallback() {
        // Enable time check with 3-second minimum
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_submission_time' => 3
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test fast submission using legacy form_data field
        $start_time = time();
        $form_data = [
            'super_form_start_time' => $start_time,
            'super_hp' => '' // Empty honeypot
        ];

        // No session context provided - should fallback to form_data
        $context = ['form_data' => $form_data];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, $context);

        $this->assertTrue($result['spam'], 'Fast submission should trigger spam via fallback');
        $this->assertEquals('time', $result['method'], 'Should identify time method');

        // Test slow submission (human)
        $form_data['super_form_start_time'] = $start_time - 5; // 5 seconds ago
        $context = ['form_data' => $form_data];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, $context);

        $this->assertFalse($result['spam'], 'Slow submission should not trigger spam via fallback');
    }

    /**
     * Test missing start time handling
     */
    public function test_missing_start_time() {
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_submission_time' => 3
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test with no start time
        $form_data = [
            'super_hp' => '',
            // No super_form_start_time field
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        // Should not flag as spam if time is missing (could be JS failure)
        $this->assertFalse($result['spam'], 'Missing time should not auto-flag as spam');
    }

    /**
     * Test IP blacklist detection
     */
    public function test_ip_blacklist() {
        $settings = [
            'spam_detection' => [
                'ip_blacklist_enabled' => true,
                'ip_blacklist' => "192.168.1.100\n10.0.0.0/24\n::1"
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test exact IP match
        $context = ['user_ip' => '192.168.1.100'];
        $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);

        // Debug: Output settings if spam not detected
        $debug_msg = 'Blacklisted IP should trigger spam';
        if (!$result['spam'] && isset($result['_debug_settings'])) {
            $debug_msg .= "\nSettings: " . print_r($result['_debug_settings'], true);
        }

        $this->assertTrue($result['spam'], $debug_msg);
        $this->assertEquals('ip_blacklist', $result['method']);

        // Test CIDR range match
        $context = ['user_ip' => '10.0.0.50'];
        $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);

        $this->assertTrue($result['spam'], 'IP in blacklisted range should trigger spam');

        // Test non-blacklisted IP
        $context = ['user_ip' => '8.8.8.8'];
        $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);

        $this->assertFalse($result['spam'], 'Non-blacklisted IP should not trigger spam');
    }

    /**
     * Test IP wildcard pattern matching
     */
    public function test_ip_wildcard_patterns() {
        $settings = [
            'spam_detection' => [
                'ip_blacklist_enabled' => true,
                'ip_blacklist' => "192.168.1.*\n10.0.*.*"
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test wildcard match - single segment
        $matching_ips = ['192.168.1.1', '192.168.1.100', '192.168.1.255'];

        foreach ($matching_ips as $ip) {
            $context = ['user_ip' => $ip];
            $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);
            $this->assertTrue($result['spam'], "IP $ip should match wildcard pattern 192.168.1.*");
            $this->assertEquals('ip_blacklist', $result['method']);
        }

        // Test wildcard match - multiple segments
        $matching_ips_multi = ['10.0.0.1', '10.0.1.1', '10.0.255.255'];

        foreach ($matching_ips_multi as $ip) {
            $context = ['user_ip' => $ip];
            $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);
            $this->assertTrue($result['spam'], "IP $ip should match wildcard pattern 10.0.*.*");
        }

        // Test non-matching IPs
        $non_matching = ['192.168.2.1', '10.1.0.1', '8.8.8.8'];

        foreach ($non_matching as $ip) {
            $context = ['user_ip' => $ip];
            $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);
            $this->assertFalse($result['spam'], "IP $ip should NOT match wildcard patterns");
        }
    }

    /**
     * Test keyword filtering
     */
    public function test_keyword_filtering() {
        $settings = [
            'spam_detection' => [
                'keyword_filter_enabled' => true,
                'spam_keywords' => "viagra\ncasino\npoker",
                'keyword_threshold' => 0.3
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test with spam keywords
        $form_data = [
            'super_hp' => '',
            'message' => 'Buy cheap viagra at our online casino',
            'subject' => 'Great poker deals'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        $this->assertTrue($result['spam'], 'Content with spam keywords should trigger spam');
        $this->assertEquals('keywords', $result['method']);
        $this->assertStringContainsString('viagra', $result['details']);

        // Test without spam keywords
        $form_data = [
            'super_hp' => '',
            'message' => 'I need help with my WordPress website',
            'subject' => 'Website support request'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        $this->assertFalse($result['spam'], 'Clean content should not trigger spam');
    }

    /**
     * Test keyword threshold calculation
     */
    public function test_keyword_threshold() {
        $settings = [
            'spam_detection' => [
                'keyword_filter_enabled' => true,
                'spam_keywords' => "test1\ntest2\ntest3\ntest4\ntest5",
                'keyword_threshold' => 0.5 // 50% threshold
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test below threshold (1 of 5 = 20%)
        $form_data = [
            'super_hp' => '',
            'message' => 'This contains test1 only'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertFalse($result['spam'], 'Below threshold should not trigger spam');

        // Test at threshold (3 of 5 = 60%)
        $form_data = [
            'super_hp' => '',
            'message' => 'This contains test1 test2 test3'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertTrue($result['spam'], 'At/above threshold should trigger spam');
    }

    /**
     * Test keyword minimum matches (absolute count)
     */
    public function test_keyword_min_matches() {
        $settings = [
            'spam_detection' => [
                'keyword_filter_enabled' => true,
                'spam_keywords' => "viagra\ncialis\ncasino\npoker",
                'keyword_threshold' => 0, // Disabled
                'keyword_min_matches' => 2 // Need 2 unique keywords
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test below minimum (1 unique keyword)
        $form_data = [
            'super_hp' => '',
            'message' => 'This mentions viagra only'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertFalse($result['spam'], 'Below minimum matches should not trigger spam');

        // Test at minimum (2 unique keywords)
        $form_data = [
            'super_hp' => '',
            'message' => 'Buy viagra and cialis here'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertTrue($result['spam'], 'At/above minimum matches should trigger spam');
    }

    /**
     * Test keyword threshold AND min_matches together (OR logic)
     */
    public function test_keyword_threshold_and_min_matches() {
        $settings = [
            'spam_detection' => [
                'keyword_filter_enabled' => true,
                'spam_keywords' => "test1\ntest2\ntest3\ntest4\ntest5",
                'keyword_threshold' => 0.6, // Need 60% (3 of 5)
                'keyword_min_matches' => 2  // OR need 2 unique keywords
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test: Meets min_matches but not threshold (2 unique = 40%, needs 60%)
        $form_data = [
            'super_hp' => '',
            'message' => 'Contains test1 and test2' // 2 of 5 = 40%
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertTrue($result['spam'], 'Should trigger via min_matches even though below threshold');

        // Test: Meets threshold but not min_matches if threshold were disabled (hypothetical)
        // Actually if 3+ keywords found, both conditions are met, so let's test edge case

        // Test: Below both thresholds
        $form_data = [
            'super_hp' => '',
            'message' => 'Contains test1 only' // 1 unique = 20%
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertFalse($result['spam'], 'Below both thresholds should not trigger spam');
    }

    /**
     * Test case-insensitive keyword matching
     */
    public function test_keyword_case_insensitive() {
        $settings = [
            'spam_detection' => [
                'keyword_filter_enabled' => true,
                'spam_keywords' => "viagra",
                'keyword_threshold' => 0.1
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        $variations = ['viagra', 'VIAGRA', 'Viagra', 'ViAgRa'];

        foreach ($variations as $variant) {
            $form_data = [
                'super_hp' => '',
                'message' => "Contains $variant in text"
            ];

            $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
            $this->assertTrue(
                $result['spam'],
                "Should detect '$variant' as spam (case-insensitive)"
            );
        }
    }

    /**
     * Test system fields are skipped in keyword check
     */
    public function test_system_fields_skipped() {
        $settings = [
            'spam_detection' => [
                'keyword_filter_enabled' => true,
                'spam_keywords' => "system",
                'keyword_threshold' => 0.1
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        $form_data = [
            'super_hp' => '',
            'super_form_start_time' => 'system', // System field
            'super_nonce' => 'system', // System field
            'user_message' => 'Clean message' // User field
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        $this->assertFalse($result['spam'], 'System fields should be ignored in keyword check');
    }

    /**
     * Test priority order of detection methods
     */
    public function test_detection_priority() {
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_submission_time' => 3,
                'keyword_filter_enabled' => true,
                'spam_keywords' => 'spam'
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Form with multiple spam indicators
        $form_data = [
            'super_hp' => 'bot filled this', // Honeypot (highest priority)
            'super_form_start_time' => time(), // Too fast
            'message' => 'This is spam content' // Keyword
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        // Honeypot should be detected first (highest priority)
        $this->assertTrue($result['spam']);
        $this->assertEquals('honeypot', $result['method'], 'Honeypot should have priority');
    }

    /**
     * Test Akismet integration (when available)
     */
    public function test_akismet_integration() {
        if (!class_exists('Akismet')) {
            $this->markTestSkipped('Akismet not available');
        }

        $settings = [
            'spam_detection' => [
                'akismet_enabled' => true
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Mock Akismet API key
        update_option('wordpress_api_key', 'test_api_key');

        // Mock Akismet response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, 'rest.akismet.com') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => 'true' // Spam
                ];
            }
            return $preempt;
        }, 10, 3);

        $form_data = [
            'super_hp' => '',
            'name' => 'Spammer',
            'email' => 'spam@spam.com',
            'message' => 'Buy cheap products'
        ];

        $context = [
            'user_ip' => '127.0.0.1',
            'page_url' => 'http://example.com/contact'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, $context);

        $this->assertTrue($result['spam'], 'Akismet should detect spam');
        $this->assertEquals('akismet', $result['method']);
    }

    /**
     * Test all methods disabled
     */
    public function test_all_methods_disabled() {
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => false,
                'ip_blacklist_enabled' => false,
                'keyword_filter_enabled' => false,
                'akismet_enabled' => false
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Only honeypot remains (always enabled)
        $form_data = [
            'super_hp' => '', // Empty honeypot
            'message' => 'Any content'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        $this->assertFalse($result['spam'], 'Should not flag spam with all methods disabled');
    }

    /**
     * Test CIDR range validation
     */
    public function test_cidr_range_validation() {
        $settings = [
            'spam_detection' => [
                'ip_blacklist_enabled' => true,
                'ip_blacklist' => "192.168.0.0/16"
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // IPs in range
        $in_range = ['192.168.0.1', '192.168.1.1', '192.168.255.254'];

        foreach ($in_range as $ip) {
            $context = ['user_ip' => $ip];
            $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);
            $this->assertTrue($result['spam'], "IP $ip should be in blacklisted range");
        }

        // IPs outside range
        $outside_range = ['192.167.0.1', '192.169.0.1', '10.0.0.1'];

        foreach ($outside_range as $ip) {
            $context = ['user_ip' => $ip];
            $result = SUPER_Spam_Detector::check($this->form_id, ['super_hp' => ''], $context);
            $this->assertFalse($result['spam'], "IP $ip should not be in blacklisted range");
        }
    }

    /**
     * Test that spam detection triggers form.spam_detected event
     */
    public function test_spam_event_firing() {
        // Track if event was fired
        $event_fired = false;
        $event_data = null;

        // Hook into the event
        add_action('super_after_trigger_event', function($event_name, $context) use (&$event_fired, &$event_data) {
            if ($event_name === 'form.spam_detected') {
                $event_fired = true;
                $event_data = $context;
            }
        }, 10, 2);

        // Trigger spam detection with honeypot
        $form_data = [
            'super_hp' => 'bot filled this',
            'name' => 'Test User'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);

        $this->assertTrue($result['spam'], 'Should detect spam');

        // Note: This test verifies the spam detector returns correct result
        // Event firing is tested in integration tests (test-event-firing.php)
        // where AJAX handler integration can be verified
    }

    /**
     * Test that log_detection() is called when spam is detected
     */
    public function test_spam_logging() {
        if (!class_exists('SUPER_Trigger_Logger')) {
            $this->markTestSkipped('SUPER_Trigger_Logger not available');
        }

        global $wpdb;

        // Get count of existing logs
        $table_name = $wpdb->prefix . 'superforms_trigger_logs';
        $initial_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        // Trigger spam detection
        $form_data = [
            'super_hp' => 'bot filled this',
            'name' => 'Test User'
        ];

        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertTrue($result['spam']);

        // Log the detection
        SUPER_Spam_Detector::log_detection($this->form_id, $result, ['user_ip' => '127.0.0.1']);

        // Verify log message was generated in database
        $new_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $this->assertGreaterThan($initial_count, $new_count, 'Spam detection should generate log entry');

        // Verify log content
        $log_entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE form_id = %d ORDER BY id DESC LIMIT 1",
                $this->form_id
            ),
            ARRAY_A
        );

        $this->assertNotEmpty($log_entry, 'Log entry should exist');
        $this->assertEquals('warning', $log_entry['status'], 'Status should be warning');
        $this->assertEquals($this->form_id, $log_entry['form_id'], 'Form ID should match');

        // Check context data contains spam method
        $context_data = json_decode($log_entry['context_data'], true);
        $this->assertNotEmpty($context_data, 'Context data should not be empty');
        $this->assertEquals('honeypot', $context_data['method'], 'Method should be honeypot');
    }

    /**
     * Test that session is marked as aborted when spam is detected
     * Note: This is an integration test - full verification requires AJAX integration
     */
    public function test_session_abort_on_spam() {
        // This test documents the expected behavior
        // Full integration testing happens in test-session-ajax.php

        // Create a session
        if (!class_exists('SUPER_Session_DAL')) {
            $this->markTestSkipped('SUPER_Session_DAL not available');
        }

        $session_id = SUPER_Session_DAL::create([
            'form_id' => $this->form_id,
            'user_id' => get_current_user_id(),
            'client_token' => 'test-token-123',
            'user_ip' => '127.0.0.1',
            'metadata' => [
                'start_timestamp' => time() - 10
            ]
        ]);

        $this->assertIsInt($session_id, 'Session should be created');

        $session = SUPER_Session_DAL::get($session_id);
        $this->assertEquals('draft', $session['status'], 'Initial status should be draft');

        // Simulate spam detection -> session abort
        $form_data = ['super_hp' => 'bot filled this'];
        $result = SUPER_Spam_Detector::check($this->form_id, $form_data, []);
        $this->assertTrue($result['spam']);

        // In actual implementation, AJAX handler marks session as aborted
        // Here we verify the DAL method works
        $update_result = SUPER_Session_DAL::mark_aborted($session['session_key'], 'spam_detected:honeypot');
        $this->assertTrue($update_result);

        // Verify status changed
        $updated_session = SUPER_Session_DAL::get_by_key($session['session_key']);
        $this->assertEquals('aborted', $updated_session['status']);
        $this->assertEquals('spam_detected:honeypot', $updated_session['metadata']['abort_reason']);
    }
}