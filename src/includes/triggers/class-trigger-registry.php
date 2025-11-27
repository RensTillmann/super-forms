<?php
/**
 * Trigger Registry - Central registration system for events and actions
 *
 * @package Super_Forms
 * @subpackage Triggers
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SUPER_Trigger_Registry {

    /**
     * Singleton instance
     *
     * @var SUPER_Trigger_Registry
     */
    private static $instance = null;

    /**
     * Registered events
     *
     * @var array
     */
    private $events = [];

    /**
     * Registered actions
     *
     * @var array
     */
    private $actions = [];

    /**
     * Whether initialize() has been called
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Private constructor for singleton
     */
    private function __construct() {
        // Initialize on instantiation or immediately if init already fired
        if ( did_action( 'init' ) ) {
            $this->initialize();
        } else {
            add_action( 'init', [ $this, 'initialize' ], 5 );
        }
    }

    /**
     * Get singleton instance
     *
     * @return SUPER_Trigger_Registry
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the registry
     */
    public function initialize() {
        // Prevent double initialization
        if ( $this->initialized ) {
            return;
        }
        $this->initialized = true;

        // Load built-in events and actions
        $this->load_builtin_events();
        $this->load_builtin_actions();

        // Allow third-party registration
        do_action('super_register_triggers', $this);

        // Apply filters for events and actions
        $this->events = apply_filters('super_trigger_events', $this->events);
        $this->actions = apply_filters('super_trigger_actions', $this->actions);
    }

    /**
     * Register an event
     *
     * @param string $event_id Unique event identifier
     * @param array $config Event configuration
     * @return bool Success
     * @throws Exception If event already registered
     */
    public function register_event($event_id, $config) {
        if (isset($this->events[$event_id])) {
            throw new Exception(sprintf(
                __('Event already registered: %s', 'super-forms'),
                $event_id
            ));
        }

        $defaults = [
            'label' => $event_id,
            'description' => '',
            'category' => 'general',
            'available_context' => [],
            'required_context' => [],
            'compatible_actions' => [],
            'addon' => null,
            'phase' => 1
        ];

        $this->events[$event_id] = wp_parse_args($config, $defaults);

        return true;
    }

    /**
     * Register an action
     *
     * @param string $action_id Unique action identifier
     * @param string $action_class Class name that extends SUPER_Trigger_Action_Base
     * @return bool Success
     */
    public function register_action($action_id, $action_class) {
        if (isset($this->actions[$action_id])) {
            throw new Exception(sprintf(
                __('Action already registered: %s', 'super-forms'),
                $action_id
            ));
        }

        // Note: Class existence not checked here to allow lazy loading
        // The class will be loaded/checked when get_action_instance() is called
        $this->actions[$action_id] = $action_class;

        return true;
    }

    /**
     * Get all registered events
     *
     * @return array
     */
    public function get_events() {
        return $this->events;
    }

    /**
     * Get all registered actions
     *
     * @return array
     */
    public function get_actions() {
        return $this->actions;
    }

    /**
     * Get specific event
     *
     * @param string $event_id
     * @return array|null
     */
    public function get_event($event_id) {
        return isset($this->events[$event_id]) ? $this->events[$event_id] : null;
    }

    /**
     * Get events by category
     *
     * @param string $category
     * @return array
     */
    public function get_events_by_category($category) {
        return array_filter($this->events, function($event) use ($category) {
            return isset($event['category']) && $event['category'] === $category;
        });
    }

    /**
     * Get compatible actions for an event
     *
     * @param string $event_id
     * @return array
     */
    public function get_compatible_actions($event_id) {
        if (!isset($this->events[$event_id])) {
            return [];
        }

        $event = $this->events[$event_id];

        // If no specific compatible actions defined, return all
        if (empty($event['compatible_actions'])) {
            return array_keys($this->actions);
        }

        return $event['compatible_actions'];
    }

    /**
     * Validate event context
     *
     * @param string $event_id
     * @param array $context
     * @return bool
     */
    public function validate_event_context($event_id, $context) {
        if (!isset($this->events[$event_id])) {
            return false;
        }

        $event = $this->events[$event_id];

        // Check required context fields
        if (!empty($event['required_context'])) {
            foreach ($event['required_context'] as $field) {
                if (!isset($context[$field])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Unregister an event
     *
     * @param string $event_id
     * @return bool
     */
    public function unregister_event($event_id) {
        if (isset($this->events[$event_id])) {
            unset($this->events[$event_id]);
            return true;
        }
        return false;
    }

    /**
     * Refresh events from filters
     */
    public function refresh_events() {
        $this->events = apply_filters('super_trigger_events', $this->events);
    }

    /**
     * Reset the registry for testing purposes
     *
     * @param bool $load_builtins Whether to load built-in events/actions after reset
     */
    public function reset( $load_builtins = false ) {
        $this->events = [];
        $this->actions = [];
        $this->initialized = false;

        if ( $load_builtins ) {
            $this->initialize();
        }
    }

    /**
     * Load built-in events
     */
    public function load_builtin_events() {
        // Phase 1: Core Events

        // Session Events
        $this->register_event('session.started', [
            'label' => __('Session Started', 'super-forms'),
            'description' => __('User focuses first field, session created', 'super-forms'),
            'category' => 'session_lifecycle',
            'available_context' => ['session_key', 'form_id', 'user_id', 'field_focused'],
            'compatible_actions' => ['log_message', 'webhook', 'set_variable'],
            'phase' => 1
        ]);

        $this->register_event('session.auto_saved', [
            'label' => __('Session Auto-Saved', 'super-forms'),
            'description' => __('Form data automatically saved on field blur/change', 'super-forms'),
            'category' => 'session_lifecycle',
            'available_context' => ['session_key', 'form_id', 'field_name', 'form_data'],
            'compatible_actions' => ['log_message'],
            'phase' => 1
        ]);

        $this->register_event('session.resumed', [
            'label' => __('Session Resumed', 'super-forms'),
            'description' => __('User returns to incomplete form', 'super-forms'),
            'category' => 'session_lifecycle',
            'available_context' => ['session_key', 'form_id', 'time_elapsed'],
            'compatible_actions' => ['log_message', 'webhook'],
            'phase' => 1
        ]);

        $this->register_event('session.completed', [
            'label' => __('Session Completed', 'super-forms'),
            'description' => __('Form submission successful, session marked complete', 'super-forms'),
            'category' => 'session_lifecycle',
            'available_context' => ['session_key', 'form_id', 'entry_id'],
            'compatible_actions' => ['log_message', 'webhook'],
            'phase' => 1
        ]);

        $this->register_event('session.abandoned', [
            'label' => __('Session Abandoned', 'super-forms'),
            'description' => __('Session marked abandoned after 30 minutes of inactivity', 'super-forms'),
            'category' => 'session_lifecycle',
            'available_context' => ['session_key', 'form_id', 'user_id', 'form_data', 'abandoned_after_minutes'],
            'compatible_actions' => ['log_message', 'send_email', 'webhook'],
            'phase' => 1
        ]);

        $this->register_event('session.expired', [
            'label' => __('Session Expired', 'super-forms'),
            'description' => __('Session deleted after expiration (24+ hours)', 'super-forms'),
            'category' => 'session_lifecycle',
            'available_context' => ['session_key', 'form_id', 'user_id', 'form_data', 'previous_status'],
            'compatible_actions' => ['log_message', 'webhook'],
            'phase' => 1
        ]);

        // Form Events
        $this->register_event('form.loaded', [
            'label' => __('Form Loaded', 'super-forms'),
            'description' => __('Form rendered on page', 'super-forms'),
            'category' => 'form_lifecycle',
            'available_context' => ['form_id', 'page_url'],
            'compatible_actions' => ['log_message', 'webhook', 'set_variable'],
            'phase' => 1
        ]);

        $this->register_event('form.before_submit', [
            'label' => __('Before Submit', 'super-forms'),
            'description' => __('Before validation and entry creation', 'super-forms'),
            'category' => 'form_lifecycle',
            'available_context' => ['form_id', 'form_data', 'uploaded_files'],
            'compatible_actions' => ['log_message'],
            'phase' => 1
        ]);

        $this->register_event('form.spam_detected', [
            'label' => __('Spam Detected', 'super-forms'),
            'description' => __('Submission flagged as spam', 'super-forms'),
            'category' => 'form_lifecycle',
            'available_context' => ['form_id', 'spam_method', 'spam_score'],
            'compatible_actions' => ['flow.abort_submission', 'log_message', 'send_email'],
            'phase' => 1
        ]);

        $this->register_event('form.duplicate_detected', [
            'label' => __('Duplicate Detected', 'super-forms'),
            'description' => __('Submission is a duplicate', 'super-forms'),
            'category' => 'form_lifecycle',
            'available_context' => ['form_id', 'duplicate_method', 'original_entry_id'],
            'compatible_actions' => ['flow.abort_submission', 'update_entry_field', 'log_message'],
            'phase' => 1
        ]);

        $this->register_event('form.validation_failed', [
            'label' => __('Validation Failed', 'super-forms'),
            'description' => __('Form validation errors occurred', 'super-forms'),
            'category' => 'form_lifecycle',
            'available_context' => ['form_id', 'validation_errors'],
            'compatible_actions' => ['log_message', 'send_email'],
            'phase' => 1
        ]);

        $this->register_event('form.submitted', [
            'label' => __('Form Submitted', 'super-forms'),
            'description' => __('Form successfully submitted', 'super-forms'),
            'category' => 'form_lifecycle',
            'available_context' => ['form_id', 'entry_id', 'session_id', 'form_data'],
            'required_context' => ['form_id'],
            'compatible_actions' => ['send_email', 'webhook', 'create_post', 'log_message'],
            'phase' => 1
        ]);

        // Entry Events
        $this->register_event('entry.created', [
            'label' => __('Entry Created', 'super-forms'),
            'description' => __('Contact entry created in database', 'super-forms'),
            'category' => 'entry_management',
            'available_context' => ['entry_id', 'form_id', 'form_data'],
            'required_context' => ['entry_id'],
            'compatible_actions' => ['send_email', 'update_entry_status', 'webhook'],
            'phase' => 1
        ]);

        $this->register_event('entry.saved', [
            'label' => __('Entry Saved', 'super-forms'),
            'description' => __('All entry data persisted', 'super-forms'),
            'category' => 'entry_management',
            'available_context' => ['entry_id', 'form_id', 'form_data'],
            'compatible_actions' => ['send_email', 'webhook', 'create_post'],
            'phase' => 1
        ]);

        $this->register_event('entry.updated', [
            'label' => __('Entry Updated', 'super-forms'),
            'description' => __('Existing entry data modified', 'super-forms'),
            'category' => 'entry_management',
            'available_context' => ['entry_id', 'form_id', 'form_data', 'previous_data'],
            'required_context' => ['entry_id'],
            'compatible_actions' => ['send_email', 'webhook', 'log_message', 'update_entry_status'],
            'phase' => 1
        ]);

        $this->register_event('entry.status_changed', [
            'label' => __('Entry Status Changed', 'super-forms'),
            'description' => __('Entry status was modified (e.g., pending to approved)', 'super-forms'),
            'category' => 'entry_management',
            'available_context' => ['entry_id', 'form_id', 'previous_status', 'new_status'],
            'required_context' => ['entry_id'],
            'compatible_actions' => ['send_email', 'webhook', 'log_message'],
            'phase' => 1
        ]);

        $this->register_event('entry.deleted', [
            'label' => __('Entry Deleted', 'super-forms'),
            'description' => __('Contact entry permanently deleted', 'super-forms'),
            'category' => 'entry_management',
            'available_context' => ['entry_id', 'form_id', 'deleted_by'],
            'required_context' => ['entry_id'],
            'compatible_actions' => ['webhook', 'log_message', 'send_email'],
            'phase' => 1
        ]);

        // File Events
        $this->register_event('file.uploaded', [
            'label' => __('File Uploaded', 'super-forms'),
            'description' => __('File upload completed successfully', 'super-forms'),
            'category' => 'file_management',
            'available_context' => ['attachment_id', 'form_id', 'file_url', 'file_name', 'file_type', 'file_size'],
            'compatible_actions' => ['webhook', 'log_message', 'send_email'],
            'phase' => 1
        ]);

        $this->register_event('file.upload_failed', [
            'label' => __('File Upload Failed', 'super-forms'),
            'description' => __('File upload encountered an error', 'super-forms'),
            'category' => 'file_management',
            'available_context' => ['form_id', 'file_name', 'error_message', 'error_code'],
            'compatible_actions' => ['log_message', 'send_email', 'webhook'],
            'phase' => 1
        ]);

        $this->register_event('file.deleted', [
            'label' => __('File Deleted', 'super-forms'),
            'description' => __('Uploaded file was removed', 'super-forms'),
            'category' => 'file_management',
            'available_context' => ['attachment_id', 'form_id', 'file_url', 'deleted_by'],
            'compatible_actions' => ['webhook', 'log_message'],
            'phase' => 1
        ]);

        // Phase 6: Payment Events (Stripe)
        $this->register_event('payment.stripe.checkout_completed', [
            'label' => __('Stripe Checkout Completed', 'super-forms'),
            'description' => __('Stripe Checkout session completed successfully', 'super-forms'),
            'category' => 'payment',
            'available_context' => ['session_id', 'payment_intent', 'amount', 'currency', 'customer_id', 'customer_email', 'entry_id', 'form_id', 'metadata'],
            'required_context' => ['session_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message', 'http_request'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        $this->register_event('payment.stripe.payment_succeeded', [
            'label' => __('Stripe Payment Succeeded', 'super-forms'),
            'description' => __('Payment intent succeeded', 'super-forms'),
            'category' => 'payment',
            'available_context' => ['payment_intent_id', 'amount', 'currency', 'customer_id', 'entry_id', 'form_id', 'metadata'],
            'required_context' => ['payment_intent_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message', 'http_request'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        $this->register_event('payment.stripe.payment_failed', [
            'label' => __('Stripe Payment Failed', 'super-forms'),
            'description' => __('Payment intent failed', 'super-forms'),
            'category' => 'payment',
            'available_context' => ['payment_intent_id', 'error_code', 'error_message', 'amount', 'entry_id', 'form_id'],
            'required_context' => ['payment_intent_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        $this->register_event('subscription.stripe.created', [
            'label' => __('Stripe Subscription Created', 'super-forms'),
            'description' => __('New subscription created', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'customer_id', 'plan_id', 'status', 'current_period_start', 'current_period_end', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message', 'http_request'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        $this->register_event('subscription.stripe.updated', [
            'label' => __('Stripe Subscription Updated', 'super-forms'),
            'description' => __('Subscription plan or status changed', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'customer_id', 'previous_plan', 'new_plan', 'status', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        $this->register_event('subscription.stripe.cancelled', [
            'label' => __('Stripe Subscription Cancelled', 'super-forms'),
            'description' => __('Subscription was cancelled', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'customer_id', 'cancel_reason', 'cancelled_at', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        $this->register_event('subscription.stripe.invoice_paid', [
            'label' => __('Stripe Invoice Paid', 'super-forms'),
            'description' => __('Subscription invoice payment succeeded', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['invoice_id', 'subscription_id', 'amount', 'currency', 'period_start', 'period_end', 'entry_id', 'form_id'],
            'required_context' => ['invoice_id'],
            'compatible_actions' => ['send_email', 'webhook', 'log_message', 'http_request'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        $this->register_event('subscription.stripe.invoice_failed', [
            'label' => __('Stripe Invoice Payment Failed', 'super-forms'),
            'description' => __('Subscription invoice payment failed', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['invoice_id', 'subscription_id', 'amount', 'attempt_count', 'next_retry', 'entry_id', 'form_id'],
            'required_context' => ['invoice_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'stripe',
            'phase' => 6
        ]);

        // Phase 6: Payment Events (PayPal)
        $this->register_event('payment.paypal.capture_completed', [
            'label' => __('PayPal Payment Captured', 'super-forms'),
            'description' => __('PayPal payment capture completed', 'super-forms'),
            'category' => 'payment',
            'available_context' => ['capture_id', 'amount', 'currency', 'payer_email', 'entry_id', 'form_id', 'custom_id'],
            'required_context' => ['capture_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message', 'http_request'],
            'addon' => 'paypal',
            'phase' => 6
        ]);

        $this->register_event('payment.paypal.capture_denied', [
            'label' => __('PayPal Payment Denied', 'super-forms'),
            'description' => __('PayPal payment capture was denied', 'super-forms'),
            'category' => 'payment',
            'available_context' => ['capture_id', 'reason', 'amount', 'entry_id', 'form_id'],
            'required_context' => ['capture_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'paypal',
            'phase' => 6
        ]);

        $this->register_event('payment.paypal.capture_refunded', [
            'label' => __('PayPal Payment Refunded', 'super-forms'),
            'description' => __('PayPal payment was refunded', 'super-forms'),
            'category' => 'payment',
            'available_context' => ['capture_id', 'refund_id', 'amount', 'currency', 'entry_id', 'form_id'],
            'required_context' => ['capture_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'paypal',
            'phase' => 6
        ]);

        $this->register_event('subscription.paypal.created', [
            'label' => __('PayPal Subscription Created', 'super-forms'),
            'description' => __('PayPal billing subscription created', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'plan_id', 'status', 'subscriber_email', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message', 'http_request'],
            'addon' => 'paypal',
            'phase' => 6
        ]);

        $this->register_event('subscription.paypal.activated', [
            'label' => __('PayPal Subscription Activated', 'super-forms'),
            'description' => __('PayPal subscription became active', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'plan_id', 'billing_start', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'paypal',
            'phase' => 6
        ]);

        $this->register_event('subscription.paypal.cancelled', [
            'label' => __('PayPal Subscription Cancelled', 'super-forms'),
            'description' => __('PayPal subscription was cancelled', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'reason', 'cancelled_at', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'paypal',
            'phase' => 6
        ]);

        $this->register_event('subscription.paypal.suspended', [
            'label' => __('PayPal Subscription Suspended', 'super-forms'),
            'description' => __('PayPal subscription was suspended', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'reason', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'paypal',
            'phase' => 6
        ]);

        $this->register_event('subscription.paypal.payment_failed', [
            'label' => __('PayPal Subscription Payment Failed', 'super-forms'),
            'description' => __('PayPal subscription payment failed', 'super-forms'),
            'category' => 'subscription',
            'available_context' => ['subscription_id', 'retry_date', 'failed_payments_count', 'entry_id', 'form_id'],
            'required_context' => ['subscription_id'],
            'compatible_actions' => ['send_email', 'webhook', 'update_entry_status', 'log_message'],
            'addon' => 'paypal',
            'phase' => 6
        ]);
    }

    /**
     * Load built-in actions
     */
    public function load_builtin_actions() {
        // Core Actions (Phase 1)
        $this->register_action('send_email', 'SUPER_Action_Send_Email');
        $this->register_action('update_entry_status', 'SUPER_Action_Update_Entry_Status');
        $this->register_action('update_entry_field', 'SUPER_Action_Update_Entry_Field');
        $this->register_action('delete_entry', 'SUPER_Action_Delete_Entry');
        $this->register_action('webhook', 'SUPER_Action_Webhook');
        $this->register_action('create_post', 'SUPER_Action_Create_Post');
        $this->register_action('log_message', 'SUPER_Action_Log_Message');
        $this->register_action('flow.abort_submission', 'SUPER_Action_Abort_Submission');

        // Flexibility Actions (Phase 1)
        $this->register_action('update_post_meta', 'SUPER_Action_Update_Post_Meta');
        $this->register_action('update_user_meta', 'SUPER_Action_Update_User_Meta');
        $this->register_action('run_hook', 'SUPER_Action_Run_Hook');
        $this->register_action('redirect_user', 'SUPER_Action_Redirect_User');
        $this->register_action('modify_user', 'SUPER_Action_Modify_User');
        $this->register_action('increment_counter', 'SUPER_Action_Increment_Counter');
        $this->register_action('set_variable', 'SUPER_Action_Set_Variable');
        $this->register_action('clear_cache', 'SUPER_Action_Clear_Cache');
        $this->register_action('conditional_action', 'SUPER_Action_Conditional');
        $this->register_action('stop_execution', 'SUPER_Action_Stop_Execution');
        $this->register_action('delay_execution', 'SUPER_Action_Delay_Execution');

        // Phase 5: Advanced Integration Actions
        $this->register_action('http_request', 'SUPER_Action_HTTP_Request');
        // Note: execute_php deferred for security review
    }

    /**
     * Get action instance
     *
     * @param string $action_id
     * @return SUPER_Trigger_Action_Base|null
     */
    public function get_action_instance($action_id) {
        if (!isset($this->actions[$action_id])) {
            return null;
        }

        $class = $this->actions[$action_id];

        if (!class_exists($class)) {
            // Try to autoload
            $file = str_replace('_', '-', strtolower($class));
            $file = str_replace('super-action-', '', $file);
            $path = SUPER_PLUGIN_DIR . '/includes/triggers/actions/class-action-' . $file . '.php';

            if (file_exists($path)) {
                require_once $path;
            }
        }

        if (class_exists($class)) {
            return new $class();
        }

        return null;
    }
}