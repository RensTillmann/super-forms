# Phase 12: WooCommerce Checkout Migration to Triggers

## Overview

Migrate the WooCommerce checkout functionality from the existing add-on architecture to use the triggers/actions system. This enables conditional checkout flows, logging, and integration with payment events.

## Current State Analysis

### WooCommerce Add-on Location
`/src/add-ons/super-forms-woocommerce/super-forms-woocommerce.php`

### Settings Storage
- **Meta Key**: `_woocommerce` (separate from `_super_form_settings`)
- **Retrieved via**: `SUPER_Common::get_form_woocommerce_settings($form_id)`
- **Saved via**: `SUPER_Common::save_form_woocommerce_settings($settings, $form_id)`

### Current Flow
```
Form Submission
      │
      ▼
SUPER_Ajax::submit_form()
      │
      ▼
do_action('super_before_email_success_msg_action')
      │
      ▼
SUPER_WooCommerce::before_email_success_msg()
      │
      ├─ Check $settings['_woocommerce']['checkout'] == 'true'
      │
      ├─ Process products array
      │  - Map form fields to product IDs
      │  - Map quantities from form fields
      │  - Map variations from form fields
      │  - Map custom pricing
      │
      ├─ Add to WC cart
      │  - $woocommerce->cart->add_to_cart()
      │  - Apply coupons
      │  - Store entry data in session
      │
      └─ Redirect to checkout
         - wp_redirect($woocommerce->cart->get_checkout_url())
```

### Key Settings Structure
```php
$wcs = [
    'checkout' => 'true',
    'checkout_method' => 'add_to_cart', // or 'create_order'
    'products' => [
        [
            'id' => '{product_field}',      // WC product ID or field tag
            'qty' => '{quantity_field}',    // Quantity
            'price' => '{price_field}',     // Custom price (optional)
            'variation' => '{var_field}',   // Variation ID
            'meta' => [...],                // Order item meta
        ],
    ],
    'coupons' => '{coupon_field}',
    'populate_checkout' => 'true',
    'populate_checkout_fields' => [...],
    'entry_status' => [...],              // Status mapping by order status
    'login_status' => [...],              // User login status mapping
    'conditional_checkout' => 'true',     // Skip checkout conditionally
    'conditional_field' => 'skip_cart',
    'conditional_value' => 'yes',
];
```

## Architecture

### Target State
```
Form Submission (form.submitted event)
      │
      ▼
┌─────────────────────────────────────────────────────────┐
│  Trigger: WooCommerce Checkout                          │
│  ────────────────────────────────────────────────       │
│  event_id: form.submitted                               │
│  conditions: [{field: 'skip_cart', operator: '!=',      │
│               value: 'yes'}]                            │
└─────────────────────────────────────────────────────────┘
      │
      ▼
┌─────────────────────────────────────────────────────────┐
│  Action: woocommerce.add_to_cart                        │
│  ────────────────────────────────────────────────       │
│  config: {                                              │
│    products: [...],                                     │
│    coupons: '{coupon_code}',                            │
│    redirect_to_checkout: true                           │
│  }                                                      │
└─────────────────────────────────────────────────────────┘

Order Completed (payment.woocommerce.completed event)
      │
      ▼
┌─────────────────────────────────────────────────────────┐
│  Trigger: Update Entry on Order Complete                │
│  ────────────────────────────────────────────────       │
│  event_id: payment.woocommerce.completed                │
└─────────────────────────────────────────────────────────┘
      │
      ▼
┌─────────────────────────────────────────────────────────┐
│  Action: update_entry_status                            │
│  ────────────────────────────────────────────────       │
│  config: { status: 'completed' }                        │
└─────────────────────────────────────────────────────────┘
```

### New WooCommerce Actions to Register

| Action Type | Description |
|-------------|-------------|
| `woocommerce.add_to_cart` | Add products to WC cart |
| `woocommerce.create_order` | Create WC order directly |
| `woocommerce.update_order` | Update existing order |
| `woocommerce.apply_coupon` | Apply coupon to cart/order |
| `woocommerce.update_order_status` | Change order status |
| `woocommerce.add_order_note` | Add note to order |

### New WooCommerce Events to Register

| Event ID | Description |
|----------|-------------|
| `payment.woocommerce.pending` | Order created, awaiting payment |
| `payment.woocommerce.processing` | Payment received, processing |
| `payment.woocommerce.completed` | Order completed |
| `payment.woocommerce.failed` | Payment failed |
| `payment.woocommerce.refunded` | Order refunded |
| `payment.woocommerce.cancelled` | Order cancelled |
| `payment.woocommerce.on_hold` | Order on hold |

## Migration Strategy

### Approach: Full Migration on Plugin Update

1. **Detection**: Check if form has `_woocommerce` meta with `checkout = 'true'`
2. **Conversion**: Create trigger with `woocommerce.add_to_cart` action
3. **Preserve Settings**: Keep `_woocommerce` meta for reference during transition
4. **Completion**: Mark migration complete

### Migration State Tracking
```php
$migration = get_option('superforms_woocommerce_trigger_migration', [
    'status' => 'not_started',
    'started_at' => null,
    'completed_at' => null,
    'forms_migrated' => 0,
    'forms_total' => 0,
    'failed_forms' => [],
]);
```

### Migration Logic
```php
foreach ($forms_with_woocommerce as $form_id) {
    $wcs = get_post_meta($form_id, '_woocommerce', true);

    if (empty($wcs) || $wcs['checkout'] !== 'true') {
        continue;
    }

    // Create main checkout trigger
    $trigger_id = SUPER_Trigger_DAL::create([
        'trigger_name' => 'WooCommerce Checkout',
        'scope' => 'form',
        'scope_id' => $form_id,
        'event_id' => 'form.submitted',
        'conditions' => $this->convert_conditional_checkout($wcs),
        'enabled' => true,
    ]);

    // Create add_to_cart action
    SUPER_Trigger_DAL::create_action([
        'trigger_id' => $trigger_id,
        'action_type' => 'woocommerce.add_to_cart',
        'action_config' => [
            'products' => $wcs['products'],
            'coupons' => $wcs['coupons'] ?? '',
            'redirect_to_checkout' => true,
            'populate_checkout_fields' => $wcs['populate_checkout_fields'] ?? [],
        ],
    ]);

    // Create entry status update triggers for each order status
    foreach ($wcs['entry_status'] as $mapping) {
        $status_trigger_id = SUPER_Trigger_DAL::create([
            'trigger_name' => 'Update Entry on Order ' . ucfirst($mapping['status']),
            'scope' => 'form',
            'scope_id' => $form_id,
            'event_id' => 'payment.woocommerce.' . $mapping['status'],
            'enabled' => true,
        ]);

        SUPER_Trigger_DAL::create_action([
            'trigger_id' => $status_trigger_id,
            'action_type' => 'update_entry_status',
            'action_config' => [
                'status' => $mapping['value'],
            ],
        ]);
    }
}
```

## Success Criteria

### Core Functionality
- [ ] `woocommerce.add_to_cart` action adds products to cart correctly
- [ ] Product field mapping works ({product_id}, {quantity}, etc.)
- [ ] Variation support preserved
- [ ] Custom pricing works
- [ ] Coupon application works
- [ ] Checkout field population works

### Events
- [ ] WooCommerce order status changes fire events
- [ ] Events include order_id, entry_id, form_id context
- [ ] Entry status updates based on order status

### Migration
- [ ] Forms with WooCommerce checkout migrated to triggers
- [ ] Entry status mappings converted to separate triggers
- [ ] Conditional checkout logic preserved in trigger conditions
- [ ] Backwards compatible during migration

### Logging
- [ ] Cart additions logged
- [ ] Order creation logged
- [ ] Order status changes logged
- [ ] Errors captured and reported

## Technical Implementation

### Files to Create

1. **`/src/includes/triggers/actions/class-action-woocommerce-add-to-cart.php`**
   ```php
   class SUPER_Action_WooCommerce_Add_To_Cart extends SUPER_Trigger_Action_Base {
       public function get_name() { return 'woocommerce.add_to_cart'; }
       public function get_label() { return 'Add to WooCommerce Cart'; }
       public function execute($config, $context) {
           // Port logic from SUPER_WooCommerce::before_email_success_msg()
       }
   }
   ```

2. **`/src/includes/triggers/actions/class-action-woocommerce-create-order.php`**
   - Direct order creation (skip cart)

3. **`/src/includes/class-woocommerce-trigger-migration.php`**
   - Migration orchestration
   - Settings conversion

### Files to Modify

1. **`/src/includes/triggers/class-trigger-registry.php`**
   - Register WooCommerce events
   - Register WooCommerce actions

2. **`/src/add-ons/super-forms-woocommerce/super-forms-woocommerce.php`**
   - Add event firing on order status change
   - Deprecate direct checkout hook (use trigger instead)

3. **`/src/includes/class-install.php`**
   - Add WooCommerce migration initialization

### WooCommerce Hooks to Integrate
```php
// Fire events on order status changes
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    $order = wc_get_order($order_id);
    $entry_id = $order->get_meta('_super_entry_id');
    $form_id = $order->get_meta('_super_form_id');

    SUPER_Trigger_Registry::fire_event('payment.woocommerce.' . $new_status, [
        'order_id' => $order_id,
        'entry_id' => $entry_id,
        'form_id' => $form_id,
        'old_status' => $old_status,
        'new_status' => $new_status,
        'order_total' => $order->get_total(),
        'customer_email' => $order->get_billing_email(),
    ]);
}, 10, 3);
```

### Action Config Schema for `woocommerce.add_to_cart`
```json
{
  "products": [
    {
      "id": "{product_id}",
      "qty": "{quantity}",
      "price": "",
      "variation": "{variation_id}",
      "meta": [
        {"key": "Size", "value": "{size_field}"}
      ]
    }
  ],
  "coupons": "{coupon_code}",
  "redirect_to_checkout": true,
  "populate_checkout_fields": [
    {"wc_field": "billing_first_name", "form_field": "first_name"},
    {"wc_field": "billing_email", "form_field": "email"}
  ],
  "clear_cart_first": false
}
```

## Testing Requirements

### Unit Tests
- [ ] Product mapping resolves field tags correctly
- [ ] Quantity calculations work with repeaters
- [ ] Variation ID resolution works
- [ ] Custom price override works
- [ ] Coupon application works

### Integration Tests
- [ ] Full checkout flow: form submit → cart → checkout → order
- [ ] Entry status updates when order completes
- [ ] Trigger logs show cart additions
- [ ] Migration converts settings correctly

### Manual Testing
- [ ] Test with simple products
- [ ] Test with variable products
- [ ] Test with subscription products
- [ ] Test conditional checkout skip
- [ ] Test checkout field population

## Dependencies

- Phase 1: Foundation - COMPLETE
- Phase 2: Action Scheduler - COMPLETE
- Phase 6: Payment Events (partial overlap) - COMPLETE
- WooCommerce plugin active on test site

## Notes

- WooCommerce add-on is a separate plugin (not core)
- Consider backwards compatibility for users not updating add-on
- Session handling must be preserved for cart persistence
- Checkout redirect must work with trigger architecture
- Entry status mapping is form-specific, not global
