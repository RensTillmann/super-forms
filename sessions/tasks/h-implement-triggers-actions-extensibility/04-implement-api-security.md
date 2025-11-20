---
name: 04-implement-api-security
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
parent: h-implement-triggers-actions-extensibility
---

# Implement API and Security Layer

## Problem/Goal
Build secure API credentials management system, REST API endpoints for external integrations, webhook support, and comprehensive security measures to protect sensitive data and prevent unauthorized access.

## Success Criteria
- [ ] Secure storage of API credentials with encryption
- [ ] REST API endpoints for trigger management
- [ ] Webhook endpoint for receiving external events
- [ ] OAuth 2.0 support for third-party services
- [ ] Rate limiting and abuse prevention
- [ ] Permission system for trigger management
- [ ] Audit trail for security events
- [ ] GDPR-compliant data handling

## Implementation Steps

### Step 1: API Credentials Manager

**File:** `/src/includes/class-trigger-credentials.php` (new file)

Create secure credentials storage system:

```php
class SUPER_Trigger_Credentials {
    private static $instance = null;
    private $encryption_key;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
    }

    private function get_encryption_key() {
        // Use AUTH_KEY if available, otherwise generate and store
        if (defined('AUTH_KEY') && AUTH_KEY) {
            return substr(hash('sha256', AUTH_KEY . 'super_forms_triggers'), 0, 32);
        }

        $key = get_option('super_forms_encryption_key');
        if (!$key) {
            $key = wp_generate_password(32, true, true);
            update_option('super_forms_encryption_key', $key);
        }
        return $key;
    }

    public function store_credential($service, $key, $value, $user_id = null) {
        global $wpdb;

        // Encrypt the value
        $encrypted = $this->encrypt($value);

        // Store in database
        $data = array(
            'service' => $service,
            'credential_key' => $key,
            'credential_value' => $encrypted,
            'user_id' => $user_id ?: get_current_user_id(),
            'created_at' => current_time('mysql')
        );

        $table = $wpdb->prefix . 'super_api_credentials';

        // Check if exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE service = %s AND credential_key = %s AND user_id = %d",
            $service, $key, $data['user_id']
        ));

        if ($existing) {
            $wpdb->update($table, $data, array('id' => $existing->id));
        } else {
            $wpdb->insert($table, $data);
        }

        // Clear cache
        wp_cache_delete('super_credentials_' . $service . '_' . $data['user_id'], 'super_forms');

        return true;
    }

    public function get_credential($service, $key, $user_id = null) {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();
        $cache_key = 'super_credentials_' . $service . '_' . $user_id;

        // Check cache
        $cached = wp_cache_get($cache_key, 'super_forms');
        if ($cached && isset($cached[$key])) {
            return $this->decrypt($cached[$key]);
        }

        // Query database
        $table = $wpdb->prefix . 'super_api_credentials';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT credential_value FROM $table WHERE service = %s AND credential_key = %s AND user_id = %d",
            $service, $key, $user_id
        ));

        if (!$result) {
            return null;
        }

        return $this->decrypt($result->credential_value);
    }

    private function encrypt($data) {
        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(16);

        $encrypted = openssl_encrypt(
            $data,
            $method,
            $this->encryption_key,
            0,
            $iv
        );

        return base64_encode($iv . $encrypted);
    }

    private function decrypt($data) {
        $method = 'AES-256-CBC';
        $data = base64_decode($data);

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt(
            $encrypted,
            $method,
            $this->encryption_key,
            0,
            $iv
        );
    }

    public function delete_credential($service, $key = null, $user_id = null) {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'super_api_credentials';

        if ($key) {
            $wpdb->delete($table, array(
                'service' => $service,
                'credential_key' => $key,
                'user_id' => $user_id
            ));
        } else {
            // Delete all credentials for service
            $wpdb->delete($table, array(
                'service' => $service,
                'user_id' => $user_id
            ));
        }

        // Clear cache
        wp_cache_delete('super_credentials_' . $service . '_' . $user_id, 'super_forms');
    }
}
```

### Step 2: REST API Endpoints

**File:** `/src/includes/class-trigger-rest-api.php` (new file)

Create REST API for trigger management:

```php
class SUPER_Trigger_REST_API {
    const NAMESPACE = 'super-forms/v1';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        // Triggers endpoints
        register_rest_route(self::NAMESPACE, '/triggers', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_triggers'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => $this->get_collection_params()
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_trigger'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => $this->get_trigger_params()
            )
        ));

        register_rest_route(self::NAMESPACE, '/triggers/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_trigger'),
                'permission_callback' => array($this, 'check_permission')
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_trigger'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => $this->get_trigger_params()
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_trigger'),
                'permission_callback' => array($this, 'check_permission')
            )
        ));

        // Webhook endpoint
        register_rest_route(self::NAMESPACE, '/webhooks/(?P<key>[a-zA-Z0-9]+)', array(
            'methods' => WP_REST_Server::ALLMETHODS,
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Public endpoint
        ));

        // Events endpoint
        register_rest_route(self::NAMESPACE, '/events', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_available_events'),
            'permission_callback' => array($this, 'check_permission')
        ));

        // Actions endpoint
        register_rest_route(self::NAMESPACE, '/actions', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_available_actions'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }

    public function check_permission($request) {
        // Check API key authentication
        $api_key = $request->get_header('X-API-Key');
        if ($api_key) {
            return $this->validate_api_key($api_key);
        }

        // Check WordPress authentication
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('Authentication required', 'super-forms'), array('status' => 401));
        }

        // Check capabilities
        $capability = 'manage_options'; // Or custom capability
        if (!current_user_can($capability)) {
            return new WP_Error('rest_forbidden', __('Insufficient permissions', 'super-forms'), array('status' => 403));
        }

        return true;
    }

    private function validate_api_key($key) {
        global $wpdb;

        // Check if key exists and is valid
        $table = $wpdb->prefix . 'super_api_keys';
        $api_key = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE api_key = %s AND status = 'active'",
            hash('sha256', $key)
        ));

        if (!$api_key) {
            return false;
        }

        // Check permissions
        $permissions = json_decode($api_key->permissions, true);
        if (!in_array('triggers', $permissions)) {
            return false;
        }

        // Update last used
        $wpdb->update($table, array(
            'last_used' => current_time('mysql'),
            'usage_count' => $api_key->usage_count + 1
        ), array('id' => $api_key->id));

        // Set current user context
        wp_set_current_user($api_key->user_id);

        return true;
    }

    public function handle_webhook($request) {
        $webhook_key = $request->get_param('key');

        // Find trigger with this webhook key
        global $wpdb;
        $trigger = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}super_triggers WHERE webhook_key = %s AND enabled = 1",
            $webhook_key
        ));

        if (!$trigger) {
            return new WP_Error('invalid_webhook', 'Invalid webhook key', array('status' => 404));
        }

        // Verify webhook signature if configured
        if ($trigger->webhook_secret) {
            $signature = $request->get_header('X-Webhook-Signature');
            $expected = hash_hmac('sha256', $request->get_body(), $trigger->webhook_secret);

            if (!hash_equals($expected, $signature)) {
                return new WP_Error('invalid_signature', 'Invalid webhook signature', array('status' => 401));
            }
        }

        // Process webhook data
        $data = array(
            'webhook_data' => $request->get_json_params(),
            'headers' => $request->get_headers(),
            'method' => $request->get_method(),
            'source_ip' => $_SERVER['REMOTE_ADDR']
        );

        // Execute trigger
        $executor = new SUPER_Trigger_Executor();
        $result = $executor->execute_trigger($trigger->id, $data);

        return rest_ensure_response(array(
            'success' => $result['success'],
            'message' => $result['success'] ? 'Webhook processed' : 'Processing failed',
            'trigger_id' => $trigger->id
        ));
    }
}
```

### Step 3: OAuth 2.0 Integration

**File:** `/src/includes/class-trigger-oauth.php` (new file)

Implement OAuth 2.0 flow for third-party services:

```php
class SUPER_Trigger_OAuth {
    private $providers = array();

    public function __construct() {
        add_action('init', array($this, 'handle_oauth_callback'));
        add_action('admin_init', array($this, 'register_providers'));
    }

    public function register_provider($name, $config) {
        $this->providers[$name] = array_merge(array(
            'client_id' => '',
            'client_secret' => '',
            'authorize_url' => '',
            'token_url' => '',
            'scope' => '',
            'redirect_uri' => admin_url('admin.php?page=super-oauth-callback')
        ), $config);
    }

    public function initiate_oauth($provider, $state = '') {
        if (!isset($this->providers[$provider])) {
            wp_die('Invalid OAuth provider');
        }

        $config = $this->providers[$provider];

        // Store state in transient
        $state_key = wp_generate_password(32, false);
        set_transient('super_oauth_state_' . $state_key, array(
            'provider' => $provider,
            'user_id' => get_current_user_id(),
            'state' => $state
        ), 600); // 10 minutes

        // Build authorization URL
        $params = array(
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $config['scope'],
            'state' => $state_key,
            'access_type' => 'offline', // Request refresh token
            'prompt' => 'consent'
        );

        $auth_url = $config['authorize_url'] . '?' . http_build_query($params);

        // Redirect to provider
        wp_redirect($auth_url);
        exit;
    }

    public function handle_oauth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'super-oauth-callback') {
            return;
        }

        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            wp_die('Invalid OAuth callback');
        }

        // Retrieve state
        $state_data = get_transient('super_oauth_state_' . $_GET['state']);
        if (!$state_data) {
            wp_die('OAuth state expired or invalid');
        }

        // Delete transient
        delete_transient('super_oauth_state_' . $_GET['state']);

        // Exchange code for token
        $provider = $state_data['provider'];
        $config = $this->providers[$provider];

        $response = wp_remote_post($config['token_url'], array(
            'body' => array(
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $_GET['code'],
                'redirect_uri' => $config['redirect_uri'],
                'grant_type' => 'authorization_code'
            )
        ));

        if (is_wp_error($response)) {
            wp_die('Failed to exchange OAuth code: ' . $response->get_error_message());
        }

        $tokens = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($tokens['access_token'])) {
            wp_die('Invalid token response');
        }

        // Store tokens securely
        $credentials = SUPER_Trigger_Credentials::instance();
        $credentials->store_credential($provider, 'access_token', $tokens['access_token'], $state_data['user_id']);

        if (isset($tokens['refresh_token'])) {
            $credentials->store_credential($provider, 'refresh_token', $tokens['refresh_token'], $state_data['user_id']);
        }

        // Store expiry time
        if (isset($tokens['expires_in'])) {
            $expiry = time() + $tokens['expires_in'];
            $credentials->store_credential($provider, 'token_expiry', $expiry, $state_data['user_id']);
        }

        // Redirect back to settings
        wp_redirect(admin_url('admin.php?page=super-triggers&oauth=success&provider=' . $provider));
        exit;
    }

    public function refresh_token($provider, $user_id = null) {
        $credentials = SUPER_Trigger_Credentials::instance();
        $refresh_token = $credentials->get_credential($provider, 'refresh_token', $user_id);

        if (!$refresh_token) {
            return false;
        }

        $config = $this->providers[$provider];

        $response = wp_remote_post($config['token_url'], array(
            'body' => array(
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token'
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $tokens = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($tokens['access_token'])) {
            $credentials->store_credential($provider, 'access_token', $tokens['access_token'], $user_id);

            if (isset($tokens['expires_in'])) {
                $expiry = time() + $tokens['expires_in'];
                $credentials->store_credential($provider, 'token_expiry', $expiry, $user_id);
            }

            return $tokens['access_token'];
        }

        return false;
    }
}
```

### Step 4: Rate Limiting & Security

**File:** `/src/includes/class-trigger-security.php` (new file)

Implement security measures:

```php
class SUPER_Trigger_Security {
    const RATE_LIMIT_WINDOW = 60; // seconds
    const RATE_LIMIT_MAX_REQUESTS = 60;

    public function __construct() {
        add_filter('rest_pre_dispatch', array($this, 'check_rate_limit'), 10, 3);
        add_action('super_trigger_before_execute', array($this, 'validate_execution'));
        add_action('super_trigger_security_event', array($this, 'log_security_event'), 10, 2);
    }

    public function check_rate_limit($result, $server, $request) {
        // Only check for our API endpoints
        if (strpos($request->get_route(), '/super-forms/v1/') !== 0) {
            return $result;
        }

        $identifier = $this->get_rate_limit_identifier($request);
        $key = 'super_rate_limit_' . md5($identifier);

        $attempts = get_transient($key) ?: 0;

        if ($attempts >= self::RATE_LIMIT_MAX_REQUESTS) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Rate limit exceeded. Please try again later.', 'super-forms'),
                array('status' => 429)
            );
        }

        set_transient($key, $attempts + 1, self::RATE_LIMIT_WINDOW);

        return $result;
    }

    private function get_rate_limit_identifier($request) {
        // Use API key if present
        $api_key = $request->get_header('X-API-Key');
        if ($api_key) {
            return 'api_' . substr($api_key, 0, 10);
        }

        // Use user ID if logged in
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }

        // Use IP address for anonymous requests
        return 'ip_' . $_SERVER['REMOTE_ADDR'];
    }

    public function validate_execution($trigger_data) {
        // Check for suspicious patterns
        $suspicious = false;
        $reason = '';

        // Check for rapid-fire executions
        $recent_key = 'super_recent_exec_' . $trigger_data['trigger_id'];
        $recent = get_transient($recent_key);

        if ($recent && (time() - $recent) < 1) {
            $suspicious = true;
            $reason = 'Rapid-fire execution detected';
        }

        set_transient($recent_key, time(), 60);

        // Check for unusual data patterns
        if ($this->contains_suspicious_data($trigger_data['data'])) {
            $suspicious = true;
            $reason = 'Suspicious data pattern detected';
        }

        if ($suspicious) {
            do_action('super_trigger_security_event', 'suspicious_execution', array(
                'trigger_id' => $trigger_data['trigger_id'],
                'reason' => $reason,
                'data' => $trigger_data
            ));

            // Optionally block execution
            if (get_option('super_triggers_strict_security', false)) {
                throw new Exception('Security check failed: ' . $reason);
            }
        }
    }

    private function contains_suspicious_data($data) {
        $patterns = array(
            '/\<script/i',           // XSS attempts
            '/javascript\:/i',        // JavaScript protocol
            '/on\w+\s*=/i',          // Event handlers
            '/union\s+select/i',      // SQL injection
            '/\.\.\//',              // Path traversal
            '/\0/',                  // Null bytes
            '/%00/',                 // URL encoded null
        );

        $json = json_encode($data);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $json)) {
                return true;
            }
        }

        return false;
    }

    public function log_security_event($event_type, $data) {
        global $wpdb;

        $log_data = array(
            'event_type' => $event_type,
            'event_data' => json_encode($data),
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => current_time('mysql')
        );

        $wpdb->insert($wpdb->prefix . 'super_security_log', $log_data);

        // Send alert for critical events
        if (in_array($event_type, array('unauthorized_access', 'api_key_breach', 'data_breach'))) {
            $this->send_security_alert($event_type, $data);
        }
    }

    private function send_security_alert($event_type, $data) {
        $admin_email = get_option('admin_email');
        $subject = sprintf('[Super Forms Security Alert] %s detected', $event_type);

        $message = "A security event has been detected:\n\n";
        $message .= "Event Type: " . $event_type . "\n";
        $message .= "Time: " . current_time('mysql') . "\n";
        $message .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $message .= "\nEvent Data:\n" . print_r($data, true);

        wp_mail($admin_email, $subject, $message);
    }
}
```

### Step 5: Permission System

Implement granular permissions:

```php
class SUPER_Trigger_Permissions {
    const CAP_MANAGE_TRIGGERS = 'super_manage_triggers';
    const CAP_EXECUTE_TRIGGERS = 'super_execute_triggers';
    const CAP_VIEW_LOGS = 'super_view_trigger_logs';
    const CAP_MANAGE_CREDENTIALS = 'super_manage_api_credentials';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'add_capabilities'));
        add_filter('user_has_cap', array(__CLASS__, 'filter_capabilities'), 10, 4);
    }

    public static function add_capabilities() {
        $admin = get_role('administrator');

        if ($admin) {
            $admin->add_cap(self::CAP_MANAGE_TRIGGERS);
            $admin->add_cap(self::CAP_EXECUTE_TRIGGERS);
            $admin->add_cap(self::CAP_VIEW_LOGS);
            $admin->add_cap(self::CAP_MANAGE_CREDENTIALS);
        }
    }

    public static function can_manage_trigger($trigger_id, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        // Admins can manage all
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check if user owns the trigger
        global $wpdb;
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM {$wpdb->prefix}super_triggers WHERE id = %d",
            $trigger_id
        ));

        return $owner == $user_id;
    }
}
```

### Step 6: API Key Management

Create UI for managing API keys:

```php
// Admin page for API key management
class SUPER_Trigger_API_Keys_Page {
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('API Keys', 'super-forms'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('super_create_api_key'); ?>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Key Name', 'super-forms'); ?></th>
                        <td><input type="text" name="key_name" required></td>
                    </tr>
                    <tr>
                        <th><?php _e('Permissions', 'super-forms'); ?></th>
                        <td>
                            <label><input type="checkbox" name="permissions[]" value="triggers"> <?php _e('Manage Triggers', 'super-forms'); ?></label><br>
                            <label><input type="checkbox" name="permissions[]" value="execute"> <?php _e('Execute Triggers', 'super-forms'); ?></label><br>
                            <label><input type="checkbox" name="permissions[]" value="logs"> <?php _e('View Logs', 'super-forms'); ?></label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="create_key" class="button-primary" value="<?php _e('Generate API Key', 'super-forms'); ?>">
                </p>
            </form>

            <?php if (isset($_POST['create_key'])) {
                $this->create_api_key();
            } ?>

            <h2><?php _e('Existing API Keys', 'super-forms'); ?></h2>
            <?php $this->list_api_keys(); ?>
        </div>
        <?php
    }

    private function create_api_key() {
        // Generate secure random key
        $raw_key = wp_generate_password(32, false);
        $hashed_key = hash('sha256', $raw_key);

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'super_api_keys', array(
            'api_key' => $hashed_key,
            'key_name' => sanitize_text_field($_POST['key_name']),
            'permissions' => json_encode($_POST['permissions']),
            'user_id' => get_current_user_id(),
            'status' => 'active',
            'created_at' => current_time('mysql')
        ));

        // Show key once (won't be shown again)
        echo '<div class="notice notice-success"><p>';
        echo __('API Key created successfully. Copy this key now (it won\'t be shown again):', 'super-forms');
        echo '<br><code>' . esc_html($raw_key) . '</code>';
        echo '</p></div>';
    }
}
```

## Context Manifest
<!-- To be added by context-gathering agent -->

## User Notes
- Use WordPress salts for encryption when available
- Store sensitive data encrypted at rest
- Implement proper CSRF protection for all forms
- Follow OWASP security guidelines
- Consider GDPR compliance for data storage
- Rate limiting should be configurable per endpoint
- OAuth tokens should auto-refresh before expiry

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Subtask created with comprehensive API and security implementation