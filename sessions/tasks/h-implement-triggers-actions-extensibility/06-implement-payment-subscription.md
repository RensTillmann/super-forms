---
name: 06-implement-payment-subscription
branch: feature/h-implement-triggers-actions-extensibility
status: pending
created: 2025-11-20
parent: h-implement-triggers-actions-extensibility
---

# Implement Payment and Subscription Events

## Problem/Goal
Integrate trigger system with existing payment add-ons (PayPal, Stripe, WooCommerce) to fire events on payment status changes, subscription lifecycle events, and refunds. This enables automated workflows based on payment activity.

## Success Criteria
- [ ] Payment events fire correctly for all payment gateways
- [ ] Subscription lifecycle events tracked (created, renewed, cancelled, expired)
- [ ] Refund and dispute events handled
- [ ] Payment data available in trigger context
- [ ] Backwards compatibility with existing payment add-ons
- [ ] Documentation for payment-triggered workflows
- [ ] Example automations for common payment scenarios

## Implementation Steps

### Step 1: Payment Event Registry

**File:** `/src/includes/class-payment-events.php` (new file)

Register all payment-related events:

```php
class SUPER_Payment_Events {

    public static function init() {
        add_action('super_trigger_register', array(__CLASS__, 'register_events'));
        self::hook_payment_gateways();
    }

    public static function register_events($registry) {
        // Payment Events
        $payment_events = array(
            // Transaction Events
            'payment.completed' => __('Payment Completed', 'super-forms'),
            'payment.failed' => __('Payment Failed', 'super-forms'),
            'payment.pending' => __('Payment Pending', 'super-forms'),
            'payment.processing' => __('Payment Processing', 'super-forms'),
            'payment.refunded' => __('Payment Refunded', 'super-forms'),
            'payment.partially_refunded' => __('Payment Partially Refunded', 'super-forms'),
            'payment.disputed' => __('Payment Disputed', 'super-forms'),
            'payment.dispute_resolved' => __('Payment Dispute Resolved', 'super-forms'),

            // Subscription Events
            'subscription.created' => __('Subscription Created', 'super-forms'),
            'subscription.activated' => __('Subscription Activated', 'super-forms'),
            'subscription.renewed' => __('Subscription Renewed', 'super-forms'),
            'subscription.updated' => __('Subscription Updated', 'super-forms'),
            'subscription.cancelled' => __('Subscription Cancelled', 'super-forms'),
            'subscription.expired' => __('Subscription Expired', 'super-forms'),
            'subscription.suspended' => __('Subscription Suspended', 'super-forms'),
            'subscription.reactivated' => __('Subscription Reactivated', 'super-forms'),
            'subscription.trial_started' => __('Subscription Trial Started', 'super-forms'),
            'subscription.trial_ending' => __('Subscription Trial Ending', 'super-forms'),
            'subscription.trial_ended' => __('Subscription Trial Ended', 'super-forms'),

            // Invoice Events
            'invoice.created' => __('Invoice Created', 'super-forms'),
            'invoice.sent' => __('Invoice Sent', 'super-forms'),
            'invoice.paid' => __('Invoice Paid', 'super-forms'),
            'invoice.payment_failed' => __('Invoice Payment Failed', 'super-forms'),
            'invoice.overdue' => __('Invoice Overdue', 'super-forms'),

            // Customer Events
            'customer.created' => __('Customer Created', 'super-forms'),
            'customer.updated' => __('Customer Updated', 'super-forms'),
            'customer.deleted' => __('Customer Deleted', 'super-forms'),
            'customer.card_updated' => __('Customer Card Updated', 'super-forms'),
            'customer.card_expiring' => __('Customer Card Expiring', 'super-forms'),
        );

        foreach ($payment_events as $id => $label) {
            $registry->register_event($id, $label, __('Payments', 'super-forms'));
        }
    }

    private static function hook_payment_gateways() {
        // PayPal hooks
        add_action('super_paypal_payment_completed', array(__CLASS__, 'handle_paypal_complete'), 10, 2);
        add_action('super_paypal_payment_failed', array(__CLASS__, 'handle_paypal_failed'), 10, 2);
        add_action('super_paypal_subscription_created', array(__CLASS__, 'handle_paypal_subscription'), 10, 2);

        // Stripe hooks
        add_action('super_stripe_payment_intent_succeeded', array(__CLASS__, 'handle_stripe_payment'), 10, 2);
        add_action('super_stripe_payment_intent_failed', array(__CLASS__, 'handle_stripe_failed'), 10, 2);
        add_action('super_stripe_subscription_updated', array(__CLASS__, 'handle_stripe_subscription'), 10, 2);
        add_action('super_stripe_webhook_received', array(__CLASS__, 'handle_stripe_webhook'), 10, 2);

        // WooCommerce hooks
        add_action('woocommerce_order_status_completed', array(__CLASS__, 'handle_woo_completed'), 10, 1);
        add_action('woocommerce_order_status_refunded', array(__CLASS__, 'handle_woo_refunded'), 10, 1);
        add_action('woocommerce_subscription_status_updated', array(__CLASS__, 'handle_woo_subscription'), 10, 3);
    }
}
```

### Step 2: PayPal Integration

**File:** `/src/includes/payment/class-paypal-triggers.php` (new file)

Handle PayPal IPN notifications and trigger events:

```php
class SUPER_PayPal_Triggers {

    public static function handle_ipn($ipn_data, $form_id, $entry_id) {
        // Parse IPN data
        $payment_status = $ipn_data['payment_status'] ?? '';
        $txn_type = $ipn_data['txn_type'] ?? '';

        // Build event data
        $event_data = array(
            'gateway' => 'paypal',
            'transaction_id' => $ipn_data['txn_id'] ?? '',
            'payment_status' => $payment_status,
            'amount' => $ipn_data['mc_gross'] ?? 0,
            'currency' => $ipn_data['mc_currency'] ?? 'USD',
            'payer_email' => $ipn_data['payer_email'] ?? '',
            'payer_name' => trim(($ipn_data['first_name'] ?? '') . ' ' . ($ipn_data['last_name'] ?? '')),
            'item_name' => $ipn_data['item_name'] ?? '',
            'item_number' => $ipn_data['item_number'] ?? '',
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'raw_data' => $ipn_data
        );

        // Trigger events based on payment status
        switch ($payment_status) {
            case 'Completed':
                self::trigger_payment_complete($event_data);
                break;

            case 'Failed':
            case 'Denied':
                self::trigger_payment_failed($event_data);
                break;

            case 'Pending':
                self::trigger_payment_pending($event_data);
                break;

            case 'Refunded':
                self::trigger_payment_refunded($event_data);
                break;

            case 'Reversed':
                self::trigger_payment_disputed($event_data);
                break;
        }

        // Handle subscription events
        if (in_array($txn_type, array('subscr_signup', 'subscr_payment', 'subscr_cancel', 'subscr_eot', 'subscr_failed'))) {
            self::handle_subscription_event($txn_type, $event_data);
        }
    }

    private static function trigger_payment_complete($data) {
        // Get triggers for this event
        $triggers = SUPER_Trigger_DAL::get_triggers_by_event('payment.completed', $data['form_id']);

        foreach ($triggers as $trigger) {
            $executor = new SUPER_Trigger_Executor();
            $executor->execute_trigger($trigger['id'], $data);
        }

        // Also trigger generic payment event
        do_action('super_payment_completed', $data);
    }

    private static function handle_subscription_event($txn_type, $data) {
        $subscription_data = array_merge($data, array(
            'subscription_id' => $data['raw_data']['subscr_id'] ?? '',
            'period' => $data['raw_data']['period3'] ?? '',
            'recurring_amount' => $data['raw_data']['mc_amount3'] ?? 0
        ));

        switch ($txn_type) {
            case 'subscr_signup':
                $event = 'subscription.created';
                break;

            case 'subscr_payment':
                $event = 'subscription.renewed';
                break;

            case 'subscr_cancel':
                $event = 'subscription.cancelled';
                break;

            case 'subscr_eot':
                $event = 'subscription.expired';
                break;

            case 'subscr_failed':
                $event = 'payment.failed';
                $subscription_data['failure_reason'] = 'Subscription payment failed';
                break;

            default:
                return;
        }

        $triggers = SUPER_Trigger_DAL::get_triggers_by_event($event, $data['form_id']);

        foreach ($triggers as $trigger) {
            $executor = new SUPER_Trigger_Executor();
            $executor->execute_trigger($trigger['id'], $subscription_data);
        }
    }
}
```

### Step 3: Stripe Integration

**File:** `/src/includes/payment/class-stripe-triggers.php` (new file)

Handle Stripe webhooks and trigger events:

```php
class SUPER_Stripe_Triggers {

    public static function handle_webhook($event, $form_id = null) {
        // Map Stripe event types to our events
        $event_map = array(
            'payment_intent.succeeded' => 'payment.completed',
            'payment_intent.payment_failed' => 'payment.failed',
            'payment_intent.processing' => 'payment.processing',
            'charge.refunded' => 'payment.refunded',
            'charge.dispute.created' => 'payment.disputed',
            'charge.dispute.closed' => 'payment.dispute_resolved',

            'customer.subscription.created' => 'subscription.created',
            'customer.subscription.updated' => 'subscription.updated',
            'customer.subscription.deleted' => 'subscription.cancelled',
            'customer.subscription.trial_will_end' => 'subscription.trial_ending',

            'invoice.created' => 'invoice.created',
            'invoice.sent' => 'invoice.sent',
            'invoice.paid' => 'invoice.paid',
            'invoice.payment_failed' => 'invoice.payment_failed',

            'customer.created' => 'customer.created',
            'customer.updated' => 'customer.updated',
            'customer.deleted' => 'customer.deleted',
        );

        $trigger_event = $event_map[$event->type] ?? null;

        if (!$trigger_event) {
            return; // Event type not mapped
        }

        // Extract relevant data from Stripe event
        $event_data = self::extract_stripe_data($event);

        // Try to find associated form and entry
        if (!$form_id) {
            $form_id = self::find_form_id($event);
        }
        $entry_id = self::find_entry_id($event);

        $event_data['form_id'] = $form_id;
        $event_data['entry_id'] = $entry_id;

        // Get triggers for this event
        $triggers = SUPER_Trigger_DAL::get_triggers_by_event($trigger_event, $form_id);

        foreach ($triggers as $trigger) {
            $executor = new SUPER_Trigger_Executor();
            $executor->execute_trigger($trigger['id'], $event_data);
        }

        // Log the event
        self::log_stripe_event($event, $trigger_event, $event_data);
    }

    private static function extract_stripe_data($event) {
        $data = array(
            'gateway' => 'stripe',
            'event_id' => $event->id,
            'event_type' => $event->type,
            'created' => $event->created,
            'livemode' => $event->livemode
        );

        // Extract data based on event type
        $object = $event->data->object;

        switch ($event->type) {
            case 'payment_intent.succeeded':
            case 'payment_intent.payment_failed':
                $data['transaction_id'] = $object->id;
                $data['amount'] = $object->amount / 100; // Convert from cents
                $data['currency'] = strtoupper($object->currency);
                $data['status'] = $object->status;
                $data['customer_id'] = $object->customer;
                $data['payment_method'] = $object->payment_method;
                $data['receipt_email'] = $object->receipt_email;
                break;

            case 'charge.refunded':
                $data['charge_id'] = $object->id;
                $data['amount'] = $object->amount / 100;
                $data['amount_refunded'] = $object->amount_refunded / 100;
                $data['currency'] = strtoupper($object->currency);
                $data['refund_reason'] = $object->refunds->data[0]->reason ?? '';
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $data['subscription_id'] = $object->id;
                $data['customer_id'] = $object->customer;
                $data['status'] = $object->status;
                $data['current_period_start'] = $object->current_period_start;
                $data['current_period_end'] = $object->current_period_end;
                $data['plan_id'] = $object->items->data[0]->price->id ?? '';
                $data['plan_amount'] = ($object->items->data[0]->price->unit_amount ?? 0) / 100;
                $data['plan_interval'] = $object->items->data[0]->price->recurring->interval ?? '';
                $data['trial_end'] = $object->trial_end;
                $data['cancel_at'] = $object->cancel_at;
                $data['cancelled_at'] = $object->canceled_at;
                break;

            case 'invoice.paid':
            case 'invoice.payment_failed':
                $data['invoice_id'] = $object->id;
                $data['customer_id'] = $object->customer;
                $data['subscription_id'] = $object->subscription;
                $data['amount'] = $object->amount_paid / 100;
                $data['currency'] = strtoupper($object->currency);
                $data['invoice_number'] = $object->number;
                $data['invoice_pdf'] = $object->invoice_pdf;
                break;
        }

        // Add metadata if present
        if (isset($object->metadata) && is_object($object->metadata)) {
            $data['metadata'] = (array) $object->metadata;

            // Try to extract form_id and entry_id from metadata
            $data['form_id'] = $object->metadata->form_id ?? null;
            $data['entry_id'] = $object->metadata->entry_id ?? null;
        }

        return $data;
    }

    private static function find_form_id($event) {
        // Try metadata first
        if (isset($event->data->object->metadata->form_id)) {
            return $event->data->object->metadata->form_id;
        }

        // Try to find from customer ID
        if (isset($event->data->object->customer)) {
            global $wpdb;
            $form_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta
                 WHERE meta_key = '_super_stripe_customer_id'
                 AND meta_value = %s
                 LIMIT 1",
                $event->data->object->customer
            ));

            if ($form_id) {
                return $form_id;
            }
        }

        return null;
    }

    private static function find_entry_id($event) {
        // Similar logic to find_form_id but for entry
        if (isset($event->data->object->metadata->entry_id)) {
            return $event->data->object->metadata->entry_id;
        }

        // Additional lookup logic...
        return null;
    }

    private static function log_stripe_event($event, $trigger_event, $data) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'super_stripe_events',
            array(
                'event_id' => $event->id,
                'event_type' => $event->type,
                'trigger_event' => $trigger_event,
                'form_id' => $data['form_id'],
                'entry_id' => $data['entry_id'],
                'data' => json_encode($data),
                'created_at' => current_time('mysql')
            )
        );
    }
}
```

### Step 4: WooCommerce Integration

**File:** `/src/includes/payment/class-woocommerce-triggers.php` (new file)

Bridge WooCommerce orders with trigger system:

```php
class SUPER_WooCommerce_Triggers {

    public static function init() {
        // Order status changes
        add_action('woocommerce_order_status_changed', array(__CLASS__, 'handle_order_status_change'), 10, 4);

        // Subscription events (if WooCommerce Subscriptions is active)
        if (class_exists('WC_Subscriptions')) {
            add_action('woocommerce_subscription_status_updated', array(__CLASS__, 'handle_subscription_status'), 10, 3);
            add_action('woocommerce_subscription_renewal_payment_complete', array(__CLASS__, 'handle_renewal_payment'), 10, 2);
            add_action('woocommerce_subscription_payment_failed', array(__CLASS__, 'handle_failed_payment'), 10, 2);
        }

        // Refund events
        add_action('woocommerce_order_refunded', array(__CLASS__, 'handle_refund'), 10, 2);
    }

    public static function handle_order_status_change($order_id, $from_status, $to_status, $order) {
        // Map WooCommerce statuses to our events
        $status_map = array(
            'completed' => 'payment.completed',
            'processing' => 'payment.processing',
            'on-hold' => 'payment.pending',
            'failed' => 'payment.failed',
            'cancelled' => 'payment.failed',
            'refunded' => 'payment.refunded'
        );

        $event = $status_map[$to_status] ?? null;

        if (!$event) {
            return;
        }

        // Check if this order was created from a Super Form
        $form_id = $order->get_meta('_super_form_id');
        $entry_id = $order->get_meta('_super_entry_id');

        if (!$form_id) {
            return; // Not a Super Forms order
        }

        $event_data = array(
            'gateway' => 'woocommerce',
            'order_id' => $order_id,
            'order_number' => $order->get_order_number(),
            'transaction_id' => $order->get_transaction_id(),
            'from_status' => $from_status,
            'to_status' => $to_status,
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'customer_id' => $order->get_customer_id(),
            'customer_email' => $order->get_billing_email(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'order_items' => self::get_order_items($order),
            'created_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'completed_date' => $to_status === 'completed' ? current_time('mysql') : null
        );

        // Get triggers for this event
        $triggers = SUPER_Trigger_DAL::get_triggers_by_event($event, $form_id);

        foreach ($triggers as $trigger) {
            $executor = new SUPER_Trigger_Executor();
            $executor->execute_trigger($trigger['id'], $event_data);
        }

        // Log the event
        self::log_woo_event($order_id, $event, $event_data);
    }

    public static function handle_subscription_status($subscription, $new_status, $old_status) {
        // Map subscription statuses to events
        $status_map = array(
            'active' => 'subscription.activated',
            'on-hold' => 'subscription.suspended',
            'cancelled' => 'subscription.cancelled',
            'expired' => 'subscription.expired',
            'pending-cancel' => 'subscription.cancelled' // Will cancel at period end
        );

        $event = $status_map[$new_status] ?? null;

        if (!$event) {
            return;
        }

        $parent_order = $subscription->get_parent();
        $form_id = $parent_order ? $parent_order->get_meta('_super_form_id') : null;
        $entry_id = $parent_order ? $parent_order->get_meta('_super_entry_id') : null;

        if (!$form_id) {
            return;
        }

        $event_data = array(
            'gateway' => 'woocommerce_subscriptions',
            'subscription_id' => $subscription->get_id(),
            'parent_order_id' => $subscription->get_parent_id(),
            'old_status' => $old_status,
            'new_status' => $new_status,
            'customer_id' => $subscription->get_customer_id(),
            'customer_email' => $subscription->get_billing_email(),
            'next_payment_date' => $subscription->get_date('next_payment'),
            'end_date' => $subscription->get_date('end'),
            'trial_end_date' => $subscription->get_date('trial_end'),
            'billing_period' => $subscription->get_billing_period(),
            'billing_interval' => $subscription->get_billing_interval(),
            'recurring_total' => $subscription->get_total(),
            'form_id' => $form_id,
            'entry_id' => $entry_id
        );

        // Execute triggers
        $triggers = SUPER_Trigger_DAL::get_triggers_by_event($event, $form_id);

        foreach ($triggers as $trigger) {
            $executor = new SUPER_Trigger_Executor();
            $executor->execute_trigger($trigger['id'], $event_data);
        }
    }

    public static function handle_refund($order_id, $refund_id) {
        $order = wc_get_order($order_id);
        $refund = wc_get_order($refund_id);

        if (!$order || !$refund) {
            return;
        }

        $form_id = $order->get_meta('_super_form_id');
        $entry_id = $order->get_meta('_super_entry_id');

        if (!$form_id) {
            return;
        }

        $event_data = array(
            'gateway' => 'woocommerce',
            'order_id' => $order_id,
            'refund_id' => $refund_id,
            'refund_amount' => $refund->get_amount(),
            'refund_reason' => $refund->get_reason(),
            'refunded_by' => $refund->get_refunded_by(),
            'total_refunded' => $order->get_total_refunded(),
            'remaining_amount' => $order->get_total() - $order->get_total_refunded(),
            'is_full_refund' => $order->get_total() == $order->get_total_refunded(),
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'customer_email' => $order->get_billing_email(),
            'refund_date' => current_time('mysql')
        );

        // Determine event type
        $event = $event_data['is_full_refund'] ? 'payment.refunded' : 'payment.partially_refunded';

        // Execute triggers
        $triggers = SUPER_Trigger_DAL::get_triggers_by_event($event, $form_id);

        foreach ($triggers as $trigger) {
            $executor = new SUPER_Trigger_Executor();
            $executor->execute_trigger($trigger['id'], $event_data);
        }
    }

    private static function get_order_items($order) {
        $items = array();

        foreach ($order->get_items() as $item) {
            $items[] = array(
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'subtotal' => $item->get_subtotal(),
                'total' => $item->get_total()
            );
        }

        return $items;
    }

    private static function log_woo_event($order_id, $event, $data) {
        // Store event in order meta for debugging
        $order = wc_get_order($order_id);
        if ($order) {
            $events = $order->get_meta('_super_trigger_events') ?: array();
            $events[] = array(
                'event' => $event,
                'timestamp' => current_time('mysql'),
                'data' => $data
            );
            $order->update_meta_data('_super_trigger_events', $events);
            $order->save();
        }
    }
}
```

### Step 5: Payment Actions

**File:** `/src/includes/actions/class-payment-actions.php` (new file)

Create payment-specific actions:

```php
class SUPER_Payment_Actions {

    public static function register($registry) {
        $registry->register_action(new SUPER_Create_Invoice_Action());
        $registry->register_action(new SUPER_Send_Payment_Receipt_Action());
        $registry->register_action(new SUPER_Update_Customer_Status_Action());
        $registry->register_action(new SUPER_Cancel_Subscription_Action());
        $registry->register_action(new SUPER_Apply_Coupon_Action());
    }
}

// Example: Send Payment Receipt Action
class SUPER_Send_Payment_Receipt_Action extends SUPER_Trigger_Action_Base {

    public function get_id() {
        return 'send_payment_receipt';
    }

    public function get_label() {
        return __('Send Payment Receipt', 'super-forms');
    }

    public function get_group() {
        return __('Payments', 'super-forms');
    }

    public function get_settings_schema() {
        return array(
            'recipient_email' => array(
                'type' => 'text',
                'label' => __('Recipient Email', 'super-forms'),
                'placeholder' => '{customer_email}',
                'required' => true
            ),
            'email_template' => array(
                'type' => 'select',
                'label' => __('Email Template', 'super-forms'),
                'options' => $this->get_email_templates(),
                'default' => 'default_receipt'
            ),
            'include_invoice' => array(
                'type' => 'checkbox',
                'label' => __('Attach Invoice PDF', 'super-forms'),
                'default' => true
            ),
            'additional_recipients' => array(
                'type' => 'textarea',
                'label' => __('CC Recipients', 'super-forms'),
                'description' => __('One email per line', 'super-forms')
            )
        );
    }

    public function execute($data, $config, $context) {
        // Build receipt email
        $to = $this->replace_tags($config['recipient_email'], $data, $context);

        $receipt_data = array(
            'transaction_id' => $data['transaction_id'] ?? '',
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'payment_method' => $data['payment_method_title'] ?? $data['payment_method'] ?? '',
            'date' => $data['completed_date'] ?? current_time('mysql'),
            'customer_name' => $data['customer_name'] ?? '',
            'customer_email' => $data['customer_email'] ?? $to,
            'items' => $data['order_items'] ?? array()
        );

        // Get email template
        $template = $this->get_template($config['email_template'], $receipt_data);

        $subject = $template['subject'];
        $message = $template['message'];
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Add CC recipients
        if (!empty($config['additional_recipients'])) {
            $cc_emails = array_filter(array_map('trim', explode("\n", $config['additional_recipients'])));
            foreach ($cc_emails as $cc) {
                $headers[] = 'Cc: ' . $cc;
            }
        }

        // Generate and attach invoice if requested
        $attachments = array();
        if ($config['include_invoice'] && !empty($data['order_id'])) {
            $invoice_path = $this->generate_invoice_pdf($data['order_id'], $receipt_data);
            if ($invoice_path) {
                $attachments[] = $invoice_path;
            }
        }

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);

        // Clean up temp files
        foreach ($attachments as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        if ($sent) {
            return $this->log_result(true, __('Payment receipt sent', 'super-forms'), array(
                'recipient' => $to,
                'transaction_id' => $data['transaction_id']
            ));
        }

        return $this->log_result(false, __('Failed to send payment receipt', 'super-forms'));
    }

    private function get_template($template_id, $data) {
        // Load template and replace variables
        $templates = array(
            'default_receipt' => array(
                'subject' => __('Payment Receipt - Transaction #{transaction_id}', 'super-forms'),
                'message' => $this->get_default_receipt_html($data)
            )
        );

        return $templates[$template_id] ?? $templates['default_receipt'];
    }

    private function get_default_receipt_html($data) {
        ob_start();
        ?>
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2><?php _e('Payment Receipt', 'super-forms'); ?></h2>

            <p><?php printf(__('Dear %s,', 'super-forms'), esc_html($data['customer_name'])); ?></p>
            <p><?php _e('Thank you for your payment. Here are the details:', 'super-forms'); ?></p>

            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php _e('Transaction ID:', 'super-forms'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($data['transaction_id']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php _e('Amount:', 'super-forms'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($data['currency'] . ' ' . number_format($data['amount'], 2)); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php _e('Payment Method:', 'super-forms'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($data['payment_method']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php _e('Date:', 'super-forms'); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($data['date']); ?></td>
                </tr>
            </table>

            <?php if (!empty($data['items'])): ?>
                <h3><?php _e('Items', 'super-forms'); ?></h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 8px; border-bottom: 2px solid #333; text-align: left;"><?php _e('Item', 'super-forms'); ?></th>
                            <th style="padding: 8px; border-bottom: 2px solid #333; text-align: center;"><?php _e('Quantity', 'super-forms'); ?></th>
                            <th style="padding: 8px; border-bottom: 2px solid #333; text-align: right;"><?php _e('Total', 'super-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['items'] as $item): ?>
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($item['name']); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;"><?php echo esc_html($item['quantity']); ?></td>
                                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;"><?php echo esc_html($data['currency'] . ' ' . number_format($item['total'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p style="margin-top: 30px; color: #666; font-size: 12px;">
                <?php _e('If you have any questions about this transaction, please contact us.', 'super-forms'); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
}
```

### Step 6: Example Payment Workflows

Document common payment automation scenarios:

```php
// Example 1: Failed Payment Recovery
$failed_payment_workflow = array(
    'trigger' => array(
        'event' => 'payment.failed',
        'conditions' => array(
            array('field' => 'amount', 'operator' => '>', 'value' => '50')
        )
    ),
    'actions' => array(
        array(
            'type' => 'send_email',
            'config' => array(
                'to' => '{customer_email}',
                'subject' => 'Payment Failed - Action Required',
                'template' => 'payment_failed_notice'
            ),
            'delay' => 0
        ),
        array(
            'type' => 'update_contact_entry_status',
            'config' => array(
                'status' => 'payment_failed'
            ),
            'delay' => 0
        ),
        array(
            'type' => 'send_email',
            'config' => array(
                'to' => '{customer_email}',
                'subject' => 'Payment Reminder',
                'template' => 'payment_reminder'
            ),
            'delay' => 86400 // 24 hours
        )
    )
);

// Example 2: Subscription Lifecycle
$subscription_workflow = array(
    'trigger' => array(
        'event' => 'subscription.trial_ending',
        'conditions' => array(
            array('field' => 'trial_end', 'operator' => 'within_days', 'value' => '3')
        )
    ),
    'actions' => array(
        array(
            'type' => 'send_email',
            'config' => array(
                'to' => '{customer_email}',
                'subject' => 'Your trial is ending soon',
                'template' => 'trial_ending_notice'
            )
        ),
        array(
            'type' => 'http_request',
            'config' => array(
                'url' => 'https://crm.example.com/api/update-lead',
                'method' => 'POST',
                'body' => '{"email": "{customer_email}", "status": "trial_ending"}'
            )
        )
    )
);

// Example 3: VIP Customer Recognition
$vip_customer_workflow = array(
    'trigger' => array(
        'event' => 'payment.completed',
        'conditions' => array(
            array('field' => 'total_spent', 'operator' => '>=', 'value' => '1000')
        )
    ),
    'actions' => array(
        array(
            'type' => 'update_customer_status',
            'config' => array(
                'status' => 'vip',
                'tags' => array('high-value', 'priority-support')
            )
        ),
        array(
            'type' => 'send_email',
            'config' => array(
                'to' => '{customer_email}',
                'subject' => 'Welcome to VIP Status!',
                'template' => 'vip_welcome'
            )
        ),
        array(
            'type' => 'apply_coupon',
            'config' => array(
                'coupon_code' => 'VIP20',
                'discount' => '20%',
                'valid_for_days' => 30
            )
        )
    )
);
```

## Context Manifest
<!-- To be added by context-gathering agent -->

## User Notes
- Payment events should be reliable and not miss any transactions
- Consider PCI compliance when handling payment data
- Store minimal payment information, reference gateway for details
- Webhook endpoints must be secured and verify signatures
- Test thoroughly with sandbox/test modes before going live
- Document which events each payment gateway supports
- Consider retry logic for failed webhook deliveries

## Work Log
<!-- Updated as work progresses -->
- [2025-11-20] Subtask created with payment and subscription event implementation