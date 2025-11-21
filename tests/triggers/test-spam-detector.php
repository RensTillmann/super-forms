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
    public function setUp() {
        parent::setUp();

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
                'min_time' => 3,
                'ip_blacklist_enabled' => false,
                'keyword_filter_enabled' => false,
                'akismet_enabled' => false
            ]
        ]);
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

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

        $this->assertFalse($result['spam'], 'Empty honeypot should not trigger spam');

        // Test with filled honeypot (bot)
        $form_data['super_hp'] = 'bot filled this';

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

        $this->assertTrue($result['spam'], 'Filled honeypot should trigger spam');
        $this->assertEquals('honeypot', $result['method'], 'Should identify honeypot method');
        $this->assertEquals(1.0, $result['score'], 'Honeypot should have max score');
    }

    /**
     * Test time-based detection (submission too fast)
     */
    public function test_time_based_detection() {
        // Enable time check with 3-second minimum
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_time' => 3
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test fast submission (bot)
        $start_time = time();
        $form_data = [
            'super_form_start_time' => $start_time,
            'super_hp' => '' // Empty honeypot
        ];

        // Submit immediately (0 seconds elapsed)
        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

        $this->assertTrue($result['spam'], 'Fast submission should trigger spam');
        $this->assertEquals('time', $result['method'], 'Should identify time method');

        // Test slow submission (human)
        $form_data['super_form_start_time'] = $start_time - 5; // 5 seconds ago

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

        $this->assertFalse($result['spam'], 'Slow submission should not trigger spam');
    }

    /**
     * Test missing start time handling
     */
    public function test_missing_start_time() {
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_time' => 3
            ]
        ];
        update_post_meta($this->form_id, '_super_form_settings', $settings);

        // Test with no start time
        $form_data = [
            'super_hp' => '',
            // No super_form_start_time field
        ];

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

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
        $result = SUPER_Spam_Detector::check_spam($this->form_id, ['super_hp' => ''], $context);

        $this->assertTrue($result['spam'], 'Blacklisted IP should trigger spam');
        $this->assertEquals('ip_blacklist', $result['method']);

        // Test CIDR range match
        $context = ['user_ip' => '10.0.0.50'];
        $result = SUPER_Spam_Detector::check_spam($this->form_id, ['super_hp' => ''], $context);

        $this->assertTrue($result['spam'], 'IP in blacklisted range should trigger spam');

        // Test non-blacklisted IP
        $context = ['user_ip' => '8.8.8.8'];
        $result = SUPER_Spam_Detector::check_spam($this->form_id, ['super_hp' => ''], $context);

        $this->assertFalse($result['spam'], 'Non-blacklisted IP should not trigger spam');
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

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

        $this->assertTrue($result['spam'], 'Content with spam keywords should trigger spam');
        $this->assertEquals('keywords', $result['method']);
        $this->assertStringContainsString('viagra', $result['details']);

        // Test without spam keywords
        $form_data = [
            'super_hp' => '',
            'message' => 'I need help with my WordPress website',
            'subject' => 'Website support request'
        ];

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

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

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);
        $this->assertFalse($result['spam'], 'Below threshold should not trigger spam');

        // Test at threshold (3 of 5 = 60%)
        $form_data = [
            'super_hp' => '',
            'message' => 'This contains test1 test2 test3'
        ];

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);
        $this->assertTrue($result['spam'], 'At/above threshold should trigger spam');
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

            $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);
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

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

        $this->assertFalse($result['spam'], 'System fields should be ignored in keyword check');
    }

    /**
     * Test priority order of detection methods
     */
    public function test_detection_priority() {
        $settings = [
            'spam_detection' => [
                'time_check_enabled' => true,
                'min_time' => 3,
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

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

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

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, $context);

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

        $result = SUPER_Spam_Detector::check_spam($this->form_id, $form_data, []);

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
            $result = SUPER_Spam_Detector::check_spam($this->form_id, ['super_hp' => ''], $context);
            $this->assertTrue($result['spam'], "IP $ip should be in blacklisted range");
        }

        // IPs outside range
        $outside_range = ['192.167.0.1', '192.169.0.1', '10.0.0.1'];

        foreach ($outside_range as $ip) {
            $context = ['user_ip' => $ip];
            $result = SUPER_Spam_Detector::check_spam($this->form_id, ['super_hp' => ''], $context);
            $this->assertFalse($result['spam'], "IP $ip should not be in blacklisted range");
        }
    }
}