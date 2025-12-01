<?php
/**
 * HTTP Request Templates
 *
 * Pre-built templates for common API integrations
 *
 * @package Super_Forms
 * @subpackage Triggers
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_HTTP_Request_Templates {

    /**
     * Singleton instance
     *
     * @var SUPER_HTTP_Request_Templates
     */
    private static $instance = null;

    /**
     * Registered templates
     *
     * @var array
     */
    private $templates = [];

    /**
     * Get singleton instance
     *
     * @return SUPER_HTTP_Request_Templates
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->register_default_templates();

        // Allow add-ons to register templates
        add_action('init', [$this, 'trigger_template_registration'], 20);
    }

    /**
     * Trigger template registration hook
     */
    public function trigger_template_registration() {
        do_action('super_http_request_register_templates', $this);
    }

    /**
     * Register default templates
     */
    private function register_default_templates() {
        // Slack Webhook
        $this->register('slack_webhook', [
            'name' => __('Slack Webhook', 'super-forms'),
            'description' => __('Send message to Slack channel via webhook', 'super-forms'),
            'category' => 'messaging',
            'icon' => 'dashicons-format-chat',
            'config' => [
                'request_name' => 'Slack Notification',
                'method' => 'POST',
                'url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
                'auth_type' => 'none',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'text' => 'New form submission from {name}',
                    'blocks' => [
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => "*New Submission*\n*Email:* {email}\n*Message:* {message}"
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200',
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Replace YOUR/WEBHOOK/URL with your Slack webhook URL from https://api.slack.com/apps', 'super-forms')
        ]);

        // Discord Webhook
        $this->register('discord_webhook', [
            'name' => __('Discord Webhook', 'super-forms'),
            'description' => __('Send message to Discord channel via webhook', 'super-forms'),
            'category' => 'messaging',
            'icon' => 'dashicons-format-chat',
            'config' => [
                'request_name' => 'Discord Notification',
                'method' => 'POST',
                'url' => 'https://discord.com/api/webhooks/YOUR/WEBHOOK/ID',
                'auth_type' => 'none',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'content' => 'New form submission!',
                    'embeds' => [
                        [
                            'title' => 'Form Submission',
                            'fields' => [
                                ['name' => 'Name', 'value' => '{name}', 'inline' => true],
                                ['name' => 'Email', 'value' => '{email}', 'inline' => true],
                                ['name' => 'Message', 'value' => '{message}']
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200,204',
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Replace YOUR/WEBHOOK/ID with your Discord webhook URL', 'super-forms')
        ]);

        // Microsoft Teams
        $this->register('teams_webhook', [
            'name' => __('Microsoft Teams', 'super-forms'),
            'description' => __('Send message to Microsoft Teams channel', 'super-forms'),
            'category' => 'messaging',
            'icon' => 'dashicons-format-chat',
            'config' => [
                'request_name' => 'Teams Notification',
                'method' => 'POST',
                'url' => 'https://outlook.office.com/webhook/YOUR/WEBHOOK/URL',
                'auth_type' => 'none',
                'body_type' => 'json',
                'json_body' => json_encode([
                    '@type' => 'MessageCard',
                    '@context' => 'http://schema.org/extensions',
                    'summary' => 'New Form Submission',
                    'themeColor' => '0076D7',
                    'title' => 'New form submission from {name}',
                    'sections' => [
                        [
                            'facts' => [
                                ['name' => 'Email', 'value' => '{email}'],
                                ['name' => 'Message', 'value' => '{message}']
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200',
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Replace YOUR/WEBHOOK/URL with your Teams incoming webhook URL', 'super-forms')
        ]);

        // Zapier Webhook
        $this->register('zapier_webhook', [
            'name' => __('Zapier Webhook', 'super-forms'),
            'description' => __('Trigger Zapier automation', 'super-forms'),
            'category' => 'automation',
            'icon' => 'dashicons-admin-generic',
            'config' => [
                'request_name' => 'Zapier Trigger',
                'method' => 'POST',
                'url' => 'https://hooks.zapier.com/hooks/catch/YOUR_HOOK_ID/',
                'auth_type' => 'none',
                'body_type' => 'auto',
                'success_status_codes' => '200',
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Replace YOUR_HOOK_ID with your Zapier webhook URL from a "Webhooks by Zapier" trigger', 'super-forms')
        ]);

        // Make (Integromat) Webhook
        $this->register('make_webhook', [
            'name' => __('Make (Integromat)', 'super-forms'),
            'description' => __('Trigger Make scenario', 'super-forms'),
            'category' => 'automation',
            'icon' => 'dashicons-admin-generic',
            'config' => [
                'request_name' => 'Make Trigger',
                'method' => 'POST',
                'url' => 'https://hook.eu1.make.com/YOUR_WEBHOOK_ID',
                'auth_type' => 'none',
                'body_type' => 'auto',
                'success_status_codes' => '200',
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Replace YOUR_WEBHOOK_ID with your Make webhook URL', 'super-forms')
        ]);

        // n8n Webhook
        $this->register('n8n_webhook', [
            'name' => __('n8n Webhook', 'super-forms'),
            'description' => __('Trigger n8n workflow', 'super-forms'),
            'category' => 'automation',
            'icon' => 'dashicons-admin-generic',
            'config' => [
                'request_name' => 'n8n Trigger',
                'method' => 'POST',
                'url' => 'https://your-n8n-instance.com/webhook/YOUR_WEBHOOK_PATH',
                'auth_type' => 'none',
                'body_type' => 'auto',
                'success_status_codes' => '200',
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Replace with your n8n webhook URL', 'super-forms')
        ]);

        // Mailchimp - Add Subscriber
        $this->register('mailchimp_subscribe', [
            'name' => __('Mailchimp Subscribe', 'super-forms'),
            'description' => __('Add subscriber to Mailchimp list', 'super-forms'),
            'category' => 'email_marketing',
            'icon' => 'dashicons-email',
            'config' => [
                'request_name' => 'Mailchimp Subscribe',
                'method' => 'POST',
                'url' => 'https://us1.api.mailchimp.com/3.0/lists/YOUR_LIST_ID/members',
                'auth_type' => 'basic',
                'basic_username' => 'anystring',
                'basic_password' => 'YOUR_API_KEY',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'email_address' => '{email}',
                    'status' => 'subscribed',
                    'merge_fields' => [
                        'FNAME' => '{first_name}',
                        'LNAME' => '{last_name}'
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200,201',
                'timeout' => 30
            ],
            'required_fields' => ['url', 'basic_password'],
            'instructions' => __('Replace us1 with your data center, YOUR_LIST_ID with your audience ID, and YOUR_API_KEY with your API key', 'super-forms')
        ]);

        // SendGrid - Send Email
        $this->register('sendgrid_email', [
            'name' => __('SendGrid Email', 'super-forms'),
            'description' => __('Send email via SendGrid API', 'super-forms'),
            'category' => 'email_marketing',
            'icon' => 'dashicons-email-alt',
            'config' => [
                'request_name' => 'SendGrid Email',
                'method' => 'POST',
                'url' => 'https://api.sendgrid.com/v3/mail/send',
                'auth_type' => 'bearer',
                'bearer_token' => 'YOUR_API_KEY',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'personalizations' => [
                        [
                            'to' => [['email' => '{email}']]
                        ]
                    ],
                    'from' => ['email' => 'noreply@example.com'],
                    'subject' => 'Thank you for your submission',
                    'content' => [
                        [
                            'type' => 'text/plain',
                            'value' => 'Hello {name}, thank you for contacting us!'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200,202',
                'timeout' => 30
            ],
            'required_fields' => ['bearer_token'],
            'instructions' => __('Replace YOUR_API_KEY with your SendGrid API key', 'super-forms')
        ]);

        // HubSpot - Create Contact
        $this->register('hubspot_contact', [
            'name' => __('HubSpot Contact', 'super-forms'),
            'description' => __('Create or update HubSpot contact', 'super-forms'),
            'category' => 'crm',
            'icon' => 'dashicons-id',
            'config' => [
                'request_name' => 'HubSpot Contact',
                'method' => 'POST',
                'url' => 'https://api.hubapi.com/crm/v3/objects/contacts',
                'auth_type' => 'bearer',
                'bearer_token' => 'YOUR_ACCESS_TOKEN',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'properties' => [
                        'email' => '{email}',
                        'firstname' => '{first_name}',
                        'lastname' => '{last_name}',
                        'phone' => '{phone}',
                        'company' => '{company}'
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200,201',
                'response_format' => 'json',
                'response_mapping' => "contact_id=id\ncontact_email=properties.email",
                'timeout' => 30
            ],
            'required_fields' => ['bearer_token'],
            'instructions' => __('Replace YOUR_ACCESS_TOKEN with your HubSpot private app access token', 'super-forms')
        ]);

        // Salesforce - Create Lead
        $this->register('salesforce_lead', [
            'name' => __('Salesforce Lead', 'super-forms'),
            'description' => __('Create lead in Salesforce', 'super-forms'),
            'category' => 'crm',
            'icon' => 'dashicons-cloud',
            'config' => [
                'request_name' => 'Salesforce Lead',
                'method' => 'POST',
                'url' => 'https://YOUR_INSTANCE.salesforce.com/services/data/v57.0/sobjects/Lead/',
                'auth_type' => 'bearer',
                'bearer_token' => 'YOUR_ACCESS_TOKEN',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'FirstName' => '{first_name}',
                    'LastName' => '{last_name}',
                    'Email' => '{email}',
                    'Phone' => '{phone}',
                    'Company' => '{company}',
                    'LeadSource' => 'Web Form'
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200,201',
                'response_format' => 'json',
                'response_mapping' => "lead_id=id\nsuccess=success",
                'timeout' => 30
            ],
            'required_fields' => ['url', 'bearer_token'],
            'instructions' => __('Replace YOUR_INSTANCE with your Salesforce instance and use OAuth to get access token', 'super-forms')
        ]);

        // Airtable - Create Record
        $this->register('airtable_record', [
            'name' => __('Airtable Record', 'super-forms'),
            'description' => __('Create record in Airtable base', 'super-forms'),
            'category' => 'database',
            'icon' => 'dashicons-database',
            'config' => [
                'request_name' => 'Airtable Record',
                'method' => 'POST',
                'url' => 'https://api.airtable.com/v0/YOUR_BASE_ID/YOUR_TABLE_NAME',
                'auth_type' => 'bearer',
                'bearer_token' => 'YOUR_API_KEY',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'fields' => [
                        'Name' => '{name}',
                        'Email' => '{email}',
                        'Message' => '{message}'
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200',
                'response_format' => 'json',
                'response_mapping' => 'record_id=id',
                'timeout' => 30
            ],
            'required_fields' => ['url', 'bearer_token'],
            'instructions' => __('Replace YOUR_BASE_ID, YOUR_TABLE_NAME, and YOUR_API_KEY with your Airtable details', 'super-forms')
        ]);

        // Notion - Create Page
        $this->register('notion_page', [
            'name' => __('Notion Page', 'super-forms'),
            'description' => __('Create page in Notion database', 'super-forms'),
            'category' => 'database',
            'icon' => 'dashicons-book',
            'config' => [
                'request_name' => 'Notion Page',
                'method' => 'POST',
                'url' => 'https://api.notion.com/v1/pages',
                'auth_type' => 'bearer',
                'bearer_token' => 'YOUR_INTEGRATION_TOKEN',
                'headers' => 'Notion-Version: 2022-06-28',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'parent' => ['database_id' => 'YOUR_DATABASE_ID'],
                    'properties' => [
                        'Name' => [
                            'title' => [
                                ['text' => ['content' => '{name}']]
                            ]
                        ],
                        'Email' => [
                            'email' => '{email}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200',
                'response_format' => 'json',
                'response_mapping' => 'page_id=id',
                'timeout' => 30
            ],
            'required_fields' => ['bearer_token'],
            'instructions' => __('Replace YOUR_INTEGRATION_TOKEN and YOUR_DATABASE_ID with your Notion integration details', 'super-forms')
        ]);

        // Google Sheets (via Apps Script)
        $this->register('google_sheets_apps_script', [
            'name' => __('Google Sheets (Apps Script)', 'super-forms'),
            'description' => __('Add row to Google Sheets via Apps Script web app', 'super-forms'),
            'category' => 'database',
            'icon' => 'dashicons-media-spreadsheet',
            'config' => [
                'request_name' => 'Google Sheets Row',
                'method' => 'POST',
                'url' => 'https://script.google.com/macros/s/YOUR_DEPLOYMENT_ID/exec',
                'auth_type' => 'none',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'name' => '{name}',
                    'email' => '{email}',
                    'message' => '{message}',
                    'timestamp' => '{current_time}'
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200,302',
                'follow_redirects' => true,
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Create a Google Apps Script web app and replace YOUR_DEPLOYMENT_ID with your deployment ID', 'super-forms')
        ]);

        // Telegram Bot
        $this->register('telegram_message', [
            'name' => __('Telegram Message', 'super-forms'),
            'description' => __('Send message via Telegram bot', 'super-forms'),
            'category' => 'messaging',
            'icon' => 'dashicons-format-chat',
            'config' => [
                'request_name' => 'Telegram Message',
                'method' => 'POST',
                'url' => 'https://api.telegram.org/botYOUR_BOT_TOKEN/sendMessage',
                'auth_type' => 'none',
                'body_type' => 'json',
                'json_body' => json_encode([
                    'chat_id' => 'YOUR_CHAT_ID',
                    'text' => "New form submission!\n\nName: {name}\nEmail: {email}\nMessage: {message}",
                    'parse_mode' => 'HTML'
                ], JSON_PRETTY_PRINT),
                'success_status_codes' => '200',
                'timeout' => 30
            ],
            'required_fields' => ['url'],
            'instructions' => __('Replace YOUR_BOT_TOKEN with your Telegram bot token and YOUR_CHAT_ID with the target chat ID', 'super-forms')
        ]);

        // Generic REST API with Bearer Token
        $this->register('rest_api_bearer', [
            'name' => __('REST API (Bearer Token)', 'super-forms'),
            'description' => __('Generic REST API call with Bearer authentication', 'super-forms'),
            'category' => 'general',
            'icon' => 'dashicons-rest-api',
            'config' => [
                'request_name' => 'API Request',
                'method' => 'POST',
                'url' => 'https://api.example.com/endpoint',
                'auth_type' => 'bearer',
                'bearer_token' => 'YOUR_TOKEN',
                'body_type' => 'json',
                'json_body' => "{\n  \n}",
                'success_status_codes' => '200,201',
                'timeout' => 30
            ],
            'required_fields' => ['url', 'bearer_token'],
            'instructions' => __('Configure the URL, token, and request body for your API', 'super-forms')
        ]);

        // Generic REST API with API Key
        $this->register('rest_api_key', [
            'name' => __('REST API (API Key)', 'super-forms'),
            'description' => __('Generic REST API call with API Key authentication', 'super-forms'),
            'category' => 'general',
            'icon' => 'dashicons-rest-api',
            'config' => [
                'request_name' => 'API Request',
                'method' => 'POST',
                'url' => 'https://api.example.com/endpoint',
                'auth_type' => 'api_key',
                'api_key_location' => 'header',
                'api_key_name' => 'X-API-Key',
                'api_key_value' => 'YOUR_API_KEY',
                'body_type' => 'json',
                'json_body' => "{\n  \n}",
                'success_status_codes' => '200,201',
                'timeout' => 30
            ],
            'required_fields' => ['url', 'api_key_value'],
            'instructions' => __('Configure the URL, API key, and request body for your API', 'super-forms')
        ]);
    }

    /**
     * Register a template
     *
     * @param string $id Template ID
     * @param array $args Template configuration
     */
    public function register($id, $args) {
        $defaults = [
            'name' => '',
            'description' => '',
            'category' => 'general',
            'icon' => 'dashicons-admin-generic',
            'config' => [],
            'required_fields' => [],
            'instructions' => ''
        ];

        $this->templates[$id] = wp_parse_args($args, $defaults);
    }

    /**
     * Unregister a template
     *
     * @param string $id Template ID
     */
    public function unregister($id) {
        unset($this->templates[$id]);
    }

    /**
     * Get all templates
     *
     * @return array
     */
    public function get_all() {
        return apply_filters('super_http_request_templates', $this->templates);
    }

    /**
     * Get templates by category
     *
     * @param string $category
     * @return array
     */
    public function get_by_category($category) {
        $templates = $this->get_all();
        return array_filter($templates, function($template) use ($category) {
            return $template['category'] === $category;
        });
    }

    /**
     * Get a single template
     *
     * @param string $id Template ID
     * @return array|null
     */
    public function get($id) {
        $templates = $this->get_all();
        return $templates[$id] ?? null;
    }

    /**
     * Get template config (ready to use)
     *
     * @param string $id Template ID
     * @return array|null
     */
    public function get_config($id) {
        $template = $this->get($id);
        return $template ? $template['config'] : null;
    }

    /**
     * Get available categories
     *
     * @return array
     */
    public function get_categories() {
        return [
            'general' => __('General', 'super-forms'),
            'messaging' => __('Messaging', 'super-forms'),
            'automation' => __('Automation', 'super-forms'),
            'email_marketing' => __('Email Marketing', 'super-forms'),
            'crm' => __('CRM', 'super-forms'),
            'database' => __('Database', 'super-forms'),
            'payment' => __('Payment', 'super-forms'),
            'analytics' => __('Analytics', 'super-forms')
        ];
    }

    /**
     * Export template config as JSON
     *
     * @param string $id Template ID
     * @return string|false JSON string or false
     */
    public function export_json($id) {
        $config = $this->get_config($id);
        if (!$config) {
            return false;
        }
        return json_encode($config, JSON_PRETTY_PRINT);
    }

    /**
     * Import template config from JSON
     *
     * @param string $json JSON config string
     * @return array|WP_Error Config array or error
     */
    public function import_json($json) {
        $config = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'invalid_json',
                __('Invalid JSON configuration', 'super-forms')
            );
        }

        // Validate required fields
        if (empty($config['url'])) {
            return new WP_Error(
                'missing_url',
                __('URL is required', 'super-forms')
            );
        }

        return $config;
    }

    /**
     * Get templates formatted for dropdown
     *
     * @return array
     */
    public function get_for_dropdown() {
        $templates = $this->get_all();
        $categories = $this->get_categories();
        $grouped = [];

        foreach ($templates as $id => $template) {
            $category = $template['category'];
            $category_label = $categories[$category] ?? $category;

            if (!isset($grouped[$category_label])) {
                $grouped[$category_label] = [];
            }

            $grouped[$category_label][$id] = $template['name'];
        }

        return $grouped;
    }
}
