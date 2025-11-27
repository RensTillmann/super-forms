<?php
/**
 * Tests for HTTP Request Templates
 *
 * @package Super_Forms
 * @subpackage Tests/Triggers
 */

class Test_HTTP_Request_Templates extends WP_UnitTestCase {

    /**
     * The templates instance
     *
     * @var SUPER_HTTP_Request_Templates
     */
    protected $templates;

    /**
     * Set up test fixtures
     */
    public function setUp(): void {
        parent::setUp();

        // Load the templates class
        require_once SUPER_PLUGIN_DIR . '/includes/triggers/class-http-request-templates.php';

        $this->templates = SUPER_HTTP_Request_Templates::instance();
    }

    /**
     * Test singleton pattern
     */
    public function test_singleton_instance() {
        $instance1 = SUPER_HTTP_Request_Templates::instance();
        $instance2 = SUPER_HTTP_Request_Templates::instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test default templates are loaded
     */
    public function test_default_templates_loaded() {
        $templates = $this->templates->get_all();

        $this->assertNotEmpty($templates);
        $this->assertIsArray($templates);
    }

    /**
     * Test specific templates exist
     */
    public function test_slack_webhook_template_exists() {
        $template = $this->templates->get('slack_webhook');

        $this->assertNotNull($template);
        $this->assertEquals('Slack Webhook', $template['name']);
        $this->assertEquals('messaging', $template['category']);
    }

    public function test_zapier_webhook_template_exists() {
        $template = $this->templates->get('zapier_webhook');

        $this->assertNotNull($template);
        $this->assertEquals('Zapier Webhook', $template['name']);
        $this->assertEquals('automation', $template['category']);
    }

    public function test_hubspot_contact_template_exists() {
        $template = $this->templates->get('hubspot_contact');

        $this->assertNotNull($template);
        $this->assertEquals('crm', $template['category']);
    }

    public function test_mailchimp_subscribe_template_exists() {
        $template = $this->templates->get('mailchimp_subscribe');

        $this->assertNotNull($template);
        $this->assertEquals('email_marketing', $template['category']);
    }

    /**
     * Test template structure
     */
    public function test_template_has_required_fields() {
        $template = $this->templates->get('slack_webhook');

        $this->assertArrayHasKey('name', $template);
        $this->assertArrayHasKey('description', $template);
        $this->assertArrayHasKey('category', $template);
        $this->assertArrayHasKey('icon', $template);
        $this->assertArrayHasKey('config', $template);
        $this->assertArrayHasKey('required_fields', $template);
        $this->assertArrayHasKey('instructions', $template);
    }

    /**
     * Test template config has valid URL
     */
    public function test_template_config_has_url() {
        $template = $this->templates->get('slack_webhook');
        $config = $template['config'];

        $this->assertArrayHasKey('url', $config);
        $this->assertNotEmpty($config['url']);
    }

    /**
     * Test template config has method
     */
    public function test_template_config_has_method() {
        $template = $this->templates->get('slack_webhook');
        $config = $template['config'];

        $this->assertArrayHasKey('method', $config);
        $this->assertContains($config['method'], ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);
    }

    /**
     * Test get_config method
     */
    public function test_get_config_returns_config() {
        $config = $this->templates->get_config('slack_webhook');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('url', $config);
        $this->assertArrayHasKey('method', $config);
    }

    public function test_get_config_returns_null_for_unknown() {
        $config = $this->templates->get_config('nonexistent_template');

        $this->assertNull($config);
    }

    /**
     * Test categories
     */
    public function test_get_categories() {
        $categories = $this->templates->get_categories();

        $this->assertIsArray($categories);
        $this->assertArrayHasKey('general', $categories);
        $this->assertArrayHasKey('messaging', $categories);
        $this->assertArrayHasKey('automation', $categories);
        $this->assertArrayHasKey('crm', $categories);
        $this->assertArrayHasKey('email_marketing', $categories);
        $this->assertArrayHasKey('database', $categories);
    }

    /**
     * Test get_by_category
     */
    public function test_get_by_category() {
        $messaging_templates = $this->templates->get_by_category('messaging');

        $this->assertNotEmpty($messaging_templates);

        foreach ($messaging_templates as $template) {
            $this->assertEquals('messaging', $template['category']);
        }
    }

    /**
     * Test registration of custom template
     */
    public function test_register_custom_template() {
        $this->templates->register('custom_test', [
            'name' => 'Custom Test Template',
            'description' => 'A test template',
            'category' => 'general',
            'config' => [
                'url' => 'https://test.example.com',
                'method' => 'POST'
            ]
        ]);

        $template = $this->templates->get('custom_test');

        $this->assertNotNull($template);
        $this->assertEquals('Custom Test Template', $template['name']);
    }

    /**
     * Test unregistration of template
     */
    public function test_unregister_template() {
        // First register
        $this->templates->register('temp_template', [
            'name' => 'Temporary Template',
            'config' => ['url' => 'https://temp.example.com', 'method' => 'POST']
        ]);

        $this->assertNotNull($this->templates->get('temp_template'));

        // Then unregister
        $this->templates->unregister('temp_template');

        $this->assertNull($this->templates->get('temp_template'));
    }

    /**
     * Test export_json
     */
    public function test_export_json() {
        $json = $this->templates->export_json('slack_webhook');

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertArrayHasKey('url', $decoded);
    }

    public function test_export_json_returns_false_for_unknown() {
        $json = $this->templates->export_json('nonexistent');

        $this->assertFalse($json);
    }

    /**
     * Test import_json
     */
    public function test_import_json_valid() {
        $json = json_encode([
            'url' => 'https://api.example.com/test',
            'method' => 'POST',
            'body_type' => 'json'
        ]);

        $config = $this->templates->import_json($json);

        $this->assertIsArray($config);
        $this->assertEquals('https://api.example.com/test', $config['url']);
    }

    public function test_import_json_invalid() {
        $result = $this->templates->import_json('not valid json{');

        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_json', $result->get_error_code());
    }

    public function test_import_json_missing_url() {
        $json = json_encode([
            'method' => 'POST'
        ]);

        $result = $this->templates->import_json($json);

        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('missing_url', $result->get_error_code());
    }

    /**
     * Test get_for_dropdown
     */
    public function test_get_for_dropdown() {
        $grouped = $this->templates->get_for_dropdown();

        $this->assertIsArray($grouped);
        $this->assertNotEmpty($grouped);

        // Should be grouped by category name (translated)
        foreach ($grouped as $category => $templates) {
            $this->assertIsString($category);
            $this->assertIsArray($templates);
        }
    }

    /**
     * Test filter hook
     */
    public function test_templates_filter_hook() {
        add_filter('super_http_request_templates', function($templates) {
            $templates['filter_test'] = [
                'name' => 'Filter Test',
                'config' => ['url' => 'https://filter.test', 'method' => 'GET']
            ];
            return $templates;
        });

        $all = $this->templates->get_all();

        $this->assertArrayHasKey('filter_test', $all);
    }
}
