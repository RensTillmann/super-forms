# Phase 18: Payment Architecture and License/SaaS Integration

## Status: PLANNING

## Overview

Implement a comprehensive payment storage architecture that supports:
- One-time payments and recurring subscriptions
- Multiple payment gateways (Stripe, PayPal, WooCommerce)
- Polymorphic resource linking (entries, bookings, tickets, licenses)
- Admin dashboard for payment management (view, refund, cancel)
- License/SaaS API for developers building paid products with Super Forms
- Invoice generation and download

**Industry Alignment:** Following patterns from Gravity Forms, WPForms, and WooCommerce HPOS.

## Prerequisites

- **Phase 6**: Payment events registered in trigger system
- **Phase 9**: Payment OAuth (Stripe Connect, PayPal OAuth)
- **Phase 17**: Entry DAL (entries custom table)

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      Payment Architecture Overview                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│   GATEWAYS                    STORAGE                      RESOURCES         │
│   ────────                    ───────                      ─────────         │
│   ┌─────────┐                                                                │
│   │ Stripe  │──┐         ┌──────────────────┐                               │
│   └─────────┘  │         │ wp_superforms_   │         ┌─────────────┐       │
│   ┌─────────┐  │         │ payments         │────────▶│ entries     │       │
│   │ PayPal  │──┼────────▶│                  │         └─────────────┘       │
│   └─────────┘  │         │ (gateway_id,     │         ┌─────────────┐       │
│   ┌─────────┐  │         │  resource_type,  │────────▶│ bookings    │       │
│   │ WooCom  │──┘         │  resource_id)    │         └─────────────┘       │
│   └─────────┘            └────────┬─────────┘         ┌─────────────┐       │
│                                   │                ──▶│ tickets     │       │
│                                   │                   └─────────────┘       │
│                          ┌────────▼─────────┐         ┌─────────────┐       │
│                          │ wp_superforms_   │────────▶│ licenses    │       │
│                          │ subscriptions    │         └─────────────┘       │
│                          └────────┬─────────┘                               │
│                                   │                                          │
│                          ┌────────▼─────────┐                               │
│                          │ subscription_    │                               │
│                          │ events           │                               │
│                          └──────────────────┘                               │
│                                                                              │
│   LICENSE/SAAS API                                                          │
│   ────────────────                                                          │
│   ┌──────────────────────────────────────────────────────────────┐         │
│   │ REST API: /super-forms/v1/licenses                           │         │
│   │ - Validate license key                                        │         │
│   │ - Check subscription status                                   │         │
│   │ - Activate/deactivate sites                                   │         │
│   │ - Usage tracking and limits                                   │         │
│   └──────────────────────────────────────────────────────────────┘         │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Database Schema

### Table 1: `wp_superforms_payments`

Core payment records - one row per payment transaction.

```sql
CREATE TABLE {$prefix}superforms_payments (
    -- Primary
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- External Gateway IDs
    gateway VARCHAR(50) NOT NULL,                    -- 'stripe', 'paypal', 'woocommerce', 'manual'
    gateway_payment_id VARCHAR(255) NOT NULL,        -- Stripe charge/payment_intent ID, PayPal capture ID
    gateway_customer_id VARCHAR(255) DEFAULT NULL,   -- Stripe customer ID, PayPal payer ID

    -- Polymorphic Resource Link
    resource_type VARCHAR(50) NOT NULL DEFAULT 'entry',  -- 'entry', 'booking', 'ticket', 'license', 'order'
    resource_id BIGINT(20) UNSIGNED NOT NULL,            -- Entry ID, Booking ID, License ID, etc.

    -- Form Context
    form_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED DEFAULT 0,

    -- Subscription Link (NULL for one-time payments)
    subscription_id BIGINT(20) UNSIGNED DEFAULT NULL,

    -- Payment Classification
    payment_type VARCHAR(20) NOT NULL DEFAULT 'one_time',  -- 'one_time', 'subscription', 'renewal', 'refund'
    status VARCHAR(30) NOT NULL DEFAULT 'pending',         -- 'pending', 'processing', 'completed', 'failed', 'refunded', 'partially_refunded', 'canceled'

    -- Amounts (stored in smallest currency unit, e.g., cents)
    amount INT NOT NULL,                             -- Total amount charged
    amount_refunded INT DEFAULT 0,                   -- Total amount refunded
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',      -- ISO 4217 currency code

    -- Fee tracking (for marketplace/connect scenarios)
    platform_fee INT DEFAULT 0,                      -- Platform fee amount
    net_amount INT DEFAULT NULL,                     -- Amount after fees (amount - platform_fee)

    -- Mode
    mode VARCHAR(10) NOT NULL DEFAULT 'live',        -- 'live', 'test'

    -- Timestamps
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    completed_at DATETIME DEFAULT NULL,              -- When payment succeeded
    refunded_at DATETIME DEFAULT NULL,               -- When (last) refund processed

    -- Gateway Response (JSON for debugging/auditing)
    gateway_response JSON DEFAULT NULL,

    -- Indexes
    PRIMARY KEY (id),
    UNIQUE KEY gateway_payment (gateway, gateway_payment_id),
    KEY resource (resource_type, resource_id),
    KEY subscription_id (subscription_id),
    KEY form_id (form_id),
    KEY user_id (user_id),
    KEY status (status),
    KEY payment_type (payment_type),
    KEY created_at (created_at),
    KEY mode (mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 2: `wp_superforms_payment_meta`

Extensible metadata for payments.

```sql
CREATE TABLE {$prefix}superforms_payment_meta (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id BIGINT(20) UNSIGNED NOT NULL,
    meta_key VARCHAR(255) NOT NULL,
    meta_value LONGTEXT,
    PRIMARY KEY (id),
    KEY payment_id (payment_id),
    KEY meta_key (meta_key(191)),
    KEY payment_meta (payment_id, meta_key(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Standard Meta Keys:**
| Meta Key | Purpose | Example Value |
|----------|---------|---------------|
| `_invoice_id` | Gateway invoice ID | `in_1234567890` |
| `_invoice_url` | Hosted invoice URL | `https://invoice.stripe.com/...` |
| `_receipt_url` | Receipt download URL | `https://pay.stripe.com/receipts/...` |
| `_invoice_pdf_url` | PDF invoice URL | `https://invoice.stripe.com/.../pdf` |
| `_refund_id` | Refund transaction ID | `re_1234567890` |
| `_refund_reason` | Why refund was issued | `requested_by_customer` |
| `_coupon_code` | Applied discount code | `SUMMER2024` |
| `_discount_amount` | Discount in cents | `500` |
| `_tax_amount` | Tax charged in cents | `299` |
| `_shipping_amount` | Shipping in cents | `0` |
| `_billing_name` | Customer billing name | `John Doe` |
| `_billing_email` | Customer email | `john@example.com` |
| `_wc_order_id` | WooCommerce order ID | `12345` |
| `_failure_code` | Payment failure code | `card_declined` |
| `_failure_message` | Failure description | `Your card was declined.` |

### Table 3: `wp_superforms_subscriptions`

Recurring payment subscriptions.

```sql
CREATE TABLE {$prefix}superforms_subscriptions (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Gateway IDs
    gateway VARCHAR(50) NOT NULL,                        -- 'stripe', 'paypal'
    gateway_subscription_id VARCHAR(255) NOT NULL,       -- sub_xxx, I-xxx
    gateway_customer_id VARCHAR(255) NOT NULL,           -- cus_xxx, PAYER-xxx
    gateway_price_id VARCHAR(255) DEFAULT NULL,          -- Stripe price ID (price_xxx)

    -- Polymorphic Resource Link
    resource_type VARCHAR(50) NOT NULL DEFAULT 'entry',  -- 'entry', 'license', 'membership'
    resource_id BIGINT(20) UNSIGNED NOT NULL,

    -- Context
    form_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED DEFAULT 0,

    -- Plan Details
    plan_name VARCHAR(255) DEFAULT NULL,                 -- Human-readable plan name
    plan_id VARCHAR(255) DEFAULT NULL,                   -- Internal plan identifier
    amount INT NOT NULL,                                 -- Recurring amount in cents
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    billing_period VARCHAR(20) NOT NULL,                 -- 'day', 'week', 'month', 'year'
    billing_interval INT NOT NULL DEFAULT 1,             -- Every X periods

    -- Status
    status VARCHAR(30) NOT NULL DEFAULT 'active',        -- See status table below

    -- Trial Period
    trial_ends_at DATETIME DEFAULT NULL,

    -- Billing Cycle
    current_period_start DATETIME DEFAULT NULL,
    current_period_end DATETIME DEFAULT NULL,

    -- Lifecycle Timestamps
    started_at DATETIME DEFAULT NULL,                    -- When subscription became active
    canceled_at DATETIME DEFAULT NULL,                   -- When cancellation requested
    ended_at DATETIME DEFAULT NULL,                      -- When subscription fully ended
    paused_at DATETIME DEFAULT NULL,                     -- When subscription paused

    -- Statistics
    renewal_count INT DEFAULT 0,                         -- Total successful renewals
    failed_payment_count INT DEFAULT 0,                  -- Failed payment attempts

    -- Cancellation Details
    cancel_at_period_end TINYINT(1) DEFAULT 0,          -- Cancel at end of period vs immediate
    cancellation_reason VARCHAR(255) DEFAULT NULL,       -- Why canceled

    -- Mode
    mode VARCHAR(10) NOT NULL DEFAULT 'live',

    -- Timestamps
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY gateway_subscription (gateway, gateway_subscription_id),
    KEY resource (resource_type, resource_id),
    KEY user_id (user_id),
    KEY form_id (form_id),
    KEY status (status),
    KEY current_period_end (current_period_end),
    KEY mode (mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Subscription Status Values:**
| Status | Description | Webhook Trigger |
|--------|-------------|-----------------|
| `incomplete` | Initial payment pending | `customer.subscription.created` |
| `incomplete_expired` | Initial payment failed | `customer.subscription.updated` |
| `trialing` | In trial period | `customer.subscription.created` |
| `active` | Subscription is active | `customer.subscription.updated` |
| `past_due` | Payment failed, retrying | `invoice.payment_failed` |
| `unpaid` | All retries exhausted | `customer.subscription.updated` |
| `canceled` | Canceled (immediate) | `customer.subscription.deleted` |
| `paused` | Temporarily paused | `customer.subscription.paused` |

### Table 4: `wp_superforms_subscription_events`

Subscription lifecycle audit trail.

```sql
CREATE TABLE {$prefix}superforms_subscription_events (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    subscription_id BIGINT(20) UNSIGNED NOT NULL,

    -- Event Details
    event_type VARCHAR(50) NOT NULL,                     -- See event types below
    event_source VARCHAR(50) DEFAULT 'webhook',          -- 'webhook', 'admin', 'api', 'cron'

    -- Related Payment (for renewal/payment events)
    payment_id BIGINT(20) UNSIGNED DEFAULT NULL,

    -- Event Data (gateway-specific payload excerpt)
    event_data JSON DEFAULT NULL,

    -- Actor (who initiated, if applicable)
    actor_id BIGINT(20) UNSIGNED DEFAULT NULL,           -- User ID if admin action
    actor_type VARCHAR(20) DEFAULT NULL,                 -- 'user', 'system', 'gateway'

    created_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    KEY subscription_id (subscription_id),
    KEY event_type (event_type),
    KEY created_at (created_at),
    KEY payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Event Types:**
| Event Type | Description |
|------------|-------------|
| `created` | Subscription created |
| `activated` | Moved to active status |
| `trial_started` | Trial period began |
| `trial_ended` | Trial period ended |
| `renewed` | Successful renewal payment |
| `payment_failed` | Renewal payment failed |
| `payment_retried` | Payment retry attempted |
| `updated` | Plan/amount changed |
| `upgraded` | Upgraded to higher plan |
| `downgraded` | Downgraded to lower plan |
| `paused` | Subscription paused |
| `resumed` | Subscription resumed |
| `canceled` | Cancellation initiated |
| `expired` | Subscription ended |
| `reactivated` | Reactivated after cancel |

### Table 5: `wp_superforms_licenses` (SaaS/License System)

License keys for SaaS/software products sold via Super Forms.

```sql
CREATE TABLE {$prefix}superforms_licenses (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- License Key
    license_key VARCHAR(255) NOT NULL,                   -- Unique license key (e.g., SF-XXXX-XXXX-XXXX-XXXX)
    license_secret VARCHAR(255) DEFAULT NULL,            -- Hashed secret for API auth (optional)

    -- Product Reference
    product_id VARCHAR(100) NOT NULL,                    -- Your product identifier
    product_name VARCHAR(255) DEFAULT NULL,              -- Human-readable product name
    product_variant VARCHAR(100) DEFAULT NULL,           -- 'basic', 'pro', 'enterprise'

    -- Customer Info
    user_id BIGINT(20) UNSIGNED DEFAULT 0,               -- WordPress user ID (if registered)
    customer_email VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255) DEFAULT NULL,

    -- Payment Link
    payment_id BIGINT(20) UNSIGNED DEFAULT NULL,         -- Initial payment
    subscription_id BIGINT(20) UNSIGNED DEFAULT NULL,    -- Linked subscription (if recurring)

    -- License Type
    license_type VARCHAR(30) NOT NULL DEFAULT 'perpetual', -- 'perpetual', 'subscription', 'trial', 'free'

    -- Status
    status VARCHAR(30) NOT NULL DEFAULT 'active',        -- 'active', 'expired', 'suspended', 'revoked'

    -- Validity Period
    valid_from DATETIME NOT NULL,
    valid_until DATETIME DEFAULT NULL,                   -- NULL = perpetual

    -- Activation Limits
    max_activations INT DEFAULT 1,                       -- Max sites/instances
    current_activations INT DEFAULT 0,                   -- Current active count

    -- Features/Limits (JSON for flexibility)
    features JSON DEFAULT NULL,                          -- {"api_calls": 10000, "storage_gb": 5}

    -- Usage Tracking
    last_validated_at DATETIME DEFAULT NULL,
    last_validated_ip VARCHAR(45) DEFAULT NULL,
    validation_count INT DEFAULT 0,

    -- Timestamps
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY license_key (license_key),
    KEY product_id (product_id),
    KEY customer_email (customer_email),
    KEY subscription_id (subscription_id),
    KEY status (status),
    KEY valid_until (valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table 6: `wp_superforms_license_activations`

Track where licenses are activated (sites/domains).

```sql
CREATE TABLE {$prefix}superforms_license_activations (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id BIGINT(20) UNSIGNED NOT NULL,

    -- Activation Details
    site_url VARCHAR(255) NOT NULL,                      -- https://example.com
    site_name VARCHAR(255) DEFAULT NULL,                 -- Site title
    activation_token VARCHAR(255) NOT NULL,              -- Unique token for this activation

    -- Environment Info
    ip_address VARCHAR(45) DEFAULT NULL,
    php_version VARCHAR(20) DEFAULT NULL,
    wp_version VARCHAR(20) DEFAULT NULL,
    product_version VARCHAR(20) DEFAULT NULL,            -- Version of your product

    -- Status
    status VARCHAR(20) NOT NULL DEFAULT 'active',        -- 'active', 'deactivated'

    -- Timestamps
    activated_at DATETIME NOT NULL,
    deactivated_at DATETIME DEFAULT NULL,
    last_check_at DATETIME DEFAULT NULL,                 -- Last validation ping

    PRIMARY KEY (id),
    UNIQUE KEY activation_token (activation_token),
    KEY license_id (license_id),
    KEY site_url (site_url(191)),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Data Access Layer Classes

### SUPER_Payment_DAL

```php
class SUPER_Payment_DAL {
    // CRUD Operations
    public static function create( array $data ): int|WP_Error;
    public static function get( int $payment_id ): object|null;
    public static function update( int $payment_id, array $data ): bool|WP_Error;
    public static function delete( int $payment_id ): bool;

    // Query Methods
    public static function query( array $args = array() ): array;
    public static function count( array $args = array() ): int;
    public static function get_by_gateway_id( string $gateway, string $gateway_payment_id ): object|null;
    public static function get_by_resource( string $resource_type, int $resource_id ): array;
    public static function get_by_subscription( int $subscription_id ): array;

    // Meta Methods
    public static function get_meta( int $payment_id, string $key, bool $single = true );
    public static function update_meta( int $payment_id, string $key, $value ): bool;
    public static function delete_meta( int $payment_id, string $key ): bool;

    // Status Operations
    public static function mark_completed( int $payment_id, array $data = array() ): bool;
    public static function mark_failed( int $payment_id, string $reason = '' ): bool;
    public static function process_refund( int $payment_id, int $amount, string $reason = '' ): bool|WP_Error;

    // Aggregation
    public static function get_total_revenue( array $args = array() ): int;
    public static function get_revenue_by_period( string $period, array $args = array() ): array;
}
```

### SUPER_Subscription_DAL

```php
class SUPER_Subscription_DAL {
    // CRUD Operations
    public static function create( array $data ): int|WP_Error;
    public static function get( int $subscription_id ): object|null;
    public static function update( int $subscription_id, array $data ): bool|WP_Error;

    // Query Methods
    public static function query( array $args = array() ): array;
    public static function get_by_gateway_id( string $gateway, string $gateway_subscription_id ): object|null;
    public static function get_by_user( int $user_id ): array;
    public static function get_active_for_resource( string $resource_type, int $resource_id ): object|null;

    // Status Operations
    public static function activate( int $subscription_id ): bool;
    public static function pause( int $subscription_id, string $reason = '' ): bool;
    public static function resume( int $subscription_id ): bool;
    public static function cancel( int $subscription_id, bool $at_period_end = true, string $reason = '' ): bool;

    // Event Logging
    public static function log_event( int $subscription_id, string $event_type, array $data = array() ): int;
    public static function get_events( int $subscription_id, array $args = array() ): array;

    // Renewal
    public static function record_renewal( int $subscription_id, int $payment_id ): bool;
    public static function record_failed_payment( int $subscription_id, string $reason ): bool;

    // Statistics
    public static function get_mrr(): int;  // Monthly Recurring Revenue
    public static function get_churn_rate( string $period = 'month' ): float;
}
```

### SUPER_License_DAL

```php
class SUPER_License_DAL {
    // CRUD Operations
    public static function create( array $data ): int|WP_Error;
    public static function get( int $license_id ): object|null;
    public static function get_by_key( string $license_key ): object|null;
    public static function update( int $license_id, array $data ): bool|WP_Error;

    // License Key Generation
    public static function generate_key( string $prefix = 'SF' ): string;
    public static function generate_secret(): string;

    // Validation
    public static function validate( string $license_key, string $site_url = '' ): array;
    public static function is_valid( string $license_key ): bool;
    public static function check_feature( string $license_key, string $feature ): bool;

    // Activation Management
    public static function activate( string $license_key, string $site_url, array $meta = array() ): array|WP_Error;
    public static function deactivate( string $license_key, string $site_url ): bool|WP_Error;
    public static function get_activations( int $license_id ): array;
    public static function get_activation_count( int $license_id ): int;

    // Status Operations
    public static function suspend( int $license_id, string $reason = '' ): bool;
    public static function revoke( int $license_id, string $reason = '' ): bool;
    public static function reactivate( int $license_id ): bool;

    // Query Methods
    public static function query( array $args = array() ): array;
    public static function get_by_customer_email( string $email ): array;
    public static function get_by_subscription( int $subscription_id ): array;
    public static function get_expiring_soon( int $days = 30 ): array;
}
```

## REST API Endpoints

### Payment Endpoints

```
GET    /wp-json/super-forms/v1/payments                    # List payments
GET    /wp-json/super-forms/v1/payments/{id}               # Get payment
POST   /wp-json/super-forms/v1/payments/{id}/refund        # Process refund
GET    /wp-json/super-forms/v1/payments/stats              # Revenue statistics

GET    /wp-json/super-forms/v1/subscriptions               # List subscriptions
GET    /wp-json/super-forms/v1/subscriptions/{id}          # Get subscription
POST   /wp-json/super-forms/v1/subscriptions/{id}/cancel   # Cancel subscription
POST   /wp-json/super-forms/v1/subscriptions/{id}/pause    # Pause subscription
POST   /wp-json/super-forms/v1/subscriptions/{id}/resume   # Resume subscription
GET    /wp-json/super-forms/v1/subscriptions/{id}/events   # Subscription history
```

### License API Endpoints (Public - for external product validation)

```
POST   /wp-json/super-forms/v1/licenses/validate           # Validate license key
POST   /wp-json/super-forms/v1/licenses/activate           # Activate on site
POST   /wp-json/super-forms/v1/licenses/deactivate         # Deactivate from site
GET    /wp-json/super-forms/v1/licenses/check              # Quick validity check
POST   /wp-json/super-forms/v1/licenses/refresh            # Refresh license data

# Admin endpoints (requires authentication)
GET    /wp-json/super-forms/v1/admin/licenses              # List all licenses
GET    /wp-json/super-forms/v1/admin/licenses/{id}         # Get license details
POST   /wp-json/super-forms/v1/admin/licenses              # Create license manually
PUT    /wp-json/super-forms/v1/admin/licenses/{id}         # Update license
DELETE /wp-json/super-forms/v1/admin/licenses/{id}         # Revoke license
```

## Subtasks

### Phase 18a: Core Payment Tables and DAL
**File:** `18a-payment-tables-dal.md`

- [ ] Add payment tables to `class-install.php`
- [ ] Create `SUPER_Payment_DAL` class
- [ ] Create `SUPER_Subscription_DAL` class
- [ ] Update webhook handlers to use Payment DAL
- [ ] Migrate existing payment meta from entry_meta
- [ ] Unit tests for Payment DAL (40+ tests)
- [ ] Unit tests for Subscription DAL (30+ tests)

### Phase 18b: Admin Payment Dashboard
**File:** `18b-payment-dashboard.md`

- [ ] Create payment list table (`class-payments-list-table.php`)
- [ ] Create subscription list table (`class-subscriptions-list-table.php`)
- [ ] Single payment view with timeline
- [ ] Refund processing UI
- [ ] Subscription management UI (cancel, pause, resume)
- [ ] Invoice download integration
- [ ] Payment analytics overview

### Phase 18c: License System and SaaS API
**File:** `18c-license-saas-api.md`

- [ ] Create license tables
- [ ] Create `SUPER_License_DAL` class
- [ ] License key generation algorithm
- [ ] REST API endpoints for validation
- [ ] Activation/deactivation flow
- [ ] License admin dashboard
- [ ] Auto-expire subscription licenses
- [ ] Developer documentation

### Phase 18d: Invoice Generation
**File:** `18d-invoice-generation.md`

- [ ] Invoice template system
- [ ] PDF generation (using existing PDF library)
- [ ] Invoice storage and retrieval
- [ ] Email invoice delivery
- [ ] Invoice numbering system
- [ ] Tax calculation integration

## Integration with Existing Systems

### Entry Integration

```php
// When creating entry with payment
$entry_id = SUPER_Entry_DAL::create([
    'form_id' => $form_id,
    'title' => 'Order #123',
    'entry_status' => 'pending_payment',
]);

$payment_id = SUPER_Payment_DAL::create([
    'gateway' => 'stripe',
    'gateway_payment_id' => $stripe_payment_intent_id,
    'resource_type' => 'entry',
    'resource_id' => $entry_id,
    'amount' => 2999,
    'status' => 'pending',
]);

// When payment completes (webhook)
SUPER_Payment_DAL::mark_completed($payment_id);
SUPER_Entry_DAL::update($entry_id, ['entry_status' => 'paid']);
```

### Trigger Integration

```php
// Payment triggers already defined in Phase 6
// Payment DAL fires these events:

do_action('super_payment_completed', $payment_id, $payment);
do_action('super_payment_failed', $payment_id, $payment, $error);
do_action('super_payment_refunded', $payment_id, $payment, $refund_amount);
do_action('super_subscription_created', $subscription_id, $subscription);
do_action('super_subscription_canceled', $subscription_id, $subscription);
do_action('super_subscription_renewed', $subscription_id, $payment_id);
```

## Success Criteria

- [ ] All payment data stored in dedicated tables (not entry_meta)
- [ ] Full payment lifecycle tracked (pending → completed → refunded)
- [ ] Subscription management from WordPress admin
- [ ] License validation API response time < 100ms
- [ ] Zero data loss during webhook processing
- [ ] Admin can process refunds without visiting Stripe/PayPal
- [ ] Invoice download available for all completed payments
- [ ] License system supports 10,000+ activations efficiently

## Documentation References

- **Epics & User Flows:** See [18-payment-epics.md](18-payment-epics.md)
- **Phase 6:** Payment events in trigger system
- **Phase 9:** Payment OAuth implementation
- **Phase 17:** Entry DAL patterns to follow
