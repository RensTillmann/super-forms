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

Schedule automatic cleanup:

```php
// In main plugin file
add_action('init', function() {
    if (!wp_next_scheduled('super_triggers_cleanup_logs')) {
        wp_schedule_event(time(), 'daily', 'super_triggers_cleanup_logs');
    }
});

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