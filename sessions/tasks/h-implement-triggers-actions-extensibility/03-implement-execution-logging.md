---
name: 03-implement-execution-logging
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
parent: h-implement-triggers-actions-extensibility
---

# Implement Execution and Logging System

## Problem/Goal
Build comprehensive execution engine with detailed logging, error handling, debugging capabilities, and performance monitoring. This provides visibility into trigger execution and helps debug issues in production.

## Success Criteria
- [ ] Robust execution engine with proper error handling
- [ ] Comprehensive logging to database with retention policies
- [ ] Debug mode with verbose output for development
- [ ] Performance metrics tracking (execution time, memory usage)
- [ ] Log viewer in admin interface with filtering
- [ ] Export logs for analysis
- [ ] Automatic log cleanup based on retention settings
- [ ] Integration with browser developer console for real-time debugging

## Implementation Steps

### Step 1: Execution Engine

**File:** `/src/includes/class-trigger-executor.php` (new file)

Create the main execution engine:

```php
class SUPER_Trigger_Executor {
    private $current_trigger;
    private $execution_context;
    private $start_time;
    private $memory_start;

    public function execute_trigger($trigger_id, $event_data) {
        $this->start_execution($trigger_id);

        try {
            // Load trigger configuration
            $trigger = SUPER_Trigger_DAL::get_trigger($trigger_id);
            if (!$trigger || !$trigger['enabled']) {
                throw new Exception('Trigger not found or disabled');
            }

            // Build execution context
            $this->execution_context = $this->build_context($trigger, $event_data);

            // Get actions for this trigger
            $actions = SUPER_Trigger_DAL::get_trigger_actions($trigger_id);

            // Execute actions in order
            $results = array();
            foreach ($actions as $action) {
                if (!$action['enabled']) continue;

                // Check conditions
                if (!$this->evaluate_conditions($action['conditions_data'], $event_data)) {
                    $this->log_action_skipped($action, 'Conditions not met');
                    continue;
                }

                // Execute action
                $result = $this->execute_action($action, $event_data);
                $results[] = $result;

                // Stop on critical failure if configured
                if (!$result['success'] && $action['stop_on_failure']) {
                    break;
                }
            }

            $this->complete_execution(true, $results);
            return array('success' => true, 'results' => $results);

        } catch (Exception $e) {
            $this->complete_execution(false, null, $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    private function execute_action($action_config, $event_data) {
        $start_time = microtime(true);

        try {
            // Get action instance from registry
            $registry = SUPER_Trigger_Registry::instance();
            $action = $registry->get_action($action_config['action_type']);

            if (!$action) {
                throw new Exception('Action type not found: ' . $action_config['action_type']);
            }

            // Parse settings
            $settings = json_decode($action_config['settings_data'], true);

            // Execute
            $result = $action->execute($event_data, $settings, $this->execution_context);

            // Log success
            $this->log_action_execution(
                $action_config,
                'success',
                $result['message'] ?? 'Completed',
                $result['data'] ?? array(),
                microtime(true) - $start_time
            );

            return $result;

        } catch (Exception $e) {
            // Log failure
            $this->log_action_execution(
                $action_config,
                'failed',
                $e->getMessage(),
                array('trace' => $e->getTraceAsString()),
                microtime(true) - $start_time
            );

            throw $e;
        }
    }

    private function evaluate_conditions($conditions_json, $data) {
        if (empty($conditions_json)) return true;

        $conditions = json_decode($conditions_json, true);
        if (!$conditions) return true;

        // Implement condition evaluation logic
        // Support AND/OR groups, nested conditions
        // Field comparisons, user role checks, etc.
    }
}
```

### Step 2: Logging System

**File:** `/src/includes/class-trigger-logger.php` (new file)

Create comprehensive logging system:

```php
class SUPER_Trigger_Logger {
    const LOG_LEVEL_ERROR = 1;
    const LOG_LEVEL_WARNING = 2;
    const LOG_LEVEL_INFO = 3;
    const LOG_LEVEL_DEBUG = 4;

    private static $instance = null;
    private $log_level;
    private $debug_mode;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->debug_mode = defined('SUPER_TRIGGERS_DEBUG') && SUPER_TRIGGERS_DEBUG;
        $this->log_level = $this->debug_mode ? self::LOG_LEVEL_DEBUG : self::LOG_LEVEL_INFO;
    }

    public function log_execution($trigger_name, $action_name, $status, $message, $data = array(), $execution_time = null) {
        global $wpdb;

        $log_data = array(
            'trigger_name' => $trigger_name,
            'action_name' => $action_name,
            'form_id' => $data['form_id'] ?? null,
            'entry_id' => $data['entry_id'] ?? null,
            'event' => $data['event'] ?? 'unknown',
            'executed_at' => current_time('mysql'),
            'execution_time_ms' => $execution_time ? round($execution_time * 1000) : null,
            'status' => $status,
            'error_message' => $status === 'failed' ? $message : null,
            'result_data' => json_encode($data),
            'user_id' => get_current_user_id(),
            'scheduled_action_id' => $data['scheduled_action_id'] ?? null
        );

        $wpdb->insert(
            $wpdb->prefix . 'super_trigger_execution_log',
            $log_data
        );

        // Also log to error_log if debug mode
        if ($this->debug_mode) {
            error_log(sprintf(
                '[Super Forms Trigger] %s - %s: %s (%s)',
                $trigger_name,
                $action_name,
                $message,
                $status
            ));
        }

        // Send to browser console if available
        if ($this->should_send_to_console()) {
            $this->send_to_browser_console($log_data);
        }
    }

    public function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'form_id' => null,
            'entry_id' => null,
            'event' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'executed_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        // Build query
        $where = array('1=1');
        $values = array();

        if ($args['form_id']) {
            $where[] = 'form_id = %d';
            $values[] = $args['form_id'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        // Add more conditions...

        $query = "SELECT * FROM {$wpdb->prefix}super_trigger_execution_log
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT %d OFFSET %d";

        $values[] = $args['limit'];
        $values[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($query, $values));
    }

    public function cleanup_old_logs($days = 30) {
        global $wpdb;

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}super_trigger_execution_log
             WHERE executed_at < %s",
            $cutoff_date
        ));
    }

    private function send_to_browser_console($data) {
        // Send via Server-Sent Events or WebSocket if available
        if (function_exists('wp_add_inline_script')) {
            wp_add_inline_script('super-forms-triggers',
                'console.log("[Super Forms Trigger]", ' . json_encode($data) . ');'
            );
        }
    }
}
```

### Step 3: Admin Log Viewer

**File:** `/src/includes/admin/class-trigger-logs-page.php` (new file)

Create admin interface for viewing logs:

```php
class SUPER_Trigger_Logs_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_menu_page() {
        add_submenu_page(
            'super_forms',
            __('Trigger Logs', 'super-forms'),
            __('Trigger Logs', 'super-forms'),
            'manage_options',
            'super-trigger-logs',
            array($this, 'render_page')
        );
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Trigger Execution Logs', 'super-forms'); ?></h1>

            <!-- Filters -->
            <div class="tablenav top">
                <form method="get" action="">
                    <input type="hidden" name="page" value="super-trigger-logs">

                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'super-forms'); ?></option>
                        <option value="success"><?php _e('Success', 'super-forms'); ?></option>
                        <option value="failed"><?php _e('Failed', 'super-forms'); ?></option>
                        <option value="skipped"><?php _e('Skipped', 'super-forms'); ?></option>
                    </select>

                    <input type="text" name="date_from" class="datepicker" placeholder="<?php _e('From Date', 'super-forms'); ?>">
                    <input type="text" name="date_to" class="datepicker" placeholder="<?php _e('To Date', 'super-forms'); ?>">

                    <input type="submit" class="button" value="<?php _e('Filter', 'super-forms'); ?>">
                    <a href="?page=super-trigger-logs&export=csv" class="button"><?php _e('Export CSV', 'super-forms'); ?></a>
                </form>
            </div>

            <!-- Logs Table -->
            <?php
            $list_table = new SUPER_Trigger_Logs_List_Table();
            $list_table->prepare_items();
            $list_table->display();
            ?>

            <!-- Log Details Modal -->
            <div id="super-log-details-modal" style="display:none;">
                <div class="log-details-content"></div>
            </div>
        </div>
        <?php
    }
}

// List Table Class
class SUPER_Trigger_Logs_List_Table extends WP_List_Table {
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'executed_at' => __('Date/Time', 'super-forms'),
            'trigger_name' => __('Trigger', 'super-forms'),
            'action_name' => __('Action', 'super-forms'),
            'status' => __('Status', 'super-forms'),
            'execution_time' => __('Duration', 'super-forms'),
            'form_id' => __('Form', 'super-forms'),
            'entry_id' => __('Entry', 'super-forms'),
            'details' => __('Details', 'super-forms')
        );
    }

    public function prepare_items() {
        $logger = SUPER_Trigger_Logger::instance();

        $per_page = 50;
        $current_page = $this->get_pagenum();

        $logs = $logger->get_logs(array(
            'limit' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ));

        $this->items = $logs;

        $total_items = $logger->get_logs_count($_GET);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}
```

### Step 4: Debug Mode & Developer Tools

**File:** `/src/includes/class-trigger-debugger.php` (new file)

Create debugging utilities:

```php
class SUPER_Trigger_Debugger {
    private static $debug_data = array();

    public static function debug($message, $data = null, $level = 'info') {
        if (!self::is_debug_mode()) return;

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? array();

        $debug_entry = array(
            'time' => microtime(true),
            'level' => $level,
            'message' => $message,
            'data' => $data,
            'file' => $caller['file'] ?? 'unknown',
            'line' => $caller['line'] ?? 0,
            'function' => $caller['function'] ?? 'unknown'
        );

        self::$debug_data[] = $debug_entry;

        // Log to error_log
        error_log(sprintf(
            '[SUPER_TRIGGERS_DEBUG] %s: %s (in %s:%d)',
            strtoupper($level),
            $message,
            basename($debug_entry['file']),
            $debug_entry['line']
        ));
    }

    public static function is_debug_mode() {
        return defined('SUPER_TRIGGERS_DEBUG') && SUPER_TRIGGERS_DEBUG;
    }

    public static function output_debug_panel() {
        if (!self::is_debug_mode()) return;
        if (!current_user_can('manage_options')) return;

        ?>
        <div id="super-triggers-debug-panel">
            <div class="debug-panel-header">
                <h3>Super Forms Triggers Debug</h3>
                <button class="toggle-debug">Toggle</button>
            </div>
            <div class="debug-panel-content">
                <?php foreach (self::$debug_data as $entry): ?>
                    <div class="debug-entry debug-<?php echo esc_attr($entry['level']); ?>">
                        <span class="time"><?php echo number_format($entry['time'] - $_SERVER['REQUEST_TIME_FLOAT'], 4); ?>s</span>
                        <span class="level"><?php echo esc_html($entry['level']); ?></span>
                        <span class="message"><?php echo esc_html($entry['message']); ?></span>
                        <?php if ($entry['data']): ?>
                            <pre class="debug-data"><?php echo esc_html(print_r($entry['data'], true)); ?></pre>
                        <?php endif; ?>
                        <span class="location"><?php echo esc_html(basename($entry['file']) . ':' . $entry['line']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
            #super-triggers-debug-panel {
                position: fixed;
                bottom: 0;
                right: 0;
                width: 500px;
                max-height: 400px;
                background: #fff;
                border: 1px solid #ccc;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                z-index: 99999;
            }
            .debug-panel-content {
                overflow-y: auto;
                max-height: 350px;
                padding: 10px;
            }
            .debug-entry {
                margin-bottom: 10px;
                padding: 5px;
                border-left: 3px solid #ccc;
            }
            .debug-entry.debug-error {
                border-color: #dc3232;
                background: #ffeaea;
            }
            .debug-entry.debug-warning {
                border-color: #ffb900;
                background: #fff8e5;
            }
        </style>
        <?php
    }
}

// Hook into footer
add_action('admin_footer', array('SUPER_Trigger_Debugger', 'output_debug_panel'));
```

### Step 5: Performance Monitoring

Add performance tracking:

```php
class SUPER_Trigger_Performance {
    private static $metrics = array();

    public static function start_timer($key) {
        self::$metrics[$key] = array(
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage()
        );
    }

    public static function end_timer($key) {
        if (!isset(self::$metrics[$key])) return null;

        $start = self::$metrics[$key];
        $end_time = microtime(true);
        $end_memory = memory_get_usage();

        return array(
            'duration' => $end_time - $start['start_time'],
            'memory' => $end_memory - $start['start_memory'],
            'peak_memory' => memory_get_peak_usage()
        );
    }

    public static function log_slow_execution($trigger_name, $duration, $threshold = 1.0) {
        if ($duration > $threshold) {
            error_log(sprintf(
                'Slow trigger execution: %s took %.2f seconds (threshold: %.2f)',
                $trigger_name,
                $duration,
                $threshold
            ));

            // Optionally send admin notification
            if ($duration > 5.0) {
                self::notify_admin_slow_execution($trigger_name, $duration);
            }
        }
    }
}
```

### Step 6: Log Retention & Cleanup

Schedule automatic cleanup using Action Scheduler:

```php
// In main plugin file - Use Action Scheduler for all background tasks
add_action('init', function() {
    // Check if recurring action is already scheduled
    $next_scheduled = as_next_scheduled_action(
        'super_triggers_cleanup_logs',
        array(),
        SUPER_Trigger_Scheduler::GROUP
    );

    if (!$next_scheduled) {
        // Schedule daily cleanup using Action Scheduler
        as_schedule_recurring_action(
            time() + DAY_IN_SECONDS,
            DAY_IN_SECONDS,
            'super_triggers_cleanup_logs',
            array(),
            SUPER_Trigger_Scheduler::GROUP
        );
    }
});

// Handle the cleanup action
add_action('super_triggers_cleanup_logs', function() {
    $logger = SUPER_Trigger_Logger::instance();

    // Get retention setting (default 30 days)
    $retention_days = get_option('super_triggers_log_retention', 30);

    // Cleanup old logs
    $deleted = $logger->cleanup_old_logs($retention_days);

    if ($deleted > 0) {
        SUPER_Trigger_Logger::instance()->log_execution(
            'System',
            'Log Cleanup',
            'success',
            sprintf('Deleted %d old log entries', $deleted)
        );
    }
});
```

### Step 7: Compliance & Audit Features

Implement comprehensive audit and compliance capabilities:

```php
class SUPER_Trigger_Compliance {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * GDPR Compliance - Right to Deletion
     * Delete all execution logs for a specific entry or user
     */
    public function delete_entry_logs($entry_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'super_trigger_logs';

        // Delete execution logs
        $deleted = $wpdb->delete(
            $table,
            array('entry_id' => $entry_id),
            array('%d')
        );

        // Delete from Data Access Layer
        SUPER_Data_Access::delete_entry_data($entry_id, array(
            '_trigger_execution_history',
            '_last_trigger_execution'
        ));

        // Log the deletion for audit trail
        $this->log_compliance_action(
            'gdpr_deletion',
            array(
                'entry_id' => $entry_id,
                'logs_deleted' => $deleted,
                'requested_by' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            )
        );

        return $deleted;
    }

    /**
     * GDPR Compliance - Data Export
     * Export all trigger/action data for a specific entry
     */
    public function export_entry_logs($entry_id, $format = 'json') {
        global $wpdb;
        $table = $wpdb->prefix . 'super_trigger_logs';

        // Get all logs for entry
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE entry_id = %d ORDER BY executed_at DESC",
            $entry_id
        ), ARRAY_A);

        // Scrub sensitive data if configured
        if (get_option('super_triggers_scrub_pii', false)) {
            $logs = $this->scrub_pii_from_logs($logs);
        }

        if ($format === 'csv') {
            return $this->format_as_csv($logs);
        }

        return json_encode($logs, JSON_PRETTY_PRINT);
    }

    /**
     * Data Minimization - Remove PII from logs
     */
    private function scrub_pii_from_logs($logs) {
        $pii_fields = get_option('super_triggers_pii_fields', array(
            'email', 'phone', 'ssn', 'credit_card', 'password'
        ));

        foreach ($logs as &$log) {
            if (!empty($log['request_data'])) {
                $data = json_decode($log['request_data'], true);
                if ($data) {
                    $data = $this->recursively_scrub_pii($data, $pii_fields);
                    $log['request_data'] = json_encode($data);
                }
            }
            if (!empty($log['response_data'])) {
                $data = json_decode($log['response_data'], true);
                if ($data) {
                    $data = $this->recursively_scrub_pii($data, $pii_fields);
                    $log['response_data'] = json_encode($data);
                }
            }
        }

        return $logs;
    }

    private function recursively_scrub_pii(&$data, $pii_fields) {
        foreach ($data as $key => &$value) {
            // Check if key contains PII field name
            foreach ($pii_fields as $field) {
                if (stripos($key, $field) !== false) {
                    $value = '[REDACTED]';
                    break;
                }
            }
            // Recurse for nested data
            if (is_array($value)) {
                $value = $this->recursively_scrub_pii($value, $pii_fields);
            }
        }
        return $data;
    }

    /**
     * Audit Trail - Track who accessed what and when
     */
    public function log_compliance_action($action_type, $details = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'super_compliance_audit';

        // Create audit table if not exists
        $this->create_audit_table();

        $wpdb->insert(
            $table,
            array(
                'action_type' => $action_type,
                'user_id' => get_current_user_id(),
                'details' => json_encode($details),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'performed_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Create compliance audit table
     */
    private function create_audit_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'super_compliance_audit';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action_type varchar(50) NOT NULL,
            user_id bigint(20),
            details longtext,
            ip_address varchar(45),
            user_agent text,
            performed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_action_type (action_type),
            KEY idx_performed_at (performed_at)
        ) $charset_collate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Credential Access Tracking
     * Log whenever API credentials are accessed
     */
    public function log_credential_access($action_id, $credential_type) {
        $this->log_compliance_action(
            'credential_access',
            array(
                'action_id' => $action_id,
                'credential_type' => $credential_type,
                'accessed_for' => 'action_execution'
            )
        );
    }

    /**
     * Configuration Change Tracking
     * Log changes to trigger/action configurations
     */
    public function log_configuration_change($trigger_id, $changes) {
        $this->log_compliance_action(
            'configuration_change',
            array(
                'trigger_id' => $trigger_id,
                'changes' => $changes,
                'previous_values' => $this->get_previous_config($trigger_id)
            )
        );
    }

    /**
     * Log Retention Policy
     * Automatically delete logs older than retention period
     */
    public function enforce_retention_policy() {
        global $wpdb;

        // Get retention settings
        $log_retention_days = get_option('super_triggers_log_retention', 30);
        $audit_retention_days = get_option('super_triggers_audit_retention', 90);

        // Delete old execution logs
        $logs_table = $wpdb->prefix . 'super_trigger_logs';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $logs_table WHERE executed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $log_retention_days
        ));

        // Delete old audit logs
        $audit_table = $wpdb->prefix . 'super_compliance_audit';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $audit_table WHERE performed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $audit_retention_days
        ));
    }

    /**
     * Encrypted Storage for Sensitive Logs
     */
    public function encrypt_sensitive_data($data, $entry_id) {
        // Use WordPress salts for encryption
        $key = wp_salt('auth') . $entry_id;
        $cipher = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(16);

        $encrypted = openssl_encrypt(
            json_encode($data),
            $cipher,
            $key,
            0,
            $iv
        );

        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt_sensitive_data($encrypted_data, $entry_id) {
        $key = wp_salt('auth') . $entry_id;
        $cipher = 'AES-256-CBC';

        list($encrypted, $iv) = explode('::', base64_decode($encrypted_data), 2);

        $decrypted = openssl_decrypt(
            $encrypted,
            $cipher,
            $key,
            0,
            $iv
        );

        return json_decode($decrypted, true);
    }

    /**
     * Export Audit Logs for External Analysis
     */
    public function export_audit_logs($start_date, $end_date, $format = 'json') {
        global $wpdb;
        $table = $wpdb->prefix . 'super_compliance_audit';

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE performed_at BETWEEN %s AND %s
             ORDER BY performed_at DESC",
            $start_date,
            $end_date
        ), ARRAY_A);

        if ($format === 'csv') {
            return $this->format_as_csv($logs);
        } elseif ($format === 'syslog') {
            return $this->format_as_syslog($logs);
        }

        return json_encode($logs, JSON_PRETTY_PRINT);
    }

    /**
     * Performance Monitoring for Large Log Tables
     */
    public function optimize_log_tables() {
        global $wpdb;

        // Add indexes if not exist
        $wpdb->query("ALTER TABLE {$wpdb->prefix}super_trigger_logs
                      ADD INDEX IF NOT EXISTS idx_entry_date (entry_id, executed_at)");

        // Analyze tables for query optimization
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}super_trigger_logs");
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}super_compliance_audit");

        // Get table sizes
        $sizes = $wpdb->get_results("
            SELECT
                TABLE_NAME as table_name,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME IN (
                '{$wpdb->prefix}super_trigger_logs',
                '{$wpdb->prefix}super_compliance_audit'
            )
        ", ARRAY_A);

        // Alert if tables are too large
        foreach ($sizes as $table) {
            if ($table['size_mb'] > 100) { // Alert if over 100MB
                $this->send_admin_alert(
                    'Large Log Table Warning',
                    sprintf('Table %s has grown to %s MB', $table['table_name'], $table['size_mb'])
                );
            }
        }

        return $sizes;
    }
}

// Initialize compliance features
add_action('init', array(SUPER_Trigger_Compliance::instance(), 'enforce_retention_policy'));

// Hook into GDPR data export
add_filter('wp_privacy_personal_data_exporters', function($exporters) {
    $exporters['super-forms-triggers'] = array(
        'exporter_friendly_name' => __('Super Forms Trigger Logs'),
        'callback' => array(SUPER_Trigger_Compliance::instance(), 'export_entry_logs'),
    );
    return $exporters;
});

// Hook into GDPR data erasure
add_filter('wp_privacy_personal_data_erasers', function($erasers) {
    $erasers['super-forms-triggers'] = array(
        'eraser_friendly_name' => __('Super Forms Trigger Logs'),
        'callback' => array(SUPER_Trigger_Compliance::instance(), 'delete_entry_logs'),
    );
    return $erasers;
});
```

### Compliance Settings UI

Add compliance settings to the admin interface:

```php
// Add compliance settings tab
add_filter('super_triggers_settings_tabs', function($tabs) {
    $tabs['compliance'] = __('Compliance & Privacy');
    return $tabs;
});

// Render compliance settings
add_action('super_triggers_settings_compliance', function() {
    $pii_fields = get_option('super_triggers_pii_fields', array());
    $scrub_pii = get_option('super_triggers_scrub_pii', false);
    $log_retention = get_option('super_triggers_log_retention', 30);
    $audit_retention = get_option('super_triggers_audit_retention', 90);
    ?>
    <h2><?php _e('Compliance & Privacy Settings', 'super-forms'); ?></h2>

    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Log Retention (days)', 'super-forms'); ?></th>
            <td>
                <input type="number" name="super_triggers_log_retention"
                       value="<?php echo esc_attr($log_retention); ?>" min="1" max="365" />
                <p class="description"><?php _e('Execution logs older than this will be automatically deleted.', 'super-forms'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('Audit Log Retention (days)', 'super-forms'); ?></th>
            <td>
                <input type="number" name="super_triggers_audit_retention"
                       value="<?php echo esc_attr($audit_retention); ?>" min="30" max="730" />
                <p class="description"><?php _e('Compliance audit logs older than this will be automatically deleted.', 'super-forms'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('Scrub PII from Logs', 'super-forms'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="super_triggers_scrub_pii" value="1"
                           <?php checked($scrub_pii, true); ?> />
                    <?php _e('Automatically redact personally identifiable information from logs', 'super-forms'); ?>
                </label>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('PII Field Names', 'super-forms'); ?></th>
            <td>
                <textarea name="super_triggers_pii_fields" rows="5" cols="50"><?php
                    echo esc_textarea(implode("\n", $pii_fields));
                ?></textarea>
                <p class="description"><?php _e('Field names to redact (one per line). Fields containing these strings will be masked.', 'super-forms'); ?></p>
            </td>
        </tr>
    </table>

    <h3><?php _e('Export Options', 'super-forms'); ?></h3>
    <p>
        <a href="<?php echo admin_url('admin-ajax.php?action=super_export_trigger_logs'); ?>"
           class="button"><?php _e('Export Execution Logs (Last 30 Days)', 'super-forms'); ?></a>
        <a href="<?php echo admin_url('admin-ajax.php?action=super_export_audit_logs'); ?>"
           class="button"><?php _e('Export Audit Logs (Last 90 Days)', 'super-forms'); ?></a>
    </p>
    <?php
});
```

## Context Manifest
<!-- To be added by context-gathering agent -->

## User Notes
- Debug mode should be opt-in via constant: `define('SUPER_TRIGGERS_DEBUG', true);`
- Consider log table size for high-traffic sites
- Implement log export functionality for analysis
- Add option to send critical errors to admin email
- Consider integration with external logging services (future enhancement)

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Subtask created with comprehensive logging and debugging system